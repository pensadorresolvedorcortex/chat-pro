<?php

declare(strict_types=1);

namespace JuntaPlay\Security;

use RuntimeException;
use function base64_decode;
use function base64_encode;
use function defined;
use function hash_hkdf;
use function hash_hmac;
use function is_string;
use function openssl_decrypt;
use function openssl_encrypt;
use function random_bytes;
use function strlen;
use const OPENSSL_RAW_DATA;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Utilitário simples para criptografar e descriptografar dados sensíveis.
 */
class Crypto
{
    private const CIPHER = 'aes-256-gcm';
    private const HKDF_INFO = 'juntaplay/encryption/v1';

    /**
     * Criptografa um texto simples usando AES-256-GCM.
     */
    public static function encrypt(string $plaintext): string
    {
        if ($plaintext === '') {
            return '';
        }

        $key = self::buildKey();
        $iv  = random_bytes(12); // 96 bits recomendado para GCM.

        $ciphertext = openssl_encrypt($plaintext, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv, $tag, '', 16);
        if ($ciphertext === false || !is_string($tag)) {
            throw new RuntimeException('Encryption failed.');
        }

        $payload = $iv . $tag . $ciphertext;

        return base64_encode($payload);
    }

    /**
     * Descriptografa um texto previamente criptografado.
     */
    public static function decrypt(string $ciphertext): string
    {
        if ($ciphertext === '') {
            return '';
        }

        $decoded = base64_decode($ciphertext, true);
        if ($decoded === false || strlen($decoded) < 29) {
            throw new RuntimeException('Invalid ciphertext.');
        }

        $iv  = substr($decoded, 0, 12);
        $tag = substr($decoded, 12, 16);
        $data = substr($decoded, 28);

        $key = self::buildKey();
        $plaintext = openssl_decrypt($data, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv, $tag, '');
        if ($plaintext === false) {
            throw new RuntimeException('Decryption failed.');
        }

        return $plaintext;
    }

    private static function buildKey(): string
    {
        $primary   = defined('AUTH_SALT') ? (string) AUTH_SALT : '';
        $secondary = defined('SECURE_AUTH_SALT') ? (string) SECURE_AUTH_SALT : '';
        $fallback  = defined('LOGGED_IN_SALT') ? (string) LOGGED_IN_SALT : '';

        $material = $primary !== '' ? $primary . '|' . $secondary : hash_hmac('sha256', $fallback, 'juntaplay');

        return hash_hkdf('sha256', $material, 32, self::HKDF_INFO, '');
    }
}
