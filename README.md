# EpicEvents — ICT726 Assessment 4

A secure, data-driven event management website built with PHP 8, MySQL 8, HTML5, CSS3 and vanilla JavaScript. This project extends the submitted Assessment 3 static site into a complete dynamic application.

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
2. Register a new member; show invalid and valid form states.
3. Log in, reserve tickets and review/cancel the booking in the member dashboard.
4. Log in as administrator; create/edit/archive an event, permanently delete an eligible draft, and update booking/enquiry statuses.
5. Attempt to open `/admin/` as a member to demonstrate server-side role enforcement.
6. Show the privacy notice, keyboard focus, mobile navigation, unique metadata and Event JSON-LD.

## Structure

| Path | Purpose |
|---|---|
| `includes/` | Shared database, security, authentication and layout code |
| `admin/` | Role-protected event, booking and enquiry management |
| `database/schema.sql` | MySQL schema, relationships, indexes and sample data |
| `assets/js/app.js` | Mobile navigation, accessible client validation and confirmations |
| `tests/` | Automated static and end-to-end application checks |
| `docs/` | Presentation guidance for the Week 12 demonstration |
| `legacy_static/` | Original Assessment 3 files retained for comparison |

## Security notes

All write operations use POST and CSRF tokens. SQL uses PDO prepared statements, passwords use `password_hash()`/`password_verify()` with rehash-on-login, login regenerates the session identifier, sessions use strict mode and expire after inactivity, output is HTML encoded, and administrator routes call `require_admin()` on the server. Database checks reinforce core numeric rules. Event deletion is transactional and refuses published events or records with booking history. Errors are logged without revealing database details to visitors.

## Quality assurance

Run the dependency-free static audit with:

```bash
node tests/static_audit.mjs
```

The repository's GitHub Actions workflow additionally provisions PHP 8.3 and MySQL 8, lints every PHP file, imports the schema, starts the application and exercises public, member and administrator workflows end to end.
