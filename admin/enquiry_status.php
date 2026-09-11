<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php'; require_admin();
if (!is_post()) { http_response_code(405); exit('Method not allowed'); }
verify_csrf(); $id=filter_input(INPUT_POST,'id',FILTER_VALIDATE_INT); $status=$_POST['status']??'';
if ($id && in_array($status,['new','in_progress','resolved'],true)) { $stmt=db()->prepare('UPDATE enquiries SET status=:status WHERE id=:id'); $stmt->execute(['status'=>$status,'id'=>$id]); flash('success','Enquiry status updated.'); }
else flash('error','Invalid enquiry update.'); redirect('admin/index.php');
