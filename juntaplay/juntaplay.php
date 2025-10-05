<?php
/**
 * Plugin Name: JuntaPlay — Gestão de Cotas
 * Description: Campanhas com cotas integradas ao WooCommerce e Elementor.
 * Version: 0.1.0
 * Requires at least: 6.2
 * Requires PHP: 8.1
 * Author: Sua Empresa
 * Text Domain: juntaplay
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

const JP_VERSION    = '0.1.0';
const JP_MIN_WP     = '6.2';
const JP_MIN_PHP    = '8.1';
const JP_DB_VERSION = '1.8.0';
const JP_SLUG       = 'juntaplay';

define('JP_FILE', __FILE__);
define('JP_DIR', plugin_dir_path(__FILE__));
define('JP_URL', plugin_dir_url(__FILE__));

spl_autoload_register(static function (string $class): void {
    $prefix   = 'JuntaPlay\\';
    $base_dir = JP_DIR . 'includes/';
    $len      = strlen($prefix);

    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file           = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

register_activation_hook(__FILE__, static function (): void {
    (new \JuntaPlay\Installer())->activate();
});

register_uninstall_hook(__FILE__, 'juntaplay_uninstall');

function juntaplay_uninstall(): void
{
    // Opcional: remover opções, eventos agendados etc. Não apagar dados por padrão.
}

add_action('plugins_loaded', static function (): void {
    if (version_compare(PHP_VERSION, JP_MIN_PHP, '<')) {
        return;
    }

    if (version_compare(get_bloginfo('version'), JP_MIN_WP, '<')) {
        return;
    }

    (new \JuntaPlay\Plugin())->init();
});
