<?php
declare(strict_types=1);

namespace JuntaPlay\Front;

defined('ABSPATH') || exit;

use JuntaPlay\Admin\Settings;
use JuntaPlay\Data\Groups;
use JuntaPlay\Data\Notifications;
use JuntaPlay\Data\GroupChats;
use JuntaPlay\Chat\ChatMessage;
use JuntaPlay\Chat\ChatShortcode;
use JuntaPlay\Chat\ChatAjax;
use function absint;
use function apply_filters;
use function add_query_arg;
use function esc_html__;
use function esc_url;
use function esc_url_raw;
use function get_current_user_id;
use function get_option;
use function get_permalink;
use function home_url;
use function is_user_logged_in;
use function sanitize_key;
use function sanitize_text_field;
use function sanitize_title;
use function shortcode_atts;
use function wc_get_checkout_url;
use function wc_get_endpoint_url;
use function wc_get_order;
use function wc_get_page_permalink;
use function wc_price;
use function wp_enqueue_media;
use function wp_enqueue_style;
use function wp_enqueue_script;
use function wp_safe_redirect;
use function wp_login_url;
use function wp_logout_url;
use function wp_style_is;
use function wp_script_is;
use function wp_unslash;
use function wp_validate_redirect;
use function wp_verify_nonce;
use function rawurlencode;
use function esc_js;
use function nocache_headers;
use function wp_json_encode;
use function rest_url;
use function wp_create_nonce;
use function trailingslashit;
use function do_shortcode;

class Shortcodes
{
    private bool $rendered_auth_modal = false;
    private ?array $auth_modal_state = null;

    public function __construct(private Auth $auth, private Profile $profile)
    {
    }

    public function init(): void
    {
        ChatShortcode::init();
        ChatAjax::init();

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
        add_shortcode('juntaplay_credits', [$this, 'credits']);
        add_shortcode('juntaplay_my_groups', [$this, 'my_groups']);
        add_shortcode('juntaplay_groups', [$this, 'groups_directory']);
        add_shortcode('juntaplay_group_search', [$this, 'group_search']);
        add_shortcode('juntaplay_group_rotator', [$this, 'group_rotator']);
        add_shortcode('juntaplay_two_factor', [$this, 'two_factor']);
        add_shortcode('juntaplay_header', [$this, 'header']);
        add_shortcode('juntaplay_group_relationship', [$this, 'group_relationship']);
        add_shortcode('juntaplay_messages', [$this, 'messages']);
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

        if (!wp_script_is('juntaplay', 'enqueued')) {
            wp_enqueue_script('juntaplay');
        }

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

        $has_search = ($search_value !== '' || $category_value !== '');

        if ($has_search) {
            $search_term      = $search_value;
            $search_category  = $category_value;
            $search_categories = $categories;
            include JP_DIR . 'templates/group-search-results.php';
        }

        return (string) ob_get_clean();
    }

