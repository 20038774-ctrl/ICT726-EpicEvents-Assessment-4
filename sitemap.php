<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
header('Content-Type: application/xml; charset=UTF-8');
$pages=['index.php','events.php','about.php','contact.php','privacy.php','accessibility.php'];
$events=db()->query("SELECT id,updated_at FROM events WHERE status='published' AND event_date>=CURDATE() ORDER BY id")->fetchAll();
echo '<?xml version="1.0" encoding="UTF-8"?' . '>';
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php foreach($pages as $page): ?><url><loc><?= e(url($page)) ?></loc><changefreq><?= $page==='events.php'?'daily':'monthly' ?></changefreq></url><?php endforeach; ?>
<?php foreach($events as $event): ?><url><loc><?= e(url('event.php?id='.$event['id'])) ?></loc><lastmod><?= e(date('Y-m-d',strtotime($event['updated_at']))) ?></lastmod><changefreq>weekly</changefreq></url><?php endforeach; ?>
</urlset>
