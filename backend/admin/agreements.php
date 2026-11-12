<?php
$managementPage = [
  'title' => 'Agreements',
  'category' => 'Management',
  'description' => 'Keep lease and sale agreements organized throughout the signing process.',
  'stats' => [
    ['label' => 'Total agreements', 'value' => '86', 'note' => 'All property contracts', 'icon' => 'fa-solid fa-file-contract', 'color' => 'stat-blue'],
    ['label' => 'Awaiting signature', 'value' => '09', 'note' => 'Pending counterparty action', 'icon' => 'fa-solid fa-pen-nib', 'color' => 'stat-amber'],
    ['label' => 'Active', 'value' => '64', 'note' => 'Currently in effect', 'icon' => 'fa-solid fa-circle-check', 'color' => 'stat-green'],
    ['label' => 'Expiring soon', 'value' => '04', 'note' => 'Within the next 30 days', 'icon' => 'fa-regular fa-calendar-xmark', 'color' => 'stat-red'],
  ],
  'listTitle' => 'Recent agreements',
  'listDescription' => 'Sample contracts and their current signing status.',
  'columns' => [
    ['label' => 'Agreement ID', 'key' => 'id'],
    ['label' => 'Property', 'key' => 'property'],
    ['label' => 'Client', 'key' => 'client'],
    ['label' => 'Agreement type', 'key' => 'type'],
    ['label' => 'Updated', 'key' => 'date'],
    ['label' => 'Status', 'key' => 'status', 'type' => 'status'],
  ],
  'rows' => [
    ['id' => 'AGR-0086', 'property' => 'Lakeview Heights, Apt 5B', 'client' => 'Arif Rahman', 'type' => 'Lease', 'date' => 'Nov 09, 2026', 'status' => 'Active'],
    ['id' => 'AGR-0085', 'property' => 'The Grand Residence', 'client' => 'Sadia Noor', 'type' => 'Sale', 'date' => 'Nov 08, 2026', 'status' => 'Pending'],
    ['id' => 'AGR-0084', 'property' => 'Palm Grove Apartment', 'client' => 'Tariq Hasan', 'type' => 'Lease', 'date' => 'Nov 07, 2026', 'status' => 'Review'],
    ['id' => 'AGR-0083', 'property' => 'Riverside Tower, 8A', 'client' => 'Farhana Mahmud', 'type' => 'Lease', 'date' => 'Nov 05, 2026', 'status' => 'Active'],
    ['id' => 'AGR-0082', 'property' => 'Cedar Court, Unit 3C', 'client' => 'Mahin Islam', 'type' => 'Sale', 'date' => 'Nov 03, 2026', 'status' => 'Draft'],
  ],
];
require __DIR__ . '/_management_page.php';
