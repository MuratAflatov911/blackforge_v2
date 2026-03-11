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
    if (!is_array($cart) || !$cart) return [];

    $ids = array_values(array_filter(array_map('intval', array_keys($cart)), fn($v) => $v > 0));
    if (!$ids) return [];

    $in = implode(',', array_fill(0, count($ids), '?'));
    $stmt = db()->prepare("SELECT p.*, (SELECT url FROM product_images i WHERE i.product_id=p.id ORDER BY i.is_primary DESC, i.id ASC LIMIT 1) AS image_url FROM products p WHERE p.id IN ($in)");
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify_or_403();
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'update_qty') {
        $removeId = (int)($_POST['remove_id'] ?? 0);
        if ($removeId > 0) {
            unset($_SESSION['cart'][$removeId]);
            flash_set('ok', 'Позиция удалена.');
            header('Location: ' . base_url('cart.php'));
            exit;
        }

        foreach ((array)($_POST['qty'] ?? []) as $pid => $qty) {
            $pid = (int)$pid;
            $qty = (int)$qty;
            if ($pid <= 0) continue;
            if ($qty <= 0) {
                unset($_SESSION['cart'][$pid]);
            } else {
                $_SESSION['cart'][$pid] = min(999, $qty);
            }
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

    if ($action === 'checkout') {
        require_login();
        $items = cart_items();
        if (!$items) {
            flash_set('err', 'Корзина пуста.');
            header('Location: ' . base_url('cart.php'));
            exit;
        }

        $u = current_user();
        $subtotal = 0.0;
        foreach ($items as $it) $subtotal += (float)$it['product']['price'] * (int)$it['qty'];

        $pdo = db();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('INSERT INTO orders (user_id, customer_email, customer_name, status, promo_code, subtotal, discount, total) VALUES (?,?,?,?,?,?,?,?)');
            $stmt->execute([(int)$u['id'], (string)$u['email'], (string)$u['full_name'], 'new', null, $subtotal, 0, $subtotal]);
            $orderId = (int)$pdo->lastInsertId();

            $ins = $pdo->prepare('INSERT INTO order_items (order_id, product_id, product_name, unit_price, qty, line_total) VALUES (?,?,?,?,?,?)');
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
        flash_set('ok', 'Заказ оформлен.');
        header('Location: ' . base_url('index.php'));
        exit;
    }
}

$items = cart_items();
$subtotal = 0.0;
foreach ($items as $it) $subtotal += (float)$it['product']['price'] * (int)$it['qty'];

render_header('Корзина — BLACKFORGE');
render_breadcrumbs([
    ['title' => 'Главная', 'url' => base_url('index.php')],
    ['title' => 'Корзина'],
]);
?>

<section class="hero"><div class="hero__card"><h1 class="hero__title">Корзина</h1><p class="hero__sub">Цена всегда указана за 1 диск. Комплект = 4 диска.</p></div></section>
<section class="grid" style="grid-template-columns: 1.2fr .8fr;">
  <div class="panel"><div class="panel__body">
    <?php if (!$items): ?>
      <div class="alert">Корзина пуста. <a class="gold" href="<?= e(base_url('index.php')) ?>">Перейти в каталог</a></div>
    <?php else: ?>
      <form method="post" action="<?= e(base_url('cart.php')) ?>">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="update_qty">
        <table class="table"><thead><tr><th>Товар</th><th>Кол-во</th><th>Цена / диск</th><th>Сумма</th><th></th></tr></thead><tbody>
        <?php foreach ($items as $it): $p=$it['product']; $qty=(int)$it['qty']; $line=(float)$p['price']*$qty; ?>
          <tr>
            <td>
              <div style="display:flex; gap:10px; align-items:center; min-width:260px;">
                <div style="width:96px; height:68px; border:1px solid var(--line); border-radius:10px; overflow:hidden; background:#f9fbff; display:flex; align-items:center; justify-content:center;">
                  <?php if (!empty($p['image_url'])): ?><img src="<?= e((string)$p['image_url']) ?>" alt="" style="width:100%;height:100%;object-fit:contain;"><?php endif; ?>
                </div>
                <div><div style="font-weight:700;"><a class="gold" href="<?= e(base_url('product.php?id=' . (int)$p['id'])) ?>"><?= e((string)$p['name']) ?></a></div><div class="hint">Комплект (4 шт): <?= number_format((float)$p['price'] * 4, 0, '.', ' ') ?> ₽</div></div>
              </div>
            </td>
            <td><input class="input" type="number" min="1" name="qty[<?= (int)$p['id'] ?>]" value="<?= $qty ?>" style="max-width:110px;"></td>
            <td><?= number_format((float)$p['price'], 0, '.', ' ') ?> ₽</td>
            <td><span class="gold"><?= number_format($line, 0, '.', ' ') ?></span> ₽</td>
            <td><button class="btn btn--danger" type="submit" name="remove_id" value="<?= (int)$p['id'] ?>">Удалить</button></td>
          </tr>
        <?php endforeach; ?>
        </tbody></table>
                <div style="display:flex; justify-content:flex-end; margin-top:12px;"><button class="btn btn--ghost" type="submit">Обновить количество</button></div>
      </form>
    <?php endif; ?>
  </div></div>

  <aside class="panel"><div class="panel__head"><div style="font-weight:700;">Итого</div></div><div class="panel__body">
    <div class="row"><div class="hint">К оплате</div><div class="price"><span class="gold"><?= number_format($subtotal, 0, '.', ' ') ?></span> ₽</div></div>
    <div style="height:14px"></div>
    <form method="post" action="<?= e(base_url('cart.php')) ?>">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="action" value="checkout">
      <?php $u = current_user(); ?>
      <?php if (!$u): ?>
        <div class="alert">Для оформления заказа войдите в аккаунт. <a class="gold" href="<?= e(base_url('login.php')) ?>">Войти</a></div>
        <button class="btn" type="button" onclick="location.href='<?= e(base_url('login.php')) ?>'" <?= $items ? '' : 'disabled' ?>>Войти для заказа</button>
      <?php else: ?>
        <div class="alert">Заказ оформится на: <span class="gold"><?= e((string)$u['full_name']) ?></span> (<?= e((string)$u['email']) ?>)</div>
        <button class="btn" type="submit" <?= $items ? '' : 'disabled' ?>>Оформить заказ</button>
      <?php endif; ?>
    </form>
  </div></aside>
</section>

<?php render_footer(); ?>
