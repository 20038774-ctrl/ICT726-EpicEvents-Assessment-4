<?php
declare(strict_types=1);

$config = require __DIR__ . '/../config/config.php';

ini_set('display_errors', $config['environment'] === 'development' ? '1' : '0');
error_reporting(E_ALL);

if (session_status() !== PHP_SESSION_ACTIVE) {
    $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_name('epicevents_session');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

if (isset($_SESSION['last_activity']) && time() - (int) $_SESSION['last_activity'] > 1800) {
    session_unset();
    session_destroy();
    session_start();
}
$_SESSION['last_activity'] = time();

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');
header("Permissions-Policy: camera=(), microphone=(), geolocation=()");

function db(): PDO
{
    static $pdo = null;
    global $config;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $db = $config['db'];
    $dsn = "mysql:host={$db['host']};port={$db['port']};dbname={$db['name']};charset={$db['charset']}";
    try {
        $pdo = new PDO($dsn, $db['user'], $db['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $exception) {
        error_log('Database connection failed: ' . $exception->getMessage());
        http_response_code(500);
        exit('The service is temporarily unavailable. Please try again later.');
    }
    return $pdo;
}

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = ''): string
{
    global $config;
    return rtrim($config['base_url'], '/') . '/' . ltrim($path, '/');
}

function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $submitted = $_POST['csrf_token'] ?? '';
    if (!is_string($submitted) || !hash_equals(csrf_token(), $submitted)) {
        http_response_code(419);
        exit('Your session expired. Please return to the form and try again.');
    }
}

function current_user(): ?array
{
    return isset($_SESSION['user']) && is_array($_SESSION['user']) ? $_SESSION['user'] : null;
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function is_admin(): bool
{
    return (current_user()['role'] ?? '') === 'admin';
}

function require_login(): void
{
    if (!is_logged_in()) {
        flash('error', 'Please log in to continue.');
        $candidate = (string) ($_SERVER['REQUEST_URI'] ?? '');
        $_SESSION['intended_url'] = str_starts_with($candidate, '/') && !str_starts_with($candidate, '//')
            ? $candidate
            : url('dashboard.php');
        redirect('login.php');
    }
    header('Cache-Control: no-store, private');
}

function require_admin(): void
{
    require_login();
    if (!is_admin()) {
        http_response_code(403);
        $robots = 'noindex, nofollow';
        require __DIR__ . '/header.php';
        echo '<main class="page-shell narrow"><section class="empty-state"><p class="eyebrow">403</p><h1>Access denied</h1><p>This area is available to administrators only.</p><a class="button" href="' . e(url('dashboard.php')) . '">Return to dashboard</a></section></main>';
        require __DIR__ . '/footer.php';
        exit;
    }
}

function error_summary(array $errors): string
{
    if (!$errors) return '';
    $items = '';
    foreach ($errors as $field => $message) {
        $label = e((string) $message);
        $items .= $field === 'form'
            ? '<li>' . $label . '</li>'
            : '<li><a href="#' . e((string) $field) . '">' . $label . '</a></li>';
    }
    return '<div class="error-summary" role="alert" tabindex="-1"><h2>Please correct the following</h2><ul>' . $items . '</ul></div>';
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function pull_flashes(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return is_array($messages) ? $messages : [];
}

function old(string $key, string $fallback = ''): string
{
    return e((string) ($_POST[$key] ?? $fallback));
}

function valid_date(string $value): bool
{
    $date = DateTime::createFromFormat('Y-m-d', $value);
    return $date !== false && $date->format('Y-m-d') === $value;
}

function client_ip_hash(): string
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    return hash('sha256', $ip . 'epicevents-contact-salt');
}
