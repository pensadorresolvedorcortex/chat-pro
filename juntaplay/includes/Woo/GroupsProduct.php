<?php
declare(strict_types=1);

namespace JuntaPlay\Woo;

use WC_Product;

use function class_exists;
use function get_option;
use function get_post_status;
use function is_wp_error;
use function update_option;
use function update_post_meta;
use function wc_get_product;
use function wp_insert_post;
use function wp_set_object_terms;
use function wp_untrash_post;
use function wp_update_post;
use function apply_filters;
use function __;

defined('ABSPATH') || exit;

class GroupsProduct
{
    private const OPTION_KEY = 'juntaplay_group_product_id';

    public static function get_product_id(): int
    {
        $product_id = (int) get_option(self::OPTION_KEY, 0);

        if ($product_id > 0) {
            $status = get_post_status($product_id);

            if ($status === 'publish') {
                return $product_id;
            }

            if ($status === 'trash') {
                wp_untrash_post($product_id);
                $status = get_post_status($product_id);
            }

            if ($status && $status !== 'publish') {
                wp_update_post([
                    'ID'          => $product_id,
                    'post_status' => 'publish',
                ]);

                return $product_id;
            }
        }

        return self::create_product();
    }

    public static function get_product(): ?WC_Product
    {
        if (!class_exists('WooCommerce')) {
            return null;
        }

        $product_id = self::get_product_id();

        return $product_id > 0 ? wc_get_product($product_id) : null;
    }

    private static function create_product(): int
    {
        if (!class_exists('WooCommerce')) {
            return 0;
        }

        $title = (string) apply_filters('juntaplay/groups/checkout_product_title', __('Assinatura de grupo', 'juntaplay'));

        $product_id = wp_insert_post([
            'post_title'   => $title,
            'post_type'    => 'product',
            'post_status'  => 'publish',
            'post_content' => '',
        ], true);

        if (is_wp_error($product_id) || !$product_id) {
            return 0;
        }

        wp_set_object_terms($product_id, 'simple', 'product_type');

        update_post_meta($product_id, '_regular_price', '0');
        update_post_meta($product_id, '_price', '0');
        update_post_meta($product_id, '_tax_status', 'none');
        update_post_meta($product_id, '_manage_stock', 'no');
        update_post_meta($product_id, '_stock_status', 'instock');
        update_post_meta($product_id, '_virtual', 'yes');
        update_post_meta($product_id, '_sold_individually', 'yes');
        update_post_meta($product_id, '_downloadable', 'no');

        $product = wc_get_product($product_id);

        if ($product instanceof WC_Product) {
            $product->set_catalog_visibility('hidden');
            $product->set_status('publish');
            $product->save();
        }

        update_option(self::OPTION_KEY, (int) $product_id);

        return (int) $product_id;
    }
}
