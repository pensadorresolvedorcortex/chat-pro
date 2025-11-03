<?php
declare(strict_types=1);

namespace JuntaPlay;

use JuntaPlay\Admin\Settings;
use JuntaPlay\Notifications\EmailHelper;
use function get_role;
use function trailingslashit;

defined('ABSPATH') || exit;

class Plugin
{
    public function init(): void
    {
        Installer::maybe_upgrade();

        // Admin
        (new Admin\Menu())->init();
        (new Admin\Settings())->init();
        (new Admin\Importer())->init();
        (new Admin\Groups())->init();

        // Frontend
        $auth = new Front\Auth();
        $auth->init();

        $profile = new Front\Profile();
        $profile->init();

        (new Front\Shortcodes($auth, $profile))->init();
        (new Front\Ajax($profile))->init();
        (new Front\Rest())->init();

        EmailHelper::init();
        (new Notifications\Groups())->init();
        (new Notifications\Credits())->init();

        // WooCommerce integration
        if (class_exists('\\WooCommerce')) {
            (new Woo\ProductType())->init();
            (new Woo\Hooks())->init();
        }

        // Elementor widgets
        add_action('elementor/widgets/register', [$this, 'register_elementor_widgets']);

        // Assets
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('init', [$this, 'ensure_upload_permissions']);
        Installer::bootstrap_cron();
        Installer::schedule_cron();
    }

    public function register_elementor_widgets($widgets_manager): void
    {
        require_once JP_DIR . 'elementor/Widgets/WidgetPoolList.php';
        require_once JP_DIR . 'elementor/Widgets/WidgetPoolHero.php';
        require_once JP_DIR . 'elementor/Widgets/WidgetQuotaGrid.php';

        $widgets_manager->register(new \JuntaPlayElementor\WidgetPoolList());
        $widgets_manager->register(new \JuntaPlayElementor\WidgetPoolHero());
        $widgets_manager->register(new \JuntaPlayElementor\WidgetQuotaGrid());
    }

    public function enqueue_assets(): void
    {
        $style_file  = (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG) ? 'assets/css/juntaplay.css' : 'assets/css/juntaplay.min.css';
        $script_file = (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG) ? 'assets/js/juntaplay.js' : 'assets/js/juntaplay.min.js';

        if (!file_exists(JP_DIR . $style_file)) {
            $style_file = 'assets/css/juntaplay.css';
        }

        if (!file_exists(JP_DIR . $script_file)) {
            $script_file = 'assets/js/juntaplay.js';
        }

        wp_enqueue_style('juntaplay', JP_URL . $style_file, [], JP_VERSION);
        wp_enqueue_script('juntaplay', JP_URL . $script_file, ['jquery'], JP_VERSION, true);

        $general = get_option(Settings::OPTION_GENERAL, []);
        $primary = isset($general['primary_color']) ? sanitize_hex_color($general['primary_color']) : null;
        $primary = $primary ?: '#ff5a5f';
        $primary_dark  = $this->shade_color($primary, -0.2);
        $primary_light = $this->shade_color($primary, 0.35);

        $inline_css = sprintf(
            ':root{--jp-primary:%1$s;--jp-primary-dark:%2$s;--jp-primary-light:%3$s;} .juntaplay-button--primary{background:%1$s;border-color:%1$s;} .juntaplay-button--primary:hover,.juntaplay-button--primary:focus{background:%2$s;border-color:%2$s;} .juntaplay-badge{background:%3$s;color:%2$s;} .juntaplay-link{color:%1$s;} .juntaplay-link:hover,.juntaplay-link:focus{color:%2$s;}',
            $primary,
            $primary_dark,
            $primary_light
        );

        wp_add_inline_style('juntaplay', $inline_css);

        wp_localize_script('juntaplay', 'JuntaPlay', [
            'ajax'   => admin_url('admin-ajax.php'),
            'nonce'  => wp_create_nonce('jp_nonce'),
            'assets' => [
                'groupCoverPlaceholder' => JP_GROUP_COVER_PLACEHOLDER,
            ],
        ]);
    }

    private function shade_color(string $hex, float $percent): string
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        $num = hexdec($hex);
        $r = ($num >> 16) & 0xff;
        $g = ($num >> 8) & 0xff;
        $b = $num & 0xff;

        $percent = max(-1, min(1, $percent));

        if ($percent < 0) {
            $r = (int) max(0, round($r * (1 + $percent)));
            $g = (int) max(0, round($g * (1 + $percent)));
            $b = (int) max(0, round($b * (1 + $percent)));
        } else {
            $r = (int) min(255, round($r + (255 - $r) * $percent));
            $g = (int) min(255, round($g + (255 - $g) * $percent));
            $b = (int) min(255, round($b + (255 - $b) * $percent));
        }

        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }

    public function ensure_upload_permissions(): void
    {
        foreach (['subscriber', 'customer'] as $role_key) {
            $role = get_role($role_key);

            if (!$role || $role->has_cap('upload_files')) {
                continue;
            }

            $role->add_cap('upload_files');
        }
    }
}
