(function() {
  function initTabs(root) {
    const tabs = root.querySelectorAll('.zxtec-glass-tab');
    const panels = root.querySelectorAll('.zxtec-glass-section');
    if (!tabs.length || !panels.length) return;

    tabs.forEach(function(tab) {
      tab.addEventListener('click', function() {
        const target = tab.getAttribute('data-tab');
        tabs.forEach(function(t) { t.classList.remove('is-active'); });
        panels.forEach(function(panel) {
          panel.classList.toggle('is-active', panel.getAttribute('data-tab-panel') === target);
        });
        tab.classList.add('is-active');
      });
    });
  }

  if (document.readyState !== 'loading') {
    document.querySelectorAll('.zxtec-glass-dashboard').forEach(initTabs);
  } else {
    document.addEventListener('DOMContentLoaded', function() {
      document.querySelectorAll('.zxtec-glass-dashboard').forEach(initTabs);
    });
  }
})();
