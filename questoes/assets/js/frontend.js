(function () {
    'use strict';

    function ready(fn) {
        if (document.readyState !== 'loading') {
            fn();
        } else {
            document.addEventListener('DOMContentLoaded', fn);
        }
    }

    function initTabs(container) {
        var tabs = container.querySelectorAll('[role="tab"]');
        var views = container.querySelectorAll('.questoes-view');

        tabs.forEach(function (tab) {
            tab.addEventListener('click', function () {
                var target = tab.getAttribute('data-target');
                tabs.forEach(function (item) {
                    item.setAttribute('aria-selected', item === tab ? 'true' : 'false');
                });
                views.forEach(function (view) {
                    view.classList.toggle('is-active', view.id === target);
                });
            });
        });
    }

    function initControls(container) {
        var zoom = 1;
        var content = container.querySelector('.questoes-views');
        if (!content) {
            return;
        }

        container.querySelectorAll('[data-action]').forEach(function (button) {
            button.addEventListener('click', function () {
                var action = button.getAttribute('data-action');
                if ('zoom-in' === action) {
                    zoom = Math.min(zoom + 0.1, 2);
                } else if ('zoom-out' === action) {
                    zoom = Math.max(zoom - 0.1, 0.6);
                } else if ('center' === action) {
                    zoom = 1;
                    content.scrollTo({ top: 0, left: 0, behavior: 'smooth' });
                } else if ('print' === action) {
                    window.print();
                    return;
                }
                content.style.transform = 'scale(' + zoom + ')';
                content.style.transformOrigin = '0 0';
            });
        });
    }

    var elementorHooked = false;

    function initQuestionCards(scope) {
        var context = scope || document;
        var texts = (window.questoesFrontend && window.questoesFrontend.texts) || {};

        context.querySelectorAll('.questoes-question-card').forEach(function (card) {
            if (card.__questoesCardInit) {
                return;
            }

            card.__questoesCardInit = true;

            var answersWrapper = card.querySelector('.questoes-question-card__answers');
            var result = card.querySelector('.questoes-question-card__result');
            var explanation = card.querySelector('.questoes-question-card__explanation');

            if (answersWrapper) {
                if (answersWrapper.hidden) {
                    answersWrapper.hidden = false;
                    answersWrapper.removeAttribute('hidden');
                }

                answersWrapper.addEventListener('click', function (event) {
                    var button = event.target.closest('.questoes-question-card__answer');
                    if (!button) {
                        return;
                    }

                    var buttons = answersWrapper.querySelectorAll('.questoes-question-card__answer');
                    buttons.forEach(function (item) {
                        item.classList.remove('is-selected', 'is-correct', 'is-incorrect', 'is-key');
                    });

                    var feedbackBlocks = answersWrapper.querySelectorAll('.questoes-question-card__feedback');
                    feedbackBlocks.forEach(function (feedback) {
                        feedback.hidden = true;
                    });

                    var isCorrect = button.getAttribute('data-correct') === '1';

                    button.classList.add('is-selected');
                    button.classList.add(isCorrect ? 'is-correct' : 'is-incorrect');

                    if (result) {
                        result.hidden = false;
                        result.setAttribute('data-state', isCorrect ? 'correct' : 'incorrect');
                        result.textContent = isCorrect ? (texts.correct || 'Resposta correta!') : (texts.incorrect || 'Resposta incorreta. Tente novamente.');
                    }

                    var feedback = button.nextElementSibling;
                    if (feedback && feedback.classList && feedback.classList.contains('questoes-question-card__feedback')) {
                        feedback.hidden = false;
                    }

                    buttons.forEach(function (item) {
                        if (item.getAttribute('data-correct') === '1') {
                            item.classList.add('is-key');
                        }
                    });

                    card.classList.add('is-revealed');

                    if (explanation) {
                        explanation.open = true;
                    }
                });
            }
        });
    }

    function initDisciplineBrowser(container) {
        if (!container || container.__questoesDisciplineInit) {
            return;
        }

        container.__questoesDisciplineInit = true;

        var texts = (window.questoesFrontend && window.questoesFrontend.texts) || {};
        var form = container.querySelector('.questoes-discipline-browser__filters');
        var list = container.querySelector('.questoes-discipline-browser__list');
        var rows = list
            ? Array.prototype.slice.call(
                  list.querySelectorAll('.questoes-discipline-browser__row:not(.questoes-discipline-browser__row--head)')
              )
            : [];
        var empty = container.querySelector('.questoes-discipline-browser__empty');
        var status = container.querySelector('.questoes-discipline-browser__status');
        var hasNavigator = typeof navigator !== 'undefined';
        var locale =
            document.documentElement.lang ||
            (hasNavigator && navigator.languages && navigator.languages.length ? navigator.languages[0] : null) ||
            (hasNavigator ? navigator.language : null) ||
            'pt-BR';
        var formatter;

        try {
            formatter = new Intl.NumberFormat(locale);
        } catch (error) {
            formatter = null;
        }

        function formatNumber(value) {
            if (!formatter) {
                return String(value);
            }

            return formatter.format(value);
        }

        function setVisibility(row, visible) {
            if (!row) {
                return;
            }

            if (visible) {
                row.classList.remove('is-hidden');
                row.removeAttribute('hidden');
            } else {
                row.classList.add('is-hidden');
                row.setAttribute('hidden', 'hidden');
            }
        }

        function applyFilters(focusFirst) {
            var searchField = form ? form.querySelector('input[name="busca"]') : null;
            var areaField = form ? form.querySelector('select[name="area"]') : null;
            var searchValue = searchField ? searchField.value.trim().toLowerCase() : '';
            var areaValue = areaField ? areaField.value : '';
            var visibleCount = 0;
            var questionTotal = 0;

            rows.forEach(function (row) {
                var keywords = (row.getAttribute('data-keywords') || '').toLowerCase();
                var area = row.getAttribute('data-area') || '';
                var matchesSearch = !searchValue || keywords.indexOf(searchValue) !== -1;
                var matchesArea = !areaValue || area === areaValue;
                var visible = matchesSearch && matchesArea;

                setVisibility(row, visible);

                if (visible) {
                    visibleCount += 1;
                    questionTotal += parseInt(row.getAttribute('data-count') || '0', 10) || 0;
                }
            });

            if (status) {
                if (visibleCount > 0) {
                    var label =
                        visibleCount === 1
                            ? texts.disciplineSingular || 'disciplina'
                            : texts.disciplinePlural || 'disciplinas';
                    var summaryTemplate = texts.disciplineSummary || '%1$s %2$s encontradas.';
                    var totalTemplate = texts.disciplineTotal || '%s questões ao todo.';
                    var summaryText = summaryTemplate
                        .replace('%1$s', formatNumber(visibleCount))
                        .replace('%2$s', label);
                    var totalText = totalTemplate.replace('%s', formatNumber(questionTotal));
                    status.textContent = summaryText + ' ' + totalText;
                    status.hidden = false;
                } else {
                    status.textContent = '';
                    status.hidden = true;
                }
            }

            if (empty) {
                empty.hidden = visibleCount > 0;
            }

            if (focusFirst && visibleCount > 0) {
                for (var index = 0; index < rows.length; index += 1) {
                    var candidate = rows[index];
                    if (!candidate.classList.contains('is-hidden')) {
                        if (typeof candidate.focus === 'function') {
                            candidate.focus({ preventScroll: true });
                        }
                        break;
                    }
                }
            }
        }

        if (form) {
            form.addEventListener('submit', function (event) {
                event.preventDefault();
                applyFilters(true);
            });

            form.addEventListener('change', function (event) {
                if (event.target && event.target.name === 'area') {
                    applyFilters(false);
                }
            });

            var searchField = form.querySelector('input[name="busca"]');
            if (searchField) {
                searchField.addEventListener('input', function () {
                    applyFilters(false);
                });
            }
        }

        applyFilters(false);
    }

    function bootstrapDisciplineBrowsers(scope) {
        var context = scope || document;
        context.querySelectorAll('.questoes-discipline-browser').forEach(function (container) {
            initDisciplineBrowser(container);
        });
    }

    function initCourseBrowser(container) {
        if (!container || container.__questoesCourseInit) {
            return;
        }

        container.__questoesCourseInit = true;

        var texts = (window.questoesFrontend && window.questoesFrontend.texts) || {};
        var filters = Array.prototype.slice.call(container.querySelectorAll('[data-region-filter]'));
        var cards = Array.prototype.slice.call(container.querySelectorAll('.questoes-course-card'));
        var empty = container.querySelector('.questoes-course-browser__empty');
        var status = container.querySelector('.questoes-course-browser__status');
        var total = status ? parseInt(status.getAttribute('data-total'), 10) || cards.length : cards.length;

        function getReferenceCount(region) {
            if (!region || region === 'all') {
                return total;
            }

            var match = null;

            filters.some(function (button) {
                if (button.getAttribute('data-region-filter') === region) {
                    match = button;
                    return true;
                }

                return false;
            });

            if (match) {
                return parseInt(match.getAttribute('data-count'), 10) || 0;
            }

            return total;
        }

        function applyFilter(region) {
            var visible = 0;

            cards.forEach(function (card) {
                var cardRegion = card.getAttribute('data-region') || '';
                var shouldShow = region === 'all' || region === cardRegion;

                if (shouldShow) {
                    card.classList.remove('is-hidden');
                    card.removeAttribute('hidden');
                    visible += 1;
                } else {
                    card.classList.add('is-hidden');
                    card.setAttribute('hidden', 'hidden');
                }
            });

            if (empty) {
                empty.hidden = visible > 0;

                if (visible === 0) {
                    empty.textContent = texts.courseEmpty || empty.textContent || 'Nenhum curso disponível para os filtros selecionados.';
                }
            }

            if (status) {
                if (visible > 0) {
                    var template = status.getAttribute('data-template') || 'Exibindo %1$s de %2$s cursos.';
                    var reference = getReferenceCount(region);
                    status.setAttribute('data-total', reference);
                    var formatted = template.replace('%1$s', visible).replace('%2$s', reference);
                    status.textContent = formatted;
                    status.hidden = false;
                } else {
                    status.hidden = true;
                }
            }
        }

        var current = 'all';
        applyFilter(current);

        filters.forEach(function (button) {
            button.addEventListener('click', function (event) {
                event.preventDefault();

                var target = button.getAttribute('data-region-filter') || 'all';

                if (target === current) {
                    return;
                }

                current = target;

                filters.forEach(function (item) {
                    var isActive = item === button;
                    item.classList.toggle('is-active', isActive);
                    item.setAttribute('aria-pressed', isActive ? 'true' : 'false');

                    if (isActive) {
                        item.setAttribute('aria-checked', 'true');
                    } else {
                        item.removeAttribute('aria-checked');
                    }
                });

                applyFilter(current);
            });
        });
    }

    function bootstrapCourseBrowsers(scope) {
        var context = scope || document;
        context.querySelectorAll('.questoes-course-browser').forEach(function (container) {
            initCourseBrowser(container);
        });
    }

    function bootstrapComponents(scope) {
        var context = scope || document;
        context.querySelectorAll('.questoes-component').forEach(function (component) {
            initTabs(component);
            initControls(component);
            initQuestionCards(component);
        });
    }

    function parseJSONScript(element) {
        if (!element) {
            return null;
        }

        try {
            return JSON.parse(element.textContent || '{}');
        } catch (error) {
            return null;
        }
    }

    function initQuestionBank(container) {
        if (!container || container.__questoesBankInit) {
            return;
        }

        container.__questoesBankInit = true;

        var config = {};

        try {
            config = JSON.parse(container.getAttribute('data-config') || '{}');
        } catch (error) {
            config = {};
        }

        var list = container.querySelector('.questoes-question-bank__list');
        var messages = container.querySelector('.questoes-question-bank__messages');
        var pagination = container.querySelector('.questoes-question-bank__pagination');
        var status = pagination ? pagination.querySelector('.questoes-question-bank__pagination-status') : null;
        var filtersForm = container.querySelector('.questoes-question-bank__filters');
        var initialData = parseJSONScript(container.querySelector('.questoes-question-bank__initial'));
        var texts = (window.questoesFrontend && window.questoesFrontend.texts) || {};
        var restUrl = (config && config.restUrl) || (window.questoesFrontend && window.questoesFrontend.restUrl) || '';
        var nonce = window.questoesFrontend && window.questoesFrontend.nonce;

        var delayInitialFetch = !!(config && config.delayInitialFetch);

        var state = {
            page: 1,
            pages: 1,
            perPage: config.perPage || 10,
            category: config.category || '',
            banca: config.banca || '',
            difficulty: config.difficulty || '',
            subject: config.subject || '',
            year: config.year || '',
            type: config.type || '',
            search: config.search || ''
        };

        function setMessage(message, type) {
            if (!messages) {
                return;
            }

            messages.textContent = message || '';
            if (type) {
                messages.setAttribute('data-type', type);
            } else {
                messages.removeAttribute('data-type');
            }
        }

        function setLoading(isLoading) {
            container.classList.toggle('is-loading', !!isLoading);
            if (isLoading) {
                setMessage(texts.loading || 'Carregando questões…', 'loading');
            }
        }

        function renderPagination() {
            if (!pagination) {
                return;
            }

            if (!state.pages || state.pages <= 1) {
                pagination.style.display = 'none';
            } else {
                pagination.style.display = '';
            }

            if (status) {
                status.textContent = 'Página ' + state.page + ' de ' + state.pages;
            }

            pagination.querySelectorAll('[data-page="prev"]').forEach(function (button) {
                button.disabled = state.page <= 1;
            });

            pagination.querySelectorAll('[data-page="next"]').forEach(function (button) {
                button.disabled = state.page >= state.pages;
            });
        }

        function renderList(data) {
            if (!list) {
                return;
            }

            var items = (data && data.items) || [];
            state.pages = data && data.pages ? data.pages : 1;

            if (state.page > state.pages) {
                state.page = state.pages || 1;
            }

            if (items.length) {
                list.innerHTML = items.map(function (item) {
                    return item && item.html ? item.html : '';
                }).join('');
            } else {
                list.innerHTML = '<p class="questoes-question-bank__empty">' + (texts.empty || 'Nenhuma questão encontrada.') + '</p>';
            }

            setMessage('', '');
            initQuestionCards(list);
            if (window.addComment && typeof window.addComment.init === 'function') {
                window.addComment.init();
            }
            renderPagination();
        }

        function buildURL() {
            if (!restUrl) {
                return '';
            }

            var params = new URLSearchParams();
            params.set('per_page', state.perPage);
            params.set('page', state.page);

            if (state.category) {
                params.set('category', state.category);
            }

            if (state.banca) {
                params.set('banca', state.banca);
            }

            if (state.difficulty) {
                params.set('difficulty', state.difficulty);
            }

            if (state.subject) {
                params.set('subject', state.subject);
            }

            if (state.type) {
                params.set('type', state.type);
            }

            if (state.year) {
                params.set('year', state.year);
            }

            if (state.search) {
                params.set('search', state.search);
            }

            return restUrl + '?' + params.toString();
        }

        var isFetching = false;

        function fetchData() {
            var url = buildURL();
            if (!url) {
                return;
            }

            if (isFetching) {
                return;
            }

            isFetching = true;
            setLoading(true);

            var options = {};
            if (nonce) {
                options.headers = { 'X-WP-Nonce': nonce };
            }

            fetch(url, options)
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error('Request failed');
                    }
                    return response.json();
                })
                .then(function (data) {
                    renderList(data);
                })
                .catch(function () {
                    setMessage(texts.error || 'Não foi possível carregar as questões. Tente novamente.', 'error');
                })
                .finally(function () {
                    isFetching = false;
                    setLoading(false);
                });
        }

        if (filtersForm) {
            filtersForm.addEventListener('submit', function (event) {
                event.preventDefault();

                var categoryField = filtersForm.querySelector('[name="categoria"]');
                var bancaField = filtersForm.querySelector('[name="banca"]');
                var difficultyField = filtersForm.querySelector('[name="dificuldade"]');
                var subjectField = filtersForm.querySelector('[name="assunto"]');
                var typeField = filtersForm.querySelector('[name="tipo"]');
                var yearField = filtersForm.querySelector('[name="ano"]');
                var searchField = filtersForm.querySelector('[name="busca"]');

                state.category = categoryField ? categoryField.value : '';
                state.banca = bancaField ? bancaField.value : '';
                state.difficulty = difficultyField ? difficultyField.value : '';
                state.subject = subjectField ? subjectField.value : '';
                state.type = typeField ? typeField.value : '';
                state.year = yearField ? yearField.value : '';
                state.search = searchField ? searchField.value.trim() : '';
                state.page = 1;

                fetchData();
            });
        }

        if (pagination) {
            pagination.addEventListener('click', function (event) {
                var button = event.target.closest('button[data-page]');
                if (!button) {
                    return;
                }

                event.preventDefault();

                var action = button.getAttribute('data-page');

                if ('prev' === action && state.page > 1) {
                    state.page -= 1;
                    fetchData();
                } else if ('next' === action && state.page < state.pages) {
                    state.page += 1;
                    fetchData();
                }
            });
        }

        if (config && config.initialNotice && delayInitialFetch) {
            setMessage(config.initialNotice, 'info');
        }

        if (initialData && initialData.items) {
            renderList(initialData);
        } else if (!delayInitialFetch && restUrl) {
            fetchData();
        }
    }

    function bootstrapQuestionBanks(scope) {
        var context = scope || document;
        context.querySelectorAll('.questoes-question-bank').forEach(function (container) {
            initQuestionBank(container);
        });
    }

    ready(function () {
        bootstrapComponents(document);
        bootstrapQuestionBanks(document);
        bootstrapDisciplineBrowsers(document);
        bootstrapCourseBrowsers(document);
        if (window.addComment && typeof window.addComment.init === 'function') {
            window.addComment.init();
        }
    });

    function initElementorBridge() {
        if (!window.elementorFrontend || !window.elementorFrontend.hooks || !window.jQuery) {
            return;
        }

        if (elementorHooked) {
            return;
        }

        elementorHooked = true;

        window.elementorFrontend.hooks.addAction('frontend/element_ready/questoes_visualizador.default', function (scope) {
            scope.find('.questoes-component').each(function (_, element) {
                initTabs(element);
                initControls(element);
                initQuestionCards(element);
            });
        });

        window.elementorFrontend.hooks.addAction('frontend/element_ready/questoes_banco.default', function (scope) {
            scope.find('.questoes-question-bank').each(function (_, element) {
                initQuestionBank(element);
            });
        });

        window.elementorFrontend.hooks.addAction('frontend/element_ready/shortcode.default', function (scope) {
            scope.find('.questoes-discipline-browser').each(function (_, element) {
                initDisciplineBrowser(element);
            });
            scope.find('.questoes-course-browser').each(function (_, element) {
                initCourseBrowser(element);
            });
        });

        window.elementorFrontend.hooks.addAction('frontend/element_ready/questoes_cursos.default', function (scope) {
            scope.find('.questoes-course-browser').each(function (_, element) {
                initCourseBrowser(element);
            });
        });
    }

    if (document.readyState !== 'loading') {
        initElementorBridge();
    } else {
        document.addEventListener('DOMContentLoaded', initElementorBridge);
    }

    if (window.jQuery) {
        window.jQuery(window).on('elementor/frontend/init', initElementorBridge);
    }
})();
