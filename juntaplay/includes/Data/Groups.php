<?php
declare(strict_types=1);

namespace JuntaPlay\Data;

use wpdb;

use function absint;
use function array_merge;
use function apply_filters;
use function do_action;
use function current_time;
use function esc_url_raw;
use function get_post_meta;
use function is_array;
use function max;
use function min;
use function sanitize_key;
use function sanitize_textarea_field;
use function sanitize_text_field;
use function sanitize_title;
use function trailingslashit;
use function wp_generate_uuid4;
use function wp_get_attachment_image_url;
use function wp_hash_password;
use function wp_prepare_attachment_for_js;
use function wp_rand;
use function __;

defined('ABSPATH') || exit;

class Groups
{
    public const STATUS_PENDING  = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_ARCHIVED = 'archived';

    /**
     * @return array<string, string>
     */
    public static function get_category_labels(): array
    {
        $categories = [
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

        return apply_filters('juntaplay/groups/categories', $categories);
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function create(array $data): int
    {
        global $wpdb;

        $table = "{$wpdb->prefix}jp_groups";

        $title = isset($data['title']) ? sanitize_text_field((string) $data['title']) : '';
        if ($title === '') {
            return 0;
        }

        $owner_id    = isset($data['owner_id']) ? absint($data['owner_id']) : 0;
        $pool_id     = isset($data['pool_id']) ? absint($data['pool_id']) : 0;
        $description = isset($data['description']) ? sanitize_textarea_field((string) $data['description']) : '';
        $service     = isset($data['service_name']) ? sanitize_text_field((string) $data['service_name']) : '';
        $service_url = isset($data['service_url']) ? esc_url_raw((string) $data['service_url']) : '';
        $rules       = isset($data['rules']) ? sanitize_textarea_field((string) $data['rules']) : '';
        $price       = isset($data['price_regular']) ? (float) $data['price_regular'] : 0.0;
        $promo       = isset($data['price_promotional']) && $data['price_promotional'] !== ''
            ? (float) $data['price_promotional']
            : null;
        $member_price = isset($data['member_price']) && $data['member_price'] !== ''
            ? (float) $data['member_price']
            : null;
        $slots_total    = isset($data['slots_total']) ? absint($data['slots_total']) : 0;
        $slots_reserved = isset($data['slots_reserved']) ? absint($data['slots_reserved']) : 0;
        $support        = isset($data['support_channel']) ? sanitize_text_field((string) $data['support_channel']) : '';
        $delivery       = isset($data['delivery_time']) ? sanitize_text_field((string) $data['delivery_time']) : '';
        $access         = isset($data['access_method']) ? sanitize_text_field((string) $data['access_method']) : '';
        $category       = isset($data['category']) ? sanitize_text_field((string) $data['category']) : '';
        $instant_access = !empty($data['instant_access']);
        $cover_id       = isset($data['cover_id']) ? absint($data['cover_id']) : 0;
        $access_url     = isset($data['access_url']) ? esc_url_raw((string) $data['access_url']) : '';
        $access_login   = isset($data['access_login']) ? sanitize_text_field((string) $data['access_login']) : '';
        $access_password = isset($data['access_password']) ? sanitize_text_field((string) $data['access_password']) : '';
        $access_notes   = isset($data['access_notes']) ? sanitize_textarea_field((string) $data['access_notes']) : '';
        $access_observations = isset($data['access_observations']) ? sanitize_textarea_field((string) $data['access_observations']) : '';
        $visibility     = 'public';
        $slug_input = isset($data['slug']) ? sanitize_title((string) $data['slug']) : '';
        $slug       = $slug_input !== ''
            ? self::ensure_unique_slug($slug_input)
            : self::generate_unique_slug($title);

        $payload = [
            'owner_id'          => $owner_id,
            'pool_id'           => $pool_id > 0 ? $pool_id : null,
            'title'             => $title,
            'service_name'      => $service,
            'service_url'       => $service_url !== '' ? $service_url : null,
            'rules'             => $rules !== '' ? $rules : null,
            'description'       => $description,
            'price_regular'     => $price,
            'price_promotional' => $promo,
            'member_price'      => $member_price,
            'slots_total'       => $slots_total,
            'slots_reserved'    => $slots_reserved,
            'support_channel'   => $support,
            'delivery_time'     => $delivery,
            'access_method'     => $access,
            'access_url'        => $access_url !== '' ? $access_url : null,
            'access_login'      => $access_login !== '' ? $access_login : null,
            'access_password'   => $access_password !== '' ? $access_password : null,
            'access_notes'      => $access_notes !== '' ? $access_notes : null,
            'access_observations' => $access_observations !== '' ? $access_observations : null,
            'category'          => $category,
            'instant_access'    => $instant_access ? 1 : 0,
            'cover_id'          => $cover_id > 0 ? $cover_id : null,
            'email_validation_hash'    => null,
            'email_validation_sent_at' => null,
            'email_validated_at'       => null,
            'status'            => self::STATUS_PENDING,
            'visibility'        => $visibility,
            'slug'              => $slug,
            'created_at'        => current_time('mysql'),
            'updated_at'        => current_time('mysql'),
        ];

        $formats = ['%d', '%d', '%s', '%s', '%s', '%s', '%s', '%f', '%f', '%f', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s'];

        $inserted = $wpdb->insert($table, $payload, $formats);

        return $inserted ? (int) $wpdb->insert_id : 0;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function update_basic(int $group_id, array $data): bool
    {
        global $wpdb;

        $group_id = absint($group_id);

        if ($group_id <= 0) {
            return false;
        }

        $table = "{$wpdb->prefix}jp_groups";

        $title = isset($data['title']) ? sanitize_text_field((string) $data['title']) : '';
        if ($title === '') {
            return false;
        }

        $description     = isset($data['description']) ? sanitize_textarea_field((string) $data['description']) : '';
        $rules           = isset($data['rules']) ? sanitize_textarea_field((string) $data['rules']) : '';
        $service_name    = isset($data['service_name']) ? sanitize_text_field((string) $data['service_name']) : '';
        $service_url     = isset($data['service_url']) ? esc_url_raw((string) $data['service_url']) : '';
        $price_regular   = isset($data['price_regular']) ? (float) $data['price_regular'] : 0.0;
        $price_promo     = isset($data['price_promotional']) ? (float) $data['price_promotional'] : 0.0;
        $member_price    = isset($data['member_price']) ? (float) $data['member_price'] : 0.0;
        $slots_total     = isset($data['slots_total']) ? absint($data['slots_total']) : 0;
        $slots_reserved  = isset($data['slots_reserved']) ? absint($data['slots_reserved']) : 0;
        $support_channel = isset($data['support_channel']) ? sanitize_text_field((string) $data['support_channel']) : '';
        $delivery_time   = isset($data['delivery_time']) ? sanitize_text_field((string) $data['delivery_time']) : '';
        $access_method   = isset($data['access_method']) ? sanitize_text_field((string) $data['access_method']) : '';
        $category        = isset($data['category']) ? sanitize_key((string) $data['category']) : '';
        $instant_access  = !empty($data['instant_access']);
        $cover_id        = isset($data['cover_id']) ? absint($data['cover_id']) : 0;
        $pool_id         = isset($data['pool_id']) ? absint($data['pool_id']) : 0;
        $access_url      = isset($data['access_url']) ? esc_url_raw((string) $data['access_url']) : '';
        $access_login    = isset($data['access_login']) ? sanitize_text_field((string) $data['access_login']) : '';
        $access_password = isset($data['access_password']) ? sanitize_text_field((string) $data['access_password']) : '';
        $access_notes    = isset($data['access_notes']) ? sanitize_textarea_field((string) $data['access_notes']) : '';
        $access_observations = isset($data['access_observations']) ? sanitize_textarea_field((string) $data['access_observations']) : '';

        $payload = [
            'title'             => $title,
            'description'       => $description,
            'rules'             => $rules,
            'service_name'      => $service_name,
            'service_url'       => $service_url,
            'price_regular'     => $price_regular,
            'price_promotional' => $price_promo > 0 ? $price_promo : 0.0,
            'member_price'      => $member_price > 0 ? $member_price : 0.0,
            'slots_total'       => $slots_total,
            'slots_reserved'    => $slots_reserved,
            'support_channel'   => $support_channel,
            'delivery_time'     => $delivery_time,
            'access_method'     => $access_method,
            'access_url'        => $access_url !== '' ? $access_url : null,
            'access_login'      => $access_login !== '' ? $access_login : null,
            'access_password'   => $access_password !== '' ? $access_password : null,
            'access_notes'      => $access_notes !== '' ? $access_notes : null,
            'access_observations' => $access_observations !== '' ? $access_observations : null,
            'category'          => $category,
            'instant_access'    => $instant_access ? 1 : 0,
            'cover_id'          => $cover_id > 0 ? $cover_id : 0,
            'pool_id'           => $pool_id,
            'updated_at'        => current_time('mysql'),
        ];

        $formats = [
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%f',
            '%f',
            '%f',
            '%d',
            '%d',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%d',
            '%d',
            '%d',
            '%s',
        ];

        $updated = $wpdb->update($table, $payload, ['id' => $group_id], $formats, ['%d']);

        return $updated !== false;
    }

    public static function delete(int $group_id): bool
    {
        global $wpdb;

        $group_id = absint($group_id);

        if ($group_id <= 0) {
            return false;
        }

        $groups_table     = "{$wpdb->prefix}jp_groups";
        $members_table    = "{$wpdb->prefix}jp_group_members";
        $events_table     = "{$wpdb->prefix}jp_group_membership_events";
        $complaints_table = "{$wpdb->prefix}jp_group_complaints";
        $messages_table   = "{$wpdb->prefix}jp_group_complaint_messages";

        $wpdb->delete($members_table, ['group_id' => $group_id], ['%d']);
        $wpdb->delete($events_table, ['group_id' => $group_id], ['%d']);

        $complaint_ids = $wpdb->get_col($wpdb->prepare("SELECT id FROM $complaints_table WHERE group_id = %d", $group_id));
        if ($complaint_ids) {
            foreach ($complaint_ids as $complaint_id) {
                $wpdb->delete($messages_table, ['complaint_id' => (int) $complaint_id], ['%d']);
            }
        }

        $wpdb->delete($complaints_table, ['group_id' => $group_id], ['%d']);

        $deleted = $wpdb->delete($groups_table, ['id' => $group_id], ['%d']);

        if ($deleted) {
            do_action('juntaplay/groups/deleted', $group_id);

            return true;
        }

        return false;
    }

    /**
     * @return array<string, mixed[]>
     */
    public static function get_groups_for_user(int $user_id): array
    {
        global $wpdb;

        $groups_table  = "{$wpdb->prefix}jp_groups";
        $members_table = "{$wpdb->prefix}jp_group_members";
        $pools_table   = "{$wpdb->prefix}jp_pools";

        $query = $wpdb->prepare(
            "SELECT g.*, gm.role AS membership_role, gm.status AS membership_status,
                    IFNULL(p.title, '') AS pool_title, IFNULL(p.slug, '') AS pool_slug,
                    (SELECT COUNT(*) FROM $members_table m WHERE m.group_id = g.id AND m.status = 'active') AS members_count
             FROM $groups_table g
             INNER JOIN $members_table gm ON gm.group_id = g.id
             LEFT JOIN $pools_table p ON p.id = g.pool_id
             WHERE gm.user_id = %d
             ORDER BY g.created_at DESC",
            $user_id
        );

        $results = $wpdb->get_results($query, ARRAY_A) ?: [];

        $owned  = [];
        $joined = [];

        foreach ($results as $row) {
            $group = [
                'id'                 => isset($row['id']) ? (int) $row['id'] : 0,
                'owner_id'           => isset($row['owner_id']) ? (int) $row['owner_id'] : 0,
                'title'              => (string) ($row['title'] ?? ''),
                'service_name'       => (string) ($row['service_name'] ?? ''),
                'service_url'        => (string) ($row['service_url'] ?? ''),
                'slug'               => (string) ($row['slug'] ?? ''),
                'status'             => (string) ($row['status'] ?? self::STATUS_PENDING),
                'visibility'         => (string) ($row['visibility'] ?? 'public'),
                'pool_id'            => isset($row['pool_id']) ? (int) $row['pool_id'] : 0,
                'pool_title'         => (string) ($row['pool_title'] ?? ''),
                'pool_slug'          => (string) ($row['pool_slug'] ?? ''),
                'price_regular'      => isset($row['price_regular']) ? (float) $row['price_regular'] : 0.0,
                'price_promotional'  => isset($row['price_promotional']) ? (float) $row['price_promotional'] : null,
                'member_price'       => isset($row['member_price']) ? (float) $row['member_price'] : null,
                'slots_total'        => isset($row['slots_total']) ? (int) $row['slots_total'] : 0,
                'slots_reserved'     => isset($row['slots_reserved']) ? (int) $row['slots_reserved'] : 0,
                'support_channel'    => (string) ($row['support_channel'] ?? ''),
                'delivery_time'      => (string) ($row['delivery_time'] ?? ''),
                'access_method'      => (string) ($row['access_method'] ?? ''),
                'access_url'         => (string) ($row['access_url'] ?? ''),
                'access_login'       => (string) ($row['access_login'] ?? ''),
                'access_password'    => (string) ($row['access_password'] ?? ''),
                'access_notes'       => (string) ($row['access_notes'] ?? ''),
                'access_observations'=> (string) ($row['access_observations'] ?? ''),
                'category'           => (string) ($row['category'] ?? ''),
                'instant_access'     => isset($row['instant_access']) ? (bool) $row['instant_access'] : false,
                'description'        => (string) ($row['description'] ?? ''),
                'rules'              => (string) ($row['rules'] ?? ''),
                'review_note'        => (string) ($row['review_note'] ?? ''),
                'reviewed_at'        => (string) ($row['reviewed_at'] ?? ''),
                'reviewed_by'        => isset($row['reviewed_by']) ? (int) $row['reviewed_by'] : 0,
                'email_validation_sent_at' => (string) ($row['email_validation_sent_at'] ?? ''),
                'email_validated_at'      => (string) ($row['email_validated_at'] ?? ''),
                'created_at'         => (string) ($row['created_at'] ?? ''),
                'updated_at'         => (string) ($row['updated_at'] ?? ''),
                'members_count'      => isset($row['members_count']) ? (int) $row['members_count'] : 0,
                'membership_role'    => (string) ($row['membership_role'] ?? 'member'),
                'membership_status'  => (string) ($row['membership_status'] ?? 'active'),
            ];

            $cover = self::resolve_cover(isset($row['cover_id']) ? (int) $row['cover_id'] : 0);
            $group['cover_id']           = $cover['id'];
            $group['cover_url']          = $cover['url'];
            $group['cover_alt']          = $cover['alt'];
            $group['cover_placeholder']  = $cover['placeholder'];

            if ($group['membership_role'] === 'owner' || $group['membership_role'] === 'manager') {
                $owned[] = $group;
            } else {
                $joined[] = $group;
            }
        }

        return [
            'owned'  => $owned,
            'member' => $joined,
        ];
    }

    /**
     * @return array{access_url:string,access_login:string,access_password:string,access_notes:string,access_observations:string}
     */
    public static function get_credentials(int $group_id): array
    {
        $group = self::get($group_id);

        if (!$group) {
            return [
                'access_url'          => '',
                'access_login'        => '',
                'access_password'     => '',
                'access_notes'        => '',
                'access_observations' => '',
            ];
        }

        return [
            'access_url'          => isset($group->access_url) ? (string) $group->access_url : '',
            'access_login'        => isset($group->access_login) ? (string) $group->access_login : '',
            'access_password'     => isset($group->access_password) ? (string) $group->access_password : '',
            'access_notes'        => isset($group->access_notes) ? (string) $group->access_notes : '',
            'access_observations' => isset($group->access_observations) ? (string) $group->access_observations : '',
        ];
    }

    /**
     * @param array<string, mixed> $args
     *
     * @return array<string, mixed>
     */
    public static function query_public(array $args = []): array
    {
        global $wpdb;

        $defaults = [
            'page'           => 1,
            'per_page'       => 16,
            'search'         => '',
            'category'       => '',
            'orderby'        => 'created',
            'order'          => 'desc',
            'instant_access' => null,
            'price_min'      => null,
            'price_max'      => null,
        ];

        $args = array_merge($defaults, $args);

        $page     = max(1, (int) ($args['page'] ?? 1));
        $per_page = max(1, min(60, (int) ($args['per_page'] ?? 16)));
        $offset   = ($page - 1) * $per_page;

        $search   = sanitize_text_field((string) ($args['search'] ?? ''));
        $category = sanitize_key((string) ($args['category'] ?? ''));
        $orderby  = sanitize_key((string) ($args['orderby'] ?? 'created'));
        $order    = strtolower((string) ($args['order'] ?? 'desc')) === 'asc' ? 'ASC' : 'DESC';
        $instant  = $args['instant_access'];
        $price_min = $args['price_min'];
        $price_max = $args['price_max'];

        $groups_table  = "{$wpdb->prefix}jp_groups";
        $members_table = "{$wpdb->prefix}jp_group_members";
        $pools_table   = "{$wpdb->prefix}jp_pools";
        $users_table   = $wpdb->users;

        $where   = [
            "(g.status = 'approved' OR g.status = 'publish' OR g.status = 'published' OR g.status = '' OR g.status IS NULL)",
            "(g.visibility = 'public' OR g.visibility = '' OR g.visibility IS NULL)",
        ];
        $prepare = [];

        if ($search !== '') {
            $like      = '%' . $wpdb->esc_like($search) . '%';
            $where[]   = '(g.title LIKE %s OR g.service_name LIKE %s OR g.description LIKE %s)';
            $prepare[] = $like;
            $prepare[] = $like;
            $prepare[] = $like;
        }

        $categories = self::get_category_labels();
        if ($category !== '' && isset($categories[$category])) {
            $where[]   = 'g.category = %s';
            $prepare[] = $category;
        }

        if ($instant !== null) {
            $where[]   = 'g.instant_access = %d';
            $prepare[] = !empty($instant) ? 1 : 0;
        }

        $price_field = 'COALESCE(NULLIF(g.member_price, 0), NULLIF(g.price_promotional, 0), g.price_regular)';

        if ($price_min !== null && is_numeric($price_min)) {
            $where[]   = $price_field . ' >= %f';
            $prepare[] = (float) $price_min;
        }

        if ($price_max !== null && is_numeric($price_max)) {
            $where[]   = $price_field . ' <= %f';
            $prepare[] = (float) $price_max;
        }

        $order_map = [
            'created' => 'g.created_at',
            'updated' => 'g.updated_at',
            'name'    => 'g.title',
            'price'   => $price_field,
            'members' => 'members_count',
        ];

        $order_by = $order_map[$orderby] ?? $order_map['created'];

        $sql = "SELECT SQL_CALC_FOUND_ROWS g.*, u.display_name AS owner_name, u.user_email AS owner_email,
                       IFNULL(p.title, '') AS pool_title, IFNULL(p.slug, '') AS pool_slug,
                       $price_field AS effective_price,
                       (SELECT COUNT(*) FROM $members_table m WHERE m.group_id = g.id AND m.status = 'active') AS members_count
                FROM $groups_table g
                LEFT JOIN $users_table u ON u.ID = g.owner_id
                LEFT JOIN $pools_table p ON p.id = g.pool_id
                WHERE " . implode(' AND ', $where) . '
                ORDER BY ' . $order_by . ' ' . $order . '
                LIMIT %d OFFSET %d';

        $query = $wpdb->prepare($sql, array_merge($prepare, [$per_page, $offset]));

        $rows  = $wpdb->get_results($query, ARRAY_A) ?: [];
        $total = (int) $wpdb->get_var('SELECT FOUND_ROWS()');
        $pages = $per_page > 0 ? (int) ceil($total / $per_page) : 0;

        $items = [];

        foreach ($rows as $row) {
            $slots_total    = isset($row['slots_total']) ? (int) $row['slots_total'] : 0;
            $slots_reserved = isset($row['slots_reserved']) ? (int) $row['slots_reserved'] : 0;
            $available      = max(0, $slots_total - $slots_reserved);
            $cover          = self::resolve_cover(isset($row['cover_id']) ? (int) $row['cover_id'] : 0);

            $items[] = [
                'id'                 => isset($row['id']) ? (int) $row['id'] : 0,
                'slug'               => (string) ($row['slug'] ?? ''),
                'title'              => (string) ($row['title'] ?? ''),
                'service_name'       => (string) ($row['service_name'] ?? ''),
                'service_url'        => (string) ($row['service_url'] ?? ''),
                'description'        => (string) ($row['description'] ?? ''),
                'rules'              => (string) ($row['rules'] ?? ''),
                'category'           => (string) ($row['category'] ?? ''),
                'instant_access'     => !empty($row['instant_access']),
                'pool_slug'          => (string) ($row['pool_slug'] ?? ''),
                'pool_title'         => (string) ($row['pool_title'] ?? ''),
                'price_regular'      => isset($row['price_regular']) ? (float) $row['price_regular'] : 0.0,
                'price_promotional'  => isset($row['price_promotional']) ? (float) $row['price_promotional'] : null,
                'member_price'       => isset($row['member_price']) ? (float) $row['member_price'] : null,
                'effective_price'    => isset($row['effective_price']) ? (float) $row['effective_price'] : 0.0,
                'slots_total'        => $slots_total,
                'slots_reserved'     => $slots_reserved,
                'slots_available'    => $available,
                'members_count'      => isset($row['members_count']) ? (int) $row['members_count'] : 0,
                'owner_id'           => isset($row['owner_id']) ? (int) $row['owner_id'] : 0,
                'owner_name'         => (string) ($row['owner_name'] ?? ''),
                'owner_email'        => (string) ($row['owner_email'] ?? ''),
                'support_channel'    => (string) ($row['support_channel'] ?? ''),
                'delivery_time'      => (string) ($row['delivery_time'] ?? ''),
                'access_method'      => (string) ($row['access_method'] ?? ''),
                'created_at'         => (string) ($row['created_at'] ?? ''),
                'updated_at'         => (string) ($row['updated_at'] ?? ''),
                'cover_id'           => $cover['id'],
                'cover_url'          => $cover['url'],
                'cover_alt'          => $cover['alt'],
                'cover_placeholder'  => $cover['placeholder'],
            ];
        }

        return [
            'items'    => $items,
            'page'     => $page,
            'pages'    => $pages,
            'total'    => $total,
            'per_page' => $per_page,
        ];
    }

    public static function get_public_detail(int $group_id): ?array
    {
        global $wpdb;

        $group_id = absint($group_id);

        if ($group_id <= 0) {
            return null;
        }

        $groups_table  = "{$wpdb->prefix}jp_groups";
        $members_table = "{$wpdb->prefix}jp_group_members";
        $pools_table   = "{$wpdb->prefix}jp_pools";

        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT g.*, IFNULL(p.title, '') AS pool_title, IFNULL(p.slug, '') AS pool_slug,
                        (SELECT COUNT(*) FROM $members_table m WHERE m.group_id = g.id AND m.status = 'active') AS members_count
                 FROM $groups_table g
                 LEFT JOIN $pools_table p ON p.id = g.pool_id
                 WHERE g.id = %d
                 LIMIT 1",
                $group_id
            ),
            ARRAY_A
        );

        if (!$row) {
            return null;
        }

        $status      = (string) ($row['status'] ?? '');
        $visibility  = (string) ($row['visibility'] ?? 'public');
        $allowed     = ['approved', 'publish', 'published', ''];
        $visible_set = ['public', ''];

        if ($status !== '' && !in_array($status, $allowed, true)) {
            return null;
        }

        if ($visibility !== '' && !in_array($visibility, $visible_set, true)) {
            return null;
        }

        $cover = self::resolve_cover(isset($row['cover_id']) ? (int) $row['cover_id'] : 0);

        return [
            'id'                 => (int) ($row['id'] ?? 0),
            'slug'               => (string) ($row['slug'] ?? ''),
            'title'              => (string) ($row['title'] ?? ''),
            'service_name'       => (string) ($row['service_name'] ?? ''),
            'service_url'        => (string) ($row['service_url'] ?? ''),
            'description'        => (string) ($row['description'] ?? ''),
            'rules'              => (string) ($row['rules'] ?? ''),
            'category'           => (string) ($row['category'] ?? ''),
            'instant_access'     => !empty($row['instant_access']),
            'pool_id'            => isset($row['pool_id']) ? (int) $row['pool_id'] : 0,
            'pool_slug'          => (string) ($row['pool_slug'] ?? ''),
            'pool_title'         => (string) ($row['pool_title'] ?? ''),
            'price_regular'      => isset($row['price_regular']) ? (float) $row['price_regular'] : 0.0,
            'price_promotional'  => isset($row['price_promotional']) ? (float) $row['price_promotional'] : null,
            'member_price'       => isset($row['member_price']) ? (float) $row['member_price'] : null,
            'slots_total'        => isset($row['slots_total']) ? (int) $row['slots_total'] : 0,
            'slots_reserved'     => isset($row['slots_reserved']) ? (int) $row['slots_reserved'] : 0,
            'members_count'      => isset($row['members_count']) ? (int) $row['members_count'] : 0,
            'support_channel'    => (string) ($row['support_channel'] ?? ''),
            'delivery_time'      => (string) ($row['delivery_time'] ?? ''),
            'access_method'      => (string) ($row['access_method'] ?? ''),
            'owner_id'           => isset($row['owner_id']) ? (int) $row['owner_id'] : 0,
            'owner_name'         => (string) ($row['owner_name'] ?? ''),
            'owner_email'        => (string) ($row['owner_email'] ?? ''),
            'created_at'         => (string) ($row['created_at'] ?? ''),
            'updated_at'         => (string) ($row['updated_at'] ?? ''),
            'cover_id'           => $cover['id'],
            'cover_url'          => $cover['url'],
            'cover_alt'          => $cover['alt'],
            'cover_placeholder'  => $cover['placeholder'],
            'status'             => $status,
            'visibility'         => $visibility,
        ];
    }

    public static function get_public_by_slug(string $slug): ?array
    {
        global $wpdb;

        $slug = sanitize_title($slug);

        if ($slug === '') {
            return null;
        }

        $table = "{$wpdb->prefix}jp_groups";

        $group_id = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM $table WHERE slug = %s LIMIT 1",
                $slug
            )
        );

        if ($group_id <= 0) {
            return null;
        }

        return self::get_public_detail($group_id);
    }

