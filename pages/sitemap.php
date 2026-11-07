<?php
header('Content-Type: application/xml; charset=utf-8');
$urls = array(
    array('loc' => canonical(''), 'changefreq' => 'weekly', 'priority' => '1.0'),
    array('loc' => canonical('documents'), 'changefreq' => 'monthly', 'priority' => '0.8'),
    array('loc' => canonical('members'), 'changefreq' => 'monthly', 'priority' => '0.7'),
);

$news = db()->query('SELECT id, updated_at, grant_date FROM news WHERE published = 1 ORDER BY id DESC')->fetchAll();
foreach ($news as $item) {
    $last = $item['updated_at'] ? $item['updated_at'] : $item['grant_date'];
    $urls[] = array(
        'loc' => canonical('news/' . (int) $item['id']),
        'lastmod' => $last ? date('c', strtotime($last)) : '',
        'changefreq' => 'monthly',
        'priority' => '0.6',
    );
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($urls as $row) {
    echo "  <url>\n";
    echo '    <loc>' . e($row['loc']) . "</loc>\n";
    if (!empty($row['lastmod'])) {
        echo '    <lastmod>' . e($row['lastmod']) . "</lastmod>\n";
    }
    echo '    <changefreq>' . e($row['changefreq']) . "</changefreq>\n";
    echo '    <priority>' . e($row['priority']) . "</priority>\n";
    echo "  </url>\n";
}
echo "</urlset>\n";
exit;
