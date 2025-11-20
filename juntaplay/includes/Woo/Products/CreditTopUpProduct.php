<?php
declare(strict_types=1);

namespace JuntaPlay\Woo\Products;

use WC_Product_Simple;

defined('ABSPATH') || exit;

class CreditTopUpProduct extends WC_Product_Simple
{
    protected $product_type = 'juntaplay_credit_topup';
}
