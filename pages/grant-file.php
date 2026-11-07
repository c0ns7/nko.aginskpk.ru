<?php
$id = isset($params[0]) ? (int) $params[0] : 0;
$stmt = db()->prepare('SELECT f.*, n.published FROM news_files f INNER JOIN news n ON n.id = f.news_id WHERE f.id = ?');
$stmt->execute(array($id));
$doc = $stmt->fetch();
if (!$doc) {
    http_response_code(404);
    echo 'Файл не найден.';
    return;
}
if (empty($doc['published']) && !current_user()) {
    http_response_code(404);
    echo 'Файл не найден.';
    return;
}
send_stored_file($doc);
