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
?>
<section class="hero"><div class="hero__card"><h1 class="hero__title">Розыгрыши и акции</h1><p class="hero__sub">Сделали отдельный раздел с активностями.</p></div></section>
<section class="grid grid--single"><div class="panel"><div class="panel__body"><?= nl2br(e($body)) ?></div></div></section>
<?php render_footer(); ?>
