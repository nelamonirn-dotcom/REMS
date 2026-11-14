<?php
session_start();

if (!isset($_SESSION['email'])) {
    header('Location: ../../admin/index.php');
    exit;
}
if (
    ($_SESSION['role'] ?? '') !== 'owner'
    || !isset($_SESSION['user_id'])
    || !ctype_digit((string) $_SESSION['user_id'])
) {
    header('Location: ../../admin/unauthorize.php');
    exit;
}
require_once __DIR__ . '/dbconfig.php';

$escape = static fn(string $value): string => htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$userId = (int) $_SESSION['user_id'];
$profile = null;
$profileError = '';
$profileStmt = $conn->prepare('SELECT user_id, name, email, phone, status, created_at, role FROM users WHERE user_id = ? AND role = ? LIMIT 1');
if (!$profileStmt) {
    error_log('Unable to prepare owner profile query: ' . $conn->error);
    $profileError = 'Your profile could not be loaded. Please try again later.';
} else {
    $role = 'owner';
    $profileStmt->bind_param('is', $userId, $role);
    if (!$profileStmt->execute()) {
        error_log('Unable to load owner profile: ' . $profileStmt->error);
        $profileError = 'Your profile could not be loaded. Please try again later.';
    } else {
        $profile = $profileStmt->get_result()->fetch_assoc();
        if (!$profile) {
            http_response_code(404);
            $profileError = 'No owner profile was found for this account.';
        }
    }
    $profileStmt->close();
}

$profileName = (string) ($profile['name'] ?? '');
$nameParts = preg_split('/\s+/', trim($profileName), -1, PREG_SPLIT_NO_EMPTY) ?: [];
$initials = $nameParts
    ? strtoupper(substr($nameParts[0], 0, 1) . (count($nameParts) > 1 ? substr($nameParts[count($nameParts) - 1], 0, 1) : ''))
    : 'O';
$memberSince = !empty($profile['created_at']) ? date('F j, Y', strtotime($profile['created_at'])) : '—';
$status = (string) ($profile['status'] ?? '');
$statusClass = $status === 'active' ? 'status-active' : 'status-inactive';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Owner profile and account settings.">
    <title>My Profile | EstateFlow</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/owner/profile.css">
