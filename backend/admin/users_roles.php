<?php
$managementPage = [
  'title' => 'Users & roles',
  'category' => 'Administration',
  'description' => 'Review administrator, agent and customer access in the workspace.',
  'stats' => [
    ['label' => 'Total users', 'value' => '64', 'note' => 'Across all account types', 'icon' => 'fa-solid fa-users', 'color' => 'stat-blue'],
    ['label' => 'Administrators', 'value' => '04', 'note' => 'Full workspace access', 'icon' => 'fa-solid fa-shield-halved', 'color' => 'stat-purple'],
    ['label' => 'Agents', 'value' => '18', 'note' => 'Property team members', 'icon' => 'fa-solid fa-user-tie', 'color' => 'stat-green'],
    ['label' => 'Pending invites', 'value' => '03', 'note' => 'Awaiting account activation', 'icon' => 'fa-regular fa-envelope', 'color' => 'stat-amber'],
  ],
  'listTitle' => 'Workspace users',
  'listDescription' => 'Sample accounts and their assigned workspace roles.',
  'columns' => [
    ['label' => 'User', 'key' => 'name'],
    ['label' => 'Email', 'key' => 'email'],
    ['label' => 'Role', 'key' => 'role'],
    ['label' => 'Last active', 'key' => 'lastActive'],
    ['label' => 'Joined', 'key' => 'joined'],
    ['label' => 'Status', 'key' => 'status', 'type' => 'status'],
  ],
  'rows' => [
    ['name' => 'Rafi Ahmed', 'email' => 'rafi.ahmed@email.com', 'role' => 'Administrator', 'lastActive' => 'Today, 10:42 AM', 'joined' => 'Jan 12, 2025', 'status' => 'Active'],
    ['name' => 'Sadia Noor', 'email' => 'sadia.noor@email.com', 'role' => 'Agent', 'lastActive' => 'Today, 9:18 AM', 'joined' => 'Mar 08, 2025', 'status' => 'Active'],
    ['name' => 'Tariq Hasan', 'email' => 'tariq.hasan@email.com', 'role' => 'Agent', 'lastActive' => 'Yesterday', 'joined' => 'Apr 21, 2025', 'status' => 'Active'],
    ['name' => 'Maliha Chowdhury', 'email' => 'maliha.c@email.com', 'role' => 'Property owner', 'lastActive' => 'Nov 08, 2026', 'joined' => 'Jun 03, 2025', 'status' => 'Active'],
    ['name' => 'Mahin Islam', 'email' => 'mahin.islam@email.com', 'role' => 'Customer', 'lastActive' => 'Nov 06, 2026', 'joined' => 'Sep 17, 2025', 'status' => 'Inactive'],
  ],
];
require __DIR__ . '/_management_page.php';
