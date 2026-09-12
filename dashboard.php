<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
require_login();
if (is_post() && ($_POST['action'] ?? '') === 'cancel') {
    verify_csrf();
    $bookingId = filter_input(INPUT_POST, 'booking_id', FILTER_VALIDATE_INT);
    if ($bookingId) {
        $stmt = db()->prepare("UPDATE bookings SET status='cancelled', updated_at=CURRENT_TIMESTAMP WHERE id=:id AND user_id=:user_id AND status IN ('pending','confirmed')");
        $stmt->execute(['id' => $bookingId, 'user_id' => current_user()['id']]);
        flash($stmt->rowCount() ? 'success' : 'error', $stmt->rowCount() ? 'Your booking has been cancelled.' : 'That booking could not be cancelled.');
    } else {
        flash('error', 'Invalid booking.');
    }
    redirect('dashboard.php');
}
$stmt = db()->prepare('SELECT b.*, e.title, e.event_date, e.start_time, e.location, e.price FROM bookings b JOIN events e ON e.id=b.event_id WHERE b.user_id=:user_id ORDER BY e.event_date DESC, b.created_at DESC');
$stmt->execute(['user_id' => current_user()['id']]);
$bookings = $stmt->fetchAll();
$pageTitle = 'My booking dashboard';
$metaDescription = 'Review and manage your EpicEvents bookings.';
$robots = 'noindex, nofollow';
require __DIR__ . '/includes/header.php';
?>
<main id="main-content" class="page-shell">
    <header class="dashboard-head"><div><p class="eyebrow">Member dashboard</p><h1>Hello, <?= e(current_user()['name']) ?>.</h1><p>Review your reservations and keep your event plans organised.</p></div><a class="button button-primary" href="<?= e(url('events.php')) ?>">Find an event</a></header>
    <section aria-labelledby="bookings-title"><div class="section-heading"><h2 id="bookings-title">My bookings</h2><span class="count-badge"><?= count($bookings) ?></span></div>
        <?php if ($bookings): ?><div class="table-wrap"><table><thead><tr><th scope="col">Event</th><th scope="col">Date</th><th scope="col">Reference</th><th scope="col">Tickets</th><th scope="col">Total</th><th scope="col">Status</th><th scope="col"><span class="sr-only">Action</span></th></tr></thead><tbody>
        <?php foreach ($bookings as $booking): ?><tr><th scope="row"><strong><?= e($booking['title']) ?></strong><span><?= e($booking['location']) ?></span></th><td><?= e(date('j M Y', strtotime($booking['event_date']))) ?><span><?= e(date('g:i a', strtotime($booking['start_time']))) ?></span></td><td><code><?= e($booking['booking_reference']) ?></code></td><td><?= (int) $booking['quantity'] ?></td><td>$<?= number_format((float) $booking['price'] * (int) $booking['quantity'], 2) ?></td><td><span class="status status-<?= e($booking['status']) ?>"><?= e(ucfirst($booking['status'])) ?></span></td><td><?php if (in_array($booking['status'], ['pending','confirmed'], true) && strtotime($booking['event_date']) >= strtotime('today')): ?><form method="post" class="inline-form"><input type="hidden" name="action" value="cancel"><input type="hidden" name="booking_id" value="<?= (int) $booking['id'] ?>"><?= csrf_field() ?><button class="text-button danger" type="submit" data-confirm="Cancel this booking?">Cancel</button></form><?php endif; ?></td></tr><?php endforeach; ?>
        </tbody></table></div><?php else: ?><div class="empty-state"><h3>No bookings yet</h3><p>Explore upcoming NSW events and reserve your first experience.</p><a class="button" href="<?= e(url('events.php')) ?>">Browse events</a></div><?php endif; ?>
    </section>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
