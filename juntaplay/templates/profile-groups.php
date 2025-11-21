<?php
/**
 * JuntaPlay profile groups hub template.
 */

declare(strict_types=1);

use JuntaPlay\Assets\ServiceIcons;

if (!defined('ABSPATH')) {
    exit;
}

$group_context = isset($group_context) && is_array($group_context) ? $group_context : [];

$groups_owned   = isset($group_context['groups_owned']) && is_array($group_context['groups_owned']) ? $group_context['groups_owned'] : [];
$groups_member  = isset($group_context['groups_member']) && is_array($group_context['groups_member']) ? $group_context['groups_member'] : [];
$pagination     = isset($group_context['pagination']) && is_array($group_context['pagination']) ? $group_context['pagination'] : [];
$group_counts   = isset($group_context['group_counts']) && is_array($group_context['group_counts']) ? $group_context['group_counts'] : [];
$pool_choices   = isset($group_context['pool_choices']) && is_array($group_context['pool_choices']) ? $group_context['pool_choices'] : [];
$pool_featured  = isset($group_context['pool_featured']) && is_array($group_context['pool_featured']) ? $group_context['pool_featured'] : [];
$pool_catalog   = isset($group_context['pool_catalog']) && is_array($group_context['pool_catalog']) ? $group_context['pool_catalog'] : [];
$group_categories = isset($group_context['group_categories']) && is_array($group_context['group_categories']) ? $group_context['group_categories'] : [];
$group_suggestions = isset($group_context['group_suggestions']) && is_array($group_context['group_suggestions']) ? $group_context['group_suggestions'] : [];
$form_errors    = isset($group_context['form_errors']) && is_array($group_context['form_errors']) ? $group_context['form_errors'] : [];
$form_values    = isset($group_context['form_values']) && is_array($group_context['form_values']) ? $group_context['form_values'] : [];
$create_success = isset($group_context['create_success']) && is_array($group_context['create_success']) ? $group_context['create_success'] : [];
$complaint_errors  = isset($group_context['complaint_errors']) && is_array($group_context['complaint_errors']) ? $group_context['complaint_errors'] : [];
$complaint_drafts  = isset($group_context['complaint_drafts']) && is_array($group_context['complaint_drafts']) ? $group_context['complaint_drafts'] : [];
$complaint_success = isset($group_context['complaint_success']) && is_array($group_context['complaint_success']) ? $group_context['complaint_success'] : [];
$complaint_reasons = isset($group_context['complaint_reasons']) && is_array($group_context['complaint_reasons']) ? $group_context['complaint_reasons'] : [];
$complaint_limits  = isset($group_context['complaint_limits']) && is_array($group_context['complaint_limits']) ? $group_context['complaint_limits'] : [];
$complaint_summary = isset($group_context['complaint_summary']) && is_array($group_context['complaint_summary']) ? $group_context['complaint_summary'] : [];
$cancel_errors     = isset($group_context['cancel_errors']) && is_array($group_context['cancel_errors']) ? $group_context['cancel_errors'] : [];
$cancel_general_errors = isset($cancel_errors['general']) && is_array($cancel_errors['general']) ? $cancel_errors['general'] : [];
$cancel_group_errors   = isset($cancel_errors['groups']) && is_array($cancel_errors['groups']) ? $cancel_errors['groups'] : [];

$complaint_max_files = isset($complaint_limits['max_files']) ? (int) $complaint_limits['max_files'] : 3;
$complaint_max_size  = isset($complaint_limits['max_size']) ? (int) $complaint_limits['max_size'] : 5 * 1024 * 1024;
$complaint_max_size_mb = max(1, round($complaint_max_size / 1048576, 1));

$success_heading_raw = isset($create_success['heading']) ? (string) $create_success['heading'] : '';
$success_body_raw = isset($create_success['body']) ? (string) $create_success['body'] : '';
$success_message_raw = isset($create_success['message']) ? (string) $create_success['message'] : '';

$should_open_success = $success_message_raw !== '' || $success_heading_raw !== '' || $success_body_raw !== '';

$success_heading = $success_heading_raw !== ''
    ? $success_heading_raw
    : __('Grupo cadastrado com sucesso!', 'juntaplay');
$success_body = $success_body_raw !== ''
    ? $success_body_raw
    : __('Aguarde que nossa equipe vai validar e você será notificado.', 'juntaplay');
$success_message = $success_message_raw !== ''
    ? $success_message_raw
    : trim($success_heading_raw . ' ' . $success_body_raw);

$success_image = '';
if (defined('JP_URL') && JP_URL !== '') {
    $success_image = trailingslashit(JP_URL) . 'assets/images/grupo.gif';
}
if ($success_image === '') {
    $success_image = plugins_url('../assets/images/grupo.gif', __FILE__);
}

$success_redirect_default = 'https://www.agenciadigitalsaopaulo.com.br/juntaplay/grupos';
$success_redirect = isset($create_success['redirect']) ? (string) $create_success['redirect'] : $success_redirect_default;
if ($success_redirect === '') {
    $success_redirect = $success_redirect_default;
}

$current_user_id = get_current_user_id();

$pool_choices_trim = array_slice($pool_choices, 0, 6, true);
if (!$pool_featured && $pool_catalog) {
    $pool_featured = array_slice($pool_catalog, 0, 6);
}

do_action('juntaplay/profile/enable_group_cover_upload');

if (function_exists('wp_enqueue_media')) {
    wp_enqueue_media();
}

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
$current_access_url  = isset($form_values['access_url']) ? (string) $form_values['access_url'] : '';
$current_access_login = isset($form_values['access_login']) ? (string) $form_values['access_login'] : '';
$current_access_password = isset($form_values['access_password']) ? (string) $form_values['access_password'] : '';
$current_relationship = isset($form_values['relationship']) ? (string) $form_values['relationship'] : '';
$relationship_options = \JuntaPlay\Data\Groups::get_relationship_options();
$cover_placeholder   = defined('JP_GROUP_COVER_PLACEHOLDER') ? JP_GROUP_COVER_PLACEHOLDER : '';
if ($cover_placeholder === '' && defined('JP_URL') && JP_URL !== '') {
    $cover_placeholder = trailingslashit(JP_URL) . 'assets/img/group-cover-placeholder.svg';
}
if ($cover_placeholder === '') {
    $cover_placeholder = plugins_url('../assets/img/group-cover-placeholder.svg', __FILE__);
}
if ($cover_placeholder === '') {
    $cover_placeholder = 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCA2IDUiIHByZXNlcnZlQXNwZWN0UmF0aW89InhNaWRZTWlkIHNsaWNlIj48ZGVmcz48bGluZWFyR3JhZGllbnQgaWQ9ImpwQ292ZXIiIHgxPSIwIiB4Mj0iMSIgeTE9IjAiIHkyPSIxIj48c3RvcCBvZmZzZXQ9IjAlIiBzdG9wLWNvbG9yPSIjRERFM0VBIiAvPjxzdG9wIG9mZnNldD0iMTAwJSIgc3RvcC1jb2xvcj0iI0YxRjNGNiIgLz48L2xpbmVhckdyYWRpZW50PjwvZGVmcz48cmVjdCB3aWR0aD0iNiIgaGVpZ2h0PSI1IiBmaWxsPSJ1cmwoI2pwQ292ZXIpIiAvPjxnIGZpbGw9IiM5MEE0QjgiIG9wYWNpdHk9IjAuOCI+PHBhdGggZD0iTTEgMy4yIDIuNCAxLjZsLjguOS44LS43TDUgMy40di44SDF6IiAvPjxjaXJjbGUgY3g9IjEuNCIgY3k9IjEuMyIgcj0iMC41IiAvPjwvZz48L3N2Zz4=';
}
$cover_preview_image = defined('JP_GROUP_COVER_PREVIEW') ? JP_GROUP_COVER_PREVIEW : '';
if ($cover_preview_image === '' && defined('JP_URL') && JP_URL !== '') {
    $cover_preview_image = trailingslashit(JP_URL) . 'assets/img/group-cover-placeholder.svg';
}
if ($cover_preview_image === '') {
    $cover_preview_image = plugins_url('../assets/img/group-cover-placeholder.svg', __FILE__);
}
if ($cover_preview_image === '') {
    $cover_preview_image = $cover_placeholder;
}
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
$relationship_keys = array_keys($relationship_options);
if ($current_relationship === '' && isset($relationship_keys[0])) {
    $current_relationship = (string) $relationship_keys[0];
}
$category_label = isset($group_categories[$current_category]) ? (string) $group_categories[$current_category] : ucwords(str_replace(['-', '_'], ' ', $current_category));
$current_instant     = isset($form_values['instant_access']) ? (string) $form_values['instant_access'] === 'on' : false;
$current_access_timing = isset($form_values['access_timing']) ? (string) $form_values['access_timing'] : '';
if ($current_access_timing === '' && ($current_instant || stripos($current_delivery, 'imediat') !== false)) {
    $current_access_timing = 'immediate';
} elseif ($current_access_timing === '') {
    $current_access_timing = 'scheduled';
}

if ($current_delivery === '') {
    $current_delivery = $current_access_timing === 'immediate'
        ? __('Imediatamente após a confirmação', 'juntaplay')
        : __('Após liberação manual do administrador', 'juntaplay');
}

$initial_create_view = 'selector';
$initial_create_allowed = '0';

$has_form_state = $current_pool !== ''
    || $current_name !== ''
    || $current_service !== ''
    || $current_description !== ''
    || $current_price !== ''
    || $current_total !== ''
    || $current_reserved !== ''
    || !empty($form_values)
    || !empty($form_errors);

if ($has_form_state) {
    $initial_create_view    = 'wizard';
    $initial_create_allowed = '1';
}

$default_rule_items = [
    __('Não compartilhe a senha com ninguém fora deste grupo de assinatura', 'juntaplay'),
    __('Use somente a conta combinada e não altere dados de login ou recuperação', 'juntaplay'),
];

$default_rule_count = count($default_rule_items);

$rule_items = $default_rule_items;
$rule_extra = '';

if ($current_rules !== '') {
    $rule_lines = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $current_rules))));
    foreach ($rule_lines as $index => $line) {
        if ($index < $default_rule_count) {
            $rule_items[$index] = $line !== '' ? $line : $default_rule_items[$index];

            continue;
        }

        $rule_extra .= ($rule_extra !== '' ? "\n" : '') . $line;
    }
}

if (strlen($rule_extra) > 500) {
    $rule_extra = substr($rule_extra, 0, 500);
}

$compiled_rules = [];
foreach ($rule_items as $rule_index => $rule_text) {
    $compiled_rules[] = sprintf('%d - %s', $rule_index + 1, $rule_text);
}
if (trim($rule_extra) !== '') {
    $compiled_rules[] = trim($rule_extra);
}

$current_rules = trim(implode("\n", $compiled_rules));

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
if ($current_relationship !== '') {
    $share_lines[] = sprintf(
        esc_html__('Relação: %s', 'juntaplay'),
        esc_html($relationship_options[$current_relationship] ?? $current_relationship)
    );
}
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
    $share_lines[] = sprintf(esc_html__('Suporte a membros: %s', 'juntaplay'), esc_html($current_support));
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
$current_page = isset($pagination['page']) ? max(1, (int) $pagination['page']) : 1;
$total_pages  = isset($pagination['pages']) ? max(1, (int) $pagination['pages']) : 1;
$per_page     = isset($pagination['per_page']) ? max(1, (int) $pagination['per_page']) : 12;
$total_groups = isset($pagination['total']) ? max(0, (int) $pagination['total']) : count($all_groups);
$pagination_base_raw = remove_query_arg('jp_groups_page');
$pagination_base     = is_string($pagination_base_raw) ? trim((string) $pagination_base_raw) : '';
$visible_count       = count($all_groups);

