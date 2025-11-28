<?php
/**
 * JuntaPlay user dashboard template.
 */

declare(strict_types=1);

use JuntaPlay\Assets\ServiceIcons;

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('juntaplay_dashboard_icon')) {
    function juntaplay_dashboard_icon(string $icon): string
    {
        $aliases = [
            'explore'     => 'search',
            'credits'     => 'wallet',
            'quotas'      => 'ticket',
            'statement'   => 'invoice',
            'user-circle' => 'user',
            'card'        => 'credit-card',
            'receipt'     => 'invoice',
        ];

        $icon = $aliases[$icon] ?? $icon;

        $icons = [
            'user' => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 12a5 5 0 1 0-5-5 5 5 0 0 0 5 5Zm0 2c-4.418 0-8 2.239-8 5v1h16v-1c0-2.761-3.582-5-8-5Z" fill="currentColor"/></svg>',
            'wallet' => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M20 7V6a2 2 0 0 0-2-2H6a4 4 0 0 0 0 8h12" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M4 10v8a2 2 0 0 0 2 2h13a1 1 0 0 0 1-1v-4.382a1 1 0 0 0-.553-.894L17 12l2.447-1.724A1 1 0 0 0 20 9.382V8a1 1 0 0 0-1-1H6" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>',
            'groups' => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4Zm6 0a3 3 0 1 0-3-3 3 3 0 0 0 3 3Zm-12 0a3 3 0 1 0-3-3 3 3 0 0 0 3 3Zm0 2c-2.21 0-6 1.11-6 3.33V20h6Zm12 0c2.21 0 6 1.11 6 3.33V20h-6Zm-6 0c2.21 0 6 1.11 6 3.33V20H6v-2.67C6 15.11 9.79 14 12 14Z" fill="none" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/></svg>',
            'ticket' => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M4 7a1 1 0 0 1 1-1h14a1 1 0 0 1 1 1v2a2 2 0 0 0 0 4v2a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-2a2 2 0 0 0 0-4Z" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/><path d="M9 5v14" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>',
            'invoice' => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M7 3h10l3 4v13a1 1 0 0 1-1 1H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/><path d="M9 9h6M9 13h6M9 17h3" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>',
            'search' => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="m21 21-4.35-4.35M10.5 18a7.5 7.5 0 1 0 0-15 7.5 7.5 0 0 0 0 15Z" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>',
            'bell' => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 22a2 2 0 0 0 2-2H10a2 2 0 0 0 2 2Zm7-6v-5a7 7 0 0 0-5-6.708V4a2 2 0 1 0-4 0v.292A7.002 7.002 0 0 0 6 11v5l-1.447 2.894A1 1 0 0 0 5.447 20h13.106a1 1 0 0 0 .894-1.447Z" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>',
            'shield' => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 3 4 6v5c0 4.418 3.134 8.94 8 10 4.866-1.06 8-5.582 8-10V6Z" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>',
            'map' => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M9 3 3 5v16l6-2 6 2 6-2V3l-6 2Z" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/><path d="M9 3v16M15 5v16" fill="none" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
            'credit-card' => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><rect x="3" y="5" width="18" height="14" rx="2" ry="2" fill="none" stroke="currentColor" stroke-width="1.5"/><path d="M3 10h18M7 15h2m3 0h2" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>',
            'document' => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M7 2h8l5 5v13a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2Zm8 0v5h5" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>',
            'arrow-right' => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M5 12h14M13 6l6 6-6 6" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        ];

        return $icons[$icon] ?? '';
    }
}

global $wpdb;

$user_id = get_current_user_id();
$user    = wp_get_current_user();
$name    = $user && $user->exists() ? $user->display_name : '';
if ($name === '') {
    $name = $user && $user->exists() ? $user->user_login : '';
}

$hero_defaults = [
    'badge'        => __('', 'juntaplay'),
      ];

$status_rows = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT status, COUNT(*) AS total
         FROM {$wpdb->prefix}jp_quotas
         WHERE user_id = %d
         GROUP BY status",
        $user_id
    ),
    ARRAY_A
);

$quota_totals = [
    'paid'     => 0,
    'reserved' => 0,
    'canceled' => 0,
    'expired'  => 0,
];

if ($status_rows) {
    foreach ($status_rows as $row) {
        $status = $row['status'] ?? '';
        if (isset($quota_totals[$status])) {
            $quota_totals[$status] = (int) ($row['total'] ?? 0);
        }
    }
}

$orders_count = (int) $wpdb->get_var(
    $wpdb->prepare(
        "SELECT COUNT(DISTINCT order_id)
         FROM {$wpdb->prefix}jp_quotas
         WHERE user_id = %d AND order_id IS NOT NULL",
        $user_id
    )
);

$total_spent = (float) $wpdb->get_var(
    $wpdb->prepare(
        "SELECT COALESCE(SUM(p.price), 0)
         FROM {$wpdb->prefix}jp_quotas q
         INNER JOIN {$wpdb->prefix}jp_pools p ON p.id = q.pool_id
         WHERE q.user_id = %d AND q.status = 'paid'",
        $user_id
    )
);

$recommended_pools = $wpdb->get_results(
    "SELECT *
     FROM {$wpdb->prefix}jp_pools
     WHERE status='publish'
     ORDER BY created_at DESC
     LIMIT 4"
);

$user_pools = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT p.id, p.title, p.product_id, p.price,
                SUM(CASE WHEN q.status='paid' THEN 1 ELSE 0 END) AS paid_count,
                SUM(CASE WHEN q.status='reserved' THEN 1 ELSE 0 END) AS reserved_count,
                COUNT(*) AS total_count,
                MAX(q.created_at) AS last_activity
         FROM {$wpdb->prefix}jp_quotas q
         INNER JOIN {$wpdb->prefix}jp_pools p ON p.id = q.pool_id
         WHERE q.user_id = %d
         GROUP BY p.id, p.title, p.product_id, p.price
         ORDER BY last_activity DESC
         LIMIT 3",
        $user_id
    )
);

$my_quotas_id = (int) get_option('juntaplay_page_minhas-cotas');
$my_quotas_url = $my_quotas_id ? get_permalink($my_quotas_id) : '';
if (!$my_quotas_url) {
    $my_quotas_url = home_url('/minhas-cotas');
}

$extrato_id = (int) get_option('juntaplay_page_extrato');
$extrato_url = $extrato_id ? get_permalink($extrato_id) : '';
$myaccount_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('myaccount') : home_url('/minha-conta');

$account_base = $myaccount_url ?: home_url('/minha-conta');
$profile_id   = (int) get_option('juntaplay_page_perfil');
$profile_url  = $profile_id ? get_permalink($profile_id) : '';
if (!$profile_url) {
    $profile_url = $account_base;
}

$groups_page_url = apply_filters('juntaplay/dashboard/groups_url', $profile_url, $user);

$quick_actions = apply_filters(
    'juntaplay/dashboard/actions',
    [
        [
            'label' => __('Explorar campanhas', 'juntaplay'),
            'href'  => $hero['cta_url'] ?? $hero_defaults['cta_url'],
            'icon'  => 'explore',
        ],
        [
            'label'      => __('Adicionar créditos', 'juntaplay'),
            'href'       => '#',
            'icon'       => 'credits',
            'attributes' => [
                'data-jp-credit-topup' => 'true',
            ],
        ],
        [
            'label' => __('Meus grupos', 'juntaplay'),
            'href'  => $groups_page_url,
            'icon'  => 'groups',
        ],
        [
            'label' => __('Minhas cotas', 'juntaplay'),
            'href'  => $my_quotas_url,
            'icon'  => 'quotas',
        ],
        [
            'label' => __('Extrato e pagamentos', 'juntaplay'),
            'href'  => $extrato_url ?: $myaccount_url,
            'icon'  => 'statement',
        ],
    ],
    $user
);

