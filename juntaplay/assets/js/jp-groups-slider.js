(() => {
    const sliders = document.querySelectorAll('.jp-groups-slider');
    if (!sliders.length) {
        return;
    }

    sliders.forEach((slider) => {
        const track = slider.querySelector('.jp-groups-slider__track');
        if (!track) {
            return;
        }

        const navButtons = slider.querySelectorAll('[data-action]');
        navButtons.forEach((button) => {
            button.addEventListener('click', () => {
                const direction = button.getAttribute('data-action');
                const scrollAmount = track.clientWidth * 0.8;
                const offset = direction === 'prev' ? -scrollAmount : scrollAmount;
                track.scrollBy({ left: offset, behavior: 'smooth' });
            });
        });
    });
})();
