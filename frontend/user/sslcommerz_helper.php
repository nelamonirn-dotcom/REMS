<?php

/**
 * Returns SSLCommerz configuration array from payment_config.php or environment variables.
 */
function sslcommerzConfig(): array
{
  $file = __DIR__ . '/payment_config.php';
  $local = is_file($file) ? require $file : [];
  if (!is_array($local)) {
    $local = [];
  }

  $storeId = trim((string)($local['store_id'] ?? getenv('SSLCOMMERZ_STORE_ID')));
  $storePassword = trim((string)($local['store_password'] ?? getenv('SSLCOMMERZ_STORE_PASSWORD')));

  // Use standard SSLCommerz sandbox test credentials if not configured or left as placeholder
  if ($storeId === '' || $storeId === 'your_sandbox_store_id') {
    $storeId = 'testbox';
  }
  if ($storePassword === '' || $storePassword === 'your_sandbox_store_password') {
    $storePassword = 'qwerty';
  }

  $isSandbox = isset($local['is_sandbox']) ? (bool)$local['is_sandbox'] : true;
  $gatewayUrl = $isSandbox ? 'https://sandbox.sslcommerz.com' : 'https://securepay.sslcommerz.com';

  // Base URL
  $appBaseUrl = trim((string)($local['base_url'] ?? getenv('APP_BASE_URL')));
  if ($appBaseUrl === '' || $appBaseUrl === 'http://localhost/RESM') {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
      || ((int)($_SERVER['SERVER_PORT'] ?? 0) === 443);
    $scheme = $isHttps ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $appBaseUrl = $scheme . '://' . $host . '/REMS';
  }
  $appBaseUrl = rtrim($appBaseUrl, '/');

  // SSL certificate verification for cURL (false on localhost to avoid Windows XAMPP SSL issues)
  $sslVerify = isset($local['ssl_verify']) ? (bool)$local['ssl_verify'] : false;

  return [
    'store_id'       => $storeId,
    'store_password' => $storePassword,
    'is_sandbox'     => $isSandbox,
    'base_url'       => $appBaseUrl,
    'gateway_url'    => $gatewayUrl,
    'ssl_verify'     => $sslVerify,
  ];
}

/**
 * Executes an HTTP request to SSLCommerz API endpoints using cURL.
 */
function sslcommerzRequest(string $url, array $fields, bool $get = false): array
{
  $config = sslcommerzConfig();
  $curl = curl_init();
  if ($curl === false) {
    throw new RuntimeException('Unable to initialize the SSLCommerz request.');
  }

  $sslVerify = !empty($config['ssl_verify']);

  $options = [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CONNECTTIMEOUT => 15,
    CURLOPT_TIMEOUT => 45,
    CURLOPT_SSL_VERIFYPEER => $sslVerify,
    CURLOPT_SSL_VERIFYHOST => $sslVerify ? 2 : 0,
  ];

  if ($get) {
    $url .= (str_contains($url, '?') ? '&' : '?') . http_build_query($fields);
  } else {
    $options[CURLOPT_POST] = true;
    $options[CURLOPT_POSTFIELDS] = http_build_query($fields);
  }

  $options[CURLOPT_URL] = $url;
  curl_setopt_array($curl, $options);
  $responseBody = curl_exec($curl);
  $curlError = curl_error($curl);
  $httpStatus = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
  curl_close($curl);

  if ($responseBody === false || $httpStatus < 200 || $httpStatus >= 300) {
    throw new RuntimeException('SSLCommerz request failed (HTTP ' . $httpStatus . '): ' . $curlError);
  }

  $response = json_decode($responseBody, true);
  if (!is_array($response)) {
    throw new RuntimeException('SSLCommerz returned an invalid JSON response.');
  }

  return $response;
}

/**
 * Validates a successful payment transaction with SSLCommerz server and updates database.
 */
