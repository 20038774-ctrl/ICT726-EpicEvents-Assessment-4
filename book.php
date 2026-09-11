<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
require_login();
$eventId = filter_input(INPUT_GET, 'event_id', FILTER_VALIDATE_INT) ?: filter_input(INPUT_POST, 'event_id', FILTER_VALIDATE_INT);
if (!$eventId) { http_response_code(404); require __DIR__ . '/404.php'; exit; }

$stmt = db()->prepare("SELECT e.*, (e.capacity - COALESCE(SUM(CASE WHEN b.status IN ('pending','confirmed') THEN b.quantity ELSE 0 END), 0)) AS places_left FROM events e LEFT JOIN bookings b ON b.event_id=e.id WHERE e.id=:id AND e.status='published' AND e.event_date >= CURDATE() GROUP BY e.id");
$stmt->execute(['id' => $eventId]);
$event = $stmt->fetch();
if (!$event) { http_response_code(404); require __DIR__ . '/404.php'; exit; }
$errors = [];
if (is_post()) {
    verify_csrf();
    $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);
    $notes = trim((string) ($_POST['accessibility_notes'] ?? ''));
    if (!$quantity || $quantity < 1 || $quantity > 10) $errors['quantity'] = 'Choose between 1 and 10 tickets.';
    if ($quantity && $quantity > (int) $event['places_left']) $errors['quantity'] = 'Only ' . max(0, (int) $event['places_left']) . ' places remain.';
    if (mb_strlen($notes) > 500) $errors['accessibility_notes'] = 'Keep access notes under 500 characters.';
    if (!$errors) {
        $pdo = db();
        try {
            $pdo->beginTransaction();
            $lockStmt = $pdo->prepare('SELECT capacity FROM events WHERE id=:id FOR UPDATE');
            $lockStmt->execute(['id' => $eventId]);
            $capacity = (int) $lockStmt->fetchColumn();
            $bookedStmt = $pdo->prepare("SELECT COALESCE(SUM(quantity),0) FROM bookings WHERE event_id=:id AND status IN ('pending','confirmed')");
            $bookedStmt->execute(['id' => $eventId]);
            $placesLeft = $capacity - (int) $bookedStmt->fetchColumn();
            if ($quantity > $placesLeft) throw new RuntimeException('capacity');
            $reference = 'EE-' . strtoupper(bin2hex(random_bytes(4)));
            $insert = $pdo->prepare("INSERT INTO bookings (user_id,event_id,booking_reference,quantity,accessibility_notes,status) VALUES (:user_id,:event_id,:reference,:quantity,:notes,'confirmed')");
            $insert->execute(['user_id' => current_user()['id'], 'event_id' => $eventId, 'reference' => $reference, 'quantity' => $quantity, 'notes' => $notes !== '' ? $notes : null]);
            $pdo->commit();
            flash('success', 'Booking confirmed. Your reference is ' . $reference . '.');
            redirect('dashboard.php');
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            if ($exception instanceof RuntimeException && $exception->getMessage() === 'capacity') $errors['quantity'] = 'Availability changed while you were booking. Please choose fewer tickets.';
            else { error_log($exception->getMessage()); $errors['form'] = 'The booking could not be completed. Please try again.'; }
        }
    }
}
$pageTitle = 'Book ' . $event['title'];
$metaDescription = 'Reserve tickets for ' . $event['title'] . ' with EpicEvents.';
$robots = 'noindex, nofollow';
require __DIR__ . '/includes/header.php';
?>
<main id="main-content" class="auth-shell booking-shell">
    <section class="booking-summary"><p class="eyebrow">Your selection</p><h1><?= e($event['title']) ?></h1><dl class="event-facts"><div><dt>Date</dt><dd><?= e(date('j F Y', strtotime($event['event_date']))) ?></dd></div><div><dt>Venue</dt><dd><?= e($event['location']) ?></dd></div><div><dt>Unit price</dt><dd><?= (float) $event['price'] > 0 ? '$' . number_format((float) $event['price'], 2) : 'Free' ?></dd></div><div><dt>Available</dt><dd><?= max(0, (int) $event['places_left']) ?> places</dd></div></dl></section>
    <section class="form-card" aria-labelledby="booking-title"><h2 id="booking-title">Reserve tickets</h2><p>Booking for <?= e(current_user()['name']) ?>.</p>
        <?= error_summary($errors) ?>
        <form method="post" action="<?= e(url('book.php')) ?>" data-validate novalidate>
            <?= csrf_field() ?><input type="hidden" name="event_id" value="<?= (int) $eventId ?>">
            <div class="field"><label for="quantity">Number of tickets</label><input id="quantity" name="quantity" type="number" min="1" max="<?= min(10, max(1, (int) $event['places_left'])) ?>" value="<?= old('quantity', '1') ?>" required aria-describedby="quantity-error" <?= isset($errors['quantity']) ? 'aria-invalid="true"' : '' ?>><?php if (isset($errors['quantity'])): ?><span class="field-error" id="quantity-error"><?= e($errors['quantity']) ?></span><?php endif; ?></div>
            <div class="field"><label for="accessibility_notes">Accessibility or support needs <span>(optional)</span></label><textarea id="accessibility_notes" name="accessibility_notes" rows="4" maxlength="500" placeholder="Tell us what would help you participate comfortably." aria-describedby="accessibility-notes-error" <?= isset($errors['accessibility_notes']) ? 'aria-invalid="true"' : '' ?>><?= old('accessibility_notes') ?></textarea><?php if (isset($errors['accessibility_notes'])): ?><span class="field-error" id="accessibility-notes-error"><?= e($errors['accessibility_notes']) ?></span><?php endif; ?></div>
            <p class="field-help">No payment is processed in this educational prototype. By booking, you agree that event staff may use your details to administer this reservation.</p>
            <button class="button button-primary button-full" type="submit">Confirm reservation</button>
        </form>
    </section>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
