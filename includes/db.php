<?php

function db_driver()
{
    static $driver = null;
    if ($driver === null) {
        db();
        $driver = $GLOBALS['_db_driver'];
    }
    return $driver;
}

function db()
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    @ini_set('default_socket_timeout', '5');
    $mysqlDsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
    try {
        $pdo = new PDO($mysqlDsn, DB_USER, DB_PASS, array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_TIMEOUT => 5,
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci',
        ));
        $pdo->exec('SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci');
        $pdo->exec('SET CHARACTER SET utf8mb4');
        $GLOBALS['_db_driver'] = 'mysql';
        return $pdo;
    } catch (PDOException $e) {
        $sqlite = ROOT_PATH . '/storage/local.sqlite';
        if (!is_dir(dirname($sqlite))) {
            mkdir(dirname($sqlite), 0755, true);
        }
        try {
            $pdo = new PDO('sqlite:' . $sqlite, null, null, array(
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ));
            $pdo->exec('PRAGMA foreign_keys = ON');
            $GLOBALS['_db_driver'] = 'sqlite';
            $GLOBALS['_db_fallback_reason'] = $e->getMessage();
            return $pdo;
        } catch (PDOException $sqliteError) {
            http_response_code(500);
            header('Content-Type: text/plain; charset=utf-8');
            echo "Не удалось подключиться к MySQL (" . DB_HOST . "): " . $e->getMessage() . "\n";
            echo "Резервная SQLite тоже недоступна: " . $sqliteError->getMessage();
            exit;
        }
    }
}

function schema_install()
{
    $flag = ROOT_PATH . '/storage/.installed';
    $force = !is_file($flag);

    if (db_driver() === 'mysql') {
        mysql_schema();
    } else {
        sqlite_schema();
    }

    seed_if_needed($force);
    if (db_driver() === 'mysql') {
        repair_mojibake_once();
    }
    if ($force) {
        @file_put_contents($flag, date('c'));
    }
}

