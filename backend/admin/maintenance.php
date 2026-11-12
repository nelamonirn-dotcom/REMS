<?php
$managementPage = [
  'title' => 'Maintenance',
  'category' => 'Finance & operations',
  'description' => 'Monitor property service requests and upcoming maintenance work.',
  'stats' => [
    ['label' => 'Open requests', 'value' => '12', 'note' => 'Across all properties', 'icon' => 'fa-solid fa-screwdriver-wrench', 'color' => 'stat-blue'],
    ['label' => 'Urgent', 'value' => '03', 'note' => 'Needs prompt attention', 'icon' => 'fa-solid fa-triangle-exclamation', 'color' => 'stat-red'],
    ['label' => 'In progress', 'value' => '05', 'note' => 'Being handled by teams', 'icon' => 'fa-solid fa-person-digging', 'color' => 'stat-amber'],
    ['label' => 'Resolved this month', 'value' => '28', 'note' => 'Service requests closed', 'icon' => 'fa-solid fa-circle-check', 'color' => 'stat-green'],
  ],
  'listTitle' => 'Maintenance requests',
  'listDescription' => 'Sample service requests reported by residents.',
  'columns' => [
    ['label' => 'Request ID', 'key' => 'id'],
    ['label' => 'Issue', 'key' => 'issue'],
    ['label' => 'Property', 'key' => 'property'],
    ['label' => 'Priority', 'key' => 'priority'],
    ['label' => 'Reported', 'key' => 'date'],
    ['label' => 'Status', 'key' => 'status', 'type' => 'status'],
  ],
  'rows' => [
    ['id' => 'MNT-0294', 'issue' => 'Water leak · Kitchen', 'property' => 'Lakeview Heights, Apt 5B', 'priority' => 'High', 'date' => 'Nov 10, 2026', 'status' => 'In progress'],
    ['id' => 'MNT-0293', 'issue' => 'Lift inspection', 'property' => 'The Grand Residence', 'priority' => 'Normal', 'date' => 'Nov 09, 2026', 'status' => 'Scheduled'],
    ['id' => 'MNT-0292', 'issue' => 'Air conditioning repair', 'property' => 'Palm Grove Apartment', 'priority' => 'High', 'date' => 'Nov 08, 2026', 'status' => 'Pending'],
    ['id' => 'MNT-0291', 'issue' => 'Door lock replacement', 'property' => 'Cedar Court, Unit 3C', 'priority' => 'Normal', 'date' => 'Nov 07, 2026', 'status' => 'Completed'],
    ['id' => 'MNT-0290', 'issue' => 'Common area repainting', 'property' => 'Riverside Tower, 8A', 'priority' => 'Low', 'date' => 'Nov 06, 2026', 'status' => 'Pending'],
  ],
];
require __DIR__ . '/_management_page.php';
