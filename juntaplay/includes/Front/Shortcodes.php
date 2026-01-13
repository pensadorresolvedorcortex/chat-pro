<?php
declare(strict_types=1);

namespace JuntaPlay\Front;

defined('ABSPATH') || exit;

use JuntaPlay\Admin\Settings;
use JuntaPlay\Assets\ServiceIcons;
use JuntaPlay\Data\Groups;
use JuntaPlay\Data\CaucaoCycles;
use JuntaPlay\Data\GroupMembers;
use JuntaPlay\Data\GroupMembershipEvents;
use JuntaPlay\Data\Notifications;
use JuntaPlay\Data\GroupChats;
use function absint;
use function apply_filters;
use function add_query_arg;
use function current_time;
use function date_i18n;
use function delete_user_meta;
use function esc_attr;
use function esc_html__;
use function esc_html;
use function esc_textarea;
use function esc_url;
use function esc_url_raw;
use function get_current_user_id;
use function get_user_by;
use function get_user_meta;
use function get_option;
use function get_permalink;
use function home_url;
use function is_user_logged_in;
use function sanitize_key;
use function sanitize_text_field;
use function sanitize_textarea_field;
use function sanitize_title;
use function shortcode_atts;
use function wc_get_checkout_url;
use function wc_get_order;
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
use function do_action;
use function rawurlencode;
use function esc_js;
use function nocache_headers;
use function number_format_i18n;
use function wp_json_encode;
use function rest_url;
use function wp_create_nonce;
use function trailingslashit;
use function wp_strip_all_tags;

class Shortcodes
{
    private bool $rendered_auth_modal = false;
    private ?array $auth_modal_state = null;

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
        add_shortcode('juntaplay_credits', [$this, 'credits']);
        add_shortcode('juntaplay_my_groups', [$this, 'my_groups']);
        add_shortcode('juntaplay_meus_grupos_assinados', [$this, 'my_groups_signed']);
        add_shortcode('juntaplay_groups', [$this, 'groups_directory']);
        add_shortcode('juntaplay_group_search', [$this, 'group_search']);
        add_shortcode('juntaplay_group_rotator', [$this, 'group_rotator']);
        add_shortcode('juntaplay_groups_slider', [$this, 'groups_slider']);
        add_shortcode('juntaplay_two_factor', [$this, 'two_factor']);
        add_shortcode('juntaplay_header', [$this, 'header']);
        add_shortcode('juntaplay_group_relationship', [$this, 'group_relationship']);
        add_shortcode('juntaplay_messages', [$this, 'messages']);
        add_shortcode('juntaplay_cancelamento', [$this, 'cancelamento']);
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

