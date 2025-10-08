<?php
declare(strict_types=1);

namespace JuntaPlay\Front;

defined('ABSPATH') || exit;

use JuntaPlay\Admin\Settings;
use JuntaPlay\Data\Groups;
use function apply_filters;
use function add_query_arg;
use function esc_html__;
use function esc_url;
use function get_current_user_id;
use function get_option;
use function get_permalink;
use function home_url;
use function is_user_logged_in;
use function sanitize_key;
use function sanitize_text_field;
use function shortcode_atts;
use function wc_get_order;
use function wp_enqueue_script;
use function wp_safe_redirect;
use function wp_login_url;
use function wp_script_is;
use function wp_unslash;
use function wp_validate_redirect;
use function rawurlencode;

class Shortcodes
{
    public function __construct(private Auth $auth, private Profile $profile)
    {
    }

    public function init(): void
    {
        add_shortcode('juntaplay_pools', [$this, 'pools']);
        add_shortcode('juntaplay_pool', [$this, 'pool']);
        add_shortcode('juntaplay_quota_selector', [$this, 'quota_selector']);
        add_shortcode('juntaplay_my_quotas', [$this, 'my_quotas']);
        add_shortcode('juntaplay_statement', [$this, 'statement']);
        add_shortcode('juntaplay_terms', [$this, 'terms']);
        add_shortcode('juntaplay_admin', [$this, 'admin_panel']);
        add_shortcode('juntaplay_login_form', [$this, 'login_form']);
        add_shortcode('juntaplay_dashboard', [$this, 'dashboard']);
        add_shortcode('juntaplay_profile', [$this, 'profile']);
        add_shortcode('juntaplay_groups', [$this, 'groups_directory']);
        add_shortcode('juntaplay_group_search', [$this, 'group_search']);
        add_shortcode('juntaplay_group_rotator', [$this, 'group_rotator']);
        add_shortcode('juntaplay_two_factor', [$this, 'two_factor']);
    }

    public function pools($atts = [], $content = ''): string
    {
        ob_start();
        include JP_DIR . 'templates/pool-list.php';

        return (string) ob_get_clean();
    }

    public function pool($atts = []): string
    {
        $atts    = shortcode_atts(['id' => 0], $atts, 'juntaplay_pool');
        $pool_id = (int) $atts['id'];

        ob_start();
        $current_pool_id = $pool_id;
        include JP_DIR . 'templates/pool-single.php';

        return (string) ob_get_clean();
    }

    public function quota_selector($atts = []): string
    {
        $atts = shortcode_atts([
            'id'       => 0,
            'per_page' => 100,
            'search'   => 'true',
            'filter'   => 'true',
        ], $atts, 'juntaplay_quota_selector');

        if (!wp_script_is('juntaplay', 'enqueued')) {
            wp_enqueue_script('juntaplay');
        }

        ob_start();
        $current_pool_id = (int) $atts['id'];
        $per_page        = (int) $atts['per_page'];
        $show_search     = $atts['search'] === 'true';
        $show_filter     = $atts['filter'] === 'true';
        include JP_DIR . 'templates/quota-grid.php';

        return (string) ob_get_clean();
    }

    public function my_quotas(): string
    {
        if (!is_user_logged_in()) {
            return '<p>' . esc_html__('Faça login para ver suas cotas.', 'juntaplay') . '</p>';
        }

        ob_start();
        include JP_DIR . 'templates/my-quotas.php';

        return (string) ob_get_clean();
    }

