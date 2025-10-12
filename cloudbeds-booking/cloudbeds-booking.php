<?php
/*
Plugin Name: Cloudbeds Booking Shortcodes
Description: Shortcodes para formulários de reserva integrados ao Cloudbeds.
Version: 1.8.1
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
        function getField(form, name) {
            return form.querySelector('input[name="' + name + '"], select[name="' + name + '"]');
        }

        function handleRedirect(event, form) {
            var checkin = getField(form, 'checkin');
            var checkout = getField(form, 'checkout');
            var rooms = getField(form, 'rooms');
            var guests = getField(form, 'guests');

            if (!checkin || !checkout || !rooms || !guests) {
                return false;
            }

            if (!checkin.value || !checkout.value || !rooms.value || !guests.value) {
                return false;
            }

            var params = new URLSearchParams();
            params.set('checkin', checkin.value);
            params.set('checkout', checkout.value);
            params.set('rooms', rooms.value);
            params.set('guests', guests.value);

            event.preventDefault();
            window.location.href = redirectUrl + '?' + params.toString();
            return true;
        }

        function attachHandlers(form) {
            if (form.dataset.cloudbedsRedirectAttached === '1') {
                return;
            }
            form.dataset.cloudbedsRedirectAttached = '1';

            form.addEventListener('submit', function (event) {
                handleRedirect(event, form);
            });

            var submitButtons = form.querySelectorAll('button[type="submit"], input[type="submit"]');
            submitButtons.forEach(function (button) {
                button.addEventListener('click', function (event) {
                    handleRedirect(event, form);
                });
            });
        }

        function collectTargetForms(root) {
            var forms = [];

            if (root === document) {
                forms = Array.prototype.slice.call(document.querySelectorAll('form'));
            } else {
                if (root.matches && root.matches('form')) {
                    forms.push(root);
                }
                if (root.querySelectorAll) {
                    forms = forms.concat(Array.prototype.slice.call(root.querySelectorAll('form')));
                }
            }

            forms.forEach(function (form) {
                if (getField(form, 'checkin') && getField(form, 'checkout') && getField(form, 'rooms') && getField(form, 'guests')) {
                    attachHandlers(form);
                }
            });
        }

        collectTargetForms(document);

        var observer = new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                mutation.addedNodes.forEach(function (node) {
                    if (!node || node.nodeType !== 1) {
                        return;
                    }

                    collectTargetForms(node);
                });
            });
        });

        observer.observe(document.body, { childList: true, subtree: true });
    });
    </script>
    <?php
}
add_action( 'wp_footer', 'cloudbeds_redirect_on_checkin' );
