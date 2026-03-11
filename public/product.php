<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/includes/layout.php';
require_once __DIR__ . '/../app/includes/session.php';
require_once __DIR__ . '/../app/includes/db.php';
require_once __DIR__ . '/../app/includes/auth.php';

ensure_session_started();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    http_response_code(404);
    echo 'Not found';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify_or_403();
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'add_to_cart') {
        $qty = max(1, (int)($_POST['qty'] ?? 1));
        $qtyType = (string)($_POST['qty_type'] ?? 'disc');
        $units = $qtyType === 'set' ? $qty * 4 : $qty;
        $_SESSION['cart'] ??= [];
        $_SESSION['cart'][$id] = (int)(($_SESSION['cart'][$id] ?? 0) + $units);
        flash_set('ok', 'Товар добавлен в корзину.');
        header('Location: ' . base_url('product.php?id=' . $id));
        exit;
    }

    if ($action === 'toggle_fav') {
        require_login();
        $u = current_user();
        $uid = (int)$u['id'];

        $stmt = db()->prepare('SELECT 1 FROM favorites WHERE user_id=? AND product_id=?');
        $stmt->execute([$uid, $id]);
        if ($stmt->fetch()) {
            db()->prepare('DELETE FROM favorites WHERE user_id=? AND product_id=?')->execute([$uid, $id]);
            flash_set('ok', 'Убрано из избранного.');
        } else {
            db()->prepare('INSERT INTO favorites (user_id, product_id) VALUES (?,?)')->execute([$uid, $id]);
            flash_set('ok', 'Добавлено в избранное.');
        }
        header('Location: ' . base_url('product.php?id=' . $id));
        exit;
    }

    if ($action === 'add_review') {
        require_login();
        $u = current_user();
        $rating = max(1, min(5, (int)($_POST['rating'] ?? 5)));
        $body = trim((string)($_POST['body'] ?? ''));
        if ($body === '' || mb_strlen($body) < 8) {
            flash_set('err', 'Отзыв слишком короткий.');
            header('Location: ' . base_url('product.php?id=' . $id));
            exit;
        }
        $stmt = db()->prepare('INSERT INTO product_reviews (product_id, user_id, rating, body, status) VALUES (?,?,?,?,?)');
        $stmt->execute([$id, (int)$u['id'], $rating, $body, 'pending']);
        flash_set('ok', 'Отзыв отправлен на модерацию.');
        header('Location: ' . base_url('product.php?id=' . $id));
        exit;
    }
}

$stmt = db()->prepare('SELECT * FROM products WHERE id=?');
$stmt->execute([$id]);
$p = $stmt->fetch();
if (!$p) {
    http_response_code(404);
    echo 'Not found';
    exit;
}

$imgsStmt = db()->prepare('SELECT * FROM product_images WHERE product_id=? ORDER BY is_primary DESC, id ASC');
$imgsStmt->execute([$id]);
$images = $imgsStmt->fetchAll();
$imgId = (int)($_GET['img'] ?? 0);
$main = $images ? (string)$images[0]['url'] : '';
if ($imgId > 0) {
    foreach ($images as $im) {
        if ((int)$im['id'] === $imgId) {
            $main = (string)$im['url'];
            break;
        }
    }
}

$u = current_user();
$isFav = false;
if ($u) {
    $chk = db()->prepare('SELECT 1 FROM favorites WHERE user_id=? AND product_id=?');
    $chk->execute([(int)$u['id'], $id]);
    $isFav = (bool)$chk->fetch();
}

$revStatsStmt = db()->prepare("SELECT COUNT(*) AS cnt, AVG(rating) AS avg_rating FROM product_reviews WHERE product_id=? AND status='approved'");
$revStatsStmt->execute([$id]);
$revStats = $revStatsStmt->fetch() ?: ['cnt' => 0, 'avg_rating' => 0];
$reviewsStmt = db()->prepare("SELECT r.*, u.full_name FROM product_reviews r JOIN users u ON u.id=r.user_id WHERE r.product_id=? AND r.status='approved' ORDER BY r.created_at DESC");
$reviewsStmt->execute([$id]);
$reviews = $reviewsStmt->fetchAll();

render_header((string)$p['name'] . ' — BLACKFORGE');
render_breadcrumbs([
    ['title' => 'Главная', 'url' => base_url('index.php')],
    ['title' => (string)$p['name']],
]);
?>

<section class="hero">
  <div class="hero__card">
    <h1 class="hero__title"><?= e((string)$p['name']) ?></h1>
    <p class="hero__sub"><?= e((string)$p['brand']) ?> · R<?= e((string)$p['diameter_inch']) ?> · <?= e((string)$p['bolt_pattern']) ?> · <?= e((string)$p['width_inch']) ?>J · ET<?= e((string)$p['et_offset']) ?></p>
  </div>
