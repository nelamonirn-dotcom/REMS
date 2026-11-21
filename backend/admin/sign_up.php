<?php
session_start();
include_once __DIR__ . '/dbconfig.php'; // database connection ($conn)

$escape = static fn(string $value): string => htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
if (empty($_SESSION['signup_csrf'])) {
  $_SESSION['signup_csrf'] = bin2hex(random_bytes(32));
}

$error = '';
$name = '';
$email = '';
$phone = '';
$accountType = 'buyer';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $csrf = $_POST['csrf'] ?? '';
  if (!is_string($csrf) || !hash_equals($_SESSION['signup_csrf'], $csrf)) {
    http_response_code(400);
    exit('Invalid request.');
  }

  $nameInput = $_POST['name'] ?? '';
  $emailInput = $_POST['email'] ?? '';
  $phoneInput = $_POST['phone'] ?? '';
  $accountTypeInput = $_POST['customer_type'] ?? '';
  $passwordInput = $_POST['password'] ?? '';
  $confirmInput = $_POST['confirm_password'] ?? '';

  $name = is_string($nameInput) ? trim($nameInput) : '';
  $email = is_string($emailInput) ? strtolower(trim($emailInput)) : '';
  $phone = is_string($phoneInput) ? trim($phoneInput) : '';
  $accountType = is_string($accountTypeInput) ? $accountTypeInput : '';
  $password = is_string($passwordInput) ? $passwordInput : '';
  $confirm = is_string($confirmInput) ? $confirmInput : '';

  if ($name === '' || $email === '' || $password === '') {
    $error = 'Name, email ar password dite hobe.';
  } elseif (strlen($name) > 100 || strlen($email) > 100 || strlen($phone) > 20) {
    $error = 'Name, email, or phone number exceeds the allowed length.';
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = 'Email thik nai.';
  } elseif (!in_array($accountType, ['buyer', 'tenant', 'owner'], true)) {
    $error = 'Account type thik nai.';
  } elseif (strlen($password) < 8) {
    $error = 'Password komporkhome 8 ta character hote hobe.';
  } elseif ($password !== $confirm) {
    $error = 'Password duto mile ni.';
  } else {
    $check = $conn->prepare('SELECT user_id FROM users WHERE email = ?');
    $check->bind_param('s', $email);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
      $error = 'Ei email diye ager theke account ache. Sign in koro.';
    } else {
      $hash = password_hash($password, PASSWORD_DEFAULT);
      $phoneValue = $phone !== '' ? $phone : null;
      $role = $accountType === 'owner' ? 'owner' : 'customer';

      try {
        $stmt = $conn->prepare('INSERT INTO users (name, email, password_hash, phone, role) VALUES (?, ?, ?, ?, ?)');
        $stmt->bind_param('sssss', $name, $email, $hash, $phoneValue, $role);
        $stmt->execute();
        header('Location: index.php?registered=1');
        exit;
      } catch (mysqli_sql_exception $exception) {
        if ((int)$exception->getCode() !== 1062) {
          throw $exception;
        }
        $error = 'Ei email diye ager theke account ache. Sign in koro.';
      }
    }
  }
}
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#e8f5e9">
  <meta name="description" content="Create an EstateFlow account to find or list a property.">
  <title>Create your account | EstateFlow</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../shared/assets/css/global.css">
  <link rel="stylesheet" href="assets/css/admin/auth.css">
  <link rel="stylesheet" href="../shared/assets/css/palette.css">
</head> 

