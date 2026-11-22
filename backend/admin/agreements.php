<?php
include_once __DIR__ . '/dbconfig.php';

function agreementTaka($amount) {
  return '৳ ' . number_format((float)$amount);
}

$totalAgreements = $activeAgreements = $expiredAgreements = $expiringAgreements = 0;
$statsResult = $conn->query("SELECT COUNT(*) AS total,
                                    COALESCE(SUM(status = 'active'), 0) AS active_count,
                                    COALESCE(SUM(status = 'expired'), 0) AS expired_count,
                                    COALESCE(SUM(status = 'active' AND end_date IS NOT NULL
                                      AND end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)), 0) AS expiring_count
                             FROM agreements");
if ($statsResult && ($stats = $statsResult->fetch_assoc())) {
  $totalAgreements = (int)$stats['total'];
  $activeAgreements = (int)$stats['active_count'];
  $expiredAgreements = (int)$stats['expired_count'];
  $expiringAgreements = (int)$stats['expiring_count'];
}

$rows = [];
$agreementsResult = $conn->query("SELECT a.agreement_id, a.property_id, a.customer_id, a.agreement_type, a.start_date,
                                         a.end_date, a.amount, a.status, a.created_at,
                                         p.title AS property_title, u.name AS customer_name
                                  FROM agreements a
                                  LEFT JOIN properties p ON p.property_id = a.property_id
                                  LEFT JOIN customers c ON c.customer_id = a.customer_id
                                  LEFT JOIN users u ON u.user_id = c.user_id
                                  ORDER BY a.created_at DESC, a.agreement_id DESC
                                  LIMIT 50");
while ($agreement = $agreementsResult->fetch_assoc()) {
  $rows[] = [
    'id' => 'AGR-' . str_pad($agreement['agreement_id'], 4, '0', STR_PAD_LEFT),
    'property' => $agreement['property_title'] ?: 'Property #' . (int)$agreement['property_id'],
    'client' => $agreement['customer_name'] ?: 'Customer #' . (int)$agreement['customer_id'],
    'type' => $agreement['agreement_type'] === 'rent' ? 'Lease' : ucfirst($agreement['agreement_type']),
    'date' => date('M d, Y', strtotime($agreement['created_at'])),
    'term' => date('M d, Y', strtotime($agreement['start_date'])) . ' – ' .
      ($agreement['end_date'] ? date('M d, Y', strtotime($agreement['end_date'])) : 'No end date'),
    'amount' => agreementTaka($agreement['amount']),
    'status' => ucfirst($agreement['status']),
  ];
}

$managementPage = [
  'title' => 'Agreements',
  'category' => 'Management',
  'description' => 'Review active, expired, and terminated property agreements.',
  'stats' => [
    ['label' => 'Total agreements', 'value' => (string)$totalAgreements, 'note' => 'All property contracts', 'icon' => 'fa-solid fa-file-contract', 'color' => 'stat-blue'],
    ['label' => 'Active', 'value' => (string)$activeAgreements, 'note' => 'Currently in effect', 'icon' => 'fa-solid fa-circle-check', 'color' => 'stat-green'],
    ['label' => 'Expiring soon', 'value' => (string)$expiringAgreements, 'note' => 'Within the next 30 days', 'icon' => 'fa-regular fa-calendar-xmark', 'color' => 'stat-amber'],
    ['label' => 'Expired', 'value' => (string)$expiredAgreements, 'note' => 'Past agreement end date', 'icon' => 'fa-solid fa-circle-exclamation', 'color' => 'stat-red'],
  ],
  'listTitle' => 'Recent agreements',
  'listDescription' => 'Latest agreements recorded in the system.',
  'columns' => [
    ['label' => 'Agreement ID', 'key' => 'id'],
    ['label' => 'Property', 'key' => 'property'],
    ['label' => 'Client', 'key' => 'client'],
    ['label' => 'Agreement type', 'key' => 'type'],
    ['label' => 'Created', 'key' => 'date'],
    ['label' => 'Term', 'key' => 'term'],
    ['label' => 'Amount', 'key' => 'amount'],
    ['label' => 'Status', 'key' => 'status', 'type' => 'status'],
  ],
  'liveData' => true,
  'emptyMessage' => 'No agreements have been recorded.',
  'rows' => $rows,
];
require __DIR__ . '/_management_page.php';