$account_sections = apply_filters(
    'juntaplay/dashboard/sections',
    [
        [
            'key'          => 'settings',
            'title'       => __('Configurações', 'juntaplay'),
            'description' => __('Atualize informações da conta e preferências.', 'juntaplay'),
            'items'       => [
                [
                    'label'       => __('Meu perfil', 'juntaplay'),
                    'description' => __('Nome, CPF e dados básicos.', 'juntaplay'),
                    'href'        => $profile_url,
                    'icon'        => 'user-circle',
                ],
                [
                    'label'       => __('Endereços', 'juntaplay'),
                    'description' => __('Entrega e cobrança.', 'juntaplay'),
                    'href'        => function_exists('wc_get_account_endpoint_url') ? wc_get_account_endpoint_url('edit-address') : $account_base,
                    'icon'        => 'map',
                ],
                [
                    'label'       => __('Segurança', 'juntaplay'),
                    'description' => __('Senha, 2FA e login social.', 'juntaplay'),
                    'href'        => $account_base,
                    'icon'        => 'shield',
                ],
                [
                    'label'       => __('Comunicações', 'juntaplay'),
                    'description' => __('Controle notificações e e-mails.', 'juntaplay'),
                    'href'        => $account_base,
                    'icon'        => 'bell',
                ],
            ],
        ],
        [
            'key'          => 'finance',
            'title'       => __('Financeiro', 'juntaplay'),
            'description' => __('Acompanhe pagamentos, extratos e saldos.', 'juntaplay'),
            'items'       => [
                [
                    'label'       => __('Pedidos', 'juntaplay'),
                    'description' => __('Histórico de compras e notas.', 'juntaplay'),
                    'href'        => function_exists('wc_get_account_endpoint_url') ? wc_get_account_endpoint_url('orders') : $account_base,
                    'icon'        => 'receipt',
                ],
                [
                    'label'       => __('Extrato de cotas', 'juntaplay'),
                    'description' => __('Detalhe dos pagamentos aprovados.', 'juntaplay'),
                    'href'        => $extrato_url ?: $account_base,
                    'icon'        => 'document',
                ],
                [
                    'label'       => __('Carteira e créditos', 'juntaplay'),
                    'description' => __('Depósitos, bônus e retiradas.', 'juntaplay'),
                    'href'        => $profile_url,
                    'icon'        => 'card',
                ],
                [
                    'label'       => __('Meios de pagamento', 'juntaplay'),
                    'description' => __('Gerencie cartões e Pix.', 'juntaplay'),
                    'href'        => function_exists('wc_get_account_endpoint_url') ? wc_get_account_endpoint_url('payment-methods') : $account_base,
                    'icon'        => 'ticket',
                ],
            ],
        ],
    ],
    $user
);

$account_sections_config  = [];
$account_sections_finance = [];

foreach ($account_sections as $section) {
    if (!is_array($section)) {
        continue;
    }

    $key   = isset($section['key']) ? (string) $section['key'] : '';
    $title = isset($section['title']) ? strtolower(wp_strip_all_tags((string) $section['title'])) : '';

    if ($key === 'finance' || ($key === '' && str_contains($title, 'finan'))) {
        $account_sections_finance[] = $section;
    } else {
        $account_sections_config[] = $section;
    }
}

$notifications_unread = \JuntaPlay\Data\Notifications::count_unread($user_id);

$groups_data        = \JuntaPlay\Data\Groups::get_groups_for_user($user_id);
$groups_owned_full  = isset($groups_data['owned']) && is_array($groups_data['owned']) ? $groups_data['owned'] : [];
$groups_member_full = isset($groups_data['member']) && is_array($groups_data['member']) ? $groups_data['member'] : [];
$groups_owned       = array_slice($groups_owned_full, 0, 4);
$groups_member      = array_slice($groups_member_full, 0, 4);
$groups_owned_total = count($groups_owned_full);
$groups_member_total = count($groups_member_full);
$groups_total       = $groups_owned_total + $groups_member_total;

$credit_balance          = (float) get_user_meta($user_id, 'juntaplay_credit_balance', true);
$credit_reserved         = (float) get_user_meta($user_id, 'juntaplay_credit_reserved', true);
$credit_bonus            = (float) get_user_meta($user_id, 'juntaplay_credit_bonus', true);
$credit_withdraw_pending = (float) get_user_meta($user_id, 'juntaplay_credit_withdraw_pending', true);
$credit_updated_at       = (string) get_user_meta($user_id, 'juntaplay_credit_updated_at', true);
$credit_last_recharge    = (string) get_user_meta($user_id, 'juntaplay_credit_last_recharge', true);

$credit_updated_diff = '';
if ($credit_updated_at !== '') {
    $updated_timestamp = strtotime($credit_updated_at);
    if ($updated_timestamp) {
        $credit_updated_diff = human_time_diff($updated_timestamp, current_time('timestamp'));
    }
}

$credit_recharge_diff = '';
if ($credit_last_recharge !== '') {
    $recharge_timestamp = strtotime($credit_last_recharge);
    if ($recharge_timestamp) {
        $credit_recharge_diff = human_time_diff($recharge_timestamp, current_time('timestamp'));
    }
}

$format_money = static function (float $value): string {
    if (function_exists('wc_price')) {
        return wp_kses_post(wc_price($value));
    }

    $formatted = number_format_i18n($value, 2);

    return '<span class="juntaplay-money">R$ ' . esc_html($formatted) . '</span>';
};

$overview_badge = array_sum($quota_totals);
$finance_badge  = number_format_i18n($credit_balance, 2);
$groups_badge   = $groups_total;
$account_badge  = $notifications_unread;

$dashboard_tabs = [
    'overview' => [
        'label' => __('Visão geral', 'juntaplay'),
        'icon'  => 'overview',
        'badge' => (string) $overview_badge,
    ],
    'finance'  => [
        'label' => __('Financeiro', 'juntaplay'),
        'icon'  => 'finance',
        'badge' => 'R$ ' . $finance_badge,
    ],
    'groups'   => [
        'label' => __('Meus grupos', 'juntaplay'),
        'icon'  => 'groups',
        'badge' => (string) $groups_badge,
    ],
    'account'  => [
        'label' => __('Minha conta', 'juntaplay'),
        'icon'  => 'account',
        'badge' => $account_badge > 0 ? (string) $account_badge : '',
    ],
];

$dashboard_tabs = apply_filters('juntaplay/dashboard/tabs', $dashboard_tabs, $user);

if (!isset($dashboard_tabs['overview'])) {
    $dashboard_tabs = array_merge(['overview' => [
        'label' => __('Visão geral', 'juntaplay'),
        'icon'  => 'overview',
    ]], $dashboard_tabs);
}

$active_tab = array_key_first($dashboard_tabs);
if (!$active_tab) {
    $active_tab = 'overview';
}

