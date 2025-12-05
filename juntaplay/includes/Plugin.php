<?php
declare(strict_types=1);

namespace JuntaPlay;

use JuntaPlay\Admin\Settings;
use JuntaPlay\Admin\PoolCategories;
use JuntaPlay\Notifications\EmailHelper;
use function apply_filters;
use function get_post;
use function get_post_meta;
use function get_permalink;
use function get_role;
use function has_shortcode;
use function is_admin;
use function is_singular;
use function trailingslashit;
use function wp_login_url;
use function wp_doing_ajax;
use function wp_enqueue_script;
use function wp_enqueue_style;
use function wp_register_script;
use function wp_register_style;
use function wp_style_is;

defined('ABSPATH') || exit;

class Plugin
{
    public function init(): void
    {
        Installer::maybe_upgrade();

        // Admin
        (new Admin\Menu())->init();
        (new Admin\Settings())->init();
        (new Admin\PoolCategories())->init();
        (new Admin\Importer())->init();
        (new Admin\Groups())->init();
        (new Admin\Pools())->init();
        (new Admin\Splits())->init();

        // Frontend
        $auth = new Front\Auth();
        $auth->init();

        $group_creation = new Front\GroupCreation($auth);
        $group_creation->init();

        $profile = new Front\Profile();
        $profile->init();

        (new Front\CheckoutDeposit())->init();
        (new Front\Shortcodes($auth, $profile))->init();
        (new Front\Ajax($profile))->init();
        (new Front\Rest())->init();
        (new Front\CheckoutColumns())->init();
        (new Front\CheckoutThankYou())->init();
        (new Front\MercadoPagoWebhook())->init();

        EmailHelper::init();
        (new Notifications\Groups())->init();
        (new Notifications\Credits())->init();
        (new Notifications\Splits())->init();

        // WooCommerce integration
        if (class_exists('\\WooCommerce')) {
            (new Woo\ProductType())->init();
            (new Woo\Hooks())->init();
            (new Woo\Checkout())->init();
        }

        // Elementor widgets
        add_action('elementor/widgets/register', [$this, 'register_elementor_widgets']);

        // Assets
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('init', [$this, 'ensure_upload_permissions']);
        add_filter('upload_mimes', [$this, 'allow_group_icon_mimes']);
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
        if (is_admin() || wp_doing_ajax()) {
            return;
        }

        [$style_file, $script_file] = $this->get_asset_files();

        wp_register_style('juntaplay', JP_URL . $style_file, [], JP_VERSION);
        wp_register_script('juntaplay', JP_URL . $script_file, ['jquery'], JP_VERSION, true);

        $access_script = defined('WP_DEBUG') && WP_DEBUG
            ? '/assets/js/juntaplay-access.js'
            : '/assets/js/juntaplay-access.min.js';

        wp_register_script('juntaplay-access', JP_URL . $access_script, ['jquery', 'juntaplay'], JP_VERSION, true);

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

        $login_page_id = (int) get_option('juntaplay_page_entrar');
        $login_url     = $login_page_id ? get_permalink($login_page_id) : wp_login_url();

        wp_localize_script('juntaplay', 'JuntaPlay', [
            'ajax'   => admin_url('admin-ajax.php'),
            'nonce'  => wp_create_nonce('jp_nonce'),
            'assets' => [
                'groupCoverPlaceholder' => JP_GROUP_COVER_PLACEHOLDER,
            ],
            'auth'   => [
                'loggedIn'      => is_user_logged_in(),
                'loginUrl'      => $login_url,
                'redirectParam' => 'redirect_to',
            ],
        ]);

        $present_shortcodes = $this->get_present_shortcodes();

        if (!$this->should_enqueue_assets($present_shortcodes)) {
            return;
        }

        if (!wp_style_is('juntaplay', 'enqueued')) {
            wp_enqueue_style('juntaplay');
        }
        wp_enqueue_script('juntaplay');

        if ($this->should_enqueue_access_assets($present_shortcodes)) {
            wp_enqueue_script('juntaplay-access');
        }
    }

    /**
     * @param string[] $present_shortcodes
     */
    private function should_enqueue_assets(array $present_shortcodes): bool
    {
        $should_enqueue = !empty(array_intersect($present_shortcodes, $this->get_known_shortcodes()));

        if (function_exists('is_checkout') && is_checkout()) {
            $should_enqueue = true;
        }

        return (bool) apply_filters('juntaplay_should_enqueue_assets', $should_enqueue, $present_shortcodes);
    }

