(function () {
    'use strict';

    const initFilters = () => {
        const wrappers = document.querySelectorAll('.qc-shortcode-wrapper');

        wrappers.forEach((wrapper) => {
            const filters = wrapper.querySelector('.qc-filters');
            const cards = wrapper.querySelectorAll('.qc-card');
            const emptyMessage = wrapper.querySelector('.qc-empty-message');
            const resultsCount = wrapper.querySelector('.qc-results-count span');

            if (!filters) {
                return;
            }

            const selects = Array.from(filters.querySelectorAll('select'));
            const clearButton = filters.querySelector('.qc-filters__clear');

            const update = () => {
                let visible = 0;

                cards.forEach((card) => {
                    let matches = true;

                    selects.forEach((select) => {
                        if (!matches) {
                            return;
                        }

                        const value = select.value;
                        if (!value) {
                            return;
                        }

                        const taxonomy = select.name.replace('qc_filter_', '');
                        const datasetValue = card.getAttribute(`data-${taxonomy}`);

                        if (!datasetValue) {
                            matches = false;
                            return;
                        }

                        const termValues = datasetValue.split(',');
                        if (!termValues.includes(value)) {
                            matches = false;
                        }
                    });

                    card.classList.toggle('is-hidden', !matches);

                    if (matches) {
                        visible += 1;
                    }
                });

                if (resultsCount) {
                    resultsCount.textContent = visible.toString();
                }

                if (emptyMessage) {
                    emptyMessage.hidden = visible !== 0;
                }
            };

            selects.forEach((select) => {
                select.addEventListener('change', update);
            });

            if (clearButton) {
                clearButton.addEventListener('click', () => {
                    selects.forEach((select) => {
                        select.selectedIndex = 0;
                    });
                    update();
                    if (selects[0]) {
                        selects[0].focus();
                    }
                });
            }

            update();
        });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initFilters);
    } else {
        initFilters();
    }
})();
