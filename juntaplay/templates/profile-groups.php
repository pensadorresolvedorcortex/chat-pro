<?php
/**
 * JuntaPlay profile groups hub template.
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$group_context = isset($group_context) && is_array($group_context) ? $group_context : [];

$groups_owned   = isset($group_context['groups_owned']) && is_array($group_context['groups_owned']) ? $group_context['groups_owned'] : [];
$groups_member  = isset($group_context['groups_member']) && is_array($group_context['groups_member']) ? $group_context['groups_member'] : [];
$group_counts   = isset($group_context['group_counts']) && is_array($group_context['group_counts']) ? $group_context['group_counts'] : [];
$pool_choices   = isset($group_context['pool_choices']) && is_array($group_context['pool_choices']) ? $group_context['pool_choices'] : [];
$group_categories = isset($group_context['group_categories']) && is_array($group_context['group_categories']) ? $group_context['group_categories'] : [];
$group_suggestions = isset($group_context['group_suggestions']) && is_array($group_context['group_suggestions']) ? $group_context['group_suggestions'] : [];
$form_errors    = isset($group_context['form_errors']) && is_array($group_context['form_errors']) ? $group_context['form_errors'] : [];
$form_values    = isset($group_context['form_values']) && is_array($group_context['form_values']) ? $group_context['form_values'] : [];
$complaint_errors  = isset($group_context['complaint_errors']) && is_array($group_context['complaint_errors']) ? $group_context['complaint_errors'] : [];
$complaint_drafts  = isset($group_context['complaint_drafts']) && is_array($group_context['complaint_drafts']) ? $group_context['complaint_drafts'] : [];
$complaint_success = isset($group_context['complaint_success']) && is_array($group_context['complaint_success']) ? $group_context['complaint_success'] : [];
$complaint_reasons = isset($group_context['complaint_reasons']) && is_array($group_context['complaint_reasons']) ? $group_context['complaint_reasons'] : [];
$complaint_limits  = isset($group_context['complaint_limits']) && is_array($group_context['complaint_limits']) ? $group_context['complaint_limits'] : [];
$complaint_summary = isset($group_context['complaint_summary']) && is_array($group_context['complaint_summary']) ? $group_context['complaint_summary'] : [];

$complaint_max_files = isset($complaint_limits['max_files']) ? (int) $complaint_limits['max_files'] : 3;
$complaint_max_size  = isset($complaint_limits['max_size']) ? (int) $complaint_limits['max_size'] : 5 * 1024 * 1024;
$complaint_max_size_mb = max(1, round($complaint_max_size / 1048576, 1));

$total_groups   = isset($group_counts['total']) ? (int) $group_counts['total'] : count($groups_owned) + count($groups_member);
$owned_count    = isset($group_counts['owned']) ? (int) $group_counts['owned'] : count($groups_owned);
$member_count   = isset($group_counts['member']) ? (int) $group_counts['member'] : count($groups_member);
$pending_count  = isset($group_counts['pending']) ? (int) $group_counts['pending'] : 0;
$approved_count = isset($group_counts['approved']) ? (int) $group_counts['approved'] : 0;
$rejected_count = isset($group_counts['rejected']) ? (int) $group_counts['rejected'] : 0;
$archived_count = isset($group_counts['archived']) ? (int) $group_counts['archived'] : 0;

$current_name        = isset($form_values['name']) ? (string) $form_values['name'] : '';
$current_pool        = isset($form_values['pool']) ? (string) $form_values['pool'] : '';
$current_description = isset($form_values['description']) ? (string) $form_values['description'] : '';
$current_service     = isset($form_values['service']) ? (string) $form_values['service'] : '';
$current_service_url = isset($form_values['service_url']) ? (string) $form_values['service_url'] : '';
$current_rules       = isset($form_values['rules']) ? (string) $form_values['rules'] : '';
$current_price       = isset($form_values['price']) ? (string) $form_values['price'] : '';
$promo_enabled       = isset($form_values['promo_enabled']) ? (string) $form_values['promo_enabled'] === 'on' : false;
$current_promo       = isset($form_values['promo']) ? (string) $form_values['promo'] : '';
$current_total       = isset($form_values['total']) ? (string) $form_values['total'] : '';
$current_reserved    = isset($form_values['reserved']) ? (string) $form_values['reserved'] : '';
$current_member      = isset($form_values['member_price']) ? (string) $form_values['member_price'] : '';
$member_was_generated = isset($form_values['member_generated']) ? (string) $form_values['member_generated'] === 'yes' : false;
$current_support     = isset($form_values['support']) ? (string) $form_values['support'] : '';
$current_delivery    = isset($form_values['delivery']) ? (string) $form_values['delivery'] : '';
$current_access      = isset($form_values['access']) ? (string) $form_values['access'] : '';
$cover_placeholder   = defined('JP_GROUP_COVER_PLACEHOLDER') ? JP_GROUP_COVER_PLACEHOLDER : '';
$current_cover_id    = isset($form_values['cover']) ? (int) $form_values['cover'] : 0;
$current_cover_preview = isset($form_values['cover_preview']) ? (string) $form_values['cover_preview'] : '';
if ($current_cover_preview === '' && $current_cover_id > 0) {
    $attachment_preview = wp_get_attachment_image_url($current_cover_id, 'large');
    if ($attachment_preview) {
        $current_cover_preview = $attachment_preview;
    }
}
if ($current_cover_preview === '') {
    $current_cover_preview = $cover_placeholder;
}
$current_category    = isset($form_values['category']) ? (string) $form_values['category'] : 'other';
if ($current_category === '' || !isset($group_categories[$current_category])) {
    $current_category = 'other';
}
$category_label = isset($group_categories[$current_category]) ? (string) $group_categories[$current_category] : ucwords(str_replace(['-', '_'], ' ', $current_category));
$current_instant     = isset($form_values['instant_access']) ? (string) $form_values['instant_access'] === 'on' : false;

$site_host = wp_parse_url(home_url(), PHP_URL_HOST);
if (!$site_host) {
    $site_host = preg_replace('~^https?://~', '', home_url());
}
$site_host = is_string($site_host) ? trim($site_host, '/') : '';

$group_type_label = esc_html__('Público', 'juntaplay');
$category_display = esc_html($category_label);
$instant_display  = $current_instant ? esc_html__('Ativado', 'juntaplay') : esc_html__('Desativado', 'juntaplay');

$price_display = '';
if ($current_price !== '') {
    $price_display = sprintf(__('R$ %s', 'juntaplay'), esc_html($current_price));
    $price_display = esc_html($price_display);
}

$promo_display = esc_html__('Não', 'juntaplay');
$promo_flag    = esc_html__('Não', 'juntaplay');
if ($promo_enabled && $current_promo !== '') {
    $promo_display = sprintf(__('R$ %s', 'juntaplay'), esc_html($current_promo));
    $promo_display = esc_html($promo_display);
    $promo_flag    = esc_html__('Sim', 'juntaplay');
}

$member_display = '';
if ($current_member !== '') {
    $member_display = sprintf(__('R$ %s', 'juntaplay'), esc_html($current_member));
    $member_display = esc_html($member_display);
}

$share_lines = [];
if ($current_service !== '') {
    $share_lines[] = sprintf(esc_html__('Serviço: %s', 'juntaplay'), esc_html($current_service));
}
if ($current_name !== '') {
    $share_lines[] = sprintf(esc_html__('Nome do grupo: %s', 'juntaplay'), esc_html($current_name));
}
$share_lines[] = sprintf(esc_html__('Tipo: %s', 'juntaplay'), $group_type_label);
$share_lines[] = sprintf(esc_html__('Categoria: %s', 'juntaplay'), $category_display);
if ($current_service_url !== '') {
    $share_lines[] = sprintf(esc_html__('Site: %s', 'juntaplay'), esc_html($current_service_url));
}
if ($current_rules !== '') {
    $share_lines[] = sprintf(esc_html__('Regras: %s', 'juntaplay'), esc_html($current_rules));
}
if ($current_description !== '') {
    $share_lines[] = sprintf(esc_html__('Descrição: %s', 'juntaplay'), esc_html($current_description));
}
if ($price_display !== '') {
    $share_lines[] = sprintf(esc_html__('Valor do serviço: %s', 'juntaplay'), $price_display);
}
$share_lines[] = sprintf(esc_html__('É valor promocional?: %s', 'juntaplay'), $promo_flag);
$share_lines[] = sprintf(esc_html__('Valor promocional: %s', 'juntaplay'), $promo_display);
if ($current_total !== '') {
    $share_lines[] = sprintf(esc_html__('Vagas totais: %s', 'juntaplay'), esc_html($current_total));
}
if ($current_reserved !== '') {
    $share_lines[] = sprintf(esc_html__('Reservadas para você: %s', 'juntaplay'), esc_html($current_reserved));
}
if ($member_display !== '') {
    $share_lines[] = sprintf(esc_html__('Os membros vão pagar: %s', 'juntaplay'), $member_display);
}
if ($current_support !== '') {
    $share_lines[] = sprintf(esc_html__('Suporte aos membros: %s', 'juntaplay'), esc_html($current_support));
}
if ($current_delivery !== '') {
    $share_lines[] = sprintf(esc_html__('Envio de acesso: %s', 'juntaplay'), esc_html($current_delivery));
}
if ($current_access !== '') {
    $share_lines[] = sprintf(esc_html__('Forma de acesso: %s', 'juntaplay'), esc_html($current_access));
}
$share_lines[] = sprintf(esc_html__('Acesso instantâneo: %s', 'juntaplay'), $instant_display);

$share_text = implode("\n", $share_lines);

$member_preview_text = '';
if ($current_member !== '') {
    $member_preview_text = sprintf(
        /* translators: %s: formatted price */
        __('Cobrando dos membros: R$ %s por vaga disponível.', 'juntaplay'),
        $current_member
    );
}

