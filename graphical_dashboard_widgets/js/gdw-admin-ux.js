(function ($) {
  function startLoop(chart) {
    if (!chart || chart.gdwLoopStarted || typeof chart.getOption !== 'function') {
      return;
    }

    const runCycle = function () {
      if (chart.gdwLoopTimer) {
        return;
      }

      const option = chart.getOption && chart.getOption();
      if (!option || !option.series || !option.series.length) {
        return;
      }

      const seriesIndex = 0;
      const series = option.series[seriesIndex];
      const data = series && series.data ? series.data : [];

      if (!data.length) {
        return;
      }

      chart.gdwLoopStarted = true;
      let cursor = 0;

      chart.gdwLoopTimer = setInterval(function () {
        if ((chart.isDisposed && chart.isDisposed()) || !chart.getOption) {
          clearInterval(chart.gdwLoopTimer);
          chart.gdwLoopTimer = null;
          chart.gdwLoopStarted = false;
          return;
        }

        const liveOption = chart.getOption();
        const liveSeries = (liveOption.series || [])[seriesIndex];
        const length = liveSeries && liveSeries.data ? liveSeries.data.length : 0;

        if (!length) {
          return;
        }

        if (cursor >= length) {
          cursor = 0;
        }

        chart.dispatchAction({ type: 'downplay', seriesIndex });
        chart.dispatchAction({ type: 'highlight', seriesIndex, dataIndex: cursor });
        chart.dispatchAction({ type: 'showTip', seriesIndex, dataIndex: cursor });

        cursor += 1;
      }, 2400);
    };

    chart.on('finished', runCycle);
    setTimeout(runCycle, 800);

    chart.on('dispose', function () {
      if (chart.gdwLoopTimer) {
        clearInterval(chart.gdwLoopTimer);
        chart.gdwLoopTimer = null;
      }
      chart.gdwLoopStarted = false;
    });
  }

  if (window.echarts) {
    const originalInit = echarts.init;

    echarts.init = function () {
      const chart = originalInit.apply(this, arguments);
      const baseOption = {
        backgroundColor: 'transparent',
        color: ['#7b6cff', '#4b7cfb', '#4fd2ff', '#ff6fb5', '#32e0c4'],
        textStyle: {
          color: '#3f4b5b',
          fontWeight: 600,
        },
        title: {
          textStyle: {
            color: '#3f4b5b',
            fontWeight: 700,
          },
        },
        tooltip: {
          backgroundColor: 'rgba(255, 255, 255, 0.9)',
          borderColor: 'rgba(162, 89, 255, 0.3)',
          textStyle: { color: '#0a0d14', fontWeight: 600 },
          extraCssText: 'backdrop-filter: blur(6px); box-shadow: 0 18px 40px rgba(35,25,64,0.25); border-radius: 12px; padding: 12px 14px;'
        },
        legend: {
          textStyle: {
            color: '#3f4b5b',
            fontWeight: 700,
            fontSize: 12,
            textBorderColor: 'transparent',
          },
          itemWidth: 18,
          itemHeight: 10,
          icon: 'roundRect',
        },
        axisPointer: {
          lineStyle: {
            color: 'rgba(162, 89, 255, 0.65)',
            type: 'dashed',
            width: 1.4,
          },
          shadowStyle: {
            color: 'rgba(23, 243, 255, 0.08)',
            blur: 12,
          },
        },
        grid: {
          left: 24,
          right: 24,
          top: 30,
          bottom: 18,
          containLabel: true,
        },
      };

      const axisDefaults = {
        axisLine: { lineStyle: { color: 'rgba(10, 13, 20, 0.18)' } },
        axisLabel: { color: '#3f4b5b', fontWeight: 700, fontSize: 12 },
        splitLine: { lineStyle: { color: 'rgba(10, 13, 20, 0.08)' } },
      };

      const originalSetOption = chart.setOption;

      chart.setOption = function (option, notMerge, lazyUpdate) {
        const merged = $.extend(true, {}, baseOption, option);

        if (merged.xAxis) {
          merged.xAxis = Array.isArray(merged.xAxis)
            ? merged.xAxis.map(function (ax) { return $.extend(true, {}, axisDefaults, ax); })
            : $.extend(true, {}, axisDefaults, merged.xAxis);
        }

        if (merged.yAxis) {
          merged.yAxis = Array.isArray(merged.yAxis)
            ? merged.yAxis.map(function (ax) { return $.extend(true, {}, axisDefaults, ax); })
            : $.extend(true, {}, axisDefaults, merged.yAxis);
        }

        if (Array.isArray(merged.series)) {
          merged.series = merged.series.map(function (series) {
            if (!series) {
              return series;
            }

            const enhanced = $.extend(true, {}, series);

            if (enhanced.type === 'line') {
              enhanced.smooth = enhanced.smooth !== false;
              enhanced.symbol = enhanced.symbol || 'circle';
              enhanced.symbolSize = enhanced.symbolSize || 10;
              enhanced.lineStyle = $.extend(true, {
                width: 3,
                shadowBlur: 18,
                shadowColor: 'rgba(162, 89, 255, 0.3)',
              }, enhanced.lineStyle);
              enhanced.areaStyle = enhanced.areaStyle || {
                color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
                  { offset: 0, color: 'rgba(162, 89, 255, 0.24)' },
                  { offset: 1, color: 'rgba(23, 243, 255, 0.12)' },
                ]),
                opacity: 0.85,
              };
            }

            if (enhanced.type === 'bar') {
              enhanced.barMaxWidth = enhanced.barMaxWidth || 28;
              enhanced.itemStyle = $.extend(true, {
                borderRadius: [10, 10, 6, 6],
                shadowBlur: 20,
                shadowColor: 'rgba(23, 243, 255, 0.25)',
              }, enhanced.itemStyle);
            }

            return enhanced;
          });
        }

        return originalSetOption.call(chart, merged, notMerge, lazyUpdate);
      };

      startLoop(chart);
      return chart;
    };
  }

  window.gdwLoopChart = startLoop;
})(jQuery);
