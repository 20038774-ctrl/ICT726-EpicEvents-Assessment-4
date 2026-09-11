<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php'; require_admin();
if (!is_post()) { http_response_code(405); exit('Method not allowed'); }
verify_csrf(); $id=filter_input(INPUT_POST,'id',FILTER_VALIDATE_INT);
if ($id) { $stmt=db()->prepare("UPDATE events SET status='archived' WHERE id=:id"); $stmt->execute(['id'=>$id]); flash('success','Event archived.'); }
redirect('admin/index.php');
