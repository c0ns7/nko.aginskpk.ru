<?php
$docs = db()->query('SELECT * FROM documents ORDER BY created_at DESC, id DESC')->fetchAll();
render_header('Документы РОО «Развитие АПК»: устав, ОГРН, ИНН, реквизиты', array(
    'description' => 'Устав, выписка ЕГРЮЛ, свидетельства ОГРН и ИНН, реквизиты РОО «Развитие АПК». Скачать официальные документы организации в Агинском.',
    'canonical' => 'documents',
    'breadcrumbs' => array(
        array('name' => 'Главная', 'url' => canonical('')),
        array('name' => 'Документы', 'url' => canonical('documents')),
    ),
    'schema' => array(
        '@context' => 'https://schema.org',
        '@type' => 'CollectionPage',
        'name' => 'Документы организации',
        'url' => canonical('documents'),
    ),
));
?>
<div class="wrap page-head">
    <h1>Документы организации</h1>
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
