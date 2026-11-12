<?php session_start(); ?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#101d35">
    <meta name="description" content="You do not have permission to access this EstateFlow page.">
    <title>Access denied | EstateFlow</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../shared/assets/css/global.css">
    <style>
      body {
        display: flex;
        min-height: 100vh;
        flex-direction: column;
        background:
          radial-gradient(ellipse at 82% 15%, rgb(49 93 154 / 9%), transparent 32rem),
          var(--ef-page);
      }

      .page-header {
        display: flex;
        width: min(100% - 48px, 1120px);
        height: 88px;
        align-items: center;
        justify-content: space-between;
        margin: 0 auto;
      }

      .brand {
        display: inline-flex;
        align-items: center;
        gap: 11px;
        color: var(--ef-navy-950);
        font-family: var(--ef-font-heading);
        font-size: 21px;
        font-weight: 800;
        letter-spacing: -.055em;
      }

      .brand-name span {
        color: var(--ef-teal-700);
      }

      .brand-symbol {
        display: grid;
        width: 37px;
        height: 37px;
        place-items: center;
        border-radius: 12px;
        color: #fff;
        background: var(--ef-navy-950);
      }

      .brand-symbol svg {
        width: 20px;
        height: 20px;
      }

      .header-label {
        color: var(--ef-muted);
        font-size: 12px;
        font-weight: 600;
        letter-spacing: .08em;
        text-transform: uppercase;
      }

      .main-content {
        display: grid;
        flex: 1;
        place-items: center;
        padding: 48px 24px 72px;
      }

      .error-card {
        width: min(100%, 560px);
        padding: clamp(32px, 7vw, 58px);
        border: 1px solid var(--ef-border);
        border-radius: 24px;
        background: var(--ef-surface);
        box-shadow: 0 24px 70px rgb(22 37 63 / 8%);
        text-align: center;
      }

      .error-icon {
        display: grid;
        width: 76px;
        height: 76px;
        place-items: center;
        margin: 0 auto 26px;
        border: 1px solid #f3d9db;
        border-radius: 24px;
        color: var(--ef-red-700);
        background: var(--ef-red-100);
      }

      .error-icon svg {
        width: 34px;
        height: 34px;
      }

      .error-code {
        margin: 0 0 10px;
        color: var(--ef-red-700);
        font-size: 12px;
        font-weight: 700;
        letter-spacing: .14em;
        text-transform: uppercase;
      }

      h1 {
        margin: 0;
        color: var(--ef-navy-950);
        font-family: var(--ef-font-heading);
        font-size: clamp(30px, 6vw, 42px);
        font-weight: 700;
        letter-spacing: -.045em;
        line-height: 1.15;
      }

      .message {
        max-width: 390px;
        margin: 16px auto 0;
        color: var(--ef-muted);
        font-size: 15px;
        line-height: 1.75;
      }

      .actions {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 12px;
        margin-top: 32px;
      }

      .button {
        display: inline-flex;
        min-height: 46px;
        align-items: center;
        justify-content: center;
        gap: 9px;
        padding: 0 19px;
        border: 1px solid transparent;
        border-radius: 10px;
        font-size: 13px;
        font-weight: 600;
        transition: background-color .18s ease, border-color .18s ease, transform .18s ease;
      }

      .button:hover {
        transform: translateY(-1px);
      }

      .button-primary {
        color: #fff;
        background: var(--ef-navy-950);
      }

      .button-primary:hover {
        background: var(--ef-navy-800);
      }

      .button-secondary {
        border-color: var(--ef-border);
        color: var(--ef-ink);
        background: #fff;
      }

      .button-secondary:hover {
        border-color: #cbd4e2;
        background: var(--ef-page);
      }

      .button svg {
        width: 16px;
        height: 16px;
      }

      .help-note {
        margin: 28px 0 0;
        padding-top: 20px;
        border-top: 1px solid var(--ef-border);
        color: var(--ef-muted);
        font-size: 12px;
        line-height: 1.7;
      }

      .page-footer {
        padding: 0 24px 26px;
        color: var(--ef-muted);
        font-size: 11px;
        text-align: center;
      }

      @media (max-width: 480px) {
        .page-header {
          width: min(100% - 32px, 1120px);
          height: 72px;
        }

        .header-label {
          font-size: 10px;
        }

        .main-content {
          padding: 28px 16px 48px;
        }

        .error-card {
          border-radius: 18px;
        }

        .actions {
          flex-direction: column;
        }
      }
    </style>
  </head>
  <body>
    <header class="page-header">
      <a class="brand" href="../../frontend/user/index.php" aria-label="EstateFlow home">
        <span class="brand-symbol" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none">
            <path d="m3.5 10 8.5-7 8.5 7v10.5h-6v-6h-5v6h-6V10Z" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"/>
          </svg>
        </span>
        <span class="brand-name">Estate<span>Flow</span></span>
      </a>
      <span class="header-label">Admin workspace</span>
    </header>

    <main class="main-content">
      <section class="error-card" aria-labelledby="error-heading">
        <div class="error-icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none">
            <path d="M12 3 21 19H3L12 3Z" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7"/>
            <path d="M12 9v4m0 3h.01" stroke="currentColor" stroke-linecap="round" stroke-width="1.9"/>
          </svg>
        </div>
        <p class="error-code">Error 403 · Access restricted</p>
        <h1 id="error-heading">You can’t access this page</h1>
        <p class="message">
          Your account doesn’t have permission to view this part of the workspace.
          If you think this is a mistake, contact your administrator.
        </p>
        <div class="actions">
          <a class="button button-primary" href="index.php">
            Return to sign in
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <path d="M5 12h14m-6-6 6 6-6 6" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"/>
            </svg>
          </a>
          <a class="button button-secondary" href="../../frontend/user/index.php">Go to homepage</a>
        </div>
        <p class="help-note">Need access? Ask your EstateFlow workspace administrator to review your account permissions.</p>
      </section>
    </main>

    <footer class="page-footer">EstateFlow Admin <span aria-hidden="true">·</span> Administrator workspace</footer>
  </body>
</html>
