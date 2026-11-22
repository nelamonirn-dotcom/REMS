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

// Resolve customer ID or auto-create if newly registered
$customerStmt = $conn->prepare('SELECT c.customer_id, c.address, u.phone, u.name, u.email
                                FROM customers c
                                JOIN users u ON u.user_id = c.user_id
                                WHERE c.user_id = ?
                                LIMIT 1');
$customerStmt->bind_param('i', $userId);
$customerStmt->execute();
$customer = $customerStmt->get_result()->fetch_assoc() ?: [];
$customerId = (int)($customer['customer_id'] ?? 0);

if ($customerId === 0 && $userId > 0) {
  $insCust = $conn->prepare("INSERT INTO customers (user_id, customer_type) VALUES (?, 'buyer')");
  $insCust->bind_param('i', $userId);
  $insCust->execute();
  $customerId = (int)$conn->insert_id;

  $customerStmt->execute();
  $customer = $customerStmt->get_result()->fetch_assoc() ?: [];
}

$customerPhone = (string)($customer['phone'] ?? '');
$customerAddress = trim((string)($customer['address'] ?? ''));

if (empty($_SESSION['csrf'])) {
  $_SESSION['csrf'] = bin2hex(random_bytes(16));
}

// Handle Payment Initiation (SSLCommerz)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $csrf = $_POST['csrf'] ?? '';
  if (!is_string($csrf) || !hash_equals($_SESSION['csrf'], $csrf)) {
    die('Invalid security token.');
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
    $_SESSION['payment_error_msg'] = $e->getMessage();
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

  $transactionId = 'EF' . strtoupper(bin2hex(random_bytes(8)));
  $gatewayLabel = 'SSLCommerz ' . ($config['is_sandbox'] ? 'Sandbox' : 'Live');

  $reserve = $conn->prepare("UPDATE payments
                             SET transaction_ref = ?, payment_method = ?
                             WHERE payment_id = ? AND status = 'pending' AND transaction_ref IS NULL");
  $reserve->bind_param('ssi', $transactionId, $gatewayLabel, $paymentId);
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
    'store_id'         => $config['store_id'],
    'store_passwd'     => $config['store_password'],
    'total_amount'     => number_format($amount, 2, '.', ''),
    'currency'         => 'BDT',
    'tran_id'          => $transactionId,
    'success_url'      => $callbackUrl,
    'fail_url'         => $callbackUrl,
    'cancel_url'       => $callbackUrl,
    'ipn_url'          => $ipnUrl,
    'cus_name'         => mb_substr($payment['customer_name'] ?: 'Customer', 0, 50),
    'cus_email'        => mb_substr($payment['customer_email'] ?: 'customer@example.com', 0, 50),
    'cus_phone'        => $phone,
    'cus_add1'         => mb_substr($customerAddress !== '' ? $customerAddress : 'Dhaka', 0, 50),
    'cus_city'         => 'Dhaka',
    'cus_postcode'     => '1200',
    'cus_country'      => 'Bangladesh',
    'shipping_method'  => 'NO',
    'product_name'     => mb_substr($payment['title'] . ' ' . ucfirst($payment['payment_type']) . ' payment', 0, 200),
    'product_category' => 'Real estate',
    'product_profile'  => 'non-physical-goods',
  ];

  try {
    $gatewayResponse = sslcommerzRequest($config['gateway_url'] . '/gwprocess/v4/api.php', $payload);

    if (($gatewayResponse['status'] ?? '') !== 'SUCCESS') {
      $reason = $gatewayResponse['failedreason'] ?? 'Gateway rejected payment initialization';
      throw new RuntimeException($reason);
    }

    $gatewayUrl = $gatewayResponse['GatewayPageURL'] ?? $gatewayResponse['redirectGatewayURL'] ?? '';
    if (!is_string($gatewayUrl) || !filter_var($gatewayUrl, FILTER_VALIDATE_URL)) {
      throw new RuntimeException('SSLCommerz did not return a valid checkout URL.');
    }

    // Redirect to SSLCommerz Checkout Page
    header('Location: ' . $gatewayUrl);
    exit;
  } catch (Throwable $e) {
    sslcommerzReleasePayment($conn, $transactionId);
    error_log('SSLCommerz Init Error: ' . $e->getMessage());
    $_SESSION['payment_error_msg'] = $e->getMessage();
    header('Location: payments.php?result=gateway_error');
    exit;
  }
}

$customError = $_SESSION['payment_error_msg'] ?? '';
unset($_SESSION['payment_error_msg']);

$resultMessages = [
  'success'            => ['success', 'Payment completed successfully! Your property record has been updated.'],
  'failed'             => ['danger', 'Payment was not completed. You can try again.'],
  'cancelled'          => ['secondary', 'Payment was cancelled. You can try again at any time.'],
  'cancel'             => ['secondary', 'Payment was cancelled. You can try again at any time.'],
  'invalid'            => ['warning', 'Please enter a valid phone number (10-15 digits) and try again.'],
  'not_configured'     => ['danger', 'SSLCommerz payment gateway is not configured. Please contact the administrator.'],
  'unavailable'        => ['warning', 'This payment is already paid or another checkout session is in progress.'],
  'amount_limit'       => ['warning', 'SSLCommerz accepts payments between ৳10 and ৳500,000 per transaction.'],
  'gateway_error'      => ['danger', 'SSLCommerz Gateway Error: ' . ($customError ?: 'Unable to start checkout. Please try again later.')],
  'verification_error' => ['danger', 'Payment could not be verified by SSLCommerz: ' . ($customError ?: 'Contact support.')],
];

$resultCode = is_string($_GET['result'] ?? null) ? $_GET['result'] : '';
$notice = $resultMessages[$resultCode] ?? null;

// Fetch customer payments
$paymentsStmt = $conn->prepare("SELECT pay.payment_id, pay.amount, pay.due_date, pay.paid_at,
                                       pay.payment_type, pay.payment_method, pay.status, pay.transaction_ref,
                                       p.title, p.address AS property_address, a.agreement_id
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
  <title>My Payments | EstateFlow SSLCommerz</title>
  <link rel="stylesheet" href="partials/style.css">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet" referrerpolicy="no-referrer">
  <style>
    .payments-section { padding: 40px 0 70px; }
    .payment-badge-bar {
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      gap: 12px;
      padding: 16px 20px;
      margin-bottom: 28px;
      background: #f8fafc;
      border: 1px solid #e2e8f0;
      border-radius: 12px;
      font-size: 14px;
      color: #334155;
    }
    .payment-badge-bar strong { color: #0f172a; }
    .gateway-pill {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 5px 12px;
      background: #ffffff;
      border: 1px solid #cbd5e1;
      border-radius: 20px;
      font-size: 13px;
      font-weight: 600;
      color: #1e293b;
    }
    .gateway-pill.bkash { border-color: #e2136e; color: #e2136e; }
    .gateway-pill.nagad { border-color: #f7941d; color: #f7941d; }
    .gateway-pill.cards { border-color: #0284c7; color: #0284c7; }

    .payment-list { display: grid; gap: 20px; }
    .payment-card {
      display: grid;
      grid-template-columns: minmax(0, 1fr) auto;
      gap: 20px;
      align-items: center;
      padding: 24px;
      border: 1px solid #e2e8f0;
      border-radius: 16px;
      background: #ffffff;
      box-shadow: 0 1px 3px rgba(0,0,0,0.04);
      transition: box-shadow 0.2s ease;
    }
    .payment-card:hover {
      box-shadow: 0 4px 12px rgba(0,0,0,0.07);
    }
    .payment-card h2 { margin: 0 0 6px; font-size: 20px; color: #0f172a; }
    .payment-meta { margin: 0; color: #64748b; font-size: 14px; line-height: 1.7; }
    .payment-total { font-size: 24px; font-weight: 800; color: #0f172a; text-align: right; }
    .payment-state {
      display: inline-block;
      margin-top: 8px;
      padding: 4px 12px;
      border-radius: 20px;
      background: #f1f5f9;
      font-size: 12px;
      font-weight: 600;
      color: #475569;
    }
    .payment-state-paid { background: #dcfce7; color: #166534; }
    .payment-state-overdue { background: #fee2e2; color: #991b1b; }

    .payment-form {
      display: flex;
      flex-direction: column;
      gap: 10px;
      margin-top: 14px;
      align-items: flex-end;
    }
    .payment-form-group {
      display: flex;
      flex-direction: column;
      width: 100%;
      max-width: 260px;
    }
    .payment-form label { font-size: 12px; font-weight: 600; color: #475569; margin-bottom: 4px; }
    .payment-form input {
      width: 100%;
      padding: 10px 12px;
      border: 1px solid #cbd5e1;
      border-radius: 8px;
      font-size: 14px;
    }
    .btn-sslpay {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: #059669;
      color: #ffffff;
      padding: 10px 20px;
      border: none;
      border-radius: 8px;
      font-size: 14px;
      font-weight: 700;
      cursor: pointer;
      transition: background 0.15s ease;
    }
    .btn-sslpay:hover { background: #047857; }
    .btn-receipt {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      background: #f8fafc;
      color: #334155;
      padding: 8px 16px;
      border: 1px solid #cbd5e1;
      border-radius: 8px;
      font-size: 13px;
      font-weight: 600;
      cursor: pointer;
      margin-top: 10px;
    }
    .btn-receipt:hover { background: #e2e8f0; }

    .payment-alert {
      margin-bottom: 24px;
      padding: 14px 18px;
      border-radius: 10px;
      font-size: 14px;
      line-height: 1.6;
    }
    .payment-alert-success { background: #dcfce7; color: #15803d; border-left: 4px solid #16a34a; }
    .payment-alert-danger { background: #fee2e2; color: #b91c1c; border-left: 4px solid #dc2626; }
    .payment-alert-warning { background: #fef3c7; color: #b45309; border-left: 4px solid #d97706; }
    .payment-alert-secondary { background: #f1f5f9; color: #475569; border-left: 4px solid #94a3b8; }

    @media (max-width: 680px) {
      .payment-card { grid-template-columns: 1fr; }
      .payment-total { text-align: left; }
      .payment-form { align-items: stretch; }
    }
  </style>
</head>
<body>
  <?php include __DIR__ . '/partials/customar_header.php'; ?>

  <main class="shell payments-section">
    <div class="section-head">
      <p class="eyebrow"><i class="fa-solid fa-shield-halved"></i> SECURE ONLINE PAYMENTS</p>
      <h1>My Payments &amp; Invoices</h1>
      <p>Pay property rent, sale advances, and booking fees instantly through Bangladesh's leading payment gateway <strong>SSLCommerz</strong>.</p>
    </div>

    <!-- Gateway Branding Bar -->
    <div class="payment-badge-bar">
      <span><strong>Accepted Payment Methods:</strong></span>
      <span class="gateway-pill bkash"><i class="fa-solid fa-mobile-screen-button"></i> bKash</span>
      <span class="gateway-pill nagad"><i class="fa-solid fa-mobile-screen-button"></i> Nagad</span>
      <span class="gateway-pill"><i class="fa-solid fa-mobile-screen-button"></i> Rocket</span>
      <span class="gateway-pill"><i class="fa-solid fa-mobile-screen-button"></i> Upay</span>
      <span class="gateway-pill cards"><i class="fa-brands fa-cc-visa"></i> VISA</span>
      <span class="gateway-pill cards"><i class="fa-brands fa-cc-mastercard"></i> Mastercard</span>
      <span class="gateway-pill cards"><i class="fa-brands fa-cc-amex"></i> Amex</span>
      <span class="gateway-pill"><i class="fa-solid fa-building-columns"></i> Internet Banking</span>
    </div>

    <?php if ($notice): ?>
      <div class="payment-alert payment-alert-<?= paymentH($notice[0]) ?>" role="status">
        <i class="fa-solid <?= $notice[0] === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation' ?>"></i>
        <?= paymentH($notice[1]) ?>
      </div>
    <?php endif; ?>

    <?php if (!$payments): ?>
      <div class="payment-alert payment-alert-secondary">
        <p style="margin: 0 0 10px;"><strong>No unpaid invoices on your account.</strong></p>
        <p style="margin: 0;">Once an agent or administrator issues an agreement invoice, or when you complete a property viewing from <a href="my_bookings.php">My Bookings</a>, your payment invoice will appear here.</p>
      </div>
    <?php else: ?>
      <div class="payment-list">
        <?php foreach ($payments as $payment): ?>
          <?php
          $isPending = $payment['status'] === 'pending';
          $isInProgress = $isPending && !empty($payment['transaction_ref']);
          $isPayable = $isPending && !$isInProgress && (float)$payment['amount'] >= 10 && (float)$payment['amount'] <= 500000;
          $isOverdue = $payment['status'] === 'pending' && $payment['due_date'] < date('Y-m-d');
          $displayStatus = $isOverdue ? 'Overdue' : ucfirst($payment['status']);
          ?>
          <article class="payment-card">
            <div>
              <h2><?= paymentH($payment['title']) ?></h2>
              <p class="payment-meta">
                <strong>Invoice Type:</strong> <?= paymentH(ucfirst($payment['payment_type'])) ?> &middot;
                <strong>Due Date:</strong> <?= paymentH(date('M d, Y', strtotime($payment['due_date']))) ?>
                <?php if ($payment['paid_at']): ?>
                  <br><strong>Paid on:</strong> <?= paymentH(date('M d, Y · h:i A', strtotime($payment['paid_at']))) ?>
                  <br><strong>Method:</strong> <?= paymentH($payment['payment_method'] ?: 'SSLCommerz') ?>
                  <br><strong>Trx Ref:</strong> <code><?= paymentH($payment['transaction_ref']) ?></code>
                <?php endif; ?>
              </p>
              <span class="payment-state <?= $payment['status'] === 'paid' ? 'payment-state-paid' : ($isOverdue ? 'payment-state-overdue' : '') ?>">
                <?= paymentH($isInProgress ? 'Checkout in progress' : $displayStatus) ?>
              </span>
            </div>

            <div>
              <div class="payment-total">৳<?= paymentH(number_format((float)$payment['amount'], 2)) ?></div>

              <?php if ($isPayable): ?>
                <form class="payment-form" method="post">
                  <input type="hidden" name="csrf" value="<?= paymentH($_SESSION['csrf']) ?>">
                  <input type="hidden" name="payment_id" value="<?= (int)$payment['payment_id'] ?>">
                  <div class="payment-form-group">
                    <label for="phone-<?= (int)$payment['payment_id'] ?>">Mobile for Payment Receipt</label>
                    <input id="phone-<?= (int)$payment['payment_id'] ?>" name="phone" type="tel" inputmode="tel"
                           pattern="[+0-9 ()-]{10,20}" value="<?= paymentH($customerPhone) ?>"
                           placeholder="01XXXXXXXXX" required>
                  </div>
                  <button class="btn-sslpay" type="submit">
                    <i class="fa-solid fa-lock"></i> Pay with SSLCommerz
                  </button>
                </form>
              <?php elseif ($isInProgress): ?>
                <p class="payment-meta" style="margin-top: 10px;">Waiting for SSLCommerz confirmation.<br>
                  <a href="payments.php" style="font-weight: 600;">Refresh Status</a>
                </p>
              <?php elseif ($payment['status'] === 'paid'): ?>
                <button class="btn-receipt" type="button" onclick="window.print()">
                  <i class="fa-solid fa-print"></i> Print Receipt
                </button>
              <?php endif; ?>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </main>

  <?php include __DIR__ . '/partials/footer.php'; ?>
</body>
</html>
