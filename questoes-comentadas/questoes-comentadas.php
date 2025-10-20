<?php
/**
 * Plugin Name: Questões Comentadas
 * Plugin URI: https://academiadacomunicacao.example
 * Description: Gerencie questões comentadas com taxonomias dedicadas e exiba-as com um shortcode acessível.
 * Version: 1.3.0
 * Author: Academia da Comunicação
 * Text Domain: questoes-comentadas
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Questoes_Comentadas_Plugin {
    const VERSION           = '1.3.0';
    const CPT               = 'questao_comentada';
    const TAXONOMY_CATEGORY = 'qc_categoria';
    const TAXONOMY_BANK     = 'qc_banca';
    const TAXONOMY_SUBJECT  = 'qc_assunto';
    const OPTION_SEEDED     = 'qc_sample_data_seeded';

    public function __construct() {
        add_action( 'init', [ $this, 'register_post_type_and_taxonomies' ] );
        add_action( 'admin_menu', [ $this, 'register_admin_menu' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_front_assets' ] );
        add_shortcode( 'questoes_comentadas', [ $this, 'render_shortcode' ] );
    }

    public function register_post_type_and_taxonomies() {
        $labels = [
            'name'               => __( 'Questões Comentadas', 'questoes-comentadas' ),
            'singular_name'      => __( 'Questão Comentada', 'questoes-comentadas' ),
            'add_new'            => __( 'Adicionar Nova', 'questoes-comentadas' ),
            'add_new_item'       => __( 'Adicionar Nova Questão', 'questoes-comentadas' ),
            'edit_item'          => __( 'Editar Questão', 'questoes-comentadas' ),
            'new_item'           => __( 'Nova Questão', 'questoes-comentadas' ),
            'view_item'          => __( 'Ver Questão', 'questoes-comentadas' ),
            'search_items'       => __( 'Buscar Questões', 'questoes-comentadas' ),
            'not_found'          => __( 'Nenhuma questão encontrada', 'questoes-comentadas' ),
            'not_found_in_trash' => __( 'Nenhuma questão encontrada na lixeira', 'questoes-comentadas' ),
            'menu_name'          => __( 'Questões Comentadas', 'questoes-comentadas' ),
        ];

        register_post_type(
            self::CPT,
            [
                'labels'       => $labels,
                'public'       => false,
                'show_ui'      => true,
                'show_in_menu' => false,
                'supports'     => [ 'title', 'editor', 'excerpt' ],
                'has_archive'  => false,
                'rewrite'      => false,
            ]
        );

        $this->register_taxonomy(
            self::TAXONOMY_CATEGORY,
            __( 'Categorias', 'questoes-comentadas' ),
            __( 'Categoria', 'questoes-comentadas' )
        );

        $this->register_taxonomy(
            self::TAXONOMY_BANK,
            __( 'Bancas', 'questoes-comentadas' ),
            __( 'Banca', 'questoes-comentadas' )
        );

        $this->register_taxonomy(
            self::TAXONOMY_SUBJECT,
            __( 'Assuntos', 'questoes-comentadas' ),
            __( 'Assunto', 'questoes-comentadas' )
        );
    }

    private function register_taxonomy( $taxonomy, $plural_label, $singular_label ) {
        $labels = [
            'name'          => $plural_label,
            'singular_name' => $singular_label,
            'search_items'  => sprintf( __( 'Buscar %s', 'questoes-comentadas' ), strtolower( $plural_label ) ),
            'all_items'     => sprintf( __( 'Todas as %s', 'questoes-comentadas' ), strtolower( $plural_label ) ),
            'edit_item'     => sprintf( __( 'Editar %s', 'questoes-comentadas' ), strtolower( $singular_label ) ),
            'update_item'   => sprintf( __( 'Atualizar %s', 'questoes-comentadas' ), strtolower( $singular_label ) ),
            'add_new_item'  => sprintf( __( 'Adicionar nova %s', 'questoes-comentadas' ), strtolower( $singular_label ) ),
            'menu_name'     => $plural_label,
        ];

        register_taxonomy(
            $taxonomy,
            self::CPT,
            [
                'labels'            => $labels,
                'hierarchical'      => true,
                'show_admin_column' => true,
                'show_ui'           => true,
                'show_in_menu'      => false,
            ]
        );
    }

    public function register_admin_menu() {
        add_menu_page(
            __( 'Questões Comentadas', 'questoes-comentadas' ),
            __( 'Questões Comentadas', 'questoes-comentadas' ),
            'edit_posts',
            'questoes-comentadas-dashboard',
            [ $this, 'render_dashboard_page' ],
            'dashicons-welcome-learn-more',
            26
        );

        add_submenu_page(
            'questoes-comentadas-dashboard',
            __( 'Todas as Questões', 'questoes-comentadas' ),
            __( 'Todas as Questões', 'questoes-comentadas' ),
            'edit_posts',
            'edit.php?post_type=' . self::CPT
        );

        add_submenu_page(
            'questoes-comentadas-dashboard',
            __( 'Adicionar Nova', 'questoes-comentadas' ),
            __( 'Adicionar Nova', 'questoes-comentadas' ),
            'edit_posts',
            'post-new.php?post_type=' . self::CPT
        );

        add_submenu_page(
            'questoes-comentadas-dashboard',
            __( 'Categorias', 'questoes-comentadas' ),
            __( 'Categorias', 'questoes-comentadas' ),
            'manage_categories',
            'edit-tags.php?taxonomy=' . self::TAXONOMY_CATEGORY . '&post_type=' . self::CPT
        );

        add_submenu_page(
            'questoes-comentadas-dashboard',
            __( 'Bancas', 'questoes-comentadas' ),
            __( 'Bancas', 'questoes-comentadas' ),
            'manage_categories',
            'edit-tags.php?taxonomy=' . self::TAXONOMY_BANK . '&post_type=' . self::CPT
        );

        add_submenu_page(
            'questoes-comentadas-dashboard',
            __( 'Assuntos', 'questoes-comentadas' ),
            __( 'Assuntos', 'questoes-comentadas' ),
            'manage_categories',
            'edit-tags.php?taxonomy=' . self::TAXONOMY_SUBJECT . '&post_type=' . self::CPT
        );
    }

    public function render_dashboard_page() {
        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_die( __( 'Você não tem permissão para acessar esta página.', 'questoes-comentadas' ) );
        }

        $status_counts      = $this->get_post_status_counts();
        $status_data        = $status_counts['data'];
        $total              = $status_data['publish']['count'];
        $drafts             = $status_data['draft']['count'];
        $all_total          = $status_counts['total'];
        $progress           = $status_counts['progress'];

        $category_summary = $this->get_taxonomy_summary( self::TAXONOMY_CATEGORY );
        $bank_summary     = $this->get_taxonomy_summary( self::TAXONOMY_BANK );
        $subject_summary  = $this->get_taxonomy_summary( self::TAXONOMY_SUBJECT );

        $taxonomy_summaries = [
            [
                'title'       => __( 'Categorias em destaque', 'questoes-comentadas' ),
                'description' => __( 'Acompanhe como as questões publicadas se distribuem nas categorias principais.', 'questoes-comentadas' ),
                'items'       => $category_summary,
            ],
            [
                'title'       => __( 'Bancas representadas', 'questoes-comentadas' ),
                'description' => __( 'Visualize quais bancas já contam com questões comentadas.', 'questoes-comentadas' ),
                'items'       => $bank_summary,
            ],
            [
                'title'       => __( 'Assuntos cobertos', 'questoes-comentadas' ),
                'description' => __( 'Confira os assuntos que receberam comentários detalhados.', 'questoes-comentadas' ),
                'items'       => $subject_summary,
            ],
        ];

        $base_url = admin_url( 'edit.php?post_type=' . self::CPT );
        ?>
        <div class="wrap qc-dashboard">
            <h1><?php esc_html_e( 'Questões Comentadas', 'questoes-comentadas' ); ?></h1>
            <p class="description">
                <?php esc_html_e( 'Cadastre, gerencie e publique questões comentadas alinhadas às bancas e assuntos da Academia da Comunicação.', 'questoes-comentadas' ); ?>
            </p>

            <div class="qc-dashboard__cards">
                <div class="qc-dashboard__card">
                    <h2><?php esc_html_e( 'Questões Publicadas', 'questoes-comentadas' ); ?></h2>
                    <p class="qc-dashboard__card-number"><?php echo esc_html( $total ); ?></p>
                    <a class="button button-primary" href="<?php echo esc_url( $base_url ); ?>">
                        <?php esc_html_e( 'Ver todas', 'questoes-comentadas' ); ?>
                    </a>
                </div>
                <div class="qc-dashboard__card">
                    <h2><?php esc_html_e( 'Rascunhos', 'questoes-comentadas' ); ?></h2>
                    <p class="qc-dashboard__card-number"><?php echo esc_html( $drafts ); ?></p>
                    <a class="button" href="<?php echo esc_url( add_query_arg( 'post_status', 'draft', $base_url ) ); ?>">
                        <?php esc_html_e( 'Gerenciar rascunhos', 'questoes-comentadas' ); ?>
                    </a>
                </div>
            </div>

            <section class="qc-dashboard__progress" aria-labelledby="qc-dashboard-progress-title">
                <h2 id="qc-dashboard-progress-title"><?php esc_html_e( 'Progresso de publicação', 'questoes-comentadas' ); ?></h2>
                <p>
                    <?php
                    printf(
                        esc_html__( '%1$d de %2$d questões estão publicadas (%3$d%%).', 'questoes-comentadas' ),
                        intval( $total ),
                        intval( $all_total ),
                        intval( $progress )
                    );
                    ?>
                </p>
                <div class="qc-progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?php echo esc_attr( $progress ); ?>">
                    <span class="qc-progress__bar" style="--qc-progress-value: <?php echo esc_attr( $progress ); ?>%;"></span>
                </div>
            </section>

            <section class="qc-dashboard__status" aria-labelledby="qc-dashboard-status-title">
                <h2 id="qc-dashboard-status-title"><?php esc_html_e( 'Acompanhamento por status', 'questoes-comentadas' ); ?></h2>
                <ul class="qc-dashboard__status-list">
                    <?php foreach ( $status_data as $status_key => $status_item ) : ?>
                        <li class="qc-dashboard__status-item">
                            <div class="qc-dashboard__status-header">
                                <span class="qc-dashboard__status-label"><?php echo esc_html( $status_item['label'] ); ?></span>
                                <span class="qc-dashboard__status-value"><?php echo esc_html( sprintf( _n( '%d registro', '%d registros', $status_item['count'], 'questoes-comentadas' ), $status_item['count'] ) ); ?> · <?php echo esc_html( $status_item['percentage'] ); ?>%</span>
                            </div>
                            <div class="qc-progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?php echo esc_attr( $status_item['percentage'] ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Status %1$s representa %2$d%% das questões cadastradas', 'questoes-comentadas' ), $status_item['label'], $status_item['percentage'] ) ); ?>">
                                <span class="qc-progress__bar" style="--qc-progress-value: <?php echo esc_attr( $status_item['percentage'] ); ?>%;"></span>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>

            <?php
            $has_taxonomy_summary = false;

            foreach ( $taxonomy_summaries as $summary ) {
                if ( ! empty( $summary['items'] ) ) {
                    $has_taxonomy_summary = true;
                    break;
                }
            }

            if ( $has_taxonomy_summary ) :
                ?>
                <section class="qc-dashboard__taxonomy" aria-labelledby="qc-dashboard-taxonomy-title">
                    <h2 id="qc-dashboard-taxonomy-title"><?php esc_html_e( 'Distribuição por taxonomias', 'questoes-comentadas' ); ?></h2>
                    <div class="qc-dashboard__taxonomy-grid">
                        <?php foreach ( $taxonomy_summaries as $summary ) : ?>
                            <?php if ( empty( $summary['items'] ) ) : ?>
                                <?php continue; ?>
                            <?php endif; ?>
                            <article class="qc-dashboard__taxonomy-card">
                                <h3><?php echo esc_html( $summary['title'] ); ?></h3>
                                <p class="qc-dashboard__taxonomy-description"><?php echo esc_html( $summary['description'] ); ?></p>
                                <ul class="qc-dashboard__taxonomy-list">
                                    <?php foreach ( $summary['items'] as $item ) : ?>
                                        <li class="qc-dashboard__taxonomy-item">
                                            <div class="qc-dashboard__taxonomy-header">
                                                <span class="qc-dashboard__taxonomy-term"><?php echo esc_html( $item['name'] ); ?></span>
                                                <span class="qc-dashboard__taxonomy-value"><?php echo esc_html( sprintf( _n( '%d questão', '%d questões', $item['count'], 'questoes-comentadas' ), $item['count'] ) ); ?> · <?php echo esc_html( $item['percentage'] ); ?>%</span>
                                            </div>
                                            <div
                                                class="qc-progress"
                                                role="progressbar"
                                                aria-valuemin="0"
                                                aria-valuemax="100"
                                                aria-valuenow="<?php echo esc_attr( $item['percentage'] ); ?>"
                                                aria-label="<?php echo esc_attr( sprintf( __( '%1$s representa %2$d%% das questões cadastradas', 'questoes-comentadas' ), $item['name'], $item['percentage'] ) ); ?>"
                                            >
                                                <span class="qc-progress__bar" style="--qc-progress-value: <?php echo esc_attr( $item['percentage'] ); ?>%;"></span>
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>
                <?php
            endif;
            ?>

            <div class="qc-dashboard__quick-actions">
                <h2><?php esc_html_e( 'Ações rápidas', 'questoes-comentadas' ); ?></h2>
                <ul>
                    <li><a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=' . self::CPT ) ); ?>"><?php esc_html_e( 'Adicionar nova questão comentada', 'questoes-comentadas' ); ?></a></li>
                    <li><a href="<?php echo esc_url( admin_url( 'edit-tags.php?taxonomy=' . self::TAXONOMY_CATEGORY . '&post_type=' . self::CPT ) ); ?>"><?php esc_html_e( 'Organizar categorias', 'questoes-comentadas' ); ?></a></li>
                    <li><a href="<?php echo esc_url( admin_url( 'edit-tags.php?taxonomy=' . self::TAXONOMY_BANK . '&post_type=' . self::CPT ) ); ?>"><?php esc_html_e( 'Organizar bancas', 'questoes-comentadas' ); ?></a></li>
                    <li><a href="<?php echo esc_url( admin_url( 'edit-tags.php?taxonomy=' . self::TAXONOMY_SUBJECT . '&post_type=' . self::CPT ) ); ?>"><?php esc_html_e( 'Organizar assuntos', 'questoes-comentadas' ); ?></a></li>
                </ul>
            </div>

            <?php
            $preview = new WP_Query(
                [
                    'post_type'      => self::CPT,
                    'post_status'    => 'publish',
                    'posts_per_page' => 3,
                ]
            );

            if ( $preview->have_posts() ) :
                ?>
                <div class="qc-dashboard__preview">
                    <h2><?php esc_html_e( 'Questões destacadas', 'questoes-comentadas' ); ?></h2>
                    <div class="qc-dashboard__preview-grid">
                        <?php
                        while ( $preview->have_posts() ) :
                            $preview->the_post();
                            $category = get_the_terms( get_the_ID(), self::TAXONOMY_CATEGORY );
                            $bank     = get_the_terms( get_the_ID(), self::TAXONOMY_BANK );
                            $subject  = get_the_terms( get_the_ID(), self::TAXONOMY_SUBJECT );
                            ?>
                            <article class="qc-dashboard-card" aria-labelledby="qc-dashboard-card-title-<?php the_ID(); ?>">
                                <h3 id="qc-dashboard-card-title-<?php the_ID(); ?>"><?php the_title(); ?></h3>
                                <p class="qc-dashboard-card__excerpt"><?php echo esc_html( get_the_excerpt() ); ?></p>
                                <ul class="qc-dashboard-card__meta">
                                    <?php if ( ! empty( $category ) ) : ?>
                                        <li><strong><?php esc_html_e( 'Categoria:', 'questoes-comentadas' ); ?></strong> <?php echo esc_html( $category[0]->name ); ?></li>
                                    <?php endif; ?>
                                    <?php if ( ! empty( $bank ) ) : ?>
                                        <li><strong><?php esc_html_e( 'Banca:', 'questoes-comentadas' ); ?></strong> <?php echo esc_html( $bank[0]->name ); ?></li>
                                    <?php endif; ?>
                                    <?php if ( ! empty( $subject ) ) : ?>
                                        <li><strong><?php esc_html_e( 'Assunto:', 'questoes-comentadas' ); ?></strong> <?php echo esc_html( $subject[0]->name ); ?></li>
                                    <?php endif; ?>
                                </ul>
                            </article>
                            <?php
                        endwhile;
                        wp_reset_postdata();
                        ?>
                    </div>
                </div>
                <?php
            endif;
            ?>
        </div>
        <?php
    }

    public function enqueue_admin_assets( $hook ) {
        if ( 'toplevel_page_questoes-comentadas-dashboard' !== $hook ) {
            return;
        }

        wp_enqueue_style(
            'questoes-comentadas-admin',
            plugin_dir_url( __FILE__ ) . 'assets/css/admin.css',
            [],
            self::VERSION
        );
    }

    public function enqueue_front_assets() {
        wp_enqueue_style(
            'questoes-comentadas-frontend',
            plugin_dir_url( __FILE__ ) . 'assets/css/frontend.css',
            [],
            self::VERSION
        );

        wp_enqueue_script(
            'questoes-comentadas-frontend',
            plugin_dir_url( __FILE__ ) . 'assets/js/frontend.js',
            [],
            self::VERSION,
            true
        );
    }

    public function render_shortcode( $atts ) {
        $atts = shortcode_atts(
            [
                'limit'        => 6,
                'filters'      => 'true',
                'show_summary' => 'true',
            ],
            $atts,
            'questoes_comentadas'
        );

        $show_filters = filter_var( $atts['filters'], FILTER_VALIDATE_BOOLEAN );
        $show_summary = filter_var( $atts['show_summary'], FILTER_VALIDATE_BOOLEAN );

        $query = new WP_Query(
            [
                'post_type'      => self::CPT,
                'post_status'    => 'publish',
                'posts_per_page' => intval( $atts['limit'] ),
            ]
        );

        if ( ! $query->have_posts() ) {
            return '<p>' . esc_html__( 'Nenhuma questão comentada disponível no momento.', 'questoes-comentadas' ) . '</p>';
        }

        $total_published  = $this->get_total_published_questions();
        $categories       = $this->get_taxonomy_summary( self::TAXONOMY_CATEGORY );
        $banks_summary    = $this->get_taxonomy_summary( self::TAXONOMY_BANK );
        $subjects_summary = $this->get_taxonomy_summary( self::TAXONOMY_SUBJECT );
        $status_counts    = $this->get_post_status_counts();
        $summary_blocks   = [
            'categoria' => [
                'title' => __( 'Distribuição por categoria', 'questoes-comentadas' ),
                'items' => $categories,
            ],
            'banca'     => [
                'title' => __( 'Distribuição por banca', 'questoes-comentadas' ),
                'items' => $banks_summary,
            ],
            'assunto'   => [
                'title' => __( 'Distribuição por assunto', 'questoes-comentadas' ),
                'items' => $subjects_summary,
            ],
        ];

        $has_summary_blocks = false;

        foreach ( $summary_blocks as $key => $block ) {
            if ( empty( $block['items'] ) ) {
                continue;
            }

            $summary_blocks[ $key ]['total'] = array_sum( wp_list_pluck( $block['items'], 'count' ) );
            $has_summary_blocks              = true;
        }

        $results_percentage_template = __( 'Você está visualizando %percentage%%% das questões desta seção.', 'questoes-comentadas' );
        $status_for_front = array_filter(
            $status_counts['data'],
            static function ( $item, $key ) {
                return $item['count'] > 0 || 'publish' === $key;
            },
            ARRAY_FILTER_USE_BOTH
        );

        ob_start();
        ?>
        <section class="qc-shortcode-wrapper">
            <header class="qc-shortcode-header">
                <p class="qc-shortcode-kicker"><?php esc_html_e( 'Academia da Comunicação', 'questoes-comentadas' ); ?></p>
                <h2 class="qc-shortcode-title"><?php esc_html_e( 'Questões Comentadas de Português', 'questoes-comentadas' ); ?></h2>
                <p class="qc-results-count" aria-live="polite">
                    <span><?php echo esc_html( $query->post_count ); ?></span>
                    <?php esc_html_e( 'questões disponíveis nesta visualização.', 'questoes-comentadas' ); ?>
                </p>
                <p
                    class="qc-results-percentage"
                    data-qc-results-percentage
                    data-baseline="<?php echo esc_attr( $query->post_count ); ?>"
                    data-template="<?php echo esc_attr( $results_percentage_template ); ?>"
                >
                    <?php echo esc_html( str_replace( '%percentage%', 100, $results_percentage_template ) ); ?>
                </p>
            </header>

            <?php if ( $show_summary && $has_summary_blocks ) : ?>
                <div class="qc-shortcode-summary-grid">
                    <?php foreach ( $summary_blocks as $taxonomy_key => $summary_block ) : ?>
                        <?php if ( empty( $summary_block['items'] ) ) : ?>
                            <?php continue; ?>
                        <?php endif; ?>
                        <section class="qc-shortcode-summary" data-qc-summary-card data-taxonomy="<?php echo esc_attr( $taxonomy_key ); ?>">
                            <h3><?php echo esc_html( $summary_block['title'] ); ?></h3>
                            <ul class="qc-progress-list" data-qc-summary-list data-taxonomy="<?php echo esc_attr( $taxonomy_key ); ?>">
                                <?php foreach ( $summary_block['items'] as $item ) :
                                    $count_text    = sprintf( _n( '%d questão', '%d questões', $item['count'], 'questoes-comentadas' ), $item['count'] );
                                    $label_template = __( '%label% representa %percentage%%% das questões visíveis', 'questoes-comentadas' );
                                    $aria_label     = str_replace(
                                        [ '%label%', '%percentage%' ],
                                        [ $item['name'], $item['percentage'] ],
                                        $label_template
                                    );
                                    ?>
                                    <li
                                        class="qc-progress-item"
                                        data-qc-summary-item
                                        data-term="<?php echo esc_attr( $item['slug'] ); ?>"
                                        data-count-singular="<?php echo esc_attr__( '%d questão', 'questoes-comentadas' ); ?>"
                                        data-count-plural="<?php echo esc_attr__( '%d questões', 'questoes-comentadas' ); ?>"
                                    >
                                        <span class="qc-progress-item__label"><?php echo esc_html( $item['name'] ); ?></span>
                                        <div
                                            class="qc-progress"
                                            role="progressbar"
                                            aria-valuemin="0"
                                            aria-valuemax="100"
                                            aria-valuenow="<?php echo esc_attr( $item['percentage'] ); ?>"
                                            aria-label="<?php echo esc_attr( $aria_label ); ?>"
                                            data-qc-progress
                                            data-label-template="<?php echo esc_attr( $label_template ); ?>"
                                        >
                                            <span class="qc-progress__bar" style="--qc-progress-value: <?php echo esc_attr( $item['percentage'] ); ?>%;"></span>
                                        </div>
                                        <span class="qc-progress-item__value">
                                            <span data-qc-summary-count-text><?php echo esc_html( $count_text ); ?></span>
                                            <span aria-hidden="true"> · </span>
                                            <span><span data-qc-summary-percentage><?php echo esc_html( $item['percentage'] ); ?></span>%</span>
                                        </span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                            <p class="qc-shortcode-total">
                                <?php
                                printf(
                                    esc_html( _n( '%d registro relacionado nesta taxonomia.', '%d registros relacionados nesta taxonomia.', $summary_block['total'], 'questoes-comentadas' ) ),
                                    intval( $summary_block['total'] )
                                );
                                ?>
                            </p>
                        </section>
                    <?php endforeach; ?>
                </div>
                <p class="qc-shortcode-total qc-shortcode-total--overall">
                    <?php printf( esc_html__( '%d questões publicadas no total.', 'questoes-comentadas' ), intval( $total_published ) ); ?>
                </p>
            <?php endif; ?>

            <?php if ( $show_filters ) :
                $filter_options = $this->get_filter_options();
                if ( ! empty( $filter_options ) ) :
                    ?>
                <form class="qc-filters" aria-label="<?php esc_attr_e( 'Filtrar questões comentadas', 'questoes-comentadas' ); ?>">
                    <?php foreach ( $filter_options as $taxonomy_key => $data ) : ?>
                        <label>
                            <span class="screen-reader-text"><?php echo esc_html( $data['label'] ); ?></span>
                            <select name="qc_filter_<?php echo esc_attr( $taxonomy_key ); ?>">
                                <option value=""><?php echo esc_html( $data['placeholder'] ); ?></option>
                                <?php foreach ( $data['options'] as $option ) : ?>
                                    <option value="<?php echo esc_attr( $option['slug'] ); ?>"><?php echo esc_html( $option['name'] ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                    <?php endforeach; ?>
                    <button type="button" class="qc-filters__clear"><?php esc_html_e( 'Limpar filtros', 'questoes-comentadas' ); ?></button>
                </form>
                    <?php
                endif;
            endif;
            ?>

            <div class="qc-shortcode-grid">
            <?php
            while ( $query->have_posts() ) {
                $query->the_post();
                $terms_category = get_the_terms( get_the_ID(), self::TAXONOMY_CATEGORY );
                $terms_bank     = get_the_terms( get_the_ID(), self::TAXONOMY_BANK );
                $terms_subject  = get_the_terms( get_the_ID(), self::TAXONOMY_SUBJECT );
                $data_attributes = $this->build_card_data_attributes(
                    [
                        'categoria' => $terms_category,
                        'banca'     => $terms_bank,
                        'assunto'   => $terms_subject,
                    ]
                );
                ?>
                <article class="qc-card" aria-labelledby="qc-card-title-<?php the_ID(); ?>" <?php echo $data_attributes; ?>>
                    <div class="qc-card__meta">
                        <?php if ( ! empty( $terms_category ) ) : ?>
                            <span class="qc-pill qc-pill--category"><?php echo esc_html( $terms_category[0]->name ); ?></span>
                        <?php endif; ?>
                        <?php if ( ! empty( $terms_bank ) ) : ?>
                            <span class="qc-pill qc-pill--bank"><?php echo esc_html( $terms_bank[0]->name ); ?></span>
                        <?php endif; ?>
                        <?php if ( ! empty( $terms_subject ) ) : ?>
                            <span class="qc-pill qc-pill--subject"><?php echo esc_html( $terms_subject[0]->name ); ?></span>
                        <?php endif; ?>
                    </div>
                    <h3 id="qc-card-title-<?php the_ID(); ?>" class="qc-card__title"><?php the_title(); ?></h3>
                    <div class="qc-card__excerpt"><?php the_excerpt(); ?></div>
                    <details class="qc-card__details">
                        <summary><?php esc_html_e( 'Ver comentário completo', 'questoes-comentadas' ); ?></summary>
                        <div class="qc-card__content">
                            <?php the_content(); ?>
                        </div>
                    </details>
                </article>
                <?php
            }
            wp_reset_postdata();
            ?>
            </div>
            <p class="qc-empty-message" hidden><?php esc_html_e( 'Nenhuma questão corresponde aos filtros selecionados.', 'questoes-comentadas' ); ?></p>

            <footer class="qc-shortcode-footer">
                <section class="qc-shortcode-progress" aria-labelledby="qc-shortcode-progress-title">
                    <h3 id="qc-shortcode-progress-title"><?php esc_html_e( 'Progresso de publicação', 'questoes-comentadas' ); ?></h3>
                    <p class="qc-shortcode-progress__summary">
                        <?php
                        printf(
                            esc_html__( '%1$d de %2$d questões estão publicadas (%3$d%%).', 'questoes-comentadas' ),
                            intval( $status_counts['data']['publish']['count'] ),
                            intval( $status_counts['total'] ),
                            intval( $status_counts['progress'] )
                        );
                        ?>
                    </p>
                    <div class="qc-progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?php echo esc_attr( $status_counts['progress'] ); ?>">
                        <span class="qc-progress__bar" style="--qc-progress-value: <?php echo esc_attr( $status_counts['progress'] ); ?>%;"></span>
                    </div>
                </section>

                <?php if ( ! empty( $status_for_front ) ) : ?>
                    <section class="qc-shortcode-status" aria-labelledby="qc-shortcode-status-title">
                        <h3 id="qc-shortcode-status-title"><?php esc_html_e( 'Distribuição por status', 'questoes-comentadas' ); ?></h3>
                        <ul class="qc-status-list">
                            <?php foreach ( $status_for_front as $status_key => $status_item ) : ?>
                                <li class="qc-status-item">
                                    <div class="qc-status-item__header">
                                        <span class="qc-status-item__label"><?php echo esc_html( $status_item['label'] ); ?></span>
                                        <span class="qc-status-item__value"><?php echo esc_html( sprintf( _n( '%d registro', '%d registros', $status_item['count'], 'questoes-comentadas' ), $status_item['count'] ) ); ?> · <?php echo esc_html( $status_item['percentage'] ); ?>%</span>
                                    </div>
                                    <div class="qc-progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?php echo esc_attr( $status_item['percentage'] ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Status %1$s representa %2$d%% das questões cadastradas', 'questoes-comentadas' ), $status_item['label'], $status_item['percentage'] ) ); ?>">
                                        <span class="qc-progress__bar" style="--qc-progress-value: <?php echo esc_attr( $status_item['percentage'] ); ?>%;"></span>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </section>
                <?php endif; ?>
            </footer>
        </section>
        <?php
        return ob_get_clean();
    }

    public static function activate() {
        $plugin = new self();
        $plugin->register_post_type_and_taxonomies();
        flush_rewrite_rules();
        $plugin->seed_sample_data();
    }

    private function seed_sample_data() {
        if ( get_option( self::OPTION_SEEDED ) ) {
            return;
        }

        $categories = [
            'Interpretação de Texto',
            'Gramática',
        ];
        $banks      = [
            'FGV',
            'CESPE',
        ];
        $subjects   = [
            'Concordância Verbal',
            'Pontuação',
            'Figuras de Linguagem',
        ];

        $category_terms = [];
        foreach ( $categories as $category ) {
            $term = wp_insert_term( $category, self::TAXONOMY_CATEGORY );
            if ( is_wp_error( $term ) ) {
                if ( 'term_exists' === $term->get_error_code() && isset( $term->error_data['term_exists'] ) ) {
                    $category_terms[] = (int) $term->error_data['term_exists'];
                }
            } else {
                $category_terms[] = $term['term_id'];
            }
        }

        $bank_terms = [];
        foreach ( $banks as $bank ) {
            $term = wp_insert_term( $bank, self::TAXONOMY_BANK );
            if ( is_wp_error( $term ) ) {
                if ( 'term_exists' === $term->get_error_code() && isset( $term->error_data['term_exists'] ) ) {
                    $bank_terms[] = (int) $term->error_data['term_exists'];
                }
            } else {
                $bank_terms[] = $term['term_id'];
            }
        }

        $subject_terms = [];
        foreach ( $subjects as $subject ) {
            $term = wp_insert_term( $subject, self::TAXONOMY_SUBJECT );
            if ( is_wp_error( $term ) ) {
                if ( 'term_exists' === $term->get_error_code() && isset( $term->error_data['term_exists'] ) ) {
                    $subject_terms[] = (int) $term->error_data['term_exists'];
                }
            } else {
                $subject_terms[] = $term['term_id'];
            }
        }

        $questions = [
            [
                'title'   => 'Uso adequado da concordância verbal',
                'excerpt' => 'Analise a frase e identifique a forma correta da concordância verbal aplicada.',
                'content' => '<p>A frase &ldquo;Fazem dois anos que estudo para este concurso&rdquo; deve ser corrigida para &ldquo;Faz dois anos...&rdquo; porque o verbo fazer indicando tempo decorrido permanece no singular.</p><p><strong>Dica:</strong> Sempre que o verbo &ldquo;fazer&rdquo; indicar tempo decorrido ou fenômeno natural, use-o no singular.</p>',
                'terms'   => [
                    self::TAXONOMY_CATEGORY => $category_terms[1] ?? null,
                    self::TAXONOMY_BANK     => $bank_terms[0] ?? null,
                    self::TAXONOMY_SUBJECT  => $subject_terms[0] ?? null,
                ],
            ],
            [
                'title'   => 'Pontuação e sentido textual',
                'excerpt' => 'A falta de vírgulas pode comprometer a clareza e o ritmo de um texto argumentativo.',
                'content' => '<p>No enunciado proposto, a ausência de vírgulas após os conectivos &ldquo;porém&rdquo; e &ldquo;além disso&rdquo; gera ambiguidade. A correção exige a colocação das vírgulas para separar orações coordenadas.</p><p><strong>Observe:</strong> A pontuação adequada orienta a leitura e evita interpretações equivocadas.</p>',
                'terms'   => [
                    self::TAXONOMY_CATEGORY => $category_terms[0] ?? null,
                    self::TAXONOMY_BANK     => $bank_terms[1] ?? null,
                    self::TAXONOMY_SUBJECT  => $subject_terms[1] ?? null,
                ],
            ],
            [
                'title'   => 'Figuras de linguagem em textos publicitários',
                'excerpt' => 'Identifique a figura de linguagem predominante em um slogan publicitário.',
                'content' => '<p>O slogan analisado utiliza metáfora para relacionar a marca a uma experiência sensorial. A metáfora é um recurso que substitui o sentido literal por outro figurado, enriquecendo a mensagem.</p><p><strong>Lembre:</strong> Reconhecer figuras de linguagem ajuda a interpretar estratégias persuasivas.</p>',
                'terms'   => [
                    self::TAXONOMY_CATEGORY => $category_terms[0] ?? null,
                    self::TAXONOMY_BANK     => $bank_terms[0] ?? null,
                    self::TAXONOMY_SUBJECT  => $subject_terms[2] ?? null,
                ],
            ],
            [
                'title'   => 'Crase em locuções femininas',
                'excerpt' => 'Verifique quando o uso da crase é obrigatório antes de locuções femininas específicas.',
                'content' => '<p>Em expressões como &ldquo;à noite&rdquo; e &ldquo;à moda de&rdquo;, a crase é obrigatória por ocorrer a fusão da preposição &ldquo;a&rdquo; com o artigo feminino &ldquo;a&rdquo;.</p><p><strong>Pratique:</strong> identifique locuções femininas que exigem a crase e observe a regência do verbo antecedente.</p>',
                'status'  => 'draft',
                'terms'   => [
                    self::TAXONOMY_CATEGORY => $category_terms[1] ?? null,
                    self::TAXONOMY_BANK     => $bank_terms[1] ?? null,
                    self::TAXONOMY_SUBJECT  => $subject_terms[1] ?? null,
                ],
            ],
            [
                'title'   => 'Coesão referencial e pronomes',
                'excerpt' => 'Analise como pronomes retomam termos anteriores e garantem coesão textual.',
                'content' => '<p>No texto apresentado, o pronome &ldquo;ele&rdquo; retoma o substantivo &ldquo;o projeto&rdquo;, evitando repetições desnecessárias. A coesão referencial mantém a fluidez do texto.</p><p><strong>Lembre-se:</strong> pronomes devem concordar em gênero e número com os referentes retomados.</p>',
                'status'  => 'pending',
                'terms'   => [
                    self::TAXONOMY_CATEGORY => $category_terms[0] ?? null,
                    self::TAXONOMY_BANK     => $bank_terms[0] ?? null,
                    self::TAXONOMY_SUBJECT  => $subject_terms[0] ?? null,
                ],
            ],
        ];

        foreach ( $questions as $question ) {
            $post_id = wp_insert_post(
                [
                    'post_type'    => self::CPT,
                    'post_status'  => $question['status'] ?? 'publish',
                    'post_title'   => $question['title'],
                    'post_excerpt' => $question['excerpt'],
                    'post_content' => $question['content'],
                ]
            );

            if ( ! is_wp_error( $post_id ) && $post_id ) {
                foreach ( $question['terms'] as $taxonomy => $term_id ) {
                    if ( $term_id ) {
                        wp_set_post_terms( $post_id, [ $term_id ], $taxonomy, false );
                    }
                }
            }
        }

        update_option( self::OPTION_SEEDED, 1 );
    }

    private function get_total_published_questions() {
        $counts = wp_count_posts( self::CPT );

        return isset( $counts->publish ) ? intval( $counts->publish ) : 0;
    }

    private function get_taxonomy_summary( $taxonomy ) {
        $terms = get_terms(
            [
                'taxonomy'   => $taxonomy,
                'hide_empty' => true,
            ]
        );

        if ( is_wp_error( $terms ) || empty( $terms ) ) {
            return [];
        }

        $total = array_sum( wp_list_pluck( $terms, 'count' ) );

        if ( 0 === $total ) {
            return [];
        }

        $summary = [];

        foreach ( $terms as $term ) {
            $percentage = round( ( $term->count / $total ) * 100 );
            $summary[]  = [
                'name'       => $term->name,
                'count'      => intval( $term->count ),
                'slug'       => $term->slug,
                'percentage' => $percentage,
            ];
        }

        return $summary;
    }

    private function get_filter_options() {
        $taxonomies = [
            'categoria' => [
                'taxonomy'    => self::TAXONOMY_CATEGORY,
                'label'       => __( 'Filtrar por categoria', 'questoes-comentadas' ),
                'placeholder' => __( 'Todas as categorias', 'questoes-comentadas' ),
            ],
            'banca'     => [
                'taxonomy'    => self::TAXONOMY_BANK,
                'label'       => __( 'Filtrar por banca', 'questoes-comentadas' ),
                'placeholder' => __( 'Todas as bancas', 'questoes-comentadas' ),
            ],
            'assunto'   => [
                'taxonomy'    => self::TAXONOMY_SUBJECT,
                'label'       => __( 'Filtrar por assunto', 'questoes-comentadas' ),
                'placeholder' => __( 'Todos os assuntos', 'questoes-comentadas' ),
            ],
        ];

        $options = [];

        foreach ( $taxonomies as $key => $data ) {
            $terms = get_terms(
                [
                    'taxonomy'   => $data['taxonomy'],
                    'hide_empty' => true,
                ]
            );

            if ( is_wp_error( $terms ) ) {
                continue;
            }

            $options[ $key ] = [
                'label'       => $data['label'],
                'placeholder' => $data['placeholder'],
                'options'     => array_map(
                    static function ( $term ) {
                        return [
                            'name' => $term->name,
                            'slug' => $term->slug,
                        ];
                    },
                    $terms
                ),
            ];
        }

        return $options;
    }

    private function build_card_data_attributes( $taxonomies_terms ) {
        $attributes = [];

        foreach ( $taxonomies_terms as $key => $terms ) {
            if ( empty( $terms ) || is_wp_error( $terms ) ) {
                continue;
            }

            $slugs = array_map(
                static function ( $term ) {
                    return sanitize_title( $term->slug );
                },
                $terms
            );

            $attributes[] = sprintf( 'data-%1$s="%2$s"', esc_attr( $key ), esc_attr( implode( ',', $slugs ) ) );
        }

        return implode( ' ', $attributes );
    }

    private function get_post_status_counts() {
        $counts = wp_count_posts( self::CPT );

        $statuses = [
            'publish' => __( 'Publicadas', 'questoes-comentadas' ),
            'draft'   => __( 'Rascunhos', 'questoes-comentadas' ),
            'pending' => __( 'Pendentes', 'questoes-comentadas' ),
            'future'  => __( 'Agendadas', 'questoes-comentadas' ),
        ];

        $data  = [];
        $total = 0;

        foreach ( $statuses as $status_key => $label ) {
            $count = isset( $counts->{$status_key} ) ? intval( $counts->{$status_key} ) : 0;
            $data[ $status_key ] = [
                'label'      => $label,
                'count'      => $count,
                'percentage' => 0,
            ];
            $total += $count;
        }

        foreach ( $data as $status_key => $status_item ) {
            $percentage                        = 0 !== $total ? round( ( $status_item['count'] / $total ) * 100 ) : 0;
            $data[ $status_key ]['percentage'] = $percentage;
        }

        $progress_total = $total;
        if ( 0 === $progress_total ) {
            $progress_total = $data['publish']['count'];
        }

        $progress = 0 !== $progress_total ? round( ( $data['publish']['count'] / $progress_total ) * 100 ) : 0;

        return [
            'total'    => $total,
            'data'     => $data,
            'progress' => $progress,
        ];
    }
}

register_activation_hook( __FILE__, [ 'Questoes_Comentadas_Plugin', 'activate' ] );
new Questoes_Comentadas_Plugin();
