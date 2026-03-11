<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/includes/layout.php';
require_once __DIR__ . '/../app/includes/session.php';
require_once __DIR__ . '/../app/includes/db.php';
require_once __DIR__ . '/../app/includes/auth.php';

ensure_session_started();

$_SESSION['cart'] ??= [];

function cart_items(): array
{
    $cart = $_SESSION['cart'] ?? [];
    if (!is_array($cart) || !$cart) {
        return [];
    }
    $ids = array_keys($cart);
    $ids = array_values(array_filter(array_map('intval', $ids), fn($v) => $v > 0));
    if (!$ids) return [];

    $in = implode(',', array_fill(0, count($ids), '?'));
    $stmt = db()->prepare("
        SELECT
          p.*,
          (SELECT url FROM product_images i WHERE i.product_id=p.id ORDER BY i.is_primary DESC, i.id ASC LIMIT 1) AS image_url
        FROM products p
        WHERE p.id IN ($in)
    ");
    $stmt->execute($ids);
    $rows = $stmt->fetchAll();

    $byId = [];
    foreach ($rows as $r) $byId[(int)$r['id']] = $r;

    $out = [];
    foreach ($cart as $pid => $qty) {
        $pid = (int)$pid;
        $qty = (int)$qty;
        if ($pid <= 0 || $qty <= 0 || empty($byId[$pid])) continue;
        $out[] = ['product' => $byId[$pid], 'qty' => $qty];
    }
    return $out;
}

function promo_apply(string $code, float $subtotal): array
{
    $code = strtoupper(trim($code));
    if ($code === '') return ['ok' => false, 'discount' => 0.0, 'code' => null, 'msg' => 'Укажи промокод.'];

    $stmt = db()->prepare('SELECT * FROM promo_codes WHERE code=? AND active=1');
    $stmt->execute([$code]);
    $p = $stmt->fetch();
    if (!$p) return ['ok' => false, 'discount' => 0.0, 'code' => null, 'msg' => 'Промокод не найден или отключен.'];

    $discount = 0.0;
    if (($p['discount_type'] ?? '') === 'percent') {
        $discount = $subtotal * ((float)$p['discount_value'] / 100.0);
    } else {
        $discount = (float)$p['discount_value'];
    }
    $discount = max(0.0, min($discount, $subtotal));

    return ['ok' => true, 'discount' => $discount, 'code' => $code, 'msg' => 'Промокод применён.'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify_or_403();
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'update_qty') {
        foreach (($_POST['qty'] ?? []) as $pid => $qty) {
            $pid = (int)$pid;
            $qty = (int)$qty;
            if ($pid <= 0) continue;
            if ($qty <= 0) unset($_SESSION['cart'][$pid]);
            else $_SESSION['cart'][$pid] = min(999, $qty);
        }
        flash_set('ok', 'Корзина обновлена.');
        header('Location: ' . base_url('cart.php'));
        exit;
    }

    if ($action === 'remove') {
        $pid = (int)($_POST['product_id'] ?? 0);
        if ($pid > 0) unset($_SESSION['cart'][$pid]);
        flash_set('ok', 'Позиция удалена.');
        header('Location: ' . base_url('cart.php'));
        exit;
    }

    if ($action === 'apply_promo') {
        $items = cart_items();
        $subtotal = 0.0;
        foreach ($items as $it) $subtotal += (float)$it['product']['price'] * (int)$it['qty'];
        $res = promo_apply((string)($_POST['promo'] ?? ''), $subtotal);
        if ($res['ok']) {
            $_SESSION['promo'] = ['code' => $res['code'], 'discount' => $res['discount']];
            flash_set('ok', $res['msg']);
        } else {
            unset($_SESSION['promo']);
            flash_set('err', $res['msg']);
        }
        header('Location: ' . base_url('cart.php'));
        exit;
    }

    if ($action === 'checkout') {
        $items = cart_items();
        if (!$items) {
            flash_set('err', 'Корзина пуста.');
            header('Location: ' . base_url('cart.php'));
            exit;
        }

        $u = current_user();
        $customerEmail = $u ? (string)$u['email'] : trim((string)($_POST['email'] ?? ''));
        $customerName = $u ? (string)$u['full_name'] : trim((string)($_POST['name'] ?? ''));
        if (!filter_var($customerEmail, FILTER_VALIDATE_EMAIL) || $customerName === '') {
            flash_set('err', 'Укажи имя и корректный email для заказа.');
            header('Location: ' . base_url('cart.php'));
            exit;
        }

        $subtotal = 0.0;
        foreach ($items as $it) $subtotal += (float)$it['product']['price'] * (int)$it['qty'];

        $promo = $_SESSION['promo']['code'] ?? null;
        $discount = (float)($_SESSION['promo']['discount'] ?? 0);
        $discount = max(0.0, min($discount, $subtotal));
        $total = $subtotal - $discount;

        $pdo = db();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('
                INSERT INTO orders (user_id, customer_email, customer_name, status, promo_code, subtotal, discount, total)
                VALUES (?,?,?,?,?,?,?,?)
            ');
            $stmt->execute([
                $u ? (int)$u['id'] : null,
                $customerEmail,
                $customerName,
                'new',
                $promo,
                $subtotal,
                $discount,
                $total,
            ]);
            $orderId = (int)$pdo->lastInsertId();

            $ins = $pdo->prepare('
                INSERT INTO order_items (order_id, product_id, product_name, unit_price, qty, line_total)
                VALUES (?,?,?,?,?,?)
            ');
            foreach ($items as $it) {
                $prod = $it['product'];
                $qty = (int)$it['qty'];
                $unit = (float)$prod['price'];
                $line = $unit * $qty;
                $ins->execute([$orderId, (int)$prod['id'], (string)$prod['name'], $unit, $qty, $line]);
            }

            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            flash_set('err', 'Не удалось оформить заказ.');
            header('Location: ' . base_url('cart.php'));
            exit;
        }

        $_SESSION['cart'] = [];
        unset($_SESSION['promo']);
        flash_set('ok', 'Заказ оформлен. Мы свяжемся с вами (учебный режим).');
        header('Location: ' . base_url('index.php'));
        exit;
    }
}

$items = cart_items();
$subtotal = 0.0;
foreach ($items as $it) $subtotal += (float)$it['product']['price'] * (int)$it['qty'];
$promoCode = (string)($_SESSION['promo']['code'] ?? '');
$discount = (float)($_SESSION['promo']['discount'] ?? 0);
$discount = max(0.0, min($discount, $subtotal));
$total = $subtotal - $discount;

render_header('Корзина — BLACKFORGE');
?>

<section class="hero">
  <div class="hero__card">
    <h1 class="hero__title">Корзина</h1>
    <p class="hero__sub">Изменяй количество, применяй промокод и оформляй заказ.</p>
  </div>
</section>

<section class="grid" style="grid-template-columns: 1.2fr .8fr;">
  <div class="panel">
    <div class="panel__body">
      <?php if (!$items): ?>
        <div class="alert">Корзина пуста. <a class="gold" href="<?= e(base_url('index.php')) ?>">Перейти в каталог</a></div>
      <?php else: ?>
        <form method="post" action="<?= e(base_url('cart.php')) ?>">
          <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="action" value="update_qty">

          <table class="table">
            <thead>
              <tr>
                <th>Товар</th>
                <th style="width:120px">Кол-во</th>
                <th style="width:140px">Цена</th>
                <th style="width:140px">Сумма</th>
                <th style="width:90px"></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($items as $it):
                $p = $it['product'];
                $qty = (int)$it['qty'];
                $line = (float)$p['price'] * $qty;
              ?>
                <tr>
                  <td>
                    <div style="display:flex; gap:10px; align-items:center;">
                      <div style="width:88px; height:62px; border:1px solid var(--line); border-radius:14px; overflow:hidden; background:#0b0b0b; display:flex; align-items:center; justify-content:center;">
                        <?php if (!empty($p['image_url'])): ?>
                          <img src="<?= e((string)$p['image_url']) ?>" alt="" style="width:100%;height:100%;object-fit:contain;">
                        <?php else: ?>
                          <span class="hint">—</span>
                        <?php endif; ?>
                      </div>
                      <div>
                        <div style="font-weight:800; letter-spacing:.02em;">
                          <a class="gold" href="<?= e(base_url('product.php?id=' . (int)$p['id'])) ?>"><?= e((string)$p['name']) ?></a>
                        </div>
                        <div class="hint"><?= e((string)$p['bolt_pattern']) ?> · R<?= e((string)$p['diameter_inch']) ?> · <?= e((string)$p['width_inch']) ?>J · ET<?= e((string)$p['et_offset']) ?></div>
                      </div>
                    </div>
                  </td>
                  <td>
                    <input class="input" type="number" min="0" name="qty[<?= (int)$p['id'] ?>]" value="<?= $qty ?>">
                  </td>
                  <td><?= number_format((float)$p['price'], 0, '.', ' ') ?> ₽</td>
                  <td><span class="gold"><?= number_format($line, 0, '.', ' ') ?></span> ₽</td>
                  <td>
                    <form method="post" action="<?= e(base_url('cart.php')) ?>">
                      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                      <input type="hidden" name="action" value="remove">
                      <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
                      <button class="btn btn--danger" type="submit">×</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>

          <div style="display:flex; justify-content:flex-end; margin-top:12px;">
            <button class="btn btn--ghost" type="submit">Обновить количество</button>
          </div>
        </form>
      <?php endif; ?>
    </div>
  </div>

  <aside class="panel">
    <div class="panel__head">
      <div style="font-weight:800; letter-spacing:.02em;">Итого</div>
    </div>
    <div class="panel__body">
      <div class="row"><div class="hint">Подытог</div><div><?= number_format($subtotal, 0, '.', ' ') ?> ₽</div></div>
      <div class="row" style="margin-top:8px;"><div class="hint">Скидка</div><div>- <?= number_format($discount, 0, '.', ' ') ?> ₽</div></div>
      <div class="row" style="margin-top:8px;"><div class="hint">К оплате</div><div class="price"><span class="gold"><?= number_format($total, 0, '.', ' ') ?></span> ₽</div></div>

      <div style="height:12px"></div>

      <form method="post" action="<?= e(base_url('cart.php')) ?>">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="apply_promo">
        <div class="field">
          <div class="label">Промокод</div>
          <input class="input" name="promo" value="<?= e($promoCode) ?>" placeholder="BLACK10">
          <div class="hint">Тестовый: <span class="gold">BLACK10</span> (‑10%)</div>
        </div>
        <button class="btn btn--ghost" type="submit">Применить</button>
      </form>

      <div style="height:14px"></div>

      <form method="post" action="<?= e(base_url('cart.php')) ?>">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="checkout">

        <?php $u = current_user(); ?>
        <?php if (!$u): ?>
          <div class="field">
            <div class="label">Имя</div>
            <input class="input" name="name" required>
          </div>
          <div class="field">
            <div class="label">Email</div>
            <input class="input" name="email" required>
          </div>
        <?php else: ?>
          <div class="alert">Заказ оформится на: <span class="gold"><?= e((string)$u['full_name']) ?></span> (<?= e((string)$u['email']) ?>)</div>
        <?php endif; ?>

        <button class="btn" type="submit" <?= $items ? '' : 'disabled' ?>>Оформить заказ</button>
      </form>
    </div>
  </aside>
</section>

<?php render_footer(); ?>

