<?php
include_once __DIR__ . '/dbconfig.php'; // database connection ($conn)

// Taka formatter: 4820000 -> ৳ 48.2L, 85000 -> ৳ 85,000
function taka($n) {
  $n = (float)$n;
  if ($n >= 100000) {
    return '৳ ' . rtrim(rtrim(number_format($n / 100000, 1), '0'), '.') . 'L';
  }
  return '৳ ' . number_format($n);
}

$monthStart = date('Y-m-01');

// ---------- Revenue chart: last 6 months (paid payments) ----------
$months = [];
for ($i = 5; $i >= 0; $i--) {
  $key = date('Y-m', strtotime("-$i month", strtotime($monthStart)));
  $months[$key] = 0;
}
$firstMonth = array_key_first($months) . '-01';

$r = $conn->query("SELECT DATE_FORMAT(paid_at, '%Y-%m') AS ym, SUM(amount) AS total
                   FROM payments
                   WHERE status = 'paid' AND paid_at >= '$firstMonth'
                   GROUP BY ym");
while ($r && ($row = $r->fetch_assoc())) {
  if (isset($months[$row['ym']])) { $months[$row['ym']] = (float)$row['total']; }
}
$maxRevenue = max($months) ?: 0;

$chartBars = [];
foreach ($months as $ym => $total) {
  $chartBars[] = [
    'label'  => date('M', strtotime($ym . '-01')),
    'value'  => taka($total),
    'height' => ($maxRevenue > 0 ? max(4, round($total / $maxRevenue * 100)) : 4) . '%',
  ];
}
$revenueThisMonth = $months[date('Y-m')] ?? 0;

// ---------- Portfolio by property type ----------
$typeColors = ['flat' => 'breakdown-blue', 'commercial' => 'breakdown-green', 'land' => 'breakdown-amber'];
$totalProperties = 0;
$types = [];
$r = $conn->query("SELECT property_type, COUNT(*) AS cnt,
                          AVG(area_sqft) AS avg_area,
                          AVG(price) AS avg_price,
                          AVG(monthly_rent) AS avg_rent
                   FROM properties
                   GROUP BY property_type
                   ORDER BY cnt DESC");
while ($r && ($row = $r->fetch_assoc())) {
  $types[] = $row;
  $totalProperties += (int)$row['cnt'];
}

$breakdown = [];
$rows = [];
foreach ($types as $t) {
  $share = $totalProperties > 0 ? round($t['cnt'] / $totalProperties * 100) : 0;
  $breakdown[] = [
    'label' => ucfirst($t['property_type']),
    'value' => $share,
    'color' => $typeColors[$t['property_type']] ?? 'breakdown-blue',
  ];
  $rows[] = [
    'portfolio'  => ucfirst($t['property_type']),
    'properties' => (string)(int)$t['cnt'],
    'area'       => $t['avg_area'] !== null ? number_format($t['avg_area']) . ' sqft' : '—',
    'price'      => $t['avg_price'] !== null ? taka($t['avg_price']) : '—',
    'rent'       => $t['avg_rent'] !== null ? taka($t['avg_rent']) . '/month' : '—',
    'share'      => $share . '%',
  ];
}

// ---------- Other summary numbers ----------
$visitsThisMonth = 0;
$scheduledVisits = 0;
$r = $conn->query("SELECT SUM(visit_time >= '$monthStart') AS this_month,
                          SUM(status = 'scheduled') AS scheduled
                   FROM visits");
if ($r && ($s = $r->fetch_assoc())) {
  $visitsThisMonth = (int)$s['this_month'];
  $scheduledVisits = (int)$s['scheduled'];
}

$overduePayments = 0;
$r = $conn->query("SELECT COUNT(*) AS c FROM payments
                   WHERE status IN ('pending','partial') AND due_date < CURDATE()");
if ($r && ($s = $r->fetch_assoc())) { $overduePayments = (int)$s['c']; }

$managementPage = [
  'title' => 'Reports & analytics',
  'category' => 'Finance & operations',
  'description' => 'Explore portfolio performance and key operational indicators.',
  'kind' => 'analytics',
  'chartTitle' => 'Revenue overview',
  'chartDescription' => 'Monthly collected revenue across the portfolio.',
  'chartBars' => $chartBars,
  'breakdown' => $breakdown,
  'stats' => [
    ['label' => 'Total properties', 'value' => (string)$totalProperties, 'note' => 'In the portfolio', 'icon' => 'fa-solid fa-building-circle-check', 'color' => 'stat-blue'],
    ['label' => 'Revenue this month', 'value' => taka($revenueThisMonth), 'note' => 'Paid payments collected', 'icon' => 'fa-solid fa-chart-line', 'color' => 'stat-green'],
    ['label' => 'Visits this month', 'value' => sprintf('%02d', $visitsThisMonth), 'note' => $scheduledVisits . ' still scheduled', 'icon' => 'fa-solid fa-calendar-check', 'color' => 'stat-purple'],
    ['label' => 'Overdue payments', 'value' => sprintf('%02d', $overduePayments), 'note' => 'Requires follow-up', 'icon' => 'fa-solid fa-list-check', 'color' => 'stat-amber'],
  ],
  'listTitle' => 'Portfolio snapshot',
  'listDescription' => 'Property groups by type.',
  'columns' => [
    ['label' => 'Portfolio', 'key' => 'portfolio'],
    ['label' => 'Properties', 'key' => 'properties'],
    ['label' => 'Avg. area', 'key' => 'area'],
    ['label' => 'Avg. price', 'key' => 'price'],
    ['label' => 'Avg. rent', 'key' => 'rent'],
    ['label' => 'Share', 'key' => 'share'],
  ],
  'rows' => $rows,
];
require __DIR__ . '/_management_page.php';