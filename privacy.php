<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
$pageTitle = 'Privacy notice';
$metaDescription = 'EpicEvents privacy notice explaining what personal data we collect, why we use it and how it is protected.';
require __DIR__ . '/includes/header.php';
?>
<main id="main-content" class="page-shell narrow legal-copy"><header class="page-intro"><p class="eyebrow">Last updated 11 September 2026</p><h1>Privacy notice</h1><p>We use the minimum information needed to provide accounts, bookings and customer support.</p></header>
<section><h2>What we collect</h2><p>Your name and email when you create an account; booking quantity and accessibility notes when you reserve; and contact details and message when you send an enquiry. We do not store payment card details in this educational prototype.</p></section>
<section><h2>How we use it</h2><p>We use account data to authenticate you, booking data to manage event capacity, and enquiry data to respond to your request. We do not sell personal information or use it for unrelated advertising.</p></section>
<section><h2>How we protect it</h2><p>Passwords are stored as one-way hashes. Prepared database queries, access controls, CSRF tokens, session timeouts and output encoding reduce common security risks. Access to administration records is role restricted.</p></section>
<section><h2>Retention and choices</h2><p>Enquiries are retained only while required for follow-up. Registered members may review or cancel their bookings through the dashboard. To request correction or deletion, email <a href="mailto:privacy@epicevents.com.au">privacy@epicevents.com.au</a>.</p></section>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
