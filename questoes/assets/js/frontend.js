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

    function initAnswerToggles(container) {
        container.querySelectorAll('.questoes-question-card__toggle').forEach(function (button) {
            if (button.__questoesToggleInit) {
                return;
            }

            button.__questoesToggleInit = true;
            button.addEventListener('click', function () {
                var answers = button.nextElementSibling;
                if (!answers) {
                    return;
                }

                var expanded = button.getAttribute('aria-expanded') === 'true';
                expanded = !expanded;

                button.setAttribute('aria-expanded', expanded ? 'true' : 'false');

                var texts = (window.questoesFrontend && window.questoesFrontend.texts) || {};
                var showText = texts.show || 'Mostrar resposta';
                var hideText = texts.hide || 'Ocultar resposta';

                button.textContent = expanded ? hideText : showText;
                answers.hidden = !expanded;
            });
        });
    }

    function bootstrapComponents(scope) {
        var context = scope || document;
        context.querySelectorAll('.questoes-component').forEach(function (component) {
            initTabs(component);
            initControls(component);
            initAnswerToggles(component);
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

        var state = {
            page: 1,
            pages: 1,
            perPage: config.perPage || 10,
            category: config.category || '',
            banca: config.banca || '',
            difficulty: config.difficulty || ''
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
            initAnswerToggles(list);
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

                state.category = categoryField ? categoryField.value : '';
                state.banca = bancaField ? bancaField.value : '';
                state.difficulty = difficultyField ? difficultyField.value : '';
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

        if (initialData && initialData.items) {
            renderList(initialData);
        } else if (restUrl) {
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
                initAnswerToggles(element);
            });
        });

        window.elementorFrontend.hooks.addAction('frontend/element_ready/questoes_banco.default', function (scope) {
            scope.find('.questoes-question-bank').each(function (_, element) {
                initQuestionBank(element);
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
