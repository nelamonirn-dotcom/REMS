<?php
session_start();
if (!isset($_SESSION['email'])) {
  header('Location: ../../backend/admin/index.php');
  exit;
}
if (($_SESSION['role'] ?? '') !== 'customer') {
  header('Location: ../../backend/admin/unauthorize.php');
  exit;
}

include_once __DIR__ . '/../../backend/admin/dbconfig.php';
require_once __DIR__ . '/sslcommerz_helper.php';

$userId = (int)($_SESSION['user_id'] ?? 0);
$customerStmt = $conn->prepare('SELECT c.customer_id, c.address, u.phone
                                FROM customers c
                                JOIN users u ON u.user_id = c.user_id
                                WHERE c.user_id = ?
                                LIMIT 1');
$customerStmt->bind_param('i', $userId);
$customerStmt->execute();
$customer = $customerStmt->get_result()->fetch_assoc() ?: [];
$customerId = (int)($customer['customer_id'] ?? 0);
$customerPhone = (string)($customer['phone'] ?? '');
$customerAddress = trim((string)($customer['address'] ?? ''));

if (empty($_SESSION['csrf'])) {
  $_SESSION['csrf'] = bin2hex(random_bytes(16));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $csrf = $_POST['csrf'] ?? '';
  if (!is_string($csrf) || !hash_equals($_SESSION['csrf'], $csrf)) {
    die('Invalid request.');
  }

  $paymentIdInput = $_POST['payment_id'] ?? '';
  $phoneInput = $_POST['phone'] ?? '';
  $paymentId = is_scalar($paymentIdInput) ? (int)$paymentIdInput : 0;
  $phone = is_string($phoneInput) ? preg_replace('/\D+/', '', $phoneInput) : '';
  if ($paymentId < 1 || strlen($phone) < 10 || strlen($phone) > 15) {
    header('Location: payments.php?result=invalid');
    exit;
  }

  try {
    $config = sslcommerzConfig();
  } catch (RuntimeException $e) {
    error_log($e->getMessage());
    header('Location: payments.php?result=not_configured');
    exit;
  }

  $conn->begin_transaction();
  $paymentStmt = $conn->prepare("SELECT pay.payment_id, pay.amount, pay.due_date, pay.status,
                                        pay.transaction_ref, pay.payment_type,
                                        p.title, u.name AS customer_name, u.email AS customer_email
                                 FROM payments pay
                                 JOIN agreements a ON a.agreement_id = pay.agreement_id
                                 JOIN properties p ON p.property_id = a.property_id
                                 JOIN customers c ON c.customer_id = a.customer_id
                                 JOIN users u ON u.user_id = c.user_id
                                 WHERE pay.payment_id = ? AND a.customer_id = ?
                                 FOR UPDATE");
  $paymentStmt->bind_param('ii', $paymentId, $customerId);
  $paymentStmt->execute();
  $payment = $paymentStmt->get_result()->fetch_assoc();

  if (!$payment || $payment['status'] !== 'pending' || !empty($payment['transaction_ref'])) {
    $conn->rollback();
    header('Location: payments.php?result=unavailable');
    exit;
  }

  $amount = (float)$payment['amount'];
  if ($amount < 10 || $amount > 500000) {
    $conn->rollback();
    header('Location: payments.php?result=amount_limit');
    exit;
  }

  $transactionId = 'EF' . bin2hex(random_bytes(12));
  $reserve = $conn->prepare("UPDATE payments
                             SET transaction_ref = ?, payment_method = 'SSLCommerz Sandbox'
                             WHERE payment_id = ? AND status = 'pending' AND transaction_ref IS NULL");
  $reserve->bind_param('si', $transactionId, $paymentId);
  $reserve->execute();

  if ($reserve->affected_rows !== 1) {
    $conn->rollback();
    header('Location: payments.php?result=unavailable');
    exit;
  }
  $conn->commit();

  $callbackUrl = $config['base_url'] . '/frontend/user/sslcommerz_return.php';
  $ipnUrl = $config['base_url'] . '/frontend/user/sslcommerz_ipn.php';
  $payload = [
    'store_id' => $config['store_id'],
    'store_passwd' => $config['store_password'],
    'total_amount' => number_format($amount, 2, '.', ''),
    'currency' => 'BDT',
    'tran_id' => $transactionId,
    'success_url' => $callbackUrl,
    'fail_url' => $callbackUrl,
    'cancel_url' => $callbackUrl,
    'ipn_url' => $ipnUrl,
    'cus_name' => mb_substr($payment['customer_name'], 0, 50),
    'cus_email' => mb_substr($payment['customer_email'], 0, 50),
    'cus_phone' => $phone,
    'cus_add1' => mb_substr($customerAddress !== '' ? $customerAddress : 'Not provided', 0, 50),
    'cus_city' => 'Dhaka',
    'cus_postcode' => '1200',
    'cus_country' => 'Bangladesh',
    'shipping_method' => 'NO',
    'product_name' => mb_substr($payment['title'] . ' ' . ucfirst($payment['payment_type']) . ' payment', 0, 200),
    'product_category' => 'Real estate',
    'product_profile' => 'non-physical-goods',
  ];

  try {
    $gatewayResponse = sslcommerzRequest($config['gateway_url'] . '/gwprocess/v4/api.php', $payload);
    $gatewayUrl = $gatewayResponse['GatewayPageURL'] ?? '';
    if (!is_string($gatewayUrl) || !filter_var($gatewayUrl, FILTER_VALIDATE_URL)) {
      throw new RuntimeException('SSLCommerz did not return a valid checkout URL.');
    }

    $gatewayScheme = parse_url($gatewayUrl, PHP_URL_SCHEME);
    $gatewayHost = parse_url($gatewayUrl, PHP_URL_HOST);
    if ($gatewayScheme !== 'https' || $gatewayHost !== 'sandbox.sslcommerz.com') {
      throw new RuntimeException('SSLCommerz returned an unexpected checkout host.');
    }

    header('Location: ' . $gatewayUrl);
    exit;
  } catch (RuntimeException $e) {
    sslcommerzReleasePayment($conn, $transactionId);
    error_log($e->getMessage());
    header('Location: payments.php?result=gateway_error');
    exit;
  }
}

$resultMessages = [
  'success' => ['success', 'Payment completed successfully.'],
  'failed' => ['danger', 'Payment was not completed. You can try again.'],
  'cancelled' => ['secondary', 'Payment was cancelled. You can try again.'],
  'invalid' => ['warning', 'Enter a valid phone number and try again.'],
  'not_configured' => ['danger', 'Online payment is not set up yet. Copy frontend/user/payment_config.sample.php to payment_config.php and add the SSLCommerz sandbox store id and password.'],
  'unavailable' => ['warning', 'This payment is already paid or has another checkout in progress. Refresh the page and try again.'],
  'amount_limit' => ['warning', 'SSLCommerz sandbox accepts payments from ৳10 to ৳500,000 per transaction. Contact the administrator about this invoice.'],
  'gateway_error' => ['danger', 'The sandbox gateway could not start checkout. Please try again later.'],
  'verification_error' => ['danger', 'Payment could not be verified. Do not retry; contact the administrator to check the transaction.'],
];
$resultCode = is_string($_GET['result'] ?? null) ? $_GET['result'] : '';
$notice = $resultMessages[$resultCode] ?? null;

$paymentsStmt = $conn->prepare("SELECT pay.payment_id, pay.amount, pay.due_date, pay.paid_at,
                                       pay.payment_type, pay.status, pay.transaction_ref,
                                       p.title
                                FROM payments pay
                                JOIN agreements a ON a.agreement_id = pay.agreement_id
                                JOIN properties p ON p.property_id = a.property_id
                                WHERE a.customer_id = ?
                                ORDER BY pay.due_date DESC, pay.payment_id DESC");
$paymentsStmt->bind_param('i', $customerId);
$paymentsStmt->execute();
$payments = $paymentsStmt->get_result()->fetch_all(MYSQLI_ASSOC);

function paymentH($value): string
{
  return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>My payments | EstateFlow</title>
  <link rel="stylesheet" href="partials/style.css">
  <style>
    .payments-section { padding: 40px 0 60px; }
    .payment-list { display: grid; gap: 16px; }
    .payment-card { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 18px; align-items: center; padding: 22px; border: 1px solid #e3e3e3; border-radius: 14px; background: #fff; }
    .payment-card h2 { margin: 0 0 6px; font-size: 19px; }
    .payment-meta { margin: 0; color: #626b6b; font-size: 14px; line-height: 1.7; }
    .payment-total { font-size: 20px; font-weight: 700; }
    .payment-state { display: inline-block; margin-top: 8px; padding: 4px 10px; border-radius: 20px; background: #edf2f2; font-size: 12px; }
    .payment-state-paid { background: #e4f4e9; color: #186a3b; }
    .payment-form { display: flex; flex-wrap: wrap; align-items: end; gap: 8px; margin-top: 12px; }
    .payment-form label { width: 100%; font-size: 13px; }
    .payment-form input { max-width: 210px; padding: 9px 10px; border: 1px solid #cbd4d4; border-radius: 8px; }
    .payment-alert { margin-bottom: 20px; padding: 12px 16px; border-radius: 8px; background: #edf2f2; }
    .payment-alert-success { background: #e4f4e9; color: #186a3b; }
    .payment-alert-danger { background: #fdecea; color: #9b1c1c; }
    @media (max-width: 640px) { .payment-card { grid-template-columns: 1fr; } }
  </style>
</head>
<body>
  <?php include __DIR__ . '/partials/customar_header.php'; ?>
  <main class="shell payments-section">
    <div class="section-head">
      <p class="eyebrow">CUSTOMER ACCOUNT</p>
      <h1>My payments</h1>
      <p>Pay outstanding agreement invoices securely through the SSLCommerz sandbox using the available card and bKash options.</p>
    </div>

    <?php if ($notice): ?>
      <div class="payment-alert payment-alert-<?= paymentH($notice[0]) ?>" role="status"><?= paymentH($notice[1]) ?></div>
    <?php endif; ?>

    <?php if (!$payments): ?>
      <p class="payment-alert">There are no agreement invoices on your account yet.</p>
    <?php else: ?>
      <div class="payment-list">
        <?php foreach ($payments as $payment): ?>
          <?php
          $isPending = $payment['status'] === 'pending';
          $isInProgress = $isPending && !empty($payment['transaction_ref']);
          $isPayable = $isPending && !$isInProgress && (float)$payment['amount'] >= 10 && (float)$payment['amount'] <= 500000;
          $displayStatus = $payment['status'] === 'pending' && $payment['due_date'] < date('Y-m-d')
            ? 'Overdue'
            : ucfirst($payment['status']);
          ?>
          <article class="payment-card">
            <div>
              <h2><?= paymentH($payment['title']) ?></h2>
              <p class="payment-meta">
                <?= paymentH(ucfirst($payment['payment_type'])) ?> invoice · Due <?= paymentH(date('M d, Y', strtotime($payment['due_date']))) ?>
                <?php if ($payment['paid_at']): ?>
                  <br>Paid <?= paymentH(date('M d, Y', strtotime($payment['paid_at']))) ?>
                <?php endif; ?>
              </p>
              <span class="payment-state <?= $payment['status'] === 'paid' ? 'payment-state-paid' : '' ?>"><?= paymentH($isInProgress ? 'Checkout in progress' : $displayStatus) ?></span>
            </div>
            <div>
              <div class="payment-total">৳<?= paymentH(number_format((float)$payment['amount'], 2)) ?></div>
              <?php if ($isPayable): ?>
                <form class="payment-form" method="post">
                  <input type="hidden" name="csrf" value="<?= paymentH($_SESSION['csrf']) ?>">
                  <input type="hidden" name="payment_id" value="<?= (int)$payment['payment_id'] ?>">
                  <label for="phone-<?= (int)$payment['payment_id'] ?>">Phone for payment receipt</label>
                  <input id="phone-<?= (int)$payment['payment_id'] ?>" name="phone" type="tel" inputmode="tel"
                         pattern="[+0-9 ()-]{10,20}" value="<?= paymentH($customerPhone) ?>"
                         placeholder="01XXXXXXXXX" required>
                  <button class="btn-sm" type="submit">Pay with bKash / Card</button>
                </form>
              <?php elseif ($isInProgress): ?>
                <p class="payment-meta">Waiting for the gateway confirmation. Refresh this page after checkout.</p>
              <?php elseif ($isPending): ?>
                <p class="payment-meta">This invoice is outside the sandbox transaction limit.</p>
              <?php elseif ($payment['status'] === 'partial'): ?>
                <p class="payment-meta">Contact the administrator to confirm the remaining balance before paying.</p>
              <?php endif; ?>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </main>
</body>
</html>
