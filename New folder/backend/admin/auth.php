<?php
declare(strict_types=1);

function admin_start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $isSecure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
        'cookie_secure' => $isSecure,
        'use_strict_mode' => true,
    ]);
}

admin_start_session();
header('Cache-Control: no-store, private');
header('X-Content-Type-Options: nosniff');

function admin_escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function admin_require_auth(): void
{
    if (!isset($_SESSION['admin_user']['user_id'])) {
        header('Location: index.php');
        exit;
    }
}

function admin_csrf_token(): string
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function admin_csrf_is_valid($token): bool
{
    return is_string($token)
        && isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

function admin_database(): PDO
{
    static $connection;

    if ($connection instanceof PDO) {
        return $connection;
    }

    if (!extension_loaded('pdo_mysql')) {
        throw new RuntimeException('Enable the PHP PDO MySQL extension before signing in.');
    }

    $database = getenv('ESTATEFLOW_DB_NAME');
    $username = getenv('ESTATEFLOW_DB_USER');
    if ($database === false || $database === '' || $username === false || $username === '') {
        throw new RuntimeException('Set ESTATEFLOW_DB_NAME and ESTATEFLOW_DB_USER before signing in.');
    }

    $host = getenv('ESTATEFLOW_DB_HOST');
    $password = getenv('ESTATEFLOW_DB_PASSWORD');
    $host = $host === false || $host === '' ? '127.0.0.1' : $host;
    $password = $password === false ? '' : $password;

    try {
        $connection = new PDO(
            'mysql:host=' . $host . ';dbname=' . $database . ';charset=utf8mb4',
            $username,
            $password,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
    } catch (PDOException $exception) {
        error_log('EstateFlow admin database connection failed: ' . $exception->getMessage());
        throw new RuntimeException('The administrator sign-in service is unavailable.');
    }

    return $connection;
}
