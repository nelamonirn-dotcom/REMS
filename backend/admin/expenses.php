<?php
$managementPage = [
  'title' => 'Expenses',
  'category' => 'Finance & operations',
  'description' => 'Track operational costs and property-related expenses.',
  'stats' => [
    ['label' => 'Total this month', 'value' => '৳ 3.2L', 'note' => 'Recorded expenses', 'icon' => 'fa-solid fa-receipt', 'color' => 'stat-blue'],
    ['label' => 'Awaiting approval', 'value' => '06', 'note' => 'Expense claims', 'icon' => 'fa-regular fa-clock', 'color' => 'stat-amber'],
    ['label' => 'Approved', 'value' => '38', 'note' => 'This month', 'icon' => 'fa-solid fa-circle-check', 'color' => 'stat-green'],
    ['label' => 'Largest category', 'value' => 'Repairs', 'note' => '35% of monthly spend', 'icon' => 'fa-solid fa-screwdriver-wrench', 'color' => 'stat-purple'],
  ],
  'listTitle' => 'Recent expenses',
  'listDescription' => 'Sample expense claims and approval status.',
  'columns' => [
    ['label' => 'Expense ID', 'key' => 'id'],
    ['label' => 'Description', 'key' => 'description'],
    ['label' => 'Category', 'key' => 'category'],
    ['label' => 'Submitted by', 'key' => 'person'],
    ['label' => 'Amount', 'key' => 'amount'],
    ['label' => 'Status', 'key' => 'status', 'type' => 'status'],
  ],
  'rows' => [
    ['id' => 'EXP-0418', 'description' => 'Lobby lighting replacement', 'category' => 'Repairs', 'person' => 'Rafi Ahmed', 'amount' => '৳ 18,500', 'status' => 'Pending'],
    ['id' => 'EXP-0417', 'description' => 'Property photography', 'category' => 'Marketing', 'person' => 'Sadia Noor', 'amount' => '৳ 12,000', 'status' => 'Approved'],
    ['id' => 'EXP-0416', 'description' => 'Plumbing service call', 'category' => 'Repairs', 'person' => 'Tariq Hasan', 'amount' => '৳ 8,500', 'status' => 'Paid'],
    ['id' => 'EXP-0415', 'description' => 'Office supplies', 'category' => 'Operations', 'person' => 'Maliha Chowdhury', 'amount' => '৳ 6,200', 'status' => 'Pending'],
    ['id' => 'EXP-0414', 'description' => 'Elevator maintenance', 'category' => 'Maintenance', 'person' => 'Mahin Islam', 'amount' => '৳ 24,000', 'status' => 'Approved'],
  ],
];
require __DIR__ . '/_management_page.php';
