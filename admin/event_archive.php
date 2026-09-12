<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php'; require_admin();
if (!is_post()) { http_response_code(405); exit('Method not allowed'); }
verify_csrf();
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    flash('error', 'Invalid event.');
    redirect('admin/index.php');
}
$stmt = db()->prepare("UPDATE events SET status='archived' WHERE id=:id AND status<>'archived'");
$stmt->execute(['id' => $id]);
flash($stmt->rowCount() ? 'success' : 'error', $stmt->rowCount() ? 'Event archived.' : 'The event was not found or was already archived.');
redirect('admin/index.php');
