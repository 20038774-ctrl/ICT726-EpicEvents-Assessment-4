<?php
declare(strict_types=1);

$pageTitle = $pageTitle ?? 'Event management across New South Wales';
$metaDescription = $metaDescription ?? 'Discover and book professionally managed festivals, corporate events and live experiences across New South Wales with EpicEvents.';
$currentPage = basename($_SERVER['PHP_SELF'] ?? 'index.php');
$isAdminRoute = str_contains((string) ($_SERVER['PHP_SELF'] ?? ''), '/admin/');
$canonical = $canonical ?? url($currentPage);
$robots = $robots ?? 'index, follow';
$user = current_user();
?>
<!doctype html>
<html lang="en-AU">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="<?= e($metaDescription) ?>">
    <meta name="robots" content="<?= e($robots) ?>">
    <meta name="theme-color" content="#111827">
    <link rel="canonical" href="<?= e($canonical) ?>">
    <title><?= e($pageTitle) ?> | EpicEvents</title>
    <link rel="stylesheet" href="<?= e(url('style.css')) ?>">
    <script type="application/ld+json"><?php echo json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'Organization',
        'name' => 'EpicEvents',
        'url' => url(''),
        'email' => 'inquire@epicevents.com.au',
        'areaServed' => 'New South Wales, Australia',
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?></script>
</head>
<body>
<a class="skip-link" href="#main-content">Skip to main content</a>
<header class="site-header">
    <div class="header-inner">
        <a class="brand" href="<?= e(url('index.php')) ?>" aria-label="EpicEvents home">Epic<span>Events</span></a>
        <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="primary-nav">Menu</button>
        <nav id="primary-nav" class="primary-nav" aria-label="Primary navigation">
            <a href="<?= e(url('index.php')) ?>" <?= $currentPage === 'index.php' && !$isAdminRoute ? 'aria-current="page"' : '' ?>>Home</a>
            <a href="<?= e(url('events.php')) ?>" <?= in_array($currentPage, ['events.php', 'event.php'], true) ? 'aria-current="page"' : '' ?>>Events</a>
            <a href="<?= e(url('about.php')) ?>" <?= $currentPage === 'about.php' ? 'aria-current="page"' : '' ?>>About</a>
            <a href="<?= e(url('contact.php')) ?>" <?= $currentPage === 'contact.php' ? 'aria-current="page"' : '' ?>>Contact</a>
            <?php if ($user): ?>
                <a href="<?= e(url('dashboard.php')) ?>" <?= $currentPage === 'dashboard.php' ? 'aria-current="page"' : '' ?>>Dashboard</a>
                <?php if (is_admin()): ?><a href="<?= e(url('admin/index.php')) ?>" <?= $isAdminRoute ? 'aria-current="page"' : '' ?>>Admin</a><?php endif; ?>
                <form class="inline-form" action="<?= e(url('logout.php')) ?>" method="post">
                    <?= csrf_field() ?>
                    <button class="nav-button" type="submit">Log out</button>
                </form>
            <?php else: ?>
                <a href="<?= e(url('login.php')) ?>" <?= $currentPage === 'login.php' ? 'aria-current="page"' : '' ?>>Log in</a>
                <a class="nav-cta" href="<?= e(url('register.php')) ?>">Create account</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
<?php foreach (pull_flashes() as $message): ?>
    <div class="flash flash-<?= e($message['type']) ?>" role="status"><?= e($message['message']) ?></div>
<?php endforeach; ?>
