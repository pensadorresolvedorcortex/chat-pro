(function () {
    'use strict';

    var menus = [];

    var closeMenu = function (menu) {
        if (!menu) {
            return;
        }

        menu.classList.remove('lae-open');
        var toggle = menu.querySelector('[data-lae-toggle]');

        if (toggle) {
            toggle.setAttribute('aria-expanded', 'false');
        }
    };

    var openMenu = function (menu) {
        if (!menu) {
            return;
        }

        menu.classList.add('lae-open');
        var toggle = menu.querySelector('[data-lae-toggle]');

        if (toggle) {
            toggle.setAttribute('aria-expanded', 'true');
        }
    };

    var getMenuItems = function (menu) {
        if (!menu) {
            return [];
        }

        var dropdown = menu.querySelector('[data-lae-dropdown]');

        if (!dropdown) {
            return [];
        }

        return Array.prototype.slice.call(dropdown.querySelectorAll('[role="menuitem"]'));
    };

    var focusMenuItem = function (items, index) {
        if (!items.length) {
            return;
        }

        if (index < 0) {
            index = items.length - 1;
        }

        if (index >= items.length) {
            index = 0;
        }

        var item = items[index];

        if (item && typeof item.focus === 'function') {
            item.focus();
        }
    };

    var focusFirstMenuItem = function (menu) {
        focusMenuItem(getMenuItems(menu), 0);
    };

    var focusLastMenuItem = function (menu) {
        var items = getMenuItems(menu);
        focusMenuItem(items, items.length - 1);
    };

    var handleToggleClick = function (event) {
        event.preventDefault();
        var menu = event.currentTarget.closest('[data-lae-menu]');

        if (!menu) {
            return;
        }

        var isOpen = menu.classList.contains('lae-open');

        menus.forEach(function (otherMenu) {
            if (otherMenu !== menu) {
                closeMenu(otherMenu);
            }
        });

        if (isOpen) {
            closeMenu(menu);
        } else {
            openMenu(menu);
            if (event.detail === 0) {
                focusFirstMenuItem(menu);
            }
        }
    };

    var handleToggleKeyDown = function (event) {
        var key = event.key;

        if (['ArrowDown', 'ArrowUp', 'Enter', ' '].indexOf(key) === -1) {
            return;
        }

        var menu = event.currentTarget.closest('[data-lae-menu]');

        if (!menu) {
            return;
        }

        event.preventDefault();

        if (!menu.classList.contains('lae-open')) {
            openMenu(menu);
        }

        if (key === 'ArrowUp') {
            focusLastMenuItem(menu);
        } else {
            focusFirstMenuItem(menu);
        }
    };

    var handleMenuItemKeyDown = function (event) {
        var key = event.key;
        var target = event.target;

        if (!target || target.getAttribute('role') !== 'menuitem') {
            return;
        }

        var menu = target.closest('[data-lae-menu]');

        if (!menu) {
            return;
        }

        var items = getMenuItems(menu);
        var index = items.indexOf(target);

        if (index === -1) {
            return;
        }

        if (key === 'ArrowDown') {
            event.preventDefault();
            focusMenuItem(items, index + 1);
        } else if (key === 'ArrowUp') {
            event.preventDefault();
            focusMenuItem(items, index - 1);
        } else if (key === 'Home') {
            event.preventDefault();
            focusFirstMenuItem(menu);
        } else if (key === 'End') {
            event.preventDefault();
            focusLastMenuItem(menu);
        } else if (key === 'Escape') {
            event.preventDefault();
            closeMenu(menu);
            var toggle = menu.querySelector('[data-lae-toggle]');

            if (toggle) {
                toggle.focus();
            }
        } else if (key === 'Tab') {
            if ((!event.shiftKey && index === items.length - 1) || (event.shiftKey && index === 0)) {
                closeMenu(menu);
            }
        }
    };

    var handleDocumentClick = function (event) {
        menus.forEach(function (menu) {
            if (!menu.contains(event.target)) {
                closeMenu(menu);
            }
        });
    };

    var handleEscape = function (event) {
        if (event.key === 'Escape') {
            menus.forEach(function (menu) {
                closeMenu(menu);
            });
        }
    };

    document.addEventListener('DOMContentLoaded', function () {
        menus = Array.prototype.slice.call(document.querySelectorAll('[data-lae-menu]'));

        if (!menus.length) {
            return;
        }

        menus.forEach(function (menu) {
            var toggle = menu.querySelector('[data-lae-toggle]');

            if (toggle) {
                toggle.addEventListener('click', handleToggleClick);
                toggle.addEventListener('keydown', handleToggleKeyDown);
            }

            var dropdown = menu.querySelector('[data-lae-dropdown]');

            if (dropdown) {
                dropdown.addEventListener('keydown', handleMenuItemKeyDown);
            }
        });

        document.addEventListener('click', handleDocumentClick);
        document.addEventListener('keydown', handleEscape);
    });
})();
