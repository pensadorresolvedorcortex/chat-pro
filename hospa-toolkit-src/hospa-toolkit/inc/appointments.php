<?php
/**
 * Appointment management helpers.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! defined( 'HOSPA_TOOLKIT_APPOINTMENTS_DB_VERSION' ) ) {
    define( 'HOSPA_TOOLKIT_APPOINTMENTS_DB_VERSION', '1.0.0' );
}

/**
 * Return the fully-qualified appointments table name.
 */
function hospa_toolkit_get_appointments_table() {
    global $wpdb;

    return $wpdb->prefix . 'hospa_appointments';
}

/**
 * Plugin install callback.
 */
function hospa_toolkit_handle_activation() {
    hospa_toolkit_install_appointments_table();
}

/**
 * Ensure the appointment table exists and is up to date.
 */
function hospa_toolkit_install_appointments_table() {
    global $wpdb;

    $table_name      = hospa_toolkit_get_appointments_table();
    $charset_collate = $wpdb->get_charset_collate();

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $sql = "CREATE TABLE {$table_name} (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        doctor_id BIGINT(20) UNSIGNED NOT NULL,
        patient_id BIGINT(20) UNSIGNED NOT NULL,
        patient_name VARCHAR(191) NOT NULL DEFAULT '',
        patient_email VARCHAR(191) NOT NULL DEFAULT '',
        patient_phone VARCHAR(50) NOT NULL DEFAULT '',
        visit_type VARCHAR(100) NOT NULL DEFAULT '',
        appointment_start DATETIME NOT NULL,
        appointment_end DATETIME NOT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'pending',
        notes TEXT NULL,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        PRIMARY KEY  (id),
        KEY doctor_id (doctor_id),
        KEY patient_id (patient_id),
        KEY appointment_start (appointment_start),
        KEY status (status)
    ) {$charset_collate};";

    dbDelta( $sql );

    update_option( 'hospa_toolkit_appointments_db_version', HOSPA_TOOLKIT_APPOINTMENTS_DB_VERSION );
}

add_action( 'plugins_loaded', 'hospa_toolkit_maybe_upgrade_appointments_table' );

/**
 * Run table upgrades if needed.
 */
function hospa_toolkit_maybe_upgrade_appointments_table() {
    $stored_version = get_option( 'hospa_toolkit_appointments_db_version' );

    if ( version_compare( $stored_version, HOSPA_TOOLKIT_APPOINTMENTS_DB_VERSION, '<' ) ) {
        hospa_toolkit_install_appointments_table();
    }
}

add_action( 'admin_menu', 'hospa_toolkit_register_appointments_admin_page' );
add_action( 'add_meta_boxes', 'hospa_toolkit_register_doctor_availability_metabox' );
add_action( 'save_post_doctors', 'hospa_toolkit_save_doctor_availability_meta' );
add_action( 'load-toplevel_page_hospa-toolkit-appointments', 'hospa_toolkit_handle_appointments_page_actions' );

/**
 * Register the appointments admin page.
 */
function hospa_toolkit_register_appointments_admin_page() {
    add_menu_page(
        __( 'Appointments', 'hospa-toolkit' ),
        __( 'Appointments', 'hospa-toolkit' ),
        'manage_options',
        'hospa-toolkit-appointments',
        'hospa_toolkit_render_appointments_page',
        'dashicons-calendar-alt',
        58
    );
}

/**
 * Process appointment page actions early to avoid header issues.
 */
function hospa_toolkit_handle_appointments_page_actions() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $list_table = new Hospa_Toolkit_Appointments_Table();
    $list_table->handle_request();
}

/**
 * Register doctor availability metabox on the Doctors post type.
 */
function hospa_toolkit_register_doctor_availability_metabox() {
    add_meta_box(
        'hospa-toolkit-doctor-availability',
        __( 'Appointment Availability', 'hospa-toolkit' ),
        'hospa_toolkit_render_doctor_availability_metabox',
        'doctors',
        'normal',
        'high'
    );
}

/**
 * Render the availability metabox.
 */
