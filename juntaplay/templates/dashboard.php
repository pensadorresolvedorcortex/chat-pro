<?php
/**
 * JuntaPlay user dashboard template.
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

$user_id = get_current_user_id();
$user    = wp_get_current_user();
$name    = $user && $user->exists() ? $user->display_name : '';
if ($name === '') {
    $name = $user && $user->exists() ? $user->user_login : '';
}

$hero_defaults = [
    'badge'        => __('10.10', 'juntaplay'),
    'title'        => sprintf(__('Bem-vindo, %s!', 'juntaplay'), $name ? wp_strip_all_tags($name) : __('ao JuntaPlay', 'juntaplay')),
    'description'  => __('Consiga as melhores cotas do mercado e fique por dentro das novidades.', 'juntaplay'),
    'cta_label'    => __('Entrar no grupo', 'juntaplay'),
    'cta_url'      => '',
    'secondary'    => __('Descubra oportunidades exclusivas e participe das campanhas mais quentes.', 'juntaplay'),
];

$campaigns_page_id = (int) get_option('juntaplay_page_campanhas');
if ($campaigns_page_id) {
    $hero_defaults['cta_url'] = get_permalink($campaigns_page_id);
}

if (!$hero_defaults['cta_url']) {
    $hero_defaults['cta_url'] = home_url('/campanhas');
}

$hero = apply_filters('juntaplay/dashboard/hero', $hero_defaults, $user);

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

$quick_actions = apply_filters(
    'juntaplay/dashboard/actions',
    [
        [
            'label' => __('Explorar campanhas', 'juntaplay'),
            'href'  => $hero['cta_url'] ?? $hero_defaults['cta_url'],
        ],
        [
            'label' => __('Minhas cotas', 'juntaplay'),
            'href'  => $my_quotas_url,
        ],
        [
            'label' => __('Pedidos e extratos', 'juntaplay'),
            'href'  => $extrato_url ?: $myaccount_url,
        ],
    ],
    $user
);

$account_base = $myaccount_url ?: home_url('/minha-conta');
$profile_id   = (int) get_option('juntaplay_page_perfil');
$profile_url  = $profile_id ? get_permalink($profile_id) : '';
if (!$profile_url) {
    $profile_url = $account_base;
}

$account_sections = apply_filters(
    'juntaplay/dashboard/sections',
    [
        [
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
                    'description' => __('Senha e login social.', 'juntaplay'),
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
                    'label'       => __('Meios de pagamento', 'juntaplay'),
                    'description' => __('Gerencie cartões e PIX.', 'juntaplay'),
                    'href'        => function_exists('wc_get_account_endpoint_url') ? wc_get_account_endpoint_url('payment-methods') : $account_base,
                    'icon'        => 'card',
                ],
                [
                    'label'       => __('Cupons e créditos', 'juntaplay'),
                    'description' => __('Aproveite recompensas disponíveis.', 'juntaplay'),
                    'href'        => function_exists('wc_get_account_endpoint_url') ? wc_get_account_endpoint_url('downloads') : $account_base,
                    'icon'        => 'ticket',
                ],
            ],
        ],
    ],
    $user
);

$format_money = static function (float $value): string {
    if (function_exists('wc_price')) {
        return wp_kses_post(wc_price($value));
    }

    $formatted = number_format_i18n($value, 2);

    return '<span class="juntaplay-money">R$ ' . esc_html($formatted) . '</span>';
};

$notifications_unread = \JuntaPlay\Data\Notifications::count_unread($user_id);
?>
<div class="juntaplay-dashboard juntaplay-section">
    <div class="juntaplay-dashboard__toolbar">
        <button type="button" class="juntaplay-notification-bell" data-jp-notifications aria-haspopup="true" aria-expanded="false"<?php if ($notifications_unread > 0) : ?> data-count="<?php echo esc_attr($notifications_unread); ?>"<?php endif; ?>>
            <span class="screen-reader-text"><?php esc_html_e('Abrir notificações', 'juntaplay'); ?></span>
            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                <path d="M12 22a2 2 0 0 0 1.995-1.85L14 20h-4a2 2 0 0 0 1.85 1.995L12 22Zm7-6v-5a7 7 0 0 0-5-6.708V4a2 2 0 1 0-4 0v.292A7.002 7.002 0 0 0 6 11v5l-1.447 2.894A1 1 0 0 0 5.447 20h13.106a1 1 0 0 0 .894-1.447Z" fill="none" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
        </button>
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
    </div>
    <section class="juntaplay-dashboard__hero">
        <div class="juntaplay-dashboard__hero-copy">
            <?php if (!empty($hero['badge'])) : ?>
                <span class="juntaplay-dashboard__badge"><?php echo esc_html((string) $hero['badge']); ?></span>
            <?php endif; ?>
            <h1><?php echo esc_html(wp_strip_all_tags((string) ($hero['title'] ?? $hero_defaults['title']))); ?></h1>
            <p class="juntaplay-dashboard__lead"><?php echo esc_html((string) ($hero['description'] ?? $hero_defaults['description'])); ?></p>
            <?php if (!empty($hero['secondary'])) : ?>
                <p class="juntaplay-dashboard__sub"><?php echo esc_html((string) $hero['secondary']); ?></p>
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
        <div class="juntaplay-dashboard__hero-card" aria-hidden="true">
            <div class="juntaplay-dashboard__hero-value"><?php echo esc_html((string) ($hero['badge'] ?? '')); ?></div>
            <p><?php esc_html_e('Consiga os melhores cupons do Mercado e fique por dentro das novidades.', 'juntaplay'); ?></p>
        </div>
    </section>

    <section class="juntaplay-dashboard__stats" aria-label="<?php esc_attr_e('Resumo da sua conta', 'juntaplay'); ?>">
        <article class="juntaplay-dashboard__stat">
            <span class="juntaplay-dashboard__stat-label"><?php esc_html_e('Cotas pagas', 'juntaplay'); ?></span>
            <strong class="juntaplay-dashboard__stat-value"><?php echo esc_html(number_format_i18n($quota_totals['paid'])); ?></strong>
            <span class="juntaplay-dashboard__stat-caption"><?php echo esc_html__('Participações confirmadas', 'juntaplay'); ?></span>
        </article>
        <article class="juntaplay-dashboard__stat">
            <span class="juntaplay-dashboard__stat-label"><?php esc_html_e('Reservas ativas', 'juntaplay'); ?></span>
            <strong class="juntaplay-dashboard__stat-value"><?php echo esc_html(number_format_i18n($quota_totals['reserved'])); ?></strong>
            <span class="juntaplay-dashboard__stat-caption"><?php echo esc_html__('Garanta a compra antes do prazo expirar.', 'juntaplay'); ?></span>
        </article>
        <article class="juntaplay-dashboard__stat">
            <span class="juntaplay-dashboard__stat-label"><?php esc_html_e('Pedidos realizados', 'juntaplay'); ?></span>
            <strong class="juntaplay-dashboard__stat-value"><?php echo esc_html(number_format_i18n($orders_count)); ?></strong>
            <span class="juntaplay-dashboard__stat-caption"><?php echo esc_html__('Acompanhe pagamentos e extratos.', 'juntaplay'); ?></span>
        </article>
        <article class="juntaplay-dashboard__stat juntaplay-dashboard__stat--highlight">
            <span class="juntaplay-dashboard__stat-label"><?php esc_html_e('Total investido', 'juntaplay'); ?></span>
            <strong class="juntaplay-dashboard__stat-value juntaplay-dashboard__stat-value--currency">
                <?php echo $format_money($total_spent); ?>
            </strong>
            <span class="juntaplay-dashboard__stat-caption"><?php esc_html_e('Somente cotas pagas contam aqui.', 'juntaplay'); ?></span>
        </article>
    </section>

    <?php if (!empty($account_sections)) : ?>
        <section class="juntaplay-dashboard__nav" aria-label="<?php esc_attr_e('Configurações da conta', 'juntaplay'); ?>">
            <?php foreach ($account_sections as $section) :
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
                            $label = isset($item['label']) ? (string) $item['label'] : '';
                            if (!$label) {
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

    <?php if (!empty($quick_actions)) : ?>
        <section class="juntaplay-dashboard__actions" aria-label="<?php esc_attr_e('Ações rápidas', 'juntaplay'); ?>">
            <?php foreach ($quick_actions as $action) :
                $href  = isset($action['href']) ? (string) $action['href'] : '#';
                $label = isset($action['label']) ? (string) $action['label'] : '';
                if (!$label) {
                    continue;
                }
                ?>
                <a class="juntaplay-dashboard__action" href="<?php echo esc_url($href); ?>">
                    <span><?php echo esc_html($label); ?></span>
                    <svg width="16" height="16" viewBox="0 0 16 16" aria-hidden="true" focusable="false">
                        <path d="M5.75 3.25L10.25 7.75L5.75 12.25" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </a>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>

    <section class="juntaplay-dashboard__panel">
        <header class="juntaplay-dashboard__panel-header">
            <div>
                <h2><?php esc_html_e('Selecionados para você', 'juntaplay'); ?></h2>
                <p><?php esc_html_e('Confira campanhas em destaque e garanta as melhores cotas antes que acabem.', 'juntaplay'); ?></p>
            </div>
            <a class="juntaplay-link" href="<?php echo esc_url($hero['cta_url'] ?? $hero_defaults['cta_url']); ?>"><?php esc_html_e('Ver todas', 'juntaplay'); ?></a>
        </header>
        <div class="juntaplay-dashboard__grid">
            <?php if ($recommended_pools) : ?>
                <?php foreach ($recommended_pools as $pool) :
                    $permalink = $pool->product_id ? get_permalink((int) $pool->product_id) : '';
                    $price     = isset($pool->price) ? (float) $pool->price : 0.0;
                    $price_str = function_exists('wc_price') ? wc_price($price) : sprintf('R$ %s', esc_html(number_format_i18n($price, 2)));
                    $total     = (int) ($pool->quotas_total ?? 0);
                    $paid      = (int) ($pool->quotas_paid ?? 0);
                    $available = max(0, $total - $paid);
                    $progress  = $total > 0 ? min(100, (int) round(($paid / $total) * 100)) : 0;
                    ?>
                    <article class="juntaplay-dashboard__card">
                        <header>
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
                            <a class="juntaplay-dashboard__card-link" href="<?php echo esc_url($permalink); ?>"><?php esc_html_e('Ver campanha', 'juntaplay'); ?></a>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            <?php else : ?>
                <p class="juntaplay-notice"><?php esc_html_e('Nenhuma campanha disponível no momento.', 'juntaplay'); ?></p>
            <?php endif; ?>
        </div>
    </section>

    <section class="juntaplay-dashboard__panel">
        <header class="juntaplay-dashboard__panel-header">
            <div>
                <h2><?php esc_html_e('Suas campanhas', 'juntaplay'); ?></h2>
                <p><?php esc_html_e('Resumo das últimas campanhas onde você possui cotas.', 'juntaplay'); ?></p>
            </div>
            <a class="juntaplay-link" href="<?php echo esc_url($my_quotas_url); ?>"><?php esc_html_e('Ver todas as cotas', 'juntaplay'); ?></a>
        </header>
        <?php if ($user_pools) : ?>
            <div class="juntaplay-dashboard__list">
                <?php foreach ($user_pools as $pool) :
                    $permalink = $pool->product_id ? get_permalink((int) $pool->product_id) : '';
                    $numbers   = (int) $pool->total_count;
                    $paid      = (int) $pool->paid_count;
                    $reserved  = (int) $pool->reserved_count;
                    $progress  = $numbers > 0 ? min(100, (int) round(($paid / $numbers) * 100)) : 0;
                    ?>
                    <article class="juntaplay-dashboard__item">
                        <div class="juntaplay-dashboard__item-head">
                            <h3><?php echo esc_html($pool->title); ?></h3>
                            <?php if ($permalink) : ?>
                                <a class="juntaplay-chip" href="<?php echo esc_url($permalink); ?>"><?php esc_html_e('Ver campanha', 'juntaplay'); ?></a>
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
            <p class="juntaplay-notice"><?php esc_html_e('Você ainda não possui cotas. Que tal começar agora mesmo?', 'juntaplay'); ?></p>
        <?php endif; ?>
    </section>
</div>
