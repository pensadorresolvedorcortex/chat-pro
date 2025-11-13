<?php
/**
 * Dashboard view that renders the Ubold layout.
 *
 * @package DashboardAgenciaPrivilege
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$widget_area            = isset( $data['widget_area'] ) ? $data['widget_area'] : '';
$cta_url                = isset( $data['cta_url'] ) ? $data['cta_url'] : '';
$elementor_cta          = isset( $data['elementor_cta'] ) ? $data['elementor_cta'] : '';
$error_logs             = isset( $data['error_logs'] ) && is_array( $data['error_logs'] ) ? $data['error_logs'] : [];
$clear_logs_url         = isset( $data['clear_logs_url'] ) ? $data['clear_logs_url'] : '';
$refresh_url            = isset( $data['refresh_url'] ) ? $data['refresh_url'] : '';
$logs_recently_cleared  = ! empty( $data['logs_recently_cleared'] );
$dashboard_recently_refreshed = ! empty( $data['dashboard_recently_refreshed'] );
$dashboard_dataset      = isset( $data['dashboard'] ) && is_array( $data['dashboard'] ) ? $data['dashboard'] : [];
$kpis                   = isset( $dashboard_dataset['kpis'] ) && is_array( $dashboard_dataset['kpis'] ) ? $dashboard_dataset['kpis'] : [];
$sales_cards            = isset( $dashboard_dataset['sales_cards'] ) && is_array( $dashboard_dataset['sales_cards'] ) ? $dashboard_dataset['sales_cards'] : [];
$sales_summary          = isset( $dashboard_dataset['sales_summary'] ) && is_array( $dashboard_dataset['sales_summary'] ) ? $dashboard_dataset['sales_summary'] : [];
$project_rows           = isset( $dashboard_dataset['project_rows'] ) && is_array( $dashboard_dataset['project_rows'] ) ? $dashboard_dataset['project_rows'] : [];
$inventory_rows         = isset( $dashboard_dataset['inventory_rows'] ) && is_array( $dashboard_dataset['inventory_rows'] ) ? $dashboard_dataset['inventory_rows'] : [];
$orders_rows            = isset( $dashboard_dataset['orders_rows'] ) && is_array( $dashboard_dataset['orders_rows'] ) ? $dashboard_dataset['orders_rows'] : [];
$activity_items         = isset( $dashboard_dataset['activity_items'] ) && is_array( $dashboard_dataset['activity_items'] ) ? $dashboard_dataset['activity_items'] : [];
$important_projects     = isset( $dashboard_dataset['important_projects'] ) && is_array( $dashboard_dataset['important_projects'] ) ? $dashboard_dataset['important_projects'] : [];
$charts                 = isset( $dashboard_dataset['charts'] ) && is_array( $dashboard_dataset['charts'] ) ? $dashboard_dataset['charts'] : [];
$project_statistics     = isset( $charts['salesAnalytics'] ) && is_array( $charts['salesAnalytics'] ) ? $charts['salesAnalytics'] : ( isset( $charts['projectStatistics'] ) ? $charts['projectStatistics'] : [] );
$email_categories       = isset( $charts['emailCategories'] ) && is_array( $charts['emailCategories'] ) ? $charts['emailCategories'] : [];
$monthly_series         = isset( $project_statistics['series']['monthly'] ) && is_array( $project_statistics['series']['monthly'] ) ? $project_statistics['series']['monthly'] : [];
$diagnostics            = isset( $data['diagnostics'] ) && is_array( $data['diagnostics'] ) ? $data['diagnostics'] : [];
$diagnostics_widget     = isset( $diagnostics['widget_area'] ) && is_array( $diagnostics['widget_area'] ) ? $diagnostics['widget_area'] : [];
$diagnostics_dataset    = isset( $diagnostics['dataset'] ) && is_array( $diagnostics['dataset'] ) ? $diagnostics['dataset'] : [];
$diagnostics_settings   = isset( $diagnostics['settings_url'] ) ? $diagnostics['settings_url'] : '';
$diagnostics_widget_url = isset( $diagnostics_widget['manage_url'] ) ? $diagnostics_widget['manage_url'] : '';
$diagnostics_widget_edit = isset( $diagnostics_widget['edit_link'] ) ? $diagnostics_widget['edit_link'] : '';
$diagnostics_has_assets = ! empty( $diagnostics['has_ubold_assets'] );
$diagnostics_global_skin = ! empty( $diagnostics['global_skin_enabled'] );
$diagnostics_widget_exists = ! empty( $diagnostics_widget['exists'] );
$diagnostics_widget_elementor = ! empty( $diagnostics_widget['elementor_ready'] );
$diagnostics_dataset_state = isset( $diagnostics_dataset['state'] ) ? $diagnostics_dataset['state'] : 'miss';
$diagnostics_dataset_label = isset( $diagnostics_dataset['generated_label'] ) ? $diagnostics_dataset['generated_label'] : '';
$diagnostics_dataset_kpis = isset( $diagnostics_dataset['kpi_count'] ) ? (int) $diagnostics_dataset['kpi_count'] : 0;
$diagnostics_dataset_projects = isset( $diagnostics_dataset['projects_count'] ) ? (int) $diagnostics_dataset['projects_count'] : 0;
$diagnostics_inventory  = isset( $diagnostics_dataset['inventory_count'] ) ? (int) $diagnostics_dataset['inventory_count'] : 0;
$diagnostics_orders     = isset( $diagnostics_dataset['orders_count'] ) ? (int) $diagnostics_dataset['orders_count'] : 0;
$diagnostics_wc         = isset( $diagnostics['woocommerce'] ) && is_array( $diagnostics['woocommerce'] ) ? $diagnostics['woocommerce'] : [];
$diagnostics_wc_active  = ! empty( $diagnostics_wc['active'] );
$diagnostics_wc_orders  = isset( $diagnostics_wc['orders_week'] ) ? (int) $diagnostics_wc['orders_week'] : 0;
$diagnostics_wc_pending = isset( $diagnostics_wc['pending'] ) ? (int) $diagnostics_wc['pending'] : 0;
$diagnostics_wc_currency = isset( $diagnostics_wc['currency'] ) ? $diagnostics_wc['currency'] : '';
$diagnostics_wc_month_total = isset( $diagnostics_wc['month_total'] ) ? $diagnostics_wc['month_total'] : 0;
$topbar                 = isset( $data['topbar'] ) && is_array( $data['topbar'] ) ? $data['topbar'] : [];
$topbar_user            = isset( $topbar['user'] ) && is_array( $topbar['user'] ) ? $topbar['user'] : [];
$topbar_languages       = isset( $topbar['languages'] ) && is_array( $topbar['languages'] ) ? $topbar['languages'] : [];
$topbar_notifications   = isset( $topbar['notifications'] ) && is_array( $topbar['notifications'] ) ? $topbar['notifications'] : [];
$topbar_customizer_url  = isset( $topbar['customizer_url'] ) ? $topbar['customizer_url'] : '';
$topbar_site_name       = isset( $topbar['site_name'] ) ? $topbar['site_name'] : get_bloginfo( 'name' );
$topbar_site_tagline    = isset( $topbar['site_tagline'] ) ? $topbar['site_tagline'] : get_bloginfo( 'description' );
$topbar_logo_light      = isset( $topbar['logo_light'] ) ? $topbar['logo_light'] : '';
$topbar_logo_dark       = isset( $topbar['logo_dark'] ) ? $topbar['logo_dark'] : '';
$topbar_logo_icon       = isset( $topbar['logo_icon'] ) ? $topbar['logo_icon'] : '';
$topbar_search_action   = isset( $topbar['search_action'] ) ? $topbar['search_action'] : admin_url( 'edit.php' );
$topbar_user_name       = isset( $topbar_user['name'] ) ? $topbar_user['name'] : wp_get_current_user()->display_name;
$topbar_user_role       = isset( $topbar_user['role'] ) ? $topbar_user['role'] : esc_html__( 'Administrador', 'dap' );
$topbar_user_avatar     = isset( $topbar_user['avatar'] ) ? $topbar_user['avatar'] : get_avatar_url( get_current_user_id(), [ 'size' => 64 ] );
$topbar_profile_url     = isset( $topbar_user['profile_url'] ) ? $topbar_user['profile_url'] : admin_url( 'profile.php' );
$topbar_logout_url      = isset( $topbar_user['logout_url'] ) ? $topbar_user['logout_url'] : wp_logout_url();
$topbar_theme_mode      = isset( $topbar['theme_mode'] ) ? $topbar['theme_mode'] : 'auto';
$topbar_theme_mode      = in_array( $topbar_theme_mode, [ 'light', 'dark', 'auto' ], true ) ? $topbar_theme_mode : 'auto';
$topbar_theme_label     = dap_get_theme_mode_label( $topbar_theme_mode );
$topbar_theme_icon      = 'auto' === $topbar_theme_mode ? 'ri-computer-line' : ( 'dark' === $topbar_theme_mode ? 'ri-moon-line' : 'ri-sun-line' );
$project_growth_delta   = 0.0;
$menu_folded_setting    = function_exists( 'get_user_setting' ) ? get_user_setting( 'mfold', 'o' ) : 'o';
$menu_folded_active     = is_string( $menu_folded_setting ) && false !== strpos( $menu_folded_setting, 'f' );
$layout                 = isset( $data['layout'] ) && is_array( $data['layout'] ) ? $data['layout'] : [];
$layout_max_width       = isset( $layout['max_width'] ) ? (int) $layout['max_width'] : dap_get_dashboard_max_width();

if ( $layout_max_width < 960 ) {
    $layout_max_width = 960;
} elseif ( $layout_max_width > 1920 ) {
    $layout_max_width = 1920;
}

$layout_style_attr = sprintf( ' style="%s"', esc_attr( '--dap-dashboard-max-width:' . $layout_max_width . 'px;' ) );

if ( count( $monthly_series ) >= 2 ) {
    $first_month = (float) reset( $monthly_series );
    $last_month  = (float) end( $monthly_series );

    if ( $first_month > 0 ) {
        $project_growth_delta = ( ( $last_month - $first_month ) / $first_month ) * 100;
    } elseif ( $last_month > 0 ) {
        $project_growth_delta = 100;
    }
}

$project_growth_formatted = ( $project_growth_delta >= 0 ? '+' : '−' ) . number_format_i18n( abs( $project_growth_delta ), 1 ) . '%';
$project_growth_label     = $project_growth_delta >= 0 ? esc_html__( 'Conclusões acima da meta', 'dap' ) : esc_html__( 'Abaixo do ritmo ideal', 'dap' );

$email_labels = isset( $email_categories['labels'] ) && is_array( $email_categories['labels'] ) ? $email_categories['labels'] : [];
$email_series = isset( $email_categories['series'] ) && is_array( $email_categories['series'] ) ? $email_categories['series'] : [];
$email_colors = isset( $email_categories['ui_colors'] ) && is_array( $email_categories['ui_colors'] ) ? $email_categories['ui_colors'] : [ 'primary', 'success', 'warning', 'info' ];
$email_total  = array_sum( $email_series );

$generated_at_gmt    = isset( $dashboard_dataset['generated_at_gmt'] ) ? $dashboard_dataset['generated_at_gmt'] : '';
$generated_at_local  = isset( $dashboard_dataset['generated_at_local'] ) ? $dashboard_dataset['generated_at_local'] : '';
$generated_timestamp = isset( $dashboard_dataset['generated_timestamp'] ) ? (int) $dashboard_dataset['generated_timestamp'] : 0;
$generated_label     = '';
$date_format         = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );

if ( $generated_at_gmt ) {
    $generated_label = get_date_from_gmt( $generated_at_gmt, $date_format );
} elseif ( $generated_at_local ) {
    $generated_label = mysql2date( $date_format, $generated_at_local );
} elseif ( $generated_timestamp ) {
    $generated_label = date_i18n( $date_format, $generated_timestamp );
}

$project_table_header = [
    esc_html__( 'Projeto', 'dap' ),
    esc_html__( 'Responsável', 'dap' ),
    esc_html__( 'Progresso', 'dap' ),
    esc_html__( 'Status', 'dap' ),
];
$add_project_url    = admin_url( 'post-new.php' );
$export_projects_url = admin_url( 'edit.php' );

$hero_image      = DAP_URL . 'assets/images/hero-placeholder.svg';
$hero_candidates = [
    'assets/ubold/assets/images/hero-1.png',
    'assets/ubold/assets/images/hero.png',
    'assets/images/hero-1.png',
    'assets/images/hero.png',
];

foreach ( $hero_candidates as $candidate ) {
    if ( dap_asset_exists( $candidate ) ) {
        $hero_image = DAP_URL . ltrim( $candidate, '/' );
        break;
    }
}
?>
<div class="dap-admin"<?php echo $layout_style_attr; ?>>
    <header class="dap-topbar shadow-sm mb-4">
        <div class="container-fluid topbar-menu py-3">
            <div class="dap-topbar-inner mx-auto d-flex align-items-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-3 flex-grow-1 flex-lg-grow-0">
                <button class="btn btn-icon btn-soft-primary dap-menu-toggle d-inline-flex" type="button" data-action="dap-toggle-menu" aria-pressed="<?php echo $menu_folded_active ? 'true' : 'false'; ?>" aria-label="<?php echo esc_attr__( 'Alternar menu lateral', 'dap' ); ?>">
                    <i class="ri-menu-2-line"></i>
                </button>
                <a class="dap-logo d-flex align-items-center gap-2" href="<?php echo esc_url( admin_url() ); ?>">
                    <?php if ( $topbar_logo_icon ) : ?>
                        <span class="logo-sm d-inline-flex align-items-center justify-content-center">
                            <img src="<?php echo esc_url( $topbar_logo_icon ); ?>" alt="<?php echo esc_attr( $topbar_site_name ); ?>" class="img-fluid" />
                        </span>
                    <?php else : ?>
                        <span class="logo-sm fw-bold text-primary">DAP</span>
                    <?php endif; ?>
                    <span class="logo-lg d-flex flex-column">
                        <?php if ( $topbar_logo_light ) : ?>
                            <img src="<?php echo esc_url( $topbar_logo_light ); ?>" alt="<?php echo esc_attr( $topbar_site_name ); ?>" class="logo-light" />
                        <?php endif; ?>
                        <?php if ( $topbar_logo_dark ) : ?>
                            <img src="<?php echo esc_url( $topbar_logo_dark ); ?>" alt="<?php echo esc_attr( $topbar_site_name ); ?>" class="logo-dark" />
                        <?php endif; ?>
                        <?php if ( ! $topbar_logo_light && ! $topbar_logo_dark ) : ?>
                            <strong class="fs-5 text-primary mb-0"><?php echo esc_html( $topbar_site_name ); ?></strong>
                        <?php endif; ?>
                    </span>
                </a>
                <div class="d-none d-xl-flex flex-column">
                    <span class="fw-semibold text-uppercase small text-muted"><?php echo esc_html__( 'Painel', 'dap' ); ?></span>
                    <span class="fw-semibold"><?php echo esc_html( $topbar_site_name ); ?></span>
                    <?php if ( $topbar_site_tagline ) : ?>
                        <small class="text-muted"><?php echo esc_html( $topbar_site_tagline ); ?></small>
                    <?php endif; ?>
                </div>
            </div>
            <form class="dap-topbar-search d-none d-lg-flex align-items-center gap-2" role="search" method="get" action="<?php echo esc_url( $topbar_search_action ); ?>">
                <span class="ri-search-line text-muted"></span>
                <input type="search" name="s" class="form-control topbar-search rounded-pill" placeholder="<?php echo esc_attr__( 'Buscar conteúdos…', 'dap' ); ?>" />
            </form>
            <div class="d-flex align-items-center gap-2 flex-wrap justify-content-end">
                <button class="btn btn-icon btn-soft-secondary d-inline-flex d-lg-none" type="button" data-action="dap-open-search" aria-expanded="false" aria-label="<?php echo esc_attr__( 'Abrir busca rápida', 'dap' ); ?>">
                    <i class="ri-search-line"></i>
                </button>
                <?php if ( ! empty( $topbar_languages ) ) : ?>
                    <div class="dropdown">
                        <button class="btn btn-icon btn-soft-secondary" type="button" id="dap-language-menu" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="ri-global-line"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end" aria-labelledby="dap-language-menu">
                            <h6 class="dropdown-header"><?php echo esc_html__( 'Selecione o idioma', 'dap' ); ?></h6>
                            <?php foreach ( $topbar_languages as $language ) :
                                $language_url    = isset( $language['url'] ) ? $language['url'] : '';
                                $language_active = ! empty( $language['active'] );
                                $language_code   = isset( $language['code'] ) ? $language['code'] : '';
                                $language_class  = 'dropdown-item d-flex justify-content-between align-items-center';

                                if ( $language_active ) {
                                    $language_class .= ' active';
                                }
                                ?>
                                <?php if ( $language_url ) : ?>
                                    <a class="<?php echo esc_attr( $language_class ); ?>" href="<?php echo esc_url( $language_url ); ?>">
                                        <span>
                                            <?php echo esc_html( $language['label'] ); ?>
                                            <?php if ( $language_code ) : ?>
                                                <small class="text-muted ms-2"><?php echo esc_html( $language_code ); ?></small>
                                            <?php endif; ?>
                                        </span>
                                        <?php if ( $language_active ) : ?>
                                            <span class="badge bg-soft-primary text-primary"><?php echo esc_html__( 'Atual', 'dap' ); ?></span>
                                        <?php endif; ?>
                                    </a>
                                <?php else : ?>
                                    <span class="<?php echo esc_attr( $language_class ); ?>">
                                        <span>
                                            <?php echo esc_html( $language['label'] ); ?>
                                            <?php if ( $language_code ) : ?>
                                                <small class="text-muted ms-2"><?php echo esc_html( $language_code ); ?></small>
                                            <?php endif; ?>
                                        </span>
                                        <?php if ( $language_active ) : ?>
                                            <span class="badge bg-soft-primary text-primary"><?php echo esc_html__( 'Atual', 'dap' ); ?></span>
                                        <?php endif; ?>
                                    </span>
                                <?php endif; ?>
                            <?php endforeach; ?>
                            <div class="dropdown-divider"></div>
                            <span class="dropdown-item-text text-muted small"><?php echo esc_html__( 'Integre seu switch de idiomas via hook dap_dashboard_languages.', 'dap' ); ?></span>
                        </div>
                    </div>
                <?php endif; ?>
                <div class="dropdown">
                    <button class="btn btn-icon btn-soft-secondary position-relative" type="button" id="dap-notification-menu" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="ri-notification-3-line"></i>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">&nbsp;</span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end dap-notification-dropdown" aria-labelledby="dap-notification-menu">
                        <div class="px-3 py-2 border-bottom d-flex justify-content-between align-items-center">
                            <span class="fw-semibold"><?php echo esc_html__( 'Notificações', 'dap' ); ?></span>
                            <span class="badge bg-soft-primary text-primary"><?php echo count( $topbar_notifications ); ?></span>
                        </div>
                        <div class="list-group list-group-flush">
                            <?php foreach ( $topbar_notifications as $notification ) : ?>
                                <div class="list-group-item d-flex align-items-start gap-3">
                                    <span class="text-primary"><i class="<?php echo esc_attr( $notification['icon'] ); ?>"></i></span>
                                    <div>
                                        <p class="mb-1 fw-semibold"><?php echo esc_html( $notification['title'] ); ?></p>
                                        <small class="text-muted"><?php echo esc_html( $notification['meta'] ); ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
        </div>
    </div>
</div>
                <div class="dropdown dap-theme-dropdown">
                    <button class="btn btn-icon btn-soft-secondary" type="button" id="dap-theme-menu" data-bs-toggle="dropdown" aria-expanded="false" data-theme-current data-theme-preference="<?php echo esc_attr( $topbar_theme_mode ); ?>" aria-label="<?php echo esc_attr( $topbar_theme_label ); ?>">
                        <i class="<?php echo esc_attr( $topbar_theme_icon ); ?>" data-theme-icon></i>
                        <span class="visually-hidden" data-theme-label><?php echo esc_html( $topbar_theme_label ); ?></span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end" aria-labelledby="dap-theme-menu">
                        <h6 class="dropdown-header"><?php echo esc_html__( 'Selecionar tema', 'dap' ); ?></h6>
                        <?php
                        $theme_options = [
                            'light' => [ 'icon' => 'ri-sun-line', 'label' => esc_html__( 'Modo claro', 'dap' ) ],
                            'dark'  => [ 'icon' => 'ri-moon-line', 'label' => esc_html__( 'Modo escuro', 'dap' ) ],
                            'auto'  => [ 'icon' => 'ri-computer-line', 'label' => esc_html__( 'Modo automático', 'dap' ) ],
                        ];
                        foreach ( $theme_options as $theme_key => $theme_option ) :
                            $is_active = ( $theme_key === $topbar_theme_mode );
                            ?>
                            <button type="button" class="dropdown-item d-flex align-items-center gap-2<?php echo $is_active ? ' active' : ''; ?>" data-theme-mode="<?php echo esc_attr( $theme_key ); ?>" aria-pressed="<?php echo $is_active ? 'true' : 'false'; ?>">
                                <i class="<?php echo esc_attr( $theme_option['icon'] ); ?>"></i>
                                <span><?php echo esc_html( $theme_option['label'] ); ?></span>
                                <i class="ri-check-line ms-auto<?php echo $is_active ? '' : ' d-none'; ?>" data-theme-check></i>
                            </button>
                        <?php endforeach; ?>
                        <div class="dropdown-divider"></div>
                        <span class="dropdown-item-text text-muted small"><?php echo esc_html__( 'O modo automático acompanha o tema do sistema operacional.', 'dap' ); ?></span>
                    </div>
                </div>
                <button class="btn btn-icon btn-soft-secondary d-none d-md-inline-flex" type="button" data-action="dap-toggle-fullscreen">
                    <i class="ri-fullscreen-line"></i>
                </button>
                <button class="btn btn-soft-primary d-none d-lg-inline-flex align-items-center gap-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#dapCustomizer" aria-controls="dapCustomizer">
                    <i class="ri-settings-3-line"></i>
                    <span><?php echo esc_html__( 'Personalizar', 'dap' ); ?></span>
                </button>
                <div class="dropdown">
                    <button class="btn d-flex align-items-center gap-2" type="button" id="dap-user-menu" data-bs-toggle="dropdown" aria-expanded="false">
                        <img src="<?php echo esc_url( $topbar_user_avatar ); ?>" alt="<?php echo esc_attr( $topbar_user_name ); ?>" class="rounded-circle" width="36" height="36" />
                        <span class="d-none d-xl-inline-flex flex-column text-start">
                            <span class="fw-semibold"><?php echo esc_html( $topbar_user_name ); ?></span>
                            <small class="text-muted"><?php echo esc_html( $topbar_user_role ); ?></small>
                        </span>
                        <i class="ri-arrow-down-s-line d-none d-xl-inline"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end" aria-labelledby="dap-user-menu">
                        <a class="dropdown-item" href="<?php echo esc_url( $topbar_profile_url ); ?>">
                            <i class="ri-user-3-line me-2"></i><?php echo esc_html__( 'Perfil', 'dap' ); ?>
                        </a>
                        <a class="dropdown-item" href="<?php echo esc_url( admin_url( 'options-general.php?page=dap-settings' ) ); ?>">
                            <i class="ri-equalizer-line me-2"></i><?php echo esc_html__( 'Configurações do Dashboard', 'dap' ); ?>
                        </a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item text-danger" href="<?php echo esc_url( $topbar_logout_url ); ?>">
                            <i class="ri-logout-box-line me-2"></i><?php echo esc_html__( 'Sair', 'dap' ); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </header>
    <div class="dap-search-overlay" data-search-overlay hidden aria-hidden="true">
        <div class="dap-search-overlay__backdrop" data-search-dismiss></div>
        <div class="dap-search-panel card border-0 dap-card-animate" data-dap-animate="rise" role="dialog" aria-modal="true" aria-labelledby="dapSearchLabel" tabindex="-1">
            <div class="card-body p-4 p-lg-5">
                <div class="d-flex justify-content-between align-items-start gap-3 mb-4">
                    <div>
                        <h5 id="dapSearchLabel" class="mb-1 fw-semibold"><?php echo esc_html__( 'Busca rápida', 'dap' ); ?></h5>
                        <p class="text-muted mb-0"><?php echo esc_html__( 'Encontre conteúdos, produtos e configurações sem sair do painel.', 'dap' ); ?></p>
                    </div>
                    <button type="button" class="btn btn-icon btn-soft-secondary" data-search-dismiss aria-label="<?php echo esc_attr__( 'Fechar busca', 'dap' ); ?>">
                        <i class="ri-close-line"></i>
                    </button>
                </div>
                <form class="dap-search-form" role="search" method="get" action="<?php echo esc_url( $topbar_search_action ); ?>">
                    <div class="input-group input-group-lg">
                        <span class="input-group-text"><i class="ri-search-line"></i></span>
                        <input type="search" name="s" class="form-control" placeholder="<?php echo esc_attr__( 'Digite para buscar…', 'dap' ); ?>" autocomplete="off" data-search-input />
                    </div>
                    <div class="d-flex flex-wrap gap-3 align-items-center mt-3 text-muted small">
                        <span class="d-flex align-items-center gap-2">
                            <kbd>Esc</kbd>
                            <span><?php echo esc_html__( 'para fechar', 'dap' ); ?></span>
                        </span>
                        <span class="d-flex align-items-center gap-2">
                            <kbd>Enter</kbd>
                            <span><?php echo esc_html__( 'para pesquisar', 'dap' ); ?></span>
                        </span>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="offcanvas offcanvas-end dap-customizer-offcanvas" tabindex="-1" id="dapCustomizer" aria-labelledby="dapCustomizerLabel">
        <div class="offcanvas-header border-bottom">
            <h5 class="offcanvas-title" id="dapCustomizerLabel"><?php echo esc_html__( 'Personalizador do painel', 'dap' ); ?></h5>
            <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="<?php echo esc_attr__( 'Fechar', 'dap' ); ?>"></button>
        </div>
        <div class="offcanvas-body">
            <p class="text-muted small"><?php echo esc_html__( 'Aplique ajustes rápidos na aparência do painel. Configurações avançadas podem ser feitas nas opções do plugin.', 'dap' ); ?></p>
            <div class="list-group list-group-flush">
                <label class="list-group-item d-flex justify-content-between align-items-center">
                    <span><?php echo esc_html__( 'Modo escuro', 'dap' ); ?></span>
                    <span class="form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" data-action="dap-toggle-theme" />
                    </span>
                </label>
                <label class="list-group-item d-flex justify-content-between align-items-center">
                    <span><?php echo esc_html__( 'Forçar skin global', 'dap' ); ?></span>
                    <span class="badge bg-soft-primary text-primary"><?php echo esc_html__( 'Ativado', 'dap' ); ?></span>
                </label>
                <label class="list-group-item d-flex justify-content-between align-items-center">
                    <span><?php echo esc_html__( 'Resetar cache do dashboard', 'dap' ); ?></span>
                    <?php if ( ! empty( $refresh_url ) ) : ?>
                        <a class="btn btn-sm btn-outline-primary" href="<?php echo esc_url( $refresh_url ); ?>"><?php echo esc_html__( 'Recarregar', 'dap' ); ?></a>
                    <?php endif; ?>
                </label>
            </div>
        </div>
        <div class="offcanvas-footer border-top p-3">
            <?php if ( $topbar_customizer_url ) : ?>
                <a class="btn btn-primary w-100" href="<?php echo esc_url( $topbar_customizer_url ); ?>"><?php echo esc_html__( 'Abrir Personalizador do Tema', 'dap' ); ?></a>
            <?php else : ?>
                <span class="text-muted small"><?php echo esc_html__( 'Nenhum customizador disponível neste ambiente.', 'dap' ); ?></span>
            <?php endif; ?>
        </div>
    </div>
    <div class="dap-dashboard container-fluid pb-5">
        <div class="dap-dashboard-inner mx-auto">
        <div class="dap-dashboard-toolbar d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4">
            <div class="d-flex flex-column flex-lg-row align-items-lg-center gap-2">
                <span class="text-muted small">
                    <?php if ( $generated_label ) : ?>
                        <?php printf( esc_html__( 'Atualizado em %s', 'dap' ), esc_html( $generated_label ) ); ?>
                    <?php else : ?>
                        <?php echo esc_html__( 'O painel será atualizado conforme novas atividades forem registradas.', 'dap' ); ?>
                    <?php endif; ?>
                </span>
                <?php if ( $dashboard_recently_refreshed ) : ?>
                    <span class="badge bg-soft-success text-success fw-semibold">
                        <?php echo esc_html__( 'Dados atualizados', 'dap' ); ?>
                    </span>
                <?php endif; ?>
            </div>
            <div class="d-flex gap-2">
                <?php if ( ! empty( $refresh_url ) ) : ?>
                    <a class="btn btn-soft-secondary btn-sm d-inline-flex align-items-center gap-2" href="<?php echo esc_url( $refresh_url ); ?>">
                        <span class="ri-refresh-line" aria-hidden="true"></span>
                        <span><?php echo esc_html__( 'Atualizar agora', 'dap' ); ?></span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <div class="row gy-4 gx-4 gx-xxl-5 mb-3">
            <div class="col-12">
                <div class="card dap-hero overflow-hidden text-white position-relative dap-card-animate" data-dap-animate="rise" style="--dap-animate-delay: 0.05s;">
                    <div class="card-body dap-hero-body">
                        <div class="row g-4 g-xl-5 align-items-center flex-lg-nowrap">
                            <div class="col-lg-7 col-xl-6 col-xxl-7">
                                <div class="dap-hero-copy pe-lg-4 pe-xxl-5">
                                    <span class="badge bg-soft-light text-uppercase text-white-50 fw-semibold mb-3"><?php echo esc_html__( 'Visão em tempo real', 'dap' ); ?></span>
                                    <?php $hero_headline = isset( $sales_summary['headline'] ) && is_array( $sales_summary['headline'] ) ? $sales_summary['headline'] : null; ?>
                                    <?php if ( $hero_headline ) : ?>
                                        <div class="d-flex flex-wrap align-items-center gap-3 mb-3">
                                            <h2 class="fw-semibold mb-0 display-6"><?php echo esc_html( $hero_headline['value'] ); ?></h2>
                                            <?php if ( ! empty( $hero_headline['trend_label'] ) ) : ?>
                                                <span class="badge bg-soft-<?php echo esc_attr( $hero_headline['trend_class'] ); ?> text-<?php echo esc_attr( $hero_headline['trend_class'] ); ?> fw-semibold px-3 py-2"><?php echo esc_html( $hero_headline['trend_label'] ); ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <p class="mb-4 lead text-white-75"><?php echo esc_html__( 'Total Sales das squads neste ciclo. Compare com o período anterior e ajuste a estratégia em tempo real.', 'dap' ); ?></p>
                                    <?php else : ?>
                                        <h2 class="fw-semibold mb-3"><?php echo esc_html__( 'Centro de Controle Privilege', 'dap' ); ?></h2>
                                        <p class="mb-4 lead text-white-75"><?php echo esc_html__( 'Monitor squads, entregas e performance de campanhas em um dashboard inspirado no tema Modern do Ubold.', 'dap' ); ?></p>
                                    <?php endif; ?>
                                    <?php if ( ! empty( $sales_summary['metrics'] ) ) : ?>
                                        <div class="d-flex flex-wrap gap-4 mb-4">
                                            <?php foreach ( $sales_summary['metrics'] as $metric ) : ?>
                                                <div class="dap-hero-metric">
                                                    <p class="text-uppercase text-white-50 small mb-1"><?php echo esc_html( $metric['label'] ); ?></p>
                                                    <h4 class="fw-semibold mb-0"><?php echo esc_html( $metric['value'] ); ?></h4>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="d-flex flex-wrap gap-3">
                                        <?php if ( ! empty( $cta_url ) ) : ?>
                                            <a class="btn btn-light btn-lg fw-semibold" href="<?php echo esc_url( $cta_url ); ?>"><?php echo esc_html__( 'Ver squads e grupos', 'dap' ); ?></a>
                                        <?php endif; ?>
                                        <?php if ( ! empty( $elementor_cta ) ) : ?>
                                            <a class="btn btn-outline-light btn-lg" href="<?php echo esc_url( $elementor_cta ); ?>"><?php echo esc_html__( 'Editar widgets iniciais', 'dap' ); ?></a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-5 col-xl-6 col-xxl-5 text-center text-lg-end">
                                <div class="dap-hero-figure ms-lg-auto">
                                    <img src="<?php echo esc_url( $hero_image ); ?>" alt="<?php echo esc_attr__( 'Equipe colaborando em frente a dashboards', 'dap' ); ?>" class="img-fluid" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if ( ! empty( $sales_cards ) ) : ?>
            <div class="row gy-3 gx-3 gx-xl-4 mb-3" data-dap-grid="sales-cards">
                <?php foreach ( $sales_cards as $index => $card ) :
                    $sales_delay = min( 0.6, 0.08 * (int) $index );
                    $sales_style = sprintf( ' style="--dap-animate-delay: %.2fs;"', $sales_delay );
                    ?>
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="card dap-sales-card h-100 dap-card-animate" data-dap-animate="fade-up"<?php echo $sales_style; ?>>
                            <div class="card-body d-flex align-items-center gap-3">
                                <span class="dap-sales-icon bg-soft-<?php echo esc_attr( $card['trend_class'] ); ?> text-<?php echo esc_attr( $card['trend_class'] ); ?>">
                                    <i class="<?php echo esc_attr( $card['icon'] ); ?>"></i>
                                </span>
                                <div>
                                    <p class="text-muted text-uppercase small fw-semibold mb-1"><?php echo esc_html( $card['label'] ); ?></p>
                                    <h3 class="fw-semibold mb-1"><?php echo esc_html( $card['value'] ); ?></h3>
                                    <p class="mb-0">
                                        <span class="dap-sales-delta text-<?php echo esc_attr( $card['trend_class'] ); ?> fw-semibold"><?php echo esc_html( $card['trend_label'] ); ?></span>
                                        <?php if ( ! empty( $card['delta'] ) ) : ?>
                                            <span class="text-muted small ms-1"><?php echo esc_html( $card['delta'] ); ?></span>
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ( ! empty( $widget_area ) ) : ?>
            <div class="dap-elementor-widgets mb-3">
                <?php echo $widget_area; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            </div>
        <?php else : ?>
            <div class="dap-elementor-widgets mb-3">
                <div class="card border-0 shadow-none dap-elementor-placeholder dap-card-animate" data-dap-animate="fade-up" style="--dap-animate-delay: 0.35s;">
                    <div class="card-body p-4 p-md-5 d-flex flex-column flex-md-row align-items-md-center justify-content-between">
                        <div class="me-md-4">
                            <h4 class="fw-semibold mb-2"><?php echo esc_html__( 'Monte os cards iniciais com Elementor', 'dap' ); ?></h4>
                            <p class="mb-0 text-muted"><?php echo esc_html__( 'Abra a área de widgets para reproduzir os cards Modern originais e deixar o dashboard 100% fiel ao HTML.', 'dap' ); ?></p>
                        </div>
                        <?php if ( ! empty( $elementor_cta ) ) : ?>
                            <a class="btn btn-primary mt-3 mt-md-0" href="<?php echo esc_url( $elementor_cta ); ?>">
                                <?php echo esc_html__( 'Abrir Widgets Elementor', 'dap' ); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        <div class="row gy-3 gx-3 gx-xl-4 mb-3" data-dap-grid="kpis">
            <?php if ( ! empty( $kpis ) ) : ?>
                <?php foreach ( $kpis as $index => $kpi ) :
                    $kpi_delay = min( 0.65, 0.08 * (int) $index + 0.1 );
                    $kpi_style = sprintf( ' style="--dap-animate-delay: %.2fs;"', $kpi_delay );
                    ?>
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="card dap-kpi-card h-100 dap-card-animate" data-dap-animate="fade-up"<?php echo $kpi_style; ?>>
                            <div class="card-body d-flex align-items-center gap-3">
                                <span class="dap-kpi-chip" style="--dap-kpi-color: <?php echo esc_attr( isset( $kpi['chip_color'] ) ? $kpi['chip_color'] : '#00bab8' ); ?>;">
                                    <?php if ( ! empty( $kpi['icon'] ) ) : ?>
                                        <i class="<?php echo esc_attr( $kpi['icon'] ); ?>" aria-hidden="true"></i>
                                    <?php endif; ?>
                                </span>
                                <div>
                                    <p class="text-uppercase small fw-semibold text-muted mb-1"><?php echo esc_html( $kpi['label'] ); ?></p>
                                    <h3 class="fw-semibold mb-0"><?php echo esc_html( $kpi['value'] ); ?></h3>
                                    <p class="mb-0 text-muted small">
                                        <span class="fw-semibold text-<?php echo esc_attr( $kpi['accent'] ); ?>"><?php echo esc_html( $kpi['delta'] ); ?></span>
                                        <?php if ( ! empty( $kpi['delta_label'] ) ) : ?>
                                            <span class="text-muted ms-1"><?php echo esc_html( $kpi['delta_label'] ); ?></span>
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else : ?>
                <div class="col-12">
                    <div class="alert alert-info mb-0" role="status">
                        <?php echo esc_html__( 'Ainda não há dados suficientes para montar os indicadores.', 'dap' ); ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="row gy-3 gx-3 gx-xl-4 mb-3" data-dap-grid="charts">
            <div class="col-12 col-xl-7">
                <div class="card dap-sales-analytics h-100 dap-card-animate" data-dap-animate="fade-up" style="--dap-animate-delay: 0.2s;">
                    <div class="card-header border-0 d-flex flex-column flex-md-row align-items-md-center justify-content-between">
                        <div>
                            <p class="text-muted mb-1 text-uppercase small fw-semibold"><?php echo esc_html__( 'Análises de desempenho', 'dap' ); ?></p>
                            <h2 class="fw-semibold mb-0"><?php echo esc_html__( 'Projetos x interações', 'dap' ); ?></h2>
                            <p class="text-muted mb-0"><?php echo esc_html__( 'Compare publicações e comentários aprovados por período e ajuste squads rapidamente.', 'dap' ); ?></p>
                        </div>
                        <div class="btn-group dap-sales-analytics-filter mt-3 mt-md-0" role="group" aria-label="<?php esc_attr_e( 'Filtro de intervalo', 'dap' ); ?>" data-rotate-series="true" data-rotate-interval="9000">
                            <button type="button" class="btn btn-soft-primary active" data-series="monthly"><?php echo esc_html__( 'Mensal', 'dap' ); ?></button>
                            <button type="button" class="btn btn-soft-secondary" data-series="weekly"><?php echo esc_html__( 'Semanal', 'dap' ); ?></button>
                            <button type="button" class="btn btn-soft-secondary" data-series="today"><?php echo esc_html__( 'Hoje', 'dap' ); ?></button>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                            <div class="d-flex align-items-center">
                                <span class="badge bg-soft-success text-success px-3 py-2 fw-semibold"><?php echo esc_html( $project_growth_formatted ); ?></span>
                                <span class="text-muted ms-2"><?php echo esc_html( $project_growth_label ); ?></span>
                            </div>
                            <?php if ( ! empty( $cta_url ) ) : ?>
                                <a class="btn btn-soft-secondary" href="<?php echo esc_url( $cta_url ); ?>"><?php echo esc_html__( 'Ver detalhamento', 'dap' ); ?></a>
                            <?php endif; ?>
                        </div>
                        <div id="dap-sales-analytics" class="dap-chart" aria-hidden="true"></div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-xl-5">
                <div class="card h-100 dap-progress-card dap-card-animate" data-dap-animate="scale" style="--dap-animate-delay: 0.25s;">
                    <div class="card-header border-0">
                        <h4 class="card-title mb-1"><?php echo esc_html__( 'Em andamento', 'dap' ); ?></h4>
                        <p class="text-muted mb-0"><?php echo esc_html__( 'Equipes executando neste momento', 'dap' ); ?></p>
                    </div>
                    <div class="card-body pt-0">
                        <div id="dap-progress-radial" class="dap-chart" aria-hidden="true"></div>
                        <ul class="list-unstyled mt-3 mb-0">
                            <li class="d-flex justify-content-between py-1">
                                <span><?php echo esc_html__( 'Squad Growth', 'dap' ); ?></span>
                                <span class="badge bg-soft-success text-success fw-semibold">32%</span>
                            </li>
                            <li class="d-flex justify-content-between py-1">
                                <span><?php echo esc_html__( 'Squad CRM', 'dap' ); ?></span>
                                <span class="badge bg-soft-primary text-primary fw-semibold">26%</span>
                            </li>
                            <li class="d-flex justify-content-between py-1">
                                <span><?php echo esc_html__( 'Squad Media', 'dap' ); ?></span>
                                <span class="badge bg-soft-warning text-warning fw-semibold">18%</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <div class="row gy-3 gx-3 gx-xl-4 mb-3" data-dap-grid="tables">
            <div class="col-12 col-xl-7">
                <div class="card dap-inventory h-100 dap-card-animate" data-dap-animate="fade-up" style="--dap-animate-delay: 0.18s;">
                    <div class="card-header border-0 d-flex flex-column flex-md-row justify-content-between align-items-md-center">
                        <div>
                            <h4 class="card-title mb-1"><?php echo esc_html__( 'Inventário de produtos', 'dap' ); ?></h4>
                            <p class="text-muted mb-0"><?php echo esc_html__( 'Acompanhe estoque e status dos produtos digitais.', 'dap' ); ?></p>
                        </div>
                        <div class="mt-3 mt-md-0">
                            <span class="badge bg-soft-primary text-primary fw-semibold"><?php printf( esc_html__( '%s itens', 'dap' ), number_format_i18n( count( $inventory_rows ) ) ); ?></span>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-centered align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th><?php echo esc_html__( 'Produto', 'dap' ); ?></th>
                                    <th><?php echo esc_html__( 'Categoria', 'dap' ); ?></th>
                                    <th><?php echo esc_html__( 'Estoque', 'dap' ); ?></th>
                                    <th><?php echo esc_html__( 'Status', 'dap' ); ?></th>
                                    <th class="text-end"><?php echo esc_html__( 'Preço', 'dap' ); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ( ! empty( $inventory_rows ) ) : ?>
                                    <?php foreach ( $inventory_rows as $row ) : ?>
                                        <tr>
                                            <td class="fw-semibold"><?php echo esc_html( $row['product'] ); ?></td>
                                            <td><?php echo esc_html( $row['category'] ); ?></td>
                                            <td>
                                                <span class="badge bg-soft-info text-info fw-semibold px-3 py-2"><?php echo esc_html( number_format_i18n( $row['stock'] ) ); ?></span>
                                            </td>
                                            <td>
                                                <span class="badge bg-soft-<?php echo esc_attr( $row['status'] ); ?> text-<?php echo esc_attr( $row['status'] ); ?> fw-semibold"><?php echo esc_html( $row['status_label'] ); ?></span>
                                            </td>
                                            <td class="text-end fw-semibold"><?php echo esc_html( $row['price'] ); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4"><?php echo esc_html__( 'Nenhum item encontrado no momento.', 'dap' ); ?></td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-12 col-xl-5">
                <div class="card dap-orders h-100 dap-card-animate" data-dap-animate="fade-up" style="--dap-animate-delay: 0.24s;">
                    <div class="card-header border-0 d-flex flex-column flex-md-row justify-content-between align-items-md-center">
                        <div>
                            <h4 class="card-title mb-1"><?php echo esc_html__( 'Pedidos recentes', 'dap' ); ?></h4>
                            <p class="text-muted mb-0"><?php echo esc_html__( 'Pedidos confirmados e em processamento.', 'dap' ); ?></p>
                        </div>
                        <div class="mt-3 mt-md-0">
                            <a class="btn btn-soft-secondary btn-sm" href="<?php echo esc_url( admin_url( 'edit-comments.php' ) ); ?>"><?php echo esc_html__( 'Ver tudo', 'dap' ); ?></a>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-centered align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th><?php echo esc_html__( 'Pedido', 'dap' ); ?></th>
                                    <th><?php echo esc_html__( 'Cliente', 'dap' ); ?></th>
                                    <th><?php echo esc_html__( 'Data', 'dap' ); ?></th>
                                    <th><?php echo esc_html__( 'Status', 'dap' ); ?></th>
                                    <th class="text-end"><?php echo esc_html__( 'Valor', 'dap' ); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ( ! empty( $orders_rows ) ) : ?>
                                    <?php foreach ( $orders_rows as $order ) : ?>
                                        <tr>
                                            <td class="fw-semibold"><?php echo esc_html( $order['order'] ); ?></td>
                                            <td><?php echo esc_html( $order['customer'] ); ?></td>
                                            <td><?php echo esc_html( $order['date'] ); ?></td>
                                            <td>
                                                <span class="badge bg-soft-<?php echo esc_attr( $order['status'] ); ?> text-<?php echo esc_attr( $order['status'] ); ?> fw-semibold"><?php echo esc_html( $order['status_label'] ); ?></span>
                                            </td>
                                            <td class="text-end fw-semibold"><?php echo esc_html( $order['amount'] ); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4"><?php echo esc_html__( 'Nenhum pedido recente encontrado.', 'dap' ); ?></td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="row gy-3 gx-3 gx-xl-4 mb-3" data-dap-grid="projects">
            <div class="col-12 col-xl-5">
                <div class="card h-100 dap-card-animate" data-dap-animate="fade-up" style="--dap-animate-delay: 0.2s;">
                    <div class="card-header border-0">
                        <h4 class="card-title mb-1"><?php echo esc_html__( 'Categorias de e-mail', 'dap' ); ?></h4>
                        <p class="text-muted mb-0"><?php echo esc_html__( 'Disparo por tipo de campanha', 'dap' ); ?></p>
                    </div>
                    <div class="card-body pt-0">
                        <div id="dap-email-categories" class="dap-chart" aria-hidden="true"></div>
                        <ul class="list-unstyled mt-3 mb-0">
                            <?php if ( ! empty( $email_labels ) ) : ?>
                                <?php foreach ( $email_labels as $index => $label ) :
                                    $value      = isset( $email_series[ $index ] ) ? (int) $email_series[ $index ] : 0;
                                    $percentage = $email_total > 0 ? round( ( $value / $email_total ) * 100 ) : 0;
                                    $ui_color   = $email_colors[ $index % count( $email_colors ) ];
                                    ?>
                                    <li class="d-flex justify-content-between align-items-center py-1">
                                        <span><i class="ri-checkbox-blank-circle-fill text-<?php echo esc_attr( $ui_color ); ?> me-2"></i><?php echo esc_html( $label ); ?></span>
                                        <span class="fw-semibold"><?php echo esc_html( $percentage ); ?>%</span>
                                    </li>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <li class="py-2 text-muted"><?php echo esc_html__( 'Nenhuma categoria encontrada.', 'dap' ); ?></li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-12 col-xl-7">
                <div class="card h-100 dap-card-animate" data-dap-animate="fade-up" style="--dap-animate-delay: 0.22s;">
                    <div class="card-header border-0 d-flex align-items-center justify-content-between flex-wrap">
                        <div>
                            <h4 class="card-title mb-1"><?php echo esc_html__( 'Projetos recentes', 'dap' ); ?></h4>
                            <p class="text-muted mb-0"><?php echo esc_html__( 'Monitor as últimas movimentações das squads', 'dap' ); ?></p>
                        </div>
                        <div class="d-flex gap-2 mt-3 mt-md-0">
                            <a class="btn btn-soft-primary" href="<?php echo esc_url( $add_project_url ); ?>"><?php echo esc_html__( 'Adicionar projeto', 'dap' ); ?></a>
                            <a class="btn btn-soft-secondary" href="<?php echo esc_url( $export_projects_url ); ?>"><?php echo esc_html__( 'Exportar CSV', 'dap' ); ?></a>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-centered align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <?php foreach ( $project_table_header as $heading ) : ?>
                                        <th><?php echo esc_html( $heading ); ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ( ! empty( $project_rows ) ) : ?>
                                    <?php foreach ( $project_rows as $row ) : ?>
                                        <tr>
                                            <td class="fw-semibold"><?php echo esc_html( $row['project'] ); ?></td>
                                            <td><?php echo esc_html( $row['owner'] ); ?></td>
                                            <td>
                                                <div class="dap-progress-bar">
                                                    <span style="width: <?php echo esc_attr( $row['progress'] ); ?>;"></span>
                                                    <strong><?php echo esc_html( $row['progress'] ); ?></strong>
                                                </div>
                                            </td>
                                            <td class="text-end">
                                                <span class="badge px-3 py-2 fw-semibold bg-soft-<?php echo esc_attr( $row['badge'] ); ?> text-<?php echo esc_attr( $row['badge'] ); ?>">
                                                    <?php echo esc_html( $row['status'] ); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted"><?php echo esc_html__( 'Sem movimentações recentes.', 'dap' ); ?></td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="row gy-3 gx-3 gx-xl-4 mb-3" data-dap-grid="logs">
            <div class="col-12 col-xxl-8">
                <div class="card dap-error-logs h-100 dap-card-animate" data-dap-animate="fade-up" style="--dap-animate-delay: 0.28s;">
                    <div class="card-header border-0 pb-0 d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                        <div>
                            <p class="text-muted mb-1 text-uppercase small fw-semibold"><?php echo esc_html__( 'Registros do plugin', 'dap' ); ?></p>
                            <h2 class="fw-semibold mb-0"><?php echo esc_html__( 'Últimos erros registrados', 'dap' ); ?></h2>
                            <p class="text-muted mb-0"><?php echo esc_html__( 'Consulte os eventos capturados pelo plugin e compartilhe com a equipe técnica para depuração.', 'dap' ); ?></p>
                        </div>
                        <div class="d-flex flex-column flex-md-row gap-2">
                            <span class="text-muted small"><?php echo esc_html__( 'Use dap_record_error_log() para adicionar entradas personalizadas.', 'dap' ); ?></span>
                            <?php if ( ! empty( $clear_logs_url ) ) : ?>
                                <a class="btn btn-soft-danger" href="<?php echo esc_url( $clear_logs_url ); ?>"><?php echo esc_html__( 'Limpar registros', 'dap' ); ?></a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if ( $logs_recently_cleared ) : ?>
                            <div class="alert alert-success py-2 px-3 mb-3">
                                <?php echo esc_html__( 'Logs limpos com sucesso.', 'dap' ); ?>
                            </div>
                        <?php endif; ?>
                        <?php if ( ! empty( $error_logs ) ) : ?>
                            <ul class="dap-log-list list-unstyled mb-0">
                                <?php foreach ( $error_logs as $log ) :
                                    $log_time    = isset( $log['time'] ) ? $log['time'] : '';
                                    $log_message = isset( $log['message'] ) ? $log['message'] : '';
                                    $log_context = isset( $log['context'] ) && is_array( $log['context'] ) ? $log['context'] : [];
                                    $timestamp   = $log_time ? strtotime( $log_time ) : false;
                                    $formatted   = $timestamp ? date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $timestamp ) : '';
                                    ?>
                                    <li class="dap-log-item py-3">
                                        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2">
                                            <span class="badge bg-soft-danger text-danger fw-semibold text-uppercase small"><?php echo esc_html__( 'Erro', 'dap' ); ?></span>
                                            <?php if ( $formatted ) : ?>
                                                <span class="text-muted small text-md-end"><?php echo esc_html( $formatted ); ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <p class="fw-semibold mt-3 mb-2 text-body"><?php echo esc_html( $log_message ); ?></p>
                                        <?php if ( ! empty( $log_context ) ) : ?>
                                            <dl class="dap-log-context mb-0">
                                                <?php foreach ( $log_context as $context_key => $context_value ) : ?>
                                                    <div class="d-flex flex-column flex-sm-row gap-1 gap-sm-2">
                                                        <dt class="text-muted small text-uppercase fw-semibold mb-0"><?php echo esc_html( $context_key ); ?>:</dt>
                                                        <dd class="text-muted small mb-0 flex-grow-1"><?php echo esc_html( $context_value ); ?></dd>
                                                    </div>
                                                <?php endforeach; ?>
                                            </dl>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else : ?>
                            <div class="dap-log-empty text-center py-4">
                                <p class="fw-semibold mb-1"><?php echo esc_html__( 'Nenhum erro capturado até agora.', 'dap' ); ?></p>
                                <p class="text-muted mb-0"><?php echo esc_html__( 'Quando o plugin registrar inconsistências elas aparecerão automaticamente aqui.', 'dap' ); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-12 col-xxl-4">
                <div class="card h-100 dap-diagnostics dap-card-animate" data-dap-animate="fade-up" style="--dap-animate-delay: 0.32s;">
                    <div class="card-header border-0 pb-0">
                        <p class="text-muted mb-1 text-uppercase small fw-semibold"><?php echo esc_html__( 'Diagnóstico', 'dap' ); ?></p>
                        <h2 class="fw-semibold mb-0"><?php echo esc_html__( 'Status do painel', 'dap' ); ?></h2>
                        <p class="text-muted mb-0"><?php echo esc_html__( 'Revise os pontos abaixo para garantir que o visual Modern e os dados estejam ativos.', 'dap' ); ?></p>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled mb-0 dap-diagnostics-list">
                            <li class="dap-diagnostics-item d-flex align-items-start mb-3">
                                <span class="dap-diagnostics-dot <?php echo $diagnostics_has_assets ? 'bg-success' : 'bg-warning'; ?>"></span>
                                <div class="ms-3">
                                    <h6 class="mb-1 fw-semibold"><?php echo esc_html__( 'Tema Ubold detectado', 'dap' ); ?></h6>
                                    <p class="mb-0 text-muted small">
                                        <?php if ( $diagnostics_has_assets ) : ?>
                                            <?php echo esc_html__( 'Arquivos locais encontrados. O layout Modern será aplicado integralmente.', 'dap' ); ?>
                                        <?php else : ?>
                                            <?php echo esc_html__( 'Usando CSS/JS fallback. Copie a pasta Docs/assets do Ubold para restaurar o visual completo.', 'dap' ); ?>
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </li>
                            <li class="dap-diagnostics-item d-flex align-items-start mb-3">
                                <span class="dap-diagnostics-dot <?php echo $diagnostics_global_skin ? 'bg-success' : 'bg-warning'; ?>"></span>
                                <div class="ms-3">
                                    <h6 class="mb-1 fw-semibold"><?php echo esc_html__( 'Skin global aplicada', 'dap' ); ?></h6>
                                    <p class="mb-0 text-muted small">
                                        <?php if ( $diagnostics_global_skin ) : ?>
                                            <?php echo esc_html__( 'Todas as telas do wp-admin usam a pele Ubold.', 'dap' ); ?>
                                        <?php else : ?>
                                            <?php echo esc_html__( 'Ative a opção "Habilitar skin global" nas configurações para estilizar o admin completo.', 'dap' ); ?>
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </li>
                            <li class="dap-diagnostics-item d-flex align-items-start mb-3">
                                <span class="dap-diagnostics-dot <?php echo ( $diagnostics_widget_exists && $diagnostics_widget_elementor ) ? 'bg-success' : 'bg-warning'; ?>"></span>
                                <div class="ms-3">
                                    <h6 class="mb-1 fw-semibold"><?php echo esc_html__( 'Canvas Elementor', 'dap' ); ?></h6>
                                    <p class="mb-0 text-muted small">
                                        <?php if ( $diagnostics_widget_exists ) : ?>
                                            <?php if ( $diagnostics_widget_elementor ) : ?>
                                                <?php echo esc_html__( 'Pronto para edição no Elementor. Personalize os cards introdutórios.', 'dap' ); ?>
                                            <?php else : ?>
                                                <?php echo esc_html__( 'Área criada, mas o Elementor não está ativo. Ative o plugin para editar visualmente.', 'dap' ); ?>
                                            <?php endif; ?>
                                        <?php else : ?>
                                            <?php echo esc_html__( 'A área será criada automaticamente após a primeira carga do painel.', 'dap' ); ?>
                                        <?php endif; ?>
                                    </p>
                            <?php if ( $diagnostics_widget_exists && $diagnostics_widget_edit ) : ?>
                                <a class="small fw-semibold d-inline-flex align-items-center gap-1 mt-2" href="<?php echo esc_url( $diagnostics_widget_edit ); ?>">
                                    <span class="ri-pencil-line" aria-hidden="true"></span>
                                    <?php echo esc_html__( 'Editar conteúdo', 'dap' ); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </li>
                    <li class="dap-diagnostics-item d-flex align-items-start mb-3">
                        <span class="dap-diagnostics-dot <?php echo $diagnostics_wc_active ? 'bg-success' : 'bg-warning'; ?>"></span>
                        <div class="ms-3">
                            <h6 class="mb-1 fw-semibold"><?php echo esc_html__( 'WooCommerce', 'dap' ); ?></h6>
                            <?php if ( $diagnostics_wc_active ) : ?>
                                <p class="mb-0 text-muted small">
                                    <?php
                                    printf(
                                        /* translators: 1: order count, 2: pending invoices, 3: revenue */
                                        esc_html__( '%1$s pedidos nos últimos 7 dias · %2$s pendentes · %3$s em 30 dias', 'dap' ),
                                        number_format_i18n( $diagnostics_wc_orders ),
                                        number_format_i18n( $diagnostics_wc_pending ),
                                        $diagnostics_wc_currency ? dap_format_currency_short( $diagnostics_wc_month_total, $diagnostics_wc_currency ) : dap_format_currency_short( $diagnostics_wc_month_total )
                                    );
                                    ?>
                                </p>
                            <?php else : ?>
                                <p class="mb-0 text-muted small"><?php echo esc_html__( 'Ative o plugin WooCommerce para exibir pedidos e métricas reais.', 'dap' ); ?></p>
                            <?php endif; ?>
                        </div>
                    </li>
                    <li class="dap-diagnostics-item d-flex align-items-start">
                        <span class="dap-diagnostics-dot <?php echo ( 'hit' === $diagnostics_dataset_state ) ? 'bg-success' : 'bg-info'; ?>"></span>
                        <div class="ms-3">
                            <h6 class="mb-1 fw-semibold"><?php echo esc_html__( 'Dados do dashboard', 'dap' ); ?></h6>
                            <p class="mb-0 text-muted small">
                                        <?php if ( 'hit' === $diagnostics_dataset_state && $diagnostics_dataset_label ) : ?>
                                            <?php printf( esc_html__( 'Cache atualizado em %s.', 'dap' ), esc_html( $diagnostics_dataset_label ) ); ?>
                                        <?php else : ?>
                                            <?php echo esc_html__( 'O painel será populado automaticamente conforme posts e comentários forem registrados.', 'dap' ); ?>
                                        <?php endif; ?>
                                    </p>
                                    <p class="mb-0 text-muted small mt-1">
                                        <?php
                                        printf(
                                            /* translators: 1: KPI count, 2: project rows, 3: inventory rows, 4: order rows */
                                            esc_html__( '%1$s indicadores • %2$s projetos • %3$s itens • %4$s pedidos', 'dap' ),
                                            number_format_i18n( $diagnostics_dataset_kpis ),
                                            number_format_i18n( $diagnostics_dataset_projects ),
                                            number_format_i18n( $diagnostics_inventory ),
                                            number_format_i18n( $diagnostics_orders )
                                        );
                                        ?>
                                    </p>
                                </div>
                            </li>
                        </ul>
                    </div>
                    <div class="card-footer border-0 pt-0 d-flex flex-column gap-2">
                        <?php if ( $diagnostics_settings ) : ?>
                            <a class="btn btn-soft-primary" href="<?php echo esc_url( $diagnostics_settings ); ?>"><?php echo esc_html__( 'Abrir configurações', 'dap' ); ?></a>
                        <?php endif; ?>
                        <?php if ( $diagnostics_widget_url ) : ?>
                            <a class="btn btn-soft-secondary" href="<?php echo esc_url( $diagnostics_widget_url ); ?>"><?php echo esc_html__( 'Gerenciar widgets', 'dap' ); ?></a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="row gy-3 gx-3 gx-xl-4" data-dap-grid="activity">
            <div class="col-12 col-xl-7">
                <div class="card h-100 dap-card-animate" data-dap-animate="fade-up" style="--dap-animate-delay: 0.28s;">
                    <div class="card-header border-0">
                        <h4 class="card-title mb-1"><?php echo esc_html__( 'Atividades', 'dap' ); ?></h4>
                        <p class="text-muted mb-0"><?php echo esc_html__( 'Últimas atualizações registradas pelas squads', 'dap' ); ?></p>
                    </div>
                    <div class="card-body pt-0">
                        <ul class="list-group list-group-flush dap-activity-list">
                            <?php if ( ! empty( $activity_items ) ) : ?>
                                <?php foreach ( $activity_items as $item ) : ?>
                                    <li class="list-group-item d-flex align-items-start">
                                        <div class="flex-shrink-0 dap-activity-dot bg-<?php echo esc_attr( $item['color'] ); ?>"></div>
                                        <div class="ms-3">
                                            <h6 class="mb-1"><?php echo esc_html( $item['title'] ); ?></h6>
                                            <p class="mb-0 text-muted small"><?php echo esc_html( $item['meta'] ); ?></p>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <li class="list-group-item text-center text-muted py-4"><?php echo esc_html__( 'Sem registros de atividade.', 'dap' ); ?></li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-12 col-xl-5">
                <div class="card h-100 dap-important-projects dap-card-animate" data-dap-animate="fade-up" style="--dap-animate-delay: 0.34s;">
                    <div class="card-header border-0">
                        <h4 class="card-title mb-1"><?php echo esc_html__( 'Projetos importantes', 'dap' ); ?></h4>
                        <p class="text-muted mb-0"><?php echo esc_html__( 'Foco imediato para liderança', 'dap' ); ?></p>
                    </div>
                    <div class="card-body pt-0">
                        <ul class="list-unstyled mb-0">
                            <?php if ( ! empty( $important_projects ) ) : ?>
                                <?php foreach ( $important_projects as $item ) : ?>
                                    <li class="d-flex align-items-start mb-3">
                                        <div class="flex-shrink-0 dap-important-icon bg-soft-<?php echo esc_attr( $item['badge'] ); ?> text-<?php echo esc_attr( $item['badge'] ); ?>">
                                            <i class="ri-pie-chart-2-line"></i>
                                        </div>
                                        <div class="ms-3">
                                            <h6 class="mb-1 fw-semibold"><?php echo esc_html( $item['name'] ); ?></h6>
                                            <span class="badge bg-soft-<?php echo esc_attr( $item['badge'] ); ?> text-<?php echo esc_attr( $item['badge'] ); ?> fw-semibold"><?php echo esc_html( $item['status'] ); ?></span>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <li class="text-muted py-2"><?php echo esc_html__( 'Nenhum projeto em destaque no momento.', 'dap' ); ?></li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
</div>
</div>
