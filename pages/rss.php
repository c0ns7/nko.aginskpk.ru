<?php
header('Content-Type: application/rss+xml; charset=utf-8');
$items = db()->query('SELECT * FROM news WHERE published = 1 ORDER BY grant_date DESC, id DESC LIMIT 30')->fetchAll();
$org = setting('org_short', 'РОО «Развитие АПК»');
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
  <channel>
    <title><?php echo e($org); ?> — новости и гранты</title>
    <link><?php echo e(canonical('')); ?></link>
    <description><?php echo e(excerpt_text(setting('org_about'), 240)); ?></description>
    <language>ru</language>
    <atom:link href="<?php echo e(canonical('rss.xml')); ?>" rel="self" type="application/rss+xml"/>
<?php foreach ($items as $item): ?>
    <item>
      <title><?php echo e($item['title']); ?></title>
      <link><?php echo e(canonical('news/' . (int) $item['id'])); ?></link>
      <guid><?php echo e(canonical('news/' . (int) $item['id'])); ?></guid>
      <?php if ($item['grant_date']): ?><pubDate><?php echo e(date('r', strtotime($item['grant_date']))); ?></pubDate><?php endif; ?>
      <description><?php echo e(excerpt_text($item['body'] ? $item['body'] : $item['participation'], 300)); ?></description>
    </item>
<?php endforeach; ?>
  </channel>
</rss>
<?php
exit;
