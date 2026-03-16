<?php
http_response_code(404);
require_once __DIR__ . '/../app/includes/layout.php';
render_header('404');
render_breadcrumbs([['title'=>'Главная','url'=>base_url('index.php')],['title'=>'404']]);
?>
<section class="hero"><div class="hero__card"><h1 class="hero__title">404</h1><p class="hero__sub">Страница не найдена.</p><a class="btn" href="<?= e(base_url('index.php')) ?>">На главную</a></div></section>
<?php render_footer(); ?>
