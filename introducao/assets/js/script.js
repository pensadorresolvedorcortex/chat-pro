(function () {
    'use strict';

    var menus = [];
    var perfilSettings = window.introducaoPerfil || {};
    var focusableSelector = 'a[href], button:not([disabled]), input:not([disabled]), textarea:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])';

    var toInt = function (value) {
        var number = parseInt(value, 10);

        if (isNaN(number)) {
            return 0;
        }

        return number;
    };

    var formatClock = function (value) {
        var seconds = Math.max(0, toInt(value));
        var minutes = Math.floor(seconds / 60);
        var remaining = seconds % 60;
        var mm = minutes < 10 ? '0' + minutes : String(minutes);
        var ss = remaining < 10 ? '0' + remaining : String(remaining);

        return mm + ':' + ss;
    };

    var getString = function (key, fallback) {
        var dictionary = perfilSettings && perfilSettings.i18n ? perfilSettings.i18n : {};

        if (dictionary && Object.prototype.hasOwnProperty.call(dictionary, key)) {
            return dictionary[key];
        }

        return typeof fallback === 'string' ? fallback : '';
    };

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
        var ctaSelector = '[data-lae-open-popup], [data-lae-login-trigger]';
        var popupTriggers = Array.prototype.slice.call(slider.querySelectorAll(ctaSelector));
        var popupCloseTriggers = popup ? Array.prototype.slice.call(popup.querySelectorAll('[data-lae-popup-close]')) : [];
        var popupTabs = popup ? Array.prototype.slice.call(popup.querySelectorAll('[data-lae-auth-tab]')) : [];
        var popupPanels = popup ? Array.prototype.slice.call(popup.querySelectorAll('[data-lae-auth-panel]')) : [];
        var activeIndex = 0;
        var previouslyFocused = null;

        var setButtonState = function (button, disabled) {
            if (!button) {
                return;
            }

            var isDisabled = Boolean(disabled);

            button.disabled = isDisabled;
            button.setAttribute('aria-disabled', isDisabled ? 'true' : 'false');
        };

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

            setButtonState(prevButton, activeIndex === 0);

            if (nextButton) {
                var isLastSlide = activeIndex === slides.length - 1;
                setButtonState(nextButton, isLastSlide);
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
                    var cta = slider.querySelector(ctaSelector);

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

    var initPerfilSwitchers = function () {
        var containers = Array.prototype.slice.call(document.querySelectorAll('[data-perfil-container]'));

        if (!containers.length) {
            return;
        }

        containers.forEach(function (container) {
            var buttons = Array.prototype.slice.call(container.querySelectorAll('[data-perfil-toggle]'));
            var panels = Array.prototype.slice.call(container.querySelectorAll('[data-perfil-panel]'));

            if (!buttons.length || !panels.length) {
                return;
            }

            var setActive = function (target) {
                if (!target) {
                    return;
                }

                container.setAttribute('data-active-panel', target);

                buttons.forEach(function (button) {
                    var isMatch = button.getAttribute('data-perfil-toggle') === target;
                    button.classList.toggle('is-active', isMatch);
                    button.setAttribute('aria-selected', isMatch ? 'true' : 'false');
                    button.setAttribute('tabindex', isMatch ? '0' : '-1');
                });

                panels.forEach(function (panel) {
                    var panelMatch = panel.getAttribute('data-perfil-panel') === target;
                    panel.classList.toggle('is-active', panelMatch);
                    panel.setAttribute('aria-hidden', panelMatch ? 'false' : 'true');

                    if (panelMatch) {
                        panel.removeAttribute('hidden');
                        var focusTarget = panel.querySelector('[data-perfil-otp-focus="true"]');

                        if (focusTarget) {
                            window.requestAnimationFrame(function () {
                                focusTarget.focus({ preventScroll: false });

                                if (typeof focusTarget.select === 'function') {
                                    focusTarget.select();
                                }
                            });
                        }
                    } else if (window.matchMedia('(max-width: 900px)').matches) {
                        panel.setAttribute('hidden', 'hidden');
                    } else {
                        panel.removeAttribute('hidden');
                    }
                });
            };

            buttons.forEach(function (button) {
                button.addEventListener('click', function (event) {
                    event.preventDefault();
                    var target = button.getAttribute('data-perfil-toggle');
                    setActive(target);
                });

                button.addEventListener('keydown', function (event) {
                    var key = event.key;

                    if (key !== 'ArrowRight' && key !== 'ArrowLeft') {
                        return;
                    }

                    event.preventDefault();
                    var offset = key === 'ArrowRight' ? 1 : -1;
                    var index = buttons.indexOf(button);
                    var nextIndex = (index + offset + buttons.length) % buttons.length;
                    var nextButton = buttons[nextIndex];

                    if (nextButton) {
                        var target = nextButton.getAttribute('data-perfil-toggle');
                        setActive(target);
                        nextButton.focus();
                    }
                });
            });

            setActive(container.getAttribute('data-active-panel'));

            var otpFocusTarget = container.querySelector('[data-perfil-otp-focus="true"]');

            if (otpFocusTarget) {
                window.requestAnimationFrame(function () {
                    otpFocusTarget.focus({ preventScroll: false });

                    if (typeof otpFocusTarget.select === 'function') {
                        otpFocusTarget.select();
                    }
                });
            }

            window.addEventListener('resize', function () {
                window.requestAnimationFrame(function () {
                    setActive(container.getAttribute('data-active-panel'));
                });
            });
        });
    };

    var initPerfilOtp = function () {
        var cards = Array.prototype.slice.call(document.querySelectorAll('[data-perfil-otp-card]'));

        if (!cards.length) {
            return;
        }

        var defaultCooldown = toInt(perfilSettings.resendCooldown || 0);
        var defaultTtl = toInt(perfilSettings.otpTtl || 0);

        cards.forEach(function (card) {
            var challenge = card.getAttribute('data-challenge');

            if (!challenge) {
                return;
            }

            var resendButton = card.querySelector('[data-perfil-resend]');
            var countdownEl = card.querySelector('[data-perfil-countdown]');
            var feedbackEl = card.querySelector('[data-perfil-otp-feedback]');
            var messageEl = card.querySelector('[data-perfil-otp-message]');
            var ttlEl = card.querySelector('[data-perfil-ttl]');
            var mask = card.getAttribute('data-email') || '';
            var cooldown = toInt(card.getAttribute('data-resend'));
            var ttl = toInt(card.getAttribute('data-ttl'));
            var timers = { cooldown: null, ttl: null };
            var defaultLabel = resendButton ? resendButton.textContent : '';
            var sendingLabel = getString('resendSending', 'Reenviando...');
            var readyMessage = getString('resendReady', '');

            var setFeedback = function (text, status) {
                if (!feedbackEl) {
                    return;
                }

                if (!text) {
                    feedbackEl.textContent = '';
                    feedbackEl.setAttribute('hidden', 'hidden');
                    feedbackEl.removeAttribute('data-status');
                    return;
                }

                feedbackEl.textContent = text;
                feedbackEl.removeAttribute('hidden');

                if (status) {
                    feedbackEl.setAttribute('data-status', status);
                } else {
                    feedbackEl.removeAttribute('data-status');
                }
            };

            var updateCooldownMessage = function (seconds) {
                if (!countdownEl) {
                    return;
                }

                var template = getString('resendCountdown', 'Você poderá solicitar um novo código em %s.');
                countdownEl.textContent = template.replace('%s', formatClock(seconds));
                countdownEl.removeAttribute('hidden');
            };

            var setLoading = function (loading) {
                if (!resendButton) {
                    return;
                }

                if (loading) {
                    resendButton.classList.add('is-loading');
                    resendButton.disabled = true;
                    resendButton.setAttribute('aria-disabled', 'true');

                    if (sendingLabel) {
                        resendButton.textContent = sendingLabel;
                    }

                    return;
                }

                resendButton.classList.remove('is-loading');

                if (defaultLabel) {
                    resendButton.textContent = defaultLabel;
                }

                if (cooldown > 0) {
                    resendButton.disabled = true;
                    resendButton.setAttribute('aria-disabled', 'true');
                } else {
                    resendButton.disabled = false;
                    resendButton.removeAttribute('aria-disabled');
                }
            };

            var startCooldown = function (seconds) {
                if (!resendButton) {
                    return;
                }

                if (timers.cooldown) {
                    window.clearTimeout(timers.cooldown);
                }

                cooldown = Math.max(0, toInt(seconds));

                var tick = function () {
                    if (cooldown > 0) {
                        resendButton.disabled = true;
                        resendButton.setAttribute('aria-disabled', 'true');
                        updateCooldownMessage(cooldown);
                        cooldown -= 1;
                        timers.cooldown = window.setTimeout(tick, 1000);
                    } else {
                        if (countdownEl) {
                            if (readyMessage) {
                                countdownEl.textContent = readyMessage;
                                countdownEl.removeAttribute('hidden');
                            } else {
                                countdownEl.textContent = '';
                                countdownEl.setAttribute('hidden', 'hidden');
                            }
                        }

                        resendButton.disabled = false;
                        resendButton.removeAttribute('aria-disabled');
                    }
                };

                tick();
            };

            var startTtl = function (seconds) {
                if (!ttlEl) {
                    return;
                }

                if (timers.ttl) {
                    window.clearTimeout(timers.ttl);
                }

                ttl = Math.max(0, toInt(seconds));

                if (ttl <= 0) {
                    ttlEl.textContent = '';
                    ttlEl.setAttribute('hidden', 'hidden');
                    return;
                }

                var tick = function () {
                    if (ttl <= 0) {
                        ttlEl.textContent = '';
                        ttlEl.setAttribute('hidden', 'hidden');
                        return;
                    }

                    var template = getString('ttlCountdown', 'O código expira em %s.');
                    ttlEl.textContent = template.replace('%s', formatClock(ttl));
                    ttlEl.removeAttribute('hidden');
                    ttl -= 1;
                    timers.ttl = window.setTimeout(tick, 1000);
                };

                tick();
            };

            var onResend = function (event) {
                if (event) {
                    event.preventDefault();
                }

                if (!resendButton || resendButton.disabled) {
                    return;
                }

                if (!perfilSettings || !perfilSettings.ajaxUrl) {
                    setFeedback(getString('securityError', 'Sua sessão expirou. Recarregue a página para tentar novamente.'), 'error');
                    return;
                }

                setFeedback('', '');
                setLoading(true);

                var formData = new FormData();
                formData.append('action', 'introducao_resend_otp');
                formData.append('challenge', challenge);
                formData.append('nonce', perfilSettings.resendNonce || '');

                window
                    .fetch(perfilSettings.ajaxUrl, {
                        method: 'POST',
                        credentials: 'same-origin',
                        body: formData,
                    })
                    .then(function (response) {
                        return response
                            .json()
                            .catch(function () {
                                return { success: false, data: {} };
                            })
                            .then(function (json) {
                                if (!response.ok || !json || json.success !== true) {
                                    json = json || {};
                                    json.httpStatus = response.status;
                                    throw json;
                                }

                                return json;
                            });
                    })
                    .then(function (payload) {
                        var data = payload.data || {};

                        if (data.challenge) {
                            challenge = data.challenge;
                            card.setAttribute('data-challenge', data.challenge);
                        }

                        if (typeof data.maskedEmail === 'string') {
                            mask = data.maskedEmail;
                            card.setAttribute('data-email', mask);
                        }

                        if (typeof data.resend_in !== 'undefined') {
                            cooldown = toInt(data.resend_in);
                        } else {
                            cooldown = defaultCooldown;
                        }

                        if (typeof data.ttl !== 'undefined') {
                            ttl = toInt(data.ttl);
                        } else if (!ttl) {
                            ttl = defaultTtl;
                        }

                        card.setAttribute('data-resend', String(cooldown));
                        card.setAttribute('data-ttl', String(ttl));

                        if (messageEl && typeof data.message === 'string') {
                            messageEl.textContent = data.message;
                        }

                        if (ttlEl) {
                            startTtl(ttl || defaultTtl);
                        }

                        startCooldown(cooldown || defaultCooldown);

                        var successMessage = getString('resendSuccess', 'Enviamos um novo código para %s.');

                        if (successMessage.indexOf('%s') !== -1) {
                            successMessage = successMessage.replace('%s', mask || '');
                        }

                        setFeedback(successMessage.trim(), 'success');
                    })
                    .catch(function (error) {
                        var message = '';

                        if (error && error.data && error.data.message) {
                            message = error.data.message;
                        } else if (typeof error === 'string') {
                            message = error;
                        }

                        if (!message) {
                            message = getString('resendError', 'Não foi possível reenviar o código. Tente novamente em instantes.');
                        }

                        setFeedback(message, 'error');
                    })
                    .finally(function () {
                        setLoading(false);
                    });
            };

            if (resendButton) {
                resendButton.addEventListener('click', onResend);
            }

            if (cooldown > 0) {
                startCooldown(cooldown);
            } else if (countdownEl) {
                if (readyMessage) {
                    countdownEl.textContent = readyMessage;
                    countdownEl.removeAttribute('hidden');
                } else {
                    countdownEl.textContent = '';
                    countdownEl.setAttribute('hidden', 'hidden');
                }
            }

            if (ttl > 0) {
                startTtl(ttl);
            } else if (ttlEl) {
                ttlEl.textContent = '';
                ttlEl.setAttribute('hidden', 'hidden');
            }

            setLoading(false);
        });
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

        initPerfilSwitchers();
        initPerfilOtp();
        initAllOnboardingSliders();
    });
})();
