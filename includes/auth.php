<?php

function current_user()
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }
    $stmt = db()->prepare('SELECT id, login FROM users WHERE id = ?');
    $stmt->execute(array((int) $_SESSION['user_id']));
    $user = $stmt->fetch();
    return $user ? $user : null;
}

function require_admin()
{
    if (!current_user()) {
        $_SESSION['after_login'] = current_path();
        redirect('admin/login');
    }
}

function login_throttle_ok()
{
    $now = time();
    if (empty($_SESSION['login_fails'])) {
        $_SESSION['login_fails'] = 0;
        $_SESSION['login_last'] = 0;
    }
    if ($_SESSION['login_fails'] >= 8 && ($now - (int) $_SESSION['login_last']) < 300) {
        return false;
    }
    if (($now - (int) $_SESSION['login_last']) > 300) {
        $_SESSION['login_fails'] = 0;
    }
    return true;
}

function attempt_login($login, $password)
{
    if (!login_throttle_ok()) {
        return 'Слишком много попыток. Подождите несколько минут.';
    }
    $stmt = db()->prepare('SELECT id, login, password_hash FROM users WHERE login = ?');
    $stmt->execute(array($login));
    $user = $stmt->fetch();
    $_SESSION['login_last'] = time();
    if (!$user || !password_verify($password, $user['password_hash'])) {
        $_SESSION['login_fails'] = (int) $_SESSION['login_fails'] + 1;
        return 'Неверный логин или пароль.';
    }
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['login_fails'] = 0;
    return true;
}

function logout_user()
{
    $_SESSION = array();
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], isset($p['domain']) ? $p['domain'] : '', !empty($p['secure']), !empty($p['httponly']));
    }
    session_destroy();
}
