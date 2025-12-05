<?php
declare(strict_types=1);

namespace JuntaPlay\Admin;

use JuntaPlay\Data\PaymentSplits;

use function add_action;
use function esc_html;
use function esc_html__;
use function esc_html_e;
use function esc_url;
use function get_edit_post_link;
use function get_userdata;
use function number_format_i18n;
use function current_user_can;
use function wp_die;

defined('ABSPATH') || exit;

class Splits
{
    public function init(): void
    {
        add_action('admin_menu', [$this, 'register_menu']);
    }

    public function register_menu(): void
    {
        add_submenu_page(
            'juntaplay',
            __('Histórico de splits', 'juntaplay'),
            __('Splits', 'juntaplay'),
            'manage_options',
            'juntaplay-splits',
            [$this, 'render_page']
        );
    }

    public function render_page(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('Você não tem permissão suficiente para acessar esta página.', 'juntaplay'));
        }

        $splits = PaymentSplits::latest(100);
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Histórico de splits internos', 'juntaplay'); ?></h1>
            <p><?php esc_html_e('Lista das últimas operações de divisão interna de pagamentos disparadas por notificações do Mercado Pago.', 'juntaplay'); ?></p>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Data', 'juntaplay'); ?></th>
                        <th><?php esc_html_e('Pedido', 'juntaplay'); ?></th>
                        <th><?php esc_html_e('Grupo', 'juntaplay'); ?></th>
                        <th><?php esc_html_e('Valor do grupo', 'juntaplay'); ?></th>
                        <th><?php esc_html_e('Admin', 'juntaplay'); ?></th>
                        <th><?php esc_html_e('Participante', 'juntaplay'); ?></th>
                        <th><?php esc_html_e('Percentual', 'juntaplay'); ?></th>
                        <th><?php esc_html_e('Superadmin', 'juntaplay'); ?></th>
                        <th><?php esc_html_e('Admin do grupo', 'juntaplay'); ?></th>
                        <th><?php esc_html_e('Caução', 'juntaplay'); ?></th>
                        <th><?php esc_html_e('Status', 'juntaplay'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$splits) : ?>
                        <tr><td colspan="10"><?php esc_html_e('Nenhuma divisão registrada até o momento.', 'juntaplay'); ?></td></tr>
                    <?php endif; ?>
                    <?php foreach ($splits as $split) :
                        $admin       = isset($split['admin_id']) ? get_userdata((int) $split['admin_id']) : null;
                        $participant = isset($split['participant_id']) ? get_userdata((int) $split['participant_id']) : null;
                        $order_link  = '';
                        if (!empty($split['order_id']) && function_exists('get_edit_post_link')) {
                            $edit_link = get_edit_post_link((int) $split['order_id']);
                            $order_link = $edit_link ? '<a href="' . esc_url($edit_link) . '">#' . esc_html((string) $split['order_id']) . '</a>' : '#' . esc_html((string) $split['order_id']);
                        }
                        ?>
                        <tr>
                            <td><?php echo esc_html($split['created_at'] ?? ''); ?></td>
                            <td><?php echo $order_link ?: '#' . esc_html((string) ($split['order_id'] ?? '')); ?></td>
                            <td><?php echo esc_html($split['group_title'] ?: (string) ($split['group_id'] ?? '')); ?></td>
                            <td><?php echo esc_html(number_format_i18n((float) ($split['base_amount'] ?? 0), 2)); ?></td>
                            <td><?php echo $admin ? esc_html($admin->display_name ?: $admin->user_login) : '—'; ?></td>
                            <td><?php echo $participant ? esc_html($participant->display_name ?: $participant->user_login) : '—'; ?></td>
                            <td><?php echo esc_html(number_format_i18n((float) ($split['percentage'] ?? 0), 2) . '%'); ?></td>
                            <td><?php echo esc_html(number_format_i18n((float) ($split['superadmin_amount'] ?? 0), 2)); ?></td>
                            <td><?php echo esc_html(number_format_i18n((float) ($split['admin_amount'] ?? 0), 2)); ?></td>
                            <td><?php echo esc_html(number_format_i18n((float) ($split['deposit_amount'] ?? 0), 2)); ?></td>
                            <td><?php echo esc_html($split['status'] ?? ''); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}
