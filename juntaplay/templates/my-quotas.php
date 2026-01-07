<?php
declare(strict_types=1);

global $wpdb;

$user_id = get_current_user_id();
$table   = "{$wpdb->prefix}jp_quotas";
$results = $wpdb->get_results($wpdb->prepare(
    "SELECT q.number, q.status, p.title, q.order_id
     FROM $table q
     INNER JOIN {$wpdb->prefix}jp_pools p ON p.id = q.pool_id
     WHERE q.user_id = %d
     ORDER BY q.created_at DESC",
    $user_id
));

$statement_page_id   = (int) get_option('juntaplay_page_extrato');
$statement_page_link = $statement_page_id ? get_permalink($statement_page_id) : '';

$status_labels = [
    'available' => __('Disponível', 'juntaplay'),
    'reserved'  => __('Reservada', 'juntaplay'),
    'paid'      => __('Paga', 'juntaplay'),
    'canceled'  => __('Cancelada', 'juntaplay'),
    'expired'   => __('Expirada', 'juntaplay'),
];
?>
<div class="juntaplay-my-quotas juntaplay-section">
    <?php if ($results) : ?>
        <table class="juntaplay-table">
            <thead>
                <tr>
                    <th><?php esc_html_e('Campanha', 'juntaplay'); ?></th>
                    <th><?php esc_html_e('Cota', 'juntaplay'); ?></th>
                    <th><?php esc_html_e('Status', 'juntaplay'); ?></th>
                    <th><?php esc_html_e('Pedido', 'juntaplay'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($results as $row) :
                    $status_key   = (string) $row->status;
                    $status_label = $status_labels[$status_key] ?? ucfirst($status_key);
                    $status_class = 'juntaplay-status juntaplay-status--' . sanitize_html_class($status_key);
                    ?>
                    <tr>
                        <td data-label="<?php echo esc_attr__('Campanha', 'juntaplay'); ?>"><?php echo esc_html($row->title); ?></td>
                        <td data-label="<?php echo esc_attr__('Cota', 'juntaplay'); ?>"><?php echo esc_html($row->number); ?></td>
                        <td data-label="<?php echo esc_attr__('Status', 'juntaplay'); ?>">
                            <span class="<?php echo esc_attr($status_class); ?>"><?php echo esc_html($status_label); ?></span>
                        </td>
                        <td data-label="<?php echo esc_attr__('Pedido', 'juntaplay'); ?>">
                            <?php if ($row->order_id) : ?>
                                <div class="juntaplay-table-actions">
                                    <a class="juntaplay-link" href="<?php echo esc_url(wc_get_endpoint_url('view-order', (string) $row->order_id, wc_get_page_permalink('myaccount'))); ?>">
                                        #<?php echo esc_html($row->order_id); ?>
                                    </a>
                                    <?php if ($statement_page_link) :
                                        $statement_url = add_query_arg('order_id', (int) $row->order_id, $statement_page_link);
                                        ?>
                                        <a class="juntaplay-chip" href="<?php echo esc_url($statement_url); ?>"><?php esc_html_e('Ver extrato', 'juntaplay'); ?></a>
                                    <?php endif; ?>
                                </div>
                            <?php else : ?>
                                <span class="juntaplay-summary__label"><?php esc_html_e('Em aberto', 'juntaplay'); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else : ?>
        <p class="juntaplay-notice"><?php esc_html_e('Nenhuma cota encontrada.', 'juntaplay'); ?></p>
    <?php endif; ?>
</div>