    public function groups_slider($atts = []): string
    {
        if (!wp_script_is('juntaplay', 'enqueued')) {
            wp_enqueue_script('juntaplay');
        }

        $atts = shortcode_atts([
            'category' => '',
            'title'    => '',
        ], $atts, 'juntaplay_groups_slider');

        $limit = 15;

        $category = sanitize_key((string) $atts['category']);

        $result = Groups::query_public([
            'page'     => 1,
            'per_page' => $limit,
            'category' => $category,
            'orderby'  => 'rand',
        ]);

        $items = $result['items'] ?? [];
        if (!$items) {
            return '';
        }

        wp_enqueue_style(
            'juntaplay-groups-slider',
            JP_URL . 'assets/css/jp-groups-slider.css',
            ['juntaplay'],
            file_exists(JP_DIR . 'assets/css/jp-groups-slider.css') ? (string) filemtime(JP_DIR . 'assets/css/jp-groups-slider.css') : null
        );
        wp_enqueue_script(
            'juntaplay-groups-slider',
            JP_URL . 'assets/js/jp-groups-slider.js',
            ['juntaplay'],
            file_exists(JP_DIR . 'assets/js/jp-groups-slider.js') ? (string) filemtime(JP_DIR . 'assets/js/jp-groups-slider.js') : null,
            true
        );

        $categories = Groups::get_category_labels();
        $slider_id  = 'jp-groups-slider-' . wp_unique_id();

        ob_start();
        ?>
        <section class="jp-groups-slider" id="<?php echo esc_attr($slider_id); ?>">
            <?php if ($atts['title'] !== '') : ?>
                <div class="jp-groups-slider__header">
                    <h3 class="jp-groups-slider__title"><?php echo esc_html((string) $atts['title']); ?></h3>
                </div>
            <?php endif; ?>
            <div class="jp-groups-slider__shell">
                <div class="jp-groups-slider__nav">
                    <button class="jp-groups-slider__nav-btn" type="button" data-action="prev" aria-label="<?php esc_attr_e('Anterior', 'juntaplay'); ?>">
                        ‹
                    </button>
                    <button class="jp-groups-slider__nav-btn" type="button" data-action="next" aria-label="<?php esc_attr_e('Próximo', 'juntaplay'); ?>">
                        ›
                    </button>
                </div>
                <div class="jp-groups-slider__track" role="list">
                    <?php
                    $avatar_base = trailingslashit(JP_URL . 'assets/img/avatars');
                    $illustrative_avatars = [
                        $avatar_base . 'avatar-01.svg',
                        $avatar_base . 'avatar-02.svg',
                        $avatar_base . 'avatar-03.svg',
                        $avatar_base . 'avatar-04.svg',
                        $avatar_base . 'avatar-05.svg',
                        $avatar_base . 'avatar-06.svg',
                    ];
                    ?>
                    <?php foreach ($items as $group_index => $group) : ?>
                        <?php
                    $group_id   = isset($group['id']) ? (int) $group['id'] : 0;
                    $title      = (string) ($group['title'] ?? '');
                    $service    = (string) ($group['service_name'] ?? '');
                    $service_url = (string) ($group['service_url'] ?? '');
                    $pool_slug  = (string) ($group['pool_slug'] ?? '');
                    $category_key = (string) ($group['category'] ?? '');
                    $category_label = $category_key !== '' && isset($categories[$category_key]) ? (string) $categories[$category_key] : '';

                    $price_regular     = isset($group['price_regular']) ? (float) $group['price_regular'] : 0.0;
                    $price_promotional = isset($group['price_promotional']) ? (float) $group['price_promotional'] : null;
                    $member_price      = isset($group['member_price']) ? (float) $group['member_price'] : null;
                    $effective_price   = isset($group['effective_price']) ? (float) $group['effective_price'] : 0.0;
                    $fee_per_member    = Settings::get_fee_per_member();
                    $price_value = 0.0;

                    if ($member_price !== null && $member_price > 0) {
                        $price_value = $member_price;
                    } elseif ($price_promotional !== null && $price_promotional > 0) {
                        $price_value = $price_promotional;
                    } elseif ($effective_price > 0) {
                        $price_value = $effective_price;
                    } elseif ($price_regular > 0) {
                        $price_value = $price_regular;
                    }

                    if ($price_value > 0) {
                        $price_value += $fee_per_member;
                    }

                    $price_label = $price_value > 0 ? $this->format_currency($price_value) : '';

                    $link = $this->resolve_group_link($group);
                    $link = $link !== '' ? $link : '#';

                    $data_attrs = sprintf(' data-jp-group-open data-group-id="%d"', $group_id);
                    $card_attrs = sprintf('data-group-card data-group-id="%d"', $group_id);
                    $card_variant = $group_index % 2 === 0 ? 'jp-groups-card--green' : 'jp-groups-card--red';
                    $avatar_total = count($illustrative_avatars);
                    $avatar_count = ($group_id > 0 && $group_id % 2 === 0) || ($group_id <= 0 && $group_index % 2 === 0) ? 4 : 3;
                    $avatar_count = $avatar_total > 0 ? min($avatar_count, $avatar_total) : 0;
                    $avatar_offset = $avatar_total > 0 ? (($group_id > 0 ? $group_id : $group_index) % $avatar_total) : 0;
                    $card_avatars = [];
                    for ($i = 0; $i < $avatar_count; $i++) {
                        $avatar_index = ($avatar_offset + $i) % $avatar_total;
                        $card_avatars[] = $illustrative_avatars[$avatar_index];
                    }
                    ?>
                    <article class="jp-groups-card <?php echo esc_attr($card_variant); ?>" role="listitem" <?php echo $group_id > 0 ? $card_attrs : 'data-group-card data-group-id="0"'; ?>>
                        <div class="jp-groups-card__top">
                            <h4 class="jp-groups-card__name"><?php echo esc_html($title !== '' ? $title : $service); ?></h4>
                        </div>
                        <?php if ($avatar_count > 0) : ?>
                            <div class="jp-groups-card__avatars" aria-hidden="true">
                                <?php foreach ($card_avatars as $avatar_url) : ?>
                                    <span class="jp-groups-card__avatar has-image" style="background-image: url('<?php echo esc_url($avatar_url); ?>')"></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <div class="jp-groups-card__base">
                            <?php if ($price_label !== '') : ?>
                                <div class="jp-groups-card__price">
                                    <span class="jp-groups-card__price-value"><?php echo esc_html($price_label); ?></span>
                                    <span class="jp-groups-card__price-period"><?php esc_html_e('/mês', 'juntaplay'); ?></span>
                                </div>
                            <?php endif; ?>
                            <a class="jp-groups-card__cta" href="<?php echo esc_url($link); ?>"<?php echo wp_kses_post($data_attrs); ?>>
                                <?php esc_html_e('Juntar-se', 'juntaplay'); ?>
                            </a>
                        </div>
                    </article>
                <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php
        if (!defined('JUNTAPLAY_GROUP_MODAL_RENDERED')) {
            define('JUNTAPLAY_GROUP_MODAL_RENDERED', true);

            $ajax_endpoint = admin_url('admin-ajax.php');
            $ajax_nonce    = wp_create_nonce('jp_nonce');
            $rest_root     = rest_url('juntaplay/v1/');
            $rest_nonce    = wp_create_nonce('wp_rest');
            ?>
            <div
                id="juntaplay-group-modal"
                class="juntaplay-modal"
                role="dialog"
                aria-modal="true"
                tabindex="-1"
                hidden
                data-ajax-endpoint="<?php echo esc_url($ajax_endpoint); ?>"
                data-ajax-nonce="<?php echo esc_attr($ajax_nonce); ?>"
                data-rest-root="<?php echo esc_url($rest_root); ?>"
                data-rest-nonce="<?php echo esc_attr($rest_nonce); ?>"
            >
                <div class="juntaplay-modal__overlay" data-group-modal-close></div>
                <div class="juntaplay-modal__dialog" role="document">
                    <button type="button" class="juntaplay-modal__close" aria-label="<?php echo esc_attr__('Fechar', 'juntaplay'); ?>">&times;</button>
                    <div class="juntaplay-modal__messages" data-modal-messages></div>
                    <div class="juntaplay-modal__content"></div>
                </div>
            </div>
            <?php
        }

        return (string) ob_get_clean();
    }

