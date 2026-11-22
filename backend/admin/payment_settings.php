<?php
session_start();
if (!isset($_SESSION['email'])) {
  header('Location: index.php');
  exit;
}
if (($_SESSION['role'] ?? '') !== 'admin') {
  header('Location: unauthorize.php');
  exit;
}

include_once __DIR__ . '/dbconfig.php';
require_once __DIR__ . '/../../frontend/user/sslcommerz_helper.php';

$configFile = __DIR__ . '/../../frontend/user/payment_config.php';

if (empty($_SESSION['csrf'])) {
  $_SESSION['csrf'] = bin2hex(random_bytes(16));
}

$notice = null;
$testResult = null;

// Read existing config
$config = is_file($configFile) ? require $configFile : [];
if (!is_array($config)) {
  $config = [];
}

$currentStoreId = $config['store_id'] ?? 'testbox';
$currentStorePassword = $config['store_password'] ?? 'qwerty';
$currentIsSandbox = isset($config['is_sandbox']) ? (bool)$config['is_sandbox'] : true;
$currentBaseUrl = $config['base_url'] ?? 'http://localhost/REMS';
$currentSslVerify = isset($config['ssl_verify']) ? (bool)$config['ssl_verify'] : false;

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $csrf = $_POST['csrf'] ?? '';
  if (!is_string($csrf) || !hash_equals($_SESSION['csrf'], $csrf)) {
    die('Invalid CSRF token.');
  }

  $action = $_POST['action'] ?? 'save';

  if ($action === 'save') {
    $storeId = trim((string)($_POST['store_id'] ?? ''));
    $storePassword = trim((string)($_POST['store_password'] ?? ''));
    $isSandbox = ($_POST['environment'] ?? 'sandbox') === 'sandbox';
    $baseUrl = rtrim(trim((string)($_POST['base_url'] ?? 'http://localhost/REMS')), '/');
    $sslVerify = !empty($_POST['ssl_verify']);

    if ($storeId === '' || $storePassword === '') {
      $notice = ['danger', 'Store ID and Store Password cannot be empty.'];
    } else {
      $newConfig = [
        'store_id'       => $storeId,
        'store_password' => $storePassword,
        'is_sandbox'     => $isSandbox,
        'base_url'       => $baseUrl,
        'ssl_verify'     => $sslVerify,
      ];

      $content = "<?php\n// SSLCommerz Payment Gateway Configuration\n// Updated via Admin Dashboard on " . date('Y-m-d H:i:s') . "\n\nreturn " . var_export($newConfig, true) . ";\n";
      if (file_put_contents($configFile, $content) !== false) {
        $notice = ['success', 'SSLCommerz payment gateway settings saved successfully!'];
        $currentStoreId = $storeId;
        $currentStorePassword = $storePassword;
        $currentIsSandbox = $isSandbox;
        $currentBaseUrl = $baseUrl;
        $currentSslVerify = $sslVerify;
      } else {
        $notice = ['danger', 'Failed to save configuration file. Please check folder permissions.'];
      }
    }
  } elseif ($action === 'test_connection') {
    $targetUrl = ($currentIsSandbox ? 'https://sandbox.sslcommerz.com' : 'https://securepay.sslcommerz.com') . '/gwprocess/v4/api.php';
    $testPayload = [
      'store_id'         => $currentStoreId,
      'store_passwd'     => $currentStorePassword,
      'total_amount'     => '100.00',
      'currency'         => 'BDT',
      'tran_id'          => 'TEST' . uniqid(),
      'success_url'      => $currentBaseUrl . '/frontend/user/sslcommerz_return.php',
      'fail_url'         => $currentBaseUrl . '/frontend/user/sslcommerz_return.php',
      'cancel_url'       => $currentBaseUrl . '/frontend/user/sslcommerz_return.php',
      'ipn_url'          => $currentBaseUrl . '/frontend/user/sslcommerz_ipn.php',
      'cus_name'         => 'Connection Test',
      'cus_email'        => 'admin@example.com',
      'cus_phone'        => '01700000000',
      'cus_add1'         => 'Dhaka',
      'cus_city'         => 'Dhaka',
      'cus_country'      => 'Bangladesh',
      'shipping_method'  => 'NO',
      'product_name'     => 'Connection Test',
      'product_category' => 'Real estate',
      'product_profile'  => 'non-physical-goods',
    ];

    try {
      $resp = sslcommerzRequest($targetUrl, $testPayload);
      if (($resp['status'] ?? '') === 'SUCCESS') {
        $testResult = [
          'success' => true,
          'message' => 'Connection verified successfully! SSLCommerz gateway returned SUCCESS and generated a valid test session.',
          'sessionkey' => $resp['sessionkey'] ?? '',
          'gateway_url' => $resp['GatewayPageURL'] ?? $resp['redirectGatewayURL'] ?? '',
        ];
      } else {
        $testResult = [
          'success' => false,
          'message' => 'Gateway reached but returned: ' . ($resp['failedreason'] ?? $resp['status'] ?? 'Unknown error'),
        ];
      }
    } catch (Throwable $e) {
      $testResult = [
        'success' => false,
        'message' => 'Connection failed: ' . $e->getMessage(),
      ];
    }
  }
}

