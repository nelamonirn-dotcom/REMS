<?php
session_start();
if (!isset($_SESSION['email'])) {
  header('Location: index.php');
  exit;
}
if (($_SESSION['role'] ?? '') !== 'admin') {
  header('Location: unauthorize.php');
  exit;
}

include_once __DIR__ . '/dbconfig.php';

if (empty($_SESSION['csrf'])) {
  $_SESSION['csrf'] = bin2hex(random_bytes(16));
}

function invoiceRedirect(string $result): void
{
  header('Location: payment_invoice.php?result=' . urlencode($result));
  exit;
}

function invoiceDateIsValid(string $value): bool
{
  $date = DateTime::createFromFormat('!Y-m-d', $value);
  $errors = DateTime::getLastErrors();
  return $date !== false
    && $date->format('Y-m-d') === $value
    && (!is_array($errors) || ($errors['warning_count'] === 0 && $errors['error_count'] === 0));
}

function invoiceAmount(string $value): ?string
{
  if (!preg_match('/^\d{1,10}(?:\.\d{1,2})?$/', $value)) {
    return null;
  }
  $amount = (float)$value;
  return $amount > 0 && $amount <= 9999999999.99
    ? number_format($amount, 2, '.', '')
    : null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $csrf = $_POST['csrf'] ?? '';
  if (!is_string($csrf) || !hash_equals($_SESSION['csrf'], $csrf)) {
    die('Invalid request.');
  }

  $target = $_POST['target'] ?? '';
  $targetMatch = is_string($target) && preg_match('/^(booking|agreement):([1-9]\d*)$/', $target, $matches);
  $paymentType = $_POST['payment_type'] ?? '';
  $agreementType = $_POST['agreement_type'] ?? '';
  $invoiceAmount = is_string($_POST['invoice_amount'] ?? null)
    ? invoiceAmount($_POST['invoice_amount'])
    : null;
  $agreementAmount = is_string($_POST['agreement_amount'] ?? null)
    ? invoiceAmount($_POST['agreement_amount'])
    : null;
  $dueDate = $_POST['due_date'] ?? '';
  $startDate = $_POST['start_date'] ?? '';

  if (
    !$targetMatch
    || !in_array($paymentType, ['rent', 'sale', 'advance'], true)
    || $invoiceAmount === null
    || (float)$invoiceAmount < 10
    || (float)$invoiceAmount > 500000
    || !is_string($dueDate)
    || !invoiceDateIsValid($dueDate)
  ) {
    invoiceRedirect('invalid');
  }

  $targetId = (int)$matches[2];
  $targetType = $matches[1];
  $conn->begin_transaction();

  if ($targetType === 'booking') {
    $bookingStmt = $conn->prepare("SELECT b.booking_id, b.property_id, b.customer_id
                                   FROM bookings b
                                   JOIN properties p ON p.property_id = b.property_id
                                   WHERE b.booking_id = ? AND b.status = 'approved' AND p.status = 'available'
                                   FOR UPDATE");
    $bookingStmt->bind_param('i', $targetId);
    $bookingStmt->execute();
    $booking = $bookingStmt->get_result()->fetch_assoc();

    $activeAgreementStmt = $conn->prepare("SELECT agreement_id
                                           FROM agreements
                                           WHERE booking_id = ? AND status = 'active'
                                           LIMIT 1
                                           FOR UPDATE");
    $activeAgreementStmt->bind_param('i', $targetId);
    $activeAgreementStmt->execute();
    $hasActiveAgreement = $activeAgreementStmt->get_result()->num_rows > 0;

    if (!$booking || $hasActiveAgreement) {
      $conn->rollback();
      invoiceRedirect('unavailable');
    }

    $bookingIdForInterest = (int)$booking['booking_id'];
    $interestStmt = $conn->prepare("SELECT interest_type, status FROM booking_interests
                                    WHERE booking_id = ?
                                    FOR UPDATE");
    $interestStmt->bind_param('i', $bookingIdForInterest);
    $interestStmt->execute();
    $bookingInterest = $interestStmt->get_result()->fetch_assoc();
    $expectedAgreementType = ($bookingInterest['status'] ?? '') === 'pending'
      ? ($bookingInterest['interest_type'] === 'buy' ? 'sale' : 'rent')
      : null;

    if ($expectedAgreementType !== null && $agreementType !== $expectedAgreementType) {
      $conn->rollback();
      invoiceRedirect('intent_mismatch');
    }

    if (
      !in_array($agreementType, ['rent', 'sale'], true)
      || ($paymentType !== 'advance' && $paymentType !== $agreementType)
      || $agreementAmount === null
      || !is_string($startDate)
      || !invoiceDateIsValid($startDate)
    ) {
      $conn->rollback();
      invoiceRedirect('invalid');
    }

    $agreementStmt = $conn->prepare("INSERT INTO agreements
                                       (booking_id, property_id, customer_id, agreement_type, start_date, amount, status)
                                     VALUES (?, ?, ?, ?, ?, ?, 'active')");
    $agreementAmountValue = $agreementAmount;
    $agreementStmt->bind_param(
      'iiisss',
      $booking['booking_id'],
      $booking['property_id'],
      $booking['customer_id'],
      $agreementType,
      $startDate,
      $agreementAmountValue
    );
    $agreementStmt->execute();
    $agreementId = (int)$conn->insert_id;
  } else {
    $agreementStmt = $conn->prepare("SELECT a.agreement_id, a.booking_id, a.agreement_type
                                     FROM agreements a
                                     JOIN bookings b ON b.booking_id = a.booking_id
                                     WHERE a.agreement_id = ? AND a.status = 'active' AND b.status = 'approved'
                                     FOR UPDATE");
    $agreementStmt->bind_param('i', $targetId);
    $agreementStmt->execute();
    $agreement = $agreementStmt->get_result()->fetch_assoc();

    if (!$agreement || ($paymentType !== 'advance' && $paymentType !== $agreement['agreement_type'])) {
      $conn->rollback();
      invoiceRedirect($agreement ? 'type_mismatch' : 'unavailable');
    }
    $agreementId = (int)$agreement['agreement_id'];
    $bookingIdForInterest = (int)$agreement['booking_id'];

    $interestStmt = $conn->prepare("SELECT interest_type, status FROM booking_interests
                                    WHERE booking_id = ?
                                    FOR UPDATE");
    $interestStmt->bind_param('i', $bookingIdForInterest);
    $interestStmt->execute();
    $bookingInterest = $interestStmt->get_result()->fetch_assoc();
    $expectedAgreementType = ($bookingInterest['status'] ?? '') === 'pending'
      ? ($bookingInterest['interest_type'] === 'buy' ? 'sale' : 'rent')
      : null;

    if ($expectedAgreementType !== null && $agreement['agreement_type'] !== $expectedAgreementType) {
      $conn->rollback();
      invoiceRedirect('intent_mismatch');
    }
  }

  $paymentStmt = $conn->prepare("INSERT INTO payments
                                  (agreement_id, amount, due_date, payment_type, status)
                                VALUES (?, ?, ?, ?, 'pending')");
  $paymentStmt->bind_param('idss', $agreementId, $invoiceAmount, $dueDate, $paymentType);
  $paymentStmt->execute();

  $invoiceInterest = $conn->prepare("UPDATE booking_interests
                                     SET status = 'invoiced'
                                     WHERE booking_id = ? AND status = 'pending'");
  $invoiceInterest->bind_param('i', $bookingIdForInterest);
  $invoiceInterest->execute();
  $conn->commit();
  invoiceRedirect('created');
}

$targets = [];
$targetResult = $conn->query("SELECT b.booking_id, a.agreement_id, a.agreement_type,
                                     p.title, u.name AS customer_name,
                                     bi.interest_type, bi.status AS interest_status
                              FROM bookings b
                              JOIN properties p ON p.property_id = b.property_id
                              JOIN customers c ON c.customer_id = b.customer_id
                              JOIN users u ON u.user_id = c.user_id
                              LEFT JOIN agreements a ON a.booking_id = b.booking_id AND a.status = 'active'
                              LEFT JOIN booking_interests bi ON bi.booking_id = b.booking_id
                              WHERE b.status = 'approved' AND (a.agreement_id IS NOT NULL OR p.status = 'available')
                              ORDER BY u.name, p.title, a.agreement_id");
while ($target = $targetResult->fetch_assoc()) {
  $interestLabel = !empty($target['interest_type'])
    ? ' · Customer wants to ' . $target['interest_type']
    : '';
  if ($target['agreement_id'] === null) {
    $targets[] = [
      'value' => 'booking:' . (int)$target['booking_id'],
      'label' => $target['customer_name'] . ' · ' . $target['title'] . ' · New agreement' . $interestLabel,
    ];
  } else {
    $targets[] = [
      'value' => 'agreement:' . (int)$target['agreement_id'],
      'label' => $target['customer_name'] . ' · ' . $target['title'] . ' · Agreement #' . (int)$target['agreement_id'] . ' (' . ucfirst($target['agreement_type']) . ')' . $interestLabel,
    ];
  }
}

$messages = [
  'created' => ['success', 'Invoice created. The customer can now pay it from My payments.'],
  'invalid' => ['danger', 'Check the agreement, payment type, amount and dates. Invoice amount must be ৳10–৳500,000.'],
  'unavailable' => ['warning', 'That approved booking or active agreement is no longer available. Refresh and try again.'],
  'type_mismatch' => ['warning', 'The payment type must match the active agreement type, or be an advance.'],
  'intent_mismatch' => ['warning', 'The agreement type must match the customer’s buy/rent request.'],
];
$resultCode = is_string($_GET['result'] ?? null) ? $_GET['result'] : '';
$notice = $messages[$resultCode] ?? null;
$escape = static fn($value): string => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#e8f5e9">
  <title>Create payment invoice | EstateFlow Admin</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet" referrerpolicy="no-referrer">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../shared/assets/css/global.css">
  <link rel="stylesheet" href="assets/css/admin/dashboard.css">
  <link rel="stylesheet" href="assets/css/admin/management.css">
  <link rel="stylesheet" href="../shared/assets/css/palette.css">
</head>
<body class="admin-dashboard">
  <a class="skip-link" href="#main-content">Skip to main content</a>
  <div class="admin-layout" id="admin-layout">
    <div class="sidebar-backdrop" id="sidebar-backdrop" hidden></div>
    <?php include __DIR__ . '/../shared/partials/admin_leftber.php'; ?>
    <div class="main-shell">
      <?php include __DIR__ . '/../shared/partials/header.php'; ?>
      <main class="dashboard-main" id="main-content">
        <section class="page-heading management-heading">
          <div>
            <p class="page-eyebrow"><span class="live-dot"></span> Finance &amp; operations</p>
            <h1>Create payment invoice</h1>
            <p class="page-subtitle">Set the amount and due date. Customers can pay the exact invoice through the sandbox gateway.</p>
          </div>
          <a class="btn btn-outline-secondary" href="payments.php">Back to payments</a>
        </section>

        <?php if ($notice): ?>
          <div class="alert alert-<?= $escape($notice[0]) ?>" role="status"><?= $escape($notice[1]) ?></div>
        <?php endif; ?>

        <section class="management-panel p-4" aria-labelledby="invoice-form-title">
          <h2 class="h4 mb-3" id="invoice-form-title">Invoice details</h2>
          <?php if (!$targets): ?>
            <p class="text-secondary mb-0">No approved bookings are available for invoicing.</p>
          <?php else: ?>
            <form method="post" class="row g-3">
              <input type="hidden" name="csrf" value="<?= $escape($_SESSION['csrf']) ?>">
              <div class="col-12">
                <label class="form-label" for="target">Approved booking or active agreement</label>
                <select class="form-select" id="target" name="target" required>
                  <option value="">Choose customer and property</option>
                  <?php foreach ($targets as $target): ?>
                    <option value="<?= $escape($target['value']) ?>"><?= $escape($target['label']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-12 col-md-6">
                <label class="form-label" for="payment-type">Payment type</label>
                <select class="form-select" id="payment-type" name="payment_type" required>
                  <option value="rent">Rent</option>
                  <option value="sale">Sale</option>
                  <option value="advance">Advance</option>
                </select>
                <small class="text-secondary">Rent/sale must match the agreement type; advance can be used with either.</small>
              </div>
              <div class="col-12 col-md-6">
                <label class="form-label" for="invoice-amount">Invoice amount (BDT)</label>
                <input class="form-control" id="invoice-amount" name="invoice_amount" type="number" min="10" max="500000" step="0.01" required>
              </div>
              <div class="col-12 col-md-6">
                <label class="form-label" for="due-date">Due date</label>
                <input class="form-control" id="due-date" name="due_date" type="date" value="<?= date('Y-m-d') ?>" required>
              </div>
              <div class="col-12 row g-3 mt-0" id="agreement-fields">
                <div class="col-12 col-md-6">
                  <label class="form-label" for="agreement-type">Agreement type (new agreement only)</label>
                  <select class="form-select" id="agreement-type" name="agreement_type">
                    <option value="rent">Rent</option>
                    <option value="sale">Sale</option>
                  </select>
                </div>
                <div class="col-12 col-md-6">
                  <label class="form-label" for="agreement-amount">Agreement amount (new agreement only, BDT)</label>
                  <input class="form-control" id="agreement-amount" name="agreement_amount" type="number" min="0.01" step="0.01">
                </div>
                <div class="col-12 col-md-6">
                  <label class="form-label" for="start-date">Agreement start date (new agreement only)</label>
                  <input class="form-control" id="start-date" name="start_date" type="date" value="<?= date('Y-m-d') ?>">
                </div>
              </div>
              <div class="col-12">
                <button class="btn btn-primary" type="submit"><i class="fa-solid fa-file-invoice-dollar me-2" aria-hidden="true"></i>Create invoice</button>
              </div>
            </form>
          <?php endif; ?>
        </section>
      </main>
    </div>
  </div>
  <script>
    const targetSelect = document.querySelector('#target');
    const agreementFields = document.querySelector('#agreement-fields');
    if (targetSelect && agreementFields) {
      const agreementInputs = agreementFields.querySelectorAll('select, input');
      const updateAgreementFields = () => {
        const needsAgreement = targetSelect.value.startsWith('booking:');
        agreementFields.hidden = !needsAgreement;
        agreementInputs.forEach((input) => {
          input.disabled = !needsAgreement;
          input.required = needsAgreement;
        });
      };
      targetSelect.addEventListener('change', updateAgreementFields);
      updateAgreementFields();
    }
  </script>
</body>
</html>