function sslcommerzCompletePayment(mysqli $conn, string $transactionId, string $validationId): bool
{
  $config = sslcommerzConfig();
  $paymentStmt = $conn->prepare("SELECT payment_id, amount, status
                                 FROM payments
                                 WHERE transaction_ref = ?
                                 LIMIT 1");
  $paymentStmt->bind_param('s', $transactionId);
  $paymentStmt->execute();
  $payment = $paymentStmt->get_result()->fetch_assoc();

  if (!$payment || !in_array($payment['status'], ['pending', 'paid'], true)) {
    return false;
  }

  if ($payment['status'] === 'paid') {
    return true;
  }

  // Validate transaction with SSLCommerz validation API
  $validation = sslcommerzRequest(
    $config['gateway_url'] . '/validator/api/validationserverAPI.php',
    [
      'val_id'       => $validationId,
      'store_id'     => $config['store_id'],
      'store_passwd' => $config['store_password'],
      'format'       => 'json',
    ],
    true
  );

  $actualAmount = isset($validation['amount']) ? (int)round((float)$validation['amount'] * 100) : -1;
  $expectedAmount = (int)round((float)$payment['amount'] * 100);
  $valid = in_array($validation['status'] ?? '', ['VALID', 'VALIDATED'], true)
    && hash_equals($transactionId, (string)($validation['tran_id'] ?? ''))
    && strtoupper((string)($validation['currency'] ?? '')) === 'BDT'
    && $actualAmount === $expectedAmount;

  if (!$valid) {
    return false;
  }

  // bKash / Card / Mobile Banking method name returned by gateway
  $method = substr(trim((string)($validation['card_type'] ?? '')), 0, 50);
  if ($method === '') {
    $method = 'SSLCommerz (' . ($config['is_sandbox'] ? 'Sandbox' : 'Live') . ')';
  }

  $conn->begin_transaction();
  try {
    $update = $conn->prepare("UPDATE payments
                              SET status = 'paid', paid_at = NOW(), payment_method = ?
                              WHERE payment_id = ? AND status = 'pending' AND transaction_ref = ?");
    $update->bind_param('sis', $method, $payment['payment_id'], $transactionId);
    $update->execute();
    $changed = $update->affected_rows === 1;

    if ($changed) {
      sslcommerzApplyPaidEffects($conn, (int)$payment['payment_id']);
    }
    $conn->commit();
  } catch (Throwable $e) {
    $conn->rollback();
    throw new RuntimeException('Could not record the payment: ' . $e->getMessage(), 0, $e);
  }

  return $changed;
}

/**
 * Runs once after a payment turns "paid": rent -> property rented, sale -> property sold.
 */
function sslcommerzApplyPaidEffects(mysqli $conn, int $paymentId): void
{
  $stmt = $conn->prepare("SELECT pay.payment_type, a.property_id
                          FROM payments pay
                          JOIN agreements a ON a.agreement_id = pay.agreement_id
                          WHERE pay.payment_id = ?
                          LIMIT 1");
  $stmt->bind_param('i', $paymentId);
  $stmt->execute();
  $row = $stmt->get_result()->fetch_assoc();
  if (!$row) {
    return;
  }

  $newStatus = ['rent' => 'rented', 'sale' => 'sold'][$row['payment_type']] ?? null;
  if ($newStatus === null) {
    return;
  }

  $propertyId = (int)$row['property_id'];
  $set = $conn->prepare("UPDATE properties SET status = ? WHERE property_id = ? AND status = 'available'");
  $set->bind_param('si', $newStatus, $propertyId);
  $set->execute();
}

/**
 * Releases reserved transaction ref if payment fails or is cancelled.
 */
function sslcommerzReleasePayment(mysqli $conn, string $transactionId): void
{
  $update = $conn->prepare("UPDATE payments
                            SET transaction_ref = NULL, payment_method = NULL
                            WHERE transaction_ref = ? AND status = 'pending'");
  $update->bind_param('s', $transactionId);
  $update->execute();
}
