<?php

declare(strict_types=1);

return [
    'db' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'name' => 'blackforge',
        'user' => 'root',
        'pass' => '',
        'charset' => 'utf8mb4',
    ],
    'app' => [
        'base_url' => '/blackforge/public',
        'uploads_dir' => __DIR__ . '/../../public/uploads',
        'uploads_url' => '/blackforge/public/uploads',
        'max_upload_bytes' => 8 * 1024 * 1024,
    ],
];

