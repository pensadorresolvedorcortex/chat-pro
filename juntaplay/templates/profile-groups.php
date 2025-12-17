<?php
/**
 * JuntaPlay profile groups hub template.
 */

declare(strict_types=1);

use JuntaPlay\Assets\ServiceIcons;
use JuntaPlay\Data\Groups;

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

$success_redirect_default = 'https://www.juntaplay.com.br/grupos';
$success_redirect = isset($create_success['redirect']) ? (string) $create_success['redirect'] : $success_redirect_default;
if ($success_redirect === '') {
    $success_redirect = $success_redirect_default;
}

$current_user_id = get_current_user_id();

$pool_choices_trim = array_slice($pool_choices, 0, 6, true);
if (!$pool_featured && $pool_catalog) {
    $pool_featured = array_slice($pool_catalog, 0, 6);
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
$current_icon_id     = isset($form_values['icon']) ? (int) $form_values['icon'] : 0;
$icon_placeholder    = defined('JP_URL') && JP_URL !== ''
    ? trailingslashit(JP_URL) . 'assets/img/services/default.svg'
    : plugins_url('assets/img/services/default.svg', JP_FILE);
$current_icon_url    = $current_icon_id > 0 ? (string) wp_get_attachment_image_url($current_icon_id, 'thumbnail') : '';
$current_relationship = isset($form_values['relationship']) ? (string) $form_values['relationship'] : '';
$relationship_options = \JuntaPlay\Data\Groups::get_relationship_options();
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

$all_groups_indexed = [];
foreach ([$groups_owned, $groups_member] as $group_collection) {
    foreach ($group_collection as $group_entry) {
        if (!is_array($group_entry)) {
            continue;
        }

        $candidate_id = isset($group_entry['id']) ? (int) $group_entry['id'] : 0;

        if ($candidate_id === 0 && isset($group_entry['group_id'])) {
            $candidate_id = (int) $group_entry['group_id'];
        }

        if ($candidate_id === 0) {
            $all_groups_indexed[] = $group_entry;

            continue;
        }

        if (!isset($all_groups_indexed[$candidate_id])) {
            $all_groups_indexed[$candidate_id] = $group_entry;
        }
    }
}

$all_groups = array_values($all_groups_indexed);
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
$profile_page_id   = (int) get_option('juntaplay_page_perfil');
$messages_base_url   = $profile_page_id ? get_permalink($profile_page_id) : home_url('/perfil/');
$messages_endpoint   = user_trailingslashit(trailingslashit($messages_base_url) . 'juntaplay-chat');
$messages_base_url   = add_query_arg('section', 'juntaplay-chat', $messages_base_url);

$active_section  = isset($_GET['section']) ? (string) wp_unslash($_GET['section']) : '';
if ($active_section === '' && get_query_var('juntaplay-chat', '') !== '') {
    $active_section = 'juntaplay-chat';
}
$is_chat_section = $active_section === 'juntaplay-chat';

$messages_panel_classes = ['juntaplay-profile__category-panel'];
if ($is_chat_section) {
    $messages_panel_classes[] = 'is-active';
}
$messages_panel_class = implode(' ', array_map('sanitize_html_class', $messages_panel_classes));

$messages_tab_classes = ['juntaplay-profile__tab-panel'];
if ($is_chat_section) {
    $messages_tab_classes[] = 'is-active';
}
$messages_tab_class = implode(' ', array_map('sanitize_html_class', $messages_tab_classes));

$groups_container_classes = ['juntaplay-groups', 'juntaplay-profile__panels', 'juntaplay-profile__panels--groups'];
$is_profile_groups_view = !empty($group_context['is_profile']) || ($profile_page_id && is_page($profile_page_id));
if ($is_profile_groups_view) {
    $groups_container_classes[] = 'juntaplay-my-groups-grid';
}
$groups_container_class = implode(' ', array_map('sanitize_html_class', $groups_container_classes));

$current_user        = wp_get_current_user();
$current_user_name   = $current_user instanceof WP_User ? (string) $current_user->display_name : '';
$current_user_avatar = $current_user instanceof WP_User ? (string) get_avatar_url($current_user->ID, ['size' => 96]) : '';

$chat_placeholder = __('Digite sua mensagem...', 'juntaplay');
$chat_groups      = [];
$chat_member_groups = [];
$chat_subscribers   = [];
$chat_owner_cache   = [];

foreach ($groups_owned as $group) {
    if (!is_array($group)) {
        continue;
    }

    $owner_id = isset($group['owner_id']) ? (int) $group['owner_id'] : $current_user_id;
    $owner_name = '';
    $owner_avatar = '';

    if ($owner_id > 0) {
        if (!isset($chat_owner_cache[$owner_id])) {
            $owner_user = get_userdata($owner_id);
            $chat_owner_cache[$owner_id] = [
                'name'   => $owner_user instanceof WP_User ? (string) $owner_user->display_name : '',
                'avatar' => $owner_user instanceof WP_User ? (string) get_avatar_url($owner_id, ['size' => 96]) : '',
            ];
        }

        $owner_name   = $chat_owner_cache[$owner_id]['name'];
        $owner_avatar = $chat_owner_cache[$owner_id]['avatar'];
    }

    $chat_groups[] = [
        'id'       => isset($group['id']) ? (int) $group['id'] : 0,
        'title'    => isset($group['title']) ? (string) $group['title'] : __('Grupo', 'juntaplay'),
        'subtitle' => isset($group['members_count']) ? sprintf(_n('%d assinante', '%d assinantes', (int) $group['members_count'], 'juntaplay'), (int) $group['members_count']) : '',
        'avatar'   => isset($group['icon_url']) ? (string) $group['icon_url'] : '',
        'owner_id' => $owner_id,
        'owner_name' => $owner_name,
        'owner_avatar' => $owner_avatar,
        'is_admin' => true,
    ];
}

// Fallback: load groups directly from the core JuntaPlay data layer to keep selectors populated.
if (!$chat_groups && !$chat_member_groups && class_exists(Groups::class)) {
    $fallback_groups = Groups::get_groups_for_user($current_user_id);

    $fallback_owned  = isset($fallback_groups['owned']) && is_array($fallback_groups['owned']) ? $fallback_groups['owned'] : [];
    $fallback_member = isset($fallback_groups['member']) && is_array($fallback_groups['member']) ? $fallback_groups['member'] : [];

    foreach ($fallback_owned as $group) {
        if (!is_array($group)) {
            continue;
        }

        $owner_id = isset($group['owner_id']) ? (int) $group['owner_id'] : 0;
        $owner_name = '';
        $owner_avatar = '';

        if ($owner_id > 0) {
            if (!isset($chat_owner_cache[$owner_id])) {
                $owner_user = get_userdata($owner_id);
                $chat_owner_cache[$owner_id] = [
                    'name'   => $owner_user instanceof WP_User ? (string) $owner_user->display_name : '',
                    'avatar' => $owner_user instanceof WP_User ? (string) get_avatar_url($owner_id, ['size' => 96]) : '',
                ];
            }

            $owner_name   = $chat_owner_cache[$owner_id]['name'];
            $owner_avatar = $chat_owner_cache[$owner_id]['avatar'];
        }

        $chat_groups[] = [
            'id'       => isset($group['id']) ? (int) $group['id'] : 0,
            'title'    => isset($group['title']) ? (string) $group['title'] : __('Grupo', 'juntaplay'),
            'subtitle' => isset($group['pool_title']) ? (string) $group['pool_title'] : '',
            'avatar'   => isset($group['icon_url']) ? (string) $group['icon_url'] : '',
            'owner_id' => $owner_id,
            'owner_name' => $owner_name,
            'owner_avatar' => $owner_avatar,
            'is_admin' => true,
        ];
    }

    foreach ($fallback_member as $group) {
        if (!is_array($group)) {
            continue;
        }

        $owner_id = isset($group['owner_id']) ? (int) $group['owner_id'] : 0;
        $owner_name = '';
        $owner_avatar = '';

        if ($owner_id > 0) {
            if (!isset($chat_owner_cache[$owner_id])) {
                $owner_user = get_userdata($owner_id);
                $chat_owner_cache[$owner_id] = [
                    'name'   => $owner_user instanceof WP_User ? (string) $owner_user->display_name : '',
                    'avatar' => $owner_user instanceof WP_User ? (string) get_avatar_url($owner_id, ['size' => 96]) : '',
                ];
            }

            $owner_name   = $chat_owner_cache[$owner_id]['name'];
            $owner_avatar = $chat_owner_cache[$owner_id]['avatar'];
        }

        $chat_member_groups[] = [
            'id'        => isset($group['id']) ? (int) $group['id'] : 0,
            'title'     => isset($group['title']) ? (string) $group['title'] : __('Grupo', 'juntaplay'),
            'subtitle'  => isset($group['pool_title']) ? (string) $group['pool_title'] : '',
            'avatar'    => isset($group['icon_url']) ? (string) $group['icon_url'] : '',
            'owner_id'  => $owner_id,
            'owner_name' => $owner_name,
            'owner_avatar' => $owner_avatar,
            'is_admin'  => false,
        ];
    }
}

foreach ($groups_member as $group) {
    if (!is_array($group)) {
        continue;
    }

    $owner_id = isset($group['owner_id']) ? (int) $group['owner_id'] : 0;
    $owner_name = '';
    $owner_avatar = '';

    if ($owner_id > 0) {
        if (!isset($chat_owner_cache[$owner_id])) {
            $owner_user = get_userdata($owner_id);
            $chat_owner_cache[$owner_id] = [
                'name'   => $owner_user instanceof WP_User ? (string) $owner_user->display_name : '',
                'avatar' => $owner_user instanceof WP_User ? (string) get_avatar_url($owner_id, ['size' => 96]) : '',
            ];
        }

        $owner_name   = $chat_owner_cache[$owner_id]['name'];
        $owner_avatar = $chat_owner_cache[$owner_id]['avatar'];
    }

    $chat_member_groups[] = [
        'id'        => isset($group['id']) ? (int) $group['id'] : 0,
        'title'     => isset($group['title']) ? (string) $group['title'] : __('Grupo', 'juntaplay'),
        'subtitle'  => isset($group['pool_title']) ? (string) $group['pool_title'] : '',
        'avatar'    => isset($group['icon_url']) ? (string) $group['icon_url'] : '',
        'owner_id'  => $owner_id,
        'owner_name' => $owner_name,
        'owner_avatar' => $owner_avatar,
        'is_admin'  => false,
    ];
}

$initial_group_id      = isset($_GET['group_id']) ? (int) wp_unslash($_GET['group_id']) : 0;
$initial_admin_id      = isset($_GET['admin_id']) ? (int) wp_unslash($_GET['admin_id']) : 0;
$initial_sender_id     = isset($_GET['sender_id']) ? (int) wp_unslash($_GET['sender_id']) : 0;
$initial_subscriber_id = isset($_GET['subscriber_id']) ? (int) wp_unslash($_GET['subscriber_id']) : 0;
$initial_user_id_param = isset($_GET['user_id']) ? (int) wp_unslash($_GET['user_id']) : 0;
$initial_thread_id     = isset($_GET['thread_id']) ? (int) wp_unslash($_GET['thread_id']) : 0;

$owns_initial_group   = false;
$member_initial_group = false;

if ($initial_group_id > 0) {
    foreach ($chat_groups as $owned_group) {
        if (isset($owned_group['id']) && (int) $owned_group['id'] === $initial_group_id) {
            $owns_initial_group = true;

            break;
        }
    }

    foreach ($chat_member_groups as $member_group) {
        if (isset($member_group['id']) && (int) $member_group['id'] === $initial_group_id) {
            $member_initial_group = true;

            break;
        }
    }
}

$initial_recipient_is_self = $initial_subscriber_id === $current_user_id && $initial_admin_id > 0;
$force_subscriber_mode = $initial_recipient_is_self
    || ($member_initial_group && $initial_admin_id !== $current_user_id)
    || ($member_initial_group && !$initial_admin_id && !$owns_initial_group)
    || ($initial_group_id === 0 && $initial_admin_id === 0 && $initial_subscriber_id === 0 && !empty($chat_member_groups));

if ($initial_admin_id === 0 && $initial_sender_id > 0) {
    $initial_admin_id = $initial_sender_id;
}

$chat_is_admin = false;
$chat_admin_id = 0;
$chat_subscriber_id = 0;
$chat_admin_name = $current_user_name !== '' ? $current_user_name : __('Administrador', 'juntaplay');
$chat_admin_avatar = $current_user_avatar;
$chat_recipient_name = __('Assinante', 'juntaplay');
$chat_recipient_role = __('Assinante', 'juntaplay');
$chat_recipient_group = __('Grupo', 'juntaplay');
$chat_recipient_avatar = '';

// Determine context for admin users (owners/managers).
if ($chat_groups && !$force_subscriber_mode) {
    $chat_is_admin = true;
    $chat_admin_id = $current_user_id;

    if ($initial_group_id > 0) {
        $initial_subscribers = juntaplay_chat_group_subscribers($initial_group_id, $current_user_id);

        if ($initial_subscriber_id === 0 && $initial_user_id_param > 0) {
            $initial_subscriber_id = $initial_user_id_param;
        }

        if ($initial_subscriber_id === 0 && $initial_subscribers && count($initial_subscribers) === 1) {
            $initial_subscriber_id = (int) $initial_subscribers[0]['id'];
        }

        if ($initial_subscriber_id > 0) {
            $chat_subscriber_id = $initial_subscriber_id;
            $recipient_user     = get_userdata($chat_subscriber_id);
            if ($recipient_user) {
                $chat_recipient_name = (string) $recipient_user->display_name;
                $chat_recipient_avatar = (string) get_avatar_url($chat_subscriber_id, ['size' => 96]);
            }
        }
    }
}

if ($chat_is_admin && $chat_subscriber_id === 0 && $initial_subscriber_id > 0) {
    $chat_subscriber_id = $initial_subscriber_id;
    $recipient_user     = get_userdata($chat_subscriber_id);
    if ($recipient_user) {
        $chat_recipient_name   = (string) $recipient_user->display_name;
        $chat_recipient_avatar = (string) get_avatar_url($chat_subscriber_id, ['size' => 96]);
    }
}

if (!$chat_is_admin) {
    $chat_recipient_name = $current_user_name !== '' ? $current_user_name : $chat_recipient_name;
    $chat_recipient_avatar = $current_user_avatar !== '' ? $current_user_avatar : $chat_recipient_avatar;
}

// Determine context for subscribers.
if (!$chat_is_admin && $chat_member_groups) {
    $member_group = null;
    foreach ($chat_member_groups as $group) {
        if ($initial_group_id > 0 && $group['id'] === $initial_group_id) {
            $member_group = $group;
            break;
        }
        if ($member_group === null) {
            $member_group = $group;
        }
    }

    if ($member_group !== null) {
        if ($initial_group_id === 0 && isset($member_group['id'])) {
            $initial_group_id = (int) $member_group['id'];
        }
        $chat_admin_id = $member_group['owner_id'];
        $chat_subscriber_id = $current_user_id;
        $chat_recipient_name = $current_user_name !== '' ? $current_user_name : __('Assinante', 'juntaplay');
        $chat_recipient_avatar = $current_user_avatar;
        $chat_recipient_group = $member_group['title'];
        if ($initial_admin_id > 0) {
            $chat_admin_id = $initial_admin_id;
        }
        if ($chat_admin_id > 0) {
            $admin_user = get_userdata($chat_admin_id);
            if ($admin_user) {
                $chat_admin_name   = (string) $admin_user->display_name;
                $chat_admin_avatar = (string) get_avatar_url($chat_admin_id, ['size' => 96]);
            }
        } elseif ($member_group['owner_name'] !== '' || $member_group['owner_avatar'] !== '') {
            $chat_admin_name   = $member_group['owner_name'];
            $chat_admin_avatar = $member_group['owner_avatar'];
        }
    }
}

$initial_group_name = '';
if ($initial_group_id > 0) {
    foreach (array_merge($chat_groups, $chat_member_groups) as $maybe_group) {
        if (isset($maybe_group['id']) && (int) $maybe_group['id'] === $initial_group_id) {
            $initial_group_name = isset($maybe_group['title']) ? (string) $maybe_group['title'] : '';
            break;
        }
    }
}

$chat_notifications_proxy_id = 'jp-chat-notifications-' . wp_unique_id();

?>
<div id="<?php echo esc_attr($chat_notifications_proxy_id); ?>" data-chat-notifications hidden aria-hidden="true"></div>
<?php

$chat_config = [
    'rest' => [
        'send'     => rest_url('juntaplay/v1/chat/send'),
        'messages' => rest_url('juntaplay/v1/chat/messages'),
        'context'  => rest_url('juntaplay/v1/chat/context'),
    ],
    'nonce'            => wp_create_nonce('wp_rest'),
    'messagesBase'     => $messages_base_url,
    'currentUserId'    => $current_user_id,
    'isAdmin'          => $chat_is_admin,
    'groups'           => $chat_groups,
    'memberGroups'     => $chat_member_groups,
    'initialGroupId'   => $initial_group_id,
    'initialGroupName' => $initial_group_name,
    'initialAdminId'   => $initial_admin_id,
    'initialThreadId'  => $initial_thread_id,
    'userName'         => $current_user_name,
    'adminName'        => $chat_admin_name,
    'adminAvatar'      => $chat_admin_avatar,
    'recipientName'    => $chat_recipient_name,
    'recipientAvatar'  => $chat_recipient_avatar,
    'adminId'          => $chat_admin_id,
    'subscriberId'     => $chat_subscriber_id,
];

if ($group_suggestions) {
    $group_suggestions = array_values($group_suggestions);
    shuffle($group_suggestions);
    if (count($group_suggestions) > 5) {
        $group_suggestions = array_slice($group_suggestions, 0, 5);
    }
}
?>
<?php if (!$is_chat_section) : ?>
    <script>
        (() => {
            if (document.querySelector('[data-juntaplay-chat]')) return;

            const proxy = document.getElementById('<?php echo esc_js($chat_notifications_proxy_id); ?>');
            if (!proxy) return;

            const contextUrl = '<?php echo esc_js($chat_config['rest']['context']); ?>';
            if (!contextUrl) return;

            const messagesBase = '<?php echo esc_js($messages_base_url); ?>';
            const nonce = '<?php echo esc_js($chat_config['nonce']); ?>';

            const emit = (threads = []) => {
                const items = threads.map((threadItem) => {
                    const name = threadItem.admin_name || threadItem.owner_name || threadItem.sender_name || '';
                    const avatar = threadItem.admin_avatar || threadItem.owner_avatar || threadItem.sender_avatar || '';
                    const preview = threadItem.last_message || '';
                    const timestamp = threadItem.updated_at || threadItem.last_message_at || threadItem.created_at || '';
                    const label = name
                        ? `${name} <?php echo esc_js(__('te enviou uma mensagem.', 'juntaplay')); ?>`
                        : '<?php echo esc_js(__('Nova mensagem no chat', 'juntaplay')); ?>';

                    const search = new URLSearchParams();
                    search.set('section', 'juntaplay-chat');
                    if (threadItem.group_id) search.set('group_id', threadItem.group_id);
                    if (threadItem.admin_id) search.set('admin_id', threadItem.admin_id);
                    if (threadItem.thread_id) search.set('thread_id', threadItem.thread_id);
                    if (threadItem.subscriber_id) search.set('subscriber_id', threadItem.subscriber_id);

                    return {
                        name,
                        avatar,
                        preview,
                        label,
                        timestamp,
                        url: `${messagesBase}?${search.toString()}`,
                    };
                });

                proxy.dataset.items = JSON.stringify(items);
                window.juntaplayChatNotifications = items;
                window.dispatchEvent(new CustomEvent('juntaplay:chatNotifications', { detail: { items } }));
            };

            fetch(contextUrl, {
                headers: { 'X-WP-Nonce': nonce },
                credentials: 'same-origin',
            })
                .then((response) => (response.ok ? response.json() : null))
                .then((data) => {
                    if (!data) return;

                    const groups = Array.isArray(data.groups) ? data.groups : Array.isArray(data.data?.groups) ? data.data.groups : [];
                    const threads = Array.isArray(data.threads)
                        ? data.threads
                        : Array.isArray(data.data?.threads)
                        ? data.data.threads
                        : [];

                    const sources = groups.length ? groups : threads;
                    if (!sources.length) return;

                    emit(sources);
                })
                .catch(() => {});
        })();
    </script>
<?php endif; ?>
<?php if ($is_chat_section) : ?>
    <div class="juntaplay-chat-placeholder" aria-live="polite">
        <h2><?php esc_html_e('Central de Mensagens', 'juntaplay'); ?></h2>
        <p><?php esc_html_e('Selecione um grupo ou assinante para começar a conversar.', 'juntaplay'); ?></p>
    </div>
<?php endif; ?>
<style>
    .juntaplay-chat-shell__header {
        display: flex;
        flex-direction: column;
        gap: 6px;
        padding: 12px 12px 0;
    }

    .juntaplay-chat-shell__eyebrow {
        font-size: 14px;
        font-weight: 700;
        color: #0f172a;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .juntaplay-chat-shell__subtitle {
        font-size: 13px;
        color: #6b7280;
        margin: 0;
    }

    .juntaplay-chat-surface {
        display: grid;
        gap: 24px;
        grid-template-columns: 360px 1fr;
        align-items: stretch;
        background: linear-gradient(145deg, rgba(255, 255, 255, 0.92), rgba(255, 255, 255, 0.82));
        border-radius: 28px;
        padding: 24px;
        box-shadow: 0 24px 60px rgba(15, 23, 42, 0.12);
        backdrop-filter: blur(20px);
    }

    .juntaplay-chat-shell {
        display: flex;
        flex-direction: column;
        gap: 16px;
    }

    .juntaplay-chat-panel {
        display: flex;
        flex-direction: column;
        gap: 16px;
    }

    .juntaplay-chat-section__title {
        margin: 0 0 6px;
        font-size: 0.85rem;
        font-weight: 800;
        color: #0f172a;
        letter-spacing: 0.2px;
        text-transform: uppercase;
    }

    .juntaplay-chat-panel__section {
        margin-bottom: 16px;
    }

    .juntaplay-chat-panel__section.is-muted {
        opacity: 0.65;
    }

    .juntaplay-chat-card-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
        margin: 0;
        padding: 0;
        list-style: none;
    }

    .juntaplay-chat-card {
        display: flex;
        align-items: center;
        gap: 16px;
        padding: 14px 16px;
        background: rgba(255, 255, 255, 0.8);
        border-radius: 24px;
        backdrop-filter: blur(18px);
        border: 1px solid rgba(255, 255, 255, 0.6);
        box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
        transition: transform 120ms ease, box-shadow 120ms ease;
        cursor: pointer;
    }

    .juntaplay-chat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 16px 40px rgba(15, 23, 42, 0.12);
    }

    .juntaplay-chat-card.is-active {
        box-shadow: 0 18px 44px rgba(14, 165, 233, 0.14);
        border-color: rgba(14, 165, 233, 0.35);
    }

    .juntaplay-chat-card__avatar {
        width: 72px;
        height: 72px;
        border-radius: 50%;
        overflow: hidden;
        background: linear-gradient(135deg, #0ea5e9, #6366f1);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-weight: 700;
        font-size: 20px;
    }

    .juntaplay-chat-card__avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .juntaplay-chat-card__meta {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .juntaplay-chat-card__name {
        font-size: 20px;
        font-weight: 700;
        color: #0f172a;
        margin: 0;
    }

    .juntaplay-chat-card__row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
    }

    .juntaplay-chat-card__time {
        font-size: 12px;
        color: #6b7280;
    }

    .juntaplay-chat-card__preview {
        font-size: 13px;
        color: #4b5563;
        display: block;
        margin-top: 2px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .juntaplay-chat-card__badge {
        min-width: 22px;
        height: 22px;
        padding: 0 6px;
        border-radius: 999px;
        background: linear-gradient(135deg, rgba(99, 102, 241, 0.2), rgba(59, 130, 246, 0.22));
        color: #0b1220;
        font-weight: 700;
        font-size: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px solid rgba(59, 130, 246, 0.25);
        box-shadow: 0 10px 24px rgba(59, 130, 246, 0.18);
    }

    .juntaplay-chat-card__group {
        font-size: 14px;
        color: #64748b;
        margin: 0;
    }

    .juntaplay-chat-window {
        display: flex;
        flex-direction: column;
        gap: 16px;
        background: rgba(255, 255, 255, 0.85);
        border-radius: 28px;
        padding: 20px;
        backdrop-filter: blur(18px);
        border: 1px solid rgba(255, 255, 255, 0.7);
        box-shadow: 0 20px 40px rgba(15, 23, 42, 0.12);
    }

    .juntaplay-chat-window__header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
    }

    .juntaplay-chat-window__participants {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .juntaplay-chat-window__avatars {
        display: flex;
        align-items: center;
    }

    .juntaplay-chat-window__avatar {
        width: 52px;
        height: 52px;
        border-radius: 50%;
        overflow: hidden;
        border: 3px solid #e0f2fe;
        background: linear-gradient(135deg, #0ea5e9, #6366f1);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-weight: 700;
        font-size: 16px;
        box-shadow: 0 10px 24px rgba(14, 165, 233, 0.25);
    }

    .juntaplay-chat-window__avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .juntaplay-chat-window__avatar + .juntaplay-chat-window__avatar {
        margin-left: -16px;
    }

    .juntaplay-chat-window__meta h3 {
        margin: 0;
        font-size: 18px;
        font-weight: 700;
        color: #0f172a;
    }

    .juntaplay-chat-window__meta p {
        margin: 0;
        font-size: 14px;
        color: #475569;
    }

    .juntaplay-chat-window__messages {
        background: #ffffff;
        border-radius: 20px;
        border: 1px solid #e2e8f0;
        min-height: 420px;
        padding: 20px;
        box-shadow: inset 0 1px 0 rgba(15, 23, 42, 0.05);
    }

    .juntaplay-chat-window__composer {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px;
        background: rgba(255, 255, 255, 0.9);
        border-radius: 999px;
        border: 1px solid rgba(226, 232, 240, 0.8);
        box-shadow: 0 12px 24px rgba(15, 23, 42, 0.08);
    }

    .juntaplay-chat-window__input {
        flex: 1;
        border: none;
        background: transparent;
        padding: 10px 14px;
        font-size: 16px;
        outline: none;
        color: #0f172a;
    }

    .juntaplay-chat-window__action {
        border: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        padding: 10px 16px;
        border-radius: 999px;
        cursor: pointer;
        background: linear-gradient(135deg, #0ea5e9, #6366f1);
        color: #fff;
        font-weight: 700;
        transition: transform 120ms ease, box-shadow 120ms ease;
        box-shadow: 0 12px 24px rgba(99, 102, 241, 0.2);
    }

    .juntaplay-chat-window__action:hover {
        transform: translateY(-1px);
        box-shadow: 0 16px 32px rgba(99, 102, 241, 0.3);
    }

    .juntaplay-chat-window__upload {
        border: none;
        width: 44px;
        height: 44px;
        border-radius: 50%;
        background: rgba(226, 232, 240, 0.7);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #0f172a;
        cursor: pointer;
        transition: background 120ms ease, transform 120ms ease;
    }

    .juntaplay-chat-window__upload:hover {
        background: rgba(226, 232, 240, 1);
        transform: translateY(-1px);
    }

    .juntaplay-chat-window__upload svg,
    .juntaplay-chat-window__action svg {
        width: 18px;
        height: 18px;
    }

    .juntaplay-my-groups-grid .juntaplay-service-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(170px, 1fr));
        gap: 8px;
    }

    @media (min-width: 992px) {
        .juntaplay-my-groups-grid .juntaplay-service-grid {
            grid-template-columns: repeat(auto-fill, minmax(170px, 1fr));
        }

        /* Force four cards per row on the my-groups shortcode */
        .juntaplay-my-groups-grid .juntaplay-groups__list.juntaplay-service-grid {
            grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
            grid-auto-columns: minmax(0, 1fr) !important;
        }
    }

    .juntaplay-my-groups-grid .juntaplay-service-card--group {
        padding: 12px 12px 10px;
        min-height: auto;
    }

    .juntaplay-my-groups-grid .juntaplay-service-card__link {
        gap: 6px;
    }

    .juntaplay-my-groups-grid .juntaplay-service-card__title {
        font-size: 0.96rem;
    }

    .juntaplay-my-groups-grid .juntaplay-service-card__meta {
        gap: 4px;
    }

    .juntaplay-my-groups-grid .juntaplay-group-card__footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 6px;
        flex-wrap: wrap;
    }

    .juntaplay-my-groups-grid .juntaplay-group-card__cta-inline {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        flex-wrap: wrap;
    }

    .juntaplay-my-groups-grid .juntaplay-group-card__cta-pill {
        min-height: auto;
        padding: 0.18rem 0.5rem;
        border-radius: 11px;
        font-size: 0.72rem;
        line-height: 1.2;
        background: linear-gradient(150deg, rgba(255, 255, 255, 0.18), rgba(255, 255, 255, 0.08));
        border: 1px solid rgba(255, 255, 255, 0.22);
        box-shadow: 0 8px 18px rgba(15, 23, 42, 0.08);
        backdrop-filter: blur(10px);
    }

    .juntaplay-my-groups-grid .juntaplay-group-card__actions {
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .juntaplay-my-groups-grid .juntaplay-group-card__toggle {
        padding: 0.32rem 0.65rem;
        font-size: 0.82rem;
        font-weight: 700;
        border-radius: 12px;
    }

    .juntaplay-my-groups-grid .juntaplay-group-card__edit,
    .juntaplay-my-groups-grid .juntaplay-group-card__edit.juntaplay-group-card__edit--inline {
        padding: 0.3rem 0.5rem;
        font-size: 0.8rem;
        border-radius: 10px;
    }

    .juntaplay-my-groups-grid .jp-role-ribbon {
        display: inline-flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        padding: 0.5rem 0.35rem;
        min-height: 74px;
        min-width: 34px;
        border-radius: 12px;
        background: linear-gradient(180deg, rgba(255, 255, 255, 0.22), rgba(255, 255, 255, 0.06));
        box-shadow: 0 12px 26px rgba(15, 23, 42, 0.16);
        border: 1px solid rgba(255, 255, 255, 0.3);
        color: #fff;
        font-weight: 800;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        writing-mode: vertical-rl;
        text-orientation: mixed;
        backdrop-filter: blur(12px);
    }

    .juntaplay-my-groups-grid .jp-role-ribbon--admin {
        background-image: linear-gradient(195deg, #7c3aed 0%, #6366f1 45%, #22d3ee 100%);
    }

    .juntaplay-my-groups-grid .jp-role-ribbon--subscriber {
        background-image: linear-gradient(195deg, #38bdf8 0%, #60a5fa 50%, #c084fc 100%);
    }

    @media (max-width: 1024px) {
        .juntaplay-chat-surface {
            grid-template-columns: 1fr;
        }
    }
</style>

<section
    id="juntaplay-profile-category-messages"
    class="<?php echo esc_attr($messages_panel_class); ?>"
    data-profile-category-panel="messages"
    aria-hidden="<?php echo $is_chat_section ? 'false' : 'true'; ?>"<?php echo $is_chat_section ? '' : ' hidden'; ?>
>
    <div
        id="juntaplay-profile-tab-messages_center"
        class="<?php echo esc_attr($messages_tab_class); ?>"
        data-profile-tab-panel="messages_center"
        aria-hidden="<?php echo $is_chat_section ? 'false' : 'true'; ?>"
    >
        <header class="juntaplay-profile__tab-heading">
            <div>
                <h2><?php esc_html_e('Central de Mensagens', 'juntaplay'); ?></h2>
                <p><?php esc_html_e('Área destinada à comunicação entre Administrador e Assinantes.', 'juntaplay'); ?></p>
            </div>
        </header>

        <section class="juntaplay-profile__group" data-group="messages">
            <header class="juntaplay-profile__group-header">
                <h2 class="juntaplay-profile__group-title"><?php esc_html_e('Central de Mensagens', 'juntaplay'); ?></h2>
                <p class="juntaplay-profile__group-description"><?php esc_html_e('Área destinada à comunicação entre Administrador e Assinantes.', 'juntaplay'); ?></p>
            </header>

            <ul class="juntaplay-profile__list" role="list">
                <li class="juntaplay-profile__row juntaplay-profile__row--custom" data-section="juntaplay-chat">
                    <div class="juntaplay-profile__content">
                        <div class="juntaplay-profile__label"><?php esc_html_e('Central de Mensagens', 'juntaplay'); ?></div>
                    </div>
                    <div class="juntaplay-profile__custom">
                        <div class="juntaplay-card">
                            <div class="juntaplay-chat-shell__header">
                                <span class="juntaplay-chat-shell__eyebrow">
                                    <?php echo $chat_is_admin
                                        ? esc_html__('Fale com Assinantes', 'juntaplay')
                                        : esc_html__('Mensagens do chat', 'juntaplay'); ?>
                                </span>
                                <p class="juntaplay-chat-shell__subtitle">
                                    <?php echo $chat_is_admin
                                        ? esc_html__('Selecione um assinante para iniciar o chat.', 'juntaplay')
                                        : esc_html__('Veja e responda as mensagens recebidas.', 'juntaplay'); ?>
                                </p>
                            </div>
                            <div
                                class="juntaplay-chat-shell"
                                data-juntaplay-chat
                                data-chat-config="<?php echo esc_attr(wp_json_encode($chat_config)); ?>"
                                data-chat-notifications-proxy="<?php echo esc_attr($chat_notifications_proxy_id); ?>"
                            >
                                <div class="juntaplay-chat-surface">
                                    <aside class="juntaplay-chat-panel">
                                        <div class="juntaplay-chat-panel__section" data-chat-panel="threads">
                                            <h3 class="juntaplay-chat-section__title"><?php esc_html_e('Conversas', 'juntaplay'); ?></h3>
                                            <ul class="juntaplay-chat-card-list" data-chat-thread-list>
                                            </ul>
                                        </div>

                                        <div class="juntaplay-chat-panel__section" data-chat-panel="groups">
                                            <h3 class="juntaplay-chat-section__title"><?php esc_html_e('Grupos', 'juntaplay'); ?></h3>
                                            <ul class="juntaplay-chat-card-list" data-chat-group-list>
                                                <?php foreach ($chat_groups as $chat_group) :
                                                    $group_title   = isset($chat_group['title']) ? (string) $chat_group['title'] : __('Grupo', 'juntaplay');
                                                    $group_meta    = isset($chat_group['subtitle']) ? (string) $chat_group['subtitle'] : __('Assinaturas ativas', 'juntaplay');
                                                    $group_avatar  = isset($chat_group['avatar']) ? (string) $chat_group['avatar'] : '';
                                                    $group_initial = $group_title !== '' ? mb_substr($group_title, 0, 1) : 'G';
                                                    ?>
                                                    <li class="juntaplay-chat-card" data-chat-select="group">
                                                        <span class="juntaplay-chat-card__avatar" aria-hidden="true">
                                                            <?php if ($group_avatar !== '') : ?>
                                                                <img src="<?php echo esc_url($group_avatar); ?>" alt="" loading="lazy" />
                                                            <?php else : ?>
                                                                <?php echo esc_html($group_initial); ?>
                                                            <?php endif; ?>
                                                        </span>
                                                        <span class="juntaplay-chat-card__meta">
                                                            <strong class="juntaplay-chat-card__name"><?php echo esc_html($group_title); ?></strong>
                                                            <span class="juntaplay-chat-card__group"><?php echo esc_html($group_meta); ?></span>
                                                    </span>
                                                </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>

                                        <div class="juntaplay-chat-panel__section is-muted" data-chat-panel="subscribers">
                                            <h3 class="juntaplay-chat-section__title"><?php esc_html_e('Assinantes', 'juntaplay'); ?></h3>
                                            <ul class="juntaplay-chat-card-list" data-chat-subscriber-list>
                                                <?php foreach ($chat_subscribers as $chat_subscriber) :
                                                    $subscriber_name  = isset($chat_subscriber['name']) ? (string) $chat_subscriber['name'] : __('Assinante', 'juntaplay');
                                                    $subscriber_group = isset($chat_subscriber['group']) ? (string) $chat_subscriber['group'] : __('Grupo', 'juntaplay');
                                                    $subscriber_avatar = isset($chat_subscriber['avatar']) ? (string) $chat_subscriber['avatar'] : '';
                                                    $subscriber_initial = $subscriber_name !== '' ? mb_substr($subscriber_name, 0, 1) : 'A';
                                                    ?>
                                                    <li class="juntaplay-chat-card" data-chat-select="subscriber">
                                                        <span class="juntaplay-chat-card__avatar" aria-hidden="true">
                                                            <?php if ($subscriber_avatar !== '') : ?>
                                                                <img src="<?php echo esc_url($subscriber_avatar); ?>" alt="" loading="lazy" />
                                                            <?php else : ?>
                                                                <?php echo esc_html($subscriber_initial); ?>
                                                            <?php endif; ?>
                                                        </span>
                                                        <span class="juntaplay-chat-card__meta">
                                                            <strong class="juntaplay-chat-card__name"><?php echo esc_html($subscriber_name); ?></strong>
                                                            <span class="juntaplay-chat-card__group"><?php echo esc_html($subscriber_group); ?></span>
                                                        </span>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    </aside>

                                    <section class="juntaplay-chat-window" data-chat-window>
                                        <header class="juntaplay-chat-window__header">
                                            <div class="juntaplay-chat-window__participants">
                                                <div class="juntaplay-chat-window__avatars" aria-hidden="true">
                                                    <span class="juntaplay-chat-window__avatar juntaplay-chat-window__avatar--admin" data-chat-avatar="admin">
                                                        <?php if ($chat_admin_avatar !== '') : ?>
                                                            <img src="<?php echo esc_url($chat_admin_avatar); ?>" alt="" loading="lazy" />
                                                        <?php else : ?>
                                                            <?php echo esc_html(mb_substr($chat_admin_name, 0, 1)); ?>
                                                        <?php endif; ?>
                                                    </span>
                                                    <span class="juntaplay-chat-window__avatar" data-chat-avatar="recipient">
                                                        <?php if ($chat_recipient_avatar !== '') : ?>
                                                            <img src="<?php echo esc_url($chat_recipient_avatar); ?>" alt="" loading="lazy" />
                                                        <?php else : ?>
                                                            <?php echo esc_html(mb_substr($chat_recipient_name, 0, 1)); ?>
                                                        <?php endif; ?>
                                                    </span>
                                                </div>
                                                <div class="juntaplay-chat-window__meta">
                                                    <h3><?php echo esc_html($chat_admin_name . ' & ' . $chat_recipient_name); ?></h3>
                                                    <p><?php echo esc_html(sprintf(__('Conversas com %s · %s', 'juntaplay'), $chat_recipient_role, $chat_recipient_group)); ?></p>
                                                </div>
                                            </div>
                                        </header>

                                        <div class="juntaplay-chat-window__messages" data-chat-thread aria-live="polite"></div>

                                        <div class="juntaplay-chat-window__composer">
                                            <button type="button" class="juntaplay-chat-window__upload" aria-label="<?php echo esc_attr__('Enviar arquivo', 'juntaplay'); ?>">
                                                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                                    <path d="M12 5v14m0-14 5 5m-5-5-5 5" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" />
                                                </svg>
                                            </button>
                                            <input type="text" class="juntaplay-chat-window__input" placeholder="<?php echo esc_attr($chat_placeholder); ?>" aria-label="<?php echo esc_attr($chat_placeholder); ?>" />
                                            <button type="button" class="juntaplay-chat-window__action">
                                                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                                    <path d="m4 4 16 8-16 8 4-8-4-8z" fill="none" stroke="currentColor" stroke-linejoin="round" stroke-linecap="round" stroke-width="1.6" />
                                                </svg>
                                                <span><?php esc_html_e('Enviar', 'juntaplay'); ?></span>
                                            </button>
                                        </div>
                                    </section>
                                </div>
                            </div>
                        </div>
                    </div>
                </li>
            </ul>
        </section>
    </div>
</section>

<?php if ($is_chat_section) : ?>
    <script>
        (() => {
            const root = document.querySelector('[data-juntaplay-chat]');

            if (!root) {
                return;
            }

            const config = (() => {
                try {
                    return JSON.parse(root.dataset.chatConfig || '{}');
                } catch (e) {
                    return {};
                }
            })();

            const thread = root.querySelector('[data-chat-thread]');
            const input = root.querySelector('.juntaplay-chat-window__input');
            const sendButton = root.querySelector('.juntaplay-chat-window__action');
            const subscriberList = root.querySelector('[data-chat-subscriber-list]');
            const groupList = root.querySelector('[data-chat-group-list]');
            const threadList = root.querySelector('[data-chat-thread-list]');
            const headerTitle = root.querySelector('.juntaplay-chat-window__meta h3');
            const headerSubtitle = root.querySelector('.juntaplay-chat-window__meta p');
            const chatWindow = root.querySelector('[data-chat-window]');
            const adminAvatarEl = root.querySelector('[data-chat-avatar="admin"]');
            const recipientAvatarEl = root.querySelector('[data-chat-avatar="recipient"]');
            const subscriberPanel = root.querySelector('[data-chat-panel="subscribers"]');
            const groupPanel = root.querySelector('[data-chat-panel="groups"]');
            const notificationsProxy = (root.dataset.chatNotificationsProxy || '').trim()
                ? document.getElementById(root.dataset.chatNotificationsProxy)
                : null;

            const state = {
                groupId: config.initialGroupId || 0,
                adminId: config.initialAdminId || config.adminId || 0,
                subscriberId: config.subscriberId || (!config.isAdmin ? (config.currentUserId || 0) : 0),
                threadId: config.initialThreadId || 0,
                isAdmin: !!config.isAdmin,
                subscribers: {},
                groups: [],
                threads: [],
            };

            const forceRecipientIfSelf = (groups = []) => {
                const currentId = Number(config.currentUserId || 0);
                const explicitAdmin = Number(config.initialGroupId || 0) > 0
                    || Number(config.initialAdminId || 0) > 0
                    || Number(config.adminId || 0) > 0;
                const hasMemberGroups = Array.isArray(config.memberGroups) && config.memberGroups.length > 0;

                if (!currentId || !state.isAdmin) {
                    return;
                }

                const targetSelf = Number(config.subscriberId || 0) === currentId;
                if (!targetSelf && explicitAdmin && !hasMemberGroups) {
                    return;
                }

                const candidate = groups.find((entry) => Number(entry?.owner_id || entry?.admin_id || 0) > 0)
                    || (config.memberGroups || []).find((entry) => Number(entry?.owner_id || entry?.admin_id || 0) > 0)
                    || (config.groups || []).find((entry) => Number(entry?.owner_id || entry?.admin_id || 0) > 0);

                state.isAdmin = false;
                state.subscriberId = currentId;
                if (!state.adminId && candidate) {
                    state.adminId = Number(candidate.owner_id || candidate.admin_id || 0);
                }
                if (!state.groupId && candidate) {
                    state.groupId = Number(candidate.group_id || candidate.id || 0);
                }
                if (!state.threadId && candidate) {
                    state.threadId = Number(candidate.thread_id || candidate.id || 0);
                }
            };

            const maybeForceSubscriberMode = (groups = []) => {
                const hasMemberGroups = Array.isArray(config.memberGroups) && config.memberGroups.length > 0;

                if (!state.isAdmin || !hasMemberGroups) {
                    return;
                }

                const targetSubscriberId = Number(config.subscriberId || state.subscriberId || config.currentUserId || 0);
                const targetFromConfig = Number(config.subscriberId || 0) === targetSubscriberId;
                const targetFromGroups = groups.some(
                    (entry) => Number(entry?.subscriber_id || entry?.target_subscriber_id || 0) === targetSubscriberId
                );

                const candidate = groups.find((entry) => Number(entry?.owner_id || entry?.admin_id || 0) > 0) || groups[0]
                    || (config.memberGroups || []).find((entry) => Number(entry?.owner_id || entry?.admin_id || 0) > 0)
                    || (config.groups || []).find((entry) => Number(entry?.owner_id || entry?.admin_id || 0) > 0);

                state.isAdmin = false;
                state.subscriberId = targetSubscriberId;
                if (!state.adminId && candidate) {
                    state.adminId = Number(candidate.owner_id || candidate.admin_id || 0);
                }
                if (!state.groupId && candidate) {
                    state.groupId = Number(candidate.group_id || candidate.id || 0);
                }
                if (!state.threadId && candidate) {
                    state.threadId = Number(candidate.thread_id || candidate.id || 0);
                }

                ensureChatVisibility();
                renderGroupList();
                if (state.groupId && state.adminId) {
                    loadMessages();
                }
            };

            if (!state.isAdmin && state.adminId && !state.groupId) {
                const targetGroup = (config.memberGroups || []).find(
                    (entry) => Number(entry?.owner_id || 0) === Number(state.adminId)
                );

                if (targetGroup) {
                    state.groupId = Number(targetGroup.id || 0);
                }
            }

            const sidePanel = root.querySelector('.juntaplay-chat-panel');

            const sortByRecent = (list = []) => {
                return [...list].sort((a, b) => {
                    const aDate = new Date(a?.updated_at || a?.last_message_at || a?.created_at || 0).getTime();
                    const bDate = new Date(b?.updated_at || b?.last_message_at || b?.created_at || 0).getTime();
                    return bDate - aDate;
                });
            };

            const normalizeThread = (item = {}) => {
                return {
                    thread_id: Number(item.thread_id || item.id || 0),
                    group_id: Number(item.group_id || item.id || 0),
                    admin_id: Number(item.admin_id || item.owner_id || 0),
                    subscriber_id: Number(item.subscriber_id || item.target_subscriber_id || item.participant_id || 0),
                    admin_name: item.admin_name || item.owner_name || '',
                    admin_avatar: item.admin_avatar || item.owner_avatar || '',
                    subscriber_name: item.subscriber_name || item.recipient_name || '',
                    subscriber_avatar: item.subscriber_avatar || item.recipient_avatar || '',
                    group_name: item.group_name || item.title || '',
                    last_message: item.last_message || item.subtitle || '',
                    updated_at: item.updated_at || item.last_message_at || item.created_at || '',
                    unread_count: Number(item.unread_count || 0),
                };
            };

            if (Array.isArray(config.threads) && config.threads.length) {
                state.threads = sortByRecent(config.threads.map(normalizeThread));
            }

            const formatTime = (value) => {
                if (!value) return '';
                const parsed = new Date(String(value).replace(' ', 'T'));
                if (Number.isNaN(parsed.getTime())) return '';
                return parsed.toLocaleString();
            };

            const setStatus = (text) => {
                if (thread) {
                    thread.innerHTML = `<p class="juntaplay-chat-status">${text}</p>`;
                }
            };

            const setAvatar = (node, url, fallback = '') => {
                if (!node) return;
                const safeFallback = fallback !== '' ? fallback : '?';
                if (url) {
                    node.innerHTML = `<img src="${url}" alt="" loading="lazy" />`;
                    return;
                }
                node.textContent = safeFallback;
            };

            const emitNotifications = (threads = []) => {
                if (!notificationsProxy) {
                    return;
                }

                const items = threads.map((threadItem) => {
                    const name = threadItem.admin_name || threadItem.owner_name || threadItem.sender_name || '';
                    const avatar = threadItem.admin_avatar || threadItem.owner_avatar || threadItem.sender_avatar || '';
                    const preview = threadItem.last_message || '';
                    const timestamp = threadItem.updated_at || threadItem.last_message_at || threadItem.created_at || '';
                    const label = name ? `${name} <?php echo esc_js(__('te enviou uma mensagem.', 'juntaplay')); ?>` : '<?php echo esc_js(__('Nova mensagem no chat', 'juntaplay')); ?>';
                    const search = new URLSearchParams();
                    search.set('section', 'juntaplay-chat');
                    if (threadItem.group_id) search.set('group_id', threadItem.group_id);
                    if (threadItem.admin_id) search.set('admin_id', threadItem.admin_id);
                    if (threadItem.thread_id) search.set('thread_id', threadItem.thread_id);
                    if (threadItem.subscriber_id) search.set('subscriber_id', threadItem.subscriber_id);

                    return {
                        name,
                        avatar,
                        preview,
                        label,
                        timestamp,
                        url: `${config.messagesBase || '<?php echo esc_js($messages_base_url); ?>'}?${search.toString()}`,
                    };
                });

                notificationsProxy.dataset.items = JSON.stringify(items);
                window.juntaplayChatNotifications = items;
                window.dispatchEvent(
                    new CustomEvent('juntaplay:chatNotifications', {
                        detail: { items },
                    })
                );
            };

            const ensureChatVisibility = () => {
                if (!chatWindow) return;
                const shouldShow = !state.isAdmin || !!state.subscriberId;
                chatWindow.classList.toggle('is-hidden', !shouldShow);
                chatWindow.hidden = !shouldShow;
                chatWindow.setAttribute('aria-hidden', shouldShow ? 'false' : 'true');
            };

            forceRecipientIfSelf(config.memberGroups || []);
            maybeForceSubscriberMode(config.memberGroups || []);

            const renderMessages = (messages) => {
                if (!thread) return;
                if (!messages || !messages.length) {
                    setStatus('<?php echo esc_js(__('Nenhuma mensagem ainda. Envie a primeira!', 'juntaplay')); ?>');
                    return;
                }

                const currentUserId = Number(config.currentUserId || 0);

                thread.innerHTML = messages.map((item) => {
                    const isMine = Number(item.sender_id) === currentUserId;
                    const time = item.created_at ? new Date(item.created_at.replace(' ', 'T')).toLocaleString() : '';
                    const message = (item.message || '').replace(/</g, '&lt;').replace(/>/g, '&gt;');
                    return `
                        <div class="juntaplay-chat-message${isMine ? ' is-mine' : ''}">
                            <div class="juntaplay-chat-message__bubble">${message}</div>
                            <span class="juntaplay-chat-message__time">${time}</span>
                        </div>
                    `;
                }).join('');
                thread.scrollTop = thread.scrollHeight;
            };

            const updateHeader = (adminName, recipientName, groupLabel, recipientAvatar, adminAvatar) => {
                const safeAdmin = adminName || '<?php echo esc_js(__('Administrador', 'juntaplay')); ?>';
                const safeRecipient = recipientName || '<?php echo esc_js(__('Assinante', 'juntaplay')); ?>';
                const safeGroup = groupLabel || '<?php echo esc_js(__('Grupo', 'juntaplay')); ?>';

                if (headerTitle) {
                    headerTitle.textContent = `${safeAdmin} & ${safeRecipient}`;
                }

                if (headerSubtitle) {
                    headerSubtitle.textContent = `${safeRecipient} · ${safeGroup}`;
                }

                setAvatar(adminAvatarEl, adminAvatar || config.adminAvatar || '', safeAdmin.substring(0, 1).toUpperCase());
                setAvatar(
                    recipientAvatarEl,
                    recipientAvatar || config.recipientAvatar || '',
                    safeRecipient.substring(0, 1).toUpperCase()
                );
            };

            const selectSubscriber = (subscriber) => {
                state.subscriberId = subscriber ? Number(subscriber.id) : 0;
                state.threadId = subscriber ? Number(subscriber.thread_id || state.threadId || 0) : 0;
                updateHeader(
                    config.isAdmin ? (config.userName || '') : (subscriber?.name || ''),
                    config.isAdmin ? (subscriber?.name || '') : (config.adminName || ''),
                    subscriber?.group || '',
                    subscriber?.avatar || '',
                    config.adminAvatar || ''
                );
                ensureChatVisibility();
                loadMessages();
            };

            const renderThreadList = () => {
                if (!threadList) return;

                const currentUserId = Number(config.currentUserId || 0);
                const threads = sortByRecent(state.threads.length ? state.threads : state.groups);

                const escapeAttr = (value) => String(value ?? '').replace(/"/g, '&quot;');
                const escapeHtml = (value) => String(value ?? '').replace(/</g, '&lt;').replace(/>/g, '&gt;');

                threadList.innerHTML = threads.map((entry) => {
                    const isAdminThread = Number(entry.admin_id || entry.owner_id || 0) === currentUserId;
                    const counterpartName = isAdminThread
                        ? (entry.subscriber_name || '<?php echo esc_js(__('Assinante', 'juntaplay')); ?>')
                        : (entry.admin_name || entry.owner_name || '<?php echo esc_js(__('Administrador', 'juntaplay')); ?>');
                    const counterpartAvatar = isAdminThread
                        ? (entry.subscriber_avatar || '')
                        : (entry.admin_avatar || entry.owner_avatar || '');
                    const initial = (counterpartName || '?').substring(0, 1).toUpperCase();
                    const avatar = counterpartAvatar
                        ? `<img src="${escapeAttr(counterpartAvatar)}" alt="" loading="lazy" />`
                        : escapeHtml(initial);
                    const lastMessage = escapeHtml(entry.last_message || entry.subtitle || '');
                    const timeLabel = formatTime(entry.updated_at);
                    const unread = Number(entry.unread_count || 0);
                    const badge = unread > 0
                        ? `<span class=\"juntaplay-chat-card__badge\">${unread > 9 ? '9+' : unread}</span>`
                        : '';
                    const groupLabel = escapeHtml(entry.group_name || entry.title || '');

                    const isActive = Number(entry.thread_id || entry.id || 0) === Number(state.threadId)
                        || (Number(entry.group_id || entry.id || 0) === Number(state.groupId)
                            && Number(entry.subscriber_id || 0) === Number(state.subscriberId));

                    return `
                        <li class="juntaplay-chat-card${isActive ? ' is-active' : ''}" data-chat-select="thread" data-thread-id="${entry.thread_id || entry.id || 0}" data-group-id="${entry.group_id || entry.id || 0}" data-admin-id="${entry.admin_id || entry.owner_id || 0}" data-subscriber-id="${entry.subscriber_id || entry.target_subscriber_id || 0}" data-admin-name="${escapeAttr(entry.admin_name || entry.owner_name || '')}" data-admin-avatar="${escapeAttr(entry.admin_avatar || entry.owner_avatar || '')}" data-subscriber-name="${escapeAttr(entry.subscriber_name || '')}" data-subscriber-avatar="${escapeAttr(entry.subscriber_avatar || '')}" data-group-name="${escapeAttr(entry.group_name || entry.title || '')}">
                            <span class="juntaplay-chat-card__avatar" aria-hidden="true">${avatar}</span>
                            <span class="juntaplay-chat-card__meta">
                                <span class="juntaplay-chat-card__row">
                                    <strong class="juntaplay-chat-card__name">${escapeHtml(counterpartName)}</strong>
                                    <span class="juntaplay-chat-card__time">${timeLabel}</span>
                                </span>
                                <span class="juntaplay-chat-card__group">${groupLabel}</span>
                                <span class="juntaplay-chat-card__row">
                                    <span class="juntaplay-chat-card__preview">${lastMessage}</span>
                                    ${badge}
                                </span>
                            </span>
                        </li>`;
                }).join('');

                threadList.querySelectorAll('[data-chat-select="thread"]').forEach((item) => {
                    item.addEventListener('click', () => {
                        threadList.querySelectorAll('[data-chat-select="thread"]').forEach((node) =>
                            node.classList.remove('is-active')
                        );
                        item.classList.add('is-active');

                        const adminId = Number(item.dataset.adminId || 0);
                        const subscriberId = Number(item.dataset.subscriberId || 0);
                        const groupId = Number(item.dataset.groupId || 0);
                        const threadId = Number(item.dataset.threadId || 0);
                        const adminName = item.dataset.adminName || '';
                        const adminAvatar = item.dataset.adminAvatar || '';
                        const subscriberName = item.dataset.subscriberName || '';
                        const subscriberAvatar = item.dataset.subscriberAvatar || '';
                        const groupName = item.dataset.groupName || '';

                        state.threadId = threadId;
                        state.groupId = groupId;
                        state.adminId = adminId;
                        state.subscriberId = subscriberId || Number(config.currentUserId || 0);
                        state.isAdmin = Number(config.currentUserId || 0) === adminId;

                        updateHeader(
                            state.isAdmin ? adminName : adminName,
                            state.isAdmin ? subscriberName : subscriberName,
                            groupName,
                            state.isAdmin ? subscriberAvatar : subscriberAvatar,
                            adminAvatar
                        );

                        ensureChatVisibility();
                        loadMessages();
                    });
                });

                if (threads.length && !state.threadId) {
                    const unread = threads.find((entry) => Number(entry.unread_count || 0) > 0) || threads[0];
                    if (unread) {
                        state.threadId = Number(unread.thread_id || unread.id || 0);
                        state.groupId = Number(unread.group_id || unread.id || 0);
                        state.adminId = Number(unread.admin_id || unread.owner_id || 0);
                        state.subscriberId = Number(unread.subscriber_id || unread.target_subscriber_id || config.currentUserId || 0);
                        state.isAdmin = Number(config.currentUserId || 0) === state.adminId;

                        updateHeader(
                            unread.admin_name || unread.owner_name || '',
                            unread.subscriber_name || '',
                            unread.group_name || unread.title || '',
                            unread.subscriber_avatar || '',
                            unread.admin_avatar || unread.owner_avatar || ''
                        );

                        renderThreadList();
                        loadMessages();
                    }
                }
            };

            const renderSubscriberList = () => {
                if (!subscriberList) return;

                const list = state.subscribers[state.groupId] || [];

                if (subscriberPanel) {
                    subscriberPanel.classList.toggle('is-muted', !state.groupId);
                }

                subscriberList.innerHTML = list.map((subscriber) => {
                    const initial = (subscriber.name || 'A').substring(0, 1).toUpperCase();
                    const avatar = subscriber.avatar ? `<img src="${subscriber.avatar}" alt="" loading="lazy" />` : initial;
                    return `
                        <li class="juntaplay-chat-card" data-chat-select="subscriber" data-subscriber-id="${subscriber.id}">
                            <span class="juntaplay-chat-card__avatar" aria-hidden="true">${avatar}</span>
                            <span class="juntaplay-chat-card__meta">
                                <strong class="juntaplay-chat-card__name">${subscriber.name || '<?php echo esc_js(__('Assinante', 'juntaplay')); ?>'}</strong>
                                <span class="juntaplay-chat-card__group">${subscriber.group || ''}</span>
                            </span>
                        </li>`;
                }).join('');

                subscriberList.querySelectorAll('[data-chat-select="subscriber"]').forEach((item) => {
                    item.addEventListener('click', () => {
                        subscriberList.querySelectorAll('[data-chat-select="subscriber"]').forEach((node) =>
                            node.classList.remove('is-active')
                        );
                        item.classList.add('is-active');

                        const id = Number(item.dataset.subscriberId || 0);
                        const found = list.find((entry) => Number(entry.id) === id);
                        selectSubscriber(found || null);
                    });
                });
            };

            const selectGroup = (group) => {
                state.groupId = group ? Number(group.id) : 0;
                state.threadId = group ? Number(group.thread_id || state.threadId || 0) : state.threadId;

                if (subscriberPanel) {
                    subscriberPanel.classList.toggle('is-muted', !state.groupId);
                }

                if (state.isAdmin) {
                    state.subscriberId = 0;
                    setStatus('<?php echo esc_js(__('Selecione um assinante para iniciar a conversa.', 'juntaplay')); ?>');
                    updateHeader(config.userName || '', '', group?.title || '', '', config.adminAvatar || '');
                }

                if (!state.isAdmin && group) {
                    state.adminId = Number(group.owner_id || state.adminId || 0);
                    const adminName = group.owner_name || config.adminName || '';
                    const adminAvatar = group.owner_avatar || config.adminAvatar || '';
                    state.subscriberId = state.subscriberId || Number(config.currentUserId || 0);
                    updateHeader(
                        adminName,
                        config.recipientName || '',
                        group.title || '',
                        config.recipientAvatar || '',
                        adminAvatar || config.adminAvatar || ''
                    );
                    loadMessages();
                    return;
                }

                if (state.isAdmin && state.groupId) {
                    fetchContext(state.groupId);
                } else {
                    loadMessages();
                }

                ensureChatVisibility();
            };

            const renderGroupList = () => {
                if (!groupList) return;
                const groupsSource = state.groups.length ? state.groups : (state.isAdmin ? (config.groups || []) : (config.memberGroups || []));
                const groups = sortByRecent(groupsSource);

                groupList.innerHTML = groups.map((group) => {
                    const initial = (group.title || 'G').substring(0, 1).toUpperCase();
                    const avatar = group.avatar ? `<img src="${group.avatar}" alt="" loading="lazy" />` : initial;
                    const isActive = Number(group.id) === Number(state.groupId);
                    const recentLabel = formatTime(group.updated_at || group.last_message_at || '');
                    const subtitle = [group.subtitle || group.last_message || '', recentLabel].filter(Boolean).join(' · ');
                    return `
                        <li class="juntaplay-chat-card${isActive ? ' is-active' : ''}" data-chat-select="group" data-group-id="${group.id}" data-owner-id="${group.owner_id || ''}" data-owner-name="${group.owner_name || ''}" data-owner-avatar="${group.owner_avatar || ''}" data-thread-id="${group.thread_id || ''}">
                            <span class="juntaplay-chat-card__avatar" aria-hidden="true">${avatar}</span>
                            <span class="juntaplay-chat-card__meta">
                                <strong class="juntaplay-chat-card__name">${group.title || '<?php echo esc_js(__('Grupo', 'juntaplay')); ?>'}</strong>
                                <span class="juntaplay-chat-card__group">${subtitle}</span>
                            </span>
                        </li>`;
                }).join('');

                groupList.querySelectorAll('[data-chat-select="group"]').forEach((item) => {
                    item.addEventListener('click', () => {
                        groupList.querySelectorAll('[data-chat-select="group"]').forEach((node) =>
                            node.classList.remove('is-active')
                        );
                        item.classList.add('is-active');

                        const id = Number(item.dataset.groupId || 0);
                        const threadId = Number(item.dataset.threadId || 0);
                        const found = groups.find((entry) => Number(entry.id) === id);
                        if (threadId && found && !found.thread_id) {
                            found.thread_id = threadId;
                        }
                        selectGroup(found || null);
                    });
                });

                if (!state.isAdmin && groups.length) {
                    const preset = groups.find((entry) => Number(entry.id) === Number(state.groupId));
                    const fallback = preset || groups[0];
                    if (fallback) {
                        state.groupId = Number(fallback.id) || state.groupId;
                        state.adminId = Number(fallback.owner_id || state.adminId || 0);
                        state.threadId = Number(fallback.thread_id || state.threadId || 0);
                        groupList.querySelectorAll('[data-chat-select="group"]').forEach((node) => node.classList.remove('is-active'));
                        const activeNode = groupList.querySelector(`[data-group-id="${state.groupId}"]`);
                        if (activeNode) {
                            activeNode.classList.add('is-active');
                        }
                        selectGroup(fallback);
                    }
                }
            };

            const loadMessages = async () => {
                if (!state.adminId || !state.subscriberId) {
                    setStatus('<?php echo esc_js(__('Selecione um participante para iniciar a conversa.', 'juntaplay')); ?>');
                    return;
                }

                const url = new URL(config.rest?.messages || '', window.location.origin);
                url.searchParams.set('admin_id', state.adminId);
                url.searchParams.set('subscriber_id', state.subscriberId);
                if (state.groupId) {
                    url.searchParams.set('group_id', state.groupId);
                }
                if (state.threadId) {
                    url.searchParams.set('thread_id', state.threadId);
                }

                setStatus('<?php echo esc_js(__('Carregando mensagens...', 'juntaplay')); ?>');

                const response = await fetch(url.toString(), {
                    headers: {
                        'X-WP-Nonce': config.nonce || '',
                    },
                    credentials: 'same-origin',
                });

                if (!response.ok) {
                    setStatus('<?php echo esc_js(__('Não foi possível carregar as mensagens.', 'juntaplay')); ?>');
                    return;
                }

                const payload = await response.json();
                renderMessages(payload?.messages || payload?.data?.messages || []);
            };

            const fetchContext = async (groupId = 0) => {
                if (!config.rest?.context) {
                    renderSubscriberList();
                    return;
                }

                const url = new URL(config.rest.context, window.location.origin);
                if (groupId) {
                    url.searchParams.set('group_id', groupId);
                }

                const response = await fetch(url.toString(), {
                    headers: { 'X-WP-Nonce': config.nonce || '' },
                    credentials: 'same-origin',
                });

                if (!response.ok) {
                    renderSubscriberList();
                    return;
                }

                const data = await response.json();
                const normalizedGroups = data?.groups || data?.data?.groups || [];
                const normalizedThreads = data?.threads || data?.data?.threads || [];

                if (Array.isArray(normalizedThreads) && normalizedThreads.length) {
                    state.threads = sortByRecent(normalizedThreads.map(normalizeThread));
                }

                if (Array.isArray(normalizedGroups) && normalizedGroups.length) {
                    state.groups = sortByRecent(normalizedGroups);
                    forceRecipientIfSelf(state.groups);
                    maybeForceSubscriberMode(state.groups);
                    if (!state.threads.length) {
                        state.threads = sortByRecent(state.groups.map(normalizeThread));
                    }
                    renderGroupList();
                    renderThreadList();
                    emitNotifications(state.threads.length ? state.threads : state.groups);

                    if (!state.isAdmin && state.adminId && !state.groupId) {
                        const adminMatch = normalizedGroups.find(
                            (entry) => Number(entry.owner_id || 0) === Number(state.adminId)
                        );

                        if (adminMatch) {
                            state.groupId = Number(adminMatch.id || 0);
                        }
                    }

                    if (!state.isAdmin && !state.adminId) {
                        const target = state.groupId
                            ? normalizedGroups.find((entry) => Number(entry.id) === Number(state.groupId))
                            : normalizedGroups.find((entry) => !entry.is_admin) || normalizedGroups[0];

                        if (target) {
                            state.groupId = state.groupId || Number(target.id) || 0;
                            state.adminId = Number(target.owner_id || 0);
                            state.subscriberId = state.subscriberId || Number(config.currentUserId || 0);
                            updateHeader(config.adminName || '', config.recipientName || '', target.title || '');
                        }
                    }

                    if (!state.isAdmin && !state.groupId && state.groups.length) {
                        const firstThread = state.groups[0];
                        state.groupId = Number(firstThread.id || 0);
                        state.adminId = Number(firstThread.owner_id || 0);
                        state.threadId = Number(firstThread.thread_id || 0);
                        state.subscriberId = state.subscriberId || Number(config.currentUserId || 0);
                        selectGroup(firstThread);
                        return;
                    }
                }

                if (!state.groups.length && Array.isArray(normalizedThreads) && normalizedThreads.length) {
                    state.groups = sortByRecent(
                        normalizedThreads.map((threadItem) => ({
                            id: threadItem.group_id || threadItem.id || 0,
                            owner_id: threadItem.admin_id || threadItem.owner_id || 0,
                            owner_name: threadItem.admin_name || threadItem.owner_name || '',
                            owner_avatar: threadItem.admin_avatar || threadItem.owner_avatar || '',
                            title: threadItem.group_name || threadItem.title || '',
                            subtitle: threadItem.last_message || '',
                            last_message: threadItem.last_message || '',
                            last_message_at: threadItem.updated_at || threadItem.last_message_at || '',
                            thread_id: threadItem.thread_id || threadItem.id || 0,
                        }))
                    );
                    forceRecipientIfSelf(state.groups);
                    maybeForceSubscriberMode(state.groups);
                    renderGroupList();
                    renderThreadList();
                    emitNotifications(state.threads.length ? state.threads : state.groups);
                }

                if (groupId) {
                    state.subscribers[groupId] = data?.subscribers || data?.data?.subscribers || [];
                }

                renderSubscriberList();

                if (!state.subscriberId) {
                    setStatus('<?php echo esc_js(__('Selecione um participante para iniciar a conversa.', 'juntaplay')); ?>');
                    ensureChatVisibility();
                } else if (!state.isAdmin && state.adminId) {
                    loadMessages();
                }
            };

            const syncActiveContext = () => {
                const activeGroup = groupList?.querySelector('.is-active');
                const activeSubscriber = subscriberList?.querySelector('.is-active');

                if (activeGroup) {
                    state.groupId = Number(activeGroup.dataset.groupId || state.groupId || 0);
                    state.adminId = Number(activeGroup.dataset.ownerId || state.adminId || 0);
                    state.threadId = Number(activeGroup.dataset.threadId || state.threadId || 0);
                }

                if (activeSubscriber) {
                    state.subscriberId = Number(activeSubscriber.dataset.subscriberId || state.subscriberId || 0);
                }
            };

            const sendMessage = async () => {
                syncActiveContext();

                if (!state.adminId || !state.subscriberId) {
                    return;
                }

                const text = input ? input.value.trim() : '';
                if (!text) {
                    return;
                }

                const response = await fetch(config.rest?.send || '', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': config.nonce || '',
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        admin_id: state.adminId,
                        subscriber_id: state.subscriberId,
                        group_id: state.groupId,
                        thread_id: state.threadId || undefined,
                        message: text,
                    }),
                });

                if (response.ok) {
                    input.value = '';
                    loadMessages();
                }
            };

            if (sendButton) {
                sendButton.addEventListener('click', () => {
                    sendMessage();
                });
            }

            if (input) {
                input.addEventListener('keydown', (event) => {
                    if (event.key === 'Enter') {
                        event.preventDefault();
                        sendMessage();
                    }
                });
            }

            updateHeader(
                config.adminName || '',
                config.recipientName || '',
                config.initialGroupId ? '' : config.initialGroupName || '',
                config.recipientAvatar || '',
                config.adminAvatar || ''
            );
            renderGroupList();
            renderThreadList();
            ensureChatVisibility();

            if (state.isAdmin && !state.subscriberId) {
                setStatus('<?php echo esc_js(__('Selecione um assinante para iniciar a conversa.', 'juntaplay')); ?>');
            }

            // Always fetch context to populate groups/subscribers when the page loads
            // from the dedicated Central de Mensagens tab.
            fetchContext(state.groupId);

            if (!state.isAdmin && state.adminId && state.subscriberId) {
                loadMessages();
            }
        })();
    </script>
<?php endif; ?>

<div
    id="jp-profile-groups"
    class="<?php echo esc_attr($groups_container_class); ?>"
    data-groups
    data-role-filter="all"
    data-status-filter="all"
    <?php echo $is_chat_section ? 'hidden' : ''; ?>
>
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

    <?php if ($cancel_general_errors) : ?>
        <div class="juntaplay-alert juntaplay-alert--danger">
            <ul>
                <?php foreach ($cancel_general_errors as $cancel_general_error) : ?>
                    <li><?php echo esc_html((string) $cancel_general_error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php
    $group_cards = [];

    if ($all_groups) :
        foreach ($all_groups as $group) :
            if (!is_array($group)) {
                continue;
            }

                    $group_id        = isset($group['id']) ? (int) $group['id'] : 0;
                    $group_title     = isset($group['title']) ? (string) $group['title'] : '';
                    $group_role      = isset($group['membership_role']) ? (string) $group['membership_role'] : 'member';
                    $owner_id        = isset($group['owner_id']) ? (int) $group['owner_id'] : 0;
                    if ($owner_id === 0 && isset($group['admin_id'])) {
                        $owner_id = (int) $group['admin_id'];
                    }
                    if ($owner_id === 0 && isset($group['owner_user_id'])) {
                        $owner_id = (int) $group['owner_user_id'];
                    }
                    if ($owner_id === 0 && isset($group['owner']) && is_array($group['owner'])) {
                        if (isset($group['owner']['id'])) {
                            $owner_id = (int) $group['owner']['id'];
                        } elseif (isset($group['owner']['user_id'])) {
                            $owner_id = (int) $group['owner']['user_id'];
                        }
                    }
                    if ($owner_id === 0 && isset($group['owner_user']) && is_array($group['owner_user'])) {
                        if (isset($group['owner_user']['id'])) {
                            $owner_id = (int) $group['owner_user']['id'];
                        } elseif (isset($group['owner_user']['user_id'])) {
                            $owner_id = (int) $group['owner_user']['user_id'];
                        }
                    }
                    if ($owner_id === 0 && isset($group['admin']) && is_array($group['admin'])) {
                        if (isset($group['admin']['id'])) {
                            $owner_id = (int) $group['admin']['id'];
                        } elseif (isset($group['admin']['user_id'])) {
                            $owner_id = (int) $group['admin']['user_id'];
                        }
                    }
                    if ($owner_id === 0 && isset($group['creator_id'])) {
                        $owner_id = (int) $group['creator_id'];
                    }
                    if ($owner_id === 0 && isset($group['user_id'])) {
                        $owner_id = (int) $group['user_id'];
                    }
                    $status            = isset($group['status']) ? (string) $group['status'] : '';
                    $membership_status = isset($group['membership_status']) ? (string) $group['membership_status'] : 'active';
                    $status_label      = isset($group['status_label']) && is_string($group['status_label']) ? (string) $group['status_label'] : '';
                    $is_owner           = $owner_id > 0 ? $owner_id === $current_user_id : ($group_role === 'owner');
                    $role_label         = isset($group['role_label']) && is_string($group['role_label']) ? (string) $group['role_label'] : '';
                    $role_tone          = isset($group['role_tone']) && is_string($group['role_tone']) ? (string) $group['role_tone'] : '';
                    $role_ribbon_label  = '';
                    $role_ribbon_tone   = '';
                    if ($is_owner) {
                        $role_label = __('Você administra este grupo', 'juntaplay');
                        $role_tone  = 'positive';
                        $role_ribbon_label = __('Administrador', 'juntaplay');
                        $role_ribbon_tone  = 'admin';
                    } elseif ($group_role !== 'owner' && $group_role !== 'manager') {
                        $role_label = __('Você é assinante', 'juntaplay');
                        $role_tone  = 'info';
                        $role_ribbon_label = __('Assinante', 'juntaplay');
                        $role_ribbon_tone  = 'subscriber';
                    }
                    if (stripos($role_label, 'criador do grupo') !== false) {
                        $role_label = __('Você administra este grupo', 'juntaplay');
                        $role_tone  = 'positive';
                    }
                    if ($group_role !== 'owner' && $group_role !== 'manager' && stripos($role_label, 'participante') !== false) {
                        $role_label = __('Você é assinante', 'juntaplay');
                        $role_tone  = 'info';
                    }
                    if ($role_label !== '' && $status_label !== '') {
                        $status_keywords = ['administra', 'assinante', 'participante'];
                        foreach ($status_keywords as $status_keyword) {
                            if ($status_label !== '' && stripos($status_label, $status_keyword) !== false) {
                                $status_label = '';

                                break;
                            }
                        }
                    }
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
                    $can_edit        = !empty($group['can_edit']);
                    $faq_items       = isset($group['faq_items']) && is_array($group['faq_items']) ? array_filter($group['faq_items'], 'is_array') : [];
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
                    if ($cta_label !== '' && (stripos($cta_label, 'administra') !== false || stripos($cta_label, 'assinante') !== false)) {
                        $cta_label = '';
                    }
                    $cta_variant        = isset($group['cta_variant']) ? (string) $group['cta_variant'] : 'ghost';
                    $cta_disabled       = !empty($group['cta_disabled']);
                    $cta_url            = isset($group['cta_url']) ? (string) $group['cta_url'] : '';

                    $group_role_key = is_string($group_role) ? strtolower($group_role) : '';
                    $is_admin_role  = $owner_id === $current_user_id
                        || in_array($group_role_key, ['owner', 'manager', 'creator', 'admin', 'administrator'], true)
                        || (is_string($role_label) && stripos($role_label, 'administra') !== false);

                    $chat_link_prefill  = '';
                    $chat_label_prefill = '';

                    if ($messages_base_url !== '') {
                        if ($is_admin_role && $membership_status !== 'guest') {
                            $chat_label_prefill = __('Fale com Assinante', 'juntaplay');
                            $chat_link_prefill  = $messages_base_url;
                        } elseif ($membership_status !== 'guest') {
                            $chat_label_prefill = __('Fale com Administrador', 'juntaplay');
                            $chat_link_prefill  = $messages_base_url;
                        }
                    }

                    $group_title_full = $group_title !== '' ? $group_title : __('Grupo sem nome', 'juntaplay');
                    if ($price_highlight === '' && $member_display !== '') {
                        $price_highlight = $member_display;
                    }

                    $service_name    = isset($group['service_name']) ? (string) $group['service_name'] : '';
                    $service_url     = isset($group['service_url']) ? (string) $group['service_url'] : '';
                    $service_slug    = isset($group['pool_slug']) ? (string) $group['pool_slug'] : '';
                    $icon_source     = isset($group['cover_url']) ? (string) $group['cover_url'] : '';
                    if ($icon_source === '') {
                        $icon_source = $service_slug !== '' ? ServiceIcons::get($service_slug) : '';
                    }
                    if ($icon_source === '') {
                        $icon_source = ServiceIcons::resolve($service_slug, $service_name !== '' ? $service_name : $group_title_full, $service_url);
                    }
                    if ($icon_source === '') {
                        $icon_source = ServiceIcons::fallback();
                    }
                    $icon_initial_raw = $group_title_full !== '' ? $group_title_full : ($service_name !== '' ? $service_name : '');
                    $icon_initial = $icon_initial_raw !== ''
                        ? (function_exists('mb_substr') ? mb_substr($icon_initial_raw, 0, 1) : substr($icon_initial_raw, 0, 1))
                        : '';
                    $icon_classes = ['juntaplay-group-card__avatar', 'juntaplay-service-card__icon'];
                    if ($icon_source !== '') {
                        $icon_classes[] = 'has-image';
                    }

                    $meta_items = [];
                    if ($role_ribbon_label !== '') {
                        $ribbon_text = function_exists('mb_strtoupper')
                            ? mb_strtoupper($role_ribbon_label)
                            : strtoupper($role_ribbon_label);
                        $meta_items[] = '<span class="jp-role-ribbon jp-role-ribbon--' . esc_attr($role_ribbon_tone ?: 'admin') . '">' . esc_html($ribbon_text) . '</span>';
                    }
                    if ($category_label !== '') {
                        $meta_items[] = '<span class="juntaplay-service-card__pill">' . esc_html($category_label) . '</span>';
                    }

                    if ($slots_available_label !== '') {
                        $meta_items[] = '<span class="juntaplay-service-card__pill juntaplay-service-card__pill--muted">' . esc_html($slots_available_label) . '</span>';
                    } elseif ($slots_total_label !== '') {
                        $meta_items[] = '<span class="juntaplay-service-card__pill juntaplay-service-card__pill--muted">' . esc_html($slots_total_label) . '</span>';
                    }

                    $role_label_display = $role_label;
                    if (
                        $role_label_display !== ''
                        && (stripos($role_label_display, 'administra') !== false || stripos($role_label_display, 'assinante') !== false)
                    ) {
                        $role_label_display = '';
                    }
                    if ($role_label_display !== '') {
                        $meta_items[] = '<span class="juntaplay-group-card__role-pill juntaplay-badge juntaplay-badge--' . esc_attr($role_tone ?: 'info') . '">' . esc_html($role_label_display) . '</span>';
                    }

                    if ($price_highlight !== '') {
                        $meta_items[] = '<span class="juntaplay-service-card__price">' . wp_kses_post($price_highlight) . '</span>';
                    }

                    $service_classes = [
                        'juntaplay-service-card',
                        'juntaplay-service-card--group',
                        'juntaplay-service-card--highlight',
                    ];

            $card_link_attrs = $cta_url !== ''
                ? ' href="' . esc_url($cta_url) . '"'
                : ' href="#"';

            ob_start();
            ?>
            <article
                class="<?php echo esc_attr(implode(' ', $service_classes)); ?>"
                data-group-item
                data-group-id="<?php echo esc_attr((string) $group_id); ?>"
                data-group-role="<?php echo esc_attr($group_role); ?>"
                data-group-status="<?php echo esc_attr($status); ?>"
                data-jp-group-open
                data-chat-link="<?php echo esc_url($chat_link_prefill); ?>"
                data-chat-label="<?php echo esc_attr($chat_label_prefill); ?>"
            >
                        <a class="juntaplay-service-card__link"<?php echo $card_link_attrs; ?> data-jp-group-open data-group-id="<?php echo esc_attr((string) $group_id); ?>" data-chat-link="<?php echo esc_url($chat_link_prefill); ?>" data-chat-label="<?php echo esc_attr($chat_label_prefill); ?>">
                            <span
                                class="<?php echo esc_attr(implode(' ', $icon_classes)); ?>"
                                <?php echo $icon_source !== '' ? ' style="background-image: url(' . esc_url($icon_source) . ')"' : ''; ?>
                                aria-hidden="true"
                            ><?php echo $icon_source === '' ? esc_html($icon_initial) : ''; ?></span>
                            <span class="juntaplay-service-card__title" title="<?php echo esc_attr($group_title_full); ?>"><?php echo esc_html($group_title_full); ?></span>
                            <?php if ($service_name !== '' && $service_name !== $group_title_full) : ?>
                                <span class="juntaplay-service-card__description"><?php echo esc_html($service_name); ?></span>
                            <?php endif; ?>
                            <?php if (!empty($meta_items)) : ?>
                                <span class="juntaplay-service-card__meta"><?php echo implode('', $meta_items); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
                            <?php endif; ?>
                        </a>
                                    <div class="juntaplay-group-card__footer">
                                        <div class="juntaplay-group-card__cta-inline">
                                            <?php if ($can_edit) : ?>
                                                <button type="button" class="juntaplay-group-card__edit juntaplay-group-card__edit--inline" data-group-id="<?php echo esc_attr((string) $group_id); ?>">
                                                    <svg width="16" height="16" viewBox="0 0 16 16" aria-hidden="true" focusable="false">
                                                        <path d="M11.013 2.338c.33-.33.866-.33 1.196 0l1.453 1.453c.33.33.33.866 0 1.196l-6.6 6.6a.85.85 0 0 1-.42.228l-2.32.53a.5.5 0 0 1-.6-.6l.53-2.32a.85.85 0 0 1 .228-.42zM9.5 4L3.75 9.75l-.4 1.74l1.74-.4L11 5.5z" fill="currentColor" />
                                                    </svg>
                                                    <span><?php esc_html_e('Editar grupo', 'juntaplay'); ?></span>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                        <div class="juntaplay-group-card__actions juntaplay-group-card__actions--primary">
                                            <button
                                                type="button"
                                                class="juntaplay-button juntaplay-button--primary juntaplay-button--glass juntaplay-group-card__toggle"
                                                style="background:linear-gradient(135deg, rgba(94,234,212,0.2), rgba(59,130,246,0.18));border:1px solid rgba(59,130,246,0.32);box-shadow:0 14px 32px rgba(59,130,246,0.22);color:#0b1220;"
                                                aria-expanded="false"
                                                data-jp-group-open
                                                data-group-id="<?php echo esc_attr((string) $group_id); ?>"
                                                data-chat-link="<?php echo esc_url($chat_link_prefill); ?>"
                                                data-chat-label="<?php echo esc_attr($chat_label_prefill); ?>"
                                            >
                                                <span class="juntaplay-group-card__toggle-label" style="color:#0b1220;"><?php esc_html_e('Ver detalhes', 'juntaplay'); ?></span>
                                                <svg width="16" height="16" viewBox="0 0 16 16" aria-hidden="true" focusable="false">
                                                    <path d="M4 6l4 4l4-4" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                        <div class="juntaplay-group-card__details-extended" hidden>
                                            <div class="juntaplay-group-card__chips">
                                                <?php if ($availability_label !== '') : ?>
                                                    <span class="juntaplay-badge juntaplay-badge--<?php echo esc_attr($availability_tone ?: 'info'); ?>"><?php echo esc_html($availability_label); ?></span>
                                                <?php endif; ?>
                                                <?php
                                                $status_is_role = false;
                                                if ($status_label !== '') {
                                                    $status_lower = strtolower($status_label);
                                                    $status_is_role = (isset($role_label) && is_string($role_label) && strcasecmp($status_label, $role_label) === 0)
                                                        || strpos($status_lower, 'administra') !== false
                                                        || strpos($status_lower, 'assinante') !== false
                                                        || strpos($status_lower, 'participante') !== false;
                                                }
                                                ?>
                                                <?php if ($status_label !== '' && !$status_is_role) : ?>
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
                                                    <button type="button" class="juntaplay-group-card__edit" data-group-id="<?php echo esc_attr((string) $group_id); ?>">
                                                        <svg width="16" height="16" viewBox="0 0 16 16" aria-hidden="true" focusable="false">
                                                            <path d="M11.013 2.338c.33-.33.866-.33 1.196 0l1.453 1.453c.33.33.33.866 0 1.196l-6.6 6.6a.85.85 0 0 1-.42.228l-2.32.53a.5.5 0 0 1-.6-.6l.53-2.32a.85.85 0 0 1 .228-.42zM9.5 4L3.75 9.75l-.4 1.74l1.74-.4L11 5.5z" fill="currentColor" />
                                                        </svg>
                                                        <span><?php esc_html_e('Editar grupo', 'juntaplay'); ?></span>
                                                    </button>
                                                <?php endif; ?>
                                                <?php if ($membership_status !== 'guest') : ?>
                                                    <button type="button" class="juntaplay-group-card__access-btn" data-group-access="<?php echo esc_attr((string) $group_id); ?>">
                                                        <?php esc_html_e('Ver dados de acesso', 'juntaplay'); ?>
                                                    </button>
                                                <?php endif; ?>
                                                <?php
                                                $chat_link  = $chat_link_prefill;
                                                $chat_label = $chat_label_prefill;
                                                ?>
                                                <?php if ($chat_link !== '' && $chat_label !== '') : ?>
                                                    <a class="juntaplay-button juntaplay-button--primary juntaplay-button--glass" href="<?php echo esc_url($chat_link); ?>"><?php echo esc_html($chat_label); ?></a>
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
                                                <?php if ($is_owner) { ?>
                                                    <?php $dispute_url = add_query_arg('section', 'juntaplay-disputas', $messages_base_url); ?>
                                                    <div class="juntaplay-group-card__complaint" data-group-complaint>
                                                        <header class="juntaplay-group-card__complaint-header">
                                                            <div>
                                                                <h4><?php echo esc_html__('Precisa de ajuda com assinantes?', 'juntaplay'); ?></h4>
                                                                <p class="juntaplay-group-card__complaint-summary"><?php echo esc_html__('Abra uma disputa para acompanhar reclamações e responder rapidamente.', 'juntaplay'); ?></p>
                                                            </div>
                                                        </header>
                                                        <a class="juntaplay-button juntaplay-button--primary" href="<?php echo esc_url($dispute_url); ?>"><?php echo esc_html__('Abrir disputa', 'juntaplay'); ?></a>
                                                    </div>
                                                <?php } elseif ($complaint_reasons) { ?>
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
                                                <?php } else { ?>
                                                    <?php $dispute_url = add_query_arg('section', 'juntaplay-disputas', $messages_base_url); ?>
                                                    <div class="juntaplay-group-card__complaint" data-group-complaint>
                                                        <header class="juntaplay-group-card__complaint-header">
                                                            <div>
                                                                <h4><?php echo esc_html__('Precisa de ajuda com assinantes?', 'juntaplay'); ?></h4>
                                                                <p class="juntaplay-group-card__complaint-summary"><?php echo esc_html__('Abra uma disputa para acompanhar reclamações e responder rapidamente.', 'juntaplay'); ?></p>
                                                            </div>
                                                        </header>
                                                        <a class="juntaplay-button juntaplay-button--primary" href="<?php echo esc_url($dispute_url); ?>"><?php echo esc_html__('Abrir disputa', 'juntaplay'); ?></a>
                                                    </div>
                                                <?php } ?>
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
</div> <!-- fecha .juntaplay-group-card__details-extended -->
</article>
<?php
// aqui sim, pega TODO o HTML do <article> (do ob_start até aqui)
// e armazena no array de cards
$group_cards[] = trim((string) ob_get_clean());
?>
  <?php endforeach; ?>
  <?php endif; ?>

    <div class="juntaplay-groups__list juntaplay-groups__cards juntaplay-service-grid" data-group-list>
        <?php echo implode("\n", $group_cards); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
    </div>
    <p class="juntaplay-groups__empty is-hidden" data-group-empty><?php echo esc_html__('Nenhum grupo corresponde aos filtros selecionados.', 'juntaplay'); ?></p>
    <?php if ($all_groups && $total_pages > 1) : ?>
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
    <?php elseif (!$all_groups) : ?>
        <p class="juntaplay-profile__empty"><?php echo esc_html__('Você ainda não participa de nenhum grupo. Crie um novo grupo ou participe de outro grupo para aparecer aqui.', 'juntaplay'); ?></p>
    <?php endif; ?>
    </div>

    <script>
        (() => {
            const myGroupsRoot = document.querySelector('.juntaplay-my-groups-grid');

            if (!myGroupsRoot) {
                return;
            }

            let lastChatLink = '';
            let lastChatLabel = '';

            const injectChatCta = (chatLink, chatLabel) => {
                if (!chatLink || !chatLabel) {
                    return;
                }

                const modalCta = document.querySelector('.juntaplay-group-modal__cta');

                if (!modalCta) {
                    return;
                }

                const existing = modalCta.querySelector('[data-chat-cta]');
                if (existing) {
                    existing.href = chatLink;
                    existing.textContent = chatLabel;

                    return;
                }

                const chatButton = document.createElement('a');
                chatButton.className = 'juntaplay-button juntaplay-button--primary juntaplay-button--glass';
                chatButton.href = chatLink;
                chatButton.textContent = chatLabel;
                chatButton.dataset.chatCta = '1';
                modalCta.appendChild(chatButton);
            };

            const tryInjectFromState = () => {
                injectChatCta(lastChatLink, lastChatLabel);
            };

            const markModalDirty = () => {
                document.querySelectorAll('.juntaplay-modal__content').forEach((content) => {
                    if (content instanceof HTMLElement) {
                        delete content.dataset.jpHydrated;
                    }
                });
            };

            const escapeHtml = (value) =>
                (value || '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;');

            let lastCardDetails = '';
            let lastCardHeader = '';

            const buildModalHtml = () => {
                if (!lastCardDetails) return '';

                return `
                    <div class="juntaplay-group-modal__detail">
                        ${lastCardHeader}
                        <div class="juntaplay-group-modal__body">${lastCardDetails}</div>
                    </div>
                `;
            };

            const hydrateGroupModal = () => {
                if (!lastCardDetails) return;

                document.querySelectorAll('.juntaplay-modal__content').forEach((content) => {
                    const parentModal = content.closest('[data-group-create-modal], [data-group-success-modal]');
                    if (parentModal) {
                        return;
                    }

                    if (content instanceof HTMLElement && content.dataset.jpHydrated === '1') {
                        return;
                    }

                    const text = (content.textContent || '').trim().toLowerCase();

                    if (!text || text.includes('carregando informa') || text.includes('carregando informações do grupo')) {
                        content.innerHTML = buildModalHtml();
                        if (content instanceof HTMLElement) {
                            content.dataset.jpHydrated = '1';
                        }
                    }
                });
            };

            let hydrationPending = false;
            const scheduleHydrate = () => {
                if (hydrationPending) return;
                hydrationPending = true;
                requestAnimationFrame(() => {
                    hydrationPending = false;
                    tryInjectFromState();
                    hydrateGroupModal();
                });
            };

            document.addEventListener(
                'click',
                (event) => {
                    const trigger = event.target.closest('[data-jp-group-open]');

                    if (trigger) {
                        const chatLink = (trigger.getAttribute('data-chat-link') || '').trim();
                        const chatLabel = (trigger.getAttribute('data-chat-label') || '').trim();

                        if (chatLink && chatLabel) {
                            lastChatLink = chatLink;
                            lastChatLabel = chatLabel;
                        }

                        scheduleHydrate();
                    }

                    const card = event.target.closest('[data-group-item][data-group-id]');
                    if (!card) {
                        return;
                    }

                    const title = card.querySelector('.juntaplay-service-card__title');
                    const meta = card.querySelector('.juntaplay-service-card__meta');
                    const price = card.querySelector('.juntaplay-group-card__cta-price');
                    const detail = card.querySelector('.juntaplay-group-card__details-extended');

                    lastCardHeader = `
                        <header class="juntaplay-group-modal__header">
                            <div class="juntaplay-group-modal__headline">
                                <h3 class="juntaplay-group-modal__title">${escapeHtml(
                                    title ? title.textContent.trim() : ''
                                )}</h3>
                                ${meta ? `<span class="juntaplay-group-modal__meta">${escapeHtml(meta.textContent.trim())}</span>` : ''}
                                ${price ? `<span class="juntaplay-badge juntaplay-badge--positive">${escapeHtml(price.textContent.trim())}</span>` : ''}
                            </div>
                        </header>`;

                    lastCardDetails = detail ? detail.innerHTML : '';

                    markModalDirty();
                    scheduleHydrate();
                },
                true
            );

            const observer = new MutationObserver((mutations) => {
                const touchesModal = mutations.some((mutation) => {
                    if (mutation.target && mutation.target.closest) {
                        if (mutation.target.closest('.juntaplay-modal')) {
                            return true;
                        }
                    }
                    return Array.from(mutation.addedNodes).some(
                        (node) => node instanceof HTMLElement && node.closest('.juntaplay-modal')
                    );
                });

                if (!touchesModal) {
                    return;
                }

                scheduleHydrate();
            });
            observer.observe(document.body, { childList: true, subtree: true });
        })();
    </script>

    <br><section class="juntaplay-groups__create-card juntaplay-create-hero">
        <div class="juntaplay-create-hero__glow" aria-hidden="true"></div>
        <div class="juntaplay-create-hero__glass">
            <div class="juntaplay-create-hero__intro">
                <h3><?php echo esc_html__('Crie um Grupo', 'juntaplay'); ?></h3>
                <p class="juntaplay-create-hero__lead"><?php echo esc_html__('Quer assumir o controle? Torne-se administrador criando um grupo agora.', 'juntaplay'); ?></p>
                <div class="juntaplay-create-hero__tags">
                    <span><?php echo esc_html__('Simples', 'juntaplay'); ?></span>
                    <span><?php echo esc_html__('Rápido', 'juntaplay'); ?></span>
                    <span><?php echo esc_html__('Seguro', 'juntaplay'); ?></span>
                </div>
            </div>

            <div class="juntaplay-create-hero__cta">
                <button type="button" class="juntaplay-button juntaplay-button--primary juntaplay-button--glow juntaplay-groups__create-trigger" data-group-create-trigger>
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
                class="juntaplay-groups__create-modal juntaplay-create-modal"
                data-group-view-root
                data-group-view-active="<?php echo esc_attr($initial_create_view); ?>"
                data-group-view-default="selector"
                data-group-view-allowed="<?php echo esc_attr($initial_create_allowed); ?>"
            >
                <div class="juntaplay-create-modal__aura" aria-hidden="true"></div>

                <header class="juntaplay-groups__create-header juntaplay-create-modal__header">
    <div class="juntaplay-groups__create-heading">
        <!-- Removido o título Fluxo Imersivo -->
        <!-- Removido o subtítulo textual -->

        <!-- INSERE A IMAGEM AQUI -->
               
        <h3><?php echo esc_html__('Crie agora seu novo grupo.', 'juntaplay'); ?></h3>
    </div>

   
</header>


                <div class="juntaplay-groups__create-body juntaplay-create-body">
                    <div class="juntaplay-create-body__aura" aria-hidden="true"></div>
                    <div class="juntaplay-create-panels">
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
                                        $pool_icon_id = isset($pool['icon_id']) ? (int) $pool['icon_id'] : 0;
                                        $pool_icon   = isset($pool['icon']) ? (string) $pool['icon'] : '';
                                        $pool_thumb  = isset($pool['thumbnail']) ? (string) $pool['thumbnail'] : '';
                                        $pool_thumbnail = $pool_icon !== '' ? $pool_icon : $pool_thumb;
                                        if ($pool_thumbnail === '' && $pool_slug !== '') {
                                            $pool_thumbnail = ServiceIcons::get($pool_slug);
                                        }
                                        if ($pool_thumbnail === '') {
                                            $pool_thumbnail = ServiceIcons::resolve($pool_slug, $pool_title, (string) ($pool['service_url'] ?? ''));
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
                                                data-pool-icon-id="<?php echo esc_attr((string) $pool_icon_id); ?>"
                                                data-pool-icon="<?php echo esc_url($pool_icon); ?>"
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
                                    $pool_icon_id = isset($pool['icon_id']) ? (int) $pool['icon_id'] : 0;
                                    $pool_icon   = isset($pool['icon']) ? (string) $pool['icon'] : '';
                                    $pool_thumb  = isset($pool['thumbnail']) ? (string) $pool['thumbnail'] : '';
                                    $pool_thumbnail = $pool_icon !== '' ? $pool_icon : $pool_thumb;
                                    if ($pool_thumbnail === '' && $pool_slug !== '') {

                                        $pool_thumbnail = ServiceIcons::get($pool_slug);
                                    }
                                    if ($pool_thumbnail === '') {
                                        $pool_thumbnail = ServiceIcons::resolve($pool_slug, $pool_title, (string) ($pool['service_url'] ?? ''));
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
                                        data-pool-icon-id="<?php echo esc_attr((string) $pool_icon_id); ?>"
                                        data-pool-icon="<?php echo esc_url($pool_icon); ?>"
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
                                        placeholder="<?php echo esc_attr__('Ex.: https://youtube.com', 'juntaplay'); ?>"
                                        inputmode="url"
                                        data-group-share-watch
                                        data-group-pool-url
                                    />
                                </div>

                                <div class="juntaplay-form__group juntaplay-form__group--inline">
                                    <div class="juntaplay-cover-field" data-group-cover data-placeholder="<?php echo esc_url($icon_placeholder); ?>">
                                        <label><?php echo esc_html__('Ícone do grupo', 'juntaplay'); ?></label>
                                        <div class="juntaplay-cover-field__preview" data-group-cover-preview>
                                            <img src="<?php echo esc_url($current_icon_url !== '' ? $current_icon_url : $icon_placeholder); ?>" alt="" loading="lazy" />
                                        </div>
                                        <div class="juntaplay-cover-field__actions">
                                            <button type="button" class="juntaplay-button juntaplay-button--ghost" data-group-cover-select>
                                                <?php echo esc_html__('Escolher ícone', 'juntaplay'); ?>
                                            </button>
                                            <button type="button" class="juntaplay-button juntaplay-button--link" data-group-cover-remove <?php disabled($current_icon_id === 0); ?>>
                                                <?php echo esc_html__('Remover', 'juntaplay'); ?>
                                            </button>
                                        </div>
                                        <p class="juntaplay-cover-field__error is-hidden" data-group-cover-error role="alert" aria-live="polite"></p>
                                        <input type="hidden" id="jp-group-icon" name="jp_profile_group_icon" value="<?php echo esc_attr((string) $current_icon_id); ?>" data-group-cover-input />
                                    </div>
                                    <p class="juntaplay-form__help"><?php echo esc_html__('Use um ícone quadrado para representar o grupo. Se você não escolher, aplicaremos o ícone do serviço.', 'juntaplay'); ?></p>
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
                                                        class="juntaplay-form__input juntaplay-rule-builder__textarea"
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
                                                class="juntaplay-form__input juntaplay-rule-builder__textarea"
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
                                    'cohabitants' => '<svg viewBox="0 0 24 24" role="presentation" focusable="false"><path fill="currentColor" d="M4.5 10.5 12 4l7.5 6.5v7.75A1.25 1.25 0 0 1 18.25 19H5.75A1.25 1.25 0 0 1 4.5 18.25Z" opacity=".9" /><path fill="currentColor" d="M9.25 19v-4a2.75 2.75 0 0 1 5.5 0v4Z" opacity=".55" /><path fill="currentColor" d="M10.5 11.25a1.5 1.5 0 1 1 3 0 1.5 1.5 0 0 1-3 0Z" /></svg>',
                                    'family'      => '<svg viewBox="0 0 24 24" role="presentation" focusable="false"><path fill="currentColor" d="M7.5 10.75a2.25 2.25 0 1 1 2.25-2.25 2.25 2.25 0 0 1-2.25 2.25Zm9 0A2.25 2.25 0 1 1 18.75 8.5 2.25 2.25 0 0 1 16.5 10.75Z" /><path fill="currentColor" d="M5.75 19.5v-1.25A3.25 3.25 0 0 1 9 15h.4a2.4 2.4 0 0 1 1.86.9l.74.94.74-.94A2.4 2.4 0 0 1 14.6 15h.4a3.25 3.25 0 0 1 3.25 3.25V19.5Z" opacity=".9" /><path fill="currentColor" d="M11.25 11.9a1.65 1.65 0 1 1 1.5 2.9 1.65 1.65 0 0 1-1.5-2.9Z" opacity=".6" /></svg>',
                                    'friends'     => '<svg viewBox="0 0 24 24" role="presentation" focusable="false"><path fill="currentColor" d="M5 6.5A2.5 2.5 0 0 1 7.5 4h5A2.5 2.5 0 0 1 15 6.5V9l2.25-1a1 1 0 0 1 1.4.9v4.35A2.75 2.75 0 0 1 15.9 16.5H12l-2.1 1.5a.75.75 0 0 1-1.2-.6V16.5H7.5A2.5 2.5 0 0 1 5 14Z" opacity=".9" /><path fill="currentColor" d="M8.25 7.75a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm3.5 0a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Z" opacity=".7" /><path fill="currentColor" d="M9 11.75a2 2 0 0 0 2 2h1.5a1 1 0 0 0 .71-.29l.62-.62a.75.75 0 0 0-1.06-1.06l-.39.38H11a.5.5 0 0 1 0-1h1.25a.75.75 0 0 0 0-1.5H11a2 2 0 0 0-2 2Z" opacity=".55" /></svg>',
                                    'coworkers'   => '<svg viewBox="0 0 24 24" role="presentation" focusable="false"><path fill="currentColor" d="M9.5 4.75A2.75 2.75 0 0 1 12.25 2h-.5A2.75 2.75 0 0 1 14.5 4.75V6h2.75A1.75 1.75 0 0 1 19 7.75V18.5a.75.75 0 0 1-1.08.67L14.25 17H9.75l-3.67 2.17A.75.75 0 0 1 5 18.5V7.75A1.75 1.75 0 0 1 6.75 6H9.5Z" opacity=".9" /><path fill="currentColor" d="M11.75 6V4.75a1 1 0 1 0-2 0V6Zm-3.5 7h7.5v1.5h-7.5Zm0-3h3.25V12h-3.25Zm4.75 0H16V12h-3.25Z" opacity=".6" /></svg>',
                                ];

                                $support_icons = [
                                    'email_whatsapp' => '<svg viewBox="0 0 24 24" role="presentation" focusable="false"><path fill="currentColor" d="M3.75 5.25A2.25 2.25 0 0 1 6 3h8.75A2.75 2.75 0 0 1 17.5 5.75v2.7l2.05-1.13a1 1 0 0 1 1.45.88v6A2.75 2.75 0 0 1 18.25 17h-3.3l-2.34 1.7a.75.75 0 0 1-1.19-.6V17H6A2.25 2.25 0 0 1 3.75 14.75Z" opacity=".22" /><path fill="currentColor" d="M5.5 6.5v7.25L10.3 11a1.5 1.5 0 0 1 1.6 0l4.8 2.75V6.5Z" /><path fill="currentColor" d="M10.25 8.5a.75.75 0 1 1 1.5 0v.35a2.25 2.25 0 0 0 2.25 2.25H15a.75.75 0 0 1 0 1.5h-1a3.75 3.75 0 0 1-3.75-3.75Zm-2.5 10a.75.75 0 0 0-.75.75V21l1.78-.48a.75.75 0 0 0-.4-1.45ZM14.75 17.4a.75.75 0 0 0-.26 1.02l.45.78a.75.75 0 1 0 1.28-.77l-.44-.77a.75.75 0 0 0-1.03-.26Zm2.9-.85a.75.75 0 0 0-.7 1.34l.77.4a.75.75 0 1 0 .68-1.34ZM15 16a.75.75 0 0 0 .55-1.27l-.6-.6a.75.75 0 1 0-1.06 1.06l.6.6A.75.75 0 0 0 15 16Z" opacity=".7" /></svg>',
                                    'email'          => '<svg viewBox="0 0 24 24" role="presentation" focusable="false"><path fill="currentColor" d="M4.5 6A1.5 1.5 0 0 1 6 4.5h12A1.5 1.5 0 0 1 19.5 6v12.75H4.5Z" opacity=".24" /><path fill="currentColor" d="M6 7.25v8.5h12v-8.5l-6 3.8Z" /><path fill="currentColor" d="M6.25 6h11.5L12 9.65Z" opacity=".6" /></svg>',
                                    'whatsapp'       => '<svg viewBox="0 0 24 24" role="presentation" focusable="false"><path fill="currentColor" d="M12 3.25a8.75 8.75 0 0 0-7.7 12.95L3 20.9l4.62-1.23A8.75 8.75 0 1 0 12 3.25Z" /><path fill="currentColor" d="M12 5.25A6.75 6.75 0 0 1 12 18l-2-.6-.63.17-2.06.55.58-2-.22-.57A6.75 6.75 0 0 1 12 5.25Z" opacity=".2" /><path fill="currentColor" d="M10.15 7.9a.75.75 0 0 0-.63.32l-.74 1.08a.9.9 0 0 0-.11.77 6.4 6.4 0 0 0 3.86 3.86.9.9 0 0 0 .77-.11l1.08-.74a.75.75 0 0 0 .32-.63.75.75 0 0 0-.44-.68l-1-.46a.75.75 0 0 0-.9.22l-.27.35a4.49 4.49 0 0 1-1.22-1.22l.35-.27a.75.75 0 0 0 .22-.9l-.46-1a.75.75 0 0 0-.69-.44Z" /></svg>',
                                    'support'        => '<svg viewBox="0 0 24 24" role="presentation" focusable="false"><path fill="currentColor" d="M12 4.25A7.75 7.75 0 0 0 4.25 12v4.75A3 3 0 0 0 7.25 20H9.5v-1.5H7.25a1.5 1.5 0 0 1-1.5-1.5V12a6.25 6.25 0 0 1 12.5 0v5a1.5 1.5 0 0 1-1.5 1.5H15V20h2.75a3 3 0 0 0 3-3V12A7.75 7.75 0 0 0 12 4.25Z" opacity=".85" /><path fill="currentColor" d="M9.75 13.25A2.25 2.25 0 0 1 12 11h0a2.25 2.25 0 0 1 2.25 2.25v.5a2.25 2.25 0 0 1-2.25 2.25H12a1.75 1.75 0 0 0 0 3.5h1.4v-1.5H12A.25.25 0 0 1 12 17h0a3.75 3.75 0 0 0 3.75-3.75v-.5A3.75 3.75 0 0 0 12 9h0A3.75 3.75 0 0 0 8.25 12.75V13a.75.75 0 0 0 1.5 0Z" opacity=".6" /></svg>',
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
                                    'custom'      => '<svg viewBox="0 0 24 24" role="presentation" focusable="false"><path fill="currentColor" d="M5 9.25a2.25 2.25 0 0 1 2.25-2.25H9a1.5 1.5 0 0 1 1.06.44l1 1 1-1A1.5 1.5 0 0 1 13.12 7h1.63A2.25 2.25 0 0 1 17 9.25v.63a3.1 3.1 0 0 1-.9 2.2l-1.95 1.95a1.75 1.75 0 0 1-2.48 0l-.22-.22-.25.25a1.75 1.75 0 0 1-2.48 0l-2-2a3.1 3.1 0 0 1-.72-2Z" opacity=".85" /><path fill="currentColor" d="M7.5 14.1 9 15.6a3.25 3.25 0 0 0 2.3.95 3.1 3.1 0 0 0 2.26-.95l1.95-1.95.7.7a.75.75 0 1 1-1.06 1.06l-.64-.64-1.23 1.23a4.6 4.6 0 0 1-6.5 0l-1.5-1.5a.75.75 0 0 1 1.06-1.06Z" opacity=".6" /></svg>',
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
                                            <div class="juntaplay-choice-grid juntaplay-choice-grid--compact juntaplay-choice-grid--two-cols">
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
                                            <div class="juntaplay-choice-grid juntaplay-choice-grid--compact juntaplay-choice-grid--two-cols">
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
                        </form>
                    </div>
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