</section>

<section class="grid" style="grid-template-columns: 1.15fr .85fr;">
  <div class="panel">
    <div class="panel__body">
      <div class="card__img" style="border:1px solid var(--line); border-radius:12px; overflow:hidden;">
        <?php if ($main !== ''): ?><img src="<?= e($main) ?>" alt="<?= e((string)$p['name']) ?>"><?php else: ?><div class="hint">Нет изображения</div><?php endif; ?>
      </div>
      <?php if ($images): ?>
        <div class="row" style="margin-top:12px;justify-content:flex-start;">
          <?php foreach ($images as $img): ?>
            <a class="tab" href="<?= e(base_url('product.php?id=' . $id . '&img=' . (int)$img['id'])) ?>" style="padding:0; overflow:hidden; width:96px; height:68px;"><img src="<?= e((string)$img['url']) ?>" alt="" style="width:100%; height:100%; object-fit:contain;"></a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
      <div style="margin-top:14px;" class="hint"><?= nl2br(e((string)($p['description'] ?? ''))) ?></div>

      <div class="panel" style="margin-top:14px;">
        <div class="panel__head"><strong>Отзывы</strong></div>
        <div class="panel__body">
          <div class="row"><div class="stars"><?= str_repeat('★', (int)round((float)$revStats['avg_rating'])) . str_repeat('☆', 5 - (int)round((float)$revStats['avg_rating'])) ?></div><div class="hint"><?= number_format((float)$revStats['avg_rating'], 1) ?> / 5 · <?= (int)$revStats['cnt'] ?> отзывов</div></div>
          <?php if (!$reviews): ?><div class="alert">Пока нет опубликованных отзывов.</div><?php endif; ?>
          <?php foreach ($reviews as $r): ?>
            <div class="alert">
              <div class="row"><strong><?= e((string)$r['full_name']) ?></strong><span class="stars"><?= str_repeat('★', (int)$r['rating']) . str_repeat('☆', 5 - (int)$r['rating']) ?></span></div>
              <div class="hint" style="margin-top:6px;"><?= nl2br(e((string)$r['body'])) ?></div>
            </div>
          <?php endforeach; ?>

          <?php if ($u): ?>
            <form method="post" action="<?= e(base_url('product.php?id=' . $id)) ?>">
              <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
              <input type="hidden" name="action" value="add_review">
              <div class="field"><div class="label">Оценка</div><select class="select" name="rating"><?php for($i=5;$i>=1;$i--): ?><option value="<?= $i ?>"><?= $i ?> ★</option><?php endfor; ?></select></div>
              <div class="field"><div class="label">Ваш отзыв</div><textarea class="input" name="body" rows="4" required></textarea></div>
              <button class="btn btn--ghost" type="submit">Отправить на модерацию</button>
            </form>
          <?php else: ?>
            <div class="hint">Чтобы оставить отзыв, <a class="gold" href="<?= e(base_url('login.php')) ?>">войдите</a>.</div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <aside class="panel">
    <div class="panel__head"><div class="row"><div class="price"><span class="gold"><?= number_format((float)$p['price'], 0, '.', ' ') ?></span> ₽ / диск</div><span class="badge"><?= (int)$p['stock_qty'] > 0 ? 'В наличии' : 'Нет в наличии' ?></span></div><div class="hint" style="margin-top:6px;">Комплект (4 шт): <strong><?= number_format((float)$p['price'] * 4, 0, '.', ' ') ?> ₽</strong></div></div>
    <div class="panel__body">
      <form method="post" action="<?= e(base_url('product.php?id=' . $id)) ?>">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="add_to_cart">
        <div class="field"><div class="label">Количество</div><input class="input" name="qty" type="number" min="1" value="1"></div>
        <div class="field"><div class="label">Считать как</div><select class="select" name="qty_type"><option value="disc">диски (шт)</option><option value="set">комплекты (1 комплект = 4 диска)</option></select></div>
        <button class="btn" type="submit" <?= (int)$p['stock_qty'] <= 0 ? 'disabled' : '' ?>>Добавить в корзину</button>
        <?php if (!$u): ?><div class="hint" style="margin-top:8px;">Оформление заказа доступно только после входа.</div><?php endif; ?>
      </form>

      <div style="height:10px"></div>
      <form method="post" action="<?= e(base_url('product.php?id=' . $id)) ?>">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="toggle_fav">
        <button class="btn btn--ghost" type="submit"><?= $isFav ? 'Убрать из избранного' : 'Добавить в избранное' ?></button>
      </form>
    </div>
  </aside>
</section>

<?php render_footer(); ?>
