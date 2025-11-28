(function ($) {
    'use strict';

    window.JP = window.JP || {};

    var i18n = (window.wp && window.wp.i18n) ? window.wp.i18n : null;

    function __(text, domain) {
        if (i18n && typeof i18n.__ === 'function') {
            return i18n.__.apply(i18n, arguments);
        }
        return text;
    }

    if (typeof window !== 'undefined' && window.jQuery && typeof window.jQuery.fn.tipTip !== 'function') {
        window.jQuery.fn.tipTip = function () {
            return this;
        };
    }

    function _n(single, plural, number, domain) {
        if (i18n && typeof i18n._n === 'function') {
            return i18n._n.apply(i18n, arguments);
        }
        return number === 1 ? single : plural;
    }

    function collectNumbers($scope) {
        var numbers = [];
        $scope.find('.juntaplay-grid__item.is-selected').each(function () {
            numbers.push($(this).data('number'));
        });
        return numbers;
    }

    function formatCurrency(amount, currency, locale) {
        var value = isFinite(amount) ? amount : 0;
        try {
            return new Intl.NumberFormat(locale || 'pt-BR', {
                style: 'currency',
                currency: currency || 'BRL',
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(value);
        } catch (e) {
            return (currency || 'R$') + ' ' + value.toFixed(2);
        }
    }

    function escapeHtml(value) {
        return String(value === null || typeof value === 'undefined' ? '' : value).replace(/[&<>"']/g, function (char) {
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

    function truncate(text, length) {
        var value = String(text || '');
        if (value.length <= length) {
            return value;
        }

        return value.slice(0, Math.max(0, length - 1)) + '…';
    }

    var checkoutUpdateTimer = null;

    function scheduleCheckoutUpdate() {
        if (!$('body').hasClass('woocommerce-checkout')) {
            return;
        }

        if (checkoutUpdateTimer) {
            clearTimeout(checkoutUpdateTimer);
        }

        checkoutUpdateTimer = window.setTimeout(function () {
            checkoutUpdateTimer = null;
            $(document.body).trigger('update_checkout');
        }, 180);
    }

    var coverPlaceholder = '';

    function refreshSelected($container) {
        var numbers = collectNumbers($container);
        var $numbersWrapper = $container.find('.juntaplay-selected__numbers');
        var $count = $container.find('.juntaplay-selected__count');
        var $total = $container.find('.juntaplay-selected__total-value');
        var $form = $container.find('.juntaplay-quota-form');
        var emptyLabel = $numbersWrapper.data('empty') || '';
        var totalEmpty = $total.data('empty') || '';
        var currency = $form.data('currency');
        var locale = $form.data('locale');
        var price = parseFloat($form.data('price')) || 0;

        if (numbers.length) {
            var chips = numbers.map(function (number) {
                return '<span class="juntaplay-chip">' + number + '</span>';
            });
            $numbersWrapper.html(chips.join(''));
            $count.text(numbers.length);
            $total.text(formatCurrency(numbers.length * price, currency, locale));
        } else {
            $numbersWrapper.html('<span class="juntaplay-selected__empty">' + emptyLabel + '</span>');
            $count.text('0');
            $total.text(totalEmpty);
        }
    }

    function toggleQuota($item) {
        if ($item.hasClass('is-disabled')) {
            return;
        }

        $item.toggleClass('is-selected');
    }

    $(document).on('click', '.juntaplay-grid__item', function () {
        var $item = $(this);
        var $container = $item.closest('.juntaplay-quota-selector');
        toggleQuota($item);
        $container.data('selectedNumbers', collectNumbers($container));
        refreshSelected($container);
    });

    $(document).on('submit', '.juntaplay-quota-form', function (event) {
        var $form = $(this);
        var $container = $form.closest('.juntaplay-quota-selector');
        var numbers = collectNumbers($container);

        if (!numbers.length) {
            event.preventDefault();
            alert($form.data('message-empty'));
            return false;
        }

        $form.find('input[name="jp_numbers[]"]').remove();

        numbers.forEach(function (number) {
            $('<input />', {
                type: 'hidden',
                name: 'jp_numbers[]',
                value: number
            }).appendTo($form);
        });

        return true;
    });

    function quotaStatusLabel(status) {
        switch (status) {
            case 'reserved':
                return __('Reservada', 'juntaplay');
            case 'paid':
                return __('Paga', 'juntaplay');
            case 'canceled':
                return __('Cancelada', 'juntaplay');
            case 'expired':
                return __('Expirada', 'juntaplay');
            default:
                return __('Disponível', 'juntaplay');
        }
    }

    function renderQuotaItem(item, selected) {
        var status = item.status || 'available';
        var classes = ['juntaplay-grid__item'];

        if (status !== 'available') {
            classes.push('is-disabled');
            classes.push('status-' + status);
        }

        if (selected) {
            classes.push('is-selected');
        }

        var badge = '';
        if (status !== 'available') {
            badge = '<span class="juntaplay-grid__badge">' + quotaStatusLabel(status) + '</span>';
        }

        return '<button type="button" class="' + classes.join(' ') + '" data-number="' + item.number + '" data-status="' + status + '">' +
            '<span class="juntaplay-grid__number">' + item.number + '</span>' + badge +
            '</button>';
    }

    function initQuotaSelector($container) {
        var state = {
            poolId: parseInt($container.data('pool'), 10) || 0,
            perPage: parseInt($container.data('perPage'), 10) || 120,
            page: 1,
            status: ($container.data('status') || 'available').toString(),
            search: '',
            sort: ($container.data('sort') || 'ASC').toString(),
            loading: false,
            hasMore: true
        };

        var $grid = $container.find('.juntaplay-grid');
        var $load = $container.find('[data-quota-load]');
        var $feedback = $container.find('[data-quota-feedback]');
        var $filters = $container.find('.juntaplay-quota-filter');

        $container.data('selectedNumbers', collectNumbers($container));

        function toggleLoading(isLoading) {
            state.loading = isLoading;
            $container.toggleClass('is-loading', isLoading);
            if (isLoading) {
                $load.prop('disabled', true).text(__('Carregando...', 'juntaplay'));
            } else {
                $load.prop('disabled', false).text(state.hasMore ? __('Ver mais números', 'juntaplay') : __('Todos os números carregados', 'juntaplay'));
            }
        }

        function fetchQuotas(reset) {
            if (state.loading || !state.poolId) {
                return;
            }

            toggleLoading(true);

            if (reset) {
                state.page = 1;
                state.hasMore = true;
            }

            $.ajax({
                url: JuntaPlay.ajax,
                dataType: 'json',
                method: 'GET',
                data: {
                    action: 'juntaplay_pool_numbers',
                    nonce: JuntaPlay.nonce,
                    pool_id: state.poolId,
                    page: state.page,
                    per_page: state.perPage,
                    status: state.status,
                    search: state.search,
                    sort: state.sort
                }
            }).done(function (response) {
                if (!response || !response.success) {
                    $feedback.text(response && response.data && response.data.message ? response.data.message : __('Não foi possível carregar as cotas agora.', 'juntaplay')).addClass('is-visible');
                    return;
                }

                var data = response.data || {};
                var items = data.items || [];

                if (reset) {
                    $grid.empty();
                }

                if (!items.length && reset) {
                    $feedback.text(__('Nenhum número encontrado para os filtros selecionados.', 'juntaplay')).addClass('is-visible');
                } else {
                    $feedback.removeClass('is-visible').text('');
                }

                var selected = $container.data('selectedNumbers') || [];

                if (items.length) {
                    var markup = items.map(function (item) {
                        return renderQuotaItem(item, selected.indexOf(item.number) !== -1);
                    });
                    $grid.append(markup.join(''));
                }

                state.page = (data.page || 1) + 1;
                state.hasMore = (data.page || 1) < (data.pages || 1);

                if (!state.hasMore) {
                    $load.attr('disabled', true).text(__('Todos os números carregados', 'juntaplay'));
                } else {
                    $load.attr('disabled', false).text(__('Ver mais números', 'juntaplay'));
                }

                refreshSelected($container);
            }).fail(function () {
                $feedback.text(__('Não foi possível carregar as cotas agora.', 'juntaplay')).addClass('is-visible');
            }).always(function () {
                toggleLoading(false);
            });
        }

        $filters.on('submit', function (event) {
            event.preventDefault();
            state.status = ($filters.find('[name="status"]').val() || 'available').toString();
            state.search = ($filters.find('[name="search"]').val() || '').toString();
            state.sort = ($filters.find('[name="sort"]').val() || 'ASC').toString();
            fetchQuotas(true);
        });

        $load.on('click', function (event) {
            event.preventDefault();
            if (!state.hasMore || state.loading) {
                return;
            }
            fetchQuotas(false);
        });

        fetchQuotas(true);
    }

    function renderPoolCard(pool) {
        var badge = pool.is_featured ? '<span class="juntaplay-pool-card__badge">' + __('Destaque', 'juntaplay') + '</span>' : '';
        var category = pool.categoryLabel ? '<span class="juntaplay-badge">' + pool.categoryLabel + '</span>' : '';
        var progress = '<div class="juntaplay-progress"><span class="juntaplay-progress__bar" style="width:' + (pool.progress || 0) + '%"></span></div>';
        var quotaMeta = '<div class="juntaplay-pool-card__meta">' +
            '<span>' + __('Disponíveis', 'juntaplay') + ': ' + pool.quotasFree + '</span>' +
            '<span>' + __('Vendidas', 'juntaplay') + ': ' + pool.quotasPaid + '</span>' +
            '</div>';
        var cover = pool.thumbnail ? '<div class="juntaplay-pool-card__cover"><img src="' + pool.thumbnail + '" alt="' + pool.title + '" /></div>' : '<div class="juntaplay-pool-card__cover is-placeholder"><span>' + __('Grupo', 'juntaplay') + '</span></div>';

        return '<article class="juntaplay-pool-card">' +
            cover +
            '<div class="juntaplay-pool-card__body">' + badge +
            '<h3 class="juntaplay-pool-card__title"><a href="' + pool.permalink + '">' + pool.title + '</a></h3>' +
            category +
            '<p class="juntaplay-pool-card__excerpt">' + pool.excerpt + '</p>' +
            '<div class="juntaplay-pool-card__price">' + __('Rifa a partir de', 'juntaplay') + ' <strong>' + pool.priceLabel + '</strong></div>' +
            progress +
            quotaMeta +
            '<a class="juntaplay-button juntaplay-button--primary" href="' + pool.permalink + '">' + __('Assinar agora', 'juntaplay') + '</a>' +
            '</div>' +
            '</article>';
    }

    function initPoolCatalog($catalog) {
        var state = {
            page: 1,
            perPage: parseInt($catalog.data('perPage'), 10) || 12,
            category: ($catalog.data('category') || '').toString(),
            orderby: ($catalog.data('orderby') || 'created_at').toString(),
            order: ($catalog.data('order') || 'desc').toString(),
            featured: ($catalog.data('featured') || '').toString(),
            minPrice: '',
            maxPrice: '',
            search: '',
            loading: false,
            hasMore: true
        };

        var $results = $catalog.find('[data-pool-results]');
        var $meta = $catalog.find('[data-pool-meta]');
        var $empty = $catalog.find('[data-pool-empty]');
        var $load = $catalog.find('[data-pools-load]');
        var $form = $catalog.find('form.juntaplay-pool-filters');

        function togglePoolsLoading(isLoading) {
            state.loading = isLoading;
            $catalog.toggleClass('is-loading', isLoading);
            if (isLoading) {
                $load.prop('disabled', true).text(__('Carregando...', 'juntaplay'));
            } else if (state.hasMore) {
                $load.prop('disabled', false).removeAttr('hidden').text(__('Carregar mais campanhas', 'juntaplay'));
            } else {
                $load.prop('disabled', true).attr('hidden', 'hidden');
            }
        }

        function updateMeta(total) {
            if (!$meta.length) {
                return;
            }
            $meta.text(total ? __('Exibindo', 'juntaplay') + ' ' + total : '');
        }

        function fetchPools(reset) {
            if (state.loading) {
                return;
            }

            togglePoolsLoading(true);

            if (reset) {
                state.page = 1;
                state.hasMore = true;
                if ($load.length) {
                    $load.removeAttr('hidden');
                }
            }

            $.ajax({
                url: JuntaPlay.ajax,
                dataType: 'json',
                method: 'GET',
                data: {
                    action: 'juntaplay_pools',
                    nonce: JuntaPlay.nonce,
                    page: state.page,
                    per_page: state.perPage,
                    category: state.category,
                    search: state.search,
                    min_price: state.minPrice,
                    max_price: state.maxPrice,
                    orderby: state.orderby,
                    order: state.order,
                    featured: state.featured
                }
            }).done(function (response) {
                if (!response || !response.success) {
                    $empty.text(response && response.data && response.data.message ? response.data.message : __('Não foi possível carregar os grupos.', 'juntaplay')).addClass('is-visible');
                    return;
                }

                var data = response.data || {};
                var items = data.items || [];

                if (reset) {
                    $results.empty();
                }

                if (!items.length && reset) {
                    $empty.text(__('Nenhum grupo encontrado com os filtros selecionados.', 'juntaplay')).addClass('is-visible');
                } else {
                    $empty.removeClass('is-visible').text('');
                }

                if (items.length) {
                    var markup = items.map(renderPoolCard);
                    $results.append(markup.join(''));
                }

                state.page = (data.page || 1) + 1;
                state.hasMore = (data.page || 1) < (data.pages || 1);
                if (!state.hasMore) {
                    $load.attr('disabled', true).attr('hidden', 'hidden');
                } else {
                    $load.attr('disabled', false).removeAttr('hidden').text(__('Carregar mais campanhas', 'juntaplay'));
                }

                updateMeta(data.total || 0);
            }).fail(function () {
                $empty.text(__('Não foi possível carregar os grupos.', 'juntaplay')).addClass('is-visible');
            }).always(function () {
                togglePoolsLoading(false);
            });
        }

        $form.on('submit', function (event) {
            event.preventDefault();
            state.category = ($form.find('[name="category"]').val() || '').toString();
            state.search = ($form.find('[name="search"]').val() || '').toString();
            state.orderby = ($form.find('[name="orderby"]').val() || 'created_at').toString();
            state.order = ($form.find('[name="order"]').val() || 'desc').toString();
            state.minPrice = ($form.find('[name="min_price"]').val() || '').toString();
            state.maxPrice = ($form.find('[name="max_price"]').val() || '').toString();
            fetchPools(true);
        });

        $load.on('click', function (event) {
            event.preventDefault();
            if (!state.hasMore || state.loading) {
                return;
            }
            fetchPools(false);
        });

        fetchPools(true);
    }

    function initDashboardTabs($container) {
        if (!$container || !$container.length) {
            return;
        }

        var $tabs = $container.find('[data-dashboard-tab]');
        var $panels = $container.find('[data-dashboard-panel]');

        if (!$tabs.length || !$panels.length) {
            return;
        }

        var active = null;

        function setActive(id) {
            if (!id) {
                return;
            }

            active = id;

            $tabs.each(function () {
                var $tab = $(this);
                var tabId = ($tab.data('dashboardTab') || '').toString();
                var isCurrent = tabId === id;
                $tab.toggleClass('is-active', isCurrent);
                $tab.attr('aria-selected', isCurrent ? 'true' : 'false');
            });

            $panels.each(function () {
                var $panel = $(this);
                var panelId = ($panel.data('dashboardPanel') || '').toString();
                var show = panelId === id;
                $panel.toggleClass('is-active', show);
                $panel.attr('aria-hidden', show ? 'false' : 'true');
            });

            if (window.history && window.history.replaceState) {
                try {
                    var url = new URL(window.location.href);
                    url.searchParams.set('jp_tab', id);
                    window.history.replaceState({}, document.title, url.toString());
                } catch (error) {
                    // ignore URL manipulation failures
                }
            }
        }

        $tabs.on('click', function (event) {
            event.preventDefault();
            var id = ($(this).data('dashboardTab') || '').toString();
            if (id && id !== active) {
                setActive(id);
            }
        });

        var defaultTab = ($tabs.filter('.is-active').first().data('dashboardTab') || '').toString();

        try {
            var params = new URL(window.location.href).searchParams;
            var requested = (params.get('jp_tab') || '').toString();
            if (requested && $tabs.filter('[data-dashboard-tab="' + requested + '"]').length) {
                defaultTab = requested;
            }
        } catch (error) {
            // ignore URL parsing failures
        }

        if (!defaultTab && window.location.hash) {
            var hash = window.location.hash.replace('#', '');
            if (hash.indexOf('jp_tab=') === 0) {
                var hashValue = hash.split('=')[1] || '';
                if (hashValue && $tabs.filter('[data-dashboard-tab="' + hashValue + '"]').length) {
                    defaultTab = hashValue;
                }
            }
        }

        if (!defaultTab && $tabs.length) {
            defaultTab = ($tabs.first().data('dashboardTab') || '').toString();
        }

        setActive(defaultTab);
    }

    $(function () {
        $('.juntaplay-quota-selector').each(function () {
            initQuotaSelector($(this));
        });

        $('.juntaplay-pool-catalog').each(function () {
            initPoolCatalog($(this));
        });

        $('[data-jp-two-factor]').each(function () {
            initTwoFactor($(this));
        });

        $('.juntaplay-groups[data-jp-groups]').each(function () {
            initGroupsDirectory($(this));
        });

        $('[data-group-cover]').each(function () {
            initGroupCoverPicker($(this));
        });

        $('.juntaplay-group-rotator').each(function () {
            initGroupRotator($(this));
        });

        $('.juntaplay-dashboard[data-dashboard]').each(function () {
            initDashboardTabs($(this));
        });

        var $autoCreateTemplate = $('#jp-group-create-template');
        if ($autoCreateTemplate.length && parseInt($autoCreateTemplate.data('autoOpen'), 10)) {
            openGroupCreate();
        }
    });

    function applyGroupFilters($scope) {
        var $list = $scope.find('[data-group-list]');
        var $items = $list.find('[data-group-item]');
        var roleFilter = ($scope.data('role-filter') || 'all').toString();
        var statusFilter = ($scope.data('status-filter') || 'all').toString();
        var visibleCount = 0;

        $items.each(function () {
            var $item = $(this);
            var role = ($item.data('group-role') || '').toString();
            var status = ($item.data('group-status') || '').toString();
            var hide = false;

            if (roleFilter === 'owned' && role !== 'owner' && role !== 'manager') {
                hide = true;
            } else if (roleFilter === 'member' && (role === 'owner' || role === 'manager')) {
                hide = true;
            }

            if (!hide && statusFilter !== 'all' && status !== statusFilter) {
                hide = true;
            }

            $item.toggleClass('is-hidden', hide);

            if (!hide) {
                visibleCount++;
            }
        });

        var $empty = $list.find('[data-group-empty]');
        if ($empty.length) {
            if (visibleCount === 0) {
                $empty.removeClass('is-hidden');
            } else {
                $empty.addClass('is-hidden');
            }
        }
    }

    $(document).on('click', '[data-group-filter]', function (event) {
        event.preventDefault();

        var $button = $(this);
        var filter = ($button.data('group-filter') || 'all').toString();
        var $groups = $button.closest('[data-groups]');

        if (!$groups.length) {
            return;
        }

        $button.addClass('is-active').attr('aria-selected', 'true');
        $button.siblings('[data-group-filter]').removeClass('is-active').attr('aria-selected', 'false');

        $groups.data('role-filter', filter);
        applyGroupFilters($groups);
    });

    $(document).on('change', '[data-group-status-filter]', function () {
        var $select = $(this);
        var status = ($select.val() || 'all').toString();
        var $groups = $select.closest('[data-groups]');

        if (!$groups.length) {
            return;
        }

        $groups.data('status-filter', status);
        applyGroupFilters($groups);
    });

    $(document).on('click', '[data-group-card-toggle]', function (event) {
        event.preventDefault();

        var $button = $(this);
        var expanded = $button.attr('aria-expanded') === 'true';
        expanded = !expanded;

        $button.attr('aria-expanded', expanded ? 'true' : 'false');

        var expandLabel = $button.data('labelExpand') || $button.data('label-expand') || '';
        var collapseLabel = $button.data('labelCollapse') || $button.data('label-collapse') || '';
        var $label = $button.find('.juntaplay-group-card__toggle-label');

        if ($label.length) {
            if (expanded && collapseLabel) {
                $label.text(collapseLabel);
            } else if (!expanded && expandLabel) {
                $label.text(expandLabel);
            }
        }

        var $card = $button.closest('.juntaplay-group-card');
        var $details = $card.find('[data-group-card-details]').first();

        if ($details.length) {
            if (expanded) {
                $details.removeAttr('hidden');
            } else {
                $details.attr('hidden', 'hidden');
            }
        }

        if ($card.length) {
            $card.toggleClass('is-expanded', expanded);
        }
    });

    $(document).on('click', '[data-group-detail-trigger]', function (event) {
        event.preventDefault();

        var target = ($(this).data('detailTarget') || $(this).data('target') || '').toString();
        if (!target) {
            return;
        }

        if (target.charAt(0) !== '#') {
            target = '#' + target;
        }

        openGroupDetail(target);
    });

    function resolveAuthConfig($context) {
        var auth = (typeof window !== 'undefined' && typeof window.JuntaPlay !== 'undefined' && window.JuntaPlay.auth)
            ? window.JuntaPlay.auth
            : {};

        var config = {
            loginUrl: (auth.loginUrl || '').toString(),
            redirectParam: (auth.redirectParam || 'redirect_to').toString(),
            loggedIn: !!auth.loggedIn
        };

        if ($context && typeof $context.closest === 'function') {
            var $rotator = $context.closest('.juntaplay-group-rotator');

            if ($rotator && $rotator.length) {
                var dataLoginUrl = ($rotator.data('loginUrl') || '').toString();
                var dataRedirect = ($rotator.data('redirectParam') || '').toString();
                var dataLogged = $rotator.data('loggedIn');

                if (!config.loginUrl && dataLoginUrl) {
                    config.loginUrl = dataLoginUrl;
                }

                if (dataRedirect) {
                    config.redirectParam = dataRedirect;
                }

                if (typeof dataLogged !== 'undefined') {
                    config.loggedIn = !!dataLogged;
                }
            }
        }

        if (!config.redirectParam) {
            config.redirectParam = 'redirect_to';
        }

        return config;
    }

    function buildGroupLoginRedirect(targetUrl, $context) {
        if (typeof window === 'undefined') {
            return targetUrl;
        }

        var auth = resolveAuthConfig($context);
        var loginUrl = (auth.loginUrl || '').toString();
        var redirectParam = (auth.redirectParam || 'redirect_to').toString();
        var redirectTarget = targetUrl;

        if (!loginUrl) {
            return targetUrl;
        }

        try {
            var redirectUrl = new URL(targetUrl, window.location.href);
            if (redirectUrl.hash) {
                var hashValue = redirectUrl.hash.replace(/^#/, '');
                redirectUrl.hash = '';
                if (hashValue) {
                    redirectUrl.searchParams.set('jp_group_anchor', hashValue);
                }
            }
            redirectTarget = redirectUrl.toString();
        } catch (err) {
            // ignore parse errors and fall back to the raw targetUrl
        }

        try {
            var url = new URL(loginUrl, window.location.origin);
            url.searchParams.set(redirectParam, redirectTarget);
            return url.toString();
        } catch (error) {
            var joiner = loginUrl.indexOf('?') === -1 ? '?' : '&';
            return loginUrl + joiner + encodeURIComponent(redirectParam) + '=' + encodeURIComponent(redirectTarget);
        }
    }

    function getGroupAnchor(groupId) {
        var base = window.location.origin + window.location.pathname + window.location.search;
        var hash = groupId ? '#jp-group-card-' + groupId : window.location.hash;

        return base + hash;
    }

    function handleGroupGuestRedirect($trigger, groupId, event) {
        var auth = resolveAuthConfig($trigger);

        if (auth.loggedIn) {
            return false;
        }

        if (!$trigger.closest('.juntaplay-group-rotator').length) {
            return false;
        }

        var target = getGroupAnchor(groupId);

        if (event && typeof event.preventDefault === 'function') {
            event.preventDefault();
            event.stopPropagation();
        }

        window.location.href = buildGroupLoginRedirect(target, $trigger);

        return true;
    }

    $(document).on('click', '[data-jp-group-open]', function (event) {
        if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || event.which === 2) {
            return;
        }

        var $trigger = $(this);
        var groupId = parseInt($trigger.data('groupId'), 10);

        if (!groupId) {
            var $card = $trigger.closest('[data-group-id]');
            if ($card.length) {
                groupId = parseInt($card.data('groupId'), 10) || 0;
            }
        }

        if (!groupId) {
            return;
        }

        if (handleGroupGuestRedirect($trigger, groupId, event)) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();

        if (typeof jpGroupModal !== 'undefined' && jpGroupModal && typeof jpGroupModal.loadDetail === 'function') {
            jpGroupModal.loadDetail(groupId);
        }
    });

    $(document).on('click', '[data-group-create-trigger]', function (event) {
        event.preventDefault();
        openGroupCreate();
    });

    $(document).on('click', '[data-modal-close]', function (event) {
        event.preventDefault();
        closeModal($(this).closest('.juntaplay-modal'));
    });

    $(document).on('click', '.juntaplay-modal__overlay', function (event) {
        event.preventDefault();
        closeModal($(this).closest('.juntaplay-modal'));
    });

    $(document).on('keyup', function (event) {
        if (event.key === 'Escape') {
            $('.juntaplay-modal.is-open').each(function () {
                closeModal($(this));
            });
        }
    });

    $(document).on('click', '[data-jp-share-network]', function (event) {
        event.preventDefault();
        var $button = $(this);
        var network = ($button.data('jpShareNetwork') || '').toString();
        var url = ($button.data('jpShareUrl') || '').toString();
        var text = ($button.data('jpShareText') || document.title || '').toString();
        var target = buildShareTarget(network, url, text);

        if (!target) {
            return;
        }

        openShareWindow(target);
    });

    $(document).on('click', '[data-jp-share-copy]', function (event) {
        event.preventDefault();
        var $button = $(this);
        var url = ($button.data('jpShareUrl') || '').toString();
        if (!url) {
            return;
        }

        var originalLabel = ($button.data('jpShareLabel') || $button.text()).toString();
        var successLabel = ($button.data('jpShareCopied') || __('Link copiado!', 'juntaplay')).toString();

        copyShareUrl(url).then(function () {
            $button.addClass('is-success').text(successLabel);
            window.setTimeout(function () {
                $button.removeClass('is-success').text(originalLabel);
            }, 2200);
        }).catch(function () {
            $button.addClass('is-error');
            window.setTimeout(function () {
                $button.removeClass('is-error').text(originalLabel);
            }, 1800);
        });
    });

    function parseMoneyInput(value) {
        if (value === null || typeof value === 'undefined') {
            return 0;
        }

        var normalized = value.toString().trim();
        if (!normalized) {
            return 0;
        }

        normalized = normalized.replace(/[^0-9,\.\-]/g, '');
        if (!normalized || normalized === '-') {
            return 0;
        }

        if (normalized.indexOf(',') !== -1 && normalized.indexOf('.') !== -1) {
            normalized = normalized.replace(/\./g, '').replace(',', '.');
        } else if (normalized.indexOf(',') !== -1) {
            normalized = normalized.replace(',', '.');
        }

        var amount = parseFloat(normalized);
        return isNaN(amount) ? 0 : amount;
    }

    function toLocaleDecimal(amount) {
        if (!isFinite(amount) || amount <= 0) {
            return '';
        }

        try {
            return amount.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        } catch (e) {
            return amount.toFixed(2);
        }
    }

    function gatherGroupShareData($form) {
        var $categoryField = $form.find('[name="jp_profile_group_category"]');
        var categoryValue = $.trim(($categoryField.val() || '').toString());
        var categoryLabel = $.trim(($categoryField.find('option:selected').text() || '').toString());

        return {
            name: $.trim(($form.find('[name="jp_profile_group_name"]').val() || '').toString()),
            service: $.trim(($form.find('[name="jp_profile_group_service"]').val() || '').toString()),
            serviceUrl: $.trim(($form.find('[name="jp_profile_group_service_url"]').val() || '').toString()),
            rules: $.trim(($form.find('[name="jp_profile_group_rules"]').val() || '').toString()),
            description: $.trim(($form.find('[name="jp_profile_group_description"]').val() || '').toString()),
            price: parseMoneyInput($form.find('[name="jp_profile_group_price"]').val()),
            promoEnabled: $form.find('[data-group-promo-toggle]').is(':checked'),
            promo: parseMoneyInput($form.find('[name="jp_profile_group_price_promo"]').val()),
            slotsTotal: parseInt(($form.find('[name="jp_profile_group_slots_total"]').val() || '0').toString(), 10) || 0,
            slotsReserved: parseInt(($form.find('[name="jp_profile_group_slots_reserved"]').val() || '0').toString(), 10) || 0,
            memberPrice: parseMoneyInput($form.find('[name="jp_profile_group_member_price"]').val()),
            support: $.trim(($form.find('[name="jp_profile_group_support"]').val() || '').toString()),
            delivery: $.trim(($form.find('[name="jp_profile_group_delivery"]').val() || '').toString()),
            access: $.trim(($form.find('[name="jp_profile_group_access"]').val() || '').toString()),
            category: categoryValue,
            categoryLabel: categoryLabel,
            instantAccess: $form.find('[name="jp_profile_group_instant"]').is(':checked')
        };
    }

    function updateGroupSharePreview($form) {
        if (!$form.length) {
            return;
        }

        var $share = $form.find('[data-group-share]');
        if (!$share.length) {
            return;
        }

        var data = gatherGroupShareData($form);
        var domain = ($share.data('domain') || '').toString();
        var fallbackPromo = ($share.find('[data-group-share-field="promo"]').data('fallback') || '').toString();
        var fallbackCategory = ($share.find('[data-group-share-field="category"]').data('empty') || '').toString();
        var fallbackInstant = ($share.find('[data-group-share-field="instant_access"]').data('fallback') || '').toString();

        var priceText = data.price > 0 ? formatCurrency(data.price, 'BRL', 'pt-BR') : '';
        var promoText = data.promoEnabled && data.promo > 0 ? formatCurrency(data.promo, 'BRL', 'pt-BR') : '';
        var memberText = data.memberPrice > 0 ? formatCurrency(data.memberPrice, 'BRL', 'pt-BR') : '';
        var reservedText = data.slotsReserved > 0 ? data.slotsReserved.toString() : '';
        var promoLabel = promoText || fallbackPromo || 'Não';
        var promoFlag = (data.promoEnabled && data.promo > 0) ? 'Sim' : 'Não';
        var categoryText = data.categoryLabel || fallbackCategory;
        var instantText = data.instantAccess ? 'Ativado' : (fallbackInstant || 'Desativado');

        var fieldMap = {
            service: data.service,
            name: data.name,
            category: categoryText,
            service_url: data.serviceUrl,
            rules: data.rules,
            description: data.description,
            price: priceText,
            promo_flag: promoFlag,
            promo: promoLabel,
            slots_total: data.slotsTotal > 0 ? data.slotsTotal.toString() : '',
            slots_reserved: reservedText || '0',
            member_price: memberText,
            support: data.support,
            delivery: data.delivery,
            access: data.access,
            instant_access: instantText
        };

        $.each(fieldMap, function (key, value) {
            var $target = $share.find('[data-group-share-field="' + key + '"]');
            if (!$target.length) {
                return;
            }

            var fallback = ($target.data('empty') || $target.data('fallback') || '').toString();
            var output = value;

            if (typeof output === 'string') {
                output = output.trim();
            }

            if (output) {
                $target.text(output);
            } else if (fallback) {
                $target.text(fallback);
            } else {
                $target.text('—');
            }
        });

        var lines = [];
		
        if (data.service) {
            lines.push('Serviço: ' + data.service);
        }
        if (data.name) {
            lines.push('Nome do grupo: ' + data.name);
        }
        lines.push('Tipo: Público');
        if (categoryText) {
            lines.push('Categoria: ' + categoryText);
        }
        if (data.serviceUrl) {
            lines.push('Site: ' + data.serviceUrl);
        }
        if (data.rules) {
            lines.push('Regras: ' + data.rules);
        }
        if (data.description) {
            lines.push('Descrição: ' + data.description);
        }
        if (priceText) {
            lines.push('Valor do serviço: ' + priceText);
        }
        lines.push('É valor promocional?: ' + promoFlag);
        lines.push('Valor promocional: ' + promoLabel);
        if (data.slotsTotal > 0) {
            lines.push('Vagas totais: ' + data.slotsTotal);
        }
        if (data.slotsReserved > 0) {
            lines.push('Reservadas para você: ' + data.slotsReserved);
        }
        if (memberText) {
            lines.push('Os membros vão pagar: ' + memberText);
        }
        if (data.support) {
            lines.push('Suporte a membros: ' + data.support);
        }
        if (data.delivery) {
            lines.push('Envio de acesso: ' + data.delivery);
        }
        if (data.access) {
            lines.push('Forma de acesso: ' + data.access);
        }
        lines.push('Acesso instantâneo: ' + instantText);

        var shareText = lines.join('\n');
        if (!shareText) {
            shareText = ($share.data('empty') || '').toString();
        }

        var $snippet = $share.find('[data-group-share-snippet]');
        if ($snippet.length) {
            $snippet.text(shareText);
        }

        var $textarea = $share.find('[data-group-share-text]');
        if ($textarea.length) {
            $textarea.val(shareText);
        }
    }

    function updateGroupPricePreview($form) {
        if (!$form.length) {
            return;
        }

        var price = parseMoneyInput($form.find('[name="jp_profile_group_price"]').val());
        var $promoToggle = $form.find('[data-group-promo-toggle]');
        var promoEnabled = $promoToggle.is(':checked');
        var promo = promoEnabled ? parseMoneyInput($form.find('[name="jp_profile_group_price_promo"]').val()) : 0;
        var total = parseInt($form.find('[name="jp_profile_group_slots_total"]').val(), 10) || 0;
        var reserved = parseInt($form.find('[name="jp_profile_group_slots_reserved"]').val(), 10) || 0;
        var available = Math.max(1, total - reserved);
        var basis = promo > 0 ? promo : price;
        var suggestion = basis > 0 ? (basis / available) : 0;
        var $member = $form.find('[data-group-member-input]');
        var $preview = $form.find('[data-group-price-preview]');
        var memberDirty = $member.data('jpDirty') === true;
        var memberGenerated = $member.data('group-member-generated') === 'yes';

        if ($member.length) {
            if (!memberDirty && suggestion > 0) {
                var formatted = toLocaleDecimal(suggestion);
                if (formatted) {
                    $member.val(formatted);
                    $member.data('group-member-generated', 'yes');
                }
            } else if (!memberDirty && suggestion <= 0 && memberGenerated) {
                $member.val('');
            }
        }

        if ($preview.length) {
            var previewAmount = parseMoneyInput($member.val());
            if (previewAmount <= 0 && suggestion > 0) {
                previewAmount = suggestion;
            }

            if (previewAmount > 0) {
                var suffix = ($preview.data('suffix') || '').toString().trim();
                var message = formatCurrency(previewAmount, 'BRL', 'pt-BR');
                if (suffix) {
                    message += ' ' + suffix;
                }
                $preview.text(message).removeClass('is-hidden');
            } else {
                var emptyMessage = ($preview.data('empty') || '').toString();
                if (emptyMessage) {
                    $preview.text(emptyMessage).removeClass('is-hidden');
                } else {
                    $preview.addClass('is-hidden');
                }
            }
        }

        updateGroupSharePreview($form);
    }

    function togglePromoField($toggle) {
        var $form = $toggle.closest('form');
        var $wrapper = $form.find('[data-group-promo-field]');

        if ($toggle.is(':checked')) {
            $wrapper.removeClass('is-hidden');
        } else {
            $wrapper.addClass('is-hidden');
            $wrapper.find('input').val('');
        }

        updateGroupPricePreview($form);
    }

    $(document).on('change', '[data-group-promo-toggle]', function () {
        togglePromoField($(this));
    });

    $(document).on('input change', '[data-group-price-input], [data-group-slot-input]', function () {
        var $form = $(this).closest('form');
        updateGroupPricePreview($form);
    });

    $(document).on('change', '#jp-group-instant', function () {
        var $input = $(this);
        var $caption = $input.closest('.juntaplay-toggle').find('.juntaplay-toggle__caption');

        if ($caption.length) {
            var activeLabel = ($caption.data('toggle-caption-active') || 'Ativado').toString();
            var inactiveLabel = ($caption.data('toggle-caption-inactive') || 'Desativado').toString();
            $caption.text($input.is(':checked') ? activeLabel : inactiveLabel);
        }

        updateGroupSharePreview($input.closest('form'));
    });

    $(document).on('input', '[data-group-member-input]', function () {
        var $input = $(this);
        $input.data('jpDirty', true);
        $input.data('group-member-generated', 'no');
        updateGroupPricePreview($input.closest('form'));
    });

    $(document).on('blur', '[data-group-member-input]', function () {
        var $input = $(this);
        var amount = parseMoneyInput($input.val());
        if (amount > 0) {
            $input.val(toLocaleDecimal(amount));
        }
    });

    $(document).on('input change', '[data-group-share-watch]', function () {
        var $form = $(this).closest('form');
        updateGroupSharePreview($form);
    });

    $(document).on('click', '[data-group-share-copy]', function (event) {
        event.preventDefault();

        var $button = $(this);
        var $share = $button.closest('[data-group-share]');
        var $textarea = $share.find('[data-group-share-text]');

        if (!$textarea.length) {
            var $form = $button.closest('form');
            if ($form.length) {
                $textarea = $form.find('[data-group-share-text]');
            }
        }

        if (!$textarea.length) {
            return;
        }

        var text = ($textarea.val() || '').toString();
        if (!text) {
            return;
        }

        var defaultLabel = ($button.data('default-label') || $button.text() || '').toString();
        var successLabel = ($button.data('success-label') || defaultLabel).toString();

        var onSuccess = function () {
            $button.text(successLabel).addClass('is-success');
            setTimeout(function () {
                $button.text(defaultLabel).removeClass('is-success');
            }, 2000);
        };

        var onFailure = function () {
            alert($button.data('error-label') || 'Não foi possível copiar agora. Copie manualmente.');
        };

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(onSuccess).catch(onFailure);
        } else {
            var element = $textarea[0];
            var shouldHide = element.hasAttribute('hidden');

            try {
                element.removeAttribute('hidden');
                element.select();
                var result = document.execCommand('copy');
                if (result) {
                    onSuccess();
                } else {
                    onFailure();
                }
            } catch (err) {
                onFailure();
            } finally {
                if (shouldHide) {
                    element.setAttribute('hidden', 'hidden');
                }
                if (window.getSelection) {
                    try {
                        window.getSelection().removeAllRanges();
                    } catch (ignore) {
                        /* noop */
                    }
                }
            }
        }
    });

    function toggleGroupPoolHelp($select) {
        var $help = $select.closest('.juntaplay-form__group').find('[data-group-pool-help]');
        if (!$help.length) {
            return;
        }

        var hasValue = $.trim(($select.val() || '').toString()) !== '';
        $help.toggleClass('is-hidden', !hasValue);
    }

    function updateRuleBuilder($builder) {
        if (!$builder || !$builder.length) {
            return;
        }

        var $form = $builder.closest('form');
        var $output = $form.find('[data-rule-output]');
        var $items = $builder.find('[data-rule-item]');
        var $extra = $builder.find('[data-rule-extra]');
        var $counter = $builder.find('[data-rule-extra-counter]');

        var parts = [];
        $items.each(function (index, element) {
            var value = $.trim($(element).val() || '');
            if (value) {
                parts.push((index + 1) + ' - ' + value);
            }
        });

        if ($extra.length) {
            var extraText = $.trim($extra.val() || '');
            if (extraText) {
                parts.push(extraText);
            }

            if ($counter.length) {
                var max = parseInt($extra.attr('maxlength'), 10) || 0;
                var current = extraText.length;
                $counter.text(max ? current + ' / ' + max : current);
            }
        }

        if ($output.length) {
            $output.val(parts.join('\n'));
        }
    }

    function initRuleBuilder($form) {
        if (!$form || !$form.length) {
            return;
        }

        var $builder = $form.find('[data-rule-builder]');
        if (!$builder.length) {
            return;
        }

        var $items = $builder.find('[data-rule-item]');
        var $extra = $builder.find('[data-rule-extra]');

        $items.on('input change', function () {
            updateRuleBuilder($builder);
        });

        $extra.on('input change', function () {
            updateRuleBuilder($builder);
        });

        updateRuleBuilder($builder);
    }

    function syncChoiceGroupState($group) {
        if (!$group || !$group.length) {
            return;
        }

        $group.find('.juntaplay-choice-card').each(function () {
            var $card = $(this);
            var $input = $card.find('input[type="radio"]').first();

            $card.toggleClass('is-active', $input.length && $input.prop('checked'));
        });
    }

    function initChoiceGroups($form) {
        if (!$form || !$form.length) {
            return;
        }

        var $groups = $form.find('[data-choice-group]');

        if (!$groups.length) {
            return;
        }

        $groups.each(function () {
            var $group = $(this);
            var syncSelector = ($group.data('choiceSync') || '').toString();
            var $syncTarget = syncSelector !== '' ? $form.find(syncSelector) : $();

            $group.on('change', 'input[type="radio"]', function () {
                var $input = $(this);

                syncChoiceGroupState($group);

                if ($syncTarget.length) {
                    $syncTarget.val($input.val());
                    $syncTarget.trigger('change');
                }
            });

            var $checked = $group.find('input[type="radio"]:checked').first();
            if ($checked.length && $syncTarget.length) {
                $syncTarget.val($checked.val());
            }

            syncChoiceGroupState($group);
        });
    }

    function initAccessTiming($form) {
        if (!$form || !$form.length) {
            return;
        }

        var $timing = $form.find('[data-access-timing]');
        if (!$timing.length) {
            return;
        }

        var $instant = $form.find('[data-access-instant]');
        var $hiddenTiming = $form.find('input[name="jp_profile_group_access_timing"]');
        var $immediateFields = $form.find('[data-access-immediate]');

        function syncTiming() {
            var value = ($timing.val() || '').toString();
            if (value === '') {
                value = 'scheduled';
            }

            var isImmediate = value === 'immediate';
            $immediateFields.toggleClass('is-hidden', !isImmediate);

            if ($instant.length) {
                $instant.val(isImmediate ? 'on' : '');
            }

            if ($hiddenTiming.length) {
                $hiddenTiming.val(value);
            }

            $form.trigger('jp:accessTimingChange', [isImmediate]);
        }

        $timing.on('change', syncTiming);
        syncTiming();
    }

    function initGroupWizard($form) {
        if (!$form || !$form.length) {
            return;
        }

        initRuleBuilder($form);
        initChoiceGroups($form);
        initAccessTiming($form);

        var $steps = $form.find('[data-group-step]');
        if (!$steps.length) {
            return;
        }

        var $stepper = $form.find('[data-group-stepper] .juntaplay-steps__item');
        var $accessStep = $form.find('[data-group-step][data-step-index="4"]');
        var $accessStepper = $form.find('[data-group-stepper] .juntaplay-steps__item[data-step-index="4"]');
        var $progress = $();
        var currentIndex = 0;

        function clearFieldError($field) {
            var $wrapper = $field.closest('.juntaplay-form__group, .juntaplay-form__field');
            if ($wrapper.length) {
                $wrapper.removeClass('has-error');
            }
        }

        function markFieldError($field) {
            var $wrapper = $field.closest('.juntaplay-form__group, .juntaplay-form__field');
            if ($wrapper.length) {
                $wrapper.addClass('has-error');
            }
        }

        function findFirstInvalidField() {
            var $fields = $form.find('input, select, textarea').filter(function () {
                if (this.disabled) {
                    return false;
                }

                var $field = $(this);
                var $step = $field.closest('[data-group-step]');
                if ($step.length && ($step.hasClass('is-hidden') || $step.hasClass('is-disabled'))) {
                    return false;
                }

                var valid = typeof this.checkValidity === 'function' ? this.checkValidity() : true;
                var required = $field.prop('required');
                var value = $.trim(($field.val() || '').toString());

                return !valid || (required && value === '');
            });

            return $fields.first();
        }

        function updateWizardProgress() {}

        function showStep(index, direction) {
            if (index < 0 || index >= $steps.length) {
                return;
            }

            var dir = direction || (index > currentIndex ? 1 : -1);
            var target = index;

            while (target >= 0 && target < $steps.length && $steps.eq(target).hasClass('is-disabled')) {
                target += dir;
            }

            if (target < 0 || target >= $steps.length) {
                return;
            }

            currentIndex = target;
            $steps.addClass('is-hidden');
            $steps.eq(currentIndex).removeClass('is-hidden');

            if ($stepper.length) {
                $stepper.removeClass('is-active');
                $stepper.each(function (idx) {
                    var $item = $(this);
                    if (!$item.hasClass('is-disabled') && idx <= currentIndex) {
                        $item.addClass('is-active');
                    }
                });
            }

            updateWizardProgress();
        }

        function focusInvalidField($field) {
            if (!$field || !$field.length) {
                return;
            }

            markFieldError($field);

            var $step = $field.closest('[data-group-step]');
            var targetIndex = Number.isFinite(parseInt($step.data('stepIndex'), 10))
                ? parseInt($step.data('stepIndex'), 10)
                : currentIndex;

            if ($step.length && targetIndex !== currentIndex) {
                showStep(targetIndex, targetIndex > currentIndex ? 1 : -1);
            }

            window.setTimeout(function () {
                if ($field[0] && typeof $field[0].scrollIntoView === 'function') {
                    $field[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                $field.trigger('focus');
            }, 60);
        }

        $form.on('click', '[data-step-next]', function (event) {
            event.preventDefault();

            var accessTiming = ($form.find('[data-access-timing]').val() || '').toString();
            var isImmediate = accessTiming === 'immediate';

            if (currentIndex === 3 && !isImmediate) {
                $form.trigger('submit');
                return;
            }

            if (currentIndex < $steps.length - 1) {
                showStep(currentIndex + 1, 1);
            }
        });

        $form.on('click', '[data-step-prev]', function (event) {
            event.preventDefault();

            if (currentIndex > 0) {
                showStep(currentIndex - 1, -1);
            }
        });

        $form.on('submit', function (event) {
            var $invalid = findFirstInvalidField();

            if ($invalid.length) {
                event.preventDefault();
                focusInvalidField($invalid);
            }
        });

        $form.on('input change', 'input, select, textarea', function () {
            clearFieldError($(this));
        });

        function toggleAccessStep(isImmediate) {
            if (!$accessStep.length || !$accessStepper.length) {
                return;
            }

            $accessStep.toggleClass('is-disabled is-hidden', !isImmediate);
            $accessStepper.toggleClass('is-disabled', !isImmediate);

            if (!isImmediate && currentIndex > 3) {
                showStep(3, -1);
            }

            updateWizardProgress();
        }

        $form.on('jp:accessTimingChange', function (_event, isImmediate) {
            toggleAccessStep(!!isImmediate);
        });

        toggleAccessStep(($form.find('[data-access-timing]').val() || '').toString() === 'immediate');

        showStep(currentIndex);
    }

    function isWizardAllowed($root) {
        if (!$root || !$root.length) {
            return false;
        }

        var allowed = ($root.attr('data-group-view-allowed') || '').toString();

        return allowed === '1' || allowed === 'true';
    }

    function allowWizardView($root) {
        if (!$root || !$root.length) {
            return;
        }

        $root.attr('data-group-view-allowed', '1');
        $root.data('groupViewAllowed', '1');
    }

    function setGroupCreateView($root, view) {
        if (!$root || !$root.length) {
            return;
        }

        var target = (view || '').toString();
        var $panels = $root.find('[data-group-view]');

        if (!$panels.length) {
            return;
        }

        if (target === 'wizard' && !isWizardAllowed($root)) {
            target = ($root.data('groupViewDefault') || '').toString() || 'selector';
        }

        var matched = false;

        $panels.each(function () {
            var $panel = $(this);
            var panelName = ($panel.data('groupView') || '').toString();
            if (target && panelName === target) {
                $panel.removeClass('is-hidden');
                matched = true;
            } else if (!target && !matched) {
                $panel.removeClass('is-hidden');
                matched = true;
            } else {
                $panel.addClass('is-hidden');
            }
        });

        if (!matched && $panels.length) {
            $panels.addClass('is-hidden');
            $panels.first().removeClass('is-hidden');
            target = ($panels.first().data('groupView') || '').toString();
        }

        if (target) {
            $root.attr('data-group-view-active', target);
        }
    }

    function initGroupViewRoot($root) {
        if (!$root || !$root.length) {
            return;
        }

        var active = ($root.data('groupViewActive') || '').toString();
        var fallback = ($root.data('groupViewDefault') || '').toString();

        if (!active && fallback) {
            active = fallback;
        }

        if (!active) {
            active = 'selector';
        }

        if (active === 'wizard') {
            allowWizardView($root);
        }

        setGroupCreateView($root, active);
    }

    function resetGroupViewRoot($root) {
        if (!$root || !$root.length) {
            return;
        }

        var active = ($root.data('groupViewActive') || '').toString();
        var fallback = ($root.data('groupViewDefault') || '').toString();
        var target = active || fallback || 'selector';

        if (target === 'wizard' && !isWizardAllowed($root)) {
            target = fallback || 'selector';
        }

        setGroupCreateView($root, target);
    }

    $(document).on('click', '[data-group-pool-apply]', function (event) {
        event.preventDefault();

        var $button = $(this);
        var $container = $button.closest('.juntaplay-groups__create-modal, .juntaplay-groups__create-card');
        var $form = $container.find('form').first();

        if (!$form.length) {
            return;
        }

        var poolId = ($button.data('poolId') || '').toString();
        var poolName = ($button.data('poolName') || '').toString();
        var poolPrice = parseFloat(($button.data('poolPrice') || '').toString().replace(',', '.'));
        var poolCategory = ($button.data('poolCategory') || '').toString();
        var poolExcerpt = ($button.data('poolExcerpt') || '').toString();
        var poolTotal = parseInt(($button.data('poolTotal') || '').toString(), 10);
        var poolStart = parseInt(($button.data('poolStart') || '').toString(), 10);
        var poolEnd = parseInt(($button.data('poolEnd') || '').toString(), 10);
        var poolCover = ($button.data('poolCover') || '').toString();
        var poolUrl = ($button.data('poolUrl') || '').toString();
        var poolReserved = Number.isFinite(poolStart) && poolStart > 0 ? Math.max(0, poolStart - 1) : 0;

        var $select = $form.find('[name="jp_profile_group_pool"]');
        if ($select.length) {
            $select.val(poolId);
            $select.trigger('change');
        }

        var $service = $form.find('[name="jp_profile_group_service"]');
        if ($service.length && !$.trim(($service.val() || '').toString()) && poolName) {
            $service.val(poolName).trigger('input');
        }

        var $description = $form.find('[name="jp_profile_group_description"]');
        if ($description.length && !$.trim(($description.val() || '').toString()) && poolExcerpt) {
            $description.val(poolExcerpt).trigger('input');
        }

        var $serviceUrl = $form.find('[name="jp_profile_group_service_url"]');
        if ($serviceUrl.length && !$.trim(($serviceUrl.val() || '').toString()) && poolUrl) {
            $serviceUrl.val(poolUrl).trigger('input');
        }

        var $name = $form.find('[name="jp_profile_group_name"]');
        if ($name.length && !$.trim(($name.val() || '').toString()) && poolName) {
            $name.val(poolName).trigger('input');
        }

        if (Number.isFinite(poolPrice) && poolPrice > 0) {
            var $price = $form.find('[name="jp_profile_group_price"]');
            if ($price.length) {
                $price.val(toLocaleDecimal(poolPrice)).trigger('input');
            }
        }

        if (poolCategory) {
            var $category = $form.find('[name="jp_profile_group_category"]');
            if ($category.length) {
                $category.val(poolCategory).trigger('change');
            }
        }

        if (Number.isFinite(poolTotal) && poolTotal > 0) {
            var $total = $form.find('[name="jp_profile_group_slots_total"]');
            if ($total.length) {
                $total.val(poolTotal).trigger('input');
            }
        }

        if (Number.isFinite(poolReserved) && poolReserved >= 0) {
            var $reserved = $form.find('[name="jp_profile_group_slots_reserved"]');
            if ($reserved.length) {
                $reserved.val(poolReserved).trigger('input');
            }
        }

        if (poolCover) {
            var $coverInput = $form.find('[data-group-cover-input]');
            var $coverPreview = $form.find('[data-group-cover-preview]');
            if ($coverInput.length) {
                $coverInput.val('').data('externalCover', poolCover);
            }
            if ($coverPreview.length) {
                $coverPreview.css('background-image', 'url(' + poolCover + ')').find('img').attr('src', poolCover);
            }
        }

        var $viewRoot = $button.closest('[data-group-view-root]');
        if ($viewRoot.length) {
            allowWizardView($viewRoot);
            setGroupCreateView($viewRoot, 'wizard');
        }
    });

    $(document).on('click', '[data-group-view-target]', function (event) {
        event.preventDefault();

        var $button = $(this);
        var target = ($button.data('groupViewTarget') || '').toString();

        if (!target) {
            return;
        }

        var $root = $button.closest('[data-group-view-root]');
        if (!$root.length) {
            $root = $('[data-group-view-root]').first();
        }

        if (!$root.length) {
            return;
        }

        if (target === 'wizard') {
            allowWizardView($root);
        }
        setGroupCreateView($root, target);

        if (target === 'wizard') {
            var $focusTarget = $root.find('form[data-group-wizard]').first().find('input, select, textarea').filter(':visible').first();
            if ($focusTarget.length) {
                $focusTarget.trigger('focus');
            }
        }
    });

    $(document).on('click', '[data-group-start-scratch]', function (event) {
        event.preventDefault();

        var $button = $(this);
        var $root = $button.closest('[data-group-view-root]');

        if (!$root.length) {
            return;
        }

        var $form = $root.find('form[data-group-wizard]').first();
        if ($form.length) {
            var $poolSelect = $form.find('[name="jp_profile_group_pool"]');
            if ($poolSelect.length) {
                $poolSelect.val('').trigger('change');
            }
        }

        allowWizardView($root);
        setGroupCreateView($root, 'wizard');
    });

    $(document).on('change', '[name="jp_profile_group_pool"]', function () {
        toggleGroupPoolHelp($(this));
    });

    $(document).on('click', '[data-group-suggestion-apply]', function (event) {
        event.preventDefault();

        var $button = $(this);
        var $card = $button.closest('[data-group-suggestion]');
        var $container = $button.closest('.juntaplay-groups__create-modal, .juntaplay-groups__create-card');
        var $form = $container.find('form').first();

        if (!$card.length || !$form.length) {
            return;
        }

        var service = ($card.data('title') || '').toString();
        var amount = parseFloat(($card.data('amount') || '').toString().replace(',', '.'));
        var category = ($card.data('category') || '').toString();
        var description = ($card.data('description') || '').toString();

        if (service) {
            $form.find('[name="jp_profile_group_service"]').val(service).trigger('input');
        }

        var $name = $form.find('[name="jp_profile_group_name"]');
        if ($name.length && !$.trim(($name.val() || '').toString())) {
            $name.val(service).trigger('input');
        }

        if (description) {
            var $description = $form.find('[name="jp_profile_group_description"]');
            if ($description.length && !$.trim(($description.val() || '').toString())) {
                $description.val(description).trigger('input');
            }
        }

        if (Number.isFinite(amount) && amount > 0) {
            $form.find('[name="jp_profile_group_price"]').val(toLocaleDecimal(amount)).trigger('input');
        }

        if (category) {
            $form.find('[name="jp_profile_group_category"]').val(category).trigger('change');
        }

        var $promoToggle = $form.find('[data-group-promo-toggle]');
        if ($promoToggle.length) {
            $promoToggle.prop('checked', false);
            togglePromoField($promoToggle);
        }

        var $promoValue = $form.find('[name="jp_profile_group_price_promo"]');
        if ($promoValue.length) {
            $promoValue.val('');
        }

        var $memberInput = $form.find('[name="jp_profile_group_member_price"]');
        if ($memberInput.length) {
            $memberInput.val('').data('group-member-generated', 'yes');
        }

        updateGroupPricePreview($form);
        updateGroupSharePreview($form);
    });

    $(document.body).on('updated_wc_div updated_cart_totals wc_fragments_refreshed', function () {
        $('.juntaplay-quota-selector').each(function () {
            refreshSelected($(this));
        });
    });

    $(function () {
        $('[data-groups]').each(function () {
            applyGroupFilters($(this));
        });

        $('[data-group-member-input]').each(function () {
            var $input = $(this);
            if ($input.data('group-member-generated') === 'yes') {
                $input.removeData('jpDirty');
            } else if ($input.val()) {
                $input.data('jpDirty', true);
            }
            updateGroupPricePreview($input.closest('form'));
        });

        $('[data-group-promo-toggle]').each(function () {
            var $toggle = $(this);
            if ($toggle.is(':checked')) {
                $toggle.closest('form').find('[data-group-promo-field]').removeClass('is-hidden');
            }
        });

        $('[name="jp_profile_group_pool"]').each(function () {
            toggleGroupPoolHelp($(this));
        });

        $('[data-group-wizard]').each(function () {
            initGroupWizard($(this));
        });

        $('[data-group-share]').each(function () {
            var $form = $(this).closest('form');
            updateGroupSharePreview($form);
        });
    });

    function activateAuthView($auth, view) {
        if (!view) {
            return;
        }

        $auth.attr('data-active-view', view);

        $auth.find('.juntaplay-auth__switch-btn').each(function () {
            var $btn = $(this);
            var isActive = $btn.data('target') === view;
            $btn.toggleClass('is-active', isActive);
            $btn.attr('aria-selected', isActive ? 'true' : 'false');
        });

        $auth.find('.juntaplay-auth__pane').each(function () {
            var $pane = $(this);
            var isActive = $pane.data('pane') === view;
            $pane.toggleClass('is-active', isActive);
            $pane.attr('aria-hidden', isActive ? 'false' : 'true');
        });
    }

    $(document).on('click', '.juntaplay-auth__switch-btn', function (event) {
        event.preventDefault();

        var $btn = $(this);

        if ($btn.is(':disabled')) {
            return;
        }

        var target = $btn.data('target');
        var $auth = $btn.closest('.juntaplay-auth');

        activateAuthView($auth, target);
    });

    $(function () {
        $('.juntaplay-auth').each(function () {
            var $auth = $(this);
            activateAuthView($auth, $auth.data('active-view'));
        });
    });

    function updateRegisterStep($form, step) {
        var $steps = $form.find('[data-auth-step]');
        var total = $steps.length || 1;
        var current = parseInt(step, 10) || 1;

        if (current < 1) {
            current = 1;
        }

        if (current > total) {
            current = total;
        }

        $form.attr('data-step', current);

        $steps.each(function () {
            var $step = $(this);
            var stepNumber = parseInt($step.data('authStep'), 10) || 1;
            var isActive = stepNumber === current;
            $step.toggleClass('is-active', isActive);
            $step.attr('aria-hidden', isActive ? 'false' : 'true');
        });

        $form.find('[data-auth-step-indicator]').each(function () {
            var $indicator = $(this);
            var indicatorStep = parseInt($indicator.data('authStepIndicator'), 10) || 1;
            var isActive = indicatorStep === current;
            var isComplete = indicatorStep < current;
            $indicator.toggleClass('is-active', isActive);
            $indicator.toggleClass('is-complete', isComplete);
        });

        var $prev = $form.find('[data-auth-step-prev]');
        var $next = $form.find('[data-auth-step-next]');
        var $submit = $form.find('[data-auth-step-submit]');

        $prev.prop('disabled', current <= 1);

        if ($next.length) {
            $next.toggle(current < total);
        }

        if ($submit.length) {
            $submit.toggle(current === total);
        }
    }

    function validateRegisterStep($form, step) {
        var $step = $form.find('[data-auth-step="' + step + '"]').first();

        if (!$step.length) {
            return true;
        }

        var isValid = true;

        $step.find('input, select, textarea').each(function () {
            var field = this;

            if (typeof field.checkValidity === 'function' && !field.checkValidity()) {
                if (typeof field.reportValidity === 'function') {
                    field.reportValidity();
                }
                isValid = false;
                return false;
            }

            return true;
        });

        return isValid;
    }

    function changeRegisterStep($form, delta) {
        var current = parseInt($form.attr('data-step'), 10) || 1;
        var target = current + delta;

        if (delta > 0 && !validateRegisterStep($form, current)) {
            return;
        }

        updateRegisterStep($form, target);
    }

    $(document).on('click', '[data-auth-step-next]', function (event) {
        event.preventDefault();

        var $form = $(this).closest('form[data-register-wizard]');

        if ($form.length) {
            changeRegisterStep($form, 1);
        }
    });

    $(document).on('click', '[data-auth-step-prev]', function (event) {
        event.preventDefault();

        var $form = $(this).closest('form[data-register-wizard]');

        if ($form.length) {
            changeRegisterStep($form, -1);
        }
    });

    $(document).on('submit', 'form[data-register-wizard]', function (event) {
        var $form = $(this);
        var current = parseInt($form.attr('data-step'), 10) || 1;
        var total = $form.find('[data-auth-step]').length || 1;

        if (current < total) {
            event.preventDefault();
            changeRegisterStep($form, 1);
            return false;
        }

        if (!validateRegisterStep($form, current)) {
            event.preventDefault();
            return false;
        }

        return true;
    });

    $(function () {
        $('form[data-register-wizard]').each(function () {
            var $form = $(this);
            updateRegisterStep($form, parseInt($form.data('step'), 10) || 1);
        });
    });

    var socialPopup = null;
    var socialContext = null;

    function displaySocialAuthMessage(status, message, pane) {
        var $pane = pane && pane.length ? pane : null;
        var $auth = $pane && $pane.length ? $pane.closest('.juntaplay-auth') : $('.juntaplay-auth').first();

        if (!$auth.length) {
            if (status === 'error' && message) {
                window.alert(message);
            }
            return;
        }

        if (status === 'success' || !message) {
            $auth.find('[data-jp-auth-social-error]').remove();
            return;
        }

        if (!$pane || !$pane.length) {
            $pane = $auth.find('.juntaplay-auth__pane.is-active').first();
            if (!$pane.length) {
                $pane = $auth.find('.juntaplay-auth__pane--login').first();
            }
        }

        var $form = $pane.find('.juntaplay-auth__form').first();
        var $alert = $pane.find('[data-jp-auth-social-error]').first();

        if (!$form.length) {
            window.alert(message);
            return;
        }

        if (!$alert.length) {
            $alert = $('<div class="juntaplay-auth__alert" role="alert" data-jp-auth-social-error><ul></ul></div>');
            $form.prepend($alert);
        }

        var $list = $alert.find('ul').first();

        if (!$list.length) {
            $list = $('<ul></ul>');
            $alert.empty().append($list);
        }

        $list.empty().append($('<li></li>').text(message));
    }

    $(document).on('click', '[data-jp-auth-popup]', function (event) {
        var $link = $(this);
        var href = ($link.attr('href') || '').toString();
        var $pane = $link.closest('.juntaplay-auth__pane');

        if (!href || href === '#') {
            return;
        }

        event.preventDefault();

        socialContext = $pane.length ? ($pane.data('pane') || null) : null;

        displaySocialAuthMessage('success', '', $pane);

        var popupUrl = href;
        var contextParam = ($link.data('jpAuthContext') || '').toString();

        try {
            var parsed = new URL(href, window.location.origin);
            parsed.searchParams.set('popup', '1');
            if (contextParam) {
                parsed.searchParams.set('context', contextParam);
            }
            popupUrl = parsed.toString();
        } catch (error) {
            popupUrl = href + (href.indexOf('?') === -1 ? '?popup=1' : '&popup=1');
            if (contextParam) {
                popupUrl += '&context=' + encodeURIComponent(contextParam);
            }
        }

        var width = 520;
        var height = 640;
        var dualScreenLeft = window.screenLeft !== undefined ? window.screenLeft : window.screenX;
        var dualScreenTop = window.screenTop !== undefined ? window.screenTop : window.screenY;
        var screenWidth = window.innerWidth ? window.innerWidth : document.documentElement.clientWidth ? document.documentElement.clientWidth : screen.width;
        var screenHeight = window.innerHeight ? window.innerHeight : document.documentElement.clientHeight ? document.documentElement.clientHeight : screen.height;
        var left = Math.max(dualScreenLeft + (screenWidth - width) / 2, 0);
        var top = Math.max(dualScreenTop + (screenHeight - height) / 2, 0);

        socialPopup = window.open(
            popupUrl,
            'juntaplaySocialLogin',
            'width=' + width + ',height=' + height + ',left=' + left + ',top=' + top + ',status=0,toolbar=0,menubar=0,location=0,resizable=1,scrollbars=1'
        );

        if (socialPopup && !socialPopup.closed) {
            try {
                socialPopup.focus();
            } catch (focusError) {
                // Ignore focus errors in strict browsers.
            }
        } else {
            window.location.href = popupUrl;
        }
    });

    $(window).on('message', function (event) {
        var original = event.originalEvent || event;
        var data = original.data;

        if (typeof data === 'string') {
            try {
                data = JSON.parse(data);
            } catch (parseError) {
                data = null;
            }
        }

        if (!data || typeof data !== 'object' || data.source !== 'juntaplay-social') {
            return;
        }

        if (socialPopup && !socialPopup.closed) {
            try {
                socialPopup.close();
            } catch (closeError) {
                // Ignore close errors.
            }
        }

        socialPopup = null;

        if (data.status === 'success') {
            displaySocialAuthMessage('success', '');
            socialContext = null;

            var redirect = typeof data.redirect === 'string' ? data.redirect : '';
            var $modal = $('[data-auth-modal]');

            if ($modal.length) {
                closeModal($modal);
            }

            if (redirect) {
                window.location.href = redirect;
            } else {
                window.location.reload();
            }

            return;
        }

        if (data.status === 'error' && typeof data.message === 'string' && data.message) {
            var $pane = null;
            var $auth = $('.juntaplay-auth').first();
            var contextValue = '';

            if (typeof data.context === 'string' && data.context) {
                contextValue = data.context;
            } else if (socialContext) {
                contextValue = socialContext;
            }

            if ($auth.length && contextValue) {
                $pane = $auth.find('.juntaplay-auth__pane[data-pane="' + contextValue + '"]').first();
            }

            displaySocialAuthMessage('error', data.message, $pane);
            socialContext = null;
        }
    });

    $(document).on('click', '[data-jp-auth-open]', function (event) {
        var $trigger = $(this);
        var $template = $('#jp-auth-modal-template');
        var $modal = $('[data-auth-modal]');

        event.preventDefault();

        if (!$template.length || !$modal.length) {
            var href = $trigger.attr('href');

            if (href) {
                window.location.href = href;
            }

            return;
        }

        var view = ($trigger.data('jpAuthOpen') || 'login').toString();
        var $content = cloneTemplate('#jp-auth-modal-template');

        if (!$content || !$content.length) {
            return;
        }

        openModal($modal, $content);

        var $auth = $modal.find('.juntaplay-auth').first();
        if ($auth.length && view) {
            activateAuthView($auth, view);
        }
    });

    function closeGuestMenu($menu) {
        if (!$menu || !$menu.length) {
            return;
        }

        $menu.removeClass('is-open');

        var $toggle = $menu.find('[data-guest-menu-toggle]').first();
        if ($toggle.length) {
            $toggle.attr('aria-expanded', 'false');
        }

        var $panel = $menu.find('[data-guest-menu-panel]').first();
        if ($panel.length) {
            $panel.attr('aria-hidden', 'true');
        }
    }

    $(document).on('click', '[data-guest-menu-toggle]', function (event) {
        event.preventDefault();

        var $toggle = $(this);
        var $menu = $toggle.closest('[data-guest-menu]');

        if (!$menu.length) {
            return;
        }

        var isOpen = $menu.hasClass('is-open');

        $('[data-guest-menu].is-open').each(function () {
            closeGuestMenu($(this));
        });

        if (isOpen) {
            return;
        }

        $menu.addClass('is-open');
        $toggle.attr('aria-expanded', 'true');

        var $panel = $menu.find('[data-guest-menu-panel]').first();
        if ($panel.length) {
            $panel.attr('aria-hidden', 'false');
        }
    });

    $(document).on('click', '[data-guest-menu-panel] a, [data-guest-menu-panel] button', function () {
        var $menu = $(this).closest('[data-guest-menu]');
        closeGuestMenu($menu);
    });

    $(document).on('click', function (event) {
        var $target = $(event.target);

        if ($target.closest('[data-guest-menu]').length) {
            return;
        }

        $('[data-guest-menu].is-open').each(function () {
            closeGuestMenu($(this));
        });
    });

    $(function () {
        var $template = $('#jp-auth-modal-template');
        if (!$template.length) {
            return;
        }

        var autoView = ($template.data('autoOpen') || '').toString();
        if (!autoView) {
            return;
        }

        window.requestAnimationFrame(function () {
            var $trigger = $('[data-jp-auth-open="' + autoView + '"]').first();
            if ($trigger.length) {
                $trigger.trigger('click');
                return;
            }

            var $modal = $('[data-auth-modal]');
            if (!$modal.length) {
                return;
            }

            var $content = cloneTemplate('#jp-auth-modal-template');
            if (!$content || !$content.length) {
                return;
            }

            openModal($modal, $content);

            var $auth = $modal.find('.juntaplay-auth').first();
            if ($auth.length) {
                activateAuthView($auth, autoView);
            }
        });
    });

    $(function () {
        $('[data-guest-menu-panel]').attr('aria-hidden', 'true');
    });

    function isProfileMenuMobile() {
        if (window.matchMedia) {
            return window.matchMedia('(max-width: 768px)').matches;
        }

        return window.innerWidth ? window.innerWidth <= 768 : false;
    }

    function closeProfileMenu($profile) {
        if (!$profile || !$profile.length) {
            return;
        }

        $profile.removeClass('is-menu-open');

        var $toggle = $profile.find('[data-profile-menu-toggle]').first();
        var hasToggle = $toggle.length > 0;
        if (hasToggle) {
            $toggle.attr('aria-expanded', 'false');
        }

        var $menu = $profile.find('[data-profile-menu]').first();
        if ($menu.length) {
            if (hasToggle) {
                $menu.attr('aria-hidden', isProfileMenuMobile() ? 'true' : 'false');
            } else {
                $menu.attr('aria-hidden', 'false');
            }
        }

        var $overlay = $profile.find('[data-profile-menu-overlay]').first();
        if ($overlay.length) {
            $overlay.attr('aria-hidden', 'true');
        }
    }

    function activateProfileTab($profile, categoryId, tabId) {
        if (!$profile || !categoryId) {
            return;
        }

        var $panel = $profile.find('[data-profile-category-panel="' + categoryId + '"]');
        if (!$panel.length) {
            return;
        }

        var $tabPanels = $panel.find('[data-profile-tab-panel]');
        var $tabButtons = $panel.find('[data-profile-tab-toggle]');

        if (!tabId && $tabPanels.length) {
            tabId = $tabPanels.first().data('profileTabPanel') || '';
        }

        $tabButtons.each(function () {
            var $btn = $(this);
            var isActive = $btn.data('profileTabToggle') === tabId;
            $btn.toggleClass('is-active', isActive);
            $btn.attr('aria-selected', isActive ? 'true' : 'false');
        });

        $tabPanels.each(function () {
            var $tab = $(this);
            var isActive = $tab.data('profileTabPanel') === tabId;
            $tab.toggleClass('is-active', isActive);
            $tab.attr('aria-hidden', isActive ? 'false' : 'true');
        });
    }

    function activateProfileCategory($profile, categoryId, tabId) {
        if (!$profile || !categoryId) {
            return;
        }

        var $buttons = $profile.find('[data-profile-category-toggle]');
        var $panels = $profile.find('[data-profile-category-panel]');

        $buttons.each(function () {
            var $btn = $(this);
            var isActive = $btn.data('profileCategoryToggle') === categoryId || $btn.attr('data-profile-category-toggle') === categoryId;
            $btn.toggleClass('is-active', isActive);
            $btn.attr('aria-pressed', isActive ? 'true' : 'false');
        });

        $panels.each(function () {
            var $panel = $(this);
            var isActive = $panel.data('profileCategoryPanel') === categoryId;
            $panel.toggleClass('is-active', isActive);
            $panel.attr('aria-hidden', isActive ? 'false' : 'true');
        });

        var $activePanel = $panels.filter('[data-profile-category-panel="' + categoryId + '"]');
        if (!$activePanel.length) {
            return;
        }

        if (!tabId) {
            var $activeButton = $activePanel.find('[data-profile-tab-toggle].is-active').first();
            if ($activeButton.length) {
                tabId = $activeButton.data('profileTabToggle') || '';
            }
        }

        if (!tabId) {
            var $firstPanel = $activePanel.find('[data-profile-tab-panel]').first();
            if ($firstPanel.length) {
                tabId = $firstPanel.data('profileTabPanel') || '';
            }
        }

        activateProfileTab($profile, categoryId, tabId);
    }

    $(document).on('click', '[data-profile-category-toggle]', function (event) {
        event.preventDefault();

        var $btn = $(this);
        var categoryId = $btn.attr('data-profile-category-toggle') || '';
        var $profile = $btn.closest('[data-profile]');

        activateProfileCategory($profile, categoryId);
        closeProfileMenu($profile);
    });

    $(document).on('click', '[data-profile-quick]', function (event) {
        event.preventDefault();

        var $btn = $(this);
        var categoryId = $btn.attr('data-profile-quick-category') || '';
        var tabId = $btn.attr('data-profile-quick-tab') || '';
        var $profile = $btn.closest('[data-profile]');

        if (!categoryId) {
            return;
        }

        activateProfileCategory($profile, categoryId, tabId);
        closeProfileMenu($profile);
    });

    $(document).on('click', '[data-profile-tab-toggle]', function (event) {
        event.preventDefault();

        var $btn = $(this);
        var tabId = $btn.attr('data-profile-tab-toggle') || '';
        var categoryId = $btn.attr('data-profile-tab-category') || '';
        var $profile = $btn.closest('[data-profile]');

        if (!categoryId) {
            var $activeCategory = $profile.find('[data-profile-category-toggle].is-active').first();
            categoryId = $activeCategory.attr('data-profile-category-toggle') || '';
        }

        activateProfileCategory($profile, categoryId, tabId);
        closeProfileMenu($profile);
    });

    $(document).on('click', '[data-profile-menu-toggle]', function (event) {
        event.preventDefault();

        var $toggle = $(this);
        var $profile = $toggle.closest('[data-profile]');

        if (!$profile.length) {
            return;
        }

        var isOpen = $profile.hasClass('is-menu-open');
        $profile.toggleClass('is-menu-open', !isOpen);
        $toggle.attr('aria-expanded', !isOpen ? 'true' : 'false');

        var $menu = $profile.find('[data-profile-menu]').first();
        if ($menu.length) {
            var mobile = isProfileMenuMobile();
            if (!isOpen && mobile) {
                $menu.attr('aria-hidden', 'false');
            } else if (mobile) {
                $menu.attr('aria-hidden', 'true');
            } else {
                $menu.attr('aria-hidden', 'false');
            }
        }

        var $overlay = $profile.find('[data-profile-menu-overlay]').first();
        if ($overlay.length) {
            if (!isOpen && isProfileMenuMobile()) {
                $overlay.attr('aria-hidden', 'false');
            } else {
                $overlay.attr('aria-hidden', 'true');
            }
        }
    });

    $(document).on('click', '[data-profile-menu-close]', function (event) {
        event.preventDefault();

        var $profile = $(this).closest('[data-profile]');
        closeProfileMenu($profile);
    });

    $(document).on('click', '[data-profile-menu-overlay]', function (event) {
        event.preventDefault();

        var $profile = $(this).closest('[data-profile]');
        closeProfileMenu($profile);
    });

    $(document).on('click', '[data-profile-menu-link]', function () {
        var $profile = $(this).closest('[data-profile]');
        closeProfileMenu($profile);
    });

    $(document).on('keydown', function (event) {
        if ((event.key && event.key.toLowerCase() === 'escape') || event.keyCode === 27) {
            var $openGuest = $('[data-guest-menu].is-open').first();
            if ($openGuest.length) {
                closeGuestMenu($openGuest);
                return;
            }

            var $openProfile = $('[data-profile].is-menu-open').first();
            if ($openProfile.length) {
                closeProfileMenu($openProfile);
            }
        }
    });

    $(function () {
        $('[data-profile-menu-overlay]').attr('aria-hidden', 'true');
        $('[data-profile]').each(function () {
            var $profile = $(this);
            var $menu = $profile.find('[data-profile-menu]').first();
            if ($menu.length) {
                var hasToggle = $profile.find('[data-profile-menu-toggle]').length > 0;
                if (hasToggle) {
                    $menu.attr('aria-hidden', isProfileMenuMobile() ? 'true' : 'false');
                } else {
                    $menu.attr('aria-hidden', 'false');
                }
            }
            var $activeCategory = $profile.find('[data-profile-category-toggle].is-active').first();
            var categoryId = $activeCategory.attr('data-profile-category-toggle') || '';

            if (!categoryId && $profile.find('[data-profile-category-toggle]').length) {
                categoryId = $profile.find('[data-profile-category-toggle]').first().attr('data-profile-category-toggle') || '';
            }

            if (!categoryId) {
                return;
            }

            var $activeTab = $profile.find('[data-profile-category-panel="' + categoryId + '"] [data-profile-tab-toggle].is-active').first();
            var tabId = $activeTab.attr('data-profile-tab-toggle') || '';

            activateProfileCategory($profile, categoryId, tabId);
        });

        if (window.matchMedia) {
            var profileMenuMedia = window.matchMedia('(max-width: 768px)');
            var updateProfileMenuState = function (event) {
                var query = event;

                if (!query || typeof query.matches !== 'boolean') {
                    query = profileMenuMedia;
                }

                var isMobileViewport = !!(query && typeof query.matches === 'boolean' && query.matches);

                $('[data-profile]').each(function () {
                    var $profile = $(this);
                    var $menu = $profile.find('[data-profile-menu]').first();
                    var hasToggle = $profile.find('[data-profile-menu-toggle]').length > 0;
                    if ($menu.length) {
                        if (hasToggle) {
                            var shouldHide = isMobileViewport && !$profile.hasClass('is-menu-open');
                            $menu.attr('aria-hidden', shouldHide ? 'true' : 'false');
                        } else {
                            $menu.attr('aria-hidden', 'false');
                        }
                    }
                    var $overlay = $profile.find('[data-profile-menu-overlay]').first();
                    if ($overlay.length) {
                        if (hasToggle) {
                            var overlayHidden = !$profile.hasClass('is-menu-open') || !isMobileViewport;
                            $overlay.attr('aria-hidden', overlayHidden ? 'true' : 'false');
                        } else {
                            $overlay.attr('aria-hidden', 'true');
                        }
                    }
                });
            };

            if (typeof profileMenuMedia.addEventListener === 'function') {
                profileMenuMedia.addEventListener('change', updateProfileMenuState);
            } else if (typeof profileMenuMedia.addListener === 'function') {
                profileMenuMedia.addListener(updateProfileMenuState);
            }

            updateProfileMenuState(profileMenuMedia);
        }
    });

    $(document).on('click', '.juntaplay-profile__edit', function (event) {
        event.preventDefault();

        var $btn = $(this);
        var $row = $btn.closest('.juntaplay-profile__row');
        var $profile = $btn.closest('.juntaplay-profile');
        var isOpen = $row.hasClass('is-editing');

        if (!isOpen) {
            $profile.find('.juntaplay-profile__row').removeClass('is-editing');
            $profile.find('.juntaplay-profile__form').attr('aria-hidden', 'true');
            $profile.find('.juntaplay-profile__edit').attr('aria-expanded', 'false');
        }

        $row.toggleClass('is-editing', !isOpen);
        $row.find('.juntaplay-profile__form').attr('aria-hidden', !isOpen ? 'false' : 'true');
        $btn.attr('aria-expanded', !isOpen ? 'true' : 'false');

        if (!isOpen) {
            var $input = $row.find('.juntaplay-form__input').first();
            if ($input.length) {
                setTimeout(function () {
                    $input.trigger('focus');
                }, 20);
            }
        }
    });

    $(document).on('click', '.juntaplay-profile__cancel', function (event) {
        event.preventDefault();

        var $row = $(this).closest('.juntaplay-profile__row');
        $row.removeClass('is-editing');
        $row.find('.juntaplay-profile__form').attr('aria-hidden', 'true');
        $row.find('.juntaplay-profile__edit').attr('aria-expanded', 'false');
    });

    var jpCepCache = {};

    function jpSanitizeCep(value) {
        return (value || '').toString().replace(/\D+/g, '').slice(0, 8);
    }

    function jpFormatCep(value) {
        var digits = jpSanitizeCep(value);
        if (digits.length === 8) {
            return digits.replace(/(\d{5})(\d{3})/, '$1-$2');
        }

        return value || '';
    }

    function jpClearCepError($input) {
        if ($input.length && $input[0].setCustomValidity) {
            $input[0].setCustomValidity('');
        }

        $input.removeAttr('data-jp-cep-error');
    }

    function jpHandleCepError($input, message) {
        if ($input.length && $input[0].setCustomValidity) {
            $input[0].setCustomValidity(message);
            if (typeof $input[0].reportValidity === 'function') {
                $input[0].reportValidity();
            }
        }

        $input.attr('data-jp-cep-error', '1');
    }

    function jpApplyCepData($input, data) {
        var $form = $input.closest('form');
        if (!$form.length) {
            return;
        }

        var addressParts = [];
        if (data.logradouro) {
            addressParts.push(data.logradouro);
        }
        if (data.complemento) {
            addressParts.push(data.complemento);
        }
        if (data.bairro) {
            addressParts.push(data.bairro);
        }

        var addressValue = addressParts.join(' - ');
        if (addressValue) {
            $form.find('[data-jp-cep-target="address"]').each(function () {
                var $field = $(this);
                $field.val(addressValue).trigger('change');
            });
        }

        if (data.localidade) {
            $form.find('[data-jp-cep-target="city"]').val(data.localidade).trigger('change');
        }

        if (data.uf) {
            $form.find('[data-jp-cep-target="state"]').val(data.uf.toUpperCase()).trigger('change');
        }

        var $country = $form.find('[data-jp-cep-target="country"]').first();
        if ($country.length) {
            var current = ($country.val() || '').toString();
            if (!current || /^(br|brasil|brazil)$/i.test(current)) {
                $country.val('Brasil').trigger('change');
            }
        }

        if (data.cep) {
            $input.val(jpFormatCep(data.cep)).trigger('change');
        }
    }

    function jpFetchCep($input, cep) {
        if (!cep || cep.length !== 8) {
            return;
        }

        if (jpCepCache[cep]) {
            jpClearCepError($input);
            jpApplyCepData($input, jpCepCache[cep]);
            return;
        }

        var loadingKey = $input.data('jpCepLoading');
        if (loadingKey === cep) {
            return;
        }

        $input.data('jpCepLoading', cep);
        $input.attr('aria-busy', 'true');

        fetch('https://viacep.com.br/ws/' + cep + '/json/')
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('network');
                }

                return response.json();
            })
            .then(function (payload) {
                $input.removeAttr('aria-busy');
                $input.removeData('jpCepLoading');

                if (!payload || payload.erro) {
                    jpHandleCepError($input, __('CEP não encontrado. Confira os números digitados.', 'juntaplay'));
                    return;
                }

                jpCepCache[cep] = payload;
                jpClearCepError($input);
                jpApplyCepData($input, payload);
            })
            .catch(function () {
                $input.removeAttr('aria-busy');
                $input.removeData('jpCepLoading');
                jpHandleCepError($input, __('Não foi possível validar o CEP informado.', 'juntaplay'));
            });
    }

    $(document).on('input', '[data-jp-cep-field="postcode"]', function () {
        jpClearCepError($(this));
    });

    $(document).on('blur', '[data-jp-cep-field="postcode"]', function () {
        var $input = $(this);
        var sanitized = jpSanitizeCep($input.val());

        if (sanitized.length === 8) {
            jpFetchCep($input, sanitized);
        }

        $input.val(jpFormatCep(sanitized)).trigger('change');
    });

    $(document).on('click', '[data-network-detail]', function (event) {
        event.preventDefault();

        var $btn = $(this);
        var $card = $btn.closest('.juntaplay-network-card');

        if (!$card.length) {
            return;
        }

        var isExpanded = $card.hasClass('is-expanded');
        var $drawer = $card.find('[data-network-groups]').first();

        $card.toggleClass('is-expanded', !isExpanded);

        if ($drawer.length) {
            if (isExpanded) {
                $drawer.attr('hidden', 'hidden');
            } else {
                $drawer.removeAttr('hidden');
            }
        }

        var defaultLabel = $btn.data('defaultLabel') || $btn.attr('data-default-label') || $btn.text();
        var openLabel = $btn.data('openLabel') || $btn.attr('data-open-label') || defaultLabel;

        $btn.text(isExpanded ? defaultLabel : openLabel);
    });

    function updateComplaintToggle($button, isOpen) {
        var defaultLabel = $button.data('defaultLabel');
        var openLabel = $button.data('openLabel');

        if (!defaultLabel) {
            defaultLabel = $button.text();
            $button.data('defaultLabel', defaultLabel);
        }

        if (!openLabel) {
            openLabel = defaultLabel;
        }

        if (isOpen) {
            $button.addClass('is-active').text(openLabel);
        } else {
            $button.removeClass('is-active').text(defaultLabel);
        }
    }

    $(document).on('click', '[data-group-complaint-toggle]', function (event) {
        event.preventDefault();

        var $button = $(this);
        var target = $button.attr('data-target') || '';
        var $form = target ? $('#' + target) : $button.closest('[data-group-complaint]').find('[data-group-complaint-form]');

        if (!$form.length) {
            return;
        }

        var willOpen = $form.hasClass('is-hidden');
        $form.toggleClass('is-hidden', !willOpen).toggleClass('is-open', willOpen);
        $button.attr('aria-expanded', willOpen ? 'true' : 'false');
        updateComplaintToggle($button, willOpen);
    });

    $(document).on('click', '[data-group-complaint-close]', function (event) {
        event.preventDefault();

        var $form = $(this).closest('[data-group-complaint-form]');

        if (!$form.length) {
            return;
        }

        $form.removeClass('is-open').addClass('is-hidden');

        var formId = $form.attr('id');
        if (!formId) {
            return;
        }

        var $toggle = $('[data-group-complaint-toggle][data-target="' + formId + '"]');
        if ($toggle.length) {
            $toggle.attr('aria-expanded', 'false');
            updateComplaintToggle($toggle, false);
        }
    });

    $(document).on('change', '[data-group-complaint-files]', function () {
        var $input = $(this);
        var files = this.files;
        var $preview = $input.closest('[data-group-complaint]').find('[data-group-complaint-preview]');

        if (!$preview.length) {
            return;
        }

        $preview.empty();

        if (!files || !files.length) {
            return;
        }

        Array.prototype.forEach.call(files, function (file) {
            var size = file.size || 0;
            var label = file.name;

            if (size > 0) {
                label += ' (' + formatBytes(size) + ')';
            }

            $('<li />').text(label).appendTo($preview);
        });
    });

    function formatBytes(bytes) {
        if (!bytes || bytes <= 0) {
            return '';
        }

        var units = ['B', 'KB', 'MB', 'GB'];
        var index = 0;
        var value = bytes;

        while (value >= 1024 && index < units.length - 1) {
            value /= 1024;
            index += 1;
        }

        return value.toFixed(index === 0 ? 0 : 1) + ' ' + units[index];
    }

    (function () {
        var $centers = $('[data-complaint-center]');

        if (!$centers.length) {
            return;
        }

        function isDesktop() {
            return window.matchMedia('(min-width: 1024px)').matches;
        }

        function applyState() {
            $centers.each(function () {
                var $center = $(this);
                var openAttr = ($center.attr('data-complaint-open') || '').toString().toLowerCase();
                var shouldOpen = openAttr === 'true';

                if (isDesktop()) {
                    $center.addClass('is-detail-open');
                } else {
                    $center.toggleClass('is-detail-open', shouldOpen);
                }
            });
        }

        function setDrawer($center, open) {
            if (!$center || !$center.length) {
                return;
            }

            $center.attr('data-complaint-open', open ? 'true' : 'false');

            if (isDesktop()) {
                $center.addClass('is-detail-open');
            } else {
                $center.toggleClass('is-detail-open', !!open);
            }
        }

        applyState();

        $(window).on('resize.juntaplayComplaints', function () {
            applyState();
        });

        $(document).on('click', '[data-complaint-drawer-toggle]', function (event) {
            event.preventDefault();

            var $center = $(this).closest('[data-complaint-center]');
            if (!$center.length) {
                return;
            }

            var openAttr = ($center.attr('data-complaint-open') || '').toString().toLowerCase();
            var isOpen = openAttr === 'true';

            setDrawer($center, !isOpen);
        });

        $(document).on('click', '[data-complaint-submit]', function () {
            var action = ($(this).attr('data-complaint-submit') || '').toString();
            if (!action) {
                return;
            }

            var $form = $(this).closest('form');
            if (!$form.length) {
                return;
            }

            var $input = $form.find('[data-complaint-action]');
            if ($input.length) {
                $input.val(action);
            }
        });
    })();

    function renderGroupCard(item, variant) {
        var mode = (variant || 'spotlight').toString();
        var classes = ['juntaplay-group-card'];

        var iconUrl = item.iconUrl || '';
        var iconInitial = (item.iconInitial || '').toString();
        var avatarClasses = ['juntaplay-group-card__avatar'];
        if (iconUrl) {
            avatarClasses.push('has-image');
        }
        var avatar = '<span class="' + avatarClasses.join(' ') + '"' + (iconUrl ? ' style="background-image: url(' + escapeHtml(iconUrl) + ')"' : '') + ' aria-hidden="true">' + (!iconUrl && iconInitial ? escapeHtml(iconInitial) : '') + '</span>';
        var media = '<div class="juntaplay-group-card__media juntaplay-group-card__media--icon">' + avatar + '</div>';

        if (mode === 'spotlight') {
            classes.push('juntaplay-group-card--spotlight');
        } else if (mode === 'compact') {
            classes.push('juntaplay-group-card--compact');
        }

        var groupId = parseInt(item.id, 10) || 0;
        var articleIdAttr = groupId ? ' id="jp-group-card-' + groupId + '"' : '';
        var titleText = item.title || item.service || __('Grupo disponível', 'juntaplay');
        var displayTitle = truncate(titleText, mode === 'compact' ? 28 : 32);
        var titleAttr = titleText ? ' title="' + escapeHtml(titleText) + '"' : '';
        var titleHeading = '<h3 class="juntaplay-group-card__title"' + titleAttr + '>' + escapeHtml(displayTitle) + '</h3>';
        var priceValue = (typeof item.memberPrice === 'number' ? item.memberPrice : (typeof item.price === 'number' ? item.price : null));
        var priceLabel = priceValue !== null ? formatCurrency(priceValue, 'BRL', 'pt-BR') : (item.memberPriceLabel || item.priceLabel || '');
        var price = priceLabel ? '<span class="juntaplay-group-card__price">' + escapeHtml(priceLabel) + '</span>' : '';
        var badgeVariant = (item.slotsBadgeVariant || '').toString();
        var slotsBadgeClass = 'juntaplay-group-card__slots-badge';
        if (badgeVariant && badgeVariant !== 'default') {
            var safeVariant = badgeVariant.replace(/[^a-z0-9_-]/gi, '');
            if (safeVariant) {
                slotsBadgeClass += ' is-' + safeVariant;
            }
        }
        var slotsBadge = item.slotsBadge ? '<span class="' + slotsBadgeClass + '">' + escapeHtml(item.slotsBadge) + '</span>' : '';
        var availabilityState = (item.availabilityState || '').toString();
        var buttonLabel = item.buttonLabel || (availabilityState === 'full' ? __('Aguardando membros', 'juntaplay') : __('Confira', 'juntaplay'));
        var link = item.permalink ? escapeHtml(item.permalink) : '#';
        var detailAttributes = ' data-jp-group-open';
        var articleAttributes = ' data-group-card';
        if (groupId) {
            detailAttributes += ' data-group-id="' + groupId + '"';
            articleAttributes += ' data-group-id="' + groupId + '"';
        }

        var ctaUrl = item.ctaUrl || item.permalink || item.poolLink || '';
        var ctaDisabled = !!item.ctaDisabled;
        if (!ctaUrl) {
            ctaUrl = link;
        }

        if (mode === 'compact') {
            var categoryLabel = item.categoryLabel || '';
            var category = categoryLabel ? '<span class="juntaplay-group-card__category">' + escapeHtml(categoryLabel) + '</span>' : '';
            var serviceLabel = item.service || '';
            var subtitle = '';
            if (serviceLabel && serviceLabel !== categoryLabel) {
                subtitle = '<span class="juntaplay-group-card__service">' + escapeHtml(serviceLabel) + '</span>';
            }

            var metaParts = [];
            if (slotsBadge) {
                metaParts.push(slotsBadge);
            }
            if (price) {
                metaParts.push(price);
            }
            var meta = metaParts.length ? '<div class="juntaplay-group-card__meta">' + metaParts.join('') + '</div>' : '';
            var arrow = '<span class="juntaplay-group-card__cta-icon" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"></path></svg></span>';
            var cta = '<span class="juntaplay-group-card__cta-text">' + escapeHtml(buttonLabel || __('Ver detalhes', 'juntaplay')) + arrow + '</span>';

            return '<article class="' + classes.join(' ') + '"' + articleIdAttr + articleAttributes + '>'
                + '<a class="juntaplay-group-card__link" href="' + link + '"' + detailAttributes + '>'
                + media
                + '<div class="juntaplay-group-card__body">'
                + category
                + titleHeading
                + subtitle
                + meta
                + cta
                + '</div>'
                + '</a>'
                + '</article>';
        }

        var buttonClass = 'juntaplay-group-card__cta';
        if (availabilityState === 'full') {
            buttonClass += ' is-disabled';
        }
        if (ctaDisabled && buttonClass.indexOf('is-disabled') === -1) {
            buttonClass += ' is-disabled';
        }

        var meta = '';
        if (slotsBadge || price) {
            meta = '<div class="juntaplay-group-card__meta">' + slotsBadge + price + '</div>';
        }

        var ctaAttributes = ' class="' + buttonClass + '"';
        if (ctaDisabled) {
            ctaAttributes += ' aria-disabled="true"';
        } else {
            ctaAttributes += detailAttributes;
        }

        return '<article class="' + classes.join(' ') + '"' + articleIdAttr + articleAttributes + '>'
            + media
            + '<div class="juntaplay-group-card__body">'
            + '<div class="juntaplay-group-card__heading">'
            + '<h3 class="juntaplay-group-card__title"' + titleAttr + '>'
            + '<a href="' + link + '"' + detailAttributes + '>' + escapeHtml(displayTitle) + '</a>'
            + '</h3>'
            + '</div>'
            + meta
            + '<a' + ctaAttributes + ' href="' + escapeHtml(ctaUrl) + '">' + escapeHtml(buttonLabel) + '</a>'
            + '</div>'
            + '</article>';
    }
    function initGroupCoverPicker($wrapper) {
        if (!$wrapper.length) {
            return;
        }

        if ($wrapper.data('jpCoverPickerReady')) {
            return;
        }

        $wrapper.data('jpCoverPickerReady', true);
        $wrapper.attr('data-group-cover-ready', '1');

        var frame;
        var placeholder = ($wrapper.data('placeholder') || '').toString();
        var authorId = parseInt($wrapper.data('mediaAuthor'), 10) || 0;
        if (!authorId && window.JuntaPlay && window.JuntaPlay.currentUser && window.JuntaPlay.currentUser.id) {
            authorId = parseInt(window.JuntaPlay.currentUser.id, 10) || 0;
        }
        var uploadContext = ($wrapper.data('uploadContext') || '').toString();
        if (!uploadContext) {
            uploadContext = 'profile-group-cover';
        }
        var defaultUploaderParams = null;
        var defaultMediaPostParams = null;
        var $input = $wrapper.find('[data-group-cover-input]');
        var $preview = $wrapper.find('[data-group-cover-preview]');
        var $remove = $wrapper.find('[data-group-cover-remove]');

        function hasMediaLibrary() {
            return typeof wp !== 'undefined' && wp.media && typeof wp.media === 'function';
        }

        function cleanupGlobalUploader() {
            if (typeof wp !== 'undefined' && wp.Uploader && wp.Uploader.defaults && defaultUploaderParams) {
                wp.Uploader.defaults.multipart_params = defaultUploaderParams;
            }
            defaultUploaderParams = null;

            if (typeof wp !== 'undefined' && wp.media && wp.media.model && wp.media.model.settings && defaultMediaPostParams !== null) {
                wp.media.model.settings.post = defaultMediaPostParams;
            }
            defaultMediaPostParams = null;
        }

        function applyAuthorRestriction(targetFrame) {
            if (!targetFrame || !authorId || typeof targetFrame.state !== 'function') {
                return;
            }

            var state = targetFrame.state();
            if (!state || typeof state.get !== 'function') {
                return;
            }

            var library = state.get('library');
            if (!library || !library.props || typeof library.props.set !== 'function') {
                return;
            }

            library.props.set('author', authorId);
            library.props.set('author__in', [authorId]);
            if (typeof library.props.unset === 'function') {
                library.props.unset('author__not_in');
            }
            library.props.set('uploadedTo', null);
            library.props.set('juntaplay_group_cover', uploadContext);

        }

        function applyUploadContext(targetFrame) {
            if (!targetFrame || !targetFrame.uploader) {
                return;
            }

            var uploaderView = targetFrame.uploader;

            if (typeof wp !== 'undefined' && wp.Uploader && wp.Uploader.defaults) {
                if (defaultUploaderParams === null) {
                    defaultUploaderParams = $.extend({}, wp.Uploader.defaults.multipart_params || {});
                }

                wp.Uploader.defaults.multipart_params = $.extend({}, wp.Uploader.defaults.multipart_params || {}, {
                    juntaplay_group_cover: uploadContext
                });

                if (authorId) {
                    wp.Uploader.defaults.multipart_params.author = authorId;
                }
            }

            if (typeof wp !== 'undefined'
                && wp.media
                && wp.media.model
                && wp.media.model.settings
            ) {
                var currentPostSettings = wp.media.model.settings.post || {};
                if (defaultMediaPostParams === null) {
                    defaultMediaPostParams = $.extend({}, currentPostSettings);
                }

                wp.media.model.settings.post = $.extend({}, currentPostSettings, {
                    juntaplay_group_cover: uploadContext
                });

                if (authorId) {
                    wp.media.model.settings.post.author = authorId;
                }
            }

            if (uploaderView.options && uploaderView.options.uploader) {
                uploaderView.options.uploader.params = uploaderView.options.uploader.params || {};
                uploaderView.options.uploader.params.juntaplay_group_cover = uploadContext;

                if (authorId) {
                    uploaderView.options.uploader.params.author = authorId;
                }
            }

            if (uploaderView.uploader && typeof uploaderView.uploader.param === 'function') {
                uploaderView.uploader.param('juntaplay_group_cover', uploadContext);

                if (authorId) {
                    uploaderView.uploader.param('author', authorId);
                }
            }

            if (typeof targetFrame.state === 'function') {
                var state = targetFrame.state();
                if (state && typeof state.get === 'function') {
                    var library = state.get('library');
                    if (library && library.props && typeof library.props.set === 'function') {
                        library.props.set('juntaplay_group_cover', uploadContext);
                        if (authorId) {
                            library.props.set('author', authorId);
                            library.props.set('author__in', [authorId]);
                            if (typeof library.props.unset === 'function') {
                                library.props.unset('author__not_in');
                            }
                        }
                    }
                }
            }
        }

        function enableSelectButton(targetFrame) {
            if (!targetFrame || !targetFrame.toolbar || typeof targetFrame.toolbar.get !== 'function') {
                return;
            }

            var toolbarView = targetFrame.toolbar.get('select');

            if (!toolbarView) {
                return;
            }

            if (toolbarView.model && typeof toolbarView.model.set === 'function') {
                toolbarView.model.set('disabled', false);
            }

            if (toolbarView.$el) {
                toolbarView.$el.find('.media-button-select').prop('disabled', false);
            }
        }

        function resolveAttachmentModel(file, callback) {
            if (typeof callback !== 'function') {
                return;
            }

            var attachmentId = null;
            var attachmentModel = null;

            if (file && typeof file.get === 'function') {
                attachmentId = file.get('id') || file.id || null;
                attachmentModel = file;
            } else if (file && typeof file === 'object' && file !== null) {
                attachmentId = typeof file.id !== 'undefined' ? file.id : null;
            }

            if (attachmentId !== null
                && (typeof wp !== 'undefined')
                && wp.media
                && wp.media.model
                && wp.media.model.Attachment
                && typeof wp.media.model.Attachment.get === 'function'
            ) {
                attachmentModel = wp.media.model.Attachment.get(attachmentId);
            }

            if (attachmentModel && typeof attachmentModel.once === 'function' && (!attachmentModel.get || !attachmentModel.get('url'))) {
                attachmentModel.once('sync', function () {
                    callback(attachmentModel, attachmentId);
                });
                if (typeof attachmentModel.fetch === 'function') {
                    attachmentModel.fetch();
                }
                return;
            }

            callback(attachmentModel || null, attachmentId);
        }

        function refreshFrameLibrary(targetFrame, focusAttachment) {
            if (!targetFrame) {
                return;
            }

            var targetState = typeof targetFrame.state === 'function' ? targetFrame.state() : null;
            if (!targetState || typeof targetState.get !== 'function') {
                return;
            }

            var targetLibrary = targetState.get('library');
            var targetSelection = targetState.get('selection');
            var contentView = (targetFrame.content && typeof targetFrame.content.get === 'function')
                ? targetFrame.content.get()
                : null;
            var browserCollection = contentView && contentView.collection ? contentView.collection : null;

            var focusModel = null;
            var focusId = null;

            if (focusAttachment) {
                if (typeof focusAttachment.get === 'function') {
                    focusModel = focusAttachment;
                    focusId = focusAttachment.get('id') || focusAttachment.id || null;
                } else if (typeof focusAttachment === 'object' && focusAttachment !== null) {
                    focusId = typeof focusAttachment.id !== 'undefined' ? focusAttachment.id : null;
                } else if (typeof focusAttachment === 'number' || typeof focusAttachment === 'string') {
                    focusId = focusAttachment;
                }
            }

            function refreshCollection(collection) {
                if (!collection) {
                    return;
                }

                if (collection.props && typeof collection.props.set === 'function') {
                    collection.props.set('ignore', Date.now());
                    if (uploadContext) {
                        collection.props.set('juntaplay_group_cover', uploadContext);
                    }
                }

                if (typeof collection.fetch === 'function') {
                    collection.fetch();
                } else if (typeof collection.more === 'function') {
                    collection.more({ reset: true });
                } else if (typeof collection._requery === 'function') {
                    collection._requery(true);
                }
            }

            refreshCollection(targetLibrary);
            if (browserCollection && browserCollection !== targetLibrary) {
                refreshCollection(browserCollection);
            }

            if (!focusModel && focusId !== null) {
                if (targetLibrary && typeof targetLibrary.get === 'function') {
                    focusModel = targetLibrary.get(focusId) || focusModel;
                }
                if ((!focusModel || !focusModel.get || !focusModel.get('id'))
                    && browserCollection
                    && typeof browserCollection.get === 'function'
                ) {
                    focusModel = browserCollection.get(focusId) || focusModel;
                }
            }

            if (!focusModel) {
                if (targetLibrary && typeof targetLibrary.last === 'function') {
                    focusModel = targetLibrary.last();
                }
                if ((!focusModel || !focusModel.get || !focusModel.get('id'))
                    && browserCollection
                    && typeof browserCollection.last === 'function'
                ) {
                    focusModel = browserCollection.last();
                }
                if (!focusModel && targetLibrary && typeof targetLibrary.at === 'function') {
                    focusModel = targetLibrary.at(0);
                }
                if (!focusModel && browserCollection && typeof browserCollection.at === 'function') {
                    focusModel = browserCollection.at(0);
                }
            }

            if (
                focusModel
                && targetSelection
                && typeof targetSelection.reset === 'function'
            ) {
                targetSelection.reset([focusModel]);
            }

            enableSelectButton(targetFrame);
        }

        function bindGlobalUploaderEvents(targetFrame) {
            if (!targetFrame || typeof wp === 'undefined' || !wp.media || !wp.media.events) {
                return;
            }

            if (
                targetFrame.jpUploaderCompleteHandler
                && typeof wp.media.events.off === 'function'
            ) {
                wp.media.events.off('uploader:complete', targetFrame.jpUploaderCompleteHandler);
            }

            targetFrame.jpUploaderCompleteHandler = function () {
                var args = Array.prototype.slice.call(arguments || []);
                var candidate = null;

                for (var i = 0; i < args.length; i += 1) {
                    var value = args[i];
                    if (!value) {
                        continue;
                    }

                    if (typeof value.get === 'function') {
                        candidate = value;
                        break;
                    }

                    if (typeof value === 'object' && typeof value.id !== 'undefined') {
                        candidate = value;
                        break;
                    }
                }

                refreshFrameLibrary(targetFrame, candidate);
            };

            if (typeof wp.media.events.on === 'function') {
                wp.media.events.on('uploader:complete', targetFrame.jpUploaderCompleteHandler);
            }
        }

        function ensureFrame() {
            if (!hasMediaLibrary()) {
                return null;
            }

            if (frame && typeof frame.open === 'function') {
                return frame;
            }

            var mediaSettings = {
                title: __('Escolher capa do grupo', 'juntaplay'),
                button: { text: __('Usar esta imagem', 'juntaplay') },
                multiple: false,
                library: {
                    type: 'image'
                },
                uploader: {
                    params: {
                        juntaplay_group_cover: uploadContext
                    }
                }
            };

            if (authorId) {
                mediaSettings.library.author = authorId;
                mediaSettings.uploader.params.author = authorId;
            }

            frame = wp.media(mediaSettings);
            bindGlobalUploaderEvents(frame);

            var maintainContext = function () {
                applyUploadContext(frame);
                if (authorId) {
                    applyAuthorRestriction(frame);
                }
            };

            frame.on('ready', maintainContext);
            frame.on('open', maintainContext);

            if (authorId) {
                frame.on('library:rendered', function () {
                    applyAuthorRestriction(frame);
                });
                frame.on('content:render:browse', function () {
                    applyAuthorRestriction(frame);
                });
            }

            frame.on('content:render:upload', function () {
                applyUploadContext(frame);
            });

            frame.on('upload:complete', function (file) {
                window.setTimeout(function () {
                    if (!frame || typeof frame.state !== 'function') {
                        return;
                    }

                    var state = frame.state();
                    if (!state || typeof state.get !== 'function') {
                        return;
                    }

                    var selection = state.get('selection');
                    var library = state.get('library');

                    resolveAttachmentModel(file, function (attachmentModel, attachmentId) {
                        if (!attachmentModel && attachmentId !== null && library && typeof library.get === 'function') {
                            attachmentModel = library.get(attachmentId) || attachmentModel;
                        }

                        if (attachmentModel && library && typeof library.add === 'function') {
                            var exists = false;
                            if (typeof library.get === 'function') {
                                exists = !!(library.get(attachmentModel) || (attachmentId !== null && library.get(attachmentId)));
                            }

                            if (!exists) {
                                library.add(attachmentModel);
                            }
                        }

                        if (
                            selection
                            && attachmentModel
                            && typeof selection.reset === 'function'
                        ) {
                            selection.reset([attachmentModel]);
                        }

                        if (attachmentModel && typeof attachmentModel.toJSON === 'function') {
                            var data = attachmentModel.toJSON();
                            if (data && typeof data.id !== 'undefined') {
                                var coverUrl = data.url || (data.sizes && data.sizes.full && data.sizes.full.url) || placeholder || coverPlaceholder || '';
                                setCover(data.id, coverUrl);
                            }
                        }

                        refreshFrameLibrary(frame, attachmentModel || attachmentId);
                    });
                }, 120);
            });

            frame.on('upload:success', function () {
                enableSelectButton(frame);
            });

            frame.on('close', function () {
                cleanupGlobalUploader();
                if (
                    typeof wp !== 'undefined'
                    && wp.media
                    && wp.media.events
                    && typeof wp.media.events.off === 'function'
                    && frame.jpUploaderCompleteHandler
                ) {
                    wp.media.events.off('uploader:complete', frame.jpUploaderCompleteHandler);
                }
            });

            frame.on('select', function () {
                var attachment = frame.state().get('selection').first();
                if (!attachment) {
                    return;
                }

                attachment = attachment.toJSON();
                setCover(attachment.id || '', attachment.url || placeholder || coverPlaceholder || '');
            });

            maintainContext();

            return frame;
        }

        function setCover(id, url) {
            var value = id ? id.toString() : '';
            var source = url || placeholder || coverPlaceholder;

            $input.val(value);

            if ($preview.length) {
                $preview.css('background-image', source ? 'url(' + source + ')' : 'none');
                var $img = $preview.find('img');
                if ($img.length) {
                    $img.attr('src', source || coverPlaceholder || '');
                }
            }

            if ($remove.length) {
                $remove.prop('disabled', !value);
            }
        }

        $wrapper.on('click', '[data-group-cover-select]', function (event) {
            event.preventDefault();

            var mediaFrame = ensureFrame();
            if (!mediaFrame) {
                window.alert(__('Não foi possível carregar a biblioteca de mídia agora.', 'juntaplay'));
                return;
            }

            applyUploadContext(mediaFrame);
            mediaFrame.open();
        });

        $wrapper.on('click', '[data-group-cover-remove]', function (event) {
            event.preventDefault();
            setCover('', '');
        });
    }

    function cloneTemplate(selector) {
        if (!selector) {
            return null;
        }

        var node = document.querySelector(selector);
        if (!node) {
            return null;
        }

        var tag = node.tagName ? node.tagName.toLowerCase() : '';

        if (tag === 'template') {
            var fragment = node.content ? node.content.cloneNode(true) : null;
            if (!fragment) {
                return null;
            }

            var container = document.createElement('div');
            container.appendChild(fragment);
            return $(container).children();
        }

        return $(node).clone(true);
    }

    function openModal($modal, $content) {
        if (!$modal.length || !$content || !$content.length) {
            return;
        }

        var $container = $modal.find('[data-modal-content]');
        $container.empty().append($content);

        $modal.removeAttr('hidden').addClass('is-open').attr('aria-hidden', 'false');
        $('body').addClass('juntaplay-modal-open');

        setTimeout(function () {
            var $focusable = $container.find('input, select, textarea, button, a[href]').filter(':visible').first();
            if ($focusable.length) {
                $focusable.trigger('focus');
            }
        }, 30);
    }

    function closeModal($modal) {
        if (!$modal.length) {
            return;
        }

        $modal.removeClass('is-open').attr('aria-hidden', 'true').attr('hidden', 'hidden');
        $modal.find('[data-modal-content]').empty();

        if (!$('.juntaplay-modal.is-open').length) {
            $('body').removeClass('juntaplay-modal-open');
        }
    }

    function buildShareTarget(network, url, text) {
        var shareUrl = (url || '').toString();
        if (!shareUrl) {
            return '';
        }

        var encodedUrl = encodeURIComponent(shareUrl);
        var encodedText = encodeURIComponent((text || '').toString());
        switch ((network || '').toString().toLowerCase()) {
            case 'whatsapp':
                return 'https://wa.me/?text=' + encodedText + (encodedText ? '%20' : '') + encodedUrl;
            case 'telegram':
                return 'https://t.me/share/url?url=' + encodedUrl + '&text=' + encodedText;
            case 'facebook':
                return 'https://www.facebook.com/sharer/sharer.php?u=' + encodedUrl;
            case 'twitter':
            case 'x':
                return 'https://twitter.com/intent/tweet?url=' + encodedUrl + '&text=' + encodedText;
            default:
                return '';
        }
    }

    function openShareWindow(targetUrl) {
        if (!targetUrl) {
            return;
        }

        var width = 640;
        var height = 520;
        var left = window.screenX + Math.max(0, (window.outerWidth - width) / 2);
        var top = window.screenY + Math.max(0, (window.outerHeight - height) / 2);

        window.open(targetUrl, '_blank', 'noopener,noreferrer,width=' + width + ',height=' + height + ',left=' + left + ',top=' + top);
    }

    function copyShareUrl(url) {
        var shareUrl = (url || '').toString();
        if (!shareUrl) {
            return Promise.reject();
        }

        if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
            return navigator.clipboard.writeText(shareUrl);
        }

        return new Promise(function (resolve, reject) {
            try {
                var textarea = document.createElement('textarea');
                textarea.value = shareUrl;
                textarea.setAttribute('readonly', 'readonly');
                textarea.style.position = 'fixed';
                textarea.style.opacity = '0';
                document.body.appendChild(textarea);
                textarea.select();
                var succeeded = document.execCommand('copy');
                document.body.removeChild(textarea);
                if (succeeded) {
                    resolve();
                } else {
                    reject();
                }
            } catch (err) {
                reject(err);
            }
        });
    }

    function openGroupDetail(selector) {
        if (!selector) {
            return;
        }

        var $modal = $('[data-group-modal]');
        if (!$modal.length) {
            return;
        }

        var $content = cloneTemplate(selector);
        if (!$content || !$content.length) {
            return;
        }

        openModal($modal, $content);
    }

    function openGroupCreate() {
        var templateSelector = '#jp-group-create-template';
        var $template = $(templateSelector);
        if (!$template.length) {
            return;
        }

        var $modal = $('[data-group-create-modal]');
        if (!$modal.length) {
            return;
        }

        var loadingText = $template.data('loadingText');
        if (typeof loadingText !== 'string' || !loadingText.length) {
            loadingText = 'Carregando formulário...';
        }

        var $loading = $('<div class="juntaplay-modal__loading" role="status" aria-live="polite"></div>');
        $loading.append('<span class="juntaplay-spinner" aria-hidden="true"></span>');
        $loading.append($('<p></p>').text(loadingText));

        openModal($modal, $loading);

        var $container = $modal.find('[data-modal-content]');

            window.requestAnimationFrame(function () {
                var $content = cloneTemplate(templateSelector);
                if (!$content || !$content.length) {
                    return;
                }

                $container.empty().append($content);
                var $viewRoot = $content.find('[data-group-view-root]').first();
                if ($viewRoot.length) {
                    var fallbackView = ($viewRoot.data('groupViewDefault') || '').toString() || 'selector';
                    var initialView = ($viewRoot.data('groupViewActive') || $viewRoot.attr('data-group-view-active') || '').toString();
                    var initialAllowed = ($viewRoot.attr('data-group-view-allowed') || '').toString();

                    if (!initialView) {
                        $viewRoot.attr('data-group-view-active', fallbackView);
                    }

                    if (!initialAllowed) {
                        $viewRoot.attr('data-group-view-allowed', '0');
                        $viewRoot.data('groupViewAllowed', '0');
                    }
                }
                initGroupCoverPicker($modal.find('[data-group-cover]'));
                initGroupWizard($modal.find('form[data-group-wizard]'));
                initGroupViewRoot($modal.find('[data-group-view-root]'));
                resetGroupViewRoot($modal.find('[data-group-view-root]'));
                $template.attr('data-auto-open', '0');

            setTimeout(function () {
                var $focusTarget = $modal.find('input, select, textarea').filter(':visible').first();
                if ($focusTarget.length) {
                    $focusTarget.trigger('focus');
                }
            }, 40);
        });
    }

    function initGroupsDirectory($root) {
        if (!$root.length || typeof window.JuntaPlay === 'undefined') {
            return;
        }

        var state = {
            page: 1,
            currentPage: 1,
            totalPages: 1,
            perPage: parseInt($root.data('perPage'), 10) || 16,
            loading: false,
            orderby: 'created',
            order: 'desc',
            category: '',
            search: '',
            instant: ''
        };

        var defaultSearch = $root.data('defaultSearch');
        var defaultCategory = $root.data('defaultCategory');
        var defaultOrderby = $root.data('defaultOrderby');
        var defaultOrder = $root.data('defaultOrder');
        var defaultInstant = $root.data('defaultInstant');

        if (typeof defaultSearch === 'string' && defaultSearch.length) {
            state.search = defaultSearch;
        }

        if (typeof defaultCategory === 'string' && defaultCategory.length) {
            state.category = defaultCategory;
        }

        if (typeof defaultOrderby === 'string' && defaultOrderby.length) {
            state.orderby = defaultOrderby;
        }

        if (typeof defaultOrder === 'string' && defaultOrder.length) {
            state.order = defaultOrder;
        }

        if (typeof defaultInstant === 'string' && defaultInstant.length) {
            state.instant = defaultInstant;
        }

        var cardVariant = ($root.data('cardVariant') || 'spotlight').toString();
        if (cardVariant !== 'compact') {
            cardVariant = 'spotlight';
        }

        var $list = $root.find('[data-jp-groups-list]');
        var $empty = $root.find('[data-jp-groups-empty]');
        var $total = $root.find('[data-jp-groups-total]');
        var $filters = $root.find('[data-jp-groups-filters]');
        var $pagination = $root.find('[data-jp-groups-pagination]');

        function setLoading(isLoading) {
            state.loading = isLoading;
            $root.toggleClass('is-loading', isLoading);
        }

        function updateTotal(total) {
            if (!$total.length) {
                return;
            }

            total = parseInt(total, 10) || 0;
            $total.text(total ? _n('%d grupo encontrado', '%d grupos encontrados', total, 'juntaplay').replace('%d', total) : __('Nenhum grupo encontrado', 'juntaplay'));
        }

        function render(items) {
            $list.empty();

            if (items && items.length) {
                var html = items.map(function (item) {
                    return renderGroupCard(item, cardVariant);
                }).join('');
                $list.html(html);
                $empty.attr('hidden', 'hidden');
            } else {
                $empty.removeAttr('hidden');
            }
        }

        function buildPageList(current, total) {
            var pages = [];
            var start = Math.max(1, current - 2);
            var end = Math.min(total, current + 2);

            if (start > 1) {
                pages.push(1);
                if (start > 2) {
                    pages.push('ellipsis');
                }
            }

            for (var page = start; page <= end; page += 1) {
                pages.push(page);
            }

            if (end < total) {
                if (end < total - 1) {
                    pages.push('ellipsis');
                }
                pages.push(total);
            }

            return pages;
        }

        function renderPagination() {
            if (!$pagination.length) {
                return;
            }

            var total = state.totalPages;
            var current = state.currentPage;

            if (total <= 1) {
                $pagination.empty().attr('hidden', 'hidden');
                return;
            }

            var pages = buildPageList(current, total);
            var prevDisabled = current <= 1;
            var nextDisabled = current >= total;
            var parts = [];

            parts.push('<button type="button" class="juntaplay-pagination__nav' + (prevDisabled ? ' is-disabled' : '') + '" data-page="' + (current - 1) + '"' + (prevDisabled ? ' aria-disabled="true" disabled' : '') + '>' + __('Anterior', 'juntaplay') + '</button>');

            pages.forEach(function (page) {
                if (page === 'ellipsis') {
                    parts.push('<span class="juntaplay-pagination__ellipsis" aria-hidden="true">…</span>');
                    return;
                }

                var isActive = page === current;
                parts.push('<button type="button" class="juntaplay-pagination__page' + (isActive ? ' is-active' : '') + '" data-page="' + page + '"' + (isActive ? ' aria-current="page"' : '') + '>' + page + '</button>');
            });

            parts.push('<button type="button" class="juntaplay-pagination__nav' + (nextDisabled ? ' is-disabled' : '') + '" data-page="' + (current + 1) + '"' + (nextDisabled ? ' aria-disabled="true" disabled' : '') + '>' + __('Próxima', 'juntaplay') + '</button>');

            $pagination.html(parts.join('')).removeAttr('hidden');
        }

        function fetch(page) {
            if (state.loading) {
                return;
            }

            if (typeof page === 'number' && !Number.isNaN(page)) {
                state.page = Math.max(1, page);
            }

            setLoading(true);

            $.getJSON(window.JuntaPlay.ajax, {
                action: 'juntaplay_groups_directory',
                nonce: window.JuntaPlay.nonce,
                page: state.page,
                per_page: state.perPage,
                search: state.search,
                category: state.category,
                orderby: state.orderby,
                order: state.order,
                instant_access: state.instant
            }).done(function (response) {
                if (!response || !response.success || !response.data) {
                    return;
                }

                var data = response.data;
                var currentPage = parseInt(data.page, 10) || state.page;
                var totalPages = parseInt(data.pages, 10) || 1;

                state.currentPage = currentPage;
                state.totalPages = totalPages;

                render(data.items || []);
                updateTotal(data.total || 0);
                renderPagination();
            }).fail(function () {
                $list.empty();
                $empty.removeAttr('hidden').text(__('Não foi possível carregar os grupos agora.', 'juntaplay'));
                state.totalPages = 1;
                renderPagination();
            }).always(function () {
                setLoading(false);
            });
        }

        if ($filters.length) {
            if (state.search) {
                $filters.find('input[name="search"]').val(state.search);
            }

            if (state.category) {
                $filters.find('select[name="category"]').val(state.category);
            }

            if (state.instant === '1') {
                $filters.find('input[name="instant"]').prop('checked', true);
            }

            if (state.orderby) {
                var $sortSelect = $filters.find('select[name="orderby"]');
                if ($sortSelect.length) {
                    var $matchingOption = $sortSelect.find('option').filter(function () {
                        var $option = $(this);
                        var optionValue = ($option.val() || '').toString();
                        var optionOrder = ($option.data('order') || '').toString();
                        if (optionValue !== state.orderby) {
                            return false;
                        }

                        if (!state.order) {
                            return true;
                        }

                        return optionOrder === state.order;
                    }).first();

                    if ($matchingOption.length) {
                        $sortSelect.val($matchingOption.val());
                        $sortSelect.find('option').prop('selected', false);
                        $matchingOption.prop('selected', true);
                        state.order = ($matchingOption.data('order') || state.order || 'desc').toString();
                    } else {
                        $sortSelect.val(state.orderby);
                        var $selectedOption = $sortSelect.find(':selected');
                        if ($selectedOption.length) {
                            state.order = ($selectedOption.data('order') || state.order || 'desc').toString();
                        }
                    }
                }
            }

            $filters.on('submit', function (event) {
                event.preventDefault();
                state.search = $filters.find('input[name="search"]').val() || '';
                fetch(1);
            });

            $filters.on('change', 'select[name="category"]', function () {
                state.category = $(this).val() || '';
                fetch(1);
            });

            $filters.on('change', 'select[name="orderby"]', function () {
                var $selected = $(this).find(':selected');
                state.orderby = $(this).val() || 'created';
                state.order = ($selected.data('order') || 'desc').toString();
                fetch(1);
            });

            $filters.on('change', 'input[name="instant"]', function () {
                state.instant = $(this).is(':checked') ? '1' : '';
                fetch(1);
            });

            $filters.on('click', '[data-jp-groups-clear]', function () {
                $filters.find('input[name="search"]').val('');
                $filters.find('select[name="category"]').prop('selectedIndex', 0);
                var $sort = $filters.find('select[name="orderby"]');
                $sort.prop('selectedIndex', 0);
                state.orderby = $sort.val() || 'created';
                state.order = ($sort.find(':selected').data('order') || 'desc').toString();
                $filters.find('input[name="instant"]').prop('checked', false);
                state.search = '';
                state.category = '';
                state.instant = '';
                fetch(1);
            });
        }

        if ($pagination.length) {
            $pagination.on('click', '[data-page]', function (event) {
                event.preventDefault();
                var target = parseInt($(this).attr('data-page'), 10);

                if (Number.isNaN(target) || target < 1 || target > state.totalPages || target === state.currentPage) {
                    return;
                }

                fetch(target);

                window.requestAnimationFrame(function () {
                    var offset = $root.offset();
                    if (!offset) {
                        return;
                    }

                    $('html, body').animate({ scrollTop: Math.max(0, offset.top - 120) }, 240);
                });
            });
        }

        fetch(1);
    }

    function initTwoFactor($root) {
        if (!$root.length) {
            return;
        }

        var remaining = parseInt($root.data('remaining'), 10) || 0;
        var cooldown = parseInt($root.data('cooldown'), 10) || 45;
        var $timer = $root.find('[data-jp-two-factor-timer]');
        var $resendButton = $root.find('[data-jp-two-factor-resend-button]');
        var $input = $root.find('[data-jp-two-factor-input]');

        if ($input.length) {
            setTimeout(function () {
                $input.trigger('focus');
            }, 150);
        }

        if ($timer.length && remaining > 0) {
            var countdown = setInterval(function () {
                remaining -= 1;

                if (remaining <= 0) {
                    clearInterval(countdown);
                    $timer.attr('hidden', 'hidden');
                    return;
                }

                var minutes = Math.floor(remaining / 60);
                var seconds = remaining % 60;
                var formatted = String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');
                $timer.removeAttr('hidden').text(__('O código expira em %s.', 'juntaplay').replace('%s', formatted));
            }, 1000);
        }

        if ($resendButton.length && cooldown > 0) {
            var cooldownTimer;
            $root.on('submit', '[data-jp-two-factor-resend]', function () {
                if ($resendButton.prop('disabled')) {
                    return;
                }

                $resendButton.prop('disabled', true).text(__('Enviando...', 'juntaplay'));

                if (cooldownTimer) {
                    clearInterval(cooldownTimer);
                }

                var remainingCooldown = cooldown;
                cooldownTimer = setInterval(function () {
                    remainingCooldown -= 1;

                    if (remainingCooldown <= 0) {
                        clearInterval(cooldownTimer);
                        $resendButton.prop('disabled', false).text(__('Enviar novo código', 'juntaplay'));
                    } else {
                        $resendButton.text(__('Tente novamente em %s s', 'juntaplay').replace('%s', remainingCooldown));
                    }
                }, 1000);
            });
        }
    }

    $(function () {
        $('.juntaplay-profile__row').each(function () {
            var $row = $(this);
            var isEditing = $row.hasClass('is-editing');
            $row.find('.juntaplay-profile__form').attr('aria-hidden', isEditing ? 'false' : 'true');
            $row.find('.juntaplay-profile__edit').attr('aria-expanded', isEditing ? 'true' : 'false');
        });

        $('[data-jp-wallet]').each(function () {
            initWallet($(this));
        });

        initNotifications();
        initAccountMenus();
    });

    $(document).on('change', 'input[name="juntaplay_use_wallet"]', function () {
        scheduleCheckoutUpdate();
    });

    $(document).on('input change', '.juntaplay-group-quantity', function () {
        scheduleCheckoutUpdate();
    });

    $(document).on('submit', '.juntaplay-form[data-confirm]', function (event) {
        var $form = $(this);
        var message = $form.data('confirm');

        if (message && !window.confirm(message)) {
            event.preventDefault();
            return false;
        }

        return true;
    });

    function initWallet($root) {
        if (!$root.length || typeof window.JuntaPlay === 'undefined') {
            return;
        }

        var isLoading = false;
        var $details = $root.find('[data-jp-credit-details]');
        var $detailsBody = $details.find('[data-jp-credit-details-body]');
        var $detailsTitle = $details.find('[data-jp-credit-details-title]');
        var $loadMore = $root.find('[data-jp-credit-load-more]');
        var $hint = $root.find('[data-jp-credit-countdown]');
        var $depositPanel = $root.find('[data-jp-credit-deposit]');
        var $depositForm = $depositPanel.find('[data-jp-credit-deposit-form]');
        var $depositInput = $depositForm.find('[name="jp_profile_deposit_amount"]');
        var $depositError = $depositForm.find('[data-jp-credit-deposit-error]');
        var depositEnabled = parseInt($root.data('deposit-enabled'), 10) === 1;
        var depositMin = parseFloat($root.data('deposit-min')) || 0;
        var depositMax = parseFloat($root.data('deposit-max')) || 0;
        var depositLoading = false;
        var $itemsList = $root.find('[data-jp-credit-items]');
        var $emptyState = $root.find('[data-jp-credit-empty]');
        var $filters = $root.find('[data-jp-credit-filter]');
        var $rangeSelect = $root.find('[data-jp-credit-range]');
        var $searchInput = $root.find('[data-jp-credit-search]');
        var $refreshButton = $root.find('[data-jp-credit-refresh]');
        var defaultEmptyMessage = $emptyState.length ? ($emptyState.data('default-message') || $emptyState.text()) : '';
        var filteredEmptyMessage = __('Nenhuma movimentação corresponde aos filtros selecionados.', 'juntaplay');
        var activeFilter = 'all';
        var $activeFilterButton = $filters.filter('.is-active').first();
        if ($activeFilterButton.length) {
            var initialFilterValue = $activeFilterButton.data('jp-credit-filter');
            if (typeof initialFilterValue !== 'undefined' && initialFilterValue !== null && initialFilterValue !== '') {
                activeFilter = initialFilterValue.toString();
            }
        }

        function showNotice(type, message) {
            var $notice = $('<div/>')
                .addClass('juntaplay-wallet__alert juntaplay-wallet__alert--' + type)
                .text(message || '');
            $root.find('.juntaplay-wallet__withdraw .juntaplay-wallet__alert').remove();
            $root.find('.juntaplay-wallet__withdraw').prepend($notice);
        }

        function renderTransaction(item) {
            var id = item.id || 0;
            var type = item.type || '';
            var status = item.status || '';
            var typeLabel = item.type_label || '';
            var statusLabel = item.status_label || '';
            var amount = item.amount_formatted || item.amount || '';
            var time = item.time || '';
            var reference = item.reference || '';
            var timestamp = parseInt(item.timestamp, 10);
            if (!isFinite(timestamp)) {
                timestamp = 0;
            }

            var search = item.search || '';
            if (!search) {
                var composite = [typeLabel, statusLabel, reference, time].filter(Boolean).join(' ');
                search = composite ? composite.toLowerCase() : '';
            }

            var referenceLabel = '';
            if (reference) {
                referenceLabel = __('Referência: %s', 'juntaplay').replace('%s', reference);
            }

            return [
                '<li class="juntaplay-wallet__item" data-transaction="' + escapeHtml(id) + '" data-type="' + escapeHtml(type) + '" data-status="' + escapeHtml(status) + '" data-time="' + escapeHtml(timestamp) + '" data-search="' + escapeHtml(search.toString().toLowerCase()) + '">',
                '  <div class="juntaplay-wallet__item-main">',
                '    <strong class="juntaplay-wallet__item-title">' + escapeHtml(typeLabel) + '</strong>',
                time ? '    <span class="juntaplay-wallet__item-meta">' + escapeHtml(time) + '</span>' : '',
                referenceLabel ? '    <span class="juntaplay-wallet__item-meta juntaplay-wallet__item-ref">' + escapeHtml(referenceLabel) + '</span>' : '',
                '  </div>',
                '  <div class="juntaplay-wallet__item-side">',
                statusLabel ? '    <span class="juntaplay-wallet__item-status">' + escapeHtml(statusLabel) + '</span>' : '',
                amount ? '    <span class="juntaplay-wallet__item-amount">' + escapeHtml(amount) + '</span>' : '',
                '  </div>',
                '</li>'
            ].filter(Boolean).join('');
        }

        function updateTotalLabel(total) {
            var $totalLabel = $root.find('[data-jp-credit-total]');
            if (!$totalLabel.length) {
                return;
            }

            var totalNumber = parseInt(total, 10);
            if (!isFinite(totalNumber)) {
                totalNumber = 0;
            }

            var text = _n('%d movimento', '%d movimentos', totalNumber, 'juntaplay');
            text = text.replace('%d', totalNumber).replace('%s', totalNumber);
            $totalLabel.text(text);
        }

        function updateLoadMoreVisibility() {
            if (!$loadMore.length) {
                return;
            }

            var current = parseInt($root.attr('data-page'), 10) || 1;
            var pages = parseInt($root.attr('data-pages'), 10) || 1;

            if (pages <= 1 || current >= pages) {
                $loadMore.attr('hidden', 'hidden');
            } else {
                $loadMore.removeAttr('hidden');
            }
        }

        function getRangeThreshold() {
            if (!$rangeSelect.length) {
                return 0;
            }

            var days = parseInt($rangeSelect.val(), 10);
            if (!isFinite(days) || days <= 0) {
                return 0;
            }

            return Math.floor(Date.now() / 1000) - (days * 86400);
        }

        function applyFilters(fromServer) {
            if (!$itemsList.length) {
                updateLoadMoreVisibility();
                return;
            }

            var searchTerm = $searchInput.length ? ($searchInput.val() || '').toString().toLowerCase() : '';
            var threshold = getRangeThreshold();
            var visible = 0;
            var hasItems = false;

            $itemsList.children('.juntaplay-wallet__item').each(function () {
                var $item = $(this);
                hasItems = true;

                var itemType = ($item.data('type') || '').toString();
                var itemSearch = ($item.data('search') || '').toString();
                var itemTimestamp = parseInt($item.data('time'), 10) || 0;

                var matchesType = activeFilter === 'all' || itemType === activeFilter;
                var matchesSearch = !searchTerm || itemSearch.indexOf(searchTerm) !== -1;
                var matchesRange = !threshold || itemTimestamp >= threshold;

                if (matchesType && matchesSearch && matchesRange) {
                    $item.removeAttr('hidden');
                    visible++;
                } else {
                    $item.attr('hidden', 'hidden');
                }
            });

            if ($emptyState.length) {
                var message = defaultEmptyMessage;
                if (hasItems && visible === 0) {
                    message = filteredEmptyMessage;
                }

                if (!hasItems && fromServer) {
                    message = defaultEmptyMessage;
                }

                $emptyState.text(message);

                if (!hasItems && fromServer) {
                    $emptyState.removeAttr('hidden');
                } else if (visible === 0) {
                    $emptyState.removeAttr('hidden');
                } else {
                    $emptyState.attr('hidden', 'hidden');
                }
            }

            updateLoadMoreVisibility();
        }

        function fetchTransactions(page, append) {
            if (!window.JuntaPlay || !window.JuntaPlay.ajax) {
                return;
            }

            if (isLoading) {
                return;
            }

            isLoading = true;
            $root.addClass('is-loading');

            var params = {
                action: 'juntaplay_credit_transactions',
                nonce: window.JuntaPlay.nonce,
                page: page
            };

            if (activeFilter && activeFilter !== 'all') {
                params.type = activeFilter;
            }

            $.getJSON(window.JuntaPlay.ajax, params).done(function (response) {
                if (!response || !response.success || !response.data) {
                    return;
                }

                var data = response.data;
                var items = Array.isArray(data.items) ? data.items : [];
                var markup = items.map(renderTransaction).join('');

                if (!append) {
                    $itemsList.html(markup);
                } else if (markup) {
                    $itemsList.append(markup);
                }

                if (typeof data.page !== 'undefined') {
                    $root.attr('data-page', data.page);
                } else if (!append) {
                    $root.attr('data-page', page);
                }

                if (typeof data.pages !== 'undefined') {
                    $root.attr('data-pages', data.pages);
                }

                if (typeof data.total !== 'undefined') {
                    $root.attr('data-total', data.total);
                    updateTotalLabel(data.total);
                }

                applyFilters(!append);
            }).always(function () {
                isLoading = false;
                $root.removeClass('is-loading');
                updateLoadMoreVisibility();
            });
        }

        function openDetails(data) {
            if (!data || !$details.length) {
                return;
            }

            var html = ['<dl>'];
            html.push('<dt>' + (data.type_label || '') + '</dt>');
            html.push('<dd>' + (data.amount_formatted || '') + '</dd>');

            if (data.status_label) {
                html.push('<dt>' + __('Status', 'juntaplay') + '</dt>');
                html.push('<dd>' + data.status_label + '</dd>');
            }

            if (data.reference) {
                html.push('<dt>' + __('Referência', 'juntaplay') + '</dt>');
                html.push('<dd>' + data.reference + '</dd>');
            }

            if (data.time) {
                html.push('<dt>' + __('Data', 'juntaplay') + '</dt>');
                html.push('<dd>' + data.time + '</dd>');
            }

            if (data.balance_after) {
                html.push('<dt>' + __('Saldo após', 'juntaplay') + '</dt>');
                html.push('<dd>' + data.balance_after + '</dd>');
            }

            if (data.context) {
                Object.keys(data.context).forEach(function (key) {
                    if (!data.context[key]) {
                        return;
                    }
                    html.push('<dt>' + key + '</dt>');
                    html.push('<dd>' + data.context[key] + '</dd>');
                });
            }

            html.push('</dl>');

            $detailsTitle.text(data.type_label || __('Detalhes', 'juntaplay'));
            $detailsBody.html(html.join(''));
            $details.removeAttr('hidden');
        }

        function closeDetails() {
            $details.attr('hidden', 'hidden');
        }

        function openDeposit() {
            if (!$depositPanel.length) {
                return;
            }

            $depositPanel.removeAttr('hidden').addClass('is-open');
            if ($depositInput.length) {
                $depositInput.trigger('focus');
            }
        }

        function closeDeposit() {
            if (!$depositPanel.length) {
                return;
            }

            $depositPanel.attr('hidden', 'hidden').removeClass('is-open');
            $depositError.attr('hidden', 'hidden').text('');
            depositLoading = false;
        }

        function showDepositError(message) {
            if (!$depositError.length) {
                window.alert(message);
                return;
            }

            $depositError.text(message || '').removeAttr('hidden');
        }

        $root.on('click', '[data-jp-credit-details-close]', function () {
            closeDetails();
        });

        $root.on('click', '.juntaplay-wallet__item', function () {
            if (isLoading) {
                return;
            }

            var transactionId = $(this).data('transaction');
            if (!transactionId) {
                return;
            }

            isLoading = true;
            $.getJSON(window.JuntaPlay.ajax, {
                action: 'juntaplay_credit_transaction',
                nonce: window.JuntaPlay.nonce,
                id: transactionId
            }).done(function (response) {
                if (response && response.success && response.data && response.data.transaction) {
                    openDetails(response.data.transaction);
                }
            }).fail(function () {
                window.alert(__('Não foi possível carregar os detalhes agora.', 'juntaplay'));
            }).always(function () {
                isLoading = false;
            });
        });

        $root.on('click', '[data-jp-credit-load-more]', function () {
            if (isLoading) {
                return;
            }

            var current = parseInt($root.attr('data-page'), 10) || 1;
            var pages = parseInt($root.attr('data-pages'), 10) || 1;

            if (current >= pages) {
                updateLoadMoreVisibility();
                return;
            }

            fetchTransactions(current + 1, true);
        });

        if ($filters.length) {
            $filters.on('click', function (event) {
                event.preventDefault();

                var value = $(this).data('jp-credit-filter');
                var nextFilter = 'all';
                if (typeof value !== 'undefined' && value !== null && value !== '') {
                    nextFilter = value.toString();
                }

                if (nextFilter === activeFilter) {
                    return;
                }

                activeFilter = nextFilter;
                $filters.removeClass('is-active');
                $(this).addClass('is-active');
                $root.attr('data-page', 1);
                applyFilters(false);
                fetchTransactions(1, false);
            });
        }

        if ($rangeSelect.length) {
            $rangeSelect.on('change', function () {
                applyFilters(false);
            });
        }

        if ($searchInput.length) {
            $searchInput.on('input', function () {
                applyFilters(false);
            });
        }

        if ($refreshButton.length) {
            $refreshButton.on('click', function (event) {
                event.preventDefault();
                fetchTransactions(1, false);
            });
        }

        $root.on('submit', '.juntaplay-wallet__form', function (event) {
            var $form = $(this);

            if (!window.JuntaPlay || !window.JuntaPlay.ajax) {
                return true;
            }

            event.preventDefault();

            if (isLoading) {
                return false;
            }

            isLoading = true;

            var payload = {
                action: 'juntaplay_credit_withdraw',
                nonce: window.JuntaPlay.nonce,
                amount: $form.find('[name="jp_profile_withdraw_amount"]').val(),
                method: $form.find('[name="jp_profile_withdraw_method"]').val(),
                code: $form.find('[name="jp_profile_withdraw_code"]').val()
            };

            $.post(window.JuntaPlay.ajax, payload, null, 'json').done(function (response) {
                if (!response) {
                    return;
                }

                if (response.success) {
                    showNotice('success', response.data && response.data.message ? response.data.message : __('Solicitação registrada com sucesso.', 'juntaplay'));
                    window.setTimeout(function () {
                        window.location.reload();
                    }, 1500);
                    return;
                }

                if (response.data && response.data.message) {
                    showNotice('warning', response.data.message);
                }
            }).fail(function () {
                showNotice('warning', __('Não foi possível registrar a solicitação agora.', 'juntaplay'));
            }).always(function () {
                isLoading = false;
            });

            return false;
        });

        $root.on('click', '[data-jp-credit-send-code]', function () {
            if (isLoading) {
                return;
            }

            isLoading = true;
            var $button = $(this);
            $button.prop('disabled', true);

            $.post(window.JuntaPlay.ajax, {
                action: 'juntaplay_credit_send_code',
                nonce: window.JuntaPlay.nonce
            }, null, 'json').done(function (response) {
                if (response && response.success) {
                    var data = response.data || {};
                    if ($hint.length && data.message) {
                        $hint.text(data.message);
                    }
                    if ($root.find('[data-jp-credit-destination]').length && data.destination) {
                        $root.find('[data-jp-credit-destination]').text(data.destination);
                    }
                } else if (response && response.data && response.data.message) {
                    window.alert(response.data.message);
                }
            }).fail(function () {
                window.alert(__('Não foi possível enviar o código agora.', 'juntaplay'));
            }).always(function () {
                isLoading = false;
                $button.prop('disabled', false);
            });
        });

        $root.on('click', '[data-jp-credit-details]', function (event) {
            if ($(event.target).is('[data-jp-credit-details]')) {
                closeDetails();
            }
        });

        $root.on('click', '[data-jp-credit-topup]', function (event) {
            event.preventDefault();

            if (!depositEnabled) {
                window.alert(__('Recarga indisponível no momento.', 'juntaplay'));

                return;
            }

            openDeposit();
        });

        $root.on('click', '[data-jp-credit-deposit-close]', function (event) {
            event.preventDefault();
            closeDeposit();
        });

        $root.on('click', '[data-jp-credit-suggestion]', function (event) {
            event.preventDefault();
            if (!$depositInput.length) {
                return;
            }

            var value = parseFloat($(this).data('jp-credit-suggestion')) || 0;
            if (value > 0) {
                $depositInput.val(value.toFixed(2)).trigger('change');
            }
        });

        $root.on('submit', '[data-jp-credit-deposit-form]', function (event) {
            event.preventDefault();

            if (!window.JuntaPlay || !window.JuntaPlay.ajax) {
                return false;
            }

            if (depositLoading) {
                return false;
            }

            var amount = 0;
            if ($depositInput.length) {
                amount = parseFloat(($depositInput.val() || '').toString().replace(',', '.')) || 0;
            }

            if (amount <= 0 || (depositMin > 0 && amount < depositMin)) {
                showDepositError(__('Informe um valor de recarga acima do mínimo permitido.', 'juntaplay'));

                return false;
            }

            if (depositMax > 0 && amount > depositMax) {
                showDepositError(__('O valor informado excede o limite máximo permitido.', 'juntaplay'));

                return false;
            }

            depositLoading = true;
            $depositError.attr('hidden', 'hidden').text('');

            var payload = {
                action: 'juntaplay_credit_deposit',
                nonce: window.JuntaPlay.nonce,
                amount: amount
            };

            $.post(window.JuntaPlay.ajax, payload, null, 'json').done(function (response) {
                if (!response) {
                    return;
                }

                if (response.success && response.data && response.data.redirect) {
                    window.location.href = response.data.redirect;
                    return;
                }

                if (response.success) {
                    window.location.reload();
                    return;
                }

                if (response.data && response.data.message) {
                    showDepositError(response.data.message);
                } else {
                    showDepositError(__('Não foi possível iniciar a recarga agora.', 'juntaplay'));
                }
            }).fail(function () {
                showDepositError(__('Não foi possível iniciar a recarga agora.', 'juntaplay'));
            }).always(function () {
                depositLoading = false;
            });

            return false;
        });

        applyFilters(true);
        updateLoadMoreVisibility();
    }

    function initGroupRotator($root) {
        if (!$root.length || typeof window.JuntaPlay === 'undefined') {
            return;
        }

        var limit = parseInt($root.data('limit'), 10) || 12;
        var defaultCategory = ($root.data('defaultCategory') || '').toString();

        var state = {
            category: defaultCategory,
            loading: false
        };

        var restoredAnchor = false;

        var $grid = $root.find('[data-rotator-grid]');
        var $track = $root.find('[data-rotator-track]');
        var $empty = $root.find('[data-rotator-empty]');
        var $navPrev = $root.find('[data-rotator-nav="prev"]');
        var $navNext = $root.find('[data-rotator-nav="next"]');

        function setLoading(isLoading) {
            state.loading = isLoading;
            $root.toggleClass('is-loading', isLoading);
        }

        function updateNav() {
            if (!$track.length) {
                return;
            }

            var element = $track.get(0);
            if (!element) {
                return;
            }

            var maxScroll = Math.max(0, element.scrollWidth - element.clientWidth);
            var scrollLeft = element.scrollLeft;
            var threshold = 4;
            var atStart = scrollLeft <= threshold;
            var atEnd = scrollLeft >= (maxScroll - threshold);

            if ($navPrev.length) {
                $navPrev.toggleClass('is-disabled', atStart).prop('disabled', atStart).attr('aria-disabled', atStart ? 'true' : 'false');
            }

            if ($navNext.length) {
                $navNext.toggleClass('is-disabled', atEnd).prop('disabled', atEnd).attr('aria-disabled', atEnd ? 'true' : 'false');
            }
        }

        function render(items) {
            $grid.empty();

            if (!items || !items.length) {
                if ($empty.length) {
                    $empty.removeAttr('hidden');
                }
                return;
            }

            var html = items.map(function (item) {
                return renderGroupCard(item, 'spotlight');
            }).join('');

            $grid.html(html);
            if ($empty.length) {
                $empty.attr('hidden', 'hidden');
            }

            restoreAnchorTarget();
        }

        function getAnchorTargetId() {
            if (typeof window === 'undefined') {
                return 0;
            }

            ensureAnchorFromQuery();

            var hash = (window.location.hash || '').toString();
            if (!hash) {
                return 0;
            }

            var match = hash.match(/^#jp-group-card-(\d+)/i);
            if (!match || match.length < 2) {
                return 0;
            }

            return parseInt(match[1], 10) || 0;
        }

        function ensureAnchorFromQuery() {
            if (typeof window === 'undefined') {
                return;
            }

            if (window.location.hash) {
                return;
            }

            var anchor = '';

            try {
                var url = new URL(window.location.href);
                anchor = (url.searchParams.get('jp_group_anchor') || '').toString();

                if (anchor) {
                    url.searchParams.delete('jp_group_anchor');
                    var cleaned = url.toString();
                    window.history.replaceState({}, document.title, cleaned);
                }
            } catch (err) {
                var search = window.location.search || '';
                var match = search.match(/[?&]jp_group_anchor=([^&#]*)/);
                if (match && match[1]) {
                    anchor = decodeURIComponent(match[1].replace(/\+/g, ' '));
                }
            }

            if (!anchor) {
                return;
            }

            var sanitized = anchor.replace(/[^A-Za-z0-9_-]/g, '');
            if (!sanitized) {
                return;
            }

            window.location.hash = '#' + sanitized;
        }

        function restoreAnchorTarget() {
            if (restoredAnchor) {
                return;
            }

            var targetId = getAnchorTargetId();
            if (!targetId) {
                return;
            }

            var $card = $('#jp-group-card-' + targetId);
            if (!$card.length) {
                return;
            }

            restoredAnchor = true;

            var cardEl = $card.get(0);
            if (cardEl && typeof cardEl.scrollIntoView === 'function') {
                try {
                    cardEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
                } catch (e) {
                    try {
                        cardEl.scrollIntoView();
                    } catch (err) {
                        // ignore scroll errors
                    }
                }
            }

            window.setTimeout(function () {
                var $current = $('#jp-group-card-' + targetId);
                if (!$current.length) {
                    return;
                }

                if (!$current.attr('tabindex')) {
                    $current.attr('tabindex', '-1');
                }

                try {
                    $current.focus();
                } catch (err) {
                    // ignore focus errors
                }

                var $trigger = $current.find('[data-jp-group-open]').first();
                if ($trigger.length) {
                    $trigger.trigger('click');
                }
            }, 120);
        }

        function fetch() {
            if (state.loading) {
                return;
            }

            setLoading(true);

            $.getJSON(window.JuntaPlay.ajax, {
                action: 'juntaplay_groups_directory',
                nonce: window.JuntaPlay.nonce,
                page: 1,
                per_page: limit,
                orderby: 'updated',
                order: 'desc',
                category: state.category
            }).done(function (response) {
                if (!response || !response.success || !response.data) {
                    render([]);
                    return;
                }

                render(response.data.items || []);
            }).fail(function () {
                render([]);
            }).always(function () {
                setLoading(false);
            });
        }

        if ($track.length) {
            $track.on('click', '[data-rotator-filter]', function (event) {
                event.preventDefault();

                var $button = $(this);
                var category = ($button.data('rotatorFilter') || '').toString();

                if (category === state.category) {
                    return;
                }

                state.category = category;
                $button.addClass('is-active').attr('aria-selected', 'true');
                $button.siblings('[data-rotator-filter]').removeClass('is-active').attr('aria-selected', 'false');
                var buttonEl = $button.get(0);
                if (buttonEl && typeof buttonEl.scrollIntoView === 'function') {
                    try {
                        buttonEl.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
                    } catch (e) {
                        // ignore scroll errors
                    }
                }
                fetch();
            });

            $track.on('scroll', function () {
                updateNav();
            });
        }

        if ($navPrev.length || $navNext.length) {
            var scrollStep = function (direction) {
                if (!$track.length) {
                    return;
                }

                var element = $track.get(0);
                if (!element) {
                    return;
                }

                var amount = element.clientWidth * 0.6;
                var target = Math.max(0, Math.min(element.scrollLeft + (direction * amount), element.scrollWidth - element.clientWidth));

                $track.stop().animate({ scrollLeft: target }, 260, 'swing', updateNav);
            };

            if ($navPrev.length) {
                $navPrev.on('click', function (event) {
                    event.preventDefault();
                    if ($(this).hasClass('is-disabled')) {
                        return;
                    }
                    scrollStep(-1);
                });
            }

            if ($navNext.length) {
                $navNext.on('click', function (event) {
                    event.preventDefault();
                    if ($(this).hasClass('is-disabled')) {
                        return;
                    }
                    scrollStep(1);
                });
            }

            $(window).on('resize', function () {
                updateNav();
            });
        }

        fetch();
        updateNav();
    }

    function initAccountMenus() {
        $('[data-jp-account]').each(function () {
            var $account = $(this);
            var $toggle = $account.find('[data-jp-account-toggle]');
            var $menu = $account.find('[data-jp-account-menu]');

            if (!$toggle.length || !$menu.length || $account.data('jpAccountInit')) {
                return;
            }

            $account.data('jpAccountInit', true);

            function setState(open) {
                $toggle.attr('aria-expanded', open ? 'true' : 'false');
                $toggle.toggleClass('is-active', open);
                $menu.attr('aria-hidden', open ? 'false' : 'true');
                $menu.toggleClass('is-open', open);
            }

            function toggle(force, skipCascade) {
                var open = typeof force === 'boolean' ? force : !$menu.hasClass('is-open');

                if (open) {
                    if (!skipCascade) {
                        $('[data-jp-account-menu].is-open').not($menu).each(function () {
                            var $otherMenu = $(this);
                            var $otherToggle = $otherMenu.closest('[data-jp-account]').find('[data-jp-account-toggle]');

                            $otherMenu.removeClass('is-open').attr('aria-hidden', 'true');
                            $otherToggle.removeClass('is-active').attr('aria-expanded', 'false');
                        });
                    }

                    setState(true);
                } else {
                    setState(false);
                }
            }

            $menu.data('jpAccountToggle', function (state, skip) {
                toggle(state, skip);
            });

            $toggle.on('click', function (event) {
                event.preventDefault();
                toggle();
            });

            $menu.on('click', 'a', function () {
                toggle(false);
            });

            setState(false);
        });

        $(document).off('click.juntaplayAccount').on('click.juntaplayAccount', function (event) {
            if ($(event.target).closest('[data-jp-account]').length) {
                return;
            }

            $('[data-jp-account-menu].is-open').each(function () {
                var $menu = $(this);
                var toggle = $menu.data('jpAccountToggle');

                if (typeof toggle === 'function') {
                    toggle(false, true);
                } else {
                    $menu.removeClass('is-open').attr('aria-hidden', 'true');
                    var $button = $menu.closest('[data-jp-account]').find('[data-jp-account-toggle]');
                    $button.removeClass('is-active').attr('aria-expanded', 'false');
                }
            });
        });
    }

    function buildGroupSuccessButton(url) {
        var href = (url || '').toString().trim();
        if (!href) {
            return '';
        }

        var safeUrl = escapeHtml(href);
        var label = escapeHtml(__('Ver + Grupos', 'juntaplay'));

        return ''
            + '<a class="elementor-button elementor-button-link elementor-size-sm" href="' + safeUrl + '">' 
            + '<span class="elementor-button-content-wrapper">'
            + '<span class="elementor-button-icon">'
            + '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="17" viewBox="0 0 16 17" fill="currentColor">'
            + '<path d="M15.5553 0.670898H5.77756C5.53189 0.670898 5.3331 0.86969 5.3331 1.11536C5.3331 1.36102 5.53189 1.55982 5.77756 1.55982H14.4824L0.129975 15.9122C-0.0436504 16.0859 -0.0436504 16.3671 0.129975 16.5407C0.216766 16.6275 0.330516 16.6709 0.444225 16.6709C0.557933 16.6709 0.671641 16.6275 0.758475 16.5407L15.1109 2.18827V10.8931C15.1109 11.1388 15.3097 11.3376 15.5553 11.3376C15.801 11.3376 15.9998 11.1388 15.9998 10.8931V1.11536C15.9998 0.86969 15.801 0.670898 15.5553 0.670898Z" fill="currentColor"></path>'
            + '</svg>'
            + '</span>'
            + '<span class="elementor-button-text">' + label + '</span>'
            + '</span>'
            + '</a>';
    }

    function createGroupSuccessContent(state) {
        var $content = cloneTemplate('#jp-group-success-template');
        if (!$content || !$content.length) {
            return null;
        }

        var heading = (state && state.heading ? state.heading : __('Grupo cadastrado com sucesso!', 'juntaplay')).toString();
        var body = (state && state.body ? state.body : __('Aguarde que nossa equipe vai validar e você será notificado.', 'juntaplay')).toString();
        var image = state && state.image ? state.image.toString() : '';
        var redirect = state && state.redirect ? state.redirect.toString() : '';

        var $heading = $content.find('[data-group-success-heading]');
        if ($heading.length) {
            $heading.text(heading);
        }

        var $body = $content.find('[data-group-success-body]');
        if ($body.length) {
            $body.text(body);
        }

        var $media = $content.find('[data-group-success-media]');
        var $image = $content.find('[data-group-success-image]');

        if ($image.length && image) {
            $image.attr('src', image).attr('alt', heading);
        } else if ($media.length) {
            $media.remove();
        }

        var fallbackUrl = 'https://www.juntaplay.com.br/grupos';
        var targetUrl = redirect.trim() !== '' ? redirect : fallbackUrl;
        var $ctaSlot = $content.find('[data-group-success-cta]');

        if ($ctaSlot.length) {
            var buttonHtml = targetUrl ? buildGroupSuccessButton(targetUrl) : '';
            if (buttonHtml) {
                $ctaSlot.html(buttonHtml);
            } else {
                $ctaSlot.remove();
            }
        }

        return $content;
    }

    function openGroupSuccessModal(state) {
        var $modal = $('[data-group-success-modal]');
        if (!$modal.length) {
            return false;
        }

        var $content = createGroupSuccessContent(state || {});
        if (!$content || !$content.length) {
            return false;
        }

        openModal($modal, $content);
        return true;
    }

    function initGroupSuccessState() {
        var $state = $('[data-group-success-state]').first();
        if (!$state.length) {
            return;
        }

        var state = {
            heading: ($state.data('successHeading') || '').toString(),
            body: ($state.data('successBody') || '').toString(),
            image: ($state.data('successImage') || '').toString(),
            redirect: ($state.data('successRedirect') || '').toString()
        };

        if (openGroupSuccessModal(state)) {
            $state.remove();
        }
    }

    function initNotifications() {
        if (typeof window.JuntaPlay === 'undefined') {
            return;
        }

        var $bells = $('[data-jp-notifications]');
        if (!$bells.length) {
            return;
        }

        function syncCount(unread) {
            $('[data-jp-notifications]').each(function () {
                var $btn = $(this);

                if (unread > 0) {
                    $btn.attr('data-count', unread);
                } else {
                    $btn.removeAttr('data-count');
                }
            });
        }

        $bells.each(function () {
            var $bell = $(this);

            if ($bell.data('jpNotificationsInit')) {
                return;
            }

            $bell.data('jpNotificationsInit', true);

            var $panel = $bell.siblings('[data-jp-notifications-panel]').first();

            if (!$panel.length) {
                var $root = $bell.closest('[data-jp-notifications-root]');
                if ($root.length) {
                    $panel = $root.find('[data-jp-notifications-panel]').first();
                }
            }

            if (!$panel.length) {
                return;
            }

            var $list = $panel.find('[data-jp-notifications-list]');
            var isLoaded = false;
            var isLoading = false;
            var emptyMarkup = '<li class="juntaplay-notifications__empty">' + __('Nenhuma notificação por enquanto.', 'juntaplay') + '</li>';

            function renderEmptyState() {
                $list.html(emptyMarkup);
            }

            $bell.attr('aria-expanded', 'false');
            $panel.attr('aria-hidden', 'true');

            function setState(open) {
                $bell.attr('aria-expanded', open ? 'true' : 'false');
                $bell.toggleClass('is-active', open);
                $panel.attr('aria-hidden', open ? 'false' : 'true');
                $panel.toggleClass('is-open', open);
            }

            function togglePanel(force, skipCascade) {
                var open = typeof force === 'boolean' ? force : !$panel.hasClass('is-open');

                if (open) {
                    if (!skipCascade) {
                        $('[data-jp-notifications-panel].is-open').not($panel).each(function () {
                            var $other = $(this);
                            var toggle = $other.data('jpNotificationsToggle');

                            if (typeof toggle === 'function') {
                                toggle(false, true);
                            } else {
                                $other.removeClass('is-open').attr('aria-hidden', 'true');
                                var $otherBell = $other.data('jpNotificationsBell');
                                if ($otherBell && $otherBell.length) {
                                    $otherBell.removeClass('is-active').attr('aria-expanded', 'false');
                                }
                            }
                        });
                    }

                    setState(true);

                    if (!isLoaded) {
                        fetchNotifications();
                    }
                } else {
                    setState(false);
                }
            }

            function fetchNotifications() {
                if (isLoading) {
                    return;
                }

                isLoading = true;

                if (!isLoaded) {
                    $list.html('<li class="juntaplay-notifications__empty">' + __('Carregando notificações...', 'juntaplay') + '</li>');
                }

                $.getJSON(window.JuntaPlay.ajax, {
                    action: 'juntaplay_notifications_feed',
                    nonce: window.JuntaPlay.nonce
                }).done(function (response) {
                    if (!response || !response.success) {
                        return;
                    }

                    isLoaded = true;

                    if (response.data && response.data.items) {
                        if (!response.data.items.length) {
                            renderEmptyState();
                        } else {
                            var html = response.data.items.map(function (item) {
                                var title = item.title || '';
                                var message = item.message || '';
                                var time = item.time || '';
                                var href = item.action_url || '';
                                var status = (item.status || '').toString();
                                var statusClass = status === 'unread' ? ' is-unread' : '';
                                var liStatus = status ? ' data-notification-status="' + status + '"' : '';
                                var baseAttrs = ' class="juntaplay-notifications__item' + statusClass + '" data-notification-id="' + item.id + '"';
                                var content = '<span class="juntaplay-notifications__item-title">' + title + '</span>' +
                                    '<span class="juntaplay-notifications__item-message">' + message + '</span>' +
                                    '<span class="juntaplay-notifications__item-time">' + time + '</span>';

                                if (href) {
                                    return '<li' + liStatus + '><a' + baseAttrs + ' href="' + href + '">' + content + '</a></li>';
                                }

                                return '<li' + liStatus + '><span' + baseAttrs + '>' + content + '</span></li>';
                            }).join('');

                            $list.html(html);
                        }
                    }

                    if (response.data && typeof response.data.unread !== 'undefined') {
                        var unread = parseInt(response.data.unread, 10) || 0;
                        syncCount(unread);
                    }
                }).always(function () {
                    isLoading = false;
                });
            }

            function markNotifications(ids, onComplete) {
                var payload = [];

                if (Array.isArray(ids)) {
                    ids.forEach(function (value) {
                        var parsed = parseInt(value, 10);
                        if (!isNaN(parsed) && parsed > 0) {
                            payload.push(parsed);
                        }
                    });
                }

                if (!payload.length) {
                    if (typeof onComplete === 'function') {
                        onComplete();
                    }

                    return;
                }

                $.post(window.JuntaPlay.ajax, {
                    action: 'juntaplay_notifications_mark',
                    nonce: window.JuntaPlay.nonce,
                    ids: payload
                }, null, 'json').done(function (response) {
                    if (response && response.data && typeof response.data.unread !== 'undefined') {
                        var unread = parseInt(response.data.unread, 10) || 0;
                        syncCount(unread);
                    }
                }).always(function () {
                    isLoaded = false;

                    if (typeof onComplete === 'function') {
                        onComplete();
                    }
                });
            }

            function markNotification(id, onComplete) {
                markNotifications([id], onComplete);
            }

            $panel.data('jpNotificationsToggle', function (state, skip) {
                togglePanel(state, skip);
            });

            $panel.data('jpNotificationsBell', $bell);

            $bell.on('click', function (event) {
                event.preventDefault();
                togglePanel();
            });

            $panel.on('click', '[data-notification-id]', function (event) {
                var $target = $(this);
                var id = parseInt($target.data('notification-id'), 10) || 0;

                if (!id) {
                    return;
                }

                var href = '';
                if ($target.is('a')) {
                    href = ($target.attr('href') || '').toString();
                }

                if (href) {
                    event.preventDefault();
                }

                var $item = $target.closest('li');
                if ($item.length) {
                    $item.remove();
                }

                if (!$list.children('[data-notification-id]').length) {
                    renderEmptyState();
                }

                markNotification(id, function () {
                    if (href) {
                        window.location.href = href;
                    } else if (!$list.children('[data-notification-id]').length) {
                        renderEmptyState();
                    }
                });
            });

            $panel.on('click', '[data-jp-notifications-clear]', function (event) {
                event.preventDefault();

                var ids = [];
                $list.find('[data-notification-id]').each(function () {
                    var value = parseInt($(this).data('notification-id'), 10);
                    if (!isNaN(value) && value > 0) {
                        ids.push(value);
                    }
                });

                if (!ids.length) {
                    renderEmptyState();
                    syncCount(0);
                    return;
                }

                $list.html('<li class="juntaplay-notifications__empty">' + __('Removendo notificações...', 'juntaplay') + '</li>');

                markNotifications(ids, function () {
                    renderEmptyState();
                });
            });

            $panel.on('click', '[data-jp-notifications-close]', function (event) {
                event.preventDefault();
                togglePanel(false);
            });

            setState(false);
        });

        $(document).off('click.juntaplayNotifications').on('click.juntaplayNotifications', function (event) {
            if ($(event.target).closest('[data-jp-notifications]').length || $(event.target).closest('[data-jp-notifications-panel]').length) {
                return;
            }

            $('[data-jp-notifications-panel].is-open').each(function () {
                var toggle = $(this).data('jpNotificationsToggle');

                if (typeof toggle === 'function') {
                    toggle(false, true);
                } else {
                    $(this).removeClass('is-open').attr('aria-hidden', 'true');
                    var $otherBell = $(this).data('jpNotificationsBell');
                    if ($otherBell && $otherBell.length) {
                        $otherBell.removeClass('is-active').attr('aria-expanded', 'false');
                    }
                }
            });
        });
    }

    var jpGroupModal = (function () {
        var ajaxEndpoint = (window.JuntaPlay && window.JuntaPlay.ajax) ? window.JuntaPlay.ajax : '';
        var ajaxNonce = (window.JuntaPlay && window.JuntaPlay.nonce) ? window.JuntaPlay.nonce : '';

        function getModal() {
            return jQuery('#juntaplay-group-modal');
        }

        function resolveAjaxEndpoint() {
            if (ajaxEndpoint) {
                return ajaxEndpoint;
            }

            var $modal = getModal();
            if ($modal.length) {
                var endpointAttr = $modal.attr('data-ajax-endpoint') ||
                    $modal.data('ajaxEndpoint') ||
                    $modal.data('ajax-endpoint');

                if (endpointAttr) {
                    ajaxEndpoint = endpointAttr.toString();
                    return ajaxEndpoint;
                }
            }

            if (typeof window.ajaxurl !== 'undefined' && window.ajaxurl) {
                ajaxEndpoint = window.ajaxurl.toString();
            }

            return ajaxEndpoint;
        }

        function resolveAjaxNonce() {
            if (ajaxNonce) {
                return ajaxNonce;
            }

            var $modal = getModal();
            if ($modal.length) {
                var nonceAttr = $modal.attr('data-ajax-nonce') ||
                    $modal.data('ajaxNonce') ||
                    $modal.data('ajax-nonce');

                if (nonceAttr) {
                    ajaxNonce = nonceAttr.toString();
                }
            }

            return ajaxNonce;
        }

        function openGroupModal(html) {
            var $modal = getModal();
            if (!$modal.length) {
                return;
            }

            $modal.find('[data-modal-messages]').empty();
            $modal.find('.juntaplay-modal__content').html(html);
            $modal.removeAttr('hidden').addClass('is-open').attr('aria-hidden', 'false');
            jQuery('body').addClass('juntaplay-modal-open');

            window.requestAnimationFrame(function () {
                var $focusable = $modal.find('input, select, textarea, button, a[href]').filter(':visible').first();
                if ($focusable.length) {
                    $focusable.trigger('focus');
                } else {
                    $modal.trigger('focus');
                }
            });
        }

        function showLoading() {
            var loadingMarkup = '<div class="juntaplay-group-modal__loading" role="status" aria-live="polite">' +
                '<span class="juntaplay-spinner" aria-hidden="true"></span>' +
                '<p>' + __('Carregando informações do grupo...', 'juntaplay') + '</p>' +
                '<div class="juntaplay-group-modal__skeleton" aria-hidden="true">' +
                '<span></span><span></span><span></span><span></span><span></span>' +
                '</div>' +
                '</div>';

            openGroupModal(loadingMarkup);
        }

        function setMessage(type, message) {
            var $modal = getModal();
            var $messages = $modal.find('[data-modal-messages]');

            if (!$messages.length) {
                return;
            }

            var tone = 'warning';
            if (type === 'success') {
                tone = 'positive';
            } else if (type === 'error') {
                tone = 'warning';
            }

            var $alert = jQuery('<div class="juntaplay-alert juntaplay-alert--' + tone + '" role="alert"></div>');
            $alert.text(message);
            $messages.empty().append($alert);
        }

        function closeModal() {
            var $modal = getModal();
            if (!$modal.length) {
                return;
            }

            $modal.removeClass('is-open').attr('aria-hidden', 'true').attr('hidden', 'hidden');
            $modal.find('.juntaplay-modal__content').empty();
            $modal.find('[data-modal-messages]').empty();

            if (!jQuery('.juntaplay-modal.is-open').not($modal).length) {
                jQuery('body').removeClass('juntaplay-modal-open');
            }
        }

        function extractError(response) {
            if (response && response.data && response.data.message) {
                return response.data.message;
            }

            return __('Não foi possível carregar os dados agora. Tente novamente em instantes.', 'juntaplay');
        }

        function load(action, groupId) {
            var endpoint = resolveAjaxEndpoint();

            if (!endpoint || !groupId) {
                return;
            }

            showLoading();

            var payload = {
                action: action,
                group_id: groupId
            };

            var nonce = resolveAjaxNonce();
            if (nonce) {
                payload.nonce = nonce;
            }

            jQuery.post(endpoint, payload).done(function (response) {
                if (response && response.success && response.data && response.data.html) {
                    openGroupModal(response.data.html);
                } else {
                    setMessage('error', extractError(response));
                }
            }).fail(function (jqXHR) {
                setMessage('error', extractError(jqXHR && jqXHR.responseJSON));
            });
        }

        function save(form) {
            var endpoint = resolveAjaxEndpoint();

            if (!endpoint) {
                return;
            }

            var formData = new window.FormData(form);
            formData.append('action', 'juntaplay_group_edit_save');

            var nonce = resolveAjaxNonce();
            if (nonce) {
                formData.append('nonce', nonce);
            }

            var $form = jQuery(form);
            var $submit = $form.find('button[type="submit"]').first();
            $submit.prop('disabled', true).addClass('is-loading');

            jQuery.ajax({
                url: endpoint,
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false
            }).done(function (response) {
                if (response && response.success) {
                    var successMessage = (response.data && response.data.message) ? response.data.message : __('Grupo atualizado com sucesso.', 'juntaplay');
                    closeModal();
                    window.alert(successMessage);
                    window.location.reload();
                } else {
                    setMessage('error', extractError(response));
                }
            }).fail(function (jqXHR) {
                setMessage('error', extractError(jqXHR && jqXHR.responseJSON));
            }).always(function () {
                $submit.prop('disabled', false).removeClass('is-loading');
            });
        }

        return {
            loadDetail: function (groupId) {
                load('juntaplay_group_detail', groupId);
            },
            loadEdit: function (groupId) {
                load('juntaplay_group_edit_form', groupId);
            },
            close: closeModal,
            save: save,
            render: openGroupModal,
            showLoading: showLoading,
            message: setMessage
        };
    })();

    jQuery(document).on('click', '.juntaplay-group-card__toggle', function (event) {
        event.preventDefault();
        event.stopPropagation();

        var $card = jQuery(this).closest('[data-group-id]');
        var groupId = $card.length ? parseInt($card.data('group-id'), 10) : 0;

        if (!groupId) {
            return;
        }

        jpGroupModal.loadDetail(groupId);
    });

    jQuery(document).on('click', '.juntaplay-group-card__edit', function (event) {
        event.preventDefault();
        event.stopPropagation();

        var groupId = parseInt(jQuery(this).data('group-id'), 10);

        if (!groupId) {
            var $card = jQuery(this).closest('[data-group-id]');
            groupId = $card.length ? parseInt($card.data('group-id'), 10) : 0;
        }

        if (!groupId) {
            return;
        }

        if (window.JP && typeof window.JP.openGroupEditModal === 'function') {
            window.JP.openGroupEditModal(groupId);
            return;
        }

        jpGroupModal.loadEdit(groupId);
    });

    jQuery(document).on('submit', '#juntaplay-group-edit-form', function (event) {
        event.preventDefault();
        if (window.JP && typeof window.JP.submitGroupEditForm === 'function') {
            window.JP.submitGroupEditForm(this);
            return;
        }

        jpGroupModal.save(this);
    });

    jQuery(document).on('click', '.juntaplay-modal__close, #juntaplay-group-modal [data-group-modal-close]', function (event) {
        event.preventDefault();
        jpGroupModal.close();
    });

    jQuery(document).on('click', '#juntaplay-group-modal', function (event) {
        if (event.target === this) {
            jpGroupModal.close();
        }
    });

    jQuery(document).on('keydown.juntaplayGroupModal', function (event) {
        if (event.key === 'Escape' || event.keyCode === 27) {
            var $modal = jQuery('#juntaplay-group-modal');

            if ($modal.hasClass('is-open')) {
                event.preventDefault();
                jpGroupModal.close();
            }
        }
    });

    var jpRestHelper = (function () {
        var $modal = jQuery('#juntaplay-group-modal');
        var root = '';
        var nonce = '';

        if ($modal.length) {
            var modalRoot = $modal.attr('data-rest-root') || $modal.data('restRoot') || '';
            var modalNonce = $modal.attr('data-rest-nonce') || $modal.data('restNonce') || '';

            if (modalRoot) {
                root = modalRoot.toString();
            }

            if (modalNonce) {
                nonce = modalNonce.toString();
            }
        }

        if (!root && window.wpApiSettings && window.wpApiSettings.root) {
            root = window.wpApiSettings.root.toString();
            if (root.slice(-1) !== '/') {
                root += '/';
            }
            if (root.indexOf('juntaplay/v1') === -1) {
                root += 'juntaplay/v1/';
            }
        }

        function normalize(url) {
            if (!url) {
                return '';
            }

            var normalized = url.toString();
            if (normalized.slice(-1) !== '/') {
                normalized += '/';
            }

            return normalized;
        }

        root = normalize(root);

        function endpoint(path) {
            var target = path ? path.toString() : '';

            if (target.charAt(0) === '/') {
                target = target.slice(1);
            }

            return root ? root + target : '';
        }

        function headers(extra) {
            var base = {
                'Accept': 'application/json'
            };

            if (nonce) {
                base['X-WP-Nonce'] = nonce;
            }

            if (extra) {
                base = $.extend({}, base, extra);
            }

            return base;
        }

        function fetchJson(path, options) {
            if (!window.fetch || !root) {
                return Promise.reject(new Error('rest_unavailable'));
            }

            var request = $.extend({
                credentials: 'same-origin'
            }, options || {});

            request.headers = headers(request.headers || null);

            return window.fetch(endpoint(path), request).then(function (response) {
                var parse = function () {
                    return response.json().catch(function () {
                        return {};
                    });
                };

                if (!response.ok) {
                    return parse().then(function (payload) {
                        if (payload && typeof payload === 'object' && payload.message) {
                            throw payload;
                        }

                        throw {
                            message: __('Não foi possível concluir a solicitação.', 'juntaplay')
                        };
                    });
                }

                return parse();
            });
        }

        return {
            root: root,
            nonce: nonce,
            endpoint: endpoint,
            headers: headers,
            fetch: fetchJson
        };
    })();

    window.JP.rest = jpRestHelper;

    (function () {
        var fallbackUsed = false;
        var lastGroupId = 0;

        function safeClass(value, fallback) {
            var token = (value || fallback || '').toString().toLowerCase();
            token = token.replace(/[^a-z0-9_-]+/g, '');
            return token || (fallback || 'info');
        }

        function updateGroupCard(group) {
            if (!group || typeof group !== 'object') {
                return;
            }

            var groupId = parseInt(group.id, 10) || 0;
            if (!groupId) {
                return;
            }

            var $card = jQuery('.juntaplay-group-card[data-group-id="' + groupId + '"]');
            if (!$card.length) {
                return;
            }

            $card.attr('data-group-status', group.status || '');
            if (group.membership_role) {
                $card.attr('data-group-role', group.membership_role);
            }

            var coverUrl = group.cover_url || '';
            if (!coverUrl && group.cover_placeholder) {
                coverUrl = coverPlaceholder || '';
            }

            var $cover = $card.find('.juntaplay-group-card__cover');
            if ($cover.length) {
                $cover.toggleClass('is-placeholder', !!group.cover_placeholder);
                var $img = $cover.find('img');
                if ($img.length) {
                    if (coverUrl) {
                        $img.attr('src', coverUrl);
                    }
                    if (group.cover_alt) {
                        $img.attr('alt', group.cover_alt);
                    }
                }
            }

            var title = group.title || group.service_name || __('Grupo sem nome', 'juntaplay');
            $card.find('.juntaplay-group-card__title').text(title).attr('title', title);

            var $roleBadge = $card.find('.juntaplay-group-card__role-badge');
            if ($roleBadge.length) {
                if (group.role_label) {
                    var roleClass = 'juntaplay-badge--' + safeClass(group.role_tone, 'info');
                    $roleBadge
                        .text(group.role_label)
                        .removeClass(function (index, className) {
                            return (className || '').split(' ').filter(function (name) {
                                return name.indexOf('juntaplay-badge--') === 0;
                            }).join(' ');
                        })
                        .addClass(roleClass);
                } else {
                    $roleBadge.remove();
                }
            }

            var $chips = $card.find('.juntaplay-group-card__chips');
            if ($chips.length) {
                var chipsHtml = '';
                if (group.availability_label) {
                    chipsHtml += '<span class="juntaplay-badge juntaplay-badge--' + safeClass(group.availability_tone, 'info') + '">' + escapeHtml(group.availability_label) + '</span>';
                }
                if (group.status_label) {
                    chipsHtml += '<span class="juntaplay-badge juntaplay-badge--' + safeClass(group.status_tone, 'info') + '">' + escapeHtml(group.status_label) + '</span>';
                }
                $chips.html(chipsHtml);
            }

            var $quickValue = $card.find('.juntaplay-group-card__quick-value');
            if ($quickValue.length) {
                $quickValue.text(group.slots_total_label || '');
            }

            var $quickHint = $card.find('.juntaplay-group-card__quick-hint');
            if ($quickHint.length) {
                var hint = group.slots_available_label || group.slots_total_hint || '';
                if (hint) {
                    $quickHint.text(hint).removeClass('is-hidden');
                } else {
                    $quickHint.text('').addClass('is-hidden');
                }
            }

            var $cta = $card.find('.juntaplay-group-card__cta');
            if ($cta.length) {
                if (!group.cta_label && !group.price_highlight) {
                    $cta.empty();
                } else {
                    var ctaHtml = '';
                    if (group.price_highlight) {
                        ctaHtml += '<span class="juntaplay-group-card__cta-price">' + escapeHtml(group.price_highlight) + '</span>';
                    }
                    if (group.cta_label) {
                        var ctaClasses = ['juntaplay-button'];
                        ctaClasses.push(group.cta_variant === 'primary' ? 'juntaplay-button--primary' : 'juntaplay-button--ghost');
                        if (group.cta_disabled) {
                            ctaClasses.push('is-disabled');
                        }
                        var ctaClassAttr = ctaClasses.join(' ');
                        if (!group.cta_disabled && group.cta_url) {
                            ctaHtml += '<a class="' + ctaClassAttr + '" href="' + escapeHtml(group.cta_url) + '">' + escapeHtml(group.cta_label) + '</a>';
                        } else {
                            ctaHtml += '<button type="button" class="' + ctaClassAttr + '"' + (group.cta_disabled ? ' disabled' : '') + '>' + escapeHtml(group.cta_label) + '</button>';
                        }
                    }
                    $cta.html(ctaHtml);
                }
            }

            var $created = $card.find('.juntaplay-group-card__created');
            if ($created.length) {
                if (group.created_human) {
                    $created.text(group.created_human).show();
                } else {
                    $created.empty().hide();
                }
            }

            var $reviewed = $card.find('.juntaplay-group-card__reviewed');
            if ($reviewed.length) {
                if (group.reviewed_human) {
                    $reviewed.text(group.reviewed_human).show();
                } else {
                    $reviewed.empty().hide();
                }
            }

            var $details = $card.find('.juntaplay-group-card__details');
            if ($details.length) {
                var detailHtml = [];
                if (group.service_name) {
                    var serviceLabel = '<strong>' + escapeHtml(__('Serviço', 'juntaplay')) + ':</strong> ';
                    if (group.service_url) {
                        detailHtml.push('<li>' + serviceLabel + '<a href="' + escapeHtml(group.service_url) + '" target="_blank" rel="noopener noreferrer">' + escapeHtml(group.service_name) + '</a></li>');
                    } else {
                        detailHtml.push('<li>' + serviceLabel + escapeHtml(group.service_name) + '</li>');
                    }
                }

                if (group.price_regular_display) {
                    detailHtml.push('<li><strong>' + escapeHtml(__('Valor do serviço', 'juntaplay')) + ':</strong> ' + escapeHtml(group.price_regular_display) + '</li>');
                }

                if (group.price_promotional_display) {
                    detailHtml.push('<li><strong>' + escapeHtml(__('Oferta promocional', 'juntaplay')) + ':</strong> ' + escapeHtml(group.price_promotional_display) + '</li>');
                }

                if (group.member_price_display) {
                    detailHtml.push('<li><strong>' + escapeHtml(__('Cobrado de cada membro', 'juntaplay')) + ':</strong> ' + escapeHtml(group.member_price_display) + '</li>');
                }

                detailHtml.push('<li><strong>' + escapeHtml(__('Tipo', 'juntaplay')) + ':</strong> ' + escapeHtml(__('Público', 'juntaplay')) + '</li>');

                if (group.category_label) {
                    detailHtml.push('<li><strong>' + escapeHtml(__('Categoria', 'juntaplay')) + ':</strong> ' + escapeHtml(group.category_label) + '</li>');
                }

                if (typeof group.members_count !== 'undefined') {
                    detailHtml.push('<li><strong>' + escapeHtml(__('Participantes', 'juntaplay')) + ':</strong> ' + escapeHtml(String(group.members_count)) + '</li>');
                }

                var poolValue = '';
                if (group.pool_title) {
                    poolValue = escapeHtml(group.pool_title);
                } else {
                    poolValue = '<span class="juntaplay-profile__empty">' + escapeHtml(__('Ainda não vinculada', 'juntaplay')) + '</span>';
                }
                detailHtml.push('<li><strong>' + escapeHtml(__('Grupo', 'juntaplay')) + ':</strong> ' + poolValue + '</li>');

                if (group.support_channel) {
                    detailHtml.push('<li><strong>' + escapeHtml(__('Suporte a Membros', 'juntaplay')) + ':</strong> ' + escapeHtml(group.support_channel) + '</li>');
                }

                if (group.delivery_time) {
                    detailHtml.push('<li><strong>' + escapeHtml(__('Prazo para liberar acesso', 'juntaplay')) + ':</strong> ' + escapeHtml(group.delivery_time) + '</li>');
                }

                if (group.access_method) {
                    detailHtml.push('<li><strong>' + escapeHtml(__('Forma de entrega', 'juntaplay')) + ':</strong> ' + escapeHtml(group.access_method) + '</li>');
                }

                if (group.instant_access_label) {
                    detailHtml.push('<li><strong>' + escapeHtml(__('Acesso instantâneo', 'juntaplay')) + ':</strong> ' + escapeHtml(group.instant_access_label) + '</li>');
                }

                $details.html(detailHtml.join(''));
            }
        }

        function openGroupEditModal(groupId) {
            var targetId = parseInt(groupId, 10) || 0;
            if (!targetId) {
                return;
            }

            lastGroupId = targetId;

            if (!window.fetch || !jpRestHelper.root) {
                if (!fallbackUsed) {
                    fallbackUsed = true;
                    jpGroupModal.loadEdit(targetId);
                }
                return;
            }

            if (jpGroupModal && typeof jpGroupModal.showLoading === 'function') {
                jpGroupModal.showLoading();
            }

            jpRestHelper.fetch('groups/' + targetId, {
                method: 'GET',
                headers: jpRestHelper.headers()
            }).then(function (response) {
                if (!response || !response.form) {
                    throw response || {};
                }

                if (jpGroupModal && typeof jpGroupModal.render === 'function') {
                    jpGroupModal.render(response.form);
                }

                initGroupCoverPicker(jQuery('#juntaplay-group-modal').find('[data-group-cover]'));
            }).catch(function (error) {
                var message = (error && error.message) ? error.message : __('Não foi possível carregar os dados agora. Tente novamente em instantes.', 'juntaplay');
                if (jpGroupModal && typeof jpGroupModal.message === 'function') {
                    jpGroupModal.message('error', message);
                }

                if (!fallbackUsed) {
                    fallbackUsed = true;
                    jpGroupModal.loadEdit(targetId);
                }
            });
        }

        function submitGroupEditForm(form) {
            if (!form) {
                return;
            }

            var $form = jQuery(form);
            var $submit = $form.find('button[type="submit"]').first();

            if ($submit.length) {
                $submit.prop('disabled', true).addClass('is-loading');
            }

            var formData = new window.FormData(form);
            var groupId = parseInt(formData.get('group_id'), 10) || lastGroupId || 0;

            if (!groupId) {
                if ($submit.length) {
                    $submit.prop('disabled', false).removeClass('is-loading');
                }

                if (jpGroupModal && typeof jpGroupModal.message === 'function') {
                    jpGroupModal.message('error', __('Não foi possível identificar o grupo selecionado.', 'juntaplay'));
                }

                return;
            }

            if (!window.fetch || !jpRestHelper.root) {
                if ($submit.length) {
                    $submit.prop('disabled', false).removeClass('is-loading');
                }

                jpGroupModal.save(form);
                return;
            }

            jpRestHelper.fetch('groups/' + groupId, {
                method: 'POST',
                body: formData,
                headers: jpRestHelper.headers()
            }).then(function (response) {
                if (!response || !response.group) {
                    throw response || {};
                }

                if (jpGroupModal) {
                    jpGroupModal.close();
                }

                var successMessage = response.message || __('Grupo atualizado com sucesso.', 'juntaplay');
                window.alert(successMessage);
                updateGroupCard(response.group);
            }).catch(function (error) {
                var message = (error && error.message) ? error.message : __('Não foi possível salvar as alterações do grupo.', 'juntaplay');
                if (jpGroupModal && typeof jpGroupModal.message === 'function') {
                    jpGroupModal.message('error', message);
                } else {
                    window.alert(message);
                }
            }).finally(function () {
                if ($submit.length) {
                    $submit.prop('disabled', false).removeClass('is-loading');
                }
            });
        }

        window.JP.openGroupEditModal = openGroupEditModal;
        window.JP.submitGroupEditForm = submitGroupEditForm;
        window.JP.updateGroupCard = updateGroupCard;
    })();

    (function () {
        var $panel = jQuery('[data-group-diagnostics]');
        if (!$panel.length) {
            return;
        }

        var $toggle = $panel.find('[data-diagnostics-toggle]');
        var $list = $panel.find('[data-diagnostics-list]');
        var $percent = $panel.find('[data-diagnostics-percent]');
        var $progress = $panel.find('[data-diagnostics-progress]');
        var $bar = $panel.find('[data-diagnostics-bar]');

        function runClientChecks() {
            var seenIds = {};
            var hasDuplicate = false;
            document.querySelectorAll('input[name="jp_profile_nonce"]').forEach(function (input) {
                var id = input.getAttribute('id');
                if (!id) {
                    return;
                }
                if (seenIds[id]) {
                    hasDuplicate = true;
                }
                seenIds[id] = true;
            });

            var previewOk = true;
            jQuery('[data-group-cover-preview] img').each(function () {
                if (!this.getAttribute('src')) {
                    previewOk = false;
                }
            });

            var hasMediaLibrary = typeof window.wp !== 'undefined' && !!(window.wp && window.wp.media);
            var singleClickReady = true;
            document.querySelectorAll('[data-group-cover]').forEach(function (node) {
                if (node.getAttribute('data-group-cover-ready') !== '1') {
                    singleClickReady = false;
                }
            });

            var uploadReady = hasMediaLibrary;
            if (uploadReady) {
                uploadReady = false;
                document.querySelectorAll('[data-group-cover]').forEach(function (node) {
                    if (node.getAttribute('data-upload-context') && node.getAttribute('data-media-author')) {
                        uploadReady = true;
                    }
                });
            }

            var backboneOk = !(window.JP && window.JP._backboneErrorReported);

            return {
                edit_modal_opens: typeof window.JP !== 'undefined' && typeof window.JP.openGroupEditModal === 'function',
                single_click_media: hasMediaLibrary && singleClickReady,
                cover_preview_ok: previewOk,
                can_upload_as_subscriber: uploadReady,
                no_duplicate_nonce_ids: !hasDuplicate,
                no_backbone_url_error: backboneOk
            };
        }

        function postChecks(checks) {
            if (!window.fetch || !jpRestHelper.root) {
                return Promise.resolve();
            }

            return jpRestHelper.fetch('diagnostics', {
                method: 'POST',
                headers: jpRestHelper.headers({ 'Content-Type': 'application/json' }),
                body: JSON.stringify({ checks: checks })
            }).catch(function () {
                return undefined;
            });
        }

        function fetchDiagnostics() {
            if (!window.fetch || !jpRestHelper.root) {
                return Promise.reject();
            }

            return jpRestHelper.fetch('diagnostics', {
                method: 'GET',
                headers: jpRestHelper.headers()
            });
        }

        function renderDiagnostics(data) {
            if (!data || !data.checks) {
                return;
            }

            $panel.removeAttr('hidden');

            var percent = typeof data.percent === 'number' ? data.percent : 0;
            $percent.text(percent + '%');

            if ($progress.length) {
                $progress.attr('aria-valuenow', percent);
            }

            if ($bar.length) {
                $bar.css('width', Math.max(0, Math.min(100, percent)) + '%');
            }

            if ($list.length) {
                $list.empty();
                var labels = {
                    edit_modal_opens: __('Modal de edição disponível', 'juntaplay'),
                    single_click_media: __('Biblioteca abre com um clique', 'juntaplay'),
                    cover_preview_ok: __('Pré-visualização da capa ativa', 'juntaplay'),
                    can_upload_as_subscriber: __('Assinante pode enviar capa', 'juntaplay'),
                    no_duplicate_nonce_ids: __('Sem IDs de nonce duplicados', 'juntaplay'),
                    no_backbone_url_error: __('Sem erro de Backbone', 'juntaplay')
                };

                Object.keys(data.checks).forEach(function (key) {
                    var ok = !!data.checks[key];
                    var label = labels[key] || key;
                    var $item = jQuery('<li class="juntaplay-diagnostics__item"></li>');
                    $item.toggleClass('is-success', ok).toggleClass('is-error', !ok);
                    $item.text((ok ? '✓ ' : '✗ ') + label);
                    $list.append($item);
                });
            }
        }

        $toggle.on('click', function (event) {
            event.preventDefault();
            var expanded = $toggle.attr('aria-expanded') === 'true';
            $toggle.attr('aria-expanded', expanded ? 'false' : 'true');
            if (expanded) {
                $list.attr('hidden', 'hidden');
            } else {
                $list.removeAttr('hidden');
            }
        });

        var clientChecks = runClientChecks();
        postChecks(clientChecks).finally(function () {
            fetchDiagnostics().then(function (data) {
                renderDiagnostics(data);
            }).catch(function () {
                $panel.attr('hidden', 'hidden');
            });
        });
    })();

    window.addEventListener('error', function (event) {
        var message = event && event.message ? String(event.message) : '';
        if (message.indexOf('"url" property or function must be specified') === -1) {
            return;
        }

        if (!window.JP) {
            window.JP = {};
        }

        if (window.JP._backboneErrorReported) {
            return;
        }

        window.JP._backboneErrorReported = true;

        if (!window.fetch || !jpRestHelper.root) {
            return;
        }

        jpRestHelper.fetch('diagnostics/flag', {
            method: 'POST',
            headers: jpRestHelper.headers({ 'Content-Type': 'application/json' }),
            body: JSON.stringify({ type: 'backbone_url_error', status: true })
        }).catch(function () {});
    });

    document.addEventListener('DOMContentLoaded', function () {
        var relationshipForms = document.querySelectorAll('.juntaplay-relationship__form');

        if (relationshipForms.length) {
            relationshipForms.forEach(function (form) {
                form.addEventListener('submit', function () {
                    var submitButton = form.querySelector('.juntaplay-button--primary');
                    if (!submitButton) {
                        return;
                    }

                    var loadingLabel = submitButton.getAttribute('data-loading-label');
                    if (!loadingLabel) {
                        loadingLabel = __('Redirecionando...', 'juntaplay');
                        submitButton.setAttribute('data-loading-label', loadingLabel);
                    }

                    if (!submitButton.dataset.originalLabel) {
                        submitButton.dataset.originalLabel = submitButton.textContent || '';
                    }

                    submitButton.textContent = loadingLabel;
                    submitButton.classList.add('is-loading');
                    submitButton.disabled = true;
                });

                form.addEventListener('change', function (event) {
                    var target = event.target;

                    if (!target || target.name !== 'jp_relationship_choice') {
                        return;
                    }

                    var cards = form.querySelectorAll('.juntaplay-relationship-card');

                    cards.forEach(function (card) {
                        card.classList.remove('is-active');
                    });

                    if (!target.checked) {
                        return;
                    }

                    var selectedCard = target.closest('.juntaplay-relationship-card');

                    if (selectedCard) {
                        selectedCard.classList.add('is-active');
                    }
                });
            });
        }

        setTimeout(function () {
            jQuery(document).trigger('juntaplay:group-modal-ready');
        }, 0);
    });

    $(document).on('juntaplay:group-modal-ready', initGroupSuccessState);

    $(function () {
        $('[data-group-view-root]').each(function () {
            initGroupViewRoot($(this));
        });
    });

    $(function () {
        initGroupSuccessState();
    });
})(jQuery);
