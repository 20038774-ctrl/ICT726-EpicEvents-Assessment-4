document.addEventListener('DOMContentLoaded', () => {
  const toggle = document.querySelector('.nav-toggle');
  const nav = document.querySelector('#primary-nav');

  const appScript = document.querySelector('script[src*="assets/js/app.js"]');
  if (appScript) {
    const fallbackImage = new URL('../images/event-placeholder.svg', appScript.src).href;
    document.querySelectorAll('.event-card img, .event-cover').forEach((image) => {
      const showFallback = () => {
        if (image.src !== fallbackImage) image.src = fallbackImage;
      };

      image.addEventListener('error', showFallback);
      if (image.complete && image.naturalWidth === 0) showFallback();
    });
  }

  if (toggle && nav) {
    const closeMenu = () => {
      toggle.setAttribute('aria-expanded', 'false');
      nav.classList.remove('is-open');
    };

    toggle.addEventListener('click', () => {
      const open = toggle.getAttribute('aria-expanded') === 'true';
      toggle.setAttribute('aria-expanded', String(!open));
      nav.classList.toggle('is-open', !open);
    });

    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' && toggle.getAttribute('aria-expanded') === 'true') {
        closeMenu();
        toggle.focus();
      }
    });

    nav.querySelectorAll('a').forEach((link) => {
      link.addEventListener('click', closeMenu);
    });
  }

  document.querySelectorAll('[data-confirm]').forEach((button) => {
    button.addEventListener('click', (event) => {
      if (!window.confirm(button.dataset.confirm)) event.preventDefault();
    });
  });

  document.querySelectorAll('form[data-validate]').forEach((form) => {
    form.addEventListener('submit', (event) => {
      if (!form.checkValidity()) {
        event.preventDefault();
        form.classList.add('was-validated');

        form.querySelector('.client-error-summary')?.remove();
        const invalidFields = Array.from(form.querySelectorAll(':invalid'));
        const summary = document.createElement('div');
        summary.className = 'error-summary client-error-summary';
        summary.setAttribute('role', 'alert');
        summary.setAttribute('tabindex', '-1');

        const heading = document.createElement('h2');
        heading.textContent = 'Please correct the following';
        summary.appendChild(heading);

        const list = document.createElement('ul');
        invalidFields.forEach((field, index) => {
          if (!field.id) field.id = `invalid-field-${index + 1}`;
          field.setAttribute('aria-invalid', 'true');

          const label = Array.from(form.querySelectorAll('label')).find(
            (candidate) => candidate.htmlFor === field.id
          );
          const item = document.createElement('li');
          const link = document.createElement('a');
          link.href = `#${field.id}`;
          link.textContent = `${label?.textContent.trim() || field.name}: ${field.validationMessage}`;
          link.addEventListener('click', (clickEvent) => {
            clickEvent.preventDefault();
            field.focus();
          });
          item.appendChild(link);
          list.appendChild(item);
        });
        summary.appendChild(list);
        form.prepend(summary);
        summary.focus();
      }
    });

    form.querySelectorAll('input, select, textarea').forEach((field) => {
      field.addEventListener('input', () => {
        if (field.checkValidity()) field.removeAttribute('aria-invalid');
      });
    });
  });

  document.querySelector('.error-summary')?.focus();
});
