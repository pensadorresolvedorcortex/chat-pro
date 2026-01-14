<?php
declare(strict_types=1);

namespace JuntaPlay\Admin;

defined('ABSPATH') || exit;

class Menu
{
    public function init(): void
    {
        add_action('admin_menu', [$this, 'register_menu']);
    }

    public function register_menu(): void
    {
        $capability = 'manage_options';

        add_menu_page(
            __('JuntaPlay', 'juntaplay'),
            __('JuntaPlay', 'juntaplay'),
            $capability,
            'juntaplay',
            [$this, 'render_dashboard'],
            'dashicons-grid-view',
            56
        );

        add_submenu_page(
            'juntaplay',
            __('Importar & Gerar Páginas', 'juntaplay'),
            __('Importar', 'juntaplay'),
            $capability,
            'juntaplay-import',
            [$this, 'render_import']
        );

        add_submenu_page(
            'juntaplay',
            __('Configurações', 'juntaplay'),
            __('Configurações', 'juntaplay'),
            $capability,
            'juntaplay-settings',
            [$this, 'render_settings']
        );

        add_submenu_page(
            'juntaplay',
            __('Relatórios — Grupos Cancelados', 'juntaplay'),
            __('Relatórios', 'juntaplay'),
            'manage_network',
            'juntaplay-reports-canceled-groups',
            [$this, 'render_cancelled_groups_report']
        );
    }

    public function render_dashboard(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('Você não tem permissão suficiente para acessar esta página.', 'juntaplay'));
        }

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('JuntaPlay — Dashboard', 'juntaplay') . '</h1>';
        echo '<p>' . esc_html__('Use os menus ao lado para gerenciar campanhas, importar cotas e ajustar configurações.', 'juntaplay') . '</p>';
        echo '</div>';
    }

    public function render_import(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('Você não tem permissão suficiente para acessar esta página.', 'juntaplay'));
        }

        do_action('juntaplay/admin/import_page');
    }

    public function render_settings(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('Você não tem permissão suficiente para acessar esta página.', 'juntaplay'));
        }

        do_action('juntaplay/admin/settings_page');
    }

    public function render_cancelled_groups_report(): void
    {
        $controller = new \JuntaPlay\Admin\Reports\CancelledGroups();
        $controller->render_page();
    }
}
