<?php
include_once __DIR__ . '/../../backend/admin/dbconfig.php';
require_once __DIR__ . '/sslcommerz_helper.php';

$transactionId = is_string($_REQUEST['tran_id'] ?? null) ? trim($_REQUEST['tran_id']) : '';
$validationId  = is_string($_REQUEST['val_id'] ?? null) ? trim($_REQUEST['val_id']) : '';
$gatewayStatus = is_string($_REQUEST['status'] ?? null) ? strtoupper(trim($_REQUEST['status'])) : '';

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
} catch (Throwable $e) {
  error_log($e->getMessage());
  http_response_code(502);
  echo 'Payment validation is temporarily unavailable.';
  exit;
}

http_response_code(200);
echo 'Payment validated.';
