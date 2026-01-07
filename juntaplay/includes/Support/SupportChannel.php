<?php

declare(strict_types=1);

namespace JuntaPlay\Support;

use function __;
use function apply_filters;
use function array_key_exists;
use function is_email;
use function preg_match;
use function preg_replace;
use function sanitize_email;
use function strtolower;
use function trim;

defined('ABSPATH') || exit;

/**
 * Helper methods to normalize and detect support channel data.
 */
class SupportChannel
{
    /**
     * @return array<string, array<string, string>>
     */
    public static function get_country_options(): array
    {
        $countries = [
            'br' => [
                'label' => __('Brasil', 'juntaplay'),
                'code'  => '+55',
                'mask'  => '(00) 00000-0000',
                'flag'  => '🇧🇷',
            ],
            'pt' => [
                'label' => __('Portugal', 'juntaplay'),
                'code'  => '+351',
                'mask'  => '000 000 000',
                'flag'  => '🇵🇹',
            ],
            'es' => [
                'label' => __('Espanha', 'juntaplay'),
                'code'  => '+34',
                'mask'  => '000 000 000',
                'flag'  => '🇪🇸',
            ],
            'us' => [
                'label' => __('Estados Unidos', 'juntaplay'),
                'code'  => '+1',
                'mask'  => '(000) 000-0000',
                'flag'  => '🇺🇸',
            ],
            'gb' => [
                'label' => __('Reino Unido', 'juntaplay'),
                'code'  => '+44',
                'mask'  => '0000 000000',
                'flag'  => '🇬🇧',
            ],
        ];

        /** @var array<string, array<string, string>> $filtered */
        $filtered = apply_filters('juntaplay/support/whatsapp_countries', $countries);

        return $filtered;
    }

    /**
     * @return array{type:string,country:string,value:string,channel:string,display:string,input:string,errors:array<int,string>}
     */
    public static function normalize(string $type, string $value, string $country = '', string $fallback = ''): array
    {
        $type    = strtolower(trim($type));
        $country = strtolower(trim($country));
        $value   = trim($value);

        $result = [
            'type'    => $type,
            'country' => $country,
            'value'   => '',
            'channel' => '',
            'display' => '',
            'input'   => $value,
            'errors'  => [],
        ];

        if ($type === '') {
            if ($fallback !== '') {
                $detected = self::detect($fallback);
                if ($detected['type'] !== '') {
                    return self::normalize($detected['type'], $detected['input'], $detected['country']);
                }
            }

            $result['errors'][] = __('Selecione o canal de suporte para os membros.', 'juntaplay');

            return $result;
        }

        if ($type === 'email') {
            $email = sanitize_email($value);

            if ($email === '' || !is_email($email)) {
                $result['errors'][] = __('Informe um e-mail válido para suporte.', 'juntaplay');

                return $result;
            }

            $result['value']   = $email;
            $result['channel'] = sprintf('E-mail %s', $email);
            $result['display'] = $result['channel'];
            $result['input']   = $email;

            return $result;
        }

        if ($type !== 'whatsapp') {
            $result['errors'][] = __('Selecione um canal de suporte válido.', 'juntaplay');

            return $result;
        }

        $countries = self::get_country_options();
        if ($country === '' || !array_key_exists($country, $countries)) {
            $country = 'br';
        }

        $country_meta = $countries[$country];
        $code_digits  = preg_replace('/\D+/', '', (string) ($country_meta['code'] ?? ''));
        $number_digits = preg_replace('/\D+/', '', $value);

        if ($number_digits === '') {
            $result['errors'][] = __('Informe um número de WhatsApp válido.', 'juntaplay');

            return $result;
        }

        if ($code_digits !== '') {
            if (strpos($number_digits, $code_digits) === 0) {
                $national_digits = substr($number_digits, strlen($code_digits));
            } else {
                $national_digits = ltrim($number_digits, '0');
                $number_digits   = $code_digits . $national_digits;
            }
        } else {
            $national_digits = $number_digits;
        }

        $e164 = '+' . ltrim($number_digits, '+');

        $mask       = isset($country_meta['mask']) ? (string) $country_meta['mask'] : '';
        $formatted  = self::format_with_mask($national_digits, $mask);
        $code_label = (string) ($country_meta['code'] ?? '+');

        $display_number = trim($code_label . ' ' . $formatted);
        if ($display_number === '') {
            $display_number = $e164;
        }

        $result['type']    = 'whatsapp';
        $result['country'] = $country;
        $result['value']   = $e164;
        $result['channel'] = sprintf('WhatsApp %s', $display_number);
        $result['display'] = sprintf('%s %s', (string) ($country_meta['flag'] ?? ''), $display_number);
        $result['display'] = trim($result['display']);
        $result['input']   = $formatted !== '' ? $formatted : $national_digits;

        return $result;
    }

