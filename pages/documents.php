<?php
$docs = db()->query('SELECT * FROM documents ORDER BY created_at DESC, id DESC')->fetchAll();
render_header('Документы', array('description' => 'Устав, свидетельства, реквизиты и другие документы организации.'));
?>
<div class="wrap page-head">
    <h1>Документы</h1>
    <p class="lede">Устав, свидетельства о регистрации, реквизиты и иные файлы организации. Документы можно скачать. Новые файлы добавляются через админку.</p>
</div>
<div class="wrap">
    <?php if (!$docs): ?>
        <p class="empty">Документы ещё не загружены.</p>
    <?php else: ?>
        <div class="doc-list">
            <?php foreach ($docs as $doc): ?>
                <article class="doc-row">
                    <div>
                        <h2><?php echo e($doc['title']); ?></h2>
                        <p class="doc-meta">
                            <?php echo e($doc['original_name']); ?>
                            · <?php echo e(format_bytes($doc['size_bytes'])); ?>
                            <?php if ($doc['description']): ?><br><?php echo e($doc['description']); ?><?php endif; ?>
                        </p>
                    </div>
                    <a class="btn" href="<?php echo e(url('download/' . (int) $doc['id'])); ?>">Скачать</a>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<?php
render_footer();
