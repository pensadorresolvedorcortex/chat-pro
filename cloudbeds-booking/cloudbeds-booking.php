<?php
/*
Plugin Name: Cloudbeds Booking Shortcodes
Description: Shortcodes para formulários de reserva integrados ao Cloudbeds.
Version: 1.4.0
Author: ChatGPT Codex
License: GPL2
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Renderiza o formulário de reserva.
 * O shortcode utilizado define o layout vertical ou horizontal.
 */
function cloudbeds_booking_form( $atts = array(), $content = null, $tag = '' ) {
    $action = 'https://hotels.cloudbeds.com/reservas/VA2vgp';
    $layout = $tag === 'cloudbeds_booking_horizontal' ? 'horizontal' : 'vertical';

    ob_start();
    ?>
    <form action="<?php echo esc_url( $action ); ?>" method="get" class="cloudbeds-booking-<?php echo esc_attr( $layout ); ?>">
        <?php if ( $layout === 'horizontal' ) : ?>
            <input type="date" name="checkin" required />
            <input type="date" name="checkout" required />
            <input type="number" name="guests" min="1" value="1" required />
            <button type="submit">Reservar</button>
        <?php else : ?>
            <p>
                <label>Check-in<br />
                    <input type="date" name="checkin" required />
                </label>
            </p>
            <p>
                <label>Check-out<br />
                    <input type="date" name="checkout" required />
                </label>
            </p>
            <p>
                <label>Hóspedes<br />
                    <input type="number" name="guests" min="1" value="1" required />
                </label>
            </p>
            <p><button type="submit">Reservar</button></p>
        <?php endif; ?>
    </form>
    <?php
    return ob_get_clean();
}

add_shortcode( 'cloudbeds_booking_horizontal', 'cloudbeds_booking_form' );
add_shortcode( 'cloudbeds_booking_vertical', 'cloudbeds_booking_form' );

/**
 * Carrega o estilo dos formulários.
 */
function cloudbeds_booking_enqueue_styles() {
    wp_register_style( 'cloudbeds-booking', plugins_url( 'style.css', __FILE__ ) );
    wp_enqueue_style( 'cloudbeds-booking' );
}
add_action( 'wp_enqueue_scripts', 'cloudbeds_booking_enqueue_styles' );

/**
 * Substitui o formulário padrão do tema pelo formulário vertical.
 */
function cloudbeds_replace_default_form() {
    if ( is_admin() ) {
        return;
    }
    $html = do_shortcode( '[cloudbeds_booking_vertical]' );
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        var container = document.querySelector('.cs-room-booking');
        if (container) {
            container.innerHTML = <?php echo json_encode( $html ); ?>;
        }
    });
    </script>
    <?php
}
add_action( 'wp_footer', 'cloudbeds_replace_default_form' );
