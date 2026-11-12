<?php
$managementPage = [
  'title' => 'Settings',
  'category' => 'Administration',
  'description' => 'Manage workspace preferences, notifications and account defaults.',
  'kind' => 'settings',
  'stats' => [
    ['label' => 'Workspace', 'value' => 'EstateFlow', 'note' => 'Realty administrator account', 'icon' => 'fa-solid fa-building', 'color' => 'stat-blue'],
    ['label' => 'Timezone', 'value' => 'UTC +06:00', 'note' => 'Dhaka Standard Time', 'icon' => 'fa-regular fa-clock', 'color' => 'stat-purple'],
    ['label' => 'Notifications', 'value' => 'Enabled', 'note' => 'Email and in-app updates', 'icon' => 'fa-regular fa-bell', 'color' => 'stat-green'],
    ['label' => 'Security', 'value' => 'Protected', 'note' => 'Administrator access', 'icon' => 'fa-solid fa-shield-halved', 'color' => 'stat-amber'],
  ],
  'settings' => [
    ['title' => 'Workspace name', 'description' => 'Name shown to your team in the admin workspace.', 'value' => 'EstateFlow Realty', 'control' => 'text'],
    ['title' => 'Default currency', 'description' => 'Currency used for portfolio and payment summaries.', 'value' => 'BDT · ৳', 'control' => 'select'],
    ['title' => 'Email notifications', 'description' => 'Receive updates about bookings and payments.', 'value' => 'On', 'control' => 'toggle'],
    ['title' => 'Maintenance alerts', 'description' => 'Notify administrators about urgent property requests.', 'value' => 'On', 'control' => 'toggle'],
    ['title' => 'Weekly summary', 'description' => 'Send a weekly overview of workspace activity.', 'value' => 'On', 'control' => 'toggle'],
    ['title' => 'Default timezone', 'description' => 'Used when displaying dates across the workspace.', 'value' => 'Asia/Dhaka (UTC+06:00)', 'control' => 'select'],
  ],
];
require __DIR__ . '/_management_page.php';
