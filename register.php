<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
if (is_logged_in()) redirect('dashboard.php');

$errors = [];
if (is_post()) {
    verify_csrf();
    $name = trim((string) ($_POST['name'] ?? ''));
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');
    $confirm = (string) ($_POST['password_confirm'] ?? '');
    $privacy = isset($_POST['privacy']);

    if (mb_strlen($name) < 2 || mb_strlen($name) > 80) $errors['name'] = 'Enter a name between 2 and 80 characters.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190) $errors['email'] = 'Enter a valid email address.';
    if (strlen($password) < 10 || !preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/\d/', $password)) $errors['password'] = 'Use at least 10 characters with uppercase, lowercase and a number.';
    if ($password !== $confirm) $errors['password_confirm'] = 'The passwords do not match.';
    if (!$privacy) $errors['privacy'] = 'You must acknowledge the privacy notice.';

    if (!$errors) {
        try {
            $stmt = db()->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (:name, :email, :password, 'member')");
            $stmt->execute(['name' => $name, 'email' => $email, 'password' => password_hash($password, PASSWORD_DEFAULT)]);
            session_regenerate_id(true);
            $_SESSION['user'] = ['id' => (int) db()->lastInsertId(), 'name' => $name, 'email' => $email, 'role' => 'member'];
            flash('success', 'Welcome to EpicEvents. Your account is ready.');
            redirect('dashboard.php');
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') $errors['email'] = 'An account already uses this email address.';
            else { error_log($exception->getMessage()); $errors['form'] = 'We could not create your account. Please try again.'; }
        }
    }
}
$pageTitle = 'Create an account';
$metaDescription = 'Create a secure EpicEvents member account to reserve and manage NSW event tickets.';
$robots = 'noindex, nofollow';
require __DIR__ . '/includes/header.php';
?>
<main id="main-content" class="auth-shell">
    <section class="auth-copy"><p class="eyebrow">Member access</p><h1>Your next event starts here.</h1><p>Create one account to reserve places, review bookings and receive clear event information.</p><ul><li>Secure password protection</li><li>Your bookings in one place</li><li>No payment details stored</li></ul></section>
    <section class="form-card" aria-labelledby="register-title"><h2 id="register-title">Create account</h2><p>Already registered? <a href="<?= e(url('login.php')) ?>">Log in</a>.</p>
        <?= error_summary($errors) ?>
        <form method="post" action="<?= e(url('register.php')) ?>" data-validate novalidate>
            <?= csrf_field() ?>
            <div class="field"><label for="name">Full name</label><input id="name" name="name" type="text" value="<?= old('name') ?>" autocomplete="name" minlength="2" maxlength="80" required<?= field_error_attributes('name', $errors) ?>><?php if (isset($errors['name'])): ?><span class="field-error" id="name-error"><?= e($errors['name']) ?></span><?php endif; ?></div>
            <div class="field"><label for="email">Email address</label><input id="email" name="email" type="email" value="<?= old('email') ?>" autocomplete="email" maxlength="190" required<?= field_error_attributes('email', $errors) ?>><?php if (isset($errors['email'])): ?><span class="field-error" id="email-error"><?= e($errors['email']) ?></span><?php endif; ?></div>
            <div class="field"><label for="password">Password</label><input id="password" name="password" type="password" autocomplete="new-password" minlength="10" required<?= field_error_attributes('password', $errors, ['password-help']) ?>><span class="field-help" id="password-help">10+ characters with uppercase, lowercase and a number.</span><?php if (isset($errors['password'])): ?><span class="field-error" id="password-error"><?= e($errors['password']) ?></span><?php endif; ?></div>
            <div class="field"><label for="password_confirm">Confirm password</label><input id="password_confirm" name="password_confirm" type="password" autocomplete="new-password" required<?= field_error_attributes('password_confirm', $errors) ?>><?php if (isset($errors['password_confirm'])): ?><span class="field-error" id="password_confirm-error"><?= e($errors['password_confirm']) ?></span><?php endif; ?></div>
            <label class="check-row" for="privacy"><input id="privacy" type="checkbox" name="privacy" value="1" required<?= field_error_attributes('privacy', $errors) ?> <?= isset($_POST['privacy']) ? 'checked' : '' ?>><span>I have read the <a href="<?= e(url('privacy.php')) ?>" target="_blank" rel="noopener">privacy notice</a>.</span></label><?php if (isset($errors['privacy'])): ?><span class="field-error" id="privacy-error"><?= e($errors['privacy']) ?></span><?php endif; ?>
            <button class="button button-primary button-full" type="submit">Create account</button>
        </form>
    </section>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
