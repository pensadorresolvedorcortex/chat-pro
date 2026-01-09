(function() {
  function initSaas(root) {
    const sidebar = root.querySelector('.zxtec-saas-sidebar');
    const navButtons = root.querySelectorAll('.zxtec-saas-nav-button');
    const sections = root.querySelectorAll('.zxtec-saas-section');
    const collapse = root.querySelector('.zxtec-saas-collapse');
    const chips = root.querySelectorAll('[data-section-target]');

    if (!navButtons.length || !sections.length) return;

    function activateSection(target) {
      navButtons.forEach(function(item) {
        item.classList.toggle('is-active', item.getAttribute('data-section') === target);
      });
      sections.forEach(function(section) {
        section.classList.toggle('is-active', section.getAttribute('data-section') === target);
      });
      if (target) {
        window.history.replaceState(null, '', '#'+target);
      }
    }

    navButtons.forEach(function(button) {
      button.addEventListener('click', function() {
        activateSection(button.getAttribute('data-section'));
      });
    });

    if (collapse && sidebar) {
      collapse.addEventListener('click', function() {
        sidebar.classList.toggle('is-collapsed');
      });
    }

    chips.forEach(function(chip) {
      chip.addEventListener('click', function() {
        activateSection(chip.getAttribute('data-section-target'));
      });
    });

    if (window.location.hash) {
      activateSection(window.location.hash.replace('#', ''));
    }
  }

  if (document.readyState !== 'loading') {
    document.querySelectorAll('.zxtec-saas').forEach(initSaas);
  } else {
    document.addEventListener('DOMContentLoaded', function() {
      document.querySelectorAll('.zxtec-saas').forEach(initSaas);
    });
  }
})();
