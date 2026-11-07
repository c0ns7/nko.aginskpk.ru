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

function format_date_parts($date)
{
    $empty = array('day' => '', 'month' => '', 'year' => '');
    if (!$date || $date === '0000-00-00') {
        return $empty;
    }
    $ts = strtotime($date);
    if (!$ts) {
        return $empty;
    }
    $months = array(
        1 => 'января', 2 => 'февраля', 3 => 'марта', 4 => 'апреля',
        5 => 'мая', 6 => 'июня', 7 => 'июля', 8 => 'августа',
        9 => 'сентября', 10 => 'октября', 11 => 'ноября', 12 => 'декабря',
    );
    return array(
        'day' => (string) (int) date('j', $ts),
        'month' => $months[(int) date('n', $ts)],
        'year' => date('Y', $ts),
    );
}

function initials($name)
{
    $parts = preg_split('/\s+/u', trim((string) $name));
    $out = '';
    foreach ($parts as $part) {
        if ($part === '') {
            continue;
        }
        $letter = function_exists('mb_substr') ? mb_substr($part, 0, 1, 'UTF-8') : substr($part, 0, 1);
        $out .= function_exists('mb_strtoupper') ? mb_strtoupper($letter, 'UTF-8') : strtoupper($letter);
        if ((function_exists('mb_strlen') ? mb_strlen($out, 'UTF-8') : strlen($out)) >= 2) {
            break;
        }
    }
    return $out !== '' ? $out : '•';
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

function setting_filled($key, $default = '')
{
    $value = setting($key, '');
    return $value !== '' ? $value : $default;
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

function save_upload_file($file)
{
    if (!is_array($file) || !isset($file['error'])) {
        return 'Файл не получен.';
    }
    if ($file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return 'Не удалось загрузить файл.';
    }
    if ($file['size'] > MAX_UPLOAD_BYTES) {
        return 'Файл больше 40 МБ.';
    }
    $ext = file_ext($file['name']);
    if (!in_array($ext, allowed_extensions(), true)) {
        return 'Этот тип файла не разрешён.';
    }
    $stored = safe_stored_name($file['name']);
    $dest = UPLOAD_DIR . DIRECTORY_SEPARATOR . $stored;
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        return 'Не удалось сохранить файл на сервере.';
    }
    $mime = '';
    if (function_exists('mime_content_type')) {
        $detected = @mime_content_type($dest);
        if ($detected) {
            $mime = $detected;
        }
    }
    return array(
        'stored_name' => $stored,
        'original_name' => $file['name'],
        'mime' => $mime,
        'size_bytes' => filesize($dest),
    );
}

function news_files($newsId)
{
    $stmt = db()->prepare('SELECT * FROM news_files WHERE news_id = ? ORDER BY id ASC');
    $stmt->execute(array((int) $newsId));
    return $stmt->fetchAll();
}

function delete_news_file_row($file)
{
    if (!$file) {
        return;
    }
    $path = UPLOAD_DIR . DIRECTORY_SEPARATOR . $file['stored_name'];
    if (is_file($path)) {
        @unlink($path);
    }
    $del = db()->prepare('DELETE FROM news_files WHERE id = ?');
    $del->execute(array((int) $file['id']));
}

function delete_news_files_for($newsId)
{
    foreach (news_files($newsId) as $file) {
        delete_news_file_row($file);
    }
}

function attach_uploads_to_news($newsId, $titlePrefix)
{
    if (empty($_FILES['files']) || !isset($_FILES['files']['name'])) {
        return 0;
    }
    $names = $_FILES['files']['name'];
    if (!is_array($names)) {
        $names = array($names);
        $map = array();
        foreach (array('type', 'tmp_name', 'error', 'size') as $k) {
            $map[$k] = array(isset($_FILES['files'][$k]) ? $_FILES['files'][$k] : '');
        }
        $map['name'] = $names;
        $_FILES['files'] = $map;
    }
    $added = 0;
    $count = count($_FILES['files']['name']);
    $ins = db()->prepare('INSERT INTO news_files (news_id, title, stored_name, original_name, mime, size_bytes, created_at) VALUES (?,?,?,?,?,?,?)');
    for ($i = 0; $i < $count; $i++) {
        $file = array(
            'name' => $_FILES['files']['name'][$i],
            'type' => $_FILES['files']['type'][$i],
            'tmp_name' => $_FILES['files']['tmp_name'][$i],
            'error' => $_FILES['files']['error'][$i],
            'size' => $_FILES['files']['size'][$i],
        );
        $saved = save_upload_file($file);
        if ($saved === null) {
            continue;
        }
        if (!is_array($saved)) {
            continue;
        }
        $title = $titlePrefix !== '' ? $titlePrefix : pathinfo($saved['original_name'], PATHINFO_FILENAME);
        $ins->execute(array(
            (int) $newsId,
            $title,
            $saved['stored_name'],
            $saved['original_name'],
            $saved['mime'],
            $saved['size_bytes'],
            date('Y-m-d H:i:s'),
        ));
        $added++;
    }
    return $added;
}

function send_stored_file($doc)
{
    $path = UPLOAD_DIR . DIRECTORY_SEPARATOR . $doc['stored_name'];
    if (!is_file($path)) {
        http_response_code(404);
        echo 'Файл отсутствует на диске.';
        return;
    }
    $mime = $doc['mime'] ? $doc['mime'] : 'application/octet-stream';
    $original = str_replace(array("\r", "\n", '"'), '', $doc['original_name']);
    $ascii = preg_replace('/[^\x20-\x7E]/', '_', $original);
    if ($ascii === '' || preg_match('/^_+$/', $ascii)) {
        $ext = file_ext($doc['original_name']);
        $ascii = 'document' . ($ext ? '.' . $ext : '');
    }
    header('X-Robots-Tag: noindex, nofollow');
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . filesize($path));
    header('Content-Disposition: attachment; filename="' . $ascii . '"; filename*=UTF-8\'\'' . rawurlencode($original));
    header('X-Content-Type-Options: nosniff');
    readfile($path);
    exit;
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
        file_put_contents($ht, "<FilesMatch \"\\.(php|phtml|php3|php4|php5|phar)$\">\nRequire all denied\n</FilesMatch>\n");
    }
}

