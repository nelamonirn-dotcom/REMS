<?php
include_once __DIR__ . '/dbconfig.php'; // database connection ($conn)

// Taka formatter: 320000 -> ৳ 3.2L, 18500 -> ৳ 18,500
function taka($n) {
  $n = (float)$n;
  if ($n >= 100000) {
    return '৳ ' . rtrim(rtrim(number_format($n / 100000, 1), '0'), '.') . 'L';
  }
  return '৳ ' . number_format($n);
}

$monthStart = date('Y-m-01');

// ---------- Summary cards ----------
$monthTotal = 0;
$monthCount = 0;
$monthAvg   = 0;

$r = $conn->query("SELECT COALESCE(SUM(amount), 0) AS total,
                          COUNT(*) AS cnt,
                          COALESCE(AVG(amount), 0) AS avg_amount
                   FROM expenses
                   WHERE expense_date >= '$monthStart'");
if ($r && ($s = $r->fetch_assoc())) {
  $monthTotal = (float)$s['total'];
  $monthCount = (int)$s['cnt'];
  $monthAvg   = (float)$s['avg_amount'];
}

// Largest category this month
$topCategory = '—';
$topNote     = 'No expenses this month';
$r = $conn->query("SELECT category, SUM(amount) AS total
                   FROM expenses
                   WHERE expense_date >= '$monthStart'
                   GROUP BY category
                   ORDER BY total DESC
                   LIMIT 1");
if ($r && ($t = $r->fetch_assoc()) && $monthTotal > 0) {
  $topCategory = $t['category'];
  $topNote     = round($t['total'] / $monthTotal * 100) . '% of monthly spend';
}

// ---------- Expense list ----------
$rows = [];
$res = $conn->query("SELECT expense_id, recorded_by, category, amount, expense_date, description
                     FROM expenses
                     ORDER BY expense_date DESC, expense_id DESC
                     LIMIT 50");
while ($e = $res->fetch_assoc()) {
  $rows[] = [
    'id'          => 'EXP-' . str_pad($e['expense_id'], 4, '0', STR_PAD_LEFT),
    'description' => $e['description'] !== null && $e['description'] !== '' ? $e['description'] : '—',
    'category'    => $e['category'],
    'person'      => 'User #' . (int)$e['recorded_by'],
    'amount'      => taka($e['amount']),
    'date'        => date('M d, Y', strtotime($e['expense_date'])),
  ];
}

$managementPage = [
  'title' => 'Expenses',
  'category' => 'Finance & operations',
  'description' => 'Track operational costs and property-related expenses.',
  'stats' => [
    ['label' => 'Total this month', 'value' => taka($monthTotal), 'note' => 'Recorded expenses', 'icon' => 'fa-solid fa-receipt', 'color' => 'stat-blue'],
    ['label' => 'Entries this month', 'value' => sprintf('%02d', $monthCount), 'note' => 'Expense records', 'icon' => 'fa-regular fa-clock', 'color' => 'stat-amber'],
    ['label' => 'Average expense', 'value' => taka($monthAvg), 'note' => 'Per record this month', 'icon' => 'fa-solid fa-chart-line', 'color' => 'stat-green'],
    ['label' => 'Largest category', 'value' => $topCategory, 'note' => $topNote, 'icon' => 'fa-solid fa-screwdriver-wrench', 'color' => 'stat-purple'],
  ],
  'listTitle' => 'Recent expenses',
  'listDescription' => 'Latest recorded expenses.',
  'columns' => [
    ['label' => 'Expense ID', 'key' => 'id'],
    ['label' => 'Description', 'key' => 'description'],
    ['label' => 'Category', 'key' => 'category'],
    ['label' => 'Recorded by', 'key' => 'person'],
    ['label' => 'Amount', 'key' => 'amount'],
    ['label' => 'Date', 'key' => 'date'],
  ],
  'rows' => $rows,
];
require __DIR__ . '/_management_page.php';