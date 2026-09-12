<?php
declare(strict_types=1);

require __DIR__ . '/../includes/bootstrap.php';
require_admin();

if (!is_post()) {
    http_response_code(405);
    exit('Method not allowed');
}

verify_csrf();
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$status = isset($_POST['status']) && is_string($_POST['status']) ? $_POST['status'] : '';
$allowedStatuses = ['pending', 'confirmed', 'cancelled'];

if (!$id || !in_array($status, $allowedStatuses, true)) {
    flash('error', 'Invalid booking update.');
    redirect('admin/index.php');
}

$pdo = db();
try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare(
        'SELECT b.event_id, b.quantity, b.status, e.capacity
         FROM bookings b
         JOIN events e ON e.id = b.event_id
         WHERE b.id = :id
         FOR UPDATE'
    );
    $stmt->execute(['id' => $id]);
    $booking = $stmt->fetch();
    if (!$booking) {
        throw new RuntimeException('missing');
    }

    // Re-check availability when an administrator reactivates a cancelled booking.
    if (in_array($status, ['pending', 'confirmed'], true) && $booking['status'] === 'cancelled') {
        $used = $pdo->prepare(
            "SELECT COALESCE(SUM(quantity), 0)
             FROM bookings
             WHERE event_id = :event_id AND status IN ('pending', 'confirmed')"
        );
        $used->execute(['event_id' => $booking['event_id']]);
        if ((int) $used->fetchColumn() + (int) $booking['quantity'] > (int) $booking['capacity']) {
            throw new RuntimeException('capacity');
        }
    }

    $update = $pdo->prepare('UPDATE bookings SET status = :status WHERE id = :id');
    $update->execute(['status' => $status, 'id' => $id]);
    $pdo->commit();
    flash('success', 'Booking status updated.');
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    if ($exception instanceof RuntimeException && $exception->getMessage() === 'capacity') {
        $message = 'Not enough capacity to reactivate this booking.';
    } elseif ($exception instanceof RuntimeException && $exception->getMessage() === 'missing') {
        $message = 'The booking was not found.';
    } else {
        error_log('Booking status update failed: ' . $exception->getMessage());
        $message = 'The booking could not be updated. Please try again.';
    }
    flash('error', $message);
}

redirect('admin/index.php');
