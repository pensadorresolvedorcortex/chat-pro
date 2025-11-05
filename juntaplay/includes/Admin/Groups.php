<?php

declare(strict_types=1);

namespace JuntaPlay\Admin;

use JuntaPlay\Data\Groups as GroupsData;

use function absint;
use function add_action;
use function add_submenu_page;
use function admin_url;
use function current_user_can;
use function esc_html;
use function esc_html__;
use function get_transient;
use function sanitize_key;
use function sanitize_text_field;
use function sanitize_textarea_field;
use function set_transient;
use function wp_safe_redirect;
use function wp_unslash;
use function wp_verify_nonce;
use function wp_die;
use function delete_transient;
use function esc_url_raw;
use function get_current_user_id;

use const HOUR_IN_SECONDS;

defined('ABSPATH') || exit;

class Groups
{
    private const NOTICE_KEY = 'juntaplay_groups_notice';

    public function init(): void
    {
        add_action('admin_menu', [$this, 'register_menu']);
        add_action('admin_post_juntaplay_group_action', [$this, 'handle_action']);
    }

    public function register_menu(): void
    {
        add_submenu_page(
            'juntaplay',
            esc_html__('Grupos do JuntaPlay', 'juntaplay'),
            esc_html__('Grupos', 'juntaplay'),
            'manage_options',
            'juntaplay-groups',
            [$this, 'render_page']
        );
    }

    public function render_page(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Você não tem permissão suficiente para acessar esta página.', 'juntaplay'));
        }

        $status = isset($_GET['status']) ? sanitize_key((string) wp_unslash($_GET['status'])) : 'all'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $search = isset($_GET['s']) ? sanitize_text_field((string) wp_unslash($_GET['s'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

        $status_counts = GroupsData::counts_by_status();
        $groups        = GroupsData::all([
            'status' => $status,
            'search' => $search,
            'limit'  => 200,
        ]);

        $notice = get_transient(self::NOTICE_KEY);
        if ($notice) {
            delete_transient(self::NOTICE_KEY);
        }

        $template = JP_DIR . 'templates/admin-groups.php';
        if (!file_exists($template)) {
            wp_die(esc_html__('Template de administração de grupos não encontrado.', 'juntaplay'));
        }

        $groups_page_context = [
            'groups'        => $groups,
            'status'        => $status,
            'search'        => $search,
            'status_counts' => $status_counts,
            'notice'        => is_array($notice) ? $notice : null,
        ];

        include $template;
    }

    public function handle_action(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Você não tem permissão suficiente para executar esta ação.', 'juntaplay'));
        }

        $referer = isset($_POST['_wpnonce']) ? sanitize_text_field((string) wp_unslash($_POST['_wpnonce'])) : '';

        if ($referer === '' || !wp_verify_nonce($referer, 'juntaplay_group_action')) {
            $this->store_notice('error', esc_html__('Sua sessão expirou. Atualize a página e tente novamente.', 'juntaplay'));
            $this->redirect_back();
        }

        $group_id = isset($_POST['group_id']) ? absint(wp_unslash($_POST['group_id'])) : 0;
        $action   = isset($_POST['group_action']) ? sanitize_key((string) wp_unslash($_POST['group_action'])) : '';
        $note     = isset($_POST['group_note']) ? sanitize_textarea_field((string) wp_unslash($_POST['group_note'])) : '';

        if ($group_id <= 0 || $action === '') {
            $this->store_notice('error', esc_html__('Selecione um grupo e uma ação válida.', 'juntaplay'));
            $this->redirect_back();
        }

        $group = GroupsData::get($group_id);
        if (!$group) {
            $this->store_notice('error', esc_html__('Grupo não encontrado.', 'juntaplay'));
            $this->redirect_back();
        }

        if ($action === 'delete') {
            $deleted = GroupsData::delete($group_id);

            if (!$deleted) {
                $this->store_notice('error', esc_html__('Não foi possível excluir o grupo.', 'juntaplay'));
            } else {
                $this->store_notice('success', esc_html__('Grupo excluído permanentemente.', 'juntaplay'));
            }

            $this->redirect_back();
        }

        $current_status = isset($group->status) ? (string) $group->status : GroupsData::STATUS_PENDING;
        $new_status     = $this->map_action_to_status($action, $current_status);

        if (!$new_status) {
            $this->store_notice('error', esc_html__('Ação não suportada para o status atual.', 'juntaplay'));
            $this->redirect_back();
        }

        if ($new_status === $current_status) {
            $this->store_notice('success', esc_html__('O status do grupo já está atualizado.', 'juntaplay'));
            $this->redirect_back();
        }

        $updated = GroupsData::update_status($group_id, $new_status, [
            'review_note'  => $note,
            'reviewed_by'  => get_current_user_id(),
            'reviewed_at'  => '',
        ]);

        if (!$updated) {
            $this->store_notice('error', esc_html__('Não foi possível atualizar o status do grupo.', 'juntaplay'));
            $this->redirect_back();
        }

        do_action('juntaplay/groups/status_changed', $group_id, $current_status, $new_status, [
            'note'     => $note,
            'admin_id' => get_current_user_id(),
        ]);

        $this->store_notice('success', esc_html__('Status do grupo atualizado com sucesso.', 'juntaplay'));
        $this->redirect_back();
    }

    private function map_action_to_status(string $action, string $current_status): ?string
    {
        return match ($action) {
            'approve' => GroupsData::STATUS_APPROVED,
            'reject'  => GroupsData::STATUS_REJECTED,
            'archive' => GroupsData::STATUS_ARCHIVED,
            'reset'   => GroupsData::STATUS_PENDING,
            default   => null,
        };
    }

    private function store_notice(string $type, string $message): void
    {
        set_transient(
            self::NOTICE_KEY,
            [
                'type'    => $type,
                'message' => $message,
            ],
            HOUR_IN_SECONDS
        );
    }

    private function redirect_back(): void
    {
        $redirect = isset($_POST['redirect_to']) ? esc_url_raw((string) wp_unslash($_POST['redirect_to'])) : '';

        if ($redirect === '') {
            $redirect = admin_url('admin.php?page=juntaplay-groups');
        }

        wp_safe_redirect($redirect);
        exit;
    }
}
