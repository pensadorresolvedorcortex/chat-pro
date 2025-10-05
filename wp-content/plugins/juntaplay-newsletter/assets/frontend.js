(function () {
    function initNewsletterModal(overlay) {
        if (!overlay) {
            return;
        }

        if (overlay.dataset.juntaplayNewsletterReady === '1') {
            return;
        }
        overlay.dataset.juntaplayNewsletterReady = '1';

        var closeButton = overlay.querySelector('.juntaplay-newsletter-close');
        var form = overlay.querySelector('form');

        function openModal() {
            overlay.classList.add('is-visible');
            var focusTarget = overlay.querySelector('input[name="name"], input, button, select, textarea');
            if (focusTarget && typeof focusTarget.focus === 'function') {
                focusTarget.focus();
            }
        }

        function closeModal() {
            overlay.classList.remove('is-visible');
        }

        if (closeButton) {
            closeButton.addEventListener('click', function (event) {
                event.preventDefault();
                closeModal();
            });
        }

        overlay.addEventListener('click', function (event) {
            if (event.target === overlay) {
                closeModal();
            }
        });

        overlay.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' || event.key === 'Esc') {
                closeModal();
            }
        });

        if (form) {
            form.addEventListener('submit', function () {
                overlay.classList.add('is-submitting');
            });
        }

        openModal();
    }

    function bootstrapModals() {
        var overlays = document.querySelectorAll('.juntaplay-newsletter-overlay');
        overlays.forEach(function (overlay) {
            initNewsletterModal(overlay);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bootstrapModals);
    } else {
        bootstrapModals();
    }
})();
