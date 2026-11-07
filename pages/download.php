<?php
$id = isset($params[0]) ? (int) $params[0] : 0;
$stmt = db()->prepare('SELECT * FROM documents WHERE id = ?');
$stmt->execute(array($id));
$doc = $stmt->fetch();
if (!$doc) {
    http_response_code(404);
    echo 'Файл не найден.';
    return;
}

$path = UPLOAD_DIR . DIRECTORY_SEPARATOR . $doc['stored_name'];
if (!is_file($path)) {
    http_response_code(404);
    echo 'Файл отсутствует на диске.';
    return;
}

send_stored_file($doc);
