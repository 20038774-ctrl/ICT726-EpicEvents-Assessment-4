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

if (!$id || !in_array($status, ['new', 'in_progress', 'resolved'], true)) {
    flash('error', 'Invalid enquiry update.');
    redirect('admin/index.php');
}

try {
    $stmt = db()->prepare('UPDATE enquiries SET status = :status WHERE id = :id');
    $stmt->execute(['status' => $status, 'id' => $id]);
    flash(
        $stmt->rowCount() ? 'success' : 'error',
        $stmt->rowCount() ? 'Enquiry status updated.' : 'The enquiry was not found or already had that status.'
    );
} catch (Throwable $exception) {
    error_log('Enquiry status update failed: ' . $exception->getMessage());
    flash('error', 'The enquiry could not be updated. Please try again.');
}

redirect('admin/index.php');
