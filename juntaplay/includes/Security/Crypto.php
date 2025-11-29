<?php
namespace Juntaplay\Security;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Utilitário de criptografia simétrica para campos sensíveis.
 */
class Crypto {
    const CIPHER = 'aes-256-gcm';

    /**
     * Criptografa um texto simples utilizando AES-256-GCM.
     *
     * @param string $plaintext Texto em claro.
     * @return string|false Texto criptografado em base64 ou false em caso de falha.
     */
    public static function encrypt( $plaintext ) {
        $plaintext = (string) $plaintext;

        if ( '' === $plaintext ) {
            return '';
        }

        $key = self::get_key();
        $iv_length = openssl_cipher_iv_length( self::CIPHER );

        if ( false === $iv_length ) {
            return false;
        }

        $iv = random_bytes( $iv_length );
        $tag = '';

        $cipher_raw = openssl_encrypt( $plaintext, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv, $tag );

        if ( false === $cipher_raw || '' === $tag ) {
            return false;
        }

        $payload = wp_json_encode(
            array(
                'alg'    => self::CIPHER,
                'iv'     => base64_encode( $iv ),
                'tag'    => base64_encode( $tag ),
                'cipher' => base64_encode( $cipher_raw ),
            )
        );

        if ( false === $payload ) {
            return false;
        }

        return base64_encode( $payload );
    }

    /**
     * Descriptografa um texto protegido.
     *
     * @param string $ciphertext Texto criptografado em base64.
     * @return string|false Texto em claro ou false em caso de falha.
     */
    public static function decrypt( $ciphertext ) {
        if ( '' === $ciphertext ) {
            return '';
        }

        $decoded = base64_decode( (string) $ciphertext, true );

        if ( false === $decoded ) {
            return false;
        }

        $payload = json_decode( $decoded, true );

        if ( ! is_array( $payload ) || empty( $payload['cipher'] ) || empty( $payload['iv'] ) || empty( $payload['tag'] ) ) {
            return false;
        }

        $cipher_raw = base64_decode( $payload['cipher'], true );
        $iv         = base64_decode( $payload['iv'], true );
        $tag        = base64_decode( $payload['tag'], true );

        if ( false === $cipher_raw || false === $iv || false === $tag ) {
            return false;
        }

        return openssl_decrypt( $cipher_raw, self::CIPHER, self::get_key(), OPENSSL_RAW_DATA, $iv, $tag );
    }

    /**
     * Obtém a chave simétrica a partir dos salts do WordPress.
     *
     * @return string Chave binária de 256 bits.
     */
    protected static function get_key() {
        $salts = AUTH_SALT . SECURE_AUTH_SALT;

        return hash( 'sha256', $salts, true );
    }
}
