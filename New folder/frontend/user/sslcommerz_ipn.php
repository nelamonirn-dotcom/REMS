<?php
include_once __DIR__ . '/../../backend/admin/dbconfig.php';
require_once __DIR__ . '/sslcommerz_helper.php';

$transactionId = is_string($_POST['tran_id'] ?? null) ? $_POST['tran_id'] : '';
$validationId = is_string($_POST['val_id'] ?? null) ? $_POST['val_id'] : '';
$gatewayStatus = is_string($_POST['status'] ?? null) ? strtoupper($_POST['status']) : '';

if (
  $transactionId === ''
  || $validationId === ''
  || !in_array($gatewayStatus, ['SUCCESS', 'VALID', 'VALIDATED'], true)
) {
  http_response_code(400);
  echo 'Invalid notification.';
  exit;
}

try {
  if (!sslcommerzCompletePayment($conn, $transactionId, $validationId)) {
    http_response_code(400);
    echo 'Payment validation failed.';
    exit;
  }
} catch (RuntimeException $e) {
  error_log($e->getMessage());
  http_response_code(502);
  echo 'Payment validation is temporarily unavailable.';
  exit;
}

http_response_code(200);
echo 'Payment validated.';
