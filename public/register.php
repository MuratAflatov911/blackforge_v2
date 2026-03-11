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

function calc_age_years(DateTimeImmutable $birth, DateTimeImmutable $today): int
{
    $diff = $today->diff($birth);
    return (int)$diff->y;
}

// Simple captcha (math)
if (empty($_SESSION['captcha_a']) || empty($_SESSION['captcha_b'])) {
    $_SESSION['captcha_a'] = random_int(2, 9);
    $_SESSION['captcha_b'] = random_int(1, 9);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify_or_403();

    $email = mb_strtolower(trim((string)($_POST['email'] ?? '')));
    $fullName = trim((string)($_POST['full_name'] ?? ''));
    $birthDate = trim((string)($_POST['birth_date'] ?? ''));
    $pass = (string)($_POST['password'] ?? '');
    $pass2 = (string)($_POST['password2'] ?? '');
    $captcha = trim((string)($_POST['captcha'] ?? ''));

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        flash_set('err', 'Укажи корректный email.');
        header('Location: ' . base_url('register.php'));
        exit;
    }
    if ($fullName === '' || mb_strlen($fullName) < 5) {
        flash_set('err', 'Укажи ФИО (минимум 5 символов).');
        header('Location: ' . base_url('register.php'));
        exit;
    }
    if ($birthDate === '') {
        flash_set('err', 'Укажи дату рождения.');
        header('Location: ' . base_url('register.php'));
        exit;
    }

    try {
        $birth = new DateTimeImmutable($birthDate);
    } catch (Throwable $e) {
        flash_set('err', 'Некорректная дата рождения.');
        header('Location: ' . base_url('register.php'));
        exit;
    }

    $age = calc_age_years($birth, new DateTimeImmutable('today'));
    if ($age < 14) {
        flash_set('err', 'Регистрация доступна с 14 лет.');
        header('Location: ' . base_url('register.php'));
        exit;
    }

    if (strlen($pass) < 8) {
        flash_set('err', 'Пароль должен быть минимум 8 символов.');
        header('Location: ' . base_url('register.php'));
        exit;
    }
    if (!hash_equals($pass, $pass2)) {
        flash_set('err', 'Пароли не совпадают.');
        header('Location: ' . base_url('register.php'));
        exit;
    }

    $expected = (int)($_SESSION['captcha_a'] ?? 0) + (int)($_SESSION['captcha_b'] ?? 0);
    if ((string)$expected !== $captcha) {
        flash_set('err', 'Капча не пройдена.');
        $_SESSION['captcha_a'] = random_int(2, 9);
        $_SESSION['captcha_b'] = random_int(1, 9);
        header('Location: ' . base_url('register.php'));
        exit;
    }

    $hash = password_hash($pass, PASSWORD_DEFAULT);

    try {
        $stmt = db()->prepare('INSERT INTO users (email, full_name, birth_date, password_hash, role) VALUES (?,?,?,?,?)');
        $stmt->execute([$email, $fullName, $birth->format('Y-m-d'), $hash, 'user']);
    } catch (PDOException $e) {
        flash_set('err', 'Пользователь с таким email уже существует.');
        header('Location: ' . base_url('register.php'));
        exit;
    }

    // Reset captcha after success
    unset($_SESSION['captcha_a'], $_SESSION['captcha_b']);

    flash_set('ok', 'Аккаунт создан. Теперь войди.');
    header('Location: ' . base_url('login.php'));
    exit;
}

render_header('Регистрация — BLACKFORGE');
?>

<section class="hero">
  <div class="hero__card">
    <h1 class="hero__title">Регистрация</h1>
    <p class="hero__sub">Email — это логин. Возраст — не менее 14 лет. Простая капча защищает форму от ботов.</p>
  </div>
</section>

<section class="grid" style="grid-template-columns: 1fr;">
  <div class="panel" style="max-width:620px; margin:0 auto;">
    <div class="panel__body">
      <form method="post" action="<?= e(base_url('register.php')) ?>">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">

        <div class="field">
          <div class="label">Email</div>
          <input class="input" name="email" autocomplete="email" required>
        </div>
        <div class="field">
          <div class="label">ФИО</div>
          <input class="input" name="full_name" autocomplete="name" required>
        </div>
        <div class="field">
          <div class="label">Дата рождения</div>
          <input class="input" type="date" name="birth_date" required>
        </div>

        <div class="field">
          <div class="label">Пароль</div>
          <input class="input" type="password" name="password" autocomplete="new-password" required>
          <div class="hint">Минимум 8 символов.</div>
        </div>
        <div class="field">
          <div class="label">Подтверждение пароля</div>
          <input class="input" type="password" name="password2" autocomplete="new-password" required>
        </div>

        <div class="field">
          <div class="label">Капча: сколько будет <?= (int)($_SESSION['captcha_a'] ?? 0) ?> + <?= (int)($_SESSION['captcha_b'] ?? 0) ?> ?</div>
          <input class="input" name="captcha" inputmode="numeric" required>
        </div>

        <button class="btn" type="submit">Создать аккаунт</button>
        <div class="hint" style="margin-top:10px;">
          Уже есть аккаунт? <a class="gold" href="<?= e(base_url('login.php')) ?>">Войти</a>
        </div>
      </form>
    </div>
  </div>
</section>

<?php render_footer(); ?>
