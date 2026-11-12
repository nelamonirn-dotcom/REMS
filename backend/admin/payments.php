<?php
$managementPage = [
  'title' => 'Payments',
  'category' => 'Finance & operations',
  'description' => 'Review incoming rent, deposits and other property-related payments.',
  'stats' => [
    ['label' => 'Collected this month', 'value' => '৳ 18.4L', 'note' => 'Across all properties', 'icon' => 'fa-solid fa-wallet', 'color' => 'stat-blue'],
    ['label' => 'Awaiting payment', 'value' => '৳ 2.1L', 'note' => '12 outstanding invoices', 'icon' => 'fa-regular fa-clock', 'color' => 'stat-amber'],
    ['label' => 'Completed', 'value' => '142', 'note' => 'Transactions this month', 'icon' => 'fa-solid fa-circle-check', 'color' => 'stat-green'],
    ['label' => 'Overdue', 'value' => '07', 'note' => 'Requires follow-up', 'icon' => 'fa-solid fa-circle-exclamation', 'color' => 'stat-red'],
  ],
  'listTitle' => 'Recent transactions',
  'listDescription' => 'A sample overview of property payment activity.',
  'columns' => [
    ['label' => 'Payment ID', 'key' => 'id'],
    ['label' => 'Payer', 'key' => 'payer'],
    ['label' => 'Property', 'key' => 'property'],
    ['label' => 'Payment date', 'key' => 'date'],
    ['label' => 'Amount', 'key' => 'amount'],
    ['label' => 'Status', 'key' => 'status', 'type' => 'status'],
  ],
  'rows' => [
    ['id' => 'PAY-3198', 'payer' => 'Arif Rahman', 'property' => 'Lakeview Heights, Apt 5B', 'date' => 'Nov 10, 2026', 'amount' => '৳ 85,000', 'status' => 'Paid'],
    ['id' => 'PAY-3197', 'payer' => 'Sadia Noor', 'property' => 'The Grand Residence', 'date' => 'Nov 09, 2026', 'amount' => '৳ 120,000', 'status' => 'Pending'],
    ['id' => 'PAY-3196', 'payer' => 'Tariq Hasan', 'property' => 'Palm Grove Apartment', 'date' => 'Nov 08, 2026', 'amount' => '৳ 65,000', 'status' => 'Paid'],
    ['id' => 'PAY-3195', 'payer' => 'Farhana Mahmud', 'property' => 'Riverside Tower, 8A', 'date' => 'Nov 07, 2026', 'amount' => '৳ 92,000', 'status' => 'Overdue'],
    ['id' => 'PAY-3194', 'payer' => 'Mahin Islam', 'property' => 'Cedar Court, Unit 3C', 'date' => 'Nov 06, 2026', 'amount' => '৳ 78,000', 'status' => 'Paid'],
  ],
];
require __DIR__ . '/_management_page.php';
