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

        const currentIndex = cursor;
        setTimeout(function () {
          if (chart && chart.dispatchAction) {
            chart.dispatchAction({ type: 'downplay', seriesIndex, dataIndex: currentIndex });
          }
        }, 900);

        cursor += 1;
      }, 2600);
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
      const chartDom = chart && chart.getDom ? chart.getDom() : null;
      if (chartDom && chartDom.classList) {
        chartDom.classList.add('gdw-chart-pulse', 'chart-content');
      }
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

  function renderRecent(listEl, items) {
    listEl.empty();

    if (!items || !items.length) {
      listEl.append(
        $('<li/>', {
          class: 'gdw-group-card__placeholder',
          text: gdwUX && gdwUX.strings ? gdwUX.strings.noRecent : 'Nenhuma transação recente',
        })
      );
      return;
    }

    items.forEach(function (item) {
      const row = $('<li/>');
      $('<span/>', { class: 'gdw-group-card__pill', text: item.date || '' }).appendTo(row);
      $('<strong/>').html(item.total || '').appendTo(row);
      listEl.append(row);
    });
  }

  function renderListWithThumbs(listEl, items, fallbackText) {
    listEl.empty();

    if (!items || !items.length) {
      listEl.append(
        $('<li/>', {
          class: 'gdw-group-card__placeholder',
          text: fallbackText,
        })
      );
      return;
    }

    items.forEach(function (item) {
      const row = $('<li/>');
      $('<img/>', { src: item.thumb || '', alt: item.title || '' }).appendTo(row);
      $('<span/>', { text: item.title || '' }).appendTo(row);
      $('<em/>', { text: gdwUX && gdwUX.strings ? gdwUX.strings.approve : '' }).appendTo(row);
      listEl.append(row);
    });
  }

  function renderAvatars(container, items) {
    container.empty();

    if (!items || !items.length) {
      container.append(
        $('<span/>', {
          class: 'gdw-group-card__placeholder',
          text: gdwUX && gdwUX.strings ? gdwUX.strings.noUsers : 'Sem cadastros recentes',
        })
      );
      return;
    }

    items.forEach(function (item) {
      const avatar = $('<span/>', {
        class: 'gdw-group-card__avatar',
        title: item.name || '',
      });
      $('<img/>', { src: item.photo || '', alt: item.name || '' }).appendTo(avatar);
      container.append(avatar);
    });
  }

  function applyGroupsSnapshot(data) {
    const card = $('.gdw-group-card');
    if (!card.length || !data) {
      return;
    }

    if (data.links && data.links.admin) {
      card.find('.gdw-group-card__link').attr('href', data.links.admin);
    }

    if (data.badge_url) {
      card.find('.gdw-group-card__badge img').attr('src', data.badge_url);
    }

    if (data.monthly_total_label) {
      card.find('[data-gdw-groups-total]').html(data.monthly_total_label);
    }

    if (typeof data.monthly_count !== 'undefined') {
      const countText = (gdwUX && gdwUX.strings ? gdwUX.strings.monthlyTransactions : '%d transações no período').replace('%d', data.monthly_count);
      card.find('[data-gdw-groups-total-count]').text(countText);
    }

    renderAvatars(card.find('[data-gdw-groups-new-users]'), data.new_users);
    renderListWithThumbs(
      card.find('[data-gdw-groups-pending]'),
      data.pending_groups,
      gdwUX && gdwUX.strings ? gdwUX.strings.noGroups : 'Nenhum grupo precisa de tratamento'
    );
    renderRecent(card.find('[data-gdw-groups-recent]'), data.recent_orders);

    if (typeof data.pending_count !== 'undefined') {
      card.find('[data-gdw-groups-summary="pending"] strong').text(data.pending_count);
    }
    if (typeof data.recent_count !== 'undefined') {
      card.find('[data-gdw-groups-summary="recent"] strong').text(data.recent_count);
    }
    if (data.new_users) {
      card.find('[data-gdw-groups-summary="new"] strong').text(data.new_users.length);
    }
  }

  function bindGroupsRefresh() {
    const card = $('.gdw-group-card');
    if (!card.length || !window.gdwUX || !gdwUX.ajaxurl) {
      return;
    }

    card.on('click', '.gdw-group-card__refresh', function (event) {
      event.preventDefault();
      event.stopPropagation();

      const button = $(this);
      if (button.hasClass('is-loading')) {
        return;
      }

      button.addClass('is-loading');

      $.post(
        gdwUX.ajaxurl,
        {
          action: 'gdw_groups_card_refresh',
          nonce: gdwUX.groupsNonce,
        }
      )
        .done(function (response) {
          if (response && response.success && response.data) {
            applyGroupsSnapshot(response.data);
          }
        })
        .always(function () {
          button.removeClass('is-loading');
        });
    });
  }

  jQuery(function () {
    bindGroupsRefresh();
  });

  window.gdwLoopChart = startLoop;
})(jQuery);