    private function format_currency(float $amount): string
    {
        if (function_exists('wc_price')) {
            return wp_strip_all_tags((string) wc_price($amount));
        }

        return 'R$ ' . number_format_i18n($amount, 2);
    }

    private function resolve_group_link(array $group): string
    {
        $page_id = (int) get_option('juntaplay_page_grupos');
        $base    = $page_id ? get_permalink($page_id) : home_url('/grupos');

        if (!$base) {
            $base = home_url('/grupos');
        }

        $slug = isset($group['slug']) ? (string) $group['slug'] : '';

        if ($slug === '') {
            return $base;
        }

        return add_query_arg('grupo', rawurlencode($slug), $base);
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
        if (isset($_GET['section'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $requested_section = sanitize_key(wp_unslash((string) $_GET['section'])); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            if ($requested_section === 'juntaplay-cancelamento') {
                return $this->cancelamento();
            }
        }

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

    public function cancelamento(): string
    {
        $profile_page_id = (int) get_option('juntaplay_page_perfil');
        $profile_url     = $profile_page_id ? get_permalink($profile_page_id) : home_url('/perfil/');
        $confirmation_url = add_query_arg('section', 'juntaplay-cancelamento', $profile_url);

        if (!is_user_logged_in()) {
            $login_page_id = (int) get_option('juntaplay_page_entrar');
            $login_url     = $login_page_id ? get_permalink($login_page_id) : wp_login_url($confirmation_url);

            if ($login_page_id && $login_url) {
                $login_url = add_query_arg('redirect_to', rawurlencode($confirmation_url), $login_url);
            }

            $login_url = $login_url ?: wp_login_url($confirmation_url);

            return '<p class="juntaplay-notice">' . esc_html__('Faça login para visualizar seu cancelamento.', 'juntaplay') . ' '
                . '<a class="juntaplay-link" href="' . esc_url($login_url) . '">' . esc_html__('Entrar agora', 'juntaplay') . '</a></p>';
        }

        $user_id = get_current_user_id();

        // Validação 1: group_id obrigatório via query string (com fallbacks).
        $group_id = 0;
        if (isset($_GET['group_id'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $group_id = absint(wp_unslash($_GET['group_id'])); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        } elseif (isset($_GET['gid'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $group_id = absint(wp_unslash($_GET['gid'])); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        } elseif (isset($_GET['group'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $group_id = absint(wp_unslash($_GET['group'])); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        } elseif (isset($_POST['group_id'])) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
            $group_id = absint(wp_unslash($_POST['group_id'])); // phpcs:ignore WordPress.Security.NonceVerification.Missing
        }

        if ($group_id <= 0) {
            $groups = Groups::get_groups_for_user($user_id);
            $member_groups = isset($groups['member']) && is_array($groups['member']) ? $groups['member'] : [];
            $active_groups = array_values(array_filter($member_groups, static function (array $group): bool {
                $status = isset($group['membership_status']) ? (string) $group['membership_status'] : 'active';
                return $status === 'active';
            }));

            if (count($active_groups) === 1) {
                $group_id = (int) ($active_groups[0]['id'] ?? 0);
            }
        }

        if ($group_id <= 0) {
            return '<p class="juntaplay-notice">' . esc_html__('Grupo inválido para cancelamento. Volte para seus grupos e tente novamente.', 'juntaplay') . '</p>';
        }

        // Validação 2: membership existente.
        $membership = GroupMembers::get_membership($group_id, $user_id);
        if (!$membership) {
            return '<p class="juntaplay-notice">' . esc_html__('Você não participa deste grupo ou sua participação já foi encerrada.', 'juntaplay') . '</p>';
        }

        // Validação 3: papel do usuário (bloqueia administradores).
        $membership_role = isset($membership['role']) ? (string) $membership['role'] : 'member';
        $blocked_roles = ['owner', 'manager', 'staff', 'system'];
        if (in_array($membership_role, $blocked_roles, true)) {
            return '<p class="juntaplay-notice">' . esc_html__('Você é administrador deste grupo e não pode cancelar sua própria participação.', 'juntaplay') . '</p>';
        }

        // Validação 4: status permitido (apenas active).
        $membership_status = isset($membership['status']) ? (string) $membership['status'] : 'active';
        if ($membership_status !== 'active') {
            return '<p class="juntaplay-notice">' . esc_html__('Sua participação não está ativa para cancelamento.', 'juntaplay') . '</p>';
        }

        // Validação 5: ciclo de caução (quando existir).
        $caucao = null;
        if (method_exists(CaucaoCycles::class, 'get_active_by_user_and_group')) {
            $caucao = CaucaoCycles::get_active_by_user_and_group($user_id, $group_id);
        } else {
            $cycles = CaucaoCycles::get_latest_for_user_groups($user_id, [$group_id]);
            $caucao = $cycles[$group_id] ?? null;
        }

        $has_caucao = (bool) $caucao;
        $caucao_amount = 0.0;

        $cycle_end = '';
        if ($has_caucao) {
            $cycle_end = is_array($caucao)
                ? (string) ($caucao['cycle_end'] ?? '')
                : (string) ($caucao->cycle_end ?? '');
        }

        $group = Groups::get($group_id);
        $group_name = is_array($group) ? (string) ($group['title'] ?? '') : '';

        // Determinação da data de liberação: cycle_end do caução, ou membership/grupo + 31 dias.
        $membership_cycle_end = isset($membership['cycle_end']) ? (string) $membership['cycle_end'] : '';
        $group_cycle_end = is_array($group) ? (string) ($group['cycle_end'] ?? '') : '';

        if ($cycle_end === '') {
            $cycle_end = $membership_cycle_end !== '' ? $membership_cycle_end : $group_cycle_end;
        }

        if ($cycle_end === '' && !empty($membership['joined_at'])) {
            $cycle_end = date('Y-m-d H:i:s', strtotime((string) $membership['joined_at'] . ' +31 days'));
        }

        $cycle_end_ts = $cycle_end !== '' ? strtotime($cycle_end) : false;
        if (!$cycle_end_ts) {
            return '<p class="juntaplay-notice">' . esc_html__('Não foi possível calcular a data de vencimento do ciclo. Tente novamente em instantes.', 'juntaplay') . '</p>';
        }

        // Cálculo de datas: a saída efetiva sempre ocorre no cycle_end.
        $now_ts = current_time('timestamp');
        $days_until_cycle_end = (int) floor(($cycle_end_ts - $now_ts) / DAY_IN_SECONDS);
        $is_within_15_days = $days_until_cycle_end < 15;
        $exit_effective_ts = $cycle_end_ts;
        $exit_effective_at = date('Y-m-d H:i:s', $exit_effective_ts);
        $exit_display = date_i18n(get_option('date_format'), $exit_effective_ts);

        // Decisão da caução: muda apenas a mensagem, a data permanece a mesma.
        // Cálculo do valor do calção: mensalidade (pedido WooCommerce) - taxa administrativa (configuração do plugin).
        $orders = function_exists('wc_get_orders')
            ? wc_get_orders([
                'limit'      => 20,
                'orderby'    => 'date',
                'order'      => 'DESC',
                'customer_id'=> $user_id,
                'status'     => ['wc-completed', 'wc-processing', 'wc-on-hold'],
            ])
            : [];

        $monthly_value = 0.0;
        if ($orders) {
            $order = $orders[0] ?? null;
            if ($order && method_exists($order, 'get_items')) {
                    foreach ($order->get_items() as $item) {
                        $item_group_id = $item->get_meta('_juntaplay_group_id', true);
                        if (!$item_group_id) {
                            $item_group_id = $item->get_meta('juntaplay_group_id', true);
                        }
                        if ((int) $item_group_id === $group_id) {
                            $monthly_value += (float) $item->get_subtotal();
                        }
                    }
            }
        }

        $fee_per_member = method_exists(Settings::class, 'get_fee_per_member')
            ? (float) Settings::get_fee_per_member()
            : 0.0;
        $caucao_amount = max(0.0, $monthly_value - $fee_per_member);

        // Definição do status do calção (determinístico e automático).
        $has_open_complaint = false;
        if (class_exists('\JuntaPlay\Data\GroupComplaints') && method_exists(\JuntaPlay\Data\GroupComplaints::class, 'has_open_for_period')) {
            $has_open_complaint = \JuntaPlay\Data\GroupComplaints::has_open_for_period(
                $user_id,
                $group_id,
                isset($caucao['cycle_start']) ? (string) $caucao['cycle_start'] : '',
                $cycle_end
            );
        }

        if ($has_open_complaint) {
            $caucao_status = __('Retido definitivamente', 'juntaplay');
        } else {
            $caucao_status = $is_within_15_days || $now_ts < $exit_effective_ts
                ? sprintf(__('Retido até %s', 'juntaplay'), $exit_display)
                : __('Liberado automaticamente (crédito em saldo)', 'juntaplay');
        }

        $notice_title = esc_html__('Sua solicitação de cancelamento está pronta para ser confirmada.', 'juntaplay');
        $user = get_user_by('id', $user_id);
        $user_name = $user && $user->exists() ? (string) $user->display_name : '';

        $reason = '';
        $errors = [];
        $scheduled = false;

        if (!empty($_POST['jp_cancelamento_submit'])) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
            $nonce = isset($_POST['jp_cancelamento_nonce']) ? wp_unslash($_POST['jp_cancelamento_nonce']) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
            if (!wp_verify_nonce($nonce, 'jp_cancelamento')) {
                $errors[] = esc_html__('Sua sessão expirou. Atualize a página e tente novamente.', 'juntaplay');
            }

            $reason = isset($_POST['jp_cancelamento_reason']) ? sanitize_textarea_field(wp_unslash($_POST['jp_cancelamento_reason'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
            if ($reason === '' || strlen($reason) < 10) {
                $errors[] = esc_html__('Informe um motivo com pelo menos 10 caracteres para solicitar sua saída.', 'juntaplay');
            }

            if (!$errors) {
                global $wpdb;
                $members_table = "{$wpdb->prefix}jp_group_members";
                $has_exit_requested = (bool) $wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM $members_table LIKE %s", 'exit_requested_at'));
                $has_exit_effective = (bool) $wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM $members_table LIKE %s", 'exit_effective_at'));

                $data = ['status' => 'exit_scheduled'];
                $format = ['%s'];

                if ($has_exit_requested) {
                    $data['exit_requested_at'] = current_time('mysql');
                    $format[] = '%s';
                }

                if ($has_exit_effective && $exit_effective_at !== '') {
                    $data['exit_effective_at'] = $exit_effective_at;
                    $format[] = '%s';
                }

                $updated = $wpdb->update(
                    $members_table,
                    $data,
                    [
                        'group_id' => $group_id,
                        'user_id'  => $user_id,
                    ],
                    $format,
                    ['%d', '%d']
                );

                if ($updated !== false) {
                    GroupMembershipEvents::log(
                        $group_id,
                        $user_id,
                        GroupMembershipEvents::TYPE_EXIT_SCHEDULED,
                        '',
                        [
                            'reason' => $reason,
                            'exit_effective_at' => $exit_effective_at,
                        ]
                    );

                    do_action('juntaplay/group_members/exit_scheduled', $group_id, $user_id, [
                        'reason' => $reason,
                        'exit_effective_at' => $exit_effective_at,
                    ]);

                    $scheduled = true;
                } else {
                    $errors[] = esc_html__('Não foi possível agendar sua saída agora. Tente novamente em instantes.', 'juntaplay');
                }
            }
        }

        $my_groups_page_id = (int) get_option('juntaplay_page_meus-grupos');
        $my_groups_url = $my_groups_page_id ? get_permalink($my_groups_page_id) : home_url('/meus-grupos/');

        ob_start();
        ?>
        <style>
            /* Glassmorphism aplicado apenas ao cartão do shortcode de cancelamento */
            .juntaplay-cancelamento {
                padding: 24px 16px;
            }

            .juntaplay-cancelamento__box {
                max-width: 720px;
                margin: 0 auto;
                padding: 32px;
                background: rgba(255, 255, 255, 0.78);
                border: 1px solid rgba(255, 255, 255, 0.4);
                border-radius: 20px;
                box-shadow: 0 12px 30px rgba(15, 23, 42, 0.12);
                backdrop-filter: blur(14px);
                -webkit-backdrop-filter: blur(14px);
            }

            .juntaplay-cancelamento__title {
                margin-bottom: 16px;
            }

            .juntaplay-cancelamento__text {
                margin: 12px 0;
            }

            /* Espaçamento entre textarea e botões */
            .juntaplay-cancelamento__form .juntaplay-form__group {
                margin-bottom: 28px;
            }

            .juntaplay-cancelamento__form .juntaplay-form__input {
                border-radius: 14px;
                border: 1px solid rgba(15, 23, 42, 0.2);
                padding: 14px;
                background: rgba(255, 255, 255, 0.9);
                box-shadow: inset 0 1px 2px rgba(15, 23, 42, 0.06);
            }

            .juntaplay-cancelamento__form .juntaplay-form__input:focus {
                outline: none;
                border-color: rgba(59, 130, 246, 0.6);
                box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
            }

            /* Botões com respiro e estilo de ação */
            .juntaplay-cancelamento__form .juntaplay-button--primary {
                border-radius: 999px;
                padding: 12px 28px;
            }

            .juntaplay-cancelamento__box > a.juntaplay-button--primary {
                margin-top: 24px;
                border-radius: 999px;
                background: rgba(255, 255, 255, 0.85);
                color: inherit;
                border: 1px solid rgba(15, 23, 42, 0.15);
            }

            /* Bloco de destaque financeiro do calção */
            .juntaplay-cancelamento__caucao {
                margin: 20px 0;
                padding: 20px;
                border-radius: 16px;
                background: rgba(255, 255, 255, 0.75);
                border: 1px solid rgba(255, 255, 255, 0.45);
                box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
                backdrop-filter: blur(12px);
                -webkit-backdrop-filter: blur(12px);
            }

            /* Hierarquia visual: labels discretos e valores em destaque */
            .juntaplay-cancelamento__caucao-grid {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 16px;
            }

            .juntaplay-cancelamento__caucao-item-label {
                font-size: 0.85rem;
                color: rgba(15, 23, 42, 0.65);
                margin-bottom: 6px;
                display: block;
            }

            .juntaplay-cancelamento__caucao-item-value {
                font-size: 1.15rem;
                font-weight: 700;
                color: rgba(15, 23, 42, 0.9);
            }

            /* Responsividade: botões empilhados e largura total em telas pequenas */
            @media (max-width: 640px) {
                .juntaplay-cancelamento__box {
                    padding: 24px;
                }

                .juntaplay-cancelamento__caucao-grid {
                    grid-template-columns: 1fr;
                }

                .juntaplay-cancelamento__caucao-item-value {
                    font-size: 1.05rem;
                }

                .juntaplay-cancelamento__form .juntaplay-button--primary,
                .juntaplay-cancelamento__box > a.juntaplay-button--primary {
                    width: 100%;
                    justify-content: center;
                }
            }
        </style>
        <section class="juntaplay-cancelamento">
            <div class="juntaplay-cancelamento__box">
                <h2 class="juntaplay-cancelamento__title"><?php echo esc_html($notice_title); ?></h2>
                <p class="juntaplay-cancelamento__text">
                    <?php
                    if ($exit_display !== '') {
                        if ($is_within_15_days) {
                            echo esc_html(sprintf(
                                __('%1$s, você está solicitando o cancelamento com menos de 15 dias de antecedência da data de vencimento. Sua saída será agendada para o dia %2$s e o crédito caução será utilizado para quitar a última fatura do grupo.', 'juntaplay'),
                                $user_name !== '' ? $user_name : esc_html__('Assinante', 'juntaplay'),
                                $exit_display
                            ));
                        } else {
                            echo esc_html(sprintf(
                                __('%1$s, você está solicitando o cancelamento com pelo menos 15 dias de antecedência da data de vencimento. Sua saída será agendada para o dia %2$s e o crédito caução será devolvido após a conclusão do ciclo.', 'juntaplay'),
                                $user_name !== '' ? $user_name : esc_html__('Assinante', 'juntaplay'),
                                $exit_display
                            ));
                        }
                    } else {
                        echo esc_html__('Não foi possível calcular a data efetiva de saída no momento.', 'juntaplay');
                    }
                    ?>
                </p>
                <p class="juntaplay-cancelamento__text">
                    <?php
                    echo esc_html__(
                        'Após essa data, caso não haja pendências, o valor será automaticamente creditado em seu saldo financeiro na plataforma.',
                        'juntaplay'
                    );
                    ?>
                </p>

                <?php if ($errors) : ?>
                    <div class="juntaplay-alert juntaplay-alert--danger">
                        <ul>
                            <?php foreach ($errors as $error) : ?>
                                <li><?php echo esc_html($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if ($scheduled) : ?>
                    <p class="juntaplay-cancelamento__text">
                        <?php
                        if ($exit_display !== '') {
                            echo esc_html(sprintf(__('Sua saída do grupo está agendada para %s.', 'juntaplay'), $exit_display));
                        } else {
                            echo esc_html__('Sua saída do grupo está agendada para data a confirmar.', 'juntaplay');
                        }
                        ?>
                    </p>
                    <p class="juntaplay-cancelamento__text"><?php echo esc_html__('Até essa data, você continuará com acesso normal ao grupo.', 'juntaplay'); ?></p>
                <?php else : ?>
                    <form class="juntaplay-cancelamento__form" method="post" action="">
                        <input type="hidden" name="jp_cancelamento_group_id" value="<?php echo esc_attr((string) $group_id); ?>" />
                        <div class="juntaplay-form__group">
                            <label for="jp-cancelamento-reason"><?php echo esc_html__('Descreva o motivo da sua saída', 'juntaplay'); ?></label>
                            <textarea id="jp-cancelamento-reason" name="jp_cancelamento_reason" class="juntaplay-form__input" rows="3" minlength="10" required><?php echo esc_textarea($reason); ?></textarea>
                        </div>

                        <div class="juntaplay-cancelamento__caucao">
                            <div class="juntaplay-cancelamento__caucao-grid">
                                <div class="juntaplay-cancelamento__caucao-item">
                                    <span class="juntaplay-cancelamento__caucao-item-label"><?php echo esc_html__('Calção do grupo', 'juntaplay'); ?></span>
                                    <div class="juntaplay-cancelamento__caucao-item-value">
                                        <?php echo wp_kses_post(wc_price($caucao_amount)); ?>
                                    </div>
                                </div>
                                <div class="juntaplay-cancelamento__caucao-item">
                                    <span class="juntaplay-cancelamento__caucao-item-label"><?php echo esc_html__('Status do calção', 'juntaplay'); ?></span>
                                    <div class="juntaplay-cancelamento__caucao-item-value">
                                        <?php echo esc_html($caucao_status); ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <input type="hidden" name="jp_cancelamento_nonce" value="<?php echo esc_attr(wp_create_nonce('jp_cancelamento')); ?>" />
                        <button type="submit" class="juntaplay-button juntaplay-button--primary" name="jp_cancelamento_submit" value="1">
                            <?php echo esc_html__('Confirmar cancelamento', 'juntaplay'); ?>
                        </button>
                    </form>
                <?php endif; ?>

                <ul class="juntaplay-cancelamento__meta">
                    <?php if ($group_name !== '') : ?>
                        <li><strong><?php echo esc_html__('Grupo:', 'juntaplay'); ?></strong> <?php echo esc_html($group_name); ?></li>
                    <?php endif; ?>
                </ul>

                <a class="juntaplay-button juntaplay-button--primary" href="<?php echo esc_url($my_groups_url); ?>">
                    <?php echo esc_html__('Voltar para Meus Grupos', 'juntaplay'); ?>
                </a>
            </div>
        </section>
        <?php

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
        if ($price_value > 0) {
            $price_value += Settings::get_fee_per_member();
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
        $header_chat_notifications = [];
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
            $header_chat_notifications = [];

            if ($notifications_unread > 0) {
                $chat = GroupChats::get_latest_unread_chat(get_current_user_id());

                if (is_array($chat) && !empty($chat['sender_id'])) {
                    $sender = get_user_by('id', (int) $chat['sender_id']);

                    if ($sender) {
                        $header_chat_notifications[] = [
                            'text' => sprintf(
                                __('%s te enviou uma mensagem.', 'juntaplay'),
                                $sender->display_name
                            ),
                            'url'  => home_url('/perfil/?section=juntaplay-chat'),
                        ];
                    }
                }
            }
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
        $header_notifications        = $header_chat_notifications;
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

    public function my_groups_signed(): string
    {
        if (!is_user_logged_in()) {
            $login_url = wp_login_url();

            return '<p class="juntaplay-notice">' . esc_html__('Faça login para visualizar seus grupos.', 'juntaplay')
                . ($login_url ? ' <a href="' . esc_url($login_url) . '">' . esc_html__('Entrar', 'juntaplay') . '</a>' : '')
                . '</p>';
        }

        if (!wp_script_is('juntaplay', 'enqueued')) {
            wp_enqueue_script('juntaplay');
        }

        $user_id = get_current_user_id();
        $groups  = \JuntaPlay\Data\Groups::get_groups_for_user($user_id);
        $owned   = isset($groups['owned']) && is_array($groups['owned']) ? $groups['owned'] : [];
        $member  = isset($groups['member']) && is_array($groups['member']) ? $groups['member'] : [];
        $all     = array_merge($owned, $member);

        ob_start();
        ?>
        <section class="juntaplay-my-groups" data-jp-my-groups>
            <?php if (!$all) : ?>
                <p class="juntaplay-notice">
                    <?php echo esc_html__('Você ainda não participa de nenhum grupo.', 'juntaplay'); ?>
                </p>
            <?php else : ?>
                <ul class="juntaplay-group-list">
                    <?php foreach ($all as $group) : ?>
                        <?php
                        $group_id    = isset($group['id']) ? (int) $group['id'] : 0;
                        $title       = isset($group['title']) ? (string) $group['title'] : '';
                        $role        = isset($group['membership_role']) ? (string) $group['membership_role'] : '';
                        $role_label  = in_array($role, ['owner', 'manager'], true)
                            ? esc_html__('Administrador', 'juntaplay')
                            : esc_html__('Assinante', 'juntaplay');
                        $service     = isset($group['service_name']) ? (string) $group['service_name'] : '';
                        $pool_title  = isset($group['pool_title']) ? (string) $group['pool_title'] : '';
                        ?>
                        <li class="juntaplay-group-item" data-group-id="<?php echo esc_attr((string) $group_id); ?>">
                            <strong class="juntaplay-group-title"><?php echo esc_html($title); ?></strong>
                            <?php if ($service !== '') : ?>
                                <span class="juntaplay-group-service"><?php echo esc_html($service); ?></span>
                            <?php elseif ($pool_title !== '') : ?>
                                <span class="juntaplay-group-service"><?php echo esc_html($pool_title); ?></span>
                            <?php endif; ?>
                            <span class="juntaplay-group-role"><?php echo esc_html($role_label); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
        <?php

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
        $dashboard_id   = (int) get_option('juntaplay_page_painel');
        $profile_page_id = (int) get_option('juntaplay_page_perfil');
        $credits_page_id = (int) get_option('juntaplay_page_creditos');
        $my_groups_id    = (int) get_option('juntaplay_page_meus-grupos');
        $my_quotas_id    = (int) get_option('juntaplay_page_minhas-cotas');
        $statement_id    = (int) get_option('juntaplay_page_extrato');

        $profile_base_url = $profile_page_id ? get_permalink($profile_page_id) : home_url('/perfil/');
        $messages_url     = add_query_arg('section', 'juntaplay-chat', $profile_base_url);

        $links = [
            [
                'label' => esc_html__('Painel', 'juntaplay'),
                'url'   => $dashboard_id ? get_permalink($dashboard_id) : home_url('/painel'),
                'icon'  => 'grid',
            ],
            [
                'label' => esc_html__('Perfil', 'juntaplay'),
                'url'   => $profile_base_url,
                'icon'  => 'user',
            ],
            [
                'label' => esc_html__('Mensagens', 'juntaplay'),
                'url'   => $messages_url,
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