    /**
     * Attempt to detect support data from a stored string.
     *
     * @return array{type:string,country:string,value:string,channel:string,display:string,input:string}
     */
    public static function detect(string $channel): array
    {
        $channel = trim($channel);
        $lower   = strtolower($channel);

        $result = [
            'type'    => '',
            'country' => 'br',
            'value'   => '',
            'channel' => $channel,
            'display' => $channel,
            'input'   => '',
        ];

        if ($channel === '') {
            return $result;
        }

        if (strpos($lower, 'email') !== false || strpos($channel, '@') !== false) {
            if (preg_match('/([A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,})/i', $channel, $matches)) {
                $email = sanitize_email($matches[1]);
                if ($email !== '') {
                    $result['type']  = 'email';
                    $result['value'] = $email;
                    $result['input'] = $email;

                    return $result;
                }
            }
        }

        if (strpos($lower, 'whats') !== false || preg_match('/\+?\d[\d\s().-]{6,}/', $channel)) {
            if (preg_match('/\+?\d[\d\s().-]{6,}/', $channel, $matches)) {
                $digits = preg_replace('/\D+/', '', $matches[0]);
                if ($digits !== '') {
                    $countries   = self::get_country_options();
                    $country     = 'br';
                    $national    = $digits;
                    foreach ($countries as $key => $meta) {
                        $code_digits = preg_replace('/\D+/', '', (string) ($meta['code'] ?? ''));
                        if ($code_digits !== '' && strpos($digits, $code_digits) === 0) {
                            $country  = $key;
                            $national = substr($digits, strlen($code_digits));
                            break;
                        }
                    }

                    $normalized = self::normalize('whatsapp', $national, $country);
                    $result['type']    = $normalized['type'];
                    $result['country'] = $normalized['country'];
                    $result['value']   = $normalized['value'];
                    $result['channel'] = $normalized['channel'];
                    $result['display'] = $normalized['display'];
                    $result['input']   = $normalized['input'];

                    return $result;
                }
            }
        }

        return $result;
    }

    private static function format_with_mask(string $digits, string $mask): string
    {
        $digits = trim($digits);
        if ($digits === '') {
            return '';
        }

        if ($mask === '') {
            return self::chunk_digits($digits);
        }

        $formatted   = '';
        $digits_index = 0;
        $mask_length  = strlen($mask);

        for ($i = 0; $i < $mask_length; $i++) {
            $char = $mask[$i];
            if ($char === '0') {
                if (isset($digits[$digits_index])) {
                    $formatted .= $digits[$digits_index];
                    $digits_index++;
                } else {
                    break;
                }

                continue;
            }

            if ($digits_index === 0) {
                continue;
            }

            $formatted .= $char;
        }

        if ($digits_index < strlen($digits)) {
            if ($formatted !== '' && substr($formatted, -1) !== ' ') {
                $formatted .= ' ';
            }

            $formatted .= substr($digits, $digits_index);
        }

        return trim($formatted);
    }

    private static function chunk_digits(string $digits): string
    {
        $length   = strlen($digits);
        $segments = [];
        for ($i = 0; $i < $length; $i += 3) {
            $segments[] = substr($digits, $i, 3);
        }

        return implode(' ', $segments);
    }
}

