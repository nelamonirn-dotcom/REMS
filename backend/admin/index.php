<?php
session_start();
include_once __DIR__ . '/dbconfig.php'; // database connection ($conn)

$error = '';

if (isset($_POST['login'])) {
  $email    = strtolower(trim($_POST['email'] ?? ''));
  $password = $_POST['password'] ?? '';

  // prepared statement: SQL injection theke safe
  $stmt = $conn->prepare("SELECT user_id, name, email, password_hash, role, status FROM users WHERE email = ?");
  $stmt->bind_param('s', $email);
  $stmt->execute();
  $row = $stmt->get_result()->fetch_assoc();

  $valid = false;
  if ($row) {
    if (password_verify($password, $row['password_hash'])) {
      // notun account (signup page theke) - password_hash()
      $valid = true;
    } elseif (hash_equals($row['password_hash'], sha1($password))) {
      // purono account - sha1. Login hole auto notun hash e upgrade hobe
      $valid = true;
      $newHash = password_hash($password, PASSWORD_DEFAULT);
      $up = $conn->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
      $up->bind_param('si', $newHash, $row['user_id']);
      $up->execute();
    }
  }

  if (!$valid) {
    $error = 'Email ba password thik nai.';
  } elseif ($row['status'] !== 'active') {
    $error = 'Ei account ta inactive. Admin er shathe jogajog koro.';
  } else {
    session_regenerate_id(true);
    $_SESSION['email']   = $row['email'];
    $_SESSION['name']    = $row['name'];
    $_SESSION['role']    = $row['role'];
    $_SESSION['user_id'] = $row['user_id'];

    if ($row['role'] == "admin") {
      $_SESSION['url'] = 'users_dashboard.php';
      header("Location: users_dashboard.php");
    } elseif ($row['role'] == "customer") {
      $_SESSION['url'] = '../../frontend/user/customers_dashboard.php';
      header("Location: ../../frontend/user/customers_dashboard.php");
    } elseif ($row['role'] == "agent") {
      $_SESSION['url'] = '../agent/agents_dashboard.php';
      header("Location: ../agent/agents_dashboard.php");
    } elseif ($row['role'] == "owner") {
      $_SESSION['url'] = '../owner/owner_dashboard.php';
      header("Location: ../owner/owner_dashboard.php");
    }
    exit;
  }
}
?>

<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#101d35">
  <title>Administrator sign in | EstateFlow</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../shared/assets/css/global.css">
  <link rel="stylesheet" href="assets/css/admin/auth.css">
</head>

<body class="admin-auth">
  <main class="auth-card" aria-labelledby="login-heading">
    <a class="auth-brand" href="../../frontend/user/index.php" aria-label="EstateFlow home">
      <span class="auth-brand-symbol" aria-hidden="true">⌂</span>
      <span>Estate<span>Flow</span></span>
    </a>
    <p class="auth-eyebrow">ADMIN WORKSPACE</p>
    <h1 id="login-heading">Welcome back</h1>
    <p class="auth-intro">Sign in with your administrator account to continue.</p>

    <?php if (isset($_GET['registered'])): ?>
      <p class="signup-notice" role="status">Account toiri hoyeche. Ekhon sign in koro.</p>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
      <p class="signup-notice" role="alert"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form class="auth-form" method="post" action="">
      <label for="email">Email address</label>
      <input id="email" name="email" type="email" autocomplete="username" maxlength="100" value="<?= htmlspecialchars($_POST['email'] ?? '@gmail.com') ?>" required autofocus>
      <label for="password">Password</label>
      <input id="password" name="password" type="password" autocomplete="current-password" required>
      <button type="submit" name="login">Sign in <span aria-hidden="true">→</span></button>
      <a href="sign_up.php"> Creat New account</a>
    </form>

    <p class="auth-footnote">Administrator access only. Contact your system administrator if you need an account.</p>
  </main>
</body>

</html>