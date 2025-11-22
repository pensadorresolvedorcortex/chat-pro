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
        'adobe-creative-cloud'   => 'adobe-creative-cloud.svg',
        'adobe-lightroom'        => 'adobe-lightroom.svg',
        'adobe-photoshop'        => 'adobe-photoshop.svg',
        'alura'                  => 'alura.svg',
        'apple-arcade'           => 'apple-arcade.svg',
        'apple-music'            => 'apple-music.svg',
        'apple-tv-plus'          => 'apple-tv-plus.svg',
        'avast-secureline'       => 'avast-secureline.svg',
        'bumble-boost'           => 'bumble-boost.svg',
        'calendly'               => 'calendly.svg',
        'calm'                   => 'calm.svg',
        'canva'                  => 'canva.svg',
        'capcut'                 => 'capcut.svg',
        'coursera'               => 'coursera.svg',
        'crunchyroll'            => 'crunchyroll.svg',
        'dashlane'               => 'dashlane.svg',
        'deezer'                 => 'deezer.svg',
        'discord-nitro'          => 'discord-nitro.svg',
        'disney-plus'            => 'disney-plus.svg',
        'dropbox'                => 'dropbox.svg',
        'duolingo'               => 'duolingo.svg',
        'ea-play'                => 'ea-play.svg',
        'epic-games-store'       => 'epic-games-store.svg',
        'evernote'               => 'evernote.svg',
        'expressvpn'             => 'expressvpn.svg',
        'figma'                  => 'figma.svg',
        'fitbit-premium'         => 'fitbit-premium.svg',
        'globoplay'              => 'globoplay.svg',
        'google-drive'           => 'google-drive.svg',
        'google-one'             => 'google-one.svg',
        'google-workspace'       => 'google-workspace.svg',
        'grammarly'              => 'grammarly.svg',
        'hbo-max'                => 'hbo-max.svg',
        'headspace'              => 'headspace.svg',
        'icloud-plus'            => 'icloud-plus.svg',
        'kindle-unlimited'       => 'kindle-unlimited.svg',
        'lastpass'               => 'lastpass.svg',
        'letterboxd'             => 'letterboxd.svg',
        'linkedin-premium'       => 'linkedin-premium.svg',
        'medium-membership'      => 'medium-membership.svg',
        'mega'                   => 'mega.svg',
        'microsoft-365'          => 'microsoft-365.svg',
        'mubi'                   => 'mubi.svg',
        'netflix'                => 'netflix.svg',
        'nintendo-switch-online' => 'nintendo-switch-online.svg',
        'nordvpn'                => 'nordvpn.svg',
        'notion'                 => 'notion.svg',
        'paramount-plus'         => 'paramount-plus.svg',
        'playplus'               => 'playplus.svg',
        'playstation-plus'       => 'playstation-plus.svg',
        'prime-video'            => 'prime-video.svg',
        'skillshare'             => 'skillshare.svg',
        'slack'                  => 'slack.svg',
        'spotify'                => 'spotify.svg',
        'star-plus'              => 'star-plus.svg',
        'steam'                  => 'steam.svg',
        'strava'                 => 'strava.svg',
        'surfshark-vpn'          => 'surfshark-vpn.svg',
        'telecine'               => 'telecine.svg',
        'tidal'                  => 'tidal.svg',
        'tinder-gold'            => 'tinder-gold.svg',
        'trello'                 => 'trello.svg',
        'udemy'                  => 'udemy.svg',
        'xbox-game-pass-ultimate'=> 'xbox-game-pass-ultimate.svg',
        'youtube-music'          => 'youtube-music.svg',
        'youtube-premium'        => 'youtube-premium.svg',
        'zoom-pro'               => 'zoom-pro.svg',
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
