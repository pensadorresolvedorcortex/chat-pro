<?php
declare(strict_types=1);

namespace JuntaPlay\Notifications;

use JuntaPlay\Admin\Settings;
use WP_User;
use WC_Order;

use function add_action;
use function current_time;
use function esc_html__;
use function get_option;
use function get_userdata;
use function number_format_i18n;
use function sprintf;
use function wc_price;

defined('ABSPATH') || exit;

class Splits
{
    public function init(): void
    {
        add_action('juntaplay/split/completed', [$this, 'on_split_completed'], 10, 3);
    }

    /**
     * @param array<int, array<string, mixed>> $entries
     * @param array<string, mixed>             $meta
     */
    public function on_split_completed(WC_Order $order, array $entries, array $meta): void
    {
        $superadmin_email = (string) get_option('admin_email');
        $percentage       = isset($meta['percentage']) ? (float) $meta['percentage'] : Settings::get_split_percentage();
        $timestamp        = current_time('mysql');

        $participant      = $order->get_user_id() ? get_userdata((int) $order->get_user_id()) : null;
        $participant_name = $participant instanceof WP_User ? ($participant->display_name ?: $participant->user_login) : __('Participante', 'juntaplay');
        $participant_id   = $participant instanceof WP_User ? (int) $participant->ID : 0;

        if ($superadmin_email !== '') {
            $this->notify_superadmin($superadmin_email, $order, $entries, $percentage, $participant_name, $participant_id, $timestamp);
        }

        $this->notify_group_admins($order, $entries, $percentage, $participant_name, $participant_id, $timestamp);
    }

    /**
     * @param array<int, array<string, mixed>> $entries
     */
    private function notify_superadmin(string $email, WC_Order $order, array $entries, float $percentage, string $participant_name, int $participant_id, string $timestamp): void
    {
        $lines = [];

        foreach ($entries as $entry) {
            $admin = isset($entry['admin_id']) ? get_userdata((int) $entry['admin_id']) : null;
            $admin_name  = $admin instanceof WP_User ? ($admin->display_name ?: $admin->user_login) : __('Admin do grupo', 'juntaplay');
            $admin_email = $admin instanceof WP_User ? (string) $admin->user_email : '';
            $admin_id    = $admin instanceof WP_User ? (int) $admin->ID : 0;

            $lines[] = sprintf(
                '#%1$s — %2$s (ID %3$s): %4$s | %5$s: %6$s | %7$s: %8$s | %9$s: %10$s (ID %11$s, %12$s)',
                $order->get_order_number(),
                $entry['group_title'] ?? '',
                $entry['group_id'] ?? '',
                wc_price((float) ($entry['deposit_amount'] ?? 0.0)),
                __('Superadmin', 'juntaplay'),
                wc_price((float) ($entry['superadmin_amount'] ?? 0.0)),
                __('Admin', 'juntaplay'),
                wc_price((float) ($entry['admin_amount'] ?? 0.0)),
                __('Admin', 'juntaplay'),
                $admin_name,
                $admin_id ?: '—',
                $admin_email !== '' ? $admin_email : __('sem e-mail', 'juntaplay')
            );
        }

        $blocks = [
            [
                'type'    => 'paragraph',
                'content' => sprintf(__('Split registrado para o pedido #%s.', 'juntaplay'), $order->get_order_number()),
            ],
            [
                'type'  => 'list',
                'items' => $lines,
            ],
            [
                'type'    => 'paragraph',
                'content' => sprintf(__('Percentual aplicado: %s%%', 'juntaplay'), number_format_i18n($percentage, 2)),
            ],
            [
                'type'    => 'paragraph',
                'content' => sprintf(__('Participante: %1$s (ID %2$s)', 'juntaplay'), $participant_name, $participant_id ?: '—'),
            ],
            [
                'type'    => 'paragraph',
                'content' => sprintf(__('Data/hora: %s', 'juntaplay'), $timestamp),
            ],
        ];

        EmailHelper::send(
            $email,
            sprintf(__('Split interno confirmado — Pedido #%s', 'juntaplay'), $order->get_order_number()),
            $blocks,
            [
                'headline'  => esc_html__('Split interno confirmado', 'juntaplay'),
                'preheader' => sprintf(__('Percentual aplicado: %s%%', 'juntaplay'), number_format_i18n($percentage, 2)),
            ]
        );
    }

    /**
     * @param array<int, array<string, mixed>> $entries
     */
    private function notify_group_admins(WC_Order $order, array $entries, float $percentage, string $participant_name, int $participant_id, string $timestamp): void
    {
        $groups = [];

        foreach ($entries as $entry) {
            $admin_id = isset($entry['admin_id']) ? (int) $entry['admin_id'] : 0;
            if ($admin_id <= 0) {
                continue;
            }

            if (!isset($groups[$admin_id])) {
                $groups[$admin_id] = [];
            }

            $groups[$admin_id][] = $entry;
        }

        foreach ($groups as $admin_id => $items) {
            $admin = get_userdata($admin_id);
            if (!$admin instanceof WP_User || $admin->user_email === '') {
                continue;
            }

            $lines = [];
            foreach ($items as $entry) {
                $lines[] = sprintf(
                    '%1$s (ID %2$s): %3$s',
                    $entry['group_title'] ?? '',
                    $entry['group_id'] ?? '',
                    wc_price((float) ($entry['admin_amount'] ?? 0.0))
                );
            }

            $blocks = [
                [
                    'type'    => 'paragraph',
                    'content' => sprintf(__('Você recebeu um repasse do pedido #%s.', 'juntaplay'), $order->get_order_number()),
                ],
                [
                    'type'  => 'list',
                    'items' => $lines,
                ],
                [
                    'type'    => 'paragraph',
                    'content' => sprintf(__('Participante: %1$s (ID %2$s)', 'juntaplay'), $participant_name, $participant_id ?: '—'),
                ],
                [
                    'type'    => 'paragraph',
                    'content' => sprintf(__('Percentual do superadmin: %s%%', 'juntaplay'), number_format_i18n($percentage, 2)),
                ],
                [
                    'type'    => 'paragraph',
                    'content' => sprintf(__('Data/hora: %s', 'juntaplay'), $timestamp),
                ],
            ];

            EmailHelper::send(
                (string) $admin->user_email,
                sprintf(__('Você recebeu um repasse — Pedido #%s', 'juntaplay'), $order->get_order_number()),
                $blocks,
                [
                    'headline'  => esc_html__('Repasse confirmado', 'juntaplay'),
                    'preheader' => sprintf(__('Total recebido: %s', 'juntaplay'), wc_price($this->sum_admin_amount($items))),
                ]
            );
        }
    }

    /**
     * @param array<int, array<string, mixed>> $items
     */
    private function sum_admin_amount(array $items): float
    {
        $total = 0.0;

        foreach ($items as $item) {
            $total += isset($item['admin_amount']) ? (float) $item['admin_amount'] : 0.0;
        }

        return $total;
    }
}
