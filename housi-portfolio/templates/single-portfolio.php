<?php
/**
 * Single portfolio item template.
 *
 * @package Housi_Portfolio
 */

wp_enqueue_style( 'housi-portfolio' );

get_header();
?>
<main class="housi-portfolio-single">
<?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>
    <article id="post-<?php the_ID(); ?>" <?php post_class( 'housi-portfolio-item' ); ?>>
        <?php if ( has_post_thumbnail() ) : ?>
            <div class="housi-portfolio-thumb"><?php the_post_thumbnail( 'large' ); ?></div>
        <?php endif; ?>
        <h1 class="housi-portfolio-title"><?php the_title(); ?></h1>
        <div class="housi-portfolio-content"><?php the_content(); ?></div>
    </article>
<?php endwhile; endif; ?>
</main>
<?php get_footer(); ?>
