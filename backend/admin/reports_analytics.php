<?php
$managementPage = [
  'title' => 'Reports & analytics',
  'category' => 'Finance & operations',
  'description' => 'Explore portfolio performance and key operational indicators.',
  'kind' => 'analytics',
  'chartTitle' => 'Revenue overview',
  'chartDescription' => 'Monthly collected revenue across the portfolio.',
  'chartBars' => [
    ['label' => 'Jun', 'value' => '৳ 34L', 'height' => '58%'],
    ['label' => 'Jul', 'value' => '৳ 39L', 'height' => '67%'],
    ['label' => 'Aug', 'value' => '৳ 36L', 'height' => '62%'],
    ['label' => 'Sep', 'value' => '৳ 43L', 'height' => '75%'],
    ['label' => 'Oct', 'value' => '৳ 41L', 'height' => '71%'],
    ['label' => 'Nov', 'value' => '৳ 48L', 'height' => '86%'],
  ],
  'breakdown' => [
    ['label' => 'Residential', 'value' => 84, 'color' => 'breakdown-blue'],
    ['label' => 'Commercial', 'value' => 72, 'color' => 'breakdown-green'],
    ['label' => 'Short stay', 'value' => 63, 'color' => 'breakdown-amber'],
  ],
  'stats' => [
    ['label' => 'Portfolio occupancy', 'value' => '84%', 'note' => 'Up 3.2% this quarter', 'icon' => 'fa-solid fa-building-circle-check', 'color' => 'stat-blue'],
    ['label' => 'Revenue this month', 'value' => '৳ 48.2L', 'note' => 'Across active properties', 'icon' => 'fa-solid fa-chart-line', 'color' => 'stat-green'],
    ['label' => 'New bookings', 'value' => '32', 'note' => 'This month', 'icon' => 'fa-solid fa-calendar-check', 'color' => 'stat-purple'],
    ['label' => 'Open requests', 'value' => '17', 'note' => 'Visits and maintenance', 'icon' => 'fa-solid fa-list-check', 'color' => 'stat-amber'],
  ],
  'listTitle' => 'Portfolio snapshot',
  'listDescription' => 'Sample performance indicators across property groups.',
  'columns' => [
    ['label' => 'Portfolio', 'key' => 'portfolio'],
    ['label' => 'Properties', 'key' => 'properties'],
    ['label' => 'Occupancy', 'key' => 'occupancy'],
    ['label' => 'Monthly revenue', 'key' => 'revenue'],
    ['label' => 'Open requests', 'key' => 'requests'],
    ['label' => 'Trend', 'key' => 'trend', 'type' => 'status'],
  ],
  'rows' => [
    ['portfolio' => 'Residential · Gulshan', 'properties' => '48', 'occupancy' => '91%', 'revenue' => '৳ 18.6L', 'requests' => '05', 'trend' => 'Active'],
    ['portfolio' => 'Residential · Dhanmondi', 'properties' => '62', 'occupancy' => '86%', 'revenue' => '৳ 14.2L', 'requests' => '04', 'trend' => 'Active'],
    ['portfolio' => 'Residential · Uttara', 'properties' => '54', 'occupancy' => '79%', 'revenue' => '৳ 9.8L', 'requests' => '06', 'trend' => 'Review'],
    ['portfolio' => 'Commercial · Dhaka', 'properties' => '28', 'occupancy' => '76%', 'revenue' => '৳ 5.6L', 'requests' => '02', 'trend' => 'Active'],
  ],
];
require __DIR__ . '/_management_page.php';
