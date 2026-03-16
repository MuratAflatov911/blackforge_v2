<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/includes/layout.php';
require_once __DIR__ . '/../../app/includes/auth.php';
require_once __DIR__ . '/../../app/includes/db.php';
require_once __DIR__ . '/../../app/includes/session.php';

require_admin();
ensure_session_started();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify_or_403();
    $id = (int)($_POST['id'] ?? 0);
    $status = (string)($_POST['status'] ?? '');
    if ($id > 0 && in_array($status, ['new', 'paid', 'shipped', 'cancelled'], true)) {
        $stmt = db()->prepare('UPDATE orders SET status=? WHERE id=?');
        $stmt->execute([$status, $id]);
        flash_set('ok', 'Статус обновлён.');
    }
    header('Location: ' . base_url('admin/orders.php'));
    exit;
}

$stmt = db()->query('SELECT * FROM orders ORDER BY created_at DESC LIMIT 200');
$orders = $stmt->fetchAll();

render_header('Админ — заказы');
render_breadcrumbs([['title' => 'Админ', 'url' => base_url('admin/index.php')], ['title' => 'Заказы']]);
?>

<section class="hero">
  <div class="hero__card">
    <h1 class="hero__title">Заказы</h1>
    <p class="hero__sub">Просмотр заказов и смена статуса.</p>
  </div>
</section>

<section class="grid" style="grid-template-columns: 1fr;">
  <div class="panel">
    <div class="panel__body">
      <div class="tabs">
        <a class="tab" href="<?= e(base_url('admin/products.php')) ?>">Товары</a>
        <a class="tab tab--active" href="<?= e(base_url('admin/orders.php')) ?>">Заказы</a>
        <a class="tab" href="<?= e(base_url('admin/users.php')) ?>">Пользователи</a>
        <a class="tab" href="<?= e(base_url('admin/reviews.php')) ?>">Отзывы</a>
        <a class="tab" href="<?= e(base_url('admin/content.php')) ?>">Контент</a>
      </div>

      <div style="height:12px"></div>

      <table class="table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Дата</th>
            <th>Клиент</th>
            <th>Сумма</th>
                        <th>Статус</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($orders as $o): ?>
            <tr>
              <td><?= (int)$o['id'] ?></td>
              <td><?= e((string)$o['created_at']) ?></td>
              <td>
                <div style="font-weight:800;"><?= e((string)$o['customer_name']) ?></div>
                <div class="hint"><?= e((string)$o['customer_email']) ?></div>
              </td>
              <td><span class="gold"><?= number_format((float)$o['total'], 0, '.', ' ') ?></span> ₽</td>
                            <td><span class="badge"><?= e((string)$o['status']) ?></span></td>
              <td style="white-space:nowrap;">
                <a class="tab tab--active" href="<?= e(base_url('admin/order_view.php?id=' . (int)$o['id'])) ?>">Открыть</a>
                <form method="post" action="<?= e(base_url('admin/orders.php')) ?>" style="display:inline;">
                  <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                  <input type="hidden" name="id" value="<?= (int)$o['id'] ?>">
                  <select class="select" name="status" onchange="this.form.submit()" style="padding:8px 10px; border-radius:12px;">
                    <?php foreach (['new','paid','shipped','cancelled'] as $st): ?>
                      <option value="<?= e($st) ?>" <?= $st === (string)$o['status'] ? 'selected' : '' ?>><?= e($st) ?></option>
                    <?php endforeach; ?>
                  </select>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$orders): ?>
            <tr><td colspan="7" class="hint">Нет заказов.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>

<?php render_footer(); ?>

