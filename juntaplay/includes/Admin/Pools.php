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

        $payload = [
            'id'           => isset($_POST['pool_id']) ? absint((string) wp_unslash($_POST['pool_id'])) : 0,
            'title'        => isset($_POST['title']) ? sanitize_text_field((string) wp_unslash($_POST['title'])) : '',
            'slug'         => isset($_POST['slug']) ? sanitize_title((string) wp_unslash($_POST['slug'])) : '',
            'price'        => isset($_POST['price']) ? (float) sanitize_text_field((string) wp_unslash($_POST['price'])) : 0.0,
            'quota_start'  => isset($_POST['quota_start']) ? absint((string) wp_unslash($_POST['quota_start'])) : 1,
            'quota_end'    => isset($_POST['quota_end']) ? absint((string) wp_unslash($_POST['quota_end'])) : 1,
            'category'     => isset($_POST['category']) ? sanitize_key((string) wp_unslash($_POST['category'])) : 'other',
            'excerpt'      => isset($_POST['excerpt']) ? sanitize_textarea_field((string) wp_unslash($_POST['excerpt'])) : null,
            'thumbnail_id' => isset($_POST['thumbnail_id']) ? absint((string) wp_unslash($_POST['thumbnail_id'])) : null,
            'icon_id'      => isset($_POST['icon_id']) ? absint((string) wp_unslash($_POST['icon_id'])) : null,
            'cover_id'     => isset($_POST['cover_id']) ? absint((string) wp_unslash($_POST['cover_id'])) : null,
            'service_url'  => isset($_POST['service_url']) ? sanitize_url((string) wp_unslash($_POST['service_url'])) : null,
            'is_featured'  => !empty($_POST['is_featured']),
            'status'       => isset($_POST['status']) ? sanitize_key((string) wp_unslash($_POST['status'])) : 'publish',
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

        if ($payload['title'] === '' || $payload['slug'] === '') {
            $this->store_notice('error', esc_html__('Título e slug são obrigatórios.', 'juntaplay'));
            $this->redirect_back();
        }

        $pool_id = PoolsData::create_or_update($payload);

        if ($pool_id <= 0) {
            $this->store_notice('error', esc_html__('Não foi possível salvar o serviço.', 'juntaplay'));
            $this->redirect_back();
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
