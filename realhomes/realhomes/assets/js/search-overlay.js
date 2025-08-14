document.addEventListener('click', function (e) {
    if (e.target.closest('.rh-search-toggle')) {
        var overlay = document.getElementById('rh-search-overlay');
        if (overlay) {
            overlay.classList.add('is-active');
        }
    }

    if (e.target.closest('.rh-search-close')) {
        var overlayClose = document.getElementById('rh-search-overlay');
        if (overlayClose) {
            overlayClose.classList.remove('is-active');
        }
    }
});

document.addEventListener('keyup', function (e) {
    if (e.key === 'Escape') {
        var overlayEsc = document.getElementById('rh-search-overlay');
        if (overlayEsc) {
            overlayEsc.classList.remove('is-active');
        }
    }
});
