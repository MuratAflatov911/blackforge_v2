<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/includes/layout.php';
require_once __DIR__ . '/../app/includes/auth.php';
require_once __DIR__ . '/../app/includes/session.php';
require_once __DIR__ . '/../app/includes/db.php';
require_once __DIR__ . '/../app/includes/images.php';

require_login();
ensure_session_started();
$u = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify_or_403();
    $fullName = trim((string)($_POST['full_name'] ?? ''));
    $birthDate = trim((string)($_POST['birth_date'] ?? ''));
    $avatarUrl = (string)($u['avatar_url'] ?? '');

    if ($fullName === '' || mb_strlen($fullName) < 5) {
        flash_set('err', 'Укажи ФИО минимум 5 символов.');
        header('Location: ' . base_url('profile.php'));
        exit;
    }

    if (!empty($_FILES['avatar_file']['name'] ?? '')) {
        try {
            $avatarUrl = save_uploaded_image($_FILES['avatar_file']);
        } catch (Throwable $e) {
            flash_set('err', 'Ошибка аватарки: ' . $e->getMessage());
            header('Location: ' . base_url('profile.php'));
            exit;
        }
    }

    db()->prepare('UPDATE users SET full_name=?, birth_date=?, avatar_url=? WHERE id=?')->execute([$fullName, $birthDate, $avatarUrl, (int)$u['id']]);
    flash_set('ok', 'Профиль обновлён.');
    header('Location: ' . base_url('profile.php'));
    exit;
}

$u = current_user();
render_header('Профиль — BLACKFORGE');
render_breadcrumbs([
    ['title' => 'Главная', 'url' => base_url('index.php')],
    ['title' => 'Профиль'],
]);
?>
<section class="hero"><div class="hero__card"><h1 class="hero__title">Личный кабинет</h1><p class="hero__sub">Редактируйте данные и аватар профиля.</p></div></section>
<section class="grid grid--single"><div class="panel"><div class="panel__body">
  <div class="row" style="justify-content:flex-start; gap:18px;">
    <div class="card__img" style="width:140px;height:140px;border-radius:50%;overflow:hidden;border:1px solid var(--line);">
      <?php if (!empty($u['avatar_url'])): ?><img src="<?= e((string)$u['avatar_url']) ?>" alt="avatar"><?php else: ?><div class="hint">Нет<br>аватара</div><?php endif; ?>
    </div>
    <div>
      <div><strong><?= e((string)$u['email']) ?></strong></div>
      <div class="hint">Роль: <?= e((string)$u['role']) ?></div>
      <?php if ((string)$u['role'] === 'admin'): ?><div style="margin-top:8px;"><a class="tab tab--active" href="<?= e(base_url('admin/index.php')) ?>">Перейти в кабинет администратора</a></div><?php endif; ?>
    </div>
  </div>
  <form method="post" enctype="multipart/form-data" action="<?= e(base_url('profile.php')) ?>" style="margin-top:14px;">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <div class="field"><div class="label">ФИО</div><input class="input" name="full_name" value="<?= e((string)$u['full_name']) ?>" required></div>
    <div class="field"><div class="label">Дата рождения</div><input class="input" type="date" name="birth_date" value="<?= e((string)$u['birth_date']) ?>" required></div>
    <div class="field"><div class="label">Аватар (JPG/PNG/WebP)</div><input class="input" type="file" name="avatar_file" accept="image/png,image/jpeg,image/webp"></div>
    <button class="btn" type="submit">Сохранить профиль</button>
  </form>
</div></div></section>
<?php render_footer(); ?>
