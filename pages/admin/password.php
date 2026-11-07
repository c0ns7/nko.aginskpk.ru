<?php
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $current = isset($_POST['current']) ? $_POST['current'] : '';
    $next = isset($_POST['next']) ? $_POST['next'] : '';
    $again = isset($_POST['again']) ? $_POST['again'] : '';
    $user = current_user();
    $stmt = db()->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute(array($user['id']));
    $row = $stmt->fetch();
    if (!$row || !password_verify($current, $row['password_hash'])) {
        $error = 'Текущий пароль указан неверно.';
    } elseif (strlen($next) < 8) {
        $error = 'Новый пароль — не короче 8 символов.';
    } elseif ($next !== $again) {
        $error = 'Новый пароль и подтверждение не совпадают.';
    } else {
        $upd = db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
        $upd->execute(array(password_hash($next, PASSWORD_DEFAULT), $user['id']));
        flash_set('ok', 'Пароль изменён.');
        redirect('admin');
    }
}

render_admin_header('Пароль');
?>
<h1>Смена пароля</h1>
<?php if ($error): ?><p class="error"><?php echo e($error); ?></p><?php endif; ?>
<form class="form" method="post">
    <?php echo csrf_field(); ?>
    <label><span>Текущий пароль</span><input type="password" name="current" required autocomplete="current-password"></label>
    <label><span>Новый пароль</span><input type="password" name="next" required autocomplete="new-password"></label>
    <label><span>Ещё раз</span><input type="password" name="again" required autocomplete="new-password"></label>
    <button class="btn" type="submit">Сменить пароль</button>
</form>
<?php
render_admin_footer();