<body class="admin-auth signup-page">
  <main class="auth-card signup-card" aria-labelledby="signup-heading">
    <aside class="signup-story" aria-label="About EstateFlow">
      <a class="auth-brand signup-brand" href="../index.php" aria-label="EstateFlow home">
        <span class="auth-brand-symbol" aria-hidden="true">⌂</span>
        <span>Estate<span>Flow</span></span>
      </a>
      <div class="signup-story-copy">
        <p class="auth-eyebrow">A BETTER WAY TO FIND HOME</p>
        <h1>Make room for what’s next.</h1>
        <p>Find a place that fits your life, with thoughtful homes and helpful local guidance at every step.</p>
      </div>
      <div class="signup-story-note">
        <span class="signup-story-mark" aria-hidden="true">“</span>
        <p>A good move starts with finding a place that feels like yours.</p>
      </div>
    </aside>

    <section class="signup-content">
      <div class="signup-mobile-brand">
        <a class="auth-brand" href="../index.php" aria-label="EstateFlow home">
          <span class="auth-brand-symbol" aria-hidden="true">⌂</span>
          <span>Estate<span>Flow</span></span>
        </a>
      </div>
      <p class="auth-eyebrow">YOUR NEXT CHAPTER STARTS HERE</p>
      <h2 id="signup-heading">Create your account</h2>
      <p class="auth-intro">Join EstateFlow to explore homes or manage your property listings.</p>

      <form class="auth-form signup-form" id="signup-form" method="post" action="">
        <input type="hidden" name="csrf" value="<?= $escape($_SESSION['signup_csrf']) ?>">
        <fieldset class="signup-intent">
          <legend>Choose your account type</legend>
          <div class="signup-intent-options">
            <label class="signup-intent-option">
              <input type="radio" name="customer_type" value="buyer" <?= $accountType === 'buyer' ? 'checked' : '' ?> required>
              <span><strong>Buy a home</strong><small>Find a place to call yours</small></span>
            </label>
            <label class="signup-intent-option">
              <input type="radio" name="customer_type" value="tenant" <?= $accountType === 'tenant' ? 'checked' : '' ?>>
              <span><strong>Rent a home</strong><small>Find your next place</small></span>
            </label>
            <label class="signup-intent-option signup-intent-option-owner">
              <input type="radio" name="customer_type" value="owner" <?= $accountType === 'owner' ? 'checked' : '' ?>>
              <span><strong>List a property</strong><small>Manage your properties</small></span>
            </label>
          </div>
        </fieldset>

        <div class="signup-field-grid">
          <div class="signup-field signup-field-wide">
            <label for="name">Full name</label>
            <input id="name" name="name" type="text" autocomplete="name" placeholder="e.g. Samira Rahman" maxlength="100" required value="<?= $escape($name) ?>">
          </div>
          <div class="signup-field">
            <label for="email">Email address</label>
            <input id="email" name="email" type="email" autocomplete="email" placeholder="you@example.com" maxlength="100" required value="<?= $escape($email) ?>">
          </div>
          <div class="signup-field">
            <label for="phone">Phone number <span class="signup-optional">Optional</span></label>
            <input id="phone" name="phone" type="tel" autocomplete="tel" placeholder="+880 1XXX-XXXXXX" maxlength="20" value="<?= $escape($phone) ?>">
          </div>
          <div class="signup-field">
            <label for="password">Password</label>
            <input id="password" name="password" type="password" autocomplete="new-password" placeholder="At least 8 characters" minlength="8" required>
          </div>
          <div class="signup-field">
            <label for="confirm-password">Confirm password</label>
            <input id="confirm-password" name="confirm_password" type="password" autocomplete="new-password" placeholder="Enter your password again" minlength="8" required>
          </div>
        </div>

        <p class="signup-notice" id="signup-notice" role="alert" <?= $error === '' ? 'hidden' : '' ?>><?= $escape($error) ?></p>
        <button type="submit">Create my account <span aria-hidden="true">→</span></button>
      </form>

      <p class="signup-login-prompt">Already have an account? <a href="index.php">Sign in</a></p>
    </section>
  </main>
  <script>
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirm-password');

    function validatePasswordMatch() {
      confirmPasswordInput.setCustomValidity(
        confirmPasswordInput.value && confirmPasswordInput.value !== passwordInput.value
          ? 'Passwords do not match.'
          : ''
      );
    }

    passwordInput.addEventListener('input', validatePasswordMatch);
    confirmPasswordInput.addEventListener('input', validatePasswordMatch);
  </script>
</body>

</html>