(function () {
    'use strict';

    var menus = [];
    var focusableSelector = 'a[href], button:not([disabled]), input:not([disabled]), textarea:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])';

    var getItems = function (menu) {
        var dropdown = menu ? menu.querySelector('[data-lae-dropdown]') : null;
        return dropdown ? Array.prototype.slice.call(dropdown.querySelectorAll('[role="menuitem"]')) : [];
    };

    var setMenuState = function (menu, open) {
        if (!menu) {
            return;
        }

        menu.classList.toggle('lae-open', open);
        var toggle = menu.querySelector('[data-lae-toggle]');

        if (toggle) {
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        }
    };

    var closeOthers = function (current) {
        menus.forEach(function (menu) {
            if (menu !== current) {
                setMenuState(menu, false);
            }
        });
    };

    var focusItem = function (items, index) {
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

    var onToggleClick = function (event) {
        event.preventDefault();
        var menu = event.currentTarget.closest('[data-lae-menu]');

        if (!menu) {
            return;
        }

        var isOpen = menu.classList.contains('lae-open');
        closeOthers(menu);
        setMenuState(menu, !isOpen);

        if (!isOpen) {
            focusItem(getItems(menu), 0);
        }
    };

    var onToggleKeyDown = function (event) {
        var key = event.key;

        if (['ArrowDown', 'ArrowUp', 'Enter', ' '].indexOf(key) === -1) {
            return;
        }

        event.preventDefault();
        var menu = event.currentTarget.closest('[data-lae-menu]');

        if (!menu) {
            return;
        }

        closeOthers(menu);
        setMenuState(menu, true);

        if (key === 'ArrowUp') {
            focusItem(getItems(menu), -1);
        } else {
            focusItem(getItems(menu), 0);
        }
    };

    var onDropdownKeyDown = function (event) {
        var menuItem = event.target;

        if (!menuItem || menuItem.getAttribute('role') !== 'menuitem') {
            return;
        }

        var menu = menuItem.closest('[data-lae-menu]');

        if (!menu) {
            return;
        }

        var items = getItems(menu);
        var index = items.indexOf(menuItem);

        if (index === -1) {
            return;
        }

        switch (event.key) {
            case 'ArrowDown':
                event.preventDefault();
                focusItem(items, index + 1);
                break;
            case 'ArrowUp':
                event.preventDefault();
                focusItem(items, index - 1);
                break;
            case 'Home':
                event.preventDefault();
                focusItem(items, 0);
                break;
            case 'End':
                event.preventDefault();
                focusItem(items, items.length - 1);
                break;
            case 'Escape':
                event.preventDefault();
                setMenuState(menu, false);
                var toggle = menu.querySelector('[data-lae-toggle]');

                if (toggle) {
                    toggle.focus();
                }
                break;
            case 'Tab':
                if ((!event.shiftKey && index === items.length - 1) || (event.shiftKey && index === 0)) {
                    setMenuState(menu, false);
                }
                break;
        }
    };

    var onDocumentClick = function (event) {
        menus.forEach(function (menu) {
            if (!menu.contains(event.target)) {
                setMenuState(menu, false);
            }
        });
    };

    var onDocumentKeyDown = function (event) {
        if (event.key === 'Escape') {
            menus.forEach(function (menu) {
                setMenuState(menu, false);
            });
        }
    };

    var getFocusable = function (root) {
        if (!root) {
            return [];
        }

        return Array.prototype.slice
            .call(root.querySelectorAll(focusableSelector))
            .filter(function (element) {
                if (element.disabled) {
                    return false;
                }

                if (element.hasAttribute('hidden')) {
                    return false;
                }

                var style = window.getComputedStyle(element);

                if (style.visibility === 'hidden' || style.display === 'none') {
                    return false;
                }

                if (element.offsetParent === null && style.position !== 'fixed') {
                    return false;
                }

                return true;
            });
    };

    var initOnboardingSlider = function (slider) {
        if (!slider) {
            return;
        }

        var slides = Array.prototype.slice.call(slider.querySelectorAll('[data-lae-slide]'));

        if (!slides.length) {
            return;
        }

        var steps = Array.prototype.slice.call(slider.querySelectorAll('[data-lae-step]'));
        var nextButton = slider.querySelector('[data-lae-next]');
        var prevButton = slider.querySelector('[data-lae-prev]');
        var finishGroup = slider.querySelector('[data-lae-finish]');
        var popup = slider.querySelector('[data-lae-popup]');
        var popupTriggers = Array.prototype.slice.call(slider.querySelectorAll('[data-lae-open-popup]'));
        var popupCloseTriggers = popup ? Array.prototype.slice.call(popup.querySelectorAll('[data-lae-popup-close]')) : [];
        var popupTabs = popup ? Array.prototype.slice.call(popup.querySelectorAll('[data-lae-auth-tab]')) : [];
        var popupPanels = popup ? Array.prototype.slice.call(popup.querySelectorAll('[data-lae-auth-panel]')) : [];
        var activeIndex = 0;
        var previouslyFocused = null;

        var setActiveSlide = function (index) {
            if (index < 0 || index >= slides.length) {
                return;
            }

            activeIndex = index;

            slides.forEach(function (slide, idx) {
                var isActive = idx === activeIndex;
                slide.classList.toggle('is-active', isActive);
                slide.setAttribute('aria-hidden', isActive ? 'false' : 'true');
            });

            steps.forEach(function (step, idx) {
                var isCurrent = idx === activeIndex;
                var isCompleted = idx <= activeIndex;
                step.classList.toggle('is-active', isCompleted);
                step.setAttribute('aria-selected', isCurrent ? 'true' : 'false');
                step.setAttribute('tabindex', isCurrent ? '0' : '-1');

                if (isCurrent) {
                    step.setAttribute('aria-current', 'step');
                } else {
                    step.removeAttribute('aria-current');
                }
            });

            if (prevButton) {
                prevButton.disabled = activeIndex === 0;
            }

            if (nextButton) {
                var isLastSlide = activeIndex === slides.length - 1;
                nextButton.disabled = isLastSlide;
                nextButton.classList.toggle('is-hidden', isLastSlide);
            }

            if (finishGroup) {
                finishGroup.classList.toggle('is-active', activeIndex === slides.length - 1);
            }
        };

        var focusFirstField = function () {
            if (!popup) {
                return;
            }

            var activePanel = popup.querySelector('[data-lae-auth-panel].is-active');

            if (!activePanel) {
                return;
            }

            var field = activePanel.querySelector('input, button, select, textarea');

            if (field && typeof field.focus === 'function') {
                field.focus();
            }
        };

        var setActiveAuthPanel = function (target) {
            if (!popup) {
                return;
            }

            var normalized = target;

            if (!normalized && popupTabs.length) {
                normalized = popupTabs[0].getAttribute('data-lae-auth-tab');
            }

            popupTabs.forEach(function (tab) {
                var isMatch = tab.getAttribute('data-lae-auth-tab') === normalized;
                tab.classList.toggle('is-active', isMatch);
                tab.setAttribute('aria-selected', isMatch ? 'true' : 'false');
                tab.setAttribute('tabindex', isMatch ? '0' : '-1');
            });

            popupPanels.forEach(function (panel) {
                var panelMatch = panel.getAttribute('data-lae-auth-panel') === normalized;

                if (panelMatch) {
                    panel.classList.add('is-active');
                    panel.removeAttribute('hidden');
                    panel.setAttribute('aria-hidden', 'false');
                } else {
                    panel.classList.remove('is-active');
                    panel.setAttribute('hidden', 'hidden');
                    panel.setAttribute('aria-hidden', 'true');
                }
            });
        };

        var closePopup = function () {
            if (!popup) {
                return;
            }

            popup.classList.remove('is-visible');
            popup.setAttribute('hidden', 'hidden');
            popup.setAttribute('aria-hidden', 'true');
            document.removeEventListener('keydown', onPopupKeyDown, true);

            if (previouslyFocused && typeof previouslyFocused.focus === 'function') {
                previouslyFocused.focus();
            }

            previouslyFocused = null;
        };

        var onPopupKeyDown = function (event) {
            if (!popup || !popup.classList.contains('is-visible')) {
                return;
            }

            if (event.key === 'Escape') {
                event.preventDefault();
                closePopup();
                return;
            }

            if (event.key !== 'Tab') {
                return;
            }

            var focusable = getFocusable(popup);

            if (!focusable.length) {
                return;
            }

            var first = focusable[0];
            var last = focusable[focusable.length - 1];
            var active = document.activeElement;

            if (event.shiftKey) {
                if (active === first) {
                    event.preventDefault();
                    last.focus();
                }
            } else if (active === last) {
                event.preventDefault();
                first.focus();
            }
        };

        var openPopup = function (trigger) {
            if (!popup) {
                return;
            }

            previouslyFocused = trigger || document.activeElement;
            popup.removeAttribute('hidden');
            popup.setAttribute('aria-hidden', 'false');
            popup.classList.add('is-visible');
            setActiveAuthPanel();
            document.addEventListener('keydown', onPopupKeyDown, true);

            window.setTimeout(focusFirstField, 30);
        };

        if (nextButton) {
            nextButton.addEventListener('click', function (event) {
                event.preventDefault();
                var targetIndex = activeIndex + 1;
                setActiveSlide(targetIndex);

                if (targetIndex === slides.length - 1) {
                    var cta = slider.querySelector('[data-lae-open-popup]');

                    if (cta && typeof cta.focus === 'function') {
                        cta.focus();
                    }
                }
            });
        }

        if (prevButton) {
            prevButton.addEventListener('click', function (event) {
                event.preventDefault();
                setActiveSlide(activeIndex - 1);
            });
        }

        steps.forEach(function (step, idx) {
            step.addEventListener('click', function (event) {
                event.preventDefault();
                setActiveSlide(idx);
                step.focus();
            });

            step.addEventListener('keydown', function (event) {
                var key = event.key;

                if (key === 'ArrowRight' || key === 'ArrowDown') {
                    event.preventDefault();
                    var nextIndex = (idx + 1) % steps.length;
                    setActiveSlide(nextIndex);
                    steps[nextIndex].focus();
                } else if (key === 'ArrowLeft' || key === 'ArrowUp') {
                    event.preventDefault();
                    var prevIndex = (idx - 1 + steps.length) % steps.length;
                    setActiveSlide(prevIndex);
                    steps[prevIndex].focus();
                } else if (key === 'Home') {
                    event.preventDefault();
                    setActiveSlide(0);
                    steps[0].focus();
                } else if (key === 'End') {
                    event.preventDefault();
                    setActiveSlide(steps.length - 1);
                    steps[steps.length - 1].focus();
                }
            });
        });

        if (popup) {
            popupTriggers.forEach(function (trigger) {
                trigger.addEventListener('click', function (event) {
                    event.preventDefault();
                    openPopup(trigger);
                });
            });

            popupCloseTriggers.forEach(function (trigger) {
                trigger.addEventListener('click', function (event) {
                    event.preventDefault();
                    closePopup();
                });
            });

            popupTabs.forEach(function (tab) {
                tab.addEventListener('click', function (event) {
                    event.preventDefault();
                    var target = tab.getAttribute('data-lae-auth-tab');
                    setActiveAuthPanel(target);
                    focusFirstField();
                });

                tab.addEventListener('keydown', function (event) {
                    var key = event.key;

                    if (key !== 'ArrowLeft' && key !== 'ArrowRight') {
                        return;
                    }

                    event.preventDefault();
                    var offset = key === 'ArrowRight' ? 1 : -1;
                    var currentIndex = popupTabs.indexOf(tab);
                    var newIndex = (currentIndex + offset + popupTabs.length) % popupTabs.length;
                    var newTab = popupTabs[newIndex];

                    if (newTab) {
                        var target = newTab.getAttribute('data-lae-auth-tab');
                        setActiveAuthPanel(target);
                        newTab.focus();
                        focusFirstField();
                    }
                });
            });

            popup.addEventListener('click', function (event) {
                if (event.target === popup) {
                    closePopup();
                }
            });
        }

        setActiveSlide(0);
    };

    var initAllOnboardingSliders = function () {
        var sliders = Array.prototype.slice.call(document.querySelectorAll('[data-lae-slider]'));

        sliders.forEach(initOnboardingSlider);
    };

    document.addEventListener('DOMContentLoaded', function () {
        menus = Array.prototype.slice.call(document.querySelectorAll('[data-lae-menu]'));

        menus.forEach(function (menu) {
            var toggle = menu.querySelector('[data-lae-toggle]');
            var dropdown = menu.querySelector('[data-lae-dropdown]');

            if (toggle) {
                toggle.addEventListener('click', onToggleClick);
                toggle.addEventListener('keydown', onToggleKeyDown);
            }

            if (dropdown) {
                dropdown.addEventListener('keydown', onDropdownKeyDown);
            }
        });

        if (menus.length) {
            document.addEventListener('click', onDocumentClick);
            document.addEventListener('keydown', onDocumentKeyDown);
        }

        initAllOnboardingSliders();
    });
})();
