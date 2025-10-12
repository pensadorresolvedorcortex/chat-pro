<?php
/*
Plugin Name: Cloudbeds Booking Shortcodes
Description: Shortcodes para formulários de reserva integrados ao Cloudbeds.
Version: 1.8.0
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
            <input type="number" name="rooms" min="1" value="1" required />
            <input type="number" name="guests" min="1" value="1" required />
            <button type="submit">CHECAR VAGAS</button>
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
                <label>Quartos<br />
                    <input type="number" name="rooms" min="1" value="1" required />
                </label>
            </p>
            <p>
                <label>Hóspedes<br />
                    <input type="number" name="guests" min="1" value="1" required />
                </label>
            </p>
            <p><button type="submit">CHECAR VAGAS</button></p>
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

/**
 * Redireciona para o Cloudbeds após preencher o formulário e clicar em "Checar vagas".
 */
function cloudbeds_redirect_on_checkin() {
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        var redirectUrl = 'https://hotels.cloudbeds.com/reservas/VA2vgp';
        function handleForm(form) {
            form.addEventListener('submit', function (event) {
                var checkin = form.querySelector('input[name="checkin"]');
                var checkout = form.querySelector('input[name="checkout"]');
                var rooms = form.querySelector('input[name="rooms"]');
                var guests = form.querySelector('input[name="guests"]');

                if (!checkin || !checkout || !rooms || !guests) {
                    return;
                }

                if (!checkin.value || !checkout.value || !rooms.value || !guests.value) {
                    return;
                }

                var params = new URLSearchParams();
                params.set('checkin', checkin.value);
                params.set('checkout', checkout.value);
                params.set('rooms', rooms.value);
                params.set('guests', guests.value);

                event.preventDefault();
                window.location.href = redirectUrl + '?' + params.toString();
            });
        }

        var scopeForms = document.querySelectorAll('.cs-room-booking form');
        scopeForms.forEach(handleForm);

        var shortcodeForms = document.querySelectorAll('form.cloudbeds-booking-horizontal, form.cloudbeds-booking-vertical');
        shortcodeForms.forEach(handleForm);
    });
    </script>
    <?php
}
add_action( 'wp_footer', 'cloudbeds_redirect_on_checkin' );
