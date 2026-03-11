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
    $id = (int)($_POST['id'] ?? 0);
    $role = (string)($_POST['role'] ?? '');
    if ($id > 0 && in_array($role, ['user', 'admin'], true)) {
        db()->prepare('UPDATE users SET role=? WHERE id=?')->execute([$role, $id]);
        flash_set('ok', 'Роль обновлена.');
    }
    header('Location: ' . base_url('admin/users.php'));
    exit;
}

$stmt = db()->query('SELECT id, email, full_name, birth_date, role, created_at FROM users ORDER BY created_at DESC LIMIT 300');
$users = $stmt->fetchAll();

render_header('Админ — пользователи');
?>

<section class="hero">
  <div class="hero__card">
    <h1 class="hero__title">Пользователи</h1>
    <p class="hero__sub">Просмотр пользователей и управление ролями.</p>
  </div>
</section>

<section class="grid" style="grid-template-columns: 1fr;">
  <div class="panel">
    <div class="panel__body">
      <div class="tabs">
        <a class="tab" href="<?= e(base_url('admin/products.php')) ?>">Товары</a>
        <a class="tab" href="<?= e(base_url('admin/orders.php')) ?>">Заказы</a>
        <a class="tab tab--active" href="<?= e(base_url('admin/users.php')) ?>">Пользователи</a>
      </div>

      <div style="height:12px"></div>

      <table class="table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Email</th>
            <th>ФИО</th>
            <th>Дата рождения</th>
            <th>Роль</th>
            <th>Создан</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($users as $u): ?>
            <tr>
              <td><?= (int)$u['id'] ?></td>
              <td><?= e((string)$u['email']) ?></td>
              <td><?= e((string)$u['full_name']) ?></td>
              <td class="hint"><?= e((string)$u['birth_date']) ?></td>
              <td><span class="badge"><?= e((string)$u['role']) ?></span></td>
              <td class="hint"><?= e((string)$u['created_at']) ?></td>
              <td>
                <form method="post" action="<?= e(base_url('admin/users.php')) ?>" style="display:flex; gap:10px; align-items:center; justify-content:flex-end;">
                  <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                  <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                  <select class="select" name="role" style="padding:8px 10px; border-radius:12px;">
                    <option value="user" <?= ((string)$u['role']) === 'user' ? 'selected' : '' ?>>user</option>
                    <option value="admin" <?= ((string)$u['role']) === 'admin' ? 'selected' : '' ?>>admin</option>
                  </select>
                  <button class="btn btn--ghost" type="submit">Сохранить</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$users): ?>
            <tr><td colspan="7" class="hint">Нет пользователей.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>

<?php render_footer(); ?>

