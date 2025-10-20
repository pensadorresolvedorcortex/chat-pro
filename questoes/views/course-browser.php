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
$totals           = isset( $totals ) && is_array( $totals ) ? $totals : array();

$total_courses   = isset( $totals['courses'] ) ? (int) $totals['courses'] : count( $all_courses );
$total_questions = isset( $totals['questions'] ) ? (int) $totals['questions'] : 0;
$hero_title       = ! empty( $featured_title ) ? $featured_title : __( 'Cursos em alta', 'questoes' );
$hero_description = ! empty( $featured_description ) ? wp_kses_post( $featured_description ) : __( 'Principais concursos do momento.', 'questoes' );

$empty_message = __( 'Nenhum curso corresponde aos critérios informados.', 'questoes' );

$primary_courses   = $featured_courses;
$primary_ids       = array();
$secondary_courses = array();

foreach ( $featured_courses as $course ) {
    if ( isset( $course['id'] ) ) {
        $primary_ids[] = $course['id'];
    }
}

if ( empty( $primary_courses ) ) {
    $primary_courses = array_slice( $all_courses, 0, 6 );
    foreach ( $primary_courses as $course ) {
        if ( isset( $course['id'] ) ) {
            $primary_ids[] = $course['id'];
        }
    }
}

if ( ! empty( $all_courses ) ) {
    foreach ( $all_courses as $course ) {
        if ( isset( $course['id'] ) && in_array( $course['id'], $primary_ids, true ) ) {
            continue;
        }

        $secondary_courses[] = $course;
    }
}
?>
<section class="questoes-stage questoes-course-browser" data-component="courses">
    <div class="questoes-shell questoes-shell--wide">
        <div class="questoes-course-landing">
            <header class="questoes-course-landing__hero">
                <div class="questoes-course-landing__intro">
                    <span class="questoes-course-browser__eyebrow"><?php echo esc_html( $hero_title ); ?></span>
                    <h2 class="questoes-course-browser__title"><?php echo wp_kses_post( $hero_description ); ?></h2>
                    <p class="questoes-course-landing__summary questoes-course-browser__status" data-template="<?php echo esc_attr__( 'Exibindo %1$s de %2$s cursos.', 'questoes' ); ?>" data-total="<?php echo esc_attr( $total_courses ); ?>" hidden></p>
                </div>
                <ul class="questoes-course-landing__stats">
                    <li>
                        <span class="questoes-course-landing__stat-label"><?php esc_html_e( 'Cursos ativos', 'questoes' ); ?></span>
                        <strong class="questoes-course-landing__stat-value"><?php echo esc_html( number_format_i18n( $total_courses ) ); ?></strong>
                    </li>
                    <?php if ( $total_questions > 0 ) : ?>
                        <li>
                            <span class="questoes-course-landing__stat-label"><?php esc_html_e( 'Questões no banco', 'questoes' ); ?></span>
                            <strong class="questoes-course-landing__stat-value"><?php echo esc_html( number_format_i18n( $total_questions ) ); ?></strong>
                        </li>
                    <?php endif; ?>
                </ul>
            </header>

            <?php if ( ! empty( $additional_content ) ) : ?>
                <div class="questoes-course-browser__callout"><?php echo $additional_content; ?></div>
            <?php endif; ?>

            <div class="questoes-course-landing__grid" data-role="course-grid">
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
