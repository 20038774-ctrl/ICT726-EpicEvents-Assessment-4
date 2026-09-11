# EpicEvents — ICT726 Assessment 4

A secure, data-driven event management website built with PHP 8, MySQL 8, HTML5, CSS3 and vanilla JavaScript. This project extends the Assessment 3 static site into a complete dynamic application.

## Quick setup with XAMPP or MAMP

1. Copy this folder into `htdocs` and name it `epicevents`.
2. Start Apache and MySQL.
3. Open phpMyAdmin, select **Import**, and import `database/schema.sql`.
4. Open `config/config.php` and confirm the database settings. XAMPP commonly uses user `root` with a blank password.
5. Confirm `base_url` is `http://localhost/epicevents`.
6. Visit `http://localhost/epicevents/`.

For MAMP, the usual MySQL port is `8889` and the default password may be `root`. Update only the corresponding values in `config/config.php`.

## Administrator setup

Fixed passwords are intentionally not published. Register a standard account through the website, then promote the chosen administrator account in phpMyAdmin:

```sql
UPDATE users SET role = 'admin' WHERE email = 'your-admin-email@example.com';
```

Replace the example address with the registered email. Log out and back in to refresh the session role. This approach preserves secure runtime password hashing without exposing reusable credentials in the repository.

## Feature walkthrough

1. Browse and search event records loaded dynamically from MySQL.
2. Register a member account and sign in securely.
3. Reserve tickets and review or cancel the booking from the member dashboard.
4. Use an administrator account to create, edit, archive or delete eligible events and manage booking and enquiry statuses.
5. Open `/admin/` as a member to verify server-side role enforcement.
6. Review the responsive navigation, keyboard focus, privacy notice, metadata and Event JSON-LD.

## Project structure

| Path | Purpose |
|---|---|
| `includes/` | Shared database, security, authentication and layout code |
| `admin/` | Role-protected event, booking and enquiry management |
| `database/schema.sql` | MySQL schema, relationships, indexes and sample data |
| `assets/js/app.js` | Mobile navigation, accessible client validation and confirmations |
| `docs/` | Project presentation guide |
| `legacy_static/` | Original Assessment 3 files retained for comparison |

## Security

All write operations use POST and CSRF tokens. SQL uses PDO prepared statements, passwords use `password_hash()` and `password_verify()` with rehash-on-login, login regenerates the session identifier, sessions use strict mode and expire after inactivity, and dynamic output is HTML encoded. Administrator routes enforce role checks on the server. Database constraints reinforce numeric rules, while transactional event deletion rejects published events and records with booking history. Errors are logged without exposing database details to visitors.

## Accessibility and SEO

The interface uses semantic HTML, keyboard-accessible controls, visible focus styles, responsive layouts, accessible validation feedback and a skip link. Search-engine support includes page-specific metadata, canonical URLs, `robots.txt`, a generated sitemap and structured Event data.
