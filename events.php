<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

$pageTitle = 'Upcoming events in NSW';
$metaDescription = 'Search upcoming NSW festivals, music, food, cultural and corporate events. Filter by category and book securely online.';
$search = trim((string) ($_GET['q'] ?? ''));
$category = trim((string) ($_GET['category'] ?? ''));
$categories = db()->query("SELECT DISTINCT category FROM events WHERE status = 'published' ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);
$sql = "SELECT e.*, (e.capacity - COALESCE(SUM(CASE WHEN b.status IN ('pending','confirmed') THEN b.quantity ELSE 0 END), 0)) AS places_left FROM events e LEFT JOIN bookings b ON b.event_id = e.id WHERE e.status = 'published' AND e.event_date >= CURDATE()";
$params = [];
if ($search !== '') {
    $sql .= ' AND (e.title LIKE :title_search OR e.description LIKE :description_search OR e.location LIKE :location_search)';
    $searchTerm = '%' . $search . '%';
    $params['title_search'] = $searchTerm;
    $params['description_search'] = $searchTerm;
    $params['location_search'] = $searchTerm;
}
if ($category !== '') {
    $sql .= ' AND e.category = :category';
    $params['category'] = $category;
}
$sql .= ' GROUP BY e.id ORDER BY e.event_date ASC';
$stmt = db()->prepare($sql);
$stmt->execute($params);
$events = $stmt->fetchAll();
require __DIR__ . '/includes/header.php';
?>
<main id="main-content" class="page-shell">
    <header class="page-intro"><p class="eyebrow">NSW event calendar</p><h1>Find your next experience</h1><p>Search verified events and reserve your place through one secure account.</p></header>
    <form class="filter-bar" method="get" action="<?= e(url('events.php')) ?>" role="search">
        <div class="field"><label for="q">Search events</label><input id="q" name="q" type="search" value="<?= e($search) ?>" placeholder="Title, venue or keyword"></div>
        <div class="field"><label for="category">Category</label><select id="category" name="category"><option value="">All categories</option><?php foreach ($categories as $option): ?><option value="<?= e($option) ?>" <?= $category === $option ? 'selected' : '' ?>><?= e($option) ?></option><?php endforeach; ?></select></div>
        <button class="button button-primary" type="submit">Search</button>
        <?php if ($search !== '' || $category !== ''): ?><a class="button button-secondary" href="<?= e(url('events.php')) ?>">Clear</a><?php endif; ?>
    </form>
    <p class="result-count" aria-live="polite"><?= count($events) ?> event<?= count($events) === 1 ? '' : 's' ?> found</p>
    <?php if ($events): ?>
        <section class="event-grid" aria-label="Search results">
            <?php foreach ($events as $event): ?>
                <article class="event-card">
                    <img src="<?= e($event['image_url']) ?>" width="640" height="420" loading="lazy" alt="" referrerpolicy="no-referrer">
                    <div class="event-card-body">
                        <p class="event-meta"><span><?= e($event['category']) ?></span> <?= e(date('D, j M Y', strtotime($event['event_date']))) ?></p>
                        <h2><a href="<?= e(url('event.php?id=' . $event['id'])) ?>"><?= e($event['title']) ?></a></h2>
                        <p><?= e(mb_strimwidth($event['description'], 0, 150, '…')) ?></p>
                        <div class="card-footer"><span><?= e($event['location']) ?></span><strong><?= (float) $event['price'] > 0 ? '$' . number_format((float) $event['price'], 2) : 'Free' ?></strong></div>
                        <p class="availability"><?= max(0, (int) $event['places_left']) ?> places available</p>
                    </div>
                </article>
            <?php endforeach; ?>
        </section>
    <?php else: ?>
        <section class="empty-state"><h2>No matching events</h2><p>Try a broader keyword or remove the category filter.</p><a class="button" href="<?= e(url('events.php')) ?>">See all events</a></section>
    <?php endif; ?>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
