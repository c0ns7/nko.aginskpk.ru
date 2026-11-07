<?php
/**
 * Настройки сайта РОО «Развитие АПК».
 * Хост aginskpk.mysql — внутреннее имя Beget; локально при недоступности MySQL
 * используется SQLite (storage/local.sqlite), на хостинге — MySQL.
 */

define('DB_HOST', 'aginskpk.mysql');
define('DB_PORT', 3306);
define('DB_NAME', 'aginskpk_nko');
define('DB_USER', 'aginskpk_nko');
define('DB_PASS', 'wMMuFhGYP7E');
define('DB_CHARSET', 'utf8mb4');

define('DEFAULT_ADMIN_LOGIN', 'admin');
define('DEFAULT_ADMIN_PASSWORD', 'RazvitieNko#2026');

define('UPLOAD_DIR', ROOT_PATH . '/uploads/documents');
define('DOCS_SOURCE_DIR', ROOT_PATH . '/docs');
define('MAX_UPLOAD_BYTES', 40 * 1024 * 1024);

define('ALLOWED_DOC_EXT', 'pdf,doc,docx,xls,xlsx,odt,ods,rtf,txt,jpg,jpeg,png,gif,webp,zip');
