<?php
declare(strict_types=1);

namespace JuntaPlay\Woo\Products;

use WC_Product_Simple;

defined('ABSPATH') || exit;

class PoolProduct extends WC_Product_Simple
{
    protected $product_type = 'juntaplay_pool_product';
}
