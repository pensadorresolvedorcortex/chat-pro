<?php
declare(strict_types=1);

namespace JuntaPlay\Admin;

use JuntaPlay\Data\PoolCategories as PoolCategoriesData;

use function add_action;
use function add_submenu_page;
use function admin_url;
use function current_user_can;
use function esc_html__;
use function sanitize_key;
use function sanitize_text_field;
use function wp_die;
use function wp_safe_redirect;
use function wp_unslash;
use function wp_verify_nonce;
use function get_transient;
use function delete_transient;
use function set_transient;

use const HOUR_IN_SECONDS;

defined('ABSPATH') || exit;

class PoolCategories
{
    private const NOTICE_KEY = 'juntaplay_pool_categories_notice';

    public function init(): void
    {
        add_action('admin_menu', [$this, 'register_menu']);
        add_action('admin_post_juntaplay_pool_category_save', [$this, 'handle_save']);
        add_action('admin_post_juntaplay_pool_category_delete', [$this, 'handle_delete']);
    }

    public function register_menu(): void
    {
        add_submenu_page(
            'juntaplay',
            esc_html__('Categorias', 'juntaplay'),
            esc_html__('Categorias', 'juntaplay'),
            'manage_options',
            'juntaplay-pool-categories',
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

        $categories = PoolCategoriesData::all();
        $edit_slug = isset($_GET['edit']) ? sanitize_key((string) wp_unslash($_GET['edit'])) : '';// phpcs:ignore WordPress.Security.NonceVerification.Recommended

        $template = JP_DIR . 'templates/admin-categories.php';
        if (!file_exists($template)) {
            wp_die(esc_html__('Template de categorias não encontrado.', 'juntaplay'));
        }

        $context = [
            'categories' => $categories,
            'notice'     => is_array($notice) ? $notice : null,
            'editing'    => $edit_slug,
        ];

        include $template;
    }

    public function handle_save(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Você não tem permissão suficiente para executar esta ação.', 'juntaplay'));
        }

        $referer = isset($_POST['_wpnonce']) ? sanitize_text_field((string) wp_unslash($_POST['_wpnonce'])) : '';

        if ($referer === '' || !wp_verify_nonce($referer, 'juntaplay_pool_category_save')) {
            $this->store_notice('error', esc_html__('Sua sessão expirou. Atualize a página e tente novamente.', 'juntaplay'));
            $this->redirect_back();
        }

        $label = isset($_POST['category_label']) ? sanitize_text_field((string) wp_unslash($_POST['category_label'])) : '';
        $slug  = isset($_POST['category_slug']) ? sanitize_key((string) wp_unslash($_POST['category_slug'])) : '';

        if ($label === '') {
            $this->store_notice('error', esc_html__('Informe um nome para a categoria.', 'juntaplay'));
            $this->redirect_back();
        }

        if ($slug === '') {
            $slug = sanitize_key($label);
        }

        if ($slug === '') {
            $this->store_notice('error', esc_html__('Não foi possível gerar um identificador para a categoria.', 'juntaplay'));
            $this->redirect_back();
        }

        PoolCategoriesData::upsert($slug, $label);

        $this->store_notice('success', esc_html__('Categoria salva com sucesso.', 'juntaplay'));
        $this->redirect_back();
    }

    public function handle_delete(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Você não tem permissão suficiente para executar esta ação.', 'juntaplay'));
        }

        $referer = isset($_POST['_wpnonce']) ? sanitize_text_field((string) wp_unslash($_POST['_wpnonce'])) : '';

        if ($referer === '' || !wp_verify_nonce($referer, 'juntaplay_pool_category_delete')) {
            $this->store_notice('error', esc_html__('Sua sessão expirou. Atualize a página e tente novamente.', 'juntaplay'));
            $this->redirect_back();
        }

        $slug = isset($_POST['category_slug']) ? sanitize_key((string) wp_unslash($_POST['category_slug'])) : '';

        if ($slug === '' || $slug === 'other') {
            $this->store_notice('error', esc_html__('Selecione uma categoria válida para excluir.', 'juntaplay'));
            $this->redirect_back();
        }

        PoolCategoriesData::delete($slug);
        $this->store_notice('success', esc_html__('Categoria removida com sucesso.', 'juntaplay'));
        $this->redirect_back();
    }

    private function redirect_back(): void
    {
        wp_safe_redirect(admin_url('admin.php?page=juntaplay-pool-categories'));
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