$escape = static fn($value): string => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
$currentAdminPage = 'payment_settings.php';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#e8f5e9">
  <title>SSLCommerz Payment Settings | EstateFlow Admin</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet" referrerpolicy="no-referrer">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../shared/assets/css/global.css">
  <link rel="stylesheet" href="assets/css/admin/dashboard.css">
  <link rel="stylesheet" href="assets/css/admin/management.css">
  <link rel="stylesheet" href="../shared/assets/css/palette.css">
</head>
<body class="admin-dashboard">
  <a class="skip-link" href="#main-content">Skip to main content</a>
  <div class="admin-layout" id="admin-layout">
    <div class="sidebar-backdrop" id="sidebar-backdrop" hidden></div>
    <?php include __DIR__ . '/../shared/partials/admin_leftber.php'; ?>
    <div class="main-shell">
      <?php include __DIR__ . '/../shared/partials/header.php'; ?>
      <main class="dashboard-main" id="main-content">
        <section class="page-heading management-heading">
          <div>
            <p class="page-eyebrow"><span class="live-dot"></span> Finance &amp; Operations</p>
            <h1>SSLCommerz Payment Gateway</h1>
            <p class="page-subtitle">Configure merchant store credentials, test sandbox mode, and manage payment webhooks.</p>
          </div>
          <a class="btn btn-outline-secondary" href="payments.php">
            <i class="fa-solid fa-arrow-left me-1"></i> Back to Payments
          </a>
        </section>

        <?php if ($notice): ?>
          <div class="alert alert-<?= $escape($notice[0]) ?> alert-dismissible fade show" role="alert">
            <i class="fa-solid <?= $notice[0] === 'success' ? 'fa-circle-check' : 'fa-triangle-exclamation' ?> me-2"></i>
            <?= $escape($notice[1]) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
        <?php endif; ?>

        <?php if ($testResult): ?>
          <div class="alert alert-<?= $testResult['success'] ? 'success' : 'danger' ?> alert-dismissible fade show" role="alert">
            <h5 class="alert-heading">
              <i class="fa-solid <?= $testResult['success'] ? 'fa-circle-check' : 'fa-circle-xmark' ?> me-2"></i>
              <?= $testResult['success'] ? 'Gateway Connection Successful' : 'Gateway Connection Test Failed' ?>
            </h5>
            <p class="mb-0"><?= $escape($testResult['message']) ?></p>
            <?php if (!empty($testResult['sessionkey'])): ?>
              <hr>
              <small class="text-muted">Session Key: <code><?= $escape($testResult['sessionkey']) ?></code></small>
            <?php endif; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
        <?php endif; ?>

        <div class="row g-4">
          <!-- Configuration Form -->
          <div class="col-12 col-lg-7">
            <section class="management-panel p-4" aria-labelledby="gateway-form-title">
              <h2 class="h5 mb-3" id="gateway-form-title">
                <i class="fa-solid fa-sliders me-2 text-primary"></i>Merchant Credentials &amp; Environment
              </h2>

              <form method="post" class="row g-3">
                <input type="hidden" name="csrf" value="<?= $escape($_SESSION['csrf']) ?>">
                <input type="hidden" name="action" value="save">

                <div class="col-12">
                  <label class="form-label" for="environment">Environment Mode</label>
                  <select class="form-select" id="environment" name="environment" required>
                    <option value="sandbox" <?= $currentIsSandbox ? 'selected' : '' ?>>Sandbox Mode (Test / Development)</option>
                    <option value="live" <?= !$currentIsSandbox ? 'selected' : '' ?>>Live Mode (Production)</option>
                  </select>
                  <div class="form-text">For testing, leave on <strong>Sandbox Mode</strong> with store ID <code>testbox</code> and password <code>qwerty</code>.</div>
                </div>

                <div class="col-12 col-md-6">
                  <label class="form-label" for="store-id">Store ID</label>
                  <input class="form-control" id="store-id" name="store_id" type="text"
                         value="<?= $escape($currentStoreId) ?>" required placeholder="e.g. testbox">
                </div>

                <div class="col-12 col-md-6">
                  <label class="form-label" for="store-password">Store Password</label>
                  <input class="form-control" id="store-password" name="store_password" type="text"
                         value="<?= $escape($currentStorePassword) ?>" required placeholder="e.g. qwerty">
                </div>

                <div class="col-12">
                  <label class="form-label" for="base-url">Application Base URL</label>
                  <input class="form-control" id="base-url" name="base_url" type="url"
                         value="<?= $escape($currentBaseUrl) ?>" required placeholder="http://localhost/REMS">
                  <div class="form-text">Your root URL without trailing slash (e.g. <code>http://localhost/REMS</code>).</div>
                </div>

                <div class="col-12">
                  <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="ssl_verify" name="ssl_verify" value="1" <?= $currentSslVerify ? 'checked' : '' ?>>
                    <label class="form-check-label" for="ssl_verify">
                      Enable cURL SSL Certificate Verification (Leave OFF for localhost/XAMPP)
                    </label>
                  </div>
                </div>

                <div class="col-12 d-flex gap-2 pt-2">
                  <button class="btn btn-primary" type="submit">
                    <i class="fa-solid fa-floppy-disk me-1"></i> Save Configuration
                  </button>
                  <button class="btn btn-outline-success" type="submit" onclick="this.form.action.value='test_connection';">
                    <i class="fa-solid fa-plug me-1"></i> Test Gateway Connection
                  </button>
                </div>
              </form>
            </section>
          </div>

          <!-- Documentation & Webhook Details -->
          <div class="col-12 col-lg-5">
            <section class="management-panel p-4 mb-4">
              <h2 class="h5 mb-3">
                <i class="fa-solid fa-circle-info me-2 text-info"></i>SSLCommerz Webhooks &amp; URLs
              </h2>
              <p class="text-secondary small">These URLs are automatically registered with SSLCommerz during transaction initiation:</p>

              <div class="mb-3">
                <label class="form-label small fw-bold">Return / Success / Cancel URL:</label>
                <div class="p-2 bg-light border rounded small font-monospace text-break">
                  <?= $escape($currentBaseUrl) ?>/frontend/user/sslcommerz_return.php
                </div>
              </div>

              <div class="mb-3">
                <label class="form-label small fw-bold">Instant Payment Notification (IPN) URL:</label>
                <div class="p-2 bg-light border rounded small font-monospace text-break">
                  <?= $escape($currentBaseUrl) ?>/frontend/user/sslcommerz_ipn.php
                </div>
              </div>
            </section>

            <section class="management-panel p-4">
              <h2 class="h5 mb-3">
                <i class="fa-solid fa-vial-circle-check me-2 text-warning"></i>Sandbox Test Credentials
              </h2>
              <p class="small text-secondary mb-2">When redirected to the SSLCommerz sandbox payment screen, you can test with:</p>
              <ul class="small mb-3 ps-3">
                <li><strong>bKash / Nagad / Rocket:</strong> Select the mobile banking tab, enter any 11-digit number (e.g. <code>01711111111</code>) and any OTP/PIN.</li>
                <li><strong>Cards:</strong> Any 16-digit test card number provided on the gateway screen.</li>
                <li><strong>Amount:</strong> BDT 10 to 500,000 per invoice.</li>
              </ul>
              <a href="https://developer.sslcommerz.com/registration/" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary">
                <i class="fa-solid fa-arrow-up-right-from-square me-1"></i> Register Free Merchant Sandbox Account
              </a>
            </section>
          </div>
        </div>
      </main>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

