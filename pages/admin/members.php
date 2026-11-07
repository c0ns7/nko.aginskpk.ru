<?php
$action = isset($params[0]) ? $params[0] : '';

if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
    $stmt = db()->prepare('DELETE FROM members WHERE id = ?');
    $stmt->execute(array($id));
    flash_set('ok', 'Запись удалена.');
    redirect('admin/members');
}

if ($action === 'new' || $action === 'edit') {
    $id = $action === 'edit' && isset($params[1]) ? (int) $params[1] : 0;
    $item = array(
        'full_name' => '', 'role_title' => '', 'member_type' => 'member',
        'bio' => '', 'grant_involvement' => '', 'sort_order' => 0, 'published' => 1,
    );
    if ($id) {
        $stmt = db()->prepare('SELECT * FROM members WHERE id = ?');
        $stmt->execute(array($id));
        $found = $stmt->fetch();
        if (!$found) {
            flash_set('error', 'Запись не найдена.');
            redirect('admin/members');
        }
        $item = $found;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        csrf_check();
        $data = array(
            'full_name' => trim(isset($_POST['full_name']) ? $_POST['full_name'] : ''),
            'role_title' => trim(isset($_POST['role_title']) ? $_POST['role_title'] : ''),
            'member_type' => isset($_POST['member_type']) ? $_POST['member_type'] : 'member',
            'bio' => trim(isset($_POST['bio']) ? $_POST['bio'] : ''),
            'grant_involvement' => trim(isset($_POST['grant_involvement']) ? $_POST['grant_involvement'] : ''),
            'sort_order' => isset($_POST['sort_order']) ? (int) $_POST['sort_order'] : 0,
            'published' => empty($_POST['published']) ? 0 : 1,
        );
        if (!isset(member_types()[$data['member_type']])) {
            $data['member_type'] = 'member';
        }
        if ($data['full_name'] === '') {
            flash_set('error', 'Укажите ФИО или название.');
            redirect($id ? 'admin/members/edit/' . $id : 'admin/members/new');
        }
        if ($id) {
            $stmt = db()->prepare('UPDATE members SET full_name=?, role_title=?, member_type=?, bio=?, grant_involvement=?, sort_order=?, published=? WHERE id=?');
            $stmt->execute(array(
                $data['full_name'], $data['role_title'], $data['member_type'], $data['bio'],
                $data['grant_involvement'], $data['sort_order'], $data['published'], $id,
            ));
            flash_set('ok', 'Состав сохранён.');
        } else {
            $stmt = db()->prepare('INSERT INTO members (full_name, role_title, member_type, bio, grant_involvement, sort_order, published) VALUES (?,?,?,?,?,?,?)');
            $stmt->execute(array(
                $data['full_name'], $data['role_title'], $data['member_type'], $data['bio'],
                $data['grant_involvement'], $data['sort_order'], $data['published'],
            ));
            flash_set('ok', 'Человек добавлен.');
        }
        redirect('admin/members');
    }

    render_admin_header($id ? 'Редактирование состава' : 'Новая запись состава');
    ?>
    <h1><?php echo $id ? 'Редактирование' : 'Новая запись состава'; ?></h1>
    <form class="form" method="post">
        <?php echo csrf_field(); ?>
        <label><span>ФИО или название</span><input type="text" name="full_name" required value="<?php echo e($item['full_name']); ?>"></label>
        <label><span>Должность / роль</span><input type="text" name="role_title" value="<?php echo e($item['role_title']); ?>"></label>
        <label>
            <span>Категория</span>
            <select name="member_type">
                <?php foreach (member_types() as $k => $label): ?>
                    <option value="<?php echo e($k); ?>"<?php echo $item['member_type'] === $k ? ' selected' : ''; ?>><?php echo e($label); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label><span>О человеке / группе</span><textarea name="bio"><?php echo e($item['bio']); ?></textarea></label>
        <label><span>Участие в грантах</span><textarea name="grant_involvement"><?php echo e($item['grant_involvement']); ?></textarea></label>
        <label><span>Порядок</span><input type="number" name="sort_order" value="<?php echo e($item['sort_order']); ?>"></label>
        <label><input type="checkbox" name="published" value="1"<?php echo !empty($item['published']) ? ' checked' : ''; ?>> Показывать на сайте</label>
        <div class="row-actions">
            <button class="btn" type="submit">Сохранить</button>
            <a class="btn btn-ghost" href="<?php echo e(url('admin/members')); ?>">Отмена</a>
        </div>
    </form>
    <?php
    render_admin_footer();
    return;
}

$people = db()->query('SELECT * FROM members ORDER BY sort_order ASC, id ASC')->fetchAll();
render_admin_header('Состав');
?>
<h1>Состав</h1>
<p><a class="btn" href="<?php echo e(url('admin/members/new')); ?>">Добавить</a></p>
<div class="table-wrap">
<table>
    <thead><tr><th>Имя</th><th>Категория</th><th>Роль</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($people as $person): ?>
        <tr>
            <td><?php echo e($person['full_name']); ?></td>
            <td><?php echo e(member_type_label($person['member_type'])); ?></td>
            <td><?php echo e($person['role_title']); ?></td>
            <td class="row-actions">
                <a class="btn btn-ghost" href="<?php echo e(url('admin/members/edit/' . (int) $person['id'])); ?>">Изменить</a>
                <form method="post" action="<?php echo e(url('admin/members/delete')); ?>" data-confirm="Удалить из состава?">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="id" value="<?php echo (int) $person['id']; ?>">
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
