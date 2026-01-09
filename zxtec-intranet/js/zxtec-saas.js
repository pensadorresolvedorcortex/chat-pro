(function() {
  function initSaas(root) {
    const sidebar = root.querySelector('.zxtec-saas-sidebar');
    const navButtons = root.querySelectorAll('.zxtec-saas-nav-button');
    const sections = root.querySelectorAll('.zxtec-saas-section');
    const collapse = root.querySelector('.zxtec-saas-collapse');

    if (!navButtons.length || !sections.length) return;

    navButtons.forEach(function(button) {
      button.addEventListener('click', function() {
        const target = button.getAttribute('data-section');
        navButtons.forEach(function(item) {
          item.classList.toggle('is-active', item === button);
        });
        sections.forEach(function(section) {
          section.classList.toggle('is-active', section.getAttribute('data-section') === target);
        });
      });
    });

    if (collapse && sidebar) {
      collapse.addEventListener('click', function() {
        sidebar.classList.toggle('is-collapsed');
      });
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
