<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/includes/layout.php';
require_once __DIR__ . '/../../app/includes/auth.php';
require_once __DIR__ . '/../../app/includes/session.php';
require_once __DIR__ . '/../../app/includes/db.php';

require_admin();
ensure_session_started();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify_or_403();
    $id = (int)($_POST['id'] ?? 0);
    $status = (string)($_POST['status'] ?? '');
    if ($id > 0 && in_array($status, ['approved', 'rejected'], true)) {
        $stmt = db()->prepare('UPDATE product_reviews SET status=?, moderated_at=NOW() WHERE id=?');
        $stmt->execute([$status, $id]);
        flash_set('ok', 'Статус отзыва обновлён.');
    }
    header('Location: ' . base_url('admin/reviews.php'));
    exit;
}

$stmt = db()->query("SELECT r.*, p.name AS product_name, u.email AS user_email FROM product_reviews r JOIN products p ON p.id=r.product_id JOIN users u ON u.id=r.user_id ORDER BY FIELD(r.status,'pending','approved','rejected'), r.created_at DESC");
$rows = $stmt->fetchAll();

render_header('Админ — отзывы');
render_breadcrumbs([
    ['title' => 'Админ', 'url' => base_url('admin/index.php')],
    ['title' => 'Отзывы'],
]);
?>
<section class="hero"><div class="hero__card"><h1 class="hero__title">Модерация отзывов</h1><p class="hero__sub">Новые отзывы публикуются только после одобрения.</p></div></section>
<section class="grid grid--single"><div class="panel"><div class="panel__body">
<div class="tabs">
  <a class="tab" href="<?= e(base_url('admin/products.php')) ?>">Товары</a>
  <a class="tab" href="<?= e(base_url('admin/orders.php')) ?>">Заказы</a>
  <a class="tab" href="<?= e(base_url('admin/users.php')) ?>">Пользователи</a>
  <a class="tab tab--active" href="<?= e(base_url('admin/reviews.php')) ?>">Отзывы</a>
  <a class="tab" href="<?= e(base_url('admin/content.php')) ?>">Контент</a>
</div>
<table class="table" style="margin-top:12px;"><thead><tr><th>Дата</th><th>Товар</th><th>Пользователь</th><th>Оценка</th><th>Текст</th><th>Статус</th><th></th></tr></thead><tbody>
<?php foreach ($rows as $r): ?>
<tr>
<td><?= e((string)$r['created_at']) ?></td>
<td><a class="gold" href="<?= e(base_url('product.php?id=' . (int)$r['product_id'])) ?>"><?= e((string)$r['product_name']) ?></a></td>
<td><?= e((string)$r['user_email']) ?></td>
<td><?= (int)$r['rating'] ?>/5</td>
<td style="white-space:normal;max-width:420px;"><?= nl2br(e((string)$r['body'])) ?></td>
<td><?= e((string)$r['status']) ?></td>
<td>
<form method="post" action="<?= e(base_url('admin/reviews.php')) ?>" style="display:flex;gap:8px;">
<input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
<input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
<button class="tab tab--active" name="status" value="approved" type="submit">Одобрить</button>
<button class="tab" name="status" value="rejected" type="submit">Отклонить</button>
</form>
</td>
</tr>
<?php endforeach; ?>
</tbody></table>
</div></div></section>
<?php render_footer(); ?>
