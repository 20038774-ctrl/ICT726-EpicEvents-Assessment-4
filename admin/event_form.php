<?php
declare(strict_types=1);

require __DIR__ . '/../includes/bootstrap.php';
require_admin();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT)
    ?: filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$event = [
    'title' => '',
    'category' => '',
    'event_date' => '',
    'start_time' => '',
    'location' => '',
    'capacity' => '',
    'price' => '0.00',
    'image_url' => '',
    'description' => '',
    'status' => 'draft',
];

if ($id) {
    $stmt = db()->prepare('SELECT * FROM events WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $stored = $stmt->fetch();
    if (!$stored) {
        http_response_code(404);
        require __DIR__ . '/../404.php';
        exit;
    }
    $event = $stored;
}

$errors = [];
if (is_post()) {
    verify_csrf();

    $editableFields = [
        'title', 'category', 'event_date', 'start_time', 'location',
        'capacity', 'price', 'image_url', 'description', 'status',
    ];
    foreach ($editableFields as $field) {
        $event[$field] = isset($_POST[$field]) && is_string($_POST[$field])
            ? trim($_POST[$field])
            : '';
    }

    $capacity = filter_input(INPUT_POST, 'capacity', FILTER_VALIDATE_INT);
    $price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);

    if (mb_strlen($event['title']) < 4 || mb_strlen($event['title']) > 160) {
        $errors['title'] = 'Use 4–160 characters.';
    }
    if (mb_strlen($event['category']) < 2 || mb_strlen($event['category']) > 60) {
        $errors['category'] = 'Enter a category between 2 and 60 characters.';
    }
    if (!in_array($event['status'], ['draft', 'published', 'archived'], true)) {
        $errors['status'] = 'Choose a valid status.';
    }
    if (!valid_date($event['event_date'])) {
        $errors['event_date'] = 'Choose a valid date.';
    } elseif ($event['status'] === 'published' && $event['event_date'] < date('Y-m-d')) {
        $errors['event_date'] = 'A published event cannot be in the past.';
    }
    if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $event['start_time'])) {
        $errors['start_time'] = 'Choose a valid time.';
    }
    if (mb_strlen($event['location']) < 3 || mb_strlen($event['location']) > 160) {
        $errors['location'] = 'Use 3–160 characters.';
    }
    if (!is_int($capacity) || $capacity < 1 || $capacity > 100000) {
        $errors['capacity'] = 'Capacity must be 1–100,000.';
    }
    if (!is_float($price) || $price < 0 || $price > 100000) {
        $errors['price'] = 'Enter a price from 0 to 100,000.';
    }
    $imageScheme = parse_url($event['image_url'], PHP_URL_SCHEME);
    $imageHost = parse_url($event['image_url'], PHP_URL_HOST);
    if (!filter_var($event['image_url'], FILTER_VALIDATE_URL) || $imageScheme !== 'https' || !$imageHost) {
        $errors['image_url'] = 'Enter a complete HTTPS image URL.';
    }
    if (mb_strlen($event['description']) < 40 || mb_strlen($event['description']) > 3000) {
        $errors['description'] = 'Use 40–3,000 characters.';
    }

    if (!$errors) {
        $values = [
            'title' => $event['title'],
            'category' => $event['category'],
            'event_date' => $event['event_date'],
            'start_time' => $event['start_time'],
            'location' => $event['location'],
            'capacity' => $capacity,
            'price' => $price,
            'image_url' => $event['image_url'],
            'description' => $event['description'],
            'status' => $event['status'],
        ];

        $pdo = db();
        try {
            if ($id) {
                // Serialize capacity edits with bookings by locking the event row.
                $pdo->beginTransaction();
                $lock = $pdo->prepare('SELECT id FROM events WHERE id = :id FOR UPDATE');
                $lock->execute(['id' => $id]);
                if (!$lock->fetchColumn()) {
                    throw new RuntimeException('missing');
                }

                $bookedStmt = $pdo->prepare(
                    "SELECT COALESCE(SUM(quantity), 0)
                     FROM bookings
                     WHERE event_id = :id AND status IN ('pending', 'confirmed')"
                );
                $bookedStmt->execute(['id' => $id]);
                $alreadyBooked = (int) $bookedStmt->fetchColumn();
                if ($capacity < $alreadyBooked) {
                    throw new RuntimeException('capacity');
                }

                $values['id'] = $id;
                $stmt = $pdo->prepare(
                    'UPDATE events
                     SET title = :title, category = :category, event_date = :event_date,
                         start_time = :start_time, location = :location, capacity = :capacity,
                         price = :price, image_url = :image_url, description = :description,
                         status = :status
                     WHERE id = :id'
                );
                $stmt->execute($values);
                $pdo->commit();
            } else {
                $values['created_by'] = current_user()['id'];
                $stmt = $pdo->prepare(
                    'INSERT INTO events
                        (title, category, event_date, start_time, location, capacity,
                         price, image_url, description, status, created_by)
                     VALUES
                        (:title, :category, :event_date, :start_time, :location, :capacity,
                         :price, :image_url, :description, :status, :created_by)'
                );
                $stmt->execute($values);
            }

            flash('success', $id ? 'Event updated.' : 'Event created.');
            redirect('admin/index.php');
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            if ($exception instanceof RuntimeException && $exception->getMessage() === 'capacity') {
                $errors['capacity'] = 'Capacity cannot be lower than the active booked places.';
            } elseif ($exception instanceof RuntimeException && $exception->getMessage() === 'missing') {
                $errors['form'] = 'The event no longer exists.';
            } else {
                error_log('Event save failed: ' . $exception->getMessage());
                $errors['form'] = 'The event could not be saved. Please try again.';
            }
        }
    }
}

