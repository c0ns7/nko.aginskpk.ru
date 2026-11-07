<?php
$id = isset($params[0]) ? (int) $params[0] : 0;
$stmt = db()->prepare('SELECT * FROM news WHERE id = ? AND published = 1');
$stmt->execute(array($id));
$item = $stmt->fetch();
if (!$item) {
    http_response_code(404);
    render_header('Новость не найдена');
    echo '<div class="wrap page-head"><h1>Новость не найдена</h1><p class="lede"><a href="' . e(url('')) . '">К списку новостей</a></p></div>';
    render_footer();
    return;
}

render_header($item['title']);
?>
<article class="wrap article">
    <div class="stamp stamp-<?php echo e($item['grant_status']); ?>"><?php echo e(grant_status_label($item['grant_status'])); ?></div>
    <h1><?php echo e($item['title']); ?></h1>
    <dl class="facts">
        <?php if ($item['grant_name']): ?><div><dt>Грант / конкурс</dt><dd><?php echo e($item['grant_name']); ?></dd></div><?php endif; ?>
        <?php if ($item['grant_date']): ?><div><dt>Дата</dt><dd><?php echo e(format_date_ru($item['grant_date'])); ?></dd></div><?php endif; ?>
        <?php if ($item['location']): ?><div><dt>Где участвовали</dt><dd><?php echo e($item['location']); ?></dd></div><?php endif; ?>
        <?php if ($item['participation']): ?><div><dt>Как участвовали</dt><dd><?php echo e($item['participation']); ?></dd></div><?php endif; ?>
    </dl>
    <div class="article-body"><?php echo nl2p($item['body']); ?></div>
    <p><a href="<?php echo e(url('')); ?>">Ко всем новостям</a></p>
</article>
<?php
render_footer();
