<?php
/**
 * Trending browser template.
 *
 * @package Questoes
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$questions   = isset( $trending_questions ) && is_array( $trending_questions ) ? $trending_questions : array();
$courses     = isset( $trending_courses ) && is_array( $trending_courses ) ? $trending_courses : array();
$disciplines = isset( $trending_disciplines ) && is_array( $trending_disciplines ) ? $trending_disciplines : array();

$has_questions   = ! empty( $questions );
$has_courses     = ! empty( $courses );
$has_disciplines = ! empty( $disciplines );
$has_any         = $has_questions || $has_courses || $has_disciplines;
?>
<section class="questoes-stage questoes-trending-browser" data-component="trending">
    <div class="questoes-shell questoes-shell--wide questoes-trending">
        <header class="questoes-trending__header">
            <?php if ( ! empty( $title ) ) : ?>
                <h2 class="questoes-trending__title"><?php echo esc_html( $title ); ?></h2>
            <?php endif; ?>

            <?php if ( ! empty( $description ) ) : ?>
                <p class="questoes-trending__description"><?php echo wp_kses_post( $description ); ?></p>
            <?php endif; ?>
        </header>

        <?php if ( $has_any ) : ?>
            <div class="questoes-trending__grid">
                <?php if ( $has_questions ) : ?>
                    <section class="questoes-trending__section questoes-trending__section--questions">
                        <header class="questoes-trending__section-header">
                            <h3><?php esc_html_e( 'Questões mais acessadas', 'questoes' ); ?></h3>
                        </header>
                        <ol class="questoes-trending-questions">
                            <?php
                            foreach ( $questions as $index => $question ) :
                                $question_id   = isset( $question['id'] ) ? (int) $question['id'] : 0;
                                $title_value   = isset( $question['title'] ) ? $question['title'] : '';
                                $permalink     = ! empty( $question['permalink'] ) ? $question['permalink'] : ( $question_id ? get_permalink( $question_id ) : '' );
                                $views         = isset( $question['views'] ) ? (int) $question['views'] : 0;
                                $raw_categories = isset( $question['categories'] ) ? (array) $question['categories'] : array();
                                $categories    = array_filter( array_map( 'sanitize_text_field', $raw_categories ) );
                                $content_value = isset( $question['content'] ) ? $question['content'] : '';
                                $excerpt       = ! empty( $question['excerpt'] ) ? $question['excerpt'] : wp_trim_words( wp_strip_all_tags( $content_value ), 28 );
                                $difficulty    = ! empty( $question['difficulty'] ) ? questoes_get_difficulty_label( $question['difficulty'] ) : '';
                                $comment_count = $question_id ? get_comments_number( $question_id ) : 0;
                                ?>
                                <li class="questoes-trending-question">
                                    <article class="questoes-trending-question__card">
                                        <span class="questoes-trending-question__position"><?php echo esc_html( sprintf( '%02d', $index + 1 ) ); ?></span>
                                        <div class="questoes-trending-question__body">
                                            <h4 class="questoes-trending-question__title">
                                                <?php if ( $permalink ) : ?>
                                                    <a href="<?php echo esc_url( $permalink ); ?>" class="questoes-trending-question__link">
                                                        <?php echo esc_html( $title_value ); ?>
                                                    </a>
                                                <?php else : ?>
                                                    <?php echo esc_html( $title_value ); ?>
                                                <?php endif; ?>
                                            </h4>

                                            <?php if ( ! empty( $categories ) ) : ?>
                                                <p class="questoes-trending-question__categories"><?php echo esc_html( implode( ' • ', $categories ) ); ?></p>
                                            <?php endif; ?>

                                            <?php if ( ! empty( $excerpt ) ) : ?>
                                                <p class="questoes-trending-question__excerpt"><?php echo esc_html( $excerpt ); ?></p>
                                            <?php endif; ?>

                                            <div class="questoes-trending-question__meta">
                                                <span class="questoes-trending-question__views"><?php echo esc_html( questoes_format_views( $views ) ); ?></span>
                                                <?php if ( $comment_count > 0 ) : ?>
                                                    <span class="questoes-trending-question__comments">
                                                        <?php
                                                        printf(
                                                            esc_html( _n( '%s comentário', '%s comentários', $comment_count, 'questoes' ) ),
                                                            esc_html( number_format_i18n( $comment_count ) )
                                                        );
                                                        ?>
                                                    </span>
                                                <?php endif; ?>
                                                <?php if ( ! empty( $difficulty ) ) : ?>
                                                    <span class="questoes-trending-question__difficulty"><?php echo esc_html( $difficulty ); ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </article>
                                </li>
                            <?php endforeach; ?>
                        </ol>
                    </section>
                <?php endif; ?>

                <?php if ( $has_courses ) : ?>
                    <section class="questoes-trending__section questoes-trending__section--courses">
                        <header class="questoes-trending__section-header">
                            <h3><?php esc_html_e( 'Cursos mais acessados', 'questoes' ); ?></h3>
                        </header>
                        <div class="questoes-trending-courses">
                            <?php foreach ( $courses as $course ) : ?>
                                <?php echo questoes_render_course_card( $course, array( 'context' => 'trending' ) ); ?>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>

                <?php if ( $has_disciplines ) : ?>
                    <section class="questoes-trending__section questoes-trending__section--disciplines">
                        <header class="questoes-trending__section-header">
                            <h3><?php esc_html_e( 'Disciplinas em alta', 'questoes' ); ?></h3>
                        </header>
                        <ul class="questoes-trending-disciplines">
                            <?php
                            foreach ( $disciplines as $entry ) :
                                $name      = isset( $entry['name'] ) ? $entry['name'] : '';
                                $link      = ! empty( $entry['link'] ) ? $entry['link'] : '';
                                $views     = isset( $entry['views'] ) ? (int) $entry['views'] : 0;
                                $questions = isset( $entry['questions'] ) ? (int) $entry['questions'] : ( isset( $entry['count'] ) ? (int) $entry['count'] : 0 );
                                $comments  = isset( $entry['comments'] ) ? (int) $entry['comments'] : 0;
                                ?>
                                <li class="questoes-trending-discipline">
                                    <?php if ( $link ) : ?>
                                        <a class="questoes-trending-discipline__card" href="<?php echo esc_url( $link ); ?>">
                                    <?php else : ?>
                                        <div class="questoes-trending-discipline__card" role="group">
                                    <?php endif; ?>
                                            <div class="questoes-trending-discipline__head">
                                                <span class="questoes-trending-discipline__name"><?php echo esc_html( $name ); ?></span>
                                            </div>
                                            <div class="questoes-trending-discipline__stats">
                                                <span class="questoes-trending-discipline__views"><?php echo esc_html( questoes_format_views( $views ) ); ?></span>
                                                <span class="questoes-trending-discipline__questions">
                                                    <?php
                                                    printf(
                                                        esc_html( _n( '%s questão', '%s questões', $questions, 'questoes' ) ),
                                                        esc_html( number_format_i18n( $questions ) )
                                                    );
                                                    ?>
                                                </span>
                                                <?php if ( $comments > 0 ) : ?>
                                                    <span class="questoes-trending-discipline__comments">
                                                        <?php
                                                        printf(
                                                            esc_html( _n( '%s comentário', '%s comentários', $comments, 'questoes' ) ),
                                                            esc_html( number_format_i18n( $comments ) )
                                                        );
                                                        ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                    <?php if ( $link ) : ?>
                                        </a>
                                    <?php else : ?>
                                        </div>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </section>
                <?php endif; ?>
            </div>
        <?php else : ?>
            <p class="questoes-empty"><?php esc_html_e( 'Nenhum item em alta no momento.', 'questoes' ); ?></p>
        <?php endif; ?>
    </div>
</section>
