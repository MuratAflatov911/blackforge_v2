<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/includes/layout.php';
require_once __DIR__ . '/../app/includes/content.php';

render_header('О нас — BLACKFORGE');
render_breadcrumbs([
    ['title' => 'Главная', 'url' => base_url('index.php')],
    ['title' => 'О нас'],
]);

$body = content_get('about_body', "BLACKFORGE — магазин автомобильных дисков. Мы помогаем подобрать диски под вашу разболтовку и стиль.\n\nРаботаем с литыми и коваными моделями, предоставляем консультации и поддержку после покупки.");
?>
<section class="hero"><div class="hero__card"><h1 class="hero__title">О нас</h1><p class="hero__sub">Коротко о компании и подходе к работе.</p></div></section>
<section class="grid grid--single"><div class="panel"><div class="panel__body"><?= nl2br(e($body)) ?></div></div></section>
<?php render_footer(); ?>
