(function ($) {
    'use strict';

    var i18n = (window.wp && window.wp.i18n) ? window.wp.i18n : null;

    function __(text, domain) {
        if (i18n && typeof i18n.__ === 'function') {
            return i18n.__.apply(i18n, arguments);
        }
        return text;
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

    var coverPlaceholder = (window.JuntaPlay && window.JuntaPlay.assets && window.JuntaPlay.assets.groupCoverPlaceholder)
        ? window.JuntaPlay.assets.groupCoverPlaceholder.toString()
        : '';

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
        var cover = pool.thumbnail ? '<div class="juntaplay-pool-card__cover"><img src="' + pool.thumbnail + '" alt="' + pool.title + '" /></div>' : '<div class="juntaplay-pool-card__cover is-placeholder"><span>' + __('Campanha', 'juntaplay') + '</span></div>';

        return '<article class="juntaplay-pool-card">' +
            cover +
            '<div class="juntaplay-pool-card__body">' + badge +
            '<h3 class="juntaplay-pool-card__title"><a href="' + pool.permalink + '">' + pool.title + '</a></h3>' +
            category +
            '<p class="juntaplay-pool-card__excerpt">' + pool.excerpt + '</p>' +
            '<div class="juntaplay-pool-card__price">' + __('Rifa a partir de', 'juntaplay') + ' <strong>' + pool.priceLabel + '</strong></div>' +
            progress +
            quotaMeta +
            '<a class="juntaplay-button juntaplay-button--primary" href="' + pool.permalink + '">' + __('Participar agora', 'juntaplay') + '</a>' +
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
            } else {
                $load.prop('disabled', false).text(state.hasMore ? __('Carregar mais campanhas', 'juntaplay') : __('Tudo carregado', 'juntaplay'));
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
                    $empty.text(response && response.data && response.data.message ? response.data.message : __('Não foi possível carregar as campanhas.', 'juntaplay')).addClass('is-visible');
                    return;
                }

                var data = response.data || {};
                var items = data.items || [];

                if (reset) {
                    $results.empty();
                }

                if (!items.length && reset) {
                    $empty.text(__('Nenhuma campanha encontrada com os filtros selecionados.', 'juntaplay')).addClass('is-visible');
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
                    $load.attr('disabled', true).text(__('Tudo carregado', 'juntaplay'));
                } else {
                    $load.attr('disabled', false).text(__('Carregar mais campanhas', 'juntaplay'));
                }

                updateMeta(data.total || 0);
            }).fail(function () {
                $empty.text(__('Não foi possível carregar as campanhas.', 'juntaplay')).addClass('is-visible');
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

        if (domain) {
            lines.push(domain);
        }
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
            lines.push('Suporte aos membros: ' + data.support);
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

    $(document).on('click', '[data-group-suggestion-apply]', function (event) {
        event.preventDefault();

        var $button = $(this);
        var $card = $button.closest('[data-group-suggestion]');
        var $form = $button.closest('.juntaplay-groups__create').find('form');

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
            $name.val('Grupo ' + service).trigger('input');
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

    function renderGroupCard(item, variant) {
        var mode = (variant || 'spotlight').toString();
        var classes = ['juntaplay-group-card'];
        if (mode === 'spotlight') {
            classes.push('juntaplay-group-card--spotlight');
        }

        var cover = item.coverUrl || coverPlaceholder || '';
        var alt = escapeHtml(item.coverAlt || item.title || item.service || __('Capa do grupo', 'juntaplay'));
        var titleText = item.title || item.service || __('Grupo disponível', 'juntaplay');
        var displayTitle = truncate(titleText, 32);
        var titleAttr = titleText ? ' title="' + escapeHtml(titleText) + '"' : '';
        var title = '<h3 class="juntaplay-group-card__title"' + titleAttr + '>' + escapeHtml(displayTitle) + '</h3>';
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
        var buttonLabel = item.buttonLabel || (availabilityState === 'full' ? __('Aguardando membros', 'juntaplay') : __('Assinar com vagas', 'juntaplay'));
        var buttonClass = 'juntaplay-group-card__cta';
        if (availabilityState === 'full') {
            buttonClass += ' is-disabled';
        }
        var link = item.permalink ? escapeHtml(item.permalink) : '#';

        var meta = '';
        if (slotsBadge || price) {
            meta = '<div class="juntaplay-group-card__meta">' + slotsBadge + price + '</div>';
        }

        return '<article class="' + classes.join(' ') + '" data-group-card>'
            + '<figure class="juntaplay-group-card__cover">'
            + '<img src="' + escapeHtml(cover) + '" alt="' + alt + '" loading="lazy" width="495" height="370" />'
            + '</figure>'
            + '<div class="juntaplay-group-card__body">'
            + '<div class="juntaplay-group-card__heading">'
            + title
            + '</div>'
            + meta
            + '<a class="' + buttonClass + '" href="' + link + '"' + (availabilityState === 'full' ? ' aria-disabled="true"' : '') + '>' + escapeHtml(buttonLabel) + '</a>'
            + '</div>'
            + '</article>';
    }
    function initGroupCoverPicker($wrapper) {
        if (!$wrapper.length || typeof wp === 'undefined' || !wp.media || typeof wp.media !== 'function') {
            return;
        }

        var frame;
        var placeholder = ($wrapper.data('placeholder') || '').toString();
        var $input = $wrapper.find('[data-group-cover-input]');
        var $preview = $wrapper.find('[data-group-cover-preview]');
        var $remove = $wrapper.find('[data-group-cover-remove]');

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

            if (frame) {
                frame.open();
                return;
            }

            frame = wp.media({
                title: __('Escolher capa do grupo', 'juntaplay'),
                button: { text: __('Usar esta imagem', 'juntaplay') },
                multiple: false
            });

            frame.on('select', function () {
                var attachment = frame.state().get('selection').first();
                if (!attachment) {
                    return;
                }

                attachment = attachment.toJSON();
                setCover(attachment.id || '', attachment.url || placeholder || coverPlaceholder || '');
            });

            frame.open();
        });

        $wrapper.on('click', '[data-group-cover-remove]', function (event) {
            event.preventDefault();
            setCover('', '');
        });
    }

    function initGroupsDirectory($root) {
        if (!$root.length || typeof window.JuntaPlay === 'undefined') {
            return;
        }

        var state = {
            page: 1,
            perPage: parseInt($root.data('perPage'), 10) || 9,
            loading: false,
            hasMore: true,
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

        var $list = $root.find('[data-jp-groups-list]');
        var $empty = $root.find('[data-jp-groups-empty]');
        var $more = $root.find('[data-jp-groups-more]');
        var $total = $root.find('[data-jp-groups-total]');
        var $filters = $root.find('[data-jp-groups-filters]');

        function setLoading(isLoading) {
            state.loading = isLoading;
            $root.toggleClass('is-loading', isLoading);

            if ($more.length) {
                if (isLoading) {
                    $more.prop('disabled', true).text(__('Carregando...', 'juntaplay'));
                } else {
                    $more.prop('disabled', false).toggle(state.hasMore).text(state.hasMore ? __('Carregar mais grupos', 'juntaplay') : __('Todos os grupos carregados', 'juntaplay'));
                }
            }
        }

        function updateTotal(total) {
            if (!$total.length) {
                return;
            }

            total = parseInt(total, 10) || 0;
            $total.text(total ? _n('%d grupo encontrado', '%d grupos encontrados', total, 'juntaplay').replace('%d', total) : __('Nenhum grupo encontrado', 'juntaplay'));
        }

        function render(items, reset) {
            if (reset) {
                $list.empty();
            }

            if (items && items.length) {
                var html = items.map(function (item) {
                    return renderGroupCard(item, 'spotlight');
                }).join('');
                $list.append(html);
                $empty.attr('hidden', 'hidden');
            } else if (reset) {
                $empty.removeAttr('hidden');
            }
        }

        function fetch(reset) {
            if (state.loading) {
                return;
            }

            setLoading(true);

            if (reset) {
                state.page = 1;
                state.hasMore = true;
            }

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
                render(data.items || [], reset);
                updateTotal(data.total || 0);
                var currentPage = parseInt(data.page, 10) || 1;
                var totalPages = parseInt(data.pages, 10) || 1;

                state.page = currentPage + 1;
                state.hasMore = currentPage < totalPages;

                if ($more.length) {
                    if (state.hasMore) {
                        $more.removeAttr('hidden').prop('disabled', false).text(__('Carregar mais grupos', 'juntaplay'));
                    } else {
                        $more.attr('hidden', 'hidden').prop('disabled', true).text(__('Todos os grupos carregados', 'juntaplay'));
                    }
                }
            }).fail(function () {
                if (reset) {
                    $list.empty();
                    $empty.removeAttr('hidden').text(__('Não foi possível carregar os grupos agora.', 'juntaplay'));
                    if ($more.length) {
                        $more.attr('hidden', 'hidden').prop('disabled', true);
                    }
                }
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
                fetch(true);
            });

            $filters.on('change', 'select[name="category"]', function () {
                state.category = $(this).val() || '';
                fetch(true);
            });

            $filters.on('change', 'select[name="orderby"]', function () {
                var $selected = $(this).find(':selected');
                state.orderby = $(this).val() || 'created';
                state.order = ($selected.data('order') || 'desc').toString();
                fetch(true);
            });

            $filters.on('change', 'input[name="instant"]', function () {
                state.instant = $(this).is(':checked') ? '1' : '';
                fetch(true);
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
                fetch(true);
            });
        }

        if ($more.length) {
            $more.on('click', function () {
                if (!state.hasMore || state.loading) {
                    return;
                }

                fetch(false);
            });
        }

        fetch(true);
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

        function showNotice(type, message) {
            var $notice = $('<div/>')
                .addClass('juntaplay-wallet__alert juntaplay-wallet__alert--' + type)
                .text(message || '');
            $root.find('.juntaplay-wallet__withdraw .juntaplay-wallet__alert').remove();
            $root.find('.juntaplay-wallet__withdraw').prepend($notice);
        }

        function renderTransaction(item) {
            var id = item.id || 0;
            var title = item.type_label || '';
            var status = item.status_label || '';
            var amount = item.amount_formatted || item.amount || '';
            var time = item.time || '';
            var reference = item.reference || '';

            var meta = time;
            if (reference) {
                meta += ' · ' + reference;
            }

            return [
                '<li class="juntaplay-wallet__item" data-transaction="' + id + '">',
                '  <div class="juntaplay-wallet__item-main">',
                '    <strong class="juntaplay-wallet__item-title">' + title + '</strong>',
                '    <span class="juntaplay-wallet__item-meta">' + meta + '</span>',
                '  </div>',
                '  <div class="juntaplay-wallet__item-side">',
                '    <span class="juntaplay-wallet__item-status">' + status + '</span>',
                '    <span class="juntaplay-wallet__item-amount">' + amount + '</span>',
                '  </div>',
                '</li>'
            ].join('');
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
                return;
            }

            isLoading = true;
            $.getJSON(window.JuntaPlay.ajax, {
                action: 'juntaplay_credit_transactions',
                nonce: window.JuntaPlay.nonce,
                page: current + 1
            }).done(function (response) {
                if (!response || !response.success) {
                    return;
                }

                if (response.data && response.data.items) {
                    var items = response.data.items.map(renderTransaction).join('');
                    $root.find('.juntaplay-wallet__list').append(items);
                }

                if (response.data && typeof response.data.page !== 'undefined') {
                    $root.attr('data-page', response.data.page);
                }

                if (response.data && typeof response.data.pages !== 'undefined') {
                    $root.attr('data-pages', response.data.pages);
                    if (response.data.page >= response.data.pages) {
                        $loadMore.remove();
                    }
                }

                if (response.data && typeof response.data.total !== 'undefined') {
                    $root.attr('data-total', response.data.total);
                    $root.find('[data-jp-credit-total]').text(response.data.total + ' ' + (response.data.total === 1 ? __('movimento', 'juntaplay') : __('movimentos', 'juntaplay')));
                }
            }).always(function () {
                isLoading = false;
            });
        });

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

    function initNotifications() {
        if (typeof window.JuntaPlay === 'undefined') {
            return;
        }

        var $bell = $('[data-jp-notifications]');
        if (!$bell.length) {
            return;
        }

        var $panel = $('[data-jp-notifications-panel]');
        var $list = $panel.find('[data-jp-notifications-list]');
        var isLoaded = false;
        var isLoading = false;

        function setState(open) {
            $bell.attr('aria-expanded', open ? 'true' : 'false');
            $panel.attr('aria-hidden', open ? 'false' : 'true');
        }

        function togglePanel(force) {
            var open = typeof force === 'boolean' ? force : !$panel.hasClass('is-open');
            if (open) {
                $panel.addClass('is-open');
                $bell.addClass('is-active');
                setState(true);
                if (!isLoaded) {
                    fetchNotifications();
                }
            } else {
                $panel.removeClass('is-open');
                $bell.removeClass('is-active');
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
                        $list.html('<li class="juntaplay-notifications__empty">' + __('Nenhuma notificação por enquanto.', 'juntaplay') + '</li>');
                    } else {
                        var html = response.data.items.map(function (item) {
                            var title = item.title || '';
                            var message = item.message || '';
                            var time = item.time || '';
                            var href = item.action_url || '';
                            var content = '<span class="juntaplay-notifications__item-title">' + title + '</span>' +
                                '<span class="juntaplay-notifications__item-message">' + message + '</span>' +
                                '<span class="juntaplay-notifications__item-time">' + time + '</span>';

                            if (href) {
                                return '<li><a class="juntaplay-notifications__item" data-notification-id="' + item.id + '" href="' + href + '">' + content + '</a></li>';
                            }

                            return '<li><span class="juntaplay-notifications__item" data-notification-id="' + item.id + '">' + content + '</span></li>';
                        }).join('');

                        $list.html(html);
                    }
                }

                if (response.data && typeof response.data.unread !== 'undefined') {
                    var unread = parseInt(response.data.unread, 10) || 0;
                    if (unread > 0) {
                        $bell.attr('data-count', unread);
                    } else {
                        $bell.removeAttr('data-count');
                    }
                }
            }).always(function () {
                isLoading = false;
            });
        }

        function markNotification(id) {
            $.post(window.JuntaPlay.ajax, {
                action: 'juntaplay_notifications_mark',
                nonce: window.JuntaPlay.nonce,
                ids: [id]
            }, null, 'json').done(function (response) {
                if (response && response.data && typeof response.data.unread !== 'undefined') {
                    var unread = parseInt(response.data.unread, 10) || 0;
                    if (unread > 0) {
                        $bell.attr('data-count', unread);
                    } else {
                        $bell.removeAttr('data-count');
                    }
                }
                isLoaded = false;
            });
        }

        $bell.on('click', function (event) {
            event.preventDefault();
            togglePanel();
        });

        $(document).on('click', function (event) {
            if (!$panel.hasClass('is-open')) {
                return;
            }

            if ($(event.target).closest('[data-jp-notifications]').length || $(event.target).closest('[data-jp-notifications-panel]').length) {
                return;
            }

            togglePanel(false);
        });

        $panel.on('click', '[data-notification-id]', function () {
            var id = $(this).data('notification-id');
            if (id) {
                markNotification(id);
                var $item = $(this).closest('li');
                if ($item.length) {
                    $item.remove();
                    if (!$list.children().length) {
                        $list.html('<li class="juntaplay-notifications__empty">' + __('Nenhuma notificação por enquanto.', 'juntaplay') + '</li>');
                    }
                }
            }
        });

        $panel.on('click', '[data-jp-notifications-close]', function (event) {
            event.preventDefault();
            togglePanel(false);
        });

        setState(false);
    }
})(jQuery);
