(function () {
    'use strict';

    var knowledgeStore = (function () {
        var storageKey = 'questoesKnowledge';
        var resumeKey = 'questoesKnowledgeResume';
        var data = { questions: {}, courses: {} };
        var listeners = [];
        var initialized = false;
        var fetchedRemote = false;
        var restUrl = '';
        var nonce = '';
        var canPersist = false;

        function loadLocal() {
            try {
                var raw = localStorage.getItem(storageKey);
                if (raw) {
                    var parsed = JSON.parse(raw);
                    if (parsed && typeof parsed === 'object') {
                        mergeData(parsed, true);
                    }
                }
            } catch (error) {
                /* noop */
            }
        }

        function persistLocal() {
            try {
                localStorage.setItem(storageKey, JSON.stringify(data));
            } catch (error) {
                /* noop */
            }
        }

        function mergeData(incoming, skipPersist) {
            if (!incoming || typeof incoming !== 'object') {
                return;
            }

            ['questions', 'courses'].forEach(function (type) {
                if (!incoming[type] || typeof incoming[type] !== 'object') {
                    return;
                }

                if (!data[type] || typeof data[type] !== 'object') {
                    data[type] = {};
                }

                Object.keys(incoming[type]).forEach(function (key) {
                    var incomingItem = incoming[type][key];
                    if (!incomingItem || typeof incomingItem !== 'object') {
                        return;
                    }

                    var current = data[type][key] || {};
                    var incomingTime = incomingItem.updated || 0;
                    var currentTime = current.updated || 0;

                    if (!currentTime || incomingTime >= currentTime) {
                        data[type][key] = Object.assign({}, current, incomingItem);
                    }
                });
            });

            if (!skipPersist) {
                persistLocal();
            }
        }

        function notify() {
            listeners.forEach(function (listener) {
                try {
                    listener(getSnapshot());
                } catch (error) {
                    /* noop */
                }
            });
        }

        function getSnapshot() {
            return {
                questions: JSON.parse(JSON.stringify(data.questions || {})),
                courses: JSON.parse(JSON.stringify(data.courses || {})),
            };
        }

        function syncItem(type, id, removed) {
            if (!canPersist || !restUrl || !window.fetch) {
                return;
            }

            var payload = {
                type: type,
                id: id,
            };

            if (removed) {
                payload.action = 'remove';
            } else {
                payload.data = data[type] && data[type][id] ? data[type][id] : {};
            }

            var options = {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(payload),
            };

            if (nonce) {
                options.headers['X-WP-Nonce'] = nonce;
            }

            fetch(restUrl, options).catch(function () {
                /* network errors are ignored */
            });
        }

        function update(type, id, payload) {
            if (!type || !id) {
                return;
            }

            if (!data[type] || typeof data[type] !== 'object') {
                data[type] = {};
            }

            var current = data[type][id] || {};
            var merged = Object.assign({}, current, payload || {});
            merged.updated = Date.now();
            merged.id = id;
            merged.type = type;

            data[type][id] = merged;

            persistLocal();
            notify();
            syncItem(type, id, false);
        }

        function remove(type, id) {
            if (!type || !id || !data[type]) {
                return;
            }

            delete data[type][id];
            persistLocal();
            notify();
            syncItem(type, id, true);
        }

        function subscribe(listener) {
            if (typeof listener === 'function') {
                listeners.push(listener);
                listener(getSnapshot());
            }
        }

        function markQuestionProgress(sessionKey, options) {
            if (!sessionKey) {
                return;
            }

            var record = data.questions && data.questions[sessionKey] ? data.questions[sessionKey] : {};
            var answeredIds = Array.isArray(record.answered_ids) ? record.answered_ids.slice() : [];
            var questionId = options && options.questionId ? String(options.questionId) : '';

            if (questionId && answeredIds.indexOf(questionId) === -1) {
                answeredIds.push(questionId);
            }

            var total = options && options.total ? parseInt(options.total, 10) : 0;
            if (!total && record.total) {
                total = parseInt(record.total, 10) || 0;
            }
            if (!total && options && options.expected) {
                total = parseInt(options.expected, 10) || 0;
            }
            if (!total && answeredIds.length) {
                total = answeredIds.length;
            }

            var answered = answeredIds.length;
            var status = total && answered >= total ? 'completed' : 'in-progress';

            var payload = Object.assign({}, record, {
                title: options && options.title ? options.title : record.title || 'Treino de questões',
                subtitle: options && options.questionTitle ? options.questionTitle : record.subtitle || '',
                answered_ids: answeredIds,
                answered: answered,
                total: total,
                status: status,
                filters: options && options.filters ? options.filters : record.filters || {},
                page: options && options.page ? options.page : record.page || 1,
                source: options && options.source ? options.source : record.source || '',
                context: options && options.context ? options.context : record.context || '',
            });

            if ('completed' === status) {
                remove('questions', sessionKey);
            } else {
                update('questions', sessionKey, payload);
            }
        }

        function markCourseProgress(course) {
            if (!course || !course.id) {
                return;
            }

            var payload = {
                title: course.title || '',
                link: course.link || '',
                cta: course.cta || '',
                status: 'in-progress',
                source: course.source || window.location.href,
                meta: course.meta || {},
            };

            update('courses', course.id, payload);
        }

        function prepareResume(payload) {
            if (!payload || !payload.id) {
                return;
            }

            try {
                sessionStorage.setItem(resumeKey, JSON.stringify(payload));
            } catch (error) {
                /* noop */
            }
        }

        function consumeResume(type) {
            try {
                var raw = sessionStorage.getItem(resumeKey);
                if (!raw) {
                    return null;
                }

                var parsed = JSON.parse(raw);
                sessionStorage.removeItem(resumeKey);

                if (!parsed || (type && parsed.type !== type)) {
                    return null;
                }

                return parsed;
            } catch (error) {
                return null;
            }
        }

        function getItem(type, id) {
            if (!type || !id || !data[type]) {
                return null;
            }

            return data[type][id] ? JSON.parse(JSON.stringify(data[type][id])) : null;
        }

        function init(initialData) {
            if (!initialized) {
                loadLocal();
                var globalConfig = window.questoesFrontend || {};
                restUrl = globalConfig.knowledgeRest || '';
                nonce = globalConfig.nonce || '';
                canPersist = globalConfig.knowledgeCanPersist === '1';
                initialized = true;
            }

            if (initialData) {
                mergeData(initialData);
                notify();
            }

            if (initialized && canPersist && restUrl && !fetchedRemote && window.fetch) {
                fetchedRemote = true;
                var options = {};
                if (nonce) {
                    options.headers = { 'X-WP-Nonce': nonce };
                }

                fetch(restUrl, options)
                    .then(function (response) {
                        if (!response.ok) {
                            throw new Error('Request failed');
                        }
                        return response.json();
                    })
                    .then(function (remote) {
                        if (remote && remote.data) {
                            mergeData(remote.data);
                            notify();
                        }
                    })
                    .catch(function () {
                        /* ignore */
                    });
            }
        }

        return {
            init: init,
            merge: mergeData,
            update: update,
            remove: remove,
            subscribe: subscribe,
            getSnapshot: getSnapshot,
            markQuestionProgress: markQuestionProgress,
            markCourseProgress: markCourseProgress,
            prepareResume: prepareResume,
            consumeResume: consumeResume,
            getItem: getItem,
        };
    })();

    knowledgeStore.init();

    function escapeHtml(value) {
        if (value === undefined || value === null) {
            return '';
        }

        return String(value).replace(/[&<>"']/g, function (char) {
            switch (char) {
                case '&':
                    return '&amp;';
                case '<':
                    return '&lt;';
                case '>':
                    return '&gt;';
                case '"':
                    return '&quot;';
                case "'":
                    return '&#039;';
                default:
                    return char;
            }
        });
    }

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

                    var bank = card.closest('.questoes-question-bank');
                    if (bank) {
                        var sessionKey = bank.getAttribute('data-session-key') || '';
                        if (sessionKey) {
                            var totalQuestions = parseInt(bank.getAttribute('data-total') || '0', 10) || 0;
                            var sessionStateRaw = bank.getAttribute('data-session-state');
                            var sessionState = {};

                            if (sessionStateRaw) {
                                try {
                                    sessionState = JSON.parse(sessionStateRaw);
                                } catch (error) {
                                    sessionState = {};
                                }
                            }

                            var titleElement = card.querySelector('.questoes-question-card__title');

                            knowledgeStore.markQuestionProgress(sessionKey, {
                                total: totalQuestions,
                                questionId: card.getAttribute('data-question-id') || '',
                                questionTitle: titleElement ? titleElement.textContent.trim() : '',
                                filters: sessionState.filters || {},
                                page: sessionState.page || 1,
                                source: sessionState.source || bank.getAttribute('data-source-path') || window.location.href,
                                title: sessionState.title || bank.getAttribute('data-title') || '',
                                context: sessionState.context || bank.getAttribute('data-knowledge-key') || '',
                            });
                        }
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

        var cards = Array.prototype.slice.call(container.querySelectorAll('.questoes-course-card'));
        var empty = container.querySelector('.questoes-course-browser__empty');
        var status = container.querySelector('.questoes-course-browser__status');
        var total = cards.length;

        cards.forEach(function (card) {
            card.classList.remove('is-hidden');
            card.removeAttribute('hidden');
        });

        var visible = cards.length;

        if (empty) {
            empty.hidden = visible > 0;
        }

        if (status) {
            var template = status.getAttribute('data-template') || 'Exibindo %1$s de %2$s cursos.';
            status.textContent = template.replace('%1$s', visible).replace('%2$s', total);
            status.hidden = false;
        }

        container.addEventListener('click', function (event) {
            var link = event.target.closest('.questoes-course-card__cta');
            if (!link) {
                return;
            }

            var card = link.closest('.questoes-course-card');
            if (!card) {
                return;
            }

            var courseId = card.getAttribute('data-course-id') || '';
            if (!courseId) {
                return;
            }

            var payload = {
                id: courseId,
                title: card.getAttribute('data-course-title') || '',
                link: link.getAttribute('href') || '',
                cta: link.textContent ? link.textContent.trim() : '',
                meta: {
                    salary: card.getAttribute('data-course-salary') || '',
                    opportunities: card.getAttribute('data-course-opps') || '',
                    badge: card.getAttribute('data-course-badge') || '',
                },
                source: window.location.href,
            };

            knowledgeStore.markCourseProgress(payload);
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

        state.contextKey = container.getAttribute('data-knowledge-key') || '';
        state.title = container.getAttribute('data-title') || '';
        state.sourcePath = window.location.href.split('#')[0];
        state.sessionKey = '';

        container.setAttribute('data-source-path', state.sourcePath);

        var resumeData = knowledgeStore.consumeResume('questions');
        var resumeApplied = false;

        function computeSessionKey() {
            return [
                state.contextKey || 'bank',
                state.category || '-',
                state.banca || '-',
                state.difficulty || '-',
                state.subject || '-',
                state.type || '-',
                state.year || '-',
                state.search || '-',
                state.perPage || '-'
            ].join('|');
        }

        function getSelectedLabel(select) {
            if (!select || typeof select.selectedIndex === 'undefined') {
                return '';
            }

            var option = select.options[select.selectedIndex];
            return option ? option.textContent.trim() : '';
        }

        function buildSessionTitle() {
            var base = state.title || container.getAttribute('data-title') || 'Questões';
            var details = [];

            if (filtersForm) {
                var categoryField = filtersForm.querySelector('[name="categoria"]');
                var bancaField = filtersForm.querySelector('[name="banca"]');
                var subjectField = filtersForm.querySelector('[name="assunto"]');
                var difficultyField = filtersForm.querySelector('[name="dificuldade"]');

                if (categoryField && categoryField.value) {
                    details.push(getSelectedLabel(categoryField));
                }
                if (bancaField && bancaField.value) {
                    details.push(getSelectedLabel(bancaField));
                }
                if (subjectField && subjectField.value) {
                    details.push(getSelectedLabel(subjectField));
                }
                if (difficultyField && difficultyField.value) {
                    details.push(getSelectedLabel(difficultyField));
                }
            }

            if (state.search) {
                details.unshift('"' + state.search + '"');
            }

            if (!details.length) {
                return base;
            }

            return base + ' — ' + details.join(' • ');
        }

        function storeSessionState(total) {
            var snapshot = {
                key: state.sessionKey,
                filters: {
                    category: state.category,
                    banca: state.banca,
                    difficulty: state.difficulty,
                    subject: state.subject,
                    year: state.year,
                    type: state.type,
                    search: state.search,
                },
                page: state.page,
                title: state.title,
                source: state.sourcePath,
                context: state.contextKey,
            };

            if (typeof total === 'number') {
                snapshot.total = total;
            }

            container.setAttribute('data-session-state', JSON.stringify(snapshot));
        }

        function registerSession(dataPayload) {
            if (!dataPayload) {
                return;
            }

            var items = Array.isArray(dataPayload.items) ? dataPayload.items : [];
            var total = 0;

            if (typeof dataPayload.total !== 'undefined') {
                total = parseInt(dataPayload.total, 10) || 0;
            }

            if (!total && items.length) {
                total = items.length;
            }

            state.sessionKey = computeSessionKey();

            if (!total) {
                container.removeAttribute('data-session-key');
                container.removeAttribute('data-total');
                container.removeAttribute('data-session-state');
                if (state.sessionKey) {
                    knowledgeStore.remove('questions', state.sessionKey);
                }
                return;
            }

            container.setAttribute('data-session-key', state.sessionKey);
            container.setAttribute('data-total', total);

            state.title = buildSessionTitle();
            storeSessionState(total);

            var existing = knowledgeStore.getItem('questions', state.sessionKey) || {};
            var payload = {
                title: state.title,
                total: total,
                answered: existing.answered || 0,
                answered_ids: existing.answered_ids || [],
                filters: {
                    category: state.category,
                    banca: state.banca,
                    difficulty: state.difficulty,
                    subject: state.subject,
                    year: state.year,
                    type: state.type,
                    search: state.search,
                },
                page: state.page,
                source: state.sourcePath,
                context: state.contextKey,
                status: existing.status || 'in-progress',
            };

            if (items.length && items[0] && items[0].title) {
                payload.subtitle = items[0].title;
            }

            if (payload.answered && payload.total && payload.answered >= payload.total) {
                knowledgeStore.remove('questions', state.sessionKey);
            } else {
                knowledgeStore.update('questions', state.sessionKey, payload);
            }
        }

        function applyFiltersToForm() {
            if (!filtersForm) {
                return;
            }

            var categoryField = filtersForm.querySelector('[name="categoria"]');
            var bancaField = filtersForm.querySelector('[name="banca"]');
            var difficultyField = filtersForm.querySelector('[name="dificuldade"]');
            var subjectField = filtersForm.querySelector('[name="assunto"]');
            var typeField = filtersForm.querySelector('[name="tipo"]');
            var yearField = filtersForm.querySelector('[name="ano"]');
            var searchField = filtersForm.querySelector('[name="busca"]');

            if (categoryField) {
                categoryField.value = state.category || '';
            }
            if (bancaField) {
                bancaField.value = state.banca || '';
            }
            if (difficultyField) {
                difficultyField.value = state.difficulty || '';
            }
            if (subjectField) {
                subjectField.value = state.subject || '';
            }
            if (typeField) {
                typeField.value = state.type || '';
            }
            if (yearField) {
                yearField.value = state.year || '';
            }
            if (searchField) {
                searchField.value = state.search || '';
            }
        }

        function applyResume(resume) {
            if (!resume) {
                return;
            }

            if (resume.source && state.sourcePath.indexOf(resume.source) === -1 && resume.source.indexOf(state.sourcePath) === -1) {
                return;
            }

            if (resume.context && resume.context !== state.contextKey) {
                return;
            }

            var filters = resume.filters || {};

            if (typeof filters.category !== 'undefined') {
                state.category = filters.category;
            }
            if (typeof filters.banca !== 'undefined') {
                state.banca = filters.banca;
            }
            if (typeof filters.difficulty !== 'undefined') {
                state.difficulty = filters.difficulty;
            }
            if (typeof filters.subject !== 'undefined') {
                state.subject = filters.subject;
            }
            if (typeof filters.year !== 'undefined') {
                state.year = filters.year;
            }
            if (typeof filters.type !== 'undefined') {
                state.type = filters.type;
            }
            if (typeof filters.search !== 'undefined') {
                state.search = filters.search;
            }

            if (typeof resume.page !== 'undefined') {
                state.page = parseInt(resume.page, 10) || 1;
            }

            resumeApplied = true;
            delayInitialFetch = false;
            applyFiltersToForm();
        }

        applyResume(resumeData);
        applyFiltersToForm();
        state.sessionKey = computeSessionKey();

        function setMessage(message, type) {
            if (!messages) {
                return;
            }

            messages.textContent = message || '';
            if (message) {
                messages.removeAttribute('hidden');
            } else {
                messages.setAttribute('hidden', 'hidden');
            }
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
            registerSession({ items: items, total: data && data.total ? data.total : items.length });
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
                state.sessionKey = computeSessionKey();

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
                    state.sessionKey = computeSessionKey();
                    fetchData();
                } else if ('next' === action && state.page < state.pages) {
                    state.page += 1;
                    state.sessionKey = computeSessionKey();
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

        if (resumeApplied && restUrl) {
            fetchData();
        }
    }

    function bootstrapQuestionBanks(scope) {
        var context = scope || document;
        context.querySelectorAll('.questoes-question-bank').forEach(function (container) {
            initQuestionBank(container);
        });
    }

    function initKnowledgeDashboard(container) {
        if (!container || container.__questoesKnowledgeInit) {
            return;
        }

        container.__questoesKnowledgeInit = true;

        var initialData = parseJSONScript(container.querySelector('.questoes-knowledge__initial'));
        if (initialData) {
            knowledgeStore.init(initialData);
        }

        var texts = (window.questoesFrontend && window.questoesFrontend.texts) || {};
        var lists = {};
        var counters = {};
        var empty = container.querySelector('[data-role="knowledge-empty"]');

        if (empty && texts.knowledgeEmpty) {
            empty.textContent = texts.knowledgeEmpty;
        }

        container.querySelectorAll('[data-role="knowledge-list"]').forEach(function (element) {
            var type = element.getAttribute('data-type');
            if (type) {
                lists[type] = element;
            }
        });

        container.querySelectorAll('[data-role="knowledge-count"]').forEach(function (element) {
            var type = element.getAttribute('data-type');
            if (type) {
                counters[type] = element;
            }
        });

        function formatRelative(timestamp) {
            if (!timestamp) {
                return '';
            }

            var diff = Date.now() - timestamp;
            if (diff < 0) {
                diff = 0;
            }

            var minutes = Math.round(diff / 60000);
            var label;

            if (minutes < 1) {
                label = 'menos de 1 minuto';
            } else if (minutes === 1) {
                label = '1 minuto';
            } else if (minutes < 60) {
                label = minutes + ' minutos';
            } else {
                var hours = Math.round(minutes / 60);
                if (hours === 1) {
                    label = '1 hora';
                } else if (hours < 24) {
                    label = hours + ' horas';
                } else {
                    var days = Math.round(hours / 24);
                    if (days === 1) {
                        label = '1 dia';
                    } else {
                        label = days + ' dias';
                    }
                }
            }

            var template = texts.knowledgeUpdated || 'Atualizado há %s';
            return template.replace('%s', label);
        }

        function createQuestionMarkup(item) {
            if (!item) {
                return '';
            }

            var answered = item.answered || 0;
            var total = item.total || 0;
            var percent = total > 0 ? Math.min(100, Math.round((answered / total) * 100)) : 0;
            var progressTemplate = texts.knowledgeProgressQuestions || '%1$s de %2$s questões concluídas';
            var progressText = progressTemplate.replace('%1$s', answered).replace('%2$s', total || '?');
            var updatedText = item.updated ? formatRelative(item.updated) : '';

            return (
                '<article class="questoes-knowledge-card" data-type="questions" data-id="' + escapeHtml(item.id || '') + '">' +
                '<div class="questoes-knowledge-card__inner">' +
                '<header class="questoes-knowledge-card__header">' +
                '<h4 class="questoes-knowledge-card__title">' + escapeHtml(item.title || '') + '</h4>' +
                (updatedText ? '<span class="questoes-knowledge-card__updated">' + escapeHtml(updatedText) + '</span>' : '') +
                '</header>' +
                (item.subtitle ? '<p class="questoes-knowledge-card__subtitle">' + escapeHtml(item.subtitle) + '</p>' : '') +
                '<div class="questoes-knowledge-card__progress">' +
                '<span class="questoes-knowledge-card__progress-text">' + escapeHtml(progressText) + '</span>' +
                '<div class="questoes-progress"><span style="width:' + percent + '%"></span></div>' +
                '</div>' +
                '<div class="questoes-knowledge-card__actions">' +
                '<button type="button" class="questoes-button questoes-button--secondary" data-action="resume">' + escapeHtml(texts.knowledgeResume || 'Retomar') + '</button>' +
                '<button type="button" class="questoes-button questoes-button--ghost" data-action="remove">' + escapeHtml(texts.knowledgeRemove || 'Remover') + '</button>' +
                '</div>' +
                '</div>' +
                '</article>'
            );
        }

        function createCourseMarkup(item) {
            if (!item) {
                return '';
            }

            var updatedText = item.updated ? formatRelative(item.updated) : '';
            var tags = [];

            if (item.meta) {
                if (item.meta.salary) {
                    tags.push('<span class="questoes-knowledge-card__tag">' + escapeHtml(item.meta.salary) + '</span>');
                }
                if (item.meta.opportunities) {
                    tags.push('<span class="questoes-knowledge-card__tag">' + escapeHtml(item.meta.opportunities) + '</span>');
                }
                if (item.meta.badge) {
                    tags.push('<span class="questoes-knowledge-card__tag questoes-knowledge-card__tag--badge">' + escapeHtml(item.meta.badge) + '</span>');
                }
            }

            return (
                '<article class="questoes-knowledge-card questoes-knowledge-card--course" data-type="courses" data-id="' + escapeHtml(item.id || '') + '">' +
                '<div class="questoes-knowledge-card__inner">' +
                '<header class="questoes-knowledge-card__header">' +
                '<h4 class="questoes-knowledge-card__title">' + escapeHtml(item.title || '') + '</h4>' +
                (updatedText ? '<span class="questoes-knowledge-card__updated">' + escapeHtml(updatedText) + '</span>' : '') +
                '</header>' +
                (tags.length ? '<div class="questoes-knowledge-card__tags">' + tags.join('') + '</div>' : '') +
                '<div class="questoes-knowledge-card__actions">' +
                '<button type="button" class="questoes-button questoes-button--secondary" data-action="resume">' + escapeHtml(texts.knowledgeResume || 'Retomar') + '</button>' +
                '<button type="button" class="questoes-button questoes-button--ghost" data-action="remove">' + escapeHtml(texts.knowledgeRemove || 'Remover') + '</button>' +
                '</div>' +
                '</div>' +
                '</article>'
            );
        }

        function render() {
            var snapshot = knowledgeStore.getSnapshot();
            var questionItems = Object.keys(snapshot.questions || {})
                .map(function (key) {
                    return snapshot.questions[key];
                })
                .filter(function (item) {
                    if (!item) {
                        return false;
                    }
                    if (item.total && item.answered && item.answered >= item.total) {
                        return false;
                    }
                    return true;
                })
                .sort(function (a, b) {
                    return (b.updated || 0) - (a.updated || 0);
                });

            var courseItems = Object.keys(snapshot.courses || {})
                .map(function (key) {
                    return snapshot.courses[key];
                })
                .filter(function (item) {
                    return !!item;
                })
                .sort(function (a, b) {
                    return (b.updated || 0) - (a.updated || 0);
                });

            if (lists.questions) {
                lists.questions.innerHTML = questionItems.map(createQuestionMarkup).join('');
            }

            if (lists.courses) {
                lists.courses.innerHTML = courseItems.map(createCourseMarkup).join('');
            }

            if (counters.questions) {
                counters.questions.textContent = questionItems.length;
            }

            if (counters.courses) {
                counters.courses.textContent = courseItems.length;
            }

            if (empty) {
                empty.hidden = questionItems.length + courseItems.length > 0;
            }
        }

        knowledgeStore.subscribe(render);

        container.addEventListener('click', function (event) {
            var button = event.target.closest('[data-action]');
            if (!button) {
                return;
            }

            event.preventDefault();

            var card = button.closest('.questoes-knowledge-card');
            if (!card) {
                return;
            }

            var type = card.getAttribute('data-type');
            var id = card.getAttribute('data-id');

            if (!type || !id) {
                return;
            }

            var action = button.getAttribute('data-action');

            if ('remove' === action) {
                knowledgeStore.remove(type, id);
                return;
            }

            if ('resume' === action) {
                if ('questions' === type) {
                    var session = knowledgeStore.getItem('questions', id);
                    if (session) {
                        knowledgeStore.prepareResume({
                            type: 'questions',
                            id: id,
                            filters: session.filters || {},
                            page: session.page || 1,
                            source: session.source || window.location.href,
                            context: session.context || '',
                        });

                        var target = session.source || window.location.href;
                        window.location.href = target;
                    }
                } else if ('courses' === type) {
                    var course = knowledgeStore.getItem('courses', id);
                    if (course && course.link) {
                        window.location.href = course.link;
                    }
                }
            }
        });
    }

    function bootstrapKnowledgeDashboards(scope) {
        var context = scope || document;
        context.querySelectorAll('.questoes-knowledge').forEach(function (container) {
            initKnowledgeDashboard(container);
        });
    }

    ready(function () {
        bootstrapComponents(document);
        bootstrapQuestionBanks(document);
        bootstrapDisciplineBrowsers(document);
        bootstrapCourseBrowsers(document);
        bootstrapKnowledgeDashboards(document);
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
            scope.find('.questoes-knowledge').each(function (_, element) {
                initKnowledgeDashboard(element);
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
