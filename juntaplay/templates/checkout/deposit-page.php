<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

global $juntaplay_deposit_template_context;

$template_context = [];

if (isset($juntaplay_deposit_template_context) && is_array($juntaplay_deposit_template_context)) {
    $template_context = $juntaplay_deposit_template_context;
}

get_header();

include JP_DIR . 'templates/checkout/deposit.php';

get_footer();

unset($GLOBALS['juntaplay_deposit_template_context']);
