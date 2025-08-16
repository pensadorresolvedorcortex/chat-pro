<?php
// Prevent direct access to this file.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * This file loads the whole theme framework.
 *
 * @package realhomes/framework
 */

// Load classes files.
$classes_file = INSPIRY_FRAMEWORK . 'classes/load.php';
if ( file_exists( $classes_file ) ) {
    require_once $classes_file;
}

// Theme version constant.
if ( ! defined( 'INSPIRY_THEME_VERSION' ) ) {
    if ( class_exists( 'RealHomes_Helper' ) ) {
        define( 'INSPIRY_THEME_VERSION', RealHomes_Helper::get_theme_version() );
    } else {
        define( 'INSPIRY_THEME_VERSION', 'classic' );
    }
}

// Load functions files.
$functions_file = INSPIRY_FRAMEWORK . 'functions/load.php';
if ( file_exists( $functions_file ) ) {
    require_once $functions_file;
}

// Google Fonts.
$google_fonts = INSPIRY_FRAMEWORK . 'customizer/google-fonts/google-fonts.php';
if ( file_exists( $google_fonts ) ) {
    require_once $google_fonts;
}

// Customizer.
$customizer = INSPIRY_FRAMEWORK . 'customizer/customizer.php';
if ( file_exists( $customizer ) ) {
    require_once $customizer;
}

// RealHomes Admin.
$rh_admin = INSPIRY_FRAMEWORK . 'include/admin/class-rh-admin.php';
if ( file_exists( $rh_admin ) ) {
    require_once $rh_admin;
}

// RealHomes Admin functions.
$rh_admin_functions = INSPIRY_FRAMEWORK . 'include/admin/admin-functions.php';
if ( file_exists( $rh_admin_functions ) ) {
    require_once $rh_admin_functions;
}

// Theme meta boxes.
$meta_boxes = [
    'include/meta-boxes/post-meta-box.php',
    'include/meta-boxes/home-page-meta-box.php',
    'include/meta-boxes/meta-boxes.php',
];

foreach ( $meta_boxes as $meta_box ) {
    $path = INSPIRY_FRAMEWORK . $meta_box;
    if ( file_exists( $path ) ) {
        require_once $path;
    }
}