function hospa_toolkit_render_doctor_availability_metabox( $post ) {
    wp_nonce_field( 'hospa_toolkit_save_doctor_availability', 'hospa_toolkit_doctor_availability_nonce' );

    $availability = get_post_meta( $post->ID, '_hospa_doctor_availability', true );

    if ( ! is_array( $availability ) ) {
        $availability = [];
    }

    $defaults = hospa_toolkit_default_doctor_availability();
    $availability = wp_parse_args( $availability, $defaults );

    $days = hospa_toolkit_get_weekdays();

    ?>
    <div class="hospa-doctor-availability">
        <p class="description"><?php esc_html_e( 'Configure recurring weekly availability. You can fine-tune specific days later when building the booking flow.', 'hospa-toolkit' ); ?></p>

        <table class="widefat striped">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Day', 'hospa-toolkit' ); ?></th>
                    <th><?php esc_html_e( 'Enabled', 'hospa-toolkit' ); ?></th>
                    <th><?php esc_html_e( 'Session 1 (Start – End)', 'hospa-toolkit' ); ?></th>
                    <th><?php esc_html_e( 'Session 2 (optional)', 'hospa-toolkit' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $days as $key => $label ) :
                    $day_settings = isset( $availability['days'][ $key ] ) ? $availability['days'][ $key ] : [];
                    $day_settings = wp_parse_args(
                        $day_settings,
                        [
                            'enabled'         => false,
                            'session_1_start' => '',
                            'session_1_end'   => '',
                            'session_2_start' => '',
                            'session_2_end'   => '',
                        ]
                    );
                    ?>
                    <tr>
                        <th scope="row">
                            <label for="hospa-availability-<?php echo esc_attr( $key ); ?>">
                                <?php echo esc_html( $label ); ?>
                            </label>
                        </th>
                        <td>
                            <input type="checkbox" id="hospa-availability-<?php echo esc_attr( $key ); ?>" name="hospa_doctor_availability[days][<?php echo esc_attr( $key ); ?>][enabled]" value="1" <?php checked( $day_settings['enabled'], true ); ?> />
                        </td>
                        <td>
                            <div class="session-fields">
                                <input type="time" name="hospa_doctor_availability[days][<?php echo esc_attr( $key ); ?>][session_1_start]" value="<?php echo esc_attr( $day_settings['session_1_start'] ); ?>" />
                                <span class="sep">&ndash;</span>
                                <input type="time" name="hospa_doctor_availability[days][<?php echo esc_attr( $key ); ?>][session_1_end]" value="<?php echo esc_attr( $day_settings['session_1_end'] ); ?>" />
                            </div>
                        </td>
                        <td>
                            <div class="session-fields">
                                <input type="time" name="hospa_doctor_availability[days][<?php echo esc_attr( $key ); ?>][session_2_start]" value="<?php echo esc_attr( $day_settings['session_2_start'] ); ?>" />
                                <span class="sep">&ndash;</span>
                                <input type="time" name="hospa_doctor_availability[days][<?php echo esc_attr( $key ); ?>][session_2_end]" value="<?php echo esc_attr( $day_settings['session_2_end'] ); ?>" />
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="availability-settings mt-3">
            <p>
                <label for="hospa-slot-length" class="fw-semibold"><?php esc_html_e( 'Default Slot Length (minutes)', 'hospa-toolkit' ); ?></label><br />
                <input type="number" min="5" step="5" id="hospa-slot-length" name="hospa_doctor_availability[slot_length]" value="<?php echo esc_attr( $availability['slot_length'] ); ?>" />
            </p>
            <p>
                <label for="hospa-slot-buffer" class="fw-semibold"><?php esc_html_e( 'Buffer Between Slots (minutes)', 'hospa-toolkit' ); ?></label><br />
                <input type="number" min="0" step="5" id="hospa-slot-buffer" name="hospa_doctor_availability[slot_buffer]" value="<?php echo esc_attr( $availability['slot_buffer'] ); ?>" />
            </p>
            <p>
                <label for="hospa-slot-capacity" class="fw-semibold"><?php esc_html_e( 'Max Bookings Per Slot', 'hospa-toolkit' ); ?></label><br />
                <input type="number" min="1" step="1" id="hospa-slot-capacity" name="hospa_doctor_availability[slot_capacity]" value="<?php echo esc_attr( $availability['slot_capacity'] ); ?>" />
            </p>
        </div>
    </div>
    <style>
        .hospa-doctor-availability .session-fields {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .hospa-doctor-availability .session-fields .sep {
            opacity: 0.7;
        }
        .hospa-doctor-availability .availability-settings input[type="number"] {
            width: 120px;
        }
    </style>
    <?php
}

/**
 * Save doctor availability metadata.
 */
function hospa_toolkit_save_doctor_availability_meta( $post_id ) {
    if ( ! isset( $_POST['hospa_toolkit_doctor_availability_nonce'] ) ) {
        return;
    }

    if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['hospa_toolkit_doctor_availability_nonce'] ) ), 'hospa_toolkit_save_doctor_availability' ) ) {
        return;
    }

    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    if ( ! isset( $_POST['hospa_doctor_availability'] ) || ! is_array( $_POST['hospa_doctor_availability'] ) ) {
        delete_post_meta( $post_id, '_hospa_doctor_availability' );
        return;
    }

    $raw  = wp_unslash( $_POST['hospa_doctor_availability'] );
    $days = hospa_toolkit_get_weekdays();

    $sanitized = [
        'slot_length'  => isset( $raw['slot_length'] ) ? max( 5, intval( $raw['slot_length'] ) ) : 30,
        'slot_buffer'  => isset( $raw['slot_buffer'] ) ? max( 0, intval( $raw['slot_buffer'] ) ) : 0,
        'slot_capacity'=> isset( $raw['slot_capacity'] ) ? max( 1, intval( $raw['slot_capacity'] ) ) : 1,
        'days'         => [],
    ];

    foreach ( $days as $key => $label ) {
        $day_raw = isset( $raw['days'][ $key ] ) ? $raw['days'][ $key ] : [];

        $enabled = ! empty( $day_raw['enabled'] );

        $session_1_start = hospa_toolkit_sanitize_time_field( isset( $day_raw['session_1_start'] ) ? $day_raw['session_1_start'] : '' );
        $session_1_end   = hospa_toolkit_sanitize_time_field( isset( $day_raw['session_1_end'] ) ? $day_raw['session_1_end'] : '' );
        $session_2_start = hospa_toolkit_sanitize_time_field( isset( $day_raw['session_2_start'] ) ? $day_raw['session_2_start'] : '' );
        $session_2_end   = hospa_toolkit_sanitize_time_field( isset( $day_raw['session_2_end'] ) ? $day_raw['session_2_end'] : '' );

        $sanitized['days'][ $key ] = [
            'enabled'         => $enabled,
            'session_1_start' => $session_1_start,
            'session_1_end'   => $session_1_end,
            'session_2_start' => $session_2_start,
            'session_2_end'   => $session_2_end,
        ];
    }

    update_post_meta( $post_id, '_hospa_doctor_availability', $sanitized );
}