    public function group_rotator($atts = []): string
    {
        if (!wp_script_is('juntaplay', 'enqueued')) {
            wp_enqueue_script('juntaplay');
        }

        $general = get_option(Settings::OPTION_GENERAL, []);
        $default_limit = isset($general['group_rotator_limit']) ? (int) $general['group_rotator_limit'] : 18;
        if ($default_limit <= 0) {
            $default_limit = 18;
        }

        $atts = shortcode_atts([
            'title'       => esc_html__('', 'juntaplay'),
            'description' => esc_html__('', 'juntaplay'),
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

        $groups_page_id = (int) get_option('juntaplay_page_grupos');
        $directory_url  = $groups_page_id ? get_permalink($groups_page_id) : home_url('/grupos');

        $login_page_id = (int) get_option('juntaplay_page_entrar');
        $login_url     = $login_page_id ? get_permalink($login_page_id) : wp_login_url();
        $redirect_param = 'redirect_to';

        $rotator_logged_in  = is_user_logged_in();
        $rotator_login_url  = $login_url;
        $rotator_redirect_param = $redirect_param;

        ob_start();
        $rotator_limit            = $limit;
        $rotator_categories       = $categories;
        $rotator_default_category = $default_category;
        $rotator_directory_url    = $directory_url;
        $rotator_title            = (string) $atts['title'];
        $rotator_description      = (string) $atts['description'];
        $rotator_cta_label        = (string) $atts['button'];
        $rotator_logged_in        = $rotator_logged_in;
        $rotator_login_url        = $rotator_login_url;
        $rotator_redirect_param   = $rotator_redirect_param;
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

        $profile_sections_raw = $this->profile->get_sections();

        $chat_section = [
            'slug'        => 'juntaplay-chat',
            'label'       => __('Central de Mensagens', 'juntaplay'),
            'title'       => __('Central de Mensagens', 'juntaplay'),
            'description' => __('Canal direto para sanar dúvidas e enviar mensagens sobre o grupo.', 'juntaplay'),
            'icon'        => 'message',
            'items'       => [
                'juntaplay-chat' => [
                    'label'       => __('Central de Mensagens', 'juntaplay'),
                    'description' => __('Canal direto para sanar dúvidas e enviar mensagens sobre o grupo.', 'juntaplay'),
                    'type'        => 'custom',
                    'editable'    => false,
                    'html'        => do_shortcode('[juntaplay_chat]'),
                ],
            ],
        ];

        $profile_sections_raw = is_array($profile_sections_raw) ? $profile_sections_raw : [];

        $profile_sections = [];
        $inserted_chat    = false;

        foreach ($profile_sections_raw as $key => $section) {
            if (!is_array($section)) {
                continue;
            }

            $section_slug = '';
            if (isset($section['slug']) && is_string($section['slug'])) {
                $section_slug = $section['slug'];
            } elseif (is_string($key)) {
                $section_slug = $key;
            }

            if ($section_slug === '') {
                continue;
            }

            $profile_sections[$section_slug] = $section;

            if (!$inserted_chat && ($section_slug === 'finance' || $section_slug === 'financeiro')) {
                $profile_sections['juntaplay-chat'] = $chat_section;
                $inserted_chat = true;
            }
        }

        if (!$inserted_chat) {
            $profile_sections = ['juntaplay-chat' => $chat_section] + $profile_sections;
        }

        $profile_errors         = $this->profile->get_errors();
        $profile_notices        = $this->profile->get_notices();
        $profile_active_section = $this->profile->get_active_section();

        if (isset($_GET['section'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $requested_section = sanitize_key(wp_unslash((string) $_GET['section'])); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

            if ($requested_section && isset($profile_sections[$requested_section])) {
                $profile_active_section = $requested_section;
            }
        }

        if (function_exists('wp_enqueue_media')) {
            wp_enqueue_media();
        }

        ob_start();
        include JP_DIR . 'templates/profile.php';

        return (string) ob_get_clean();
    }

    public function group_relationship(): string
    {
        if (!wp_script_is('juntaplay', 'enqueued')) {
            wp_enqueue_script('juntaplay');
        }

        $slug = '';
        if (isset($_GET['grupo'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $slug_raw = wp_unslash((string) $_GET['grupo']); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $slug     = sanitize_title($slug_raw);
        }

        $group_id = 0;
        if (isset($_GET['group_id'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $group_id = absint(wp_unslash((string) $_GET['group_id'])); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        }

        $group = null;
        if ($slug !== '') {
            $group = Groups::get_public_by_slug($slug);
        }

        if (!$group && $group_id > 0) {
            $group = Groups::get_public_detail($group_id);
        }

        if (!$group) {
            return '<section class="juntaplay-relationship"><div class="juntaplay-relationship__container"><p>' . esc_html__('Grupo não encontrado ou indisponível no momento.', 'juntaplay') . '</p></div></section>';
        }

        $group_id = isset($group['id']) ? (int) $group['id'] : $group_id;
        $slug     = isset($group['slug']) ? (string) $group['slug'] : $slug;

        $relationship_options   = Groups::get_relationship_options();
        $relationship_type_raw  = isset($group['relationship_type']) ? (string) $group['relationship_type'] : '';
        $relationship_type      = Groups::normalize_relationship_key($relationship_type_raw);
        $relationship_label_text = isset($relationship_options[$relationship_type]) ? (string) $relationship_options[$relationship_type] : '';
        $selected_relationship   = $relationship_type;

        $errors         = [];
        $accept_checked = false;

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['jp_relationship_action'])) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
            $nonce_raw = isset($_POST['jp_relationship_nonce']) ? wp_unslash((string) $_POST['jp_relationship_nonce']) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
            $nonce     = sanitize_text_field($nonce_raw);

            if (!wp_verify_nonce($nonce, 'juntaplay_relationship_confirm')) {
                $errors[] = __('Sua sessão expirou. Recarregue a página e tente novamente.', 'juntaplay');
            }

            $choice_raw = isset($_POST['jp_relationship_choice']) ? wp_unslash((string) $_POST['jp_relationship_choice']) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
            $choice     = sanitize_key($choice_raw);

            if ($choice !== '') {
                if (isset($relationship_options[$choice])) {
                    $selected_relationship = $choice;
                }
            }

            if ($relationship_type !== '' && $choice !== $relationship_type) {
                $errors[] = __('Confirme o relacionamento exigido para continuar.', 'juntaplay');
            }

            $accept_checked = !empty($_POST['jp_relationship_accept']); // phpcs:ignore WordPress.Security.NonceVerification.Missing

            if (!$accept_checked) {
                $errors[] = __('Confirme que está ciente das condições antes de prosseguir.', 'juntaplay');
            }

            if (!$errors) {
                $checkout_url = '';
                if (function_exists('wc_get_checkout_url')) {
                    $checkout_url = wc_get_checkout_url();
                }

                if ($checkout_url) {
                    if ($slug !== '') {
                        $checkout_url = add_query_arg('grupo', rawurlencode($slug), $checkout_url);
                    } elseif ($group_id > 0) {
                        $checkout_url = add_query_arg('group_id', $group_id, $checkout_url);
                    }

                    $checkout_url = add_query_arg('jp_rel', '1', $checkout_url);
                    $validated_checkout = wp_validate_redirect($checkout_url, $checkout_url);

                    if ($validated_checkout) {
                        nocache_headers();
                        wp_safe_redirect($validated_checkout);

                        $fallback_url = esc_url($validated_checkout);
                        $fallback_message = esc_html__('Redirecionando para o checkout...', 'juntaplay');
                        $script_target = wp_json_encode($validated_checkout);
                        if (!$script_target) {
                            $script_target = '"' . esc_js($validated_checkout) . '"';
                        }

                        printf(
                            '<!DOCTYPE html><html><head><meta charset="utf-8" /><meta http-equiv="refresh" content="0;url=%1$s" /><script>window.location.replace(%2$s);</script></head><body style="font:16px/1.6 -apple-system,BlinkMacSystemFont,\"Segoe UI\",sans-serif;text-align:center;padding:3rem 1rem;color:#1f2937;background:#f3fafc;">%3$s</body></html>',
                            $fallback_url,
                            $script_target,
                            $fallback_message
                        );
                        exit;
                    }
                }

                $errors[] = __('Não foi possível direcionar para o checkout no momento. Tente novamente em instantes.', 'juntaplay');
            }
        }

        $service_name = (string) ($group['service_name'] ?? '');
        $group_title  = (string) ($group['title'] ?? $service_name);

        $price_value = isset($group['member_price']) ? (float) $group['member_price'] : 0.0;
        if ($price_value <= 0 && isset($group['price_promotional'])) {
            $price_value = (float) $group['price_promotional'];
        }
        if ($price_value <= 0 && isset($group['price_regular'])) {
            $price_value = (float) $group['price_regular'];
        }
        $price_label = $price_value > 0 ? wc_price($price_value) : '';

        $back_param = isset($_GET['back']) ? (string) wp_unslash($_GET['back']) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $back_raw   = $back_param !== '' ? rawurldecode($back_param) : '';
        $back_url   = $back_raw !== '' ? esc_url_raw($back_raw) : '';

        if ($back_url === '') {
            $groups_page_id = (int) get_option('juntaplay_page_grupos');
            $back_base      = $groups_page_id ? get_permalink($groups_page_id) : home_url('/grupos');

            if ($back_base) {
                if ($slug !== '') {
                    $back_url = add_query_arg('grupo', rawurlencode($slug), $back_base);
                } elseif ($group_id > 0) {
                    $back_url = add_query_arg('group_id', $group_id, $back_base);
                } else {
                    $back_url = $back_base;
                }
            }
        }

        $cards = [];
        foreach ($relationship_options as $key => $label) {
            $key_string  = (string) $key;
            $is_required = $relationship_type !== '';
            $is_selected = $selected_relationship !== '' && $selected_relationship === $key_string;

            $cards[] = [
                'key'      => $key_string,
                'label'    => (string) $label,
                'active'   => $is_selected,
                'locked'   => false,
                'disabled' => false,
            ];
        }

        if ($selected_relationship === '' && $cards) {
            $cards[0]['active']   = true;
            $cards[0]['locked']   = false;
        }

        ob_start();
        $relationship_cards   = $cards;
        $relationship_errors  = $errors;
        $relationship_accept  = $accept_checked;
        $relationship_title   = $group_title !== '' ? $group_title : $service_name;
        $relationship_service = $service_name;
        $relationship_price   = $price_label;
        $relationship_label   = $relationship_label_text;
        $relationship_required = $relationship_type;
        $relationship_back    = $back_url;
        $relationship_group_id = $group_id;
        $relationship_slug     = $slug;
        include JP_DIR . 'templates/group-relationship.php';

        return (string) ob_get_clean();
    }

    public function header(): string
    {
        $is_guest = !is_user_logged_in();

        $header_context   = [];
        $header_menu      = [];
        $notifications_unread = 0;
        $header_auth_context  = [];
        $header_auth_auto_open = '';
        if (!wp_style_is('juntaplay', 'enqueued')) {
            wp_enqueue_style('juntaplay');
        }
        if (!wp_script_is('juntaplay', 'enqueued')) {
            wp_enqueue_script('juntaplay');
        }

        if ($is_guest) {
            $header_auth_context = $this->build_auth_template_context();
            $existing_redirect   = isset($header_auth_context['redirect_to']) ? (string) $header_auth_context['redirect_to'] : '';
            $default_redirect    = $this->auth->get_default_redirect();
            $request_uri         = isset($_SERVER['REQUEST_URI']) ? wp_unslash($_SERVER['REQUEST_URI']) : '';
            $current_url         = '';

            if (is_string($request_uri) && $request_uri !== '') {
                $current_url = esc_url_raw(home_url($request_uri));
            }

            if ($current_url !== '' && ($existing_redirect === '' || $existing_redirect === $default_redirect)) {
                $header_auth_context['redirect_to'] = $current_url;
            }

            if (!empty($header_auth_context['login_errors'])) {
                $header_auth_auto_open = 'login';
            } elseif (!empty($header_auth_context['register_errors'])) {
                $header_auth_auto_open = 'register';
            } else {
                $requested_view = '';

                if (isset($_REQUEST['jp_auth_view'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                    $requested_view = sanitize_key(wp_unslash($_REQUEST['jp_auth_view'])); // phpcs:ignore WordPress.Security.NonceVerification
                } elseif (isset($_GET['action'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                    $requested_view = sanitize_key(wp_unslash($_GET['action'])); // phpcs:ignore WordPress.Security.NonceVerification
                }

                if ($requested_view === 'register') {
                    $header_auth_auto_open = 'register';
                } elseif ($requested_view === 'login') {
                    $header_auth_auto_open = 'login';
                } elseif (($header_auth_context['active_view'] ?? '') === 'register') {
                    $header_auth_auto_open = 'register';
                }
            }
        } else {
            $header_context = $this->profile->get_header_context();

            if ($header_context === []) {
                return '';
            }

            $notifications_unread = Notifications::count_unread(get_current_user_id());

            $chat           = new ChatMessage();
            $user_groups    = Groups::get_groups_for_user(get_current_user_id());
            $is_group_admin = !empty($user_groups['owned']);

            $chat_unread = $is_group_admin
                ? $chat->count_unread_for_admin(get_current_user_id())
                : $chat->count_unread_for_user(get_current_user_id());

            $notifications_unread += $chat_unread;
            $header_menu           = $this->build_header_menu_links();
        }

        $login_page_id   = (int) get_option('juntaplay_page_entrar');
        $login_base_url  = $login_page_id ? get_permalink($login_page_id) : '';

        if (!$login_base_url) {
            $login_base_url = wp_login_url();
        }

        $header_login_url      = $login_base_url ?: '';
        $header_register_url   = '';
        $header_redirect_value = isset($header_auth_context['redirect_to']) ? (string) $header_auth_context['redirect_to'] : '';

        if ($login_page_id && $login_base_url) {
            $header_login_url    = add_query_arg('jp_auth_view', 'login', $login_base_url);
            $header_register_url = add_query_arg('jp_auth_view', 'register', $login_base_url);
        } else {
            if (function_exists('wp_registration_url')) {
                $header_register_url = wp_registration_url();
            }

            if (!$header_register_url && $header_login_url) {
                $header_register_url = add_query_arg('action', 'register', $header_login_url);
            }
        }

        if ($header_redirect_value !== '') {
            $encoded_redirect = rawurlencode($header_redirect_value);
            if ($header_login_url !== '') {
                $header_login_url = add_query_arg('redirect_to', $encoded_redirect, $header_login_url);
            }

            if ($header_register_url !== '') {
                $header_register_url = add_query_arg('redirect_to', $encoded_redirect, $header_register_url);
            }
        }

        if ($is_guest) {
            $this->auth_modal_state = [
                'context'   => $header_auth_context,
                'auto_open' => $header_auth_auto_open,
            ];

            if (!$this->rendered_auth_modal) {
                add_action('wp_footer', [$this, 'render_auth_modal']);
                $this->rendered_auth_modal = true;
            }
        }

        ob_start();
        $header_guest                = $is_guest;
        $header_menu_items           = $header_menu;
        $header_notifications_unread = $notifications_unread;
        $header_auth_context         = $header_auth_context;
        $header_auth_auto_open       = $header_auth_auto_open;
        include JP_DIR . 'templates/header-bar.php';

        return (string) ob_get_clean();
    }

    public function render_auth_modal(): void
    {
        if (!$this->auth_modal_state) {
            return;
        }

        $auth_context = is_array($this->auth_modal_state['context'] ?? null) ? $this->auth_modal_state['context'] : [];
        $auto_open    = isset($this->auth_modal_state['auto_open']) ? (string) $this->auth_modal_state['auto_open'] : '';

        ob_start();
        include JP_DIR . 'templates/auth/modal-wrapper.php';
        echo ob_get_clean();
    }

    public function credits(): string
    {
        if (!is_user_logged_in()) {
            $login_url = wp_login_url();

            return '<p class="juntaplay-notice">' . esc_html__('Faça login para visualizar seus créditos.', 'juntaplay')
                . ($login_url ? ' <a href="' . esc_url($login_url) . '">' . esc_html__('Entrar', 'juntaplay') . '</a>' : '')
                . '</p>';
        }

        if (!wp_script_is('juntaplay', 'enqueued')) {
            wp_enqueue_script('juntaplay');
        }

        ob_start();
        $context = $this->profile->get_credit_page_context();
        include JP_DIR . 'templates/credits.php';

        return (string) ob_get_clean();
    }

    public function my_groups(): string
    {
        if (!is_user_logged_in()) {
            $login_url = wp_login_url();

            return '<p class="juntaplay-notice">' . esc_html__('Faça login para gerenciar seus grupos.', 'juntaplay')
                . ($login_url ? ' <a href="' . esc_url($login_url) . '">' . esc_html__('Entrar', 'juntaplay') . '</a>' : '')
                . '</p>';
        }

        if (!wp_script_is('juntaplay', 'enqueued')) {
            wp_enqueue_script('juntaplay');
        }

        if (function_exists('wp_enqueue_media')) {
            wp_enqueue_media();
        }

        ob_start();
        $context = $this->profile->get_groups_page_context();
        include JP_DIR . 'templates/my-groups.php';

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

        $auth_context = $this->build_auth_template_context();

        $login_errors    = $auth_context['login_errors'];
        $register_errors = $auth_context['register_errors'];
        $active_view     = $auth_context['active_view'];
        $redirect_to     = $auth_context['redirect_to'];

        ob_start();
        include JP_DIR . 'templates/auth/login.php';

        return (string) ob_get_clean();
    }

    private function build_auth_template_context(): array
    {
        $login_errors    = $this->auth->get_login_errors();
        $register_errors = $this->auth->get_register_errors();
        $active_view     = $this->auth->get_active_view();
        $redirect_to     = $this->auth->get_redirect_url();

        if ($redirect_to === '') {
            $redirect_to = $this->auth->get_default_redirect();
        }

        if ($redirect_to === '' && isset($_GET['redirect_to'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $raw_redirect = wp_unslash($_GET['redirect_to']); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $validated    = wp_validate_redirect($raw_redirect, '');
            if (is_string($validated)) {
                $redirect_to = $validated;
            }
        }

        if ($active_view !== 'register' && isset($_GET['action']) && sanitize_key(wp_unslash($_GET['action'])) === 'register') { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $active_view = 'register';
        }

        return [
            'login_errors'    => $login_errors,
            'register_errors' => $register_errors,
            'active_view'     => $active_view,
            'redirect_to'     => $redirect_to,
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function build_header_menu_links(): array
    {
        $dashboard_id    = (int) get_option('juntaplay_page_painel');
        $profile_page_id = (int) get_option('juntaplay_page_perfil');
        $credits_page_id = (int) get_option('juntaplay_page_creditos');
        $my_groups_id    = (int) get_option('juntaplay_page_meus-grupos');
        $my_quotas_id    = (int) get_option('juntaplay_page_minhas-cotas');
        $statement_id    = (int) get_option('juntaplay_page_extrato');

        $profile_url = $profile_page_id ? get_permalink($profile_page_id) : home_url('/perfil');

        $links = [
            [
                'label' => esc_html__('Painel', 'juntaplay'),
                'url'   => $dashboard_id ? get_permalink($dashboard_id) : home_url('/painel'),
                'icon'  => 'grid',
            ],
            [
                'label' => esc_html__('Perfil', 'juntaplay'),
                'url'   => $profile_url,
                'icon'  => 'user',
            ],
            [
                'label' => esc_html__('Mensagens', 'juntaplay'),
                'url'   => add_query_arg('section', 'juntaplay-chat', $profile_url),
                'icon'  => 'message',
            ],
            [
                'label' => esc_html__('Meus créditos', 'juntaplay'),
                'url'   => $credits_page_id ? get_permalink($credits_page_id) : home_url('/creditos'),
                'icon'  => 'wallet',
            ],
            [
                'label' => esc_html__('Meus grupos', 'juntaplay'),
                'url'   => $my_groups_id ? get_permalink($my_groups_id) : home_url('/meus-grupos'),
                'icon'  => 'users',
            ],
            [
                'label' => esc_html__('Minhas cotas', 'juntaplay'),
                'url'   => $my_quotas_id ? get_permalink($my_quotas_id) : home_url('/minhas-cotas'),
                'icon'  => 'ticket',
            ],
            [
                'label' => esc_html__('Extrato', 'juntaplay'),
                'url'   => $statement_id ? get_permalink($statement_id) : home_url('/extrato'),
                'icon'  => 'receipt',
            ],
            [
                'label' => esc_html__('Sair', 'juntaplay'),
                'url'   => wp_logout_url(home_url('/')),
                'icon'  => 'logout',
                'type'  => 'logout',
            ],
        ];

        $seen = [];
        $links = array_values(array_filter($links, static function (array $item) use (&$seen): bool {
            $url = isset($item['url']) ? (string) $item['url'] : '';

            if ($url === '') {
                return false;
            }

            if (isset($seen[$url])) {
                return false;
            }

            $seen[$url] = true;

            return true;
        }));

        return apply_filters('juntaplay/header/menu', $links);
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

    public function messages(): string
    {
        if (!is_user_logged_in()) {
            $login_url = wp_login_url();
            return '<p>' . sprintf(
                esc_html__('Faça login para acessar suas mensagens. %s', 'juntaplay'),
                '<a class="juntaplay-link" href="' . esc_url($login_url) . '">' . esc_html__('Entrar', 'juntaplay') . '</a>'
            ) . '</p>';
        }

        $user_id = get_current_user_id();

        $groups        = Groups::get_groups_for_user($user_id);
        $member_groups = $groups['member'] ?? [];

        foreach ($member_groups as $group) {
            $owner_id = isset($group['owner_id']) ? (int) $group['owner_id'] : 0;
            if ($owner_id > 0) {
                GroupChats::ensure_chat((int) $group['id'], $user_id, $owner_id);
            }
        }

        if (!wp_script_is('juntaplay', 'enqueued')) {
            wp_enqueue_script('juntaplay');
        }

        wp_enqueue_media();

        $rest_base = trailingslashit(rest_url('juntaplay/v1'));
        wp_localize_script('juntaplay', 'JuntaPlayChat', [
            'restBase' => $rest_base,
            'nonce'    => wp_create_nonce('wp_rest'),
            'userId'   => $user_id,
        ]);

        ob_start();
        $chat_groups = $groups;
        $chat_rest   = $rest_base;
        include JP_DIR . 'templates/group-chat.php';

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
