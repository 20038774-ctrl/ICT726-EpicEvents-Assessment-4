import fs from 'node:fs';
import path from 'node:path';
import process from 'node:process';

const root = path.resolve(import.meta.dirname, '..');
const failures = [];
let assertions = 0;

function check(condition, message) {
  assertions += 1;
  if (!condition) failures.push(message);
}

function read(relativePath) {
  return fs.readFileSync(path.join(root, relativePath), 'utf8');
}

function phpFiles(directory = root) {
  return fs.readdirSync(directory, { withFileTypes: true }).flatMap((entry) => {
    const fullPath = path.join(directory, entry.name);
    const relativePath = path.relative(root, fullPath);
    if (entry.isDirectory()) {
      if (['legacy_static', '.git'].includes(entry.name)) return [];
      return phpFiles(fullPath);
    }
    return entry.isFile() && entry.name.endsWith('.php') ? [relativePath] : [];
  });
}

function phpSegments(source) {
  return Array.from(source.matchAll(/<\?(?:php|=)([\s\S]*?)(?:\?>|$)/g), (match) => match[1]);
}

function balancedPhp(source, file) {
  const pairs = { ')': '(', ']': '[', '}': '{' };
  const openings = new Set(Object.values(pairs));

  for (const segment of phpSegments(source)) {
    const stack = [];
    let quote = null;
    let escaped = false;
    let lineComment = false;
    let blockComment = false;

    for (let index = 0; index < segment.length; index += 1) {
      const character = segment[index];
      const next = segment[index + 1];

      if (lineComment) {
        if (character === '\n') lineComment = false;
        continue;
      }
      if (blockComment) {
        if (character === '*' && next === '/') {
          blockComment = false;
          index += 1;
        }
        continue;
      }
      if (quote) {
        if (escaped) escaped = false;
        else if (character === '\\') escaped = true;
        else if (character === quote) quote = null;
        continue;
      }
      if (character === '/' && next === '/') {
        lineComment = true;
        index += 1;
        continue;
      }
      if (character === '/' && next === '*') {
        blockComment = true;
        index += 1;
        continue;
      }
      if (character === '#') {
        lineComment = true;
        continue;
      }
      if (character === "'" || character === '"') {
        quote = character;
        continue;
      }
      if (openings.has(character)) stack.push(character);
      else if (pairs[character] && stack.pop() !== pairs[character]) {
        failures.push(`${file}: unbalanced PHP delimiter ${character}`);
        return;
      }
    }

    check(!quote && !blockComment && stack.length === 0, `${file}: unterminated PHP string, comment or delimiter`);
  }
}

const requiredFiles = [
  'index.php', 'events.php', 'event.php', 'register.php', 'login.php',
  'logout.php', 'book.php', 'dashboard.php', 'contact.php', 'privacy.php',
  'admin/index.php', 'admin/event_form.php', 'database/schema.sql',
  'includes/bootstrap.php', 'style.css', 'assets/js/app.js',
];
requiredFiles.forEach((file) => check(fs.existsSync(path.join(root, file)), `Missing required file: ${file}`));

const php = phpFiles();
php.forEach((file) => balancedPhp(read(file), file));

const allPhp = php.map(read).join('\n');
const schema = read('database/schema.sql');
const header = read('includes/header.php');
const bootstrap = read('includes/bootstrap.php');
const privacy = read('privacy.php');

check(!/\b(mysql_query|mysqli_query)\s*\(/.test(allPhp), 'Legacy or unprepared database API detected');
check(!/password\s*=\s*['"][^'"]+['"]/i.test(allPhp), 'Hard-coded password detected');
check((allPhp.match(/<form\b/gi) || []).length >= 2, 'At least two forms are required');
check((allPhp.match(/verify_csrf\s*\(\s*\)/g) || []).length >= 8, 'Expected CSRF verification on write handlers');
check(php.filter((file) => file.startsWith('admin/') && file !== 'admin/index.php').every(
  (file) => read(file).includes('require_admin();')
), 'Every administrator handler must enforce the administrator role');
check(bootstrap.includes('PDO::ATTR_EMULATE_PREPARES => false'), 'Native PDO prepares must be enabled');
check(bootstrap.includes("session_regenerate_id(true)"), 'Session identifiers must be regenerated');
check(bootstrap.includes("'httponly' => true") && bootstrap.includes("'samesite' => 'Lax'"), 'Secure cookie attributes are incomplete');
check(bootstrap.includes('hash_equals(csrf_token(), $submitted)'), 'Constant-time CSRF comparison is missing');
check(bootstrap.includes('safe_local_target'), 'Post-login redirect validation is missing');

for (const table of ['users', 'events', 'bookings', 'enquiries']) {
  check(new RegExp(`CREATE TABLE ${table}\\b`, 'i').test(schema), `Missing database table: ${table}`);
}
check((schema.match(/FOREIGN KEY/gi) || []).length >= 4, 'Expected relational foreign keys');
check((schema.match(/\bCHECK\s*\(/gi) || []).length >= 3, 'Expected database check constraints');
check(/password_hash\s+VARCHAR\(255\)/i.test(schema), 'Password hash column is missing or too short');
check(/ENGINE=InnoDB/gi.test(schema), 'Transactional InnoDB tables are required');

check(header.includes('<meta name="description"'), 'Meta descriptions are missing');
check(header.includes('<link rel="canonical"'), 'Canonical links are missing');
check(header.includes('application/ld+json'), 'Structured data is missing');
check(read('event.php').includes("'@type'=>'Event'"), 'Event structured data is missing');
check(read('sitemap.php').includes('<urlset'), 'Dynamic XML sitemap is missing');
check(read('robots.txt').includes('Disallow: /admin/'), 'robots.txt must exclude administrator routes');

check(/<main\b[^>]*id="main-content"/.test(read('index.php')), 'Home page skip-link target is missing');
check(read('style.css').includes(':focus-visible'), 'Visible keyboard focus styling is missing');
check(read('style.css').includes('prefers-reduced-motion'), 'Reduced-motion support is missing');
check(read('assets/js/app.js').includes("event.key === 'Escape'"), 'Mobile menu Escape-key support is missing');
check(privacy.includes('What we collect') && privacy.includes('How we protect it'), 'Privacy notice is incomplete');

if (failures.length) {
  console.error(`Static audit failed: ${failures.length} of ${assertions} checks failed.`);
  failures.forEach((failure) => console.error(`- ${failure}`));
  process.exit(1);
}

console.log(`Static audit passed: ${assertions} checks across ${php.length} PHP files.`);
