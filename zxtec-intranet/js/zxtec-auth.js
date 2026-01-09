(function() {
  function setMessage(container, message, state) {
    if (!container) return;
    container.textContent = message || '';
    container.classList.remove('is-error', 'is-success');
    if (state) {
      container.classList.add(state);
    }
  }

  function toggleForm(root, target) {
    const forms = root.querySelectorAll('.zxtec-auth-form');
    forms.forEach(function(form) {
      form.classList.toggle('is-active', form.getAttribute('data-auth-form') === target);
    });
  }

  function handleSubmit(form, ajaxUrl) {
    const message = form.querySelector('.zxtec-auth-message');
    const button = form.querySelector('button[type="submit"]');
    const formData = new FormData(form);

    setMessage(message, '', null);
    if (button) {
      button.disabled = true;
      button.dataset.originalText = button.textContent;
      button.textContent = 'Processando...';
    }

    fetch(ajaxUrl, {
      method: 'POST',
      credentials: 'same-origin',
      body: formData
    })
      .then(function(response) {
        return response.json();
      })
      .then(function(payload) {
        if (!payload || typeof payload.success === 'undefined') {
          throw new Error('Resposta invalida');
        }

        if (!payload.success) {
          setMessage(message, payload.data && payload.data.message ? payload.data.message : 'Nao foi possivel concluir.', 'is-error');
          return;
        }

        setMessage(message, payload.data && payload.data.message ? payload.data.message : 'Pronto!', 'is-success');
        if (payload.data && payload.data.redirect) {
          window.location.href = payload.data.redirect;
        }
      })
      .catch(function() {
        setMessage(message, 'Nao foi possivel conectar. Tente novamente.', 'is-error');
      })
      .finally(function() {
        if (button) {
          button.disabled = false;
          button.textContent = button.dataset.originalText || button.textContent;
        }
      });
  }

  function initAuth(root) {
    const ajaxUrl = root.getAttribute('data-ajax-url');
    if (!ajaxUrl) return;

    root.querySelectorAll('[data-auth-toggle]').forEach(function(toggle) {
      toggle.addEventListener('click', function() {
        toggleForm(root, toggle.getAttribute('data-auth-toggle'));
      });
    });

    root.querySelectorAll('.zxtec-auth-form-element').forEach(function(form) {
      form.addEventListener('submit', function(event) {
        event.preventDefault();
        handleSubmit(form, ajaxUrl);
      });
    });
  }

  if (document.readyState !== 'loading') {
    document.querySelectorAll('.zxtec-auth').forEach(initAuth);
  } else {
    document.addEventListener('DOMContentLoaded', function() {
      document.querySelectorAll('.zxtec-auth').forEach(initAuth);
    });
  }
})();
