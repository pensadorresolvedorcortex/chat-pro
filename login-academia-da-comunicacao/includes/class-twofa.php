<?php
/**
 * Two factor authentication manager.
 *
 * @package ADC\Login\TwoFA
 */

namespace ADC\Login\TwoFA;

use ADC\Login\Email\Emails;
use function ADC\Login\get_option_value;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Manager
 */
class Manager {

    /**
     * Email handler.
     *
     * @var Emails
     */
    protected $emails;

    /**
     * Constructor.
     *
     * @param Emails $emails Email handler.
     */
    public function __construct( Emails $emails ) {
        $this->emails = $emails;
    }

    /**
     * Initialize hooks.
     */
    public function init() {
        add_action( 'wp_logout', array( $this, 'clear_current_user_flags' ) );
    }

    /**
     * Whether 2FA is enforced.
     *
     * @return bool
     */
    public function is_enforced() {
        return (bool) get_option_value( 'enable_2fa', 1 );
    }

    /**
     * Mark user as pending verification.
     *
     * @param int $user_id User ID.
     */
    public function mark_pending( $user_id ) {
        update_user_meta( $user_id, 'adc_twofa_pending', 1 );
    }

    /**
     * Determine if user must confirm 2FA.
     *
     * @param int $user_id User ID.
     *
     * @return bool
     */
    public function user_requires_approval( $user_id ) {
        return (bool) get_user_meta( $user_id, 'adc_twofa_pending', true );
    }

    /**
     * Send 2FA code via email.
     *
     * @param int $user_id User ID.
     */
    public function send_code( $user_id ) {
        $code    = $this->generate_code();
        $expires = time() + ( 10 * MINUTE_IN_SECONDS );

        $hash = wp_hash_password( $code );
        update_user_meta( $user_id, 'adc_twofa_code_hash', $hash );
        update_user_meta( $user_id, 'adc_twofa_expires', $expires );
        update_user_meta( $user_id, 'adc_twofa_last_sent', time() );

        $this->emails->send_twofa_code( $user_id, $code, $expires );
    }

    /**
     * Verify code for user.
     *
     * @param int    $user_id User ID.
     * @param string $code    Submitted code.
     *
     * @return bool
     */
    public function validate_code( $user_id, $code ) {
        $stored_hash = get_user_meta( $user_id, 'adc_twofa_code_hash', true );
        $expires     = (int) get_user_meta( $user_id, 'adc_twofa_expires', true );

        if ( empty( $stored_hash ) || empty( $expires ) ) {
            return false;
        }

        if ( time() > $expires ) {
            return false;
        }

        return wp_check_password( $code, $stored_hash );
    }

    /**
     * Clear pending verification and stored codes.
     *
     * @param int $user_id User ID.
     */
    public function clear_pending( $user_id ) {
        delete_user_meta( $user_id, 'adc_twofa_pending' );
        delete_user_meta( $user_id, 'adc_twofa_code_hash' );
        delete_user_meta( $user_id, 'adc_twofa_expires' );
        delete_user_meta( $user_id, 'adc_twofa_last_sent' );
    }

    /**
     * Determine if user can request a new code.
     *
     * @param int $user_id User ID.
     *
     * @return bool
     */
    public function can_request_new_code( $user_id ) {
        $last_sent = (int) get_user_meta( $user_id, 'adc_twofa_last_sent', true );

        if ( empty( $last_sent ) ) {
            return true;
        }

        return ( time() - $last_sent ) > MINUTE_IN_SECONDS;
    }

    /**
     * Clear flags on logout.
     */
    public function clear_current_user_flags() {
        $user_id = get_current_user_id();

        if ( $user_id ) {
            $this->clear_pending( $user_id );
        }
    }

    /**
     * Generate random numeric code.
     *
     * @return string
     */
    protected function generate_code() {
        return str_pad( wp_rand( 0, 999999 ), 6, '0', STR_PAD_LEFT );
    }
}
