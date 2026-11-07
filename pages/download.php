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

$mime = $doc['mime'] ? $doc['mime'] : 'application/octet-stream';
$original = str_replace(array("\r", "\n", '"'), '', $doc['original_name']);
$ascii = preg_replace('/[^\x20-\x7E]/', '_', $original);
if ($ascii === '' || preg_match('/^_+$/', $ascii)) {
    $ext = file_ext($doc['original_name']);
    $ascii = 'document' . ($ext ? '.' . $ext : '');
}

header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($path));
header('Content-Disposition: attachment; filename="' . $ascii . '"; filename*=UTF-8\'\'' . rawurlencode($original));
header('X-Content-Type-Options: nosniff');
readfile($path);
exit;
