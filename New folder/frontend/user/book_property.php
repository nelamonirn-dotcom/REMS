<?php
session_start();
if (!isset($_SESSION['email'])) {
  header("Location: ../../backend/admin/index.php");
  exit;
}
if ($_SESSION['role'] != "customer") {
  header("Location: ../../backend/admin/unauthorize.php");
  exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header("Location: customers_dashboard.php#properties");
  exit;
}

// dbconfig.php koi ache seta auto khuje nibe
$dbCandidates = [
  __DIR__ . '/dbconfig.php',
  __DIR__ . '/../dbconfig.php',
  __DIR__ . '/../../dbconfig.php',
  __DIR__ . '/../../backend/dbconfig.php',
  __DIR__ . '/../../backend/admin/dbconfig.php',
];
foreach ($dbCandidates as $file) {
  if (file_exists($file)) { include_once $file; break; }
}
if (!isset($conn)) {
  die('dbconfig.php khuje pawa jayni.');
}

/* ---- CONFIG: users table er nam/column thik na hole eta change koro ---- */
$usersTable    = 'users';    // login er account table
$usersIdCol    = 'user_id';  // users table er primary key (customers.user_id er sathe mile)
$usersEmailCol = 'email';    // users table er email column
/* ------------------------------------------------------------- */

$propertyId  = (int)($_POST['property_id'] ?? 0);
$bookingDate = trim($_POST['booking_date'] ?? '');

function back($id, $key) {
  header("Location: property-details.php?id=" . (int)$id . "&" . $key . "#booking");
  exit;
}

// customer_id ber koro: users (email) -> customers (user_id) -> customer_id
$customerId = $_SESSION['customer_id'] ?? null;
if (!$customerId) {
  try {
    $q = $conn->prepare("SELECT c.customer_id
                         FROM customers c
                         JOIN `$usersTable` u ON u.`$usersIdCol` = c.user_id
                         WHERE u.`$usersEmailCol` = ? LIMIT 1");
    $q->bind_param('s', $_SESSION['email']);
    $q->execute();
    $row = $q->get_result()->fetch_row();
    $customerId = $row[0] ?? null;
  } catch (mysqli_sql_exception $e) {
    die('Customer lookup fail: ' . htmlspecialchars($e->getMessage()) . ' (CONFIG er users table/column nam check koro)');
  }
}
// customers e row na thakle auto toiri koro
if (!$customerId) {
  try {
    $q = $conn->prepare("SELECT `$usersIdCol` FROM `$usersTable` WHERE `$usersEmailCol` = ? LIMIT 1");
    $q->bind_param('s', $_SESSION['email']);
    $q->execute();
    $u = $q->get_result()->fetch_row();
    if ($u) {
      $uid = (int)$u[0];
      $q = $conn->prepare("INSERT INTO customers (user_id, customer_type) VALUES (?, 'buyer')");
      $q->bind_param('i', $uid);
      $q->execute();
      $customerId = $conn->insert_id;
    }
  } catch (mysqli_sql_exception $e) {
    die('Customer row toiri kora gelo na: ' . htmlspecialchars($e->getMessage()));
  }
}
if (!$customerId) { back($propertyId, 'booking=error'); }
$customerId = (int)$customerId;

// property ache ar available kina
$q = $conn->prepare("SELECT status FROM properties WHERE property_id = ? LIMIT 1");
$q->bind_param('i', $propertyId);
$q->execute();
$prop = $q->get_result()->fetch_assoc();
if (!$prop) { header("Location: customers_dashboard.php#properties"); exit; }
if ($prop['status'] !== 'available') { back($propertyId, 'booking=unavailable'); }

// date validation
$ts = strtotime($bookingDate);
if ($bookingDate === '' || $ts === false || $ts < time()) {
  back($propertyId, 'booking=baddate');
}
$dt = date('Y-m-d H:i:s', $ts);

// ekoi property te already pending booking ache kina
$q = $conn->prepare("SELECT booking_id FROM bookings
                     WHERE property_id = ? AND customer_id = ? AND status = 'pending' LIMIT 1");
$q->bind_param('ii', $propertyId, $customerId);
$q->execute();
if ($q->get_result()->num_rows > 0) { back($propertyId, 'booking=exists'); }

// insert
$q = $conn->prepare("INSERT INTO bookings (property_id, customer_id, booking_date) VALUES (?, ?, ?)");
$q->bind_param('iis', $propertyId, $customerId, $dt);
$q->execute();

back($propertyId, 'booking=success');