<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

function content_get(string $key, string $default = ''): string
{
    $stmt = db()->prepare('SELECT value FROM site_content WHERE content_key=? LIMIT 1');
    $stmt->execute([$key]);
    $row = $stmt->fetch();
    return $row ? (string)$row['value'] : $default;
}

function content_set(string $key, string $value): void
{
    $stmt = db()->prepare('INSERT INTO site_content (content_key, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value=VALUES(value)');
    $stmt->execute([$key, $value]);
}