/**
 * Provide default availability.
 */
function hospa_toolkit_default_doctor_availability() {
    $days = hospa_toolkit_get_weekdays();

    $default_day = [
        'enabled'         => true,
        'session_1_start' => '09:00',
        'session_1_end'   => '13:00',
        'session_2_start' => '14:00',
        'session_2_end'   => '17:30',
    ];

    $defaults = [
        'slot_length'   => 30,
        'slot_buffer'   => 10,
        'slot_capacity' => 1,
        'days'          => [],
    ];

    foreach ( $days as $key => $label ) {
        $defaults['days'][ $key ] = $default_day;
        if ( in_array( $key, [ 'saturday', 'sunday' ], true ) ) {
            $defaults['days'][ $key ]['enabled'] = false;
        }
    }

    return $defaults;
}

/**
 * Weekday labels helper.
 */
function hospa_toolkit_get_weekdays() {
    return [
        'monday'    => __( 'Monday', 'hospa-toolkit' ),
        'tuesday'   => __( 'Tuesday', 'hospa-toolkit' ),
        'wednesday' => __( 'Wednesday', 'hospa-toolkit' ),
        'thursday'  => __( 'Thursday', 'hospa-toolkit' ),
        'friday'    => __( 'Friday', 'hospa-toolkit' ),
        'saturday'  => __( 'Saturday', 'hospa-toolkit' ),
        'sunday'    => __( 'Sunday', 'hospa-toolkit' ),
    ];
}

/**
 * Ensure time fields are stored as HH:MM or empty string.
 */
function hospa_toolkit_sanitize_time_field( $value ) {
    $value = sanitize_text_field( $value );

    if ( '' === $value ) {
        return '';
    }

    if ( preg_match( '/^(2[0-3]|[01]?\d):([0-5]\d)$/', $value ) ) {
        return $value;
    }

    return '';
}

/**
 * Retrieve a doctor's availability configuration.
 */
function hospa_toolkit_get_doctor_availability( $doctor_id ) {
    $defaults = hospa_toolkit_default_doctor_availability();

    $availability = get_post_meta( $doctor_id, '_hospa_doctor_availability', true );
    if ( ! is_array( $availability ) ) {
        $availability = [];
    }

    $availability = wp_parse_args( $availability, $defaults );

    if ( empty( $availability['days'] ) || ! is_array( $availability['days'] ) ) {
        $availability['days'] = $defaults['days'];
    }

    return $availability;
}

/**
 * Count non-cancelled appointments overlapping a window.
 */
function hospa_toolkit_count_overlapping_appointments( $doctor_id, $start_gmt, $end_gmt, $exclude_statuses = [ 'cancelled' ] ) {
    global $wpdb;

    $table  = hospa_toolkit_get_appointments_table();
    $params = [ (int) $doctor_id, $end_gmt, $start_gmt ];

    $sql = "SELECT COUNT(*) FROM {$table} WHERE doctor_id = %d AND appointment_start < %s AND appointment_end > %s";

    if ( ! empty( $exclude_statuses ) ) {
        $placeholders = implode( ',', array_fill( 0, count( $exclude_statuses ), '%s' ) );
        $sql         .= " AND status NOT IN ({$placeholders})";
        $params       = array_merge( $params, array_map( 'sanitize_text_field', $exclude_statuses ) );
    }

    $prepared = $wpdb->prepare( $sql, $params );

    return (int) $wpdb->get_var( $prepared );
}

/**
 * Generate slot options for a doctor on a given date.
 */
function hospa_toolkit_generate_daily_slots( $doctor_id, $date ) {
    $availability = hospa_toolkit_get_doctor_availability( $doctor_id );

    try {
        $tz       = wp_timezone();
        $date_obj = new DateTimeImmutable( $date, $tz );
    } catch ( Exception $e ) {
        return [];
    }

    $day_key = strtolower( $date_obj->format( 'l' ) );

    if ( empty( $availability['days'][ $day_key ]['enabled'] ) ) {
        return [];
    }

    $day_settings = wp_parse_args(
        $availability['days'][ $day_key ],
        [
            'enabled'         => false,
            'session_1_start' => '',
            'session_1_end'   => '',
            'session_2_start' => '',
            'session_2_end'   => '',
        ]
    );

    $slot_length   = max( 5, (int) $availability['slot_length'] );
    $slot_buffer   = max( 0, (int) $availability['slot_buffer'] );
    $slot_capacity = max( 1, (int) $availability['slot_capacity'] );

    $sessions = [
        [ $day_settings['session_1_start'], $day_settings['session_1_end'] ],
        [ $day_settings['session_2_start'], $day_settings['session_2_end'] ],
    ];

    $slots  = [];
    $utc_tz = new DateTimeZone( 'UTC' );

    foreach ( $sessions as $session ) {
        list( $session_start, $session_end ) = $session;

        if ( '' === $session_start || '' === $session_end ) {
            continue;
        }

        list( $start_hour, $start_minute ) = array_map( 'intval', explode( ':', $session_start ) );
        list( $end_hour, $end_minute )     = array_map( 'intval', explode( ':', $session_end ) );

        try {
            $current_start = $date_obj->setTime( $start_hour, $start_minute );
            $session_limit = $date_obj->setTime( $end_hour, $end_minute );
        } catch ( Exception $e ) {
            continue;
        }

        if ( $current_start >= $session_limit ) {
            continue;
        }

        while ( $current_start < $session_limit ) {
            $slot_end = $current_start->add( new DateInterval( 'PT' . $slot_length . 'M' ) );

            if ( $slot_end > $session_limit ) {
                break;
            }

            $start_gmt = $current_start->setTimezone( $utc_tz )->format( 'Y-m-d H:i:s' );
            $end_gmt   = $slot_end->setTimezone( $utc_tz )->format( 'Y-m-d H:i:s' );

            $existing = hospa_toolkit_count_overlapping_appointments( $doctor_id, $start_gmt, $end_gmt );

            if ( $existing < $slot_capacity ) {
                $slots[] = [
                    'start'              => $current_start->format( 'Y-m-d H:i:s' ),
                    'end'                => $slot_end->format( 'Y-m-d H:i:s' ),
                    'start_gmt'          => $start_gmt,
                    'end_gmt'            => $end_gmt,
                    'label'              => sprintf( '%s – %s', $current_start->format( 'g:i A' ), $slot_end->format( 'g:i A' ) ),
                    'capacity_remaining' => max( 0, $slot_capacity - $existing ),
                ];
            }

            $next_start = $slot_end;

            if ( $slot_buffer > 0 ) {
                $next_start = $next_start->add( new DateInterval( 'PT' . $slot_buffer . 'M' ) );
            }

            if ( $next_start <= $current_start ) {
                break;
            }

            $current_start = $next_start;
        }
    }

    return $slots;
}

