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
$project_rows           = isset( $dashboard_dataset['project_rows'] ) && is_array( $dashboard_dataset['project_rows'] ) ? $dashboard_dataset['project_rows'] : [];
$activity_items         = isset( $dashboard_dataset['activity_items'] ) && is_array( $dashboard_dataset['activity_items'] ) ? $dashboard_dataset['activity_items'] : [];
$important_projects     = isset( $dashboard_dataset['important_projects'] ) && is_array( $dashboard_dataset['important_projects'] ) ? $dashboard_dataset['important_projects'] : [];
$charts                 = isset( $dashboard_dataset['charts'] ) && is_array( $dashboard_dataset['charts'] ) ? $dashboard_dataset['charts'] : [];
$project_statistics     = isset( $charts['projectStatistics'] ) && is_array( $charts['projectStatistics'] ) ? $charts['projectStatistics'] : [];
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
$project_growth_delta   = 0.0;

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
    esc_html__( 'Project', 'dap' ),
    esc_html__( 'Owner', 'dap' ),
    esc_html__( 'Progress', 'dap' ),
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
<div class="dap-admin">
    <div class="dap-dashboard container-fluid px-3 px-md-4 pb-5">
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
        <div class="row g-4 mb-4">
            <div class="col-12">
                <div class="card dap-hero overflow-hidden text-white">
                    <div class="card-body p-4 p-lg-5 d-flex flex-column flex-lg-row align-items-lg-center">
                        <div class="dap-hero-copy pe-lg-5 mb-4 mb-lg-0">
                            <span class="badge bg-soft-light text-uppercase text-white-50 fw-semibold mb-3"><?php echo esc_html__( 'Welcome back', 'dap' ); ?></span>
                            <h1 class="fw-semibold mb-3"><?php echo esc_html__( 'Privilege Control Center', 'dap' ); ?></h1>
                            <p class="mb-4 lead text-white-75"><?php echo esc_html__( 'Monitor squads, entregas e performance de campanhas em um dashboard inspirado no tema Modern do Ubold.', 'dap' ); ?></p>
                            <?php if ( ! empty( $cta_url ) ) : ?>
                                <a class="btn btn-light btn-lg fw-semibold" href="<?php echo esc_url( $cta_url ); ?>">
                                    <?php echo esc_html__( 'Ver squads e grupos', 'dap' ); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                        <div class="dap-hero-figure ms-lg-auto text-center">
                            <img src="<?php echo esc_url( $hero_image ); ?>" alt="<?php echo esc_attr__( 'Equipe colaborando em frente a dashboards', 'dap' ); ?>" class="img-fluid" />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if ( ! empty( $widget_area ) ) : ?>
            <div class="dap-elementor-widgets mb-4">
                <?php echo $widget_area; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            </div>
        <?php else : ?>
            <div class="dap-elementor-widgets mb-4">
                <div class="card border-0 shadow-none dap-elementor-placeholder">
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
        <div class="row g-4 mb-4">
            <?php if ( ! empty( $kpis ) ) : ?>
                <?php foreach ( $kpis as $kpi ) : ?>
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="card dap-kpi-card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <span class="dap-kpi-chip bg-soft-<?php echo esc_attr( $kpi['accent'] ); ?> text-<?php echo esc_attr( $kpi['accent'] ); ?>"></span>
                                    <span class="text-muted text-uppercase fw-semibold small"><?php echo esc_html( $kpi['label'] ); ?></span>
                                </div>
                                <h2 class="fw-semibold mb-2"><?php echo esc_html( $kpi['value'] ); ?></h2>
                                <p class="mb-0 text-muted">
                                    <span class="fw-semibold text-<?php echo esc_attr( $kpi['accent'] ); ?>"><?php echo esc_html( $kpi['delta'] ); ?></span>
                                    <?php if ( ! empty( $kpi['delta_label'] ) ) : ?>
                                        <span class="text-muted ms-1"><?php echo esc_html( $kpi['delta_label'] ); ?></span>
                                    <?php endif; ?>
                                </p>
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

        <div class="row g-4 mb-4">
            <div class="col-12 col-xl-8">
                <div class="card dap-project-statistics h-100">
                    <div class="card-header border-0 d-flex flex-column flex-md-row align-items-md-center justify-content-between">
                        <div>
                            <p class="text-muted mb-1 text-uppercase small fw-semibold"><?php echo esc_html__( 'Project Statistics', 'dap' ); ?></p>
                            <h2 class="fw-semibold mb-0"><?php echo esc_html__( 'Performance Overview', 'dap' ); ?></h2>
                            <p class="text-muted mb-0"><?php echo esc_html__( 'Acompanhe entregas por período e ajuste squads rapidamente.', 'dap' ); ?></p>
                        </div>
                        <div class="btn-group dap-project-statistics-filter mt-3 mt-md-0" role="group" aria-label="<?php esc_attr_e( 'Filtro de intervalo', 'dap' ); ?>">
                            <button type="button" class="btn btn-soft-primary active" data-series="monthly"><?php echo esc_html__( 'Monthly', 'dap' ); ?></button>
                            <button type="button" class="btn btn-soft-secondary" data-series="weekly"><?php echo esc_html__( 'Weekly', 'dap' ); ?></button>
                            <button type="button" class="btn btn-soft-secondary" data-series="today"><?php echo esc_html__( 'Today', 'dap' ); ?></button>
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
                        <div id="dap-project-statistics" class="dap-chart" aria-hidden="true"></div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-xl-4">
                <div class="card h-100 dap-progress-card">
                    <div class="card-header border-0">
                        <h4 class="card-title mb-1"><?php echo esc_html__( 'On Progress', 'dap' ); ?></h4>
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
        <div class="row g-4 mb-4">
            <div class="col-12 col-xl-4">
                <div class="card h-100">
                    <div class="card-header border-0">
                        <h4 class="card-title mb-1"><?php echo esc_html__( 'Email Categories', 'dap' ); ?></h4>
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
            <div class="col-12 col-xl-8">
                <div class="card h-100">
                    <div class="card-header border-0 d-flex align-items-center justify-content-between flex-wrap">
                        <div>
                            <h4 class="card-title mb-1"><?php echo esc_html__( 'Recent Projects', 'dap' ); ?></h4>
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
        <div class="row g-4 mb-4">
            <div class="col-12 col-xxl-8">
                <div class="card dap-error-logs h-100">
                    <div class="card-header border-0 pb-0 d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                        <div>
                            <p class="text-muted mb-1 text-uppercase small fw-semibold"><?php echo esc_html__( 'Plugin Logs', 'dap' ); ?></p>
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
                <div class="card h-100 dap-diagnostics">
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
                                            /* translators: 1: KPI count, 2: project rows */
                                            esc_html__( '%1$s indicadores • %2$s projetos recentes', 'dap' ),
                                            number_format_i18n( $diagnostics_dataset_kpis ),
                                            number_format_i18n( $diagnostics_dataset_projects )
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
        <div class="row g-4">
            <div class="col-12 col-xl-7">
                <div class="card h-100">
                    <div class="card-header border-0">
                        <h4 class="card-title mb-1"><?php echo esc_html__( 'Activity', 'dap' ); ?></h4>
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
                <div class="card h-100 dap-important-projects">
                    <div class="card-header border-0">
                        <h4 class="card-title mb-1"><?php echo esc_html__( 'Important Projects', 'dap' ); ?></h4>
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