    /**
     * Determine the appropriate script/style assets to use, falling back to unminified files when missing.
     *
     * @return array{string,string}
     */
    private function get_asset_files(): array
    {
        $style_file  = (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG) ? 'assets/css/juntaplay.css' : 'assets/css/juntaplay.min.css';
        $script_file = (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG) ? 'assets/js/juntaplay.js' : 'assets/js/juntaplay.min.js';

        if (!file_exists(JP_DIR . $style_file)) {
            $style_file = 'assets/css/juntaplay.css';
        }

        if (!file_exists(JP_DIR . $script_file)) {
            $script_file = 'assets/js/juntaplay.js';
        }

        return [$style_file, $script_file];
    }

    /**
     * @param string[] $present_shortcodes
     */
    private function should_enqueue_access_assets(array $present_shortcodes): bool
    {
        $access_shortcodes = [
            'juntaplay_profile',
            'juntaplay_my_groups',
            'juntaplay_dashboard',
        ];

        return !empty(array_intersect($present_shortcodes, $access_shortcodes));
    }

    /**
     * @return string[]
     */
    private function get_present_shortcodes(): array
    {
        if (!is_singular()) {
            return [];
        }

        $post = get_post();

        if (!$post || !isset($post->post_content)) {
            return [];
        }

        $content = (string) $post->post_content;
        $found   = $this->extract_shortcodes_from_content($content);

        $elementor_shortcodes = $this->get_elementor_shortcodes((int) $post->ID);

        if ($elementor_shortcodes) {
            $found = array_merge($found, $elementor_shortcodes);
        }

        return array_values(array_unique($found));
    }

    /**
     * @param string $content
     *
     * @return string[]
     */
    private function extract_shortcodes_from_content(string $content): array
    {
        $found = [];

        foreach ($this->get_known_shortcodes() as $shortcode) {
            if (has_shortcode($content, $shortcode)) {
                $found[] = $shortcode;
            }
        }

        return array_values(array_unique($found));
    }

    /**
     * @return string[]
     */
    private function get_known_shortcodes(): array
    {
        return [
            'juntaplay_pools',
            'juntaplay_pool',
            'juntaplay_quota_selector',
            'juntaplay_my_quotas',
            'juntaplay_statement',
            'juntaplay_terms',
            'juntaplay_admin',
            'juntaplay_login_form',
            'juntaplay_dashboard',
            'juntaplay_profile',
            'juntaplay_credits',
            'juntaplay_my_groups',
            'juntaplay_groups',
            'juntaplay_group_search',
            'juntaplay_group_rotator',
            'juntaplay_two_factor',
            'juntaplay_header',
            'juntaplay_group_relationship',
            'juntaplay_group_create',
            'juntaplay_create_group_form',
        ];
    }

    /**
     * Detect Elementor widgets that render JuntaPlay shortcodes so assets can be loaded on those pages too.
     *
     * @return string[]
     */
    private function get_elementor_shortcodes(int $post_id): array
    {
        if (!class_exists('\\Elementor\\Plugin')) {
            return [];
        }

        $raw_data = get_post_meta($post_id, '_elementor_data', true);

        if (!$raw_data) {
            return [];
        }

        $data = is_array($raw_data) ? $raw_data : json_decode((string) $raw_data, true);

        if (!is_array($data)) {
            return [];
        }

        $map = [
            'juntaplay_pool_list'  => 'juntaplay_pools',
            'juntaplay_pool_hero'  => 'juntaplay_pool',
            'juntaplay_quota_grid' => 'juntaplay_quota_selector',
        ];

        $found = [];

        $walker = function (array $elements) use (&$walker, &$found, $map): void {
            foreach ($elements as $element) {
                if (($element['elType'] ?? '') === 'widget') {
                    $widget_type = (string) ($element['widgetType'] ?? '');

                    if (isset($map[$widget_type])) {
                        $found[] = $map[$widget_type];
                    }

                    if ($widget_type === 'shortcode' && !empty($element['settings']['shortcode'])) {
                        $found = array_merge(
                            $found,
                            $this->extract_shortcodes_from_content((string) $element['settings']['shortcode'])
                        );
                    }
                }

                if (!empty($element['elements']) && is_array($element['elements'])) {
                    $walker($element['elements']);
                }
            }
        };

        $walker($data);

        return array_values(array_unique($found));
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

    /**
     * @param array<string, string> $mimes
     * @return array<string, string>
     */
    public function allow_group_icon_mimes(array $mimes): array
    {
        $mimes['png']  = 'image/png';
        $mimes['jpg']  = 'image/jpeg';
        $mimes['jpeg'] = 'image/jpeg';
        $mimes['webp'] = 'image/webp';

        return $mimes;
    }
}
