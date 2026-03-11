<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/includes/layout.php';
require_once __DIR__ . '/../app/includes/auth.php';
require_once __DIR__ . '/../app/includes/session.php';
require_once __DIR__ . '/../app/includes/db.php';

require_login();
ensure_session_started();

$u = current_user();
$uid = (int)$u['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify_or_403();
    $pid = (int)($_POST['product_id'] ?? 0);
    if ($pid > 0) {
        $del = db()->prepare('DELETE FROM favorites WHERE user_id=? AND product_id=?');
        $del->execute([$uid, $pid]);
        flash_set('ok', 'Убрано из избранного.');
    }
    header('Location: ' . base_url('favorites.php'));
    exit;
}

$stmt = db()->prepare("
SELECT
  p.*,
  (SELECT url FROM product_images i WHERE i.product_id=p.id ORDER BY i.is_primary DESC, i.id ASC LIMIT 1) AS image_url
FROM favorites f
JOIN products p ON p.id=f.product_id
WHERE f.user_id=?
ORDER BY f.created_at DESC
");
$stmt->execute([$uid]);
$items = $stmt->fetchAll();

render_header('Избранное — BLACKFORGE');
?>

<section class="hero">
  <div class="hero__card">
    <h1 class="hero__title">Избранное</h1>
    <p class="hero__sub">Список сохранённых дисков привязан к аккаунту.</p>
  </div>
</section>

<section class="grid" style="grid-template-columns: 1fr;">
  <div class="panel">
    <div class="panel__body">
      <?php if (!$items): ?>
        <div class="alert">Пока пусто. Открой товар и нажми «Добавить в избранное».</div>
      <?php else: ?>
        <div class="products">
          <?php foreach ($items as $p): ?>
            <div class="card">
              <a class="card__img" href="<?= e(base_url('product.php?id=' . (int)$p['id'])) ?>">
                <?php if (!empty($p['image_url'])): ?>
                  <img src="<?= e((string)$p['image_url']) ?>" alt="<?= e((string)$p['name']) ?>">
                <?php else: ?>
                  <div class="hint">Нет изображения</div>
                <?php endif; ?>
              </a>
              <div class="card__body">
                <div class="row">
                  <div class="card__title"><?= e((string)$p['name']) ?></div>
                  <span class="badge">R<?= e((string)$p['diameter_inch']) ?></span>
                </div>
                <div class="card__meta">
                  <?= e((string)$p['brand']) ?> · <?= e((string)$p['bolt_pattern']) ?> · <?= e((string)$p['width_inch']) ?>J · ET<?= e((string)$p['et_offset']) ?>
                </div>
                <div class="row" style="margin-top:auto;">
                  <div class="price"><span class="gold"><?= number_format((float)$p['price'], 0, '.', ' ') ?></span> ₽</div>
                  <form method="post" action="<?= e(base_url('favorites.php')) ?>">
                    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
                    <button class="btn btn--danger" type="submit">Удалить</button>
                  </form>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>

<?php render_footer(); ?>

