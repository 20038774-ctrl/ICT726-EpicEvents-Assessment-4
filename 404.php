<?php
if (!isset($config)) { require __DIR__ . '/includes/bootstrap.php'; }
$pageTitle = 'Page not found';
$metaDescription = 'The requested EpicEvents page could not be found.';
$robots = 'noindex, nofollow';
require __DIR__ . '/includes/header.php';
?>
<main id="main-content" class="page-shell narrow"><section class="empty-state"><p class="eyebrow">404</p><h1>That page has left the venue.</h1><p>The address may be incorrect or the content may have moved.</p><a class="button button-primary" href="<?= e(url('index.php')) ?>">Return home</a></section></main>
<?php require __DIR__ . '/includes/footer.php'; ?>
