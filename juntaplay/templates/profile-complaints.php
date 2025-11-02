<?php
/**
 * JuntaPlay profile complaint center template.
 */

declare(strict_types=1);

use JuntaPlay\Data\GroupComplaintMessages;

if (!defined('ABSPATH')) {
    exit;
}

$complaints_context = isset($complaints_context) && is_array($complaints_context) ? $complaints_context : [];

$filter          = isset($complaints_context['filter']) ? (string) $complaints_context['filter'] : 'open';
$counts          = isset($complaints_context['counts']) && is_array($complaints_context['counts']) ? $complaints_context['counts'] : [];
$complaints_list = isset($complaints_context['complaints']) && is_array($complaints_context['complaints']) ? $complaints_context['complaints'] : [];
$active_id       = isset($complaints_context['active_id']) ? (int) $complaints_context['active_id'] : 0;
$detail          = isset($complaints_context['detail']) && is_array($complaints_context['detail']) ? $complaints_context['detail'] : null;
$detail_role     = isset($complaints_context['detail_role']) ? (string) $complaints_context['detail_role'] : 'viewer';
$messages        = isset($complaints_context['messages']) && is_array($complaints_context['messages']) ? $complaints_context['messages'] : [];
$errors_map      = isset($complaints_context['errors']) && is_array($complaints_context['errors']) ? $complaints_context['errors'] : [];
$success_map     = isset($complaints_context['success']) && is_array($complaints_context['success']) ? $complaints_context['success'] : [];
$global_errors   = isset($complaints_context['global_errors']) && is_array($complaints_context['global_errors']) ? $complaints_context['global_errors'] : [];
$nonces          = isset($complaints_context['nonces']) && is_array($complaints_context['nonces']) ? $complaints_context['nonces'] : [];
$limits          = isset($complaints_context['limits']) && is_array($complaints_context['limits']) ? $complaints_context['limits'] : [];
$urls            = isset($complaints_context['urls']) && is_array($complaints_context['urls']) ? $complaints_context['urls'] : [];
$can_reply       = !empty($complaints_context['can_reply']);
$can_propose     = !empty($complaints_context['can_propose']);
$can_accept      = !empty($complaints_context['can_accept']);
$has_proposal    = !empty($complaints_context['has_pending_proposal']);
$latest_proposal = isset($complaints_context['latest_proposal']) && is_array($complaints_context['latest_proposal']) ? $complaints_context['latest_proposal'] : null;
$active_ticket   = isset($complaints_context['active_ticket']) ? (string) $complaints_context['active_ticket'] : '';

$filter_urls = isset($urls['filters']) && is_array($urls['filters']) ? $urls['filters'] : [];
$base_url    = isset($urls['base']) ? (string) $urls['base'] : '';
$groups_url  = isset($urls['groups']) ? (string) $urls['groups'] : '';

$participant_counts = isset($counts['participant']) && is_array($counts['participant']) ? $counts['participant'] : [];
$owner_counts       = isset($counts['owner']) && is_array($counts['owner']) ? $counts['owner'] : [];

$participant_open = (int) (($participant_counts['open'] ?? 0) + ($participant_counts['under_review'] ?? 0));
$owner_open       = (int) (($owner_counts['open'] ?? 0) + ($owner_counts['under_review'] ?? 0));

$current_errors  = $active_id > 0 && isset($errors_map[$active_id]) && is_array($errors_map[$active_id]) ? $errors_map[$active_id] : [];
$current_success = $active_id > 0 && isset($success_map[$active_id]) && is_array($success_map[$active_id]) ? $success_map[$active_id] : [];

$reply_nonce  = isset($nonces['reply']) ? (string) $nonces['reply'] : '';
$accept_nonce = isset($nonces['accept']) ? (string) $nonces['accept'] : '';

$max_files = isset($limits['max_files']) ? (int) $limits['max_files'] : 3;
$max_size  = isset($limits['max_size']) ? (int) $limits['max_size'] : 5 * 1024 * 1024;
$max_size_mb = max(1, round($max_size / 1048576, 1));

$empty_image = defined('JP_URL') && JP_URL !== ''
    ? trailingslashit(JP_URL) . 'assets/images/complaint-empty.svg'
    : plugins_url('../assets/images/complaint-empty.svg', __FILE__);

$has_detail = $detail !== null && $active_id > 0;
$root_classes = 'juntaplay-complaints';

if ($has_detail) {
    $root_classes .= ' is-detail-open';
}

