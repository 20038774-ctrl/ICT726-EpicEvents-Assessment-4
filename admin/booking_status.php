<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php'; require_admin();
if (!is_post()) { http_response_code(405); exit('Method not allowed'); }
verify_csrf(); $id=filter_input(INPUT_POST,'id',FILTER_VALIDATE_INT); $status=$_POST['status']??'';
if (!$id || !in_array($status,['pending','confirmed','cancelled'],true)) { flash('error','Invalid booking update.'); redirect('admin/index.php'); }
$pdo=db();
try {
    $pdo->beginTransaction();
    $stmt=$pdo->prepare('SELECT b.event_id,b.quantity,b.status,e.capacity FROM bookings b JOIN events e ON e.id=b.event_id WHERE b.id=:id FOR UPDATE');
    $stmt->execute(['id'=>$id]); $booking=$stmt->fetch();
    if (!$booking) throw new RuntimeException('missing');
    if (in_array($status,['pending','confirmed'],true) && $booking['status']==='cancelled') {
        $used=$pdo->prepare("SELECT COALESCE(SUM(quantity),0) FROM bookings WHERE event_id=:event_id AND status IN ('pending','confirmed')");
        $used->execute(['event_id'=>$booking['event_id']]);
        if ((int)$used->fetchColumn()+(int)$booking['quantity']>(int)$booking['capacity']) throw new RuntimeException('capacity');
    }
    $update=$pdo->prepare('UPDATE bookings SET status=:status WHERE id=:id'); $update->execute(['status'=>$status,'id'=>$id]);
    $pdo->commit(); flash('success','Booking status updated.');
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    flash('error',$exception instanceof RuntimeException && $exception->getMessage()==='capacity'?'Not enough capacity to reactivate this booking.':'Booking update failed.');
}
redirect('admin/index.php');
