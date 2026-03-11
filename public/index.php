<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/includes/layout.php';
require_once __DIR__ . '/../app/includes/db.php';
require_once __DIR__ . '/../app/includes/view.php';

// Filters
$diameterMin = isset($_GET['r_min']) ? (float)$_GET['r_min'] : null;
$diameterMax = isset($_GET['r_max']) ? (float)$_GET['r_max'] : null;
$bolt = isset($_GET['bolt']) ? trim((string)$_GET['bolt']) : '';
$widthMin = isset($_GET['w_min']) ? (float)$_GET['w_min'] : null;
$widthMax = isset($_GET['w_max']) ? (float)$_GET['w_max'] : null;
$etMin = isset($_GET['et_min']) ? (int)$_GET['et_min'] : null;
$etMax = isset($_GET['et_max']) ? (int)$_GET['et_max'] : null;
$brand = isset($_GET['brand']) ? trim((string)$_GET['brand']) : '';
$material = isset($_GET['material']) ? trim((string)$_GET['material']) : '';
$type = isset($_GET['type']) ? trim((string)$_GET['type']) : '';
$color = isset($_GET['color']) ? trim((string)$_GET['color']) : '';
$priceMin = isset($_GET['p_min']) ? (float)$_GET['p_min'] : null;
$priceMax = isset($_GET['p_max']) ? (float)$_GET['p_max'] : null;
$q = isset($_GET['q']) ? trim((string)$_GET['q']) : '';

$sort = isset($_GET['sort']) ? (string)$_GET['sort'] : 'new';
$sortSql = 'p.created_at DESC';
if ($sort === 'price_asc') $sortSql = 'p.price ASC';
if ($sort === 'price_desc') $sortSql = 'p.price DESC';
if ($sort === 'popular') $sortSql = 'p.popularity DESC';

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 9;
$offset = ($page - 1) * $perPage;

$where = [];
$params = [];

if ($diameterMin !== null) { $where[] = 'p.diameter_inch >= ?'; $params[] = $diameterMin; }
if ($diameterMax !== null && $diameterMax > 0) { $where[] = 'p.diameter_inch <= ?'; $params[] = $diameterMax; }
if ($bolt !== '') { $where[] = 'p.bolt_pattern = ?'; $params[] = $bolt; }
if ($widthMin !== null) { $where[] = 'p.width_inch >= ?'; $params[] = $widthMin; }
if ($widthMax !== null && $widthMax > 0) { $where[] = 'p.width_inch <= ?'; $params[] = $widthMax; }
if ($etMin !== null) { $where[] = 'p.et_offset >= ?'; $params[] = $etMin; }
if ($etMax !== null && $etMax !== 0) { $where[] = 'p.et_offset <= ?'; $params[] = $etMax; }
if ($brand !== '') { $where[] = 'p.brand = ?'; $params[] = $brand; }
if ($material !== '') { $where[] = 'p.material = ?'; $params[] = $material; }
if ($type !== '') { $where[] = 'p.type = ?'; $params[] = $type; }
if ($color !== '') { $where[] = 'p.color = ?'; $params[] = $color; }
if ($priceMin !== null) { $where[] = 'p.price >= ?'; $params[] = $priceMin; }
if ($priceMax !== null && $priceMax > 0) { $where[] = 'p.price <= ?'; $params[] = $priceMax; }
if ($q !== '') { $where[] = '(p.name LIKE ? OR p.brand LIKE ?)'; $params[] = '%' . $q . '%'; $params[] = '%' . $q . '%'; }

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

// Count
$stmt = db()->prepare("SELECT COUNT(*) AS c FROM products p $whereSql");
$stmt->execute($params);
$total = (int)($stmt->fetch()['c'] ?? 0);
$pages = max(1, (int)ceil($total / $perPage));

// Rows with primary image
$sql = "
SELECT
  p.*,
  (SELECT url FROM product_images i WHERE i.product_id=p.id ORDER BY i.is_primary DESC, i.id ASC LIMIT 1) AS image_url
