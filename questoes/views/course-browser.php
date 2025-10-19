<?php
/**
 * Course browser template.
 *
 * @package Questoes
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$featured_courses = isset( $featured ) ? $featured : array();
$all_courses      = isset( $courses ) ? $courses : array();
$region_entries   = isset( $regions ) ? $regions : array();
$totals           = isset( $totals ) ? $totals : array();

$total_courses   = isset( $totals['courses'] ) ? (int) $totals['courses'] : count( $all_courses );
$total_questions = isset( $totals['questions'] ) ? (int) $totals['questions'] : 0;

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
?>
<section class="questoes-course-browser" data-component="courses">
    <?php if ( ! empty( $featured_courses ) ) : ?>
        <div class="questoes-course-browser__section questoes-course-browser__section--featured">
            <header class="questoes-course-browser__header">
                <?php if ( ! empty( $featured_title ) ) : ?>
                    <h2 class="questoes-course-browser__title"><?php echo esc_html( $featured_title ); ?></h2>
                <?php endif; ?>

                <?php if ( ! empty( $featured_description ) ) : ?>
                    <p class="questoes-course-browser__description"><?php echo $featured_description; ?></p>
                <?php endif; ?>
            </header>

            <div class="questoes-course-browser__grid questoes-course-browser__grid--featured">
                <?php foreach ( $featured_courses as $course ) : ?>
                    <?php echo questoes_render_course_card( $course, array( 'context' => 'featured' ) ); ?>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="questoes-course-browser__section questoes-course-browser__section--regions">
        <header class="questoes-course-browser__header">
            <?php if ( ! empty( $regions_title ) ) : ?>
                <h2 class="questoes-course-browser__title"><?php echo esc_html( $regions_title ); ?></h2>
            <?php endif; ?>

            <?php if ( ! empty( $regions_description ) ) : ?>
                <p class="questoes-course-browser__description"><?php echo $regions_description; ?></p>
            <?php endif; ?>
        </header>

        <div class="questoes-course-browser__summary" role="status" aria-live="polite">
            <span class="questoes-course-browser__summary-item">
                <?php
                printf(
                    /* translators: %s: total number of courses */
                    esc_html__( '%s cursos disponíveis', 'questoes' ),
                    esc_html( number_format_i18n( $total_courses ) )
                );
                ?>
            </span>
            <?php if ( $total_questions > 0 ) : ?>
                <span class="questoes-course-browser__summary-item">
                    <?php
                    printf(
                        /* translators: %s: total number of questions */
                        esc_html__( '%s questões catalogadas', 'questoes' ),
                        esc_html( number_format_i18n( $total_questions ) )
                    );
                    ?>
                </span>
            <?php endif; ?>
        </div>

        <?php if ( ! empty( $additional_content ) ) : ?>
            <div class="questoes-course-browser__extra"><?php echo $additional_content; ?></div>
        <?php endif; ?>

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

        <p class="questoes-course-browser__status" data-template="<?php echo esc_attr__( 'Exibindo %1$s de %2$s cursos.', 'questoes' ); ?>" data-total="<?php echo esc_attr( $total_courses ); ?>" hidden></p>

        <div class="questoes-course-browser__grid questoes-course-browser__grid--all">
            <?php foreach ( $all_courses as $course ) : ?>
                <?php echo questoes_render_course_card( $course, array( 'context' => 'all' ) ); ?>
            <?php endforeach; ?>
        </div>

        <p class="questoes-course-browser__empty" hidden><?php echo esc_html( $empty_message ); ?></p>
    </div>
</section>
