<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/includes/session.php';
require_once __DIR__ . '/../app/includes/view.php';

ensure_session_started();
$_SESSION = [];
session_destroy();

header('Location: ' . base_url('index.php'));
exit;

