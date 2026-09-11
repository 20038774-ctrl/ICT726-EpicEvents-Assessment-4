<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

$pageTitle = 'Sydney and NSW event management';
$metaDescription = 'Browse and book curated festivals, corporate gatherings and cultural events across Sydney and New South Wales.';
$featured = db()->query("SELECT id, title, category, event_date, location, capacity, price, image_url, description FROM events WHERE status = 'published' AND event_date >= CURDATE() ORDER BY event_date LIMIT 3")->fetchAll();
require __DIR__ . '/includes/header.php';
?>
<main id="main-content">
    <section class="hero">
        <div class="hero-content">
            <p class="eyebrow">Events across New South Wales</p>
            <h1>Make the moment matter.</h1>
            <p>Discover carefully produced festivals, cultural programs and professional gatherings—and reserve your place in minutes.</p>
            <div class="button-row">
                <a class="button button-primary" href="<?= e(url('events.php')) ?>">Explore events</a>
                <a class="button button-ghost" href="<?= e(url('contact.php')) ?>">Plan with us</a>
            </div>
        </div>
        <aside class="hero-note" aria-label="EpicEvents commitment"><strong>Local knowledge.</strong><span>Accessible experiences.</span><span>Clear, secure booking.</span></aside>
    </section>

    <section class="section-shell" aria-labelledby="featured-title">
        <div class="section-heading"><div><p class="eyebrow">Coming up</p><h2 id="featured-title">Featured experiences</h2></div><a class="text-link" href="<?= e(url('events.php')) ?>">View every event <span aria-hidden="true">→</span></a></div>
        <div class="event-grid">
            <?php foreach ($featured as $event): ?>
                <article class="event-card">
                    <img src="<?= e($event['image_url']) ?>" width="640" height="420" loading="lazy" alt="" referrerpolicy="no-referrer">
                    <div class="event-card-body">
                        <p class="event-meta"><span><?= e($event['category']) ?></span> <?= e(date('j M Y', strtotime($event['event_date']))) ?></p>
                        <h3><a href="<?= e(url('event.php?id=' . $event['id'])) ?>"><?= e($event['title']) ?></a></h3>
                        <p><?= e(mb_strimwidth($event['description'], 0, 135, '…')) ?></p>
                        <div class="card-footer"><span><?= e($event['location']) ?></span><strong><?= (float) $event['price'] > 0 ? '$' . number_format((float) $event['price'], 2) : 'Free' ?></strong></div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="trust-band" aria-labelledby="trust-title">
        <div><p class="eyebrow">Why EpicEvents</p><h2 id="trust-title">Built around people, not paperwork.</h2></div>
        <div class="trust-items"><p><strong>Accessible by design</strong><span>Keyboard-friendly flows, clear labels and readable contrast.</span></p><p><strong>Privacy with purpose</strong><span>We collect only what is required to manage your reservation.</span></p><p><strong>NSW expertise</strong><span>Relevant events from Sydney Harbour to regional communities.</span></p></div>
    </section>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
