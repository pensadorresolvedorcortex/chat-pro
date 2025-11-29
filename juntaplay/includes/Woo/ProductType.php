<?php
declare(strict_types=1);

namespace JuntaPlay\Woo;

defined('ABSPATH') || exit;

class ProductType
{
    public function init(): void
    {
        add_filter('product_type_selector', [$this, 'add_product_type']);
        add_filter('woocommerce_product_class', [$this, 'map_product_class'], 10, 2);
    }

    public function add_product_type(array $types): array
    {
        $types['juntaplay_pool_product']   = __('Campanha JuntaPlay', 'juntaplay');
        $types['juntaplay_credit_topup']   = __('Recarga de Créditos', 'juntaplay');

        return $types;
    }

    public function map_product_class(string $classname, string $product_type): string
    {
        if ('juntaplay_pool_product' === $product_type) {
            return '\\JuntaPlay\\Woo\\Products\\PoolProduct';
        }

        if ('juntaplay_credit_topup' === $product_type) {
            return '\\JuntaPlay\\Woo\\Products\\CreditTopUpProduct';
        }

        return $classname;
    }
}
