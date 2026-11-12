<?php
$managementPage = [
  'title' => 'Notifications',
  'category' => 'Administration',
  'description' => 'Stay up to date on bookings, payments and property activity.',
  'stats' => [
    ['label' => 'Unread', 'value' => '03', 'note' => 'Needs your attention', 'icon' => 'fa-regular fa-bell', 'color' => 'stat-blue'],
    ['label' => 'Today', 'value' => '08', 'note' => 'New updates received', 'icon' => 'fa-regular fa-calendar', 'color' => 'stat-purple'],
    ['label' => 'Booking updates', 'value' => '12', 'note' => 'In the last 7 days', 'icon' => 'fa-solid fa-calendar-check', 'color' => 'stat-green'],
    ['label' => 'Payment alerts', 'value' => '04', 'note' => 'In the last 7 days', 'icon' => 'fa-solid fa-wallet', 'color' => 'stat-amber'],
  ],
  'listTitle' => 'Recent notifications',
  'listDescription' => 'A sample activity feed for your administrator account.',
  'columns' => [
    ['label' => 'Notification', 'key' => 'title'],
    ['label' => 'Details', 'key' => 'details'],
    ['label' => 'Category', 'key' => 'category'],
    ['label' => 'Received', 'key' => 'date'],
    ['label' => 'Status', 'key' => 'status', 'type' => 'status'],
  ],
  'rows' => [
    ['title' => 'New booking request', 'details' => 'Arif Rahman requested Lakeview Heights, Apt 5B.', 'category' => 'Bookings', 'date' => '10 min ago', 'status' => 'Unread'],
    ['title' => 'Payment received', 'details' => '৳ 85,000 payment received for booking BK-2048.', 'category' => 'Payments', 'date' => '45 min ago', 'status' => 'Unread'],
    ['title' => 'Visit confirmed', 'details' => 'Nadia Karim confirmed a property visit for Nov 12.', 'category' => 'Visits', 'date' => '2 hours ago', 'status' => 'Unread'],
    ['title' => 'Agreement signed', 'details' => 'Agreement AGR-0086 has been signed by both parties.', 'category' => 'Agreements', 'date' => 'Yesterday', 'status' => 'Read'],
    ['title' => 'Maintenance request updated', 'details' => 'Request MNT-0294 is now being handled.', 'category' => 'Maintenance', 'date' => 'Yesterday', 'status' => 'Read'],
  ],
];
require __DIR__ . '/_management_page.php';
