<?php

function is_https()
{
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        return true;
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') {
        return true;
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on') {
        return true;
    }
    if (!empty($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443) {
        return true;
    }
    return false;
}

function base_url()
{
    $scheme = is_https() ? 'https' : 'http';
    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
    $script = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '/index.php';
    $dir = rtrim(str_replace('\\', '/', dirname($script)), '/.');
    if ($dir === '/' || $dir === '\\' || $dir === '.') {
        $dir = '';
    }
    return $scheme . '://' . $host . $dir;
}

function url($path)
{
    $path = ltrim((string) $path, '/');
    $base = rtrim(base_url(), '/');
    return $path === '' ? $base . '/' : $base . '/' . $path;
}

function asset($path)
{
    return url(ltrim($path, '/'));
}

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect($path)
{
    header('Location: ' . url($path));
    exit;
}

function csrf_token()
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(function_exists('random_bytes') ? random_bytes(16) : openssl_random_pseudo_bytes(16));
    }
    return $_SESSION['csrf'];
}

function csrf_field()
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check()
{
    $token = isset($_POST['csrf']) ? $_POST['csrf'] : '';
    if ($token === '' || empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $token)) {
        http_response_code(400);
        echo 'Недействительный запрос. Обновите страницу и повторите.';
        exit;
    }
}

function flash_set($type, $message)
{
    $_SESSION['flash'] = array('type' => $type, 'message' => $message);
}

function flash_get()
{
    if (empty($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

function grant_statuses()
{
    return array(
        'planned' => 'Планируется',
        'submitted' => 'Подана заявка',
        'approved' => 'Одобрен',
        'rejected' => 'Отклонён',
        'participated' => 'Участие',
    );
}

function grant_status_label($key)
{
    $all = grant_statuses();
    return isset($all[$key]) ? $all[$key] : $key;
}

function member_types()
{
    return array(
        'board' => 'Органы управления',
        'member' => 'Члены организации',
        'grant_participant' => 'Участники грантов',
    );
}

function member_type_label($key)
{
    $all = member_types();
    return isset($all[$key]) ? $all[$key] : $key;
}

function format_date_ru($date)
{
    if (!$date || $date === '0000-00-00') {
        return '';
    }
    $ts = strtotime($date);
    if (!$ts) {
        return $date;
    }
    $months = array(
        1 => 'января', 2 => 'февраля', 3 => 'марта', 4 => 'апреля',
        5 => 'мая', 6 => 'июня', 7 => 'июля', 8 => 'августа',
        9 => 'сентября', 10 => 'октября', 11 => 'ноября', 12 => 'декабря',
    );
    return (int) date('j', $ts) . ' ' . $months[(int) date('n', $ts)] . ' ' . date('Y', $ts);
}

function format_bytes($bytes)
{
    $bytes = (int) $bytes;
    if ($bytes < 1024) {
        return $bytes . ' Б';
    }
    if ($bytes < 1048576) {
        return round($bytes / 1024, 1) . ' КБ';
    }
    return round($bytes / 1048576, 1) . ' МБ';
}

function setting($key, $default = '')
{
    static $cache = null;
    if ($cache === null) {
        $cache = array();
        try {
            $rows = db()->query('SELECT k, v FROM settings')->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $row) {
                $cache[$row['k']] = $row['v'];
            }
        } catch (Exception $e) {
            return $default;
        }
    }
    return array_key_exists($key, $cache) ? $cache[$key] : $default;
}

function setting_set($key, $value)
{
    $sql = 'INSERT INTO settings (k, v) VALUES (?, ?) ON DUPLICATE KEY UPDATE v = VALUES(v)';
    if (db_driver() === 'sqlite') {
        $sql = 'INSERT INTO settings (k, v) VALUES (?, ?) ON CONFLICT(k) DO UPDATE SET v = excluded.v';
    }
    $stmt = db()->prepare($sql);
    $stmt->execute(array($key, $value));
}

function request_route()
{
    if (isset($_GET['r'])) {
        return trim((string) $_GET['r'], '/');
    }
    $uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';
    $path = parse_url($uri, PHP_URL_PATH);
    $script = isset($_SERVER['SCRIPT_NAME']) ? dirname($_SERVER['SCRIPT_NAME']) : '';
    $script = rtrim(str_replace('\\', '/', $script), '/');
    if ($script !== '' && $script !== '/' && strpos($path, $script) === 0) {
        $path = substr($path, strlen($script));
    }
    $path = trim($path, '/');
    if ($path === 'index.php') {
        return '';
    }
    return $path;
}

function current_path()
{
    static $route = null;
    if ($route === null) {
        $route = request_route();
    }
    return $route;
}

function nav_active($path)
{
    $current = current_path();
    if ($path === '') {
        return $current === '' ? ' aria-current="page"' : '';
    }
    return ($current === $path || strpos($current, $path . '/') === 0) ? ' aria-current="page"' : '';
}

function allowed_extensions()
{
    return array_map('trim', explode(',', ALLOWED_DOC_EXT));
}

function file_ext($name)
{
    $pos = strrpos($name, '.');
    if ($pos === false) {
        return '';
    }
    return strtolower(substr($name, $pos + 1));
}

function safe_stored_name($original)
{
    $ext = file_ext($original);
    $ext = preg_replace('/[^a-z0-9]/', '', $ext);
    return date('YmdHis') . '_' . bin2hex(function_exists('random_bytes') ? random_bytes(4) : openssl_random_pseudo_bytes(4)) . ($ext ? '.' . $ext : '');
}

function ensure_storage()
{
    $dirs = array(
        ROOT_PATH . '/uploads',
        UPLOAD_DIR,
        ROOT_PATH . '/storage',
    );
    foreach ($dirs as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }
    $ht = ROOT_PATH . '/uploads/.htaccess';
    if (!is_file($ht)) {
        file_put_contents($ht, "Options -Indexes\n<FilesMatch \"\\.(php|phtml|php3|php4|php5|phar)$\">\nDeny from all\n</FilesMatch>\n");
    }
}

function nl2p($text)
{
    $text = trim((string) $text);
    if ($text === '') {
        return '';
    }
    $parts = preg_split("/\n\s*\n/", $text);
    $html = '';
    foreach ($parts as $part) {
        $html .= '<p>' . nl2br(e($part)) . '</p>';
    }
    return $html;
}
