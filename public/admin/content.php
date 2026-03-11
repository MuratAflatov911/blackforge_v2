<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/includes/layout.php';
require_once __DIR__ . '/../../app/includes/auth.php';
require_once __DIR__ . '/../../app/includes/session.php';
require_once __DIR__ . '/../../app/includes/content.php';

require_admin();
ensure_session_started();

$keys = [
    'about_body' => 'О нас: текст',
    'contacts_phone' => 'Контакты: телефон',
    'contacts_email' => 'Контакты: email',
    'contacts_address' => 'Контакты: адрес',
    'contacts_body' => 'Контакты: доп. текст',
    'giveaways_body' => 'Розыгрыши: текст',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify_or_403();
    foreach ($keys as $k => $_) {
        content_set($k, trim((string)($_POST[$k] ?? '')));
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
<form method="post" action="<?= e(base_url('admin/content.php')) ?>" style="margin-top:14px;">
<input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
<?php foreach ($keys as $k => $label): ?>
  <div class="field">
    <div class="label"><?= e($label) ?></div>
    <?php if (str_contains($k, '_body')): ?>
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
