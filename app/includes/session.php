<?php

declare(strict_types=1);

function ensure_session_started(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function csrf_token(): string
{
    ensure_session_started();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return (string)$_SESSION['csrf'];
}

function csrf_verify_or_403(): void
{
    ensure_session_started();
    $token = $_POST['csrf'] ?? '';
    if (!is_string($token) || $token === '' || !hash_equals((string)($_SESSION['csrf'] ?? ''), $token)) {
        http_response_code(403);
        echo 'Forbidden';
        exit;
    }
}

function flash_set(string $key, string $value): void
{
    ensure_session_started();
    $_SESSION['flash'][$key] = $value;
}

function flash_get(string $key): ?string
{
    ensure_session_started();
    $v = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return is_string($v) ? $v : null;
}

