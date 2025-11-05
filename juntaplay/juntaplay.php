<?php
/**
 * Plugin Name: JuntaPlay — Gestão de Cotas
 * Description: Campanhas com cotas integradas ao WooCommerce e Elementor.
 * Version: 0.1.4
 * Requires at least: 6.2
 * Requires PHP: 8.1
 * Author: Sua Empresa
 * Text Domain: juntaplay
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

const JP_VERSION    = '0.1.4';
const JP_MIN_WP     = '6.2';
const JP_MIN_PHP    = '8.1';
const JP_DB_VERSION = '2.0.0';
const JP_SLUG       = 'juntaplay';
const JP_GROUP_COVER_PLACEHOLDER = 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0naHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmcnIHdpZHRoPSc0OTUnIGhlaWdodD0nMzcwJyB2aWV3Qm94PScwIDA0OTUgMzcwJz4KICA8ZGVmcz4KICAgIDxsaW5lYXJHcmFkaWVudCBpZD0nZycgeDE9JzAnIHkxPScwJyB4Mj0nMScgeTI9JzEnPgogICAgICA8c3RvcCBvZmZzZXQ9JzAlJyBzdG9wLWNvbG9yPScjNUI2Q0ZGJy8+CiAgICAgIDxzdG9wIG9mZnNldD0nMTAwJScgc3RvcC1jb2xvcj0nIzhFNTRFOScvPgogICAgPC9saW5lYXJHcmFkaWVudD4KICA8L2RlZnM+CiAgPHJlY3Qgd2lkdGg9JzQ5NScgaGVpZ2h0PSczNzAnIGZpbGw9J3VybCgjZyknIHJ4PSczMicvPgogIDxnIGZpbGw9JyNGRkZGRkYnIGZvbnQtZmFtaWx5PSdGcmVkb2thLCBGaWd0cmVlLCBzYW5zLXNlcmlmJyBmb250LXdlaWdodD0nNjAwJz4KICAgIDx0ZXh0IHg9JzUwJScgeT0nNDglJyBkb21pbmFudC1iYXNlbGluZT0nbWlkZGxlJyB0ZXh0LWFuY2hvcj0nbWlkZGxlJyBmb250LXNpemU9JzQwJz5KdW50YVBsYXk8L3RleHQ+CiAgICA8dGV4dCB4PSc1MCUnIHk9JzYwJScgZG9taW5hbnQtYmFzZWxpbmU9J21pZGRsZScgdGV4dC1hbmNob3I9J21pZGRsZScgZm9udC1zaXplPScyNCcgZm9udC13ZWlnaHQ9JzQwMCc+Q2FwYSBEZW1vbnN0cmF0aXZhPC90ZXh0PgogIDwvZz4KPC9zdmc+';

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
