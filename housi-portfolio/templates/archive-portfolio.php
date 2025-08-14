<?php
/**
 * Archive template for portfolio items.
 *
 * @package Housi_Portfolio
 */

wp_enqueue_style( 'housi-portfolio' );

get_header();
?>
<main class="housi-portfolio-archive">
<?php if ( have_posts() ) : ?>
    <div class="housi-portfolio-grid columns-3">
    <?php while ( have_posts() ) : the_post(); ?>
        <div class="housi-portfolio-item">
            <?php if ( has_post_thumbnail() ) : ?>
                <div class="housi-portfolio-thumb"><a href="<?php the_permalink(); ?>"><?php the_post_thumbnail( 'medium' ); ?></a></div>
            <?php endif; ?>
            <h3 class="housi-portfolio-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
        </div>
    <?php endwhile; ?>
    </div>
    <?php the_posts_pagination(); ?>
<?php else : ?>
    <p><?php esc_html_e( 'No portfolio items found.', 'housi-portfolio' ); ?></p>
<?php endif; ?>
</main>
<?php get_footer(); ?>