add_action( 'wp_ajax_hospa_toolkit_get_slots', 'hospa_toolkit_ajax_get_slots' );
add_action( 'wp_ajax_nopriv_hospa_toolkit_get_slots', 'hospa_toolkit_ajax_get_slots' );
add_action( 'wp_ajax_hospa_toolkit_book_appointment', 'hospa_toolkit_ajax_book_appointment' );
add_action( 'wp_ajax_nopriv_hospa_toolkit_book_appointment', 'hospa_toolkit_ajax_book_appointment' );
add_action( 'wp_ajax_hospa_toolkit_update_account', 'hospa_toolkit_ajax_update_account' );

/**
 * AJAX: Fetch available slots for a doctor/date.
 */
function hospa_toolkit_ajax_get_slots() {
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'hospa_toolkit_fetch_slots' ) ) {
        wp_send_json_error( [ 'message' => __( 'Security check failed.', 'hospa-toolkit' ) ] );
    }

    $doctor_id = isset( $_POST['doctor_id'] ) ? intval( $_POST['doctor_id'] ) : 0;
    $date      = isset( $_POST['date'] ) ? sanitize_text_field( wp_unslash( $_POST['date'] ) ) : '';

    if ( $doctor_id <= 0 || '' === $date ) {
        wp_send_json_error( [ 'message' => __( 'Doctor and date are required.', 'hospa-toolkit' ) ] );
    }

    $doctor_post = get_post( $doctor_id );
    if ( ! $doctor_post || 'doctors' !== $doctor_post->post_type || 'publish' !== $doctor_post->post_status ) {
        wp_send_json_error( [ 'message' => __( 'Invalid doctor selected.', 'hospa-toolkit' ) ] );
    }

    $slots = hospa_toolkit_generate_daily_slots( $doctor_id, $date );

    wp_send_json_success( [ 'slots' => $slots ] );
}

/**
 * AJAX: Update logged-in user profile basics.
 */
function hospa_toolkit_ajax_update_account() {
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( [ 'message' => __( 'You must be logged in to update your profile.', 'hospa-toolkit' ) ] );
    }

    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'hospa_toolkit_update_account' ) ) {
        wp_send_json_error( [ 'message' => __( 'Security check failed.', 'hospa-toolkit' ) ] );
    }

    $display_name = isset( $_POST['display_name'] ) ? sanitize_text_field( wp_unslash( $_POST['display_name'] ) ) : '';
    $phone        = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';

    if ( '' === $display_name ) {
        wp_send_json_error( [ 'message' => __( 'Display name cannot be empty.', 'hospa-toolkit' ) ] );
    }

    $user_id = get_current_user_id();

    $update_data = [
        'ID'           => $user_id,
        'display_name' => $display_name,
    ];

    $name_bits = preg_split( '/\s+/', $display_name );
    if ( ! empty( $name_bits ) ) {
        $update_data['first_name'] = array_shift( $name_bits );
        $update_data['last_name']  = implode( ' ', $name_bits );
    }

    $result = wp_update_user( $update_data );

    if ( is_wp_error( $result ) ) {
        wp_send_json_error( [ 'message' => $result->get_error_message() ] );
    }

    update_user_meta( $user_id, 'phone', $phone );

    do_action( 'hospa_toolkit_user_profile_updated', $user_id, $display_name, $phone );

    wp_send_json_success( [ 'message' => __( 'Profile updated successfully.', 'hospa-toolkit' ) ] );
}

/**
 * AJAX: Book an appointment.
 */