if ($pagination_base !== '') {
    $has_scheme = (bool) preg_match('~^https?://~i', $pagination_base);

    if (!$has_scheme) {
        if ($pagination_base !== '' && $pagination_base[0] === '?') {
            $pagination_base = home_url($pagination_base);
        } else {
            $path = '/' . ltrim($pagination_base, '/');
            $pagination_base = home_url($path);
        }
    }
}

if ($pagination_base === '') {
    $current_page_id = get_queried_object_id();

    if ($current_page_id) {
        $permalink = get_permalink($current_page_id);
        if (is_string($permalink) && $permalink !== '') {
            $pagination_base = $permalink;
        }
    }
}

if ($pagination_base === '') {
    $request_uri = isset($_SERVER['REQUEST_URI']) ? (string) wp_unslash($_SERVER['REQUEST_URI']) : '';
    $pagination_base = $request_uri !== '' ? home_url($request_uri) : home_url('/');
}
$page_offset = ($current_page - 1) * $per_page;
$page_start  = $total_groups > 0 ? $page_offset + 1 : 0;
$page_end    = min($total_groups, $page_offset + $visible_count);
$campanhas_page_id = (int) get_option('juntaplay_page_campanhas');
$campaigns_url = $campanhas_page_id ? get_permalink($campanhas_page_id) : home_url('/grupos');

