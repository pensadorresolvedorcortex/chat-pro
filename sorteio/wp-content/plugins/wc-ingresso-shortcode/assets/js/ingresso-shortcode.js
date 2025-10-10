(function ($) {
    'use strict';

    function updateButtonUrl(container, quantity) {
        var baseUrl = container.attr('data-add-to-cart');
        if (!baseUrl) {
            return;
        }

        quantity = parseInt(quantity, 10);
        if (isNaN(quantity) || quantity < 1) {
            quantity = 1;
        }

        var url = new URL(baseUrl, window.location.origin);
        url.searchParams.set('quantity', quantity);
        container.closest('.wc-ingresso-card').find('.wc-ingresso-button').attr('href', url.toString());
    }

    $(document).on('click', '.wc-ingresso-qty-btn', function (event) {
        event.preventDefault();

        var button = $(this);
        var container = button.closest('.wc-ingresso-quantity');
        var input = container.find('.wc-ingresso-qty');
        var value = parseInt(input.val(), 10) || 1;

        if (button.data('action') === 'increase') {
            value += 1;
        } else if (button.data('action') === 'decrease') {
            value = Math.max(1, value - 1);
        }

        input.val(value);
        updateButtonUrl(container, value);
    });

    $(document).on('change', '.wc-ingresso-qty', function () {
        var input = $(this);
        var container = input.closest('.wc-ingresso-quantity');
        var value = parseInt(input.val(), 10) || 1;

        value = Math.max(1, value);
        input.val(value);
        updateButtonUrl(container, value);
    });
})(jQuery);
