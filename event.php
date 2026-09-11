<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) { http_response_code(404); require __DIR__ . '/404.php'; exit; }
$stmt = db()->prepare("SELECT e.*, (e.capacity - COALESCE(SUM(CASE WHEN b.status IN ('pending','confirmed') THEN b.quantity ELSE 0 END), 0)) AS places_left FROM events e LEFT JOIN bookings b ON b.event_id = e.id WHERE e.id = :id AND e.status = 'published' GROUP BY e.id");
$stmt->execute(['id' => $id]);
$event = $stmt->fetch();
if (!$event) { http_response_code(404); require __DIR__ . '/404.php'; exit; }
$pageTitle = $event['title'] . ' – NSW event';
$metaDescription = mb_strimwidth($event['description'], 0, 155, '…');
$canonical = url('event.php?id=' . $event['id']);
$eventStart = new DateTimeImmutable(
    $event['event_date'] . ' ' . $event['start_time'],
    new DateTimeZone('Australia/Sydney')
);
require __DIR__ . '/includes/header.php';
?>
<main id="main-content" class="page-shell">
    <article class="event-detail">
        <img class="event-cover" src="<?= e($event['image_url']) ?>" width="1200" height="675" alt="" referrerpolicy="no-referrer">
        <div class="event-copy">
            <p class="eyebrow"><?= e($event['category']) ?></p>
            <h1><?= e($event['title']) ?></h1>
            <dl class="event-facts">
                <div><dt>Date</dt><dd><?= e(date('l, j F Y', strtotime($event['event_date']))) ?></dd></div>
                <div><dt>Time</dt><dd><?= e(date('g:i a', strtotime($event['start_time']))) ?></dd></div>
                <div><dt>Location</dt><dd><?= e($event['location']) ?></dd></div>
                <div><dt>Price</dt><dd><?= (float) $event['price'] > 0 ? '$' . number_format((float) $event['price'], 2) : 'Free' ?></dd></div>
            </dl>
            <p class="lead"><?= nl2br(e($event['description'])) ?></p>
            <div class="booking-panel">
                <div><strong><?= max(0, (int) $event['places_left']) ?></strong><span>places remaining</span></div>
                <?php if ((int) $event['places_left'] > 0): ?><a class="button button-primary" href="<?= e(url('book.php?event_id=' . $event['id'])) ?>"><?= is_logged_in() ? 'Reserve tickets' : 'Log in to book' ?></a><?php else: ?><span class="sold-out">Sold out</span><?php endif; ?>
            </div>
        </div>
    </article>
</main>
<script type="application/ld+json"><?php echo json_encode(['@context'=>'https://schema.org','@type'=>'Event','name'=>$event['title'],'description'=>$event['description'],'image'=>[$event['image_url']],'startDate'=>$eventStart->format(DateTimeInterface::ATOM),'eventStatus'=>'https://schema.org/EventScheduled','eventAttendanceMode'=>'https://schema.org/OfflineEventAttendanceMode','location'=>['@type'=>'Place','name'=>$event['location'],'address'=>['@type'=>'PostalAddress','addressRegion'=>'NSW','addressCountry'=>'AU']],'organizer'=>['@type'=>'Organization','name'=>'EpicEvents','url'=>url('')],'offers'=>['@type'=>'Offer','price'=>$event['price'],'priceCurrency'=>'AUD','availability'=>(int)$event['places_left'] > 0 ? 'https://schema.org/InStock' : 'https://schema.org/SoldOut','validFrom'=>(new DateTimeImmutable('now', new DateTimeZone('Australia/Sydney')))->format('Y-m-d'),'url'=>$canonical]], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?></script>
<?php require __DIR__ . '/includes/footer.php'; ?>
