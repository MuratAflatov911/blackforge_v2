<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/includes/layout.php';
require_once __DIR__ . '/../../app/includes/auth.php';
require_once __DIR__ . '/../../app/includes/db.php';
require_once __DIR__ . '/../../app/includes/session.php';

require_admin();
ensure_session_started();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    http_response_code(404);
    echo 'Not found';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify_or_403();
    $status = (string)($_POST['status'] ?? '');
    if (in_array($status, ['new', 'paid', 'shipped', 'cancelled'], true)) {
        db()->prepare('UPDATE orders SET status=? WHERE id=?')->execute([$status, $id]);
        flash_set('ok', 'Статус обновлён.');
    }
    header('Location: ' . base_url('admin/order_view.php?id=' . $id));
    exit;
}

$stmt = db()->prepare('SELECT * FROM orders WHERE id=?');
$stmt->execute([$id]);
$order = $stmt->fetch();
if (!$order) {
    http_response_code(404);
    echo 'Not found';
    exit;
}

$itemsStmt = db()->prepare('SELECT * FROM order_items WHERE order_id=? ORDER BY id ASC');
$itemsStmt->execute([$id]);
$items = $itemsStmt->fetchAll();

render_header('Админ — заказ #' . $id);
render_breadcrumbs([['title' => 'Админ', 'url' => base_url('admin/index.php')], ['title' => 'Заказы', 'url' => base_url('admin/orders.php')], ['title' => 'Заказ #' . $id]]);
?>

<section class="hero">
  <div class="hero__card">
    <h1 class="hero__title">Заказ #<?= (int)$order['id'] ?></h1>
    <p class="hero__sub"><?= e((string)$order['created_at']) ?> · <span class="badge"><?= e((string)$order['status']) ?></span></p>
  </div>
</section>

<section class="grid" style="grid-template-columns: 1fr .8fr;">
  <div class="panel">
    <div class="panel__body">
      <table class="table">
        <thead>
          <tr>
            <th>Товар</th>
            <th style="width:120px">Цена</th>
            <th style="width:90px">Кол-во</th>
            <th style="width:140px">Сумма</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($items as $it): ?>
            <tr>
              <td>
                <div style="font-weight:800;"><?= e((string)$it['product_name']) ?></div>
                <div class="hint">product_id: <?= (int)$it['product_id'] ?></div>
              </td>
              <td><?= number_format((float)$it['unit_price'], 0, '.', ' ') ?> ₽</td>
              <td><?= (int)$it['qty'] ?></td>
              <td><span class="gold"><?= number_format((float)$it['line_total'], 0, '.', ' ') ?></span> ₽</td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$items): ?>
            <tr><td colspan="4" class="hint">Нет позиций.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <aside class="panel">
    <div class="panel__head">
      <div class="row">
        <div style="font-weight:800; letter-spacing:.02em;">Детали</div>
        <a class="tab" href="<?= e(base_url('admin/orders.php')) ?>">← К заказам</a>
      </div>
    </div>
    <div class="panel__body">
      <div class="alert">
        <div style="font-weight:800;"><?= e((string)$order['customer_name']) ?></div>
        <div class="hint"><?= e((string)$order['customer_email']) ?></div>
      </div>

      <div class="row"><div class="hint">Подытог</div><div><?= number_format((float)$order['subtotal'], 0, '.', ' ') ?> ₽</div></div>
      <div class="row" style="margin-top:8px;"><div class="hint">Скидка</div><div>- <?= number_format((float)$order['discount'], 0, '.', ' ') ?> ₽</div></div>
      <div class="row" style="margin-top:8px;"><div class="hint">К оплате</div><div class="price"><span class="gold"><?= number_format((float)$order['total'], 0, '.', ' ') ?></span> ₽</div></div>

      <div style="height:14px"></div>

      <form method="post" action="<?= e(base_url('admin/order_view.php?id=' . $id)) ?>">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <div class="field">
          <div class="label">Статус</div>
          <select class="select" name="status">
            <?php foreach (['new','paid','shipped','cancelled'] as $st): ?>
              <option value="<?= e($st) ?>" <?= $st === (string)$order['status'] ? 'selected' : '' ?>><?= e($st) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <button class="btn btn--ghost" type="submit">Сохранить статус</button>
      </form>
    </div>
  </aside>
</section>

<?php render_footer(); ?>

