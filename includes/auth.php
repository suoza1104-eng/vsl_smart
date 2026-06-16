<?php
declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

function start_admin_session(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_name('vsl_smart_admin');
        session_start();
    }
}

function is_admin_logged(): bool
{
    start_admin_session();
    return !empty($_SESSION['admin_logged']);
}

function require_admin(): void
{
    if (!is_admin_logged()) {
        header('Location: login.php');
        exit;
    }
}

function csrf_token(): string
{
    start_admin_session();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf(): void
{
    start_admin_session();
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(419);
        exit('CSRF token inválido.');
    }
}

