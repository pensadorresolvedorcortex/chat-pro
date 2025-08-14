<?php
/**
 * Archive template for portfolio items.
 *
 * @package Housi_Portfolio
 */

wp_enqueue_style( 'housi-portfolio' );
wp_enqueue_script( 'housi-portfolio' );

get_header();
?>
<main class="housi-portfolio-archive">
<?php if ( have_posts() ) : ?>
    <?php
    $terms = get_terms( [
        'taxonomy'   => 'project-cat',
        'hide_empty' => true,
    ] );
    if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) : ?>
        <div class="housi-portfolio-filters">
            <button class="active" data-term="all"><?php esc_html_e( 'Mostrar Todos', 'housi-portfolio' ); ?></button>
            <?php foreach ( $terms as $term ) : ?>
                <button data-term="<?php echo esc_attr( $term->slug ); ?>"><?php echo esc_html( $term->name ); ?></button>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="housi-portfolio-grid columns-3">
    <?php while ( have_posts() ) : the_post(); ?>
        <?php
        $post_terms   = get_the_terms( get_the_ID(), 'project-cat' );
        $term_classes = '';
        if ( ! empty( $post_terms ) && ! is_wp_error( $post_terms ) ) {
            foreach ( $post_terms as $t ) {
                $term_classes .= ' term-' . $t->slug;
            }
        }
        ?>
        <div class="housi-portfolio-item<?php echo esc_attr( $term_classes ); ?>">
            <?php if ( has_post_thumbnail() ) : ?>
                <div class="housi-portfolio-thumb"><a href="<?php the_permalink(); ?>">
                    <?php the_post_thumbnail( 'large' ); ?>
                    <div class="housi-portfolio-overlay">
                        <h3 class="housi-portfolio-title"><?php the_title(); ?></h3>
                    </div>
                </a></div>
            <?php else : ?>
                <h3 class="housi-portfolio-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
            <?php endif; ?>
        </div>
    <?php endwhile; ?>
    </div>
    <?php the_posts_pagination(); ?>
<?php else : ?>
    <p><?php esc_html_e( 'No portfolio items found.', 'housi-portfolio' ); ?></p>
<?php endif; ?>
</main>
<?php get_footer(); ?>
