(function () {
    'use strict';

    var menus = [];
    var avatarSettings = null;
    var accountSettings = null;
    var avatarManager = {
        container: null,
        input: null,
        uploadButton: null,
        removeButton: null,
        message: null,
        preview: null,
    };
    var securityElements = {
        twoFactor: {
            container: null,
            toggle: null,
            status: null,
            message: null,
        },
        password: {
            form: null,
            message: null,
            submit: null,
        },
    };
    var loginSettings = typeof window !== 'undefined' && window.LAELogin ? window.LAELogin : null;
    var loginElements = {
        modal: null,
        overlay: null,
        closeButtons: [],
        triggers: [],
        tabs: [],
        forms: {
            login: null,
            register: null,
        },
        message: null,
        passwordWrap: null,
        twoFactor: null,
        twoFactorHint: null,
        twoFactorResend: null,
        codeInput: null,
        loginInput: null,
    };
    var loginState = {
        activeTab: 'login',
        awaitingTwoFactor: false,
        credentials: {
            login: '',
            password: '',
            remember: false,
            redirect: '',
        },
        context: {},
        redirectTimer: null,
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

    var getAvatarMessage = function (key) {
        if (!avatarSettings || !avatarSettings.messages) {
            return '';
        }

        return avatarSettings.messages[key] || '';
    };

    var setAvatarMessage = function (text, type) {
        if (!avatarManager.message) {
            return;
        }

        var baseClass = 'lae-avatar-message';
        avatarManager.message.className = baseClass;

        if (type) {
            avatarManager.message.className += ' ' + baseClass + '--' + type;
        }

        avatarManager.message.textContent = text || '';
    };

    var getLoginMessage = function (key) {
        if (!loginSettings || !loginSettings.messages) {
            return '';
        }

        return loginSettings.messages[key] || '';
    };

    var setLoginMessage = function (text, type) {
        if (!loginElements.message) {
            return;
        }

        loginElements.message.textContent = text || '';
        loginElements.message.classList.remove('is-error', 'is-success');

        if (type === 'error') {
            loginElements.message.classList.add('is-error');
        } else if (type === 'success') {
            loginElements.message.classList.add('is-success');
        }
    };

    var setLoginFormBusy = function (form, isBusy) {
        if (!form) {
            return;
        }

        form.classList.toggle('is-busy', !!isBusy);

        var controls = form.querySelectorAll('input, button, select, textarea');

        Array.prototype.forEach.call(controls, function (control) {
            if (!control) {
                return;
            }

            if (isBusy) {
                control.setAttribute('disabled', 'disabled');
            } else if (!loginState.awaitingTwoFactor || control.name !== 'login') {
                control.removeAttribute('disabled');
            }
        });
    };

    var formatWaitDuration = function (seconds) {
        var total = parseInt(seconds, 10);

        if (isNaN(total) || total <= 0) {
            return '';
        }

        if (total < 60) {
            return total + 's';
        }

        var minutes = Math.floor(total / 60);
        var remaining = total % 60;
        var parts = [];

        if (minutes > 0) {
            parts.push(minutes + 'min');
        }

        if (remaining > 0) {
            parts.push(remaining + 's');
        }

        return parts.join(' ');
    };

    var applyTwoFactorState = function () {
        var loginInput = loginElements.loginInput;
        var passwordInput = loginElements.forms.login ? loginElements.forms.login.querySelector('input[name="password"]') : null;

        if (loginState.awaitingTwoFactor) {
            if (loginInput) {
                loginInput.setAttribute('disabled', 'disabled');
            }

            if (passwordInput) {
                passwordInput.setAttribute('disabled', 'disabled');
            }
        } else {
            if (loginInput) {
                loginInput.removeAttribute('disabled');
            }

            if (passwordInput) {
                passwordInput.removeAttribute('disabled');
            }
        }
    };

    var hideTwoFactorStep = function () {
        loginState.awaitingTwoFactor = false;
        loginState.context = {};

        if (loginElements.twoFactor) {
            loginElements.twoFactor.setAttribute('hidden', 'hidden');
        }

        if (loginElements.twoFactorHint) {
            loginElements.twoFactorHint.textContent = '';
        }

        if (loginElements.twoFactorResend) {
            loginElements.twoFactorResend.hidden = true;
            loginElements.twoFactorResend.disabled = false;
            delete loginElements.twoFactorResend.dataset.login;
            delete loginElements.twoFactorResend.dataset.key;
        }

        if (loginElements.codeInput) {
            loginElements.codeInput.value = '';
        }

        if (loginElements.passwordWrap) {
            loginElements.passwordWrap.removeAttribute('hidden');
        }

        applyTwoFactorState();
    };

    var updateTwoFactorControls = function (context) {
        loginState.context = context || {};

        if (!loginState.context.login) {
            loginState.context.login = loginState.credentials.login;
        }

        if (!loginElements.twoFactor) {
            return;
        }

        var wait = 0;

        if (context && typeof context.wait !== 'undefined') {
            wait = parseInt(context.wait, 10);

            if (isNaN(wait)) {
                wait = 0;
            }
        }

        if (loginElements.twoFactorResend) {
            if (!context || !context.resend_key || wait > 0) {
                loginElements.twoFactorResend.hidden = true;
                loginElements.twoFactorResend.disabled = true;
            } else {
                loginElements.twoFactorResend.hidden = false;
                loginElements.twoFactorResend.disabled = false;
                loginElements.twoFactorResend.dataset.login = context.login || loginState.credentials.login;
                loginElements.twoFactorResend.dataset.key = context.resend_key;
            }
        }

        if (loginElements.twoFactorHint) {
            if (wait > 0) {
                var label = getLoginMessage('resendWait');
                var suffix = formatWaitDuration(wait);

                loginElements.twoFactorHint.textContent = suffix ? label + ' (' + suffix + ')' : label;
            }
        }
    };

    var showTwoFactorStep = function (context, message, login) {
        loginState.awaitingTwoFactor = true;

        if (typeof login === 'string' && login) {
            loginState.credentials.login = login;
        }

        if (loginElements.passwordWrap) {
            loginElements.passwordWrap.setAttribute('hidden', 'hidden');
        }

        if (loginElements.twoFactor) {
            loginElements.twoFactor.removeAttribute('hidden');
        }

        if (loginElements.twoFactorHint) {
            loginElements.twoFactorHint.textContent = message || getLoginMessage('twoFactorRequired');
        }

        if (loginElements.forms.login) {
            var passwordInput = loginElements.forms.login.querySelector('input[name="password"]');

            if (passwordInput) {
                passwordInput.value = '';
            }
        }

        updateTwoFactorControls(context || {});
        applyTwoFactorState();

        if (loginElements.codeInput) {
            loginElements.codeInput.focus();
        }

        setLoginMessage(message || getLoginMessage('twoFactorRequired'), 'info');
    };

    var activateLoginTab = function (tab) {
        loginState.activeTab = tab || 'login';

        loginElements.tabs.forEach(function (button) {
            var target = button.getAttribute('data-lae-login-tab');
            var isActive = target === loginState.activeTab;

            button.classList.toggle('is-active', isActive);
            button.setAttribute('aria-selected', isActive ? 'true' : 'false');
        });

        if (loginElements.forms.login) {
            var activeLogin = loginState.activeTab === 'login';
            loginElements.forms.login.classList.toggle('is-active', activeLogin);

            if (activeLogin) {
                loginElements.forms.login.removeAttribute('hidden');
            } else {
                loginElements.forms.login.setAttribute('hidden', 'hidden');
            }
        }

        if (loginElements.forms.register) {
            var activeRegister = loginState.activeTab === 'register';
            loginElements.forms.register.classList.toggle('is-active', activeRegister);

            if (activeRegister) {
                loginElements.forms.register.removeAttribute('hidden');
            } else {
                loginElements.forms.register.setAttribute('hidden', 'hidden');
            }
        }
    };

    var resetLoginForms = function () {
        if (loginState.redirectTimer) {
            clearTimeout(loginState.redirectTimer);
            loginState.redirectTimer = null;
        }

        loginState.credentials = {
            login: '',
            password: '',
            remember: false,
            redirect: '',
        };

        hideTwoFactorStep();
        activateLoginTab('login');
        setLoginMessage('', '');

        if (loginElements.forms.login) {
            loginElements.forms.login.reset();
        }

        if (loginElements.forms.register) {
            loginElements.forms.register.reset();
        }
    };

    var openLoginModal = function () {
        if (!loginElements.modal) {
            return;
        }

        loginElements.modal.classList.add('is-visible');
        document.body.classList.add('lae-login-open');
        resetLoginForms();

        if (loginElements.loginInput) {
            window.setTimeout(function () {
                loginElements.loginInput.focus();
            }, 60);
        }
    };

    var closeLoginModal = function () {
        if (!loginElements.modal) {
            return;
        }

        loginElements.modal.classList.remove('is-visible');
        document.body.classList.remove('lae-login-open');
        resetLoginForms();
    };

    var getAccountMessage = function (key) {
        if (!accountSettings || !accountSettings.messages) {
            return '';
        }

        return accountSettings.messages[key] || '';
    };

    var handleLoginPayload = function (payload) {
        if (!payload) {
            setLoginMessage(getLoginMessage('error'), 'error');
            return;
        }

        if (payload.status === 'two_factor_required') {
            if (payload.login) {
                loginState.credentials.login = payload.login;
            }

            if (typeof payload.redirect === 'string' && payload.redirect) {
                loginState.credentials.redirect = payload.redirect;
            }

            showTwoFactorStep(payload.context || {}, payload.message || getLoginMessage('twoFactorRequired'), payload.login);
            return;
        }

        if (payload.status === 'registered' || payload.status === 'logged_in') {
            var redirect = payload.redirect || (loginSettings ? loginSettings.redirect : '');
            var message = payload.message || getLoginMessage('success');

            setLoginMessage(message, 'success');

            loginState.credentials.login = '';
            loginState.credentials.password = '';

            if (loginState.redirectTimer) {
                clearTimeout(loginState.redirectTimer);
            }

            loginState.redirectTimer = window.setTimeout(function () {
                if (redirect) {
                    window.location.href = redirect;
                } else {
                    window.location.reload();
                }
            }, 600);

            return;
        }

        if (payload.message) {
            setLoginMessage(payload.message, 'error');
        } else {
            setLoginMessage(getLoginMessage('error'), 'error');
        }
    };

    var submitLoginForm = function (event) {
        event.preventDefault();

        if (!loginSettings || !loginSettings.ajaxUrl || !loginSettings.nonces || !loginSettings.nonces.login) {
            return;
        }

        var form = loginElements.forms.login;

        if (!form) {
            return;
        }

        if (!loginState.awaitingTwoFactor) {
            var loginValue = loginElements.loginInput ? loginElements.loginInput.value.trim() : '';
            var passwordInput = form.querySelector('input[name="password"]');
            var passwordValue = passwordInput ? passwordInput.value : '';
            var rememberInput = form.querySelector('input[name="remember"]');
            var redirectInput = form.querySelector('input[name="redirect_to"]');

            if (!loginValue || !passwordValue) {
                setLoginMessage(getLoginMessage('missingFields') || getLoginMessage('error'), 'error');
                return;
            }

            loginState.credentials.login = loginValue;
            loginState.credentials.password = passwordValue;
            loginState.credentials.remember = !!(rememberInput && rememberInput.checked);
            loginState.credentials.redirect = redirectInput ? redirectInput.value : '';
        }

        var codeValue = '';

        if (loginState.awaitingTwoFactor) {
            codeValue = loginElements.codeInput ? loginElements.codeInput.value.trim() : '';

            if (!codeValue) {
                setLoginMessage(getLoginMessage('twoFactorRequired') || getLoginMessage('missingFields'), 'error');
                return;
            }
        }

        var formData = new FormData();
        formData.append('action', 'lae_login_user');
        formData.append('nonce', loginSettings.nonces.login);
        formData.append('login', loginState.credentials.login);
        formData.append('password', loginState.credentials.password);
        formData.append('remember', loginState.credentials.remember ? '1' : '0');
        formData.append('redirect_to', loginState.credentials.redirect || '');

        if (loginState.awaitingTwoFactor && codeValue) {
            formData.append('code', codeValue);
        }

        setLoginFormBusy(form, true);
        setLoginMessage(getLoginMessage('loginWorking'), 'info');

        fetch(loginSettings.ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            body: formData,
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error(getLoginMessage('error'));
                }

                return response.json();
            })
            .then(function (result) {
                if (!result || typeof result.success === 'undefined') {
                    throw new Error(getLoginMessage('error'));
                }

                if (!result.success) {
                    var payload = result.data || {};

                    if (payload.status === 'two_factor_invalid') {
                        if (payload.login) {
                            loginState.credentials.login = payload.login;
                        }

                        showTwoFactorStep(payload.context || loginState.context || {}, payload.message || getLoginMessage('twoFactorInvalid'), payload.login);
                        setLoginMessage(payload.message || getLoginMessage('twoFactorInvalid'), 'error');

                        return;
                    }

                    throw new Error(payload.message || getLoginMessage('error'));
                }

                handleLoginPayload(result.data || {});
            })
            .catch(function (error) {
                var message = error && error.message ? error.message : getLoginMessage('error');
                setLoginMessage(message, 'error');

                if (!loginState.awaitingTwoFactor) {
                    hideTwoFactorStep();
                }
            })
            .finally(function () {
                setLoginFormBusy(form, false);
                applyTwoFactorState();
            });
    };

    var submitRegisterForm = function (event) {
        event.preventDefault();

        if (!loginSettings || !loginSettings.ajaxUrl || !loginSettings.nonces || !loginSettings.nonces.register) {
            return;
        }

        var form = loginElements.forms.register;

        if (!form) {
            return;
        }

        var nameInput = form.querySelector('input[name="name"]');
        var emailInput = form.querySelector('input[name="email"]');
        var passwordInput = form.querySelector('input[name="password"]');
        var confirmInput = form.querySelector('input[name="confirm"]');
        var redirectInput = form.querySelector('input[name="redirect_to"]');

        var name = nameInput ? nameInput.value.trim() : '';
        var email = emailInput ? emailInput.value.trim() : '';
        var password = passwordInput ? passwordInput.value : '';
        var confirm = confirmInput ? confirmInput.value : '';

        if (!name || !email || !password || !confirm) {
            setLoginMessage(getLoginMessage('missingFields'), 'error');
            return;
        }

        if (password.length < 8) {
            setLoginMessage(getLoginMessage('passwordWeak'), 'error');
            return;
        }

        if (password !== confirm) {
            setLoginMessage(getLoginMessage('passwordMismatch') || getLoginMessage('error'), 'error');
            return;
        }

        var formData = new FormData();
        formData.append('action', 'lae_register_user');
        formData.append('nonce', loginSettings.nonces.register);
        formData.append('name', name);
        formData.append('email', email);
        formData.append('password', password);
        formData.append('confirm', confirm);
        formData.append('redirect_to', redirectInput ? redirectInput.value : '');

        setLoginFormBusy(form, true);
        setLoginMessage(getLoginMessage('registerWorking'), 'info');

        fetch(loginSettings.ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            body: formData,
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error(getLoginMessage('error'));
                }

                return response.json();
            })
            .then(function (result) {
                if (!result || typeof result.success === 'undefined') {
                    throw new Error(getLoginMessage('error'));
                }

                if (!result.success) {
                    var payload = result.data || {};
                    throw new Error(payload.message || getLoginMessage('error'));
                }

                handleLoginPayload(result.data || {});
            })
            .catch(function (error) {
                var message = error && error.message ? error.message : getLoginMessage('error');
                setLoginMessage(message, 'error');
            })
            .finally(function () {
                setLoginFormBusy(form, false);
            });
    };

    var onTwoFactorResend = function (event) {
        event.preventDefault();

        if (!loginSettings || !loginSettings.ajaxUrl || !loginSettings.nonces || !loginSettings.nonces.resend) {
            return;
        }

        if (!loginState.context || !loginState.context.resend_key) {
            setLoginMessage(getLoginMessage('resendWait') || getLoginMessage('error'), 'error');
            return;
        }

        if (!loginElements.twoFactorResend || loginElements.twoFactorResend.disabled) {
            return;
        }

        loginElements.twoFactorResend.disabled = true;

        var formData = new FormData();
        formData.append('action', 'lae_resend_two_factor');
        formData.append('nonce', loginSettings.nonces.resend);
        formData.append('login', loginState.context.login || loginState.credentials.login);
        formData.append('key', loginState.context.resend_key);

        fetch(loginSettings.ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            body: formData,
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error(getLoginMessage('error'));
                }

                return response.json();
            })
            .then(function (result) {
                if (!result || typeof result.success === 'undefined') {
                    throw new Error(getLoginMessage('error'));
                }

                if (!result.success) {
                    var payload = result.data || {};
                    throw new Error(payload.message || getLoginMessage('error'));
                }

                updateTwoFactorControls(result.data && result.data.context ? result.data.context : {});
                setLoginMessage(result.data && result.data.message ? result.data.message : getLoginMessage('resendSuccess'), 'info');

                if (loginElements.codeInput) {
                    loginElements.codeInput.focus();
                }
            })
            .catch(function (error) {
                var message = error && error.message ? error.message : getLoginMessage('error');
                setLoginMessage(message, 'error');
            })
            .finally(function () {
                var wait = loginState.context && loginState.context.wait ? parseInt(loginState.context.wait, 10) : 0;

                if (!wait || wait <= 0) {
                    loginElements.twoFactorResend.disabled = false;
                }
            });
    };

    var onLoginTabClick = function (event) {
        var tab = event.currentTarget ? event.currentTarget.getAttribute('data-lae-login-tab') : '';

        if (!tab || tab === loginState.activeTab) {
            return;
        }

        activateLoginTab(tab);
        setLoginMessage('', '');

        if (tab === 'login') {
            hideTwoFactorStep();
        }
    };

    var initLoginModal = function () {
        if (!loginSettings || !loginSettings.ajaxUrl) {
            return;
        }

        var modal = document.querySelector('[data-lae-login-modal]');

        if (!modal) {
            return;
        }

        loginElements.modal = modal;
        loginElements.triggers = Array.prototype.slice.call(document.querySelectorAll('[data-lae-login-trigger]'));
        loginElements.closeButtons = Array.prototype.slice.call(modal.querySelectorAll('[data-lae-login-close]'));
        loginElements.tabs = Array.prototype.slice.call(modal.querySelectorAll('[data-lae-login-tab]'));
        loginElements.forms.login = modal.querySelector('[data-lae-login-form="login"]');
        loginElements.forms.register = modal.querySelector('[data-lae-login-form="register"]');
        loginElements.message = modal.querySelector('[data-lae-login-message]');

        if (loginElements.forms.login) {
            loginElements.passwordWrap = loginElements.forms.login.querySelector('[data-lae-login-password]');
            loginElements.twoFactor = loginElements.forms.login.querySelector('[data-lae-login-2fa]');
            loginElements.twoFactorHint = loginElements.forms.login.querySelector('[data-lae-login-2fa-hint]');
            loginElements.twoFactorResend = loginElements.forms.login.querySelector('[data-lae-login-2fa-resend]');
            loginElements.codeInput = loginElements.forms.login.querySelector('input[name="code"]');
            loginElements.loginInput = loginElements.forms.login.querySelector('input[name="login"]');

            loginElements.forms.login.addEventListener('submit', submitLoginForm);
        }

        if (loginElements.forms.register) {
            loginElements.forms.register.addEventListener('submit', submitRegisterForm);
        }

        loginElements.triggers.forEach(function (trigger) {
            trigger.addEventListener('click', function (event) {
                event.preventDefault();
                openLoginModal();
            });
        });

        loginElements.closeButtons.forEach(function (button) {
            button.addEventListener('click', function (event) {
                event.preventDefault();
                closeLoginModal();
            });
        });

        loginElements.tabs.forEach(function (button) {
            button.addEventListener('click', onLoginTabClick);
        });

        if (loginElements.twoFactorResend) {
            loginElements.twoFactorResend.addEventListener('click', onTwoFactorResend);
        }

        hideTwoFactorStep();
        activateLoginTab('login');
    };

    var setStatusMessage = function (node, text, type) {
        if (!node) {
            return;
        }

        var base = 'lae-security-message';
        node.className = base;

        if (type) {
            node.className += ' ' + base + '--' + type;
        }

        node.textContent = text || '';
    };

    var setCardBusy = function (element, isBusy) {
        if (!element) {
            return;
        }

        element.classList.toggle('is-busy', !!isBusy);

        var controls = element.querySelectorAll('button, input, select, textarea');

        Array.prototype.forEach.call(controls, function (control) {
            if (control.matches('[data-lae-avatar-input]')) {
                return;
            }

            if (typeof control.disabled !== 'undefined') {
                control.disabled = !!isBusy;
            }
        });
    };

    var updateTwoFactorUI = function (enabled, statusText) {
        var elements = securityElements.twoFactor;

        if (!elements.container) {
            return;
        }

        elements.container.setAttribute('data-lae-2fa-enabled', enabled ? '1' : '0');

        var labels = accountSettings && accountSettings.labels ? accountSettings.labels : {};

        if (elements.toggle) {
            var toggleLabel = enabled ? labels.disableTwoFactor : labels.enableTwoFactor;

            if (!toggleLabel) {
                toggleLabel = enabled ? 'Desativar' : 'Ativar';
            }

            elements.toggle.textContent = toggleLabel;
        }

        if (elements.status) {
            var status = statusText;

            if (!status) {
                status = enabled ? labels.twoFactorEnabled : labels.twoFactorDisabled;
            }

            if (status) {
                elements.status.textContent = status;
            }
        }
    };

    var toggleAvatarLoading = function (isLoading) {
        if (!avatarManager.container) {
            return;
        }

        avatarManager.container.classList.toggle('is-uploading', !!isLoading);
    };

    var syncAvatar = function (url, hasCustom) {
        var finalUrl = url || (avatarSettings && avatarSettings.defaultAvatar ? avatarSettings.defaultAvatar : '');

        if (avatarManager.preview && finalUrl) {
            avatarManager.preview.src = finalUrl;
        }

        var syncImages = document.querySelectorAll('[data-lae-avatar-sync="image"]');

        Array.prototype.forEach.call(syncImages, function (image) {
            if (finalUrl) {
                image.src = finalUrl;
            }
        });

        var containers = document.querySelectorAll('[data-lae-avatar-container]');

        Array.prototype.forEach.call(containers, function (node) {
            if (finalUrl) {
                node.classList.add('has-image');
            } else {
                node.classList.remove('has-image');
            }
        });

        if (avatarManager.removeButton) {
            if (hasCustom) {
                avatarManager.removeButton.removeAttribute('hidden');
            } else {
                avatarManager.removeButton.setAttribute('hidden', 'hidden');
            }
        }
    };

    var handleAvatarResponse = function (payload) {
        if (!payload) {
            return;
        }

        var message = payload.message || '';
        var hasCustom = !!payload.hasCustom;

        syncAvatar(payload.url, hasCustom);

        if (!message) {
            message = hasCustom ? getAvatarMessage('uploadSuccess') : getAvatarMessage('removeSuccess');
        }

        if (message) {
            setAvatarMessage(message, 'success');
        } else {
            setAvatarMessage('', '');
        }
    };

    var sendAvatarRequest = function (formData, progressKey) {
        if (!avatarSettings || !avatarSettings.ajaxUrl || !formData || typeof formData.append !== 'function') {
            return;
        }

        if (avatarSettings.nonce) {
            formData.append('nonce', avatarSettings.nonce);
        }

        var progressMessage = getAvatarMessage(progressKey);

        setAvatarMessage('', '');

        if (progressMessage) {
            setAvatarMessage(progressMessage, '');
        }

        toggleAvatarLoading(true);

        fetch(avatarSettings.ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            body: formData,
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error(getAvatarMessage('error'));
                }

                return response.json();
            })
            .then(function (data) {
                if (!data || typeof data.success === 'undefined') {
                    throw new Error(getAvatarMessage('error'));
                }

                if (!data.success) {
                    var errorMessage = data.data && data.data.message ? data.data.message : getAvatarMessage('error');
                    throw new Error(errorMessage || getAvatarMessage('error'));
                }

                handleAvatarResponse(data.data || {});
            })
            .catch(function (error) {
                var fallback = getAvatarMessage('error');
                var message = error && error.message ? error.message : fallback;
                setAvatarMessage(message, 'error');
            })
            .finally(function () {
                toggleAvatarLoading(false);
            });
    };

    var onTwoFactorToggle = function (event) {
        event.preventDefault();

        if (!accountSettings || !accountSettings.ajaxUrl) {
            return;
        }

        var elements = securityElements.twoFactor;

        if (!elements.container || !elements.toggle) {
            return;
        }

        var current = elements.container.getAttribute('data-lae-2fa-enabled');
        var isEnabled = current === '1' || current === 'true';
        var enableNext = !isEnabled;
        var nonce = accountSettings.nonces && accountSettings.nonces.twoFactor ? accountSettings.nonces.twoFactor : '';

        if (!nonce) {
            setStatusMessage(elements.message, getAccountMessage('twoFactorError'), 'error');
            return;
        }

        var pendingMessage = enableNext ? getAccountMessage('twoFactorEnabling') : getAccountMessage('twoFactorDisabling');
        setStatusMessage(elements.message, pendingMessage, '');
        setCardBusy(elements.container, true);

        var formData = new FormData();
        formData.append('action', 'lae_toggle_two_factor');
        formData.append('enable', enableNext ? '1' : '0');
        formData.append('nonce', nonce);

        fetch(accountSettings.ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            body: formData,
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error(getAccountMessage('twoFactorError'));
                }

                return response.json();
            })
            .then(function (data) {
                if (!data || typeof data.success === 'undefined') {
                    throw new Error(getAccountMessage('twoFactorError'));
                }

                if (!data.success) {
                    var errorMessage = data.data && data.data.message ? data.data.message : getAccountMessage('twoFactorError');
                    throw new Error(errorMessage || getAccountMessage('twoFactorError'));
                }

                var payload = data.data || {};
                var enabledValue = payload.enabled;
                var finalEnabled = false;

                if (typeof enabledValue === 'string') {
                    finalEnabled = enabledValue === '1' || enabledValue === 'true';
                } else {
                    finalEnabled = !!enabledValue;
                }

                updateTwoFactorUI(finalEnabled, payload.status || '');

                var successMessage = payload.message;

                if (!successMessage) {
                    successMessage = finalEnabled ? getAccountMessage('twoFactorEnabled') : getAccountMessage('twoFactorDisabled');
                }

                setStatusMessage(elements.message, successMessage, 'success');
            })
            .catch(function (error) {
                var fallback = getAccountMessage('twoFactorError');
                var message = error && error.message ? error.message : fallback;
                setStatusMessage(elements.message, message, 'error');
            })
            .finally(function () {
                setCardBusy(elements.container, false);
            });
    };

    var onPasswordSubmit = function (event) {
        event.preventDefault();

        if (!accountSettings || !accountSettings.ajaxUrl) {
            return;
        }

        var elements = securityElements.password;

        if (!elements.form) {
            return;
        }

        var currentInput = elements.form.querySelector('input[name="current_password"]');
        var newInput = elements.form.querySelector('input[name="new_password"]');
        var confirmInput = elements.form.querySelector('input[name="confirm_password"]');

        if (!currentInput || !newInput || !confirmInput) {
            return;
        }

        var currentValue = currentInput.value || '';
        var newValue = newInput.value || '';
        var confirmValue = confirmInput.value || '';

        setStatusMessage(elements.message, '', '');

        if (typeof elements.form.reportValidity === 'function' && !elements.form.reportValidity()) {
            return;
        }

        if (newValue.length < 8) {
            setStatusMessage(elements.message, getAccountMessage('passwordWeak'), 'error');
            return;
        }

        if (newValue !== confirmValue) {
            setStatusMessage(elements.message, getAccountMessage('passwordMismatch'), 'error');
            return;
        }

        setStatusMessage(elements.message, getAccountMessage('passwordWorking'), '');
        setCardBusy(elements.form, true);

        var nonce = accountSettings.nonces && accountSettings.nonces.password ? accountSettings.nonces.password : '';

        if (!nonce) {
            setStatusMessage(elements.message, getAccountMessage('passwordError'), 'error');
            setCardBusy(elements.form, false);
            return;
        }

        var formData = new FormData();
        formData.append('action', 'lae_change_password');
        formData.append('current_password', currentValue);
        formData.append('new_password', newValue);
        formData.append('confirm_password', confirmValue);
        formData.append('nonce', nonce);

        fetch(accountSettings.ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            body: formData,
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error(getAccountMessage('passwordError'));
                }

                return response.json();
            })
            .then(function (data) {
                if (!data || typeof data.success === 'undefined') {
                    throw new Error(getAccountMessage('passwordError'));
                }

                if (!data.success) {
                    var errorMessage = data.data && data.data.message ? data.data.message : getAccountMessage('passwordError');
                    throw new Error(errorMessage || getAccountMessage('passwordError'));
                }

                if (typeof elements.form.reset === 'function') {
                    elements.form.reset();
                }

                var successMessage = data.data && data.data.message ? data.data.message : getAccountMessage('passwordSuccess');
                setStatusMessage(elements.message, successMessage, 'success');
            })
            .catch(function (error) {
                var fallback = getAccountMessage('passwordError');
                var message = error && error.message ? error.message : fallback;
                setStatusMessage(elements.message, message, 'error');
            })
            .finally(function () {
                setCardBusy(elements.form, false);
            });
    };

    var onAvatarInputChange = function () {
        if (!avatarManager.input || !avatarManager.input.files || !avatarManager.input.files.length) {
            return;
        }

        var file = avatarManager.input.files[0];
        avatarManager.input.value = '';

        if (!file) {
            return;
        }

        var formData = new FormData();
        formData.append('action', 'lae_upload_avatar');
        formData.append('avatar', file);

        sendAvatarRequest(formData, 'uploading');
    };

    var onAvatarRemoveClick = function (event) {
        event.preventDefault();

        var formData = new FormData();
        formData.append('action', 'lae_remove_avatar');

        sendAvatarRequest(formData, 'removing');
    };

    var initAvatarManager = function () {
        var container = document.querySelector('[data-lae-avatar-manager]');

        if (!container || typeof window === 'undefined' || typeof window.LAEAvatar === 'undefined') {
            return;
        }

        avatarSettings = window.LAEAvatar;

        avatarManager.container = container;
        avatarManager.input = container.querySelector('[data-lae-avatar-input]');
        avatarManager.uploadButton = container.querySelector('[data-lae-avatar-upload]');
        avatarManager.removeButton = container.querySelector('[data-lae-avatar-remove]');
        avatarManager.message = container.querySelector('[data-lae-avatar-message]');
        avatarManager.preview = container.querySelector('[data-lae-avatar-preview]');

        if (avatarManager.uploadButton && avatarManager.input) {
            avatarManager.uploadButton.addEventListener('click', function (event) {
                event.preventDefault();
                avatarManager.input.click();
            });

            avatarManager.input.addEventListener('change', onAvatarInputChange);
        }

        if (avatarManager.removeButton) {
            avatarManager.removeButton.addEventListener('click', onAvatarRemoveClick);
        }
    };

    var initAccountSecurity = function () {
        if (typeof window === 'undefined' || typeof window.LAEAccount === 'undefined') {
            return;
        }

        accountSettings = window.LAEAccount;

        var twoFactorContainer = document.querySelector('[data-lae-two-factor]');

        if (twoFactorContainer) {
            securityElements.twoFactor.container = twoFactorContainer;
            securityElements.twoFactor.toggle = twoFactorContainer.querySelector('[data-lae-2fa-toggle]');
            securityElements.twoFactor.status = twoFactorContainer.querySelector('[data-lae-2fa-status]');
            securityElements.twoFactor.message = twoFactorContainer.querySelector('[data-lae-2fa-message]');

            if (securityElements.twoFactor.toggle) {
                securityElements.twoFactor.toggle.addEventListener('click', onTwoFactorToggle);
            }
        }

        var passwordForm = document.querySelector('[data-lae-password-form]');

        if (passwordForm) {
            securityElements.password.form = passwordForm;
            securityElements.password.message = passwordForm.querySelector('[data-lae-password-message]');
            securityElements.password.submit = passwordForm.querySelector('[data-lae-password-submit]');

            passwordForm.addEventListener('submit', onPasswordSubmit);
        }
    };

    var onDropdownClick = function (event) {
        var target = event.target.closest('[role="menuitem"]');

        if (!target) {
            return;
        }

        var menu = target.closest('[data-lae-menu]');

        if (!menu) {
            return;
        }

        setMenuState(menu, false);
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

            if (loginElements.modal && loginElements.modal.classList.contains('is-visible')) {
                closeLoginModal();
            }
        }
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
                dropdown.addEventListener('click', onDropdownClick);
            }
        });

        document.addEventListener('click', onDocumentClick);
        document.addEventListener('keydown', onDocumentKeyDown);

        initAvatarManager();
        initAccountSecurity();
        initLoginModal();
    });
})();