FROM products p
$whereSql
ORDER BY $sortSql
LIMIT $perPage OFFSET $offset
";
$stmt = db()->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Filter options (simple)
$brands = db()->query("SELECT DISTINCT brand FROM products ORDER BY brand")->fetchAll();
$bolts = db()->query("SELECT DISTINCT bolt_pattern FROM products ORDER BY bolt_pattern")->fetchAll();
$colors = db()->query("SELECT DISTINCT color FROM products ORDER BY color")->fetchAll();

render_header('BLACKFORGE — Каталог');
render_breadcrumbs([['title' => 'Главная'],]);
?>

<section class="hero">
  <div class="hero__card">
    <h1 class="hero__title"><span class="gold">BLACKFORGE</span> — диски, где металл выглядит дорого</h1>
    <p class="hero__sub">Строгий премиальный каталог: фильтруй по размеру, параметрам и материалу. Картинки отображаются без обрезки — диск всегда в центре внимания.</p>
  </div>
</section>

<section class="grid">
  <aside class="panel">
    <div class="panel__head">
      <div class="row">
        <div style="font-weight:800; letter-spacing:.02em;">Фильтры</div>
        <a class="tab" href="<?= e(base_url('index.php')) ?>">Сброс</a>
      </div>
    </div>
    <div class="panel__body">
      <form method="get" action="<?= e(base_url('index.php')) ?>">
        <div class="field">
          <div class="label">Поиск</div>
          <input class="input" name="q" value="<?= e($q) ?>" placeholder="BLACKFORGE / бренд / модель">
        </div>

        <div class="field">
          <div class="label">Диаметр (R)</div>
          <div class="row">
            <input class="input" style="width:50%" name="r_min" value="<?= e($diameterMin !== null ? (string)$diameterMin : '') ?>" placeholder="мин">
            <input class="input" style="width:50%" name="r_max" value="<?= e($diameterMax !== null ? (string)$diameterMax : '') ?>" placeholder="макс">
          </div>
          <div class="hint">Напр. 15–22+</div>
        </div>

        <div class="field">
          <div class="label">Разболтовка</div>
          <select class="select" name="bolt">
            <option value="">Любая</option>
            <?php foreach ($bolts as $b): $v = (string)$b['bolt_pattern']; ?>
              <option value="<?= e($v) ?>" <?= $v === $bolt ? 'selected' : '' ?>><?= e($v) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="field">
          <div class="label">Ширина (J)</div>
          <div class="row">
            <input class="input" style="width:50%" name="w_min" value="<?= e($widthMin !== null ? (string)$widthMin : '') ?>" placeholder="мин">
            <input class="input" style="width:50%" name="w_max" value="<?= e($widthMax !== null ? (string)$widthMax : '') ?>" placeholder="макс">
          </div>
        </div>

        <div class="field">
          <div class="label">Вылет (ET)</div>
          <div class="row">
            <input class="input" style="width:50%" name="et_min" value="<?= e($etMin !== null ? (string)$etMin : '') ?>" placeholder="мин">
            <input class="input" style="width:50%" name="et_max" value="<?= e($etMax !== null ? (string)$etMax : '') ?>" placeholder="макс">
          </div>
        </div>

        <div class="field">
          <div class="label">Производитель</div>
          <select class="select" name="brand">
            <option value="">Любой</option>
            <?php foreach ($brands as $b): $v = (string)$b['brand']; ?>
              <option value="<?= e($v) ?>" <?= $v === $brand ? 'selected' : '' ?>><?= e($v) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="field">
          <div class="label">Материал</div>
          <select class="select" name="material">
            <option value="">Любой</option>
            <option value="cast" <?= $material === 'cast' ? 'selected' : '' ?>>Литые</option>
            <option value="forged" <?= $material === 'forged' ? 'selected' : '' ?>>Кованые</option>
          </select>
        </div>

        <div class="field">
          <div class="label">Тип</div>
          <select class="select" name="type">
            <option value="">Любой</option>
            <option value="sport" <?= $type === 'sport' ? 'selected' : '' ?>>Спортивные</option>
            <option value="lux" <?= $type === 'lux' ? 'selected' : '' ?>>Люкс</option>
          </select>
        </div>

        <div class="field">
          <div class="label">Цвет</div>
          <select class="select" name="color">
            <option value="">Любой</option>
            <?php foreach ($colors as $c): $v = (string)$c['color']; ?>
              <option value="<?= e($v) ?>" <?= $v === $color ? 'selected' : '' ?>><?= e($v) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="field">
          <div class="label">Цена</div>
          <div class="row">
            <input class="input" style="width:50%" name="p_min" value="<?= e($priceMin !== null ? (string)$priceMin : '') ?>" placeholder="от">
            <input class="input" style="width:50%" name="p_max" value="<?= e($priceMax !== null ? (string)$priceMax : '') ?>" placeholder="до">
          </div>
        </div>

        <div class="field">
          <div class="label">Сортировка</div>
          <select class="select" name="sort">
            <option value="new" <?= $sort === 'new' ? 'selected' : '' ?>>Новизне</option>
            <option value="popular" <?= $sort === 'popular' ? 'selected' : '' ?>>Популярности</option>
            <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Цене (возр.)</option>
            <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Цене (убыв.)</option>
          </select>
        </div>

        <button class="btn" type="submit">Показать</button>
      </form>
    </div>
  </aside>

  <div class="panel">
    <div class="panel__head">
      <div class="row">
        <div style="font-weight:800; letter-spacing:.02em;">Товары</div>
        <div class="hint"><?= (int)$total ?> поз.</div>
      </div>
    </div>
    <div class="panel__body">
      <?php if (!$products): ?>
        <div class="alert">Ничего не найдено — попробуй ослабить фильтры.</div>
      <?php else: ?>
        <div class="products">
          <?php foreach ($products as $p): ?>
            <a class="card" href="<?= e(base_url('product.php?id=' . (int)$p['id'])) ?>">
              <div class="card__img">
                <?php if (!empty($p['image_url'])): ?>
                  <img src="<?= e((string)$p['image_url']) ?>" alt="<?= e((string)$p['name']) ?>">
                <?php else: ?>
                  <div class="hint">Нет изображения</div>
                <?php endif; ?>
              </div>
              <div class="card__body">
                <div class="row">
                  <div class="card__title"><?= e((string)$p['name']) ?></div>
                  <span class="badge">R<?= e((string)$p['diameter_inch']) ?></span>
                </div>
                <div class="card__meta">
                  <?= e((string)$p['brand']) ?> · <?= e((string)$p['bolt_pattern']) ?> · <?= e((string)$p['width_inch']) ?>J · ET<?= e((string)$p['et_offset']) ?><br>
                  <?= e((string)$p['color']) ?> · <?= e((string)$p['material']) ?> · <?= e((string)$p['type']) ?>
                </div>
                <div class="row" style="margin-top:auto;">
                  <div class="price"><span class="gold"><?= number_format((float)$p['price'], 0, '.', ' ') ?></span> ₽ / диск</div>
                  <div class="hint">Комплект: <?= number_format((float)$p['price'] * 4, 0, '.', ' ') ?> ₽</div>
                  <div class="hint"><?= (int)$p['stock_qty'] > 0 ? 'В наличии' : 'Нет' ?></div>
                </div>
              </div>
            </a>
          <?php endforeach; ?>
        </div>

        <div style="display:flex; gap:10px; justify-content:space-between; margin-top:14px; align-items:center;">
          <div class="hint">Стр. <?= (int)$page ?> / <?= (int)$pages ?></div>
          <div style="display:flex; gap:10px;">
            <?php
              $qs = $_GET;
              if ($page > 1) {
                  $qs['page'] = $page - 1;
                  echo '<a class="tab" href="' . e(base_url('index.php?' . http_build_query($qs))) . '">← Назад</a>';
              }
              if ($page < $pages) {
                  $qs['page'] = $page + 1;
                  echo '<a class="tab tab--active" href="' . e(base_url('index.php?' . http_build_query($qs))) . '">Далее →</a>';
              }
            ?>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>

<?php render_footer(); ?>

