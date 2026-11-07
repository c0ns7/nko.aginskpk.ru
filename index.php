<?php
define('ROOT_PATH', __DIR__);
require_once ROOT_PATH . '/includes/bootstrap.php';
require_once ROOT_PATH . '/includes/layout.php';

$route = current_path();
$parts = $route === '' ? array() : explode('/', $route);
$head = isset($parts[0]) ? $parts[0] : '';
$params = array_slice($parts, 1);

if ($head === 'admin') {
    $section = isset($parts[1]) ? $parts[1] : 'dashboard';
    $params = array_slice($parts, 2);
    $adminPages = array(
        'dashboard' => 'dashboard.php',
        'login' => 'login.php',
        'logout' => 'logout.php',
        'news' => 'news.php',
        'documents' => 'documents.php',
        'members' => 'members.php',
        'settings' => 'settings.php',
        'password' => 'password.php',
    );
    if (!isset($adminPages[$section])) {
        http_response_code(404);
        render_header('Страница не найдена');
        echo '<div class="wrap page-head"><h1>Страница не найдена</h1></div>';
        render_footer();
        exit;
    }
    require ROOT_PATH . '/pages/admin/' . $adminPages[$section];
    exit;
}

$public = array(
    '' => 'home.php',
    'documents' => 'documents.php',
    'members' => 'members.php',
    'news' => 'news.php',
    'download' => 'download.php',
);

if (!isset($public[$head])) {
    http_response_code(404);
    render_header('Страница не найдена');
    echo '<div class="wrap page-head"><h1>Страница не найдена</h1><p class="lede"><a href="' . e(url('')) . '">На главную</a></p></div>';
    render_footer();
    exit;
}

require ROOT_PATH . '/pages/' . $public[$head];
