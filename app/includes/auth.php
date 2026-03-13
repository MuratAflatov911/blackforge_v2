<?php

declare(strict_types=1);

require_once __DIR__ . '/session.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/view.php';

function current_user(): ?array
{
    ensure_session_started();
    if (empty($_SESSION['user_id'])) {
        return null;
    }
    $id = (int)$_SESSION['user_id'];
    $stmt = db()->prepare('SELECT id, email, full_name, birth_date, role, avatar_url, created_at FROM users WHERE id = ?');
    $stmt->execute([$id]);
    $u = $stmt->fetch();
    return $u ?: null;
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function require_login(): void
{
    if (!is_logged_in()) {
        header('Location: ' . base_url('login.php'));
        exit;
    }
}

function require_admin(): void
{
    $u = current_user();
    if (!$u || ($u['role'] ?? '') !== 'admin') {
        http_response_code(403);
        echo 'Forbidden';
        exit;
    }
}