function hospa_toolkit_ajax_book_appointment() {
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'hospa_toolkit_book_appointment' ) ) {
        wp_send_json_error( [ 'message' => __( 'Security check failed.', 'hospa-toolkit' ) ] );
    }

    $doctor_id     = isset( $_POST['doctor_id'] ) ? intval( $_POST['doctor_id'] ) : 0;
    $date          = isset( $_POST['appointment_date'] ) ? sanitize_text_field( wp_unslash( $_POST['appointment_date'] ) ) : '';
    $slot_start    = isset( $_POST['slot_start'] ) ? sanitize_text_field( wp_unslash( $_POST['slot_start'] ) ) : '';
    $slot_end      = isset( $_POST['slot_end'] ) ? sanitize_text_field( wp_unslash( $_POST['slot_end'] ) ) : '';
    $visit_type    = isset( $_POST['visit_type'] ) ? sanitize_text_field( wp_unslash( $_POST['visit_type'] ) ) : '';
    $patient_name  = isset( $_POST['patient_name'] ) ? sanitize_text_field( wp_unslash( $_POST['patient_name'] ) ) : '';
    $patient_email = isset( $_POST['patient_email'] ) ? sanitize_email( wp_unslash( $_POST['patient_email'] ) ) : '';
    $patient_phone = isset( $_POST['patient_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['patient_phone'] ) ) : '';
    $notes         = isset( $_POST['notes'] ) ? wp_kses_post( wp_unslash( $_POST['notes'] ) ) : '';
    $consent       = ! empty( $_POST['consent'] );

    if ( $doctor_id <= 0 || '' === $date || '' === $slot_start || '' === $slot_end ) {
        wp_send_json_error( [ 'message' => __( 'Please complete all booking details.', 'hospa-toolkit' ) ] );
    }

    if ( '' === $patient_name || '' === $patient_email ) {
        wp_send_json_error( [ 'message' => __( 'Name and email are required.', 'hospa-toolkit' ) ] );
    }

    if ( ! is_email( $patient_email ) ) {
        wp_send_json_error( [ 'message' => __( 'Please provide a valid email address.', 'hospa-toolkit' ) ] );
    }

    if ( ! $consent ) {
        wp_send_json_error( [ 'message' => __( 'You must agree to the privacy policy.', 'hospa-toolkit' ) ] );
    }

    $doctor_post = get_post( $doctor_id );
    if ( ! $doctor_post || 'doctors' !== $doctor_post->post_type || 'publish' !== $doctor_post->post_status ) {
        wp_send_json_error( [ 'message' => __( 'Invalid doctor selected.', 'hospa-toolkit' ) ] );
    }

    $available_slots = hospa_toolkit_generate_daily_slots( $doctor_id, $date );
    $matched_slot    = null;

    foreach ( $available_slots as $slot ) {
        if ( $slot['start'] === $slot_start && $slot['end'] === $slot_end ) {
            $matched_slot = $slot;
            break;
        }
    }

    if ( ! $matched_slot ) {
        wp_send_json_error( [ 'message' => __( 'The selected time slot is no longer available. Please choose another slot.', 'hospa-toolkit' ) ] );
    }

    $existing = hospa_toolkit_count_overlapping_appointments( $doctor_id, $matched_slot['start_gmt'], $matched_slot['end_gmt'] );
    $capacity = max( 1, (int) hospa_toolkit_get_doctor_availability( $doctor_id )['slot_capacity'] );

    if ( $existing >= $capacity ) {
        wp_send_json_error( [ 'message' => __( 'That slot has just been taken. Please pick another one.', 'hospa-toolkit' ) ] );
    }

    $patient_id = get_current_user_id();

    $inserted = hospa_toolkit_insert_appointment(
        [
            'doctor_id'         => $doctor_id,
            'patient_id'        => $patient_id,
            'patient_name'      => $patient_name,
            'patient_email'     => $patient_email,
            'patient_phone'     => $patient_phone,
            'visit_type'        => $visit_type,
            'appointment_start' => $matched_slot['start_gmt'],
            'appointment_end'   => $matched_slot['end_gmt'],
            'status'            => apply_filters( 'hospa_toolkit_default_appointment_status', 'pending', $doctor_id ),
            'notes'             => $notes,
        ]
    );

    if ( ! $inserted ) {
        wp_send_json_error( [ 'message' => __( 'Unable to create appointment. Please try again.', 'hospa-toolkit' ) ] );
    }

    do_action( 'hospa_toolkit_appointment_created', $inserted );

    wp_send_json_success(
        [
            'appointment_id' => $inserted,
            'message'        => __( 'Appointment booked successfully!', 'hospa-toolkit' ),
        ]
    );
}

/**
 * Render the appointments list page.
 */
function hospa_toolkit_render_appointments_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'You do not have permission to access this page.', 'hospa-toolkit' ) );
    }

    $list_table = new Hospa_Toolkit_Appointments_Table();
    $list_table->prepare_items();

    $message = '';
    if ( isset( $_GET['message'] ) ) {
        switch ( sanitize_text_field( wp_unslash( $_GET['message'] ) ) ) {
            case 'updated':
                $message = __( 'Appointment status updated.', 'hospa-toolkit' );
                break;
            case 'bulk-updated':
                $message = __( 'Appointments updated.', 'hospa-toolkit' );
                break;
        }
    }

    echo '<div class="wrap">';
    echo '<h1 class="wp-heading-inline">' . esc_html__( 'Appointments', 'hospa-toolkit' ) . '</h1>';

    if ( $message ) {
        printf( '<div id="message" class="notice notice-success is-dismissible"><p>%s</p></div>', esc_html( $message ) );
    }

    echo '<form method="get">';
    echo '<input type="hidden" name="page" value="hospa-toolkit-appointments" />';
    $list_table->search_box( __( 'Search appointments', 'hospa-toolkit' ), 'hospa-toolkit-appointments' );
    $list_table->views();
    $list_table->display();
    echo '</form>';
    echo '</div>';
}

