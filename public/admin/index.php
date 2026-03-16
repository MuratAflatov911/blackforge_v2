<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/includes/layout.php';
require_once __DIR__ . '/../../app/includes/auth.php';
require_once __DIR__ . '/../../app/includes/view.php';

require_admin();

render_header('Админка — BLACKFORGE');
render_breadcrumbs([
    ['title' => 'Админ', 'url' => base_url('admin/index.php')],
]);

?>

<section class="hero">
  <div class="hero__card">
    <h1 class="hero__title">Админ‑панель</h1>
    <p class="hero__sub">Управление товарами, заказами, пользователями и изображениями (файл/URL).</p>
  </div>
</section>

<section class="grid" style="grid-template-columns: 1fr;">
  <div class="panel">
    <div class="panel__body">
      <div class="tabs">
        <a class="tab tab--active" href="<?= e(base_url('admin/products.php')) ?>">Товары</a>
        <a class="tab" href="<?= e(base_url('admin/orders.php')) ?>">Заказы</a>
        <a class="tab" href="<?= e(base_url('admin/users.php')) ?>">Пользователи</a>
        <a class="tab" href="<?= e(base_url('admin/reviews.php')) ?>">Отзывы</a>
        <a class="tab" href="<?= e(base_url('admin/content.php')) ?>">Контент</a>
      </div>
      <div class="alert" style="margin-top:14px;">
        Если это первый запуск: зарегистрируйся и выдай себе роль admin через SQL (см. `README.md`).
      </div>
    </div>
  </div>
</section>

<?php render_footer(); ?>

