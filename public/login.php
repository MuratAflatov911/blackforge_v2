<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/includes/layout.php';
require_once __DIR__ . '/../app/includes/session.php';
require_once __DIR__ . '/../app/includes/db.php';

ensure_session_started();

if (current_user()) {
    header('Location: ' . base_url('index.php'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify_or_403();
    $email = trim((string)($_POST['email'] ?? ''));
    $pass = (string)($_POST['password'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        flash_set('err', 'Укажи корректный email.');
        header('Location: ' . base_url('login.php'));
        exit;
    }

    $stmt = db()->prepare('SELECT id, password_hash FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $u = $stmt->fetch();

    if (!$u || !password_verify($pass, (string)$u['password_hash'])) {
        flash_set('err', 'Неверный логин или пароль.');
        header('Location: ' . base_url('login.php'));
        exit;
    }

    $_SESSION['user_id'] = (int)$u['id'];
    flash_set('ok', 'Добро пожаловать в BLACKFORGE.');
    header('Location: ' . base_url('index.php'));
    exit;
}

render_header('Вход — BLACKFORGE');
?>

<section class="hero">
  <div class="hero__card">
    <h1 class="hero__title">Вход</h1>
    <p class="hero__sub">Избранное и заказ доступны после авторизации.</p>
  </div>
</section>

<section class="grid" style="grid-template-columns: 1fr;">
  <div class="panel" style="max-width:520px; margin:0 auto;">
    <div class="panel__body">
      <form method="post" action="<?= e(base_url('login.php')) ?>">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <div class="field">
          <div class="label">Email (логин)</div>
          <input class="input" name="email" autocomplete="email" required>
        </div>
        <div class="field">
          <div class="label">Пароль</div>
          <input class="input" type="password" name="password" autocomplete="current-password" required>
        </div>
        <button class="btn" type="submit">Войти</button>
        <div class="hint" style="margin-top:10px;">
          Нет аккаунта? <a class="gold" href="<?= e(base_url('register.php')) ?>">Регистрация</a>
        </div>
      </form>
    </div>
  </div>
</section>

<?php render_footer(); ?>

