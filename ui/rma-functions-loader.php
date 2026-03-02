<?php
/**
 * Loader mínimo para colar no functions.php do tema.
 *
 * Uso recomendado:
 * 1) Copie este arquivo e o rma-glass-theme-wordpress-snippet.php para /wp-content/themes/seu-tema/ui/
 * 2) No functions.php do tema, adicione:
 *
 *    require_once get_stylesheet_directory() . '/ui/rma-functions-loader.php';
 */

if (! defined('ABSPATH')) {
    exit;
}

if (! function_exists('rma_load_theme_onboarding_snippet')) {
    function rma_load_theme_onboarding_snippet() {
        $candidates = [
            trailingslashit(get_stylesheet_directory()) . 'ui/rma-glass-theme-wordpress-snippet.php',
            trailingslashit(get_template_directory()) . 'ui/rma-glass-theme-wordpress-snippet.php',
        ];

        foreach ($candidates as $candidate) {
            if (file_exists($candidate)) {
                require_once $candidate;
                return true;
            }
        }

        return false;
    }
}

add_action('after_setup_theme', function () {
    $loaded = rma_load_theme_onboarding_snippet();

    if (! $loaded && is_admin()) {
        add_action('admin_notices', function () {
            echo '<div class="notice notice-error"><p><strong>RMA:</strong> arquivo <code>ui/rma-glass-theme-wordpress-snippet.php</code> não encontrado no tema.</p></div>';
        });
    }
}, 1);
