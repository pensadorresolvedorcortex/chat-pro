(function () {
    'use strict';

    var root = typeof window !== 'undefined' ? window : {};

    var dashboardConfig = root.dapDashboard || {};
    var chartConfig = dashboardConfig.charts || {};
    var localizedStrings = dashboardConfig.strings || {};
    var restEndpoints = dashboardConfig.rest || {};
    var palette = dashboardConfig.palette || {};
    var topbarConfig = dashboardConfig.topbar || {};
    var topbarUser = topbarConfig.user || {};
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
    var reduceMotionQuery = typeof root.matchMedia === 'function' ? root.matchMedia('(prefers-reduced-motion: reduce)') : null;
    var reduceMotionPreferred = reduceMotionQuery ? reduceMotionQuery.matches : false;
    var autoRotations = [];
    var charts = { sales: null, radial: null, donut: null };

    function addMediaQueryListener(query, handler) {
        if (!query || typeof handler !== 'function') {
            return;
        }

        if (typeof query.addEventListener === 'function') {
            query.addEventListener('change', handler);
        } else if (typeof query.addListener === 'function') {
            query.addListener(handler);
        }
    }

    function updateReduceMotionPreference(event) {
        if (event && typeof event.matches === 'boolean') {
            reduceMotionPreferred = event.matches;
        } else if (reduceMotionQuery) {
            reduceMotionPreferred = reduceMotionQuery.matches;
        }
    }

    function shouldReduceMotion() {
        return !!reduceMotionPreferred;
    }

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

    function getCurrentThemeMode() {
        var body = document.body;

        if (!body) {
            return 'light';
        }

        return body.classList.contains('dap-dark-mode') ? 'dark' : 'light';
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
        var themeMode = getCurrentThemeMode();
        var axisColor = themeMode === 'dark' ? '#cbd5f5' : paletteMuted;
        var legendColor = themeMode === 'dark' ? '#e2e8f0' : paletteDark;
        var gridColor = themeMode === 'dark' ? hexToRgba('#475569', 0.35) : hexToRgba(paletteMuted, 0.25);
        var tooltipTheme = themeMode === 'dark' ? 'dark' : 'light';

        var options = {
            chart: {
                id: 'dap-sales-analytics-chart',
                type: 'area',
                height: 360,
                toolbar: { show: false },
                zoom: { enabled: false },
                animations: {
                    enabled: true,
                    easing: 'easeinout',
                    speed: 800,
                    animateGradually: {
                        enabled: true,
                        delay: 120
                    },
                    dynamicAnimation: {
                        enabled: true,
                        speed: 350
                    }
                },
                foreColor: axisColor,
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
                    colors: legendColor
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
                        colors: axisColor
                    }
                }
            },
            yaxis: {
                labels: {
                    style: {
                        colors: axisColor
                    }
                }
            },
            grid: {
                borderColor: gridColor,
                strokeDashArray: 4,
                padding: {
                    left: 0,
                    right: 0,
                    top: 0,
                    bottom: 0
                }
            },
            fill: buildGradient(initialPalette),
            theme: { mode: themeMode },
            tooltip: {
                shared: true,
                theme: tooltipTheme,
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
        charts.sales = chart;

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
                var currentTheme = getCurrentThemeMode();
                var currentAxis = currentTheme === 'dark' ? '#cbd5f5' : paletteMuted;
                var currentLegend = currentTheme === 'dark' ? '#e2e8f0' : paletteDark;
                var currentGrid = currentTheme === 'dark' ? hexToRgba('#475569', 0.35) : hexToRgba(paletteMuted, 0.25);

                chart.updateOptions({
                    xaxis: {
                        categories: categories[key],
                        labels: {
                            style: {
                                colors: currentAxis
                            }
                        }
                    },
                    colors: nextPalette,
                    fill: buildGradient(nextPalette),
                    stroke: {
                        width: new Array(nextSeries.length).fill(3),
                        curve: 'smooth'
                    },
                    legend: {
                        labels: {
                            colors: currentLegend
                        }
                    },
                    grid: {
                        borderColor: currentGrid
                    },
                    theme: { mode: currentTheme },
                    tooltip: {
                        theme: currentTheme === 'dark' ? 'dark' : 'light'
                    }
                });

                chart.updateSeries(nextSeries);
            });
        });

        if (buttons.length) {
            var rotationContainer = buttons[0].closest('[data-rotate-series]');

            if (rotationContainer) {
                initSeriesAutoRotation(rotationContainer, buttons);
            }
        }

        return chart;
    }

    function initSeriesAutoRotation(container, buttons) {
        if (!container || !buttons || !buttons.length) {
            return null;
        }

        var buttonList = Array.prototype.slice.call(buttons);

        if (buttonList.length <= 1) {
            return null;
        }

        var intervalAttr = parseInt(container.getAttribute('data-rotate-interval'), 10);
        var interval = Number.isFinite(intervalAttr) && intervalAttr >= 4000 ? intervalAttr : 9000;
        var timer = null;
        var resumeTimer = null;
        var hover = false;
        var manualPause = false;
        var activeIndex = buttonList.findIndex(function (button) {
            return button.classList.contains('active');
        });

        if (activeIndex < 0) {
            activeIndex = 0;
        }

        container.setAttribute('data-rotate-active', 'true');

        function clearResumeTimer() {
            if (resumeTimer) {
                clearTimeout(resumeTimer);
                resumeTimer = null;
            }
        }

        function step() {
            activeIndex = (activeIndex + 1) % buttonList.length;
            var target = buttonList[activeIndex];

            if (target && typeof target.click === 'function') {
                target.click();
            }
        }

        function stopTimer() {
            if (timer) {
                clearInterval(timer);
                timer = null;
            }
        }

        function startTimer() {
            if (timer || hover || manualPause || shouldReduceMotion()) {
                return;
            }

            timer = setInterval(step, interval);
        }

        function pauseTemporarily(multiplier) {
            manualPause = true;
            stopTimer();
            clearResumeTimer();

            var delay = interval * (multiplier || 1.5);
            resumeTimer = setTimeout(function () {
                manualPause = false;

                if (!hover && !shouldReduceMotion()) {
                    startTimer();
                }
            }, delay);
        }

        buttonList.forEach(function (button, index) {
            button.addEventListener('click', function () {
                activeIndex = index;
                pauseTemporarily(2);
            });

            button.addEventListener('pointerdown', function () {
                manualPause = true;
                stopTimer();
            });

            button.addEventListener('mousedown', function () {
                manualPause = true;
                stopTimer();
            });

            button.addEventListener('focus', function () {
                manualPause = true;
                stopTimer();
            });

            button.addEventListener('blur', function () {
                clearResumeTimer();

                resumeTimer = setTimeout(function () {
                    manualPause = false;

                    if (!hover && !shouldReduceMotion()) {
                        startTimer();
                    }
                }, interval);
            });
        });

        container.addEventListener('mouseenter', function () {
            hover = true;
            stopTimer();
        });

        container.addEventListener('mouseleave', function () {
            hover = false;

            if (!manualPause && !shouldReduceMotion()) {
                startTimer();
            }
        });

        container.addEventListener('focusin', function () {
            manualPause = true;
            stopTimer();
        });

        container.addEventListener('focusout', function (event) {
            if (container.contains(event.relatedTarget)) {
                return;
            }

            pauseTemporarily(1.25);
        });

        var controller = {
            start: function () {
                manualPause = false;
                clearResumeTimer();
                startTimer();
            },
            stop: function () {
                manualPause = true;
                stopTimer();
                clearResumeTimer();
            },
            onPreferenceChange: function () {
                if (shouldReduceMotion()) {
                    stopTimer();
                } else if (!hover && !manualPause) {
                    startTimer();
                }
            }
        };

        autoRotations.push(controller);

        if (!shouldReduceMotion()) {
            startTimer();
        }

        return controller;
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

        var themeMode = getCurrentThemeMode();
        var radialTrack = hexToRgba(themeMode === 'dark' ? '#1e293b' : paletteMuted, themeMode === 'dark' ? 0.45 : 0.25);
        var radialValueColor = themeMode === 'dark' ? '#e2e8f0' : paletteDark;
        var radialNameColor = themeMode === 'dark' ? '#cbd5f5' : paletteMuted;

        var options = {
            chart: {
                id: 'dap-progress-radial-chart',
                type: 'radialBar',
                height: 320,
                sparkline: { enabled: true },
                animations: {
                    enabled: true,
                    speed: 900,
                    easing: 'easeinout',
                    animateGradually: {
                        enabled: true,
                        delay: 140
                    }
                }
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
            theme: { mode: themeMode },
            plotOptions: {
                radialBar: {
                    hollow: {
                        size: '58%'
                    },
                    track: {
                        background: radialTrack
                    },
                    dataLabels: {
                        name: {
                            show: true,
                            fontSize: '16px',
                            color: radialNameColor
                        },
                        value: {
                            show: true,
                            fontSize: '32px',
                            fontWeight: 600,
                            color: radialValueColor,
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
        charts.radial = chart;
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

        var themeMode = getCurrentThemeMode();
        var donutStroke = themeMode === 'dark' ? '#0f172a' : '#ffffff';

        var options = {
            chart: {
                id: 'dap-email-categories-chart',
                type: 'donut',
                height: 320,
                animations: {
                    enabled: true,
                    speed: 700,
                    easing: 'easeinout',
                    animateGradually: {
                        enabled: true,
                        delay: 120
                    }
                }
            },
            series: donutSeries,
            labels: donutLabels,
            colors: donutColors,
            legend: {
                show: false
            },
            stroke: {
                width: 2,
                colors: [donutStroke]
            },
            states: {
                hover: {
                    filter: { type: 'lighten', value: 0.05 }
                },
                active: {
                    filter: { type: 'darken', value: 0.05 }
                }
            },
            plotOptions: {
                pie: {
                    expandOnClick: false,
                    donut: {
                        size: '72%'
                    }
                }
            },
            theme: { mode: themeMode },
            tooltip: {
                theme: themeMode === 'dark' ? 'dark' : 'light'
            }
        };

        var chart = new ApexCharts(container, options);
        chart.render();
        charts.donut = chart;
        return chart;
    }

    function updateChartsTheme(appliedMode) {
        if (typeof ApexCharts === 'undefined') {
            return;
        }

        var mode = appliedMode === 'dark' ? 'dark' : 'light';
        var axisColor = mode === 'dark' ? '#cbd5f5' : paletteMuted;
        var legendColor = mode === 'dark' ? '#e2e8f0' : paletteDark;
        var gridColor = mode === 'dark' ? hexToRgba('#475569', 0.35) : hexToRgba(paletteMuted, 0.25);
        var tooltipTheme = mode === 'dark' ? 'dark' : 'light';
        var radialTrack = hexToRgba(mode === 'dark' ? '#1e293b' : paletteMuted, mode === 'dark' ? 0.45 : 0.25);
        var radialNameColor = mode === 'dark' ? '#cbd5f5' : paletteMuted;
        var radialValueColor = mode === 'dark' ? '#e2e8f0' : paletteDark;
        var donutStroke = mode === 'dark' ? '#0f172a' : '#ffffff';

        if (charts.sales && typeof charts.sales.updateOptions === 'function') {
            charts.sales.updateOptions({
                chart: {
                    foreColor: axisColor
                },
                theme: { mode: mode },
                xaxis: {
                    labels: {
                        style: { colors: axisColor }
                    }
                },
                yaxis: {
                    labels: {
                        style: { colors: axisColor }
                    }
                },
                legend: {
                    labels: {
                        colors: legendColor
                    }
                },
                grid: {
                    borderColor: gridColor
                },
                tooltip: {
                    theme: tooltipTheme
                }
            }, false, true);
        }

        if (charts.radial && typeof charts.radial.updateOptions === 'function') {
            charts.radial.updateOptions({
                theme: { mode: mode },
                plotOptions: {
                    radialBar: {
                        track: {
                            background: radialTrack
                        },
                        dataLabels: {
                            name: {
                                color: radialNameColor
                            },
                            value: {
                                color: radialValueColor
                            }
                        }
                    }
                }
            }, false, true);
        }

        if (charts.donut && typeof charts.donut.updateOptions === 'function') {
            charts.donut.updateOptions({
                theme: { mode: mode },
                stroke: {
                    colors: [donutStroke]
                },
                tooltip: {
                    theme: tooltipTheme
                }
            }, false, true);
        }
    }

    function initViewportAnimations() {
        var elements = document.querySelectorAll('.dap-admin [data-dap-animate]');

        if (!elements.length) {
            return;
        }

        if (shouldReduceMotion()) {
            elements.forEach(function (element) {
                element.classList.add('is-visible');
            });
            return;
        }

        if (!('IntersectionObserver' in root)) {
            elements.forEach(function (element) {
                element.classList.add('is-visible');
            });
            return;
        }

        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        }, {
            rootMargin: '0px 0px -12% 0px',
            threshold: 0.2
        });

        elements.forEach(function (element) {
            if (element.classList.contains('is-visible')) {
                return;
            }

            observer.observe(element);
        });
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
        updateChartsTheme(applied);

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

    function renderSidebarUserPanel() {
        var wrap = document.getElementById('adminmenuwrap');

        if (!wrap || wrap.querySelector('.dap-sidebar-user') || !topbarUser || !topbarUser.name) {
            return;
        }

        var panel = document.createElement('div');
        panel.className = 'dap-sidebar-user';

        if (topbarUser.avatar) {
            var avatar = document.createElement('img');
            avatar.className = 'dap-sidebar-user__avatar';
            avatar.src = topbarUser.avatar;
            avatar.alt = topbarUser.name;
            panel.appendChild(avatar);
        }

        var nameEl = document.createElement('div');
        nameEl.className = 'dap-sidebar-user__name';
        nameEl.textContent = topbarUser.name;
        panel.appendChild(nameEl);

        if (topbarUser.role) {
            var roleEl = document.createElement('span');
            roleEl.className = 'dap-sidebar-user__role';
            roleEl.textContent = topbarUser.role;
            panel.appendChild(roleEl);
        }

        var actions = document.createElement('div');
        actions.className = 'dap-sidebar-user__actions';

        function createAction(icon, url, label) {
            if (!url) {
                return null;
            }

            var link = document.createElement('a');
            link.href = url;
            link.className = 'dap-sidebar-user__action';
            link.setAttribute('title', label);
            link.setAttribute('aria-label', label);

            var iconEl = document.createElement('i');
            iconEl.className = icon;
            link.appendChild(iconEl);

            return link;
        }

        var editLabel = localizedStrings.editProfile || 'Editar perfil';
        var logoutLabel = localizedStrings.logout || 'Sair';
        var editLink = createAction('ri-user-settings-line', topbarUser.editUrl || topbarUser.profileUrl, editLabel);
        var logoutLink = createAction('ri-shut-down-line', topbarUser.logoutUrl, logoutLabel);

        if (editLink) {
            actions.appendChild(editLink);
        }

        if (logoutLink) {
            actions.appendChild(logoutLink);
        }

        if (actions.childElementCount) {
            panel.appendChild(actions);
        }

        wrap.insertBefore(panel, wrap.firstChild);
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

        var menuBackdrop = document.getElementById('adminmenuback');

        if (menuBackdrop) {
            menuBackdrop.addEventListener('click', function () {
                if (root.innerWidth && root.innerWidth > 960) {
                    return;
                }

                if (!document.body.classList.contains('folded')) {
                    document.body.classList.add('folded');
                    syncMenuToggleState(buttonList);
                }
            });
        }

        syncMenuToggleState(buttonList);
        observeMenuState(buttonList);
    }

    function bindSearchOverlay() {
        var triggers = document.querySelectorAll('[data-action="dap-open-search"]');
        var overlay = document.querySelector('[data-search-overlay]');

        if (!overlay || !triggers.length) {
            return;
        }

        var dismissors = overlay.querySelectorAll('[data-search-dismiss]');
        var input = overlay.querySelector('[data-search-input]');
        var panel = overlay.querySelector('.dap-search-panel');
        var isOpen = false;
        var lastFocused = null;
        var fallbackTimer = null;
        var focusableSelector = 'a[href], button:not([disabled]), input:not([disabled]), textarea:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])';

        function cleanupFallback() {
            if (fallbackTimer) {
                clearTimeout(fallbackTimer);
                fallbackTimer = null;
            }
        }

        function setExpanded(state) {
            triggers.forEach(function (trigger) {
                trigger.setAttribute('aria-expanded', state ? 'true' : 'false');
            });
        }

        function focusFirstField() {
            if (input && typeof input.focus === 'function') {
                try {
                    input.focus({ preventScroll: true });
                } catch (error) {
                    input.focus();
                }
                return;
            }

            if (panel && typeof panel.focus === 'function') {
                try {
                    panel.focus({ preventScroll: true });
                } catch (error) {
                    panel.focus();
                }
            }
        }

        function openOverlay(event) {
            if (event) {
                event.preventDefault();
            }

            if (isOpen) {
                focusFirstField();
                return;
            }

            isOpen = true;
            lastFocused = document.activeElement;
            cleanupFallback();

            overlay.hidden = false;
            overlay.classList.remove('is-leaving');
            overlay.setAttribute('aria-hidden', 'false');
            setExpanded(true);
            document.body.classList.add('dap-search-open');

            requestAnimationFrame(function () {
                overlay.classList.add('is-visible');
            });

            setTimeout(focusFirstField, 60);
        }

        function closeOverlay(event) {
            if (event && event.type !== 'submit') {
                event.preventDefault();
            }

            if (!isOpen) {
                return;
            }

            isOpen = false;
            overlay.classList.remove('is-visible');
            overlay.classList.add('is-leaving');
            overlay.setAttribute('aria-hidden', 'true');
            setExpanded(false);
            document.body.classList.remove('dap-search-open');

            cleanupFallback();
            fallbackTimer = setTimeout(function () {
                if (!isOpen) {
                    overlay.hidden = true;
                    overlay.classList.remove('is-leaving');
                }
                fallbackTimer = null;
            }, 320);

            if (lastFocused && typeof lastFocused.focus === 'function') {
                setTimeout(function () {
                    try {
                        lastFocused.focus({ preventScroll: true });
                    } catch (error) {
                        lastFocused.focus();
                    }
                }, 120);
            }
        }

        function trapFocus(event) {
            if (!isOpen) {
                return;
            }

            if (event.key === 'Escape') {
                closeOverlay(event);
                return;
            }

            if (event.key !== 'Tab') {
                return;
            }

            if (!panel) {
                return;
            }

            var focusable = Array.prototype.slice.call(panel.querySelectorAll(focusableSelector)).filter(function (element) {
                return element && typeof element.focus === 'function' && (element.offsetParent !== null || element === document.activeElement);
            });

            if (!focusable.length) {
                event.preventDefault();
                focusFirstField();
                return;
            }

            var first = focusable[0];
            var last = focusable[focusable.length - 1];
            var active = document.activeElement;

            if (event.shiftKey) {
                if (active === first || !panel.contains(active)) {
                    event.preventDefault();
                    last.focus();
                }
            } else if (active === last) {
                event.preventDefault();
                first.focus();
            }
        }

        setExpanded(false);

        triggers.forEach(function (trigger) {
            trigger.addEventListener('click', openOverlay);
        });

        dismissors.forEach(function (dismissor) {
            dismissor.addEventListener('click', closeOverlay);
        });

        overlay.addEventListener('keydown', trapFocus);

        overlay.addEventListener('transitionend', function (event) {
            if (event.target !== overlay || isOpen) {
                return;
            }

            overlay.hidden = true;
            overlay.classList.remove('is-leaving');
            cleanupFallback();
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && isOpen) {
                closeOverlay(event);
            }
        });
    }

    function applyStoredTheme() {
        var stored = getStoredTheme();
        var fallback = dashboardConfig.themeMode || 'auto';
        var initial = stored || fallback;

        applyThemeMode(initial, false);
    }

    function bootstrap() {
        charts.sales = initSalesAnalyticsChart();
        charts.radial = initProgressRadialChart();
        charts.donut = initEmailCategoriesChart();
        watchSystemPreference();
        applyStoredTheme();
        bindThemeControls();
        bindThemeDropdown();
        bindFullscreenControl();
        bindMenuToggle();
        renderSidebarUserPanel();
        bindSearchOverlay();
        initViewportAnimations();

        addMediaQueryListener(reduceMotionQuery, function (event) {
            updateReduceMotionPreference(event);
            initViewportAnimations();

            autoRotations.forEach(function (controller) {
                if (controller && typeof controller.onPreferenceChange === 'function') {
                    controller.onPreferenceChange();
                }
            });
        });

        if (typeof root.addEventListener === 'function') {
            root.addEventListener('load', initViewportAnimations);
        }

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
