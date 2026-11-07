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
<section class="hero">
    <div class="wrap">
        <h1>Новости и гранты <?php echo e(setting('org_short')); ?></h1>
        <p><?php echo e(setting('org_about')); ?></p>
        <div class="meta-row">
            <span><?php echo e(setting('org_head')); ?></span>
            <span>ИНН <?php echo e(setting('org_inn')); ?></span>
            <span>ОГРН <?php echo e(setting('org_ogrn')); ?></span>
        </div>
    </div>
</section>
<div class="wrap">
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
                        <div class="stamp stamp-<?php echo e($item['grant_status']); ?>"><?php echo e(grant_status_label($item['grant_status'])); ?></div>
                        <h2 class="news-title"><?php echo e($item['title']); ?></h2>
                        <?php if ($item['grant_name']): ?><p class="news-grant"><?php echo e($item['grant_name']); ?></p><?php endif; ?>
                        <p class="news-excerpt">
                            <?php
                            $bits = array();
                            if ($item['location']) {
                                $bits[] = $item['location'];
                            }
                            if ($item['participation']) {
                                $bits[] = $item['participation'];
                            }
                            echo e(implode(' — ', $bits));
                            ?>
                        </p>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<?php
render_footer();
