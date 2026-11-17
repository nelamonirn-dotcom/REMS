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

require_once __DIR__ . '/dbconfig.php';

if (empty($_SESSION['admin_create_csrf'])) {
    $_SESSION['admin_create_csrf'] = bin2hex(random_bytes(32));
}

$escape = static fn(string $value): string => htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$error = '';
$name = '';
$email = '';
$phone = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf'] ?? '';
    if (!is_string($csrf) || !hash_equals($_SESSION['admin_create_csrf'], $csrf)) {
        http_response_code(400);
        exit('Invalid request.');
    }

    $nameInput = $_POST['name'] ?? '';
    $emailInput = $_POST['email'] ?? '';
    $phoneInput = $_POST['phone'] ?? '';
    $passwordInput = $_POST['password'] ?? '';
    $confirmInput = $_POST['confirm_password'] ?? '';

    $name = is_string($nameInput) ? trim($nameInput) : '';
    $email = is_string($emailInput) ? strtolower(trim($emailInput)) : '';
    $phone = is_string($phoneInput) ? trim($phoneInput) : '';
    $password = is_string($passwordInput) ? $passwordInput : '';
    $confirmPassword = is_string($confirmInput) ? $confirmInput : '';

    if ($name === '' || strlen($name) > 100) {
        $error = 'Enter a name up to 100 characters.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 100) {
        $error = 'Enter a valid email address up to 100 characters.';
    } elseif (strlen($phone) > 20) {
        $error = 'Phone number must be 20 characters or fewer.';
    } elseif (strlen($password) < 12 || strlen($password) > 128) {
        $error = 'Password must be between 12 and 128 characters.';
    } elseif (!hash_equals($password, $confirmPassword)) {
        $error = 'The passwords do not match.';
    } else {
        $check = $conn->prepare('SELECT user_id FROM users WHERE email = ?');
        $check->bind_param('s', $email);
        $check->execute();

        if ($check->get_result()->num_rows > 0) {
            $error = 'An account with this email already exists.';
        } else {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $phoneValue = $phone !== '' ? $phone : null;
            $role = 'admin';
            $status = 'active';

            try {
                $insert = $conn->prepare(
                    'INSERT INTO users (name, email, password_hash, phone, role, status)
                     VALUES (?, ?, ?, ?, ?, ?)'
                );
                $insert->bind_param('ssssss', $name, $email, $passwordHash, $phoneValue, $role, $status);
                $insert->execute();
                header('Location: add_admin.php?created=1');
                exit;
            } catch (mysqli_sql_exception $exception) {
                if ((int)$exception->getCode() !== 1062) {
                    throw $exception;
                }
                $error = 'An account with this email already exists.';
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
    <meta name="theme-color" content="#fff2f2">
    <title>Add administrator | EstateFlow</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet" referrerpolicy="no-referrer">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../shared/assets/css/global.css">
    <link rel="stylesheet" href="assets/css/admin/dashboard.css">
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
                <section class="page-heading mb-4" aria-labelledby="add-admin-heading">
                    <div>
                        <p class="page-eyebrow"><span class="live-dot"></span> Administration</p>
                        <h1 id="add-admin-heading">Add administrator</h1>
                        <p class="page-subtitle">Create an active administrator account for the workspace.</p>
                    </div>
                </section>

                <?php if (isset($_GET['created']) && $_GET['created'] === '1'): ?>
                    <div class="alert alert-success" role="status">Administrator account created successfully.</div>
                <?php endif; ?>
                <?php if ($error !== ''): ?>
                    <div class="alert alert-danger" role="alert"><?= $escape($error) ?></div>
                <?php endif; ?>

                <section class="card border-0 shadow-sm" style="max-width: 760px;" aria-label="New administrator details">
                    <div class="card-body p-4 p-md-5">
                        <form method="post" action="">
                            <input type="hidden" name="csrf" value="<?= $escape($_SESSION['admin_create_csrf']) ?>">
                            <div class="mb-3">
                                <label class="form-label" for="name">Full name</label>
                                <input class="form-control" id="name" name="name" type="text" maxlength="100" autocomplete="name" required value="<?= $escape($name) ?>">
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="email">Email address</label>
                                    <input class="form-control" id="email" name="email" type="email" maxlength="100" autocomplete="email" required value="<?= $escape($email) ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="phone">Phone <span class="text-secondary">(optional)</span></label>
                                    <input class="form-control" id="phone" name="phone" type="tel" maxlength="20" autocomplete="tel" value="<?= $escape($phone) ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="password">Password</label>
                                    <input class="form-control" id="password" name="password" type="password" minlength="12" maxlength="128" autocomplete="new-password" required>
                                    <div class="form-text">Use at least 12 characters.</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="confirm-password">Confirm password</label>
                                    <input class="form-control" id="confirm-password" name="confirm_password" type="password" minlength="12" maxlength="128" autocomplete="new-password" required>
                                </div>
                            </div>
                            <div class="d-flex justify-content-end mt-4">
                                <button class="btn btn-primary" type="submit">
                                    <i class="fa-solid fa-user-plus me-2" aria-hidden="true"></i>Create administrator
                                </button>
                            </div>
                        </form>
                    </div>
                </section>
            </main>
        </div>
    </div>
    <script>
        (() => {
            const layout = document.querySelector('#admin-layout');
            const sidebar = document.querySelector('#admin-sidebar');
            const collapseButton = document.querySelector('#sidebar-collapse');
            const menuButton = document.querySelector('#mobile-menu-toggle');
            const backdrop = document.querySelector('#sidebar-backdrop');

            const setMenuOpen = (isOpen) => {
                sidebar.classList.toggle('is-open', isOpen);
                backdrop.hidden = !isOpen;
                menuButton.setAttribute('aria-expanded', String(isOpen));
                menuButton.setAttribute('aria-label', isOpen ? 'Close navigation' : 'Open navigation');
            };

            collapseButton.addEventListener('click', () => {
                const isCollapsed = layout.classList.toggle('is-collapsed');
                collapseButton.setAttribute('aria-expanded', String(!isCollapsed));
                collapseButton.setAttribute('aria-label', isCollapsed ? 'Expand sidebar' : 'Collapse sidebar');
            });
            menuButton.addEventListener('click', () => setMenuOpen(menuButton.getAttribute('aria-expanded') !== 'true'));
            backdrop.addEventListener('click', () => setMenuOpen(false));
            window.addEventListener('resize', () => {
                if (window.innerWidth > 700) setMenuOpen(false);
            });
        })();
    </script>
</body>
</html>
