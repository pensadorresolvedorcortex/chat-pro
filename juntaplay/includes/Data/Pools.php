<?php
declare(strict_types=1);

namespace JuntaPlay\Data;

use WC_Product_Simple;

use function absint;
use function apply_filters;
use function array_map;
use function get_post_status;
use function sanitize_key;
use function sanitize_text_field;
use function sanitize_title;
use function wp_parse_args;
use function wp_prepare_attachment_for_js;
use function __;

defined('ABSPATH') || exit;

class Pools
{
    /**
     * @return array<string, string>
     */
    public static function get_category_labels(): array
    {
        $categories = [
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

        return apply_filters('juntaplay/pools/categories', $categories);
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function create_or_update(array $data): int
    {
        global $wpdb;

        $table = "{$wpdb->prefix}jp_pools";
        $slug  = isset($data['slug']) ? sanitize_title((string) $data['slug']) : '';
        $pool_id = isset($data['id']) ? absint($data['id']) : 0;

        if ($pool_id > 0) {
            $existing = $pool_id;
        } else {
            $existing = $slug !== ''
                ? $wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE slug = %s", $slug))
                : null;
        }

        $category = isset($data['category']) ? sanitize_key((string) $data['category']) : '';
        $categories = self::get_category_labels();
        if ($category !== '' && !isset($categories[$category])) {
            $category = 'other';
        }

        $payload = [
            'title'        => sanitize_text_field($data['title'] ?? ''),
            'slug'         => $slug,
            'price'        => isset($data['price']) ? (float) $data['price'] : 0.0,
            'quota_start'  => isset($data['quota_start']) ? (int) $data['quota_start'] : 1,
            'quota_end'    => isset($data['quota_end']) ? (int) $data['quota_end'] : 1,
            'quotas_total' => max(0, (int) ($data['quota_end'] ?? 1) - (int) ($data['quota_start'] ?? 1) + 1),
            'category'     => $category,
            'excerpt'      => isset($data['excerpt']) ? sanitize_text_field((string) $data['excerpt']) : null,
            'thumbnail_id' => isset($data['thumbnail_id']) ? absint($data['thumbnail_id']) : null,
            'icon_id'      => isset($data['icon_id']) ? absint($data['icon_id']) : null,
            'cover_id'     => isset($data['cover_id']) ? absint($data['cover_id']) : null,
            'service_url'  => isset($data['service_url']) ? esc_url_raw((string) $data['service_url']) : null,
            'is_featured'  => !empty($data['is_featured']) ? 1 : 0,
            'status'       => isset($data['status']) ? sanitize_key((string) $data['status']) : 'publish',
        ];

        if (empty($payload['thumbnail_id'])) {
            $payload['thumbnail_id'] = null;
        }

        if (empty($payload['icon_id'])) {
            $payload['icon_id'] = null;
        }

        if (empty($payload['cover_id'])) {
            $payload['cover_id'] = null;
        }

        if ($existing) {
            $wpdb->update($table, $payload, ['id' => (int) $existing]);

            return (int) $existing;
        }

        $wpdb->insert($table, $payload);

        return (int) $wpdb->insert_id;
    }

    /**
     * @param array<string, mixed> $args
     * @return array<string, mixed>
     */
    public static function query(array $args = []): array
    {
        global $wpdb;

        $defaults = [
            'page'      => 1,
            'per_page'  => 12,
            'search'    => '',
            'category'  => '',
            'min_price' => null,
            'max_price' => null,
            'status'    => 'publish',
            'featured'  => null,
            'order'     => 'DESC',
            'orderby'   => 'created_at',
        ];

        $args = wp_parse_args($args, $defaults);

        $page     = max(1, (int) $args['page']);
        $per_page = max(1, min(120, (int) $args['per_page']));
        $offset   = ($page - 1) * $per_page;

        $table  = "{$wpdb->prefix}jp_pools";
        $where  = [];
        $params = [];

        if ($args['status']) {
            $where[]  = 'status = %s';
            $params[] = (string) $args['status'];
        }

        if ($args['category']) {
            $where[]  = 'category = %s';
            $params[] = sanitize_key((string) $args['category']);
        }

        if ($args['featured'] !== null) {
            $where[]  = 'is_featured = %d';
            $params[] = !empty($args['featured']) ? 1 : 0;
        }

        if ($args['search']) {
            $where[] = '(title LIKE %s OR slug LIKE %s)';
            $like    = '%' . $wpdb->esc_like((string) $args['search']) . '%';
            $params[] = $like;
            $params[] = $like;
        }

        if ($args['min_price'] !== null) {
            $where[]  = 'price >= %f';
            $params[] = (float) $args['min_price'];
        }

        if ($args['max_price'] !== null) {
            $where[]  = 'price <= %f';
            $params[] = (float) $args['max_price'];
        }

        $where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $orderby = in_array($args['orderby'], ['created_at', 'price', 'title', 'quotas_paid'], true)
            ? $args['orderby']
            : 'created_at';
        $order = strtoupper((string) $args['order']) === 'ASC' ? 'ASC' : 'DESC';

        $query = $wpdb->prepare(
            "SELECT SQL_CALC_FOUND_ROWS *
             FROM $table
             $where_sql
             ORDER BY $orderby $order
             LIMIT %d OFFSET %d",
            ...array_merge($params, [$per_page, $offset])
        );

        $rows  = $query ? $wpdb->get_results($query, ARRAY_A) : [];
        $total = (int) $wpdb->get_var('SELECT FOUND_ROWS()');
        $pages = (int) ceil($total / $per_page);

        $items = array_map([self::class, 'map_row'], $rows ?: []);

        return [
            'items'    => $items,
            'total'    => $total,
            'pages'    => $pages,
            'page'     => $page,
            'per_page' => $per_page,
        ];
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private static function map_row(array $row): array
    {
        $total  = isset($row['quotas_total']) ? max(0, (int) $row['quotas_total']) : 0;
        $paid   = isset($row['quotas_paid']) ? max(0, (int) $row['quotas_paid']) : 0;
        $remain = max(0, $total - $paid);
        $progress = $total > 0 ? min(100, (int) round(($paid / $total) * 100)) : 0;

        $thumbnail_id = isset($row['thumbnail_id']) ? (int) $row['thumbnail_id'] : 0;
        $thumbnail    = null;
        $thumbnail_meta = [];
        $icon_id      = isset($row['icon_id']) ? (int) $row['icon_id'] : 0;
        $icon         = null;
        $icon_meta    = [];
        $cover_id     = isset($row['cover_id']) ? (int) $row['cover_id'] : 0;
        $cover        = null;
        $cover_meta   = [];

        if ($thumbnail_id > 0) {
            $attachment = wp_prepare_attachment_for_js($thumbnail_id);
            if (is_array($attachment) && !empty($attachment['url'])) {
                $thumbnail = $attachment['url'];
                $thumbnail_meta = [
                    'width'  => isset($attachment['width']) ? (int) $attachment['width'] : 0,
                    'height' => isset($attachment['height']) ? (int) $attachment['height'] : 0,
                ];
            }
        }

        if ($icon_id > 0) {
            $icon_attachment = wp_prepare_attachment_for_js($icon_id);
            if (is_array($icon_attachment) && !empty($icon_attachment['url'])) {
                $icon = $icon_attachment['url'];
                $icon_meta = [
                    'width'  => isset($icon_attachment['width']) ? (int) $icon_attachment['width'] : 0,
                    'height' => isset($icon_attachment['height']) ? (int) $icon_attachment['height'] : 0,
                ];
            }
        }

        if ($cover_id > 0) {
            $cover_attachment = wp_prepare_attachment_for_js($cover_id);
            if (is_array($cover_attachment) && !empty($cover_attachment['url'])) {
                $cover = $cover_attachment['url'];
                $cover_meta = [
                    'width'  => isset($cover_attachment['width']) ? (int) $cover_attachment['width'] : 0,
                    'height' => isset($cover_attachment['height']) ? (int) $cover_attachment['height'] : 0,
                ];
            }
        }

        return [
            'id'           => isset($row['id']) ? (int) $row['id'] : 0,
            'title'        => (string) ($row['title'] ?? ''),
            'slug'         => (string) ($row['slug'] ?? ''),
            'price'        => isset($row['price']) ? (float) $row['price'] : 0.0,
            'product_id'   => isset($row['product_id']) ? (int) $row['product_id'] : 0,
            'quota_start'  => isset($row['quota_start']) ? (int) $row['quota_start'] : 1,
            'quota_end'    => isset($row['quota_end']) ? (int) $row['quota_end'] : 1,
            'quotas_total' => $total,
            'quotas_paid'  => $paid,
            'quotas_free'  => $remain,
            'progress'     => $progress,
            'category'     => (string) ($row['category'] ?? ''),
            'excerpt'      => (string) ($row['excerpt'] ?? ''),
            'service_url'  => (string) ($row['service_url'] ?? ''),
            'thumbnail_id' => $thumbnail_id,
            'thumbnail'    => $thumbnail,
            'thumbnail_meta' => $thumbnail_meta,
            'icon_id'      => $icon_id,
            'icon'         => $icon,
            'icon_meta'    => $icon_meta,
            'cover_id'     => $cover_id,
            'cover'        => $cover,
            'cover_meta'   => $cover_meta,
            'is_featured'  => !empty($row['is_featured']),
            'status'       => (string) ($row['status'] ?? 'draft'),
            'created_at'   => (string) ($row['created_at'] ?? ''),
            'updated_at'   => (string) ($row['updated_at'] ?? ''),
        ];
    }

    public static function ensure_product(int $pool_id): void
    {
        if (!class_exists('WC_Product_Simple')) {
            return;
        }

        $pool = self::get($pool_id);

        if (!$pool) {
            return;
        }

        if (!empty($pool->product_id) && get_post_status((int) $pool->product_id)) {
            return;
        }

        $product = new WC_Product_Simple();
        $product->set_name($pool->title);
        $product->set_status('publish');
        $product->set_catalog_visibility('hidden');
        $product->set_virtual(true);
        $product->set_sold_individually(false);
        $product->set_regular_price((string) $pool->price);
        $product->save();

        wp_set_object_terms($product->get_id(), 'juntaplay_pool_product', 'product_type');

        global $wpdb;

        $wpdb->update(
            "{$wpdb->prefix}jp_pools",
            ['product_id' => $product->get_id()],
            ['id' => $pool_id]
        );

        update_post_meta($product->get_id(), '_juntaplay_pool_id', $pool_id);
    }

    public static function get(int $pool_id)
    {
        global $wpdb;

        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}jp_pools WHERE id = %d", $pool_id), ARRAY_A);

        if (!$row) {
            return null;
        }

        return (object) self::map_row($row);
    }

    public static function delete(int $pool_id): bool
    {
        global $wpdb;

        $pool_id = absint($pool_id);

        if ($pool_id <= 0) {
            return false;
        }

        $deleted = $wpdb->delete("{$wpdb->prefix}jp_pools", ['id' => $pool_id], ['%d']);

        return (bool) $deleted;
    }
}
