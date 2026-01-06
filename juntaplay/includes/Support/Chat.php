<?php

declare(strict_types=1);

namespace JuntaPlay\Support;

use JuntaPlay\Data\GroupMembers;
use JuntaPlay\Data\Groups;
use wpdb;

use function absint;
use function current_time;
use function esc_url_raw;
use function sanitize_textarea_field;

defined('ABSPATH') || exit;

class Chat
{
    /**
     * Verifica se admin e assinante pertencem ao mesmo grupo e se o admin é o proprietário.
     */
    public static function validar_relacao_entre_admin_assinante(int $group_id, int $user_id, int $admin_id): bool
    {
        $group_id = absint($group_id);
        $user_id  = absint($user_id);
        $admin_id = absint($admin_id);

        if ($group_id <= 0 || $user_id <= 0 || $admin_id <= 0) {
            return false;
        }

        $group = Groups::get($group_id);
        if (!$group || (int) ($group->owner_id ?? 0) !== $admin_id) {
            return false;
        }

        $membership = GroupMembers::get_membership($group_id, $user_id);
        if (!$membership || ($membership['status'] ?? '') !== 'active') {
            return false;
        }

        return true;
    }

    /**
     * Registra uma mensagem entre administrador e assinante do grupo.
     */
    public static function enviar_mensagem(
        int $group_id,
        int $user_id,
        int $admin_id,
        int $sender_id,
        string $message,
        ?string $image_url = null
    ): int {
        global $wpdb;

        $group_id  = absint($group_id);
        $user_id   = absint($user_id);
        $admin_id  = absint($admin_id);
        $sender_id = absint($sender_id);
        $message   = sanitize_textarea_field($message);
        $image_url = $image_url !== null && $image_url !== '' ? esc_url_raw($image_url) : null;

        if (!self::validar_relacao_entre_admin_assinante($group_id, $user_id, $admin_id)) {
            return 0;
        }

        if ($sender_id !== $user_id && $sender_id !== $admin_id) {
            return 0;
        }

        if ($message === '' && $image_url === null) {
            return 0;
        }

        $inserted = $wpdb->insert(
            "{$wpdb->prefix}juntaplay_chat_messages",
            [
                'group_id'   => $group_id,
                'admin_id'   => $admin_id,
                'user_id'    => $user_id,
                'sender_id'  => $sender_id,
                'message'    => $message !== '' ? $message : null,
                'image_url'  => $image_url,
                'created_at' => current_time('mysql'),
            ],
            ['%d', '%d', '%d', '%d', '%s', '%s', '%s']
        );

        return $inserted ? (int) $wpdb->insert_id : 0;
    }

    /**
     * Obtém as mensagens entre administrador e assinante de um grupo.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function obter_mensagens(
        int $group_id,
        int $user_id,
        int $admin_id,
        int $limit = 50,
        ?string $after = null
    ): array {
        global $wpdb;

        if (!self::validar_relacao_entre_admin_assinante($group_id, $user_id, $admin_id)) {
            return [];
        }

        $group_id = absint($group_id);
        $user_id  = absint($user_id);
        $admin_id = absint($admin_id);
        $limit    = max(1, min(200, $limit));

        $where  = $wpdb->prepare(
            'WHERE group_id = %d AND admin_id = %d AND user_id = %d',
            $group_id,
            $admin_id,
            $user_id
        );
        $params = [$limit];

        if ($after !== null && $after !== '') {
            $where  .= $wpdb->prepare(' AND created_at > %s', $after);
        }

        $table = "{$wpdb->prefix}juntaplay_chat_messages";
        $sql   = "SELECT * FROM $table $where ORDER BY created_at ASC, id ASC LIMIT %d";

        $rows = $wpdb->get_results($wpdb->prepare($sql, ...$params), ARRAY_A) ?: [];

        return array_map(static function (array $row): array {
            return [
                'id'         => isset($row['id']) ? (int) $row['id'] : 0,
                'group_id'   => isset($row['group_id']) ? (int) $row['group_id'] : 0,
                'admin_id'   => isset($row['admin_id']) ? (int) $row['admin_id'] : 0,
                'user_id'    => isset($row['user_id']) ? (int) $row['user_id'] : 0,
                'sender_id'  => isset($row['sender_id']) ? (int) $row['sender_id'] : 0,
                'message'    => (string) ($row['message'] ?? ''),
                'image_url'  => (string) ($row['image_url'] ?? ''),
                'created_at' => (string) ($row['created_at'] ?? ''),
            ];
        }, $rows);
    }
}
