<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/includes/layout.php';
require_once __DIR__ . '/../../app/includes/auth.php';
require_once __DIR__ . '/../../app/includes/db.php';
require_once __DIR__ . '/../../app/includes/session.php';
require_once __DIR__ . '/../../app/includes/images.php';

require_admin();
ensure_session_started();

$id = (int)($_GET['id'] ?? 0);
$isEdit = $id > 0;

$p = [
    'name' => '',
    'brand' => '',
    'description' => '',
    'diameter_inch' => '19.0',
    'bolt_pattern' => '5x112',
    'width_inch' => '8.5',
    'et_offset' => '35',
    'material' => 'cast',
    'type' => 'sport',
    'color' => 'Graphite',
    'price' => '28990',
    'stock_qty' => '10',
    'popularity' => '0',
];

if ($isEdit) {
    $stmt = db()->prepare('SELECT * FROM products WHERE id=?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) {
        http_response_code(404);
        echo 'Not found';
        exit;
    }
    $p = array_merge($p, $row);
}

function redirect_self(int $id = 0): void
{
    $url = $id > 0 ? base_url('admin/product_edit.php?id=' . $id) : base_url('admin/product_edit.php');
    header('Location: ' . $url);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify_or_403();
    $action = (string)($_POST['action'] ?? 'save');

    if ($action === 'save') {
        $data = [
            'name' => trim((string)($_POST['name'] ?? '')),
            'brand' => trim((string)($_POST['brand'] ?? '')),
            'description' => trim((string)($_POST['description'] ?? '')),
            'diameter_inch' => (float)($_POST['diameter_inch'] ?? 0),
            'bolt_pattern' => trim((string)($_POST['bolt_pattern'] ?? '')),
            'width_inch' => (float)($_POST['width_inch'] ?? 0),
            'et_offset' => (int)($_POST['et_offset'] ?? 0),
            'material' => (string)($_POST['material'] ?? ''),
            'type' => (string)($_POST['type'] ?? ''),
            'color' => trim((string)($_POST['color'] ?? '')),
            'price' => (float)($_POST['price'] ?? 0),
            'stock_qty' => (int)($_POST['stock_qty'] ?? 0),
            'popularity' => (int)($_POST['popularity'] ?? 0),
        ];

        if ($data['name'] === '' || $data['brand'] === '' || $data['bolt_pattern'] === '' || $data['color'] === '') {
            flash_set('err', 'Заполни обязательные поля (название, бренд, разболтовка, цвет).');
            redirect_self($id);
        }
        if (!in_array($data['material'], ['cast', 'forged'], true) || !in_array($data['type'], ['sport', 'lux'], true)) {
            flash_set('err', 'Некорректный материал/тип.');
            redirect_self($id);
        }

        if ($isEdit) {
            $stmt = db()->prepare('
                UPDATE products SET
                  name=?, brand=?, description=?, diameter_inch=?, bolt_pattern=?, width_inch=?, et_offset=?,
                  material=?, type=?, color=?, price=?, stock_qty=?, popularity=?
                WHERE id=?
            ');
            $stmt->execute([
                $data['name'], $data['brand'], $data['description'], $data['diameter_inch'], $data['bolt_pattern'],
                $data['width_inch'], $data['et_offset'], $data['material'], $data['type'], $data['color'],
                $data['price'], $data['stock_qty'], $data['popularity'], $id
            ]);
            flash_set('ok', 'Товар обновлён.');
            redirect_self($id);
        } else {
            $stmt = db()->prepare('
                INSERT INTO products (name, brand, description, diameter_inch, bolt_pattern, width_inch, et_offset, material, type, color, price, stock_qty, popularity)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)
            ');
            $stmt->execute([
                $data['name'], $data['brand'], $data['description'], $data['diameter_inch'], $data['bolt_pattern'],
                $data['width_inch'], $data['et_offset'], $data['material'], $data['type'], $data['color'],
                $data['price'], $data['stock_qty'], $data['popularity']
            ]);
            $newId = (int)db()->lastInsertId();
            flash_set('ok', 'Товар создан.');
            redirect_self($newId);
        }
    }

    if ($action === 'add_image') {
        if (!$isEdit) {
            flash_set('err', 'Сначала создай товар, затем добавляй изображения.');
            redirect_self(0);
        }

        try {
            $imageUrl = '';
            if (!empty($_FILES['image_file']['name'] ?? '')) {
                $imageUrl = save_uploaded_image($_FILES['image_file']);
            } else {
                $imageUrl = save_image_from_url((string)($_POST['image_url'] ?? ''));
            }

            $isPrimary = !empty($_POST['is_primary']);
            if ($isPrimary) {
                db()->prepare('UPDATE product_images SET is_primary=0 WHERE product_id=?')->execute([$id]);
            }
            $stmt = db()->prepare('INSERT INTO product_images (product_id, url, is_primary) VALUES (?,?,?)');
            $stmt->execute([$id, $imageUrl, $isPrimary ? 1 : 0]);

            flash_set('ok', 'Изображение добавлено.');
        } catch (Throwable $e) {
            flash_set('err', $e->getMessage());
        }
        redirect_self($id);
    }

    if ($action === 'delete_image') {
        if ($isEdit) {
            $imgId = (int)($_POST['img_id'] ?? 0);
            if ($imgId > 0) {
                $stmt = db()->prepare('DELETE FROM product_images WHERE id=? AND product_id=?');
                $stmt->execute([$imgId, $id]);
                flash_set('ok', 'Изображение удалено.');
            }
        }
        redirect_self($id);
    }

    if ($action === 'set_primary') {
        if ($isEdit) {
            $imgId = (int)($_POST['img_id'] ?? 0);
            if ($imgId > 0) {
                db()->prepare('UPDATE product_images SET is_primary=0 WHERE product_id=?')->execute([$id]);
                db()->prepare('UPDATE product_images SET is_primary=1 WHERE id=? AND product_id=?')->execute([$imgId, $id]);
                flash_set('ok', 'Основное изображение обновлено.');
            }
        }
        redirect_self($id);
    }
}

$images = [];
if ($isEdit) {
    $imgsStmt = db()->prepare('SELECT * FROM product_images WHERE product_id=? ORDER BY is_primary DESC, id ASC');
    $imgsStmt->execute([$id]);
    $images = $imgsStmt->fetchAll();
}

render_header($isEdit ? 'Админ — редактировать товар' : 'Админ — новый товар');
render_breadcrumbs([['title' => 'Админ', 'url' => base_url('admin/index.php')], ['title' => 'Товары', 'url' => base_url('admin/products.php')], ['title' => $isEdit ? 'Редактирование' : 'Новый товар']]);
?>

<section class="hero">
  <div class="hero__card">
    <h1 class="hero__title"><?= $isEdit ? 'Редактирование товара' : 'Новый товар' ?></h1>
    <p class="hero__sub">Точные параметры и крупные фото — диск всегда главный.</p>
  </div>
</section>

<section class="grid" style="grid-template-columns: 1.15fr .85fr;">
  <div class="panel">
    <div class="panel__head">
      <div class="row">
        <div style="font-weight:800; letter-spacing:.02em;">Данные товара</div>
        <a class="tab" href="<?= e(base_url('admin/products.php')) ?>">← Назад к списку</a>
      </div>
    </div>
    <div class="panel__body">
      <form method="post" action="<?= e($isEdit ? base_url('admin/product_edit.php?id=' . $id) : base_url('admin/product_edit.php')) ?>">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="save">

        <div class="field">
          <div class="label">Название</div>
          <input class="input" name="name" value="<?= e((string)$p['name']) ?>" required>
        </div>
        <div class="field">
          <div class="label">Бренд</div>
          <input class="input" name="brand" value="<?= e((string)$p['brand']) ?>" required>
        </div>
        <div class="field">
          <div class="label">Описание</div>
          <textarea class="input" name="description" rows="5" style="resize:vertical;"><?= e((string)($p['description'] ?? '')) ?></textarea>
        </div>

        <div class="row" style="gap:12px; align-items:flex-start;">
          <div class="field field--grow">
            <div class="label">Диаметр (R)</div>
            <input class="input" name="diameter_inch" value="<?= e((string)$p['diameter_inch']) ?>" required>
          </div>
          <div class="field field--grow">
            <div class="label">Разболтовка</div>
            <input class="input" name="bolt_pattern" value="<?= e((string)$p['bolt_pattern']) ?>" required>
          </div>
        </div>

        <div class="row" style="gap:12px; align-items:flex-start;">
          <div class="field field--grow">
            <div class="label">Ширина (J)</div>
            <input class="input" name="width_inch" value="<?= e((string)$p['width_inch']) ?>" required>
          </div>
          <div class="field field--grow">
            <div class="label">Вылет (ET)</div>
            <input class="input" name="et_offset" value="<?= e((string)$p['et_offset']) ?>" required>
          </div>
        </div>

        <div class="row" style="gap:12px; align-items:flex-start;">
          <div class="field field--grow">
            <div class="label">Материал</div>
            <select class="select" name="material">
              <option value="cast" <?= ((string)$p['material']) === 'cast' ? 'selected' : '' ?>>Литые</option>
              <option value="forged" <?= ((string)$p['material']) === 'forged' ? 'selected' : '' ?>>Кованые</option>
            </select>
          </div>
          <div class="field field--grow">
            <div class="label">Тип</div>
            <select class="select" name="type">
              <option value="sport" <?= ((string)$p['type']) === 'sport' ? 'selected' : '' ?>>Спортивные</option>
              <option value="lux" <?= ((string)$p['type']) === 'lux' ? 'selected' : '' ?>>Люкс</option>
            </select>
          </div>
        </div>

        <div class="row" style="gap:12px; align-items:flex-start;">
          <div class="field field--grow">
            <div class="label">Цвет</div>
            <input class="input" name="color" value="<?= e((string)$p['color']) ?>" required>
          </div>
          <div class="field field--grow">
            <div class="label">Цена за 1 диск</div>
            <input class="input" name="price" value="<?= e((string)$p['price']) ?>" required>
          </div>
        </div>

        <div class="row" style="gap:12px; align-items:flex-start;">
          <div class="field field--grow">
            <div class="label">Склад</div>
            <input class="input" name="stock_qty" value="<?= e((string)$p['stock_qty']) ?>" required>
          </div>
          <div class="field field--grow">
            <div class="label">Популярность</div>
            <input class="input" name="popularity" value="<?= e((string)($p['popularity'] ?? '0')) ?>">
          </div>
        </div>

        <button class="btn" type="submit"><?= $isEdit ? 'Сохранить' : 'Создать' ?></button>
      </form>
    </div>
  </div>

  <aside class="panel">
    <div class="panel__head">
      <div style="font-weight:800; letter-spacing:.02em;">Изображения</div>
    </div>
    <div class="panel__body">
      <?php if (!$isEdit): ?>
        <div class="alert">Сначала создай товар — затем появится блок загрузки изображений.</div>
      <?php else: ?>
        <form method="post" enctype="multipart/form-data" action="<?= e(base_url('admin/product_edit.php?id=' . $id)) ?>">
          <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="action" value="add_image">

          <div class="field">
            <div class="label">Загрузка с устройства</div>
            <input class="input" type="file" name="image_file" accept="image/png,image/jpeg,image/webp">
            <div class="hint">JPG/PNG/WebP до 8 МБ.</div>
          </div>

          <div class="field">
            <div class="label">Или по URL</div>
            <input class="input" name="image_url" placeholder="https://.../image.jpg">
          </div>

          <label class="hint" style="display:flex; gap:10px; align-items:center; margin-bottom:12px;">
            <input type="checkbox" name="is_primary" value="1">
            Сделать основным
          </label>

          <button class="btn btn--ghost" type="submit">Добавить изображение</button>
        </form>

        <div style="height:14px"></div>

        <?php if (!$images): ?>
          <div class="alert">Пока нет изображений.</div>
        <?php else: ?>
          <div style="display:grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap:10px;">
            <?php foreach ($images as $img): ?>
              <div class="panel" style="border-radius:18px;">
                <div class="card__img" style="aspect-ratio: 16/11;">
                  <img src="<?= e((string)$img['url']) ?>" alt="" style="object-fit:contain;">
                </div>
                <div class="panel__body" style="padding:10px;">
                  <div class="row">
                    <span class="badge"><?= (int)$img['is_primary'] === 1 ? 'Основное' : 'Фото' ?></span>
                    <span class="hint">ID <?= (int)$img['id'] ?></span>
                  </div>
                  <div style="height:10px"></div>
                  <div style="display:flex; gap:10px; flex-wrap:wrap;">
                    <form method="post" action="<?= e(base_url('admin/product_edit.php?id=' . $id)) ?>">
                      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                      <input type="hidden" name="action" value="set_primary">
                      <input type="hidden" name="img_id" value="<?= (int)$img['id'] ?>">
                      <button class="tab tab--active" type="submit">Сделать основным</button>
                    </form>
                    <form method="post" action="<?= e(base_url('admin/product_edit.php?id=' . $id)) ?>">
                      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                      <input type="hidden" name="action" value="delete_image">
                      <input type="hidden" name="img_id" value="<?= (int)$img['id'] ?>">
                      <button class="tab" type="submit" onclick="return confirm('Удалить изображение?')">Удалить</button>
                    </form>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </aside>
</section>

<?php render_footer(); ?>