$all_groups = array_merge($groups_owned, $groups_member);
$campanhas_page_id = (int) get_option('juntaplay_page_campanhas');
$campaigns_url = $campanhas_page_id ? get_permalink($campanhas_page_id) : home_url('/campanhas');
?>
<div class="juntaplay-groups" data-groups data-role-filter="all" data-status-filter="all">
    <div class="juntaplay-groups__filters" role="tablist">
        <button type="button" class="juntaplay-chip is-active" data-group-filter="all" aria-selected="true">
            <?php echo esc_html(sprintf(__('Todos (%d)', 'juntaplay'), $total_groups)); ?>
        </button>
        <button type="button" class="juntaplay-chip" data-group-filter="owned" aria-selected="false">
            <?php echo esc_html(sprintf(__('Meus grupos (%d)', 'juntaplay'), $owned_count)); ?>
        </button>
        <button type="button" class="juntaplay-chip" data-group-filter="member" aria-selected="false">
            <?php echo esc_html(sprintf(__('Participando (%d)', 'juntaplay'), $member_count)); ?>
        </button>
    </div>

    <div class="juntaplay-groups__status-filter">
        <label for="jp-group-status-filter"><?php echo esc_html__('Status', 'juntaplay'); ?></label>
        <select id="jp-group-status-filter" class="juntaplay-form__input" data-group-status-filter>
            <option value="all"><?php echo esc_html__('Todos os status', 'juntaplay'); ?></option>
            <option value="pending"><?php echo esc_html(sprintf(__('Em análise (%d)', 'juntaplay'), $pending_count)); ?></option>
            <option value="approved"><?php echo esc_html(sprintf(__('Aprovados (%d)', 'juntaplay'), $approved_count)); ?></option>
            <option value="rejected"><?php echo esc_html(sprintf(__('Recusados (%d)', 'juntaplay'), $rejected_count)); ?></option>
            <option value="archived"><?php echo esc_html(sprintf(__('Arquivados (%d)', 'juntaplay'), $archived_count)); ?></option>
        </select>
    </div>

    <div class="juntaplay-groups__list" data-group-list>
        <?php if ($all_groups) : ?>
            <?php foreach ($all_groups as $group) :
                if (!is_array($group)) {
                    continue;
                }

                $group_id        = isset($group['id']) ? (int) $group['id'] : 0;
                $group_title     = isset($group['title']) ? (string) $group['title'] : '';
                $group_role      = isset($group['membership_role']) ? (string) $group['membership_role'] : 'member';
                $role_label      = isset($group['role_label']) ? (string) $group['role_label'] : '';
                $role_tone       = isset($group['role_tone']) ? (string) $group['role_tone'] : '';
                $status          = isset($group['status']) ? (string) $group['status'] : '';
                $status_label    = isset($group['status_label']) ? (string) $group['status_label'] : '';
                $status_tone     = isset($group['status_tone']) ? (string) $group['status_tone'] : '';
                $status_message  = isset($group['status_message']) ? (string) $group['status_message'] : '';
                $members_count   = isset($group['members_count']) ? (int) $group['members_count'] : 0;
                $created_human   = isset($group['created_human']) ? (string) $group['created_human'] : '';
                $pool_title      = isset($group['pool_title']) ? (string) $group['pool_title'] : '';
                $pool_link       = isset($group['pool_link']) ? (string) $group['pool_link'] : '';
                $review_note     = isset($group['review_note']) ? (string) $group['review_note'] : '';
                $reviewed_human  = isset($group['reviewed_human']) ? (string) $group['reviewed_human'] : '';
                $service_name    = isset($group['service_name']) ? (string) $group['service_name'] : '';
                $service_url     = isset($group['service_url']) ? (string) $group['service_url'] : '';
                $group_rules     = isset($group['rules']) ? (string) $group['rules'] : '';
                $price_display   = isset($group['price_regular_display']) ? (string) $group['price_regular_display'] : '';
                $promo_display   = isset($group['price_promotional_display']) ? (string) $group['price_promotional_display'] : '';
                $member_display  = isset($group['member_price_display']) ? (string) $group['member_price_display'] : '';
                $enrollment_total = isset($group['enrollment_total_display']) ? (string) $group['enrollment_total_display'] : '';
                $slots_summary   = isset($group['slots_summary']) ? (string) $group['slots_summary'] : '';
                $support_channel = isset($group['support_channel']) ? (string) $group['support_channel'] : '';
                $delivery_time   = isset($group['delivery_time']) ? (string) $group['delivery_time'] : '';
                $access_method   = isset($group['access_method']) ? (string) $group['access_method'] : '';
                $category_label  = isset($group['category_label']) ? (string) $group['category_label'] : '';
                $instant_label   = isset($group['instant_access_label']) ? (string) $group['instant_access_label'] : '';
                $blocked_notice  = isset($group['blocked_notice']) ? (string) $group['blocked_notice'] : '';
                $members_preview = isset($group['members_preview']) && is_array($group['members_preview']) ? $group['members_preview'] : [];
                $member_names    = isset($members_preview['names']) && is_array($members_preview['names']) ? array_filter($members_preview['names'], 'is_string') : [];
                $members_remaining = isset($members_preview['remaining']) ? (int) $members_preview['remaining'] : 0;
                $faq_items       = isset($group['faq_items']) && is_array($group['faq_items']) ? array_filter($group['faq_items'], 'is_array') : [];
                $share_domain    = isset($group['share_domain']) ? (string) $group['share_domain'] : '';
                $share_snippet   = isset($group['share_snippet']) ? (string) $group['share_snippet'] : '';
                $complaints_meta = isset($group['complaints']) && is_array($group['complaints']) ? $group['complaints'] : [];
                $complaints_open = isset($complaints_meta['open']) ? (int) $complaints_meta['open'] : 0;
                $complaints_total = isset($complaints_meta['total']) ? (int) $complaints_meta['total'] : 0;
                $complaint_latest = isset($complaints_meta['latest']) && is_array($complaints_meta['latest']) ? $complaints_meta['latest'] : [];
                $complaint_status_label = isset($complaint_latest['status_label']) ? (string) $complaint_latest['status_label'] : '';
                $complaint_status_tone  = isset($complaint_latest['status_tone']) ? (string) $complaint_latest['status_tone'] : 'info';
                $complaint_status_message = isset($complaint_latest['status_message']) ? (string) $complaint_latest['status_message'] : '';
                $complaint_summary_text = isset($complaint_latest['summary']) ? (string) $complaint_latest['summary'] : '';
                $complaint_key    = 'group_complaint_' . $group_id;
                $complaint_messages = isset($complaint_errors[$complaint_key]) && is_array($complaint_errors[$complaint_key]) ? $complaint_errors[$complaint_key] : [];
                $complaint_draft  = isset($complaint_drafts[$group_id]) && is_array($complaint_drafts[$group_id]) ? $complaint_drafts[$group_id] : [];
                $complaint_success_messages = isset($complaint_success[$group_id]) && is_array($complaint_success[$group_id]) ? $complaint_success[$group_id] : [];
                $availability_label = isset($group['availability_label']) ? (string) $group['availability_label'] : '';
                $availability_tone  = isset($group['availability_tone']) ? (string) $group['availability_tone'] : '';
                $slots_total_label  = isset($group['slots_total_label']) ? (string) $group['slots_total_label'] : '';
                $slots_total_hint   = isset($group['slots_total_hint']) ? (string) $group['slots_total_hint'] : '';
                $slots_available_label = isset($group['slots_available_label']) ? (string) $group['slots_available_label'] : '';
                $price_highlight    = isset($group['price_highlight']) ? (string) $group['price_highlight'] : '';
                $cta_label          = isset($group['cta_label']) ? (string) $group['cta_label'] : '';
                $cta_variant        = isset($group['cta_variant']) ? (string) $group['cta_variant'] : 'ghost';
                $cta_disabled       = !empty($group['cta_disabled']);
                $cta_url            = isset($group['cta_url']) ? (string) $group['cta_url'] : '';
            ?>
                <?php
                $cover_url = isset($group['cover_url']) ? (string) $group['cover_url'] : $cover_placeholder;
                $cover_alt = isset($group['cover_alt']) ? (string) $group['cover_alt'] : esc_html__('Capa do grupo', 'juntaplay');
                $cover_placeholder_flag = !empty($group['cover_placeholder']);
                $show_cover_hint = $cover_placeholder_flag && in_array($group_role, ['owner', 'manager'], true);
                ?>
                <article class="juntaplay-group-card" data-group-item data-group-role="<?php echo esc_attr($group_role); ?>" data-group-status="<?php echo esc_attr($status); ?>">
                    <figure class="juntaplay-group-card__cover<?php echo $cover_placeholder_flag ? ' is-placeholder' : ''; ?>">
                        <img src="<?php echo esc_url($cover_url); ?>" alt="<?php echo esc_attr($cover_alt); ?>" loading="lazy" width="495" height="370" />
                        <?php if ($show_cover_hint) : ?>
                            <span><?php esc_html_e('Adicione uma capa para aumentar as conversões.', 'juntaplay'); ?></span>
                        <?php endif; ?>
                    </figure>
                    <div class="juntaplay-group-card__body">
                        <header class="juntaplay-group-card__header">
                            <div class="juntaplay-group-card__headline">
                                <h3 class="juntaplay-group-card__title"><?php echo esc_html($group_title); ?></h3>
                                <?php if ($role_label !== '') : ?>
                                    <span class="juntaplay-group-card__role-badge juntaplay-badge juntaplay-badge--<?php echo esc_attr($role_tone ?: 'info'); ?>"><?php echo esc_html($role_label); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="juntaplay-group-card__chips">
                                <?php if ($availability_label !== '') : ?>
                                    <span class="juntaplay-badge juntaplay-badge--<?php echo esc_attr($availability_tone ?: 'info'); ?>"><?php echo esc_html($availability_label); ?></span>
                                <?php endif; ?>
                                <?php if ($status_label !== '') : ?>
                                    <span class="juntaplay-badge juntaplay-badge--<?php echo esc_attr($status_tone ?: 'info'); ?>"><?php echo esc_html($status_label); ?></span>
                                <?php endif; ?>
                            </div>
                        </header>
                        <div class="juntaplay-group-card__quick">
                            <div class="juntaplay-group-card__quick-item">
                                <span class="juntaplay-group-card__quick-label"><?php esc_html_e('Quantidade de vagas', 'juntaplay'); ?></span>
                                <?php if ($slots_total_label !== '') : ?>
                                    <strong class="juntaplay-group-card__quick-value"><?php echo esc_html($slots_total_label); ?></strong>
                                <?php endif; ?>
                                <?php if ($slots_available_label !== '') : ?>
                                    <span class="juntaplay-group-card__quick-hint"><?php echo esc_html($slots_available_label); ?></span>
                                <?php elseif ($slots_total_hint !== '') : ?>
                                    <span class="juntaplay-group-card__quick-hint"><?php echo esc_html($slots_total_hint); ?></span>
                                <?php endif; ?>
                            </div>
                            <?php if ($price_highlight !== '') : ?>
                                <div class="juntaplay-group-card__quick-item">
                                    <span class="juntaplay-group-card__quick-label"><?php esc_html_e('Valor por membro', 'juntaplay'); ?></span>
                                    <strong class="juntaplay-group-card__quick-value"><?php echo esc_html($price_highlight); ?></strong>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php if ($cta_label !== '') : ?>
                            <div class="juntaplay-group-card__cta">
                                <?php
                                $cta_classes = ['juntaplay-button'];
                                $cta_classes[] = $cta_variant === 'primary' ? 'juntaplay-button--primary' : 'juntaplay-button--ghost';
                                if ($cta_disabled) {
                                    $cta_classes[] = 'is-disabled';
                                }
                                $cta_class_attr = implode(' ', array_map('sanitize_html_class', $cta_classes));
                                ?>
                                <?php if (!$cta_disabled && $cta_url !== '') : ?>
                                    <a class="<?php echo esc_attr($cta_class_attr); ?>" href="<?php echo esc_url($cta_url); ?>"><?php echo esc_html($cta_label); ?></a>
                                <?php else : ?>
                                    <button type="button" class="<?php echo esc_attr($cta_class_attr); ?>" disabled><?php echo esc_html($cta_label); ?></button>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        <button type="button" class="juntaplay-group-card__toggle" data-group-card-toggle aria-expanded="false" data-label-expand="<?php echo esc_attr__('Ver detalhes', 'juntaplay'); ?>" data-label-collapse="<?php echo esc_attr__('Ocultar detalhes', 'juntaplay'); ?>">
                            <span class="juntaplay-group-card__toggle-label"><?php esc_html_e('Ver detalhes', 'juntaplay'); ?></span>
                            <svg width="16" height="16" viewBox="0 0 16 16" aria-hidden="true" focusable="false">
                                <path d="M4 6l4 4l4-4" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </button>
                        <div class="juntaplay-group-card__details-extended" data-group-card-details hidden>
                    <div class="juntaplay-group-card__meta">
                        <?php if ($created_human !== '') : ?>
                            <span class="juntaplay-group-card__created"><?php echo esc_html($created_human); ?></span>
                        <?php endif; ?>
                        <?php if ($reviewed_human !== '' && $status !== 'pending') : ?>
                            <span class="juntaplay-group-card__reviewed"><?php echo esc_html($reviewed_human); ?></span>
                        <?php endif; ?>
                    </div>
                    <ul class="juntaplay-group-card__details">
                        <?php if ($service_name !== '') : ?>
                            <li>
                                <strong><?php echo esc_html__('Serviço', 'juntaplay'); ?>:</strong>
                                <?php if ($service_url !== '') : ?>
                                    <a href="<?php echo esc_url($service_url); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html($service_name); ?></a>
                                <?php else : ?>
                                    <?php echo esc_html($service_name); ?>
                                <?php endif; ?>
                            </li>
                        <?php endif; ?>
                        <?php if ($price_display !== '') : ?>
                            <li>
                                <strong><?php echo esc_html__('Valor do serviço', 'juntaplay'); ?>:</strong>
                                <?php echo esc_html($price_display); ?>
                            </li>
                        <?php endif; ?>
                        <?php if ($promo_display !== '') : ?>
                            <li>
                                <strong><?php echo esc_html__('Oferta promocional', 'juntaplay'); ?>:</strong>
                                <?php echo esc_html($promo_display); ?>
                            </li>
                        <?php endif; ?>
                        <?php if ($member_display !== '') : ?>
                            <li>
                                <strong><?php echo esc_html__('Cobrado de cada membro', 'juntaplay'); ?>:</strong>
                                <?php echo esc_html($member_display); ?>
                            </li>
                        <?php endif; ?>
                        <li>
                            <strong><?php echo esc_html__('Tipo', 'juntaplay'); ?>:</strong>
                            <?php echo esc_html__('Público', 'juntaplay'); ?>
                        </li>
                        <?php if ($category_label !== '') : ?>
                            <li>
                                <strong><?php echo esc_html__('Categoria', 'juntaplay'); ?>:</strong>
                                <?php echo esc_html($category_label); ?>
                            </li>
                        <?php endif; ?>
                        <li>
                            <strong><?php echo esc_html__('Participantes', 'juntaplay'); ?>:</strong>
                            <?php echo esc_html(number_format_i18n($members_count)); ?>
                        </li>
                        <li>
                            <strong><?php echo esc_html__('Campanha', 'juntaplay'); ?>:</strong>
                            <?php echo $pool_title !== '' ? esc_html($pool_title) : '<span class="juntaplay-profile__empty">' . esc_html__('Ainda não vinculada', 'juntaplay') . '</span>'; ?>
                        </li>
                        <?php if ($slots_summary !== '') : ?>
                            <li>
                                <strong><?php echo esc_html__('Vagas', 'juntaplay'); ?>:</strong>
                                <?php echo esc_html($slots_summary); ?>
                            </li>
                        <?php endif; ?>
                        <?php if ($support_channel !== '') : ?>
                            <li>
                                <strong><?php echo esc_html__('Suporte aos membros', 'juntaplay'); ?>:</strong>
                                <?php echo esc_html($support_channel); ?>
                            </li>
                        <?php endif; ?>
                        <?php if ($delivery_time !== '') : ?>
                            <li>
                                <strong><?php echo esc_html__('Envio de acesso', 'juntaplay'); ?>:</strong>
                                <?php echo esc_html($delivery_time); ?>
                            </li>
                        <?php endif; ?>
                        <?php if ($access_method !== '') : ?>
                            <li>
                                <strong><?php echo esc_html__('Forma de acesso', 'juntaplay'); ?>:</strong>
                                <?php echo esc_html($access_method); ?>
                            </li>
                        <?php endif; ?>
                        <?php if ($instant_label !== '') : ?>
                            <li>
                                <strong><?php echo esc_html__('Acesso instantâneo', 'juntaplay'); ?>:</strong>
                                <?php echo esc_html($instant_label); ?>
                            </li>
                        <?php endif; ?>
                    </ul>
                    <?php if ($enrollment_total !== '') : ?>
                        <div class="juntaplay-group-card__enrollment">
                            <span class="juntaplay-group-card__enrollment-label"><?php echo esc_html__('Total da inscrição', 'juntaplay'); ?></span>
                            <strong class="juntaplay-group-card__enrollment-value"><?php echo esc_html($enrollment_total); ?></strong>
                            <?php if ($blocked_notice !== '') : ?>
                                <p class="juntaplay-group-card__enrollment-note"><?php echo esc_html($blocked_notice); ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($member_names) : ?>
                        <div class="juntaplay-group-card__members">
                            <h4><?php echo esc_html__('Quem faz parte', 'juntaplay'); ?></h4>
                            <ul>
                                <?php foreach ($member_names as $member_name) : ?>
                                    <li><?php echo esc_html((string) $member_name); ?></li>
                                <?php endforeach; ?>
                                <?php if ($members_remaining > 0) : ?>
                                    <li class="juntaplay-group-card__members-more"><?php echo esc_html(sprintf(_n('e mais %d participante', 'e mais %d participantes', $members_remaining, 'juntaplay'), $members_remaining)); ?></li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    <?php if ($status_message !== '') : ?>
                        <p class="juntaplay-group-card__message"><?php echo esc_html($status_message); ?></p>
                    <?php endif; ?>
                    <?php if ($group_rules !== '') : ?>
                        <div class="juntaplay-group-card__rules" role="note">
                            <strong><?php echo esc_html__('Regras do grupo', 'juntaplay'); ?>:</strong>
                            <p><?php echo esc_html($group_rules); ?></p>
                        </div>
                    <?php endif; ?>
                    <?php if ($review_note !== '' && $status !== 'pending') : ?>
                        <div class="juntaplay-group-card__review" role="note">
                            <strong><?php echo esc_html__('Observação do administrador', 'juntaplay'); ?>:</strong>
                            <p><?php echo esc_html($review_note); ?></p>
                        </div>
                    <?php endif; ?>
                    <?php if ($share_snippet !== '') : ?>
                        <div class="juntaplay-group-card__share" data-group-share>
                            <header>
                                <span class="juntaplay-group-card__share-label"><?php echo esc_html__('Compartilhar', 'juntaplay'); ?></span>
                                <?php if ($share_domain !== '') : ?>
                                    <span class="juntaplay-group-card__share-domain"><?php echo esc_html($share_domain); ?></span>
                                <?php endif; ?>
                            </header>
                            <pre class="juntaplay-group-card__share-snippet" data-group-share-snippet><?php echo esc_html($share_snippet); ?></pre>
                            <textarea class="juntaplay-group-card__share-text" data-group-share-text hidden><?php echo esc_textarea($share_snippet); ?></textarea>
                            <button type="button" class="juntaplay-button juntaplay-button--ghost" data-group-share-copy data-default-label="<?php echo esc_attr__('Copiar resumo', 'juntaplay'); ?>" data-success-label="<?php echo esc_attr__('Resumo copiado!', 'juntaplay'); ?>" data-error-label="<?php echo esc_attr__('Não foi possível copiar agora. Copie manualmente.', 'juntaplay'); ?>">
                                <?php echo esc_html__('Copiar resumo', 'juntaplay'); ?>
                            </button>
                        </div>
                    <?php endif; ?>
                    <div class="juntaplay-group-card__complaint" data-group-complaint>
                        <header class="juntaplay-group-card__complaint-header">
                            <h4><?php echo esc_html__('Precisa de ajuda com esta cota?', 'juntaplay'); ?></h4>
                            <?php if ($complaint_status_label !== '') : ?>
                                <span class="juntaplay-badge juntaplay-badge--<?php echo esc_attr($complaint_status_tone); ?>"><?php echo esc_html(sprintf(__('Última reclamação: %s', 'juntaplay'), $complaint_status_label)); ?></span>
                            <?php endif; ?>
                        </header>
                        <?php if ($complaint_summary_text !== '') : ?>
                            <p class="juntaplay-group-card__complaint-summary"><?php echo esc_html($complaint_summary_text); ?></p>
                        <?php endif; ?>
                        <?php if ($complaint_status_message !== '') : ?>
                            <p class="juntaplay-group-card__complaint-note"><?php echo esc_html($complaint_status_message); ?></p>
                        <?php endif; ?>
                        <?php if ($complaints_open > 0) : ?>
                            <p class="juntaplay-group-card__complaint-open"><?php echo esc_html(sprintf(_n('Você possui %d reclamação aberta.', 'Você possui %d reclamações abertas.', $complaints_open, 'juntaplay'), $complaints_open)); ?></p>
                        <?php elseif ($complaints_total > 0) : ?>
                            <p class="juntaplay-group-card__complaint-open"><?php echo esc_html(sprintf(_n('Você já abriu %d reclamação para este grupo.', 'Você já abriu %d reclamações para este grupo.', $complaints_total, 'juntaplay'), $complaints_total)); ?></p>
                        <?php endif; ?>
                        <?php if ($complaint_success_messages) : ?>
                            <div class="juntaplay-alert juntaplay-alert--success" role="status">
                                <?php foreach ($complaint_success_messages as $success_message) : ?>
                                    <p><?php echo esc_html((string) $success_message); ?></p>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <?php if ($complaint_messages) : ?>
                            <div class="juntaplay-alert juntaplay-alert--error" role="alert">
                                <ul>
                                    <?php foreach ($complaint_messages as $error_message) : ?>
                                        <li><?php echo esc_html((string) $error_message); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        <p class="juntaplay-group-card__complaint-description"><?php echo esc_html__('Se algo não saiu como combinado com este grupo, relate o problema para que possamos ajudar.', 'juntaplay'); ?></p>
                        <?php
                        $form_open = !empty($complaint_messages) || !empty($complaint_draft);
                        $toggle_label = $form_open ? __('Fechar formulário', 'juntaplay') : __('Abrir reclamação', 'juntaplay');
                        ?>
                        <button type="button" class="juntaplay-button juntaplay-button--ghost" data-group-complaint-toggle data-target="jp-group-complaint-<?php echo esc_attr($group_id); ?>" data-default-label="<?php echo esc_attr__('Abrir reclamação', 'juntaplay'); ?>" data-open-label="<?php echo esc_attr__('Fechar formulário', 'juntaplay'); ?>" aria-expanded="<?php echo $form_open ? 'true' : 'false'; ?>" aria-controls="jp-group-complaint-<?php echo esc_attr($group_id); ?>">
                            <?php echo esc_html($toggle_label); ?>
                        </button>
                        <form id="jp-group-complaint-<?php echo esc_attr($group_id); ?>" class="juntaplay-group-complaint__form<?php echo $form_open ? ' is-open' : ' is-hidden'; ?>" method="post" enctype="multipart/form-data" data-group-complaint-form>
                            <input type="hidden" name="jp_profile_action" value="1" />
                            <input type="hidden" name="jp_profile_section" value="group_complaint" />
                            <input type="hidden" name="jp_profile_complaint_group" value="<?php echo esc_attr((string) $group_id); ?>" />
                            <?php wp_nonce_field('juntaplay_profile_update', 'jp_profile_nonce'); ?>
                            <div class="juntaplay-form__group">
                                <label for="jp-group-complaint-reason-<?php echo esc_attr($group_id); ?>"><?php echo esc_html__('Motivo da reclamação', 'juntaplay'); ?></label>
                                <select id="jp-group-complaint-reason-<?php echo esc_attr($group_id); ?>" name="jp_profile_complaint_reason" class="juntaplay-form__input">
                                    <?php foreach ($complaint_reasons as $reason_value => $reason_label) : ?>
                                        <option value="<?php echo esc_attr((string) $reason_value); ?>" <?php selected(isset($complaint_draft['reason']) ? $complaint_draft['reason'] : 'other', (string) $reason_value); ?>><?php echo esc_html((string) $reason_label); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="juntaplay-form__group">
                                <label for="jp-group-complaint-message-<?php echo esc_attr($group_id); ?>"><?php echo esc_html__('Descreva o que aconteceu', 'juntaplay'); ?></label>
                                <textarea id="jp-group-complaint-message-<?php echo esc_attr($group_id); ?>" name="jp_profile_complaint_message" class="juntaplay-form__input" rows="4" placeholder="<?php echo esc_attr__('Conte os detalhes, prazos e se já falou com o administrador.', 'juntaplay'); ?>"><?php echo esc_textarea(isset($complaint_draft['message']) ? $complaint_draft['message'] : ''); ?></textarea>
                            </div>
                            <div class="juntaplay-form__group">
                                <label for="jp-group-complaint-order-<?php echo esc_attr($group_id); ?>"><?php echo esc_html__('Número do pedido (opcional)', 'juntaplay'); ?></label>
                                <input type="text" id="jp-group-complaint-order-<?php echo esc_attr($group_id); ?>" name="jp_profile_complaint_order" class="juntaplay-form__input" inputmode="numeric" value="<?php echo esc_attr(isset($complaint_draft['order']) ? $complaint_draft['order'] : ''); ?>" placeholder="<?php echo esc_attr__('Ex.: 12345', 'juntaplay'); ?>" />
                            </div>
                            <div class="juntaplay-form__group">
                                <label for="jp-group-complaint-files-<?php echo esc_attr($group_id); ?>"><?php echo esc_html__('Anexar prints ou comprovantes', 'juntaplay'); ?></label>
                                <input type="file" id="jp-group-complaint-files-<?php echo esc_attr($group_id); ?>" name="jp_profile_complaint_attachments[]" class="juntaplay-form__input" accept="image/*,.pdf" multiple data-group-complaint-files />
                                <p class="juntaplay-form__help"><?php echo esc_html(sprintf(_n('Até %1$d arquivo de até %2$s MB.', 'Até %1$d arquivos de até %2$s MB cada.', $complaint_max_files, 'juntaplay'), $complaint_max_files, number_format_i18n($complaint_max_size_mb, 1))); ?></p>
                                <ul class="juntaplay-group-complaint__files" data-group-complaint-preview></ul>
                            </div>
                            <div class="juntaplay-group-complaint__actions">
                                <button type="submit" class="juntaplay-button juntaplay-button--primary"><?php echo esc_html__('Enviar reclamação', 'juntaplay'); ?></button>
                                <button type="button" class="juntaplay-button juntaplay-button--ghost" data-group-complaint-close><?php echo esc_html__('Cancelar', 'juntaplay'); ?></button>
                            </div>
                        </form>
                    </div>
                    <?php if ($faq_items) : ?>
                        <div class="juntaplay-group-card__faq">
                            <h4><?php echo esc_html__('Dúvidas frequentes', 'juntaplay'); ?></h4>
                            <div class="juntaplay-accordion">
                                <?php foreach ($faq_items as $faq_item) :
                                    if (!is_array($faq_item)) {
                                        continue;
                                    }
                                    $faq_question = isset($faq_item['question']) ? (string) $faq_item['question'] : '';
                                    $faq_answer   = isset($faq_item['answer']) ? (string) $faq_item['answer'] : '';
                                    if ($faq_question === '' || $faq_answer === '') {
                                        continue;
                                    }
                                ?>
                                    <details class="juntaplay-accordion__item">
                                        <summary class="juntaplay-accordion__summary"><?php echo esc_html($faq_question); ?></summary>
                                        <p class="juntaplay-accordion__content"><?php echo esc_html($faq_answer); ?></p>
                                    </details>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    </div>
                    <footer class="juntaplay-group-card__footer">
                        <?php if ($pool_link !== '') : ?>
                            <a class="juntaplay-link" href="<?php echo esc_url($pool_link); ?>" target="_blank" rel="noopener">
                                <?php echo esc_html__('Ver campanha', 'juntaplay'); ?>
                            </a>
                        <?php else : ?>
                            <span class="juntaplay-group-card__note"><?php echo esc_html__('Campanha será definida pelo administrador.', 'juntaplay'); ?></span>
                        <?php endif; ?>
                        <?php if ($campaigns_url) : ?>
                            <div class="juntaplay-group-card__extras">
                                <span><?php echo esc_html__('Outras opções', 'juntaplay'); ?></span>
                                <a class="juntaplay-link" href="<?php echo esc_url($campaigns_url); ?>"><?php echo esc_html__('Explorar campanhas disponíveis', 'juntaplay'); ?></a>
                            </div>
                        <?php endif; ?>
                    </footer>
                    </div>
                </article>
            <?php endforeach; ?>
            <p class="juntaplay-groups__empty is-hidden" data-group-empty><?php echo esc_html__('Nenhum grupo corresponde aos filtros selecionados.', 'juntaplay'); ?></p>
        <?php else : ?>
            <p class="juntaplay-profile__empty"><?php echo esc_html__('Você ainda não participa de nenhum grupo. Crie um novo grupo ou participe de uma campanha para aparecer aqui.', 'juntaplay'); ?></p>
        <?php endif; ?>
    </div>

    <div class="juntaplay-groups__create">
        <h3><?php echo esc_html__('Criar novo grupo', 'juntaplay'); ?></h3>
        <p><?php echo esc_html__('Os grupos são públicos e passam por análise do super administrador. Assim que o pedido for aprovado, todos os participantes recebem um e-mail de confirmação.', 'juntaplay'); ?></p>

        <?php if ($group_suggestions) : ?>
            <div class="juntaplay-groups__ideas" aria-live="polite">
                <h4><?php echo esc_html__('Inspirações para começar', 'juntaplay'); ?></h4>
                <p class="juntaplay-groups__ideas-description"><?php echo esc_html__('Veja alguns exemplos de campanhas populares e utilize-os como ponto de partida para montar o seu grupo.', 'juntaplay'); ?></p>
                <div class="juntaplay-groups__ideas-list">
                    <?php foreach ($group_suggestions as $suggestion) :
                        if (!is_array($suggestion)) {
                            continue;
                        }

                        $idea_title       = isset($suggestion['title']) ? (string) $suggestion['title'] : '';
                        $idea_price       = isset($suggestion['price']) ? (string) $suggestion['price'] : '';
                        $idea_amount      = isset($suggestion['amount']) ? (string) $suggestion['amount'] : '';
                        $idea_category    = isset($suggestion['category']) ? (string) $suggestion['category'] : 'other';
                        $idea_description = isset($suggestion['description']) ? (string) $suggestion['description'] : '';
                        $idea_category_label = isset($group_categories[$idea_category]) ? (string) $group_categories[$idea_category] : ucwords(str_replace(['-', '_'], ' ', $idea_category));
                    ?>
                        <article class="juntaplay-groups__idea" data-group-suggestion
                            data-title="<?php echo esc_attr($idea_title); ?>"
                            data-amount="<?php echo esc_attr($idea_amount); ?>"
                            data-category="<?php echo esc_attr($idea_category); ?>"
                            data-description="<?php echo esc_attr($idea_description); ?>">
                            <header class="juntaplay-groups__idea-header">
                                <span class="juntaplay-groups__idea-category"><?php echo esc_html($idea_category_label); ?></span>
                                <h5><?php echo esc_html($idea_title); ?></h5>
                                <?php if ($idea_price !== '') : ?>
                                    <span class="juntaplay-groups__idea-price"><?php echo esc_html($idea_price); ?></span>
                                <?php endif; ?>
                            </header>
                            <?php if ($idea_description !== '') : ?>
                                <p class="juntaplay-groups__idea-description"><?php echo esc_html($idea_description); ?></p>
                            <?php endif; ?>
                            <button type="button" class="juntaplay-button juntaplay-button--ghost" data-group-suggestion-apply>
                                <?php echo esc_html__('Usar esta sugestão', 'juntaplay'); ?>
                            </button>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($form_errors) : ?>
            <ul class="juntaplay-form__errors" role="alert">
                <?php foreach ($form_errors as $error_message) : ?>
                    <li><?php echo esc_html($error_message); ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <form method="post" class="juntaplay-form juntaplay-groups__form">
            <input type="hidden" name="jp_profile_action" value="1" />
            <input type="hidden" name="jp_profile_section" value="group_create" />
            <?php wp_nonce_field('juntaplay_profile_update', 'jp_profile_nonce'); ?>

            <div class="juntaplay-form__grid">
                <div class="juntaplay-form__group">
                    <label for="jp-group-name"><?php echo esc_html__('Nome do grupo', 'juntaplay'); ?></label>
                    <input
                        type="text"
                        id="jp-group-name"
                        name="jp_profile_group_name"
                        class="juntaplay-form__input"
                        value="<?php echo esc_attr($current_name); ?>"
                        placeholder="<?php echo esc_attr__('Ex.: Amigos do Sorteio de Sábado', 'juntaplay'); ?>"
                        required
                        data-group-share-watch
                    />
                </div>
                <div class="juntaplay-form__group">
                    <label for="jp-group-service"><?php echo esc_html__('Serviço ou assinatura', 'juntaplay'); ?></label>
                    <input
                        type="text"
                        id="jp-group-service"
                        name="jp_profile_group_service"
                        class="juntaplay-form__input"
                        value="<?php echo esc_attr($current_service); ?>"
                        placeholder="<?php echo esc_attr__('Ex.: ChatGPT Plus, Netflix, Spotify…', 'juntaplay'); ?>"
                        required
                        data-group-share-watch
                    />
                </div>
            </div>

            <div class="juntaplay-form__group">
                <label for="jp-group-service-url"><?php echo esc_html__('Site oficial do serviço', 'juntaplay'); ?></label>
                <input
                    type="url"
                    id="jp-group-service-url"
                    name="jp_profile_group_service_url"
                    class="juntaplay-form__input"
                    value="<?php echo esc_attr($current_service_url); ?>"
                    placeholder="<?php echo esc_attr__('https://exemplo.com', 'juntaplay'); ?>"
                    inputmode="url"
                    data-group-share-watch
                />
            </div>

            <div class="juntaplay-form__group juntaplay-form__group--cover" data-group-cover data-placeholder="<?php echo esc_url($cover_placeholder); ?>">
                <label for="jp-group-cover"><?php echo esc_html__('Capa do grupo (495x370 px)', 'juntaplay'); ?></label>
                <div class="juntaplay-cover-picker" data-group-cover-wrapper>
                    <div class="juntaplay-cover-picker__media" data-group-cover-preview style="background-image: url('<?php echo esc_url($current_cover_preview); ?>');">
                        <img src="<?php echo esc_url($current_cover_preview); ?>" alt="<?php echo esc_attr__('Pré-visualização da capa do grupo', 'juntaplay'); ?>" loading="lazy" />
                    </div>
                    <input type="hidden" id="jp-group-cover" name="jp_profile_group_cover" value="<?php echo esc_attr($current_cover_id); ?>" data-group-cover-input />
                    <div class="juntaplay-cover-picker__actions">
                        <button type="button" class="juntaplay-button juntaplay-button--ghost" data-group-cover-select><?php echo esc_html__('Escolher imagem', 'juntaplay'); ?></button>
                        <button type="button" class="juntaplay-button juntaplay-button--subtle" data-group-cover-remove <?php disabled($current_cover_id === 0); ?>><?php echo esc_html__('Remover', 'juntaplay'); ?></button>
                    </div>
                    <p class="juntaplay-form__help"><?php echo esc_html__('Essa capa será usada nos cards públicos do seu grupo. Utilize dimensões proporcionais a 495x370 px.', 'juntaplay'); ?></p>
                </div>
            </div>

            <div class="juntaplay-form__grid">
                <div class="juntaplay-form__group">
                    <label for="jp-group-category"><?php echo esc_html__('Categoria do serviço', 'juntaplay'); ?></label>
                    <select id="jp-group-category" name="jp_profile_group_category" class="juntaplay-form__input" data-group-share-watch>
                        <?php foreach ($group_categories as $category_value => $category_name) : ?>
                            <option value="<?php echo esc_attr((string) $category_value); ?>" <?php selected($current_category, (string) $category_value); ?>><?php echo esc_html((string) $category_name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="juntaplay-form__group juntaplay-form__group--toggle">
                    <span class="juntaplay-form__label"><?php echo esc_html__('Acesso instantâneo após aprovação', 'juntaplay'); ?></span>
                    <label class="juntaplay-toggle" for="jp-group-instant">
                        <input
                            type="checkbox"
                            id="jp-group-instant"
                            name="jp_profile_group_instant"
                            value="on"
                            <?php checked($current_instant); ?>
                            data-group-share-watch
                        />
                        <span class="juntaplay-toggle__slider" aria-hidden="true"></span>
                        <span class="juntaplay-toggle__caption"
                            data-toggle-caption-active="<?php echo esc_attr__('Ativado', 'juntaplay'); ?>"
                            data-toggle-caption-inactive="<?php echo esc_attr__('Desativado', 'juntaplay'); ?>">
                            <?php echo $current_instant ? esc_html__('Ativado', 'juntaplay') : esc_html__('Desativado', 'juntaplay'); ?>
                        </span>
                    </label>
                    <p class="juntaplay-form__help"><?php echo esc_html__('Quando ativado, o grupo libera o acesso automaticamente assim que for aprovado pelo super administrador.', 'juntaplay'); ?></p>
                </div>
            </div>

            <div class="juntaplay-form__group">
                <label for="jp-group-rules"><?php echo esc_html__('Regras principais para os participantes', 'juntaplay'); ?></label>
                <textarea
                    id="jp-group-rules"
                    name="jp_profile_group_rules"
                    class="juntaplay-form__input"
                    rows="3"
                    placeholder="<?php echo esc_attr__('Ex.: Não compartilhar senhas, manter dados atualizados, respeitar prazos.', 'juntaplay'); ?>"
                    data-group-share-watch
                ><?php echo esc_textarea($current_rules); ?></textarea>
            </div>

            <div class="juntaplay-form__group">
                <label for="jp-group-description"><?php echo esc_html__('Mensagem para os participantes', 'juntaplay'); ?></label>
                <textarea
                    id="jp-group-description"
                    name="jp_profile_group_description"
                    class="juntaplay-form__input"
                    rows="4"
                    placeholder="<?php echo esc_attr__('Descreva o propósito do grupo, metas e como os participantes podem colaborar.', 'juntaplay'); ?>"
                    data-group-share-watch
                ><?php echo esc_textarea($current_description); ?></textarea>
                <p class="juntaplay-form__help"><?php echo esc_html__('Sem grupos privados: todos podem visualizar e solicitar entrada.', 'juntaplay'); ?></p>
            </div>

            <div class="juntaplay-form__group">
                <label for="jp-group-pool"><?php echo esc_html__('Campanha vinculada (opcional)', 'juntaplay'); ?></label>
                <select id="jp-group-pool" name="jp_profile_group_pool" class="juntaplay-form__input">
                    <option value=""><?php echo esc_html__('Escolha uma campanha', 'juntaplay'); ?></option>
                    <?php foreach ($pool_choices as $pool_id => $pool_name) : ?>
                        <option value="<?php echo esc_attr((string) $pool_id); ?>" <?php selected((string) $pool_id, $current_pool); ?>><?php echo esc_html((string) $pool_name); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="juntaplay-form__grid">
                <div class="juntaplay-form__group">
                    <label for="jp-group-price"><?php echo esc_html__('Valor mensal do serviço', 'juntaplay'); ?></label>
                    <input
                        type="text"
                        id="jp-group-price"
                        name="jp_profile_group_price"
                        class="juntaplay-form__input"
                        inputmode="decimal"
                        value="<?php echo esc_attr($current_price); ?>"
                        placeholder="<?php echo esc_attr__('Ex.: 120,00', 'juntaplay'); ?>"
                        data-group-price-input
                        data-group-share-watch
                        required
                    />
                </div>
                <div class="juntaplay-form__group juntaplay-form__group--inline">
                    <label class="juntaplay-form__checkbox">
                        <input type="checkbox" name="jp_profile_group_promo_toggle" value="on" data-group-promo-toggle <?php checked($promo_enabled); ?> />
                        <span><?php echo esc_html__('Ofereço valor promocional aos membros', 'juntaplay'); ?></span>
                    </label>
                    <div class="juntaplay-form__group<?php echo $promo_enabled ? '' : ' is-hidden'; ?>" data-group-promo-field>
                        <label for="jp-group-price-promo" class="screen-reader-text"><?php echo esc_html__('Valor promocional', 'juntaplay'); ?></label>
                        <input
                            type="text"
                            id="jp-group-price-promo"
                            name="jp_profile_group_price_promo"
                            class="juntaplay-form__input"
                            inputmode="decimal"
                            value="<?php echo esc_attr($current_promo); ?>"
                            placeholder="<?php echo esc_attr__('Ex.: 110,00', 'juntaplay'); ?>"
                            data-group-price-input
                            data-group-share-watch
                        />
                    </div>
                </div>
            </div>

            <div class="juntaplay-form__grid">
                <div class="juntaplay-form__group">
                    <label for="jp-group-slots-total"><?php echo esc_html__('Total de vagas', 'juntaplay'); ?></label>
                    <input
                        type="number"
                        id="jp-group-slots-total"
                        name="jp_profile_group_slots_total"
                        class="juntaplay-form__input"
                        min="1"
                        value="<?php echo esc_attr($current_total); ?>"
                        placeholder="<?php echo esc_attr__('Ex.: 5', 'juntaplay'); ?>"
                        data-group-slot-input
                        data-group-share-watch
                        required
                    />
                </div>
                <div class="juntaplay-form__group">
                    <label for="jp-group-slots-reserved"><?php echo esc_html__('Vagas reservadas para você', 'juntaplay'); ?></label>
                    <input
                        type="number"
                        id="jp-group-slots-reserved"
                        name="jp_profile_group_slots_reserved"
                        class="juntaplay-form__input"
                        min="0"
                        value="<?php echo esc_attr($current_reserved); ?>"
                        placeholder="<?php echo esc_attr__('Ex.: 1', 'juntaplay'); ?>"
                        data-group-slot-input
                        data-group-share-watch
                    />
                    <p class="juntaplay-form__help"><?php echo esc_html__('Lembre-se: o grupo permanece público e auditado pelo super administrador.', 'juntaplay'); ?></p>
                </div>
            </div>

            <div class="juntaplay-form__group">
                <label for="jp-group-member-price"><?php echo esc_html__('Valor cobrado de cada membro', 'juntaplay'); ?></label>
                <input
                    type="text"
                    id="jp-group-member-price"
                    name="jp_profile_group_member_price"
                    class="juntaplay-form__input"
                    inputmode="decimal"
                    value="<?php echo esc_attr($current_member); ?>"
                    placeholder="<?php echo esc_attr__('Será sugerido automaticamente', 'juntaplay'); ?>"
                    data-group-price-input
                    data-group-member-input
                    data-group-member-generated="<?php echo $member_was_generated ? 'yes' : 'no'; ?>"
                    data-group-share-watch
                />
                <p class="juntaplay-form__hint juntaplay-groups__price-preview <?php echo $member_preview_text === '' ? 'is-hidden' : ''; ?>" data-group-price-preview data-empty="<?php echo esc_attr__('Informe valor do serviço e vagas para sugerir o valor por membro.', 'juntaplay'); ?>" data-suffix="<?php echo esc_attr__('por membro disponível', 'juntaplay'); ?>">
                    <?php echo esc_html($member_preview_text); ?>
                </p>
            </div>

            <div class="juntaplay-form__grid">
                <div class="juntaplay-form__group">
                    <label for="jp-group-support"><?php echo esc_html__('Suporte aos membros', 'juntaplay'); ?></label>
                    <input
                        type="text"
                        id="jp-group-support"
                        name="jp_profile_group_support"
                        class="juntaplay-form__input"
                        value="<?php echo esc_attr($current_support); ?>"
                        placeholder="<?php echo esc_attr__('Ex.: E-mail, WhatsApp comercial, Telegram…', 'juntaplay'); ?>"
                        data-group-share-watch
                        required
                    />
                </div>
                <div class="juntaplay-form__group">
                    <label for="jp-group-delivery"><?php echo esc_html__('Prazo para envio de acesso', 'juntaplay'); ?></label>
                    <input
                        type="text"
                        id="jp-group-delivery"
                        name="jp_profile_group_delivery"
                        class="juntaplay-form__input"
                        value="<?php echo esc_attr($current_delivery); ?>"
                        placeholder="<?php echo esc_attr__('Ex.: Imediatamente após pagamento', 'juntaplay'); ?>"
                        data-group-share-watch
                        required
                    />
                </div>
            </div>

            <div class="juntaplay-form__grid">
                <div class="juntaplay-form__group">
                    <label for="jp-group-access"><?php echo esc_html__('Forma de acesso enviada', 'juntaplay'); ?></label>
                    <input
                        type="text"
                        id="jp-group-access"
                        name="jp_profile_group_access"
                        class="juntaplay-form__input"
                        value="<?php echo esc_attr($current_access); ?>"
                        placeholder="<?php echo esc_attr__('Ex.: Código de ativação, login compartilhado, convite por e-mail…', 'juntaplay'); ?>"
                        data-group-share-watch
                        required
                    />
                </div>
            </div>

            <div class="juntaplay-groups__share" data-group-share data-domain="<?php echo esc_attr($site_host); ?>" data-empty="<?php echo esc_attr__('Preencha os campos ao lado para gerar um texto completo de convite.', 'juntaplay'); ?>">
                <h4><?php echo esc_html__('Prévia para convidar participantes', 'juntaplay'); ?></h4>
                <p class="juntaplay-groups__share-intro"><?php echo esc_html__('Revise e compartilhe este resumo com interessados antes de enviar para análise.', 'juntaplay'); ?></p>
                <div class="juntaplay-groups__share-card">
                    <span class="juntaplay-groups__share-domain"><?php echo esc_html($site_host); ?></span>
                    <dl class="juntaplay-groups__share-list">
                        <div class="juntaplay-groups__share-row">
                            <dt><?php echo esc_html__('Serviço', 'juntaplay'); ?></dt>
                            <dd data-group-share-field="service" data-empty="<?php echo esc_attr__('Informe o serviço', 'juntaplay'); ?>"><?php echo $current_service !== '' ? esc_html($current_service) : esc_html__('Informe o serviço', 'juntaplay'); ?></dd>
                        </div>
                        <div class="juntaplay-groups__share-row">
                            <dt><?php echo esc_html__('Nome do grupo', 'juntaplay'); ?></dt>
                            <dd data-group-share-field="name" data-empty="<?php echo esc_attr__('Defina o nome do grupo', 'juntaplay'); ?>"><?php echo $current_name !== '' ? esc_html($current_name) : esc_html__('Defina o nome do grupo', 'juntaplay'); ?></dd>
                        </div>
                        <div class="juntaplay-groups__share-row">
                            <dt><?php echo esc_html__('Tipo', 'juntaplay'); ?></dt>
                            <dd><?php echo $group_type_label; ?></dd>
                        </div>
                        <div class="juntaplay-groups__share-row">
                            <dt><?php echo esc_html__('Categoria', 'juntaplay'); ?></dt>
                            <dd data-group-share-field="category" data-empty="<?php echo esc_attr__('Escolha uma categoria', 'juntaplay'); ?>"><?php echo $category_display !== '' ? $category_display : esc_html__('Escolha uma categoria', 'juntaplay'); ?></dd>
                        </div>
                        <div class="juntaplay-groups__share-row">
                            <dt><?php echo esc_html__('Site', 'juntaplay'); ?></dt>
                            <dd data-group-share-field="service_url" data-empty="<?php echo esc_attr__('Inclua o link oficial', 'juntaplay'); ?>"><?php echo $current_service_url !== '' ? esc_html($current_service_url) : esc_html__('Inclua o link oficial', 'juntaplay'); ?></dd>
                        </div>
                        <div class="juntaplay-groups__share-row">
                            <dt><?php echo esc_html__('Regras', 'juntaplay'); ?></dt>
                            <dd data-group-share-field="rules" data-empty="<?php echo esc_attr__('Compartilhe as regras principais', 'juntaplay'); ?>"><?php echo $current_rules !== '' ? esc_html($current_rules) : esc_html__('Compartilhe as regras principais', 'juntaplay'); ?></dd>
                        </div>
                        <div class="juntaplay-groups__share-row">
                            <dt><?php echo esc_html__('Descrição', 'juntaplay'); ?></dt>
                            <dd data-group-share-field="description" data-empty="<?php echo esc_attr__('Descreva o objetivo do grupo', 'juntaplay'); ?>"><?php echo $current_description !== '' ? esc_html($current_description) : esc_html__('Descreva o objetivo do grupo', 'juntaplay'); ?></dd>
                        </div>
                        <div class="juntaplay-groups__share-row">
                            <dt><?php echo esc_html__('Valor do serviço', 'juntaplay'); ?></dt>
                            <dd data-group-share-field="price" data-empty="<?php echo esc_attr__('Informe o valor', 'juntaplay'); ?>"><?php echo $price_display !== '' ? esc_html($price_display) : esc_html__('Informe o valor', 'juntaplay'); ?></dd>
                        </div>
                        <div class="juntaplay-groups__share-row">
                            <dt><?php echo esc_html__('É valor promocional?', 'juntaplay'); ?></dt>
                            <dd data-group-share-field="promo_flag" data-fallback="<?php echo esc_attr__('Não', 'juntaplay'); ?>"><?php echo $promo_flag; ?></dd>
                        </div>
                        <div class="juntaplay-groups__share-row">
                            <dt><?php echo esc_html__('Valor promocional', 'juntaplay'); ?></dt>
                            <dd data-group-share-field="promo" data-fallback="<?php echo esc_attr__('Não', 'juntaplay'); ?>"><?php echo esc_html($promo_display); ?></dd>
                        </div>
                        <div class="juntaplay-groups__share-row">
                            <dt><?php echo esc_html__('Vagas totais', 'juntaplay'); ?></dt>
                            <dd data-group-share-field="slots_total" data-empty="<?php echo esc_attr__('Defina o total de vagas', 'juntaplay'); ?>"><?php echo $current_total !== '' ? esc_html($current_total) : esc_html__('Defina o total de vagas', 'juntaplay'); ?></dd>
                        </div>
                        <div class="juntaplay-groups__share-row">
                            <dt><?php echo esc_html__('Reservadas para você', 'juntaplay'); ?></dt>
                            <dd data-group-share-field="slots_reserved" data-empty="0"><?php echo $current_reserved !== '' ? esc_html($current_reserved) : '0'; ?></dd>
                        </div>
                        <div class="juntaplay-groups__share-row">
                            <dt><?php echo esc_html__('Valor por membro', 'juntaplay'); ?></dt>
                            <dd data-group-share-field="member_price" data-empty="<?php echo esc_attr__('Será calculado automaticamente', 'juntaplay'); ?>"><?php echo $member_display !== '' ? esc_html($member_display) : esc_html__('Será calculado automaticamente', 'juntaplay'); ?></dd>
                        </div>
                        <div class="juntaplay-groups__share-row">
                            <dt><?php echo esc_html__('Suporte', 'juntaplay'); ?></dt>
                            <dd data-group-share-field="support" data-empty="<?php echo esc_attr__('Informe o canal de suporte', 'juntaplay'); ?>"><?php echo $current_support !== '' ? esc_html($current_support) : esc_html__('Informe o canal de suporte', 'juntaplay'); ?></dd>
                        </div>
                        <div class="juntaplay-groups__share-row">
                            <dt><?php echo esc_html__('Envio de acesso', 'juntaplay'); ?></dt>
                            <dd data-group-share-field="delivery" data-empty="<?php echo esc_attr__('Defina o prazo de entrega', 'juntaplay'); ?>"><?php echo $current_delivery !== '' ? esc_html($current_delivery) : esc_html__('Defina o prazo de entrega', 'juntaplay'); ?></dd>
                        </div>
                        <div class="juntaplay-groups__share-row">
                            <dt><?php echo esc_html__('Forma de acesso', 'juntaplay'); ?></dt>
                            <dd data-group-share-field="access" data-empty="<?php echo esc_attr__('Explique como o acesso será enviado', 'juntaplay'); ?>"><?php echo $current_access !== '' ? esc_html($current_access) : esc_html__('Explique como o acesso será enviado', 'juntaplay'); ?></dd>
                        </div>
                        <div class="juntaplay-groups__share-row">
                            <dt><?php echo esc_html__('Acesso instantâneo', 'juntaplay'); ?></dt>
                            <dd data-group-share-field="instant_access" data-fallback="<?php echo esc_attr__('Desativado', 'juntaplay'); ?>"><?php echo $instant_display; ?></dd>
                        </div>
                    </dl>
                </div>
                <pre class="juntaplay-groups__share-snippet" data-group-share-snippet><?php echo esc_html($share_text); ?></pre>
                <textarea class="juntaplay-groups__share-text" data-group-share-text readonly hidden><?php echo esc_textarea($share_text); ?></textarea>
                <div class="juntaplay-groups__share-actions">
                    <button type="button" class="juntaplay-button juntaplay-button--ghost" data-group-share-copy data-default-label="<?php echo esc_attr__('Copiar resumo', 'juntaplay'); ?>" data-success-label="<?php echo esc_attr__('Resumo copiado!', 'juntaplay'); ?>" data-error-label="<?php echo esc_attr__('Não foi possível copiar agora. Copie manualmente.', 'juntaplay'); ?>"><?php echo esc_html__('Copiar resumo', 'juntaplay'); ?></button>
                </div>
            </div>

            <div class="juntaplay-form__actions">
                <button type="submit" class="juntaplay-button juntaplay-button--primary"><?php echo esc_html__('Enviar para análise', 'juntaplay'); ?></button>
            </div>
        </form>
    </div>
</div>
