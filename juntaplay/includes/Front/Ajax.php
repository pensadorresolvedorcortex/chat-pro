<?php
declare(strict_types=1);

namespace JuntaPlay\Front;

use JuntaPlay\Admin\Settings;
use JuntaPlay\Data\CreditTransactions;
use JuntaPlay\Data\CreditWithdrawals;
use JuntaPlay\Data\Notifications as NotificationsData;
use JuntaPlay\Data\Groups;
use JuntaPlay\Data\Pools;
use JuntaPlay\Data\Quotas;

use function absint;
use function add_action;
use function add_filter;
use function add_query_arg;
use function array_map;
use function in_array;
use function check_ajax_referer;
use WP_User;
use function get_avatar_url;
use function get_current_user_id;
use function get_option;
use function get_permalink;
use function max;
use function min;
use function mysql2date;
use function number_format_i18n;
use function sanitize_key;
use function sanitize_text_field;
use function sanitize_textarea_field;
use function esc_url_raw;
use function file_exists;
use function ltrim;
use function sprintf;
use function home_url;
use function rawurlencode;
use function is_array;
use function is_string;
use function preg_replace;
use function wp_date;
use function wp_get_current_user;
use function wp_send_json_error;
use function wp_send_json_success;
use function wp_unslash;
use function wp_strip_all_tags;
use function is_user_logged_in;
use function wp_verify_nonce;
use function wc_get_checkout_url;
use function wc_price;
use function _n;
use function __;
use function is_wp_error;
use function media_handle_upload;
use function do_action;
use function strtolower;
use function trim;
use function strpos;

defined('ABSPATH') || exit;

class Ajax
{
    public function __construct(private Profile $profile)
    {
        add_action('wp_ajax_juntaplay_pools', [$this, 'pools']);
        add_action('wp_ajax_nopriv_juntaplay_pools', [$this, 'pools']);
        add_action('wp_ajax_juntaplay_pool_numbers', [$this, 'pool_numbers']);
        add_action('wp_ajax_nopriv_juntaplay_pool_numbers', [$this, 'pool_numbers']);
        add_action('wp_ajax_juntaplay_groups_directory', [$this, 'groups_directory']);
        add_action('wp_ajax_nopriv_juntaplay_groups_directory', [$this, 'groups_directory']);
        add_action('wp_ajax_juntaplay_credit_deposit', [$this, 'credit_deposit']);
        add_action('wp_ajax_juntaplay_group_detail', [$this, 'group_detail']);
        add_action('wp_ajax_juntaplay_group_edit_form', [$this, 'group_edit_form']);
        add_action('wp_ajax_juntaplay_group_edit_save', [$this, 'group_edit_save']);
        add_filter('user_has_cap', [$this, 'grant_group_owner_upload_cap'], 10, 4);
        add_filter('upload_capability', [$this, 'filter_group_owner_upload_cap']);
    }

