(() => {
    const sliders = document.querySelectorAll('.jp-groups-slider');
    if (!sliders.length) {
        return;
    }

    const preventScroll = (event) => {
        event.preventDefault();
    };

    sliders.forEach((slider) => {
        const track = slider.querySelector('.jp-groups-slider__track');
        if (!track) {
            return;
        }

        const navButtons = slider.querySelectorAll('[data-action]');
        navButtons.forEach((button) => {
            button.addEventListener('click', (event) => {
                event.preventDefault();
            });
            button.setAttribute('aria-disabled', 'true');
        });

        track.style.overflow = 'hidden';
        track.style.scrollBehavior = 'auto';
        track.style.touchAction = 'none';
        track.addEventListener('wheel', preventScroll, { passive: false });
        track.addEventListener('touchmove', preventScroll, { passive: false });

        const items = Array.from(track.children);
        if (items.length < 2) {
            return;
        }

        const originalScrollWidth = track.scrollWidth;
        items.forEach((item) => {
            track.appendChild(item.cloneNode(true));
        });

        const itemWidth = items[0].getBoundingClientRect().width;
        const trackStyles = getComputedStyle(track);
        const gap = parseFloat(trackStyles.columnGap || trackStyles.gap || '0');
        const step = itemWidth + gap;
        const duration = 2000;
        const speed = step / duration;
        let lastTimestamp = null;

        const tick = (timestamp) => {
            if (lastTimestamp === null) {
                lastTimestamp = timestamp;
            }

            const delta = timestamp - lastTimestamp;
            lastTimestamp = timestamp;
            track.scrollLeft += speed * delta;

            if (track.scrollLeft >= originalScrollWidth) {
                track.scrollLeft -= originalScrollWidth;
            }

            requestAnimationFrame(tick);
        };

        requestAnimationFrame(tick);
    });
})();
