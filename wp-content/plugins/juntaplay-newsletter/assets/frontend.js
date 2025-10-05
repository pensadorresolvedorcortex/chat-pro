(function () {
    var newsletterMessageCleared = false;

    function clearNewsletterQueryParam() {
        if (newsletterMessageCleared) {
            return;
        }

        try {
            var url = new URL(window.location.href);
            if (url.searchParams.has('juntaplay-newsletter')) {
                url.searchParams.delete('juntaplay-newsletter');
                var newUrl = url.pathname + (url.search ? url.search : '') + (url.hash ? url.hash : '');
                window.history.replaceState({}, document.title, newUrl || '/');
            }
        } catch (e) {
            var query = window.location.search;
            if (!query || query.indexOf('juntaplay-newsletter') === -1) {
                newsletterMessageCleared = true;
                return;
            }

            var params = query.slice(1).split('&').filter(function (param) {
                return param.split('=')[0] !== 'juntaplay-newsletter';
            });
            var base = window.location.origin ? window.location.origin : '';
            var path = window.location.pathname || '';
            var hash = window.location.hash || '';
            var next = path + (params.length ? '?' + params.join('&') : '') + hash;
            window.history.replaceState({}, document.title, base + next);
        }

        newsletterMessageCleared = true;
    }

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

        if (overlay.dataset && overlay.dataset.hasMessage === '1') {
            clearNewsletterQueryParam();
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
