<?php
// Prevent direct access to this file.
if ( ! defined( 'ABSPATH' ) ) {
        exit;
}

/**
 * This file loads other files containing various functions used in this theme
 *
 * @package realhomes/functions
 */

// Purchase API.
$purchase_api = INSPIRY_FRAMEWORK . 'functions/purchase-api.php';
if ( ! class_exists( 'ERE_Purchase_API' ) && file_exists( $purchase_api ) ) {
        require_once $purchase_api;
}

// global data classes.
$data_file = INSPIRY_FRAMEWORK . 'functions/data.php';
if ( file_exists( $data_file ) ) {
        require_once $data_file;
}

// Basic functions.
$basic_file = INSPIRY_FRAMEWORK . 'functions/basic.php';
if ( file_exists( $basic_file ) ) {
        require_once $basic_file;
}

// Load optional function modules only when files are present.
$function_files = [
        'ajax-search.php',
        'agent-search.php',
        'agency-search.php',
        'header.php',
        'google-map-helper.php',
        'google-map.php',
        'open-street-map.php',
        'mapbox.php',
        'woocommerce.php',
        'design-variations-handler.php',
        'pagination.php',
        'price.php',
        'real-estate.php',
        'real-estate-search.php',
        'home.php',
        'contact.php',
        'breadcrumbs.php',
        'member.php',
        'submit-edit.php',
        'favorites.php',
        'property-submit-handler.php',
        'property-print.php',
        'user-profile.php',
        'edit-profile-handler.php',
        'theme-comment.php',
        'compare.php',
        'property-custom-fields.php',
        'save-searches.php',
        'membership.php',
        'comment-ratings.php',
        'dashboard.php',
        'dashboard-colorschemes.php',
        'colorschemes.php',
];

foreach ( $function_files as $file ) {
        $path = INSPIRY_FRAMEWORK . 'functions/' . $file;
        if ( file_exists( $path ) ) {
                require_once $path;
        }
}

// If realhomes-vacation-rentals plugin is activated and enabled from its settings.
if ( function_exists( 'inspiry_is_rvr_enabled' ) && inspiry_is_rvr_enabled() ) {
        // Realhomes Vacation Rentals related files.
        $rvr_search = INSPIRY_FRAMEWORK . 'functions/rvr/rvr-search.php';
        if ( file_exists( $rvr_search ) ) {
                require_once $rvr_search;
        }
        $rvr_functions = INSPIRY_FRAMEWORK . 'functions/rvr/rvr-functions.php';
        if ( file_exists( $rvr_functions ) ) {
                require_once $rvr_functions;
        }
}

// Theme update functions.
$theme_update = INSPIRY_FRAMEWORK . 'functions/theme-update.php';
if ( class_exists( 'ERE_Subscription_API' ) && ERE_Subscription_API::status() && file_exists( $theme_update ) ) {
        require_once $theme_update;
}

// Subscription API.
$subscription_api = INSPIRY_FRAMEWORK . 'functions/subscription-api.php';
if ( ! class_exists( 'ERE_Subscription_API' ) && file_exists( $subscription_api ) ) {
        require_once $subscription_api;
}

if ( 'classic' !== INSPIRY_THEME_VERSION ) {
        // Functions related to property meta custom icons.
        $pm_icons = INSPIRY_FRAMEWORK . 'functions/property-meta-custom-icons.php';
        if ( file_exists( $pm_icons ) ) {
                require_once $pm_icons;
        }
}
