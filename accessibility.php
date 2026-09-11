<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
$pageTitle = 'Accessibility statement';
$metaDescription = 'Accessibility features and feedback contact for the EpicEvents website.';
require __DIR__ . '/includes/header.php';
?>
<main id="main-content" class="page-shell narrow legal-copy"><header class="page-intro"><p class="eyebrow">Our commitment</p><h1>Accessibility statement</h1><p>We aim to make EpicEvents usable for people with diverse access needs and technologies.</p></header>
<section><h2>Measures included</h2><p>The site uses semantic landmarks, logical headings, visible keyboard focus, a skip link, labelled controls, meaningful status messages, sufficient colour contrast and responsive layouts. Forms explain errors in text and never rely on colour alone.</p></section>
<section><h2>Compatibility</h2><p>Pages are designed for current versions of major browsers, keyboard navigation and screen readers. Content remains readable when text is enlarged.</p></section>
<section><h2>Feedback</h2><p>If something prevents access, email <a href="mailto:access@epicevents.com.au">access@epicevents.com.au</a> or use our <a href="<?= e(url('contact.php')) ?>">contact form</a>. Include the page and difficulty encountered so we can investigate.</p></section>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
