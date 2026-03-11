<?php

declare(strict_types=1);

require_once __DIR__ . '/session.php';

function e(?string $s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function app_config(): array
{
    static $cfg = null;
    if (is_array($cfg)) {
        return $cfg;
    }
    $cfg = require __DIR__ . '/config.php';
    return $cfg;
}

function base_url(string $path = ''): string
{
    $cfg = app_config();
    $base = rtrim((string)$cfg['app']['base_url'], '/');
    $path = ltrim($path, '/');
    return $path === '' ? $base . '/' : $base . '/' . $path;
}

