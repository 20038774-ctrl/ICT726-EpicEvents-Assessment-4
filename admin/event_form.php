<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';
require_admin();
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$event = ['title'=>'','category'=>'','event_date'=>'','start_time'=>'','location'=>'','capacity'=>'','price'=>'0.00','image_url'=>'','description'=>'','status'=>'draft'];
if ($id) {
    $stmt = db()->prepare('SELECT * FROM events WHERE id=:id'); $stmt->execute(['id'=>$id]);
    $stored = $stmt->fetch();
    if (!$stored) { http_response_code(404); require __DIR__ . '/../404.php'; exit; }
    $event = $stored;
}
$errors = [];
if (is_post()) {
    verify_csrf();
    foreach (array_keys($event) as $key) if (array_key_exists($key,$_POST) && is_string($_POST[$key])) $event[$key]=trim($_POST[$key]);
    $capacity = filter_input(INPUT_POST,'capacity',FILTER_VALIDATE_INT);
    $price = filter_input(INPUT_POST,'price',FILTER_VALIDATE_FLOAT);
    if (mb_strlen($event['title'])<4 || mb_strlen($event['title'])>160) $errors['title']='Use 4–160 characters.';
    if (mb_strlen($event['category'])<2 || mb_strlen($event['category'])>60) $errors['category']='Enter a valid category.';
    if (!valid_date($event['event_date'])) $errors['event_date']='Choose a valid date.';
    elseif ($event['status']==='published' && $event['event_date'] < date('Y-m-d')) $errors['event_date']='A published event cannot be in the past.';
    if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/',$event['start_time'])) $errors['start_time']='Choose a valid time.';
    if (mb_strlen($event['location'])<3 || mb_strlen($event['location'])>160) $errors['location']='Use 3–160 characters.';
    if (!$capacity || $capacity<1 || $capacity>100000) $errors['capacity']='Capacity must be 1–100,000.';
    elseif ($id) {
        $bookedStmt=db()->prepare("SELECT COALESCE(SUM(quantity),0) FROM bookings WHERE event_id=:id AND status IN ('pending','confirmed')");
        $bookedStmt->execute(['id'=>$id]);
        $alreadyBooked=(int)$bookedStmt->fetchColumn();
        if ($capacity<$alreadyBooked) $errors['capacity']='Capacity cannot be lower than '.$alreadyBooked.' active booked places.';
    }
    if ($price===false || $price<0 || $price>100000) $errors['price']='Enter a non-negative price.';
    if (!filter_var($event['image_url'],FILTER_VALIDATE_URL) || !preg_match('/^https:\/\//',$event['image_url'])) $errors['image_url']='Enter a complete HTTPS image URL.';
    if (mb_strlen($event['description'])<40 || mb_strlen($event['description'])>3000) $errors['description']='Use 40–3,000 characters.';
    if (!in_array($event['status'],['draft','published','archived'],true)) $errors['status']='Choose a valid status.';
    if (!$errors) {
        $values=['title'=>$event['title'],'category'=>$event['category'],'event_date'=>$event['event_date'],'start_time'=>$event['start_time'],'location'=>$event['location'],'capacity'=>$capacity,'price'=>$price,'image_url'=>$event['image_url'],'description'=>$event['description'],'status'=>$event['status']];
        if ($id) { $values['id']=$id; $stmt=db()->prepare('UPDATE events SET title=:title,category=:category,event_date=:event_date,start_time=:start_time,location=:location,capacity=:capacity,price=:price,image_url=:image_url,description=:description,status=:status WHERE id=:id'); }
        else { $stmt=db()->prepare('INSERT INTO events (title,category,event_date,start_time,location,capacity,price,image_url,description,status,created_by) VALUES (:title,:category,:event_date,:start_time,:location,:capacity,:price,:image_url,:description,:status,:created_by)'); $values['created_by']=current_user()['id']; }
        $stmt->execute($values); flash('success',$id?'Event updated.':'Event created.'); redirect('admin/index.php');
    }
}
$pageTitle=$id?'Edit event':'Create event';
$metaDescription='EpicEvents event administration form.';
$robots='noindex, nofollow';
require __DIR__ . '/../includes/header.php';
?>
<main id="main-content" class="page-shell narrow"><header class="page-intro"><p class="eyebrow">Administration</p><h1><?= $id?'Edit event':'Create an event' ?></h1><p>Accurate event details support customer trust, search visibility and accessible planning.</p></header>
<section class="form-card wide"><?= error_summary($errors) ?><form method="post" action="<?= e(url('admin/event_form.php')) ?>" data-validate novalidate><?= csrf_field() ?><?php if($id): ?><input type="hidden" name="id" value="<?= (int)$id ?>"><?php endif; ?>
<div class="form-grid"><div class="field span-2"><label for="title">Event title</label><input id="title" name="title" value="<?= e($event['title']) ?>" minlength="4" maxlength="160" required><?php if(isset($errors['title'])):?><span class="field-error"><?=e($errors['title'])?></span><?php endif;?></div>
<div class="field"><label for="category">Category</label><input id="category" name="category" value="<?= e($event['category']) ?>" maxlength="60" required><?php if(isset($errors['category'])):?><span class="field-error"><?=e($errors['category'])?></span><?php endif;?></div>
<div class="field"><label for="location">Location</label><input id="location" name="location" value="<?= e($event['location']) ?>" maxlength="160" required><?php if(isset($errors['location'])):?><span class="field-error"><?=e($errors['location'])?></span><?php endif;?></div>
<div class="field"><label for="event_date">Date</label><input id="event_date" name="event_date" type="date" value="<?= e($event['event_date']) ?>" required><?php if(isset($errors['event_date'])):?><span class="field-error"><?=e($errors['event_date'])?></span><?php endif;?></div>
<div class="field"><label for="start_time">Start time</label><input id="start_time" name="start_time" type="time" value="<?= e(substr((string)$event['start_time'],0,5)) ?>" required><?php if(isset($errors['start_time'])):?><span class="field-error"><?=e($errors['start_time'])?></span><?php endif;?></div>
<div class="field"><label for="capacity">Capacity</label><input id="capacity" name="capacity" type="number" min="1" max="100000" value="<?= e((string)$event['capacity']) ?>" required><?php if(isset($errors['capacity'])):?><span class="field-error"><?=e($errors['capacity'])?></span><?php endif;?></div>
<div class="field"><label for="price">Price (AUD)</label><input id="price" name="price" type="number" min="0" max="100000" step="0.01" value="<?= e((string)$event['price']) ?>" required><?php if(isset($errors['price'])):?><span class="field-error"><?=e($errors['price'])?></span><?php endif;?></div>
<div class="field span-2"><label for="image_url">Image URL (HTTPS)</label><input id="image_url" name="image_url" type="url" value="<?= e($event['image_url']) ?>" required><?php if(isset($errors['image_url'])):?><span class="field-error"><?=e($errors['image_url'])?></span><?php endif;?></div>
<div class="field span-2"><label for="description">Description</label><textarea id="description" name="description" rows="7" minlength="40" maxlength="3000" required><?= e($event['description']) ?></textarea><?php if(isset($errors['description'])):?><span class="field-error"><?=e($errors['description'])?></span><?php endif;?></div>
<div class="field"><label for="status">Publication status</label><select id="status" name="status"><option value="draft" <?=$event['status']==='draft'?'selected':''?>>Draft</option><option value="published" <?=$event['status']==='published'?'selected':''?>>Published</option><option value="archived" <?=$event['status']==='archived'?'selected':''?>>Archived</option></select></div></div>
<div class="button-row"><button class="button button-primary" type="submit"><?= $id?'Save changes':'Create event' ?></button><a class="button button-secondary" href="<?= e(url('admin/index.php')) ?>">Cancel</a></div></form></section></main>
<?php require __DIR__ . '/../includes/footer.php'; ?>
