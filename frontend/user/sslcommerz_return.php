<?php
session_start();
include_once __DIR__ . '/../../backend/admin/dbconfig.php';
require_once __DIR__ . '/sslcommerz_helper.php';

$transactionId = is_string($_REQUEST['tran_id'] ?? null) ? trim($_REQUEST['tran_id']) : '';
$gatewayStatus = is_string($_REQUEST['status'] ?? null) ? strtoupper(trim($_REQUEST['status'])) : '';
$validationId  = is_string($_REQUEST['val_id'] ?? null) ? trim($_REQUEST['val_id']) : '';
$result = 'verification_error';

if ($transactionId !== '' && in_array($gatewayStatus, ['SUCCESS', 'VALID', 'VALIDATED'], true)) {
  if ($validationId !== '') {
    try {
      if (sslcommerzCompletePayment($conn, $transactionId, $validationId)) {
        $result = 'success';
      }
    } catch (Throwable $e) {
      error_log($e->getMessage());
      $_SESSION['payment_error_msg'] = $e->getMessage();
    }
  }
} elseif ($transactionId !== '' && in_array($gatewayStatus, ['FAILED', 'CANCEL', 'CANCELLED'], true)) {
  sslcommerzReleasePayment($conn, $transactionId);
  $result = strtolower($gatewayStatus);
}

header('Location: payments.php?result=' . urlencode($result));
exit;
