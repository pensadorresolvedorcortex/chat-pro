(function () {
    'use strict';

    function ready(fn) {
        if (document.readyState !== 'loading') {
            fn();
        } else {
            document.addEventListener('DOMContentLoaded', fn);
        }
    }

    function initTabs(container) {
        var tabs = container.querySelectorAll('[role="tab"]');
        var views = container.querySelectorAll('.questoes-view');

        tabs.forEach(function (tab) {
            tab.addEventListener('click', function () {
                var target = tab.getAttribute('data-target');
                tabs.forEach(function (item) {
                    item.setAttribute('aria-selected', item === tab ? 'true' : 'false');
                });
                views.forEach(function (view) {
                    view.classList.toggle('is-active', view.id === target);
                });
            });
        });
    }

    function initControls(container) {
        var zoom = 1;
        var content = container.querySelector('.questoes-views');
        if (!content) {
            return;
        }

        container.querySelectorAll('[data-action]').forEach(function (button) {
            button.addEventListener('click', function () {
                var action = button.getAttribute('data-action');
                if ('zoom-in' === action) {
                    zoom = Math.min(zoom + 0.1, 2);
                } else if ('zoom-out' === action) {
                    zoom = Math.max(zoom - 0.1, 0.6);
                } else if ('center' === action) {
                    zoom = 1;
                    content.scrollTo({ top: 0, left: 0, behavior: 'smooth' });
                } else if ('print' === action) {
                    window.print();
                    return;
                }
                content.style.transform = 'scale(' + zoom + ')';
                content.style.transformOrigin = '0 0';
            });
        });
    }

    ready(function () {
        document.querySelectorAll('.questoes-component').forEach(function (component) {
            initTabs(component);
            initControls(component);
        });
    });
})();
