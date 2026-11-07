<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $op = isset($_POST['op']) ? $_POST['op'] : '';

    if ($op === 'delete') {
        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $stmt = db()->prepare('SELECT * FROM documents WHERE id = ?');
        $stmt->execute(array($id));
        $doc = $stmt->fetch();
        if ($doc) {
            $path = UPLOAD_DIR . DIRECTORY_SEPARATOR . $doc['stored_name'];
            if (is_file($path)) {
                @unlink($path);
            }
            $del = db()->prepare('DELETE FROM documents WHERE id = ?');
            $del->execute(array($id));
            flash_set('ok', 'Документ удалён.');
        }
        redirect('admin/documents');
    }

    if ($op === 'upload') {
        $title = trim(isset($_POST['title']) ? $_POST['title'] : '');
        $description = trim(isset($_POST['description']) ? $_POST['description'] : '');
        if (empty($_FILES['file']) || $_FILES['file']['error'] === UPLOAD_ERR_NO_FILE) {
            flash_set('error', 'Выберите файл.');
            redirect('admin/documents');
        }
        $file = $_FILES['file'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            flash_set('error', 'Не удалось загрузить файл.');
            redirect('admin/documents');
        }
        if ($file['size'] > MAX_UPLOAD_BYTES) {
            flash_set('error', 'Файл больше 20 МБ.');
            redirect('admin/documents');
        }
        $ext = file_ext($file['name']);
        if (!in_array($ext, allowed_extensions(), true)) {
            flash_set('error', 'Этот тип файла не разрешён.');
            redirect('admin/documents');
        }
        $stored = safe_stored_name($file['name']);
        $dest = UPLOAD_DIR . DIRECTORY_SEPARATOR . $stored;
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            flash_set('error', 'Не удалось сохранить файл на сервере.');
            redirect('admin/documents');
        }
        if ($title === '') {
            $title = pathinfo($file['name'], PATHINFO_FILENAME);
        }
        $mime = '';
        if (function_exists('mime_content_type')) {
            $detected = @mime_content_type($dest);
            if ($detected) {
                $mime = $detected;
            }
        }
        $stmt = db()->prepare('INSERT INTO documents (title, description, stored_name, original_name, mime, size_bytes, created_at) VALUES (?,?,?,?,?,?,?)');
        $stmt->execute(array($title, $description, $stored, $file['name'], $mime, filesize($dest), date('Y-m-d H:i:s')));
        flash_set('ok', 'Документ добавлен.');
        redirect('admin/documents');
    }
}

$docs = db()->query('SELECT * FROM documents ORDER BY created_at DESC, id DESC')->fetchAll();
render_admin_header('Документы');
?>
<h1>Документы</h1>
<p class="lede">Файлы из папки docs уже подключены. Можно добавить новые или удалить существующие.</p>
<form class="form" method="post" enctype="multipart/form-data">
    <?php echo csrf_field(); ?>
    <input type="hidden" name="op" value="upload">
    <label><span>Название</span><input type="text" name="title" placeholder="Например, Устав"></label>
    <label><span>Краткое описание</span><input type="text" name="description"></label>
    <label><span>Файл</span><input type="file" name="file" required></label>
    <button class="btn" type="submit">Добавить документ</button>
</form>
<div class="table-wrap" style="margin-top:1.5rem">
<table>
    <thead><tr><th>Название</th><th>Файл</th><th>Размер</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($docs as $doc): ?>
        <tr>
            <td><?php echo e($doc['title']); ?><?php if ($doc['description']): ?><br><span style="color:var(--mute)"><?php echo e($doc['description']); ?></span><?php endif; ?></td>
            <td><?php echo e($doc['original_name']); ?></td>
            <td><?php echo e(format_bytes($doc['size_bytes'])); ?></td>
            <td class="row-actions">
                <a class="btn btn-ghost" href="<?php echo e(url('download/' . (int) $doc['id'])); ?>">Скачать</a>
                <form method="post" data-confirm="Удалить документ? Файл будет стёрт с сайта.">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="op" value="delete">
                    <input type="hidden" name="id" value="<?php echo (int) $doc['id']; ?>">
                    <button class="btn btn-danger" type="submit">Удалить</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php
render_admin_footer();
