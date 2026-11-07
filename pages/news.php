<?php
$id = isset($params[0]) ? (int) $params[0] : 0;
$stmt = db()->prepare('SELECT * FROM news WHERE id = ? AND published = 1');
$stmt->execute(array($id));
$item = $stmt->fetch();
if (!$item) {
    http_response_code(404);
    render_header('Новость не найдена', array('noindex' => true));
    echo '<div class="wrap page-head"><h1>Новость не найдена</h1><p class="lede"><a href="' . e(url('')) . '">К списку новостей</a></p></div>';
    render_footer();
    return;
}

$desc = excerpt_text($item['body'] ? $item['body'] : ($item['grant_name'] . ' ' . $item['participation']), 170);
$published = $item['grant_date'] ? $item['grant_date'] : substr($item['created_at'], 0, 10);
$modified = $item['updated_at'] ? $item['updated_at'] : $item['created_at'];
$articleUrl = canonical('news/' . (int) $item['id']);
$articleSchema = array(
    '@context' => 'https://schema.org',
    '@type' => 'NewsArticle',
    'headline' => $item['title'],
    'description' => $desc,
    'datePublished' => $published,
    'dateModified' => date('c', strtotime($modified)),
    'inLanguage' => 'ru-RU',
    'mainEntityOfPage' => $articleUrl,
    'author' => array('@id' => canonical('') . '#organization'),
    'publisher' => array('@id' => canonical('') . '#organization'),
    'articleSection' => grant_status_label($item['grant_status']),
    'about' => $item['grant_name'] ? $item['grant_name'] : $item['title'],
);
if ($item['location']) {
    $articleSchema['contentLocation'] = array('@type' => 'Place', 'name' => $item['location']);
}

render_header($item['title'] . ' — грант, ' . grant_status_label($item['grant_status']), array(
    'description' => $desc,
    'canonical' => 'news/' . (int) $item['id'],
    'og_type' => 'article',
    'published_time' => $published,
    'modified_time' => $modified,
    'breadcrumbs' => array(
        array('name' => 'Главная', 'url' => canonical('')),
        array('name' => 'Новости и гранты', 'url' => canonical('')),
        array('name' => $item['title'], 'url' => $articleUrl),
    ),
    'schema' => $articleSchema,
));
?>
<article class="wrap article" itemscope itemtype="https://schema.org/NewsArticle">
    <div class="stamp stamp-<?php echo e($item['grant_status']); ?>"><?php echo e(grant_status_label($item['grant_status'])); ?></div>
    <h1 itemprop="headline"><?php echo e($item['title']); ?></h1>
    <dl class="facts">
        <?php if ($item['grant_name']): ?><div><dt>Грант / конкурс</dt><dd itemprop="about"><?php echo e($item['grant_name']); ?></dd></div><?php endif; ?>
        <?php if ($item['grant_date']): ?><div><dt>Дата</dt><dd><time itemprop="datePublished" datetime="<?php echo e($item['grant_date']); ?>"><?php echo e(format_date_ru($item['grant_date'])); ?></time></dd></div><?php endif; ?>
        <?php if ($item['location']): ?><div><dt>Где участвовали</dt><dd><?php echo e($item['location']); ?></dd></div><?php endif; ?>
        <?php if ($item['participation']): ?><div><dt>Как участвовали</dt><dd><?php echo e($item['participation']); ?></dd></div><?php endif; ?>
    </dl>
    <div class="article-body" itemprop="articleBody"><?php echo nl2p($item['body']); ?></div>
    <?php
    $files = news_files($item['id']);
    if ($files):
    ?>
    <h2>Документы гранта</h2>
    <div class="doc-list">
        <?php foreach ($files as $file): ?>
            <article class="doc-row">
                <div>
                    <h2><?php echo e($file['title']); ?></h2>
                    <p class="doc-meta"><?php echo e($file['original_name']); ?> · <?php echo e(format_bytes($file['size_bytes'])); ?></p>
                </div>
                <a class="btn" href="<?php echo e(url('grant-file/' . (int) $file['id'])); ?>">Скачать</a>
            </article>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <p><a href="<?php echo e(url('')); ?>">Ко всем новостям</a></p>
</article>
<?php
render_footer();
