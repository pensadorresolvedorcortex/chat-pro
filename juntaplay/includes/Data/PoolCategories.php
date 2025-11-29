<?php
declare(strict_types=1);

namespace JuntaPlay\Data;

use function __;
use function array_filter;
use function get_option;
use function sanitize_key;
use function sanitize_title;
use function sanitize_text_field;
use function update_option;

defined('ABSPATH') || exit;

class PoolCategories
{
    private const OPTION_KEY = 'juntaplay_pool_categories';

    /**
     * @return array<string, string>
     */
    public static function defaults(): array
    {
        return [
            'boloes'      => __('Bolões e rifas', 'juntaplay'),
            'video'       => __('Vídeo e streaming', 'juntaplay'),
            'music'       => __('Música e áudio', 'juntaplay'),
            'education'   => __('Cursos e educação', 'juntaplay'),
            'reading'     => __('Leitura e revistas', 'juntaplay'),
            'office'      => __('Escritório e produtividade', 'juntaplay'),
            'software'    => __('Software e ferramentas', 'juntaplay'),
            'games'       => __('Jogos e esportes', 'juntaplay'),
            'ai'          => __('Ferramentas de IA', 'juntaplay'),
            'security'    => __('Segurança e VPN', 'juntaplay'),
            'marketplace' => __('Mercado e delivery', 'juntaplay'),
            'lifestyle'   => __('Lifestyle e clubes', 'juntaplay'),
            'other'       => __('Outros serviços', 'juntaplay'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function all(): array
    {
        $stored = get_option(self::OPTION_KEY, []);
        $sanitized = self::sanitize_map(is_array($stored) ? $stored : []);

        if (empty($sanitized)) {
            $sanitized = self::defaults();
            self::persist($sanitized);
        }

        if (!isset($sanitized['other'])) {
            $sanitized['other'] = __('Outros serviços', 'juntaplay');
        }

        return $sanitized;
    }

    /**
     * @param array<string, string> $categories
     */
    public static function persist(array $categories): void
    {
        update_option(self::OPTION_KEY, self::sanitize_map($categories));
    }

    public static function upsert(string $slug, string $label): void
    {
        $normalized = sanitize_key($slug);
        $name = sanitize_text_field($label);

        if ($normalized === '') {
            $normalized = sanitize_title($name);
        }

        if ($normalized === '') {
            return;
        }

        $categories = self::all();
        $categories[$normalized] = $name;

        self::persist($categories);
    }

    public static function delete(string $slug): void
    {
        $normalized = sanitize_key($slug);
        if ($normalized === '' || $normalized === 'other') {
            return;
        }

        $categories = self::all();
        unset($categories[$normalized]);

        if (empty($categories)) {
            $categories = self::defaults();
        }

        self::persist($categories);
    }

    /**
     * @param array<string, string> $categories
     * @return array<string, string>
     */
    private static function sanitize_map(array $categories): array
    {
        $filtered = [];
        foreach ($categories as $key => $value) {
            $slug = sanitize_key((string) $key);
            $label = sanitize_text_field((string) $value);

            if ($slug === '' || $label === '') {
                continue;
            }

            $filtered[$slug] = $label;
        }

        return array_filter($filtered);
    }
}
