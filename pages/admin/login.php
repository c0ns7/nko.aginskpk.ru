<?php
if (current_user()) {
    redirect('admin');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $login = isset($_POST['login']) ? trim($_POST['login']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $result = attempt_login($login, $password);
    if ($result === true) {
        $next = !empty($_SESSION['after_login']) ? $_SESSION['after_login'] : 'admin';
        unset($_SESSION['after_login']);
        if (strpos($next, 'admin') !== 0) {
            $next = 'admin';
        }
        redirect($next);
    }
    $error = $result;
}

render_header('Вход', array('noindex' => true, 'canonical' => 'admin/login'));
?>
<div class="login-box">
    <h1>Вход в админку</h1>
    <?php if ($error): ?><p class="error"><?php echo e($error); ?></p><?php endif; ?>
    <form class="form" method="post" action="<?php echo e(url('admin/login')); ?>">
        <?php echo csrf_field(); ?>
        <label>
            <span>Логин</span>
            <input type="text" name="login" autocomplete="username" required>
        </label>
        <label>
            <span>Пароль</span>
            <input type="password" name="password" autocomplete="current-password" required>
        </label>
        <button class="btn" type="submit">Войти</button>
    </form>
</div>
<?php
render_footer();
