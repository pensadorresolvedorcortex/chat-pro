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

// Ajax Search functions.
require_once INSPIRY_FRAMEWORK . 'functions/ajax-search.php';

// Agent Search functions.
require_once INSPIRY_FRAMEWORK . 'functions/agent-search.php';

// Agency Search functions.
require_once INSPIRY_FRAMEWORK . 'functions/agency-search.php';

// Header functions.
require_once INSPIRY_FRAMEWORK . 'functions/header.php';

// Contains Enqueue and Render functions related to Google Map
require_once INSPIRY_FRAMEWORK . 'functions/google-map-helper.php';
require_once INSPIRY_FRAMEWORK . 'functions/google-map.php';

// Contains Enqueue and Render functions related to Open Street Map
require_once INSPIRY_FRAMEWORK . 'functions/open-street-map.php';

// Contains Enqueue and Render functions related to MapBox
require_once INSPIRY_FRAMEWORK . 'functions/mapbox.php';

// Contains WooCommerce related functions.
require_once INSPIRY_FRAMEWORK . 'functions/woocommerce.php';

// Load required styles and scripts
require_once INSPIRY_FRAMEWORK . 'functions/design-variations-handler.php';

// Pagination functions.
require_once INSPIRY_FRAMEWORK . 'functions/pagination.php';

// Price functions.
require_once INSPIRY_FRAMEWORK . 'functions/price.php';

// Real Estate functions.
require_once INSPIRY_FRAMEWORK . 'functions/real-estate.php';

// Real Estate Search Functions.
require_once INSPIRY_FRAMEWORK . 'functions/real-estate-search.php';

// Home related functions.
require_once INSPIRY_FRAMEWORK . 'functions/home.php';

// Contact related functions.
require_once INSPIRY_FRAMEWORK . 'functions/contact.php';

// Breadcrumbs functions.
require_once INSPIRY_FRAMEWORK . 'functions/breadcrumbs.php';

// Users / Members related functions.
require_once INSPIRY_FRAMEWORK . 'functions/member.php';

// Property submit and edit.
require_once INSPIRY_FRAMEWORK . 'functions/submit-edit.php';

// Favorites functions.
require_once INSPIRY_FRAMEWORK . 'functions/favorites.php';

// Property submit handler.
require_once INSPIRY_FRAMEWORK . 'functions/property-submit-handler.php';

// Property print.
require_once INSPIRY_FRAMEWORK . 'functions/property-print.php';

// User profile.
require_once INSPIRY_FRAMEWORK . 'functions/user-profile.php';

// Edit profile handler.
require_once INSPIRY_FRAMEWORK . 'functions/edit-profile-handler.php';

// Theme's custom comment.
require_once INSPIRY_FRAMEWORK . 'functions/theme-comment.php';

// Compare functions.
require_once INSPIRY_FRAMEWORK . 'functions/compare.php';

// Property custom fields functions.
require_once INSPIRY_FRAMEWORK . 'functions/property-custom-fields.php';

// Save Searches Functions.
require_once INSPIRY_FRAMEWORK . 'functions/save-searches.php';

// Memberships functions.
require_once INSPIRY_FRAMEWORK . 'functions/membership.php';

// Property Rating functions.
require_once INSPIRY_FRAMEWORK . 'functions/comment-ratings.php';

// If realhomes-vacation-rentals plugin is activated and enabled from its settings
if ( inspiry_is_rvr_enabled() ) {
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

// Dashboard functions
require_once INSPIRY_FRAMEWORK . 'functions/dashboard.php';
require_once INSPIRY_FRAMEWORK . 'functions/dashboard-colorschemes.php';

// Subscription API.
$subscription_api = INSPIRY_FRAMEWORK . 'functions/subscription-api.php';
if ( ! class_exists( 'ERE_Subscription_API' ) && file_exists( $subscription_api ) ) {
        require_once $subscription_api;
}

// Color schemes file.
require_once INSPIRY_FRAMEWORK . 'functions/colorschemes.php';

if ( 'classic' !== INSPIRY_THEME_VERSION ) {
	// Functions related to property meta custom icons.
	require_once INSPIRY_FRAMEWORK . 'functions/property-meta-custom-icons.php';
}
