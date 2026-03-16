<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/includes/layout.php';
require_once __DIR__ . '/../app/includes/content.php';

render_header('Контакты — BLACKFORGE');
render_breadcrumbs([
    ['title' => 'Главная', 'url' => base_url('index.php')],
    ['title' => 'Контакты'],
]);

$phones = content_get('contacts_phone', '+7 (999) 123-45-67');
$email = content_get('contacts_email', 'info@blackforge.local');
$address = content_get('contacts_address', 'г. Москва, ул. Примерная, 10');
$body = content_get('contacts_body', 'Ежедневно с 10:00 до 20:00.');
?>
<section class="hero"><div class="hero__card"><h1 class="hero__title">Контакты</h1><p class="hero__sub">Свяжитесь с нами удобным способом.</p></div></section>
<section class="grid grid--single"><div class="panel"><div class="panel__body">
<div class="field"><div class="label">Телефон</div><div><?= e($phones) ?></div></div>
<div class="field"><div class="label">Email</div><div><?= e($email) ?></div></div>
<div class="field"><div class="label">Адрес</div><div><?= e($address) ?></div></div>
<div class="field"><div class="label">Дополнительно</div><div><?= nl2br(e($body)) ?></div></div>
</div></div></section>
<?php render_footer(); ?>
