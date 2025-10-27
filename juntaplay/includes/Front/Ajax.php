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
use function add_query_arg;
use function array_map;
use function check_ajax_referer;
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
use function wp_date;
use function wp_send_json_error;
use function wp_send_json_success;
use function wp_unslash;
use function wp_strip_all_tags;
use function wc_get_checkout_url;
use function wc_price;
use function _n;
use function __;
use function is_wp_error;
use function media_handle_upload;
use function do_action;

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

        $html = $this->render_template('group-modal-edit.php', [
            'group'      => $context['group'],
            'categories' => isset($context['categories']) && is_array($context['categories']) ? $context['categories'] : [],
        ]);

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

        $context = $this->profile->get_group_modal_context($group_id);
        if (!$context || empty($context['is_owner'])) {
            wp_send_json_error(['message' => __('Você não pode editar este grupo.', 'juntaplay')], 403);
        }

        $categories    = isset($context['categories']) && is_array($context['categories']) ? $context['categories'] : [];
        $group_payload = $context['group'];

        $title           = sanitize_text_field(wp_unslash($_POST['title'] ?? ''));
        $description     = sanitize_textarea_field(wp_unslash($_POST['description'] ?? ''));
        $rules           = sanitize_textarea_field(wp_unslash($_POST['rules'] ?? ''));
        $service_name    = sanitize_text_field(wp_unslash($_POST['service_name'] ?? ''));
        $service_url     = esc_url_raw((string) ($_POST['service_url'] ?? ''));
        $price_regular   = $this->profile->parse_money_input((string) ($_POST['price_regular'] ?? ''));
        $price_promo     = $this->profile->parse_money_input((string) ($_POST['price_promotional'] ?? ''));
        $member_price    = $this->profile->parse_money_input((string) ($_POST['member_price'] ?? ''));
        $slots_total     = max(0, absint($_POST['slots_total'] ?? 0));
        $slots_reserved  = max(0, absint($_POST['slots_reserved'] ?? 0));
        $support_channel = sanitize_text_field(wp_unslash($_POST['support_channel'] ?? ''));
        $delivery_time   = sanitize_text_field(wp_unslash($_POST['delivery_time'] ?? ''));
        $access_method   = sanitize_text_field(wp_unslash($_POST['access_method'] ?? ''));
        $category        = sanitize_key((string) ($_POST['category'] ?? ''));
        $instant_access  = !empty($_POST['instant_access']);
        $cover_id        = absint($_POST['cover_id'] ?? 0);

        $errors = [];

        if ($title === '' || strlen($title) < 3) {
            $errors[] = __('Informe um nome para o grupo com pelo menos 3 caracteres.', 'juntaplay');
        }

        if ($service_name === '' || strlen($service_name) < 3) {
            $errors[] = __('Descreva qual serviço será compartilhado.', 'juntaplay');
        }

        if ($description === '' || strlen($description) < 10) {
            $errors[] = __('Escreva uma descrição para os participantes.', 'juntaplay');
        }

        if ($rules === '' || strlen($rules) < 10) {
            $errors[] = __('Explique as regras do grupo.', 'juntaplay');
        }

        if ($price_regular <= 0) {
            $errors[] = __('Informe o valor do serviço.', 'juntaplay');
        }

        if ($member_price <= 0) {
            $errors[] = __('Defina quanto cada participante irá pagar.', 'juntaplay');
        }

        if ($slots_total <= 0) {
            $errors[] = __('Defina a quantidade de vagas disponíveis.', 'juntaplay');
        }

        if ($slots_reserved >= $slots_total) {
            $errors[] = __('As vagas reservadas precisam ser menores que o total disponível.', 'juntaplay');
        }

        if ($support_channel === '') {
            $errors[] = __('Informe o canal de suporte para os membros.', 'juntaplay');
        }

        if ($delivery_time === '') {
            $errors[] = __('Informe o prazo para liberar o acesso.', 'juntaplay');
        }

        if ($access_method === '') {
            $errors[] = __('Descreva como o acesso será entregue.', 'juntaplay');
        }

        if (!isset($categories[$category])) {
            $category = 'other';
        }

        if (!empty($_FILES['group_cover']) && !empty($_FILES['group_cover']['name'])) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';

            $uploaded = media_handle_upload('group_cover', 0);
            if (is_wp_error($uploaded)) {
                $errors[] = $uploaded->get_error_message();
            } else {
                $cover_id = (int) $uploaded;
            }
        }

        if ($cover_id <= 0) {
            $errors[] = __('Envie uma imagem de capa para o grupo.', 'juntaplay');
        }

        if ($errors) {
            wp_send_json_error(['message' => implode(' ', $errors)], 400);
        }

        $payload = [
            'title'             => $title,
            'description'       => $description,
            'rules'             => $rules,
            'service_name'      => $service_name,
            'service_url'       => $service_url,
            'price_regular'     => $price_regular,
            'price_promotional' => $price_promo,
            'member_price'      => $member_price,
            'slots_total'       => $slots_total,
            'slots_reserved'    => $slots_reserved,
            'support_channel'   => $support_channel,
            'delivery_time'     => $delivery_time,
            'access_method'     => $access_method,
            'category'          => $category,
            'instant_access'    => $instant_access,
            'cover_id'          => $cover_id,
            'pool_id'           => isset($group_payload['pool_id']) ? (int) $group_payload['pool_id'] : 0,
        ];

        $updated = Groups::update_basic($group_id, $payload);

        if (!$updated) {
            wp_send_json_error(['message' => __('Não foi possível salvar as alterações do grupo.', 'juntaplay')], 500);
        }

        $this->profile->invalidate_profile_cache();
        do_action('juntaplay/profile/groups/updated', get_current_user_id(), $group_id, $payload);

        wp_send_json_success(['message' => __('Grupo atualizado com sucesso.', 'juntaplay')]);
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
        check_ajax_referer('jp_nonce', 'nonce');

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
        $product_id = isset($pool['product_id']) ? (int) $pool['product_id'] : 0;

        if ($product_id > 0) {
            $permalink = get_permalink($product_id);
            if ($permalink) {
                return (string) $permalink;
            }
        }

        $slug = isset($pool['slug']) ? (string) $pool['slug'] : '';
        if ($slug !== '') {
            return home_url('/grupo/' . $slug);
        }

        return home_url('/grupos');
    }

    /**
     * @param array<string, mixed> $group
     * @param array<string, string> $categories
     * @return array<string, mixed>
     */
    private function format_group(array $group, array $categories): array
    {
        $category     = isset($group['category']) ? (string) $group['category'] : '';
        $category_lbl = $category !== '' && isset($categories[$category]) ? $categories[$category] : '';
        $owner_id     = isset($group['owner_id']) ? (int) $group['owner_id'] : 0;
        $price        = isset($group['effective_price']) ? (float) $group['effective_price'] : 0.0;

        $slots_total    = isset($group['slots_total']) ? max(0, (int) $group['slots_total']) : 0;
        $slots_reserved = isset($group['slots_reserved']) ? max(0, (int) $group['slots_reserved']) : 0;
        $slots_available = isset($group['slots_available']) ? max(0, (int) $group['slots_available']) : max(0, $slots_total - $slots_reserved);
        if ($slots_total > 0 && $slots_available > $slots_total) {
            $slots_available = $slots_total;
        }
        $slots_taken = $slots_total > 0 ? max(0, $slots_total - $slots_available) : 0;
        $availability_state = $slots_available > 0 ? 'available' : 'full';

        if ($slots_total > 0) {
            if ($slots_available === 0) {
                $slots_badge    = __('Vagas preenchidas', 'juntaplay');
                $badge_variant  = 'filled';
            } elseif ($slots_available === 1) {
                $slots_badge    = __('1 última vaga', 'juntaplay');
                $badge_variant  = 'last';
            } else {
                $slots_badge    = sprintf(_n('%d vaga disponível', '%d vagas disponíveis', $slots_available, 'juntaplay'), $slots_available);
                $badge_variant  = 'default';
            }
            $slots_summary = sprintf(__('Participantes: %1$d de %2$d confirmados', 'juntaplay'), $slots_taken, $slots_total);
        } else {
            $slots_badge    = __('Vagas sob consulta', 'juntaplay');
            $badge_variant  = 'info';
            $slots_summary  = __('O organizador confirma a disponibilidade após o pedido.', 'juntaplay');
        }

        $button_label = $availability_state === 'available'
            ? __('Assinar com vagas', 'juntaplay')
            : __('Aguardando membros', 'juntaplay');

        return [
            'id'              => (int) ($group['id'] ?? 0),
            'slug'            => (string) ($group['slug'] ?? ''),
            'title'           => (string) ($group['title'] ?? ''),
            'service'         => (string) ($group['service_name'] ?? ''),
            'serviceUrl'      => (string) ($group['service_url'] ?? ''),
            'category'        => $category,
            'categoryLabel'   => $category_lbl,
            'instantAccess'   => !empty($group['instant_access']),
            'coverUrl'        => (string) ($group['cover_url'] ?? ''),
            'coverAlt'        => (string) ($group['cover_alt'] ?? ''),
            'coverPlaceholder'=> !empty($group['cover_placeholder']),
            'price'           => $price,
            'priceLabel'      => $this->format_currency($price),
            'memberPrice'     => isset($group['member_price']) ? (float) $group['member_price'] : null,
            'memberPriceLabel'=> isset($group['member_price']) && $group['member_price'] !== null
                ? $this->format_currency((float) $group['member_price'])
                : '',
            'membersCount'    => (int) ($group['members_count'] ?? 0),
            'slotsTotal'      => $slots_total,
            'slotsAvailable'  => $slots_available,
            'slotsBadge'      => $slots_badge,
            'slotsBadgeVariant'=> $badge_variant,
            'slotsSummary'    => $slots_summary,
            'availabilityState'=> $availability_state,
            'buttonLabel'     => $button_label,
            'slotsTaken'      => $slots_taken,
            'support'         => (string) ($group['support_channel'] ?? ''),
            'delivery'        => (string) ($group['delivery_time'] ?? ''),
            'accessMethod'    => (string) ($group['access_method'] ?? ''),
            'description'     => (string) ($group['description'] ?? ''),
            'rules'           => (string) ($group['rules'] ?? ''),
            'ownerName'       => (string) ($group['owner_name'] ?? ''),
            'ownerEmail'      => (string) ($group['owner_email'] ?? ''),
            'ownerAvatar'     => $owner_id > 0 ? (string) get_avatar_url($owner_id, ['size' => 96]) : '',
            'permalink'       => $this->resolve_group_link($group),
            'created'         => $this->format_datetime((string) ($group['created_at'] ?? '')),
            'updated'         => $this->format_datetime((string) ($group['updated_at'] ?? '')),
        ];
    }

    /**
     * @param array<string, mixed> $group
     */
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
