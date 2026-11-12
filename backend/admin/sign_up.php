<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#102b2b">
  <meta name="description" content="Create your EstateFlow account and find a place to call home.">
  <title>Create your account | EstateFlow</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../shared/assets/css/global.css">
  <link rel="stylesheet" href="assets/css/admin/auth.css">
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
      <p class="auth-intro">Join EstateFlow to explore homes and keep your search moving.</p>

      <form class="auth-form signup-form" id="signup-form">
        <fieldset class="signup-intent">
          <legend>I’m looking to</legend>
          <div class="signup-intent-options">
            <label class="signup-intent-option">
              <input type="radio" name="customer_type" value="buyer" checked>
              <span><strong>Buy a home</strong><small>Find a place to call yours</small></span>
            </label>
            <label class="signup-intent-option">
              <input type="radio" name="customer_type" value="tenant">
              <span><strong>Rent a home</strong><small>Find your next place</small></span>
            </label>
          </div>
        </fieldset>

        <div class="signup-field-grid">
          <div class="signup-field signup-field-wide">
            <label for="name">Full name</label>
            <input id="name" name="name" type="text" autocomplete="name" placeholder="e.g. Samira Rahman" maxlength="100" required>
          </div>
          <div class="signup-field">
            <label for="email">Email address</label>
            <input id="email" name="email" type="email" autocomplete="email" placeholder="you@example.com" maxlength="100" required>
          </div>
          <div class="signup-field">
            <label for="phone">Phone number <span class="signup-optional">Optional</span></label>
            <input id="phone" name="phone" type="tel" autocomplete="tel" placeholder="+880 1XXX-XXXXXX" maxlength="20">
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

        <p class="signup-notice" id="signup-notice" role="status" hidden></p>
        <button type="submit">Create my account <span aria-hidden="true">→</span></button>
      </form>

      <p class="auth-footnote signup-preview-note">This sign-up preview does not save account details yet.</p>
      <p class="signup-login-prompt">Already have an account? <a href="index.php">Sign in</a></p>
    </section>
  </main>
  <script>
    const signupForm = document.getElementById('signup-form');
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirm-password');
    const signupNotice = document.getElementById('signup-notice');

    function validatePasswordMatch() {
      confirmPasswordInput.setCustomValidity(
        confirmPasswordInput.value && confirmPasswordInput.value !== passwordInput.value
          ? 'Passwords do not match.'
          : ''
      );
    }

    passwordInput.addEventListener('input', validatePasswordMatch);
    confirmPasswordInput.addEventListener('input', validatePasswordMatch);
    signupForm.addEventListener('submit', function (event) {
      event.preventDefault();
      signupNotice.textContent = 'This preview is not connected to account creation. Your details have not been saved.';
      signupNotice.hidden = false;
    });
  </script>
</body>

</html>