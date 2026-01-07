<?php

declare(strict_types=1);

namespace JuntaPlay\Admin;

use JuntaPlay\Data\Pools as PoolsData;

use function absint;
use function add_action;
use function add_submenu_page;
use function admin_url;
use function current_user_can;
use function esc_html__;
use function esc_url_raw;
use function delete_transient;
use function get_transient;
use function sanitize_key;
use function sanitize_title;
use function sanitize_text_field;
use function sanitize_textarea_field;
use function sanitize_url;
use function set_transient;
use function wp_enqueue_media;
use function wp_die;
use function wp_safe_redirect;
use function wp_unslash;
use function wp_verify_nonce;

use const HOUR_IN_SECONDS;

defined('ABSPATH') || exit;

class Pools
{
    private const NOTICE_KEY = 'juntaplay_pools_notice';

    public function init(): void
    {
        add_action('admin_menu', [$this, 'register_menu']);
        add_action('admin_post_juntaplay_pool_save', [$this, 'handle_save']);
        add_action('admin_post_juntaplay_pool_delete', [$this, 'handle_delete']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function enqueue_assets(string $hook): void
    {
        $page = isset($_GET['page']) ? (string) sanitize_key(wp_unslash($_GET['page'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

        if ($page !== 'juntaplay-pools') {
            return;
        }

        wp_enqueue_script(
            'juntaplay-admin-media',
            JP_URL . 'assets/js/juntaplay-admin-media.js',
            ['jquery', 'media-editor'],
            JP_VERSION,
            true
        );
    }

    public function register_menu(): void
    {
        add_submenu_page(
            'juntaplay',
            esc_html__('Serviços pré-aprovados', 'juntaplay'),
            esc_html__('Serviços', 'juntaplay'),
            'manage_options',
            'juntaplay-pools',
            [$this, 'render_page']
        );
    }

    public function render_page(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Você não tem permissão suficiente para acessar esta página.', 'juntaplay'));
        }

        $notice = get_transient(self::NOTICE_KEY);
        if ($notice) {
            delete_transient(self::NOTICE_KEY);
        }

        $edit_id = isset($_GET['edit']) ? absint((string) wp_unslash($_GET['edit'])) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $editing = $edit_id > 0 ? PoolsData::get($edit_id) : null;
        $plans   = $edit_id > 0 ? PoolsData::get_plans($edit_id) : [];
        $pool_type = !empty($plans) ? 'variable' : 'simple';

        $pools = PoolsData::query([
            'status' => '',
            'per_page' => 200,
        ]);

        $template = JP_DIR . 'templates/admin-pools.php';
        if (!file_exists($template)) {
            wp_die(esc_html__('Template de serviços não encontrado.', 'juntaplay'));
        }

        wp_enqueue_media();

        $context = [
            'pools'      => $pools['items'] ?? [],
            'categories' => PoolsData::get_category_labels(),
            'notice'     => is_array($notice) ? $notice : null,
            'editing'    => $editing,
            'plans'      => $plans,
            'pool_type'  => $pool_type,
        ];

        include $template;
    }

    public function handle_save(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Você não tem permissão suficiente para executar esta ação.', 'juntaplay'));
        }

        $referer = isset($_POST['_wpnonce']) ? sanitize_text_field((string) wp_unslash($_POST['_wpnonce'])) : '';

        if ($referer === '' || !wp_verify_nonce($referer, 'juntaplay_pool_save')) {
            $this->store_notice('error', esc_html__('Sua sessão expirou. Atualize a página e tente novamente.', 'juntaplay'));
            $this->redirect_back();
        }

        $price_raw = isset($_POST['price']) ? (string) wp_unslash($_POST['price']) : '';
        $price_clean = preg_replace('/[^0-9.,]/', '', $price_raw);
        $price_clean = str_replace(',', '.', (string) $price_clean);
        $price_parts = explode('.', (string) $price_clean);

        if (count($price_parts) > 2) {
            $decimal     = array_pop($price_parts);
            $price_clean = implode('', $price_parts) . '.' . $decimal;
        }

        $status_raw = isset($_POST['status']) ? sanitize_key((string) wp_unslash($_POST['status'])) : 'publish';
        if ($status_raw === 'active') {
            $status_raw = 'publish';
        } elseif ($status_raw === 'inactive') {
            $status_raw = 'draft';
        }

        $payload = [
            'id'           => isset($_POST['pool_id']) ? absint((string) wp_unslash($_POST['pool_id'])) : 0,
            'title'        => isset($_POST['title']) ? sanitize_text_field((string) wp_unslash($_POST['title'])) : '',
            'slug'         => isset($_POST['slug']) ? sanitize_title((string) wp_unslash($_POST['slug'])) : '',
            'price'        => $price_clean !== '' ? (float) $price_clean : 0.0,
            'quota_start'  => isset($_POST['quota_start']) ? absint((string) wp_unslash($_POST['quota_start'])) : 1,
            'quota_end'    => isset($_POST['quota_end']) ? absint((string) wp_unslash($_POST['quota_end'])) : 1,
            'category'     => isset($_POST['category']) ? sanitize_key((string) wp_unslash($_POST['category'])) : 'other',
            'excerpt'      => isset($_POST['excerpt']) ? sanitize_textarea_field((string) wp_unslash($_POST['excerpt'])) : null,
            'thumbnail_id' => isset($_POST['thumbnail_id']) ? absint((string) wp_unslash($_POST['thumbnail_id'])) : null,
            'icon_id'      => isset($_POST['icon_id']) ? absint((string) wp_unslash($_POST['icon_id'])) : null,
            'cover_id'     => isset($_POST['cover_id']) ? absint((string) wp_unslash($_POST['cover_id'])) : null,
            'service_url'  => isset($_POST['service_url']) ? esc_url_raw((string) wp_unslash($_POST['service_url'])) : null,
            'is_featured'  => !empty($_POST['is_featured']),
            'status'       => $status_raw,
        ];

        if (empty($payload['thumbnail_id'])) {
            $payload['thumbnail_id'] = null;
        }

        if (empty($payload['icon_id'])) {
            $payload['icon_id'] = null;
        }

        if (empty($payload['cover_id'])) {
            $payload['cover_id'] = null;
        }

        if ($payload['service_url'] === '') {
            $payload['service_url'] = null;
        }

        if ($payload['slug'] === '' && $payload['id'] > 0) {
            $existing = PoolsData::get($payload['id']);
            if ($existing && !empty($existing->slug)) {
                $payload['slug'] = sanitize_title((string) $existing->slug);
            }
        }

        if ($payload['slug'] === '') {
            $payload['slug'] = sanitize_title($payload['title']);
        }

        if ($payload['slug'] === '') {
            $payload['slug'] = sanitize_title(uniqid('juntaplay-pool-', true));
        }

        if ($payload['title'] === '' || $payload['slug'] === '') {
            $this->store_notice('error', esc_html__('Título é obrigatório e o identificador será gerado automaticamente.', 'juntaplay'));
            $this->redirect_back();
        }

        $pool_type = isset($_POST['pool_type']) ? sanitize_key((string) wp_unslash($_POST['pool_type'])) : 'simple';
        if (!in_array($pool_type, ['simple', 'variable'], true)) {
            $pool_type = 'simple';
        }

        $plans_payload = [];
        $has_plans_input = array_key_exists('jp_plans', $_POST);
        $plans_input = $has_plans_input ? wp_unslash($_POST['jp_plans']) : [];
        if ($pool_type === 'variable' && is_array($plans_input)) {
            foreach ($plans_input as $plan) {
                if (!is_array($plan)) {
                    continue;
                }

                $plan_name = isset($plan['name']) ? sanitize_text_field((string) $plan['name']) : '';
                $plan_desc = isset($plan['description']) ? sanitize_textarea_field((string) $plan['description']) : '';
                $plan_price_raw = isset($plan['price']) ? (string) $plan['price'] : '';
                $plan_price_clean = preg_replace('/[^0-9.,]/', '', $plan_price_raw);
                $plan_price_clean = str_replace(',', '.', (string) $plan_price_clean);
                $plan_price_parts = explode('.', (string) $plan_price_clean);

                if (count($plan_price_parts) > 2) {
                    $plan_decimal     = array_pop($plan_price_parts);
                    $plan_price_clean = implode('', $plan_price_parts) . '.' . $plan_decimal;
                }

                $plan_price = $plan_price_clean !== '' ? (float) $plan_price_clean : 0.0;
                $plan_max = isset($plan['max_members']) ? absint((string) $plan['max_members']) : 0;
                $plan_order = isset($plan['order']) ? (int) $plan['order'] : 0;
                $plan_status_raw = isset($plan['status']) ? sanitize_key((string) $plan['status']) : 'active';
                $plan_status = $plan_status_raw === 'inactive' ? 'inactive' : 'active';

                if ($plan_name === '' && $plan_desc === '' && $plan_price_clean === '' && $plan_max === 0) {
                    continue;
                }

                if ($plan_name === '') {
                    $this->store_notice('error', esc_html__('O nome do plano é obrigatório.', 'juntaplay'));
                    $this->redirect_back();
                }

                if ($plan_price <= 0) {
                    $this->store_notice('error', esc_html__('O preço do plano deve ser maior que zero.', 'juntaplay'));
                    $this->redirect_back();
                }

                if ($plan_max < 1) {
                    $this->store_notice('error', esc_html__('A quantidade máxima de usuários do plano deve ser no mínimo 1.', 'juntaplay'));
                    $this->redirect_back();
                }

                $plans_payload[] = [
                    'name'        => $plan_name,
                    'description' => $plan_desc,
                    'price'       => $plan_price,
                    'max_members' => $plan_max,
                    'status'      => $plan_status,
                    'order'       => $plan_order,
                ];
            }
        }

        if ($pool_type === 'variable' && empty($plans_payload)) {
            $this->store_notice('error', esc_html__('Adicione pelo menos uma variação ativa para salvar um serviço variável.', 'juntaplay'));
            $this->redirect_back();
        }

        $pool_id = PoolsData::create_or_update($payload);

        if ($pool_id <= 0) {
            $this->store_notice('error', esc_html__('Não foi possível salvar o serviço.', 'juntaplay'));
            $this->redirect_back();
        }

        if ($pool_type === 'variable') {
            PoolsData::update_plans($pool_id, $plans_payload);
        } else {
            PoolsData::update_plans($pool_id, []);
        }

        $this->store_notice('success', esc_html__('Serviço salvo com sucesso.', 'juntaplay'));
        $this->redirect_back();
    }

    public function handle_delete(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Você não tem permissão suficiente para executar esta ação.', 'juntaplay'));
        }

        $referer = isset($_POST['_wpnonce']) ? sanitize_text_field((string) wp_unslash($_POST['_wpnonce'])) : '';

        if ($referer === '' || !wp_verify_nonce($referer, 'juntaplay_pool_delete')) {
            $this->store_notice('error', esc_html__('Sua sessão expirou. Atualize a página e tente novamente.', 'juntaplay'));
            $this->redirect_back();
        }

        $pool_id = isset($_POST['pool_id']) ? absint((string) wp_unslash($_POST['pool_id'])) : 0;

        if ($pool_id <= 0) {
            $this->store_notice('error', esc_html__('Selecione um serviço válido para excluir.', 'juntaplay'));
            $this->redirect_back();
        }

        $deleted = PoolsData::delete($pool_id);

        if ($deleted) {
            $this->store_notice('success', esc_html__('Serviço removido.', 'juntaplay'));
        } else {
            $this->store_notice('error', esc_html__('Não foi possível excluir o serviço.', 'juntaplay'));
        }

        $this->redirect_back();
    }

    private function redirect_back(): void
    {
        wp_safe_redirect(admin_url('admin.php?page=juntaplay-pools'));
        exit;
    }

    private function store_notice(string $type, string $message): void
    {
        set_transient(self::NOTICE_KEY, [
            'type'    => $type,
            'message' => $message,
        ], HOUR_IN_SECONDS);
    }
}
