<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/includes/layout.php';
require_once __DIR__ . '/../../app/includes/auth.php';
require_once __DIR__ . '/../../app/includes/session.php';
require_once __DIR__ . '/../../app/includes/content.php';
require_once __DIR__ . '/../../app/includes/images.php';

require_admin();
ensure_session_started();

$keys = [
    'about_body' => 'О нас: текст',
    'contacts_phone' => 'Контакты: телефон',
    'contacts_email' => 'Контакты: email',
    'contacts_address' => 'Контакты: адрес',
    'contacts_body' => 'Контакты: доп. текст',
    'giveaways_body' => 'Розыгрыши: текст',
    'giveaways_image_1' => 'Розыгрыши: изображение 1 URL',
    'giveaways_image_2' => 'Розыгрыши: изображение 2 URL',
    'giveaways_image_3' => 'Розыгрыши: изображение 3 URL',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify_or_403();
    foreach ($keys as $k => $_) {
        $val = trim((string)($_POST[$k] ?? ''));
        if (str_starts_with($k, 'giveaways_image_')) {
            $idx = (int)substr($k, -1);
            if (!empty($_FILES['giveaway_file_' . $idx]['name'] ?? '')) {
                try {
                    $val = save_uploaded_image($_FILES['giveaway_file_' . $idx]);
                } catch (Throwable $e) {
                    flash_set('err', 'Ошибка загрузки изображения #' . $idx . ': ' . $e->getMessage());
                    header('Location: ' . base_url('admin/content.php'));
                    exit;
                }
            }
        }
        content_set($k, $val);
    }
    flash_set('ok', 'Контент обновлён.');
    header('Location: ' . base_url('admin/content.php'));
    exit;
}

render_header('Админ — контент');
render_breadcrumbs([
    ['title' => 'Админ', 'url' => base_url('admin/index.php')],
    ['title' => 'Контент сайта'],
]);
?>
<section class="hero"><div class="hero__card"><h1 class="hero__title">Контент сайта</h1><p class="hero__sub">Редактирование страниц: О нас, Контакты, Розыгрыши.</p></div></section>
<section class="grid grid--single"><div class="panel"><div class="panel__body">
<div class="tabs">
  <a class="tab" href="<?= e(base_url('admin/products.php')) ?>">Товары</a>
  <a class="tab" href="<?= e(base_url('admin/orders.php')) ?>">Заказы</a>
  <a class="tab" href="<?= e(base_url('admin/users.php')) ?>">Пользователи</a>
  <a class="tab" href="<?= e(base_url('admin/reviews.php')) ?>">Отзывы</a>
  <a class="tab tab--active" href="<?= e(base_url('admin/content.php')) ?>">Контент</a>
</div>
<form method="post" enctype="multipart/form-data" action="<?= e(base_url('admin/content.php')) ?>" style="margin-top:14px;">
<input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
<?php foreach ($keys as $k => $label): ?>
  <div class="field">
    <div class="label"><?= e($label) ?></div>
    <?php if (str_contains($k, 'giveaways_image_')): ?>
      <input class="input" name="<?= e($k) ?>" value="<?= e(content_get($k)) ?>" placeholder="https://...">
      <input class="input" type="file" name="giveaway_file_<?= (int)substr($k, -1) ?>" accept="image/png,image/jpeg,image/webp" style="margin-top:8px;">
    <?php elseif (str_contains($k, '_body')): ?>
      <textarea class="input" name="<?= e($k) ?>" rows="5"><?= e(content_get($k)) ?></textarea>
    <?php else: ?>
      <input class="input" name="<?= e($k) ?>" value="<?= e(content_get($k)) ?>">
    <?php endif; ?>
  </div>
<?php endforeach; ?>
<button class="btn" type="submit">Сохранить</button>
</form>
</div></div></section>
<?php render_footer(); ?>
