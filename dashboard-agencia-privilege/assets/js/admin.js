(function () {
    'use strict';

    var root = typeof window !== 'undefined' ? window : {};

    var dashboardConfig = root.dapDashboard || {};
    var chartConfig = dashboardConfig.charts || {};
    var localizedStrings = dashboardConfig.strings || {};
    var restEndpoints = dashboardConfig.rest || {};
    var palette = dashboardConfig.palette || {};
    var palettePrimary = palette.primary || '#7366ff';
    var paletteSecondary = palette.secondary || '#2bb0f8';
    var paletteAccent = palette.accent || paletteSecondary;
    var paletteInfo = palette.info || paletteSecondary;
    var paletteSuccess = palette.success || '#1ccab8';
    var paletteWarning = palette.warning || '#f7b84b';
    var paletteDanger = palette.danger || '#f1556c';
    var paletteMuted = palette.muted || '#94a3b8';
    var paletteDark = palette.dark || '#0f172a';
    var themeStorageKey = 'dap-dashboard-theme';
    var themePreference = 'auto';
    var systemThemeQuery = typeof root.matchMedia === 'function' ? root.matchMedia('(prefers-color-scheme: dark)') : null;

    function hexToRgba(hex, alpha) {
        if (!hex) {
            return 'rgba(148, 163, 184, ' + (typeof alpha === 'number' ? alpha : 1) + ')';
        }

        var sanitized = String(hex).replace('#', '');

        if (sanitized.length === 3) {
            sanitized = sanitized[0] + sanitized[0] + sanitized[1] + sanitized[1] + sanitized[2] + sanitized[2];
        }

        var bigint = parseInt(sanitized, 16);

        if (Number.isNaN(bigint)) {
            return 'rgba(148, 163, 184, ' + (typeof alpha === 'number' ? alpha : 1) + ')';
        }

        var r = (bigint >> 16) & 255;
        var g = (bigint >> 8) & 255;
        var b = bigint & 255;

        var normalisedAlpha = typeof alpha === 'number' ? alpha : 1;

        return 'rgba(' + r + ', ' + g + ', ' + b + ', ' + normalisedAlpha + ')';
    }

    function initSalesAnalyticsChart() {
        if (typeof ApexCharts === 'undefined') {
            return null;
        }

        var container = document.querySelector('#dap-sales-analytics');
        if (!container) {
            return null;
        }

        var strings = (root.dapDashboard && root.dapDashboard.strings) || {};
        var statsData = chartConfig.salesAnalytics || chartConfig.projectStatistics || {};

        var defaultSeries = {
            monthly: [38, 42, 48, 51, 57, 63, 69, 74, 79, 84, 88, 93],
            weekly: [9, 11, 10, 14, 13, 16, 12],
            today: [1, 2, 2, 3, 3, 4]
        };

        var defaultComparison = {
            monthly: [24, 28, 30, 33, 36, 40, 43, 45, 47, 50, 53, 55],
            weekly: [6, 7, 6, 8, 7, 9, 8],
            today: [1, 1, 1, 2, 2, 2]
        };

        var defaultCategories = {
            monthly: ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'],
            weekly: ['Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb', 'Dom'],
            today: ['08h', '10h', '12h', '14h', '16h', '18h']
        };

        function ensureArray(source, fallback) {
            if (Array.isArray(source) && source.length) {
                return source.slice();
            }

            if (Array.isArray(fallback)) {
                return fallback.slice();
            }

            return [];
        }

        function normaliseToLength(data, length, fallback) {
            var reference = ensureArray(data, fallback);

            if (typeof length === 'number' && length > 0) {
                reference = reference.slice(0, length);

                while (reference.length < length) {
                    reference.push(0);
                }
            }

            return reference;
        }

        var rawSeries = statsData.series || {};
        var rawComparison = statsData.comparison_series || {};
        var rawCategories = statsData.categories || {};
        var labelMap = statsData.labels || {};
        var colorMap = statsData.colors || {};

        var categories = {
            monthly: ensureArray(rawCategories.monthly, defaultCategories.monthly),
            weekly: ensureArray(rawCategories.weekly, defaultCategories.weekly),
            today: ensureArray(rawCategories.today, defaultCategories.today)
        };

        var primarySeries = {
            monthly: normaliseToLength(rawSeries.monthly, categories.monthly.length, defaultSeries.monthly),
            weekly: normaliseToLength(rawSeries.weekly, categories.weekly.length, defaultSeries.weekly),
            today: normaliseToLength(rawSeries.today, categories.today.length, defaultSeries.today)
        };

        var comparisonSeries = {
            monthly: normaliseToLength(rawComparison.monthly, categories.monthly.length, defaultComparison.monthly),
            weekly: normaliseToLength(rawComparison.weekly, categories.weekly.length, defaultComparison.weekly),
            today: normaliseToLength(rawComparison.today, categories.today.length, defaultComparison.today)
        };

        function hasData(values) {
            return Array.isArray(values) && values.some(function (value) {
                return Number(value) !== 0;
            });
        }

        var primaryLabel = labelMap.primary || strings.primarySeries || strings.sales || strings.projects || 'Projetos';
        var comparisonLabel = labelMap.comparison || strings.comparisonSeries || strings.engagement || 'Interações';
        var primaryColor = colorMap.primary || palettePrimary;
        var comparisonColor = colorMap.comparison || paletteInfo;

        function buildSeries(key) {
            var dataset = [
                {
                    name: primaryLabel,
                    data: primarySeries[key]
                }
            ];

            if (comparisonSeries[key] && comparisonSeries[key].length) {
                dataset.push({
                    name: comparisonLabel,
                    data: comparisonSeries[key]
                });
            }

            return dataset;
        }

        function buildPalette(seriesList) {
            var palette = [primaryColor];

            if (seriesList.length > 1 || hasData(comparisonSeries.monthly)) {
                palette.push(comparisonColor);
            }

            return palette;
        }

        function buildGradient(seriesPalette) {
            var gradientTargets = [];

            if (Array.isArray(seriesPalette)) {
                gradientTargets = seriesPalette.map(function (color, index) {
                    if (index === 0) {
                        return paletteAccent || paletteSecondary || color;
                    }

                    if (index === 1) {
                        return paletteSecondary || paletteInfo || color;
                    }

                    return color;
                });
            }

            return {
                type: 'gradient',
                gradient: {
                    shadeIntensity: 1,
                    opacityFrom: 0.45,
                    opacityTo: 0.05,
                    stops: [0, 80, 100],
                    gradientToColors: gradientTargets.length ? gradientTargets : seriesPalette
                }
            };
        }

        var activeKey = 'monthly';
        var initialSeries = buildSeries(activeKey);
        var initialPalette = buildPalette(initialSeries);

        var options = {
            chart: {
                type: 'area',
                height: 360,
                toolbar: { show: false },
                foreColor: paletteMuted,
                fontFamily: 'Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif'
            },
            stroke: {
                width: new Array(initialSeries.length).fill(3),
                curve: 'smooth'
            },
            legend: {
                show: true,
                position: 'top',
                horizontalAlign: 'left',
                fontSize: '13px',
                fontWeight: 600,
                labels: {
                    colors: paletteDark
                },
                markers: {
                    width: 14,
                    height: 14,
                    radius: 10
                },
                itemMargin: {
                    horizontal: 12
                }
            },
            markers: {
                size: 4,
                hover: {
                    size: 6
                }
            },
            colors: initialPalette,
            series: initialSeries,
            dataLabels: { enabled: false },
            xaxis: {
                categories: categories[activeKey],
                axisTicks: { show: false },
                axisBorder: { show: false },
                labels: {
                    style: {
                        colors: paletteMuted
                    }
                }
            },
            yaxis: {
                labels: {
                    style: {
                        colors: paletteMuted
                    }
                }
            },
            grid: {
                borderColor: hexToRgba(paletteMuted, 0.25),
                strokeDashArray: 4
            },
            fill: buildGradient(initialPalette),
            tooltip: {
                shared: true,
                theme: 'light',
                intersect: false,
                y: {
                    formatter: function (value) {
                        if (typeof value !== 'number') {
                            return value;
                        }

                        return Math.round(value);
                    }
                }
            }
        };

        var chart = new ApexCharts(container, options);
        chart.render();

        var buttons = document.querySelectorAll('.dap-sales-analytics-filter .btn');
        buttons.forEach(function (button) {
            button.addEventListener('click', function () {
                buttons.forEach(function (other) {
                    other.classList.remove('active');
                    other.classList.remove('btn-soft-primary');
                    other.classList.add('btn-soft-secondary');
                });

                button.classList.add('active');
                button.classList.remove('btn-soft-secondary');
                button.classList.add('btn-soft-primary');

                var key = button.getAttribute('data-series');
                if (!key || !categories[key]) {
                    return;
                }

                activeKey = key;
                var nextSeries = buildSeries(key);
                var nextPalette = buildPalette(nextSeries);

                chart.updateOptions({
                    xaxis: {
                        categories: categories[key]
                    },
                    colors: nextPalette,
                    fill: buildGradient(nextPalette),
                    stroke: {
                        width: new Array(nextSeries.length).fill(3),
                        curve: 'smooth'
                    }
                });

                chart.updateSeries(nextSeries);
            });
        });

        return chart;
    }

    function initProgressRadialChart() {
        var strings = (root.dapDashboard && root.dapDashboard.strings) || {};
        var progressLabel = strings.onProgress || 'Em andamento';
        var radialConfig = chartConfig.radialProgress || {};
        var radialValue = typeof radialConfig.value === 'number' ? radialConfig.value : 72;

        if (typeof ApexCharts === 'undefined') {
            return null;
        }

        var container = document.querySelector('#dap-progress-radial');
        if (!container) {
            return null;
        }

        var options = {
            chart: {
                type: 'radialBar',
                height: 320,
                sparkline: { enabled: true }
            },
            series: [radialValue],
            labels: [progressLabel],
            colors: [palettePrimary],
            fill: {
                type: 'gradient',
                gradient: {
                    shade: 'light',
                    shadeIntensity: 0.45,
                    gradientToColors: [paletteAccent || paletteSecondary || palettePrimary],
                    inverseColors: false,
                    opacityFrom: 1,
                    opacityTo: 1,
                    stops: [0, 100]
                }
            },
            plotOptions: {
                radialBar: {
                    hollow: {
                        size: '58%'
                    },
                    track: {
                        background: hexToRgba(paletteMuted, 0.25)
                    },
                    dataLabels: {
                        name: {
                            show: true,
                            fontSize: '16px',
                            color: paletteMuted
                        },
                        value: {
                            show: true,
                            fontSize: '32px',
                            fontWeight: 600,
                            color: paletteDark,
                            formatter: function (val) {
                                return val + '%';
                            }
                        }
                    }
                }
            }
        };

        var chart = new ApexCharts(container, options);
        chart.render();
        return chart;
    }

    function initEmailCategoriesChart() {
        if (typeof ApexCharts === 'undefined') {
            return null;
        }

        var container = document.querySelector('#dap-email-categories');
        if (!container) {
            return null;
        }

        var emailData = chartConfig.emailCategories || {};
        var donutSeries = Array.isArray(emailData.series) && emailData.series.length ? emailData.series : [45, 28, 19, 8];
        var donutLabels = Array.isArray(emailData.labels) && emailData.labels.length ? emailData.labels : ['Campanhas sazonais', 'Fluxos automáticos', 'Nutrição leads', 'Transacionais'];
        var donutColors = Array.isArray(emailData.colors) && emailData.colors.length ? emailData.colors : [palettePrimary, paletteSuccess, paletteWarning, paletteInfo];

        var options = {
            chart: {
                type: 'donut',
                height: 320
            },
            series: donutSeries,
            labels: donutLabels,
            colors: donutColors,
            legend: {
                show: false
            },
            stroke: {
                width: 0
            },
            plotOptions: {
                pie: {
                    expandOnClick: false,
                    donut: {
                        size: '72%'
                    }
                }
            }
        };

        var chart = new ApexCharts(container, options);
        chart.render();
        return chart;
    }

    function getStoredTheme() {
        try {
            return root.localStorage ? root.localStorage.getItem(themeStorageKey) : null;
        } catch (error) {
            return null;
        }
    }

    function setStoredTheme(mode) {
        try {
            if (root.localStorage) {
                root.localStorage.setItem(themeStorageKey, mode);
            }
        } catch (error) {
            // noop
        }
    }

    function normalizeThemePreference(mode) {
        var normalized = (mode || '').toString().toLowerCase();

        if (normalized !== 'light' && normalized !== 'dark' && normalized !== 'auto') {
            normalized = 'auto';
        }

        return normalized;
    }

    function getThemeLabel(mode) {
        var map = {
            light: localizedStrings.themeLight || 'Modo claro',
            dark: localizedStrings.themeDark || 'Modo escuro',
            auto: localizedStrings.themeAuto || 'Modo automático'
        };

        return map[mode] || map.auto;
    }

    function sendThemePreference(mode) {
        if (!restEndpoints || !restEndpoints.theme || !dashboardConfig || !dashboardConfig.nonce) {
            return;
        }

        try {
            root.fetch(restEndpoints.theme, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': dashboardConfig.nonce
                },
                body: JSON.stringify({ mode: mode })
            }).catch(function () {});
        } catch (error) {
            // noop
        }
    }

    function syncThemeControls(appliedMode, preferenceMode) {
        var controls = document.querySelectorAll('[data-action="dap-toggle-theme"]');
        controls.forEach(function (control) {
            var isDark = appliedMode === 'dark';

            if (control.tagName === 'INPUT') {
                control.checked = isDark;
            } else {
                control.setAttribute('aria-pressed', isDark ? 'true' : 'false');
            }
        });

        var options = document.querySelectorAll('[data-theme-mode]');
        options.forEach(function (option) {
            var targetMode = option.getAttribute('data-theme-mode');
            var isActive = targetMode === preferenceMode;
            option.classList.toggle('active', isActive);
            option.setAttribute('aria-pressed', isActive ? 'true' : 'false');

            var check = option.querySelector('[data-theme-check]');
            if (check) {
                check.classList.toggle('d-none', !isActive);
            }
        });

        var trigger = document.querySelector('[data-theme-current]');

        if (trigger) {
            var icon = trigger.querySelector('[data-theme-icon]');
            var labelNode = trigger.querySelector('[data-theme-label]');
            var iconClass = preferenceMode === 'auto' ? 'ri-computer-line' : (appliedMode === 'dark' ? 'ri-moon-line' : 'ri-sun-line');
            var label = getThemeLabel(preferenceMode);

            trigger.setAttribute('data-theme-preference', preferenceMode);
            trigger.setAttribute('aria-label', label);

            if (icon) {
                icon.setAttribute('class', iconClass);
            }

            if (labelNode) {
                labelNode.textContent = label;
            }
        }
    }

    function applyThemeMode(mode, persist) {
        var body = document.body;
        var preference = normalizeThemePreference(mode);
        var applied = preference;

        themePreference = preference;

        if (preference === 'auto') {
            applied = systemThemeQuery && systemThemeQuery.matches ? 'dark' : 'light';
        }

        if (applied === 'dark') {
            body.classList.add('dap-dark-mode');
        } else {
            body.classList.remove('dap-dark-mode');
        }

        body.setAttribute('data-dap-theme', applied);
        body.setAttribute('data-dap-theme-preference', preference);

        syncThemeControls(applied, preference);

        if (persist) {
            setStoredTheme(preference);
            sendThemePreference(preference);
        }
    }

    function toggleThemeMode() {
        var isDark = document.body.classList.contains('dap-dark-mode');
        applyThemeMode(isDark ? 'light' : 'dark', true);
    }

    function bindThemeControls() {
        var controls = document.querySelectorAll('[data-action="dap-toggle-theme"]');

        if (!controls.length) {
            return;
        }

        controls.forEach(function (control) {
            if (control.tagName === 'INPUT') {
                control.addEventListener('change', function () {
                    applyThemeMode(control.checked ? 'dark' : 'light', true);
                });
            } else {
                control.addEventListener('click', function (event) {
                    event.preventDefault();
                    toggleThemeMode();
                });
            }
        });
    }

    function bindThemeDropdown() {
        var options = document.querySelectorAll('[data-theme-mode]');

        if (!options.length) {
            return;
        }

        options.forEach(function (option) {
            option.addEventListener('click', function (event) {
                event.preventDefault();
                var mode = option.getAttribute('data-theme-mode') || 'auto';
                applyThemeMode(mode, true);
            });
        });
    }

    function handleSystemPreferenceChange() {
        if (themePreference === 'auto') {
            applyThemeMode('auto', false);
        }
    }

    function watchSystemPreference() {
        if (!systemThemeQuery) {
            return;
        }

        if (typeof systemThemeQuery.addEventListener === 'function') {
            systemThemeQuery.addEventListener('change', handleSystemPreferenceChange);
        } else if (typeof systemThemeQuery.addListener === 'function') {
            systemThemeQuery.addListener(handleSystemPreferenceChange);
        }
    }

    function bindFullscreenControl() {
        var buttons = document.querySelectorAll('[data-action="dap-toggle-fullscreen"]');

        if (!buttons.length) {
            return;
        }

        buttons.forEach(function (button) {
            button.addEventListener('click', function (event) {
                event.preventDefault();

                if (!document.fullscreenElement && document.documentElement.requestFullscreen) {
                    document.documentElement.requestFullscreen().catch(function () {});
                } else if (document.exitFullscreen) {
                    document.exitFullscreen().catch(function () {});
                }
            });
        });
    }

    function syncMenuToggleState(buttons) {
        var folded = document.body.classList.contains('folded');

        buttons.forEach(function (button) {
            button.setAttribute('aria-pressed', folded ? 'true' : 'false');

            if (folded) {
                button.classList.add('active');
            } else {
                button.classList.remove('active');
            }
        });
    }

    function observeMenuState(buttons) {
        if (!('MutationObserver' in root)) {
            return;
        }

        var observer = new MutationObserver(function () {
            syncMenuToggleState(buttons);
        });

        observer.observe(document.body, { attributes: true, attributeFilter: ['class'] });
    }

    function bindMenuToggle() {
        var buttons = document.querySelectorAll('[data-action="dap-toggle-menu"]');

        if (!buttons.length) {
            return;
        }

        var buttonList = Array.prototype.slice.call(buttons);

        buttonList.forEach(function (button) {
            button.addEventListener('click', function (event) {
                event.preventDefault();

                var folded = document.body.classList.toggle('folded');

                if (typeof root.setUserSetting === 'function') {
                    root.setUserSetting('mfold', folded ? 'f' : 'o');
                }

                syncMenuToggleState(buttonList);
            });
        });

        var collapseToggle = document.getElementById('collapse-menu');

        if (collapseToggle) {
            collapseToggle.addEventListener('click', function () {
                var delay = (root && typeof root.setTimeout === 'function') ? root.setTimeout : setTimeout;

                delay(function () {
                    syncMenuToggleState(buttonList);
                }, 60);
            });
        }

        syncMenuToggleState(buttonList);
        observeMenuState(buttonList);
    }

    function applyStoredTheme() {
        var stored = getStoredTheme();
        var fallback = dashboardConfig.themeMode || 'auto';
        var initial = stored || fallback;

        applyThemeMode(initial, false);
    }

    function bootstrap() {
        initSalesAnalyticsChart();
        initProgressRadialChart();
        initEmailCategoriesChart();
        watchSystemPreference();
        applyStoredTheme();
        bindThemeControls();
        bindThemeDropdown();
        bindFullscreenControl();
        bindMenuToggle();

        if (root.dapDashboard && root.dapDashboard.hasUboldAssets) {
            document.body.classList.add('dap-has-ubold-assets');
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bootstrap);
    } else {
        bootstrap();
    }
})();
