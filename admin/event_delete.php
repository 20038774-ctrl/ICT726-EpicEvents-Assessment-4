<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';
require_admin();
if (!is_post()) { http_response_code(405); exit('Method not allowed'); }
verify_csrf();
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$id) { flash('error', 'Invalid event.'); redirect('admin/index.php'); }
$pdo = db();
try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare('SELECT status FROM events WHERE id=:id FOR UPDATE');
    $stmt->execute(['id' => $id]);
    $status = $stmt->fetchColumn();
    if ($status === false || $status === 'published') throw new RuntimeException('state');
    $bookings = $pdo->prepare('SELECT COUNT(*) FROM bookings WHERE event_id=:id');
    $bookings->execute(['id' => $id]);
    if ((int) $bookings->fetchColumn() > 0) throw new RuntimeException('bookings');
    $delete = $pdo->prepare('DELETE FROM events WHERE id=:id');
    $delete->execute(['id' => $id]);
    $pdo->commit();
    flash('success', 'Event permanently deleted.');
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    if ($exception instanceof RuntimeException && $exception->getMessage() === 'bookings') {
        $message = 'Events with booking history cannot be deleted; archive them instead.';
    } elseif ($exception instanceof RuntimeException && $exception->getMessage() === 'state') {
        $message = 'Only draft or archived events can be permanently deleted.';
    } else {
        error_log('Event deletion failed: ' . $exception->getMessage());
        $message = 'The event could not be deleted. Please try again.';
    }
    flash('error', $message);
}
redirect('admin/index.php');
