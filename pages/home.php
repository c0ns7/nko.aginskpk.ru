<?php
$status = isset($_GET['status']) ? $_GET['status'] : '';
$allowed = grant_statuses();
$sql = 'SELECT * FROM news WHERE published = 1';
$params = array();
if ($status !== '' && isset($allowed[$status])) {
    $sql .= ' AND grant_status = ?';
    $params[] = $status;
}
$sql .= ' ORDER BY grant_date DESC, id DESC';
$stmt = db()->prepare($sql);
$stmt->execute($params);
$items = $stmt->fetchAll();

$homeTitle = setting_filled('seo_title', 'Гранты и новости РОО «Развитие АПК» — Агинское, Забайкальский край');
$homeDesc = setting_filled('seo_description', 'Официальный сайт РОО «Развитие АПК»: гранты (одобренные, отклонённые и поданные заявки), документы организации и состав. Агинское, Забайкальский край.');
$list = array();
foreach ($items as $n => $row) {
    $list[] = array(
        '@type' => 'ListItem',
        'position' => $n + 1,
        'url' => canonical('news/' . (int) $row['id']),
        'name' => $row['title'],
    );
}
render_header($homeTitle, array(
    'title_full' => true,
    'description' => $homeDesc,
    'canonical' => '',
    'schema' => array(
        '@context' => 'https://schema.org',
        '@type' => 'CollectionPage',
        'name' => 'Новости и гранты',
        'url' => canonical(''),
        'about' => array('@id' => canonical('') . '#organization'),
        'mainEntity' => array(
            '@type' => 'ItemList',
            'itemListElement' => $list,
        ),
    ),
));
?>
<section class="intro">
    <div class="wrap intro-grid">
        <div>
            <h1>Гранты и жизнь <?php echo e(setting('org_short')); ?></h1>
            <p><?php echo e(setting('org_about')); ?></p>
            <div class="intro-actions">
                <a class="btn" href="#grants">Смотреть гранты</a>
                <a class="btn btn-ghost" href="<?php echo e(url('documents')); ?>">Документы</a>
            </div>
        </div>
        <aside class="passport">
            <p class="passport-role"><?php echo e(setting('org_head')); ?></p>
            <dl>
                <div><dt>ИНН</dt><dd><?php echo e(setting('org_inn')); ?></dd></div>
                <div><dt>ОГРН</dt><dd><?php echo e(setting('org_ogrn')); ?></dd></div>
                <div><dt>Адрес</dt><dd><?php echo e(setting('org_address')); ?></dd></div>
            </dl>
        </aside>
    </div>
</section>
<div class="wrap" id="grants">
    <nav class="filters" aria-label="Фильтр по статусу гранта">
        <a class="chip<?php echo $status === '' ? ' is-on' : ''; ?>" href="<?php echo e(url('')); ?>">Все</a>
        <?php foreach ($allowed as $key => $label): ?>
            <a class="chip<?php echo $status === $key ? ' is-on' : ''; ?>" href="<?php echo e(url('?status=' . urlencode($key))); ?>"><?php echo e($label); ?></a>
        <?php endforeach; ?>
    </nav>
    <?php if (!$items): ?>
        <p class="empty">Пока нет опубликованных новостей по выбранному статусу.</p>
    <?php else: ?>
        <div class="news-list">
            <?php foreach ($items as $item): ?>
                <a class="news-item" href="<?php echo e(url('news/' . (int) $item['id'])); ?>">
                    <time class="news-date" datetime="<?php echo e($item['grant_date']); ?>"><?php echo e(format_date_ru($item['grant_date'])); ?></time>
                    <div>
                        <span class="pill pill-<?php echo e($item['grant_status']); ?>"><?php echo e(grant_status_label($item['grant_status'])); ?></span>
                        <h2 class="news-title"><?php echo e($item['title']); ?></h2>
                        <?php if ($item['grant_name']): ?><p class="news-grant"><?php echo e($item['grant_name']); ?></p><?php endif; ?>
                        <?php if ($item['location']): ?><p class="news-excerpt"><?php echo e($item['location']); ?></p><?php endif; ?>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<?php
render_footer();
