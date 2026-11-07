<?php
header('Content-Type: text/plain; charset=utf-8');
header('X-Robots-Tag: noindex');
$sitemap = canonical('sitemap.xml');
echo "User-agent: *\n";
echo "Allow: /\n";
echo "Disallow: /admin\n";
echo "Disallow: /admin/\n";
echo "Disallow: /health.php\n";
echo "Disallow: /storage\n";
echo "Disallow: /storage/\n";
echo "Disallow: /uploads/\n";
echo "Disallow: /download/\n";
echo "Disallow: /grant-file/\n";
echo "Clean-param: status /\n";
echo "Host: " . seo_host() . "\n";
echo "Sitemap: " . $sitemap . "\n";
exit;
