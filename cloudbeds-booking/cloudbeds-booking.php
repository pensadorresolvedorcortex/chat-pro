<?php
/*
Plugin Name: Cloudbeds Booking Widget
Description: Adiciona um widget de reserva integrado ao Cloudbeds.
Version: 1.0.0
Author: ChatGPT Codex
License: GPL2
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Cloudbeds_Booking_Widget extends WP_Widget {
    public function __construct() {
        parent::__construct(
            'cloudbeds_booking_widget',
            'Cloudbeds Booking',
            array( 'description' => 'Formul\xC3\xA1rio de reserva integrado ao Cloudbeds.' )
        );
    }

    public function widget( $args, $instance ) {
        echo $args['before_widget'];
        ?>
        <form action="https://hotels.cloudbeds.com/reservas/VA2vgp" method="GET" class="cloudbeds-booking-form">
            <p>
                <label for="cloudbeds-checkin">Check-in</label>
                <input type="date" id="cloudbeds-checkin" name="checkin" required>
            </p>
            <p>
                <label for="cloudbeds-checkout">Check-out</label>
                <input type="date" id="cloudbeds-checkout" name="checkout" required>
            </p>
            <p>
                <label for="cloudbeds-guests">H\xC3\xB3spedes</label>
                <input type="number" id="cloudbeds-guests" name="guests" min="1" value="1" required>
            </p>
            <p>
                <button type="submit">Reservar</button>
            </p>
        </form>
        <?php
        echo $args['after_widget'];
    }

    public function form( $instance ) {
        echo '<p>'. __( 'Nenhuma configura\xC3\xA7\xC3\xA3o necess\xC3\xA1ria.', 'cloudbeds-booking' ) .'</p>';
    }

    public function update( $new_instance, $old_instance ) {
        return $old_instance;
    }
}

function cloudbeds_booking_register_widget() {
    register_widget( 'Cloudbeds_Booking_Widget' );
}
add_action( 'widgets_init', 'cloudbeds_booking_register_widget' );

?>
