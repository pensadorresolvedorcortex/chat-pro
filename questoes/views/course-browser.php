<?php
/**
 * Course browser template.
 *
 * @package Questoes
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$featured_courses = isset( $featured ) && is_array( $featured ) ? $featured : array();
$all_courses      = isset( $courses ) && is_array( $courses ) ? $courses : array();
$region_entries   = isset( $regions ) && is_array( $regions ) ? array_values( $regions ) : array();
$totals           = isset( $totals ) && is_array( $totals ) ? $totals : array();

$total_courses   = isset( $totals['courses'] ) ? (int) $totals['courses'] : count( $all_courses );
$total_questions = isset( $totals['questions'] ) ? (int) $totals['questions'] : 0;
$regions_total   = max( 0, count( $region_entries ) );
$featured_total  = count( $featured_courses );
$hero_title       = __( 'Catálogo de cursos', 'questoes' );
$hero_description = sprintf(
    /* translators: 1: total courses, 2: total regions */
    esc_html__( 'Explore %1$s cursos organizados em %2$s recortes regionais e acompanhe as estatísticas do banco de questões.', 'questoes' ),
    esc_html( number_format_i18n( $total_courses ) ),
    esc_html( number_format_i18n( $regions_total ) )
);

$region_filters = array(
    array(
        'slug'      => 'all',
        'label'     => __( 'Todos', 'questoes' ),
        'courses'   => $total_courses,
        'questions' => $total_questions,
    ),
);

foreach ( $region_entries as $entry ) {
    if ( empty( $entry['slug'] ) || empty( $entry['label'] ) ) {
        continue;
    }

    $region_filters[] = array(
        'slug'      => sanitize_key( $entry['slug'] ),
        'label'     => $entry['label'],
        'courses'   => isset( $entry['courses'] ) ? (int) $entry['courses'] : 0,
        'questions' => isset( $entry['questions'] ) ? (int) $entry['questions'] : 0,
    );
}

$empty_message   = __( 'Nenhum curso corresponde aos filtros selecionados.', 'questoes' );

$featured_eyebrow = ! empty( $featured_title ) ? $featured_title : __( 'Cursos em alta', 'questoes' );

if ( ! empty( $featured_description ) ) {
    $featured_heading = wp_kses_post( $featured_description );
    $featured_body    = '';
} else {
    $featured_heading = esc_html( $hero_title );
    $featured_body    = ! empty( $hero_description ) ? esc_html( $hero_description ) : '';
}

$regions_eyebrow = ! empty( $regions_title ) ? $regions_title : __( 'Cursos por região', 'questoes' );

if ( ! empty( $regions_description ) ) {
    $regions_heading = wp_kses_post( $regions_description );
    $regions_body    = '';
} else {
    $regions_heading = esc_html( $regions_eyebrow );
    $regions_body    = ! empty( $hero_description ) ? esc_html( $hero_description ) : '';
}
?>
<section class="questoes-stage questoes-course-browser" data-component="courses">
    <div class="questoes-shell questoes-shell--wide">
        <?php if ( ! empty( $featured_courses ) ) : ?>
            <div class="questoes-surface questoes-course-browser__section questoes-course-browser__section--featured">
                <header class="questoes-course-browser__section-heading">
                    <span class="questoes-course-browser__eyebrow"><?php echo esc_html( $featured_eyebrow ); ?></span>
                    <h2 class="questoes-course-browser__title"><?php echo $featured_heading; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h2>
                    <?php if ( ! empty( $featured_body ) ) : ?>
                        <p class="questoes-course-browser__description"><?php echo esc_html( $featured_body ); ?></p>
                    <?php endif; ?>
                </header>

                <div class="questoes-course-browser__grid questoes-course-browser__grid--featured">
                    <?php foreach ( $featured_courses as $course ) : ?>
                        <?php echo questoes_render_course_card( $course, array( 'context' => 'featured' ) ); ?>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="questoes-surface questoes-course-browser__section questoes-course-browser__section--regions">
            <header class="questoes-course-browser__section-heading">
                <span class="questoes-course-browser__eyebrow"><?php echo esc_html( $regions_eyebrow ); ?></span>
                <h2 class="questoes-course-browser__title"><?php echo $regions_heading; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h2>
                <?php if ( ! empty( $regions_body ) ) : ?>
                    <p class="questoes-course-browser__description"><?php echo esc_html( $regions_body ); ?></p>
                <?php endif; ?>
            </header>

            <div class="questoes-course-browser__filters" role="radiogroup" aria-label="<?php esc_attr_e( 'Filtrar por região', 'questoes' ); ?>">
                <?php foreach ( $region_filters as $index => $filter ) :
                    $is_active = 0 === $index;
                    ?>
                    <button
                        type="button"
                        class="questoes-course-browser__filter<?php echo $is_active ? ' is-active' : ''; ?>"
                        data-region-filter="<?php echo esc_attr( $filter['slug'] ); ?>"
                        aria-pressed="<?php echo $is_active ? 'true' : 'false'; ?>"
                        role="radio"
                        data-count="<?php echo esc_attr( $filter['courses'] ); ?>"
                        <?php if ( $is_active ) : ?> aria-checked="true"<?php endif; ?>
                    >
                        <span class="questoes-course-browser__filter-label"><?php echo esc_html( $filter['label'] ); ?></span>
                        <span class="questoes-course-browser__filter-count" aria-hidden="true"><?php echo esc_html( number_format_i18n( $filter['courses'] ) ); ?></span>
                    </button>
                <?php endforeach; ?>
            </div>

            <?php if ( ! empty( $additional_content ) ) : ?>
                <div class="questoes-course-browser__callout"><?php echo $additional_content; ?></div>
            <?php endif; ?>

            <p class="questoes-course-browser__status" data-template="<?php echo esc_attr__( 'Exibindo %1$s de %2$s cursos.', 'questoes' ); ?>" data-total="<?php echo esc_attr( $total_courses ); ?>" hidden></p>

            <div class="questoes-course-browser__grid questoes-course-browser__grid--all">
                <?php foreach ( $all_courses as $course ) : ?>
                    <?php echo questoes_render_course_card( $course, array( 'context' => 'all' ) ); ?>
                <?php endforeach; ?>
            </div>

            <p class="questoes-course-browser__empty" hidden><?php echo esc_html( $empty_message ); ?></p>
        </div>
    </div>
</section>