    public function pools(): void
    {
        check_ajax_referer('jp_nonce', 'nonce');

        $page     = isset($_GET['page']) ? absint($_GET['page']) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $per_page = isset($_GET['per_page']) ? absint($_GET['per_page']) : 12; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $search   = isset($_GET['search']) ? sanitize_text_field(wp_unslash($_GET['search'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $category = isset($_GET['category']) ? sanitize_key(wp_unslash($_GET['category'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $featured = isset($_GET['featured']) ? sanitize_text_field(wp_unslash($_GET['featured'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $orderby  = isset($_GET['orderby']) ? sanitize_key(wp_unslash($_GET['orderby'])) : 'created_at'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $order    = isset($_GET['order']) ? sanitize_key(wp_unslash($_GET['order'])) : 'desc'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $min_price = isset($_GET['min_price']) ? floatval(wp_unslash($_GET['min_price'])) : null; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $max_price = isset($_GET['max_price']) ? floatval(wp_unslash($_GET['max_price'])) : null; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

        $result = Pools::query([
            'page'      => $page,
            'per_page'  => $per_page,
            'search'    => $search,
            'category'  => $category,
            'min_price' => $min_price,
            'max_price' => $max_price,
            'featured'  => $featured !== '' ? in_array($featured, ['1', 'yes', 'true'], true) : null,
            'orderby'   => $orderby,
            'order'     => $order,
        ]);

        $categories = Pools::get_category_labels();
        $items      = array_map(function (array $pool) use ($categories) {
            return $this->format_pool($pool, $categories);
        }, $result['items']);

        wp_send_json_success([
            'items'      => $items,
            'page'       => $result['page'],
            'pages'      => $result['pages'],
            'total'      => $result['total'],
            'categories' => $categories,
        ]);
    }

    public function group_detail(): void
    {
        check_ajax_referer('jp_nonce', 'nonce');

        $group_id = absint($_POST['group_id'] ?? 0);
        $context  = $this->profile->get_group_modal_context($group_id);

        if (!$context) {
            wp_send_json_error(['message' => __('Grupo não encontrado.', 'juntaplay')], 404);
        }

        $html = $this->render_template('group-modal-detail.php', [
            'group'    => $context['group'],
            'is_owner' => !empty($context['is_owner']),
        ]);

        if ($html === '') {
            wp_send_json_error(['message' => __('Não foi possível carregar os detalhes do grupo.', 'juntaplay')], 500);
        }

        wp_send_json_success(['html' => $html]);
    }

    public function group_edit_form(): void
    {
        check_ajax_referer('jp_nonce', 'nonce');

        $group_id = absint($_POST['group_id'] ?? 0);
        $context  = $this->profile->get_group_modal_context($group_id);

        if (!$context || empty($context['is_owner'])) {
            wp_send_json_error(['message' => __('Você não pode editar este grupo.', 'juntaplay')], 403);
        }

        $html = $this->profile->render_group_edit_template(
            $context['group'],
            isset($context['categories']) && is_array($context['categories']) ? $context['categories'] : []
        );

        if ($html === '') {
            wp_send_json_error(['message' => __('Não foi possível carregar o formulário de edição.', 'juntaplay')], 500);
        }

        wp_send_json_success(['html' => $html]);
    }

    public function group_edit_save(): void
    {
        check_ajax_referer('jp_nonce', 'nonce');

        $group_id = absint($_POST['group_id'] ?? 0);
        if ($group_id <= 0) {
            wp_send_json_error(['message' => __('Grupo inválido.', 'juntaplay')], 400);
        }

        $result = $this->profile->handle_group_update($group_id, $_POST, $_FILES);

        if (empty($result['success'])) {
            $status  = isset($result['status']) ? (int) $result['status'] : 400;
            $message = isset($result['message']) ? (string) $result['message'] : __('Não foi possível salvar as alterações do grupo.', 'juntaplay');

            wp_send_json_error([
                'message' => $message,
                'errors'  => isset($result['errors']) && is_array($result['errors']) ? $result['errors'] : [],
            ], $status);
        }

        wp_send_json_success([
            'message' => isset($result['message']) ? (string) $result['message'] : __('Grupo atualizado com sucesso.', 'juntaplay'),
            'group'   => isset($result['group']) && is_array($result['group']) ? $result['group'] : [],
        ]);
    }

    public function pool_numbers(): void
    {
        check_ajax_referer('jp_nonce', 'nonce');

        $pool_id = isset($_GET['pool_id']) ? absint($_GET['pool_id']) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if ($pool_id <= 0) {
            wp_send_json_error(['message' => __('Grupo não encontrado.', 'juntaplay')], 404);
        }

        $page    = isset($_GET['page']) ? absint($_GET['page']) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $per     = isset($_GET['per_page']) ? absint($_GET['per_page']) : 120; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $status  = isset($_GET['status']) ? sanitize_key(wp_unslash($_GET['status'])) : 'available'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $search  = isset($_GET['search']) ? sanitize_text_field(wp_unslash($_GET['search'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $sort    = isset($_GET['sort']) ? sanitize_key(wp_unslash($_GET['sort'])) : 'ASC'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

        $result = Quotas::list_numbers($pool_id, [
            'page'     => $page,
            'per_page' => $per,
            'status'   => $status,
            'search'   => $search,
            'sort'     => $sort,
        ]);

        wp_send_json_success($result);
    }

    public function groups_directory(): void
    {
        $nonce = isset($_REQUEST['nonce']) ? sanitize_text_field(wp_unslash((string) $_REQUEST['nonce'])) : '';

        if (!wp_verify_nonce($nonce, 'jp_nonce')) {
            if (is_user_logged_in()) {
                wp_send_json_error([
                    'message' => __('Sua sessão expirou. Atualize a página e tente novamente.', 'juntaplay'),
                ], 403);
            }
        }

        $page      = isset($_GET['page']) ? absint($_GET['page']) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $per_page  = isset($_GET['per_page']) ? absint($_GET['per_page']) : 12; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $search    = isset($_GET['search']) ? sanitize_text_field(wp_unslash($_GET['search'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $category  = isset($_GET['category']) ? sanitize_key(wp_unslash($_GET['category'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $orderby   = isset($_GET['orderby']) ? sanitize_key(wp_unslash($_GET['orderby'])) : 'created'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $order     = isset($_GET['order']) ? sanitize_key(wp_unslash($_GET['order'])) : 'desc'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $instant   = isset($_GET['instant_access']) ? sanitize_key(wp_unslash($_GET['instant_access'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $min_price = isset($_GET['min_price']) ? floatval(wp_unslash($_GET['min_price'])) : null; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $max_price = isset($_GET['max_price']) ? floatval(wp_unslash($_GET['max_price'])) : null; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

        $result = Groups::query_public([
            'page'           => $page,
            'per_page'       => $per_page,
            'search'         => $search,
            'category'       => $category,
            'orderby'        => $orderby,
            'order'          => $order,
            'instant_access' => $instant !== '' ? in_array($instant, ['1', 'yes', 'true'], true) : null,
            'price_min'      => $min_price,
            'price_max'      => $max_price,
        ]);

        $categories = Groups::get_category_labels();
        $items      = array_map(function (array $group) use ($categories) {
            return $this->format_group($group, $categories);
        }, $result['items']);

        wp_send_json_success([
            'items'      => $items,
            'page'       => $result['page'],
            'pages'      => $result['pages'],
            'total'      => $result['total'],
            'categories' => $categories,
        ]);
    }

    public function init(): void
    {
        add_action('wp_ajax_juntaplay_reserve', [$this, 'reserve']);
        add_action('wp_ajax_nopriv_juntaplay_reserve', [$this, 'reserve']);
        add_action('wp_ajax_juntaplay_credit_transactions', [$this, 'credit_transactions']);
        add_action('wp_ajax_juntaplay_credit_transaction', [$this, 'credit_transaction']);
        add_action('wp_ajax_juntaplay_credit_send_code', [$this, 'credit_send_code']);
        add_action('wp_ajax_juntaplay_credit_withdraw', [$this, 'credit_withdraw']);
        add_action('wp_ajax_juntaplay_notifications_feed', [$this, 'notifications_feed']);
        add_action('wp_ajax_juntaplay_notifications_mark', [$this, 'notifications_mark']);
    }

    public function reserve(): void
    {
        check_ajax_referer('jp_nonce', 'nonce');

        $pool_id = absint($_POST['pool_id'] ?? 0);
        $numbers = array_map('intval', $_POST['numbers'] ?? []);
        $user_id = get_current_user_id() ?: 0;

        if (!$pool_id || empty($numbers)) {
            wp_send_json_error(['message' => __('Dados insuficientes para reservar cotas.', 'juntaplay')], 400);
        }

        $settings = get_option(Settings::OPTION_RESERVE, ['minutes' => 15]);
        $minutes  = (int) ($settings['minutes'] ?? 15);

        $reserved = Quotas::reserve($pool_id, $numbers, $user_id, $minutes);

        if (count($reserved) !== count($numbers)) {
            wp_send_json_error([
                'message' => __('Algumas cotas já foram reservadas. Atualize e tente novamente.', 'juntaplay'),
                'reserved' => $reserved,
            ], 409);
        }

        wp_send_json_success(['reserved' => $reserved]);
    }

    public function credit_transactions(): void
    {
        check_ajax_referer('jp_nonce', 'nonce');

        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(['message' => __('É necessário estar logado para visualizar as movimentações.', 'juntaplay')], 401);
        }

        $page     = isset($_GET['page']) ? absint($_GET['page']) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $per_page = isset($_GET['per_page']) ? absint($_GET['per_page']) : 10; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $page     = max(1, $page);
        $per_page = max(5, min(50, $per_page));

        $filters = [];
        if (!empty($_GET['type'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $filters['type'] = sanitize_key(wp_unslash($_GET['type']));
        }

        if (!empty($_GET['status'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $filters['status'] = sanitize_key(wp_unslash($_GET['status']));
        }

        $result = CreditTransactions::get_for_user($user_id, $page, $per_page, $filters);

        $items = array_map([$this, 'format_transaction'], $result['items']);

        wp_send_json_success([
            'items' => $items,
            'page'  => $result['page'],
            'pages' => $result['pages'],
            'total' => $result['total'],
        ]);
    }

    public function credit_transaction(): void
    {
        check_ajax_referer('jp_nonce', 'nonce');

        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(['message' => __('Sua sessão expirou. Faça login novamente.', 'juntaplay')], 401);
        }

        $transaction_id = isset($_GET['id']) ? absint($_GET['id']) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if ($transaction_id <= 0) {
            wp_send_json_error(['message' => __('Transação não encontrada.', 'juntaplay')], 404);
        }

        $transaction = CreditTransactions::get($transaction_id, $user_id);

        if (!$transaction) {
            wp_send_json_error(['message' => __('Transação não encontrada.', 'juntaplay')], 404);
        }

        wp_send_json_success(['transaction' => $this->format_transaction($transaction, true)]);
    }

    public function credit_send_code(): void
    {
        check_ajax_referer('jp_nonce', 'nonce');

        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(['message' => __('Sessão expirada. Faça login novamente.', 'juntaplay')], 401);
        }

        $result = $this->profile->send_withdraw_code($user_id);

        if (!empty($result['error'])) {
            wp_send_json_error(['message' => (string) $result['error']], 400);
        }

        wp_send_json_success([
            'message'     => (string) ($result['message'] ?? __('Código enviado com sucesso.', 'juntaplay')),
            'expires'     => $result['expires'] ?? '',
            'destination' => $result['destination'] ?? '',
        ]);
    }

    public function credit_withdraw(): void
    {
        check_ajax_referer('jp_nonce', 'nonce');

        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(['message' => __('Sessão expirada. Faça login novamente.', 'juntaplay')], 401);
        }

        $amount_raw = isset($_POST['amount']) ? wp_unslash($_POST['amount']) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $method     = isset($_POST['method']) ? sanitize_key(wp_unslash($_POST['method'])) : 'pix'; // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $code       = isset($_POST['code']) ? sanitize_text_field(wp_unslash($_POST['code'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing

        $result = $this->profile->handle_withdrawal_request($user_id, [
            'amount' => $amount_raw,
            'method' => $method,
            'code'   => $code,
        ]);

        if (!empty($result['error'])) {
            $status = isset($result['status']) ? (int) $result['status'] : 400;
            wp_send_json_error(['message' => (string) $result['error'], 'field' => $result['field'] ?? ''], $status);
        }

        wp_send_json_success([
            'message'        => (string) ($result['message'] ?? __('Solicitação registrada com sucesso.', 'juntaplay')),
            'withdrawal_id'  => $result['withdrawal_id'] ?? 0,
            'unread'         => NotificationsData::count_unread($user_id),
        ]);
    }

    public function credit_deposit(): void
    {
        check_ajax_referer('jp_nonce', 'nonce');

        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(['message' => __('Faça login para adicionar créditos.', 'juntaplay')], 401);
        }

        $amount_raw = isset($_POST['amount']) ? wp_unslash($_POST['amount']) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing

        $result = $this->profile->initiate_deposit($user_id, $amount_raw);

        if (!empty($result['error'])) {
            $status = isset($result['status']) ? (int) $result['status'] : 400;
            wp_send_json_error([
                'message' => (string) $result['error'],
                'field'   => $result['field'] ?? '',
            ], $status);
        }

        wp_send_json_success([
            'message'  => (string) ($result['message'] ?? __('Recarga adicionada ao carrinho.', 'juntaplay')),
            'redirect' => (string) ($result['redirect'] ?? wc_get_checkout_url()),
        ]);
    }

    public function notifications_feed(): void
    {
        check_ajax_referer('jp_nonce', 'nonce');

        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(['message' => __('Faça login para ver suas notificações.', 'juntaplay')], 401);
        }

        $notifications = NotificationsData::get_recent($user_id, 15);
        $items         = [];

        foreach ($notifications as $notification) {
            $items[] = [
                'id'         => $notification['id'],
                'title'      => $notification['title'],
                'message'    => $notification['message'],
                'status'     => $notification['status'],
                'created_at' => $notification['created_at'],
                'time'       => $this->format_datetime($notification['created_at']),
                'action_url' => $notification['action_url'],
            ];
        }

        wp_send_json_success([
            'items'  => $items,
            'unread' => NotificationsData::count_unread($user_id),
        ]);
    }

    public function notifications_mark(): void
    {
        check_ajax_referer('jp_nonce', 'nonce');

        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(['message' => __('Sessão expirada.', 'juntaplay')], 401);
        }

        $ids = isset($_POST['ids']) ? (array) $_POST['ids'] : []; // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $ids = array_map('absint', $ids);

        if ($ids) {
            NotificationsData::mark_read($user_id, $ids);
        }

        wp_send_json_success(['unread' => NotificationsData::count_unread($user_id)]);
    }

    /**
     * @param array<string, mixed> $transaction
     */
    private function format_transaction(array $transaction, bool $with_context = false): array
    {
        $amount      = isset($transaction['amount']) ? (float) $transaction['amount'] : 0.0;
        $created_raw = (string) ($transaction['created_at'] ?? '');
        $context     = isset($transaction['context']) && is_array($transaction['context']) ? $transaction['context'] : [];

        $amount_formatted = $this->format_currency($amount);

        $timestamp = $created_raw !== '' ? strtotime($created_raw) : 0;
        if ($timestamp === false) {
            $timestamp = 0;
        }

        $type_label   = $this->translate_transaction_type((string) ($transaction['type'] ?? ''));
        $status_label = $this->translate_transaction_status((string) ($transaction['status'] ?? ''));

        $formatted = [
            'id'         => (int) ($transaction['id'] ?? 0),
            'type'       => (string) ($transaction['type'] ?? ''),
            'type_label' => $type_label,
            'status'     => (string) ($transaction['status'] ?? ''),
            'status_label' => $status_label,
            'amount'     => $amount_formatted,
            'amount_raw' => $amount,
            'amount_formatted' => $amount_formatted,
            'created_at' => $created_raw,
            'time'       => $this->format_datetime($created_raw),
            'reference'  => (string) ($transaction['reference'] ?? ''),
            'timestamp'  => is_int($timestamp) ? $timestamp : 0,
        ];

        $formatted['search'] = $this->normalize_search_terms([
            $type_label,
            $status_label,
            $formatted['reference'],
            $formatted['time'],
        ]);

        if ($with_context) {
            $formatted['context'] = $context;
            $formatted['balance_after'] = isset($transaction['balance_after']) ? $this->format_currency((float) $transaction['balance_after']) : '';
        }

        return $formatted;
    }

    private function translate_transaction_type(string $type): string
    {
        return match ($type) {
            CreditTransactions::TYPE_DEPOSIT => __('Entrada de créditos', 'juntaplay'),
            CreditTransactions::TYPE_WITHDRAWAL => __('Retirada', 'juntaplay'),
            CreditTransactions::TYPE_BONUS => __('Bônus promocional', 'juntaplay'),
            CreditTransactions::TYPE_PURCHASE => __('Compra de cotas', 'juntaplay'),
            CreditTransactions::TYPE_REFUND => __('Reembolso', 'juntaplay'),
            default => __('Ajuste de saldo', 'juntaplay'),
        };
    }

    private function translate_transaction_status(string $status): string
    {
        return match ($status) {
            CreditTransactions::STATUS_PENDING => __('Pendente', 'juntaplay'),
            CreditTransactions::STATUS_FAILED => __('Cancelado', 'juntaplay'),
            default => __('Concluído', 'juntaplay'),
        };
    }

    /**
     * @param string[] $parts
     */
    private function normalize_search_terms(array $parts): string
    {
        $joined = trim(implode(' ', array_filter(array_map('trim', $parts))));

        if ($joined === '') {
            return '';
        }

        if (function_exists('mb_strtolower')) {
            return mb_strtolower($joined, 'UTF-8');
        }

        return strtolower($joined);
    }

    /**
     * @param array<string, mixed> $pool
     * @param array<string, string> $categories
     * @return array<string, mixed>
     */
    private function format_pool(array $pool, array $categories): array
    {
        $category     = isset($pool['category']) ? (string) $pool['category'] : '';
        $category_lbl = $category !== '' && isset($categories[$category]) ? $categories[$category] : '';

        return [
            'id'            => (int) ($pool['id'] ?? 0),
            'title'         => (string) ($pool['title'] ?? ''),
            'slug'          => (string) ($pool['slug'] ?? ''),
            'excerpt'       => (string) ($pool['excerpt'] ?? ''),
            'thumbnail'     => (string) ($pool['thumbnail'] ?? ''),
            'is_featured'   => !empty($pool['is_featured']),
            'category'      => $category,
            'categoryLabel' => $category_lbl,
            'price'         => (float) ($pool['price'] ?? 0.0),
            'priceLabel'    => $this->format_currency((float) ($pool['price'] ?? 0.0)),
            'quotasTotal'   => (int) ($pool['quotas_total'] ?? 0),
            'quotasPaid'    => (int) ($pool['quotas_paid'] ?? 0),
            'quotasFree'    => (int) ($pool['quotas_free'] ?? 0),
            'progress'      => (int) ($pool['progress'] ?? 0),
            'permalink'     => $this->resolve_pool_link($pool),
        ];
    }

    private function resolve_pool_link(array $pool): string
    {
        $page_id = (int) get_option('juntaplay_page_grupos');
        $base    = $page_id ? get_permalink($page_id) : home_url('/grupos');

        if (!$base) {
            $base = home_url('/grupos');
        }

        $slug = isset($pool['slug']) ? (string) $pool['slug'] : '';

        if ($slug === '') {
            return $base;
        }

        return add_query_arg('grupo', rawurlencode($slug), $base);
    }

    /**
     * @param array<string, bool> $allcaps
     * @param string[] $caps
     * @param array<int|string, mixed> $args
     */
    public function grant_group_owner_upload_cap(array $allcaps, array $caps, array $args, WP_User $user): array
    {
        if (!in_array('upload_files', $caps, true)) {
            return $allcaps;
        }

        if (!isset($user->ID) || $user->ID <= 0) {
            return $allcaps;
        }

        if (!$this->profile->is_group_cover_upload_context_for_user((int) $user->ID)) {
            return $allcaps;
        }

        $allcaps['upload_files'] = true;

        return $allcaps;
    }

    public function filter_group_owner_upload_cap(string $required_cap): string
    {
        if ($this->profile->is_group_cover_upload_context_for_user()) {
            return 'upload_files';
        }

        return $required_cap;
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

    /**
     * @param array<string, mixed> $vars
     */
    private function render_template(string $template, array $vars = []): string
    {
        $file = JP_DIR . 'templates/' . ltrim($template, '/');

        if (!file_exists($file)) {
            return '';
        }

        if ($vars) {
            extract($vars, EXTR_SKIP);
        }

        ob_start();
        include $file;

        return (string) ob_get_clean();
    }

    private function format_currency(float $amount): string
    {
        if (class_exists('\\NumberFormatter')) {
            $formatter = new \NumberFormatter('pt_BR', \NumberFormatter::CURRENCY);
            $formatted = $formatter->formatCurrency($amount, 'BRL');
            if ($formatted !== false) {
                return str_replace("\xc2\xa0", ' ', $formatted);
            }
        }

        $formatted = 'R$ ' . number_format($amount, 2, ',', '.');

        return $formatted;
    }

    private function format_datetime(string $datetime): string
    {
        if ($datetime === '') {
            return '';
        }

        $timestamp = mysql2date('U', $datetime, false);

        if (!$timestamp) {
            return $datetime;
        }

        return wp_date(__('d/m/Y \à\s H\hi', 'juntaplay'), $timestamp);
    }
}