function mysql_schema()
{
    $pdo = db();
    try {
        $pdo->exec('ALTER DATABASE `' . str_replace('`', '', DB_NAME) . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    } catch (Exception $e) {
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS settings (
        k VARCHAR(64) NOT NULL PRIMARY KEY,
        v TEXT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        login VARCHAR(64) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        created_at DATETIME NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS news (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        body TEXT,
        grant_name VARCHAR(255) DEFAULT '',
        grant_status VARCHAR(32) NOT NULL DEFAULT 'planned',
        grant_date DATE DEFAULT NULL,
        location VARCHAR(255) DEFAULT '',
        participation TEXT,
        published TINYINT(1) NOT NULL DEFAULT 1,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS documents (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        stored_name VARCHAR(255) NOT NULL,
        original_name VARCHAR(255) NOT NULL,
        mime VARCHAR(128) DEFAULT '',
        size_bytes INT UNSIGNED NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS members (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        full_name VARCHAR(255) NOT NULL,
        role_title VARCHAR(255) DEFAULT '',
        member_type VARCHAR(32) NOT NULL DEFAULT 'member',
        bio TEXT,
        grant_involvement TEXT,
        sort_order INT NOT NULL DEFAULT 0,
        published TINYINT(1) NOT NULL DEFAULT 1
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $tables = array('settings', 'users', 'news', 'documents', 'members');
    foreach ($tables as $table) {
        try {
            $status = $pdo->query("SHOW TABLE STATUS LIKE '" . $table . "'")->fetch();
            $collation = $status && isset($status['Collation']) ? $status['Collation'] : '';
            if (stripos($collation, 'utf8mb4') === false) {
                $pdo->exec('ALTER TABLE `' . $table . '` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
            }
        } catch (Exception $e) {
        }
    }
}

function text_is_mojibake($value)
{
    if (!is_string($value) || $value === '') {
        return false;
    }
    return (strpos($value, 'Р°') !== false || strpos($value, 'Рё') !== false || strpos($value, 'С‚') !== false || strpos($value, 'Рѕ') !== false);
}

function unmojibake_text($value)
{
    if (!is_string($value) || $value === '' || !text_is_mojibake($value)) {
        return $value;
    }
    $bytes = function_exists('iconv') ? @iconv('UTF-8', 'CP1251//IGNORE', $value) : false;
    if ($bytes === false || $bytes === '') {
        return $value;
    }
    if (function_exists('mb_check_encoding') && !mb_check_encoding($bytes, 'UTF-8')) {
        return $value;
    }
    if (!preg_match('/[А-Яа-яЁё]/u', $bytes)) {
        return $value;
    }
    return $bytes;
}

function repair_mojibake_once()
{
    $done = db()->prepare('SELECT v FROM settings WHERE k = ?');
    $done->execute(array('mojibake_fixed'));
    $row = $done->fetch();
    if ($row && $row['v'] === '1') {
        return;
    }

    $check = db()->query("SELECT v FROM settings WHERE k = 'org_short'")->fetch();
    $sample = $check ? $check['v'] : '';
    if ($sample === '' || !text_is_mojibake($sample)) {
        setting_set('mojibake_fixed', '1');
        return;
    }

    $maps = array(
        'settings' => array('v'),
        'news' => array('title', 'body', 'grant_name', 'location', 'participation'),
        'documents' => array('title', 'description', 'original_name'),
        'members' => array('full_name', 'role_title', 'bio', 'grant_involvement'),
    );

    foreach ($maps as $table => $columns) {
        $rows = db()->query('SELECT * FROM `' . $table . '`')->fetchAll();
        foreach ($rows as $item) {
            $changed = false;
            $sets = array();
            $params = array();
            foreach ($columns as $col) {
                if (!isset($item[$col])) {
                    continue;
                }
                $fixed = unmojibake_text($item[$col]);
                if ($fixed !== $item[$col]) {
                    $sets[] = '`' . $col . '` = ?';
                    $params[] = $fixed;
                    $changed = true;
                }
            }
            if ($changed) {
                $params[] = $item['id'];
                $sql = 'UPDATE `' . $table . '` SET ' . implode(', ', $sets) . ' WHERE id = ?';
                if ($table === 'settings') {
                    $sql = 'UPDATE settings SET ' . implode(', ', $sets) . ' WHERE k = ?';
                    $params[count($params) - 1] = $item['k'];
                }
                $upd = db()->prepare($sql);
                $upd->execute($params);
            }
        }
    }

    setting_set('mojibake_fixed', '1');
}

function sqlite_schema()
{
    $pdo = db();
    $pdo->exec("CREATE TABLE IF NOT EXISTS settings (
        k TEXT NOT NULL PRIMARY KEY,
        v TEXT
    )");
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        login TEXT NOT NULL UNIQUE,
        password_hash TEXT NOT NULL,
        created_at TEXT NOT NULL
    )");
    $pdo->exec("CREATE TABLE IF NOT EXISTS news (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        body TEXT,
        grant_name TEXT DEFAULT '',
        grant_status TEXT NOT NULL DEFAULT 'planned',
        grant_date TEXT DEFAULT NULL,
        location TEXT DEFAULT '',
        participation TEXT,
        published INTEGER NOT NULL DEFAULT 1,
        created_at TEXT NOT NULL,
        updated_at TEXT NOT NULL
    )");
    $pdo->exec("CREATE TABLE IF NOT EXISTS documents (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        description TEXT,
        stored_name TEXT NOT NULL,
        original_name TEXT NOT NULL,
        mime TEXT DEFAULT '',
        size_bytes INTEGER NOT NULL DEFAULT 0,
        created_at TEXT NOT NULL
    )");
    $pdo->exec("CREATE TABLE IF NOT EXISTS members (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        full_name TEXT NOT NULL,
        role_title TEXT DEFAULT '',
        member_type TEXT NOT NULL DEFAULT 'member',
        bio TEXT,
        grant_involvement TEXT,
        sort_order INTEGER NOT NULL DEFAULT 0,
        published INTEGER NOT NULL DEFAULT 1
    )");
}

function seed_if_needed($force)
{
    $count = (int) db()->query('SELECT COUNT(*) FROM users')->fetchColumn();
    if ($count > 0 && !$force) {
        seed_documents_from_folder(false);
        return;
    }
    if ($count === 0) {
        $stmt = db()->prepare('INSERT INTO users (login, password_hash, created_at) VALUES (?, ?, ?)');
        $stmt->execute(array(
            DEFAULT_ADMIN_LOGIN,
            password_hash(DEFAULT_ADMIN_PASSWORD, PASSWORD_DEFAULT),
            date('Y-m-d H:i:s'),
        ));
    }

    $defaults = array(
        'org_short' => 'РОО «Развитие АПК»',
        'org_full' => 'Региональная общественная организация «Развитие Агинского педагогического колледжа им. Базара Ринчино»',
        'org_address' => '687000, Забайкальский край, пгт Агинское, ул. Комсомольская, д. 22, кабинет 102',
        'org_email' => 'aginskpk@mail.ru',
        'org_phone' => '(30239) 3-75-25',
        'org_inn' => '8001018735',
        'org_ogrn' => '1187500000566',
        'org_kpp' => '800101001',
        'org_head' => 'Шойдокова Наталья Цыренжаповна, председатель совета',
        'org_about' => "Региональная общественная организация создана для поддержки развития Агинского педагогического колледжа им. Базара Ринчино, участия в грантовых конкурсах и социально значимых проектах на территории Агинского Бурятского округа и Забайкальского края.\n\nНа сайте публикуются сведения о грантах: какие конкурсы рассматриваются, куда поданы заявки, какие проекты одобрены или отклонены, где и в каком качестве организация принимала участие.",
    );
    foreach ($defaults as $k => $v) {
        $exists = db()->prepare('SELECT k FROM settings WHERE k = ?');
        $exists->execute(array($k));
        if (!$exists->fetch()) {
            setting_set($k, $v);
        }
    }

    $newsCount = (int) db()->query('SELECT COUNT(*) FROM news')->fetchColumn();
    if ($newsCount === 0) {
        $now = date('Y-m-d H:i:s');
        $items = array(
            array(
                'title' => 'Участие в конкурсе грантов губернатора Забайкальского края',
                'body' => 'Организация готовит и сопровождает заявки социально значимых проектов, связанных с образованием, воспитанием и развитием студенческого сообщества колледжа. Итоги публикуются после официального объявления оператора конкурса.',
                'grant_name' => 'Гранты губернатора Забайкальского края',
                'grant_status' => 'submitted',
                'grant_date' => '2024-03-15',
                'location' => 'Забайкальский край, пгт Агинское',
                'participation' => 'Подача заявки, подготовка пакета документов, привлечение преподавателей и студентов колледжа как команды проекта.',
            ),
            array(
                'title' => 'Одобрен проект поддержки образовательных инициатив колледжа',
                'body' => 'Проект направлен на развитие внеучебной деятельности, просветительских встреч и партнёрства с организациями округа. Средства гранта используются строго по смете и с отчётностью перед грантодателем.',
                'grant_name' => 'Конкурс Фонда президентских грантов',
                'grant_status' => 'approved',
                'grant_date' => '2023-09-01',
                'location' => 'Российская Федерация / Забайкальский край',
                'participation' => 'Заявитель и исполнитель проекта: формирование команды, календарный план, отчётность.',
            ),
            array(
                'title' => 'Заявка на инфраструктурный грант не получила поддержку',
                'body' => 'Конкурсная комиссия не включила заявку в список победителей. Организация сохраняет материалы проекта и планирует доработку к следующим приёмам заявок.',
                'grant_name' => 'Региональный конкурс инфраструктурных проектов НКО',
                'grant_status' => 'rejected',
                'grant_date' => '2023-05-20',
                'location' => 'г. Чита',
                'participation' => 'Подача заявки от имени РОО, экспертное сопровождение со стороны членов организации.',
            ),
            array(
                'title' => 'Организация выступила партнёром просветительских мероприятий округа',
                'body' => 'Члены РОО участвовали в подготовке площадок, приглашении спикеров и информационной поддержке мероприятий для студентов и жителей посёлка.',
                'grant_name' => 'Совместные социально значимые мероприятия',
                'grant_status' => 'participated',
                'grant_date' => '2022-08-18',
                'location' => 'пгт Агинское',
                'participation' => 'Партнёр и участник: волонтёрская помощь, информационное сопровождение, привлечение студентов.',
            ),
        );
        $stmt = db()->prepare('INSERT INTO news (title, body, grant_name, grant_status, grant_date, location, participation, published, created_at, updated_at) VALUES (?,?,?,?,?,?,?,1,?,?)');
        foreach ($items as $item) {
            $stmt->execute(array(
                $item['title'], $item['body'], $item['grant_name'], $item['grant_status'],
                $item['grant_date'], $item['location'], $item['participation'], $now, $now,
            ));
        }
    }

    $memberCount = (int) db()->query('SELECT COUNT(*) FROM members')->fetchColumn();
    if ($memberCount === 0) {
        $people = array(
            array('Шойдокова Наталья Цыренжаповна', 'Председатель совета', 'board', 'Руководит советом организации с 19 марта 2021 года.', 'Координация грантовых заявок и представление организации.', 10),
            array('Совет организации', 'Коллегиальный орган управления', 'board', 'Рассматривает вопросы участия в конкурсах, утверждает документы и отчёты.', 'Принятие решений об участии в грантах.', 20),
            array('Члены РОО «Развитие АПК»', 'Члены региональной общественной организации', 'member', 'В состав входят педагоги, сотрудники и партнёры Агинского педагогического колледжа им. Базара Ринчино. Полный список уточняется и публикуется по решению совета.', '', 30),
            array('Команда грантовых проектов', 'Участники реализации грантов', 'grant_participant', 'Преподаватели, студенты и приглашённые специалисты, которые включаются в конкретный проект по гранту.', 'Исполнение мероприятий, отчётность, работа с целевыми группами.', 40),
        );
        $stmt = db()->prepare('INSERT INTO members (full_name, role_title, member_type, bio, grant_involvement, sort_order, published) VALUES (?,?,?,?,?,?,1)');
        foreach ($people as $p) {
            $stmt->execute($p);
        }
    }

    seed_documents_from_folder(true);
}

function seed_documents_from_folder($copyAlways)
{
    if (!is_dir(DOCS_SOURCE_DIR)) {
        return;
    }
    $titles = array(
        'выписка.pdf' => array('Выписка из ЕГРЮЛ', 'Выписка о государственной регистрации организации.'),
        'УСТАВ РОО Развитие.pdf' => array('Устав РОО «Развитие»', 'Устав региональной общественной организации.'),
        'ОГРН РОО Развитие.pdf' => array('Свидетельство ОГРН', 'Документ о присвоении ОГРН 1187500000566.'),
        'ИНН РОО Развитие.pdf' => array('Свидетельство ИНН', 'Документ о постановке на учёт, ИНН 8001018735.'),
        'РЕКВИЗИТЫ РОО РАЗВИТИЕ.doc' => array('Реквизиты организации', 'Банковские и юридические реквизиты РОО «Развитие АПК».'),
    );

    $files = scandir(DOCS_SOURCE_DIR);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') {
            continue;
        }
        $src = DOCS_SOURCE_DIR . DIRECTORY_SEPARATOR . $file;
        if (!is_file($src)) {
            continue;
        }

        $check = db()->prepare('SELECT id FROM documents WHERE original_name = ?');
        $check->execute(array($file));
        if ($check->fetch()) {
            continue;
        }

        $stored = safe_stored_name($file);
        $dest = UPLOAD_DIR . DIRECTORY_SEPARATOR . $stored;
        if (!@copy($src, $dest)) {
            continue;
        }

        $meta = isset($titles[$file]) ? $titles[$file] : array(pathinfo($file, PATHINFO_FILENAME), '');
        $mime = '';
        if (function_exists('mime_content_type')) {
            $detected = @mime_content_type($dest);
            if ($detected) {
                $mime = $detected;
            }
        }
        $stmt = db()->prepare('INSERT INTO documents (title, description, stored_name, original_name, mime, size_bytes, created_at) VALUES (?,?,?,?,?,?,?)');
        $stmt->execute(array(
            $meta[0],
            $meta[1],
            $stored,
            $file,
            $mime,
            filesize($dest),
            date('Y-m-d H:i:s'),
        ));
    }
}
