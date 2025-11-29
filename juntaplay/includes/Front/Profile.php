<?php

declare(strict_types=1);

namespace JuntaPlay\Front;

use JuntaPlay\Data\CreditTransactions;
use JuntaPlay\Data\CreditWithdrawals;
use JuntaPlay\Data\GroupComplaintMessages;
use JuntaPlay\Data\GroupComplaints;
use JuntaPlay\Data\GroupMembershipEvents;
use JuntaPlay\Data\GroupMembers;
use JuntaPlay\Data\Groups;
use JuntaPlay\Data\Pools;
use JuntaPlay\Setup\PoolSeeder;
use JuntaPlay\Woo\Credits as WooCredits;
use JuntaPlay\Woo\GroupsProduct as WooGroupsProduct;
use WP_Error;
use WP_User;

use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

use WP_Post;
use WP_Session_Tokens;
use function __;
use function _n;
use function add_action;
use function add_filter;
use function add_query_arg;
use function absint;
use function apply_filters;
use function do_action;
use function esc_url_raw;
use function email_exists;
use function in_array;
use function current_time;
use function current_user_can;
use function date_i18n;
use function delete_user_meta;
use function get_current_user_id;
use function get_bloginfo;
use function get_avatar_url;
use function get_option;
use function get_post;
use function get_post_mime_type;
use function get_permalink;
use function get_role;
use function get_userdata;
use function get_user_meta;
use function get_the_title;
use function gmdate;
use function human_time_diff;
use function home_url;
use function is_admin;
use function is_email;
use function is_string;
use function is_user_logged_in;
use function number_format_i18n;
use function preg_replace;
use function preg_split;
use function plugins_url;
use function rawurlencode;
use function parse_str;
use function register_rest_route;
use function sanitize_email;
use function sanitize_key;
use function sanitize_text_field;
use function sanitize_textarea_field;
use function sanitize_title;
use function strtotime;
use function strlen;
use function str_starts_with;
use function strpos;
use function substr;
use function strtolower;
use function strtok;
use function trim;
use function strtoupper;
use function time;
use function update_user_meta;
use function update_option;
use function wp_check_password;
use function is_wp_error;
use function media_handle_upload;
use function wp_delete_attachment;
use function wp_destroy_other_sessions;
use function wp_delete_user;
use function wp_generate_uuid4;
use function wp_create_nonce;
use function wp_get_current_user;
use function wp_get_attachment_image_url;
use function wp_get_attachment_url;
use function wp_get_session_token;
use function wp_parse_url;
use function wp_strip_all_tags;
use function wp_set_auth_cookie;
use function wp_unslash;
use function wp_update_user;
use function wp_hash_password;
use function wp_rand;
use function has_filter;
use const MINUTE_IN_SECONDS;
use JuntaPlay\Notifications\EmailHelper;
use WP_Query;
use function wp_verify_nonce;
use function trailingslashit;
use function WC;
use function wc_get_checkout_url;
use function wc_load_cart;
use function wp_safe_redirect;
use function wp_logout;
use function remove_query_arg;
use function wc_add_notice;
use function is_checkout;

if (!defined('ABSPATH')) {
    exit;
}

class Profile
{
    private const DIAGNOSTICS_OPTION = 'juntaplay_diagnostics_status';

    private const SECTION_COMPLAINTS = 'complaints_center';

    /** @var array<string, string[]> */
    private array $errors = [];

    /** @var string[] */
    private array $notices = [];

    private ?string $active_section = null;

    /** @var array<string, mixed>|null */
    private ?array $cached_profile = null;

    /** @var array<string, string> */
    private array $group_draft = [];

    /** @var array<string, string>|null */
    private ?array $group_create_success = null;

    /** @var array<int, array<string, mixed>> */
    private array $group_complaint_summary = [];

    /** @var array<int, array<string, string>> */
    private array $group_complaint_draft = [];

    /** @var array<int, string[]> */
    private array $group_complaint_success = [];

    /** @var array<int, string[]> */
    private array $complaint_center_errors = [];

    /** @var array<int, string[]> */
    private array $complaint_center_success = [];

    /** @var array<string, mixed>|null */
    private ?array $complaint_center_state = null;

    /** @var array<int, array<string, mixed>> */
    private array $group_cancellation_events = [];

    public function init(): void
    {
        add_action('init', [$this, 'synchronize_subscriber_capabilities'], 5);
        add_action('init', [$this, 'maybe_handle_update']);
        add_filter('ajax_query_attachments_args', [$this, 'limit_media_library_to_author']);
        add_action('pre_get_posts', [$this, 'limit_media_library_query']);
        add_filter('wp_prepare_attachment_for_js', [$this, 'maybe_hide_foreign_attachments'], 10, 3);
        add_action('rest_api_init', [$this, 'register_rest_routes']);
        add_action('template_redirect', [$this, 'maybe_prepare_group_checkout']);
    }

    public function synchronize_subscriber_capabilities(): void
    {
        $contributor = get_role('contributor');

        if (!$contributor instanceof \WP_Role) {
            return;
        }

        $capabilities = [];

        foreach ($contributor->capabilities as $capability => $granted) {
            if ($granted) {
                $capabilities[] = $capability;
            }
        }

        if ($capabilities === []) {
            return;
        }

        foreach (['subscriber'] as $role_key) {
            $role = get_role($role_key);

            if (!$role instanceof \WP_Role) {
                continue;
            }

            foreach ($capabilities as $capability) {
                if (!$role->has_cap($capability)) {
                    $role->add_cap($capability);
                }
            }

            if (!$role->has_cap('upload_files')) {
                $role->add_cap('upload_files');
            }
        }
    }

    public function maybe_handle_update(): void
    {
        if (!isset($_POST['jp_profile_action'])) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
            return;
        }

        if (!is_user_logged_in()) {
            $this->add_error('general', __('Faça login para atualizar o perfil.', 'juntaplay'));
            return;
        }

        $section = isset($_POST['jp_profile_section'])
            ? sanitize_key(wp_unslash($_POST['jp_profile_section'])) // phpcs:ignore WordPress.Security.NonceVerification.Missing
            : '';

        if (in_array($section, ['avatar_upload', 'avatar_remove'], true)) {
            $this->active_section = 'avatar';
        } else {
            $this->active_section = $section !== '' ? $section : null;
        }

        if (!isset($_POST['jp_profile_nonce'])
            || !wp_verify_nonce(
                sanitize_text_field(wp_unslash($_POST['jp_profile_nonce'] ?? '')),
                'juntaplay_profile_update'
            )
        ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
            $this->add_error($section ?: 'general', __('Sua sessão expirou. Atualize a página e tente novamente.', 'juntaplay'));
            return;
        }

        $user_id = get_current_user_id();

        if (!$user_id) {
            $this->add_error('general', __('Não foi possível localizar o usuário autenticado.', 'juntaplay'));
            return;
        }

