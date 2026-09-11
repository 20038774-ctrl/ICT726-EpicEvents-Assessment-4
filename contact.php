<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
$errors = [];
if (is_post()) {
    verify_csrf();
    $name = trim((string) ($_POST['name'] ?? ''));
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $subject = trim((string) ($_POST['subject'] ?? ''));
    $message = trim((string) ($_POST['message'] ?? ''));
    $honeypot = trim((string) ($_POST['website'] ?? ''));
    if ($honeypot !== '') { redirect('contact.php'); }
    if (mb_strlen($name) < 2 || mb_strlen($name) > 80) $errors['name'] = 'Enter your full name.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190) $errors['email'] = 'Enter a valid email address.';
    if (mb_strlen($subject) < 3 || mb_strlen($subject) > 120) $errors['subject'] = 'Enter a subject between 3 and 120 characters.';
    if (mb_strlen($message) < 20 || mb_strlen($message) > 2000) $errors['message'] = 'Enter a message between 20 and 2,000 characters.';
    if (!$errors) {
        $stmt = db()->prepare('INSERT INTO enquiries (user_id, name, email, subject, message, ip_hash) VALUES (:user_id, :name, :email, :subject, :message, :ip_hash)');
        $stmt->execute(['user_id' => current_user()['id'] ?? null, 'name' => $name, 'email' => $email, 'subject' => $subject, 'message' => $message, 'ip_hash' => client_ip_hash()]);
        flash('success', 'Thanks—your enquiry has been received. We will respond within two business days.');
        redirect('contact.php');
    }
}
$pageTitle = 'Contact our NSW event planners';
$metaDescription = 'Contact EpicEvents about bookings, accessibility or event planning across Sydney and regional NSW.';
$defaults = current_user() ?? [];
require __DIR__ . '/includes/header.php';
?>
<main id="main-content" class="contact-layout page-shell">
    <section class="contact-copy"><p class="eyebrow">Talk with the team</p><h1>Let’s shape something memorable.</h1><p>Ask about an event, tell us about access requirements or begin planning a new experience.</p><div class="contact-details"><p><strong>Email</strong><a href="mailto:inquire@epicevents.com.au">inquire@epicevents.com.au</a></p><p><strong>Response time</strong><span>Within two business days</span></p><p><strong>Service area</strong><span>Sydney and regional NSW</span></p></div></section>
    <section class="form-card" aria-labelledby="contact-title"><h2 id="contact-title">Send an enquiry</h2><p>Fields marked * are required.</p>
        <?= error_summary($errors) ?>
        <form method="post" action="<?= e(url('contact.php')) ?>" data-validate novalidate>
            <?= csrf_field() ?><div class="honeypot" aria-hidden="true"><label for="website">Website</label><input id="website" name="website" type="text" tabindex="-1" autocomplete="off"></div>
            <div class="field"><label for="name">Full name *</label><input id="name" name="name" type="text" value="<?= old('name', $defaults['name'] ?? '') ?>" minlength="2" maxlength="80" required aria-describedby="name-error" <?= isset($errors['name']) ? 'aria-invalid="true"' : '' ?>><?php if (isset($errors['name'])): ?><span class="field-error" id="name-error"><?= e($errors['name']) ?></span><?php endif; ?></div>
            <div class="field"><label for="email">Email address *</label><input id="email" name="email" type="email" value="<?= old('email', $defaults['email'] ?? '') ?>" maxlength="190" required aria-describedby="email-error" <?= isset($errors['email']) ? 'aria-invalid="true"' : '' ?>><?php if (isset($errors['email'])): ?><span class="field-error" id="email-error"><?= e($errors['email']) ?></span><?php endif; ?></div>
            <div class="field"><label for="subject">Subject *</label><input id="subject" name="subject" type="text" value="<?= old('subject') ?>" minlength="3" maxlength="120" required aria-describedby="subject-error" <?= isset($errors['subject']) ? 'aria-invalid="true"' : '' ?>><?php if (isset($errors['subject'])): ?><span class="field-error" id="subject-error"><?= e($errors['subject']) ?></span><?php endif; ?></div>
            <div class="field"><label for="message">How can we help? *</label><textarea id="message" name="message" rows="6" minlength="20" maxlength="2000" required aria-describedby="message-error" <?= isset($errors['message']) ? 'aria-invalid="true"' : '' ?>><?= old('message') ?></textarea><?php if (isset($errors['message'])): ?><span class="field-error" id="message-error"><?= e($errors['message']) ?></span><?php endif; ?></div>
            <p class="field-help">We use your details only to answer this enquiry. See our <a href="<?= e(url('privacy.php')) ?>">privacy notice</a>.</p>
            <button class="button button-primary" type="submit">Send enquiry</button>
        </form>
    </section>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
