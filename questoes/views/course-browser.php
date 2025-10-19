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
$hero_title       = ! empty( $featured_title ) ? $featured_title : __( 'Cursos em alta', 'questoes' );
$hero_description = ! empty( $featured_description ) ? wp_kses_post( $featured_description ) : __( 'Principais concursos do momento.', 'questoes' );

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

$empty_message = __( 'Nenhum curso corresponde aos filtros selecionados.', 'questoes' );

$primary_courses   = $featured_courses;
$primary_ids       = array();
$secondary_courses = array();

foreach ( $featured_courses as $course ) {
    if ( isset( $course['id'] ) ) {
        $primary_ids[] = $course['id'];
    }
}

if ( empty( $primary_courses ) ) {
    $primary_courses = $all_courses;
} elseif ( ! empty( $all_courses ) ) {
    foreach ( $all_courses as $course ) {
        if ( isset( $course['id'] ) && in_array( $course['id'], $primary_ids, true ) ) {
            continue;
        }

        $secondary_courses[] = $course;
    }
}

$hero_support          = ! empty( $regions_description ) ? wp_kses_post( $regions_description ) : '';
$region_filters_enabled = count( $region_filters ) > 1;
?>
<section class="questoes-stage questoes-course-browser" data-component="courses">
    <div class="questoes-shell questoes-shell--wide">
        <div class="questoes-course-showcase questoes-surface">
            <header class="questoes-course-showcase__header">
                <div class="questoes-course-showcase__intro">
                    <span class="questoes-course-browser__eyebrow"><?php echo esc_html( $hero_title ); ?></span>
                    <h2 class="questoes-course-browser__title"><?php echo wp_kses_post( $hero_description ); ?></h2>
                    <?php if ( ! empty( $hero_support ) ) : ?>
                        <p class="questoes-course-browser__description"><?php echo wp_kses_post( $hero_support ); ?></p>
                    <?php endif; ?>
                </div>
                <ul class="questoes-course-showcase__stats">
                    <li>
                        <span class="questoes-course-showcase__stat-label"><?php esc_html_e( 'Cursos ativos', 'questoes' ); ?></span>
                        <strong class="questoes-course-showcase__stat-value"><?php echo esc_html( number_format_i18n( $total_courses ) ); ?></strong>
                    </li>
                    <?php if ( $total_questions > 0 ) : ?>
                        <li>
                            <span class="questoes-course-showcase__stat-label"><?php esc_html_e( 'Questões no banco', 'questoes' ); ?></span>
                            <strong class="questoes-course-showcase__stat-value"><?php echo esc_html( number_format_i18n( $total_questions ) ); ?></strong>
                        </li>
                    <?php endif; ?>
                </ul>
            </header>

            <?php if ( $region_filters_enabled ) : ?>
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
            <?php endif; ?>

            <?php if ( ! empty( $additional_content ) ) : ?>
                <div class="questoes-course-browser__callout"><?php echo $additional_content; ?></div>
            <?php endif; ?>

            <p class="questoes-course-browser__status" data-template="<?php echo esc_attr__( 'Exibindo %1$s de %2$s cursos.', 'questoes' ); ?>" data-total="<?php echo esc_attr( $total_courses ); ?>" hidden></p>

            <div class="questoes-course-browser__grid questoes-course-browser__grid--showcase">
                <?php foreach ( $primary_courses as $course ) : ?>
                    <?php echo questoes_render_course_card( $course, array( 'context' => 'featured' ) ); ?>
                <?php endforeach; ?>

                <?php foreach ( $secondary_courses as $course ) : ?>
                    <?php echo questoes_render_course_card( $course, array( 'context' => 'all' ) ); ?>
                <?php endforeach; ?>
            </div>

            <p class="questoes-course-browser__empty" hidden><?php echo esc_html( $empty_message ); ?></p>
        </div>
    </div>
</section>
