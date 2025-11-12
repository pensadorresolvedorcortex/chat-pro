<?php
/**
 * Dashboard view that renders the Ubold layout.
 *
 * @package DashboardAgenciaPrivilege
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$widget_area   = isset( $data['widget_area'] ) ? $data['widget_area'] : '';
$cta_url       = isset( $data['cta_url'] ) ? $data['cta_url'] : '';
$elementor_cta = isset( $data['elementor_cta'] ) ? $data['elementor_cta'] : '';

$hero_image = DAP_URL . 'assets/ubold/assets/images/hero-1.png';
if ( ! dap_asset_exists( 'assets/ubold/assets/images/hero-1.png' ) ) {
    $hero_image = DAP_URL . 'assets/images/hero-placeholder.svg';
}

$kpis = [
    [
        'label'       => esc_html__( 'Total Projects', 'dap' ),
        'value'       => '148',
        'delta'       => '+12%',
        'delta_label' => esc_html__( 'vs last sprint', 'dap' ),
        'accent'      => 'primary',
    ],
    [
        'label'       => esc_html__( 'New Projects', 'dap' ),
        'value'       => '26',
        'delta'       => '+4',
        'delta_label' => esc_html__( 'launched this week', 'dap' ),
        'accent'      => 'success',
    ],
    [
        'label'       => esc_html__( 'On Progress', 'dap' ),
        'value'       => '72%',
        'delta'       => '+6%',
        'delta_label' => esc_html__( 'uptime on track', 'dap' ),
        'accent'      => 'info',
    ],
    [
        'label'       => esc_html__( 'Unfinished', 'dap' ),
        'value'       => '9',
        'delta'       => esc_html__( 'needs attention', 'dap' ),
        'delta_label' => '',
        'accent'      => 'danger',
    ],
];

$project_rows = [
    [ 'project' => esc_html__( 'Privilege Commerce Revamp', 'dap' ), 'owner' => 'Squad Growth', 'status' => esc_html__( 'In Review', 'dap' ), 'badge' => 'warning', 'progress' => '78%' ],
    [ 'project' => esc_html__( 'CRM Automation Flows', 'dap' ), 'owner' => 'Squad CRM', 'status' => esc_html__( 'Live', 'dap' ), 'badge' => 'success', 'progress' => '100%' ],
    [ 'project' => esc_html__( 'Influencer Campaign Blitz', 'dap' ), 'owner' => 'Squad Media', 'status' => esc_html__( 'On Hold', 'dap' ), 'badge' => 'danger', 'progress' => '35%' ],
    [ 'project' => esc_html__( 'Privilege App 3.0', 'dap' ), 'owner' => 'Squad Product', 'status' => esc_html__( 'Development', 'dap' ), 'badge' => 'primary', 'progress' => '62%' ],
];

$activity_items = [
    [
        'color' => 'primary',
        'title' => esc_html__( 'Design system tokens merged to main', 'dap' ),
        'meta'  => esc_html__( '09:24 · Added by Júlia Martins', 'dap' ),
    ],
    [
        'color' => 'success',
        'title' => esc_html__( 'CRM automation paused for QA approval', 'dap' ),
        'meta'  => esc_html__( '11:03 · Workflow Bot', 'dap' ),
    ],
    [
        'color' => 'warning',
        'title' => esc_html__( 'Media team awaiting creatives for Blitz', 'dap' ),
        'meta'  => esc_html__( '14:17 · Comment by Joana Reis', 'dap' ),
    ],
    [
        'color' => 'info',
        'title' => esc_html__( 'Inbound squad scheduled content refresh', 'dap' ),
        'meta'  => esc_html__( '16:42 · Calendar Sync', 'dap' ),
    ],
];

$important_projects = [
    [
        'name'   => esc_html__( 'Experience Hub 2.0', 'dap' ),
        'badge'  => 'primary',
        'status' => esc_html__( 'Milestone due tomorrow', 'dap' ),
    ],
    [
        'name'   => esc_html__( 'Privilege Rewards Launch', 'dap' ),
        'badge'  => 'success',
        'status' => esc_html__( 'Go-live confirmed', 'dap' ),
    ],
    [
        'name'   => esc_html__( 'Retail Analytics Dashboard', 'dap' ),
        'badge'  => 'info',
        'status' => esc_html__( 'Stakeholder review Friday', 'dap' ),
    ],
];

$project_table_header = [
    esc_html__( 'Project', 'dap' ),
    esc_html__( 'Owner', 'dap' ),
    esc_html__( 'Progress', 'dap' ),
    esc_html__( 'Status', 'dap' ),
];
?>
<div class="dap-admin">
    <div class="dap-dashboard container-fluid px-3 px-md-4 pb-5">
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
                                <span class="badge bg-soft-success text-success px-3 py-2 fw-semibold">+18.3%</span>
                                <span class="text-muted ms-2"><?php echo esc_html__( 'Conclusões acima da meta', 'dap' ); ?></span>
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
                            <li class="d-flex justify-content-between align-items-center py-1">
                                <span><i class="ri-checkbox-blank-circle-fill text-primary me-2"></i><?php echo esc_html__( 'Campanhas sazonais', 'dap' ); ?></span>
                                <span class="fw-semibold">45%</span>
                            </li>
                            <li class="d-flex justify-content-between align-items-center py-1">
                                <span><i class="ri-checkbox-blank-circle-fill text-success me-2"></i><?php echo esc_html__( 'Fluxos automáticos', 'dap' ); ?></span>
                                <span class="fw-semibold">28%</span>
                            </li>
                            <li class="d-flex justify-content-between align-items-center py-1">
                                <span><i class="ri-checkbox-blank-circle-fill text-warning me-2"></i><?php echo esc_html__( 'Nutrição leads', 'dap' ); ?></span>
                                <span class="fw-semibold">19%</span>
                            </li>
                            <li class="d-flex justify-content-between align-items-center py-1">
                                <span><i class="ri-checkbox-blank-circle-fill text-info me-2"></i><?php echo esc_html__( 'Transacionais', 'dap' ); ?></span>
                                <span class="fw-semibold">8%</span>
                            </li>
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
                            <a class="btn btn-soft-primary" href="#"><?php echo esc_html__( 'Adicionar projeto', 'dap' ); ?></a>
                            <a class="btn btn-soft-secondary" href="#"><?php echo esc_html__( 'Exportar CSV', 'dap' ); ?></a>
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
                            </tbody>
                        </table>
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
                            <?php foreach ( $activity_items as $item ) : ?>
                                <li class="list-group-item d-flex align-items-start">
                                    <div class="flex-shrink-0 dap-activity-dot bg-<?php echo esc_attr( $item['color'] ); ?>"></div>
                                    <div class="ms-3">
                                        <h6 class="mb-1"><?php echo esc_html( $item['title'] ); ?></h6>
                                        <p class="mb-0 text-muted small"><?php echo esc_html( $item['meta'] ); ?></p>
                                    </div>
                                </li>
                            <?php endforeach; ?>
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
                        </ul>
                    </div>
                </div>
            </div>
        </div>
</div>
</div>
