<?php
/**
 * Admin listing for JuntaPlay groups.
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$context = isset($groups_page_context) && is_array($groups_page_context) ? $groups_page_context : [];

$groups        = isset($context['groups']) && is_array($context['groups']) ? $context['groups'] : [];
$current_status = isset($context['status']) ? (string) $context['status'] : 'all';
$search_term    = isset($context['search']) ? (string) $context['search'] : '';
$status_counts  = isset($context['status_counts']) && is_array($context['status_counts']) ? $context['status_counts'] : [];
$notice         = isset($context['notice']) && is_array($context['notice']) ? $context['notice'] : null;

$pending_count  = isset($status_counts[\JuntaPlay\Data\Groups::STATUS_PENDING]) ? (int) $status_counts[\JuntaPlay\Data\Groups::STATUS_PENDING] : 0;
$approved_count = isset($status_counts[\JuntaPlay\Data\Groups::STATUS_APPROVED]) ? (int) $status_counts[\JuntaPlay\Data\Groups::STATUS_APPROVED] : 0;
$rejected_count = isset($status_counts[\JuntaPlay\Data\Groups::STATUS_REJECTED]) ? (int) $status_counts[\JuntaPlay\Data\Groups::STATUS_REJECTED] : 0;
$archived_count = isset($status_counts[\JuntaPlay\Data\Groups::STATUS_ARCHIVED]) ? (int) $status_counts[\JuntaPlay\Data\Groups::STATUS_ARCHIVED] : 0;

$status_options = [
    'all'                                   => esc_html__('Todos os status', 'juntaplay'),
    \JuntaPlay\Data\Groups::STATUS_PENDING  => sprintf(esc_html__('Em análise (%d)', 'juntaplay'), $pending_count),
    \JuntaPlay\Data\Groups::STATUS_APPROVED => sprintf(esc_html__('Aprovados (%d)', 'juntaplay'), $approved_count),
    \JuntaPlay\Data\Groups::STATUS_REJECTED => sprintf(esc_html__('Recusados (%d)', 'juntaplay'), $rejected_count),
    \JuntaPlay\Data\Groups::STATUS_ARCHIVED => sprintf(esc_html__('Arquivados (%d)', 'juntaplay'), $archived_count),
];

$current_url = add_query_arg([
    'page'   => 'juntaplay-groups',
    'status' => $current_status,
    's'      => $search_term,
], admin_url('admin.php'));
?>
<div class="wrap">
    <h1><?php esc_html_e('Grupos do JuntaPlay', 'juntaplay'); ?></h1>

    <?php if ($notice) : ?>
        <div class="notice notice-<?php echo esc_attr($notice['type'] === 'error' ? 'error' : 'success'); ?> is-dismissible">
            <p><?php echo esc_html($notice['message']); ?></p>
        </div>
    <?php endif; ?>

    <form method="get" class="juntaplay-groups-admin__filters">
        <input type="hidden" name="page" value="juntaplay-groups" />

        <label for="jp-groups-status" class="screen-reader-text"><?php esc_html_e('Filtrar por status', 'juntaplay'); ?></label>
        <select id="jp-groups-status" name="status">
            <?php foreach ($status_options as $value => $label) : ?>
                <option value="<?php echo esc_attr((string) $value); ?>" <?php selected((string) $value, $current_status); ?>><?php echo esc_html($label); ?></option>
            <?php endforeach; ?>
        </select>

        <label for="jp-groups-search" class="screen-reader-text"><?php esc_html_e('Buscar grupo', 'juntaplay'); ?></label>
        <input type="search" id="jp-groups-search" name="s" value="<?php echo esc_attr($search_term); ?>" placeholder="<?php esc_attr_e('Buscar por nome ou criador', 'juntaplay'); ?>" />

        <button type="submit" class="button button-primary"><?php esc_html_e('Filtrar', 'juntaplay'); ?></button>

        <?php if ($current_status !== 'all' || $search_term !== '') : ?>
            <a href="<?php echo esc_url(admin_url('admin.php?page=juntaplay-groups')); ?>" class="button button-link"><?php esc_html_e('Limpar filtros', 'juntaplay'); ?></a>
        <?php endif; ?>
    </form>

    <table class="wp-list-table widefat striped">
        <thead>
            <tr>
                <th scope="col"><?php esc_html_e('Grupo', 'juntaplay'); ?></th>
                <th scope="col"><?php esc_html_e('Criador', 'juntaplay'); ?></th>
                <th scope="col"><?php esc_html_e('Status', 'juntaplay'); ?></th>
                <th scope="col"><?php esc_html_e('Campanha', 'juntaplay'); ?></th>
                <th scope="col" style="width:90px; text-align:center; "><?php esc_html_e('Membros', 'juntaplay'); ?></th>
                <th scope="col"><?php esc_html_e('Criado em', 'juntaplay'); ?></th>
                <th scope="col" style="width:220px; "><?php esc_html_e('Ações', 'juntaplay'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (!$groups) : ?>
                <tr>
                    <td colspan="7"><?php esc_html_e('Nenhum grupo encontrado.', 'juntaplay'); ?></td>
                </tr>
            <?php else : ?>
                <?php $category_labels = \JuntaPlay\Data\Groups::get_category_labels(); ?>
                <?php foreach ($groups as $group) :
                    if (!is_array($group)) {
                        continue;
                    }

                    $group_id      = isset($group['id']) ? (int) $group['id'] : 0;
                    $group_title   = isset($group['title']) ? (string) $group['title'] : '';
                    $status        = isset($group['status']) ? (string) $group['status'] : '';
                    $status_label  = \JuntaPlay\Data\Groups::get_status_label($status);
                    $owner_name    = isset($group['owner_name']) ? (string) $group['owner_name'] : '';
                    $owner_email   = isset($group['owner_email']) ? (string) $group['owner_email'] : '';
                    $pool_title    = isset($group['pool_title']) ? (string) $group['pool_title'] : '';
                    $members_count = isset($group['members_count']) ? (int) $group['members_count'] : 0;
                    $created_at    = isset($group['created_at']) ? (string) $group['created_at'] : '';
                    $review_note   = isset($group['review_note']) ? (string) $group['review_note'] : '';
                    $service_name  = isset($group['service_name']) ? (string) $group['service_name'] : '';
                    $service_url   = isset($group['service_url']) ? (string) $group['service_url'] : '';
                    $price_regular = isset($group['price_regular']) ? (float) $group['price_regular'] : 0.0;
                    $price_promo   = isset($group['price_promotional']) ? (float) $group['price_promotional'] : 0.0;
                    $member_price  = isset($group['member_price']) ? (float) $group['member_price'] : 0.0;
                    $slots_total   = isset($group['slots_total']) ? (int) $group['slots_total'] : 0;
                    $slots_reserved = isset($group['slots_reserved']) ? (int) $group['slots_reserved'] : 0;
                    $support_channel = isset($group['support_channel']) ? (string) $group['support_channel'] : '';
                    $delivery_time = isset($group['delivery_time']) ? (string) $group['delivery_time'] : '';
                    $access_method = isset($group['access_method']) ? (string) $group['access_method'] : '';
                    $category      = isset($group['category']) ? (string) $group['category'] : '';
                    $instant_access = !empty($group['instant_access']);
                    $category_label = $category !== '' && isset($category_labels[$category])
                        ? (string) $category_labels[$category]
                        : ($category !== '' ? ucwords(str_replace(['-', '_'], ' ', $category)) : '');

                    $created_at_display = $created_at !== '' ? date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($created_at)) : '—';

                    $available_actions = [];
                    if ($status === \JuntaPlay\Data\Groups::STATUS_PENDING) {
                        $available_actions = [
                            'approve' => esc_html__('Aprovar', 'juntaplay'),
                            'reject'  => esc_html__('Recusar', 'juntaplay'),
                        ];
                    } elseif ($status === \JuntaPlay\Data\Groups::STATUS_APPROVED) {
                        $available_actions = [
                            'archive' => esc_html__('Arquivar', 'juntaplay'),
                            'reject'  => esc_html__('Recusar', 'juntaplay'),
                        ];
                    } elseif ($status === \JuntaPlay\Data\Groups::STATUS_REJECTED) {
                        $available_actions = [
                            'approve' => esc_html__('Aprovar novamente', 'juntaplay'),
                            'archive' => esc_html__('Arquivar', 'juntaplay'),
                            'reset'   => esc_html__('Voltar para análise', 'juntaplay'),
                        ];
                    } else {
                        $available_actions = [
                            'approve' => esc_html__('Reativar', 'juntaplay'),
                            'reset'   => esc_html__('Voltar para análise', 'juntaplay'),
                        ];
                    }
                    ?>
                    <tr>
                        <td>
                            <strong><?php echo esc_html($group_title ?: __('(Sem título)', 'juntaplay')); ?></strong>
                            <?php if ($service_name !== '' || $price_regular > 0 || $member_price > 0 || $slots_total > 0) : ?>
                                <div class="description juntaplay-admin-group__meta">
                                    <?php if ($service_name !== '') : ?>
                                        <span>
                                            <?php esc_html_e('Serviço:', 'juntaplay'); ?>
                                            <?php if ($service_url !== '') : ?>
                                                <a href="<?php echo esc_url($service_url); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html($service_name); ?></a>
                                            <?php else : ?>
                                                <?php echo esc_html($service_name); ?>
                                            <?php endif; ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($price_regular > 0) : ?>
                                        <span><?php echo esc_html(sprintf(__('Valor do serviço: R$ %s', 'juntaplay'), number_format_i18n($price_regular, 2))); ?></span>
                                    <?php endif; ?>
                                    <?php if ($price_promo > 0) : ?>
                                        <span><?php echo esc_html(sprintf(__('Promo: R$ %s', 'juntaplay'), number_format_i18n($price_promo, 2))); ?></span>
                                    <?php endif; ?>
                                    <?php if ($member_price > 0) : ?>
                                        <span><?php echo esc_html(sprintf(__('Cota por membro: R$ %s', 'juntaplay'), number_format_i18n($member_price, 2))); ?></span>
                                    <?php endif; ?>
                                    <?php if ($slots_total > 0) : ?>
                                        <span><?php echo esc_html(sprintf(__('Vagas: %1$d (reservadas: %2$d)', 'juntaplay'), $slots_total, $slots_reserved)); ?></span>
                                    <?php endif; ?>
                                    <?php if ($category_label !== '') : ?>
                                        <span><?php echo esc_html(sprintf(__('Categoria: %s', 'juntaplay'), $category_label)); ?></span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            <?php if ($support_channel !== '' || $delivery_time !== '' || $access_method !== '' || $instant_access) : ?>
                                <div class="description juntaplay-admin-group__meta">
                                    <?php if ($support_channel !== '') : ?>
                                        <span><?php echo esc_html(sprintf(__('Suporte: %s', 'juntaplay'), $support_channel)); ?></span>
                                    <?php endif; ?>
                                    <?php if ($delivery_time !== '') : ?>
                                        <span><?php echo esc_html(sprintf(__('Entrega: %s', 'juntaplay'), $delivery_time)); ?></span>
                                    <?php endif; ?>
                                    <?php if ($access_method !== '') : ?>
                                        <span><?php echo esc_html(sprintf(__('Acesso: %s', 'juntaplay'), $access_method)); ?></span>
                                    <?php endif; ?>
                                    <span><?php echo esc_html(sprintf(__('Instantâneo: %s', 'juntaplay'), $instant_access ? __('Ativado', 'juntaplay') : __('Desativado', 'juntaplay'))); ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if ($review_note !== '') : ?>
                                <div class="description"><?php echo esc_html($review_note); ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php echo esc_html($owner_name ?: __('Usuário', 'juntaplay')); ?>
                            <?php if ($owner_email !== '') : ?>
                                <div class="description"><a href="mailto:<?php echo esc_attr($owner_email); ?>"><?php echo esc_html($owner_email); ?></a></div>
                            <?php endif; ?>
                        </td>
                        <td><?php echo esc_html($status_label); ?></td>
                        <td><?php echo $pool_title !== '' ? esc_html($pool_title) : '—'; ?></td>
                        <td style="text-align:center; "><?php echo esc_html(number_format_i18n($members_count)); ?></td>
                        <td><?php echo esc_html($created_at_display); ?></td>
                        <td>
                            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="juntaplay-group-action">
                                <?php wp_nonce_field('juntaplay_group_action'); ?>
                                <input type="hidden" name="action" value="juntaplay_group_action" />
                                <input type="hidden" name="group_id" value="<?php echo esc_attr((string) $group_id); ?>" />
                                <input type="hidden" name="redirect_to" value="<?php echo esc_attr($current_url); ?>" />
                                <select name="group_action" required>
                                    <option value=""><?php esc_html_e('Selecione…', 'juntaplay'); ?></option>
                                    <?php foreach ($available_actions as $value => $label) : ?>
                                        <option value="<?php echo esc_attr($value); ?>"><?php echo esc_html($label); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="text" name="group_note" class="regular-text" placeholder="<?php esc_attr_e('Mensagem ao criador (opcional)', 'juntaplay'); ?>" />
                                <button type="submit" class="button button-secondary"><?php esc_html_e('Aplicar', 'juntaplay'); ?></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
