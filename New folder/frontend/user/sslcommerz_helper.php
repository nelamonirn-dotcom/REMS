<?php

function sslcommerzConfig(): array
{
  // Credentials: payment_config.php (same folder) first, then environment variables.
  $file = __DIR__ . '/payment_config.php';
  $local = is_file($file) ? require $file : [];
  if (!is_array($local)) {
    $local = [];
  }

  $storeId = trim((string)($local['store_id'] ?? ''));
  $storePassword = trim((string)($local['store_password'] ?? ''));
  if ($storeId === '' || $storeId === 'your_sandbox_store_id') {
    $storeId = trim((string)getenv('SSLCOMMERZ_STORE_ID'));
  }
  if ($storePassword === '' || $storePassword === 'your_sandbox_store_password') {
    $storePassword = trim((string)getenv('SSLCOMMERZ_STORE_PASSWORD'));
  }
  $appBaseUrl = rtrim((string)($local['base_url'] ?? getenv('APP_BASE_URL')), '/');
  $urlParts = parse_url($appBaseUrl);
  $scheme = is_array($urlParts) ? (string)($urlParts['scheme'] ?? '') : '';
  $host = is_array($urlParts) ? strtolower((string)($urlParts['host'] ?? '')) : '';
  $isLocalHost = $host === 'localhost'
    || $host === '127.0.0.1'
    || preg_match('/\.(test|local|localhost)$/', $host) === 1;

  if (
    $storeId === ''
    || $storePassword === ''
    || $storeId === 'your_sandbox_store_id'
    || $storePassword === 'your_sandbox_store_password'
  ) {
    throw new RuntimeException('SSLCommerz sandbox credentials are not configured.');
  }
  if ($host === '' || !in_array($scheme, ['http', 'https'], true) || ($scheme !== 'https' && !$isLocalHost)) {
    throw new RuntimeException('base_url must be https (http is allowed only for localhost / *.test).');
  }

  return [
    'store_id' => $storeId,
    'store_password' => $storePassword,
    'base_url' => $appBaseUrl,
    'gateway_url' => 'https://sandbox.sslcommerz.com',
  ];
}

function sslcommerzRequest(string $url, array $fields, bool $get = false): array
{
  $curl = curl_init();
  if ($curl === false) {
    throw new RuntimeException('Unable to initialize the SSLCommerz request.');
  }

  $options = [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2,
  ];

  if ($get) {
    $url .= '?' . http_build_query($fields);
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
    throw new RuntimeException('SSLCommerz returned an invalid response.');
  }

  return $response;
}

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

  $validation = sslcommerzRequest(
    $config['gateway_url'] . '/validator/api/validationserverAPI.php',
    [
      'val_id' => $validationId,
      'store_id' => $config['store_id'],
      'store_passwd' => $config['store_password'],
      'format' => 'json',
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
  if ($payment['status'] === 'paid') {
    return true;
  }

  // bKash / card name comes back from the gateway (e.g. "BKASH-BKash", "VISA-Dutch Bangla").
  $method = substr(trim((string)($validation['card_type'] ?? '')), 0, 50);
  if ($method === '') {
    $method = 'SSLCommerz Sandbox';
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

// Runs once, right after a payment turns "paid": rent -> property rented, sale -> property sold.
// An "advance" payment does not change the property status.
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

function sslcommerzReleasePayment(mysqli $conn, string $transactionId): void
{
  $update = $conn->prepare("UPDATE payments
                            SET transaction_ref = NULL, payment_method = NULL
                            WHERE transaction_ref = ? AND status = 'pending'");
  $update->bind_param('s', $transactionId);
  $update->execute();
}
