<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
if (is_logged_in()) redirect('dashboard.php');

$error = '';
if (is_post()) {
    verify_csrf();
    $attempts = $_SESSION['login_attempts'] ?? [];
    $attempts = array_values(array_filter($attempts, fn($time) => $time > time() - 900));
    if (count($attempts) >= 5) {
        $error = 'Too many attempts. Please wait 15 minutes before trying again.';
    } else {
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');
        $stmt = db()->prepare('SELECT id, name, email, password_hash, role, is_active FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();
        if ($user && (int) $user['is_active'] === 1 && password_verify($password, $user['password_hash'])) {
            if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
                $rehash = db()->prepare('UPDATE users SET password_hash = :hash WHERE id = :id');
                $rehash->execute(['hash' => password_hash($password, PASSWORD_DEFAULT), 'id' => $user['id']]);
            }
            session_regenerate_id(true);
            unset($user['password_hash'], $user['is_active']);
            $_SESSION['user'] = $user;
            $_SESSION['login_attempts'] = [];
            $destination = $_SESSION['intended_url'] ?? url('dashboard.php');
            unset($_SESSION['intended_url']);
            if (!is_string($destination) || preg_match('/[\r\n]/', $destination) || str_starts_with($destination, '//')) {
                $destination = url('dashboard.php');
            }
            header('Location: ' . $destination);
            exit;
        }
        $attempts[] = time();
        $_SESSION['login_attempts'] = $attempts;
        $error = 'The email or password is incorrect.';
    }
}
$pageTitle = 'Member login';
$metaDescription = 'Log in securely to manage your EpicEvents reservations.';
$robots = 'noindex, nofollow';
require __DIR__ . '/includes/header.php';
?>
<main id="main-content" class="auth-shell compact">
    <section class="auth-copy"><p class="eyebrow">Welcome back</p><h1>Keep every plan in reach.</h1><p>Log in to review, make or cancel your event reservations.</p></section>
    <section class="form-card" aria-labelledby="login-title"><h2 id="login-title">Log in</h2><p>New here? <a href="<?= e(url('register.php')) ?>">Create an account</a>.</p>
        <?php if ($error): ?><div class="form-error" role="alert"><?= e($error) ?></div><?php endif; ?>
        <form method="post" action="<?= e(url('login.php')) ?>" data-validate novalidate>
            <?= csrf_field() ?>
            <div class="field"><label for="email">Email address</label><input id="email" name="email" type="email" value="<?= old('email') ?>" autocomplete="email" required></div>
            <div class="field"><label for="password">Password</label><input id="password" name="password" type="password" autocomplete="current-password" required></div>
            <button class="button button-primary button-full" type="submit">Log in securely</button>
        </form>
    </section>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
