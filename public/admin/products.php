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
    $action = (string)($_POST['action'] ?? '');
    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = db()->prepare('DELETE FROM products WHERE id=?');
            $stmt->execute([$id]);
            flash_set('ok', 'Товар удалён.');
        }
        header('Location: ' . base_url('admin/products.php'));
        exit;
    }
}

$q = trim((string)($_GET['q'] ?? ''));
$params = [];
$where = '';
if ($q !== '') {
    $where = 'WHERE name LIKE ? OR brand LIKE ?';
    $params = ['%' . $q . '%', '%' . $q . '%'];
}

$stmt = db()->prepare("SELECT * FROM products $where ORDER BY created_at DESC");
$stmt->execute($params);
$rows = $stmt->fetchAll();

render_header('Админ — товары');
?>

<section class="hero">
  <div class="hero__card">
    <h1 class="hero__title">Товары</h1>
    <p class="hero__sub">Полный CRUD: создание, редактирование, удаление. Изображения добавляются на странице редактирования.</p>
  </div>
</section>

<section class="grid" style="grid-template-columns: 1fr;">
  <div class="panel">
    <div class="panel__body">
      <div class="tabs">
        <a class="tab tab--active" href="<?= e(base_url('admin/products.php')) ?>">Товары</a>
        <a class="tab" href="<?= e(base_url('admin/orders.php')) ?>">Заказы</a>
        <a class="tab" href="<?= e(base_url('admin/users.php')) ?>">Пользователи</a>
      </div>

      <div style="display:flex; gap:10px; justify-content:space-between; align-items:center; margin-top:14px; flex-wrap:wrap;">
        <form method="get" action="<?= e(base_url('admin/products.php')) ?>" style="display:flex; gap:10px; align-items:center; flex:1; min-width:280px;">
          <input class="input" name="q" value="<?= e($q) ?>" placeholder="поиск по названию/бренду" style="width:100%; max-width:420px;">
          <button class="btn btn--ghost" type="submit">Найти</button>
        </form>
        <a class="btn" href="<?= e(base_url('admin/product_edit.php')) ?>">+ Новый товар</a>
      </div>

      <div style="height:12px"></div>

      <table class="table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Название</th>
            <th>Бренд</th>
            <th>R</th>
            <th>Цена</th>
            <th>Склад</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $p): ?>
            <tr>
              <td><?= (int)$p['id'] ?></td>
              <td><?= e((string)$p['name']) ?></td>
              <td><?= e((string)$p['brand']) ?></td>
              <td>R<?= e((string)$p['diameter_inch']) ?></td>
              <td><span class="gold"><?= number_format((float)$p['price'], 0, '.', ' ') ?></span> ₽</td>
              <td><?= (int)$p['stock_qty'] ?></td>
              <td style="white-space:nowrap;">
                <a class="tab tab--active" href="<?= e(base_url('admin/product_edit.php?id=' . (int)$p['id'])) ?>">Редактировать</a>
                <form method="post" action="<?= e(base_url('admin/products.php')) ?>" style="display:inline;">
                  <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                  <button class="tab" type="submit" onclick="return confirm('Удалить товар?')">Удалить</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$rows): ?>
            <tr><td colspan="7" class="hint">Нет товаров.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>

<?php render_footer(); ?>

