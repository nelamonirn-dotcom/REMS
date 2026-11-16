<?php
include_once __DIR__ . '/dbconfig.php'; // database connection ($conn)

// Taka formatter: 1840000 -> ৳ 18.4L, 85000 -> ৳ 85,000
function taka($n) {
  $n = (float)$n;
  if ($n >= 100000) {
    return '৳ ' . rtrim(rtrim(number_format($n / 100000, 1), '0'), '.') . 'L';
  }
  return '৳ ' . number_format($n);
}

// ---------- Summary cards ----------
$collected = $awaiting = 0;
$awaitingCount = $completedCount = $overdueCount = 0;

$r = $conn->query("SELECT
    COALESCE(SUM(CASE WHEN status = 'paid' AND paid_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01') THEN amount END), 0) AS collected,
    COALESCE(SUM(CASE WHEN status IN ('pending','partial') THEN amount END), 0) AS awaiting,
    SUM(status IN ('pending','partial')) AS awaiting_count,
    SUM(status = 'paid' AND paid_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')) AS completed_count,
    SUM(status IN ('pending','partial') AND due_date < CURDATE()) AS overdue_count
  FROM payments");
if ($r && ($s = $r->fetch_assoc())) {
  $collected      = $s['collected'];
  $awaiting       = $s['awaiting'];
  $awaitingCount  = (int)$s['awaiting_count'];
  $completedCount = (int)$s['completed_count'];
  $overdueCount   = (int)$s['overdue_count'];
}

// ---------- Transactions ----------
$rows = [];
$res = $conn->query("SELECT payment_id, agreement_id, amount, due_date, paid_at,
                            payment_type, status
                     FROM payments
                     ORDER BY COALESCE(paid_at, due_date) DESC
                     LIMIT 50");
while ($p = $res->fetch_assoc()) {
  // pending/partial but due date gone = Overdue
  if (in_array($p['status'], ['pending', 'partial'], true) && $p['due_date'] < date('Y-m-d')) {
    $statusLabel = 'Overdue';
  } else {
    $statusLabel = ucfirst($p['status']);
  }

  $dateValue = $p['paid_at'] ?: $p['due_date'];

  $rows[] = [
    'id'       => 'PAY-' . str_pad($p['payment_id'], 4, '0', STR_PAD_LEFT),
    'payer'    => 'Agreement #' . (int)$p['agreement_id'],
    'property' => ucfirst($p['payment_type']),
    'date'     => date('M d, Y', strtotime($dateValue)),
    'amount'   => taka($p['amount']),
    'status'   => $statusLabel,
  ];
}

$managementPage = [
  'title' => 'Payments',
  'category' => 'Finance & operations',
  'description' => 'Review incoming rent, deposits and other property-related payments.',
  'stats' => [
    ['label' => 'Collected this month', 'value' => taka($collected), 'note' => 'Across all properties', 'icon' => 'fa-solid fa-wallet', 'color' => 'stat-blue'],
    ['label' => 'Awaiting payment', 'value' => taka($awaiting), 'note' => $awaitingCount . ' outstanding payments', 'icon' => 'fa-regular fa-clock', 'color' => 'stat-amber'],
    ['label' => 'Completed', 'value' => (string)$completedCount, 'note' => 'Transactions this month', 'icon' => 'fa-solid fa-circle-check', 'color' => 'stat-green'],
    ['label' => 'Overdue', 'value' => sprintf('%02d', $overdueCount), 'note' => 'Requires follow-up', 'icon' => 'fa-solid fa-circle-exclamation', 'color' => 'stat-red'],
  ],
  'listTitle' => 'Recent transactions',
  'listDescription' => 'Latest property payment activity.',
  'columns' => [
    ['label' => 'Payment ID', 'key' => 'id'],
    ['label' => 'Agreement', 'key' => 'payer'],
    ['label' => 'Payment type', 'key' => 'property'],
    ['label' => 'Date', 'key' => 'date'],
    ['label' => 'Amount', 'key' => 'amount'],
    ['label' => 'Status', 'key' => 'status', 'type' => 'status'],
  ],
  'rows' => $rows,
];
require __DIR__ . '/_management_page.php';