<?php
/**
 * General helper functions shared by the plugin classes.
 *
 * @package ADC\Login
 */

namespace ADC\Login;

use WP_Post;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Retrieve plugin options with defaults applied.
 *
 * @return array
 */
function get_options() {
    $defaults = array(
        'onboarding_page'         => 0,
        'post_login_redirect'     => '',
        'force_onboarding'        => 1,
        'enable_2fa'              => 1,
        'recaptcha_site_key'      => '',
        'recaptcha_secret_key'    => '',
        'logo_id'                 => 0,
        'color_primary'           => '#6a5ae0',
        'color_accent'            => '#bf83ff',
        'color_ink'               => '#242142',
        'email_from_name'         => get_bloginfo( 'name' ),
        'email_from_address'      => get_bloginfo( 'admin_email' ),
        'subject_account_created' => __( 'Bem-vindo à Academia da Comunicação', 'login-academia-da-comunicacao' ),
        'subject_password_reminder' => __( 'Redefinição de senha', 'login-academia-da-comunicacao' ),
        'subject_twofa_code'      => __( 'Seu código de verificação', 'login-academia-da-comunicacao' ),
        'terms_url'               => '',
        'onboarding_skip_url'     => '',
        'enable_social_google'    => 0,
        'enable_social_apple'     => 0,
        'rest_allowed_origins'    => '',
    );

    $options = get_option( 'adc_login_options', array() );

    return wp_parse_args( $options, $defaults );
}

/**
 * Get a single option value.
 *
 * @param string $key Option key.
 * @param mixed  $default Default value.
 *
 * @return mixed
 */
function get_option_value( $key, $default = '' ) {
    $options = get_options();

    return isset( $options[ $key ] ) ? $options[ $key ] : $default;
}

/**
 * Retrieve the onboarding page permalink.
 *
 * @return string
 */
function get_onboarding_url() {
    $page_id = absint( get_option_value( 'onboarding_page', 0 ) );

    return $page_id ? get_permalink( $page_id ) : home_url( '/' );
}

/**
 * Retrieve the onboarding skip target URL.
 *
 * @return string
 */
function get_onboarding_skip_url() {
    $skip_url = get_option_value( 'onboarding_skip_url', '' );

    if ( ! empty( $skip_url ) ) {
        return esc_url_raw( $skip_url );
    }

    return add_query_arg( 'step', 'login', get_onboarding_url() );
}

/**
 * Retrieve the redirect URL after login.
 *
 * @return string
 */
function get_post_login_url() {
    $url = get_option_value( 'post_login_redirect', '' );

    if ( ! empty( $url ) ) {
        return esc_url_raw( $url );
    }

    $page_id = absint( get_option_value( 'onboarding_page', 0 ) );

    if ( $page_id ) {
        $page = get_post( $page_id );

        if ( $page instanceof WP_Post && ! empty( $page->post_parent ) ) {
            return get_permalink( $page->post_parent );
        }
    }

    return home_url( '/dashboard/' );
}

/**
 * Retrieve the stored logo URL with fallback to plugin asset.
 *
 * @return string
 */
function get_logo_url() {
    $logo_id = absint( get_option_value( 'logo_id', 0 ) );

    if ( $logo_id && is_allowed_logo_attachment( $logo_id ) ) {
        $logo = wp_get_attachment_image_url( $logo_id, 'full' );

        if ( $logo ) {
            return esc_url_raw( $logo );
        }
    }

    return ADC_LOGIN_PLUGIN_URL . 'assets/img/logo.svg';
}

/**
 * Retrieve the allowed mime types keyed by their extensions for the custom logo field.
 *
 * @return array
 */
function get_allowed_logo_mime_map() {
    $map = array(
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
        'gif'  => 'image/gif',
        'svg'  => 'image/svg+xml',
        'webp' => 'image/webp',
    );

    $map = apply_filters( 'adc_login_allowed_logo_mime_map', $map );

    if ( ! is_array( $map ) ) {
        return array();
    }

    return $map;
}

/**
 * Retrieve allowed mime types for the custom logo field.
 *
 * @return array
 */
function get_allowed_logo_mimes() {
    $map = get_allowed_logo_mime_map();

    $mimes = array_values( $map );

    $mimes = apply_filters( 'adc_login_allowed_logo_mimes', $mimes );

    return array_unique( array_filter( $mimes, 'is_string' ) );
}

/**
 * Determine whether an attachment ID references a compatible logo file.
 *
 * @param int $attachment_id Attachment ID to validate.
 *
 * @return bool
 */
function is_allowed_logo_attachment( $attachment_id ) {
    $attachment_id = absint( $attachment_id );

    if ( ! $attachment_id ) {
        return false;
    }

    $file_path = get_attached_file( $attachment_id );

    if ( ! $file_path || ! file_exists( $file_path ) ) {
        return false;
    }

    $file_name = wp_basename( $file_path );
    $file_info = wp_check_filetype_and_ext( $file_path, $file_name );

    $mime = '';

    if ( ! empty( $file_info['type'] ) ) {
        $mime = $file_info['type'];
    } else {
        $mime = get_post_mime_type( $attachment_id );
    }

    if ( ! $mime ) {
        return false;
    }

    $allowed_map  = get_allowed_logo_mime_map();
    $allowed_mime = get_allowed_logo_mimes();

    if ( ! in_array( $mime, $allowed_mime, true ) ) {
        return false;
    }

    $extension = '';

    if ( ! empty( $file_info['ext'] ) ) {
        $extension = strtolower( $file_info['ext'] );
    } else {
        $extension = strtolower( pathinfo( $file_name, PATHINFO_EXTENSION ) );
    }

    if ( $extension ) {
        if ( isset( $allowed_map[ $extension ] ) ) {
            if ( $allowed_map[ $extension ] !== $mime ) {
                return false;
            }
        } else {
            return false;
        }
    }

    if ( 'image/svg+xml' !== $mime && ! wp_attachment_is_image( $attachment_id ) ) {
        return false;
    }

    return true;
}

/**
 * Retrieve a plugin asset URL by relative path.
 *
 * @param string $relative Relative asset path from the plugin root.
 *
 * @return string
 */
function get_asset_url( $relative ) {
    $relative = ltrim( $relative, '/' );

    return ADC_LOGIN_PLUGIN_URL . $relative;
}

/**
 * Render a template file located in templates directory.
 *
 * @param string $template Template name relative to the templates dir.
 * @param array  $context  Data to pass to template.
 */
function render_template( $template, $context = array() ) {
    $path = locate_template( $template );

    if ( file_exists( $path ) ) {
        $context = apply_filters( 'adc_login_template_context', $context, $template );

        extract( $context ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract
        include $path;
    }
}

/**
 * Retrieve and clear flash messages.
 *
 * @param string $type Message type.
 *
 * @return string
 */
function get_flash_message( $type ) {
    $ip      = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown'; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
    $key     = 'adc_flash_' . $type . '_' . md5( $ip );
    $message = get_transient( $key );

    if ( $message ) {
        delete_transient( $key );
    }

    return $message;
}

/**
 * Locate a template allowing theme overrides.
 *
 * @param string $template Template name.
 *
 * @return string
 */
function locate_template( $template ) {
    $theme_path = \locate_template( 'login-academia-da-comunicacao/' . $template );

    if ( $theme_path ) {
        return $theme_path;
    }

    return ADC_LOGIN_PLUGIN_DIR . 'templates/' . $template;
}