?>
<div class="<?php echo esc_attr($root_classes); ?>" data-complaint-center data-complaint-open="<?php echo esc_attr($has_detail ? 'true' : 'false'); ?>">
    <aside class="juntaplay-complaints__sidebar" data-complaint-sidebar>
        <header class="juntaplay-complaints__header">
            <h3><?php esc_html_e('Reclamações', 'juntaplay'); ?></h3>
            <p><?php esc_html_e('Gerencie tickets como participante ou administrador.', 'juntaplay'); ?></p>
        </header>
        <nav class="juntaplay-complaints__filters" aria-label="<?php esc_attr_e('Filtrar reclamações', 'juntaplay'); ?>">
            <a class="juntaplay-chip<?php echo $filter === 'open' ? ' is-active' : ''; ?>" href="<?php echo esc_url($filter_urls['open'] ?? $base_url); ?>">
                <?php echo esc_html__('Abertas', 'juntaplay'); ?>
            </a>
            <a class="juntaplay-chip<?php echo $filter === 'closed' ? ' is-active' : ''; ?>" href="<?php echo esc_url($filter_urls['closed'] ?? $base_url); ?>">
                <?php echo esc_html__('Finalizadas', 'juntaplay'); ?>
            </a>
            <a class="juntaplay-chip<?php echo $filter === 'all' ? ' is-active' : ''; ?>" href="<?php echo esc_url($filter_urls['all'] ?? $base_url); ?>">
                <?php echo esc_html__('Todas', 'juntaplay'); ?>
            </a>
        </nav>
        <ul class="juntaplay-complaints__summary" role="list">
            <li>
                <strong><?php esc_html_e('Como participante', 'juntaplay'); ?></strong>
                <span><?php echo esc_html(number_format_i18n($participant_open)); ?></span>
            </li>
            <li>
                <strong><?php esc_html_e('Como administrador', 'juntaplay'); ?></strong>
                <span><?php echo esc_html(number_format_i18n($owner_open)); ?></span>
            </li>
        </ul>
        <?php if ($groups_url !== '') : ?>
            <a class="juntaplay-button juntaplay-button--ghost juntaplay-complaints__new" href="<?php echo esc_url($groups_url); ?>">
                <?php esc_html_e('Abrir nova reclamação', 'juntaplay'); ?>
            </a>
        <?php endif; ?>
        <div class="juntaplay-complaints__list" role="list">
            <?php if ($complaints_list) : ?>
                <?php foreach ($complaints_list as $item) :
                    if (!is_array($item)) {
                        continue;
                    }

                    $item_id      = isset($item['id']) ? (int) $item['id'] : 0;
                    $item_ticket  = isset($item['ticket']) ? (string) $item['ticket'] : '';
                    $item_title   = isset($item['group_title']) ? (string) $item['group_title'] : '';
                    $item_summary = isset($item['summary']) ? (string) $item['summary'] : '';
                    $item_status  = isset($item['status_label']) ? (string) $item['status_label'] : '';
                    $item_tone    = isset($item['status_tone']) ? (string) $item['status_tone'] : 'info';
                    $item_role    = isset($item['role']) ? (string) $item['role'] : 'participant';
                    $item_url     = isset($item['url']) ? (string) $item['url'] : '';
                    $is_active    = !empty($item['is_active']);
                    ?>
                    <a class="juntaplay-complaints__item<?php echo $is_active ? ' is-active' : ''; ?>" href="<?php echo esc_url($item_url ?: $base_url); ?>" data-complaint-select>
                        <span class="juntaplay-complaints__item-ticket"><?php echo esc_html($item_ticket !== '' ? $item_ticket : sprintf('#%d', $item_id)); ?></span>
                        <?php if ($item_title !== '') : ?>
                            <strong class="juntaplay-complaints__item-title"><?php echo esc_html($item_title); ?></strong>
                        <?php endif; ?>
                        <?php if ($item_summary !== '') : ?>
                            <span class="juntaplay-complaints__item-summary"><?php echo esc_html($item_summary); ?></span>
                        <?php endif; ?>
                        <span class="juntaplay-badge juntaplay-badge--<?php echo esc_attr($item_tone); ?>"><?php echo esc_html($item_status); ?></span>
                        <span class="juntaplay-complaints__item-role"><?php echo esc_html($item_role === 'owner' ? __('Administrador', 'juntaplay') : __('Participante', 'juntaplay')); ?></span>
                    </a>
                <?php endforeach; ?>
            <?php else : ?>
                <div class="juntaplay-complaints__empty">
                    <img src="<?php echo esc_url($empty_image); ?>" alt="" width="280" height="200" loading="lazy" />
                    <p><?php esc_html_e('Nenhuma reclamação encontrada para o filtro selecionado.', 'juntaplay'); ?></p>
                    <?php if ($groups_url !== '') : ?>
                        <a class="juntaplay-button juntaplay-button--ghost" href="<?php echo esc_url($groups_url); ?>"><?php esc_html_e('Abrir primeira reclamação', 'juntaplay'); ?></a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </aside>
    <?php
    $complaint_detail_context = [
        'active_ticket'   => $active_ticket,
        'detail'          => $detail,
        'detail_role'     => $detail_role,
        'global_errors'   => $global_errors,
        'current_errors'  => $current_errors,
        'current_success' => $current_success,
        'messages'        => $messages,
        'can_reply'       => $can_reply,
        'can_propose'     => $can_propose,
        'can_accept'      => $can_accept,
        'has_proposal'    => $has_proposal,
        'latest_proposal' => $latest_proposal,
        'reply_nonce'     => $reply_nonce,
        'accept_nonce'    => $accept_nonce,
        'max_files'       => $max_files,
        'max_size_mb'     => $max_size_mb,
        'base_url'        => $base_url,
        'empty_image'     => $empty_image,
    ];

    include JP_DIR . 'templates/profile-complaint-detail.php';
    ?>
</div>