$pageTitle = $id ? 'Edit event' : 'Create event';
$metaDescription = 'EpicEvents event administration form.';
$robots = 'noindex, nofollow';
require __DIR__ . '/../includes/header.php';
?>
<main id="main-content" class="page-shell narrow">
    <header class="page-intro">
        <p class="eyebrow">Administration</p>
        <h1><?= $id ? 'Edit event' : 'Create an event' ?></h1>
        <p>Accurate event details support customer trust, search visibility and accessible planning.</p>
    </header>
    <section class="form-card wide">
        <?= error_summary($errors) ?>
        <form method="post" action="<?= e(url('admin/event_form.php')) ?>" data-validate novalidate>
            <?= csrf_field() ?>
            <?php if ($id): ?><input type="hidden" name="id" value="<?= (int) $id ?>"><?php endif; ?>
            <div class="form-grid">
                <div class="field span-2">
                    <label for="title">Event title</label>
                    <input id="title" name="title" value="<?= e($event['title']) ?>" minlength="4" maxlength="160" required<?= field_error_attributes('title', $errors) ?>>
                    <?php if (isset($errors['title'])): ?><span class="field-error" id="title-error"><?= e($errors['title']) ?></span><?php endif; ?>
                </div>
                <div class="field">
                    <label for="category">Category</label>
                    <input id="category" name="category" value="<?= e($event['category']) ?>" minlength="2" maxlength="60" required<?= field_error_attributes('category', $errors) ?>>
                    <?php if (isset($errors['category'])): ?><span class="field-error" id="category-error"><?= e($errors['category']) ?></span><?php endif; ?>
                </div>
                <div class="field">
                    <label for="location">Location</label>
                    <input id="location" name="location" value="<?= e($event['location']) ?>" minlength="3" maxlength="160" required<?= field_error_attributes('location', $errors) ?>>
                    <?php if (isset($errors['location'])): ?><span class="field-error" id="location-error"><?= e($errors['location']) ?></span><?php endif; ?>
                </div>
                <div class="field">
                    <label for="event_date">Date</label>
                    <input id="event_date" name="event_date" type="date" value="<?= e($event['event_date']) ?>" required<?= field_error_attributes('event_date', $errors) ?>>
                    <?php if (isset($errors['event_date'])): ?><span class="field-error" id="event_date-error"><?= e($errors['event_date']) ?></span><?php endif; ?>
                </div>
                <div class="field">
                    <label for="start_time">Start time</label>
                    <input id="start_time" name="start_time" type="time" value="<?= e(substr((string) $event['start_time'], 0, 5)) ?>" required<?= field_error_attributes('start_time', $errors) ?>>
                    <?php if (isset($errors['start_time'])): ?><span class="field-error" id="start_time-error"><?= e($errors['start_time']) ?></span><?php endif; ?>
                </div>
                <div class="field">
                    <label for="capacity">Capacity</label>
                    <input id="capacity" name="capacity" type="number" min="1" max="100000" value="<?= e((string) $event['capacity']) ?>" required<?= field_error_attributes('capacity', $errors) ?>>
                    <?php if (isset($errors['capacity'])): ?><span class="field-error" id="capacity-error"><?= e($errors['capacity']) ?></span><?php endif; ?>
                </div>
                <div class="field">
                    <label for="price">Price in AUD</label>
                    <input id="price" name="price" type="number" min="0" max="100000" step="0.01" value="<?= e((string) $event['price']) ?>" required<?= field_error_attributes('price', $errors) ?>>
                    <?php if (isset($errors['price'])): ?><span class="field-error" id="price-error"><?= e($errors['price']) ?></span><?php endif; ?>
                </div>
                <div class="field span-2">
                    <label for="image_url">Image URL using HTTPS</label>
                    <input id="image_url" name="image_url" type="url" value="<?= e($event['image_url']) ?>" required<?= field_error_attributes('image_url', $errors) ?>>
                    <?php if (isset($errors['image_url'])): ?><span class="field-error" id="image_url-error"><?= e($errors['image_url']) ?></span><?php endif; ?>
                </div>
                <div class="field span-2">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="7" minlength="40" maxlength="3000" required<?= field_error_attributes('description', $errors) ?>><?= e($event['description']) ?></textarea>
                    <?php if (isset($errors['description'])): ?><span class="field-error" id="description-error"><?= e($errors['description']) ?></span><?php endif; ?>
                </div>
                <div class="field">
                    <label for="status">Publication status</label>
                    <select id="status" name="status"<?= field_error_attributes('status', $errors) ?>>
                        <option value="draft" <?= $event['status'] === 'draft' ? 'selected' : '' ?>>Draft</option>
                        <option value="published" <?= $event['status'] === 'published' ? 'selected' : '' ?>>Published</option>
                        <option value="archived" <?= $event['status'] === 'archived' ? 'selected' : '' ?>>Archived</option>
                    </select>
                    <?php if (isset($errors['status'])): ?><span class="field-error" id="status-error"><?= e($errors['status']) ?></span><?php endif; ?>
                </div>
            </div>
            <div class="button-row">
                <button class="button button-primary" type="submit"><?= $id ? 'Save changes' : 'Create event' ?></button>
                <a class="button button-secondary" href="<?= e(url('admin/index.php')) ?>">Cancel</a>
            </div>
        </form>
    </section>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>