        switch ($section) {
            case 'name':
                $this->update_name($user_id);
                break;
            case 'email':
                $this->update_email($user_id);
                break;
            case 'phone':
                $this->update_phone($user_id);
                break;
            case 'whatsapp':
                $this->update_whatsapp($user_id);
                break;
            case 'account_delete':
                $this->delete_account($user_id);
                break;
            case 'tax_type':
                $this->update_tax_type($user_id);
                break;
            case 'tax_document':
                $this->update_tax_document($user_id);
                break;
            case 'tax_company':
                $this->update_tax_company($user_id);
                break;
            case 'tax_state_registration':
                $this->update_tax_state_registration($user_id);
                break;
            case 'tax_address':
                $this->update_tax_address($user_id);
                break;
            case 'tax_city':
                $this->update_tax_city($user_id);
                break;
            case 'tax_state':
                $this->update_tax_state($user_id);
                break;
            case 'tax_country':
                $this->update_tax_country($user_id);
                break;
            case 'tax_postcode':
                $this->update_tax_postcode($user_id);
                break;
            case 'password':
                $this->update_password($user_id);
                break;
            case 'two_factor':
                $this->update_two_factor($user_id);
                break;
            case 'login_alerts':
                $this->update_login_alerts($user_id);
                break;
            case 'sessions':
                $this->update_sessions($user_id);
                break;
            case 'credit_auto':
                $this->update_credit_auto($user_id);
                break;
            case 'credit_payment_method':
                $this->update_credit_payment_method($user_id);
                break;
            case 'credit_pix_key':
                $this->update_credit_pix_key($user_id);
                break;
            case 'credit_bank_account':
                $this->update_credit_bank_account($user_id);
                break;
            case 'credit_withdrawal':
                $this->submit_credit_withdrawal_form($user_id);
                break;
            case 'group_create':
                $this->create_group($user_id);
                break;
            case 'group_complaint':
                $this->submit_group_complaint($user_id);
                break;
            case 'group_cancel':
                $this->cancel_group_membership($user_id);
                break;
            case 'complaint_reply':
                $this->submit_complaint_message($user_id, GroupComplaintMessages::TYPE_MESSAGE);
                break;
            case 'complaint_proposal':
                $this->submit_complaint_message($user_id, GroupComplaintMessages::TYPE_PROPOSAL);
                break;
            case 'complaint_accept':
                $this->accept_complaint_proposal($user_id);
                break;
            case 'avatar_upload':
                $this->update_avatar_upload($user_id);
                break;
            case 'avatar_remove':
                $this->remove_avatar($user_id);
                break;
            default:
                $this->add_error('general', __('Atualização inválida.', 'juntaplay'));
        }
    }

    /**
     * @param array<string, mixed> $args
     * @return array<string, mixed>
     */
    public function limit_media_library_to_author(array $args): array
    {
        if (!is_user_logged_in()) {
            return $args;
        }

        if (current_user_can('manage_options') || current_user_can('edit_others_posts')) {
            return $args;
        }

        $user_id = get_current_user_id();
        if ($user_id <= 0) {
            return $args;
        }

        $args['author']     = $user_id;
        $args['author__in'] = [$user_id];

        unset($args['author__not_in']);

        return $args;
    }

    /**
     * @param WP_Query $query
     */
    public function limit_media_library_query($query): void
    {
        if (!$query instanceof WP_Query) {
            return;
        }

        if (!is_admin() || !is_user_logged_in()) {
            return;
        }

        if ($query->get('post_type') !== 'attachment') {
            return;
        }

        if (current_user_can('manage_options') || current_user_can('edit_others_posts')) {
            return;
        }

        if ($query->get('author') || $query->get('author__in')) {
            return;
        }

        $user_id = get_current_user_id();
        if ($user_id <= 0) {
            return;
        }

        $query->set('author', $user_id);
    }

    /**
     * Hide attachments from other users when preparing media responses.
     *
     * @param array<string, mixed>|false $response
     * @param WP_Post                     $attachment
     * @param array<string, mixed>        $meta
     * @return array<string, mixed>|false
     */
    public function maybe_hide_foreign_attachments($response, $attachment, $meta)
    {
        if (!is_array($response) || !$attachment instanceof WP_Post) {
            return $response;
        }

        if (current_user_can('manage_options') || current_user_can('edit_others_posts')) {
            return $response;
        }

        if (!is_user_logged_in()) {
            return false;
        }

        $user_id = get_current_user_id();
        if ($user_id <= 0) {
            return false;
        }

        if ((int) $attachment->post_author !== $user_id) {
            return false;
        }

        return $response;
    }


    public function get_active_section(): ?string
    {
        return $this->active_section;
    }

    /**
     * @return array<string, string[]>
     */
    public function get_errors(): array
    {
        return $this->errors;
    }

    /**
     * @return string[]
     */
    public function get_notices(): array
    {
        return $this->notices;
    }

    /**
     * @return array<string, mixed>
     */
    public function get_profile_data(): array
    {
        if ($this->cached_profile !== null) {
            return $this->cached_profile;
        }

        $user = wp_get_current_user();

        if (!$user instanceof WP_User || !$user->exists()) {
            $this->cached_profile = [];

            return $this->cached_profile;
        }

        $avatar_custom_url    = (string) get_user_meta($user->ID, 'juntaplay_avatar_url', true);
        $avatar_attachment    = (int) get_user_meta($user->ID, 'juntaplay_avatar_id', true);
        $phone                = (string) get_user_meta($user->ID, 'billing_phone', true);
        $whatsapp             = (string) get_user_meta($user->ID, 'juntaplay_whatsapp', true);
        $tax_type             = (string) get_user_meta($user->ID, 'juntaplay_tax_type', true);
        $tax_document         = (string) get_user_meta($user->ID, 'juntaplay_tax_document', true);
        $tax_company          = (string) get_user_meta($user->ID, 'billing_company', true);
        $tax_state_regist     = (string) get_user_meta($user->ID, 'juntaplay_tax_state_registration', true);
        $tax_address          = (string) get_user_meta($user->ID, 'billing_address_1', true);
        $tax_city             = (string) get_user_meta($user->ID, 'billing_city', true);
        $tax_state            = (string) get_user_meta($user->ID, 'billing_state', true);
        $tax_country          = (string) get_user_meta($user->ID, 'billing_country', true);
        $tax_postcode         = (string) get_user_meta($user->ID, 'billing_postcode', true);
        $two_factor           = (string) get_user_meta($user->ID, 'juntaplay_two_factor_method', true);
        $login_alerts         = (string) get_user_meta($user->ID, 'juntaplay_login_alerts', true);
        $password_changed_at  = (string) get_user_meta($user->ID, 'juntaplay_password_changed_at', true);
        $sessions_active      = $this->get_sessions_count($user);
        $credit_balance       = $this->to_float(get_user_meta($user->ID, 'juntaplay_credit_balance', true));
        $credit_reserved      = $this->to_float(get_user_meta($user->ID, 'juntaplay_credit_reserved', true));
        $credit_bonus         = $this->to_float(get_user_meta($user->ID, 'juntaplay_credit_bonus', true));
        $credit_bonus_expiry  = (string) get_user_meta($user->ID, 'juntaplay_credit_bonus_expires_at', true);
        $credit_updated_at    = (string) get_user_meta($user->ID, 'juntaplay_credit_updated_at', true);
        $credit_last_recharge = (string) get_user_meta($user->ID, 'juntaplay_credit_last_recharge', true);
        $credit_auto_status   = (string) get_user_meta($user->ID, 'juntaplay_credit_auto_status', true);
        $credit_auto_amount   = $this->to_float(get_user_meta($user->ID, 'juntaplay_credit_auto_amount', true));
        $credit_auto_threshold = $this->to_float(get_user_meta($user->ID, 'juntaplay_credit_auto_threshold', true));
        $credit_payment_method = (string) get_user_meta($user->ID, 'juntaplay_credit_payment_method', true);
        $credit_pix_key       = (string) get_user_meta($user->ID, 'juntaplay_credit_pix_key', true);
        $credit_bank_holder   = (string) get_user_meta($user->ID, 'juntaplay_credit_bank_holder', true);
        $credit_bank_document = (string) get_user_meta($user->ID, 'juntaplay_credit_bank_document', true);
        $credit_bank_name     = (string) get_user_meta($user->ID, 'juntaplay_credit_bank_name', true);
        $credit_bank_type     = (string) get_user_meta($user->ID, 'juntaplay_credit_bank_type', true);
        $credit_bank_agency   = (string) get_user_meta($user->ID, 'juntaplay_credit_bank_agency', true);
        $credit_bank_account  = (string) get_user_meta($user->ID, 'juntaplay_credit_bank_account', true);
        $credit_bank_account_type = (string) get_user_meta($user->ID, 'juntaplay_credit_bank_account_type', true);
        $credit_withdraw_pending  = CreditWithdrawals::get_pending_total((int) $user->ID);
        $withdraw_code_expires    = (int) get_user_meta($user->ID, 'juntaplay_withdraw_code_expires', true);

        if ($whatsapp === '') {
            $whatsapp = $phone;
        }

        if ($tax_type === '') {
            $tax_type = 'pf';
        }

        if ($tax_country === '') {
            $tax_country = 'BR';
        }

        if (!in_array($two_factor, ['email', 'whatsapp'], true)) {
            $two_factor = 'off';
        }

        if ($login_alerts !== 'no') {
            $login_alerts = 'yes';
        }

        if ($credit_auto_status !== 'on') {
            $credit_auto_status = 'off';
        }

        if (!in_array($credit_payment_method, ['pix', 'card', 'boleto'], true)) {
            $credit_payment_method = 'pix';
        }

        if (!in_array($credit_bank_type, ['pf', 'pj'], true)) {
            $credit_bank_type = 'pf';
        }

        if (!in_array($credit_bank_account_type, ['checking', 'savings'], true)) {
            $credit_bank_account_type = 'checking';
        }

        $groups_data = Groups::get_groups_for_user((int) $user->ID);
        $pool_choices = Groups::get_pool_choices();

        $avatar_url = '';
        $has_custom = false;

        if ($avatar_custom_url !== '') {
            $avatar_url = esc_url_raw($avatar_custom_url);
            $has_custom = true;
        }

        if ($avatar_url === '' && $avatar_attachment > 0) {
            $maybe_url = wp_get_attachment_image_url($avatar_attachment, 'thumbnail');
            if ($maybe_url) {
                $avatar_url = $maybe_url;
                $has_custom = true;
            }
        }

        $avatar_fallback = get_avatar_url($user->ID, ['size' => 160]);
        if ($avatar_url === '' && $avatar_fallback) {
            $avatar_url = esc_url_raw($avatar_fallback);
        }

        $profile = [
            'name'                   => $user->display_name ?: $user->user_login,
            'avatar_id'              => $avatar_attachment,
            'avatar_url'             => $avatar_url,
            'avatar_has_custom'      => $has_custom,
            'email'                  => $user->user_email,
            'phone'                  => $phone,
            'whatsapp'               => $whatsapp,
            'tax_type'               => $tax_type,
            'tax_document'           => $tax_document,
            'tax_company'            => $tax_company,
            'tax_state_registration' => $tax_state_regist,
            'tax_address'            => $tax_address,
            'tax_city'               => $tax_city,
            'tax_state'              => $tax_state,
            'tax_country'            => $tax_country,
            'tax_postcode'           => $tax_postcode,
            'two_factor_method'      => $two_factor,
            'login_alerts'           => $login_alerts,
            'password_changed_at'    => $password_changed_at,
            'sessions_active'        => $sessions_active,
            'credit_balance'         => $credit_balance,
            'credit_reserved'        => $credit_reserved,
            'credit_bonus'           => $credit_bonus,
            'credit_bonus_expiry'    => $credit_bonus_expiry,
            'credit_updated_at'      => $credit_updated_at,
            'credit_last_recharge'   => $credit_last_recharge,
            'credit_auto_status'     => $credit_auto_status,
            'credit_auto_amount'     => $credit_auto_amount,
            'credit_auto_threshold'  => $credit_auto_threshold,
            'credit_payment_method'  => $credit_payment_method,
            'credit_pix_key'         => $credit_pix_key,
            'credit_bank_holder'     => $credit_bank_holder,
            'credit_bank_document'   => $credit_bank_document,
            'credit_bank_name'       => $credit_bank_name,
            'credit_bank_type'       => $credit_bank_type,
            'credit_bank_agency'     => $credit_bank_agency,
            'credit_bank_account'    => $credit_bank_account,
            'credit_bank_account_type' => $credit_bank_account_type,
            'credit_withdraw_pending'  => $credit_withdraw_pending,
            'withdraw_code_expires'    => $withdraw_code_expires,
            'groups'                 => [
                'owned'  => $groups_data['owned'] ?? [],
                'member' => $groups_data['member'] ?? [],
            ],
            'group_pool_options'     => $pool_choices,
        ];

        $this->cached_profile = apply_filters('juntaplay/profile/data', $profile, $user);

        return $this->cached_profile;
    }

    private function get_profile_base_url(): string
    {
        $profile_page_id = (int) get_option('juntaplay_page_perfil');
        $url             = $profile_page_id ? get_permalink($profile_page_id) : '';

        if ($url === '') {
            $url = home_url('/perfil');
        }

        if ($url === '') {
            $url = home_url('/');
        }

        return $url;
    }

    private function get_support_center_url(array $args = []): string
    {
        $url = add_query_arg(
            [
                'jp_category' => 'support',
                'jp_tab'      => 'support_complaints',
            ],
            $this->get_profile_base_url()
        );

        if ($args) {
            $url = add_query_arg($args, $url);
        }

        return $url;
    }

    /**
     * @return array<string, string>
     */
    public function get_header_context(): array
    {
        $user = wp_get_current_user();

        if (!$user instanceof WP_User || !$user->exists()) {
            return [];
        }

        $data = $this->get_profile_data();

        $name  = isset($data['name']) ? (string) $data['name'] : ($user->display_name ?: $user->user_login);
        $email = isset($data['email']) ? (string) $data['email'] : $user->user_email;

        $first_name = trim((string) get_user_meta($user->ID, 'first_name', true));

        if ($first_name === '') {
            $candidates = [$name, $user->display_name, $user->user_login];

            foreach ($candidates as $candidate) {
                if (!is_string($candidate)) {
                    continue;
                }

                $candidate = trim($candidate);

                if ($candidate === '') {
                    continue;
                }

                $parts = preg_split('/\s+/', $candidate);

                if (!empty($parts)) {
                    $first_name = (string) $parts[0];
                    break;
                }
            }
        }

        if ($first_name === '') {
            $first_name = $user->user_login;
        }

        $initial_source = $first_name !== '' ? $first_name : $name;
        $initial        = '';

        if (is_string($initial_source) && $initial_source !== '') {
            $initial = function_exists('mb_substr') ? mb_substr($initial_source, 0, 1) : substr($initial_source, 0, 1);
        }

        if ($initial === '') {
            $initial = 'J';
        }

        if (function_exists('mb_strtoupper')) {
            $initial = mb_strtoupper($initial);
        } else {
            $initial = strtoupper($initial);
        }

        $avatar_url = isset($data['avatar_url']) ? (string) $data['avatar_url'] : '';

        if ($avatar_url === '') {
            $fallback = get_avatar_url($user->ID, ['size' => 96]);
            if ($fallback) {
                $avatar_url = esc_url_raw($fallback);
            }
        }

        return [
            'name'       => $name,
            'first_name' => $first_name,
            'email'      => $email,
            'avatar_url' => $avatar_url,
            'initial'    => $initial,
        ];
    }

    public function get_credit_page_context(): array
    {
        $data    = $this->get_profile_data();
        $wallet  = $this->build_credit_history_context($data);
        $summary = [];

        $updated_label = '';
        if (!empty($data['credit_updated_at'])) {
            $updated_time = strtotime((string) $data['credit_updated_at']);
            if ($updated_time) {
                $updated_label = date_i18n(__('d/m/Y \à\s H\hi', 'juntaplay'), $updated_time);
            }
        }

        $last_recharge_label = '';
        if (!empty($data['credit_last_recharge'])) {
            $last_recharge_time = strtotime((string) $data['credit_last_recharge']);
            if ($last_recharge_time) {
                $last_recharge_label = date_i18n(__('d/m/Y \à\s H\hi', 'juntaplay'), $last_recharge_time);
            }
        }

        $bonus_expiry_label = '';
        if (!empty($data['credit_bonus_expiry'])) {
            $bonus_expiry_time = strtotime((string) $data['credit_bonus_expiry']);
            if ($bonus_expiry_time) {
                $bonus_expiry_label = date_i18n(__('d/m/Y', 'juntaplay'), $bonus_expiry_time);
            }
        }

        $summary[] = [
            'label' => __('Saldo disponível', 'juntaplay'),
            'value' => isset($wallet['balance_label']) ? (string) $wallet['balance_label'] : '',
            'hint'  => $updated_label !== '' ? sprintf(__('Atualizado %s', 'juntaplay'), $updated_label) : '',
        ];

        $summary[] = [
            'label' => __('Reservado', 'juntaplay'),
            'value' => isset($wallet['reserved_label']) ? (string) $wallet['reserved_label'] : '',
        ];

        $summary[] = [
            'label' => __('Bônus disponível', 'juntaplay'),
            'value' => isset($wallet['bonus_label']) ? (string) $wallet['bonus_label'] : '',
            'hint'  => $bonus_expiry_label !== '' ? sprintf(__('Expira em %s', 'juntaplay'), $bonus_expiry_label) : '',
            'tone'  => 'accent',
        ];

        $summary[] = [
            'label' => __('Saques em análise', 'juntaplay'),
            'value' => isset($wallet['withdraw_pending']) ? (string) $wallet['withdraw_pending'] : '',
            'tone'  => 'warning',
        ];

        $summary = array_filter(
            $summary,
            static fn (array $item): bool => ($item['value'] ?? '') !== ''
        );

        return [
            'profile' => [
                'name'           => isset($data['name']) ? (string) $data['name'] : '',
                'email'          => isset($data['email']) ? (string) $data['email'] : '',
                'last_recharge'  => $last_recharge_label,
                'updated_at'     => $updated_label,
                'bonus_expires'  => $bonus_expiry_label,
            ],
            'wallet'  => $wallet,
            'summary' => array_values($summary),
        ];
    }

    public function get_groups_page_context(): array
    {
        $data      = $this->get_profile_data();
        $group_hub = $this->build_group_hub_context($data);

        $group_counts    = $group_hub['group_counts'];
        $open_complaints = $this->count_open_complaints();
        $complaint_hint  = $this->format_complaint_hint();

        $summary = [];

        $summary[] = [
            'label' => __('Criados por você', 'juntaplay'),
            'value' => number_format_i18n($group_counts['owned'] ?? 0),
            'tone'  => 'info',
        ];

        $summary[] = [
            'label' => __('Participando', 'juntaplay'),
            'value' => number_format_i18n($group_counts['member'] ?? 0),
            'tone'  => 'accent',
        ];

        $summary[] = [
            'label' => __('Grupos aprovados', 'juntaplay'),
            'value' => number_format_i18n($group_counts['approved'] ?? 0),
            'tone'  => 'positive',
        ];

        $summary[] = [
            'label' => __('Aguardando análise', 'juntaplay'),
            'value' => number_format_i18n($group_counts['pending'] ?? 0),
            'tone'  => 'warning',
        ];

        $summary[] = [
            'label' => __('Reclamações abertas', 'juntaplay'),
            'value' => number_format_i18n($open_complaints),
            'tone'  => $open_complaints > 0 ? 'danger' : 'info',
            'hint'  => $complaint_hint,
        ];

        $summary = array_values(array_filter(
            $summary,
            static fn (array $item): bool => array_key_exists('value', $item)
        ));

        $profile_name = isset($data['name']) ? (string) $data['name'] : '';
        $profile_email = isset($data['email']) ? (string) $data['email'] : '';

        $hero_description = $profile_name !== ''
            ? sprintf(__('Olá, %s! Organize os grupos que você administra ou participa.', 'juntaplay'), $profile_name)
            : __('Organize os grupos que você administra ou participa.', 'juntaplay');

        $campanhas_page_id = (int) get_option('juntaplay_page_campanhas');
        $campaigns_url     = $campanhas_page_id ? get_permalink($campanhas_page_id) : home_url('/grupos');

        $actions = [
            [
                'label'      => __('Criar novo grupo', 'juntaplay'),
                'variant'    => 'primary',
                'icon'       => 'plus',
                'description'=> __('Envie um novo grupo para aprovação em poucos passos.', 'juntaplay'),
                'attributes' => [
                    'data-group-create-trigger' => '1',
                ],
            ],
        ];

        if ($campaigns_url) {
            $actions[] = [
                'label'   => __('Explorar grupos', 'juntaplay'),
                'variant' => 'ghost',
                'icon'    => 'search',
                'description' => __('Encontre oportunidades com vagas imediatas.', 'juntaplay'),
                'url'     => $campaigns_url,
            ];
        }

        $support_url = $this->get_support_center_url();
        if ($support_url !== '') {
            $actions[] = [
                'label'       => __('Reclamações', 'juntaplay'),
                'variant'     => 'ghost',
                'icon'        => 'ticket',
                'description' => __('Acompanhe tickets abertos ou resolvidos pelos seus grupos.', 'juntaplay'),
                'url'         => $support_url,
            ];
        }

        return [
            'profile' => [
                'name'        => $profile_name,
                'email'       => $profile_email,
                'description' => $hero_description,
            ],
            'summary'       => $summary,
            'actions'       => $actions,
            'group_context' => $group_hub['context'],
            'counts'        => $group_counts,
            'open_complaints' => $open_complaints,
            'complaint_hint'  => $complaint_hint,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function get_complaint_center_context(): array
    {
        if ($this->complaint_center_state !== null) {
            return $this->complaint_center_state;
        }

        $user_id  = get_current_user_id();
        $base_url = $this->get_support_center_url();

        $filter_raw = isset($_GET['jp_complaint_filter']) ? wp_unslash($_GET['jp_complaint_filter']) : '';
        $filter     = sanitize_key((string) $filter_raw);

        if (!in_array($filter, ['open', 'closed', 'all'], true)) {
            $filter = 'open';
        }

        $counts_raw = $user_id > 0 ? GroupComplaints::get_counts($user_id) : ['participant' => [], 'owner' => []];
        $participant_counts = $this->summarize_complaint_counts($counts_raw['participant'] ?? []);
        $owner_counts       = $this->summarize_complaint_counts($counts_raw['owner'] ?? []);

        $participant_rows = $user_id > 0 ? GroupComplaints::get_for_user($user_id, $filter) : [];
        $owner_rows       = $user_id > 0 ? GroupComplaints::get_for_owner($user_id, $filter) : [];

        $items = [];
        foreach ($participant_rows as $complaint) {
            $items[] = $this->decorate_complaint_list_item($complaint, 'participant');
        }
        foreach ($owner_rows as $complaint) {
            $items[] = $this->decorate_complaint_list_item($complaint, 'owner');
        }

        usort($items, static function (array $a, array $b): int {
            return strcmp((string) ($b['created_at'] ?? ''), (string) ($a['created_at'] ?? ''));
        });

        $ticket_param = isset($_GET['jp_ticket']) ? wp_unslash($_GET['jp_ticket']) : '';
        $active_id    = $this->parse_ticket_param((string) $ticket_param);

        if ($active_id <= 0 && $items) {
            $active_id = (int) ($items[0]['id'] ?? 0);
        }

        foreach ($items as &$item) {
            $query_args = [
                'jp_complaint_filter' => $filter,
            ];

            $ticket_value = isset($item['ticket']) ? (string) $item['ticket'] : '';
            if ($ticket_value !== '') {
                $query_args['jp_ticket'] = $ticket_value;
            }

            $item['url']       = add_query_arg($query_args, $base_url);
            $item['is_active'] = $active_id > 0 && (int) ($item['id'] ?? 0) === $active_id;
        }
        unset($item);

        $detail               = null;
        $messages             = [];
        $detail_role          = 'viewer';
        $can_reply            = false;
        $can_propose          = false;
        $can_accept           = false;
        $has_pending_proposal = false;
        $latest_proposal      = null;

        if ($active_id > 0 && $user_id > 0) {
            $detail = GroupComplaints::get($active_id);

            if ($detail) {
                $messages_raw = GroupComplaintMessages::get_for_complaint($active_id);
                $messages     = array_map(function (array $message) use ($detail, $user_id): array {
                    $author_id = isset($message['user_id']) ? (int) $message['user_id'] : 0;
                    $role      = 'system';

                    if ($message['type'] === GroupComplaintMessages::TYPE_SYSTEM || $author_id === 0) {
                        $role = 'system';
                    } elseif ($author_id === (int) ($detail['user_id'] ?? 0)) {
                        $role = 'participant';
                    } elseif ($author_id === (int) ($detail['owner_id'] ?? 0)) {
                        $role = 'owner';
                    } else {
                        $role = 'staff';
                    }

                    return [
                        'id'          => isset($message['id']) ? (int) $message['id'] : 0,
                        'type'        => (string) ($message['type'] ?? GroupComplaintMessages::TYPE_MESSAGE),
                        'message'     => (string) ($message['message'] ?? ''),
                        'created_at'  => (string) ($message['created_at'] ?? ''),
                        'time'        => $this->format_datetime((string) ($message['created_at'] ?? '')),
                        'author_name' => (string) ($message['author_name'] ?? ''),
                        'role'        => $role,
                        'is_current'  => $author_id === $user_id,
                        'attachments' => $this->build_complaint_attachments($message['attachments'] ?? []),
                    ];
                }, $messages_raw);

                $detail['attachments_list'] = $this->build_complaint_attachments($detail['attachments'] ?? []);
                $detail['messages']         = $messages;

                $detail_role = $user_id === (int) ($detail['owner_id'] ?? 0)
                    ? 'owner'
                    : ($user_id === (int) ($detail['user_id'] ?? 0) ? 'participant' : 'viewer');

                $status  = isset($detail['status']) ? (string) $detail['status'] : GroupComplaints::STATUS_OPEN;
                $is_open = in_array($status, [GroupComplaints::STATUS_OPEN, GroupComplaints::STATUS_UNDER_REVIEW], true);

                $can_reply   = $detail_role === 'owner' || $detail_role === 'participant';
                $can_propose = $detail_role === 'owner' && $is_open;

                foreach (array_reverse($messages) as $message) {
                    if (($message['type'] ?? '') !== GroupComplaintMessages::TYPE_PROPOSAL) {
                        continue;
                    }

                    $latest_proposal = $message;
                    break;
                }

                if ($detail_role === 'participant' && $is_open && $latest_proposal && ($latest_proposal['role'] ?? '') === 'owner') {
                    $has_pending_proposal = true;
                    $can_accept           = true;
                }

                $detail['opened_label']  = $this->format_datetime((string) ($detail['created_at'] ?? ''));
                $detail['updated_label'] = $this->format_datetime((string) ($detail['updated_at'] ?? ''));
                $detail['order_label']   = isset($detail['order_id']) && (int) $detail['order_id'] > 0
                    ? '#' . number_format_i18n((int) $detail['order_id'])
                    : '';
            }
        }

        $global_errors = $this->complaint_center_errors[0] ?? [];
        unset($this->complaint_center_errors[0]);

        $errors  = $this->complaint_center_errors;
        $success = $this->complaint_center_success;

        $limits = $this->get_complaint_limits();

        $filter_urls = [
            'open'   => add_query_arg(['jp_complaint_filter' => 'open'], $base_url),
            'closed' => add_query_arg(['jp_complaint_filter' => 'closed'], $base_url),
            'all'    => add_query_arg(['jp_complaint_filter' => 'all'], $base_url),
        ];

        $my_groups_page_id = (int) get_option('juntaplay_page_meus-grupos');
        $my_groups_url     = $my_groups_page_id ? get_permalink($my_groups_page_id) : '';
        if ($my_groups_url === '') {
            $my_groups_url = home_url('/meus-grupos/');
        }

        $this->complaint_center_state = [
            'filter'        => $filter,
            'counts'        => [
                'participant' => $participant_counts,
                'owner'       => $owner_counts,
            ],
            'complaints'    => $items,
            'active_id'     => $active_id,
            'active_ticket' => $detail && isset($detail['ticket']) ? (string) $detail['ticket'] : '',
            'detail'        => $detail,
            'detail_role'   => $detail_role,
            'messages'      => $messages,
            'errors'        => $errors,
            'global_errors' => $global_errors,
            'success'       => $success,
            'nonces'        => [
                'reply'  => wp_create_nonce('jp_profile_complaint_action'),
                'accept' => wp_create_nonce('jp_profile_complaint_accept'),
            ],
            'limits'        => $limits,
            'urls'          => [
                'base'    => $base_url,
                'filters' => $filter_urls,
                'groups'  => $my_groups_url,
            ],
            'can_reply'            => $can_reply,
            'can_propose'          => $can_propose,
            'can_accept'           => $can_accept,
            'has_pending_proposal' => $has_pending_proposal,
            'latest_proposal'      => $latest_proposal,
        ];

        $this->complaint_center_errors  = [];
        $this->complaint_center_success = [];

        return $this->complaint_center_state;
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array{
     *     context: array<string, mixed>,
     *     groups_owned: array<int, array<string, mixed>>,
     *     groups_member: array<int, array<string, mixed>>,
     *     group_counts: array<string, int>,
     *     pool_choices: array<mixed>,
     *     group_categories: array<string, string>,
     *     group_suggestions: array<int, array<string, mixed>>
     * }
     */
    private function build_group_hub_context(array $data): array
    {
        $groups_owned  = [];
        $groups_member = [];
        $group_counts  = [
            'owned'    => 0,
            'member'   => 0,
            'pending'  => 0,
            'approved' => 0,
            'rejected' => 0,
            'archived' => 0,
            'total'    => 0,
        ];

        $raw_owned  = [];
        $raw_member = [];

        if (isset($data['groups']) && is_array($data['groups'])) {
            $raw_owned = isset($data['groups']['owned']) && is_array($data['groups']['owned']) ? $data['groups']['owned'] : [];
            foreach ($raw_owned as $group) {
                if (!is_array($group)) {
                    continue;
                }

                $normalized     = $this->prepare_group_entry($group, true);
                $groups_owned[] = $normalized;

                $this->tally_group_counts($group_counts, $normalized);
            }

            $raw_member = isset($data['groups']['member']) && is_array($data['groups']['member']) ? $data['groups']['member'] : [];
            foreach ($raw_member as $group) {
                if (!is_array($group)) {
                    continue;
                }

                $normalized      = $this->prepare_group_entry($group, false);
                $groups_member[] = $normalized;

                $this->tally_group_counts($group_counts, $normalized);
            }
        }

        $group_counts['owned']  = count($groups_owned);
        $group_counts['member'] = count($groups_member);
        $group_counts['total']  = $group_counts['owned'] + $group_counts['member'];

        $group_ids = $this->collect_group_ids($raw_owned, $raw_member);
        $user_id   = get_current_user_id();

        if ($user_id) {
            $summary = GroupComplaints::get_summary_for_user($user_id, $group_ids);
            $this->group_complaint_summary = $this->decorate_group_complaint_summary($summary);
            $this->group_cancellation_events = GroupMembershipEvents::get_latest_for_user($user_id, $group_ids);
        } else {
            $this->group_complaint_summary = [];
            $this->group_cancellation_events = [];
        }

        (new PoolSeeder())->maybe_seed_defaults();

        $pool_choices = [];
        if (isset($data['group_pool_options']) && is_array($data['group_pool_options'])) {
            $pool_choices = $data['group_pool_options'];
        }

        $pool_featured = [];
        $pool_catalog  = [];

        $pool_catalog_query = Pools::query([
            'status'   => 'publish',
            'per_page' => 80,
            'order'    => 'ASC',
            'orderby'  => 'created_at',
        ]);

        if (isset($pool_catalog_query['items']) && is_array($pool_catalog_query['items'])) {
            $pool_catalog = $pool_catalog_query['items'];
        }

        $pool_featured_query = Pools::query([
            'status'   => 'publish',
            'featured' => 1,
            'per_page' => 6,
            'order'    => 'ASC',
            'orderby'  => 'created_at',
        ]);

        if (isset($pool_featured_query['items']) && is_array($pool_featured_query['items'])) {
            $pool_featured = $pool_featured_query['items'];
        }

        if (empty($pool_featured) && !empty($pool_catalog)) {
            $pool_featured = array_slice($pool_catalog, 0, 6);
        }

        $group_categories  = $this->get_group_categories();
        $group_suggestions = $this->get_group_suggestions();

        $cancel_errors = [
            'general' => isset($this->errors['group_cancel']) && is_array($this->errors['group_cancel'])
                ? $this->errors['group_cancel']
                : [],
            'groups'  => [],
        ];

        foreach ($this->errors as $error_key => $messages) {
            if (!is_array($messages)) {
                continue;
            }

            if (!str_starts_with($error_key, 'group_cancel_')) {
                continue;
            }

            $group_key = (int) substr($error_key, strlen('group_cancel_'));

            if ($group_key > 0) {
                $cancel_errors['groups'][$group_key] = $messages;
            } else {
                $cancel_errors['general'] = array_merge($cancel_errors['general'], $messages);
            }
        }

        if (!isset($cancel_errors['groups'])) {
            $cancel_errors['groups'] = [];
        }

        $all_groups_combined = array_merge($groups_owned, $groups_member);
        $per_page = (int) apply_filters('juntaplay/my_groups/per_page', 12);
        if ($per_page <= 0) {
            $per_page = 12;
        }

        $page_query = isset($_GET['jp_groups_page']) ? wp_unslash($_GET['jp_groups_page']) : '';
        $current_page = max(1, absint($page_query));
        $total_groups = count($all_groups_combined);
        $total_pages  = max(1, (int) ceil($total_groups / $per_page));

        if ($current_page > $total_pages) {
            $current_page = $total_pages;
        }

        $offset = ($current_page - 1) * $per_page;
        $visible_groups = array_slice($all_groups_combined, $offset, $per_page);

        $groups_owned_visible = [];
        $groups_member_visible = [];

        foreach ($visible_groups as $visible_group) {
            if (!is_array($visible_group)) {
                continue;
            }

            $role = isset($visible_group['membership_role']) ? (string) $visible_group['membership_role'] : 'member';

            if (in_array($role, ['owner', 'manager'], true)) {
                $groups_owned_visible[] = $visible_group;
            } else {
                $groups_member_visible[] = $visible_group;
            }
        }

        $pagination = [
            'page'     => $current_page,
            'pages'    => $total_pages,
            'total'    => $total_groups,
            'per_page' => $per_page,
        ];

        $group_context = [
            'groups_owned'      => $groups_owned_visible,
            'groups_member'     => $groups_member_visible,
            'groups_owned_full' => $groups_owned,
            'groups_member_full'=> $groups_member,
            'group_counts'      => $group_counts,
            'pool_choices'      => $pool_choices,
            'pool_catalog'      => $pool_catalog,
            'pool_featured'     => $pool_featured,
            'group_categories'  => $group_categories,
            'group_suggestions' => $group_suggestions,
            'form_errors'       => $this->errors['group_create'] ?? [],
            'form_values'       => $this->group_draft,
            'create_success'    => $this->group_create_success,
            'complaint_errors'  => $this->get_group_complaint_errors(),
            'complaint_drafts'  => $this->group_complaint_draft,
            'complaint_success' => $this->group_complaint_success,
            'complaint_reasons' => GroupComplaints::get_reasons(),
            'complaint_limits'  => $this->get_complaint_limits(),
            'complaint_summary' => $this->group_complaint_summary,
            'cancel_errors'     => $cancel_errors,
            'pagination'        => $pagination,
        ];

        return [
            'context'          => $group_context,
            'groups_owned'     => $groups_owned,
            'groups_member'    => $groups_member,
            'group_counts'     => $group_counts,
            'pool_choices'     => $pool_choices,
            'pool_catalog'     => $pool_catalog,
            'pool_featured'    => $pool_featured,
            'group_categories' => $group_categories,
            'group_suggestions'=> $group_suggestions,
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function get_sections(): array
    {
        $data = $this->get_profile_data();

        $group_hub = $this->build_group_hub_context($data);
        $groups_owned_visible  = $group_hub['groups_owned'];
        $groups_member_visible = $group_hub['groups_member'];
        $groups_owned_full     = $group_hub['groups_owned_full'] ?? $groups_owned_visible;
        $groups_member_full    = $group_hub['groups_member_full'] ?? $groups_member_visible;

        $network_connections = $this->build_network_connections($groups_owned_full, $groups_member_full);
        $network_total       = count($network_connections);
        $network_group_links = array_sum(array_map(static function (array $connection): int {
            return isset($connection['groups_count']) ? (int) $connection['groups_count'] : 0;
        }, $network_connections));

        $complaint_center = $this->get_complaint_center_context();
        $participant_counts = isset($complaint_center['counts']['participant']) && is_array($complaint_center['counts']['participant'])
            ? $complaint_center['counts']['participant']
            : [];
        $owner_counts = isset($complaint_center['counts']['owner']) && is_array($complaint_center['counts']['owner'])
            ? $complaint_center['counts']['owner']
            : [];

        $participant_open = (int) (($participant_counts[GroupComplaints::STATUS_OPEN] ?? 0)
            + ($participant_counts[GroupComplaints::STATUS_UNDER_REVIEW] ?? 0));
        $owner_open = (int) (($owner_counts[GroupComplaints::STATUS_OPEN] ?? 0)
            + ($owner_counts[GroupComplaints::STATUS_UNDER_REVIEW] ?? 0));
        $total_open = $participant_open + $owner_open;

        $participant_closed = (int) (($participant_counts[GroupComplaints::STATUS_RESOLVED] ?? 0)
            + ($participant_counts[GroupComplaints::STATUS_REJECTED] ?? 0));
        $owner_closed = (int) (($owner_counts[GroupComplaints::STATUS_RESOLVED] ?? 0)
            + ($owner_counts[GroupComplaints::STATUS_REJECTED] ?? 0));
        $total_closed = $participant_closed + $owner_closed;

        $complaint_summary = [
            [
                'label' => __('Pendências', 'juntaplay'),
                'value' => number_format_i18n($total_open),
                'tone'  => $total_open > 0 ? 'danger' : 'info',
            ],
            [
                'label' => __('Finalizadas', 'juntaplay'),
                'value' => number_format_i18n($total_closed),
                'tone'  => $total_closed > 0 ? 'positive' : 'info',
            ],
            [
                'label' => __('Como participante', 'juntaplay'),
                'value' => number_format_i18n($participant_open),
                'tone'  => $participant_open > 0 ? 'accent' : 'info',
            ],
            [
                'label' => __('Como administrador', 'juntaplay'),
                'value' => number_format_i18n($owner_open),
                'tone'  => $owner_open > 0 ? 'warning' : 'info',
            ],
        ];

        $avatar_label_source = isset($data['name']) ? wp_strip_all_tags((string) $data['name']) : '';
        if ($avatar_label_source === '' && isset($data['email'])) {
            $avatar_label_source = wp_strip_all_tags((string) $data['email']);
        }

        $avatar_initial = '';
        if ($avatar_label_source !== '') {
            if (function_exists('mb_substr')) {
                $avatar_initial = mb_strtoupper(mb_substr($avatar_label_source, 0, 1, 'UTF-8'), 'UTF-8');
            } else {
                $avatar_initial = strtoupper(substr($avatar_label_source, 0, 1));
            }
        }

        $sections = [
            'contact' => [
                'title'       => __('Informações de contato', 'juntaplay'),
                'description' => __('Mantenha seus contatos atualizados para que possamos falar com você rapidamente.', 'juntaplay'),
                'items'       => [
                    'avatar' => [
                        'label'       => __('Foto de perfil', 'juntaplay'),
                        'description' => __('Adicione, atualize ou remova a imagem que aparece no seu painel.', 'juntaplay'),
                        'type'        => 'custom',
                        'editable'    => false,
                        'template'    => 'profile-avatar.php',
                        'context'     => [
                            'avatar_url'        => $data['avatar_url'] ?? '',
                            'avatar_has_custom' => !empty($data['avatar_has_custom']),
                            'avatar_initial'    => $avatar_initial,
                            'avatar_label'      => $avatar_label_source,
                            'errors'            => $this->errors['avatar'] ?? [],
                        ],
                    ],
                    'name' => [
                        'label'       => __('Nome completo', 'juntaplay'),
                        'description' => __('Como aparecerá no painel e nos e-mails.', 'juntaplay'),
                        'value'       => $data['name'] ?? '',
                        'placeholder' => __('Seu nome completo', 'juntaplay'),
                        'type'        => 'text',
                    ],
                    'email' => [
                        'label'       => __('E-mail', 'juntaplay'),
                        'description' => __('Receba confirmações e novidades do JuntaPlay.', 'juntaplay'),
                        'value'       => $data['email'] ?? '',
                        'placeholder' => __('seu@email.com', 'juntaplay'),
                        'type'        => 'email',
                    ],
                    'phone' => [
                        'label'       => __('Telefone', 'juntaplay'),
                        'description' => __('Contato principal para suporte e reservas.', 'juntaplay'),
                        'value'       => $data['phone'] ?? '',
                        'placeholder' => __('(00) 90000-0000', 'juntaplay'),
                        'type'        => 'tel',
                    ],
                    'whatsapp' => [
                        'label'       => __('WhatsApp', 'juntaplay'),
                        'description' => __('Canal rápido para avisos e confirmações.', 'juntaplay'),
                        'value'       => $data['whatsapp'] ?? '',
                        'placeholder' => __('(00) 90000-0000', 'juntaplay'),
                        'type'        => 'tel',
                    ],
                    'account_delete' => [
                        'label'        => __('Excluir conta', 'juntaplay'),
                        'description'  => __('Remova permanentemente sua conta e todos os dados associados. Esta ação não pode ser desfeita.', 'juntaplay'),
                        'display_value' => __('Esta ação removerá permanentemente sua conta.', 'juntaplay'),
                        'type'         => 'action',
                        'submit_label' => __('Excluir minha conta', 'juntaplay'),
                        'confirmation' => __('Tem certeza? Esta ação é irreversível e seus dados não poderão ser recuperados.', 'juntaplay'),
                        'fields'       => [
                            [
                                'name'       => 'confirm_delete',
                                'type'       => 'checkbox',
                                'label'      => __('Confirmo que desejo excluir minha conta e compreendo que os dados não poderão ser recuperados.', 'juntaplay'),
                                'value'      => '1',
                                'attributes' => [
                                    'required' => 'required',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'fiscal' => [
                'title'       => __('Dados fiscais', 'juntaplay'),
                'description' => __('Utilizamos estes dados para emissão de notas, comprovantes e relatórios financeiros.', 'juntaplay'),
                'notice'      => __('Revise seus dados antes de participar de um grupo para evitar atrasos na validação de pagamento.', 'juntaplay'),
                'items'       => [
                    'tax_type' => [
                        'label'         => __('Cadastro', 'juntaplay'),
                        'description'   => __('Selecione se você atua como pessoa física ou jurídica.', 'juntaplay'),
                        'value'         => $data['tax_type'] ?? 'pf',
                        'display_value' => $this->format_tax_type((string) ($data['tax_type'] ?? 'pf')),
                        'type'          => 'select',
                        'options'       => [
                            'pf' => __('Pessoa física', 'juntaplay'),
                            'pj' => __('Pessoa jurídica', 'juntaplay'),
                        ],
                    ],
                    'tax_document' => [
                        'label'       => __('Documento fiscal', 'juntaplay'),
                        'description' => __('Informe seu CPF ou CNPJ para emissão de recibos.', 'juntaplay'),
                        'value'       => $data['tax_document'] ?? '',
                        'display_value' => $this->format_tax_document((string) ($data['tax_document'] ?? '')),
                        'placeholder' => __('Digite seu CPF ou CNPJ', 'juntaplay'),
                        'type'        => 'text',
                    ],
                    'tax_company' => [
                        'label'       => __('Razão social / Nome da empresa', 'juntaplay'),
                        'description' => __('Obrigatório para pessoas jurídicas.', 'juntaplay'),
                        'value'       => $data['tax_company'] ?? '',
                        'placeholder' => __('Informe a razão social', 'juntaplay'),
                        'type'        => 'text',
                    ],
                    'tax_state_registration' => [
                        'label'       => __('Inscrição estadual', 'juntaplay'),
                        'description' => __('Caso isento, informe “Isento”.', 'juntaplay'),
                        'value'       => $data['tax_state_registration'] ?? '',
                        'placeholder' => __('Número ou “Isento”', 'juntaplay'),
                        'type'        => 'text',
                    ],
                    'tax_postcode' => [
                        'label'         => __('CEP', 'juntaplay'),
                        'value'         => $data['tax_postcode'] ?? '',
                        'display_value' => $this->format_postcode((string) ($data['tax_postcode'] ?? '')),
                        'placeholder'   => __('00000-000', 'juntaplay'),
                        'type'          => 'text',
                        'autocomplete'  => 'postal-code',
                        'attributes'    => [
                            'data-jp-cep-field' => 'postcode',
                            'inputmode'         => 'numeric',
                            'maxlength'         => '9',
                        ],
                    ],
                    'tax_address' => [
                        'label'         => __('Endereço', 'juntaplay'),
                        'description'   => __('Rua, número e complemento utilizados para faturamento.', 'juntaplay'),
                        'value'         => $data['tax_address'] ?? '',
                        'placeholder'   => __('Rua Exemplo, 123 - Bairro', 'juntaplay'),
                        'type'          => 'text',
                        'autocomplete'  => 'address-line1',
                        'attributes'    => [
                            'data-jp-cep-target' => 'address',
                        ],
                    ],
                    'tax_city' => [
                        'label'        => __('Cidade', 'juntaplay'),
                        'value'        => $data['tax_city'] ?? '',
                        'placeholder'  => __('Sua cidade', 'juntaplay'),
                        'type'         => 'text',
                        'autocomplete' => 'address-level2',
                        'attributes'   => [
                            'data-jp-cep-target' => 'city',
                        ],
                    ],
                    'tax_state' => [
                        'label'         => __('Estado (UF)', 'juntaplay'),
                        'value'         => $data['tax_state'] ?? '',
                        'display_value' => strtoupper((string) ($data['tax_state'] ?? '')),
                        'placeholder'   => __('UF', 'juntaplay'),
                        'type'          => 'text',
                        'autocomplete'  => 'address-level1',
                        'attributes'    => [
                            'data-jp-cep-target' => 'state',
                            'maxlength'          => '2',
                        ],
                    ],
                    'tax_country' => [
                        'label'         => __('País', 'juntaplay'),
                        'description'   => __('País utilizado para faturamento e emissão de notas.', 'juntaplay'),
                        'value'         => $data['tax_country'] ?? '',
                        'display_value' => $this->format_country((string) ($data['tax_country'] ?? '')),
                        'placeholder'   => __('Brasil', 'juntaplay'),
                        'type'          => 'text',
                        'autocomplete'  => 'country-name',
                        'attributes'    => [
                            'data-jp-cep-target' => 'country',
                        ],
                    ],
                ],
            ],
            'network' => [
                'title'       => __('Minha rede', 'juntaplay'),
                'description' => __('Conheça participantes que compartilham grupos com você e acompanhe as conexões ativas.', 'juntaplay'),
                'summary'     => [
                    [
                        'label' => __('Conexões ativas', 'juntaplay'),
                        'value' => number_format_i18n($network_total),
                        'tone'  => $network_total > 0 ? 'accent' : 'info',
                    ],
                    [
                        'label' => __('Grupos em comum', 'juntaplay'),
                        'value' => number_format_i18n($network_group_links),
                        'tone'  => $network_group_links > 0 ? 'positive' : 'info',
                    ],
                ],
                'items'       => [
                    'network_connections' => [
                        'label'       => __('Conexões recentes', 'juntaplay'),
                        'description' => __('Visualize quem está nos mesmos grupos e envie convites com segurança.', 'juntaplay'),
                        'type'        => 'custom',
                        'editable'    => false,
                        'template'    => 'profile-network.php',
                        'context'     => [
                            'connections'      => $network_connections,
                            'groups_total'     => $network_group_links,
                            'connections_total'=> $network_total,
                        ],
                    ],
                ],
            ],
            'complaints' => [
                'title'       => __('Reclamações', 'juntaplay'),
                'description' => __('Acompanhe tickets, envie mensagens e aceite propostas de acordo sem sair do painel.', 'juntaplay'),
                'summary'     => $complaint_summary,
                'items'       => [
                    self::SECTION_COMPLAINTS => [
                        'label'    => __('Reclamações', 'juntaplay'),
                        'type'     => 'custom',
                        'editable' => false,
                        'template' => 'profile-complaints.php',
                        'context'  => $complaint_center,
                    ],
                ],
            ],
            'credits' => [
                'title'       => __('Créditos e saldo', 'juntaplay'),
                'description' => __('Gerencie sua carteira pré-paga, configure recargas automáticas e mantenha seus dados de saque em dia.', 'juntaplay'),
                'summary'     => [
                    [
                        'label' => __('Saldo disponível', 'juntaplay'),
                        'value' => $this->format_currency((float) ($data['credit_balance'] ?? 0.0)),
                        'tone'  => 'positive',
                        'hint'  => $this->combine_hints([
                            $this->format_credit_updated_at((string) ($data['credit_updated_at'] ?? '')),
                            $this->format_credit_last_recharge((string) ($data['credit_last_recharge'] ?? '')),
                        ]),
                    ],
                    [
                        'label' => __('Reservado em pedidos', 'juntaplay'),
                        'value' => $this->format_currency((float) ($data['credit_reserved'] ?? 0.0)),
                        'tone'  => 'warning',
                        'hint'  => __('Valores bloqueados aguardando confirmação de pagamento.', 'juntaplay'),
                    ],
                    [
                        'label' => __('Bônus disponível', 'juntaplay'),
                        'value' => $this->format_currency((float) ($data['credit_bonus'] ?? 0.0)),
                        'tone'  => 'accent',
                        'hint'  => $this->format_credit_bonus_hint((string) ($data['credit_bonus_expiry'] ?? '')),
                    ],
                    [
                        'label' => __('Saques em análise', 'juntaplay'),
                        'value' => $this->format_currency((float) ($data['credit_withdraw_pending'] ?? 0.0)),
                        'tone'  => ((float) ($data['credit_withdraw_pending'] ?? 0.0)) > 0 ? 'warning' : 'info',
                        'hint'  => __('Solicitações aguardando processamento financeiro.', 'juntaplay'),
                    ],
                ],
                'items'       => [
                    'credit_history' => [
                        'label'       => __('Carteira e extrato', 'juntaplay'),
                        'description' => __('Visualize movimentações, solicite retiradas e acompanhe pendências.', 'juntaplay'),
                        'type'        => 'custom',
                        'editable'    => false,
                        'template'    => 'profile-credit-history.php',
                        'context'     => $this->build_credit_history_context($data),
                    ],
                    'credit_auto' => [
                        'label'         => __('Recarga automática', 'juntaplay'),
                        'description'   => __('Adicione créditos automaticamente quando o saldo estiver baixo.', 'juntaplay'),
                        'display_value' => $this->format_credit_auto($data),
                        'fields'        => [
                            [
                                'name'    => 'credit_auto_status',
                                'label'   => __('Status', 'juntaplay'),
                                'type'    => 'select',
                                'value'   => (string) ($data['credit_auto_status'] ?? 'off'),
                                'options' => [
                                    'on'  => __('Ativada', 'juntaplay'),
                                    'off' => __('Desativada', 'juntaplay'),
                                ],
                            ],
                            [
                                'name'        => 'credit_auto_amount',
                                'label'       => __('Valor da recarga (R$)', 'juntaplay'),
                                'type'        => 'number',
                                'value'       => $this->format_decimal((float) ($data['credit_auto_amount'] ?? 0.0)),
                                'placeholder' => __('Ex.: 100,00', 'juntaplay'),
                                'attributes'  => [
                                    'step' => '0.01',
                                    'min'  => '0',
                                ],
                            ],
                            [
                                'name'        => 'credit_auto_threshold',
                                'label'       => __('Saldo mínimo para recarregar (R$)', 'juntaplay'),
                                'type'        => 'number',
                                'value'       => $this->format_decimal((float) ($data['credit_auto_threshold'] ?? 0.0)),
                                'placeholder' => __('Ex.: 50,00', 'juntaplay'),
                                'attributes'  => [
                                    'step' => '0.01',
                                    'min'  => '0',
                                ],
                                'help'        => __('Quando o saldo disponível ficar abaixo deste valor, uma nova recarga será sugerida.', 'juntaplay'),
                            ],
                        ],
                        'submit_label' => __('Salvar preferências', 'juntaplay'),
                    ],
                    'credit_payment_method' => [
                        'label'         => __('Forma de pagamento preferida', 'juntaplay'),
                        'description'   => __('Defina o meio favorito para adicionar créditos rapidamente.', 'juntaplay'),
                        'display_value' => $this->format_credit_payment_method((string) ($data['credit_payment_method'] ?? 'pix')),
                        'type'          => 'select',
                        'options'       => [
                            'pix'    => __('Pix (instantâneo)', 'juntaplay'),
                            'card'   => __('Cartão de crédito', 'juntaplay'),
                            'boleto' => __('Boleto bancário', 'juntaplay'),
                        ],
                        'value'        => (string) ($data['credit_payment_method'] ?? 'pix'),
                        'fields'       => [
                            [
                                'name'    => 'credit_payment_method',
                                'type'    => 'select',
                                'label'   => __('Forma de pagamento', 'juntaplay'),
                                'value'   => (string) ($data['credit_payment_method'] ?? 'pix'),
                                'options' => [
                                    'pix'    => __('Pix (instantâneo)', 'juntaplay'),
                                    'card'   => __('Cartão de crédito', 'juntaplay'),
                                    'boleto' => __('Boleto bancário', 'juntaplay'),
                                ],
                            ],
                        ],
                        'submit_label' => __('Salvar forma de pagamento', 'juntaplay'),
                    ],
                    'credit_pix_key' => [
                        'label'         => __('Chave Pix para resgates', 'juntaplay'),
                        'description'   => __('Use uma chave Pix para receber estornos e premiações instantaneamente.', 'juntaplay'),
                        'value'         => (string) ($data['credit_pix_key'] ?? ''),
                        'display_value' => $this->format_credit_pix((string) ($data['credit_pix_key'] ?? '')),
                        'placeholder'   => __('Seu CPF, CNPJ, e-mail ou chave aleatória', 'juntaplay'),
                        'fields'        => [
                            [
                                'name'        => 'credit_pix_key',
                                'type'        => 'text',
                                'value'       => (string) ($data['credit_pix_key'] ?? ''),
                                'placeholder' => __('Informe sua chave Pix', 'juntaplay'),
                                'help'        => __('Deixe em branco para remover a chave cadastrada.', 'juntaplay'),
                            ],
                        ],
                    ],
                    'credit_bank_account' => [
                        'label'         => __('Dados bancários para saques', 'juntaplay'),
                        'description'   => __('Informe a conta bancária para resgates manuais e transferências maiores.', 'juntaplay'),
                        'display_value' => $this->format_credit_bank($data),
                        'fields'        => [
                            [
                                'name'        => 'credit_bank_holder',
                                'label'       => __('Titular da conta', 'juntaplay'),
                                'type'        => 'text',
                                'value'       => (string) ($data['credit_bank_holder'] ?? ''),
                                'placeholder' => __('Nome completo como consta no banco', 'juntaplay'),
                            ],
                            [
                                'name'        => 'credit_bank_document',
                                'label'       => __('Documento do titular', 'juntaplay'),
                                'type'        => 'text',
                                'value'       => (string) ($data['credit_bank_document'] ?? ''),
                                'placeholder' => __('CPF ou CNPJ do titular', 'juntaplay'),
                            ],
                            [
                                'name'    => 'credit_bank_type',
                                'label'   => __('Tipo de titularidade', 'juntaplay'),
                                'type'    => 'select',
                                'value'   => (string) ($data['credit_bank_type'] ?? 'pf'),
                                'options' => [
                                    'pf' => __('Pessoa física', 'juntaplay'),
                                    'pj' => __('Pessoa jurídica', 'juntaplay'),
                                ],
                            ],
                            [
                                'name'        => 'credit_bank_name',
                                'label'       => __('Banco', 'juntaplay'),
                                'type'        => 'text',
                                'value'       => (string) ($data['credit_bank_name'] ?? ''),
                                'placeholder' => __('Ex.: Nubank, Banco do Brasil, Itaú…', 'juntaplay'),
                            ],
                            [
                                'name'        => 'credit_bank_agency',
                                'label'       => __('Agência', 'juntaplay'),
                                'type'        => 'text',
                                'value'       => (string) ($data['credit_bank_agency'] ?? ''),
                                'placeholder' => __('Com dígito, se houver', 'juntaplay'),
                            ],
                            [
                                'name'        => 'credit_bank_account',
                                'label'       => __('Conta', 'juntaplay'),
                                'type'        => 'text',
                                'value'       => (string) ($data['credit_bank_account'] ?? ''),
                                'placeholder' => __('Número da conta com dígito', 'juntaplay'),
                            ],
                            [
                                'name'    => 'credit_bank_account_type',
                                'label'   => __('Tipo de conta', 'juntaplay'),
                                'type'    => 'select',
                                'value'   => (string) ($data['credit_bank_account_type'] ?? 'checking'),
                                'options' => [
                                    'checking' => __('Conta corrente', 'juntaplay'),
                                    'savings'  => __('Conta poupança', 'juntaplay'),
                                ],
                            ],
                        ],
                        'submit_label' => __('Salvar dados bancários', 'juntaplay'),
                    ],
                ],
            ],
            'security' => [
                'title'       => __('Segurança da conta', 'juntaplay'),
                'description' => __('Refine a proteção do seu login, configure verificações extras e controle quais dispositivos estão conectados.', 'juntaplay'),
                'notice'      => __('Uma senha forte e a verificação em duas etapas mantêm suas cotas protegidas.', 'juntaplay'),
                'items'       => [
                    'password' => [
                        'label'         => __('Senha de acesso', 'juntaplay'),
                        'description'   => __('Use letras, números e símbolos para criar uma senha difícil de adivinhar.', 'juntaplay'),
                        'value'         => $data['password_changed_at'] ?? '',
                        'display_value' => $this->format_password_updated((string) ($data['password_changed_at'] ?? '')),
                        'type'          => 'password',
                        'fields'        => [
                            [
                                'name'        => 'password_current',
                                'label'       => __('Senha atual', 'juntaplay'),
                                'type'        => 'password',
                                'placeholder' => __('Digite sua senha atual', 'juntaplay'),
                                'autocomplete' => 'current-password',
                            ],
                            [
                                'name'        => 'password_new',
                                'label'       => __('Nova senha', 'juntaplay'),
                                'type'        => 'password',
                                'placeholder' => __('Crie uma nova senha', 'juntaplay'),
                                'autocomplete' => 'new-password',
                                'help'        => __('Mínimo de 8 caracteres com combinação de letras e números.', 'juntaplay'),
                            ],
                            [
                                'name'        => 'password_confirm',
                                'label'       => __('Confirmar nova senha', 'juntaplay'),
                                'type'        => 'password',
                                'placeholder' => __('Repita a nova senha', 'juntaplay'),
                                'autocomplete' => 'new-password',
                            ],
                        ],
                        'submit_label'  => __('Atualizar senha', 'juntaplay'),
                    ],
                    'two_factor' => [
                        'label'         => __('Verificação em duas etapas', 'juntaplay'),
                        'description'   => __('Solicite um código extra ao entrar para confirmar que é você.', 'juntaplay'),
                        'value'         => $data['two_factor_method'] ?? 'off',
                        'display_value' => $this->format_two_factor_method((string) ($data['two_factor_method'] ?? 'off')),
                        'type'          => 'select',
                        'options'       => [
                            'off'      => __('Desativada', 'juntaplay'),
                            'email'    => __('Código por e-mail', 'juntaplay'),
                            'whatsapp' => __('Código por WhatsApp', 'juntaplay'),
                        ],
                    ],
                    'login_alerts' => [
                        'label'         => __('Alertas de login', 'juntaplay'),
                        'description'   => __('Receba um aviso quando um novo dispositivo acessar sua conta.', 'juntaplay'),
                        'value'         => $data['login_alerts'] ?? 'yes',
                        'display_value' => $this->format_login_alerts((string) ($data['login_alerts'] ?? 'yes')),
                        'type'          => 'select',
                        'options'       => [
                            'yes' => __('Enviar alerta por e-mail', 'juntaplay'),
                            'no'  => __('Não enviar alertas', 'juntaplay'),
                        ],
                    ],
                    'sessions' => [
                        'label'         => __('Sessões ativas', 'juntaplay'),
                        'description'   => __('Encerre acessos em outros navegadores e mantenha apenas esta sessão conectada.', 'juntaplay'),
                        'value'         => (string) ($data['sessions_active'] ?? 1),
                        'display_value' => $this->format_sessions_count((int) ($data['sessions_active'] ?? 1)),
                        'type'          => 'action',
                        'submit_label'  => __('Encerrar outras sessões', 'juntaplay'),
                        'confirmation'  => __('Tem certeza de que deseja desconectar os outros dispositivos?', 'juntaplay'),
                    ],
                ],
            ],
        ];

        return apply_filters('juntaplay/profile/sections', $sections, $data);
    }

    public function send_withdraw_code(int $user_id): array
    {
        $user = get_userdata($user_id);

        if (!$user instanceof WP_User || !$user->exists()) {
            return ['error' => __('Não foi possível localizar sua conta.', 'juntaplay')];
        }

        $data   = $this->get_profile_data();
        $method = isset($data['two_factor_method']) ? (string) $data['two_factor_method'] : 'email';

        if (!in_array($method, ['email', 'whatsapp'], true)) {
            $method = 'email';
        }

        $minutes = (int) apply_filters('juntaplay/credits/withdraw_code_minutes', 10);
        if ($minutes <= 0) {
            $minutes = 10;
        }

        $expires = time() + ($minutes * 60);
        $code    = (string) wp_rand(100000, 999999);
        $hash    = wp_hash_password($code);

        update_user_meta($user_id, 'juntaplay_withdraw_code_hash', $hash);
        update_user_meta($user_id, 'juntaplay_withdraw_code_expires', $expires);
        update_user_meta($user_id, 'juntaplay_withdraw_code_attempts', 0);

        $destination = $this->resolve_two_factor_destination($method, $data, (string) $user->user_email);
        $site_name   = get_bloginfo('name');

        $subject = sprintf(__('Código de confirmação para retirada — %s', 'juntaplay'), $site_name);
        $blocks  = [
            [
                'type'    => 'paragraph',
                'content' => sprintf(__('Olá %s, utilize o código abaixo para confirmar sua retirada no JuntaPlay.', 'juntaplay'), $user->display_name ?: $user->user_login),
            ],
            [
                'type'    => 'code',
                'content' => $code,
            ],
            [
                'type'    => 'paragraph',
                'content' => sprintf(__('O código expira em %d minutos.', 'juntaplay'), $minutes),
            ],
            [
                'type'    => 'paragraph',
                'content' => __('Se você não solicitou, ignore esta mensagem.', 'juntaplay'),
            ],
        ];

        $sent = EmailHelper::send(
            (string) $user->user_email,
            $subject,
            $blocks,
            [
                'headline'  => __('Confirme sua retirada', 'juntaplay'),
                'preheader' => sprintf(__('Seu código expira em %d minutos.', 'juntaplay'), $minutes),
            ]
        );

        if (!$sent) {
            delete_user_meta($user_id, 'juntaplay_withdraw_code_hash');
            delete_user_meta($user_id, 'juntaplay_withdraw_code_expires');
            delete_user_meta($user_id, 'juntaplay_withdraw_code_attempts');

            return ['error' => __('Não foi possível enviar o código agora. Tente novamente em instantes.', 'juntaplay')];
        }

        $message = sprintf(__('Código enviado para %s. Ele expira em %d minutos.', 'juntaplay'), $destination !== '' ? $destination : $this->mask_email((string) $user->user_email), $minutes);

        if ($method === 'whatsapp' && $destination === '') {
            $message .= ' ' . __('Como medida temporária, o envio foi realizado para o e-mail cadastrado.', 'juntaplay');
        }

        return [
            'message'     => $message,
            'expires'     => gmdate('c', $expires),
            'destination' => $destination !== '' ? $destination : $this->mask_email((string) $user->user_email),
        ];
    }

    /**
     * @param array<string, mixed> $request
     * @return array<string, mixed>
     */
    public function handle_withdrawal_request(int $user_id, array $request): array
    {
        $user = get_userdata($user_id);

        if (!$user instanceof WP_User || !$user->exists()) {
            return ['error' => __('Não foi possível localizar sua conta.', 'juntaplay'), 'status' => 401];
        }

        $amount_raw = $request['amount'] ?? '';
        if (is_string($amount_raw)) {
            $amount = $this->parse_decimal($amount_raw);
        } else {
            $amount = is_numeric($amount_raw) ? (float) $amount_raw : 0.0;
        }

        if ($amount <= 0) {
            return ['error' => __('Informe um valor de saque válido.', 'juntaplay'), 'field' => 'amount', 'status' => 400];
        }

        $data    = $this->get_profile_data();
        $balance = (float) ($data['credit_balance'] ?? 0.0);

        if ($amount > $balance) {
            return ['error' => __('Você não possui saldo suficiente para essa retirada.', 'juntaplay'), 'field' => 'amount', 'status' => 400];
        }

        $method = isset($request['method']) ? sanitize_key((string) $request['method']) : 'pix';
        if (!in_array($method, ['pix', 'bank'], true)) {
            $method = 'pix';
        }

        if ($method === 'pix') {
            $pix_key = (string) get_user_meta($user_id, 'juntaplay_credit_pix_key', true);
            if ($pix_key === '') {
                return ['error' => __('Cadastre uma chave Pix antes de solicitar saques.', 'juntaplay'), 'field' => 'method', 'status' => 400];
            }
        } else {
            $holder = (string) get_user_meta($user_id, 'juntaplay_credit_bank_holder', true);
            $bank   = (string) get_user_meta($user_id, 'juntaplay_credit_bank_name', true);
            $account = (string) get_user_meta($user_id, 'juntaplay_credit_bank_account', true);

            if ($holder === '' || $bank === '' || $account === '') {
                return ['error' => __('Preencha seus dados bancários para transferências.', 'juntaplay'), 'field' => 'method', 'status' => 400];
            }
        }

        $code = isset($request['code']) ? trim((string) $request['code']) : '';
        $hash = (string) get_user_meta($user_id, 'juntaplay_withdraw_code_hash', true);
        $expires = (int) get_user_meta($user_id, 'juntaplay_withdraw_code_expires', true);
        $attempts = (int) get_user_meta($user_id, 'juntaplay_withdraw_code_attempts', true);

        if ($hash === '' || !$expires) {
            return ['error' => __('Solicite um código de confirmação antes de concluir o saque.', 'juntaplay'), 'field' => 'code', 'status' => 400];
        }

        if ($expires < time()) {
            delete_user_meta($user_id, 'juntaplay_withdraw_code_hash');
            delete_user_meta($user_id, 'juntaplay_withdraw_code_expires');
            delete_user_meta($user_id, 'juntaplay_withdraw_code_attempts');

            return ['error' => __('O código informado expirou. Peça um novo código para continuar.', 'juntaplay'), 'field' => 'code', 'status' => 400];
        }

        if ($code === '' || !wp_check_password($code, $hash)) {
            $attempts++;
            update_user_meta($user_id, 'juntaplay_withdraw_code_attempts', $attempts);

            if ($attempts >= 5) {
                delete_user_meta($user_id, 'juntaplay_withdraw_code_hash');
                delete_user_meta($user_id, 'juntaplay_withdraw_code_expires');
                delete_user_meta($user_id, 'juntaplay_withdraw_code_attempts');

                return ['error' => __('Limite de tentativas excedido. Solicite um novo código.', 'juntaplay'), 'field' => 'code', 'status' => 400];
            }

            return ['error' => __('Código inválido. Verifique e tente novamente.', 'juntaplay'), 'field' => 'code', 'status' => 400];
        }

        delete_user_meta($user_id, 'juntaplay_withdraw_code_hash');
        delete_user_meta($user_id, 'juntaplay_withdraw_code_expires');
        delete_user_meta($user_id, 'juntaplay_withdraw_code_attempts');

        $balance_after = max(0.0, $balance - $amount);
        update_user_meta($user_id, 'juntaplay_credit_balance', $this->store_decimal($balance_after));
        update_user_meta($user_id, 'juntaplay_credit_updated_at', current_time('mysql'));

        $destination = $this->build_withdraw_destination($user_id, $method);
        $reference   = sprintf('JPW-%s', strtoupper(substr(wp_generate_uuid4(), 0, 8)));

        $withdrawal_id = CreditWithdrawals::create([
            'user_id'    => $user_id,
            'amount'     => $amount,
            'method'     => $method,
            'status'     => CreditWithdrawals::STATUS_PENDING,
            'destination'=> $destination,
            'reference'  => $reference,
        ]);

        if (!$withdrawal_id) {
            update_user_meta($user_id, 'juntaplay_credit_balance', $this->store_decimal($balance));

            return ['error' => __('Não foi possível registrar sua solicitação. Tente novamente.', 'juntaplay'), 'status' => 500];
        }

        CreditTransactions::create([
            'user_id'       => $user_id,
            'type'          => CreditTransactions::TYPE_WITHDRAWAL,
            'status'        => CreditTransactions::STATUS_PENDING,
            'amount'        => -$amount,
            'balance_after' => $balance_after,
            'reference'     => $reference,
            'context'       => [
                'withdrawal_id' => $withdrawal_id,
                'method'        => $method,
            ],
        ]);

        $pending_total = CreditWithdrawals::get_pending_total($user_id);
        update_user_meta($user_id, 'juntaplay_credit_withdraw_pending', $this->store_decimal($pending_total));

        $this->invalidate_cache();

        do_action('juntaplay/credits/withdrawal_requested', $user_id, $withdrawal_id, [
            'amount'      => $amount,
            'method'      => $method,
            'reference'   => $reference,
            'destination' => $destination,
        ]);

        return [
            'message'       => __('Sua solicitação foi registrada. Avisaremos assim que for concluída.', 'juntaplay'),
            'withdrawal_id' => $withdrawal_id,
        ];
    }

    /**
     * @param mixed $amount_raw
     * @return array<string, mixed>
     */
    public function initiate_deposit(int $user_id, $amount_raw): array
    {
        if (!class_exists('WooCommerce') || !function_exists('WC')) {
            return ['error' => __('A recarga de créditos está indisponível no momento.', 'juntaplay')];
        }

        $user = get_userdata($user_id);

        if (!$user instanceof WP_User || !$user->exists()) {
            return ['error' => __('Não foi possível localizar sua conta.', 'juntaplay')];
        }

        if (is_string($amount_raw)) {
            $amount = $this->parse_decimal($amount_raw);
        } elseif (is_numeric($amount_raw)) {
            $amount = (float) $amount_raw;
        } else {
            $amount = 0.0;
        }

        $min = (float) apply_filters('juntaplay/credits/deposit_min', 25.0, $user_id);
        $max = (float) apply_filters('juntaplay/credits/deposit_max', 5000.0, $user_id);

        if ($amount <= 0 || $amount < $min) {
            return ['error' => sprintf(__('O valor mínimo para recarga é %s.', 'juntaplay'), $this->format_currency($min)), 'field' => 'amount'];
        }

        if ($max > 0 && $amount > $max) {
            return ['error' => sprintf(__('O valor máximo permitido para recarga é %s.', 'juntaplay'), $this->format_currency($max)), 'field' => 'amount'];
        }

        $product_id = WooCredits::get_product_id();

        if ($product_id <= 0) {
            return ['error' => __('Não foi possível preparar o produto de recarga.', 'juntaplay')];
        }

        if (!wc_get_checkout_url()) {
            return ['error' => __('Checkout indisponível no momento. Tente novamente em instantes.', 'juntaplay')];
        }

        $woocommerce = WC();

        if (!$woocommerce) {
            return ['error' => __('Não foi possível iniciar seu carrinho de compras.', 'juntaplay')];
        }

        if (!isset($woocommerce->cart) || !$woocommerce->cart) {
            wc_load_cart();
        }

        $cart = $woocommerce->cart;

        if (!$cart) {
            return ['error' => __('Não foi possível iniciar seu carrinho de compras.', 'juntaplay')];
        }

        foreach ($cart->get_cart() as $item_key => $item) {
            if (!empty($item['juntaplay_deposit'])) {
                $cart->remove_cart_item($item_key);
            }
        }

        $reference = sprintf('JPD-%s', strtoupper(substr(wp_generate_uuid4(), 0, 8)));

        $cart_item_data = [
            'juntaplay_deposit' => [
                'amount'    => $amount,
                'reference' => $reference,
                'display'   => $this->format_currency($amount),
            ],
        ];

        $cart_item_key = $cart->add_to_cart($product_id, 1, 0, [], $cart_item_data);

        if (!$cart_item_key) {
            return ['error' => __('Não foi possível adicionar a recarga ao carrinho.', 'juntaplay')];
        }

        if (method_exists($cart, 'calculate_totals')) {
            $cart->calculate_totals();
        }

        do_action('juntaplay/credits/deposit_initiated', $user_id, [
            'amount'    => $amount,
            'reference' => $reference,
            'product_id'=> $product_id,
        ]);

        return [
            'message'  => sprintf(__('Recarga de %s adicionada ao carrinho.', 'juntaplay'), $this->format_currency($amount)),
            'redirect' => wc_get_checkout_url(),
        ];
    }

    public function maybe_prepare_group_checkout(): void
    {
        if (!class_exists('WooCommerce') || !function_exists('WC') || !function_exists('is_checkout') || !is_checkout()) {
            return;
        }

        $slug_raw = isset($_GET['grupo']) ? wp_unslash((string) $_GET['grupo']) : '';
        $id_raw   = isset($_GET['group_id']) ? wp_unslash((string) $_GET['group_id']) : '';

        if ($slug_raw === '' && $id_raw === '') {
            return;
        }

        $slug     = $slug_raw !== '' ? sanitize_title($slug_raw) : '';
        $group_id = absint($id_raw);

        $group = null;

        if ($slug !== '') {
            $group = Groups::get_public_by_slug($slug);
        }

        if (!$group && $group_id > 0) {
            $group = Groups::get_public_detail($group_id);
        }

        if (!$group) {
            $this->redirect_group_checkout_failure(__('Não foi possível localizar o grupo selecionado.', 'juntaplay'));

            return;
        }

        $user_id = get_current_user_id();
        $membership_exists = $user_id > 0 && GroupMembers::user_has_membership((int) ($group['id'] ?? 0), $user_id);
        $relationship_confirmed = isset($_GET['jp_rel']) && (string) $_GET['jp_rel'] === '1';

        if (!$relationship_confirmed && !$membership_exists) {
            $pool_link = $this->build_group_pool_link((int) ($group['pool_id'] ?? 0), (string) ($group['pool_slug'] ?? ''));
            $relationship_url = $this->build_group_relationship_url((int) ($group['id'] ?? 0), (string) ($group['slug'] ?? ''), $pool_link);
            wp_safe_redirect($relationship_url);
            exit;
        }

        $price = $this->resolve_group_checkout_price($group);

        if ($price <= 0) {
            $this->redirect_group_checkout_failure(__('O grupo não possui um valor configurado para pagamento.', 'juntaplay'), $group);

            return;
        }

        $product_id = WooGroupsProduct::get_product_id();

        if ($product_id <= 0) {
            $this->redirect_group_checkout_failure(__('Não foi possível preparar o checkout deste grupo.', 'juntaplay'), $group);

            return;
        }

        $woocommerce = WC();

        if (!$woocommerce) {
            $this->redirect_group_checkout_failure(__('Não foi possível iniciar seu carrinho de compras.', 'juntaplay'), $group);

            return;
        }

        if (!isset($woocommerce->cart) || !$woocommerce->cart) {
            wc_load_cart();
        }

        $cart = $woocommerce->cart;

        if (!$cart) {
            $this->redirect_group_checkout_failure(__('Não foi possível iniciar seu carrinho de compras.', 'juntaplay'), $group);

            return;
        }

        foreach ($cart->get_cart() as $item_key => $cart_item) {
            if (!empty($cart_item['juntaplay_group'])) {
                $cart->remove_cart_item($item_key);
            }
        }

        $cart_item_data = [
            'juntaplay_group' => [
                'group_id'    => isset($group['id']) ? (int) $group['id'] : 0,
                'group_slug'  => isset($group['slug']) ? (string) $group['slug'] : '',
                'title'       => isset($group['title']) ? (string) $group['title'] : '',
                'price'       => $price,
                'price_label' => $this->format_currency($price),
            ],
        ];

        $cart_item_key = $cart->add_to_cart($product_id, 1, 0, [], $cart_item_data);

        if (!$cart_item_key) {
            $this->redirect_group_checkout_failure(__('Não foi possível adicionar o grupo ao checkout. Tente novamente em instantes.', 'juntaplay'), $group);

            return;
        }

        $cart_contents = $cart->get_cart();

        if (isset($cart_contents[$cart_item_key]['data']) && $cart_contents[$cart_item_key]['data'] instanceof \WC_Product) {
            $product = $cart_contents[$cart_item_key]['data'];
            $title   = isset($group['title']) ? (string) $group['title'] : '';

            if ($title !== '') {
                $product->set_name(sprintf(__('Participação no grupo %s', 'juntaplay'), $title));
            }
        }

        if (method_exists($cart, 'calculate_totals')) {
            $cart->calculate_totals();
        }

        if (!empty($group['title'])) {
            wc_add_notice(sprintf(__('Revise e finalize a assinatura do grupo %s.', 'juntaplay'), (string) $group['title']), 'success');
        } else {
            wc_add_notice(__('Revise e finalize sua assinatura de grupo.', 'juntaplay'), 'success');
        }

        $redirect = remove_query_arg(['grupo', 'group_id', 'jp_rel'], wc_get_checkout_url());
        wp_safe_redirect($redirect);
        exit;
    }

    private function redirect_group_checkout_failure(string $message, ?array $group = null): void
    {
        wc_add_notice($message, 'error');

        $redirect = remove_query_arg(['grupo', 'group_id', 'jp_rel'], wc_get_checkout_url());

        if ($group !== null) {
            $fallback = $this->build_group_pool_link(
                isset($group['pool_id']) ? (int) $group['pool_id'] : 0,
                isset($group['pool_slug']) ? (string) $group['pool_slug'] : ''
            );

            if ($fallback !== '') {
                $redirect = $fallback;
            }
        }

        wp_safe_redirect($redirect);
        exit;
    }

    /**
     * @param array<string, mixed> $group
     */
    private function resolve_group_checkout_price(array $group): float
    {
        $member_price = isset($group['member_price']) ? (float) $group['member_price'] : 0.0;
        if ($member_price > 0) {
            return $member_price;
        }

        $promo_price = isset($group['price_promotional']) ? (float) $group['price_promotional'] : 0.0;
        if ($promo_price > 0) {
            return $promo_price;
        }

        $regular_price = isset($group['price_regular']) ? (float) $group['price_regular'] : 0.0;
        if ($regular_price > 0) {
            return $regular_price;
        }

        return isset($group['effective_price']) ? (float) $group['effective_price'] : 0.0;
    }

    /**
     * @param array<string, mixed> $group
     * @return array<string, mixed>
     */
    private function prepare_group_entry(array $group, bool $is_owner): array
    {
        $status            = isset($group['status']) ? (string) $group['status'] : Groups::STATUS_PENDING;
        $membership_status = isset($group['membership_status']) ? (string) $group['membership_status'] : 'active';
        $role              = isset($group['membership_role']) ? (string) $group['membership_role'] : ($is_owner ? 'owner' : 'member');
        $service_name      = isset($group['service_name']) ? (string) $group['service_name'] : '';
        $service_url       = isset($group['service_url']) ? (string) $group['service_url'] : '';
        $rules             = isset($group['rules']) ? (string) $group['rules'] : '';
        $price_regular     = isset($group['price_regular']) ? (float) $group['price_regular'] : 0.0;
        $price_promotional = isset($group['price_promotional']) ? (float) $group['price_promotional'] : 0.0;
        $member_price      = isset($group['member_price']) ? (float) $group['member_price'] : 0.0;
        $slots_total       = isset($group['slots_total']) ? (int) $group['slots_total'] : 0;
        $slots_reserved    = isset($group['slots_reserved']) ? (int) $group['slots_reserved'] : 0;
        $support_channel   = isset($group['support_channel']) ? (string) $group['support_channel'] : '';
        $delivery_time     = isset($group['delivery_time']) ? (string) $group['delivery_time'] : '';
        $access_method     = isset($group['access_method']) ? (string) $group['access_method'] : '';
        $access_url        = isset($group['access_url']) ? (string) $group['access_url'] : '';
        $access_login      = isset($group['access_login']) ? (string) $group['access_login'] : '';
        $access_password   = isset($group['access_password']) ? (string) $group['access_password'] : '';
        $access_notes      = isset($group['access_notes']) ? (string) $group['access_notes'] : '';
        $access_observations = isset($group['access_observations']) ? (string) $group['access_observations'] : '';
        $category          = isset($group['category']) ? (string) $group['category'] : '';
        $instant_access    = !empty($group['instant_access']);
        $slots_available   = max(0, $slots_total - $slots_reserved);
        $group_id          = isset($group['id']) ? (int) $group['id'] : 0;
        $group_slug        = isset($group['slug']) ? (string) $group['slug'] : '';
        $relationship_type = isset($group['relationship_type']) ? (string) $group['relationship_type'] : '';
        $relationship_label = $this->format_group_relationship($relationship_type);

        $status_meta = $this->describe_group_status($status, $membership_status, $is_owner);

        $cover_url = '';
        $cover_alt = '';
        $cover_placeholder = false;

        $group['pool_link']       = $this->build_group_pool_link((int) ($group['pool_id'] ?? 0), (string) ($group['pool_slug'] ?? ''));
        $checkout_url             = $this->build_group_checkout_url($group_id, $group_slug, $group['pool_link']);
        $relationship_url         = $this->build_group_relationship_url($group_id, $group_slug, $group['pool_link']);
        $settings_url             = $this->build_group_settings_url($group_id);

        $group['status']            = $status;
        $group['membership_status'] = $membership_status;
        $group['membership_role']   = $role;
        $group['status_label']      = $status_meta['label'];
        $group['status_tone']       = $status_meta['tone'];
        $group['status_message']    = $status_meta['message'];
        $group['role_label']        = $this->format_group_role($role, $is_owner);
        $group['role_tone']         = $is_owner ? 'positive' : 'info';
        $group['created_human']     = $this->format_group_created_at((string) ($group['created_at'] ?? ''));
        $group['relationship_type'] = $relationship_type;
        $group['relationship_label'] = $relationship_label;
        $group['relationship_url']   = $relationship_url;
        $group['settings_url']       = $settings_url;

        $availability = $this->describe_group_availability(
            $status,
            $slots_total,
            $slots_available,
            $group['pool_link'],
            $is_owner,
            $membership_status,
            $checkout_url,
            $relationship_url,
            $settings_url
        );

        $group['slots_total_label']      = $availability['slots_total_label'];
        $group['slots_total_hint']       = $availability['slots_total_hint'];
        $group['slots_available_label']  = $availability['slots_available_label'];
        $group['availability_label']     = $availability['availability_label'];
        $group['availability_tone']      = $availability['availability_tone'];
        $group['cta_label']              = $availability['cta_label'];
        $group['cta_variant']            = $availability['cta_variant'];
        $group['cta_disabled']           = $availability['cta_disabled'];
        $group['cta_url']                = $availability['cta_url'];

        $group['members_count']     = isset($group['members_count']) ? (int) $group['members_count'] : 0;
        $group['review_note']       = isset($group['review_note']) ? (string) $group['review_note'] : '';
        $group['reviewed_human']    = $this->format_group_reviewed_at((string) ($group['reviewed_at'] ?? ''));
        $group['service_name']      = $service_name;
        $group['service_url']       = $service_url;
        $group['rules']             = $rules;
        $group['price_regular']     = $price_regular;
        $group['price_regular_display'] = $price_regular > 0 ? $this->format_currency($price_regular) : '';
        $group['price_promotional'] = $price_promotional > 0 ? $price_promotional : 0.0;
        $group['price_promotional_display'] = $price_promotional > 0 ? $this->format_currency($price_promotional) : '';
        $group['member_price']      = $member_price;
        $group['member_price_display'] = $member_price > 0 ? $this->format_currency($member_price) : '';
        $group['price_highlight'] = $group['member_price_display'] !== ''
            ? $group['member_price_display']
            : ($group['price_promotional_display'] !== ''
                ? $group['price_promotional_display']
                : $group['price_regular_display']);
        $group['slots_total']       = $slots_total;
        $group['slots_reserved']    = $slots_reserved;
        $group['slots_available']   = $slots_available;
        $group['support_channel']   = $support_channel;
        $support_meta               = $this->describe_support_channel($support_channel, $membership_status, $role, $is_owner);
        $group['support_channel_display'] = $support_meta['display'];
        $group['support_channel_type']    = $support_meta['type'];
        $group['support_channel_masked']  = $support_meta['masked'];
        $group['support_channel_notice']  = $support_meta['notice'];
        $group['support_channel_label']   = $support_meta['label'];
        $group['delivery_time']     = $delivery_time;
        $group['access_method']     = $access_method;
        $group['access_url']        = $is_owner ? $access_url : '';
        $group['access_login']      = $is_owner ? $access_login : '';
        $group['access_password']   = $is_owner ? $access_password : '';
        $group['access_notes']      = $is_owner ? $access_notes : '';
        $group['access_observations'] = $is_owner ? $access_observations : '';
        $group['category']          = $category;
        $group['category_label']    = $this->format_group_category_label($category);
        $group['instant_access']    = $instant_access;
        $group['instant_access_label'] = $instant_access
            ? __('Acesso instantâneo ativado', 'juntaplay')
            : __('Acesso instantâneo desativado', 'juntaplay');
        $group['slots_summary']     = sprintf(__('Total: %1$d vagas • Reservadas: %2$d • Disponíveis: %3$d', 'juntaplay'), $slots_total, $slots_reserved, $slots_available);
        $group['members_preview']   = $this->build_group_members_preview((int) ($group['id'] ?? 0), $group['members_count']);

        $reserved_for_owner = $slots_reserved > 0 ? $slots_reserved : 1;
        $enrollment_basis   = $member_price > 0
            ? $member_price
            : ($price_promotional > 0 ? $price_promotional : $price_regular);
        $enrollment_total   = $enrollment_basis > 0 ? $enrollment_basis * max(1, $reserved_for_owner) : 0.0;

        $group['enrollment_total']         = $enrollment_total;
        $group['enrollment_total_display'] = $enrollment_total > 0 ? $this->format_currency($enrollment_total) : '';
        $group['blocked_notice']           = $status === Groups::STATUS_PENDING
            ? __('Pagamentos ficam bloqueados até a aprovação do super administrador.', 'juntaplay')
            : '';

        $group['payment_methods'] = $this->get_payment_methods();
        $group['faq_items']       = $this->build_group_faq($group);
        $group['cover_url']       = $cover_url;
        $group['cover_alt']       = $cover_alt;
        $group['cover_placeholder'] = $cover_placeholder;

        $summary  = $group_id > 0 && isset($this->group_complaint_summary[$group_id])
            ? $this->group_complaint_summary[$group_id]
            : [];

        $group['complaints'] = [
            'open'   => (int) ($summary['open'] ?? 0),
            'total'  => (int) ($summary['total'] ?? 0),
            'latest' => isset($summary['latest']) && is_array($summary['latest']) ? $summary['latest'] : [],
        ];

        $cancel_meta = $group_id > 0 && isset($this->group_cancellation_events[$group_id])
            ? $this->group_cancellation_events[$group_id]
            : null;

        if (is_array($cancel_meta)) {
            $reason_text = '';
            if (isset($cancel_meta['metadata']) && is_array($cancel_meta['metadata'])) {
                $reason_text = (string) ($cancel_meta['metadata']['reason'] ?? '');
            }

            $group['cancellation'] = [
                'message'    => isset($cancel_meta['message']) ? (string) $cancel_meta['message'] : '',
                'reason'     => $reason_text,
                'created_at' => isset($cancel_meta['created_at']) ? (string) $cancel_meta['created_at'] : '',
            ];
        } else {
            $group['cancellation'] = null;
        }

        return $group;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function get_group_modal_context(int $group_id): ?array
    {
        $group_id = absint($group_id);

        if ($group_id <= 0) {
            return null;
        }

        $user_id = get_current_user_id();
        if ($user_id <= 0) {
            return null;
        }

        $groups = Groups::get_groups_for_user($user_id);
        $target = null;
        $is_owner = false;

        if (isset($groups['owned']) && is_array($groups['owned'])) {
            foreach ($groups['owned'] as $group) {
                if (!is_array($group)) {
                    continue;
                }

                if ((int) ($group['id'] ?? 0) === $group_id) {
                    $target = $group;
                    $is_owner = true;
                    break;
                }
            }
        }

        if (!$target && isset($groups['member']) && is_array($groups['member'])) {
            foreach ($groups['member'] as $group) {
                if (!is_array($group)) {
                    continue;
                }

                if ((int) ($group['id'] ?? 0) === $group_id) {
                    $target = $group;
                    $is_owner = false;
                    break;
                }
            }
        }

        if (!$target) {
            return null;
        }

        $normalized = $this->prepare_group_entry($target, $is_owner);

        return [
            'group'      => $normalized,
            'is_owner'   => $is_owner,
            'categories' => $this->get_group_categories(),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function get_public_group_modal_context(int $group_id): ?array
    {
        $group = Groups::get_public_detail($group_id);

        if (!$group) {
            return null;
        }

        $group['membership_role']   = 'guest';
        $group['membership_status'] = 'guest';

        $normalized = $this->prepare_group_entry($group, false);

        return [
            'group'      => $normalized,
            'is_owner'   => false,
            'categories' => $this->get_group_categories(),
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $groups_owned
     * @param array<int, array<string, mixed>> $groups_member
     *
     * @return array<int, array<string, mixed>>
     */
    private function build_network_connections(array $groups_owned, array $groups_member): array
    {
        $connections     = [];
        $current_user_id = get_current_user_id();
        $all_groups      = array_merge($groups_owned, $groups_member);

        foreach ($all_groups as $group) {
            if (!is_array($group)) {
                continue;
            }

            $group_id = isset($group['id']) ? (int) $group['id'] : 0;
            if ($group_id <= 0) {
                continue;
            }

            $group_title = (string) ($group['title'] ?? $group['service_name'] ?? __('Grupo', 'juntaplay'));
            $group_link  = (string) ($group['pool_link'] ?? '');
            $group_status = (string) ($group['status_label'] ?? '');

            $members = GroupMembers::get_details($group_id, 40, 'active');

            foreach ($members as $member) {
                $member_id = isset($member['user_id']) ? (int) $member['user_id'] : 0;
                if ($member_id <= 0 || $member_id === $current_user_id) {
                    continue;
                }

                $name = trim((string) ($member['name'] ?? ''));
                if ($name === '') {
                    $name = __('Participante', 'juntaplay');
                }

                if (!isset($connections[$member_id])) {
                    $avatar_url = get_avatar_url($member_id, ['size' => 96]);

                    $connections[$member_id] = [
                        'user_id'      => $member_id,
                        'name'         => $name,
                        'avatar'       => is_string($avatar_url) ? $avatar_url : '',
                        'initials'     => $this->profile_initials($name),
                        'groups'       => [],
                    ];
                }

                $role      = isset($member['role']) ? (string) $member['role'] : 'member';
                $is_owner  = $role === 'owner';
                $role_label = $this->format_group_role($role, $is_owner);

                $connections[$member_id]['groups'][] = [
                    'id'         => $group_id,
                    'title'      => $group_title,
                    'link'       => $group_link,
                    'role'       => $role_label,
                    'status'     => $group_status,
                ];
            }
        }

        if (!$connections) {
            return [];
        }

        foreach ($connections as &$connection) {
            $groups = $connection['groups'];
            usort($groups, static function (array $a, array $b): int {
                return strcasecmp((string) ($a['title'] ?? ''), (string) ($b['title'] ?? ''));
            });

            $connection['groups']        = $groups;
            $connection['groups_count']  = count($groups);
            $connection['groups_preview'] = array_map(static function (array $group): string {
                return (string) ($group['title'] ?? '');
            }, array_slice($groups, 0, 3));
            $connection['groups_more']   = max(0, $connection['groups_count'] - count($connection['groups_preview']));
        }
        unset($connection);

        uasort($connections, static function (array $a, array $b): int {
            return strcasecmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? ''));
        });

        return array_values($connections);
    }

    /**
     * @return array<string, mixed>
     */
    private function describe_group_availability(
        string $status,
        int $slots_total,
        int $slots_available,
        string $pool_link,
        bool $is_owner,
        string $membership_status,
        string $checkout_url,
        string $relationship_url,
        string $settings_url
    ): array {
        $available = max(0, $slots_available);
        $slots_total_label = $slots_total > 0
            ? number_format_i18n($slots_total)
            : __('Não informado', 'juntaplay');
        $slots_total_hint = $slots_total > 0
            ? ''
            : __('Defina a quantidade de vagas', 'juntaplay');
        $availability_label = '';
        $availability_tone  = 'info';
        $cta_label          = '';
        $cta_variant        = 'ghost';
        $cta_disabled       = false;
        $cta_url            = $pool_link;

        if ($status === Groups::STATUS_APPROVED) {
            if ($membership_status === 'guest') {
                if ($available <= 0) {
                    $availability_label = __('Sem vagas no momento', 'juntaplay');
                    $availability_tone  = 'muted';
                    $cta_label          = __('Aguardando vagas', 'juntaplay');
                    $cta_variant        = 'ghost';
                    $cta_disabled       = true;
                } else {
                    $availability_label = $available === 1
                        ? __('1 vaga disponível', 'juntaplay')
                        : sprintf(
                            _n('%d vaga disponível', '%d vagas disponíveis', $available, 'juntaplay'),
                            $available
                        );
                    $availability_tone = $available <= 3 ? 'warning' : 'positive';
                    $cta_label         = __('Assinar', 'juntaplay');
                    $cta_variant       = 'primary';
                    $cta_url           = $relationship_url !== '' ? $relationship_url : ($checkout_url !== '' ? $checkout_url : $pool_link);
                    $cta_disabled      = $cta_url === '';
                }
            } else {
                if ($available === 0) {
                    $availability_label = __('Sem vagas no momento', 'juntaplay');
                    $availability_tone  = 'muted';
                } elseif ($available === 1) {
                    $availability_label = __('1 última vaga', 'juntaplay');
                    $availability_tone  = 'warning';
                } else {
                    $availability_label = sprintf(
                        _n('%d vaga disponível', '%d vagas disponíveis', $available, 'juntaplay'),
                        $available
                    );
                    $availability_tone = 'positive';
                }

                $cta_label    = __('Você é assinante', 'juntaplay');
                $cta_variant  = 'primary';
                $cta_url      = $settings_url !== '' ? $settings_url : $pool_link;
                $cta_disabled = $cta_url === '';
            }
        } elseif ($status === Groups::STATUS_PENDING) {
            $availability_label = __('Em Análise', 'juntaplay');
            $availability_tone  = 'warning';
            $cta_label          = '';
            $cta_variant        = 'ghost';
            $cta_disabled       = true;
            $cta_url            = '';
        } elseif ($status === Groups::STATUS_REJECTED) {
            $availability_label = __('Reveja o envio do grupo', 'juntaplay');
            $availability_tone  = 'danger';
            $cta_label          = $is_owner ? __('Ajustar cadastro', 'juntaplay') : __('Indisponível', 'juntaplay');
            $cta_variant        = $is_owner ? 'ghost' : 'ghost';
            $cta_disabled       = !$is_owner;
            if (!$is_owner) {
                $cta_url = '';
            }
        } elseif ($status === Groups::STATUS_ARCHIVED) {
            $availability_label = __('Arquivado', 'juntaplay');
            $availability_tone  = 'muted';
            $cta_label          = __('Arquivado', 'juntaplay');
            $cta_variant        = 'ghost';
            $cta_disabled       = true;
            $cta_url            = '';
        }

        $slots_available_label = $available > 0
            ? sprintf(_n('%d vaga disponível', '%d vagas disponíveis', $available, 'juntaplay'), $available)
            : __('Nenhuma vaga disponível', 'juntaplay');

        if ($cta_label === '') {
            $cta_disabled = true;
        }

        return [
            'slots_total_label'     => $slots_total_label,
            'slots_total_hint'      => $slots_total_hint,
            'slots_available_label' => $slots_available_label,
            'availability_label'    => $availability_label,
            'availability_tone'     => $availability_tone,
            'cta_label'             => $cta_label,
            'cta_variant'           => $cta_variant,
            'cta_disabled'          => $cta_disabled,
            'cta_url'               => $cta_url,
        ];
    }

    /**
     * @return array{display: string, type: string, masked: bool, notice: string, label: string}
     */
    private function describe_support_channel(string $channel, string $membership_status, string $role, bool $is_owner): array
    {
        $channel = trim($channel);
        $normalized = strtolower($channel);
        $type = 'generic';

        if ($normalized !== '') {
            if (strpos($normalized, 'whats') !== false) {
                $type = 'whatsapp';
            } elseif (strpos($normalized, '@') !== false) {
                $type = 'email';
            } elseif (strpos($normalized, 'telegram') !== false) {
                $type = 'telegram';
            } elseif (strpos($normalized, 'discord') !== false) {
                $type = 'discord';
            }
        }

        $has_access = $is_owner || $role === 'owner' || $role === 'manager' || $membership_status !== 'guest';
        $masked = false;
        $display = $channel;
        $notice = '';

        if ($channel !== '' && !$has_access) {
            $masked = true;
            $display = '**********';
            $notice = __('Disponível somente para membros confirmados.', 'juntaplay');
        }

        return [
            'display' => $channel === '' ? '' : $display,
            'type'    => $type,
            'masked'  => $masked,
            'notice'  => $notice,
            'label'   => __('Suporte a Membros', 'juntaplay'),
        ];
    }

    /**
     * @param array<int, mixed> $owned
     * @param array<int, mixed> $member
     * @return int[]
     */
    private function collect_group_ids(array $owned, array $member): array
    {
        $ids = [];

        foreach ([$owned, $member] as $collection) {
            foreach ($collection as $group) {
                if (!is_array($group)) {
                    continue;
                }

                $id = isset($group['id']) ? (int) $group['id'] : 0;

                if ($id > 0) {
                    $ids[] = $id;
                }
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * @param array<int, array<string, mixed>> $summary
     * @return array<int, array<string, mixed>>
     */
    private function decorate_group_complaint_summary(array $summary): array
    {
        foreach ($summary as $group_id => $data) {
            if (!is_array($data)) {
                continue;
            }

            $latest = isset($data['latest']) && is_array($data['latest']) ? $data['latest'] : null;

            if (!$latest) {
                continue;
            }

            $status_meta = GroupComplaints::describe_status((string) ($latest['status'] ?? GroupComplaints::STATUS_OPEN));

            $latest['status_label']   = $status_meta['label'];
            $latest['status_tone']    = $status_meta['tone'];
            $latest['status_message'] = $status_meta['message'];
            $latest['reason_label']   = GroupComplaints::get_reason_label((string) ($latest['reason'] ?? 'other'));
            $latest['created_human']  = $this->format_group_created_at((string) ($latest['created_at'] ?? ''));
            $latest['summary']        = $this->format_complaint_summary_line($latest);

            $summary[$group_id]['latest'] = $latest;
        }

        return $summary;
    }

    /**
     * @param array<string, mixed> $latest
     */
    private function format_complaint_summary_line(array $latest): string
    {
        $parts = [];

        $reason = isset($latest['reason_label']) ? (string) $latest['reason_label'] : '';
        if ($reason !== '') {
            $parts[] = $reason;
        }

        $created = isset($latest['created_human']) ? (string) $latest['created_human'] : '';
        if ($created !== '') {
            $parts[] = $created;
        }

        $order = isset($latest['order_id']) ? (int) $latest['order_id'] : 0;
        if ($order > 0) {
            $parts[] = sprintf(__('Pedido #%d', 'juntaplay'), $order);
        }

        return implode(' • ', array_filter($parts));
    }

    private function count_open_complaints(): int
    {
        $total = 0;

        foreach ($this->group_complaint_summary as $summary) {
            if (!is_array($summary)) {
                continue;
            }

            $total += (int) ($summary['open'] ?? 0);
        }

        return $total;
    }

    private function count_total_complaints(): int
    {
        $total = 0;

        foreach ($this->group_complaint_summary as $summary) {
            if (!is_array($summary)) {
                continue;
            }

            $total += (int) ($summary['total'] ?? 0);
        }

        return $total;
    }

    private function format_complaint_hint(): string
    {
        $total = $this->count_total_complaints();

        if ($total <= 0) {
            return __('Nenhuma reclamação', 'juntaplay');
        }

        $open = $this->count_open_complaints();

        if ($open > 0) {
            return sprintf(_n('Você tem %d reclamação em análise.', 'Você tem %d reclamações em análise.', $open, 'juntaplay'), $open);
        }

        return sprintf(_n('Você já resolveu %d reclamação.', 'Você já resolveu %d reclamações.', $total, 'juntaplay'), $total);
    }

    /**
     * @param array<string, int> $status_counts
     * @return array<string, int>
     */
    private function summarize_complaint_counts(array $status_counts): array
    {
        $open_statuses   = [GroupComplaints::STATUS_OPEN, GroupComplaints::STATUS_UNDER_REVIEW];
        $closed_statuses = [GroupComplaints::STATUS_RESOLVED, GroupComplaints::STATUS_REJECTED];

        $open   = 0;
        $closed = 0;

        foreach ($status_counts as $status => $total) {
            $status = sanitize_key((string) $status);
            $total  = (int) $total;

            if (in_array($status, $open_statuses, true)) {
                $open += $total;
            } elseif (in_array($status, $closed_statuses, true)) {
                $closed += $total;
            }
        }

        return [
            'open'   => $open,
            'closed' => $closed,
            'all'    => $open + $closed,
        ];
    }

    /**
     * @param int[] $attachments
     * @return array<int, array<string, string|int>>
     */
    private function build_complaint_attachments(array $attachments): array
    {
        $files = [];

        foreach ($attachments as $attachment_id) {
            $attachment_id = absint($attachment_id);
            if ($attachment_id <= 0) {
                continue;
            }

            $url = wp_get_attachment_url($attachment_id);
            if (!$url) {
                continue;
            }

            $files[] = [
                'id'    => $attachment_id,
                'url'   => $url,
                'title' => get_the_title($attachment_id),
                'type'  => get_post_mime_type($attachment_id) ?: '',
            ];
        }

        return $files;
    }

    /**
     * @param array<string, mixed> $complaint
     * @return array<string, mixed>
     */
    private function decorate_complaint_list_item(array $complaint, string $role): array
    {
        $summary = $this->format_complaint_summary_line([
            'reason'     => $complaint['reason'] ?? 'other',
            'status'     => $complaint['status'] ?? GroupComplaints::STATUS_OPEN,
            'order_id'   => $complaint['order_id'] ?? 0,
            'created_at' => $complaint['created_at'] ?? '',
        ]);

        return [
            'id'           => (int) ($complaint['id'] ?? 0),
            'ticket'       => (string) ($complaint['ticket'] ?? ''),
            'group_title'  => (string) ($complaint['group_title'] ?? ''),
            'status_label' => (string) ($complaint['status_label'] ?? ''),
            'status_tone'  => (string) ($complaint['status_tone'] ?? 'info'),
            'reason_label' => (string) ($complaint['reason_label'] ?? ''),
            'created_at'   => (string) ($complaint['created_at'] ?? ''),
            'summary'      => $summary,
            'role'         => $role,
        ];
    }

    private function parse_ticket_param(string $ticket): int
    {
        $ticket = trim($ticket);

        if ($ticket === '') {
            return 0;
        }

        if ($ticket[0] === '#') {
            $numeric = absint(substr($ticket, 1));
            $base    = absint(substr(GroupComplaints::get_ticket_number(1), 1)) - 1;

            if ($numeric > $base) {
                return $numeric - $base;
            }

            return absint($numeric);
        }

        return absint($ticket);
    }

    /**
     * @return array<string, string[]>
     */
    private function get_group_complaint_errors(): array
    {
        $errors = [];

        foreach ($this->errors as $key => $messages) {
            if (strpos((string) $key, 'group_complaint_') !== 0 || !is_array($messages)) {
                continue;
            }

            $errors[(string) $key] = $messages;
        }

        return $errors;
    }

    private function add_complaint_error(int $complaint_id, string $message): void
    {
        $complaint_id = absint($complaint_id);

        if (!isset($this->complaint_center_errors[$complaint_id])) {
            $this->complaint_center_errors[$complaint_id] = [];
        }

        $this->complaint_center_errors[$complaint_id][] = $message;
    }

    private function add_complaint_success(int $complaint_id, string $message): void
    {
        $complaint_id = absint($complaint_id);

        if (!isset($this->complaint_center_success[$complaint_id])) {
            $this->complaint_center_success[$complaint_id] = [];
        }

        $this->complaint_center_success[$complaint_id][] = $message;
    }

    /**
     * @return array<string, int>
     */
    private function get_complaint_limits(): array
    {
        $max_files = (int) apply_filters('juntaplay/groups/complaints/max_files', 3);
        $max_size  = (int) apply_filters('juntaplay/groups/complaints/max_file_size', 5 * 1024 * 1024);

        if ($max_files <= 0) {
            $max_files = 3;
        }

        if ($max_size <= 0) {
            $max_size = 5 * 1024 * 1024;
        }

        return [
            'max_files' => $max_files,
            'max_size'  => $max_size,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function build_group_members_preview(int $group_id, int $total_members): array
    {
        $preview = [
            'names'     => [],
            'remaining' => 0,
        ];

        if ($group_id <= 0) {
            return $preview;
        }

        $members = GroupMembers::get_details($group_id, 5, 'active');

        foreach ($members as $member) {
            $name = trim((string) ($member['name'] ?? ''));
            if ($name === '') {
                $name = __('Participante', 'juntaplay');
            }

            if (($member['role'] ?? '') === 'owner') {
                $name = sprintf(__('Administrador: %s', 'juntaplay'), $name);
            }

            $preview['names'][] = $name;
        }

        $count_preview = count($preview['names']);
        $preview['remaining'] = max(0, $total_members - $count_preview);

        return $preview;
    }

    private function profile_initials(string $name): string
    {
        $name = trim($name);
        if ($name === '') {
            return 'J';
        }

        $parts = preg_split('/\s+/u', $name) ?: [$name];
        $initials = '';

        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }

            if (function_exists('mb_substr')) {
                $initials .= mb_strtoupper(mb_substr($part, 0, 1));
            } else {
                $initials .= strtoupper(substr($part, 0, 1));
            }

            if ((function_exists('mb_strlen') ? mb_strlen($initials) : strlen($initials)) >= 2) {
                break;
            }
        }

        return $initials !== '' ? $initials : 'J';
    }

    /**
     * @return array<string, string>
     */
    /**
     * @return array<int, array<string, string>>
     */
    private function build_group_faq(array $group): array
    {
        $faq    = [];
        $access = (string) ($group['delivery_time'] ?? '');
        $instant = !empty($group['instant_access']);
        $members_total = (int) ($group['slots_total'] ?? 0);
        $payment_methods = $this->get_payment_methods();
        $payment_list = implode(', ', array_map('wp_strip_all_tags', $payment_methods));
        $limits = $this->get_complaint_limits();
        $max_files = (int) ($limits['max_files'] ?? 3);
        $max_size  = (int) ($limits['max_size'] ?? (5 * 1024 * 1024));
        $max_size_mb = number_format_i18n(max(1, $max_size / 1048576), 1);

        if ($instant) {
            $access_text = __('Assim que seu pagamento for confirmado o acesso é liberado automaticamente.', 'juntaplay');
        } elseif ($access !== '') {
            $access_text = sprintf(__('O administrador envia os dados em até %s após a confirmação do pagamento.', 'juntaplay'), $access);
        } else {
            $access_text = __('O administrador envia o acesso logo após o grupo ser aprovado.', 'juntaplay');
        }

        $faq[] = [
            'question' => __('Quando terei acesso ao serviço?', 'juntaplay'),
            'answer'   => $access_text,
        ];

        $faq[] = [
            'question' => __('Quais as formas de pagamento aceitas?', 'juntaplay'),
            'answer'   => $payment_list !== ''
                ? sprintf(__('Utilizamos os meios de pagamento habilitados no WooCommerce: %s.', 'juntaplay'), $payment_list)
                : __('Os pagamentos são processados pelos métodos ativos do WooCommerce da loja.', 'juntaplay'),
        ];

        $faq[] = [
            'question' => __('O que é caução?', 'juntaplay'),
            'answer'   => __('É o valor que fica bloqueado na sua carteira até que o administrador confirme o envio do serviço ou o grupo seja aprovado. Caso algo dê errado, devolvemos automaticamente.', 'juntaplay'),
        ];

        if ($members_total > 0) {
            $faq[] = [
                'question' => __('Com quem posso dividir uma assinatura?', 'juntaplay'),
                'answer'   => sprintf(__('Este grupo comporta até %d participantes. Convide amigos ou familiares para preencher as vagas disponíveis.', 'juntaplay'), $members_total),
            ];
        }

        $faq[] = [
            'question' => __('Como faço uma reclamação?', 'juntaplay'),
            'answer'   => __('Abra a aba “Abrir reclamação”, descreva o ocorrido e envie evidências. O administrador e a equipe JuntaPlay são notificados automaticamente.', 'juntaplay'),
        ];

        $faq[] = [
            'question' => __('Posso anexar comprovantes?', 'juntaplay'),
            'answer'   => sprintf(
                __('Sim, você pode anexar até %1$d arquivos (imagens ou PDF) de até %2$s MB cada para agilizar a análise.', 'juntaplay'),
                max(1, $max_files),
                $max_size_mb
            ),
        ];

        $faq[] = [
            'question' => __('O que acontece depois que envio?', 'juntaplay'),
            'answer'   => __('Você recebe um protocolo por e-mail e acompanhamos o caso até a solução. Valores envolvidos podem ficar bloqueados até a conclusão da análise.', 'juntaplay'),
        ];

        return $faq;
    }

    /**
     * @return string[]
     */
    private function get_payment_methods(): array
    {
        static $cached = null;

        if ($cached !== null) {
            return $cached;
        }

        $labels = [];

        if (function_exists('WC')) {
            $wc = WC();
            $gateways = null;

            if ($wc && isset($wc->payment_gateways) && method_exists($wc->payment_gateways, 'get_available_payment_gateways')) {
                $gateways = $wc->payment_gateways->get_available_payment_gateways();
            }

            if (!$gateways && class_exists('\\WC_Payment_Gateways')) {
                $gateways = \WC_Payment_Gateways::instance()->get_available_payment_gateways();
            }

            if (is_array($gateways)) {
                foreach ($gateways as $gateway) {
                    if (!$gateway) {
                        continue;
                    }

                    $title = '';
                    if (is_object($gateway) && method_exists($gateway, 'get_title')) {
                        $title = (string) $gateway->get_title();
                    } elseif (is_array($gateway) && isset($gateway['title'])) {
                        $title = (string) $gateway['title'];
                    }

                    $title = wp_strip_all_tags($title);

                    if ($title !== '') {
                        $labels[] = $title;
                    }
                }
            }
        }

        if (!$labels) {
            $labels = [
                __('Pix', 'juntaplay'),
                __('Cartão de crédito', 'juntaplay'),
                __('Boleto bancário', 'juntaplay'),
            ];
        }

        $cached = array_values(array_unique(array_filter($labels)));

        return $cached;
    }

    /**
     * @return array<string, string>
     */
    private function get_group_categories(): array
    {
        return Groups::get_category_labels();
    }

    private function format_group_category_label(string $category): string
    {
        $categories = $this->get_group_categories();

        if ($category === '') {
            return '';
        }

        if (isset($categories[$category])) {
            return $categories[$category];
        }

        return ucwords(str_replace(['-', '_'], ' ', $category));
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function get_group_suggestions(): array
    {
        $suggestions = [
            [
                'title'       => 'YouTube Premium',
                'price'       => $this->format_currency(22.9),
                'amount'      => '22.90',
                'category'    => 'video',
                'description' => __('Plano família com 6 perfis para dividir música e vídeos sem anúncios.', 'juntaplay'),
            ],
            [
                'title'       => 'Mubi Cinemateca',
                'price'       => $this->format_currency(19.9),
                'amount'      => '19.90',
                'category'    => 'video',
                'description' => __('Seleção de filmes independentes e clássicos restaurados toda semana.', 'juntaplay'),
            ],
            [
                'title'       => 'NBA League Pass',
                'price'       => $this->format_currency(119.9),
                'amount'      => '119.90',
                'category'    => 'games',
                'description' => __('Temporada completa de jogos ao vivo com múltiplos dispositivos.', 'juntaplay'),
            ],
            [
                'title'       => 'PlayPlus Família',
                'price'       => $this->format_currency(21.9),
                'amount'      => '21.90',
                'category'    => 'video',
                'description' => __('Conteúdos exclusivos da Record TV com acesso simultâneo para a família.', 'juntaplay'),
            ],
            [
                'title'       => 'Spotify Premium Família',
                'price'       => $this->format_currency(24.9),
                'amount'      => '24.90',
                'category'    => 'music',
                'description' => __('Música sem anúncios, mix família e controle parental em um só plano.', 'juntaplay'),
            ],
            [
                'title'       => 'Tidal HiFi Max',
                'price'       => $this->format_currency(29.9),
                'amount'      => '29.90',
                'category'    => 'music',
                'description' => __('Áudio sem perdas e suporte a Dolby Atmos para entusiastas.', 'juntaplay'),
            ],
            [
                'title'       => 'Brainly Premium',
                'price'       => $this->format_currency(21.9),
                'amount'      => '21.90',
                'category'    => 'education',
                'description' => __('Respostas verificadas, tutores online e revisão focada em vestibulares.', 'juntaplay'),
            ],
            [
                'title'       => 'Ubook',
                'price'       => $this->format_currency(14.9),
                'amount'      => '14.90',
                'category'    => 'reading',
                'description' => __('Audiobooks e podcasts originais para maratonar no celular.', 'juntaplay'),
            ],
            [
                'title'       => 'Super Interessante Digital',
                'price'       => $this->format_currency(12.9),
                'amount'      => '12.90',
                'category'    => 'reading',
                'description' => __('Revista de ciência e cultura com acesso ao acervo histórico completo.', 'juntaplay'),
            ],
            [
                'title'       => 'Veja Saúde',
                'price'       => $this->format_currency(9.9),
                'amount'      => '9.90',
                'category'    => 'reading',
                'description' => __('Reportagens sobre saúde, bem-estar e alimentação com curadoria médica.', 'juntaplay'),
            ],
            [
                'title'       => 'Perplexity Pro',
                'price'       => $this->format_currency(79.9),
                'amount'      => '79.90',
                'category'    => 'ai',
                'description' => __('Pesquisa com IA generativa, histórico compartilhado e exportação de respostas.', 'juntaplay'),
            ],
            [
                'title'       => 'Canva Pro',
                'price'       => $this->format_currency(31.9),
                'amount'      => '31.90',
                'category'    => 'office',
                'description' => __('Templates premium, branding kit e bibliotecas colaborativas.', 'juntaplay'),
            ],
            [
                'title'       => 'Google One 2TB',
                'price'       => $this->format_currency(24.9),
                'amount'      => '24.90',
                'category'    => 'office',
                'description' => __('Armazenamento compartilhado, VPN e suporte especializado da Google.', 'juntaplay'),
            ],
            [
                'title'       => 'ExpressVPN',
                'price'       => $this->format_currency(42.9),
                'amount'      => '42.90',
                'category'    => 'security',
                'description' => __('Rede privada virtual com mais de 90 países e proteção para 5 dispositivos.', 'juntaplay'),
            ],
            [
                'title'       => __('Netflix Premium Compartilhado', 'juntaplay'),
                'price'       => $this->format_currency(20.0),
                'amount'      => '20.00',
                'category'    => 'video',
                'description' => __('Acesso 4K com perfis individuais e troca mensal de senha.', 'juntaplay'),
            ],
            [
                'title'       => 'ChatGPT Team',
                'price'       => $this->format_currency(27.5),
                'amount'      => '27.50',
                'category'    => 'ai',
                'description' => __('Espaço colaborativo para times criarem assistentes e compartilharem prompts.', 'juntaplay'),
            ],
        ];

        /**
         * Permite ajustar os cards de inspiração exibidos na criação de grupos.
         */
        $suggestions = apply_filters('juntaplay/groups/suggestions', $suggestions);

        if (count($suggestions) > 5) {
            shuffle($suggestions);
            $suggestions = array_slice($suggestions, 0, 5);
        }

        return $suggestions;
    }

    /**
     * @param array<string, int>   $counts
     * @param array<string, mixed> $group
     */
    private function tally_group_counts(array &$counts, array $group): void
    {
        if (!isset($counts['pending'], $counts['approved'], $counts['rejected'], $counts['archived'])) {
            return;
        }

        $status            = isset($group['status']) ? (string) $group['status'] : Groups::STATUS_PENDING;
        $membership_status = isset($group['membership_status']) ? (string) $group['membership_status'] : 'active';

        if ($status === Groups::STATUS_PENDING) {
            ++$counts['pending'];
        }

        if ($status === Groups::STATUS_APPROVED && $membership_status === 'active') {
            ++$counts['approved'];
        }

        if ($status === Groups::STATUS_REJECTED) {
            ++$counts['rejected'];
        }

        if ($status === Groups::STATUS_ARCHIVED) {
            ++$counts['archived'];
        }
    }

    /**
     * @return array{label: string, tone: string, message: string}
     */
    private function describe_group_status(string $status, string $membership_status, bool $is_owner): array
    {
        $label   = '';
        $tone    = 'info';
        $message = '';

        switch ($status) {
            case Groups::STATUS_APPROVED:
                $label   = __('Aprovado', 'juntaplay');
                $tone    = 'positive';
                $message = __('Grupo disponível para convites e compras.', 'juntaplay');
                break;
            case Groups::STATUS_REJECTED:
                $label   = __('Recusado', 'juntaplay');
                $tone    = 'danger';
                $message = __('Entre em contato com o suporte para revisar as informações do grupo.', 'juntaplay');
                break;
            case Groups::STATUS_ARCHIVED:
                $label   = __('Arquivado', 'juntaplay');
                $tone    = 'muted';
                $message = __('Grupo arquivado e indisponível para novas cotas.', 'juntaplay');
                break;
            case Groups::STATUS_PENDING:
            default:
                $label   = __('Em Análise', 'juntaplay');
                $tone    = 'warning';
                $message = __('Aguarde a aprovação do super administrador. Você será avisado por e-mail.', 'juntaplay');
                break;
        }

        if (!$is_owner) {
            if ($membership_status === 'guest' && $status === Groups::STATUS_APPROVED) {
                $label   = __('Disponível para participação', 'juntaplay');
                $tone    = 'positive';
                $message = __('Selecione uma cota e finalize o pagamento para entrar no grupo.', 'juntaplay');
            } elseif ($membership_status !== 'active') {
                $label   = __('Convite pendente', 'juntaplay');
                $tone    = 'warning';
                $message = __('O administrador do grupo ainda precisa aprovar sua participação.', 'juntaplay');
            }
        }

        return [
            'label'   => $label,
            'tone'    => $tone,
            'message' => $message,
        ];
    }

    private function format_group_role(string $role, bool $is_owner): string
    {
        if ($is_owner || $role === 'owner') {
            return __('Criador do grupo', 'juntaplay');
        }

        if ($role === 'manager') {
            return __('Organizador', 'juntaplay');
        }

        if ($role === 'guest') {
            return __('Interessado', 'juntaplay');
        }

        return __('Participante', 'juntaplay');
    }

    private function format_group_created_at(string $timestamp): string
    {
        if ($timestamp === '') {
            return '';
        }

        $time = strtotime($timestamp);

        if (!$time) {
            return '';
        }

        $diff = human_time_diff($time, current_time('timestamp'));

        return sprintf(__('Criado há %s', 'juntaplay'), $diff);
    }

    private function format_group_reviewed_at(string $timestamp): string
    {
        if ($timestamp === '') {
            return '';
        }

        $time = strtotime($timestamp);

        if (!$time) {
            return '';
        }

        $diff = human_time_diff($time, current_time('timestamp'));

        return sprintf(__('Atualizado há %s', 'juntaplay'), $diff);
    }

    private function build_group_checkout_url(int $group_id, string $group_slug, string $fallback = ''): string
    {
        $checkout = wc_get_checkout_url();

        if (!$checkout) {
            return $fallback;
        }

        if ($group_slug !== '') {
            return add_query_arg('grupo', rawurlencode($group_slug), $checkout);
        }

        if ($group_id > 0) {
            return add_query_arg('group_id', $group_id, $checkout);
        }

        return $checkout ?: $fallback;
    }

    private function build_group_relationship_url(int $group_id, string $group_slug, string $fallback = ''): string
    {
        $page_id = (int) get_option('juntaplay_page_relacao-administrador');
        $base    = $page_id ? get_permalink($page_id) : home_url('/relacao-administrador');

        if (!$base) {
            $base = home_url('/relacao-administrador');
        }

        if (!$base) {
            return $fallback;
        }

        $target = $base;

        if ($fallback !== '') {
            $target = add_query_arg('back', rawurlencode($fallback), $target);
        }

        if ($group_slug !== '') {
            return add_query_arg('grupo', rawurlencode($group_slug), $target);
        }

        if ($group_id > 0) {
            return add_query_arg('group_id', $group_id, $target);
        }

        return $target;
    }

    private function build_group_settings_url(int $group_id): string
    {
        $page_id = (int) get_option('juntaplay_page_perfil');
        $base    = $page_id ? get_permalink($page_id) : home_url('/perfil');

        if (!$base) {
            $base = home_url('/perfil');
        }

        if (!$base) {
            return '';
        }

        $clean_base = strpos($base, '#') !== false ? strtok($base, '#') : $base;
        $clean_base = rtrim((string) $clean_base, '/');

        $anchor = '#jp-profile-groups';

        return $clean_base . $anchor;
    }

    private function format_group_relationship(string $relationship_type): string
    {
        if ($relationship_type === '') {
            return '';
        }

        $options = Groups::get_relationship_options();

        return isset($options[$relationship_type]) ? (string) $options[$relationship_type] : '';
    }

    private function build_group_pool_link(int $pool_id, string $pool_slug): string
    {
        if ($pool_slug !== '') {
            return trailingslashit(home_url('/grupo/' . ltrim($pool_slug, '/')));
        }

        if ($pool_id > 0) {
            $page_id = (int) get_option('juntaplay_page_campanhas');
            $base    = $page_id > 0 ? (string) get_permalink($page_id) : trailingslashit(home_url('/grupos'));

            return add_query_arg('pool', $pool_id, $base);
        }

        return '';
    }

    private function format_tax_type(string $type): string
    {
        return $type === 'pj'
            ? __('Pessoa jurídica', 'juntaplay')
            : __('Pessoa física', 'juntaplay');
    }

    private function format_tax_document(string $document): string
    {
        $digits = preg_replace('/\D+/', '', $document);

        if (strlen($digits) === 11) {
            return substr($digits, 0, 3) . '.' . substr($digits, 3, 3) . '.' . substr($digits, 6, 3) . '-' . substr($digits, 9, 2);
        }

        if (strlen($digits) === 14) {
            return substr($digits, 0, 2) . '.' . substr($digits, 2, 3) . '.' . substr($digits, 5, 3) . '/' . substr($digits, 8, 4) . '-' . substr($digits, 12, 2);
        }

        return $document;
    }

    private function format_postcode(string $postcode): string
    {
        $digits = preg_replace('/\D+/', '', $postcode);

        if (strlen($digits) === 8) {
            return substr($digits, 0, 5) . '-' . substr($digits, 5, 3);
        }

        return $postcode;
    }

    private function format_country(string $country): string
    {
        $country = trim($country);

        if ($country === '') {
            return '';
        }

        if (strcasecmp($country, 'BR') === 0 || strcasecmp($country, 'BRA') === 0
            || stripos($country, 'brasil') === 0 || stripos($country, 'brazil') === 0
        ) {
            return __('Brasil', 'juntaplay');
        }

        if (strlen($country) === 2) {
            return strtoupper($country);
        }

        return ucwords(mb_strtolower($country));
    }

    private function format_password_updated(string $timestamp): string
    {
        if ($timestamp === '') {
            return __('Nunca atualizada', 'juntaplay');
        }

        $time = strtotime($timestamp);

        if (!$time) {
            return __('Atualizada recentemente', 'juntaplay');
        }

        $diff = human_time_diff($time, current_time('timestamp'));

        return sprintf(__('Atualizada há %s', 'juntaplay'), $diff);
    }

    private function format_two_factor_method(string $method): string
    {
        switch ($method) {
            case 'email':
                return __('Código por e-mail', 'juntaplay');
            case 'whatsapp':
                return __('Código por WhatsApp', 'juntaplay');
            default:
                return __('Desativada', 'juntaplay');
        }
    }

    private function format_login_alerts(string $status): string
    {
        return $status === 'no'
            ? __('Alertas desativados', 'juntaplay')
            : __('Alertas por e-mail ativados', 'juntaplay');
    }

    private function format_sessions_count(int $count): string
    {
        if ($count < 1) {
            $count = 1;
        }

        return sprintf(_n('%d sessão ativa', '%d sessões ativas', $count, 'juntaplay'), $count);
    }

    private function format_currency(float $amount): string
    {
        $formatted = number_format_i18n($amount, 2);

        return sprintf('R$ %s', $formatted);
    }

    private function money_to_input(float $amount): string
    {
        if ($amount <= 0) {
            return '';
        }

        return number_format_i18n($amount, 2);
    }

    private function parse_money(string $raw): float
    {
        $value = trim($raw);

        if ($value === '') {
            return 0.0;
        }

        $filtered = preg_replace('/[^0-9,\.\-]/', '', $value);
        if (!is_string($filtered) || $filtered === '' || $filtered === '-') {
            return 0.0;
        }

        $has_comma = strpos($filtered, ',') !== false;
        $has_dot   = strpos($filtered, '.') !== false;

        if ($has_comma && $has_dot) {
            $filtered = str_replace('.', '', $filtered);
            $filtered = str_replace(',', '.', $filtered);
        } elseif ($has_comma) {
            $filtered = str_replace(',', '.', $filtered);
        }

        return round((float) $filtered, 2);
    }

    public function parse_money_input(string $raw): float
    {
        return $this->parse_money($raw);
    }

    /**
     * @param string[] $hints
     */
    private function combine_hints(array $hints): string
    {
        $filtered = array_values(array_filter(array_map('trim', $hints)));

        return $filtered ? implode(' • ', $filtered) : '';
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
     * @param array<string, mixed> $data
     */
    private function format_credit_auto(array $data): string
    {
        if (($data['credit_auto_status'] ?? 'off') !== 'on') {
            return __('Desativada', 'juntaplay');
        }

        $amount    = $this->format_currency((float) ($data['credit_auto_amount'] ?? 0.0));
        $threshold = $this->format_currency((float) ($data['credit_auto_threshold'] ?? 0.0));

        return sprintf(__('Recarga de %1$s quando o saldo ficar abaixo de %2$s', 'juntaplay'), $amount, $threshold);
    }

    private function format_credit_payment_method(string $method): string
    {
        switch ($method) {
            case 'card':
                return __('Cartão de crédito', 'juntaplay');
            case 'boleto':
                return __('Boleto bancário', 'juntaplay');
            case 'pix':
            default:
                return __('Pix (instantâneo)', 'juntaplay');
        }
    }

    private function format_credit_pix(string $key): string
    {
        if ($key === '') {
            return __('Nenhuma chave cadastrada', 'juntaplay');
        }

        if (strlen($key) > 24) {
            $prefix = function_exists('mb_substr') ? mb_substr($key, 0, 12) : substr($key, 0, 12);
            $suffix = function_exists('mb_substr') ? mb_substr($key, -6) : substr($key, -6);

            return $prefix . '…' . $suffix;
        }

        return $key;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function format_credit_bank(array $data): string
    {
        $holder      = (string) ($data['credit_bank_holder'] ?? '');
        $document    = (string) ($data['credit_bank_document'] ?? '');
        $bank        = (string) ($data['credit_bank_name'] ?? '');
        $agency      = (string) ($data['credit_bank_agency'] ?? '');
        $account     = (string) ($data['credit_bank_account'] ?? '');
        $accountType = (string) ($data['credit_bank_account_type'] ?? 'checking');

        if ($holder === '' && $bank === '' && $agency === '' && $account === '') {
            return __('Nenhuma conta cadastrada', 'juntaplay');
        }

        $parts = [];

        if ($holder !== '') {
            $parts[] = $holder;
        }

        if ($bank !== '') {
            $parts[] = $bank;
        }

        if ($agency !== '') {
            $parts[] = sprintf(__('Ag. %s', 'juntaplay'), $agency);
        }

        if ($account !== '') {
            $parts[] = sprintf(__('Conta %1$s (%2$s)', 'juntaplay'), $account, $this->format_credit_account_type($accountType));
        }

        if ($document !== '') {
            $parts[] = sprintf(__('Doc: %s', 'juntaplay'), $this->format_tax_document($document));
        }

        return implode(' • ', $parts);
    }

    private function format_credit_account_type(string $type): string
    {
        return $type === 'savings'
            ? __('Poupança', 'juntaplay')
            : __('Corrente', 'juntaplay');
    }

    private function format_credit_bonus_hint(string $timestamp): string
    {
        if ($timestamp === '') {
            return '';
        }

        $time = strtotime($timestamp);

        if (!$time) {
            return '';
        }

        $now = current_time('timestamp');

        if ($time <= $now) {
            return __('Bônus expirado', 'juntaplay');
        }

        $date = date_i18n(get_option('date_format'), $time);

        return sprintf(__('Expira em %s', 'juntaplay'), $date);
    }

    private function format_credit_updated_at(string $timestamp): string
    {
        if ($timestamp === '') {
            return '';
        }

        $time = strtotime($timestamp);

        if (!$time) {
            return '';
        }

        $format = trim((string) get_option('date_format') . ' ' . (string) get_option('time_format'));

        return sprintf(__('Atualizado em %s', 'juntaplay'), date_i18n($format, $time));
    }

    private function format_credit_last_recharge(string $timestamp): string
    {
        if ($timestamp === '') {
            return '';
        }

        $time = strtotime($timestamp);

        if (!$time) {
            return '';
        }

        return sprintf(__('Última recarga em %s', 'juntaplay'), date_i18n(get_option('date_format'), $time));
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function build_credit_history_context(array $data): array
    {
        $user_id = get_current_user_id();

        $deposit_enabled = class_exists('WooCommerce');
        $deposit_min     = (float) apply_filters('juntaplay/credits/deposit_min', 25.0, $user_id);
        $deposit_max     = (float) apply_filters('juntaplay/credits/deposit_max', 5000.0, $user_id);
        $suggestions_raw = apply_filters('juntaplay/credits/deposit_suggestions', [50, 100, 150], $user_id);

        $suggestions = [];
        if (is_array($suggestions_raw)) {
            foreach ($suggestions_raw as $value) {
                if (is_numeric($value)) {
                    $float = (float) $value;
                    if ($float > 0) {
                        $suggestions[] = $float;
                    }
                }
            }
        }

        $context = [
            'transactions'     => [],
            'pagination'       => ['page' => 1, 'pages' => 1, 'total' => 0],
            'withdrawals'      => [],
            'two_factor'       => [
                'method'        => 'email',
                'label'         => $this->format_two_factor_label('email'),
                'destination'   => '',
                'code_expires'  => '',
                'code_remaining'=> 0,
            ],
            'has_pix'          => !empty($data['credit_pix_key']),
            'has_bank'         => !empty($data['credit_bank_holder']) && !empty($data['credit_bank_account']),
            'balance_label'    => $this->format_currency((float) ($data['credit_balance'] ?? 0.0)),
            'reserved_label'   => $this->format_currency((float) ($data['credit_reserved'] ?? 0.0)),
            'bonus_label'      => $this->format_currency((float) ($data['credit_bonus'] ?? 0.0)),
            'withdraw_pending' => $this->format_currency((float) ($data['credit_withdraw_pending'] ?? 0.0)),
            'deposit'          => [
                'enabled'     => $deposit_enabled,
                'min'         => $this->format_currency($deposit_min),
                'min_raw'     => $deposit_min,
                'max'         => $deposit_max > 0 ? $this->format_currency($deposit_max) : '',
                'max_raw'     => $deposit_max,
                'suggestions' => array_map(fn (float $value): array => [
                    'value' => $value,
                    'label' => $this->format_currency($value),
                ], $suggestions),
            ],
        ];

        if (!$user_id) {
            return $context;
        }

        $transactions_page = CreditTransactions::get_for_user($user_id, 1, 10, []);
        $transactions      = [];

        foreach ($transactions_page['items'] as $transaction) {
            if (is_array($transaction)) {
                $transactions[] = $this->decorate_transaction_entry($transaction);
            }
        }

        $withdrawals = [];
        foreach (CreditWithdrawals::get_for_user($user_id, 6) as $withdrawal) {
            if (is_array($withdrawal)) {
                $withdrawals[] = $this->decorate_withdrawal_entry($withdrawal);
            }
        }

        $method = isset($data['two_factor_method']) ? (string) $data['two_factor_method'] : 'email';
        if (!in_array($method, ['email', 'whatsapp'], true)) {
            $method = 'email';
        }

        $current_user   = wp_get_current_user();
        $fallback_email = $current_user instanceof WP_User ? (string) $current_user->user_email : '';
        $destination    = $this->resolve_two_factor_destination($method, $data, $fallback_email);

        $code_expires = isset($data['withdraw_code_expires'])
            ? (int) $data['withdraw_code_expires']
            : (int) get_user_meta($user_id, 'juntaplay_withdraw_code_expires', true);
        $remaining    = $code_expires > 0 ? max(0, $code_expires - time()) : 0;

        $context['transactions'] = $transactions;
        $context['pagination']   = [
            'page'  => $transactions_page['page'],
            'pages' => $transactions_page['pages'],
            'total' => $transactions_page['total'],
        ];
        $context['withdrawals']  = $withdrawals;
        $context['two_factor']   = [
            'method'        => $method,
            'label'         => $this->format_two_factor_label($method),
            'destination'   => $destination,
            'code_expires'  => $code_expires > 0 ? gmdate('c', $code_expires) : '',
            'code_remaining'=> $remaining,
        ];

        return $context;
    }

    /**
     * @param array<string, mixed> $transaction
     * @return array<string, mixed>
     */
    private function decorate_transaction_entry(array $transaction): array
    {
        $amount = isset($transaction['amount']) ? (float) $transaction['amount'] : 0.0;
        $type   = (string) ($transaction['type'] ?? CreditTransactions::TYPE_ADJUSTMENT);
        $status = (string) ($transaction['status'] ?? CreditTransactions::STATUS_COMPLETED);

        $created_at_raw = (string) ($transaction['created_at'] ?? '');
        $time_label     = $this->format_datetime($created_at_raw);
        $timestamp      = $created_at_raw !== '' ? strtotime($created_at_raw) : 0;
        if ($timestamp === false) {
            $timestamp = 0;
        }

        $type_label   = $this->format_transaction_type_label($type);
        $status_label = $this->format_transaction_status_label($status);
        $reference    = (string) ($transaction['reference'] ?? '');

        return [
            'id'            => isset($transaction['id']) ? (int) $transaction['id'] : 0,
            'type'          => $type,
            'type_label'    => $type_label,
            'status'        => $status,
            'status_label'  => $status_label,
            'amount'        => $this->format_currency($amount),
            'amount_raw'    => $amount,
            'reference'     => $reference,
            'time'          => $time_label,
            'created_at'    => $created_at_raw,
            'timestamp'     => is_int($timestamp) ? $timestamp : 0,
            'search'        => $this->normalize_search_terms([
                $type_label,
                $status_label,
                $reference,
                $time_label,
            ]),
        ];
    }

    /**
     * @param array<string, mixed> $withdrawal
     * @return array<string, mixed>
     */
    private function decorate_withdrawal_entry(array $withdrawal): array
    {
        $amount      = isset($withdrawal['amount']) ? (float) $withdrawal['amount'] : 0.0;
        $status      = (string) ($withdrawal['status'] ?? CreditWithdrawals::STATUS_PENDING);
        $destination = [];

        if (isset($withdrawal['destination']) && is_array($withdrawal['destination'])) {
            $destination = $withdrawal['destination'];
        }

        return [
            'id'          => isset($withdrawal['id']) ? (int) $withdrawal['id'] : 0,
            'status'      => $status,
            'status_label'=> $this->format_withdrawal_status($status),
            'amount'      => $this->format_currency($amount),
            'reference'   => (string) ($withdrawal['reference'] ?? ''),
            'time'        => $this->format_datetime((string) ($withdrawal['requested_at'] ?? '')),
            'destination' => $this->format_withdraw_destination_label($destination),
        ];
    }

    private function format_transaction_type_label(string $type): string
    {
        return match ($type) {
            CreditTransactions::TYPE_DEPOSIT    => __('Entrada de créditos', 'juntaplay'),
            CreditTransactions::TYPE_WITHDRAWAL => __('Retirada', 'juntaplay'),
            CreditTransactions::TYPE_BONUS      => __('Bônus promocional', 'juntaplay'),
            CreditTransactions::TYPE_PURCHASE   => __('Compra de cotas', 'juntaplay'),
            CreditTransactions::TYPE_REFUND     => __('Reembolso', 'juntaplay'),
            default                             => __('Ajuste de saldo', 'juntaplay'),
        };
    }

    private function format_transaction_status_label(string $status): string
    {
        return match ($status) {
            CreditTransactions::STATUS_PENDING => __('Pendente', 'juntaplay'),
            CreditTransactions::STATUS_FAILED  => __('Cancelado', 'juntaplay'),
            default                            => __('Concluído', 'juntaplay'),
        };
    }

    private function format_withdrawal_status(string $status): string
    {
        return match ($status) {
            CreditWithdrawals::STATUS_PENDING    => __('Em análise', 'juntaplay'),
            CreditWithdrawals::STATUS_PROCESSING => __('Processando', 'juntaplay'),
            CreditWithdrawals::STATUS_APPROVED   => __('Pago', 'juntaplay'),
            CreditWithdrawals::STATUS_REJECTED   => __('Recusado', 'juntaplay'),
            CreditWithdrawals::STATUS_CANCELED   => __('Cancelado', 'juntaplay'),
            default                              => __('Em análise', 'juntaplay'),
        };
    }

    /**
     * @param array<string, mixed> $destination
     */
    private function format_withdraw_destination_label(array $destination): string
    {
        $method = isset($destination['method']) ? (string) $destination['method'] : 'pix';

        if ($method === 'pix') {
            return $this->format_credit_pix((string) ($destination['pix_key'] ?? ''));
        }

        $parts = [];
        if (!empty($destination['bank'])) {
            $parts[] = (string) $destination['bank'];
        }
        if (!empty($destination['agency'])) {
            $parts[] = sprintf(__('Ag. %s', 'juntaplay'), (string) $destination['agency']);
        }
        if (!empty($destination['account'])) {
            $parts[] = sprintf(__('Conta %s', 'juntaplay'), (string) $destination['account']);
        }

        return $parts ? implode(' • ', $parts) : __('Conta bancária cadastrada', 'juntaplay');
    }

    private function format_datetime(string $timestamp): string
    {
        if ($timestamp === '') {
            return '';
        }

        $time = strtotime($timestamp);

        if (!$time) {
            return '';
        }

        $format = trim((string) get_option('date_format') . ' ' . (string) get_option('time_format'));

        return date_i18n($format, $time);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function resolve_two_factor_destination(string $method, array $data, string $fallback_email): string
    {
        if ($method === 'whatsapp') {
            $phone = isset($data['whatsapp']) ? (string) $data['whatsapp'] : '';
            if ($phone === '') {
                $phone = isset($data['phone']) ? (string) $data['phone'] : '';
            }

            if ($phone !== '') {
                return $this->mask_phone($phone);
            }
        }

        if ($fallback_email !== '') {
            return $this->mask_email($fallback_email);
        }

        return '';
    }

    private function format_two_factor_label(string $method): string
    {
        return match ($method) {
            'whatsapp' => __('Código por WhatsApp', 'juntaplay'),
            'email'    => __('Código por e-mail', 'juntaplay'),
            default    => __('Desativada', 'juntaplay'),
        };
    }

    private function mask_email(string $email): string
    {
        if (!is_email($email)) {
            return $email;
        }

        [$local, $domain] = explode('@', $email, 2);
        $length = strlen($local);

        if ($length <= 2) {
            $masked = str_repeat('*', $length);
        } else {
            $masked = substr($local, 0, 2) . str_repeat('*', max(1, $length - 2));
        }

        return $masked . '@' . $domain;
    }

    private function mask_phone(string $phone): string
    {
        $digits = $this->normalize_phone($phone);

        if ($digits === '') {
            return $phone;
        }

        $last = substr($digits, -4);

        if (strlen($digits) >= 11) {
            $ddd = substr($digits, 0, 2);

            return sprintf('(%s) *****-%s', $ddd, $last);
        }

        return sprintf('****-%s', $last);
    }

    private function normalize_phone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone);

        return is_string($digits) ? $digits : '';
    }

    private function build_withdraw_destination(int $user_id, string $method): array
    {
        if ($method === 'bank') {
            return [
                'method'       => 'bank',
                'holder'       => (string) get_user_meta($user_id, 'juntaplay_credit_bank_holder', true),
                'document'     => (string) get_user_meta($user_id, 'juntaplay_credit_bank_document', true),
                'bank'         => (string) get_user_meta($user_id, 'juntaplay_credit_bank_name', true),
                'agency'       => (string) get_user_meta($user_id, 'juntaplay_credit_bank_agency', true),
                'account'      => (string) get_user_meta($user_id, 'juntaplay_credit_bank_account', true),
                'account_type' => (string) get_user_meta($user_id, 'juntaplay_credit_bank_account_type', true),
            ];
        }

        return [
            'method'  => 'pix',
            'pix_key' => (string) get_user_meta($user_id, 'juntaplay_credit_pix_key', true),
        ];
    }

    private function parse_decimal(string $value): float
    {
        $normalized = preg_replace('/[^0-9,.-]/', '', $value);

        if ($normalized === null || $normalized === '' || $normalized === '-' || $normalized === '--') {
            return 0.0;
        }

        $comma = strrpos($normalized, ',');
        $dot   = strrpos($normalized, '.');

        if ($comma !== false && $dot !== false) {
            if ($comma > $dot) {
                $normalized = str_replace('.', '', $normalized);
                $normalized = str_replace(',', '.', $normalized);
            } else {
                $normalized = str_replace(',', '', $normalized);
            }
        } elseif ($comma !== false) {
            $normalized = str_replace(',', '.', $normalized);
        }

        return (float) $normalized;
    }

    private function format_decimal(float $value): string
    {
        return number_format($value, 2, '.', '');
    }

    private function store_decimal(float $value): string
    {
        return number_format($value, 2, '.', '');
    }

    private function to_float($value): float
    {
        if (is_array($value)) {
            $value = reset($value);
        }

        if (is_string($value)) {
            $value = str_replace(',', '.', preg_replace('/[^0-9,.-]/', '', $value) ?? '0');
        }

        return (float) $value;
    }

    private function get_sessions_count(WP_User $user): int
    {
        if (!class_exists(WP_Session_Tokens::class)) {
            return 1;
        }

        $manager = WP_Session_Tokens::get_instance($user->ID);

        if (!$manager) {
            return 1;
        }

        $sessions = $manager->get_all();

        if (!is_array($sessions)) {
            return 1;
        }

        $count = count($sessions);

        return $count > 0 ? $count : 1;
    }

    private function update_name(int $user_id): void
    {
        $name = isset($_POST['jp_profile_name'])
            ? sanitize_text_field(wp_unslash($_POST['jp_profile_name'])) // phpcs:ignore WordPress.Security.NonceVerification.Missing
            : '';

        if ($name === '') {
            $this->add_error('name', __('Informe seu nome completo.', 'juntaplay'));

            return;
        }

        $updated = wp_update_user([
            'ID'           => $user_id,
            'display_name' => $name,
            'first_name'   => $name,
        ]);

        if ($updated instanceof WP_Error) {
            $this->add_error('name', $updated->get_error_message());

            return;
        }

        update_user_meta($user_id, 'first_name', $name);
        update_user_meta($user_id, 'billing_first_name', $name);

        $this->invalidate_cache();
        $this->add_notice(__('Nome atualizado com sucesso.', 'juntaplay'));
        $this->active_section = null;

        do_action('juntaplay/profile/updated', $user_id, 'name', ['name' => $name]);
    }

    private function update_email(int $user_id): void
    {
        $email = isset($_POST['jp_profile_email'])
            ? sanitize_email(wp_unslash($_POST['jp_profile_email'])) // phpcs:ignore WordPress.Security.NonceVerification.Missing
            : '';

        if ($email === '' || !is_email($email)) {
            $this->add_error('email', __('Informe um e-mail válido.', 'juntaplay'));

            return;
        }

        $existing = email_exists($email);
        if ($existing && (int) $existing !== $user_id) {
            $this->add_error('email', __('Este e-mail já está em uso.', 'juntaplay'));

            return;
        }

        $updated = wp_update_user([
            'ID'         => $user_id,
            'user_email' => $email,
        ]);

        if ($updated instanceof WP_Error) {
            $this->add_error('email', $updated->get_error_message());

            return;
        }

        $this->invalidate_cache();
        $this->add_notice(__('E-mail atualizado com sucesso.', 'juntaplay'));
        $this->active_section = null;

        do_action('juntaplay/profile/updated', $user_id, 'email', ['email' => $email]);
    }

    private function update_phone(int $user_id): void
    {
        $phone = isset($_POST['jp_profile_phone'])
            ? sanitize_text_field(wp_unslash($_POST['jp_profile_phone'])) // phpcs:ignore WordPress.Security.NonceVerification.Missing
            : '';

        if ($phone === '') {
            $this->add_error('phone', __('Informe um telefone para contato.', 'juntaplay'));

            return;
        }

        update_user_meta($user_id, 'billing_phone', $phone);
        update_user_meta($user_id, 'phone', $phone);

        $this->invalidate_cache();
        $this->add_notice(__('Telefone atualizado com sucesso.', 'juntaplay'));
        $this->active_section = null;

        do_action('juntaplay/profile/updated', $user_id, 'phone', ['phone' => $phone]);
    }

    private function update_whatsapp(int $user_id): void
    {
        $whatsapp = isset($_POST['jp_profile_whatsapp'])
            ? sanitize_text_field(wp_unslash($_POST['jp_profile_whatsapp'])) // phpcs:ignore WordPress.Security.NonceVerification.Missing
            : '';

        if ($whatsapp === '') {
            $this->add_error('whatsapp', __('Informe um número de WhatsApp válido.', 'juntaplay'));

            return;
        }

        update_user_meta($user_id, 'juntaplay_whatsapp', $whatsapp);
        update_user_meta($user_id, 'billing_whatsapp', $whatsapp);

        $this->invalidate_cache();
        $this->add_notice(__('WhatsApp atualizado com sucesso.', 'juntaplay'));
        $this->active_section = null;

        do_action('juntaplay/profile/updated', $user_id, 'whatsapp', ['whatsapp' => $whatsapp]);
    }

    private function delete_account(int $user_id): void
    {
        $confirmed = isset($_POST['jp_profile_confirm_delete'])
            ? sanitize_text_field(wp_unslash($_POST['jp_profile_confirm_delete'])) // phpcs:ignore WordPress.Security.NonceVerification.Missing
            : '';

        if ($confirmed !== '1') {
            $this->add_error('account_delete', __('Confirme a exclusão da conta para continuar.', 'juntaplay'));

            return;
        }

        $user = get_userdata($user_id);
        if (!$user instanceof WP_User) {
            $this->add_error('account_delete', __('Não foi possível localizar sua conta.', 'juntaplay'));

            return;
        }

        if (!function_exists('wp_delete_user')) {
            require_once ABSPATH . 'wp-admin/includes/user.php';
        }

        /**
         * Allow third-parties to prevent the account deletion.
         *
         * @param bool    $prevent
         * @param int     $user_id
         * @param WP_User $user
         */
        $prevent = (bool) apply_filters('juntaplay/profile/prevent_account_delete', false, $user_id, $user);
        if ($prevent) {
            $this->add_error('account_delete', __('Não foi possível excluir sua conta no momento.', 'juntaplay'));

            return;
        }

        do_action('juntaplay/profile/before_account_delete', $user_id, $user);

        $deleted = wp_delete_user($user_id);
        if (!$deleted) {
            $this->add_error('account_delete', __('Não foi possível excluir sua conta agora. Tente novamente.', 'juntaplay'));

            return;
        }

        if (function_exists('wp_destroy_other_sessions')) {
            wp_destroy_other_sessions();
        }

        do_action('juntaplay/profile/after_account_delete', $user_id, $user);

        wp_logout();

        $redirect = apply_filters('juntaplay/profile/account_delete_redirect', home_url('/'), $user_id, $user);
        wp_safe_redirect($redirect ?: home_url('/'));
        exit;
    }

    private function update_avatar_upload(int $user_id): void
    {
        $this->active_section = 'avatar';

        if (!isset($_FILES['jp_profile_avatar_file'])) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
            $this->add_error('avatar', __('Selecione uma imagem para enviar.', 'juntaplay'));

            return;
        }

        $file = $_FILES['jp_profile_avatar_file']; // phpcs:ignore WordPress.Security.NonceVerification.Missing

        if (!is_array($file)) {
            $this->add_error('avatar', __('Não foi possível processar o arquivo enviado.', 'juntaplay'));

            return;
        }

        $error = isset($file['error']) ? (int) $file['error'] : UPLOAD_ERR_NO_FILE;

        if ($error === UPLOAD_ERR_NO_FILE) {
            $this->add_error('avatar', __('Selecione uma imagem para enviar.', 'juntaplay'));

            return;
        }

        if ($error !== UPLOAD_ERR_OK) {
            $this->add_error('avatar', __('Ocorreu um erro ao enviar a imagem. Tente novamente.', 'juntaplay'));

            return;
        }

        $size_limit = 5 * 1024 * 1024; // 5MB
        $file_size  = isset($file['size']) ? (int) $file['size'] : 0;

        if ($file_size > $size_limit) {
            $this->add_error('avatar', __('A imagem deve ter no máximo 5MB.', 'juntaplay'));

            return;
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';

        $previous_id = (int) get_user_meta($user_id, 'juntaplay_avatar_id', true);

        $attachment_id = media_handle_upload('jp_profile_avatar_file', 0, [], ['test_form' => false]);

        if ($attachment_id instanceof WP_Error) {
            $this->add_error('avatar', $this->format_upload_error($attachment_id));

            return;
        }

        $attachment_id = (int) $attachment_id;

        $avatar_url = wp_get_attachment_image_url($attachment_id, 'thumbnail');

        update_user_meta($user_id, 'juntaplay_avatar_id', $attachment_id);
        update_user_meta($user_id, 'juntaplay_avatar_source', 'manual');

        if ($avatar_url) {
            update_user_meta($user_id, 'juntaplay_avatar_url', esc_url_raw($avatar_url));
        } else {
            delete_user_meta($user_id, 'juntaplay_avatar_url');
        }

        if ($previous_id > 0 && $previous_id !== $attachment_id) {
            wp_delete_attachment($previous_id, true);
        }

        $this->invalidate_cache();
        $this->add_notice(__('Foto de perfil atualizada com sucesso.', 'juntaplay'));
        $this->active_section = null;

        do_action('juntaplay/profile/updated', $user_id, 'avatar_upload', ['attachment_id' => $attachment_id]);
    }

    private function remove_avatar(int $user_id): void
    {
        $this->active_section = 'avatar';

        $avatar_id = (int) get_user_meta($user_id, 'juntaplay_avatar_id', true);

        if ($avatar_id > 0) {
            wp_delete_attachment($avatar_id, true);
        }

        delete_user_meta($user_id, 'juntaplay_avatar_id');
        delete_user_meta($user_id, 'juntaplay_avatar_url');
        delete_user_meta($user_id, 'juntaplay_avatar_source');

        $this->invalidate_cache();
        $this->add_notice(__('Foto de perfil removida.', 'juntaplay'));
        $this->active_section = null;

        do_action('juntaplay/profile/updated', $user_id, 'avatar_remove', []);
    }

    private function update_tax_type(int $user_id): void
    {
        $type = isset($_POST['jp_profile_tax_type'])
            ? sanitize_key(wp_unslash($_POST['jp_profile_tax_type'])) // phpcs:ignore WordPress.Security.NonceVerification.Missing
            : '';

        if (!in_array($type, ['pf', 'pj'], true)) {
            $this->add_error('tax_type', __('Selecione o tipo de cadastro.', 'juntaplay'));

            return;
        }

        update_user_meta($user_id, 'juntaplay_tax_type', $type);

        $this->invalidate_cache();
        $this->add_notice(__('Tipo de cadastro atualizado com sucesso.', 'juntaplay'));
        $this->active_section = null;

        do_action('juntaplay/profile/updated', $user_id, 'tax_type', ['tax_type' => $type]);
    }

    private function update_tax_document(int $user_id): void
    {
        $document = isset($_POST['jp_profile_tax_document'])
            ? preg_replace('/\D+/', '', wp_unslash($_POST['jp_profile_tax_document'])) // phpcs:ignore WordPress.Security.NonceVerification.Missing
            : '';

        $document = is_string($document) ? $document : '';

        if ($document === '') {
            $this->add_error('tax_document', __('Informe um CPF ou CNPJ válido.', 'juntaplay'));

            return;
        }

        $length = strlen($document);
        if (!in_array($length, [11, 14], true)) {
            $this->add_error('tax_document', __('O CPF/CNPJ deve conter 11 ou 14 dígitos.', 'juntaplay'));

            return;
        }

        update_user_meta($user_id, 'juntaplay_tax_document', $document);

        $this->invalidate_cache();
        $this->add_notice(__('Documento fiscal atualizado com sucesso.', 'juntaplay'));
        $this->active_section = null;

        do_action('juntaplay/profile/updated', $user_id, 'tax_document', ['tax_document' => $document]);
    }

    private function update_tax_company(int $user_id): void
    {
        $company = isset($_POST['jp_profile_tax_company'])
            ? sanitize_text_field(wp_unslash($_POST['jp_profile_tax_company'])) // phpcs:ignore WordPress.Security.NonceVerification.Missing
            : '';

        $type = (string) get_user_meta($user_id, 'juntaplay_tax_type', true);

        if ($type === 'pj' && $company === '') {
            $this->add_error('tax_company', __('Informe a razão social da empresa.', 'juntaplay'));

            return;
        }

        update_user_meta($user_id, 'billing_company', $company);

        $this->invalidate_cache();
        $this->add_notice(__('Dados da empresa atualizados com sucesso.', 'juntaplay'));
        $this->active_section = null;

        do_action('juntaplay/profile/updated', $user_id, 'tax_company', ['tax_company' => $company]);
    }

    private function update_tax_state_registration(int $user_id): void
    {
        $state_registration = isset($_POST['jp_profile_tax_state_registration'])
            ? sanitize_text_field(wp_unslash($_POST['jp_profile_tax_state_registration'])) // phpcs:ignore WordPress.Security.NonceVerification.Missing
            : '';

        update_user_meta($user_id, 'juntaplay_tax_state_registration', $state_registration);

        $this->invalidate_cache();
        $this->add_notice(__('Inscrição estadual atualizada com sucesso.', 'juntaplay'));
        $this->active_section = null;

        do_action(
            'juntaplay/profile/updated',
            $user_id,
            'tax_state_registration',
            ['tax_state_registration' => $state_registration]
        );
    }

    private function update_tax_address(int $user_id): void
    {
        $address = isset($_POST['jp_profile_tax_address'])
            ? sanitize_text_field(wp_unslash($_POST['jp_profile_tax_address'])) // phpcs:ignore WordPress.Security.NonceVerification.Missing
            : '';

        if ($address === '') {
            $this->add_error('tax_address', __('Informe o endereço utilizado para faturamento.', 'juntaplay'));

            return;
        }

        update_user_meta($user_id, 'billing_address_1', $address);

        $this->invalidate_cache();
        $this->add_notice(__('Endereço atualizado com sucesso.', 'juntaplay'));
        $this->active_section = null;

        do_action('juntaplay/profile/updated', $user_id, 'tax_address', ['tax_address' => $address]);
    }

    private function update_tax_city(int $user_id): void
    {
        $city = isset($_POST['jp_profile_tax_city'])
            ? sanitize_text_field(wp_unslash($_POST['jp_profile_tax_city'])) // phpcs:ignore WordPress.Security.NonceVerification.Missing
            : '';

        if ($city === '') {
            $this->add_error('tax_city', __('Informe a cidade para faturamento.', 'juntaplay'));

            return;
        }

        update_user_meta($user_id, 'billing_city', $city);

        $this->invalidate_cache();
        $this->add_notice(__('Cidade atualizada com sucesso.', 'juntaplay'));
        $this->active_section = null;

        do_action('juntaplay/profile/updated', $user_id, 'tax_city', ['tax_city' => $city]);
    }

    private function update_tax_state(int $user_id): void
    {
        $state = isset($_POST['jp_profile_tax_state'])
            ? sanitize_text_field(wp_unslash($_POST['jp_profile_tax_state'])) // phpcs:ignore WordPress.Security.NonceVerification.Missing
            : '';

        $state = strtoupper($state);

        if ($state === '' || strlen($state) !== 2) {
            $this->add_error('tax_state', __('Informe a sigla do estado (UF).', 'juntaplay'));

            return;
        }

        update_user_meta($user_id, 'billing_state', $state);

        $this->invalidate_cache();
        $this->add_notice(__('Estado atualizado com sucesso.', 'juntaplay'));
        $this->active_section = null;

        do_action('juntaplay/profile/updated', $user_id, 'tax_state', ['tax_state' => $state]);
    }

    private function update_tax_country(int $user_id): void
    {
        $country = isset($_POST['jp_profile_tax_country'])
            ? sanitize_text_field(wp_unslash($_POST['jp_profile_tax_country'])) // phpcs:ignore WordPress.Security.NonceVerification.Missing
            : '';

        $country = trim($country);

        if ($country === '') {
            $this->add_error('tax_country', __('Informe o país de faturamento.', 'juntaplay'));

            return;
        }

        if (stripos($country, 'brasil') === 0 || stripos($country, 'brazil') === 0) {
            $country = 'BR';
        } elseif (strlen($country) === 2) {
            $country = strtoupper($country);
        } else {
            $country = strtoupper(substr($country, 0, 2));
        }

        if (!preg_match('/^[A-Z]{2}$/', $country)) {
            $this->add_error('tax_country', __('Informe o país utilizando apenas duas letras.', 'juntaplay'));

            return;
        }

        update_user_meta($user_id, 'billing_country', $country);

        $this->invalidate_cache();
        $this->add_notice(__('País de faturamento atualizado com sucesso.', 'juntaplay'));
        $this->active_section = null;

        do_action('juntaplay/profile/updated', $user_id, 'tax_country', ['tax_country' => $country]);
    }

    private function update_tax_postcode(int $user_id): void
    {
        $postcode = isset($_POST['jp_profile_tax_postcode'])
            ? preg_replace('/\D+/', '', wp_unslash($_POST['jp_profile_tax_postcode'])) // phpcs:ignore WordPress.Security.NonceVerification.Missing
            : '';

        $postcode = is_string($postcode) ? $postcode : '';

        if ($postcode === '' || strlen($postcode) < 5) {
            $this->add_error('tax_postcode', __('Informe um CEP válido.', 'juntaplay'));

            return;
        }

        update_user_meta($user_id, 'billing_postcode', $postcode);

        $this->invalidate_cache();
        $this->add_notice(__('CEP atualizado com sucesso.', 'juntaplay'));
        $this->active_section = null;

        do_action('juntaplay/profile/updated', $user_id, 'tax_postcode', ['tax_postcode' => $postcode]);
    }

    private function update_password(int $user_id): void
    {
        $user = wp_get_current_user();

        if (!$user instanceof WP_User || (int) $user->ID !== $user_id) {
            $this->add_error('password', __('Não foi possível validar o usuário autenticado.', 'juntaplay'));

            return;
        }

        $current = isset($_POST['jp_profile_password_current'])
            ? (string) wp_unslash($_POST['jp_profile_password_current']) // phpcs:ignore WordPress.Security.NonceVerification.Missing
            : '';
        $new_password = isset($_POST['jp_profile_password_new'])
            ? (string) wp_unslash($_POST['jp_profile_password_new']) // phpcs:ignore WordPress.Security.NonceVerification.Missing
            : '';
        $confirm = isset($_POST['jp_profile_password_confirm'])
            ? (string) wp_unslash($_POST['jp_profile_password_confirm']) // phpcs:ignore WordPress.Security.NonceVerification.Missing
            : '';

        if ($current === '' || !wp_check_password($current, $user->user_pass, $user_id)) {
            $this->add_error('password', __('A senha atual não confere.', 'juntaplay'));

            return;
        }

        if ($new_password === '') {
            $this->add_error('password', __('Informe a nova senha.', 'juntaplay'));

            return;
        }

        if (strlen($new_password) < 8) {
            $this->add_error('password', __('A nova senha deve ter pelo menos 8 caracteres.', 'juntaplay'));

            return;
        }

        if ($new_password === $current) {
            $this->add_error('password', __('A nova senha deve ser diferente da senha atual.', 'juntaplay'));

            return;
        }

        if ($new_password !== $confirm) {
            $this->add_error('password', __('As senhas informadas não coincidem.', 'juntaplay'));

            return;
        }

        $result = wp_update_user([
            'ID'        => $user_id,
            'user_pass' => $new_password,
        ]);

        if ($result instanceof WP_Error) {
            $this->add_error('password', $result->get_error_message());

            return;
        }

        update_user_meta($user_id, 'juntaplay_password_changed_at', current_time('mysql'));

        if (function_exists('wp_destroy_other_sessions')) {
            wp_destroy_other_sessions();
        } elseif (class_exists(WP_Session_Tokens::class)) {
            $token = wp_get_session_token();
            if ($token) {
                $manager = WP_Session_Tokens::get_instance($user_id);
                if ($manager) {
                    $manager->destroy_other_sessions($token);
                }
            }
        }

        if (function_exists('wp_set_auth_cookie')) {
            wp_set_auth_cookie($user_id);
        }

        $this->invalidate_cache();
        $this->add_notice(__('Senha atualizada com sucesso.', 'juntaplay'));
        $this->active_section = null;

        do_action('juntaplay/profile/updated', $user_id, 'password', []);
    }

    private function update_two_factor(int $user_id): void
    {
        $method = isset($_POST['jp_profile_two_factor'])
            ? sanitize_key(wp_unslash($_POST['jp_profile_two_factor'])) // phpcs:ignore WordPress.Security.NonceVerification.Missing
            : 'off';

        if (!in_array($method, ['off', 'email', 'whatsapp'], true)) {
            $this->add_error('two_factor', __('Selecione uma opção válida.', 'juntaplay'));

            return;
        }

        update_user_meta($user_id, 'juntaplay_two_factor_method', $method);

        $this->invalidate_cache();
        $this->add_notice(
            $method === 'off'
                ? __('Verificação em duas etapas desativada.', 'juntaplay')
                : __('Verificação em duas etapas atualizada.', 'juntaplay')
        );
        $this->active_section = null;

        do_action('juntaplay/profile/updated', $user_id, 'two_factor', ['two_factor' => $method]);
    }

    private function update_login_alerts(int $user_id): void
    {
        $status = isset($_POST['jp_profile_login_alerts'])
            ? sanitize_key(wp_unslash($_POST['jp_profile_login_alerts'])) // phpcs:ignore WordPress.Security.NonceVerification.Missing
            : 'yes';

        if (!in_array($status, ['yes', 'no'], true)) {
            $this->add_error('login_alerts', __('Selecione uma opção válida.', 'juntaplay'));

            return;
        }

        update_user_meta($user_id, 'juntaplay_login_alerts', $status);

        $this->invalidate_cache();
        $this->add_notice(
            $status === 'no'
                ? __('Alertas de login desativados.', 'juntaplay')
                : __('Alertas de login ativados.', 'juntaplay')
        );
        $this->active_section = null;

        do_action('juntaplay/profile/updated', $user_id, 'login_alerts', ['login_alerts' => $status]);
    }

    private function update_credit_auto(int $user_id): void
    {
        $status = isset($_POST['jp_profile_credit_auto_status'])
            ? sanitize_key(wp_unslash($_POST['jp_profile_credit_auto_status'])) // phpcs:ignore WordPress.Security.NonceVerification.Missing
            : 'off';

        if ($status !== 'on') {
            $status = 'off';
        }

        $amount_raw = isset($_POST['jp_profile_credit_auto_amount'])
            ? wp_unslash($_POST['jp_profile_credit_auto_amount']) // phpcs:ignore WordPress.Security.NonceVerification.Missing
            : '';
        $threshold_raw = isset($_POST['jp_profile_credit_auto_threshold'])
            ? wp_unslash($_POST['jp_profile_credit_auto_threshold']) // phpcs:ignore WordPress.Security.NonceVerification.Missing
            : '';

        $amount    = is_string($amount_raw) ? $this->parse_decimal($amount_raw) : 0.0;
        $threshold = is_string($threshold_raw) ? $this->parse_decimal($threshold_raw) : 0.0;

        if ($status === 'on' && $amount <= 0) {
            $this->add_error('credit_auto', __('Informe um valor de recarga válido.', 'juntaplay'));

            return;
        }

        if ($status === 'on' && $threshold < 0) {
            $this->add_error('credit_auto', __('O saldo mínimo não pode ser negativo.', 'juntaplay'));

            return;
        }

        if ($amount < 0) {
            $amount = 0.0;
        }

        $threshold = max(0.0, $threshold);

        update_user_meta($user_id, 'juntaplay_credit_auto_status', $status);
        update_user_meta($user_id, 'juntaplay_credit_auto_amount', $this->store_decimal($amount));
        update_user_meta($user_id, 'juntaplay_credit_auto_threshold', $this->store_decimal($threshold));

        $this->invalidate_cache();
        $this->add_notice(__('Preferências de recarga automática atualizadas.', 'juntaplay'));
        $this->active_section = null;

        do_action(
            'juntaplay/profile/updated',
            $user_id,
            'credit_auto',
            [
                'status'    => $status,
                'amount'    => $amount,
                'threshold' => $threshold,
            ]
        );
    }

    private function update_credit_payment_method(int $user_id): void
    {
        $method = isset($_POST['jp_profile_credit_payment_method'])
            ? sanitize_key(wp_unslash($_POST['jp_profile_credit_payment_method'])) // phpcs:ignore WordPress.Security.NonceVerification.Missing
            : 'pix';

        if (!in_array($method, ['pix', 'card', 'boleto'], true)) {
            $this->add_error('credit_payment_method', __('Selecione uma forma de pagamento válida.', 'juntaplay'));

            return;
        }

        update_user_meta($user_id, 'juntaplay_credit_payment_method', $method);

        $this->invalidate_cache();
        $this->add_notice(__('Forma de pagamento preferida atualizada.', 'juntaplay'));
        $this->active_section = null;

        do_action('juntaplay/profile/updated', $user_id, 'credit_payment_method', ['method' => $method]);
    }

    private function update_credit_pix_key(int $user_id): void
    {
        $key = isset($_POST['jp_profile_credit_pix_key'])
            ? sanitize_text_field(wp_unslash($_POST['jp_profile_credit_pix_key'])) // phpcs:ignore WordPress.Security.NonceVerification.Missing
            : '';

        $key = trim($key);

        if ($key !== '' && strlen($key) < 6) {
            $this->add_error('credit_pix_key', __('Informe uma chave Pix válida.', 'juntaplay'));

            return;
        }

        update_user_meta($user_id, 'juntaplay_credit_pix_key', $key);

        $this->invalidate_cache();
        $this->add_notice(
            $key === ''
                ? __('Chave Pix removida.', 'juntaplay')
                : __('Chave Pix atualizada com sucesso.', 'juntaplay')
        );
        $this->active_section = null;

        do_action('juntaplay/profile/updated', $user_id, 'credit_pix_key', ['key' => $key]);
    }

    private function update_credit_bank_account(int $user_id): void
    {
        $holder = isset($_POST['jp_profile_credit_bank_holder'])
            ? sanitize_text_field(wp_unslash($_POST['jp_profile_credit_bank_holder'])) // phpcs:ignore WordPress.Security.NonceVerification.Missing
            : '';
        $document_raw = isset($_POST['jp_profile_credit_bank_document'])
            ? wp_unslash($_POST['jp_profile_credit_bank_document']) // phpcs:ignore WordPress.Security.NonceVerification.Missing
            : '';
        $document = is_string($document_raw) ? preg_replace('/\D+/', '', $document_raw) : '';
        $bank_type = isset($_POST['jp_profile_credit_bank_type'])
            ? sanitize_key(wp_unslash($_POST['jp_profile_credit_bank_type'])) // phpcs:ignore WordPress.Security.NonceVerification.Missing
            : 'pf';
        $bank = isset($_POST['jp_profile_credit_bank_name'])
            ? sanitize_text_field(wp_unslash($_POST['jp_profile_credit_bank_name'])) // phpcs:ignore WordPress.Security.NonceVerification.Missing
            : '';
        $agency_raw = isset($_POST['jp_profile_credit_bank_agency'])
            ? wp_unslash($_POST['jp_profile_credit_bank_agency']) // phpcs:ignore WordPress.Security.NonceVerification.Missing
            : '';
        $agency = is_string($agency_raw) ? preg_replace('/[^0-9-]/', '', $agency_raw) : '';
        $account_raw = isset($_POST['jp_profile_credit_bank_account'])
            ? wp_unslash($_POST['jp_profile_credit_bank_account']) // phpcs:ignore WordPress.Security.NonceVerification.Missing
            : '';
        $account = is_string($account_raw) ? preg_replace('/[^0-9-]/', '', $account_raw) : '';
        $account_type = isset($_POST['jp_profile_credit_bank_account_type'])
            ? sanitize_key(wp_unslash($_POST['jp_profile_credit_bank_account_type'])) // phpcs:ignore WordPress.Security.NonceVerification.Missing
            : 'checking';

        if (!in_array($bank_type, ['pf', 'pj'], true)) {
            $bank_type = 'pf';
        }

        if (!in_array($account_type, ['checking', 'savings'], true)) {
            $account_type = 'checking';
        }

        $all_empty = $holder === '' && ($document === '' || $document === null)
            && $bank === '' && $agency === '' && $account === '';

        if ($all_empty) {
            update_user_meta($user_id, 'juntaplay_credit_bank_holder', '');
            update_user_meta($user_id, 'juntaplay_credit_bank_document', '');
            update_user_meta($user_id, 'juntaplay_credit_bank_type', 'pf');
            update_user_meta($user_id, 'juntaplay_credit_bank_name', '');
            update_user_meta($user_id, 'juntaplay_credit_bank_agency', '');
            update_user_meta($user_id, 'juntaplay_credit_bank_account', '');
            update_user_meta($user_id, 'juntaplay_credit_bank_account_type', 'checking');

            $this->invalidate_cache();
            $this->add_notice(__('Dados bancários removidos.', 'juntaplay'));
            $this->active_section = null;

            do_action('juntaplay/profile/updated', $user_id, 'credit_bank_account', ['removed' => true]);

            return;
        }

        if ($holder === '' || $bank === '' || $agency === '' || $account === '') {
            $this->add_error('credit_bank_account', __('Preencha todos os campos obrigatórios.', 'juntaplay'));

            return;
        }

        if (!is_string($document) || $document === '' || !in_array(strlen($document), [11, 14], true)) {
            $this->add_error('credit_bank_account', __('Informe um CPF ou CNPJ válido do titular.', 'juntaplay'));

            return;
        }

        update_user_meta($user_id, 'juntaplay_credit_bank_holder', $holder);
        update_user_meta($user_id, 'juntaplay_credit_bank_document', $document);
        update_user_meta($user_id, 'juntaplay_credit_bank_type', $bank_type);
        update_user_meta($user_id, 'juntaplay_credit_bank_name', $bank);
        update_user_meta($user_id, 'juntaplay_credit_bank_agency', $agency);
        update_user_meta($user_id, 'juntaplay_credit_bank_account', $account);
        update_user_meta($user_id, 'juntaplay_credit_bank_account_type', $account_type);

        $this->invalidate_cache();
        $this->add_notice(__('Dados bancários atualizados.', 'juntaplay'));
        $this->active_section = null;

        do_action(
            'juntaplay/profile/updated',
            $user_id,
            'credit_bank_account',
            [
                'bank'          => $bank,
                'agency'        => $agency,
                'account'       => $account,
                'account_type'  => $account_type,
                'bank_type'     => $bank_type,
                'holder'        => $holder,
                'document'      => $document,
            ]
        );
    }

    private function submit_credit_withdrawal_form(int $user_id): void
    {
        $amount_raw = isset($_POST['jp_profile_withdraw_amount'])
            ? wp_unslash($_POST['jp_profile_withdraw_amount']) // phpcs:ignore WordPress.Security.NonceVerification.Missing
            : '';
        $method = isset($_POST['jp_profile_withdraw_method'])
            ? sanitize_key(wp_unslash($_POST['jp_profile_withdraw_method'])) // phpcs:ignore WordPress.Security.NonceVerification.Missing
            : 'pix';
        $code = isset($_POST['jp_profile_withdraw_code'])
            ? sanitize_text_field(wp_unslash($_POST['jp_profile_withdraw_code'])) // phpcs:ignore WordPress.Security.NonceVerification.Missing
            : '';

        $result = $this->handle_withdrawal_request($user_id, [
            'amount' => $amount_raw,
            'method' => $method,
            'code'   => $code,
            'context' => 'form',
        ]);

        if (!empty($result['error'])) {
            $this->add_error('credit_withdrawal', (string) $result['error']);
            $this->active_section = 'credit_history';

            return;
        }

        $this->notices[] = (string) ($result['message'] ?? __('Solicitação registrada com sucesso.', 'juntaplay'));
        $this->active_section = null;
    }

    private function create_group(int $user_id): void
    {
        $this->group_create_success = null;

        $name_raw        = isset($_POST['jp_profile_group_name']) ? wp_unslash($_POST['jp_profile_group_name']) : '';
        $pool_raw        = isset($_POST['jp_profile_group_pool']) ? wp_unslash($_POST['jp_profile_group_pool']) : '';
        $description_raw = isset($_POST['jp_profile_group_description']) ? wp_unslash($_POST['jp_profile_group_description']) : '';
        $service_raw     = isset($_POST['jp_profile_group_service']) ? wp_unslash($_POST['jp_profile_group_service']) : '';
        $service_url_raw = isset($_POST['jp_profile_group_service_url']) ? wp_unslash($_POST['jp_profile_group_service_url']) : '';
        $rules_raw       = isset($_POST['jp_profile_group_rules']) ? wp_unslash($_POST['jp_profile_group_rules']) : '';
        $price_raw       = isset($_POST['jp_profile_group_price']) ? wp_unslash($_POST['jp_profile_group_price']) : '';
        $promo_toggle    = isset($_POST['jp_profile_group_promo_toggle']) ? wp_unslash($_POST['jp_profile_group_promo_toggle']) : '';
        $promo_raw       = isset($_POST['jp_profile_group_price_promo']) ? wp_unslash($_POST['jp_profile_group_price_promo']) : '';
        $total_raw       = isset($_POST['jp_profile_group_slots_total']) ? wp_unslash($_POST['jp_profile_group_slots_total']) : '';
        $reserved_raw    = isset($_POST['jp_profile_group_slots_reserved']) ? wp_unslash($_POST['jp_profile_group_slots_reserved']) : '';
        $member_raw      = isset($_POST['jp_profile_group_member_price']) ? wp_unslash($_POST['jp_profile_group_member_price']) : '';
        $support_raw     = isset($_POST['jp_profile_group_support']) ? wp_unslash($_POST['jp_profile_group_support']) : '';
        $delivery_raw    = isset($_POST['jp_profile_group_delivery']) ? wp_unslash($_POST['jp_profile_group_delivery']) : '';
        $access_raw      = isset($_POST['jp_profile_group_access']) ? wp_unslash($_POST['jp_profile_group_access']) : '';
        $access_url_raw  = isset($_POST['jp_profile_group_access_url']) ? wp_unslash($_POST['jp_profile_group_access_url']) : '';
        $access_login_raw = isset($_POST['jp_profile_group_access_login']) ? wp_unslash($_POST['jp_profile_group_access_login']) : '';
        $access_password_raw = isset($_POST['jp_profile_group_access_password']) ? wp_unslash($_POST['jp_profile_group_access_password']) : '';
        $access_notes_raw = isset($_POST['jp_profile_group_access_notes']) ? wp_unslash($_POST['jp_profile_group_access_notes']) : '';
        $access_obs_raw   = isset($_POST['jp_profile_group_access_observations']) ? wp_unslash($_POST['jp_profile_group_access_observations']) : '';
        $relationship_raw = isset($_POST['jp_profile_group_relationship']) ? wp_unslash($_POST['jp_profile_group_relationship']) : '';
        $access_timing_raw = isset($_POST['jp_profile_group_access_timing']) ? wp_unslash($_POST['jp_profile_group_access_timing']) : '';
        $category_raw    = isset($_POST['jp_profile_group_category']) ? wp_unslash($_POST['jp_profile_group_category']) : '';
        $instant_raw     = isset($_POST['jp_profile_group_instant']) ? wp_unslash($_POST['jp_profile_group_instant']) : '';
        $icon_raw        = isset($_POST['jp_profile_group_icon']) ? wp_unslash($_POST['jp_profile_group_icon']) : '';

        $name        = sanitize_text_field(is_string($name_raw) ? $name_raw : '');
        $service     = sanitize_text_field(is_string($service_raw) ? $service_raw : '');
        $service_url = esc_url_raw(is_string($service_url_raw) ? $service_url_raw : '');
        $rules       = sanitize_textarea_field(is_string($rules_raw) ? $rules_raw : '');
        $pool_id     = absint(is_string($pool_raw) ? $pool_raw : 0);
        $description = sanitize_textarea_field(is_string($description_raw) ? $description_raw : '');
        $support     = sanitize_text_field(is_string($support_raw) ? $support_raw : '');
        $delivery_value = sanitize_text_field(is_string($delivery_raw) ? $delivery_raw : '');
        $access      = sanitize_text_field(is_string($access_raw) ? $access_raw : '');
        $access_url  = esc_url_raw(is_string($access_url_raw) ? $access_url_raw : '');
        $access_login = sanitize_text_field(is_string($access_login_raw) ? $access_login_raw : '');
        $access_password = sanitize_text_field(is_string($access_password_raw) ? $access_password_raw : '');
        $access_notes = sanitize_textarea_field(is_string($access_notes_raw) ? $access_notes_raw : '');
        $access_observations = sanitize_textarea_field(is_string($access_obs_raw) ? $access_obs_raw : '');
        $relationship_input = sanitize_key(is_string($relationship_raw) ? $relationship_raw : '');
        $relationship_options = Groups::get_relationship_options();
        $relationship = array_key_exists($relationship_input, $relationship_options) ? $relationship_input : '';
        $icon_id      = absint(is_string($icon_raw) ? $icon_raw : 0);
        $access_timing = sanitize_key(is_string($access_timing_raw) ? $access_timing_raw : '');
        if (!in_array($access_timing, ['immediate', 'scheduled'], true)) {
            $access_timing = 'scheduled';
        }
        $category_input = sanitize_key(is_string($category_raw) ? $category_raw : '');
        $categories     = array_keys($this->get_group_categories());
        $category       = in_array($category_input, $categories, true) ? $category_input : 'other';
        $instant_access = ($access_timing === 'immediate') || (is_string($instant_raw) ? sanitize_key((string) $instant_raw) === 'on' : false);
        $delivery       = $access_timing === 'immediate'
            ? __('Imediatamente após a confirmação', 'juntaplay')
            : (($delivery_value !== '' && !in_array($delivery_value, ['immediate', 'scheduled'], true))
                ? $delivery_value
                : __('Após liberação manual do administrador', 'juntaplay'));

        $price_value   = $this->parse_money(is_string($price_raw) ? (string) $price_raw : '');
        $promo_enabled = is_string($promo_toggle) ? sanitize_key((string) $promo_toggle) === 'on' : false;
        $promo_value   = $promo_enabled ? $this->parse_money(is_string($promo_raw) ? (string) $promo_raw : '') : 0.0;
        $promo_value   = $promo_enabled && $promo_value > 0 ? $promo_value : 0.0;

        $slots_total    = absint(is_string($total_raw) ? $total_raw : 0);
        $slots_reserved = absint(is_string($reserved_raw) ? $reserved_raw : 0);

        $member_input_provided = is_string($member_raw) && trim((string) $member_raw) !== '';
        $member_value          = $member_input_provided
            ? $this->parse_money((string) $member_raw)
            : 0.0;

        $available_slots = max(1, $slots_total - $slots_reserved);
        $price_basis     = $promo_value > 0 ? $promo_value : $price_value;
        if ($member_value <= 0) {
            $member_value = $available_slots > 0 ? $price_basis / (float) $available_slots : 0.0;
        }

        $this->group_draft = [
            'name'          => $name,
            'service'       => $service,
            'service_url'   => $service_url,
            'rules'         => $rules,
            'pool'          => $pool_id > 0 ? (string) $pool_id : '',
            'description'   => $description,
            'price'         => $this->money_to_input($price_value),
            'promo_enabled' => $promo_enabled ? 'on' : 'off',
            'promo'         => $promo_enabled && $promo_value > 0 ? $this->money_to_input($promo_value) : '',
            'total'         => $slots_total > 0 ? (string) $slots_total : '',
            'reserved'      => $slots_reserved > 0 ? (string) $slots_reserved : '',
            'member_price'  => $member_value > 0 ? $this->money_to_input($member_value) : '',
            'member_generated' => $member_input_provided ? 'no' : 'yes',
            'support'        => $support,
            'delivery'       => $delivery,
            'access'         => $access,
            'access_url'     => $access_url,
            'access_login'   => $access_login,
            'access_password'=> $access_password,
            'access_notes'   => $access_notes,
            'access_observations' => $access_observations,
            'category'       => $category,
            'instant_access' => $instant_access ? 'on' : 'off',
            'relationship'   => $relationship,
            'access_timing'  => $access_timing,
            'icon'           => $icon_id > 0 ? (string) $icon_id : '',
        ];

        if ($name === '' || strlen($name) < 3) {
            $this->add_error('group_create', __('Informe um nome para o grupo com pelo menos 3 caracteres.', 'juntaplay'));

            return;
        }

        if ($service === '' || strlen($service) < 3) {
            $this->add_error('group_create', __('Descreva qual serviço ou assinatura será compartilhado no grupo.', 'juntaplay'));

            return;
        }

        if ($price_value <= 0) {
            $this->add_error('group_create', __('Informe o valor mensal do serviço para calcular as cotas.', 'juntaplay'));

            return;
        }

        if ($slots_total <= 0) {
            $this->add_error('group_create', __('Defina a quantidade de vagas disponíveis para o grupo.', 'juntaplay'));

            return;
        }

        if ($slots_reserved >= $slots_total) {
            $this->add_error('group_create', __('As vagas reservadas para você precisam ser menores que o total disponível.', 'juntaplay'));

            return;
        }

        if ($member_value <= 0) {
            $this->add_error('group_create', __('Revise o valor cobrado dos membros antes de enviar para análise.', 'juntaplay'));

            return;
        }

        if ($support === '') {
            $this->add_error('group_create', __('Informe como os membros receberão suporte (ex.: e-mail, WhatsApp).', 'juntaplay'));

            return;
        }

        if ($delivery === '') {
            $this->add_error('group_create', __('Indique em quanto tempo o acesso será liberado aos participantes.', 'juntaplay'));

            return;
        }

        if ($access === '') {
            $this->add_error('group_create', __('Explique qual será a forma de acesso enviada aos membros.', 'juntaplay'));

            return;
        }

        if ($relationship === '') {
            $this->add_error('group_create', __('Selecione a relação com o administrador.', 'juntaplay'));

            return;
        }

        if ($access_timing === 'immediate' && ($access_login === '' || $access_password === '')) {
            $this->add_error('group_create', __('Preencha login e senha compartilhados para liberar acesso imediato.', 'juntaplay'));

            return;
        }

        $pool_title = '';
        if ($pool_id > 0) {
            $pool = Pools::get($pool_id);
            if (!$pool) {
                $this->add_error('group_create', __('Selecione um grupo válido para vincular.', 'juntaplay'));

                return;
            }

            $pool_title = isset($pool->title) ? (string) $pool->title : '';
        }

        $auto_approved = $pool_id > 0;

        $group_id = Groups::create([
            'owner_id'          => $user_id,
            'pool_id'           => $pool_id,
            'title'             => $name,
            'service_name'      => $service,
            'service_url'       => $service_url,
            'rules'             => $rules,
            'description'       => $description,
            'price_regular'     => $price_value,
            'price_promotional' => $promo_value > 0 ? $promo_value : null,
            'member_price'      => $member_value,
            'slots_total'       => $slots_total,
            'slots_reserved'    => $slots_reserved,
            'support_channel'   => $support,
            'delivery_time'     => $delivery,
            'access_method'     => $access,
            'access_url'        => $access_url,
            'access_login'      => $access_login,
            'access_password'   => $access_password,
            'access_notes'      => $access_notes,
            'access_observations' => $access_observations,
            'relationship_type' => $relationship,
            'category'          => $category,
            'instant_access'    => $instant_access,
            'cover_id'          => $icon_id,
        ]);

        if ($group_id <= 0) {
            $this->add_error('group_create', __('Não foi possível criar o grupo agora. Tente novamente em instantes.', 'juntaplay'));

            return;
        }

        $validation_code = Groups::generate_email_validation_code($group_id) ?? '';

        GroupMembers::add($group_id, $user_id, 'owner', 'active');

        $this->invalidate_cache();
        $this->group_draft = [];

        $success_heading  = $auto_approved
            ? __('Grupo publicado com sucesso!', 'juntaplay')
            : __('Grupo cadastrado com sucesso!', 'juntaplay');
        $success_body     = $auto_approved
            ? __('Seu grupo pré-aprovado foi liberado automaticamente para convidar participantes.', 'juntaplay')
            : __('Aguarde que nossa equipe vai validar e você será notificado.', 'juntaplay');
        $success_redirect = 'https://www.juntaplay.com.br/grupos';
        $success_image    = '';

        if (defined('JP_URL') && JP_URL !== '') {
            $success_image = trailingslashit(JP_URL) . 'assets/images/agradecimento.gif';
        }

        if ($success_image === '') {
            $success_image = plugins_url('assets/images/agradecimento.gif', JP_FILE);
        }

        $success_data = [
            'heading'  => $success_heading,
            'message'  => trim($success_heading . ' ' . $success_body),
            'body'     => $success_body,
            'redirect' => $success_redirect,
        ];

        if ($success_image !== '') {
            $success_data['image'] = $success_image;
        }

        $this->group_create_success = $success_data;

        $this->add_notice(
            $auto_approved
                ? __('Seu grupo foi aprovado automaticamente e já está ativo.', 'juntaplay')
                : __('Seu grupo foi enviado para análise. Você receberá um e-mail quando houver uma decisão.', 'juntaplay')
        );
        $this->active_section = null;

        do_action('juntaplay/profile/groups/created', $user_id, $group_id, [
            'title'             => $name,
            'pool_id'           => $pool_id,
            'description'       => $description,
            'service_name'      => $service,
            'service_url'       => $service_url,
            'rules'             => $rules,
            'price_regular'     => $price_value,
            'price_promotional' => $promo_value > 0 ? $promo_value : null,
            'member_price'      => $member_value,
            'slots_total'       => $slots_total,
            'slots_reserved'    => $slots_reserved,
            'support_channel'   => $support,
            'delivery_time'     => $delivery,
            'access_method'     => $access,
            'access_url'        => $access_url,
            'access_login'      => $access_login,
            'access_password'   => $access_password,
            'access_notes'      => $access_notes,
            'access_observations' => $access_observations,
            'relationship_type' => $relationship,
            'access_timing'     => $access_timing,
            'category'          => $category,
            'instant_access'    => $instant_access,
            'validation_code'   => $validation_code,
        ]);
    }

    private function submit_group_complaint(int $user_id): void
    {
        $this->active_section = 'groups';

        $group_raw   = isset($_POST['jp_profile_complaint_group']) ? wp_unslash($_POST['jp_profile_complaint_group']) : '';
        $reason_raw  = isset($_POST['jp_profile_complaint_reason']) ? wp_unslash($_POST['jp_profile_complaint_reason']) : '';
        $message_raw = isset($_POST['jp_profile_complaint_message']) ? wp_unslash($_POST['jp_profile_complaint_message']) : '';
        $order_raw   = isset($_POST['jp_profile_complaint_order']) ? wp_unslash($_POST['jp_profile_complaint_order']) : '';

        $group_id = absint($group_raw);
        $reason   = sanitize_key($reason_raw);
        $message  = sanitize_textarea_field($message_raw);
        $order_id = absint($order_raw);

        $errors = [];
        $group_title = '';

        if ($group_id <= 0) {
            $errors[] = __('Selecione um grupo válido para abrir a reclamação.', 'juntaplay');
        }

        $group = null;
        if (!$errors) {
            $group = Groups::get($group_id);
            if (!$group) {
                $errors[] = __('Não foi possível identificar o grupo informado.', 'juntaplay');
            } else {
                $group_title = isset($group->title) ? (string) $group->title : '';
            }
        }

        if (!$errors && !GroupMembers::user_has_membership($group_id, $user_id)) {
            $errors[] = __('Você não participa deste grupo.', 'juntaplay');
        }

        $reasons = GroupComplaints::get_reasons();
        if (!isset($reasons[$reason])) {
            $reason = 'other';
        }

        if ($message === '' || strlen($message) < 15) {
            $errors[] = __('Descreva o que aconteceu com pelo menos 15 caracteres.', 'juntaplay');
        }

        $limits    = $this->get_complaint_limits();
        $max_files = (int) ($limits['max_files'] ?? 3);
        $max_size  = (int) ($limits['max_size'] ?? 5 * 1024 * 1024);

        $this->group_complaint_draft[$group_id] = [
            'reason'  => $reason,
            'message' => $message,
            'order'   => $order_id > 0 ? (string) $order_id : '',
        ];

        $attachment_ids = [];
        $files          = $_FILES['jp_profile_complaint_attachments'] ?? null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

        if (!$errors && is_array($files) && isset($files['name'])) {
            $names = array_filter((array) $files['name'], static fn($name): bool => (string) $name !== '');
            if ($max_files > 0 && count($names) > $max_files) {
                $errors[] = sprintf(
                    _n('Envie no máximo %d arquivo de evidência.', 'Envie no máximo %d arquivos de evidência.', $max_files, 'juntaplay'),
                    $max_files
                );
            }
        }

        if (!$errors && is_array($files) && isset($files['name'])) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';

            foreach ((array) $files['name'] as $index => $filename) {
                if ($filename === '') {
                    continue;
                }

                $size       = isset($files['size'][$index]) ? (int) $files['size'][$index] : 0;
                $error_code = isset($files['error'][$index]) ? (int) $files['error'][$index] : UPLOAD_ERR_OK;

                if ($max_size > 0 && $size > $max_size) {
                    $errors[] = sprintf(
                        __('O arquivo %1$s ultrapassa o limite de %2$s MB.', 'juntaplay'),
                        sanitize_text_field((string) $filename),
                        number_format_i18n(max(1, $max_size / 1048576), 1)
                    );
                    continue;
                }

                if ($error_code !== UPLOAD_ERR_OK) {
                    $errors[] = sprintf(
                        __('Não foi possível enviar o arquivo %s. Tente novamente.', 'juntaplay'),
                        sanitize_text_field((string) $filename)
                    );
                    continue;
                }

                $file_array = [
                    'name'     => $filename,
                    'type'     => $files['type'][$index] ?? '',
                    'tmp_name' => $files['tmp_name'][$index] ?? '',
                    'error'    => $error_code,
                    'size'     => $size,
                ];

                $_FILES['jp_profile_complaint_file'] = $file_array; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

                $attachment_id = media_handle_upload('jp_profile_complaint_file', 0);

                if (is_wp_error($attachment_id)) {
                    $errors[] = sprintf(
                        __('Não foi possível salvar o arquivo %1$s: %2$s', 'juntaplay'),
                        sanitize_text_field((string) $filename),
                        $this->format_upload_error($attachment_id)
                    );
                } elseif ($attachment_id) {
                    $attachment_ids[] = (int) $attachment_id;
                }
            }

            unset($_FILES['jp_profile_complaint_file']);
        }

        if ($errors) {
            foreach ($attachment_ids as $attachment_id) {
                wp_delete_attachment($attachment_id, true);
            }

            foreach ($errors as $message_error) {
                $this->add_error('group_complaint_' . $group_id, $message_error);
            }

            return;
        }

        $complaint_id = GroupComplaints::create([
            'group_id'    => $group_id,
            'user_id'     => $user_id,
            'order_id'    => $order_id,
            'reason'      => $reason,
            'message'     => $message,
            'attachments' => $attachment_ids,
            'status'      => GroupComplaints::STATUS_OPEN,
        ]);

        if ($complaint_id <= 0) {
            foreach ($attachment_ids as $attachment_id) {
                wp_delete_attachment($attachment_id, true);
            }

            $this->add_error('group_complaint_' . $group_id, __('Não foi possível registrar sua reclamação agora. Tente novamente em instantes.', 'juntaplay'));

            return;
        }

        $this->group_complaint_success[$group_id] = [
            sprintf(__('Reclamação #%d enviada. Você receberá atualizações por e-mail.', 'juntaplay'), $complaint_id),
        ];

        unset($this->group_complaint_draft[$group_id]);

        $this->invalidate_cache();

        $summary = GroupComplaints::get_summary_for_user($user_id, [$group_id]);
        $decorated = $this->decorate_group_complaint_summary($summary);
        if (isset($decorated[$group_id])) {
            $this->group_complaint_summary[$group_id] = $decorated[$group_id];
        }

        do_action('juntaplay/groups/complaint_created', $complaint_id, $group_id, $user_id, [
            'reason'      => $reason,
            'message'     => $message,
            'order_id'    => $order_id,
            'attachments' => $attachment_ids,
            'group_title' => $group_title,
        ]);
    }

    private function cancel_group_membership(int $user_id): void
    {
        $this->active_section = 'groups';

        $group_raw  = isset($_POST['jp_profile_group_cancel']) ? wp_unslash($_POST['jp_profile_group_cancel']) : '';
        $reason_raw = isset($_POST['jp_profile_group_cancel_reason']) ? wp_unslash($_POST['jp_profile_group_cancel_reason']) : '';
        $nonce      = isset($_POST['jp_profile_group_cancel_nonce']) ? wp_unslash($_POST['jp_profile_group_cancel_nonce']) : '';

        if (!wp_verify_nonce($nonce, 'jp_profile_group_cancel')) {
            $this->add_error('group_cancel', __('Sua sessão expirou. Atualize a página e tente novamente.', 'juntaplay'));

            return;
        }

        $group_id = absint($group_raw);
        if ($group_id <= 0) {
            $this->add_error('group_cancel', __('Selecione um grupo válido para cancelar sua participação.', 'juntaplay'));

            return;
        }

        $membership = GroupMembers::get_membership($group_id, $user_id);
        if (!$membership) {
            $this->add_error('group_cancel_' . $group_id, __('Você não participa deste grupo ou a participação já foi removida.', 'juntaplay'));

            return;
        }

        $status = isset($membership['status']) ? (string) $membership['status'] : 'active';
        if ($status === 'canceled') {
            $this->add_notice(__('Sua participação neste grupo já está cancelada.', 'juntaplay'));

            return;
        }

        $role = isset($membership['role']) ? (string) $membership['role'] : 'member';
        if ($role === 'owner') {
            $this->add_error('group_cancel_' . $group_id, __('Você é o administrador deste grupo. Use as ferramentas de gerenciamento para encerrar ou transferir a administração.', 'juntaplay'));

            return;
        }

        $reason = sanitize_textarea_field($reason_raw);
        if ($reason === '' || strlen($reason) < 10) {
            $this->add_error('group_cancel_' . $group_id, __('Informe um motivo com pelo menos 10 caracteres para cancelar sua participação.', 'juntaplay'));

            return;
        }

        if (!GroupMembers::update_status($group_id, $user_id, 'canceled')) {
            $this->add_error('group_cancel_' . $group_id, __('Não foi possível cancelar sua participação agora. Tente novamente em instantes.', 'juntaplay'));

            return;
        }

        $event = [
            'message'    => '',
            'metadata'   => ['reason' => $reason],
            'created_at' => current_time('mysql'),
        ];

        GroupMembershipEvents::log($group_id, $user_id, GroupMembershipEvents::TYPE_CANCEL, '', ['reason' => $reason]);
        $this->group_cancellation_events[$group_id] = $event;

        $this->invalidate_cache();
        $this->add_notice(__('Participação cancelada com sucesso.', 'juntaplay'));

        do_action('juntaplay/group_members/canceled', $group_id, $user_id, [
            'reason' => $reason,
        ]);
    }

    private function submit_complaint_message(int $user_id, string $type): void
    {
        $this->active_section = self::SECTION_COMPLAINTS;

        $complaint_raw = isset($_POST['jp_profile_complaint_id']) ? wp_unslash($_POST['jp_profile_complaint_id']) : '';
        $group_raw     = isset($_POST['jp_profile_complaint_group']) ? wp_unslash($_POST['jp_profile_complaint_group']) : '';
        $nonce         = isset($_POST['jp_profile_complaint_action_nonce']) ? wp_unslash($_POST['jp_profile_complaint_action_nonce']) : '';

        if (!wp_verify_nonce($nonce, 'jp_profile_complaint_action')) {
            $this->add_complaint_error(0, __('Sua sessão expirou. Atualize a página e tente novamente.', 'juntaplay'));

            return;
        }

        $complaint_id = absint($complaint_raw);
        $group_id     = absint($group_raw);

        if ($complaint_id <= 0 || $group_id <= 0) {
            $this->add_complaint_error($complaint_id, __('Não foi possível identificar a reclamação selecionada.', 'juntaplay'));

            return;
        }

        $complaint = GroupComplaints::get($complaint_id);
        if (!$complaint || (int) ($complaint['group_id'] ?? 0) !== $group_id) {
            $this->add_complaint_error($complaint_id, __('Reclamação não encontrada.', 'juntaplay'));

            return;
        }

        $owner_id  = isset($complaint['owner_id']) ? (int) $complaint['owner_id'] : 0;
        $author_id = isset($complaint['user_id']) ? (int) $complaint['user_id'] : 0;

        $role = '';
        if ($owner_id === $user_id) {
            $role = 'owner';
        } elseif ($author_id === $user_id) {
            $role = 'participant';
        }

        if ($role === '') {
            $this->add_complaint_error($complaint_id, __('Você não tem permissão para responder esta reclamação.', 'juntaplay'));

            return;
        }

        $message_raw = isset($_POST['jp_profile_complaint_message']) ? wp_unslash($_POST['jp_profile_complaint_message']) : '';
        $message     = sanitize_textarea_field($message_raw);

        if ($message === '' || strlen($message) < 5) {
            $this->add_complaint_error($complaint_id, __('Descreva sua mensagem com pelo menos 5 caracteres.', 'juntaplay'));

            return;
        }

        $limits    = $this->get_complaint_limits();
        $max_files = (int) ($limits['max_files'] ?? 3);
        $max_size  = (int) ($limits['max_size'] ?? 5 * 1024 * 1024);

        $attachment_ids = [];
        $files          = $_FILES['jp_profile_complaint_message_files'] ?? null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

        if (is_array($files) && isset($files['name'])) {
            $names = array_filter((array) $files['name'], static fn($name): bool => (string) $name !== '');
            if ($max_files > 0 && count($names) > $max_files) {
                $this->add_complaint_error($complaint_id, sprintf(
                    _n('Envie no máximo %d arquivo de evidência.', 'Envie no máximo %d arquivos de evidência.', $max_files, 'juntaplay'),
                    $max_files
                ));

                return;
            }

            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';

            foreach ((array) $files['name'] as $index => $filename) {
                if ($filename === '') {
                    continue;
                }

                $size       = isset($files['size'][$index]) ? (int) $files['size'][$index] : 0;
                $error_code = isset($files['error'][$index]) ? (int) $files['error'][$index] : UPLOAD_ERR_OK;

                if ($max_size > 0 && $size > $max_size) {
                    $this->add_complaint_error($complaint_id, sprintf(
                        __('O arquivo %1$s ultrapassa o limite de %2$s MB.', 'juntaplay'),
                        sanitize_text_field((string) $filename),
                        number_format_i18n(max(1, $max_size / 1048576), 1)
                    ));
                    continue;
                }

                if ($error_code !== UPLOAD_ERR_OK) {
                    $this->add_complaint_error($complaint_id, sprintf(
                        __('Não foi possível enviar o arquivo %s. Tente novamente.', 'juntaplay'),
                        sanitize_text_field((string) $filename)
                    ));
                    continue;
                }

                $file_array = [
                    'name'     => $filename,
                    'type'     => $files['type'][$index] ?? '',
                    'tmp_name' => $files['tmp_name'][$index] ?? '',
                    'error'    => $error_code,
                    'size'     => $size,
                ];

                $_FILES['jp_profile_complaint_temp'] = $file_array; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

                $attachment_id = media_handle_upload('jp_profile_complaint_temp', 0);

                if (is_wp_error($attachment_id)) {
                    $this->add_complaint_error($complaint_id, sprintf(
                        __('Não foi possível salvar o arquivo %1$s: %2$s', 'juntaplay'),
                        sanitize_text_field((string) $filename),
                        $this->format_upload_error($attachment_id)
                    ));
                } elseif ($attachment_id) {
                    $attachment_ids[] = (int) $attachment_id;
                }
            }

            unset($_FILES['jp_profile_complaint_temp']);

            if (!empty($this->complaint_center_errors[$complaint_id])) {
                foreach ($attachment_ids as $attachment_id) {
                    wp_delete_attachment($attachment_id, true);
                }

                return;
            }
        }

        $message_id = GroupComplaintMessages::add($complaint_id, $user_id, $type, $message, $attachment_ids);

        if ($message_id <= 0) {
            foreach ($attachment_ids as $attachment_id) {
                wp_delete_attachment($attachment_id, true);
            }

            $this->add_complaint_error($complaint_id, __('Não foi possível enviar sua mensagem agora. Tente novamente em instantes.', 'juntaplay'));

            return;
        }

        $this->invalidate_cache();
        $this->complaint_center_state = null;

        if ($type === GroupComplaintMessages::TYPE_PROPOSAL) {
            $this->add_complaint_success($complaint_id, __('Proposta registrada com sucesso.', 'juntaplay'));
            do_action('juntaplay/groups/complaint_proposal', $complaint_id, $group_id, $user_id, [
                'message'      => $message,
                'attachments'  => $attachment_ids,
                'role'         => $role,
            ]);
        } else {
            $this->add_complaint_success($complaint_id, __('Mensagem enviada com sucesso.', 'juntaplay'));
            do_action('juntaplay/groups/complaint_reply', $complaint_id, $group_id, $user_id, [
                'message'      => $message,
                'attachments'  => $attachment_ids,
                'role'         => $role,
            ]);
        }
    }

    private function accept_complaint_proposal(int $user_id): void
    {
        $this->active_section = self::SECTION_COMPLAINTS;

        $complaint_raw = isset($_POST['jp_profile_complaint_id']) ? wp_unslash($_POST['jp_profile_complaint_id']) : '';
        $group_raw     = isset($_POST['jp_profile_complaint_group']) ? wp_unslash($_POST['jp_profile_complaint_group']) : '';
        $nonce         = isset($_POST['jp_profile_complaint_accept_nonce']) ? wp_unslash($_POST['jp_profile_complaint_accept_nonce']) : '';

        if (!wp_verify_nonce($nonce, 'jp_profile_complaint_accept')) {
            $this->add_complaint_error(0, __('Sua sessão expirou. Atualize a página e tente novamente.', 'juntaplay'));

            return;
        }

        $complaint_id = absint($complaint_raw);
        $group_id     = absint($group_raw);

        if ($complaint_id <= 0 || $group_id <= 0) {
            $this->add_complaint_error($complaint_id, __('Não foi possível identificar a reclamação selecionada.', 'juntaplay'));

            return;
        }

        $complaint = GroupComplaints::get($complaint_id);
        if (!$complaint || (int) ($complaint['group_id'] ?? 0) !== $group_id) {
            $this->add_complaint_error($complaint_id, __('Reclamação não encontrada.', 'juntaplay'));

            return;
        }

        $author_id = isset($complaint['user_id']) ? (int) $complaint['user_id'] : 0;
        if ($author_id !== $user_id) {
            $this->add_complaint_error($complaint_id, __('Apenas o assinante que abriu a reclamação pode aceitar a proposta.', 'juntaplay'));

            return;
        }

        $note_raw = isset($_POST['jp_profile_complaint_accept_note']) ? wp_unslash($_POST['jp_profile_complaint_accept_note']) : '';
        $note     = sanitize_textarea_field($note_raw);

        $resolved = GroupComplaints::update_status($complaint_id, GroupComplaints::STATUS_RESOLVED, [
            'resolved_by' => $user_id,
            'resolved_at' => current_time('mysql'),
            'admin_note'  => $note !== '' ? $note : null,
        ]);

        if (!$resolved) {
            $this->add_complaint_error($complaint_id, __('Não foi possível atualizar o status da reclamação.', 'juntaplay'));

            return;
        }

        $summary_message = $note !== ''
            ? sprintf(__('Proposta aceita: %s', 'juntaplay'), $note)
            : __('Proposta aceita pelo participante.', 'juntaplay');

        GroupComplaintMessages::add($complaint_id, $user_id, GroupComplaintMessages::TYPE_SYSTEM, $summary_message, []);

        $this->invalidate_cache();
        $this->complaint_center_state = null;
        $this->add_complaint_success($complaint_id, __('Proposta aceita com sucesso. Entraremos em contato se forem necessárias novas etapas.', 'juntaplay'));

        do_action('juntaplay/groups/complaint_resolved', $complaint_id, $group_id, $user_id, [
            'note' => $note,
        ]);
    }

    public function register_rest_routes(): void
    {
        register_rest_route(
            'juntaplay/v1',
            '/groups/(?P<id>\\d+)',
            [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [$this, 'rest_get_group'],
                    'permission_callback' => [$this, 'rest_can_manage_group'],
                    'args'                => [
                        'id' => [
                            'type'              => 'integer',
                            'required'          => true,
                            'sanitize_callback' => 'absint',
                        ],
                    ],
                ],
                [
                    'methods'             => WP_REST_Server::EDITABLE,
                    'callback'            => [$this, 'rest_update_group'],
                    'permission_callback' => [$this, 'rest_can_manage_group'],
                    'args'                => [
                        'id' => [
                            'type'              => 'integer',
                            'required'          => true,
                            'sanitize_callback' => 'absint',
                        ],
                    ],
                ],
            ]
        );

        register_rest_route(
            'juntaplay/v1',
            '/diagnostics',
            [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [$this, 'rest_get_diagnostics'],
                    'permission_callback' => [$this, 'rest_require_login'],
                ],
                [
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => [$this, 'rest_update_diagnostics'],
                    'permission_callback' => [$this, 'rest_require_login'],
                ],
            ]
        );

        register_rest_route(
            'juntaplay/v1',
            '/diagnostics/flag',
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'rest_flag_diagnostics'],
                'permission_callback' => [$this, 'rest_require_login'],
            ]
        );
    }

    public function rest_require_login(WP_REST_Request $request): bool
    {
        return is_user_logged_in();
    }

    public function rest_can_manage_group(WP_REST_Request $request): bool
    {
        $group_id = absint($request['id']);

        return $this->user_can_manage_group($group_id);
    }

    public function rest_get_group(WP_REST_Request $request)
    {
        $group_id = absint($request['id']);
        $context  = $this->get_group_modal_context($group_id);

        if (!$context || empty($context['is_owner'])) {
            return new WP_REST_Response([
                'message' => __('Você não pode editar este grupo.', 'juntaplay'),
            ], 403);
        }

        $form = $this->render_group_edit_template(
            $context['group'],
            isset($context['categories']) && is_array($context['categories']) ? $context['categories'] : []
        );

        if ($form === '') {
            return new WP_REST_Response([
                'message' => __('Não foi possível carregar o formulário de edição.', 'juntaplay'),
            ], 500);
        }

        return new WP_REST_Response([
            'group' => $context['group'],
            'form'  => $form,
        ]);
    }

    public function rest_update_group(WP_REST_Request $request)
    {
        $group_id = absint($request['id']);
        $result   = $this->handle_group_update($group_id, $request->get_params(), $request->get_file_params());

        if (empty($result['success'])) {
            $status  = isset($result['status']) ? (int) $result['status'] : 400;
            $message = isset($result['message']) ? (string) $result['message'] : __('Não foi possível salvar as alterações do grupo.', 'juntaplay');

            return new WP_REST_Response([
                'message' => $message,
                'errors'  => isset($result['errors']) && is_array($result['errors']) ? $result['errors'] : [],
            ], $status);
        }

        return new WP_REST_Response([
            'message' => isset($result['message']) ? (string) $result['message'] : __('Grupo atualizado com sucesso.', 'juntaplay'),
            'group'   => isset($result['group']) && is_array($result['group']) ? $result['group'] : [],
        ]);
    }

    public function rest_get_diagnostics(WP_REST_Request $request)
    {
        $state  = $this->get_diagnostics_state();
        $checks = $this->normalize_diagnostics_checks($state);

        $passed = 0;
        $total  = count($checks);

        foreach ($checks as $value) {
            if ($value) {
                $passed++;
            }
        }

        $percent = $total > 0 ? (int) round(($passed / $total) * 100) : 0;

        return new WP_REST_Response([
            'checks'  => $checks,
            'passed'  => $passed,
            'total'   => $total,
            'percent' => $percent,
        ]);
    }

    public function rest_update_diagnostics(WP_REST_Request $request)
    {
        $payload = $request->get_json_params();
        if (!is_array($payload) || !isset($payload['checks'])) {
            $payload = $request->get_body_params();
        }

        $checks = [];
        if (isset($payload['checks']) && is_array($payload['checks'])) {
            foreach ($payload['checks'] as $key => $value) {
                $normalized_key = sanitize_key((string) $key);
                if ($normalized_key === '') {
                    continue;
                }
                $checks[$normalized_key] = (bool) filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                if ($checks[$normalized_key] === null) {
                    $checks[$normalized_key] = (bool) $value;
                }
            }
        }

        $state = $this->get_diagnostics_state();
        if (!isset($state['checks']) || !is_array($state['checks'])) {
            $state['checks'] = [];
        }

        foreach ($checks as $key => $value) {
            $state['checks'][$key] = (bool) $value;
        }

        $state['updated_at'] = time();
        $this->persist_diagnostics_state($state);

        return $this->rest_get_diagnostics($request);
    }

    public function rest_flag_diagnostics(WP_REST_Request $request)
    {
        $type   = sanitize_key((string) $request->get_param('type'));
        $status = $request->get_param('status');

        if ($type === '') {
            return new WP_REST_Response([
                'message' => __('Tipo de diagnóstico inválido.', 'juntaplay'),
            ], 400);
        }

        $flagged = !($status === '0' || $status === 0 || $status === false || $status === 'false');

        $state = $this->get_diagnostics_state();
        if (!isset($state['flags']) || !is_array($state['flags'])) {
            $state['flags'] = [];
        }

        $state['flags'][$type] = $flagged;

        if ($type === 'backbone_url_error') {
            if (!isset($state['checks']) || !is_array($state['checks'])) {
                $state['checks'] = [];
            }
            $state['checks']['no_backbone_url_error'] = !$flagged;
        }

        $this->persist_diagnostics_state($state);

        return $this->rest_get_diagnostics($request);
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $files
     *
     * @return array<string, mixed>
     */
    public function handle_group_update(int $group_id, array $data, array $files = []): array
    {
        $group_id = absint($group_id);

        if ($group_id <= 0) {
            return [
                'success' => false,
                'message' => __('Grupo inválido.', 'juntaplay'),
                'status'  => 400,
            ];
        }

        if (!is_user_logged_in()) {
            return [
                'success' => false,
                'message' => __('Faça login para editar o grupo.', 'juntaplay'),
                'status'  => 403,
            ];
        }

        $context = $this->get_group_modal_context($group_id);
        if (!$context || empty($context['is_owner'])) {
            return [
                'success' => false,
                'message' => __('Você não pode editar este grupo.', 'juntaplay'),
                'status'  => 403,
            ];
        }

        $categories = isset($context['categories']) && is_array($context['categories']) ? $context['categories'] : [];

        $title           = sanitize_text_field(wp_unslash($data['title'] ?? ''));
        $description     = sanitize_textarea_field(wp_unslash($data['description'] ?? ''));
        $rules           = sanitize_textarea_field(wp_unslash($data['rules'] ?? ''));
        $service_name    = sanitize_text_field(wp_unslash($data['service_name'] ?? ''));
        $service_url     = esc_url_raw((string) ($data['service_url'] ?? ''));
        $price_regular   = $this->parse_money_input((string) ($data['price_regular'] ?? ''));
        $price_promo     = $this->parse_money_input((string) ($data['price_promotional'] ?? ''));
        $member_price    = $this->parse_money_input((string) ($data['member_price'] ?? ''));
        $slots_total     = max(0, absint($data['slots_total'] ?? 0));
        $slots_reserved  = max(0, absint($data['slots_reserved'] ?? 0));
        $support_channel = sanitize_text_field(wp_unslash($data['support_channel'] ?? ''));
        $delivery_time   = sanitize_text_field(wp_unslash($data['delivery_time'] ?? ''));
        $access_method   = sanitize_text_field(wp_unslash($data['access_method'] ?? ''));
        $access_url      = esc_url_raw((string) ($data['access_url'] ?? ''));
        $access_login    = sanitize_text_field(wp_unslash($data['access_login'] ?? ''));
        $access_password = sanitize_text_field(wp_unslash($data['access_password'] ?? ''));
        $access_notes    = sanitize_textarea_field(wp_unslash($data['access_notes'] ?? ''));
        $access_observations = sanitize_textarea_field(wp_unslash($data['access_observations'] ?? ''));
        $category        = sanitize_key((string) ($data['category'] ?? ''));
        $instant_access  = !empty($data['instant_access']);
        $cover_id        = isset($data['cover_id']) ? absint($data['cover_id']) : 0;

        if (!isset($categories[$category])) {
            $category = 'other';
        }

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

        if ($errors) {
            return [
                'success' => false,
                'message' => implode(' ', $errors),
                'errors'  => $errors,
                'status'  => 400,
            ];
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
            'access_url'        => $access_url,
            'access_login'      => $access_login,
            'access_password'   => $access_password,
            'access_notes'      => $access_notes,
            'access_observations' => $access_observations,
            'category'          => $category,
            'instant_access'    => $instant_access,
            'cover_id'          => $cover_id,
            'pool_id'           => isset($context['group']['pool_id']) ? (int) $context['group']['pool_id'] : 0,
        ];

        $previous_group = isset($context['group']) && is_array($context['group']) ? $context['group'] : [];

        $updated = Groups::update_basic($group_id, $payload);

        if (!$updated) {
            return [
                'success' => false,
                'message' => __('Não foi possível salvar as alterações do grupo.', 'juntaplay'),
                'status'  => 500,
            ];
        }

        $this->invalidate_profile_cache();
        do_action('juntaplay/profile/groups/updated', get_current_user_id(), $group_id, $payload, $previous_group);

        $updated_context = $this->get_group_modal_context($group_id);

        return [
            'success' => true,
            'message' => __('Grupo atualizado com sucesso.', 'juntaplay'),
            'group'   => $updated_context ? $updated_context['group'] : [],
            'payload' => $payload,
        ];
    }

    /**
     * @param array<string, mixed> $group
     * @param array<string, string> $categories
     */
    public function render_group_edit_template(array $group, array $categories): string
    {
        return $this->render_template('group-modal-edit.php', [
            'group'      => $group,
            'categories' => $categories,
        ]);
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

    private function get_diagnostics_state(): array
    {
        $state = get_option(self::DIAGNOSTICS_OPTION, []);

        if (!is_array($state)) {
            $state = [];
        }

        if (!isset($state['checks']) || !is_array($state['checks'])) {
            $state['checks'] = [];
        }

        if (!isset($state['flags']) || !is_array($state['flags'])) {
            $state['flags'] = [];
        }

        return $state;
    }

    private function persist_diagnostics_state(array $state): void
    {
        update_option(self::DIAGNOSTICS_OPTION, $state, false);
    }

    private function normalize_diagnostics_checks(array $state): array
    {
        $defaults = [
            'edit_modal_opens'       => false,
            'single_click_media'     => false,
            'cover_preview_ok'       => false,
            'can_upload_as_subscriber' => false,
            'no_duplicate_nonce_ids' => true,
            'no_backbone_url_error'  => empty($state['flags']['backbone_url_error']),
        ];

        $stored = isset($state['checks']) && is_array($state['checks']) ? $state['checks'] : [];

        foreach ($stored as $key => $value) {
            $normalized_key = sanitize_key((string) $key);
            if ($normalized_key === '') {
                continue;
            }

            $defaults[$normalized_key] = (bool) $value;
        }

        return $defaults;
    }

    private function user_can_manage_group(int $group_id): bool
    {
        if ($group_id <= 0) {
            return false;
        }

        if (!is_user_logged_in()) {
            return false;
        }

        if (current_user_can('manage_options')) {
            return true;
        }

        $user_id = get_current_user_id();
        if ($user_id <= 0) {
            return false;
        }

        $groups = Groups::get_groups_for_user($user_id);
        $owned  = isset($groups['owned']) && is_array($groups['owned']) ? $groups['owned'] : [];

        foreach ($owned as $group) {
            if (!is_array($group)) {
                continue;
            }

            if ((int) ($group['id'] ?? 0) === $group_id) {
                return true;
            }
        }

        return false;
    }

    private function update_sessions(int $user_id): void
    {
        $token = wp_get_session_token();

        if (!$token) {
            $this->add_error('sessions', __('Não foi possível identificar a sessão atual.', 'juntaplay'));

            return;
        }

        $destroyed = false;

        if (function_exists('wp_destroy_other_sessions')) {
            wp_destroy_other_sessions();
            $destroyed = true;
        } elseif (class_exists(WP_Session_Tokens::class)) {
            $manager = WP_Session_Tokens::get_instance($user_id);
            if ($manager) {
                $manager->destroy_other_sessions($token);
                $destroyed = true;
            }
        }

        if (!$destroyed) {
            $this->add_error('sessions', __('Não foi possível encerrar as outras sessões.', 'juntaplay'));

            return;
        }

        $this->invalidate_cache();
        $this->add_notice(__('Outras sessões foram desconectadas.', 'juntaplay'));
        $this->active_section = null;

        do_action('juntaplay/profile/updated', $user_id, 'sessions', []);
    }

    private function format_upload_error(WP_Error $error): string
    {
        $message = trim($error->get_error_message());

        if ($message === '') {
            return __('Não foi possível processar o arquivo enviado.', 'juntaplay');
        }

        $normalized = strtolower($message);

        if (strpos($normalized, 'ficheiros bin') !== false || strpos($normalized, 'binary file') !== false) {
            return __('Formato de arquivo não suportado. Envie imagens nos formatos JPG, PNG ou WEBP.', 'juntaplay');
        }

        return $message;
    }

    private function add_error(string $section, string $message): void
    {
        if (!isset($this->errors[$section])) {
            $this->errors[$section] = [];
        }

        $this->errors[$section][] = $message;
    }

    private function add_notice(string $message): void
    {
        $this->notices[] = $message;
    }

    public function invalidate_profile_cache(): void
    {
        $this->invalidate_cache();
    }

    private function invalidate_cache(): void
    {
        $this->cached_profile = null;
    }
}
