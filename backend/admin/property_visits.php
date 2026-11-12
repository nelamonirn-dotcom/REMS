<?php
$managementPage = [
  'title' => 'Property visits',
  'category' => 'Management',
  'description' => 'Coordinate upcoming property tours and keep track of visit requests.',
  'stats' => [
    ['label' => 'Scheduled visits', 'value' => '24', 'note' => 'Across all properties', 'icon' => 'fa-solid fa-calendar-days', 'color' => 'stat-blue'],
    ['label' => 'Today', 'value' => '06', 'note' => 'Visits on the calendar', 'icon' => 'fa-regular fa-clock', 'color' => 'stat-amber'],
    ['label' => 'Completed', 'value' => '18', 'note' => 'This week', 'icon' => 'fa-solid fa-circle-check', 'color' => 'stat-green'],
    ['label' => 'Awaiting reply', 'value' => '05', 'note' => 'Guest confirmation needed', 'icon' => 'fa-regular fa-hourglass-half', 'color' => 'stat-purple'],
  ],
  'listTitle' => 'Upcoming visits',
  'listDescription' => 'A sample schedule of property viewings.',
  'columns' => [
    ['label' => 'Visit ID', 'key' => 'id'],
    ['label' => 'Guest', 'key' => 'guest'],
    ['label' => 'Property', 'key' => 'property'],
    ['label' => 'Date & time', 'key' => 'date'],
    ['label' => 'Assigned agent', 'key' => 'agent'],
    ['label' => 'Status', 'key' => 'status', 'type' => 'status'],
  ],
  'rows' => [
    ['id' => 'VIS-0184', 'guest' => 'Nadia Karim', 'property' => 'Lakeview Heights, Apt 5B', 'date' => 'Nov 12 · 10:30 AM', 'agent' => 'Rafi Ahmed', 'status' => 'Confirmed'],
    ['id' => 'VIS-0183', 'guest' => 'Imran Hossain', 'property' => 'Palm Grove Apartment', 'date' => 'Nov 12 · 1:00 PM', 'agent' => 'Sadia Noor', 'status' => 'Pending'],
    ['id' => 'VIS-0182', 'guest' => 'Maliha Chowdhury', 'property' => 'Cedar Court, Unit 3C', 'date' => 'Nov 13 · 11:00 AM', 'agent' => 'Tariq Hasan', 'status' => 'Confirmed'],
    ['id' => 'VIS-0181', 'guest' => 'Fahim Rahman', 'property' => 'Riverside Tower, 8A', 'date' => 'Nov 13 · 3:30 PM', 'agent' => 'Rafi Ahmed', 'status' => 'Pending'],
    ['id' => 'VIS-0180', 'guest' => 'Sanjida Alam', 'property' => 'The Grand Residence', 'date' => 'Nov 14 · 9:00 AM', 'agent' => 'Mahin Islam', 'status' => 'Confirmed'],
  ],
];
require __DIR__ . '/_management_page.php';
