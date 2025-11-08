<?php
/**
 * Inline SVG illustrations used across the plugin.
 */

declare(strict_types=1);

namespace JuntaPlay\Assets;

use function wp_kses;

final class Illustrations
{
    /**
     * Return the illustration used when the complaint inbox is empty.
     */
    public static function complaintEmpty(): string
    {
        $svg = <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" width="320" height="220" viewBox="0 0 320 220" fill="none">
    <rect width="320" height="220" rx="28" fill="#F3F5FA" />
    <path d="M88 152c18 24 52 36 72 36s54-12 72-36" stroke="#00AFA1" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" />
    <path d="M160 72c-32 0-58 26-58 58s26 58 58 58 58-26 58-58-26-58-58-58Zm0 92a34 34 0 1 1 0-68 34 34 0 0 1 0 68Z" fill="#0D2840" fill-opacity="0.08" />
    <path d="M160 90a40 40 0 0 0-40 40c0 22 18 40 40 40s40-18 40-40a40 40 0 0 0-40-40Zm0 58a18 18 0 1 1 0-36 18 18 0 0 1 0 36Z" fill="#0D2840" fill-opacity="0.12" />
    <rect x="114" y="50" width="92" height="20" rx="10" fill="#E0ECF4" />
    <rect x="106" y="34" width="108" height="16" rx="8" fill="#CFE7F0" />
    <circle cx="112" cy="138" r="8" fill="#00CCC0" />
    <circle cx="208" cy="138" r="8" fill="#00CCC0" />
</svg>
SVG;

        return self::sanitizeSvg($svg);
    }

    /**
     * Return the illustration displayed on the checkout thank you screen.
     */
    public static function thankyouPlaceholder(): string
    {
        $svg = <<<'SVG'
<svg width="360" height="240" viewBox="0 0 360 240" fill="none" xmlns="http://www.w3.org/2000/svg">
    <rect width="360" height="240" rx="32" fill="url(#paint0_linear)" />
    <defs>
        <linearGradient id="paint0_linear" x1="28" y1="12" x2="332" y2="228" gradientUnits="userSpaceOnUse">
            <stop stop-color="#5B6CFF" />
            <stop offset="1" stop-color="#8E54E9" />
        </linearGradient>
    </defs>
    <g fill="white" opacity="0.92">
        <circle cx="90" cy="92" r="38" fill="rgba(255,255,255,0.15)" />
        <path d="M101 81L89 101L78 90" stroke="white" stroke-width="6" stroke-linecap="round" stroke-linejoin="round" />
    </g>
    <g transform="translate(150 62)">
        <rect x="0" y="0" width="162" height="108" rx="20" fill="rgba(255,255,255,0.18)" />
        <rect x="18" y="24" width="126" height="12" rx="6" fill="white" opacity="0.95" />
        <rect x="18" y="48" width="84" height="12" rx="6" fill="white" opacity="0.8" />
        <rect x="18" y="72" width="108" height="12" rx="6" fill="white" opacity="0.65" />
    </g>
</svg>
SVG;

        return self::sanitizeSvg($svg);
    }

    /**
     * Sanitize SVG markup so it can be safely inlined in templates.
     */
    private static function sanitizeSvg(string $svg): string
    {
        $allowed = [
            'svg' => [
                'xmlns' => true,
                'width' => true,
                'height' => true,
                'viewBox' => true,
                'fill' => true,
            ],
            'rect' => [
                'width' => true,
                'height' => true,
                'rx' => true,
                'x' => true,
                'y' => true,
                'fill' => true,
                'opacity' => true,
            ],
            'path' => [
                'd' => true,
                'fill' => true,
                'fill-opacity' => true,
                'stroke' => true,
                'stroke-width' => true,
                'stroke-linecap' => true,
                'stroke-linejoin' => true,
            ],
            'circle' => [
                'cx' => true,
                'cy' => true,
                'r' => true,
                'fill' => true,
            ],
            'defs' => [],
            'linearGradient' => [
                'id' => true,
                'x1' => true,
                'y1' => true,
                'x2' => true,
                'y2' => true,
                'gradientUnits' => true,
            ],
            'stop' => [
                'offset' => true,
                'stop-color' => true,
            ],
            'g' => [
                'fill' => true,
                'opacity' => true,
                'transform' => true,
            ],
        ];

        return trim((string) wp_kses($svg, $allowed));
    }
}
