(function (wp, document) {
    if (!wp || !wp.media) {
        return;
    }

    function setupPicker(picker) {
        const fieldKey = picker.dataset.mediaField;
        const selectBtn = picker.querySelector('[data-media-select]');
        const removeBtn = picker.querySelector('[data-media-remove]');
        const preview = picker.querySelector('[data-media-preview]');
        const hiddenInput = picker.querySelector(`#${fieldKey}`) || picker.querySelector(`[name="${fieldKey}"]`);
        const hint = picker.querySelector('[data-media-hint]');
        const desiredSize = picker.dataset.mediaSize || '';
        const defaultHint = picker.dataset.mediaDefaultHint || '';
        const placeholder = preview ? preview.innerHTML : '';
        const currentWidth = Number(picker.dataset.mediaCurrentWidth || 0);
        const currentHeight = Number(picker.dataset.mediaCurrentHeight || 0);
        const minWidth = Number(picker.dataset.mediaMinWidth || 0);
        const minHeight = Number(picker.dataset.mediaMinHeight || 0);

        if (!selectBtn || !removeBtn || !hiddenInput || !preview) {
            return;
        }

        function setHint(message, isError) {
            if (hint) {
                hint.textContent = message || defaultHint || '';
                hint.classList.toggle('is-error', !!isError);
            }

            picker.classList.toggle('is-invalid', !!isError);
        }

        function getDimensions(data) {
            if (!data) {
                return { width: 0, height: 0 };
            }

            const width = Number(data.width || (data.sizes && data.sizes.full && data.sizes.full.width) || 0);
            const height = Number(data.height || (data.sizes && data.sizes.full && data.sizes.full.height) || 0);

            return { width, height };
        }

        function validateDimensions(data) {
            if (!desiredSize) {
                setHint(defaultHint, false);
                return;
            }

            const { width, height } = getDimensions(data || {});
            if (!width || !height) {
                setHint(defaultHint, false);
                return;
            }

            if ((minWidth && width < minWidth) || (minHeight && height < minHeight)) {
                setHint(
                    `Use arquivos com ao menos ${minWidth || width}px de largura e ${minHeight || height}px de altura. Tamanho atual: ${width}×${height} px.`,
                    true
                );
                return;
            }

            if (desiredSize.includes(':')) {
                const [targetW, targetH] = desiredSize.split(':').map(Number);
                const expected = targetW && targetH ? targetW / targetH : 0;
                const current = width / height;

                if (expected > 0 && Math.abs(current - expected) > 0.02) {
                    setHint(
                        `O arquivo deve seguir a proporção ${desiredSize}. Tamanho atual: ${width}×${height} px.`,
                        true
                    );
                    return;
                }

                setHint(defaultHint, false);
                return;
            }

            const [targetWidth, targetHeight] = desiredSize.split('x').map(Number);
            if (targetWidth && targetHeight && (width !== targetWidth || height !== targetHeight)) {
                setHint(
                    `A arte deve ter ${targetWidth}×${targetHeight} px. Tamanho atual: ${width}×${height} px.`,
                    true
                );
                return;
            }

            setHint(defaultHint, false);
        }

        const frame = wp.media({
            title: selectBtn.textContent || 'Selecionar mídia',
            button: { text: selectBtn.textContent || 'Usar arquivo' },
            multiple: false,
        });

        selectBtn.addEventListener('click', function (event) {
            event.preventDefault();
            frame.open();
        });

        frame.on('select', function () {
            const attachment = frame.state().get('selection').first();
            if (!attachment) {
                return;
            }

            const data = attachment.toJSON();
            if (!data.id || !data.url) {
                return;
            }

            hiddenInput.value = data.id;
            preview.innerHTML = '<img src="' + data.url + '" alt="" />';

            validateDimensions(data);
        });

        removeBtn.addEventListener('click', function (event) {
            event.preventDefault();
            hiddenInput.value = '';
            preview.innerHTML = placeholder;
            setHint(defaultHint, false);
        });

        setHint(defaultHint, false);

        if (currentWidth && currentHeight) {
            validateDimensions({ width: currentWidth, height: currentHeight });
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        const pickers = document.querySelectorAll('[data-media-field]');
        pickers.forEach(setupPicker);
    });
})(window.wp, document);
