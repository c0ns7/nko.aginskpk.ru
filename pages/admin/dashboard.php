<?php
render_admin_header('Обзор');
$news = (int) db()->query('SELECT COUNT(*) FROM news')->fetchColumn();
$docs = (int) db()->query('SELECT COUNT(*) FROM documents')->fetchColumn();
$members = (int) db()->query('SELECT COUNT(*) FROM members')->fetchColumn();
$usingSqlite = db_driver() === 'sqlite';
?>
<h1>Обзор</h1>
<p class="lede">Здесь можно править новости о грантах, документы и состав организации.</p>
<?php if ($usingSqlite): ?>
    <p class="error">Сейчас сайт работает на локальной SQLite: MySQL-сервер <?php echo e(DB_HOST); ?> недоступен с этой машины. На хостинге Beget подключение пойдёт к базе <strong><?php echo e(DB_NAME); ?></strong>.</p>
<?php endif; ?>
<div class="stats">
    <div class="stat"><b><?php echo $news; ?></b> новостей</div>
    <div class="stat"><b><?php echo $docs; ?></b> документов</div>
    <div class="stat"><b><?php echo $members; ?></b> в составе</div>
</div>
<p>
    <a class="btn" href="<?php echo e(url('admin/news/new')); ?>">Добавить новость</a>
    <a class="btn btn-ghost" href="<?php echo e(url('admin/documents')); ?>">Загрузить документ</a>
</p>
<?php
render_admin_footer();
