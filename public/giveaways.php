<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/includes/layout.php';
require_once __DIR__ . '/../app/includes/content.php';

render_header('Розыгрыши — BLACKFORGE');
render_breadcrumbs([
    ['title' => 'Главная', 'url' => base_url('index.php')],
    ['title' => 'Розыгрыши'],
]);

$body = content_get('giveaways_body', "Каждый месяц запускаем розыгрыш сертификата на покупку дисков.\n\nУсловия участия публикуются здесь и в наших соцсетях.");

$images = [
    content_get('giveaways_image_1', ''),
    content_get('giveaways_image_2', ''),
    content_get('giveaways_image_3', ''),
];
$images = array_values(array_filter(array_map('trim', $images), fn($v) => $v !== ''));

?>
<section class="hero"><div class="hero__card"><h1 class="hero__title">Розыгрыши и акции</h1><p class="hero__sub">Сделали отдельный раздел с активностями.</p></div></section>
<section class="grid grid--single"><div class="panel"><div class="panel__body"><?= nl2br(e($body)) ?>
<?php if ($images): ?>
  <div class="products" style="grid-template-columns: repeat(<?= count($images) >= 3 ? 3 : count($images) ?>, minmax(0,1fr)); margin-top:12px;">
    <?php foreach ($images as $img): ?>
      <div class="card" style="min-height:240px;"><div class="card__img" style="height:240px;"><img src="<?= e($img) ?>" alt="giveaway"></div></div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
</div></div></section>
<?php render_footer(); ?>
