<?php

function render_header($title, $options = array())
{
    $isAdmin = !empty($options['admin']);
    if (!headers_sent()) {
        header('Content-Type: text/html; charset=utf-8');
    }
    $pageTitle = $title . ' — ' . setting('org_short', 'РОО «Развитие АПК»');
    $description = isset($options['description']) ? $options['description'] : setting('org_full');
    ?><!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title><?php echo e($pageTitle); ?></title>
    <meta name="description" content="<?php echo e($description); ?>">
    <link rel="icon" href="<?php echo e(asset('public/favicon.svg')); ?>" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Literata:opsz,wght@7..72,500;7..72,650&family=Source+Sans+3:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo e(asset('public/css/app.css')); ?>">
</head>
<body class="<?php echo $isAdmin ? 'is-admin' : ''; ?>">
<a class="skip-link" href="#main">Перейти к содержанию</a>
<header class="site-header">
    <div class="wrap header-inner">
        <a class="brand" href="<?php echo e(url('')); ?>">
            <span class="brand-mark" aria-hidden="true">
                <svg viewBox="0 0 48 48" width="44" height="44" focusable="false">
                    <circle cx="24" cy="24" r="22" fill="none" stroke="currentColor" stroke-width="1.5"/>
                    <circle cx="24" cy="24" r="16.5" fill="none" stroke="currentColor" stroke-width="0.75"/>
                    <path d="M24 12v24M16 20h16M17.5 28h13" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
                </svg>
            </span>
            <span class="brand-text">
                <strong><?php echo e(setting('org_short', 'РОО «Развитие АПК»')); ?></strong>
                <span>Агинский педагогический колледж им. Базара Ринчино</span>
            </span>
        </a>
        <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="site-nav" data-nav-toggle>
            <span class="nav-toggle-bars" aria-hidden="true"></span>
            <span class="visually-hidden">Меню</span>
        </button>
        <nav class="site-nav" id="site-nav" data-nav>
            <a href="<?php echo e(url('')); ?>"<?php echo nav_active(''); ?>>Новости</a>
            <a href="<?php echo e(url('documents')); ?>"<?php echo nav_active('documents'); ?>>Документы</a>
            <a href="<?php echo e(url('members')); ?>"<?php echo nav_active('members'); ?>>Состав</a>
            <?php if (current_user()): ?>
                <a href="<?php echo e(url('admin')); ?>"<?php echo nav_active('admin'); ?>>Админка</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
<main id="main" class="site-main">
<?php
}

function render_footer()
{
    $flash = flash_get();
    ?>
</main>
<footer class="site-footer">
    <div class="wrap footer-grid">
        <div>
            <p class="footer-name"><?php echo e(setting('org_full')); ?></p>
            <p><?php echo e(setting('org_address')); ?></p>
        </div>
        <div>
            <p>ИНН <?php echo e(setting('org_inn')); ?><br>ОГРН <?php echo e(setting('org_ogrn')); ?><br>КПП <?php echo e(setting('org_kpp')); ?></p>
        </div>
        <div>
            <p>
                <?php if (setting('org_phone')): ?><a href="tel:<?php echo e(preg_replace('/[^\d+]/', '', setting('org_phone'))); ?>"><?php echo e(setting('org_phone')); ?></a><br><?php endif; ?>
                <?php if (setting('org_email')): ?><a href="mailto:<?php echo e(setting('org_email')); ?>"><?php echo e(setting('org_email')); ?></a><?php endif; ?>
            </p>
            <p class="footer-note">Сайт работает по HTTP и HTTPS. <a href="<?php echo e(url('admin/login')); ?>">Вход для редактора</a></p>
        </div>
    </div>
</footer>
<?php if ($flash): ?>
<div class="toast toast-<?php echo e($flash['type']); ?>" role="status"><?php echo e($flash['message']); ?></div>
<?php endif; ?>
<script src="<?php echo e(asset('public/js/app.js')); ?>"></script>
</body>
</html>
<?php
}

function render_admin_header($title)
{
    require_admin();
    render_header($title, array('admin' => true));
    ?>
    <div class="wrap admin-shell">
        <aside class="admin-side">
            <p class="admin-side-label">Редактирование</p>
            <nav class="admin-nav">
                <a href="<?php echo e(url('admin')); ?>"<?php echo nav_active('admin') && current_path() === 'admin' ? ' aria-current="page"' : ''; ?>>Обзор</a>
                <a href="<?php echo e(url('admin/news')); ?>"<?php echo current_path() === 'admin/news' || strpos(current_path(), 'admin/news/') === 0 ? ' aria-current="page"' : ''; ?>>Новости и гранты</a>
                <a href="<?php echo e(url('admin/documents')); ?>"<?php echo current_path() === 'admin/documents' ? ' aria-current="page"' : ''; ?>>Документы</a>
                <a href="<?php echo e(url('admin/members')); ?>"<?php echo current_path() === 'admin/members' || strpos(current_path(), 'admin/members/') === 0 ? ' aria-current="page"' : ''; ?>>Состав</a>
                <a href="<?php echo e(url('admin/settings')); ?>"<?php echo current_path() === 'admin/settings' ? ' aria-current="page"' : ''; ?>>Реквизиты сайта</a>
                <a href="<?php echo e(url('admin/password')); ?>"<?php echo current_path() === 'admin/password' ? ' aria-current="page"' : ''; ?>>Пароль</a>
                <a href="<?php echo e(url('admin/logout')); ?>">Выйти</a>
            </nav>
        </aside>
        <div class="admin-content">
    <?php
}

function render_admin_footer()
{
    echo '</div></div>';
    render_footer();
}
