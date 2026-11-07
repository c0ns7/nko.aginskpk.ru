<?php

function render_header($title, $options = array())
{
    $isAdmin = !empty($options['admin']);
    $noindex = $isAdmin || !empty($options['noindex']);
    $orgShort = setting('org_short', 'РОО «Развитие АПК»');
    $pageTitle = !empty($options['title_full']) ? $title : ($title . ' — ' . $orgShort);
    $description = isset($options['description']) && $options['description'] !== ''
        ? excerpt_text($options['description'], 170)
        : excerpt_text(setting_filled('seo_description', setting_filled('org_about', setting('org_full'))), 170);
    $canonicalPath = array_key_exists('canonical', $options) ? $options['canonical'] : current_path();
    $canonicalUrl = canonical($canonicalPath);
    $ogType = isset($options['og_type']) ? $options['og_type'] : 'website';
    $ogImage = isset($options['og_image']) ? $options['og_image'] : canonical('public/og.svg');
    $robots = $noindex ? 'noindex, nofollow' : 'index, follow';
    $crumbs = isset($options['breadcrumbs']) ? $options['breadcrumbs'] : array();
    $extraSchema = isset($options['schema']) ? $options['schema'] : null;

    if (!headers_sent()) {
        header('Content-Type: text/html; charset=utf-8');
        header('X-Robots-Tag: ' . $robots);
    }
    ?><!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title><?php echo e($pageTitle); ?></title>
    <meta name="description" content="<?php echo e($description); ?>">
    <meta name="robots" content="<?php echo e($robots); ?>">
    <meta name="googlebot" content="<?php echo e($robots); ?>">
    <meta name="yandex" content="<?php echo e($noindex ? 'noindex, nofollow' : 'index, follow'); ?>">
    <link rel="canonical" href="<?php echo e($canonicalUrl); ?>">
    <link rel="alternate" hreflang="ru" href="<?php echo e($canonicalUrl); ?>">
    <link rel="alternate" hreflang="x-default" href="<?php echo e($canonicalUrl); ?>">
    <link rel="alternate" type="application/rss+xml" title="<?php echo e($orgShort); ?> — новости и гранты" href="<?php echo e(canonical('rss.xml')); ?>">
    <meta property="og:locale" content="ru_RU">
    <meta property="og:type" content="<?php echo e($ogType); ?>">
    <meta property="og:site_name" content="<?php echo e($orgShort); ?>">
    <meta property="og:title" content="<?php echo e($pageTitle); ?>">
    <meta property="og:description" content="<?php echo e($description); ?>">
    <meta property="og:url" content="<?php echo e($canonicalUrl); ?>">
    <meta property="og:image" content="<?php echo e($ogImage); ?>">
    <meta property="og:image:alt" content="<?php echo e($orgShort); ?>">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo e($pageTitle); ?>">
    <meta name="twitter:description" content="<?php echo e($description); ?>">
    <meta name="twitter:image" content="<?php echo e($ogImage); ?>">
    <?php if (!empty($options['published_time'])): ?>
    <meta property="article:published_time" content="<?php echo e($options['published_time']); ?>">
    <?php endif; ?>
    <?php if (!empty($options['modified_time'])): ?>
    <meta property="article:modified_time" content="<?php echo e($options['modified_time']); ?>">
    <meta http-equiv="last-modified" content="<?php echo e(gmdate('D, d M Y H:i:s', strtotime($options['modified_time']))); ?> GMT">
    <?php endif; ?>
    <meta name="author" content="<?php echo e($orgShort); ?>">
    <meta name="geo.region" content="RU-ZAB">
    <meta name="geo.placename" content="Агинское">
    <meta name="theme-color" content="#0f766e">
    <?php if (setting('yandex_verification')): ?>
    <meta name="yandex-verification" content="<?php echo e(setting('yandex_verification')); ?>">
    <?php endif; ?>
    <?php if (setting('google_verification')): ?>
    <meta name="google-site-verification" content="<?php echo e(setting('google_verification')); ?>">
    <?php endif; ?>
    <link rel="icon" href="<?php echo e(asset('public/favicon.svg')); ?>?v=2" type="image/svg+xml">
    <link rel="apple-touch-icon" href="<?php echo e(asset('public/favicon.svg')); ?>?v=2">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Literata:opsz,wght@7..72,500;7..72,650&family=Onest:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo e(asset('public/css/app.css')); ?>?v=5">
    <?php if (!$noindex): ?>
    <script type="application/ld+json"><?php echo json_ld(org_schema()); ?></script>
    <script type="application/ld+json"><?php echo json_ld(array(
        '@context' => 'https://schema.org',
        '@type' => 'WebSite',
        '@id' => canonical('') . '#website',
        'url' => canonical(''),
        'name' => $orgShort,
        'inLanguage' => 'ru-RU',
        'publisher' => array('@id' => canonical('') . '#organization'),
    )); ?></script>
    <?php if ($crumbs): ?>
    <script type="application/ld+json"><?php
        $crumbList = array();
        foreach ($crumbs as $i => $crumb) {
            $crumbList[] = array(
                '@type' => 'ListItem',
                'position' => $i + 1,
                'name' => $crumb['name'],
                'item' => isset($crumb['url']) ? $crumb['url'] : $canonicalUrl,
            );
        }
        echo json_ld(array(
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $crumbList,
        ));
    ?></script>
    <?php endif; ?>
    <?php if ($extraSchema): ?>
    <script type="application/ld+json"><?php echo json_ld($extraSchema); ?></script>
    <?php endif; ?>
    <?php endif; ?>
</head>
<body class="<?php echo $isAdmin ? 'is-admin' : ''; ?>">
<a class="skip-link" href="#main">Перейти к содержанию</a>
<header class="site-header">
    <div class="wrap header-inner">
        <a class="brand" href="<?php echo e(url('')); ?>">
            <span class="brand-mark" aria-hidden="true">
                <svg viewBox="0 0 48 48" width="44" height="44" focusable="false">
                    <rect x="3" y="3" width="42" height="42" rx="4" fill="none" stroke="currentColor" stroke-width="1.5"/>
                    <path d="M24 16c-3.2 1.6-7.2 2-10 2v15c2.8 0 6.8-.4 10-2 3.2 1.6 7.2 2 10 2V18c-2.8 0-6.8-.4-10-2z" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/>
                    <path d="M24 16v15" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                </svg>
            </span>
            <span class="brand-text">
                <strong><?php echo e($orgShort); ?></strong>
                <span>Агинский педагогический колледж им. Базара Ринчино</span>
            </span>
        </a>
        <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="site-nav" data-nav-toggle>
            <span class="nav-toggle-bars" aria-hidden="true"></span>
            <span class="visually-hidden">Меню</span>
        </button>
        <nav class="site-nav" id="site-nav" data-nav aria-label="Основное меню">
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
    if ($crumbs && !$isAdmin) {
        echo '<nav class="crumbs wrap" aria-label="Навигационная цепочка"><ol>';
        $last = count($crumbs) - 1;
        foreach ($crumbs as $i => $crumb) {
            echo '<li>';
            if ($i !== $last && !empty($crumb['url'])) {
                echo '<a href="' . e($crumb['url']) . '">' . e($crumb['name']) . '</a>';
            } else {
                echo '<span>' . e($crumb['name']) . '</span>';
            }
            echo '</li>';
        }
        echo '</ol></nav>';
    }
}

function render_footer($options = array())
{
    $flash = flash_get();
    $hideFooter = !empty($options['hide']);
    ?>
</main>
<?php if (!$hideFooter): ?>
<footer class="site-footer" role="contentinfo">
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
        </div>
    </div>
</footer>
<?php endif; ?>
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
    render_header($title, array('admin' => true, 'noindex' => true));
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
    render_footer(array('hide' => true));
}
