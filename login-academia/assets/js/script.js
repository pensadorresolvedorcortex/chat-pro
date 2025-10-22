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
    var initialChallenge = loginSettings && loginSettings.challenge ? loginSettings.challenge : null;
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
        loginPasswordWrap: null,
        registerFields: null,
        twoFactor: null,
        twoFactorHint: null,
        twoFactorResend: null,
        codeInput: null,
        loginInput: null,
        passwordToggles: [],
        passwordStrength: {
            input: null,
            output: null,
            confirm: null,
            mismatch: null,
        },
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
        context: {
            flow: 'login',
            challenge: '',
            masked_email: '',
            resend_in: 0,
            ttl: 0,
            ttl_label: '',
        },
        redirectTimer: null,
        countdowns: {
            resend: null,
            ttl: null,
        },
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
                return;
            }

            var keepDisabled = false;

            if (loginState.awaitingTwoFactor) {
                var flow = loginState.context && loginState.context.flow ? loginState.context.flow : 'login';

                if (flow === 'login') {
                    if (control.name === 'login' || control.name === 'password') {
                        keepDisabled = true;
                    }
                } else if (flow === 'register') {
                    if (control.closest('[data-lae-register-fields]')) {
                        keepDisabled = true;
                    }
                }
            }

            if (!keepDisabled) {
                control.removeAttribute('disabled');
            }
        });
    };

    var getPasswordLabels = function () {
        return loginSettings && loginSettings.labels ? loginSettings.labels : {};
    };

    var getPasswordToggleLabel = function (isVisible) {
        var labels = getPasswordLabels();

        if (isVisible) {
            return labels.hidePassword || 'Ocultar';
        }

        return labels.showPassword || 'Mostrar';
    };

    var setLoginFieldValidity = function (input, isValid) {
        if (!input) {
            return;
        }

        var field = input.closest('.lae-login-field');

        if (!field) {
            return;
        }

        if (isValid) {
            field.classList.remove('is-invalid');
            input.removeAttribute('aria-invalid');
        } else {
            field.classList.add('is-invalid');
            input.setAttribute('aria-invalid', 'true');
        }
    };

    var setupPasswordToggles = function () {
        if (!loginElements.modal) {
            return;
        }

        loginElements.passwordToggles = Array.prototype.slice.call(
            loginElements.modal.querySelectorAll('[data-lae-password-toggle]')
        );

        if (!loginElements.passwordToggles.length) {
            return;
        }

        loginElements.passwordToggles.forEach(function (toggle) {
            var field = toggle.closest('[data-lae-password-field]');
            var input = field ? field.querySelector('input') : null;

            if (!input) {
                return;
            }

            var updateLabel = function (visible) {
                var labelNode = toggle.querySelector('[data-lae-password-toggle-label]');

                if (labelNode) {
                    labelNode.textContent = getPasswordToggleLabel(visible);
                }
            };

            updateLabel(false);

            toggle.addEventListener('click', function (event) {
                event.preventDefault();
                var isVisible = input.getAttribute('type') === 'text';
                input.setAttribute('type', isVisible ? 'password' : 'text');
                toggle.setAttribute('aria-pressed', isVisible ? 'false' : 'true');
                updateLabel(!isVisible);

                if (!isVisible) {
                    input.focus();

                    if (typeof input.selectionStart !== 'undefined') {
                        var length = input.value.length;
                        input.setSelectionRange(length, length);
                    }
                }
            });
        });
    };

    var calculatePasswordScore = function (value) {
        if (!value) {
            return 0;
        }

        var score = 0;

        if (value.length >= 8) {
            score += 1;
        }

        if (value.length >= 12) {
            score += 1;
        }

        if (/[A-Z]/.test(value)) {
            score += 1;
        }

        if (/[0-9]/.test(value)) {
            score += 1;
        }

        if (/[^A-Za-z0-9]/.test(value)) {
            score += 1;
        }

        return score;
    };

    var getStrengthLevel = function (score) {
        if (score <= 1) {
            return 'very-weak';
        }

        if (score === 2) {
            return 'weak';
        }

        if (score === 3) {
            return 'medium';
        }

        return 'strong';
    };

    var getStrengthConfig = function () {
        return loginSettings && loginSettings.strength ? loginSettings.strength : {};
    };

    var updatePasswordStrength = function () {
        var input = loginElements.passwordStrength.input;
        var output = loginElements.passwordStrength.output;

        if (!input || !output) {
            return;
        }

        var value = input.value || '';
        var baseClass = 'lae-password-strength';
        output.className = baseClass;

        if (!value) {
            output.textContent = '';
            setLoginFieldValidity(input, true);
            return;
        }

        var score = calculatePasswordScore(value);
        var level = getStrengthLevel(score);
        var config = getStrengthConfig();
        var labels = config.labels || {};
        var hints = config.hints || {};
        var label = labels[level] || '';
        var hint = hints[level] || '';
        var message = label;

        if (hint) {
            message = message ? message + ' · ' + hint : hint;
        }

        output.textContent = message;

        if (level) {
            output.className += ' is-' + level;
        }

        var meetsThreshold = score >= 3 && value.length >= 8;
        setLoginFieldValidity(input, meetsThreshold);
    };

    var isPasswordStrongEnough = function (value) {
        if (!value || value.length < 8) {
            return false;
        }

        return calculatePasswordScore(value) >= 3;
    };

    var updatePasswordMismatchMessage = function () {
        var confirmInput = loginElements.passwordStrength.confirm;
        var passwordInput = loginElements.passwordStrength.input;
        var messageNode = loginElements.passwordStrength.mismatch;

        if (!confirmInput || !messageNode) {
            return;
        }

        var passwordValue = passwordInput ? passwordInput.value : '';
        var confirmValue = confirmInput.value || '';
        var mismatch = !!confirmValue && passwordValue !== confirmValue;

        if (mismatch) {
            messageNode.textContent = getLoginMessage('passwordMismatch') || '';
            messageNode.hidden = false;
        } else {
            messageNode.textContent = '';
            messageNode.hidden = true;
        }

        setLoginFieldValidity(confirmInput, !mismatch);
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

    var getTwoFactorExpiryLabel = function (seconds) {
        var formatted = formatWaitDuration(seconds);

        if (!formatted) {
            return '';
        }

        var template = getLoginMessage('twoFactorExpires');

        if (template && template.indexOf('%s') !== -1) {
            return template.replace('%s', formatted);
        }

        if (template) {
            return template + ' ' + formatted;
        }

        return 'O código expira em ' + formatted;
    };

    var setActiveTwoFactorElements = function (flow) {
        var target = flow === 'register' ? loginElements.forms.register : loginElements.forms.login;

        if (flow === 'register') {
            loginElements.twoFactor = target ? target.querySelector('[data-lae-register-2fa]') : null;
            loginElements.twoFactorHint = target ? target.querySelector('[data-lae-register-2fa-hint]') : null;
            loginElements.twoFactorResend = target ? target.querySelector('[data-lae-register-2fa-resend]') : null;
            loginElements.codeInput = target ? target.querySelector('[data-lae-register-2fa-input]') : null;
        } else {
            loginElements.twoFactor = target ? target.querySelector('[data-lae-login-2fa]') : null;
            loginElements.twoFactorHint = target ? target.querySelector('[data-lae-login-2fa-hint]') : null;
            loginElements.twoFactorResend = target ? target.querySelector('[data-lae-login-2fa-resend]') : null;
            loginElements.codeInput = target ? target.querySelector('input[name="code"]') : null;
        }
    };

    var applyTwoFactorState = function () {
        var flow = loginState.context && loginState.context.flow ? loginState.context.flow : 'login';
        var loginInput = loginElements.loginInput;
        var passwordInput = loginElements.forms.login ? loginElements.forms.login.querySelector('input[name="password"]') : null;
        var registerControls = loginElements.registerFields
            ? loginElements.registerFields.querySelectorAll('input, select, textarea, button')
            : [];

        if (flow === 'login' && loginState.awaitingTwoFactor) {
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

        if (!registerControls.length) {
            return;
        }

        Array.prototype.forEach.call(registerControls, function (control) {
            if (loginState.awaitingTwoFactor && flow === 'register') {
                control.setAttribute('disabled', 'disabled');
            } else {
                control.removeAttribute('disabled');
            }
        });
    };

    var clearTwoFactorCountdowns = function () {
        if (loginState.countdowns.resend) {
            window.clearInterval(loginState.countdowns.resend);
            loginState.countdowns.resend = null;
        }

        if (loginState.countdowns.ttl) {
            window.clearInterval(loginState.countdowns.ttl);
            loginState.countdowns.ttl = null;
        }
    };

    var updateResendAvailability = function () {
        if (!loginElements.twoFactorResend) {
            return;
        }

        var challenge = loginState.context && loginState.context.challenge ? loginState.context.challenge : '';
        var wait = loginState.context && typeof loginState.context.resend_in !== 'undefined'
            ? parseInt(loginState.context.resend_in, 10)
            : 0;

        if (isNaN(wait)) {
            wait = 0;
        }

        if (!challenge) {
            loginElements.twoFactorResend.hidden = true;
            loginElements.twoFactorResend.disabled = true;
            delete loginElements.twoFactorResend.dataset.challenge;
            return;
        }

        loginElements.twoFactorResend.dataset.challenge = challenge;

        if (wait > 0) {
            loginElements.twoFactorResend.hidden = true;
            loginElements.twoFactorResend.disabled = true;
        } else {
            loginElements.twoFactorResend.hidden = false;
            loginElements.twoFactorResend.disabled = false;
        }
    };

    var updateTwoFactorHint = function () {
        if (!loginElements.twoFactorHint) {
            return;
        }

        var context = loginState.context || {};
        var hint = '';

        if (context.ttl && context.ttl > 0) {
            hint = getTwoFactorExpiryLabel(context.ttl) || context.ttl_label || '';
        } else if (context.ttl_label) {
            hint = context.ttl_label;
        } else if (context.resend_in && context.resend_in > 0) {
            var waitLabel = getLoginMessage('resendWait') || '';
            var suffix = formatWaitDuration(context.resend_in);
            hint = waitLabel;

            if (suffix) {
                hint = waitLabel ? waitLabel + ' (' + suffix + ')' : suffix;
            }
        } else if (context.masked_email) {
            var base = getLoginMessage('twoFactorRequired') || '';
            hint = base ? base + ' ' + context.masked_email : context.masked_email;
        } else {
            hint = getLoginMessage('twoFactorRequired') || '';
        }

        loginElements.twoFactorHint.textContent = hint;
    };

    var scheduleTwoFactorCountdowns = function () {
        clearTwoFactorCountdowns();
        updateResendAvailability();
        updateTwoFactorHint();

        var wait = loginState.context && typeof loginState.context.resend_in !== 'undefined'
            ? parseInt(loginState.context.resend_in, 10)
            : 0;

        if (isNaN(wait)) {
            wait = 0;
        }

        if (wait > 0) {
            loginState.countdowns.resend = window.setInterval(function () {
                wait -= 1;

                if (wait <= 0) {
                    window.clearInterval(loginState.countdowns.resend);
                    loginState.countdowns.resend = null;
                    loginState.context.resend_in = 0;
                    updateResendAvailability();
                    updateTwoFactorHint();
                    return;
                }

                loginState.context.resend_in = wait;
                updateTwoFactorHint();
            }, 1000);
        }

        var ttl = loginState.context && typeof loginState.context.ttl !== 'undefined'
            ? parseInt(loginState.context.ttl, 10)
            : 0;

        if (isNaN(ttl)) {
            ttl = 0;
        }

        if (ttl > 0) {
            loginState.countdowns.ttl = window.setInterval(function () {
                ttl -= 1;

                if (ttl <= 0) {
                    window.clearInterval(loginState.countdowns.ttl);
                    loginState.countdowns.ttl = null;
                    loginState.context.ttl = 0;
                    loginState.context.ttl_label = '';
                    updateTwoFactorHint();
                    updateResendAvailability();
                    setLoginMessage(getLoginMessage('twoFactorExpired') || getLoginMessage('error'), 'error');

                    if (loginElements.codeInput) {
                        loginElements.codeInput.value = '';
                        loginElements.codeInput.focus();
                    }

                    return;
                }

                loginState.context.ttl = ttl;
                loginState.context.ttl_label = getTwoFactorExpiryLabel(ttl);
                updateTwoFactorHint();
            }, 1000);
        }
    };

    var hideTwoFactorStep = function () {
        clearTwoFactorCountdowns();
        loginState.awaitingTwoFactor = false;
        loginState.context = {
            flow: 'login',
            challenge: '',
            masked_email: '',
            resend_in: 0,
            ttl: 0,
            ttl_label: '',
        };

        if (loginElements.forms.login) {
            var loginOtp = loginElements.forms.login.querySelector('[data-lae-login-2fa]');
            var loginHint = loginElements.forms.login.querySelector('[data-lae-login-2fa-hint]');
            var loginResend = loginElements.forms.login.querySelector('[data-lae-login-2fa-resend]');
            var loginCode = loginElements.forms.login.querySelector('input[name="code"]');

            if (loginOtp) {
                loginOtp.setAttribute('hidden', 'hidden');
            }

            if (loginElements.loginPasswordWrap) {
                loginElements.loginPasswordWrap.removeAttribute('hidden');
            }

            if (loginHint) {
                loginHint.textContent = '';
            }

            if (loginResend) {
                loginResend.hidden = true;
                loginResend.disabled = false;
                delete loginResend.dataset.challenge;
            }

            if (loginCode) {
                loginCode.value = '';
            }
        }

        if (loginElements.forms.register) {
            var registerOtp = loginElements.forms.register.querySelector('[data-lae-register-2fa]');
            var registerHint = loginElements.forms.register.querySelector('[data-lae-register-2fa-hint]');
            var registerResend = loginElements.forms.register.querySelector('[data-lae-register-2fa-resend]');
            var registerCode = loginElements.forms.register.querySelector('[data-lae-register-2fa-input]');

            if (registerOtp) {
                registerOtp.setAttribute('hidden', 'hidden');
            }

            if (loginElements.registerFields) {
                loginElements.registerFields.removeAttribute('hidden');

                var controls = loginElements.registerFields.querySelectorAll('input, select, textarea, button');
                Array.prototype.forEach.call(controls, function (control) {
                    if (typeof control.disabled !== 'undefined') {
                        control.disabled = false;
                    }
                });
            }

            if (registerHint) {
                registerHint.textContent = '';
            }

            if (registerResend) {
                registerResend.hidden = true;
                registerResend.disabled = false;
                delete registerResend.dataset.challenge;
            }

            if (registerCode) {
                registerCode.value = '';
            }
        }

        setActiveTwoFactorElements(loginState.activeTab || 'login');

        applyTwoFactorState();
    };

    var updateTwoFactorControls = function (context) {
        context = context || {};
        var flow = context.flow || loginState.context.flow || loginState.activeTab || 'login';

        loginState.context = Object.assign({}, loginState.context, context, {
            flow: flow,
        });

        setActiveTwoFactorElements(flow);

        if (typeof context.challenge !== 'undefined') {
            loginState.context.challenge = context.challenge;
        }

        if (typeof context.resend_in !== 'undefined') {
            var wait = parseInt(context.resend_in, 10);
            loginState.context.resend_in = isNaN(wait) ? 0 : wait;
        }

        if (typeof context.ttl !== 'undefined') {
            var ttl = parseInt(context.ttl, 10);
            loginState.context.ttl = isNaN(ttl) ? 0 : ttl;

            if (isNaN(ttl) || ttl <= 0) {
                loginState.context.ttl_label = '';
            }
        }

        if (typeof context.ttl_label !== 'undefined') {
            loginState.context.ttl_label = context.ttl_label || '';
        }

        if (typeof context.masked_email !== 'undefined') {
            loginState.context.masked_email = context.masked_email || '';
        }

        scheduleTwoFactorCountdowns();
    };

    var showTwoFactorStep = function (context, message, login, flow) {
        flow = flow || loginState.context.flow || loginState.activeTab || 'login';
        activateLoginTab(flow);
        setActiveTwoFactorElements(flow);
        clearTwoFactorCountdowns();

        loginState.awaitingTwoFactor = true;
        loginState.context = Object.assign({}, loginState.context, context || {}, {
            flow: flow,
        });

        if (typeof login === 'string' && login) {
            loginState.credentials.login = login;
        }

        if (flow === 'login') {
            if (loginElements.loginPasswordWrap) {
                loginElements.loginPasswordWrap.setAttribute('hidden', 'hidden');
            }

            if (loginElements.forms.login) {
                var passwordInput = loginElements.forms.login.querySelector('input[name="password"]');

                if (passwordInput) {
                    passwordInput.value = '';
                }
            }
        } else if (flow === 'register' && loginElements.registerFields) {
            loginElements.registerFields.setAttribute('hidden', 'hidden');

            var controls = loginElements.registerFields.querySelectorAll('input, select, textarea, button');
            Array.prototype.forEach.call(controls, function (control) {
                if (typeof control.disabled !== 'undefined') {
                    control.disabled = true;
                }
            });
        }

        if (loginElements.twoFactor) {
            loginElements.twoFactor.removeAttribute('hidden');
        }

        if (loginElements.twoFactorResend) {
            loginElements.twoFactorResend.hidden = true;
            loginElements.twoFactorResend.disabled = true;
        }

        if (loginElements.codeInput) {
            loginElements.codeInput.value = '';
            loginElements.codeInput.focus();
        }

        updateTwoFactorControls(context || {});
        applyTwoFactorState();

        setLoginMessage(message || getLoginMessage('twoFactorRequired'), 'info');
    };

    var bootstrapChallenge = function (challenge) {
        if (!challenge || !challenge.challenge) {
            hideTwoFactorStep();
            activateLoginTab('login');
            return;
        }

        var flow = challenge.type === 'register' ? 'register' : 'login';
        var identifier = challenge.identifier || '';
        var message = challenge.message || getLoginMessage('twoFactorRequired');
        var context = {
            challenge: challenge.challenge,
            masked_email: challenge.masked_email || '',
            resend_in: challenge.resend_in || 0,
            ttl: challenge.ttl || 0,
            ttl_label: challenge.ttl_label || '',
            flow: flow,
        };

        loginState.credentials.login = identifier;
        loginState.credentials.redirect = challenge.redirect || (loginSettings ? loginSettings.redirect : '');
        loginState.credentials.remember = !!challenge.remember;
        loginState.credentials.friendlyName = challenge.friendly_name || '';

        if (flow === 'login' && loginElements.loginInput && identifier) {
            loginElements.loginInput.value = identifier;
        }

        if (flow === 'register' && loginElements.registerFields) {
            var nameInput = loginElements.registerFields.querySelector('input[name="name"]');
            var emailInput = loginElements.registerFields.querySelector('input[name="email"]');

            if (nameInput && challenge.pending_name) {
                nameInput.value = challenge.pending_name;
            }

            if (emailInput && challenge.pending_email) {
                emailInput.value = challenge.pending_email;
            }
        }

        showTwoFactorStep(context, message, identifier, flow);
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

    var resetLoginForms = function (preserveChallenge) {
        if (loginState.redirectTimer) {
            clearTimeout(loginState.redirectTimer);
            loginState.redirectTimer = null;
        }

        if (preserveChallenge && loginState.awaitingTwoFactor) {
            if (loginElements.codeInput) {
                loginElements.codeInput.value = '';
            }

            setLoginMessage('', '');
            clearTwoFactorCountdowns();
            scheduleTwoFactorCountdowns();
            applyTwoFactorState();

            return;
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
        resetLoginForms(true);

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
        resetLoginForms(true);
    };

    var getAccountMessage = function (key) {
        if (!accountSettings || !accountSettings.messages) {
            return '';
        }

        return accountSettings.messages[key] || '';
    };

    var handleLoginPayload = function (payload, flow) {
        if (!payload) {
            setLoginMessage(getLoginMessage('error'), 'error');
            return;
        }

        flow = flow || loginState.context.flow || loginState.activeTab || 'login';

        if (payload.status === 'two_factor_required') {
            if (payload.login) {
                loginState.credentials.login = payload.login;
            }

            if (typeof payload.redirect === 'string' && payload.redirect) {
                loginState.credentials.redirect = payload.redirect;
            }

            var context = payload.context || {};

            context.flow = flow;

            if (payload.challenge && !context.challenge) {
                context.challenge = payload.challenge;
            }

            if (payload.email && !context.masked_email) {
                context.masked_email = payload.email;
            }

            showTwoFactorStep(context, payload.message || getLoginMessage('twoFactorRequired'), payload.login, flow);
            return;
        }

        if (payload.status === 'registered' || payload.status === 'logged_in') {
            var redirect = payload.redirect || (loginSettings ? loginSettings.redirect : '');
            var message = payload.message || getLoginMessage('success');

            setLoginMessage(message, 'success');

            hideTwoFactorStep();

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

        var flow = loginState.context && loginState.context.flow ? loginState.context.flow : 'login';

        if (loginState.awaitingTwoFactor && flow !== 'login') {
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
            formData.append('challenge', loginState.context && loginState.context.challenge ? loginState.context.challenge : '');
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

                    if (payload.status === 'two_factor_invalid' || payload.status === 'two_factor_required' || payload.status === 'two_factor_expired') {
                        var context = payload.context || {};
                        context.flow = 'login';

                        if (payload.challenge && !context.challenge) {
                            context.challenge = payload.challenge;
                        }

                        showTwoFactorStep(context, payload.message || getLoginMessage(payload.status === 'two_factor_expired' ? 'twoFactorExpired' : 'twoFactorInvalid'), payload.login || loginState.credentials.login, 'login');
                        if (payload.status === 'two_factor_invalid') {
                            setLoginMessage(payload.message || getLoginMessage('twoFactorInvalid'), 'error');
                        }

                        return;
                    }

                    throw new Error(payload.message || getLoginMessage('error'));
                }

                handleLoginPayload(result.data || {}, 'login');
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

        var redirectInput = form.querySelector('input[name="redirect_to"]');
        var redirectValue = redirectInput ? redirectInput.value : '';
        if (loginState.credentials && loginState.credentials.redirect && !redirectValue) {
            redirectValue = loginState.credentials.redirect;
        }
        var awaitingRegister = loginState.awaitingTwoFactor && (loginState.context.flow === 'register' || loginState.activeTab === 'register');
        var formData = new FormData();

        formData.append('action', 'lae_register_user');
        formData.append('nonce', loginSettings.nonces.register);

        if (awaitingRegister) {
            var codeInput = form.querySelector('[data-lae-register-2fa-input]');
            var codeValue = codeInput ? codeInput.value.trim() : '';
            var challenge = loginState.context && loginState.context.challenge ? loginState.context.challenge : '';

            if (!codeValue) {
                setLoginMessage(getLoginMessage('twoFactorRequired') || getLoginMessage('missingFields'), 'error');

                if (codeInput) {
                    codeInput.focus();
                }

                return;
            }

            if (!challenge) {
                setLoginMessage(getLoginMessage('error'), 'error');
                return;
            }

            if (loginState.credentials && loginState.credentials.redirect) {
                redirectValue = loginState.credentials.redirect;
            }

            formData.append('challenge', challenge);
            formData.append('code', codeValue);
            formData.append('redirect_to', redirectValue || '');
        } else {
            var nameInput = form.querySelector('input[name="name"]');
            var emailInput = form.querySelector('input[name="email"]');
            var passwordInput = form.querySelector('input[name="password"]');
            var confirmInput = form.querySelector('input[name="confirm"]');

            var name = nameInput ? nameInput.value.trim() : '';
            var email = emailInput ? emailInput.value.trim() : '';
            var password = passwordInput ? passwordInput.value : '';
            var confirm = confirmInput ? confirmInput.value : '';

            if (passwordInput) {
                setLoginFieldValidity(passwordInput, true);
            }

            if (confirmInput) {
                setLoginFieldValidity(confirmInput, true);
            }

            if (!name || !email || !password || !confirm) {
                setLoginMessage(getLoginMessage('missingFields'), 'error');
                return;
            }

            if (!isPasswordStrongEnough(password)) {
                setLoginMessage(getLoginMessage('passwordStronger') || getLoginMessage('passwordWeak'), 'error');

                if (passwordInput) {
                    setLoginFieldValidity(passwordInput, false);
                }

                updatePasswordStrength();
                return;
            }

            if (password !== confirm) {
                setLoginMessage(getLoginMessage('passwordMismatch') || getLoginMessage('error'), 'error');

                updatePasswordMismatchMessage();
                return;
            }

            if (loginState.credentials) {
                loginState.credentials.redirect = redirectValue || '';
            }

            formData.append('name', name);
            formData.append('email', email);
            formData.append('password', password);
            formData.append('confirm', confirm);
            formData.append('redirect_to', redirectValue || '');
        }

        setLoginFormBusy(form, true);
        setLoginMessage(awaitingRegister ? getLoginMessage('loginWorking') : getLoginMessage('registerWorking'), 'info');

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

                    if (payload.status === 'two_factor_required' || payload.status === 'two_factor_invalid' || payload.status === 'two_factor_expired') {
                        var context = payload.context || {};
                        context.flow = 'register';

                        if (payload.challenge && !context.challenge) {
                            context.challenge = payload.challenge;
                        }

                        if (payload.email && !context.masked_email) {
                            context.masked_email = payload.email;
                        }

                        showTwoFactorStep(context, payload.message || getLoginMessage('twoFactorRequired'), '', 'register');

                        if (payload.status === 'two_factor_invalid') {
                            setLoginMessage(payload.message || getLoginMessage('twoFactorInvalid'), 'error');
                        } else if (payload.status === 'two_factor_expired') {
                            setLoginMessage(payload.message || getLoginMessage('twoFactorExpired'), 'error');
                        }

                        return;
                    }

                    if (payload.status === 'registration_disabled') {
                        throw new Error(getLoginMessage('registrationClosed'));
                    }

                    throw new Error(payload.message || getLoginMessage('error'));
                }

                handleLoginPayload(result.data || {}, 'register');
            })
            .catch(function (error) {
                var message = error && error.message ? error.message : getLoginMessage('error');
                setLoginMessage(message, 'error');
            })
            .finally(function () {
                setLoginFormBusy(form, false);
                applyTwoFactorState();
            });
    };

    var onTwoFactorResend = function (event) {
        event.preventDefault();

        if (!loginSettings || !loginSettings.ajaxUrl || !loginSettings.nonces || !loginSettings.nonces.resend) {
            return;
        }

        var trigger = event.currentTarget || loginElements.twoFactorResend;
        var challenge = trigger && trigger.dataset ? trigger.dataset.challenge : '';

        if (!challenge) {
            challenge = loginState.context && loginState.context.challenge ? loginState.context.challenge : '';
        }

        if (!challenge) {
            setLoginMessage(getLoginMessage('resendWait') || getLoginMessage('error'), 'error');
            return;
        }

        if (trigger) {
            trigger.disabled = true;
        }

        var formData = new FormData();
        formData.append('action', 'lae_resend_two_factor');
        formData.append('nonce', loginSettings.nonces.resend);
        formData.append('challenge', challenge);

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

                var responseContext = result.data && result.data.context ? result.data.context : {};

                if (loginState.context && loginState.context.flow) {
                    responseContext.flow = loginState.context.flow;
                }

                updateTwoFactorControls(responseContext);
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
                var wait = loginState.context && typeof loginState.context.resend_in !== 'undefined' ? parseInt(loginState.context.resend_in, 10) : 0;

                if (trigger && (!wait || wait <= 0)) {
                    trigger.disabled = false;
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
            loginElements.loginPasswordWrap = loginElements.forms.login.querySelector('[data-lae-login-password]');
            loginElements.twoFactor = loginElements.forms.login.querySelector('[data-lae-login-2fa]');
            loginElements.twoFactorHint = loginElements.forms.login.querySelector('[data-lae-login-2fa-hint]');
            loginElements.twoFactorResend = loginElements.forms.login.querySelector('[data-lae-login-2fa-resend]');
            loginElements.codeInput = loginElements.forms.login.querySelector('input[name="code"]');
            loginElements.loginInput = loginElements.forms.login.querySelector('input[name="login"]');

            loginElements.forms.login.addEventListener('submit', submitLoginForm);

            if (loginElements.twoFactorResend) {
                loginElements.twoFactorResend.addEventListener('click', onTwoFactorResend);
            }
        }

        if (loginElements.forms.register) {
            loginElements.registerFields = loginElements.forms.register.querySelector('[data-lae-register-fields]');
            loginElements.passwordStrength.input = loginElements.forms.register.querySelector('input[name="password"]');
            loginElements.passwordStrength.output = loginElements.forms.register.querySelector('[data-lae-password-strength]');
            loginElements.passwordStrength.confirm = loginElements.forms.register.querySelector('input[name="confirm"]');
            loginElements.passwordStrength.mismatch = loginElements.forms.register.querySelector('[data-lae-password-mismatch]');

            if (loginElements.passwordStrength.input) {
                loginElements.passwordStrength.input.addEventListener('input', function () {
                    updatePasswordStrength();
                    updatePasswordMismatchMessage();
                });
            }

            if (loginElements.passwordStrength.confirm) {
                loginElements.passwordStrength.confirm.addEventListener('input', updatePasswordMismatchMessage);
            }

            loginElements.forms.register.addEventListener('submit', submitRegisterForm);

            var registerResend = loginElements.forms.register.querySelector('[data-lae-register-2fa-resend]');

            if (registerResend) {
                registerResend.addEventListener('click', onTwoFactorResend);
            }
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

        setupPasswordToggles();
        updatePasswordStrength();
        updatePasswordMismatchMessage();

        var datasetContext = null;

        if (loginElements.modal) {
            var contextAttr = loginElements.modal.getAttribute('data-lae-login-context');

            if (!initialChallenge && contextAttr) {
                try {
                    datasetContext = JSON.parse(contextAttr);
                } catch (error) {
                    datasetContext = null;
                }
            }
        }

        var bootContext = initialChallenge || datasetContext;

        if (bootContext && bootContext.challenge) {
            bootstrapChallenge(bootContext);
        } else {
            setActiveTwoFactorElements('login');
            hideTwoFactorStep();
            activateLoginTab('login');
        }
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

    var initCourseFilters = function () {
        var sections = document.querySelectorAll('[data-lae-course-section]');

        if (!sections.length) {
            return;
        }

        Array.prototype.forEach.call(sections, function (section) {
            var filterButtons = section.querySelectorAll('[data-lae-filter-option]');
            var cards = section.querySelectorAll('.lae-course-card');

            if (!filterButtons.length || !cards.length) {
                return;
            }

            var applyFilter = function (value) {
                var target = value || 'all';
                var showAll = target === 'all';

                Array.prototype.forEach.call(filterButtons, function (button) {
                    var buttonValue = button.getAttribute('data-filter-value') || 'all';
                    var isActive = showAll ? buttonValue === 'all' : buttonValue === target;
                    button.classList.toggle('is-active', isActive);
                    button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
                });

                Array.prototype.forEach.call(cards, function (card) {
                    if (showAll) {
                        card.classList.remove('is-hidden');
                        card.removeAttribute('aria-hidden');
                        card.removeAttribute('tabindex');
                        return;
                    }

                    var termList = card.getAttribute('data-lae-course-terms') || '';
                    var terms = termList.split(/\s+/).filter(Boolean);
                    var matches = terms.indexOf(target) !== -1;

                    if (matches) {
                        card.classList.remove('is-hidden');
                        card.removeAttribute('aria-hidden');
                        card.removeAttribute('tabindex');
                    } else {
                        card.classList.add('is-hidden');
                        card.setAttribute('aria-hidden', 'true');
                        card.setAttribute('tabindex', '-1');
                    }
                });

                var grid = section.querySelector('.lae-course-grid');

                if (grid) {
                    grid.classList.toggle('is-filtering', !showAll);
                }
            };

            Array.prototype.forEach.call(filterButtons, function (button) {
                var value = button.getAttribute('data-filter-value') || 'all';

                button.addEventListener('click', function (event) {
                    event.preventDefault();
                    applyFilter(value);
                });

                button.addEventListener('keydown', function (event) {
                    if (event.key === 'Enter' || event.key === ' ') {
                        event.preventDefault();
                        applyFilter(value);
                    }
                });
            });

            var initial = 'all';

            Array.prototype.forEach.call(filterButtons, function (button) {
                if (button.classList.contains('is-active')) {
                    initial = button.getAttribute('data-filter-value') || 'all';
                }
            });

            applyFilter(initial);
        });
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
        initCourseFilters();
        initLoginModal();
    });
})();
