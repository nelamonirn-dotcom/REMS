<?php
session_start();
include_once __DIR__ . '/../../backend/admin/dbconfig.php';
require_once __DIR__ . '/sslcommerz_helper.php';

$transactionId = is_string($_POST['tran_id'] ?? null) ? $_POST['tran_id'] : '';
$gatewayStatus = is_string($_POST['status'] ?? null) ? strtoupper($_POST['status']) : '';
$result = 'verification_error';

if (
  $transactionId !== ''
  && in_array($gatewayStatus, ['SUCCESS', 'VALID', 'VALIDATED'], true)
) {
  $validationId = is_string($_POST['val_id'] ?? null) ? $_POST['val_id'] : '';
  if ($validationId !== '') {
    try {
      if (sslcommerzCompletePayment($conn, $transactionId, $validationId)) {
        $result = 'success';
      }
    } catch (RuntimeException $e) {
      error_log($e->getMessage());
    }
  }
} elseif ($transactionId !== '' && in_array($gatewayStatus, ['FAILED', 'CANCEL', 'CANCELLED'], true)) {
  sslcommerzReleasePayment($conn, $transactionId);
  $result = strtolower($gatewayStatus);
}

header('Location: payments.php?result=' . urlencode($result));
exit;