    public static function get(int $group_id): ?object
    {
        global $wpdb;

        $group_id = absint($group_id);

        if ($group_id <= 0) {
            return null;
        }

        $table = "{$wpdb->prefix}jp_groups";

        $row = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table WHERE id = %d", $group_id)
        );

        return $row ?: null;
    }

    /**
     * @param array<string, mixed> $args
     *
     * @return array<int, array<string, mixed>>
     */
    public static function all(array $args = []): array
    {
        global $wpdb;

        $defaults = [
            'status' => 'all',
            'search' => '',
            'limit'  => 100,
        ];

        $args = array_merge($defaults, $args);

        $status = sanitize_key((string) $args['status']);
        $search = sanitize_text_field((string) $args['search']);
        $limit  = absint($args['limit']);

        if ($limit <= 0 || $limit > 500) {
            $limit = 100;
        }

        $groups_table  = "{$wpdb->prefix}jp_groups";
        $members_table = "{$wpdb->prefix}jp_group_members";
        $pools_table   = "{$wpdb->prefix}jp_pools";
        $users_table   = $wpdb->users;

        $where   = ['1=1'];
        $prepare = [];

        if ($status !== '' && $status !== 'all' && in_array($status, self::get_allowed_statuses(), true)) {
            $where[]   = 'g.status = %s';
            $prepare[] = $status;
        }

        if ($search !== '') {
            $like      = '%' . $wpdb->esc_like($search) . '%';
            $where[]   = '(g.title LIKE %s OR g.description LIKE %s OR u.display_name LIKE %s)';
            $prepare[] = $like;
            $prepare[] = $like;
            $prepare[] = $like;
        }

        $sql = "SELECT g.*, u.display_name AS owner_name, u.user_email AS owner_email,
                       p.title AS pool_title, p.slug AS pool_slug,
                       (SELECT COUNT(*) FROM $members_table m WHERE m.group_id = g.id AND m.status = 'active') AS members_count
                FROM $groups_table g
                LEFT JOIN $users_table u ON u.ID = g.owner_id
                LEFT JOIN $pools_table p ON p.id = g.pool_id
                WHERE " . implode(' AND ', $where) . '
                ORDER BY g.created_at DESC
                LIMIT ' . $limit;

        if ($prepare) {
            $sql = $wpdb->prepare($sql, $prepare);
        }

        $rows = $wpdb->get_results($sql, ARRAY_A) ?: [];

        $groups = [];
        foreach ($rows as $row) {
            $cover = self::resolve_cover(isset($row['cover_id']) ? (int) $row['cover_id'] : 0);
            $groups[] = [
                'id'                 => isset($row['id']) ? (int) $row['id'] : 0,
                'owner_id'           => isset($row['owner_id']) ? (int) $row['owner_id'] : 0,
                'owner_name'         => (string) ($row['owner_name'] ?? ''),
                'owner_email'        => (string) ($row['owner_email'] ?? ''),
                'title'              => (string) ($row['title'] ?? ''),
                'service_name'       => (string) ($row['service_name'] ?? ''),
                'service_url'        => (string) ($row['service_url'] ?? ''),
                'status'             => (string) ($row['status'] ?? self::STATUS_PENDING),
                'visibility'         => (string) ($row['visibility'] ?? 'public'),
                'slug'               => (string) ($row['slug'] ?? ''),
                'pool_id'            => isset($row['pool_id']) ? (int) $row['pool_id'] : 0,
                'pool_title'         => (string) ($row['pool_title'] ?? ''),
                'pool_slug'          => (string) ($row['pool_slug'] ?? ''),
                'price_regular'      => isset($row['price_regular']) ? (float) $row['price_regular'] : 0.0,
                'price_promotional'  => isset($row['price_promotional']) ? (float) $row['price_promotional'] : null,
                'member_price'       => isset($row['member_price']) ? (float) $row['member_price'] : null,
                'slots_total'        => isset($row['slots_total']) ? (int) $row['slots_total'] : 0,
                'slots_reserved'     => isset($row['slots_reserved']) ? (int) $row['slots_reserved'] : 0,
                'support_channel'    => (string) ($row['support_channel'] ?? ''),
                'delivery_time'      => (string) ($row['delivery_time'] ?? ''),
                'access_method'      => (string) ($row['access_method'] ?? ''),
                'access_url'         => (string) ($row['access_url'] ?? ''),
                'access_login'       => (string) ($row['access_login'] ?? ''),
                'access_password'    => (string) ($row['access_password'] ?? ''),
                'access_notes'       => (string) ($row['access_notes'] ?? ''),
                'access_observations'=> (string) ($row['access_observations'] ?? ''),
                'category'           => (string) ($row['category'] ?? ''),
                'instant_access'     => isset($row['instant_access']) ? (bool) $row['instant_access'] : false,
                'rules'              => (string) ($row['rules'] ?? ''),
                'description'        => (string) ($row['description'] ?? ''),
                'review_note'        => (string) ($row['review_note'] ?? ''),
                'reviewed_at'        => (string) ($row['reviewed_at'] ?? ''),
                'reviewed_by'        => isset($row['reviewed_by']) ? (int) $row['reviewed_by'] : 0,
                'created_at'         => (string) ($row['created_at'] ?? ''),
                'updated_at'         => (string) ($row['updated_at'] ?? ''),
                'members_count'      => isset($row['members_count']) ? (int) $row['members_count'] : 0,
                'cover_id'           => $cover['id'],
                'cover_url'          => $cover['url'],
                'cover_alt'          => $cover['alt'],
                'cover_placeholder'  => $cover['placeholder'],
            ];
        }

        return $groups;
    }

    /**
     * @return array<string, int>
     */
    public static function counts_by_status(): array
    {
        global $wpdb;

        $table = "{$wpdb->prefix}jp_groups";

        $rows = $wpdb->get_results("SELECT status, COUNT(*) AS total FROM $table GROUP BY status", ARRAY_A) ?: [];

        $counts = [
            self::STATUS_PENDING  => 0,
            self::STATUS_APPROVED => 0,
            self::STATUS_REJECTED => 0,
            self::STATUS_ARCHIVED => 0,
        ];

        foreach ($rows as $row) {
            $status = isset($row['status']) ? (string) $row['status'] : '';
            $total  = isset($row['total']) ? (int) $row['total'] : 0;

            if (isset($counts[$status])) {
                $counts[$status] = $total;
            }
        }

        return $counts;
    }

    /**
     * @param array<string, mixed> $args
     */
    public static function update_status(int $group_id, string $status, array $args = []): bool
    {
        global $wpdb;

        $group_id = absint($group_id);
        $status   = sanitize_key($status);

        if ($group_id <= 0 || !in_array($status, self::get_allowed_statuses(), true)) {
            return false;
        }

        $table = "{$wpdb->prefix}jp_groups";

        $columns = ['status = %s'];
        $prepare = [$status];

        $review_note = isset($args['review_note']) ? sanitize_textarea_field((string) $args['review_note']) : '';
        $reviewed_by = isset($args['reviewed_by']) ? absint($args['reviewed_by']) : 0;
        $reviewed_at = isset($args['reviewed_at']) ? sanitize_text_field((string) $args['reviewed_at']) : '';

        if ($status === self::STATUS_PENDING) {
            $columns[] = 'reviewed_by = NULL';
            $columns[] = 'reviewed_at = NULL';
            $columns[] = 'review_note = NULL';
        } else {
            if ($reviewed_by > 0) {
                $columns[] = 'reviewed_by = %d';
                $prepare[] = $reviewed_by;
            } else {
                $columns[] = 'reviewed_by = NULL';
            }

            if ($reviewed_at === '') {
                $reviewed_at = current_time('mysql');
            }

            $columns[] = 'reviewed_at = %s';
            $prepare[] = $reviewed_at;

            if ($review_note !== '') {
                $columns[] = 'review_note = %s';
                $prepare[] = $review_note;
            } else {
                $columns[] = 'review_note = NULL';
            }
        }

        $columns[] = 'updated_at = %s';
        $prepare[] = current_time('mysql');

        $prepare[] = $group_id;

        $sql = 'UPDATE ' . $table . ' SET ' . implode(', ', $columns) . ' WHERE id = %d';

        $result = $wpdb->query($wpdb->prepare($sql, $prepare));

        return (bool) $result;
    }

    public static function get_status_label(string $status): string
    {
        return match ($status) {
            self::STATUS_APPROVED => __('Aprovado', 'juntaplay'),
            self::STATUS_REJECTED => __('Recusado', 'juntaplay'),
            self::STATUS_ARCHIVED => __('Arquivado', 'juntaplay'),
            default               => __('Em análise', 'juntaplay'),
        };
    }

    /**
     * @return string[]
     */
    public static function get_allowed_statuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_APPROVED,
            self::STATUS_REJECTED,
            self::STATUS_ARCHIVED,
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function get_pool_choices(): array
    {
        global $wpdb;

        $table   = "{$wpdb->prefix}jp_pools";
        $results = $wpdb->get_results("SELECT id, title FROM $table WHERE status IN ('publish','published','active') ORDER BY created_at DESC", ARRAY_A) ?: [];

        $choices = [];
        foreach ($results as $row) {
            $choices[(int) $row['id']] = (string) ($row['title'] ?? '');
        }

        return $choices;
    }

    public static function generate_email_validation_code(int $group_id): ?string
    {
        global $wpdb;

        $group_id = absint($group_id);

        if ($group_id <= 0) {
            return null;
        }

        $code = (string) wp_rand(100000, 999999);
        $hash = wp_hash_password($code);

        $table   = "{$wpdb->prefix}jp_groups";
        $updated = $wpdb->update(
            $table,
            [
                'email_validation_hash'    => $hash,
                'email_validation_sent_at' => current_time('mysql'),
                'email_validated_at'       => null,
            ],
            ['id' => $group_id],
            ['%s', '%s', '%s'],
            ['%d']
        );

        return $updated !== false ? $code : null;
    }

    public static function slug_exists(string $slug): bool
    {
        global $wpdb;

        $slug = sanitize_title($slug);

        if ($slug === '') {
            return false;
        }

        $table = "{$wpdb->prefix}jp_groups";

        return (int) $wpdb->get_var(
            $wpdb->prepare("SELECT COUNT(*) FROM $table WHERE slug = %s", $slug)
        ) > 0;
    }

    private static function generate_unique_slug(string $title): string
    {
        global $wpdb;

        $base = sanitize_title($title);
        if ($base === '') {
            $base = 'grupo-' . substr((string) wp_generate_uuid4(), 0, 8);
        }

        $slug   = $base;
        $suffix = 2;
        $table  = "{$wpdb->prefix}jp_groups";

        while ((int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table WHERE slug = %s", $slug)) > 0) {
            $slug = $base . '-' . $suffix;
            ++$suffix;
        }

        return $slug;
    }

    private static function ensure_unique_slug(string $base): string
    {
        global $wpdb;

        $slug   = $base;
        $suffix = 2;
        $table  = "{$wpdb->prefix}jp_groups";

        while ((int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table WHERE slug = %s", $slug)) > 0) {
            $slug = $base . '-' . $suffix;
            ++$suffix;
        }

        return $slug;
    }

    /**
     * @return array{id: int, url: string, alt: string, placeholder: bool}
     */
    private static function resolve_cover(int $attachment_id): array
    {
        $attachment_id = $attachment_id > 0 ? $attachment_id : 0;
        $url           = '';
        $alt           = '';

        if ($attachment_id > 0) {
            $attachment = wp_prepare_attachment_for_js($attachment_id);

            if (is_array($attachment)) {
                $url = (string) ($attachment['sizes']['large']['url'] ?? $attachment['sizes']['medium_large']['url'] ?? $attachment['url'] ?? '');
                $alt = (string) ($attachment['alt'] ?? $attachment['title'] ?? '');
            }

            if ($url === '') {
                $url = (string) wp_get_attachment_image_url($attachment_id, 'large');
            }

            if ($alt === '') {
                $alt = (string) get_post_meta($attachment_id, '_wp_attachment_image_alt', true);
            }
        }

        if ($url === '') {
            $url = self::get_default_cover_url();
        }

        if ($alt === '') {
            $alt = __('Capa do grupo', 'juntaplay');
        }

        return [
            'id'          => $attachment_id,
            'url'         => $url,
            'alt'         => $alt,
            'placeholder' => $attachment_id <= 0,
        ];
    }

    private static function get_default_cover_url(): string
    {
        if (defined('JP_GROUP_COVER_PLACEHOLDER') && JP_GROUP_COVER_PLACEHOLDER !== '') {
            return JP_GROUP_COVER_PLACEHOLDER;
        }

        if (defined('JP_URL') && JP_URL !== '') {
            return trailingslashit(JP_URL) . 'assets/img/group-cover-placeholder.svg';
        }

        return '';
    }
}