$requested_tab = '';
foreach (['jp_tab', 'tab', 'section'] as $tab_param) {
    if (isset($_GET[$tab_param])) {
        $requested_tab = sanitize_key((string) wp_unslash($_GET[$tab_param]));
        break;
    }
}

if ($requested_tab && isset($dashboard_tabs[$requested_tab])) {
    $active_tab = $requested_tab;
}

$group_status_labels = [
    \JuntaPlay\Data\Groups::STATUS_PENDING  => __('Em análise', 'juntaplay'),
    \JuntaPlay\Data\Groups::STATUS_APPROVED => __('Ativo', 'juntaplay'),
    \JuntaPlay\Data\Groups::STATUS_REJECTED => __('Recusado', 'juntaplay'),
    \JuntaPlay\Data\Groups::STATUS_ARCHIVED => __('Arquivado', 'juntaplay'),
];

$group_role_labels = [
    'owner'   => __('Administrador', 'juntaplay'),
    'manager' => __('Co-administrador', 'juntaplay'),
    'member'  => __('Participante', 'juntaplay'),
];

$group_categories = \JuntaPlay\Data\Groups::get_category_labels();
$avatar_url       = '';
$custom_avatar    = get_user_meta($user_id, 'juntaplay_avatar_url', true);

if (is_string($custom_avatar)) {
    $custom_avatar = trim($custom_avatar);
}

if (is_string($custom_avatar) && $custom_avatar !== '') {
    $avatar_url = esc_url_raw($custom_avatar);
}

if ($avatar_url === '') {
    $avatar_id = get_user_meta($user_id, 'juntaplay_avatar_id', true);
    if ($avatar_id) {
        $maybe_url = wp_get_attachment_image_url((int) $avatar_id, 'thumbnail');
        if (is_string($maybe_url) && $maybe_url !== '') {
            $avatar_url = $maybe_url;
        }
    }
}

