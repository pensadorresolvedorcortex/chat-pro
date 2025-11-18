(function ($) {
  function startLoop(chart) {
    if (!chart || chart.gdwLoopStarted || typeof chart.getOption !== 'function') {
      return;
    }

    const runCycle = function () {
      if (chart.gdwLoopStarted) {
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

      setInterval(function () {
        const length = (chart.getOption().series || [])[seriesIndex].data.length;
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
  }

  if (window.echarts) {
    const originalInit = echarts.init;

    echarts.init = function () {
      const chart = originalInit.apply(this, arguments);
      startLoop(chart);
      return chart;
    };
  }

  window.gdwLoopChart = startLoop;
})(jQuery);
