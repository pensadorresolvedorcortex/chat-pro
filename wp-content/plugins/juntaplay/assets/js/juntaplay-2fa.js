(function () {
    if (typeof window.jplay2faData === 'undefined') {
        return;
    }

    const data = window.jplay2faData;
    const trigger = document.querySelector('[data-jplay-2fa-trigger]');
    const modal = document.getElementById('jplay-2fa-modal');

    if (!trigger || !modal) {
        return;
    }

    const closeButtons = modal.querySelectorAll('[data-jplay-close]');
    const statusEl = modal.querySelector('[data-jplay-2fa-status]');
    const feedbackEl = modal.querySelector('[data-jplay-modal-feedback]');
    const form = modal.querySelector('form');
    const codeInput = modal.querySelector('#jplay_2fa_code');
    const resendButton = modal.querySelector('[data-jplay-resend]');
    const submitButton = modal.querySelector('[data-jplay-submit]');
    const globalFeedback = document.querySelector('[data-jplay-2fa-feedback]');

    let lastFocused = null;
    let isModalOpen = false;

    function setLoading(button, loading) {
        if (!button) {
            return;
        }

        button.disabled = loading;
        if (loading) {
            button.classList.add('is-loading');
        } else {
            button.classList.remove('is-loading');
        }
    }

    function setStatus(message) {
        if (statusEl) {
            statusEl.textContent = message || '';
        }
    }

    function setFeedback(message, type) {
        if (!feedbackEl) {
            return;
        }

        feedbackEl.textContent = message || '';
        feedbackEl.classList.remove('is-error', 'is-success');
        if (message && type) {
            feedbackEl.classList.add(type);
        }
    }

    function setGlobalFeedback(message, type) {
        if (!globalFeedback) {
            return;
        }

        globalFeedback.textContent = message || '';
        globalFeedback.classList.remove('is-error', 'is-success');
        if (message && type) {
            globalFeedback.classList.add(type);
        }
    }

    function focusTrap(event) {
        if (!isModalOpen) {
            return;
        }

        const focusableSelectors = 'a[href], button:not([disabled]), textarea, input, select, [tabindex="0"]';
        const focusables = modal.querySelectorAll(focusableSelectors);
        const focusArray = Array.prototype.slice.call(focusables).filter((el) => el.offsetParent !== null);

        if (!focusArray.length) {
            return;
        }

        const first = focusArray[0];
        const last = focusArray[focusArray.length - 1];

        if (event.key === 'Tab') {
            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        } else if (event.key === 'Escape') {
            event.preventDefault();
            closeModal();
        }
    }

    function openModal() {
        if (isModalOpen) {
            return;
        }

        lastFocused = document.activeElement;
        modal.hidden = false;
        document.body.classList.add('jplay-modal-open');
        isModalOpen = true;
        setFeedback('', '');
        setGlobalFeedback('', '');

        window.setTimeout(() => {
            if (codeInput) {
                codeInput.focus();
                codeInput.select();
            }
        }, 50);

        document.addEventListener('keydown', focusTrap);
    }

    function closeModal() {
        if (!isModalOpen) {
            return;
        }

        modal.hidden = true;
        document.body.classList.remove('jplay-modal-open');
        isModalOpen = false;
        document.removeEventListener('keydown', focusTrap);

        if (codeInput) {
            codeInput.value = '';
        }

        if (lastFocused && typeof lastFocused.focus === 'function') {
            lastFocused.focus();
        }
    }

    function sendCode({ openOnSuccess = true, sourceButton = trigger } = {}) {
        const postId = parseInt(trigger.getAttribute('data-jplay-post-id') || data.postId, 10);

        if (!postId) {
            return;
        }

        setLoading(sourceButton, true);
        setFeedback('', '');
        setGlobalFeedback('', '');
        setStatus(data.messages ? data.messages.sending : '');

        const payload = new URLSearchParams();
        payload.append('action', 'jplay_send_2fa');
        payload.append('nonce', data.nonce);
        payload.append('post_id', postId.toString());

        fetch(data.ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            },
            body: payload.toString(),
        })
            .then((response) => response.json())
            .then((json) => {
                if (!json || json.success !== true) {
                    throw new Error(json && json.data && json.data.message ? json.data.message : '');
                }

                setStatus(data.messages ? data.messages.sent : '');

                if (openOnSuccess) {
                    openModal();
                }
            })
            .catch((error) => {
                const message = error && error.message ? error.message : (data.messages ? data.messages.error : '');
                setGlobalFeedback(message, 'is-error');
            })
            .finally(() => {
                setLoading(sourceButton, false);
            });
    }

    function validateCode(event) {
        event.preventDefault();

        if (!form || !codeInput) {
            return;
        }

        const code = codeInput.value.trim();
        const postId = parseInt(trigger.getAttribute('data-jplay-post-id') || data.postId, 10);

        if (!code || code.length !== 6) {
            setFeedback(data.messages ? data.messages.error : '', 'is-error');
            codeInput.focus();
            return;
        }

        setLoading(submitButton, true);
        setFeedback('', '');

        const payload = new URLSearchParams();
        payload.append('action', 'jplay_validate_2fa');
        payload.append('nonce', data.nonce);
        payload.append('post_id', postId.toString());
        payload.append('code', code);

        fetch(data.ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            },
            body: payload.toString(),
        })
            .then((response) => response.json())
            .then((json) => {
                if (!json || json.success !== true) {
                    throw new Error(json && json.data && json.data.message ? json.data.message : '');
                }

                const successMessage = json && json.data && json.data.message ? json.data.message : (data.messages ? data.messages.success : '');
                setGlobalFeedback(successMessage, 'is-success');
                trigger.disabled = true;
                trigger.classList.add('is-disabled');
                closeModal();
            })
            .catch((error) => {
                const message = error && error.message ? error.message : (data.messages ? data.messages.error : '');
                setFeedback(message, 'is-error');
            })
            .finally(() => {
                setLoading(submitButton, false);
            });
    }

    function handleResend(event) {
        event.preventDefault();
        sendCode({ openOnSuccess: false, sourceButton: resendButton });
    }

    trigger.addEventListener('click', (event) => {
        event.preventDefault();
        sendCode({ sourceButton: trigger });
    });

    closeButtons.forEach((button) => {
        button.addEventListener('click', (event) => {
            event.preventDefault();
            closeModal();
        });
    });

    modal.addEventListener('click', (event) => {
        if (event.target === modal) {
            closeModal();
        }
    });

    if (form) {
        form.addEventListener('submit', validateCode);
    }

    if (resendButton) {
        resendButton.addEventListener('click', handleResend);
    }
})();