</head>
<body class="owner-profile-page">
    <main class="container py-4 py-lg-5">
        <nav class="page-nav d-flex align-items-center justify-content-between mb-5" aria-label="Page navigation">
            <a class="brand-mark text-decoration-none" href="../owner_dashboard.php">
                <span class="brand-icon" aria-hidden="true">E</span>
                <span>EstateFlow</span>
            </a>
            <a class="btn btn-outline-secondary" href="../owner_dashboard.php">Back to dashboard</a>
        </nav>

        <header class="profile-page-heading mb-4">
            <p class="eyebrow mb-2">OWNER WORKSPACE</p>
            <div class="d-flex flex-column flex-md-row align-items-md-end justify-content-between gap-3">
                <div>
                    <h1 class="display-6 fw-bold mb-2">My Profile</h1>
                    <p class="text-secondary mb-0">Manage your personal information and review your account details.</p>
                </div>
                <span class="profile-secure-label"><span aria-hidden="true">✓</span> <?= $escape(ucfirst((string) ($profile['role'] ?? 'owner'))) ?> account</span>
            </div>
        </header>

        <?php if ($profileError !== ''): ?>
            <div class="alert alert-danger" role="alert"><?= $escape($profileError) ?></div>
        <?php endif; ?>

        <section class="profile-card mb-4" aria-labelledby="profile-name">
            <div class="profile-cover"></div>
            <div class="profile-identity">
                <div class="profile-avatar" aria-hidden="true"><?= $escape($initials) ?></div>
                <div class="profile-heading">
                    <h2 id="profile-name" class="h3 mb-1"><?= $escape($profileName ?: 'Property Owner') ?></h2>
                    <p class="mb-2"><?= $escape(ucfirst((string) ($profile['role'] ?? 'owner'))) ?> account</p>
                    <span class="account-badge <?= $statusClass ?>"><span aria-hidden="true"></span> <?= $escape(ucfirst($status ?: 'Unknown')) ?></span>
                </div>
                <span class="member-since">Member since <?= $escape($memberSince) ?></span>
            </div>
        </section>

        <div class="row g-4">
            <div class="col-12 col-lg-7">
                <section class="detail-card profile-form-card h-100" aria-labelledby="personal-info-title">
                    <div class="section-heading">
                        <div>
                            <h2 id="personal-info-title" class="h5 mb-1">Personal information</h2>
                            <p class="text-secondary mb-0">Your profile details and contact information.</p>
                        </div>
                        <span class="section-icon" aria-hidden="true">◎</span>
                    </div>

                    <form>
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="name" class="form-label">Full name</label>
                                <input class="form-control" id="name" name="name" type="text" maxlength="100" value="<?= $escape($profileName) ?>" placeholder="Your full name" autocomplete="name">
                            </div>
                            <div class="col-12">
                                <label for="email" class="form-label">Email address</label>
                                <input class="form-control" id="email" name="email" type="email" maxlength="100" value="<?= $escape((string) ($profile['email'] ?? '')) ?>" placeholder="you@example.com" autocomplete="email">
                                <div class="form-text">Your account email address.</div>
                            </div>
                            <div class="col-12">
                                <label for="phone" class="form-label">Phone number <span class="text-secondary fw-normal">(optional)</span></label>
                                <input class="form-control" id="phone" name="phone" type="tel" maxlength="20" value="<?= $escape((string) ($profile['phone'] ?? '')) ?>" placeholder="+880 1XXX-XXX-XXX" autocomplete="tel">
                            </div>
                            <div class="col-12">
                                <label for="new-password" class="form-label">New password <span class="text-secondary fw-normal">(optional)</span></label>
                                <input class="form-control" id="new-password" name="password" type="password" autocomplete="new-password" placeholder="Enter a new password">
                                <div class="form-text">Password details are never displayed here.</div>
                            </div>
                            <div class="col-12">
                                <div class="form-actions d-flex flex-column flex-sm-row justify-content-end gap-2 pt-2">
                                    <a class="btn btn-light" href="../owner_dashboard.php">Cancel</a>
                                    <button class="btn btn-primary px-4" type="button">Save changes</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </section>
            </div>

            <div class="col-12 col-lg-5">
                <section class="detail-card account-info-card h-100" aria-labelledby="account-info-title">
                    <div class="section-heading">
                        <div>
                            <h2 id="account-info-title" class="h5 mb-1">Account information</h2>
                            <p class="text-secondary mb-0">System-managed details for your account.</p>
                        </div>
                        <span class="section-icon portfolio-icon" aria-hidden="true">⌂</span>
                    </div>

                    <div class="account-info-list">
                        <div class="account-info-row">
                            <span class="account-info-icon" aria-hidden="true">#</span>
                            <div><small>User ID</small><strong><?= $profile ? (int) $profile['user_id'] : '—' ?></strong></div>
                        </div>
                        <div class="account-info-row">
                            <span class="account-info-icon" aria-hidden="true">◎</span>
                            <div><small>Account role</small><strong><?= $escape(ucfirst((string) ($profile['role'] ?? '—'))) ?></strong></div>
                        </div>
                        <div class="account-info-row">
                            <span class="account-info-icon" aria-hidden="true">✓</span>
                            <div><small>Account status</small><strong class="account-status-text"><?= $escape(ucfirst($status ?: '—')) ?></strong></div>
                        </div>
                        <div class="account-info-row">
                            <span class="account-info-icon" aria-hidden="true">◷</span>
                            <div><small>Created at</small><strong><?= $escape($memberSince) ?></strong></div>
                        </div>
                    </div>

                    <div class="account-info-note mt-3">
                        <span aria-hidden="true">ⓘ</span>
                        Your account ID, role, status, and creation date are managed by the system.
                    </div>
                </section>
            </div>
        </div>

        <footer class="page-footer mt-5 pt-3">
            <span>EstateFlow · Owner workspace</span>
            <a href="../owner_dashboard.php">Return to dashboard</a>
        </footer>
    </main>
</body>
</html>
