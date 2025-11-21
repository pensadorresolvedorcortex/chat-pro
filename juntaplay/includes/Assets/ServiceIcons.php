<?php
/**
 * Service icon helper for curated pools.
 */

declare(strict_types=1);

namespace JuntaPlay\Assets;

use function trailingslashit;
use function plugins_url;
use function sanitize_key;

defined('ABSPATH') || exit;

final class ServiceIcons
{
    /**
     * Map pool slugs to icon filenames.
     *
     * @var array<string, string>
     */
    private const ICONS = [
        'youtube-premium' => 'youtube-premium.svg',
        'spotify'         => 'spotify.svg',
        'mubi'            => 'mubi.svg',
        'canva'           => 'canva.svg',
        'capcut'          => 'capcut.svg',
        'duolingo'        => 'duolingo.svg',
    ];

    /**
     * Return the full URL for a service icon when available.
     */
    public static function get(string $slug): string
    {
        $slug = sanitize_key($slug);

        if ($slug === '' || !isset(self::ICONS[$slug])) {
            return '';
        }

        $base = defined('JP_URL') ? trailingslashit(JP_URL) : plugins_url('/', dirname(__DIR__, 2));

        return $base . 'assets/img/services/' . self::ICONS[$slug];
    }

    /**
     * Return the fallback icon URL when no specific asset exists.
     */
    public static function fallback(): string
    {
        $base = defined('JP_URL') ? trailingslashit(JP_URL) : plugins_url('/', dirname(__DIR__, 2));

        return $base . 'assets/img/services/default.svg';
    }
}
