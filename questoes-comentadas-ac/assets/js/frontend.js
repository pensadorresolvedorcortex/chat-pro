(function () {
    'use strict';

    const initFilters = () => {
        const wrappers = document.querySelectorAll('.qc-shortcode-wrapper');

        wrappers.forEach((wrapper) => {
            const filters = wrapper.querySelector('.qc-filters');
            const cards = Array.from(wrapper.querySelectorAll('.qc-card'));
            const emptyMessage = wrapper.querySelector('.qc-empty-message');
            const resultsCount = wrapper.querySelector('.qc-results-count span');
            const resultsPercentage = wrapper.querySelector('[data-qc-results-percentage]');
            const summaryLists = Array.from(wrapper.querySelectorAll('[data-qc-summary-list]'));
            const taxonomyKeys = summaryLists
                .map((list) => list.getAttribute('data-taxonomy'))
                .filter((taxonomy) => typeof taxonomy === 'string' && taxonomy.length > 0);
            const selects = filters ? Array.from(filters.querySelectorAll('select')) : [];
            const clearButton = filters ? filters.querySelector('.qc-filters__clear') : null;
            const baseline = resultsPercentage
                ? parseInt(resultsPercentage.getAttribute('data-baseline') || String(cards.length), 10)
                : cards.length;
            const percentageTemplate = resultsPercentage
                ? resultsPercentage.getAttribute('data-template') || ''
                : '';

            const updateSummaries = (visibleCards) => {
                if (!summaryLists.length) {
                    return;
                }

                const totalsByTaxonomy = {};

                taxonomyKeys.forEach((taxonomy) => {
                    totalsByTaxonomy[taxonomy] = {};
                });

                visibleCards.forEach((card) => {
                    taxonomyKeys.forEach((taxonomy) => {
                        const datasetValue = card.getAttribute(`data-${taxonomy}`);
                        if (!datasetValue) {
                            return;
                        }

                        datasetValue.split(',').forEach((slug) => {
                            const trimmed = slug.trim();
                            if (!trimmed) {
                                return;
                            }

                            if (!totalsByTaxonomy[taxonomy][trimmed]) {
                                totalsByTaxonomy[taxonomy][trimmed] = 0;
                            }
                            totalsByTaxonomy[taxonomy][trimmed] += 1;
                        });
                    });
                });

                summaryLists.forEach((list) => {
                    const taxonomy = list.getAttribute('data-taxonomy');
                    if (!taxonomy) {
                        return;
                    }

                    const items = Array.from(list.querySelectorAll('[data-qc-summary-item]'));
                    const totalForTaxonomy = items.reduce((accumulator, item) => {
                        const slug = item.getAttribute('data-term');
                        if (!slug) {
                            return accumulator;
                        }

                        return accumulator + (totalsByTaxonomy[taxonomy]?.[slug] || 0);
                    }, 0);

                    items.forEach((item) => {
                        const slug = item.getAttribute('data-term');
                        if (!slug) {
                            return;
                        }

                        const count = totalsByTaxonomy[taxonomy]?.[slug] || 0;
                        const singularTemplate = item.getAttribute('data-count-singular') || '%d';
                        const pluralTemplate = item.getAttribute('data-count-plural') || '%d';
                        const template = count === 1 ? singularTemplate : pluralTemplate;
                        const countText = template.replace('%d', count.toString());
                        const countTarget = item.querySelector('[data-qc-summary-count-text]');
                        const percentageTarget = item.querySelector('[data-qc-summary-percentage]');
                        const progress = item.querySelector('[data-qc-progress]');
                        const labelElement = item.querySelector('.qc-progress-item__label');
                        const percentage = totalForTaxonomy ? Math.round((count / totalForTaxonomy) * 100) : 0;

                        if (countTarget) {
                            countTarget.textContent = countText;
                        }

                        if (percentageTarget) {
                            percentageTarget.textContent = percentage.toString();
                        }

                        if (progress) {
                            progress.setAttribute('aria-valuenow', percentage.toString());
                            const labelTemplate = progress.getAttribute('data-label-template');

                            if (labelTemplate && labelElement) {
                                const labelText = labelTemplate
                                    .replace('%label%', labelElement.textContent.trim())
                                    .replace('%percentage%', percentage.toString());
                                progress.setAttribute('aria-label', labelText);
                            }

                            const bar = progress.querySelector('.qc-progress__bar');
                            if (bar) {
                                bar.style.setProperty('--qc-progress-value', `${percentage}%`);
                            }
                        }
                    });
                });
            };

            const update = () => {
                let visible = 0;
                const visibleCards = [];

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

                        const taxonomy = select.name.replace('qca_filter_', '');
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
                        visibleCards.push(card);
                    }
                });

                if (resultsCount) {
                    resultsCount.textContent = visible.toString();
                }

                if (emptyMessage) {
                    emptyMessage.hidden = visible !== 0;
                }

                if (resultsPercentage && percentageTemplate) {
                    const percent = baseline ? Math.round((visible / baseline) * 100) : 0;
                    resultsPercentage.textContent = percentageTemplate.replace('%percentage%', percent.toString());
                }

                updateSummaries(visibleCards);
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
