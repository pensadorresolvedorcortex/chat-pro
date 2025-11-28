<?php
namespace Juntaplay\Hooks;

use Juntaplay\Security\Crypto;
use Juntaplay\Shortcodes\GroupRelationship;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Persiste os metadados obrigatórios do grupo.
 */
class SaveGroupMeta {
    /**
     * Registra o hook de salvamento.
     */
    public static function register() {
        add_action( 'save_post_juntaplay_group', array( __CLASS__, 'handle' ), 10, 3 );
    }

    /**
     * Manipula o salvamento dos metadados do grupo.
     *
     * @param int      $post_id ID do post.
     * @param \WP_Post $post    Objeto do post.
     * @param bool     $update  Se o post está sendo atualizado.
     */
    public static function handle( $post_id, $post, $update ) {
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( wp_is_post_revision( $post_id ) ) {
            return;
        }

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        $allowed_relationships = class_exists( '\\Juntaplay\\Shortcodes\\GroupRelationship' )
            ? GroupRelationship::get_relationship_options()
            : array();

        if ( isset( $_POST['jplay_relationship_admin'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
            $relationship = sanitize_text_field( wp_unslash( $_POST['jplay_relationship_admin'] ) );

            if ( '' === $relationship || ! in_array( $relationship, $allowed_relationships, true ) ) {
                wp_die( esc_html__( 'Selecione a relação com o administrador.', 'juntaplay' ) );
            }

            update_post_meta( $post_id, 'jplay_relationship_admin', $relationship );
        }

        if ( isset( $_POST['jplay_shared_url'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
            $shared_url = esc_url_raw( wp_unslash( $_POST['jplay_shared_url'] ) );

            if ( '' === $shared_url ) {
                wp_die( esc_html__( 'Informe a URL de acesso compartilhado.', 'juntaplay' ) );
            }

            update_post_meta( $post_id, 'jplay_shared_url', $shared_url );
        }

        if ( isset( $_POST['jplay_shared_user'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
            $shared_user = sanitize_text_field( wp_unslash( $_POST['jplay_shared_user'] ) );

            if ( '' === $shared_user ) {
                wp_die( esc_html__( 'Informe o usuário compartilhado.', 'juntaplay' ) );
            }

            update_post_meta( $post_id, 'jplay_shared_user', $shared_user );
        }

        if ( isset( $_POST['jplay_shared_pass'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
            $shared_pass = sanitize_text_field( wp_unslash( $_POST['jplay_shared_pass'] ) );

            if ( '' === $shared_pass ) {
                wp_die( esc_html__( 'Informe a senha compartilhada.', 'juntaplay' ) );
            }

            $encrypted = Crypto::encrypt( $shared_pass );

            if ( false === $encrypted ) {
                wp_die( esc_html__( 'Não foi possível proteger a senha compartilhada.', 'juntaplay' ) );
            }

            update_post_meta( $post_id, 'jplay_shared_pass_enc', $encrypted );
        }

        if ( isset( $_POST['jplay_shared_notes'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
            $shared_notes = sanitize_textarea_field( wp_unslash( $_POST['jplay_shared_notes'] ) );

            if ( '' !== $shared_notes ) {
                update_post_meta( $post_id, 'jplay_shared_notes', $shared_notes );
            } else {
                delete_post_meta( $post_id, 'jplay_shared_notes' );
            }
        }

        $allowed_support = array( 'whatsapp', 'email' );

        if ( isset( $_POST['jplay_support_channel'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
            $support_channel = sanitize_text_field( wp_unslash( $_POST['jplay_support_channel'] ) );

            if ( '' === $support_channel || ! in_array( $support_channel, $allowed_support, true ) ) {
                wp_die( esc_html__( 'Selecione o canal de suporte aos membros.', 'juntaplay' ) );
            }

            update_post_meta( $post_id, 'jplay_support_channel', $support_channel );
        }

        if ( isset( $_POST['jplay_support_contact'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
            $support_channel = isset( $support_channel ) ? $support_channel : ( isset( $_POST['jplay_support_channel'] ) ? sanitize_text_field( wp_unslash( $_POST['jplay_support_channel'] ) ) : '' ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
            $support_channel = in_array( $support_channel, $allowed_support, true ) ? $support_channel : '';

            $contact_value = sanitize_text_field( wp_unslash( $_POST['jplay_support_contact'] ) );

            if ( 'email' === $support_channel ) {
                $contact_value = sanitize_email( $contact_value );

                if ( empty( $contact_value ) || ! is_email( $contact_value ) ) {
                    wp_die( esc_html__( 'Informe um e-mail de suporte válido.', 'juntaplay' ) );
                }

                delete_post_meta( $post_id, 'jplay_support_country' );
            } elseif ( 'whatsapp' === $support_channel ) {
                $digits = preg_replace( '/\D+/', '', $contact_value );

                if ( strlen( $digits ) < 10 ) {
                    wp_die( esc_html__( 'Informe um número de WhatsApp válido com DDI e DDD.', 'juntaplay' ) );
                }

                $country = isset( $_POST['jplay_support_country'] ) ? sanitize_text_field( wp_unslash( $_POST['jplay_support_country'] ) ) : '+55'; // phpcs:ignore WordPress.Security.NonceVerification.Missing
                $country = preg_replace( '/[^+\d]/', '', $country );

                if ( '' === $country ) {
                    $country = '+55';
                }

                $contact_value = $country . $digits;
                update_post_meta( $post_id, 'jplay_support_country', $country );
            }

            update_post_meta( $post_id, 'jplay_support_contact', $contact_value );
        }
    }
}