if ($avatar_url === '') {
    $avatar_url = get_avatar_url($user_id, ['size' => 160]);
}
$display_email    = $user && $user->exists() ? (string) $user->user_email : '';
?>
<div class="juntaplay-dashboard juntaplay-section" data-dashboard>
    <header class="juntaplay-dashboard__header" data-jp-notifications-root>
        <div class="juntaplay-dashboard__profile">
            <span class="juntaplay-dashboard__avatar" aria-hidden="true">
                <img src="<?php echo esc_url($avatar_url); ?>" alt="<?php echo esc_attr(sprintf(__('Avatar de %s', 'juntaplay'), $name ? wp_strip_all_tags($name) : __('assinante', 'juntaplay'))); ?>" loading="lazy" />
            </span>
            <div class="juntaplay-dashboard__profile-copy">
                <span class="juntaplay-dashboard__welcome"><?php esc_html_e('Painel do assinante', 'juntaplay'); ?></span>
                <?php if ($name) : ?>
                    <h1 class="juntaplay-dashboard__title"><?php echo esc_html(wp_strip_all_tags($name)); ?></h1>
                <?php endif; ?>
                <?php if ($display_email !== '') : ?>
                    <p class="juntaplay-dashboard__subtitle"><?php echo esc_html($display_email); ?></p>
                <?php endif; ?>
            </div>
            <div class="juntaplay-dashboard__profile-actions">
                <?php if ($profile_url) : ?>
                    <a class="juntaplay-button juntaplay-button--ghost" href="<?php echo esc_url($profile_url); ?>"><?php esc_html_e('Editar perfil', 'juntaplay'); ?></a>
                <?php endif; ?>
                <button type="button" class="juntaplay-notification-bell" data-jp-notifications aria-haspopup="true" aria-expanded="false"<?php if ($notifications_unread > 0) : ?> data-count="<?php echo esc_attr($notifications_unread); ?>"<?php endif; ?>>
                    <span class="screen-reader-text"><?php esc_html_e('Abrir notificações', 'juntaplay'); ?></span>
                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                        <path d="M12 22a2 2 0 0 0 1.995-1.85L14 20h-4a2 2 0 0 0 1.85 1.995L12 22Zm7-6v-5a7 7 0 0 0-5-6.708V4a2 2 0 1 0-4 0v.292A7.002 7.002 0 0 0 6 11v5l-1.447 2.894A1 1 0 0 0 5.447 20h13.106a1 1 0 0 0 .894-1.447Z" fill="none" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </button>
            </div>
        </div>
        <ul class="juntaplay-dashboard__snapshot" aria-label="<?php esc_attr_e('Resumo rápido', 'juntaplay'); ?>">
            <li>
                <span><?php esc_html_e('Saldo disponível', 'juntaplay'); ?></span>
                <strong><?php echo wp_kses_post($format_money($credit_balance)); ?></strong>
            </li>
            <li>
                <span><?php esc_html_e('Reservado', 'juntaplay'); ?></span>
                <strong><?php echo wp_kses_post($format_money($credit_reserved)); ?></strong>
            </li>
            <li>
                <span><?php esc_html_e('Cotas pagas', 'juntaplay'); ?></span>
                <strong><?php echo esc_html(number_format_i18n($quota_totals['paid'])); ?></strong>
            </li>
        </ul>
        <div class="juntaplay-notifications" data-jp-notifications-panel aria-hidden="true">
            <div class="juntaplay-notifications__header">
                <h4><?php esc_html_e('Notificações', 'juntaplay'); ?></h4>
            </div>
            <ul class="juntaplay-notifications__list" data-jp-notifications-list>
                <li class="juntaplay-notifications__empty"><?php esc_html_e('Carregando notificações...', 'juntaplay'); ?></li>
            </ul>
            <div class="juntaplay-notifications__footer">
                <button type="button" data-jp-notifications-close><?php esc_html_e('Fechar', 'juntaplay'); ?></button>
            </div>
        </div>
    </header>
    <?php if (!empty($quick_actions)) : ?>
        <nav class="juntaplay-dashboard__menu" aria-label="<?php esc_attr_e('Acessos rápidos', 'juntaplay'); ?>">
            <?php foreach ($quick_actions as $action) :
                if (!is_array($action)) {
                    continue;
                }
                $label = isset($action['label']) ? (string) $action['label'] : '';
                if ($label === '') {
                    continue;
                }
                $href        = isset($action['href']) ? (string) $action['href'] : '#';
                $icon        = isset($action['icon']) ? (string) $action['icon'] : '';
                $attributes  = isset($action['attributes']) && is_array($action['attributes']) ? $action['attributes'] : [];
                ?>
                <a class="juntaplay-dashboard__menu-item" href="<?php echo esc_url($href); ?>"<?php
                    foreach ($attributes as $attr_key => $attr_value) {
                        echo ' ' . esc_attr($attr_key) . '="' . esc_attr((string) $attr_value) . '"';
                    }
                ?>>
                    <?php if ($icon) : ?>
                        <span class="juntaplay-dashboard__menu-icon juntaplay-dashboard__menu-icon--<?php echo esc_attr($icon); ?>" aria-hidden="true"></span>
                    <?php endif; ?>
                    <span class="juntaplay-dashboard__menu-label"><?php echo esc_html($label); ?></span>
                </a>
            <?php endforeach; ?>
        </nav>
    <?php endif; ?>
    <div class="juntaplay-dashboard__surface">
        <?php if (!empty($dashboard_tabs)) : ?>
            <nav class="juntaplay-dashboard__tabs" role="tablist" aria-orientation="horizontal">
                <?php foreach ($dashboard_tabs as $tab_slug => $tab) :
                    $tab_slug   = (string) $tab_slug;
                    $tab_label  = isset($tab['label']) ? (string) $tab['label'] : '';
                    if ($tab_label === '') {
                        continue;
                    }
                    $tab_icon   = isset($tab['icon']) ? (string) $tab['icon'] : '';
                    $tab_badge  = isset($tab['badge']) ? trim((string) $tab['badge']) : '';
                    $tab_id     = 'juntaplay-dashboard-tab-' . sanitize_html_class($tab_slug);
                    $panel_id   = 'juntaplay-dashboard-panel-' . sanitize_html_class($tab_slug);
                    $is_active  = $tab_slug === $active_tab;
                    ?>
                    <button type="button"
                        class="juntaplay-dashboard__tab<?php echo $is_active ? ' is-active' : ''; ?>"
                        id="<?php echo esc_attr($tab_id); ?>"
                        role="tab"
                        data-dashboard-tab="<?php echo esc_attr($tab_slug); ?>"
                        aria-selected="<?php echo $is_active ? 'true' : 'false'; ?>"
                        aria-controls="<?php echo esc_attr($panel_id); ?>">
                        <span class="juntaplay-dashboard__tab-inner">
                            <?php if ($tab_icon) : ?>
                                <span class="juntaplay-dashboard__tab-icon juntaplay-dashboard__tab-icon--<?php echo esc_attr($tab_icon); ?>" aria-hidden="true"></span>
                            <?php endif; ?>
                            <span class="juntaplay-dashboard__tab-label"><?php echo esc_html($tab_label); ?></span>
                            <?php if ($tab_badge !== '') : ?>
                                <span class="juntaplay-dashboard__tab-badge"><?php echo esc_html($tab_badge); ?></span>
                            <?php endif; ?>
                        </span>
                    </button>
                <?php endforeach; ?>
            </nav>
        <?php endif; ?>

        <div class="juntaplay-dashboard__tab-panels">
        <section class="juntaplay-dashboard__tab-panel<?php echo $active_tab === 'overview' ? ' is-active' : ''; ?>" role="tabpanel" id="juntaplay-dashboard-panel-overview" aria-labelledby="juntaplay-dashboard-tab-overview" aria-hidden="<?php echo $active_tab === 'overview' ? 'false' : 'true'; ?>" data-dashboard-panel="overview">
            <section class="juntaplay-dashboard__hero">
                <div class="juntaplay-dashboard__hero-copy">
                    <?php if (!empty($hero['badge'])) : ?>
                        <span class="juntaplay-dashboard__badge"><?php echo esc_html((string) $hero['badge']); ?></span>
                    <?php endif; ?>
                    <h1><?php echo esc_html(wp_strip_all_tags((string) ($hero['title'] ?? $hero_defaults['title']))); ?></h1>
                    <p class="juntaplay-dashboard__lead"><?php echo esc_html((string) ($hero['description'] ?? $hero_defaults['description'])); ?></p>
                    <?php if (!empty($hero['secondary'])) : ?>
                        <p class="juntaplay-dashboard__sub"><?php echo esc_html((string) ($hero['secondary'] ?? '')); ?></p>
                    <?php endif; ?>
                    <div class="juntaplay-dashboard__cta">
                        <?php if (!empty($hero['cta_url'])) : ?>
                            <a class="juntaplay-button juntaplay-button--primary" href="<?php echo esc_url((string) $hero['cta_url']); ?>">
                                <?php echo esc_html((string) ($hero['cta_label'] ?? $hero_defaults['cta_label'])); ?>
                            </a>
                        <?php endif; ?>
                        <a class="juntaplay-link" href="<?php echo esc_url($my_quotas_url); ?>"><?php esc_html_e('Ver minhas cotas', 'juntaplay'); ?></a>
                    </div>
                </div>
               
            </section>

            <section class="juntaplay-profile__group juntaplay-dashboard__overview-group" aria-label="<?php esc_attr_e('Resumo da sua conta', 'juntaplay'); ?>">
                <header class="juntaplay-profile__group-header">
                    <h2 class="juntaplay-profile__group-title"><?php esc_html_e('Resumo da conta', 'juntaplay'); ?></h2>
                    <p class="juntaplay-profile__group-description"><?php esc_html_e('Visualize rapidamente suas participações e valores.', 'juntaplay'); ?></p>
                </header>
                <div class="juntaplay-profile__summary">
                    <article class="juntaplay-profile__summary-item">
                        <span class="juntaplay-profile__summary-label"><?php esc_html_e('Cotas pagas', 'juntaplay'); ?></span>
                        <span class="juntaplay-profile__summary-value"><?php echo esc_html(number_format_i18n($quota_totals['paid'])); ?></span>
                        <span class="juntaplay-profile__summary-hint"><?php esc_html_e('Participações confirmadas', 'juntaplay'); ?></span>
                    </article>
                    <article class="juntaplay-profile__summary-item">
                        <span class="juntaplay-profile__summary-label"><?php esc_html_e('Reservas ativas', 'juntaplay'); ?></span>
                        <span class="juntaplay-profile__summary-value"><?php echo esc_html(number_format_i18n($quota_totals['reserved'])); ?></span>
                        <span class="juntaplay-profile__summary-hint"><?php esc_html_e('Garanta a compra antes do prazo expirar.', 'juntaplay'); ?></span>
                    </article>
                    <article class="juntaplay-profile__summary-item">
                        <span class="juntaplay-profile__summary-label"><?php esc_html_e('Pedidos realizados', 'juntaplay'); ?></span>
                        <span class="juntaplay-profile__summary-value"><?php echo esc_html(number_format_i18n($orders_count)); ?></span>
                        <span class="juntaplay-profile__summary-hint"><?php esc_html_e('Pagamentos e extratos em dia.', 'juntaplay'); ?></span>
                    </article>
                    <article class="juntaplay-profile__summary-item">
                        <span class="juntaplay-profile__summary-label"><?php esc_html_e('Total investido', 'juntaplay'); ?></span>
                        <span class="juntaplay-profile__summary-value juntaplay-dashboard__summary-value--currency"><?php echo wp_kses_post($format_money($total_spent)); ?></span>
                        <span class="juntaplay-profile__summary-hint"><?php esc_html_e('Somente cotas pagas contam aqui.', 'juntaplay'); ?></span>
                    </article>
                </div>
            </section>

            <?php if (!empty($quick_actions)) : ?>
                <section class="juntaplay-profile__group juntaplay-dashboard__overview-group" aria-label="<?php esc_attr_e('Ações rápidas', 'juntaplay'); ?>">
                    <header class="juntaplay-profile__group-header">
                        <h2 class="juntaplay-profile__group-title"><?php esc_html_e('Atalhos rápidos', 'juntaplay'); ?></h2>
                        <p class="juntaplay-profile__group-description"><?php esc_html_e('Acesse as tarefas mais importantes sem sair do painel.', 'juntaplay'); ?></p>
                    </header>
                    <div class="juntaplay-profile__quick-nav">
                        <?php foreach ($quick_actions as $action) :
                            if (!is_array($action)) {
                                continue;
                            }
                            $href       = isset($action['href']) ? (string) $action['href'] : '#';
                            $label      = isset($action['label']) ? (string) $action['label'] : '';
                            $icon_name  = isset($action['icon']) ? (string) $action['icon'] : '';
                            $icon_markup = $icon_name !== '' ? juntaplay_dashboard_icon($icon_name) : '';
                            $attrs      = '';

                            if (!empty($action['attributes']) && is_array($action['attributes'])) {
                                foreach ($action['attributes'] as $attr_key => $attr_value) {
                                    $attr_key = trim((string) $attr_key);
                                    if ($attr_key === '') {
                                        continue;
                                    }
                                    if ($attr_value === true) {
                                        $attr_value = 'true';
                                    } elseif ($attr_value === false) {
                                        $attr_value = 'false';
                                    }
                                    $attrs .= sprintf(' %s="%s"', esc_attr($attr_key), esc_attr((string) $attr_value));
                                }
                            }

                            if ($label === '') {
                                continue;
                            }
                            ?>
                            <a class="juntaplay-profile__quick-card juntaplay-dashboard__quick-link" href="<?php echo esc_url($href); ?>"<?php echo $attrs; ?>>
                                <?php if ($icon_markup !== '') : ?>
                                    <span class="juntaplay-profile__quick-icon"><?php echo $icon_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
                                <?php endif; ?>
                                <span class="juntaplay-profile__quick-text">
                                    <span class="juntaplay-profile__quick-label"><?php echo esc_html($label); ?></span>
                                </span>
                                <span class="juntaplay-dashboard__quick-arrow" aria-hidden="true"><?php echo juntaplay_dashboard_icon('arrow-right'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

            <section class="juntaplay-profile__group juntaplay-dashboard__overview-group">
                <header class="juntaplay-profile__group-header">
                    <h2 class="juntaplay-profile__group-title"><?php esc_html_e('Selecionados para você', 'juntaplay'); ?></h2>
                    <p class="juntaplay-profile__group-description"><?php esc_html_e('Campanhas alinhadas ao seu perfil e prontas para entrar.', 'juntaplay'); ?></p>
                    <a class="juntaplay-link juntaplay-dashboard__group-link" href="<?php echo esc_url($hero['cta_url'] ?? $hero_defaults['cta_url']); ?>"><?php esc_html_e('Ver todas', 'juntaplay'); ?></a>
                </header>
                <?php if ($recommended_pools) : ?>
                    <div class="juntaplay-dashboard__overview-grid">
                        <?php foreach ($recommended_pools as $pool) :
                            $permalink = $pool->product_id ? get_permalink((int) $pool->product_id) : '';
                            $price     = isset($pool->price) ? (float) $pool->price : 0.0;
                            $price_str = function_exists('wc_price') ? wc_price($price) : sprintf('R$ %s', esc_html(number_format_i18n($price, 2)));
                            $total     = (int) ($pool->quotas_total ?? 0);
                            $paid      = (int) ($pool->quotas_paid ?? 0);
                            $available = max(0, $total - $paid);
                            $progress  = $total > 0 ? min(100, (int) round(($paid / $total) * 100)) : 0;
                            ?>
                            <article class="juntaplay-dashboard__card juntaplay-dashboard__card--glass">
                                <header class="juntaplay-dashboard__card-header">
                                    <h3><?php echo esc_html($pool->title); ?></h3>
                                    <span class="juntaplay-badge"><?php esc_html_e('Campanha ativa', 'juntaplay'); ?></span>
                                </header>
                                <p class="juntaplay-dashboard__card-price"><?php echo wp_kses_post(sprintf(__('Cota a partir de %s', 'juntaplay'), $price_str)); ?></p>
                                <div class="juntaplay-progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?php echo esc_attr((string) $progress); ?>">
                                    <span class="juntaplay-progress__bar" style="width: <?php echo esc_attr((string) $progress); ?>%;"></span>
                                </div>
                                <ul class="juntaplay-dashboard__metrics">
                                    <li>
                                        <span><?php esc_html_e('Disponíveis', 'juntaplay'); ?></span>
                                        <strong><?php echo esc_html(number_format_i18n($available)); ?></strong>
                                    </li>
                                    <li>
                                        <span><?php esc_html_e('Vendidas', 'juntaplay'); ?></span>
                                        <strong><?php echo esc_html(number_format_i18n($paid)); ?></strong>
                                    </li>
                                </ul>
                                <?php if ($permalink) : ?>
                                    <a class="juntaplay-link juntaplay-dashboard__card-cta" href="<?php echo esc_url($permalink); ?>"><?php esc_html_e('Ver campanha', 'juntaplay'); ?></a>
                                <?php endif; ?>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php else : ?>
                    <p class="juntaplay-profile__empty"><?php esc_html_e('Nenhuma campanha disponível no momento.', 'juntaplay'); ?></p>
                <?php endif; ?>
            </section>

            <section class="juntaplay-profile__group juntaplay-dashboard__overview-group">
                <header class="juntaplay-profile__group-header">
                    <h2 class="juntaplay-profile__group-title"><?php esc_html_e('Suas campanhas', 'juntaplay'); ?></h2>
                    <p class="juntaplay-profile__group-description"><?php esc_html_e('Acompanhe as campanhas em que você já participa.', 'juntaplay'); ?></p>
                    <a class="juntaplay-link juntaplay-dashboard__group-link" href="<?php echo esc_url($my_quotas_url); ?>"><?php esc_html_e('Ver todas as cotas', 'juntaplay'); ?></a>
                </header>
                <?php if ($user_pools) : ?>
                    <div class="juntaplay-dashboard__overview-list">
                        <?php foreach ($user_pools as $pool) :
                            $permalink = $pool->product_id ? get_permalink((int) $pool->product_id) : '';
                            $numbers   = (int) $pool->total_count;
                            $paid      = (int) $pool->paid_count;
                            $reserved  = (int) $pool->reserved_count;
                            $progress  = $numbers > 0 ? min(100, (int) round(($paid / $numbers) * 100)) : 0;
                            ?>
                            <article class="juntaplay-dashboard__item juntaplay-dashboard__item--glass">
                                <div class="juntaplay-dashboard__item-head">
                                    <h3><?php echo esc_html($pool->title); ?></h3>
                                    <?php if ($permalink) : ?>
                                        <a class="juntaplay-link" href="<?php echo esc_url($permalink); ?>"><?php esc_html_e('Ver campanha', 'juntaplay'); ?></a>
                                    <?php endif; ?>
                                </div>
                                <div class="juntaplay-dashboard__item-body">
                                    <div class="juntaplay-dashboard__item-progress">
                                        <div class="juntaplay-progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?php echo esc_attr((string) $progress); ?>">
                                            <span class="juntaplay-progress__bar" style="width: <?php echo esc_attr((string) $progress); ?>%;"></span>
                                        </div>
                                        <span><?php echo esc_html(sprintf(_n('%s cota ativa', '%s cotas ativas', $paid, 'juntaplay'), number_format_i18n($paid))); ?></span>
                                    </div>
                                    <ul class="juntaplay-dashboard__item-stats">
                                        <li>
                                            <span><?php esc_html_e('Reservadas', 'juntaplay'); ?></span>
                                            <strong><?php echo esc_html(number_format_i18n($reserved)); ?></strong>
                                        </li>
                                        <li>
                                            <span><?php esc_html_e('Pagas', 'juntaplay'); ?></span>
                                            <strong><?php echo esc_html(number_format_i18n($paid)); ?></strong>
                                        </li>
                                        <li>
                                            <span><?php esc_html_e('Total', 'juntaplay'); ?></span>
                                            <strong><?php echo esc_html(number_format_i18n($numbers)); ?></strong>
                                        </li>
                                    </ul>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php else : ?>
                    <p class="juntaplay-profile__empty"><?php esc_html_e('Você ainda não possui cotas. Que tal começar agora mesmo?', 'juntaplay'); ?></p>
                <?php endif; ?>
            </section>
        </section>

        <section class="juntaplay-dashboard__tab-panel<?php echo $active_tab === 'finance' ? ' is-active' : ''; ?>" role="tabpanel" id="juntaplay-dashboard-panel-finance" aria-labelledby="juntaplay-dashboard-tab-finance" aria-hidden="<?php echo $active_tab === 'finance' ? 'false' : 'true'; ?>" data-dashboard-panel="finance">
            <header class="juntaplay-dashboard__panel-header">
                <div>
                    <h2><?php esc_html_e('Carteira e pagamentos', 'juntaplay'); ?></h2>
                    <p><?php esc_html_e('Controle seus créditos, retiradas e pagamentos em um só lugar.', 'juntaplay'); ?></p>
                </div>
            </header>
            <section class="juntaplay-dashboard__stats juntaplay-dashboard__stats--finance" aria-label="<?php esc_attr_e('Resumo financeiro', 'juntaplay'); ?>">
                <article class="juntaplay-dashboard__stat juntaplay-dashboard__stat--highlight">
                    <span class="juntaplay-dashboard__stat-label"><?php esc_html_e('Saldo disponível', 'juntaplay'); ?></span>
                    <strong class="juntaplay-dashboard__stat-value juntaplay-dashboard__stat-value--currency"><?php echo $format_money($credit_balance); ?></strong>
                    <?php if ($credit_updated_diff) : ?>
                        <span class="juntaplay-dashboard__stat-caption"><?php printf(esc_html__('Atualizado há %s', 'juntaplay'), esc_html($credit_updated_diff)); ?></span>
                    <?php endif; ?>
                </article>
                <article class="juntaplay-dashboard__stat">
                    <span class="juntaplay-dashboard__stat-label"><?php esc_html_e('Reservado', 'juntaplay'); ?></span>
                    <strong class="juntaplay-dashboard__stat-value juntaplay-dashboard__stat-value--currency"><?php echo $format_money($credit_reserved); ?></strong>
                    <span class="juntaplay-dashboard__stat-caption"><?php esc_html_e('Bloqueado em compras em andamento.', 'juntaplay'); ?></span>
                </article>
                <article class="juntaplay-dashboard__stat">
                    <span class="juntaplay-dashboard__stat-label"><?php esc_html_e('Bônus disponível', 'juntaplay'); ?></span>
                    <strong class="juntaplay-dashboard__stat-value juntaplay-dashboard__stat-value--currency"><?php echo $format_money($credit_bonus); ?></strong>
                    <span class="juntaplay-dashboard__stat-caption"><?php esc_html_e('Aplique em novas participações.', 'juntaplay'); ?></span>
                </article>
                <article class="juntaplay-dashboard__stat">
                    <span class="juntaplay-dashboard__stat-label"><?php esc_html_e('Saques pendentes', 'juntaplay'); ?></span>
                    <strong class="juntaplay-dashboard__stat-value juntaplay-dashboard__stat-value--currency"><?php echo $format_money($credit_withdraw_pending); ?></strong>
                    <span class="juntaplay-dashboard__stat-caption"><?php esc_html_e('Aguardando liberação da equipe financeira.', 'juntaplay'); ?></span>
                </article>
                <article class="juntaplay-dashboard__stat">
                    <span class="juntaplay-dashboard__stat-label"><?php esc_html_e('Total investido', 'juntaplay'); ?></span>
                    <strong class="juntaplay-dashboard__stat-value juntaplay-dashboard__stat-value--currency"><?php echo $format_money($total_spent); ?></strong>
                    <?php if ($credit_recharge_diff) : ?>
                        <span class="juntaplay-dashboard__stat-caption"><?php printf(esc_html__('Última recarga há %s', 'juntaplay'), esc_html($credit_recharge_diff)); ?></span>
                    <?php endif; ?>
                </article>
            </section>

            <?php if ($account_sections_finance) : ?>
                <section class="juntaplay-dashboard__nav" aria-label="<?php esc_attr_e('Atalhos financeiros', 'juntaplay'); ?>">
                    <?php foreach ($account_sections_finance as $section) :
                        $section_title = isset($section['title']) ? (string) $section['title'] : '';
                        $section_desc  = isset($section['description']) ? (string) $section['description'] : '';
                        $items         = isset($section['items']) && is_array($section['items']) ? $section['items'] : [];
                        if (!$items) {
                            continue;
                        }
                        ?>
                        <article class="juntaplay-dashboard__nav-card">
                            <header>
                                <?php if ($section_title) : ?>
                                    <h2><?php echo esc_html($section_title); ?></h2>
                                <?php endif; ?>
                                <?php if ($section_desc) : ?>
                                    <p><?php echo esc_html($section_desc); ?></p>
                                <?php endif; ?>
                            </header>
                            <ul>
                                <?php foreach ($items as $item) :
                                    if (!is_array($item)) {
                                        continue;
                                    }
                                    $label = isset($item['label']) ? (string) $item['label'] : '';
                                    if ($label === '') {
                                        continue;
                                    }
                                    $href        = isset($item['href']) ? (string) $item['href'] : '#';
                                    $description = isset($item['description']) ? (string) $item['description'] : '';
                                    $icon        = isset($item['icon']) ? (string) $item['icon'] : '';
                                    ?>
                                    <li>
                                        <a href="<?php echo esc_url($href); ?>" class="juntaplay-dashboard__nav-link">
                                            <?php if ($icon) : ?>
                                                <span class="juntaplay-dashboard__nav-icon juntaplay-dashboard__nav-icon--<?php echo esc_attr($icon); ?>" aria-hidden="true"></span>
                                            <?php endif; ?>
                                            <span class="juntaplay-dashboard__nav-copy">
                                                <strong><?php echo esc_html($label); ?></strong>
                                                <?php if ($description) : ?>
                                                    <small><?php echo esc_html($description); ?></small>
                                                <?php endif; ?>
                                            </span>
                                            <span class="juntaplay-dashboard__nav-arrow" aria-hidden="true">
                                                <svg width="16" height="16" viewBox="0 0 16 16" focusable="false">
                                                    <path d="M5.5 3.5L10.5 8L5.5 12.5" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                                </svg>
                                            </span>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </article>
                    <?php endforeach; ?>
                </section>
            <?php endif; ?>
        </section>

        <section class="juntaplay-dashboard__tab-panel<?php echo $active_tab === 'groups' ? ' is-active' : ''; ?>" role="tabpanel" id="juntaplay-dashboard-panel-groups" aria-labelledby="juntaplay-dashboard-tab-groups" aria-hidden="<?php echo $active_tab === 'groups' ? 'false' : 'true'; ?>" data-dashboard-panel="groups">
            <header class="juntaplay-dashboard__panel-header">
                <div>
                    <h2><?php esc_html_e('Comunidades e grupos', 'juntaplay'); ?></h2>
                    <p><?php esc_html_e('Gerencie os grupos que você administra e acompanhe as participações em andamento.', 'juntaplay'); ?></p>
                </div>
                <a class="juntaplay-link" href="<?php echo esc_url($groups_page_url); ?>"><?php esc_html_e('Ir para Meus grupos', 'juntaplay'); ?></a>
            </header>

            <section class="juntaplay-dashboard__stats juntaplay-dashboard__stats--groups" aria-label="<?php esc_attr_e('Resumo dos grupos', 'juntaplay'); ?>">
                <article class="juntaplay-dashboard__stat">
                    <span class="juntaplay-dashboard__stat-label"><?php esc_html_e('Total de grupos', 'juntaplay'); ?></span>
                    <strong class="juntaplay-dashboard__stat-value"><?php echo esc_html(number_format_i18n($groups_total)); ?></strong>
                    <span class="juntaplay-dashboard__stat-caption"><?php esc_html_e('Inclui grupos criados e que você participa.', 'juntaplay'); ?></span>
                </article>
                <article class="juntaplay-dashboard__stat">
                    <span class="juntaplay-dashboard__stat-label"><?php esc_html_e('Administrados por você', 'juntaplay'); ?></span>
                    <strong class="juntaplay-dashboard__stat-value"><?php echo esc_html(number_format_i18n($groups_owned_total)); ?></strong>
                    <span class="juntaplay-dashboard__stat-caption"><?php esc_html_e('Convide novos membros e mantenha o status em dia.', 'juntaplay'); ?></span>
                </article>
                <article class="juntaplay-dashboard__stat">
                    <span class="juntaplay-dashboard__stat-label"><?php esc_html_e('Grupos que você segue', 'juntaplay'); ?></span>
                    <strong class="juntaplay-dashboard__stat-value"><?php echo esc_html(number_format_i18n($groups_member_total)); ?></strong>
                    <span class="juntaplay-dashboard__stat-caption"><?php esc_html_e('Receba avisos de novas cotas e renovações.', 'juntaplay'); ?></span>
                </article>
            </section>

            <div class="juntaplay-dashboard__groups">
                <?php if ($groups_owned) : ?>
                    <section class="juntaplay-dashboard__groups-column">
                        <h3><?php esc_html_e('Meus grupos', 'juntaplay'); ?></h3>
                        <ul class="juntaplay-dashboard__groups-list">
                            <?php foreach ($groups_owned as $group) :
                                if (!is_array($group)) {
                                    continue;
                                }
                                $category_slug = isset($group['category']) ? (string) $group['category'] : '';
                                $category_label = $category_slug && isset($group_categories[$category_slug]) ? $group_categories[$category_slug] : '';
                                $status        = isset($group['status']) ? (string) $group['status'] : '';
                                $status_label  = $status && isset($group_status_labels[$status]) ? $group_status_labels[$status] : $status;
                                $members_count = isset($group['members_count']) ? (int) $group['members_count'] : 0;
                                $price_value   = null;
                                if (isset($group['member_price']) && $group['member_price'] !== null) {
                                    $price_value = (float) $group['member_price'];
                                } elseif (isset($group['price_promotional']) && $group['price_promotional'] !== null) {
                                    $price_value = (float) $group['price_promotional'];
                                } elseif (isset($group['price_regular']) && $group['price_regular'] !== null) {
                                    $price_value = (float) $group['price_regular'];
                                }
                                $price_label = $price_value !== null ? sprintf(__('A partir de %s', 'juntaplay'), strip_tags($format_money((float) $price_value))) : '';
                                $role_label  = $group_role_labels['owner'];
                                $manage_url  = apply_filters('juntaplay/dashboard/group_manage_url', $groups_page_url, $group, $user_id);
                                $pool_slug   = isset($group['pool_slug']) ? (string) $group['pool_slug'] : '';
                                $service_name = isset($group['service_name']) ? (string) $group['service_name'] : '';
                                $service_url  = isset($group['service_url']) ? (string) $group['service_url'] : '';
                                $group_icon   = $pool_slug !== '' ? ServiceIcons::get($pool_slug) : '';
                                if ($group_icon === '') {
                                    $group_icon = ServiceIcons::resolve($pool_slug, $service_name !== '' ? $service_name : ($group['title'] ?? ''), $service_url);
                                }
                                if ($group_icon === '') {
                                    $group_icon = ServiceIcons::fallback();
                                }
                                $group_initial_raw = isset($group['title']) ? (string) $group['title'] : ($service_name !== '' ? $service_name : '');
                                $group_initial = $group_initial_raw !== ''
                                    ? (function_exists('mb_substr') ? mb_substr($group_initial_raw, 0, 1) : substr($group_initial_raw, 0, 1))
                                    : '';
                                $icon_classes = ['juntaplay-dashboard__group-icon'];
                                if ($group_icon !== '') {
                                    $icon_classes[] = 'has-image';
                                }
                                ?>
                                <li class="juntaplay-dashboard__group-card" data-group-role="owner">
                                    <div class="juntaplay-dashboard__group-media">
                                        <span
                                            class="<?php echo esc_attr(implode(' ', $icon_classes)); ?>"
                                            <?php echo $group_icon !== '' ? ' style="background-image: url(' . esc_url($group_icon) . ')"' : ''; ?>
                                            aria-hidden="true"
                                        ><?php echo $group_icon === '' ? esc_html($group_initial) : ''; ?></span>
                                        <?php if ($status_label) : ?>
                                            <span class="juntaplay-chip juntaplay-chip--status"><?php echo esc_html($status_label); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="juntaplay-dashboard__group-body">
                                        <h4><?php echo esc_html($group['title'] ?? ''); ?></h4>
                                        <?php if ($category_label) : ?>
                                            <span class="juntaplay-dashboard__group-category"><?php echo esc_html($category_label); ?></span>
                                        <?php endif; ?>
                                        <ul class="juntaplay-dashboard__group-meta">
                                            <?php if ($price_label) : ?>
                                                <li>
                                                    <span class="juntaplay-dashboard__group-icon" aria-hidden="true"></span>
                                                    <span><?php echo esc_html($price_label); ?></span>
                                                </li>
                                            <?php endif; ?>
                                            <li>
                                                <span class="juntaplay-dashboard__group-icon juntaplay-dashboard__group-icon--members" aria-hidden="true"></span>
                                                <span><?php echo esc_html(sprintf(_n('%s membro ativo', '%s membros ativos', $members_count, 'juntaplay'), number_format_i18n($members_count))); ?></span>
                                            </li>
                                            <li>
                                                <span class="juntaplay-dashboard__group-icon juntaplay-dashboard__group-icon--role" aria-hidden="true"></span>
                                                <span><?php echo esc_html($role_label); ?></span>
                                            </li>
                                        </ul>
                                        <a class="juntaplay-dashboard__group-link juntaplay-button juntaplay-button--ghost" href="<?php echo esc_url($manage_url); ?>"><?php esc_html_e('Gerenciar grupo', 'juntaplay'); ?></a>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </section>
                <?php endif; ?>

                <section class="juntaplay-dashboard__groups-column">
                    <h3><?php esc_html_e('Grupos que participo', 'juntaplay'); ?></h3>
                    <?php if ($groups_member) : ?>
                        <ul class="juntaplay-dashboard__groups-list">
                            <?php foreach ($groups_member as $group) :
                                if (!is_array($group)) {
                                    continue;
                                }
                                $category_slug = isset($group['category']) ? (string) $group['category'] : '';
                                $category_label = $category_slug && isset($group_categories[$category_slug]) ? $group_categories[$category_slug] : '';
                                $status        = isset($group['status']) ? (string) $group['status'] : '';
                                $status_label  = $status && isset($group_status_labels[$status]) ? $group_status_labels[$status] : $status;
                                $members_count = isset($group['members_count']) ? (int) $group['members_count'] : 0;
                                $role          = isset($group['membership_role']) ? (string) $group['membership_role'] : 'member';
                                $role_label    = $group_role_labels[$role] ?? $group_role_labels['member'];
                                $manage_url    = apply_filters('juntaplay/dashboard/group_manage_url', $groups_page_url, $group, $user_id);
                                $pool_slug     = isset($group['pool_slug']) ? (string) $group['pool_slug'] : '';
                                $service_name  = isset($group['service_name']) ? (string) $group['service_name'] : '';
                                $service_url   = isset($group['service_url']) ? (string) $group['service_url'] : '';
                                $group_icon    = $pool_slug !== '' ? ServiceIcons::get($pool_slug) : '';
                                if ($group_icon === '') {
                                    $group_icon = ServiceIcons::resolve($pool_slug, $service_name !== '' ? $service_name : ($group['title'] ?? ''), $service_url);
                                }
                                if ($group_icon === '') {
                                    $group_icon = ServiceIcons::fallback();
                                }
                                $group_initial_raw = isset($group['title']) ? (string) $group['title'] : ($service_name !== '' ? $service_name : '');
                                $group_initial = $group_initial_raw !== ''
                                    ? (function_exists('mb_substr') ? mb_substr($group_initial_raw, 0, 1) : substr($group_initial_raw, 0, 1))
                                    : '';
                                $icon_classes = ['juntaplay-dashboard__group-icon'];
                                if ($group_icon !== '') {
                                    $icon_classes[] = 'has-image';
                                }
                                ?>
                                <li class="juntaplay-dashboard__group-card" data-group-role="<?php echo esc_attr($role); ?>">
                                    <div class="juntaplay-dashboard__group-media">
                                        <span
                                            class="<?php echo esc_attr(implode(' ', $icon_classes)); ?>"
                                            <?php echo $group_icon !== '' ? ' style="background-image: url(' . esc_url($group_icon) . ')"' : ''; ?>
                                            aria-hidden="true"
                                        ><?php echo $group_icon === '' ? esc_html($group_initial) : ''; ?></span>
                                        <?php if ($status_label) : ?>
                                            <span class="juntaplay-chip juntaplay-chip--status"><?php echo esc_html($status_label); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="juntaplay-dashboard__group-body">
                                        <h4><?php echo esc_html($group['title'] ?? ''); ?></h4>
                                        <?php if ($category_label) : ?>
                                            <span class="juntaplay-dashboard__group-category"><?php echo esc_html($category_label); ?></span>
                                        <?php endif; ?>
                                        <ul class="juntaplay-dashboard__group-meta">
                                            <li>
                                                <span class="juntaplay-dashboard__group-icon juntaplay-dashboard__group-icon--members" aria-hidden="true"></span>
                                                <span><?php echo esc_html(sprintf(_n('%s participante', '%s participantes', $members_count, 'juntaplay'), number_format_i18n($members_count))); ?></span>
                                            </li>
                                            <li>
                                                <span class="juntaplay-dashboard__group-icon juntaplay-dashboard__group-icon--role" aria-hidden="true"></span>
                                                <span><?php echo esc_html($role_label); ?></span>
                                            </li>
                                        </ul>
                                        <a class="juntaplay-dashboard__group-link juntaplay-button juntaplay-button--ghost" href="<?php echo esc_url($manage_url); ?>"><?php esc_html_e('Ver detalhes', 'juntaplay'); ?></a>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else : ?>
                        <p class="juntaplay-notice"><?php esc_html_e('Você ainda não participa de nenhum grupo. Explore as campanhas e encontre a melhor oportunidade.', 'juntaplay'); ?></p>
                    <?php endif; ?>
                </section>
            </div>
        </section>

        <section class="juntaplay-dashboard__tab-panel<?php echo $active_tab === 'account' ? ' is-active' : ''; ?>" role="tabpanel" id="juntaplay-dashboard-panel-account" aria-labelledby="juntaplay-dashboard-tab-account" aria-hidden="<?php echo $active_tab === 'account' ? 'false' : 'true'; ?>" data-dashboard-panel="account">
            <header class="juntaplay-dashboard__panel-header">
                <div>
                    <h2><?php esc_html_e('Dados da conta', 'juntaplay'); ?></h2>
                    <p><?php esc_html_e('Acesse rapidamente as áreas de perfil, segurança e preferências.', 'juntaplay'); ?></p>
                </div>
            </header>

            <?php if ($account_sections_config) : ?>
                <section class="juntaplay-dashboard__nav" aria-label="<?php esc_attr_e('Atalhos da conta', 'juntaplay'); ?>">
                    <?php foreach ($account_sections_config as $section) :
                        $section_title = isset($section['title']) ? (string) $section['title'] : '';
                        $section_desc  = isset($section['description']) ? (string) $section['description'] : '';
                        $items         = isset($section['items']) && is_array($section['items']) ? $section['items'] : [];
                        if (!$items) {
                            continue;
                        }
                        ?>
                        <article class="juntaplay-dashboard__nav-card">
                            <header>
                                <?php if ($section_title) : ?>
                                    <h2><?php echo esc_html($section_title); ?></h2>
                                <?php endif; ?>
                                <?php if ($section_desc) : ?>
                                    <p><?php echo esc_html($section_desc); ?></p>
                                <?php endif; ?>
                            </header>
                            <ul>
                                <?php foreach ($items as $item) :
                                    if (!is_array($item)) {
                                        continue;
                                    }
                                    $label = isset($item['label']) ? (string) $item['label'] : '';
                                    if ($label === '') {
                                        continue;
                                    }
                                    $href        = isset($item['href']) ? (string) $item['href'] : '#';
                                    $description = isset($item['description']) ? (string) $item['description'] : '';
                                    $icon        = isset($item['icon']) ? (string) $item['icon'] : '';
                                    ?>
                                    <li>
                                        <a href="<?php echo esc_url($href); ?>" class="juntaplay-dashboard__nav-link">
                                            <?php if ($icon) : ?>
                                                <span class="juntaplay-dashboard__nav-icon juntaplay-dashboard__nav-icon--<?php echo esc_attr($icon); ?>" aria-hidden="true"></span>
                                            <?php endif; ?>
                                            <span class="juntaplay-dashboard__nav-copy">
                                                <strong><?php echo esc_html($label); ?></strong>
                                                <?php if ($description) : ?>
                                                    <small><?php echo esc_html($description); ?></small>
                                                <?php endif; ?>
                                            </span>
                                            <span class="juntaplay-dashboard__nav-arrow" aria-hidden="true">
                                                <svg width="16" height="16" viewBox="0 0 16 16" focusable="false">
                                                    <path d="M5.5 3.5L10.5 8L5.5 12.5" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                                </svg>
                                            </span>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </article>
                    <?php endforeach; ?>
                </section>
            <?php else : ?>
                <p class="juntaplay-notice"><?php esc_html_e('Nenhuma área de conta disponível no momento.', 'juntaplay'); ?></p>
            <?php endif; ?>
        </section>
    </div>
</div>
</div>
