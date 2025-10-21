(function () {
    'use strict';

    var menus = [];
    var avatarSettings = null;
    var avatarManager = {
        container: null,
        input: null,
        uploadButton: null,
        removeButton: null,
        message: null,
        preview: null,
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
    });
})();