    public function group_search($atts = []): string
    {
        $atts = shortcode_atts([
            'button' => esc_html__('Buscar', 'juntaplay'),
        ], $atts, 'juntaplay_group_search');

        $groups_page_id = (int) get_option('juntaplay_page_grupos');
        $action         = $groups_page_id ? get_permalink($groups_page_id) : home_url('/');

        $categories = Groups::get_category_labels();
        $preferred_keys = ['video', 'music', 'education', 'reading', 'office', 'games'];
        $filtered_categories = array_filter(
            $categories,
            static fn ($label, $key): bool => in_array($key, $preferred_keys, true),
            ARRAY_FILTER_USE_BOTH
        );

        if (empty($filtered_categories)) {
            $filtered_categories = $categories;
        }

        $search_value = '';
        if (isset($_GET['search'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $search_value = sanitize_text_field((string) wp_unslash($_GET['search'])); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        }

        $category_value = '';
        if (isset($_GET['category'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $candidate = sanitize_key((string) wp_unslash($_GET['category'])); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            if (isset($categories[$candidate])) {
                $category_value = $candidate;
            }
        }

        ob_start();
        $hero_button      = $atts['button'];
        $hero_action      = $action;
        $hero_categories  = $filtered_categories;
        $hero_search      = $search_value;
        $hero_category    = $category_value;
        include JP_DIR . 'templates/group-search-hero.php';

        return (string) ob_get_clean();
    }

    public function group_rotator($atts = []): string
    {
        if (!wp_script_is('juntaplay', 'enqueued')) {
            wp_enqueue_script('juntaplay');
        }

        $general = get_option(Settings::OPTION_GENERAL, []);
        $default_limit = isset($general['group_rotator_limit']) ? (int) $general['group_rotator_limit'] : 12;
        if ($default_limit <= 0) {
            $default_limit = 12;
        }

        $atts = shortcode_atts([
            'title'       => esc_html__('Descubra grupos com vagas agora', 'juntaplay'),
            'description' => esc_html__('Seleção dinâmica de campanhas criadas pela comunidade com moderação do super admin.', 'juntaplay'),
            'button'      => esc_html__('Ver todos os grupos', 'juntaplay'),
            'limit'       => 0,
            'category'    => '',
        ], $atts, 'juntaplay_group_rotator');

        $limit = (int) ($atts['limit'] ?: $default_limit);
        $limit = max(4, min(40, $limit));

        $categories = Groups::get_category_labels();

        $preferred_keys = ['video', 'music', 'education', 'reading', 'office', 'games'];
        $filtered_categories = array_filter(
            $categories,
            static fn ($label, $key): bool => in_array($key, $preferred_keys, true),
            ARRAY_FILTER_USE_BOTH
        );

        if (!empty($filtered_categories)) {
            $categories = $filtered_categories;
        }

        $default_category = (string) $atts['category'];
        if ($default_category !== '' && !isset($categories[$default_category])) {
            $default_category = '';
        }

        $groups_page_id   = (int) get_option('juntaplay_page_grupos');
        $directory_url    = $groups_page_id ? get_permalink($groups_page_id) : home_url('/grupos');

        ob_start();
        $rotator_limit            = $limit;
        $rotator_categories       = $categories;
        $rotator_default_category = $default_category;
        $rotator_directory_url    = $directory_url;
        include JP_DIR . 'templates/group-rotator.php';

        return (string) ob_get_clean();
    }

    public function statement($atts = []): string
    {
        if (!is_user_logged_in()) {
            return '<p>' . esc_html__('Faça login para visualizar seu extrato.', 'juntaplay') . '</p>';
        }

        if (!class_exists('\\WooCommerce')) {
            return '<p>' . esc_html__('WooCommerce é necessário para exibir o extrato.', 'juntaplay') . '</p>';
        }

        $atts = shortcode_atts([
            'order_id' => 0,
        ], $atts, 'juntaplay_statement');

        $order_id = (int) ($atts['order_id'] ?: (isset($_GET['order_id']) ? wp_unslash($_GET['order_id']) : 0)); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $order_id = absint($order_id);

        if (!$order_id) {
            return '<p class="juntaplay-notice">' . esc_html__('Selecione um pedido para visualizar o extrato.', 'juntaplay') . '</p>';
        }

        $order = wc_get_order($order_id);

        if (!$order) {
            return '<p class="juntaplay-notice">' . esc_html__('Pedido não encontrado.', 'juntaplay') . '</p>';
        }

        if ((int) $order->get_user_id() !== get_current_user_id()) {
            return '<p class="juntaplay-notice">' . esc_html__('Você não tem permissão para acessar este extrato.', 'juntaplay') . '</p>';
        }

        global $wpdb;

        $items = [];
        foreach ($order->get_items() as $item) {
            $pool_id = (int) $item->get_meta('JuntaPlay Pool', true);
            $raw_numbers = $item->get_meta('JuntaPlay Cotas', true);
            $numbers = array_filter(array_map('absint', array_map('trim', explode(',', (string) $raw_numbers))));

            if (!$pool_id || !$numbers) {
                continue;
            }

            sort($numbers);

            $placeholders = implode(',', array_fill(0, count($numbers), '%d'));
            $prepared     = $wpdb->prepare(
                "SELECT number, status FROM {$wpdb->prefix}jp_quotas WHERE pool_id = %d AND number IN ($placeholders)",
                ...array_merge([$pool_id], $numbers)
            );
            $quota_rows   = $prepared ? $wpdb->get_results($prepared, ARRAY_A) : [];
            $status_map   = [];

            if ($quota_rows) {
                foreach ($quota_rows as $quota_row) {
                    $status_map[(int) $quota_row['number']] = (string) $quota_row['status'];
                }
            }

            $pool       = \JuntaPlay\Data\Pools::get($pool_id);
            $pool_title = $pool->title ?? $item->get_name();
            $pool_link  = '';

            if ($pool && !empty($pool->product_id)) {
                $pool_link = get_permalink((int) $pool->product_id);
            }

            $pool_link = apply_filters('juntaplay_pool_permalink', $pool_link, $pool_id, $pool);
            $pool_link = $pool_link ? (string) $pool_link : '';

            $items[] = [
                'pool_id'     => $pool_id,
                'title'       => $pool_title,
                'link'        => $pool_link,
                'numbers'     => $numbers,
                'statuses'    => $status_map,
                'line_total'  => $item->get_total() + $item->get_total_tax(),
                'quantity'    => $item->get_quantity(),
                'line_subtotal' => $item->get_subtotal(),
            ];
        }

        if (!$items) {
            return '<p class="juntaplay-notice">' . esc_html__('Nenhuma cota vinculada a este pedido.', 'juntaplay') . '</p>';
        }

        $balance = (float) apply_filters('juntaplay_statement_balance', 0.0, $order);

        ob_start();
        $statement_order  = $order;
        $statement_items  = $items;
        $statement_balance = $balance;
        include JP_DIR . 'templates/statement.php';

        return (string) ob_get_clean();
    }

    public function terms(): string
    {
        ob_start();
        include JP_DIR . 'templates/terms.php';

        return (string) ob_get_clean();
    }

    public function profile(): string
    {
        if (!is_user_logged_in()) {
            $profile_page_id = (int) get_option('juntaplay_page_perfil');
            $redirect        = $profile_page_id ? get_permalink($profile_page_id) : '';

            if (!$redirect) {
                $redirect = home_url('/perfil');
            }

            $login_page_id = (int) get_option('juntaplay_page_entrar');
            $login_url     = $login_page_id ? get_permalink($login_page_id) : wp_login_url($redirect);

            if ($login_page_id && $login_url) {
                $login_url = add_query_arg('redirect_to', rawurlencode($redirect), $login_url);
            }

            $login_url = $login_url ?: wp_login_url($redirect);

            return '<p class="juntaplay-notice">' . esc_html__('Faça login para atualizar seus dados.', 'juntaplay') . ' '
                . '<a class="juntaplay-link" href="' . esc_url($login_url) . '">' . esc_html__('Entrar agora', 'juntaplay')
                . '</a></p>';
        }

        $profile_sections       = $this->profile->get_sections();
        $profile_errors         = $this->profile->get_errors();
        $profile_notices        = $this->profile->get_notices();
        $profile_active_section = $this->profile->get_active_section();

        if (function_exists('wp_enqueue_media')) {
            wp_enqueue_media();
        }

        ob_start();
        include JP_DIR . 'templates/profile.php';

        return (string) ob_get_clean();
    }

    public function admin_panel(): string
    {
        if (!current_user_can('manage_options')) {
            return '<p>' . esc_html__('Acesso restrito.', 'juntaplay') . '</p>';
        }

        ob_start();
        include JP_DIR . 'templates/admin-panel.php';

        return (string) ob_get_clean();
    }

    public function login_form(): string
    {
        if (is_user_logged_in()) {
            $redirect = $this->auth->get_redirect_url();
            if (!$redirect) {
                $redirect = $this->auth->get_default_redirect();
            }

            wp_safe_redirect($redirect);
            exit;
        }

        $login_errors    = $this->auth->get_login_errors();
        $register_errors = $this->auth->get_register_errors();
        $active_view     = $this->auth->get_active_view();
        $redirect_to     = $this->auth->get_redirect_url();

        if (!$redirect_to) {
            $redirect_to = $this->auth->get_default_redirect();
        }

        if (!$redirect_to && isset($_GET['redirect_to'])) {
            $raw_redirect = wp_unslash($_GET['redirect_to']);
            $validated    = wp_validate_redirect($raw_redirect, '');
            if (is_string($validated)) {
                $redirect_to = $validated;
            }
        }

        if ($active_view !== 'register' && isset($_GET['action']) && sanitize_key(wp_unslash($_GET['action'])) === 'register') { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $active_view = 'register';
        }

        ob_start();
        include JP_DIR . 'templates/auth/login.php';

        return (string) ob_get_clean();
    }

    public function dashboard(): string
    {
        if (!is_user_logged_in()) {
            $login_page_id = (int) get_option('juntaplay_page_entrar');
            $redirect      = $this->auth->get_default_redirect();
            $login_url     = $login_page_id ? get_permalink($login_page_id) : wp_login_url($redirect);

            if ($login_page_id && $login_url) {
                $login_url = add_query_arg('redirect_to', rawurlencode($redirect), $login_url);
            }

            $login_url = $login_url ?: wp_login_url($redirect);

            return '<p class="juntaplay-notice">' . esc_html__('Faça login para acessar seu painel.', 'juntaplay') . ' ' .
                '<a class="juntaplay-link" href="' . esc_url($login_url) . '">' . esc_html__('Entrar agora', 'juntaplay') . '</a></p>';
        }

        if (function_exists('wp_enqueue_media')) {
            wp_enqueue_media();
        }

        ob_start();
        include JP_DIR . 'templates/dashboard.php';

        return (string) ob_get_clean();
    }

    public function groups_directory(): string
    {
        $categories = Groups::get_category_labels();

        ob_start();
        include JP_DIR . 'templates/groups-directory.php';

        return (string) ob_get_clean();
    }

    public function two_factor(): string
    {
        if (is_user_logged_in()) {
            $redirect = $this->auth->get_default_redirect();
            wp_safe_redirect($redirect);
            exit;
        }

        $context = $this->auth->get_two_factor_context();
        $errors  = $this->auth->get_two_factor_errors();

        $login_page_id = (int) get_option('juntaplay_page_entrar');
        $login_url     = $login_page_id ? get_permalink($login_page_id) : wp_login_url();

        ob_start();
        include JP_DIR . 'templates/auth/two-factor.php';

        return (string) ob_get_clean();
    }
}
