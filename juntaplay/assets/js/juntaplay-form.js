(function () {
    const form = document.querySelector('.jplay-form--create-group');

    if (!form) {
        return;
    }

    const channelSelect = form.querySelector('#jplay_support_channel');
    const detailBlocks = form.querySelectorAll('.jplay-support-details');
    const whatsappInput = form.querySelector('#jplay_support_whatsapp');
    const countrySelect = form.querySelector('#jplay_support_country');
    const emailInput = form.querySelector('#jplay_support_email');

    function toggleDetails() {
        const selected = channelSelect ? channelSelect.value : '';

        detailBlocks.forEach((block) => {
            const target = block.getAttribute('data-support-target');
            const isActive = target === selected;

            block.hidden = !isActive;

            const inputs = block.querySelectorAll('input, select');
            inputs.forEach((input) => {
                input.disabled = !isActive;
            });
        });

        if (whatsappInput) {
            whatsappInput.required = selected === 'whatsapp';
        }

        if (countrySelect) {
            countrySelect.disabled = selected !== 'whatsapp';
        }

        if (emailInput) {
            emailInput.required = selected === 'email';
            emailInput.disabled = selected !== 'email';
        }
    }

    function formatWhatsApp(value) {
        const digits = value.replace(/\D+/g, '').slice(0, 15);

        if (!digits) {
            return '';
        }

        let formatted = '';

        if (digits.length <= 2) {
            formatted = digits;
        } else if (digits.length <= 6) {
            formatted = `(${digits.slice(0, 2)}) ${digits.slice(2)}`;
        } else if (digits.length <= 10) {
            formatted = `(${digits.slice(0, 2)}) ${digits.slice(2, 6)}-${digits.slice(6)}`;
        } else {
            formatted = `(${digits.slice(0, 2)}) ${digits.slice(2, 7)}-${digits.slice(7)}`;
        }

        return formatted;
    }

    function handleWhatsAppInput(event) {
        const input = event.target;
        const caretPosition = input.selectionStart;
        const beforeLength = input.value.length;

        input.value = formatWhatsApp(input.value);

        const afterLength = input.value.length;
        const diff = afterLength - beforeLength;
        const newCaret = typeof caretPosition === 'number' ? Math.max(caretPosition + diff, 0) : afterLength;
        input.setSelectionRange(newCaret, newCaret);
    }

    function validateSupport(event) {
        if (!channelSelect) {
            return;
        }

        const channel = channelSelect.value;

        if (channel === 'whatsapp') {
            const digits = whatsappInput ? whatsappInput.value.replace(/\D+/g, '') : '';

            if (!digits || digits.length < 10) {
                if (whatsappInput) {
                    whatsappInput.setCustomValidity('Informe um número válido com DDI e DDD.');
                    whatsappInput.reportValidity();
                }
                event.preventDefault();
            } else if (whatsappInput) {
                whatsappInput.setCustomValidity('');
            }
        } else if (channel === 'email') {
            if (emailInput && !emailInput.value) {
                emailInput.setCustomValidity('Informe um e-mail de suporte válido.');
                emailInput.reportValidity();
                event.preventDefault();
            } else if (emailInput) {
                emailInput.setCustomValidity('');
            }
        }
    }

    if (channelSelect) {
        channelSelect.addEventListener('change', toggleDetails);
        toggleDetails();
    }

    if (whatsappInput) {
        whatsappInput.addEventListener('input', handleWhatsAppInput);
    }

    form.addEventListener('submit', validateSupport);
})();
