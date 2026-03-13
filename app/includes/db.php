<?php

declare(strict_types=1);

function db(): PDO
{
    static $pdo = null;
    static $bootstrapped = false;

    if ($pdo instanceof PDO) {
        if (!$bootstrapped) {
            db_bootstrap($pdo);
            $bootstrapped = true;
        }
        return $pdo;
    }

    $cfg = require __DIR__ . '/config.php';
    $db = $cfg['db'];

    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        $db['host'],
        (int)$db['port'],
        $db['name'],
        $db['charset']
    );

    $pdo = new PDO($dsn, $db['user'], $db['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    if (!$bootstrapped) {
        db_bootstrap($pdo);
        $bootstrapped = true;
    }

    return $pdo;
}

function db_bootstrap(PDO $pdo): void
{

    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN avatar_url VARCHAR(255) NULL AFTER role");
    } catch (Throwable $e) {
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS site_content (
      id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
      content_key VARCHAR(120) NOT NULL,
      value TEXT NOT NULL,
      updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      UNIQUE KEY uq_site_content_key (content_key)
    ) ENGINE=InnoDB");

    $pdo->exec("CREATE TABLE IF NOT EXISTS product_reviews (
      id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
      product_id INT UNSIGNED NOT NULL,
      user_id INT UNSIGNED NOT NULL,
      rating TINYINT UNSIGNED NOT NULL,
      body TEXT NOT NULL,
      status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
      moderated_at TIMESTAMP NULL DEFAULT NULL,
      created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
      CONSTRAINT fk_reviews_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
      CONSTRAINT fk_reviews_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
      KEY idx_reviews_product_status (product_id, status),
      KEY idx_reviews_status (status)
    ) ENGINE=InnoDB");
}