function seo_host()
{
    return isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'nko.aginskpk.ru';
}

function canonical_base()
{
    $script = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '/index.php';
    $dir = rtrim(str_replace('\\', '/', dirname($script)), '/.');
    if ($dir === '/' || $dir === '\\' || $dir === '.') {
        $dir = '';
    }
    return 'https://' . seo_host() . $dir;
}

function canonical($path = '')
{
    $path = ltrim((string) $path, '/');
    $base = rtrim(canonical_base(), '/');
    return $path === '' ? $base . '/' : $base . '/' . $path;
}

function excerpt_text($text, $len = 160)
{
    $text = trim(preg_replace('/\s+/u', ' ', str_replace(array("\n", "\r"), ' ', strip_tags((string) $text))));
    if ($text === '') {
        return '';
    }
    if (function_exists('mb_strlen') && mb_strlen($text, 'UTF-8') > $len) {
        return rtrim(mb_substr($text, 0, $len - 1, 'UTF-8'), " \t,.;:") . '…';
    }
    if (strlen($text) > $len) {
        return rtrim(substr($text, 0, $len - 1), " \t,.;:") . '…';
    }
    return $text;
}

function json_ld($data)
{
    return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

function org_schema()
{
    $id = canonical('');
    $schema = array(
        '@context' => 'https://schema.org',
        '@type' => 'NGO',
        '@id' => $id . '#organization',
        'name' => setting('org_short', 'РОО «Развитие АПК»'),
        'legalName' => setting('org_full'),
        'url' => $id,
        'logo' => canonical('public/favicon.svg'),
        'image' => canonical('public/og.svg'),
        'email' => setting('org_email'),
        'telephone' => setting('org_phone'),
        'address' => array(
            '@type' => 'PostalAddress',
            'streetAddress' => 'ул. Комсомольская, д. 22, кабинет 102',
            'addressLocality' => 'пгт Агинское',
            'addressRegion' => 'Забайкальский край',
            'postalCode' => '687000',
            'addressCountry' => 'RU',
        ),
        'identifier' => array(
            array('@type' => 'PropertyValue', 'name' => 'ИНН', 'value' => setting('org_inn')),
            array('@type' => 'PropertyValue', 'name' => 'ОГРН', 'value' => setting('org_ogrn')),
            array('@type' => 'PropertyValue', 'name' => 'КПП', 'value' => setting('org_kpp')),
        ),
        'parentOrganization' => array(
            '@type' => 'EducationalOrganization',
            'name' => 'ГАПОУ «Агинский педагогический колледж им. Базара Ринчино»',
            'url' => 'https://aginskpk.ru/',
        ),
        'sameAs' => array('https://aginskpk.ru/'),
        'areaServed' => array(
            '@type' => 'AdministrativeArea',
            'name' => 'Агинский Бурятский округ, Забайкальский край',
        ),
    );
    if (setting('org_head')) {
        $schema['employee'] = array(
            '@type' => 'Person',
            'name' => setting('org_head'),
            'jobTitle' => 'Председатель совета',
        );
    }
    return $schema;
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
