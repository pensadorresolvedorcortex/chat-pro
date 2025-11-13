(function () {
    'use strict';

    var root = typeof window !== 'undefined' ? window : {};

    var chartConfig = (root.dapDashboard && root.dapDashboard.charts) || {};

    function initProjectStatisticsChart() {
        var strings = (root.dapDashboard && root.dapDashboard.strings) || {};
        var seriesLabel = strings.projects || 'Projects';
        var statsData = chartConfig.projectStatistics || {};
        var defaultSeries = {
            monthly: [38, 42, 48, 51, 57, 63, 69, 74, 79, 84, 88, 93],
            weekly: [9, 11, 10, 14, 13, 16, 12],
            today: [1, 2, 2, 3, 3, 4]
        };
        var defaultCategories = {
            monthly: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            weekly: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            today: ['08h', '10h', '12h', '14h', '16h', '18h']
        };
        var seriesMap = Object.assign({}, defaultSeries, statsData.series || {});
        var categories = Object.assign({}, defaultCategories, statsData.categories || {});

        if (typeof ApexCharts === 'undefined') {
            return null;
        }

        var container = document.querySelector('#dap-project-statistics');
        if (!container) {
            return null;
        }

        var options = {
            chart: {
                type: 'area',
                height: 360,
                toolbar: { show: false }
            },
            stroke: {
                width: 3,
                curve: 'smooth'
            },
            series: [
                {
                    name: seriesLabel,
                    data: seriesMap.monthly
                }
            ],
            dataLabels: { enabled: false },
            xaxis: {
                categories: categories.monthly,
                axisTicks: { show: false },
                axisBorder: { show: false },
                labels: {
                    style: {
                        colors: '#94a3b8'
                    }
                }
            },
            yaxis: {
                labels: {
                    style: {
                        colors: '#94a3b8'
                    }
                }
            },
            grid: {
                borderColor: '#e2e8f0',
                strokeDashArray: 4
            },
            fill: {
                type: 'gradient',
                gradient: {
                    shadeIntensity: 1,
                    opacityFrom: 0.35,
                    opacityTo: 0.05,
                    stops: [0, 90, 100]
                }
            },
            colors: ['#4f46e5']
        };

        var chart = new ApexCharts(container, options);
        chart.render();

        var buttons = document.querySelectorAll('.dap-project-statistics-filter .btn');
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
                if (!key || !seriesMap[key]) {
                    return;
                }

                chart.updateOptions({
                    xaxis: {
                        categories: categories[key]
                    }
                });

                chart.updateSeries([
                    {
                        name: seriesLabel,
                        data: seriesMap[key]
                    }
                ]);
            });
        });

        return chart;
    }

    function initProgressRadialChart() {
        var strings = (root.dapDashboard && root.dapDashboard.strings) || {};
        var progressLabel = strings.onProgress || 'On Progress';
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
            colors: ['#4f46e5'],
            plotOptions: {
                radialBar: {
                    hollow: {
                        size: '58%'
                    },
                    track: {
                        background: '#e2e8f0'
                    },
                    dataLabels: {
                        name: {
                            show: true,
                            fontSize: '16px',
                            color: '#475569'
                        },
                        value: {
                            show: true,
                            fontSize: '32px',
                            fontWeight: 600,
                            color: '#0f172a',
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
        var donutColors = Array.isArray(emailData.colors) && emailData.colors.length ? emailData.colors : ['#4f46e5', '#10b981', '#f59e0b', '#38bdf8'];

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
            }
        };

        var chart = new ApexCharts(container, options);
        chart.render();
        return chart;
    }

    function bootstrap() {
        initProjectStatisticsChart();
        initProgressRadialChart();
        initEmailCategoriesChart();

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
