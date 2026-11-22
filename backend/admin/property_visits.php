<?php
session_start();
if (!isset($_SESSION['email'])) {
  header("Location: index.php");
  exit;
}
if (($_SESSION['role'] ?? '') !== 'admin') {
  header("Location: unauthorize.php");
  exit;
}

include_once __DIR__ . '/dbconfig.php'; // database connection ($conn)

// Handle Admin Actions on Property Visits
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  $visitId = (int)($_POST['visit_id'] ?? 0);

  if ($action === 'complete_visit' && $visitId > 0) {
    $conn->query("UPDATE visits SET status = 'completed' WHERE visit_id = $visitId");
    header('Location: property_visits.php?msg=completed');
    exit;
  } elseif ($action === 'cancel_visit' && $visitId > 0) {
    $conn->query("UPDATE visits SET status = 'cancelled' WHERE visit_id = $visitId");
    header('Location: property_visits.php?msg=cancelled');
    exit;
  }
}

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

// ---------- Visit list: customer/property/date/agent from the visits table ----------
$rows = [];
$res = $conn->query("SELECT v.visit_id, v.customer_id, v.agent_id, v.visit_time, v.status,
                            v.booking_id, p.property_id, p.title, p.address,
                            customer_user.name AS customer_name,
                            agent_user.name AS agent_name
                     FROM visits v
                     LEFT JOIN properties p ON p.property_id = v.property_id
                     LEFT JOIN customers c ON c.customer_id = v.customer_id
                     LEFT JOIN users customer_user ON customer_user.user_id = c.user_id
                     LEFT JOIN agents a ON a.agent_id = v.agent_id
                     LEFT JOIN users agent_user ON agent_user.user_id = a.user_id
                     ORDER BY v.visit_time DESC");
while ($v = $res->fetch_assoc()) {
  $vId = (int)$v['visit_id'];
  $actionsHtml = '';

  if ($v['status'] === 'scheduled') {
    $actionsHtml = '<form method="post" style="display:inline-flex; gap:6px;">
                      <input type="hidden" name="visit_id" value="' . $vId . '">
                      <button type="submit" name="action" value="complete_visit" class="btn btn-sm btn-success py-1 px-2" title="Mark as Visited">
                        <i class="fa-solid fa-check"></i> Mark Visited
                      </button>
                      <button type="submit" name="action" value="cancel_visit" class="btn btn-sm btn-outline-danger py-1 px-2" title="Cancel Visit" onclick="return confirm(\'Cancel this visit?\');">
                        <i class="fa-solid fa-xmark"></i>
                      </button>
                    </form>';
  } elseif ($v['status'] === 'completed') {
    $actionsHtml = '<span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1"><i class="fa-solid fa-circle-check"></i> Visited</span>';
  } else {
    $actionsHtml = '<span class="badge bg-secondary-subtle text-secondary px-2 py-1">Cancelled</span>';
  }

  $rows[] = [
    'id'       => 'VIS-' . str_pad($v['visit_id'], 4, '0', STR_PAD_LEFT),
    'booking'  => '#BK-' . (int)$v['booking_id'],
    'guest'    => $v['customer_name'] ?: 'Customer #' . (int)$v['customer_id'],
    'property' => ($v['title'] ?? 'Property removed') . (!empty($v['address']) ? ', ' . $v['address'] : ''),
    'date'     => date('M d · h:i A', strtotime($v['visit_time'])),
    'agent'    => $v['agent_name'] ?: 'Unassigned',
    'status'   => $v['status'] === 'completed' ? 'Visited' : ucfirst($v['status']),
    'actions'  => $actionsHtml,
  ];
}

$managementPage = [
  'title' => 'Property visits',
  'category' => 'Management',
  'description' => 'Coordinate upcoming property tours and keep track of visit requests.',
  'liveData' => true,
  'emptyMessage' => 'No property visits have been scheduled yet.',
  'stats' => [
    ['label' => 'Scheduled visits', 'value' => sprintf('%02d', $scheduled), 'note' => 'Across all properties', 'icon' => 'fa-solid fa-calendar-days', 'color' => 'stat-blue'],
    ['label' => 'Today', 'value' => sprintf('%02d', $today), 'note' => 'Visits on the calendar', 'icon' => 'fa-regular fa-clock', 'color' => 'stat-amber'],
    ['label' => 'Completed / Visited', 'value' => sprintf('%02d', $completed), 'note' => 'Finished visits', 'icon' => 'fa-solid fa-circle-check', 'color' => 'stat-green'],
    ['label' => 'Cancelled', 'value' => sprintf('%02d', $cancelled), 'note' => 'Visits called off', 'icon' => 'fa-solid fa-ban', 'color' => 'stat-purple'],
  ],
  'listTitle' => 'Property visits',
  'listDescription' => 'Who visited which property, and when.',
  'columns' => [
    ['label' => 'Visit ID', 'key' => 'id'],
    ['label' => 'Booking ID', 'key' => 'booking'],
    ['label' => 'Visitor', 'key' => 'guest'],
    ['label' => 'Property', 'key' => 'property'],
    ['label' => 'Date & time', 'key' => 'date'],
    ['label' => 'Assigned agent', 'key' => 'agent'],
    ['label' => 'Status', 'key' => 'status', 'type' => 'status'],
    ['label' => 'Actions', 'key' => 'actions', 'type' => 'raw'],
  ],
  'rows' => $rows,
];
require __DIR__ . '/_management_page.php';
