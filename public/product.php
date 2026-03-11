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

// Cart actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify_or_403();
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'add_to_cart') {
        $qty = max(1, (int)($_POST['qty'] ?? 1));
        $_SESSION['cart'] ??= [];
        $_SESSION['cart'][$id] = (int)(($_SESSION['cart'][$id] ?? 0) + $qty);
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
            $del = db()->prepare('DELETE FROM favorites WHERE user_id=? AND product_id=?');
            $del->execute([$uid, $id]);
            flash_set('ok', 'Убрано из избранного.');
        } else {
            $ins = db()->prepare('INSERT INTO favorites (user_id, product_id) VALUES (?,?)');
            $ins->execute([$uid, $id]);
            flash_set('ok', 'Добавлено в избранное.');
        }
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

render_header((string)$p['name'] . ' — BLACKFORGE');
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
      <div class="card__img" style="border:1px solid var(--line); border-radius:18px; overflow:hidden;">
        <?php if ($main !== ''): ?>
          <img src="<?= e($main) ?>" alt="<?= e((string)$p['name']) ?>">
        <?php else: ?>
          <div class="hint">Нет изображения</div>
        <?php endif; ?>
      </div>

      <?php if ($images): ?>
        <div style="display:flex; gap:10px; flex-wrap:wrap; margin-top:12px;">
          <?php foreach ($images as $img): ?>
            <a class="tab" href="<?= e(base_url('product.php?id=' . $id . '&img=' . (int)$img['id'])) ?>" style="padding:0; overflow:hidden; width:86px; height:60px;">
              <img src="<?= e((string)$img['url']) ?>" alt="" style="width:100%; height:100%; object-fit:contain; background:#0b0b0b;">
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <div style="margin-top:14px; color:var(--muted); line-height:1.55;">
        <?= nl2br(e((string)($p['description'] ?? ''))) ?>
      </div>
    </div>
  </div>

  <aside class="panel">
    <div class="panel__head">
      <div class="row">
        <div class="price"><span class="gold"><?= number_format((float)$p['price'], 0, '.', ' ') ?></span> ₽</div>
        <span class="badge"><?= (int)$p['stock_qty'] > 0 ? 'В наличии' : 'Нет в наличии' ?></span>
      </div>
    </div>
    <div class="panel__body">
      <div class="field">
        <div class="label">Характеристики</div>
        <div class="hint">
          Диаметр: R<?= e((string)$p['diameter_inch']) ?><br>
          Разболтовка: <?= e((string)$p['bolt_pattern']) ?><br>
          Ширина: <?= e((string)$p['width_inch']) ?>J<br>
          Вылет: ET<?= e((string)$p['et_offset']) ?><br>
          Материал: <?= e((string)$p['material']) ?><br>
          Тип: <?= e((string)$p['type']) ?><br>
          Цвет: <?= e((string)$p['color']) ?><br>
        </div>
      </div>

      <form method="post" action="<?= e(base_url('product.php?id=' . $id)) ?>">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="add_to_cart">
        <div class="field">
          <div class="label">Количество</div>
          <input class="input" name="qty" type="number" min="1" value="1" style="max-width:140px">
        </div>
        <button class="btn" type="submit" <?= (int)$p['stock_qty'] <= 0 ? 'disabled' : '' ?>>Добавить в корзину</button>
        <?php if (!$u): ?>
          <div class="hint" style="margin-top:8px;">Заказ доступен только после входа в аккаунт.</div>
        <?php endif; ?>
      </form>

      <div style="height:10px"></div>

      <form method="post" action="<?= e(base_url('product.php?id=' . $id)) ?>">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="toggle_fav">
        <button class="btn btn--ghost" type="submit"><?= $isFav ? 'Убрать из избранного' : 'Добавить в избранное' ?></button>
        <?php if (!$u): ?>
          <div class="hint" style="margin-top:8px;">Избранное доступно только после входа.</div>
        <?php endif; ?>
      </form>

      <div style="height:14px"></div>

      <a class="tab tab--active" href="<?= e(base_url('cart.php')) ?>">Перейти в корзину →</a>
    </div>
  </aside>
</section>

<?php render_footer(); ?>

