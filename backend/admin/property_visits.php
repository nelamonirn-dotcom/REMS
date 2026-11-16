<?php
include_once __DIR__ . '/dbconfig.php'; // database connection ($conn)

// ---------- Summary cards ----------
$scheduled = $today = $completed = $cancelled = 0;

$r = $conn->query("SELECT
    SUM(status = 'scheduled') AS scheduled,
    SUM(DATE(visit_time) = CURDATE() AND status = 'scheduled') AS today,
    SUM(status = 'completed') AS completed,
    SUM(status = 'cancelled') AS cancelled
  FROM visits");
if ($r && ($s = $r->fetch_assoc())) {
  $scheduled = (int)$s['scheduled'];
  $today     = (int)$s['today'];
  $completed = (int)$s['completed'];
  $cancelled = (int)$s['cancelled'];
}

// ---------- Visit list: kon customer kon property visit korse ----------
$rows = [];
$res = $conn->query("SELECT v.visit_id, v.customer_id, v.agent_id, v.visit_time, v.status,
                            p.title, p.address
                     FROM visits v
                     LEFT JOIN properties p ON p.property_id = v.property_id
                     ORDER BY v.visit_time DESC
                     LIMIT 50");
while ($v = $res->fetch_assoc()) {
  $rows[] = [
    'id'       => 'VIS-' . str_pad($v['visit_id'], 4, '0', STR_PAD_LEFT),
    'guest'    => 'Customer #' . (int)$v['customer_id'],
    'property' => ($v['title'] ?? 'Property removed') . (!empty($v['address']) ? ', ' . $v['address'] : ''),
    'date'     => date('M d · h:i A', strtotime($v['visit_time'])),
    'agent'    => 'Agent #' . (int)$v['agent_id'],
    'status'   => ucfirst($v['status']),
  ];
}

$managementPage = [
  'title' => 'Property visits',
  'category' => 'Management',
  'description' => 'Coordinate upcoming property tours and keep track of visit requests.',
  'stats' => [
    ['label' => 'Scheduled visits', 'value' => sprintf('%02d', $scheduled), 'note' => 'Across all properties', 'icon' => 'fa-solid fa-calendar-days', 'color' => 'stat-blue'],
    ['label' => 'Today', 'value' => sprintf('%02d', $today), 'note' => 'Visits on the calendar', 'icon' => 'fa-regular fa-clock', 'color' => 'stat-amber'],
    ['label' => 'Completed', 'value' => sprintf('%02d', $completed), 'note' => 'Finished visits', 'icon' => 'fa-solid fa-circle-check', 'color' => 'stat-green'],
    ['label' => 'Cancelled', 'value' => sprintf('%02d', $cancelled), 'note' => 'Visits called off', 'icon' => 'fa-solid fa-ban', 'color' => 'stat-purple'],
  ],
  'listTitle' => 'Property visits',
  'listDescription' => 'Who visited which property, and when.',
  'columns' => [
    ['label' => 'Visit ID', 'key' => 'id'],
    ['label' => 'Customer', 'key' => 'guest'],
    ['label' => 'Property', 'key' => 'property'],
    ['label' => 'Date & time', 'key' => 'date'],
    ['label' => 'Assigned agent', 'key' => 'agent'],
    ['label' => 'Status', 'key' => 'status', 'type' => 'status'],
  ],
  'rows' => $rows,
];
require __DIR__ . '/_management_page.php';