if ( ! class_exists( 'WP_List_Table' ) && is_admin() ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

if ( class_exists( 'WP_List_Table' ) ) {
    class Hospa_Toolkit_Appointments_Table extends WP_List_Table {

    protected $statuses = [
        'pending'   => 'pending',
        'confirmed' => 'confirmed',
        'completed' => 'completed',
        'cancelled' => 'cancelled',
    ];

    public function __construct() {
        parent::__construct(
            [
                'singular' => 'appointment',
                'plural'   => 'appointments',
                'ajax'     => false,
            ]
        );
    }

    public function get_columns() {
        return [
            'cb'                 => '<input type="checkbox" />',
            'id'                 => __( 'ID', 'hospa-toolkit' ),
            'appointment_start'  => __( 'Date & Time', 'hospa-toolkit' ),
            'doctor'             => __( 'Doctor', 'hospa-toolkit' ),
            'patient'            => __( 'Patient', 'hospa-toolkit' ),
            'visit_type'         => __( 'Visit Type', 'hospa-toolkit' ),
            'status'             => __( 'Status', 'hospa-toolkit' ),
            'created_at'         => __( 'Created', 'hospa-toolkit' ),
        ];
    }

    protected function get_sortable_columns() {
        return [
            'appointment_start' => [ 'appointment_start', true ],
            'created_at'        => [ 'created_at', false ],
            'status'            => [ 'status', false ],
        ];
    }

    protected function column_default( $item, $column_name ) {
        switch ( $column_name ) {
            case 'id':
            case 'visit_type':
                return esc_html( $item[ $column_name ] );
            case 'appointment_start':
            case 'created_at':
                return esc_html( get_date_from_gmt( $item[ $column_name ], 'M j, Y g:i a' ) );
            case 'doctor':
                return $this->format_doctor_column( $item );
            case 'patient':
                return $this->format_patient_column( $item );
            case 'status':
                return $this->format_status_column( $item );
        }

        return isset( $item[ $column_name ] ) ? esc_html( $item[ $column_name ] ) : '';
    }

    protected function column_cb( $item ) {
        return sprintf( '<input type="checkbox" name="appointment_ids[]" value="%d" />', (int) $item['id'] );
    }

    protected function format_doctor_column( $item ) {
        $doctor_name = get_the_title( (int) $item['doctor_id'] );
        if ( ! $doctor_name ) {
            $doctor_name = __( '—', 'hospa-toolkit' );
        }

        return sprintf( '#%d &mdash; %s', (int) $item['doctor_id'], esc_html( $doctor_name ) );
    }

    protected function format_patient_column( $item ) {
        if ( $item['patient_id'] ) {
            $user = get_user_by( 'id', (int) $item['patient_id'] );
            if ( $user ) {
                return esc_html( $user->display_name ) . '<br><small>' . esc_html( $user->user_email ) . '</small>';
            }
        }

        $bits = [];
        if ( ! empty( $item['patient_name'] ) ) {
            $bits[] = esc_html( $item['patient_name'] );
        }
        if ( ! empty( $item['patient_email'] ) ) {
            $bits[] = esc_html( $item['patient_email'] );
        }

        return implode( '<br>', $bits );
    }

    protected function format_status_column( $item ) {
        $current_status = sanitize_key( $item['status'] );
        if ( ! isset( $this->statuses[ $current_status ] ) ) {
            $current_status = 'pending';
        }

        $out   = '<strong>' . esc_html( ucfirst( $current_status ) ) . '</strong>';
        $nonce = wp_create_nonce( 'hospa_toolkit_update_status_' . $item['id'] );

        $out .= '<div class="row-actions">';
        foreach ( $this->statuses as $status => $label ) {
            if ( $status === $current_status ) {
                continue;
            }
            $url = add_query_arg(
                [
                    'page'          => 'hospa-toolkit-appointments',
                    'action'        => 'change-status',
                    'appointment'   => (int) $item['id'],
                    'new_status'    => $status,
                    '_wpnonce'      => $nonce,
                ],
                admin_url( 'admin.php' )
            );
            $out .= sprintf( '<span class="change-status"><a href="%1$s">%2$s</a></span> ', esc_url( $url ), esc_html( ucfirst( $status ) ) );
        }
        $out .= '</div>';

        return $out;
    }

    protected function get_bulk_actions() {
        return [
            'set-pending'   => __( 'Mark as Pending', 'hospa-toolkit' ),
            'set-confirmed' => __( 'Mark as Confirmed', 'hospa-toolkit' ),
            'set-completed' => __( 'Mark as Completed', 'hospa-toolkit' ),
            'set-cancelled' => __( 'Mark as Cancelled', 'hospa-toolkit' ),
        ];
    }

    public function handle_request() {
        $this->process_bulk_action();
    }

    protected function process_bulk_action() {
        if ( 'change-status' === $this->current_action() ) {
            $this->handle_single_status_change();
        }

        $bulk_action = $this->current_action();

        if ( ! $bulk_action || empty( $_REQUEST['appointment_ids'] ) ) {
            return;
        }

        $appointment_ids = array_map( 'intval', (array) $_REQUEST['appointment_ids'] );
        $target_status   = $this->map_bulk_action_to_status( $bulk_action );

        if ( ! $target_status ) {
            return;
        }

        foreach ( $appointment_ids as $appointment_id ) {
            hospa_toolkit_update_appointment( $appointment_id, [ 'status' => $target_status ] );
        }

        wp_safe_redirect( add_query_arg( 'message', 'bulk-updated', remove_query_arg( [ 'action', 'appointment_ids' ] ) ) );
        exit;
    }

    protected function handle_single_status_change() {
        $appointment_id = isset( $_GET['appointment'] ) ? intval( $_GET['appointment'] ) : 0;
        $new_status     = isset( $_GET['new_status'] ) ? sanitize_key( $_GET['new_status'] ) : '';
        $nonce          = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';

        if ( ! $appointment_id || ! isset( $this->statuses[ $new_status ] ) ) {
            return;
        }

        if ( ! wp_verify_nonce( $nonce, 'hospa_toolkit_update_status_' . $appointment_id ) ) {
            wp_die( esc_html__( 'Security check failed', 'hospa-toolkit' ) );
        }

        hospa_toolkit_update_appointment( $appointment_id, [ 'status' => $new_status ] );

        wp_safe_redirect( add_query_arg( 'message', 'updated', remove_query_arg( [ 'action', 'appointment', 'new_status', '_wpnonce' ] ) ) );
        exit;
    }

    protected function map_bulk_action_to_status( $action ) {
        $map = [
            'set-pending'   => 'pending',
            'set-confirmed' => 'confirmed',
            'set-completed' => 'completed',
            'set-cancelled' => 'cancelled',
        ];

        return isset( $map[ $action ] ) ? $map[ $action ] : null;
    }

    public function prepare_items() {
        $per_page     = $this->get_items_per_page( 'hospa_toolkit_appointments_per_page', 20 );
        $current_page = $this->get_pagenum();
        $offset       = ( $current_page - 1 ) * $per_page;

        $args = [
            'limit'  => $per_page,
            'offset' => $offset,
            'order'  => isset( $_REQUEST['order'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['order'] ) ) : 'ASC',
        ];

        if ( ! empty( $_REQUEST['s'] ) ) {
            $args['search'] = sanitize_text_field( wp_unslash( $_REQUEST['s'] ) );
        }

        if ( ! empty( $_REQUEST['status'] ) && isset( $this->statuses[ sanitize_key( $_REQUEST['status'] ) ] ) ) {
            $args['status'] = sanitize_key( $_REQUEST['status'] );
        }

        $items = hospa_toolkit_query_appointments_with_search( $args );

        $total_items = $items['total'];
        $this->items = $items['data'];

        $this->_column_headers = [ $this->get_columns(), [], $this->get_sortable_columns(), $this->get_primary_column_name() ];

        $this->set_pagination_args( [
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil( $total_items / $per_page ),
        ] );
    }

    protected function get_views() {
        $current = isset( $_REQUEST['status'] ) ? sanitize_key( $_REQUEST['status'] ) : '';
        $links   = [];

        $all_url = remove_query_arg( 'status' );
        $links['all'] = sprintf(
            '<a href="%1$s" class="%2$s">%3$s</a>',
            esc_url( $all_url ),
            $current ? '' : 'current',
            esc_html__( 'All', 'hospa-toolkit' )
        );

        foreach ( $this->statuses as $status => $label ) {
            $url   = add_query_arg( 'status', $status, $all_url );
            $class = ( $status === $current ) ? 'current' : '';
            $links[ $status ] = sprintf( '<a href="%1$s" class="%2$s">%3$s</a>', esc_url( $url ), $class, esc_html( ucfirst( $status ) ) );
        }

        return $links;
    }
    }
}

/**
 * Query helper with optional search support.
 */
function hospa_toolkit_query_appointments_with_search( array $args ) {
    global $wpdb;

    $table = hospa_toolkit_get_appointments_table();

    $defaults = [
        'limit'  => 20,
        'offset' => 0,
        'order'  => 'ASC',
        'status' => null,
        'search' => null,
    ];

    $args = wp_parse_args( $args, $defaults );

    $where  = [];
    $params = [];

    if ( $args['status'] ) {
        $where[]  = 'status = %s';
        $params[] = $args['status'];
    }

    if ( $args['search'] ) {
        $like = '%' . $wpdb->esc_like( $args['search'] ) . '%';
        $where[] = '(patient_name LIKE %s OR patient_email LIKE %s OR patient_phone LIKE %s OR visit_type LIKE %s)';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }

    $where_clause = '';
    if ( $where ) {
        $where_clause = 'WHERE ' . implode( ' AND ', $where );
    }

    $order = strtoupper( $args['order'] ) === 'DESC' ? 'DESC' : 'ASC';

    $limit  = absint( $args['limit'] );
    $offset = absint( $args['offset'] );

    $data_sql = $wpdb->prepare(
        "SELECT * FROM {$table} {$where_clause} ORDER BY appointment_start {$order} LIMIT %d OFFSET %d",
        array_merge( $params, [ $limit, $offset ] )
    );

    $count_sql = "SELECT COUNT(*) FROM {$table} {$where_clause}";
    if ( $params ) {
        $count_sql = $wpdb->prepare( $count_sql, $params );
    }

    $data  = $wpdb->get_results( $data_sql, ARRAY_A );
    $total = (int) $wpdb->get_var( $count_sql );

    return [
        'data'  => $data,
        'total' => $total,
    ];
}

/**
 * Insert a new appointment row.
 *
 * @param array $args Appointment details.
 *
 * @return int|false Inserted appointment ID on success, false on failure.
 */
function hospa_toolkit_insert_appointment( array $args ) {
    global $wpdb;

    $defaults = [
        'doctor_id'        => 0,
        'patient_id'       => 0,
        'patient_name'     => '',
        'patient_email'    => '',
        'patient_phone'    => '',
        'visit_type'       => '',
        'appointment_start'=> '',
        'appointment_end'  => '',
        'status'           => 'pending',
        'notes'            => '',
    ];

    $data = wp_parse_args( $args, $defaults );

    if ( empty( $data['doctor_id'] ) || empty( $data['appointment_start'] ) || empty( $data['appointment_end'] ) ) {
        return false;
    }

    $table = hospa_toolkit_get_appointments_table();
    $now   = current_time( 'mysql', true );

    try {
        $start_dt = new \DateTimeImmutable( $data['appointment_start'], new \DateTimeZone( 'UTC' ) );
        $end_dt   = new \DateTimeImmutable( $data['appointment_end'], new \DateTimeZone( 'UTC' ) );
    } catch ( \Exception $e ) {
        return false;
    }

    $start_timestamp = $start_dt->getTimestamp();
    $end_timestamp   = $end_dt->getTimestamp();

    if ( $end_timestamp <= $start_timestamp ) {
        return false;
    }

    $inserted = $wpdb->insert(
        $table,
        [
            'doctor_id'         => (int) $data['doctor_id'],
            'patient_id'        => (int) $data['patient_id'],
            'patient_name'      => sanitize_text_field( $data['patient_name'] ),
            'patient_email'     => sanitize_email( $data['patient_email'] ),
            'patient_phone'     => sanitize_text_field( $data['patient_phone'] ),
            'visit_type'        => sanitize_text_field( $data['visit_type'] ),
            'appointment_start' => gmdate( 'Y-m-d H:i:s', $start_timestamp ),
            'appointment_end'   => gmdate( 'Y-m-d H:i:s', $end_timestamp ),
            'status'            => sanitize_text_field( $data['status'] ),
            'notes'             => wp_kses_post( $data['notes'] ),
            'created_at'        => $now,
            'updated_at'        => $now,
        ],
        [ '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ]
    );

    if ( false === $inserted ) {
        return false;
    }

    return (int) $wpdb->insert_id;
}

/**
 * Update an existing appointment.
 *
 * @param int   $appointment_id Appointment ID.
 * @param array $args           Data to update.
 *
 * @return bool Whether the update succeeded.
 */
function hospa_toolkit_update_appointment( $appointment_id, array $args ) {
    global $wpdb;

    $appointment_id = (int) $appointment_id;

    if ( $appointment_id <= 0 ) {
        return false;
    }

    $allowed = [
        'doctor_id'         => '%d',
        'patient_id'        => '%d',
        'patient_name'      => '%s',
        'patient_email'     => '%s',
        'patient_phone'     => '%s',
        'visit_type'        => '%s',
        'appointment_start' => '%s',
        'appointment_end'   => '%s',
        'status'            => '%s',
        'notes'             => '%s',
    ];

    $data   = [];
    $format = [];

    foreach ( $allowed as $field => $field_format ) {
        if ( ! array_key_exists( $field, $args ) ) {
            continue;
        }

        $value = $args[ $field ];

        switch ( $field ) {
            case 'doctor_id':
            case 'patient_id':
                $value = (int) $value;
                break;
            case 'appointment_start':
            case 'appointment_end':
                $timestamp = strtotime( $value );
                if ( false === $timestamp ) {
                    continue 2;
                }
                $value = gmdate( 'Y-m-d H:i:s', $timestamp );
                break;
            case 'patient_email':
                $value = sanitize_email( $value );
                break;
            case 'notes':
                $value = wp_kses_post( $value );
                break;
            default:
                $value = sanitize_text_field( $value );
                break;
        }

        $data[ $field ]   = $value;
        $format[]         = $field_format;
    }

    if ( empty( $data ) ) {
        return false;
    }

    $data['updated_at'] = current_time( 'mysql', true );
    $format[]           = '%s';

    $updated = $wpdb->update(
        hospa_toolkit_get_appointments_table(),
        $data,
        [ 'id' => $appointment_id ],
        $format,
        [ '%d' ]
    );

    if ( false === $updated ) {
        return false;
    }

    return true;
}

/**
 * Fetch appointments based on arguments.
 *
 * @param array $args Query args.
 *
 * @return array List of appointments.
 */
function hospa_toolkit_get_appointments( array $args = [] ) {
    global $wpdb;

    $defaults = [
        'doctor_id'   => null,
        'patient_id'  => null,
        'status'      => null,
        'date_after'  => null,
        'date_before' => null,
        'limit'       => 50,
        'offset'      => 0,
        'order'       => 'ASC',
    ];

    $args = wp_parse_args( $args, $defaults );

    $where  = [];
    $params = [];

    if ( null !== $args['doctor_id'] ) {
        $where[]  = 'doctor_id = %d';
        $params[] = (int) $args['doctor_id'];
    }

    if ( null !== $args['patient_id'] ) {
        $where[]  = 'patient_id = %d';
        $params[] = (int) $args['patient_id'];
    }

    if ( null !== $args['status'] ) {
        $where[]  = 'status = %s';
        $params[] = sanitize_text_field( $args['status'] );
    }

    if ( null !== $args['date_after'] ) {
        $where[]  = 'appointment_start >= %s';
        $params[] = gmdate( 'Y-m-d H:i:s', strtotime( $args['date_after'] ) );
    }

    if ( null !== $args['date_before'] ) {
        $where[]  = 'appointment_start <= %s';
        $params[] = gmdate( 'Y-m-d H:i:s', strtotime( $args['date_before'] ) );
    }

    $where_clause = '';

    if ( ! empty( $where ) ) {
        $where_clause = 'WHERE ' . implode( ' AND ', $where );
    }

    $order = strtoupper( $args['order'] ) === 'DESC' ? 'DESC' : 'ASC';

    $limit  = absint( $args['limit'] );
    $offset = absint( $args['offset'] );

    $sql = $wpdb->prepare(
        "SELECT * FROM " . hospa_toolkit_get_appointments_table() . " {$where_clause} ORDER BY appointment_start {$order} LIMIT %d OFFSET %d",
        array_merge( $params, [ $limit, $offset ] )
    );

    return $wpdb->get_results( $sql, ARRAY_A );
}
