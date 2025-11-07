<?php
declare(strict_types=1);

namespace JuntaPlay\Front;

use function add_action;
use function function_exists;
use function is_checkout;
use function wp_add_inline_script;
use function wp_script_is;

defined('ABSPATH') || exit;

class CheckoutColumns
{
    private const TARGET_HANDLE = 'juntaplay';

    public function init(): void
    {
        add_action('wp_enqueue_scripts', [$this, 'inject_inline_script'], 20);
    }

    public function inject_inline_script(): void
    {
        if (!function_exists('is_checkout') || !is_checkout()) {
            return;
        }

        if (!wp_script_is(self::TARGET_HANDLE, 'enqueued')) {
            return;
        }

        $script = <<<'JS'
(function () {
    if (typeof document === 'undefined' || !document.body || !document.body.classList.contains('woocommerce-checkout')) {
        return;
    }

    function applyColumnWidths() {
        [
            { selector: '.woocommerce-checkout .col-lg-8.col-12', remove: 'col-lg-8', add: 'col-lg-6' },
            { selector: '.woocommerce-checkout .col-lg-4.col-12', remove: 'col-lg-4', add: 'col-lg-6' }
        ].forEach(function (config) {
            document.querySelectorAll(config.selector).forEach(function (element) {
                element.classList.remove(config.remove);
                if (!element.classList.contains(config.add)) {
                    element.classList.add(config.add);
                }
            });
        });
    }

    if (typeof window !== 'undefined' && typeof window.jQuery === 'function' && window.jQuery(document.body).on) {
        window.jQuery(document.body).on('updated_checkout', applyColumnWidths);
    } else if (document.body && typeof document.body.addEventListener === 'function') {
        document.body.addEventListener('updated_checkout', applyColumnWidths);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', applyColumnWidths, { once: true });
    } else {
        applyColumnWidths();
    }
})();
JS;

        wp_add_inline_script(self::TARGET_HANDLE, $script);
    }
}
