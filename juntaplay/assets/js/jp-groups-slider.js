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

        track.style.overflow = 'visible';
        track.style.scrollBehavior = 'auto';
        track.style.touchAction = 'none';
        track.addEventListener('wheel', preventScroll, { passive: false });
        track.addEventListener('touchmove', preventScroll, { passive: false });
        slider.addEventListener('wheel', preventScroll, { passive: false });
        slider.addEventListener('touchmove', preventScroll, { passive: false });

        const items = Array.from(track.children);
        if (items.length < 2) {
            return;
        }

        items.forEach((item) => {
            track.appendChild(item.cloneNode(true));
        });

        const duration = 2000;
        let lastTimestamp = null;
        let offset = 0;
        let gap = 0;
        let step = 0;
        let originalWidth = 0;
        let isPaused = false;

        const measure = () => {
            const trackStyles = getComputedStyle(track);
            gap = parseFloat(trackStyles.columnGap || trackStyles.gap || '0');
            originalWidth = items.reduce((total, item, index) => {
                const width = item.getBoundingClientRect().width;
                const spacing = index > 0 ? gap : 0;
                return total + width + spacing;
            }, 0);
            step = items[0].getBoundingClientRect().width + gap;
        };

        const start = () => {
            measure();
            if (!originalWidth || !step) {
                requestAnimationFrame(start);
                return;
            }

            const speed = step / duration;
            track.style.willChange = 'transform';

            const tick = (timestamp) => {
                if (lastTimestamp === null) {
                    lastTimestamp = timestamp;
                }

                if (isPaused) {
                    lastTimestamp = timestamp;
                    requestAnimationFrame(tick);
                    return;
                }

                const delta = timestamp - lastTimestamp;
                lastTimestamp = timestamp;
                offset += speed * delta;

                if (offset >= originalWidth) {
                    offset -= originalWidth;
                }

                track.style.transform = `translateX(${-offset}px)`;
                requestAnimationFrame(tick);
            };

            requestAnimationFrame(tick);
        };

        slider.addEventListener('mouseenter', () => {
            isPaused = true;
        });
        slider.addEventListener('mouseleave', () => {
            isPaused = false;
            lastTimestamp = null;
        });

        requestAnimationFrame(start);
    });
})();
