<?php
$action = isset($params[0]) ? $params[0] : '';

if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
    delete_news_files_for($id);
    $stmt = db()->prepare('DELETE FROM news WHERE id = ?');
    $stmt->execute(array($id));
    flash_set('ok', 'Новость удалена.');
    redirect('admin/news');
}

if ($action === 'file-delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $newsId = isset($_POST['news_id']) ? (int) $_POST['news_id'] : 0;
    $fileId = isset($_POST['file_id']) ? (int) $_POST['file_id'] : 0;
    $stmt = db()->prepare('SELECT * FROM news_files WHERE id = ? AND news_id = ?');
    $stmt->execute(array($fileId, $newsId));
    $file = $stmt->fetch();
    if ($file) {
        delete_news_file_row($file);
        flash_set('ok', 'Файл гранта удалён.');
    }
    redirect('admin/news/edit/' . $newsId);
}

if ($action === 'new' || $action === 'edit') {
    $id = $action === 'edit' && isset($params[1]) ? (int) $params[1] : 0;
    $item = array(
        'title' => '', 'body' => '', 'grant_name' => '', 'grant_status' => 'planned',
        'grant_date' => date('Y-m-d'), 'location' => '', 'participation' => '', 'published' => 1,
    );
    if ($id) {
        $stmt = db()->prepare('SELECT * FROM news WHERE id = ?');
        $stmt->execute(array($id));
        $found = $stmt->fetch();
        if (!$found) {
            flash_set('error', 'Запись не найдена.');
            redirect('admin/news');
        }
        $item = $found;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();
        $data = array(
            'title' => trim(isset($_POST['title']) ? $_POST['title'] : ''),
            'body' => trim(isset($_POST['body']) ? $_POST['body'] : ''),
            'grant_name' => trim(isset($_POST['grant_name']) ? $_POST['grant_name'] : ''),
            'grant_status' => isset($_POST['grant_status']) ? $_POST['grant_status'] : 'planned',
            'grant_date' => isset($_POST['grant_date']) && $_POST['grant_date'] !== '' ? $_POST['grant_date'] : null,
            'location' => trim(isset($_POST['location']) ? $_POST['location'] : ''),
            'participation' => trim(isset($_POST['participation']) ? $_POST['participation'] : ''),
            'published' => empty($_POST['published']) ? 0 : 1,
        );
        if (!isset(grant_statuses()[$data['grant_status']])) {
            $data['grant_status'] = 'planned';
        }
        if ($data['title'] === '') {
            flash_set('error', 'Укажите заголовок.');
            redirect($id ? 'admin/news/edit/' . $id : 'admin/news/new');
        }
        $now = date('Y-m-d H:i:s');
        if ($id) {
            $stmt = db()->prepare('UPDATE news SET title=?, body=?, grant_name=?, grant_status=?, grant_date=?, location=?, participation=?, published=?, updated_at=? WHERE id=?');
            $stmt->execute(array(
                $data['title'], $data['body'], $data['grant_name'], $data['grant_status'], $data['grant_date'],
                $data['location'], $data['participation'], $data['published'], $now, $id,
            ));
        } else {
            $stmt = db()->prepare('INSERT INTO news (title, body, grant_name, grant_status, grant_date, location, participation, published, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,?)');
            $stmt->execute(array(
                $data['title'], $data['body'], $data['grant_name'], $data['grant_status'], $data['grant_date'],
                $data['location'], $data['participation'], $data['published'], $now, $now,
            ));
            $id = (int) db()->lastInsertId();
        }
        $fileTitle = trim(isset($_POST['file_title']) ? $_POST['file_title'] : '');
        attach_uploads_to_news($id, $fileTitle);
        flash_set('ok', 'Новость сохранена.');
        redirect('admin/news/edit/' . $id);
    }

    $files = $id ? news_files($id) : array();
    render_admin_header($id ? 'Редактирование новости' : 'Новая новость');
    ?>
    <h1><?php echo $id ? 'Редактирование новости' : 'Новая новость'; ?></h1>
    <form class="form" method="post" enctype="multipart/form-data">
        <?php echo csrf_field(); ?>
        <label><span>Заголовок</span><input type="text" name="title" required value="<?php echo e($item['title']); ?>"></label>
        <label><span>Грант / конкурс</span><input type="text" name="grant_name" value="<?php echo e($item['grant_name']); ?>"></label>
        <label>
            <span>Статус</span>
            <select name="grant_status">
                <?php foreach (grant_statuses() as $k => $label): ?>
                    <option value="<?php echo e($k); ?>"<?php echo $item['grant_status'] === $k ? ' selected' : ''; ?>><?php echo e($label); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label><span>Дата</span><input type="date" name="grant_date" value="<?php echo e($item['grant_date']); ?>"></label>
        <label><span>Где участвовали</span><input type="text" name="location" value="<?php echo e($item['location']); ?>"></label>
        <label><span>Как участвовали</span><textarea name="participation"><?php echo e($item['participation']); ?></textarea></label>
        <label><span>Текст новости</span><textarea name="body"><?php echo e($item['body']); ?></textarea></label>
        <label><input type="checkbox" name="published" value="1"<?php echo !empty($item['published']) ? ' checked' : ''; ?>> Опубликовать</label>

        <h2>Добавить документы гранта</h2>
        <p class="lede">Смета, заявка, отчёт, презентация — можно выбрать несколько файлов.</p>
        <label><span>Название для новых файлов</span><input type="text" name="file_title" placeholder="Если пусто — возьмётся имя файла"></label>
        <label><span>Файлы</span><input type="file" name="files[]" multiple></label>

        <div class="row-actions">
            <button class="btn" type="submit">Сохранить</button>
            <a class="btn btn-ghost" href="<?php echo e(url('admin/news')); ?>">К списку</a>
        </div>
    </form>
    <?php if ($files): ?>
        <h2>Прикреплённые файлы</h2>
        <div class="table-wrap">
        <table>
            <thead><tr><th>Название</th><th>Файл</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($files as $file): ?>
                <tr>
                    <td><?php echo e($file['title']); ?></td>
                    <td><?php echo e($file['original_name']); ?> · <?php echo e(format_bytes($file['size_bytes'])); ?></td>
                    <td class="row-actions">
                        <a class="btn btn-ghost" href="<?php echo e(url('grant-file/' . (int) $file['id'])); ?>">Скачать</a>
                        <form method="post" action="<?php echo e(url('admin/news/file-delete')); ?>" data-confirm="Удалить этот файл?">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="news_id" value="<?php echo (int) $id; ?>">
                            <input type="hidden" name="file_id" value="<?php echo (int) $file['id']; ?>">
                            <button class="btn btn-danger" type="submit">Удалить</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    <?php endif; ?>
    <?php
    render_admin_footer();
    return;
}

$items = db()->query('SELECT * FROM news ORDER BY grant_date DESC, id DESC')->fetchAll();
render_admin_header('Новости и гранты');
?>
<h1>Новости и гранты</h1>
<p><a class="btn" href="<?php echo e(url('admin/news/new')); ?>">Добавить</a></p>
<div class="table-wrap">
<table>
    <thead><tr><th>Дата</th><th>Статус</th><th>Заголовок</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($items as $item): ?>
        <tr>
            <td><?php echo e(format_date_ru($item['grant_date'])); ?></td>
            <td><?php echo e(grant_status_label($item['grant_status'])); ?></td>
            <td><?php echo e($item['title']); ?><?php echo empty($item['published']) ? ' (скрыто)' : ''; ?></td>
            <td class="row-actions">
                <a class="btn btn-ghost" href="<?php echo e(url('admin/news/edit/' . (int) $item['id'])); ?>">Изменить</a>
                <form method="post" action="<?php echo e(url('admin/news/delete')); ?>" data-confirm="Удалить эту новость?">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="id" value="<?php echo (int) $item['id']; ?>">
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