if ($group_suggestions) {
    $group_suggestions = array_values($group_suggestions);
    shuffle($group_suggestions);
    if (count($group_suggestions) > 5) {
        $group_suggestions = array_slice($group_suggestions, 0, 5);
    }
}
?>
<div id="jp-profile-groups" class="juntaplay-groups" data-groups data-role-filter="all" data-status-filter="all" data-cover-placeholder="<?php echo esc_url($cover_placeholder); ?>">
    <section class="juntaplay-diagnostics" data-group-diagnostics hidden>
        <header class="juntaplay-diagnostics__header">
            <strong class="juntaplay-diagnostics__title"><?php esc_html_e('Diagnóstico dos grupos', 'juntaplay'); ?></strong>
            <span class="juntaplay-diagnostics__percent" data-diagnostics-percent>0%</span>
        </header>
        <div class="juntaplay-diagnostics__progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" data-diagnostics-progress>
            <div class="juntaplay-diagnostics__progress-bar" data-diagnostics-bar style="width:0%;"></div>
        </div>
        <button type="button" class="juntaplay-diagnostics__toggle" data-diagnostics-toggle aria-expanded="false">
            <?php esc_html_e('Detalhes', 'juntaplay'); ?>
        </button>
        <ul class="juntaplay-diagnostics__list" data-diagnostics-list hidden></ul>
    </section>
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

    <div class="juntaplay-groups__list">
        <?php if ($cancel_general_errors) : ?>
            <div class="juntaplay-alert juntaplay-alert--danger">
                <ul>
                    <?php foreach ($cancel_general_errors as $cancel_general_error) : ?>
                        <li><?php echo esc_html((string) $cancel_general_error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        <?php if ($all_groups) : ?>
            <div class="juntaplay-groups__cards" data-group-list>
                <?php foreach ($all_groups as $group) :
                    if (!is_array($group)) {
                        continue;
                    }

                    $group_id        = isset($group['id']) ? (int) $group['id'] : 0;
                    $group_title     = isset($group['title']) ? (string) $group['title'] : '';
                    $group_role      = isset($group['membership_role']) ? (string) $group['membership_role'] : 'member';
                    $owner_id        = isset($group['owner_id']) ? (int) $group['owner_id'] : 0;
                    $is_owner        = $owner_id > 0 ? $owner_id === $current_user_id : ($group_role === 'owner');
                    $role_label      = isset($group['role_label']) ? (string) $group['role_label'] : '';
                    $role_tone       = isset($group['role_tone']) ? (string) $group['role_tone'] : '';
                    $status          = isset($group['status']) ? (string) $group['status'] : '';
                    $membership_status = isset($group['membership_status']) ? (string) $group['membership_status'] : 'active';
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
                    $cancellation_meta = isset($group['cancellation']) && is_array($group['cancellation']) ? $group['cancellation'] : null;
                    $cancellation_reason = '';
                    $cancellation_note   = '';
                    $cancellation_created = '';
                    $cancellation_display = '';
                    if (is_array($cancellation_meta)) {
                        $cancellation_reason = isset($cancellation_meta['reason']) ? (string) $cancellation_meta['reason'] : '';
                        $cancellation_note   = isset($cancellation_meta['message']) ? (string) $cancellation_meta['message'] : '';
                        $cancellation_created = isset($cancellation_meta['created_at']) ? (string) $cancellation_meta['created_at'] : '';
                        if ($cancellation_created !== '') {
                            $timestamp = mysql2date('U', $cancellation_created, false);
                            if ($timestamp) {
                                $cancellation_display = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $timestamp);
                            }
                        }
                    }
                    $group_cancel_messages = isset($cancel_group_errors[$group_id]) && is_array($cancel_group_errors[$group_id]) ? $cancel_group_errors[$group_id] : [];
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

                    $group_title_full = $group_title !== '' ? $group_title : __('Grupo sem nome', 'juntaplay');
                    if ($price_highlight === '' && $member_display !== '') {
                        $price_highlight = $member_display;
                    }

                    $cover_url = isset($group['cover_url']) ? (string) $group['cover_url'] : $cover_placeholder;
                    $cover_alt = isset($group['cover_alt']) ? (string) $group['cover_alt'] : esc_html__('Capa do grupo', 'juntaplay');
                    $cover_placeholder_flag = !empty($group['cover_placeholder']);
                    $show_cover_hint = $cover_placeholder_flag && in_array($group_role, ['owner', 'manager'], true);
                    ?>
                    <article class="juntaplay-group-card juntaplay-group-card--profile" data-group-item data-group-id="<?php echo esc_attr((string) $group_id); ?>" data-group-role="<?php echo esc_attr($group_role); ?>" data-group-status="<?php echo esc_attr($status); ?>">
                                    <figure class="juntaplay-group-card__cover<?php echo $cover_placeholder_flag ? ' is-placeholder' : ''; ?>">
                                        <img src="<?php echo esc_url($cover_url); ?>" alt="<?php echo esc_attr($cover_alt); ?>" loading="lazy" width="495" height="370" />
                                        <?php if ($show_cover_hint) : ?>
                                            <span><?php esc_html_e('Adicione uma capa para aumentar as conversões.', 'juntaplay'); ?></span>
                                        <?php endif; ?>
                                    </figure>
                                    <div class="juntaplay-group-card__body">
                                        <header class="juntaplay-group-card__header juntaplay-group-card__header--compact">
                                            <h3 class="juntaplay-group-card__title" title="<?php echo esc_attr($group_title_full); ?>"><?php echo esc_html($group_title_full); ?></h3>
                                            <?php if ($role_label !== '') : ?>
                                                <span class="juntaplay-group-card__role-pill juntaplay-badge juntaplay-badge--<?php echo esc_attr($role_tone ?: 'info'); ?>"><?php echo esc_html($role_label); ?></span>
                                            <?php endif; ?>
                                        </header>
                                        <div class="juntaplay-group-card__actions juntaplay-group-card__actions--primary">
                                            <button type="button" class="juntaplay-group-card__toggle" aria-expanded="false">
                                                <span class="juntaplay-group-card__toggle-label"><?php esc_html_e('Ver detalhes', 'juntaplay'); ?></span>
                                                <svg width="16" height="16" viewBox="0 0 16 16" aria-hidden="true" focusable="false">
                                                    <path d="M4 6l4 4l4-4" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                                </svg>
                                            </button>
                                        </div>
                                        <div class="juntaplay-group-card__details-extended" hidden>
                                            <div class="juntaplay-group-card__chips">
                                                <?php if ($availability_label !== '') : ?>
                                                    <span class="juntaplay-badge juntaplay-badge--<?php echo esc_attr($availability_tone ?: 'info'); ?>"><?php echo esc_html($availability_label); ?></span>
                                                <?php endif; ?>
                                                <?php if ($status_label !== '') : ?>
                                                    <span class="juntaplay-badge juntaplay-badge--<?php echo esc_attr($status_tone ?: 'info'); ?>"><?php echo esc_html($status_label); ?></span>
                                                <?php endif; ?>
                                            </div>
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
                                            </div>
                                            <?php if ($cta_label !== '' || $price_highlight !== '') : ?>
                                                <div class="juntaplay-group-card__cta">
                                                    <?php if ($price_highlight !== '') : ?>
                                                        <span class="juntaplay-group-card__cta-price"><?php echo esc_html($price_highlight); ?></span>
                                                    <?php endif; ?>
                                                    <?php if ($cta_label !== '') : ?>
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
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                            <div class="juntaplay-group-card__details-actions">
                                                <?php if ($is_owner) : ?>
                                                    <button type="button" class="juntaplay-group-card__edit" data-group-id="<?php echo esc_attr((string) $group_id); ?>"><?php esc_html_e('Editar grupo', 'juntaplay'); ?></button>
                                                <?php endif; ?>
                                                <?php if ($membership_status !== 'guest') : ?>
                                                    <button type="button" class="juntaplay-group-card__access-btn" data-group-access="<?php echo esc_attr((string) $group_id); ?>">
                                                        <?php esc_html_e('Ver dados de acesso', 'juntaplay'); ?>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                            <div class="juntaplay-group-card__access-panel" data-group-access-panel hidden>
                                                <h4 class="juntaplay-group-card__access-title"><?php esc_html_e('Dados de acesso do grupo', 'juntaplay'); ?></h4>
                                                <dl class="juntaplay-group-card__access-list" data-group-access-details></dl>
                                                <p class="juntaplay-group-card__access-hint" data-group-access-hint hidden></p>
                                            </div>
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
                                                    <strong><?php echo esc_html__('Grupo', 'juntaplay'); ?>:</strong>
                                                    <?php echo $pool_title !== '' ? esc_html($pool_title) : '<span class="juntaplay-profile__empty">' . esc_html__('Ainda não vinculada', 'juntaplay') . '</span>'; ?>
                                                </li>
                                                <?php if ($support_channel !== '') : ?>
                                                    <li>
                                                        <strong><?php echo esc_html__('Suporte a Membros', 'juntaplay'); ?>:</strong>
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
                                                        <strong><?php echo esc_html__('Acesso', 'juntaplay'); ?>:</strong>
                                                        <?php echo esc_html($instant_label); ?>
                                                    </li>
                                                <?php endif; ?>
                                            </ul>
                                            <div class="juntaplay-group-card__enrollment">
                                                <span class="juntaplay-group-card__enrollment-label"><?php echo esc_html__('Total da inscrição', 'juntaplay'); ?></span>
                                                <strong class="juntaplay-group-card__enrollment-value"><?php echo esc_html($enrollment_total); ?></strong>
                                                <?php if ($blocked_notice !== '') : ?>
                                                    <p class="juntaplay-group-card__enrollment-note"><?php echo esc_html($blocked_notice); ?></p>
                                                <?php endif; ?>
                                            </div>
                                            <div class="juntaplay-group-card__members">
                                                <span class="juntaplay-group-card__members-label"><?php echo esc_html__('Participantes recentes', 'juntaplay'); ?></span>
                                                <?php if ($member_names) : ?>
                                                    <ul class="juntaplay-group-card__members-list">
                                                        <?php foreach ($member_names as $member_name) : ?>
                                                            <li><?php echo esc_html((string) $member_name); ?></li>
                                                        <?php endforeach; ?>
                                                        <?php if ($members_remaining > 0) : ?>
                                                            <li class="juntaplay-group-card__members-more"><?php echo esc_html(sprintf(_n('e mais %d participante', 'e mais %d participantes', $members_remaining, 'juntaplay'), $members_remaining)); ?></li>
                                                        <?php endif; ?>
                                                    </ul>
                                                <?php else : ?>
                                                    <p class="juntaplay-group-card__members-empty"><?php echo esc_html__('Seja o primeiro a entrar neste grupo!', 'juntaplay'); ?></p>
                                                <?php endif; ?>
                                            </div>
                                            <?php if ($status_message !== '') : ?>
                                                <p class="juntaplay-group-card__message"><?php echo esc_html($status_message); ?></p>
                                            <?php endif; ?>
                                            <?php if ($group_rules !== '') : ?>
                                                <div class="juntaplay-group-card__rules" role="note">
                                                    <h4><?php echo esc_html__('Regras combinadas', 'juntaplay'); ?></h4>
                                                    <p><?php echo esc_html($group_rules); ?></p>
                                                </div>
                                            <?php endif; ?>
                                            <?php if ($review_note !== '') : ?>
                                                <div class="juntaplay-group-card__review" role="note">
                                                    <h4><?php echo esc_html__('Retorno da moderação', 'juntaplay'); ?></h4>
                                                    <p><?php echo esc_html($review_note); ?></p>
                                                </div>
                                            <?php endif; ?>
                                            <div class="juntaplay-group-card__share" data-group-share>
                                                <header class="juntaplay-group-card__share-header">
                                                    <span class="juntaplay-group-card__share-label"><?php echo esc_html__('Compartilhar', 'juntaplay'); ?></span>
                                                    <?php if ($share_domain !== '') : ?>
                                                        <span class="juntaplay-group-card__share-domain"><?php echo esc_html($share_domain); ?></span>
                                                    <?php endif; ?>
                                                    <button type="button" class="juntaplay-button juntaplay-button--ghost" data-group-share-copy><?php echo esc_html__('Copiar convite', 'juntaplay'); ?></button>
                                                </header>
                                                <pre class="juntaplay-group-card__share-snippet" data-group-share-snippet><?php echo esc_html($share_snippet); ?></pre>
                                                <textarea class="juntaplay-group-card__share-text" data-group-share-text hidden><?php echo esc_textarea($share_snippet); ?></textarea>
                                                <p class="juntaplay-group-card__share-help"><?php echo esc_html__('Envie este resumo para convidar pessoas de confiança.', 'juntaplay'); ?></p>
                                            </div>
                                            <?php if (!$is_owner) : ?>
                                                <div class="juntaplay-group-card__complaint juntaplay-group-card__cancel">
                                                    <header class="juntaplay-group-card__complaint-header">
                                                        <div>
                                                            <h4><?php echo esc_html__('Cancelar participação', 'juntaplay'); ?></h4>
                                                            <?php if ($cancellation_display !== '') : ?>
                                                                <p class="juntaplay-group-card__complaint-summary"><?php echo esc_html(sprintf(__('Cancelamento registrado em %s.', 'juntaplay'), $cancellation_display)); ?></p>
                                                            <?php else : ?>
                                                                <p class="juntaplay-group-card__complaint-summary"><?php echo esc_html__('Caso precise encerrar sua assinatura, avise o motivo para que possamos registrar o cancelamento.', 'juntaplay'); ?></p>
                                                            <?php endif; ?>
                                                        </div>
                                                        <span class="juntaplay-badge juntaplay-badge--<?php echo esc_attr($membership_status === 'canceled' ? 'warning' : 'positive'); ?>">
                                                            <?php echo esc_html($membership_status === 'canceled' ? __('Cancelado', 'juntaplay') : __('Participação ativa', 'juntaplay')); ?>
                                                        </span>
                                                    </header>
                                                    <?php if ($cancellation_note !== '') : ?>
                                                        <p class="juntaplay-group-card__complaint-note"><?php echo esc_html($cancellation_note); ?></p>
                                                    <?php endif; ?>
                                                    <?php if ($cancellation_reason !== '') : ?>
                                                        <p class="juntaplay-group-card__complaint-note"><?php echo esc_html(sprintf(__('Motivo informado: %s', 'juntaplay'), $cancellation_reason)); ?></p>
                                                    <?php endif; ?>
                                                    <?php if ($membership_status !== 'canceled') : ?>
                                                        <?php if ($group_cancel_messages) : ?>
                                                            <div class="juntaplay-alert juntaplay-alert--danger">
                                                                <ul>
                                                                    <?php foreach ($group_cancel_messages as $cancel_message) : ?>
                                                                        <li><?php echo esc_html((string) $cancel_message); ?></li>
                                                                    <?php endforeach; ?>
                                                                </ul>
                                                            </div>
                                                        <?php endif; ?>
                                                        <form class="juntaplay-group-cancel__form" method="post">
                                                            <input type="hidden" name="jp_profile_action" value="1" />
                                                            <input type="hidden" name="jp_profile_section" value="group_cancel" />
                                                            <input type="hidden" name="jp_profile_group_cancel" value="<?php echo esc_attr((string) $group_id); ?>" />
                                                            <?php
                                                            $cancel_profile_nonce = wp_nonce_field(
                                                                'juntaplay_profile_update',
                                                                'jp_profile_nonce',
                                                                true,
                                                                false
                                                            );
                                                            $cancel_profile_nonce = preg_replace(
                                                                '/id="jp_profile_nonce"/',
                                                                'id="' . esc_attr(wp_unique_id('jp_profile_nonce_')) . '"',
                                                                $cancel_profile_nonce
                                                            );
                                                            echo $cancel_profile_nonce; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

                                                            $cancel_action_nonce = wp_nonce_field(
                                                                'jp_profile_group_cancel',
                                                                'jp_profile_group_cancel_nonce',
                                                                true,
                                                                false
                                                            );
                                                            $cancel_action_nonce = preg_replace(
                                                                '/id="jp_profile_group_cancel_nonce"/',
                                                                'id="' . esc_attr(wp_unique_id('jp_profile_group_cancel_nonce_')) . '"',
                                                                $cancel_action_nonce
                                                            );
                                                            echo $cancel_action_nonce; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                            ?>
                                                            <div class="juntaplay-form__group">
                                                                <label for="jp-group-cancel-reason-<?php echo esc_attr($group_id); ?>"><?php echo esc_html__('Descreva o motivo do cancelamento', 'juntaplay'); ?></label>
                                                                <textarea id="jp-group-cancel-reason-<?php echo esc_attr($group_id); ?>" name="jp_profile_group_cancel_reason" class="juntaplay-form__input" rows="3" placeholder="<?php echo esc_attr__('Explique o que aconteceu para que possamos orientar o administrador.', 'juntaplay'); ?>"></textarea>
                                                                <p class="juntaplay-form__help"><?php echo esc_html__('Após confirmar, seu acesso ao grupo é encerrado imediatamente.', 'juntaplay'); ?></p>
                                                            </div>
                                                            <div class="juntaplay-group-complaint__actions">
                                                                <button type="submit" class="juntaplay-button juntaplay-button--ghost"><?php echo esc_html__('Cancelar participação', 'juntaplay'); ?></button>
                                                            </div>
                                                        </form>
                                                    <?php else : ?>
                                                        <p class="juntaplay-group-card__complaint-description"><?php echo esc_html__('Se quiser retomar futuramente, solicite uma nova entrada ao administrador ou fale com o suporte.', 'juntaplay'); ?></p>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="juntaplay-group-card__complaint" data-group-complaint>
                                                <header class="juntaplay-group-card__complaint-header">
                                                    <div>
                                                        <h4><?php echo esc_html__('Teve algum problema?', 'juntaplay'); ?></h4>
                                                        <?php if ($complaint_summary_text !== '') : ?>
                                                            <p class="juntaplay-group-card__complaint-summary"><?php echo esc_html($complaint_summary_text); ?></p>
                                                        <?php endif; ?>
                                                    </div>
                                                    <?php if ($complaint_status_label !== '') : ?>
                                                        <span class="juntaplay-badge juntaplay-badge--<?php echo esc_attr($complaint_status_tone ?: 'info'); ?>"><?php echo esc_html($complaint_status_label); ?></span>
                                                    <?php endif; ?>
                                                </header>
                                                <?php if ($complaint_status_message !== '') : ?>
                                                    <p class="juntaplay-group-card__complaint-note"><?php echo esc_html($complaint_status_message); ?></p>
                                                <?php endif; ?>
                                                <?php if ($complaints_open > 0) : ?>
                                                    <p class="juntaplay-group-card__complaint-open"><?php echo esc_html(sprintf(_n('Você possui %d reclamação aberta.', 'Você possui %d reclamações abertas.', $complaints_open, 'juntaplay'), $complaints_open)); ?></p>
                                                <?php endif; ?>
                                                <?php if ($complaints_total > 0) : ?>
                                                    <p class="juntaplay-group-card__complaint-open"><?php echo esc_html(sprintf(_n('Você já abriu %d reclamação para este grupo.', 'Você já abriu %d reclamações para este grupo.', $complaints_total, 'juntaplay'), $complaints_total)); ?></p>
                                                <?php endif; ?>
                                                <?php if ($complaint_success_messages) : ?>
                                                    <div class="juntaplay-alert juntaplay-alert--success">
                                                        <ul>
                                                            <?php foreach ($complaint_success_messages as $success_message) : ?>
                                                                <li><?php echo esc_html((string) $success_message); ?></li>
                                                            <?php endforeach; ?>
                                                        </ul>
                                                    </div>
                                                <?php endif; ?>
                                                <?php if ($complaint_messages) : ?>
                                                    <div class="juntaplay-alert juntaplay-alert--danger">
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
                                                    <?php
                                                    $complaint_nonce_field = wp_nonce_field(
                                                        'juntaplay_profile_update',
                                                        'jp_profile_nonce',
                                                        true,
                                                        false
                                                    );
                                                    $complaint_nonce_field = preg_replace(
                                                        '/id="jp_profile_nonce"/',
                                                        'id="' . esc_attr(wp_unique_id('jp_profile_nonce_')) . '"',
                                                        $complaint_nonce_field
                                                    );
                                                    echo $complaint_nonce_field; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                    ?>
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
                                            <?php endif; ?>
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
                                        </div>
                                </article>
                <?php endforeach; ?>
                <p class="juntaplay-groups__empty is-hidden" data-group-empty><?php echo esc_html__('Nenhum grupo corresponde aos filtros selecionados.', 'juntaplay'); ?></p>
            </div>
            <?php if ($total_pages > 1) : ?>
                <?php
                $pagination_links = paginate_links([
                    'base'      => add_query_arg('jp_groups_page', '%#%', $pagination_base),
                    'format'    => '',
                    'current'   => $current_page,
                    'total'     => $total_pages,
                    'type'      => 'array',
                    'prev_text' => __('Anterior', 'juntaplay'),
                    'next_text' => __('Próximo', 'juntaplay'),
                ]);

                if ($pagination_links) :
                    $pagination_summary = sprintf(
                        /* translators: 1: first group number shown, 2: last group number shown, 3: total groups */
                        __('Mostrando %1$d–%2$d de %3$d grupos', 'juntaplay'),
                        $page_start,
                        $page_end,
                        $total_groups
                    );
                    ?>
                    <nav class="juntaplay-pagination" aria-label="<?php echo esc_attr__('Paginação de grupos', 'juntaplay'); ?>">
                        <span class="juntaplay-pagination__summary"><?php echo esc_html($pagination_summary); ?></span>
                        <?php foreach ($pagination_links as $pagination_link) : ?>
                            <?php
                            $is_current   = strpos($pagination_link, 'current') !== false;
                            $is_prev      = strpos($pagination_link, 'prev') !== false;
                            $is_next      = strpos($pagination_link, 'next') !== false;
                            $is_ellipsis  = strpos($pagination_link, 'dots') !== false;
                            $is_disabled  = strpos($pagination_link, '<span') === 0;

                            if ($is_ellipsis) {
                                echo '<span class="juntaplay-pagination__ellipsis">' . esc_html__('…', 'juntaplay') . '</span>';

                                continue;
                            }

                            $element_class = $is_prev || $is_next ? 'juntaplay-pagination__nav' : 'juntaplay-pagination__page';

                            if ($is_current) {
                                $element_class .= ' is-active';
                            }

                            if ($is_disabled && ($is_prev || $is_next)) {
                                $element_class .= ' is-disabled';
                            }

                            $link_text = trim(wp_strip_all_tags($pagination_link));

                            if ($is_disabled) {
                                echo '<span class="' . esc_attr($element_class) . '">' . esc_html($link_text) . '</span>';

                                continue;
                            }

                            $href = '';
                            if (preg_match('/href="([^"]+)"/', $pagination_link, $href_match)) {
                                $href = $href_match[1];
                            }

                            $rel_attribute = '';
                            if (preg_match('/rel="([^"]+)"/', $pagination_link, $rel_match)) {
                                $rel_attribute = $rel_match[1];
                            }

                            ?>
                            <a class="<?php echo esc_attr($element_class); ?>" href="<?php echo esc_url($href); ?>"<?php echo $rel_attribute !== '' ? ' rel="' . esc_attr($rel_attribute) . '"' : ''; ?>><?php echo esc_html($link_text); ?></a>
                        <?php endforeach; ?>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        <?php else : ?>
            <p class="juntaplay-profile__empty"><?php echo esc_html__('Você ainda não participa de nenhum grupo. Crie um novo grupo ou participe de outro grupo para aparecer aqui.', 'juntaplay'); ?></p>
        <?php endif; ?>
    </div>

    <section class="juntaplay-groups__create-card">
        <header class="juntaplay-groups__create-header">
            <div class="juntaplay-groups__create-heading">
                <h3><?php echo esc_html__('Criar novo grupo', 'juntaplay'); ?></h3>
                <p><?php echo esc_html__('Os grupos são públicos e passam por análise do super administrador. Assim que o pedido for aprovado, todos os participantes recebem um e-mail de confirmação.', 'juntaplay'); ?></p>
            </div>
        </header>
        <div class="juntaplay-groups__create-launch">
            <div class="juntaplay-groups__create-summary">
                <p><?php echo esc_html__('Clique no botão abaixo para informar os detalhes e enviar seu grupo para moderação.', 'juntaplay'); ?></p>
                <ul class="juntaplay-groups__create-highlights">
                    <li><?php echo esc_html__('Aprovação rápida pelo super administrador', 'juntaplay'); ?></li>
                    <li><?php echo esc_html__('Alertas automáticos para todos os participantes', 'juntaplay'); ?></li>
                    <li><?php echo esc_html__('Layout responsivo e fácil de configurar', 'juntaplay'); ?></li>
                </ul>
            </div>
            <div class="juntaplay-groups__create-actions">
                <button type="button" class="juntaplay-button juntaplay-button--primary juntaplay-groups__create-trigger" data-group-create-trigger>
                    <?php esc_html_e('Criar novo grupo', 'juntaplay'); ?>
                </button>
            </div>
        </div>
    </section>

    <?php if ($should_open_success) : ?>
        <div
            class="juntaplay-groups__success-state"
            data-group-success-state
            data-success-heading="<?php echo esc_attr($success_heading); ?>"
            data-success-body="<?php echo esc_attr($success_body); ?>"
            data-success-image="<?php echo esc_url($success_image); ?>"
            data-success-redirect="<?php echo esc_url($success_redirect); ?>"
        ></div>
    <?php endif; ?>

    <template id="jp-group-create-template" data-auto-open="<?php echo !empty($form_errors) ? '1' : '0'; ?>" data-loading-text="<?php echo esc_attr__('Carregando formulário...', 'juntaplay'); ?>">
            <div
                class="juntaplay-groups__create-modal"
                data-group-view-root
                data-group-view-active="<?php echo esc_attr($initial_create_view); ?>"
                data-group-view-default="selector"
                data-group-view-allowed="<?php echo esc_attr($initial_create_allowed); ?>"
            >
                <header class="juntaplay-groups__create-header">
                    <div class="juntaplay-groups__create-heading">
                        <h3><?php echo esc_html__('Criar novo grupo', 'juntaplay'); ?></h3>
                        <p><?php echo esc_html__('Os grupos são públicos e passam por análise do super administrador. Assim que o pedido for aprovado, todos os participantes recebem um e-mail de confirmação.', 'juntaplay'); ?></p>
                    </div>
                </header>

                <div class="juntaplay-groups__create-body">
                        <section
                            class="juntaplay-groups__panel juntaplay-groups__service-view<?php echo $initial_create_view === 'selector' ? '' : ' is-hidden'; ?>"
                            data-group-view="selector"
                            aria-live="polite"
                        >
                            <?php if ($form_errors) : ?>
                                <ul class="juntaplay-form__errors" role="alert">
                                    <?php foreach ($form_errors as $error_message) : ?>
                                        <li><?php echo esc_html($error_message); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                            <div class="juntaplay-service-list__header">
                                <h4><?php echo esc_html__('O que vai compartilhar hoje?', 'juntaplay'); ?></h4>
                            </div>
                            <?php if ($pool_featured) : ?>
                                <ul class="juntaplay-service-list">
                                    <?php foreach ($pool_featured as $pool) :
                                        $pool_id       = isset($pool['id']) ? (int) $pool['id'] : 0;
                                        $pool_slug     = isset($pool['slug']) ? (string) $pool['slug'] : '';
                                        $pool_title    = isset($pool['title']) ? (string) $pool['title'] : '';
                                        $pool_price    = isset($pool['price']) ? (float) $pool['price'] : 0.0;
                                        $pool_excerpt  = isset($pool['excerpt']) ? (string) $pool['excerpt'] : '';
                                        $pool_category = isset($pool['category']) ? (string) $pool['category'] : '';
                                        $pool_total    = isset($pool['quotas_total']) ? (int) $pool['quotas_total'] : 0;
                                        $pool_start    = isset($pool['quota_start']) ? (int) $pool['quota_start'] : 0;
                                        $pool_end      = isset($pool['quota_end']) ? (int) $pool['quota_end'] : 0;
                                        $pool_icon   = isset($pool['icon']) ? (string) $pool['icon'] : '';
                                        $pool_thumb  = isset($pool['thumbnail']) ? (string) $pool['thumbnail'] : '';
                                        $pool_cover  = isset($pool['cover']) ? (string) $pool['cover'] : '';
                                        $pool_thumbnail = $pool_icon !== '' ? $pool_icon : $pool_thumb;
                                        if ($pool_thumbnail === '' && $pool_slug !== '') {
                                            $pool_thumbnail = ServiceIcons::get($pool_slug);
                                        }
                                        if ($pool_thumbnail === '') {
                                            $pool_thumbnail = ServiceIcons::fallback();
                                        }
                                        $category_label = $pool_category !== '' && isset($group_categories[$pool_category]) ? (string) $group_categories[$pool_category] : '';
                                        $display_price = function_exists('wc_price') ? wc_price($pool_price) : number_format((float) $pool_price, 2, ',', '.');
                                        $member_count  = $pool_total > 0
                                            ? sprintf(_n('%d usuário', '%d usuários', $pool_total, 'juntaplay'), $pool_total)
                                            : '';
                                        $initial_icon_raw = $pool_title !== ''
                                            ? (function_exists('mb_substr') ? mb_substr($pool_title, 0, 1) : substr($pool_title, 0, 1))
                                            : '';
                                        $initial_icon  = strtoupper($initial_icon_raw);
                                    ?>
                                        <li>
                                            <button
                                                type="button"
                                                class="juntaplay-service-list__item"
                                                data-group-pool-apply
                                                data-pool-id="<?php echo esc_attr((string) $pool_id); ?>"
                                                data-pool-name="<?php echo esc_attr($pool_title); ?>"
                                                data-pool-price="<?php echo esc_attr((string) $pool_price); ?>"
                                                data-pool-category="<?php echo esc_attr($pool_category); ?>"
                                                data-pool-excerpt="<?php echo esc_attr($pool_excerpt); ?>"
                                                data-pool-total="<?php echo esc_attr((string) $pool_total); ?>"
                                                data-pool-start="<?php echo esc_attr((string) $pool_start); ?>"
                                                data-pool-end="<?php echo esc_attr((string) $pool_end); ?>"
                                                data-pool-icon="<?php echo esc_url($pool_icon); ?>"
                                                data-pool-cover="<?php echo esc_url($pool_cover); ?>"
                                                data-pool-url="<?php echo esc_url($pool['service_url'] ?? ''); ?>"
                                            >
                                                <span class="juntaplay-service-list__icon<?php echo $pool_thumbnail !== '' ? ' has-image' : ''; ?>"<?php if ($pool_thumbnail !== '') : ?> style="background-image: url('<?php echo esc_url($pool_thumbnail); ?>');"<?php endif; ?> aria-hidden="true"><?php echo $pool_thumbnail === '' ? esc_html($initial_icon) : ''; ?></span>
                                                <span class="juntaplay-service-list__content">
                                                    <span class="juntaplay-service-list__title"><?php echo esc_html($pool_title); ?></span>
                                                    <?php if ($pool_excerpt !== '') : ?>
                                                        <span class="juntaplay-service-list__description"><?php echo esc_html($pool_excerpt); ?></span>
                                                    <?php endif; ?>
                                                    <span class="juntaplay-service-list__meta">
                                                        <?php if ($category_label !== '') : ?>
                                                            <span class="juntaplay-service-list__pill"><?php echo esc_html($category_label); ?></span>
                                                        <?php endif; ?>
                                                        <?php if ($member_count !== '') : ?>
                                                            <span class="juntaplay-service-list__pill juntaplay-service-list__pill--muted"><?php echo esc_html($member_count); ?></span>
                                                        <?php endif; ?>
                                                        <span class="juntaplay-service-list__price"><?php echo wp_kses_post($display_price); ?></span>
                                                    </span>
                                                </span>
                                                <span class="juntaplay-service-list__chevron" aria-hidden="true"></span>
                                            </button>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else : ?>
                                <p class="juntaplay-groups__catalog-empty"><?php echo esc_html__('Nenhum serviço disponível no momento.', 'juntaplay'); ?></p>
                            <?php endif; ?>
                            <div class="juntaplay-service-list__footer">
                                <button type="button" class="juntaplay-service-list__all" data-group-view-target="catalog">
                                    <?php echo esc_html__('Listar todos os serviços', 'juntaplay'); ?>
                                </button>
                            </div>
                        </section>
                        <section
                            class="juntaplay-groups__panel juntaplay-groups__service-catalog is-hidden"
                            data-group-view="catalog"
                            aria-live="polite"
                        >
                            <div class="juntaplay-service-grid__header">
                                <p class="juntaplay-eyebrow"><?php echo esc_html__('Serviços', 'juntaplay'); ?></p>
                                <h4><?php echo esc_html__('O que vai compartilhar hoje?', 'juntaplay'); ?></h4>
                            </div>
                            <div class="juntaplay-service-grid">
                                <?php foreach ($pool_catalog as $pool) :
                                    $pool_id       = isset($pool['id']) ? (int) $pool['id'] : 0;
                                    $pool_slug     = isset($pool['slug']) ? (string) $pool['slug'] : '';
                                    $pool_title    = isset($pool['title']) ? (string) $pool['title'] : '';
                                    $pool_price    = isset($pool['price']) ? (float) $pool['price'] : 0.0;
                                    $pool_excerpt  = isset($pool['excerpt']) ? (string) $pool['excerpt'] : '';
                                    $pool_category = isset($pool['category']) ? (string) $pool['category'] : '';
                                    $pool_total    = isset($pool['quotas_total']) ? (int) $pool['quotas_total'] : 0;
                                    $pool_start    = isset($pool['quota_start']) ? (int) $pool['quota_start'] : 0;
                                    $pool_end      = isset($pool['quota_end']) ? (int) $pool['quota_end'] : 0;
                                    $pool_icon   = isset($pool['icon']) ? (string) $pool['icon'] : '';
                                    $pool_thumb  = isset($pool['thumbnail']) ? (string) $pool['thumbnail'] : '';
                                    $pool_cover  = isset($pool['cover']) ? (string) $pool['cover'] : '';
                                    $pool_thumbnail = $pool_icon !== '' ? $pool_icon : $pool_thumb;
                                    if ($pool_thumbnail === '' && $pool_slug !== '') {
        
                                        $pool_thumbnail = ServiceIcons::get($pool_slug);
                                    }
                                    if ($pool_thumbnail === '') {
                                        $pool_thumbnail = ServiceIcons::fallback();
                                    }
                                    $category_label = $pool_category !== '' && isset($group_categories[$pool_category]) ? (string) $group_categories[$pool_category] : '';
                                    $display_price = function_exists('wc_price') ? wc_price($pool_price) : number_format((float) $pool_price, 2, ',', '.');
                                    $member_count  = $pool_total > 0
                                        ? sprintf(_n('%d usuário', '%d usuários', $pool_total, 'juntaplay'), $pool_total)
                                        : '';
                                    $initial_icon_raw = $pool_title !== ''
                                        ? (function_exists('mb_substr') ? mb_substr($pool_title, 0, 1) : substr($pool_title, 0, 1))
                                        : '';
                                    $initial_icon  = strtoupper($initial_icon_raw);
                                ?>
                                    <button
                                        type="button"
                                        class="juntaplay-service-card"
                                        data-group-pool-apply
                                        data-pool-id="<?php echo esc_attr((string) $pool_id); ?>"
                                        data-pool-name="<?php echo esc_attr($pool_title); ?>"
                                        data-pool-price="<?php echo esc_attr((string) $pool_price); ?>"
                                        data-pool-category="<?php echo esc_attr($pool_category); ?>"
                                        data-pool-excerpt="<?php echo esc_attr($pool_excerpt); ?>"
                                        data-pool-total="<?php echo esc_attr((string) $pool_total); ?>"
                                        data-pool-start="<?php echo esc_attr((string) $pool_start); ?>"
                                        data-pool-end="<?php echo esc_attr((string) $pool_end); ?>"
                                        data-pool-icon="<?php echo esc_url($pool_icon); ?>"
                                        data-pool-cover="<?php echo esc_url($pool_cover); ?>"
                                        data-pool-url="<?php echo esc_url($pool['service_url'] ?? ''); ?>"
                                    >
                                        <span class="juntaplay-service-card__icon<?php echo $pool_thumbnail !== '' ? ' has-image' : ''; ?>"<?php if ($pool_thumbnail !== '') : ?> style="background-image: url('<?php echo esc_url($pool_thumbnail); ?>');"<?php endif; ?> aria-hidden="true"><?php echo $pool_thumbnail === '' ? esc_html($initial_icon) : ''; ?></span>
                                        <span class="juntaplay-service-card__title"><?php echo esc_html($pool_title); ?></span>
                                        <?php if ($pool_excerpt !== '') : ?>
                                            <span class="juntaplay-service-card__description"><?php echo esc_html($pool_excerpt); ?></span>
                                        <?php endif; ?>
                                        <span class="juntaplay-service-card__meta">
                                            <?php if ($category_label !== '') : ?>
                                                <span class="juntaplay-service-card__pill"><?php echo esc_html($category_label); ?></span>
                                            <?php endif; ?>
                                            <?php if ($member_count !== '') : ?>
                                                <span class="juntaplay-service-card__pill juntaplay-service-card__pill--muted"><?php echo esc_html($member_count); ?></span>
                                            <?php endif; ?>
                                            <span class="juntaplay-service-card__price"><?php echo wp_kses_post($display_price); ?></span>
                                        </span>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                            <div class="juntaplay-service-grid__footer">
                                <button type="button" class="juntaplay-service-grid__cta" data-group-start-scratch>
                                    <?php echo esc_html__('O serviço que procura não está disponível? Crie um grupo', 'juntaplay'); ?>
                                </button>
                                <button type="button" class="juntaplay-service-grid__back" data-group-view-target="selector">
                                    <?php echo esc_html__('Voltar', 'juntaplay'); ?>
                                </button>
                            </div>
                        </section>

                    <div class="juntaplay-groups__panel juntaplay-groups__form-wrapper<?php echo $initial_create_view === 'wizard' ? '' : ' is-hidden'; ?>" data-group-view="wizard">
                        <?php if ($form_errors) : ?>
                            <ul class="juntaplay-form__errors" role="alert">
                                <?php foreach ($form_errors as $error_message) : ?>
                                    <li><?php echo esc_html($error_message); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>

                        <form method="post" class="juntaplay-form juntaplay-groups__form" data-group-wizard>
                            <input type="hidden" name="jp_profile_action" value="1" />
                            <input type="hidden" name="jp_profile_section" value="group_create" />
                            <input type="hidden" name="jp_profile_group_rules" id="jp-group-rules" value="<?php echo esc_attr($current_rules); ?>" data-rule-output />
                            <input type="hidden" name="jp_profile_group_instant" value="<?php echo $current_access_timing === 'immediate' ? 'on' : ''; ?>" data-access-instant />
                            <input type="hidden" name="jp_profile_group_access_timing" value="<?php echo esc_attr($current_access_timing); ?>" />
                            <?php
                            $create_nonce_field = wp_nonce_field(
                                'juntaplay_profile_update',
                                'jp_profile_nonce',
                                true,
                                false
                            );
                            $create_nonce_field = preg_replace(
                                '/id="jp_profile_nonce"/',
                                'id="' . esc_attr(wp_unique_id('jp_profile_nonce_')) . '"',
                                $create_nonce_field
                            );
                            echo $create_nonce_field; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                            ?>

                            <div class="juntaplay-steps__header">
                                <ol class="juntaplay-steps" data-group-stepper>
                                    <li class="juntaplay-steps__item is-active" data-step-index="0"><?php echo esc_html__('Serviço', 'juntaplay'); ?></li>
                                    <li class="juntaplay-steps__item" data-step-index="1"><?php echo esc_html__('Regras', 'juntaplay'); ?></li>
                                    <li class="juntaplay-steps__item" data-step-index="2"><?php echo esc_html__('Valores', 'juntaplay'); ?></li>
                                    <li class="juntaplay-steps__item" data-step-index="3"><?php echo esc_html__('Entrega', 'juntaplay'); ?></li>
                                    <li class="juntaplay-steps__item<?php echo $current_access_timing === 'immediate' ? '' : ' is-disabled'; ?>" data-step-index="4"><?php echo esc_html__('Acesso', 'juntaplay'); ?></li>
                                </ol>
                            </div>

                            <section class="juntaplay-form__step" data-group-step data-step-index="0">
                                <div class="juntaplay-form__grid">
                                    <div class="juntaplay-form__group">
                                        <label for="jp-group-name"><?php echo esc_html__('Nome do grupo', 'juntaplay'); ?></label>
                                        <input
                                            type="text"
                                            id="jp-group-name"
                                            name="jp_profile_group_name"
                                            class="juntaplay-form__input"
                                            value="<?php echo esc_attr($current_name); ?>"
                                            placeholder="<?php echo esc_attr__('Ex.: YouTube Premium', 'juntaplay'); ?>"
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
                                            data-group-pool-service
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
                                        data-group-pool-url
                                    />
                                </div>

                                <div
                                    class="juntaplay-form__group juntaplay-form__group--cover"
                                    data-group-cover
                                    data-placeholder="<?php echo esc_url($cover_placeholder); ?>"
                                    data-media-author="<?php echo esc_attr((string) $current_user_id); ?>"
                                    data-upload-context="profile-group-cover"
                                    data-group-cover-ready="0"
                                >
                                    <label for="jp-group-cover"><?php echo esc_html__('Capa do grupo (formato horizontal)', 'juntaplay'); ?></label>
                                    <div class="juntaplay-cover-picker" data-group-cover-wrapper>
                                        <div class="juntaplay-cover-picker__media"
                                            data-group-cover-preview
                                            style="background-image: url('<?php echo esc_url($cover_preview_image); ?>');">
                                            <img src="<?php echo esc_url($cover_preview_image); ?>"
                                                alt="<?php esc_attr_e('Pré-visualização da capa do grupo', 'juntaplay'); ?>"
                                                loading="lazy" />
                                        </div>

                                        <input type="hidden" id="jp-group-cover" name="jp_profile_group_cover" value="<?php echo esc_attr($current_cover_id); ?>" data-group-cover-input />
                                        <div class="juntaplay-cover-picker__actions">
                                            <button type="button" class="juntaplay-button juntaplay-button--ghost" data-group-cover-select><?php echo esc_html__('Escolher imagem', 'juntaplay'); ?></button>
                                            <button type="button" class="juntaplay-button juntaplay-button--subtle" data-group-cover-remove <?php disabled($current_cover_id === 0); ?>><?php echo esc_html__('Remover', 'juntaplay'); ?></button>
                                        </div>
                                        <p class="juntaplay-form__help"><?php echo esc_html__('Essa capa será usada nos cards públicos do seu grupo. Use uma imagem horizontal com pelo menos 500px de largura; o recorte é automático.', 'juntaplay'); ?></p>
                                    </div>
                                </div>

                                <div class="juntaplay-form__group">
                                    <label for="jp-group-pool"><?php echo esc_html__('Grupo vinculado (opcional)', 'juntaplay'); ?></label>
                                    <select id="jp-group-pool" name="jp_profile_group_pool" class="juntaplay-form__input" data-group-pool-select>
                                        <option value=""><?php echo esc_html__('Escolha um grupo', 'juntaplay'); ?></option>
                                        <?php foreach ($pool_choices as $pool_id => $pool_name) : ?>
                                            <option value="<?php echo esc_attr((string) $pool_id); ?>" <?php selected((string) $pool_id, $current_pool); ?>><?php echo esc_html((string) $pool_name); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <p class="juntaplay-form__help<?php echo $current_pool !== '' ? '' : ' is-hidden'; ?>" data-group-pool-help>
                                        <?php echo esc_html__('Serviços pré-aprovados são publicados imediatamente e notificam os participantes automaticamente.', 'juntaplay'); ?>
                                    </p>
                                </div>

                                <div class="juntaplay-form__nav">
                                    <button type="button" class="juntaplay-button juntaplay-button--primary" data-step-next><?php echo esc_html__('Avançar', 'juntaplay'); ?></button>
                                </div>
                            </section>

                            <section class="juntaplay-form__step is-hidden" data-group-step data-step-index="1">
                                <div class="juntaplay-form__group">
                                    <div class="juntaplay-rule-builder" data-rule-builder>
                                        <div class="juntaplay-rule-builder__header">
                                            <h4><?php echo esc_html__('Regras obrigatórias do grupo', 'juntaplay'); ?></h4>
                                            <p class="juntaplay-form__help"><?php echo esc_html__('As duas primeiras regras já vêm preenchidas, mas você pode ajustá-las. Todas ficam visíveis para os participantes.', 'juntaplay'); ?></p>
                                        </div>
                                        <div class="juntaplay-rule-builder__items">
                                            <?php foreach ($rule_items as $rule_index => $rule_text) :
                                                $input_id = 'jp-rule-item-' . ($rule_index + 1);
                                            ?>
                                                <div class="juntaplay-form__group juntaplay-rule-builder__item">
                                                    <label for="<?php echo esc_attr($input_id); ?>"><?php echo sprintf(esc_html__('Regra %d', 'juntaplay'), $rule_index + 1); ?></label>
                                                    <textarea
                                                        id="<?php echo esc_attr($input_id); ?>"
                                                        class="juntaplay-form__input"
                                                        rows="2"
                                                        maxlength="200"
                                                        data-rule-item
                                                        data-rule-position="<?php echo esc_attr((string) $rule_index); ?>"
                                                    ><?php echo esc_textarea($rule_text); ?></textarea>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <div class="juntaplay-form__group">
                                            <label for="jp-rule-extra"><?php echo esc_html__('Outras regras (opcional)', 'juntaplay'); ?></label>
                                            <textarea
                                                id="jp-rule-extra"
                                                class="juntaplay-form__input"
                                                rows="3"
                                                maxlength="500"
                                                data-rule-extra
                                                placeholder="<?php echo esc_attr__('Inclua detalhes adicionais importantes para manter o grupo seguro.', 'juntaplay'); ?>"
                                            ><?php echo esc_textarea($rule_extra); ?></textarea>
                                            <p class="juntaplay-form__help" data-rule-extra-counter></p>
                                        </div>
                                    </div>
                                </div>

                                <div class="juntaplay-form__group">
                                    <label for="jp-group-description"><?php echo esc_html__('Mensagem para os participantes', 'juntaplay');?></label>
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

                                <div class="juntaplay-form__nav">
                                    <button type="button" class="juntaplay-button juntaplay-button--ghost" data-step-prev><?php echo esc_html__('Voltar', 'juntaplay'); ?></button>
                                    <button type="button" class="juntaplay-button juntaplay-button--primary" data-step-next><?php echo esc_html__('Avançar', 'juntaplay'); ?></button>
                                </div>
                            </section>

                            <section class="juntaplay-form__step is-hidden" data-group-step data-step-index="2">
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

                                <div class="juntaplay-form__nav">
                                    <button type="button" class="juntaplay-button juntaplay-button--ghost" data-step-prev><?php echo esc_html__('Voltar', 'juntaplay'); ?></button>
                                    <button type="button" class="juntaplay-button juntaplay-button--primary" data-step-next><?php echo esc_html__('Avançar', 'juntaplay'); ?></button>
                                </div>
                            </section>

                            <section class="juntaplay-form__step is-hidden" data-group-step data-step-index="3">
                                <?php
                                $relationship_icons = [
                                    'cohabitants' => '<svg viewBox="0 0 24 24" role="presentation" focusable="false"><path fill="currentColor" d="M12 3 3 9.5V21h6.25v-4.25a.75.75 0 0 1 .75-.75h4a.75.75 0 0 1 .75.75V21H21V9.5Zm0 2.3 6.5 4.6V19h-2.75v-3.5a2.25 2.25 0 0 0-2.25-2.25h-4a2.25 2.25 0 0 0-2.25 2.25V19H4.5V9.9Z" /><path fill="currentColor" d="M12 10.5a2.25 2.25 0 1 0-2.25-2.25A2.25 2.25 0 0 0 12 10.5Z" /><path fill="currentColor" d="M12 9.4a1.15 1.15 0 1 1 1.15-1.15A1.16 1.16 0 0 1 12 9.4Z" opacity=".35" /></svg>',
                                    'family'      => '<svg viewBox="0 0 24 24" role="presentation" focusable="false"><path fill="currentColor" d="M8 11.25a2.75 2.75 0 1 0-2.75-2.75A2.76 2.76 0 0 0 8 11.25Zm8 0A2.75 2.75 0 1 0 13.25 8.5 2.76 2.76 0 0 0 16 11.25Z" /><path fill="currentColor" d="M3.5 18.5v-1c0-2.37 2.37-3.5 4.5-3.5S12.5 15.13 12.5 17.5v1ZM13.5 18.5v-1c0-1.5.83-2.4 1.93-2.94a5.75 5.75 0 0 1 5.14 0c1.1.54 1.93 1.44 1.93 2.94v1Z" /><path fill="currentColor" d="M12 17.25a2.25 2.25 0 1 0-2.25-2.25A2.25 2.25 0 0 0 12 17.25Z" opacity=".35" /></svg>',
                                    'friends'     => '<svg viewBox="0 0 24 24" role="presentation" focusable="false"><path fill="currentColor" d="M4.75 12.5a3.25 3.25 0 1 0-3.25-3.25 3.26 3.26 0 0 0 3.25 3.25Zm14.5 0a3.25 3.25 0 1 0-3.25-3.25 3.26 3.26 0 0 0 3.25 3.25Z" /><path fill="currentColor" d="M2 19.25V18c0-1.91 1.76-3.25 3.75-3.25h2.7l1.55-1.6 1.55 1.6h2.7C16.24 14.75 18 16.09 18 18v1.25Z" /><path fill="currentColor" d="M9.45 13.7 8 15.1l1.35 1.4 1.65-1.7 1.65 1.7 1.35-1.4-1.45-1.4a1.7 1.7 0 0 0-2.4 0Z" opacity=".35" /></svg>',
                                    'coworkers'   => '<svg viewBox="0 0 24 24" role="presentation" focusable="false"><path fill="currentColor" d="M8 11a2.75 2.75 0 1 0-2.75-2.75A2.76 2.76 0 0 0 8 11Zm8 0a2.75 2.75 0 1 0-2.75-2.75A2.76 2.76 0 0 0 16 11Z" /><path fill="currentColor" d="M4 19.5v-1c0-2.1 1.92-3.5 4-3.5h2.35a3.72 3.72 0 0 1 1.65.37l.94.45.94-.45a3.72 3.72 0 0 1 1.65-.37H18c2.08 0 4 1.4 4 3.5v1Z" /><path fill="currentColor" d="M12 17.5a2.25 2.25 0 1 0-2.25-2.25A2.25 2.25 0 0 0 12 17.5Z" opacity=".35" /><path fill="currentColor" d="M14.25 7.35h2.25a.5.5 0 0 0 .5-.5V4.5a1 1 0 0 0-1-1h-8a1 1 0 0 0-1 1v2.35a.5.5 0 0 0 .5.5H9.5" opacity=".35" /></svg>',
                                ];

                                $support_icons = [
                                    'email_whatsapp' => '<svg viewBox="0 0 24 24" role="presentation" focusable="false"><path fill="currentColor" d="M4 5.25A2.25 2.25 0 0 1 6.25 3h9.5A2.25 2.25 0 0 1 18 5.25v4.1L22 12l-4 2.65v3.85a1.25 1.25 0 0 1-1.84 1.1L12 17.6l-4.16 1.99A1.25 1.25 0 0 1 6 18.5v-13Z" opacity=".35" /><path fill="currentColor" d="M6 6v8.68l2.9-1.39a1.25 1.25 0 0 1 1.1 0l2 .95 2-.95a1.25 1.25 0 0 1 1.1 0L18 14.7V6Z" /><path fill="currentColor" d="M10.75 8.5h2.5a.75.75 0 0 1 0 1.5h-2.5a.75.75 0 0 1 0-1.5Z" /></svg>',
                                    'email'          => '<svg viewBox="0 0 24 24" role="presentation" focusable="false"><path fill="currentColor" d="M4.5 4h15A2.5 2.5 0 0 1 22 6.5v11A2.5 2.5 0 0 1 19.5 20h-15A2.5 2.5 0 0 1 2 17.5v-11A2.5 2.5 0 0 1 4.5 4Zm0 2 7.5 4.6L19.5 6Zm0 3.35V17h15v-7.65l-6.94 4.2a2 2 0 0 1-2.12 0Z" /></svg>',
                                    'whatsapp'       => '<svg viewBox="0 0 24 24" role="presentation" focusable="false"><path fill="currentColor" d="M12 2.75A9.25 9.25 0 0 0 4.1 17.44L3 21l3.7-1.07A9.25 9.25 0 1 0 12 2.75Zm0 2A7.25 7.25 0 0 1 17.64 15l-.33.51a1.15 1.15 0 0 1-1.52.33l-1.65-1a.9.9 0 0 0-.9 0l-.93.56a1.6 1.6 0 0 1-1.57 0 9.7 9.7 0 0 1-4.44-4.44 1.06 1.06 0 0 1 0-1.02l.56-.93a.9.9 0 0 0 0-.9l-1-1.65a1.15 1.15 0 0 1 .33-1.52l.51-.33A7.25 7.25 0 0 1 12 4.75Z" /></svg>',
                                    'support'        => '<svg viewBox="0 0 24 24" role="presentation" focusable="false"><path fill="currentColor" d="M12 3.5a8.5 8.5 0 0 0-8.5 8.5v2A3 3 0 0 0 6.5 17H8v-5.5H5.5a6.5 6.5 0 0 1 13 0H16V17h1.5A3 3 0 0 0 20.5 14v-2A8.5 8.5 0 0 0 12 3.5Zm-1 12.75h2v2h-2Z" /><path fill="currentColor" d="M15 18.5h-6v1a1.5 1.5 0 0 0 1.5 1.5h3A1.5 1.5 0 0 0 15 19.5Z" opacity=".35" /></svg>',
                                ];

                                $support_options = [
                                    'email_whatsapp' => __('E-mail e WhatsApp', 'juntaplay'),
                                    'email'          => __('E-mail', 'juntaplay'),
                                    'whatsapp'       => __('WhatsApp', 'juntaplay'),
                                    'support'        => __('Central de Suporte', 'juntaplay'),
                                ];

                                $support_key = 'email_whatsapp';
                                if ($current_support !== '') {
                                    $normalized_support = function_exists('mb_strtolower')
                                        ? mb_strtolower($current_support)
                                        : strtolower($current_support);
                                    if (strpos($normalized_support, 'whatsapp') !== false && strpos($normalized_support, 'mail') === false) {
                                        $support_key = 'whatsapp';
                                    } elseif (strpos($normalized_support, 'mail') !== false && strpos($normalized_support, 'whats') === false) {
                                        $support_key = 'email';
                                    } elseif (strpos($normalized_support, 'suporte') !== false) {
                                        $support_key = 'support';
                                    }
                                }

                                $access_options = [
                                    'login_senha' => __('Login e Senha', 'juntaplay'),
                                    'convite'     => __('Convite', 'juntaplay'),
                                    'codigo'      => __('Código de Ativação', 'juntaplay'),
                                    'cookie'      => __('Cookie', 'juntaplay'),
                                    'custom'      => __('A combinar', 'juntaplay'),
                                ];

                                $access_icons = [
                                    'login_senha' => '<svg viewBox="0 0 24 24" role="presentation" focusable="false"><path fill="currentColor" d="M6 10.5A4.5 4.5 0 0 1 10.5 6h3A4.5 4.5 0 0 1 18 10.5V12h1.25A1.75 1.75 0 0 1 21 13.75v5.5A1.75 1.75 0 0 1 19.25 21H4.75A1.75 1.75 0 0 1 3 19.25v-5.5A1.75 1.75 0 0 1 4.75 12H6Zm2 0V12h8v-1.5A2.5 2.5 0 0 0 13.5 8h-3A2.5 2.5 0 0 0 8 10.5Z" /><path fill="currentColor" d="M11.5 15.25a1.25 1.25 0 1 1-1.25 1.25 1.25 1.25 0 0 1 1.25-1.25Z" opacity=".35" /></svg>',
                                    'convite'     => '<svg viewBox="0 0 24 24" role="presentation" focusable="false"><path fill="currentColor" d="M4 5.5A2.5 2.5 0 0 1 6.5 3h11A2.5 2.5 0 0 1 20 5.5v13L12 15l-8 3.5Z" /><path fill="currentColor" d="M6.5 5 12 7.9 17.5 5Z" opacity=".35" /></svg>',
                                    'codigo'      => '<svg viewBox="0 0 24 24" role="presentation" focusable="false"><path fill="currentColor" d="M4.25 4.25h6.5v6.5h-6.5Zm9 0h6.5v6.5h-6.5Zm0 9h6.5v6.5h-6.5Zm-9 0h6.5v6.5h-6.5Z" /><path fill="currentColor" d="M9 6.75H6.75V9H9Zm0 8.25H6.75V17H9Zm8.25-8.25H15V9h2.25Zm0 8.25H15V17h2.25Z" opacity=".35" /></svg>',
                                    'cookie'      => '<svg viewBox="0 0 24 24" role="presentation" focusable="false"><path fill="currentColor" d="M12 2.75A9.25 9.25 0 1 0 21.25 12a.75.75 0 0 1-.75-.75 1.75 1.75 0 0 1-1.75-1.75.75.75 0 0 1-.75-.75 1.75 1.75 0 0 1-1.75-1.75.75.75 0 0 1-.75-.75A9.2 9.2 0 0 0 12 2.75Zm-3.25 7a1.25 1.25 0 1 1-1.25-1.25 1.25 1.25 0 0 1 1.25 1.25Zm1.25 5a1 1 0 1 1-1-1 1 1 0 0 1 1 1Zm2.75-2a1.25 1.25 0 1 1-1.25-1.25 1.25 1.25 0 0 1 1.25 1.25Zm2 2.75a1.15 1.15 0 1 1-1.15-1.15 1.16 1.16 0 0 1 1.15 1.15Z" /></svg>',
                                    'custom'      => '<svg viewBox="0 0 24 24" role="presentation" focusable="false"><path fill="currentColor" d="M5 4.5A1.5 1.5 0 0 1 6.5 3h7A1.5 1.5 0 0 1 15 4.5v1.56a4.35 4.35 0 0 1 2.06.93L18.2 5.9a1.2 1.2 0 0 1 1.7 0l.2.2a1.2 1.2 0 0 1 0 1.7l-.6.6a4.35 4.35 0 0 1 .06 3.44l.54.54a1.2 1.2 0 0 1 0 1.7l-.2.2a1.2 1.2 0 0 1-1.7 0l-.84-.84a4.35 4.35 0 0 1-2.06.93v1.76A1.5 1.5 0 0 1 13.5 21h-7A1.5 1.5 0 0 1 5 19.5Z" /><path fill="currentColor" d="M7.5 6.25h5a.75.75 0 0 1 0 1.5h-5a.75.75 0 0 1 0-1.5Zm0 10a.75.75 0 0 0 0 1.5h5a.75.75 0 0 0 0-1.5Zm0-4.5a.75.75 0 0 0 0 1.5h3.5a.75.75 0 0 0 0-1.5Z" opacity=".35" /></svg>',
                                ];

                                $access_key = 'login_senha';
                                if ($current_access !== '') {
                                    $normalized_access = function_exists('mb_strtolower')
                                        ? mb_strtolower($current_access)
                                        : strtolower($current_access);
                                    if (strpos($normalized_access, 'convite') !== false) {
                                        $access_key = 'convite';
                                    } elseif (strpos($normalized_access, 'cód') !== false || strpos($normalized_access, 'codigo') !== false) {
                                        $access_key = 'codigo';
                                    } elseif (strpos($normalized_access, 'cookie') !== false) {
                                        $access_key = 'cookie';
                                    } elseif (strpos($normalized_access, 'combinar') !== false) {
                                        $access_key = 'custom';
                                    }
                                }

                                $category_icons = [
                                    'video'     => '<svg viewBox="0 0 24 24" role="presentation" focusable="false"><path fill="currentColor" d="M5 5h10.5A2.5 2.5 0 0 1 18 7.5V9l2.4-1.5A1 1 0 0 1 22 8.4v7.2a1 1 0 0 1-1.6.8L18 14.9V16.5A2.5 2.5 0 0 1 15.5 19H5A2.5 2.5 0 0 1 2.5 16.5v-9A2.5 2.5 0 0 1 5 5Zm5 3v6l4.5-3Z" /></svg>',
                                    'music'     => '<svg viewBox="0 0 24 24" role="presentation" focusable="false"><path fill="currentColor" d="m17.5 3.5-9 2.5V16a3 3 0 1 0 1.5 2.6V9.65l6-1.67V14a3 3 0 1 0 1.5 2.6Z" /></svg>',
                                    'education' => '<svg viewBox="0 0 24 24" role="presentation" focusable="false"><path fill="currentColor" d="m12 3.25-9.75 5L12 13l6.75-3.4V14H20V9.25Zm-6.75 8V10.4L12 13.85 18.75 10.4V11.2L12 14.65Zm0 3 6.75 3.5 6.75-3.5V18L12 21.25 5.25 18Z" /></svg>',
                                    'reading'   => '<svg viewBox="0 0 24 24" role="presentation" focusable="false"><path fill="currentColor" d="M6.25 4.5H12v15H7.5A3 3 0 0 0 5 16.5v-10A2 2 0 0 1 6.25 4.5Zm11.5 0H12v15h4.5A3 3 0 0 0 19 16.5v-10A2 2 0 0 0 17.75 4.5Z" /><path fill="currentColor" d="M8.5 7h3.5v1.5H8.5Zm0 3h3.5v1.5H8.5Z" opacity=".35" /></svg>',
                                    'office'    => '<svg viewBox="0 0 24 24" role="presentation" focusable="false"><path fill="currentColor" d="M8 4h8a2 2 0 0 1 2 2h2.5A1.5 1.5 0 0 1 22 7.5v11A1.5 1.5 0 0 1 20.5 20h-17A1.5 1.5 0 0 1 2 18.5v-11A1.5 1.5 0 0 1 3.5 6H6a2 2 0 0 1 2-2Zm0 2v1h8V6Zm-2 4v6h2v-6Zm4-2v8h2v-8Zm4 3v5h2v-5Z" /></svg>',
                                    'games'     => '<svg viewBox="0 0 24 24" role="presentation" focusable="false"><path fill="currentColor" d="M6.25 8.5h11.5A3.25 3.25 0 0 1 20.9 12l-.92 3.46A3 3 0 0 1 17 18.5H7a3 3 0 0 1-2.98-3.04l-.92-3.46A3.25 3.25 0 0 1 6.25 8.5Zm-.5 3.5v1h1.5v1.5h1V13H9v-1H8.25v-1.5h-1V12Zm12 1.75a.75.75 0 1 0-.75.75.75.75 0 0 0 .75-.75Zm-2 1.5a.75.75 0 1 0-.75.75.75.75 0 0 0 .75-.75Z" /></svg>',
                                ];

                                $survey_steps = [
                                    __('Serviço', 'juntaplay'),
                                    __('Regras', 'juntaplay'),
                                    __('Valores', 'juntaplay'),
                                    __('Entrega', 'juntaplay'),
                                    __('Acesso', 'juntaplay'),
                                ];

                                $timing_icons = [
                                    'immediate' => '<svg viewBox="0 0 24 24" role="presentation" focusable="false"><path fill="currentColor" d="m12.75 2.5-8 10.75H10.7L9 21.5l9-12h-6.05Z" /></svg>',
                                    'scheduled' => '<svg viewBox="0 0 24 24" role="presentation" focusable="false"><path fill="currentColor" d="M7 3h2v2h6V3h2v2h3.25A1.75 1.75 0 0 1 22 6.75v12.5A1.75 1.75 0 0 1 20.25 21H3.75A1.75 1.75 0 0 1 2 19.25V6.75A1.75 1.75 0 0 1 3.75 5H7Zm13 6.5H4v9.25h16Zm-5 2.25v3.5h4v-2h-2.5v-1.5Z" /></svg>',
                                ];
                                ?>

                                <div class="juntaplay-questionnaire">
                                    <div class="juntaplay-questionnaire__header">
                                        <div>
                                            <h3 class="juntaplay-questionnaire__title"><?php echo esc_html__('Só mais essas perguntinhas', 'juntaplay'); ?></h3>
                                        </div>
                                        <div class="juntaplay-questionnaire__progress" aria-label="<?php echo esc_attr__('Progresso do formulário', 'juntaplay'); ?>">
                                            <?php foreach ($survey_steps as $index => $step_label) : ?>
                                                <span class="juntaplay-questionnaire__progress-dot<?php echo $index <= 3 ? ' is-active' : ''; ?>" aria-hidden="true"></span>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>

                                    <div class="juntaplay-questionnaire__grid">
                                        <div class="juntaplay-question" data-choice-group data-group-share-watch>
                                            <div class="juntaplay-question__title"><?php echo esc_html__('Qual a categoria?', 'juntaplay'); ?></div>
                                            <div class="juntaplay-choice-grid">
                                                <?php foreach ($group_categories as $category_value => $category_name) :
                                                    $is_selected = (string) $category_value === $current_category;
                                                    $card_classes = ['juntaplay-choice-card'];
                                                    if ($is_selected) {
                                                        $card_classes[] = 'is-active';
                                                    }
                                                    $icon_markup = isset($category_icons[$category_value]) ? $category_icons[$category_value] : '<svg viewBox="0 0 24 24" role="presentation" focusable="false"><path fill="currentColor" d="M4 5h16v2H4Zm0 6h16v2H4Zm0 6h16v2H4Z" /></svg>';
                                                    $input_id = 'jp-category-' . sanitize_key((string) $category_value);
                                                ?>
                                                    <label class="<?php echo esc_attr(implode(' ', $card_classes)); ?>" for="<?php echo esc_attr($input_id); ?>">
                                                        <input
                                                            type="radio"
                                                            id="<?php echo esc_attr($input_id); ?>"
                                                            name="jp_profile_group_category"
                                                            value="<?php echo esc_attr((string) $category_value); ?>"
                                                            <?php checked($is_selected); ?>
                                                            data-group-share-watch
                                                            required
                                                        />
                                                        <span class="juntaplay-choice-card__icon" aria-hidden="true"><?php echo $icon_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
                                                        <span class="juntaplay-choice-card__label"><?php echo esc_html((string) $category_name); ?></span>
                                                    </label>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>

                                        <div class="juntaplay-question" data-choice-group>
                                            <div class="juntaplay-question__title"><?php echo esc_html__('Qual é o relacionamento entre os participantes?', 'juntaplay'); ?></div>
                                            <div class="juntaplay-choice-grid juntaplay-choice-grid--compact">
                                                <?php foreach ($relationship_options as $relationship_key => $relationship_label) :
                                                    $rel_id = 'jp-relationship-' . $relationship_key;
                                                    $is_selected = (string) $relationship_key === $current_relationship;
                                                    $card_classes = ['juntaplay-choice-card'];
                                                    if ($is_selected) {
                                                        $card_classes[] = 'is-active';
                                                    }
                                                    $icon_markup = isset($relationship_icons[$relationship_key]) ? $relationship_icons[$relationship_key] : '<svg viewBox="0 0 24 24" role="presentation" focusable="false"><circle cx="12" cy="7" r="3" fill="currentColor"></circle><path fill="currentColor" d="M12 12c-3 0-9 1.5-9 4.5V21h18v-4.5C21 13.5 15 12 12 12Z" /></svg>';
                                                ?>
                                                    <label class="<?php echo esc_attr(implode(' ', $card_classes)); ?>" for="<?php echo esc_attr($rel_id); ?>">
                                                        <input
                                                            type="radio"
                                                            id="<?php echo esc_attr($rel_id); ?>"
                                                            name="jp_profile_group_relationship"
                                                            value="<?php echo esc_attr((string) $relationship_key); ?>"
                                                            <?php checked($is_selected); ?>
                                                            required
                                                        />
                                                        <span class="juntaplay-choice-card__icon" aria-hidden="true"><?php echo $icon_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
                                                        <span class="juntaplay-choice-card__label"><?php echo esc_html((string) $relationship_label); ?></span>
                                                    </label>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>

                                        <div class="juntaplay-question" data-choice-group>
                                            <div class="juntaplay-question__title"><?php echo esc_html__('Como os participantes podem falar com você?', 'juntaplay'); ?></div>
                                            <div class="juntaplay-choice-grid juntaplay-choice-grid--compact">
                                                <?php foreach ($support_options as $support_value => $support_label) :
                                                    $support_id = 'jp-support-' . $support_value;
                                                    $is_selected = $support_value === $support_key;
                                                    $card_classes = ['juntaplay-choice-card'];
                                                    if ($is_selected) {
                                                        $card_classes[] = 'is-active';
                                                    }
                                                    $icon_markup = isset($support_icons[$support_value])
                                                        ? $support_icons[$support_value]
                                                        : '<svg viewBox="0 0 24 24" role="presentation" focusable="false"><path fill="currentColor" d="M4 4h16v12H5.17L4 17.17V4Zm2 2v8h12V6Zm3 12h5v2H9Z" /></svg>';
                                                ?>
                                                    <label class="<?php echo esc_attr(implode(' ', $card_classes)); ?>" for="<?php echo esc_attr($support_id); ?>">
                                                        <input
                                                            type="radio"
                                                            id="<?php echo esc_attr($support_id); ?>"
                                                            name="jp_profile_group_support"
                                                            value="<?php echo esc_attr((string) $support_label); ?>"
                                                            <?php checked($is_selected); ?>
                                                            data-group-share-watch
                                                            required
                                                        />
                                                        <span class="juntaplay-choice-card__icon" aria-hidden="true"><?php echo $icon_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
                                                        <span class="juntaplay-choice-card__label"><?php echo esc_html((string) $support_label); ?></span>
                                                    </label>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>

                                        <div class="juntaplay-question" data-choice-group data-choice-sync="[data-access-timing]">
                                            <div class="juntaplay-question__title"><?php echo esc_html__('Quando os participantes vão receber o acesso ao serviço?', 'juntaplay'); ?></div>
                                            <div class="juntaplay-choice-grid juntaplay-choice-grid--compact">
                                                <?php
                                                $timing_options = [
                                                    'scheduled' => __('Após grupo completar', 'juntaplay'),
                                                    'immediate' => __('Imediatamente', 'juntaplay'),
                                                ];

                                                foreach ($timing_options as $timing_value => $timing_label) :
                                                    $timing_id = 'jp-access-' . $timing_value;
                                                    $is_selected = $timing_value === $current_access_timing;
                                                    $card_classes = ['juntaplay-choice-card'];
                                                    if ($is_selected) {
                                                        $card_classes[] = 'is-active';
                                                    }
                                                    $icon_markup = isset($timing_icons[$timing_value])
                                                        ? $timing_icons[$timing_value]
                                                        : '<svg viewBox="0 0 24 24" role="presentation" focusable="false"><path fill="currentColor" d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2Zm1 5h-2v4.59l3.71 3.7 1.42-1.42L13 11.17Z" /></svg>';
                                                ?>
                                                    <label class="<?php echo esc_attr(implode(' ', $card_classes)); ?>" for="<?php echo esc_attr($timing_id); ?>">
                                                        <input
                                                            type="radio"
                                                            id="<?php echo esc_attr($timing_id); ?>"
                                                            name="jp_profile_group_delivery"
                                                            value="<?php echo esc_attr($timing_value); ?>"
                                                            <?php checked($is_selected); ?>
                                                            data-access-timing-option
                                                            required
                                                        />
                                                        <span class="juntaplay-choice-card__icon" aria-hidden="true"><?php echo $icon_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
                                                        <span class="juntaplay-choice-card__label"><?php echo esc_html($timing_label); ?></span>
                                                    </label>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>

                                        <div class="juntaplay-question" data-choice-group>
                                            <div class="juntaplay-question__title"><?php echo esc_html__('Como será disponibilizado o acesso ao serviço?', 'juntaplay'); ?></div>
                                            <div class="juntaplay-choice-grid juntaplay-choice-grid--compact">
                                                <?php foreach ($access_options as $access_value => $access_label) :
                                                    $access_id = 'jp-access-method-' . $access_value;
                                                    $is_selected = $access_value === $access_key;
                                                    $card_classes = ['juntaplay-choice-card'];
                                                    if ($is_selected) {
                                                        $card_classes[] = 'is-active';
                                                    }
                                                    $icon_markup = isset($access_icons[$access_value])
                                                        ? $access_icons[$access_value]
                                                        : '<svg viewBox="0 0 24 24" role="presentation" focusable="false"><path fill="currentColor" d="M20 4H4a2 2 0 0 0-2 2v9h2V6h16v8h2V6a2 2 0 0 0-2-2Zm-1 10-4 4H9l-4-4Zm-8 4h2v3h-2Z" /></svg>';
                                                ?>
                                                    <label class="<?php echo esc_attr(implode(' ', $card_classes)); ?>" for="<?php echo esc_attr($access_id); ?>">
                                                        <input
                                                            type="radio"
                                                            id="<?php echo esc_attr($access_id); ?>"
                                                            name="jp_profile_group_access"
                                                            value="<?php echo esc_attr((string) $access_label); ?>"
                                                            <?php checked($is_selected); ?>
                                                            data-group-share-watch
                                                            required
                                                        />
                                                        <span class="juntaplay-choice-card__icon" aria-hidden="true"><?php echo $icon_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
                                                        <span class="juntaplay-choice-card__label"><?php echo esc_html((string) $access_label); ?></span>
                                                    </label>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <input
                                    type="hidden"
                                    id="jp-group-delivery"
                                    name="jp_profile_group_delivery"
                                    class="juntaplay-form__input juntaplay-form__input--hidden"
                                    data-access-timing
                                    data-group-share-watch
                                    value="<?php echo esc_attr($current_access_timing); ?>"
                                />

                                <div class="juntaplay-form__nav">
                                    <button type="button" class="juntaplay-button juntaplay-button--ghost" data-step-prev><?php echo esc_html__('Voltar', 'juntaplay'); ?></button>
                                    <button type="button" class="juntaplay-button juntaplay-button--primary" data-step-next><?php echo esc_html__('Avançar', 'juntaplay'); ?></button>
                                </div>
                            </section>

                                <section class="juntaplay-form__step is-hidden<?php echo $current_access_timing === 'immediate' ? '' : ' is-disabled'; ?>" data-group-step data-step-index="4" data-access-immediate>
                                <div class="juntaplay-stepsection">
                                    <div class="juntaplay-stepsection__header">
                                        <div>
                                            <h3 class="juntaplay-stepsection__title"><?php echo esc_html__('Dados para acesso imediato', 'juntaplay'); ?></h3>
                                            <p class="juntaplay-stepsection__subtitle"><?php echo esc_html__('Complete os campos abaixo somente quando escolher liberação imediata.', 'juntaplay'); ?></p>
                                        </div>
                                    </div>

                                    <div class="juntaplay-stepflow">
                                        <div class="juntaplay-stepflow__row">
                                            <article class="juntaplay-stepcard juntaplay-stepcard--full juntaplay-access-fields">
                                                <div class="juntaplay-stepcard__icon" aria-hidden="true">
                                                    <svg viewBox="0 0 24 24" role="presentation" focusable="false">
                                                        <path fill="currentColor" d="M11 3h2l1 5h5l-4 4 1 7-6-4-6 4 1-7-4-4h5Z" />
                                                    </svg>
                                                </div>
                                                <div class="juntaplay-stepcard__body">
                                                    <h4><?php echo esc_html__('Dados para acesso imediato', 'juntaplay'); ?></h4>
                                                    <p class="juntaplay-form__help"><?php echo esc_html__('Preencha cada item para liberar o grupo automaticamente após a aprovação.', 'juntaplay'); ?></p>
                                                    <div class="juntaplay-form__grid">
                                                        <div class="juntaplay-form__group">
                                                            <label for="jp-group-access-url"><?php echo esc_html__('URL de acesso', 'juntaplay'); ?></label>
                                                            <input
                                                                type="url"
                                                                id="jp-group-access-url"
                                                                name="jp_profile_group_access_url"
                                                                class="juntaplay-form__input"
                                                                value="<?php echo esc_attr($current_access_url); ?>"
                                                                placeholder="<?php echo esc_attr__('https://', 'juntaplay'); ?>"
                                                                data-group-pool-url
                                                            />
                                                        </div>
                                                        <div class="juntaplay-form__group">
                                                            <label for="jp-group-access-login"><?php echo esc_html__('Login ou e-mail', 'juntaplay'); ?></label>
                                                            <input
                                                                type="text"
                                                                id="jp-group-access-login"
                                                                name="jp_profile_group_access_login"
                                                                class="juntaplay-form__input"
                                                                value="<?php echo esc_attr($current_access_login); ?>"
                                                                placeholder="<?php echo esc_attr__('nome@exemplo.com', 'juntaplay'); ?>"
                                                                data-group-pool-login
                                                            />
                                                        </div>
                                                    </div>
                                                    <div class="juntaplay-form__grid">
                                                        <div class="juntaplay-form__group">
                                                            <label for="jp-group-access-password"><?php echo esc_html__('Senha ou código', 'juntaplay'); ?></label>
                                                            <input
                                                                type="text"
                                                                id="jp-group-access-password"
                                                                name="jp_profile_group_access_password"
                                                                class="juntaplay-form__input"
                                                                value="<?php echo esc_attr($current_access_password); ?>"
                                                                placeholder="<?php echo esc_attr__('••••••', 'juntaplay'); ?>"
                                                                data-group-pool-password
                                                            />
                                                        </div>
                                                    </div>
                                                </div>
                                            </article>

                                        </div>
                                    </div>
                                </div>

                                <div class="juntaplay-form__nav">
                                    <button type="button" class="juntaplay-button juntaplay-button--ghost" data-step-prev><?php echo esc_html__('Voltar', 'juntaplay'); ?></button>
                                    <button type="submit" class="juntaplay-button juntaplay-button--primary"><?php echo esc_html__('Criar um Grupo Agora', 'juntaplay'); ?></button>
                                </div>
                            </section>
                        </form
                    </div>
                </div>
            </div>
    </template>

    <div class="juntaplay-modal" data-group-create-modal hidden aria-hidden="true">
        <div class="juntaplay-modal__overlay" data-modal-close></div>
        <div class="juntaplay-modal__dialog" role="dialog" aria-modal="true">
            <button type="button" class="juntaplay-modal__close" data-modal-close aria-label="<?php echo esc_attr__('Fechar', 'juntaplay'); ?>">
                <span aria-hidden="true">&times;</span>
            </button>
            <div class="juntaplay-modal__content" data-modal-content></div>
        </div>
    </div>

    <template id="jp-group-success-template">
        <div class="juntaplay-group-success" role="document">
            <figure class="juntaplay-group-success__media" data-group-success-media>
                <img src="" alt="" loading="lazy" data-group-success-image />
            </figure>
            <h2 class="juntaplay-group-success__title" data-group-success-heading></h2>
            <p class="juntaplay-group-success__body" data-group-success-body></p>
            <div class="juntaplay-group-success__actions">
                <div class="juntaplay-group-success__cta" data-group-success-cta></div>
            </div>
        </div>
    </template>

    <div class="juntaplay-modal juntaplay-modal--success" data-group-success-modal hidden aria-hidden="true">
        <div class="juntaplay-modal__overlay" data-modal-close></div>
        <div class="juntaplay-modal__dialog" role="dialog" aria-modal="true">
            <button type="button" class="juntaplay-modal__close" data-modal-close aria-label="<?php echo esc_attr__('Fechar', 'juntaplay'); ?>">
                <span aria-hidden="true">&times;</span>
            </button>
            <div class="juntaplay-modal__content" data-modal-content></div>
        </div>
    </div>


</div>
