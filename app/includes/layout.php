<?php

declare(strict_types=1);

require_once __DIR__ . '/view.php';
require_once __DIR__ . '/auth.php';

function render_header(string $title = 'BLACKFORGE'): void
{
    $u = current_user();
    $ok = flash_get('ok');
    $err = flash_get('err');

    ?>
    <!doctype html>
    <html lang="ru">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?= e($title) ?></title>
        <link rel="stylesheet" href="<?= e(base_url('assets/css/styles.css')) ?>">
    </head>
    <body>
    <div class="topbar">
        <div class="container">
            <div class="topbar__inner">
                <a class="logo" href="<?= e(base_url('index.php')) ?>">
                    <span class="logo__mark"></span>
                    <span class="gold">BLACKFORGE</span>
                </a>

                <nav class="nav">
                    <a href="<?= e(base_url('index.php')) ?>">Каталог</a>
                    <a href="<?= e(base_url('cart.php')) ?>">Корзина</a>
                    <a href="<?= e(base_url('favorites.php')) ?>">Избранное</a>
                    <?php if ($u): ?>
                        <?php if (($u['role'] ?? '') === 'admin'): ?>
                            <a href="<?= e(base_url('admin/index.php')) ?>">Админ</a>
                        <?php endif; ?>
                        <a href="<?= e(base_url('logout.php')) ?>">Выйти</a>
                    <?php else: ?>
                        <a href="<?= e(base_url('login.php')) ?>">Войти</a>
                        <a class="btn btn--ghost" href="<?= e(base_url('register.php')) ?>">Регистрация</a>
                    <?php endif; ?>
                </nav>
            </div>
        </div>
    </div>

    <main class="container">
        <?php if ($ok): ?>
            <div class="alert alert--ok"><?= e($ok) ?></div>
        <?php endif; ?>
        <?php if ($err): ?>
            <div class="alert alert--bad"><?= e($err) ?></div>
        <?php endif; ?>
    <?php
}

function render_footer(): void
{
    ?>
    </main>
    <footer class="footer">
        <div class="container">
            <div>© <?= date('Y') ?> <span class="gold">BLACKFORGE</span>. Учебный проект.</div>
        </div>
    </footer>
    </body>
    </html>
    <?php
}

