<?php
declare(strict_types=1);

namespace JuntaPlay\Front;

use JuntaPlay\Admin\Settings;
use JuntaPlay\Data\CaucaoCycles;
use JuntaPlay\Data\CreditTransactions;
use JuntaPlay\Data\Groups;
use JuntaPlay\Data\PaymentSplits;
use JuntaPlay\Support\CaucaoManager;
use JuntaPlay\Support\Wallet;
use RuntimeException;
use WC_Order;
use WC_Order_Item_Product;
use WP_User;
use WP_REST_Request;
use WP_REST_Response;

use function __;
use function absint;
use function add_action;
use function current_time;
use function get_option;
use function get_user_by;
use function is_array;
use function in_array;
use function number_format;
use function register_rest_route;
use function sanitize_email;
use function sanitize_text_field;
use function sprintf;
use function stripos;
use function update_user_meta;
use function wc_get_order;
use function wc_get_order_id_by_order_key;
use function wc_price;
use function wp_unslash;

defined('ABSPATH') || exit;

class MercadoPagoWebhook
{
    private const CALLBACK_META_KEY = '_payment_via_mp_callback';
    private const ORDER_HANDLED_META_KEY = '_juntaplay_order_handled';

    public function init(): void
    {
        add_action('rest_api_init', [$this, 'register_routes']);

        add_action('woocommerce_order_status_processing', [$this, 'handle_paid_order'], 10, 1);
        add_action('woocommerce_order_status_completed', [$this, 'handle_paid_order'], 10, 1);
    }

    public function register_routes(): void
    {
        register_rest_route('juntaplay/v1', '/mercadopago/webhook', [
            'methods'             => ['POST', 'GET'],
            'callback'            => [$this, 'handle_notification'],
            'permission_callback' => '__return_true',
        ]);
    }

    public function handle_notification(WP_REST_Request $request): WP_REST_Response
    {
        $payload = $request->get_json_params();

        if (!$payload) {
            $payload = $request->get_params();
        }

        if (!$this->is_payment_approved($request, $payload)) {
            return new WP_REST_Response(['message' => 'Evento ignorado'], 202);
        }

        $order_id = $this->resolve_order_id($request, $payload);

        if ($order_id <= 0) {
            return new WP_REST_Response(['message' => 'Pedido não encontrado na notificação.'], 400);
        }

        $order = wc_get_order($order_id);

        if (!$order instanceof WC_Order) {
            return new WP_REST_Response(['message' => 'Pedido inválido ou inexistente.'], 404);
        }

        if (!empty($order->get_meta('_juntaplay_split_processed', true)) || PaymentSplits::has_completed_for_order($order_id)) {
            return new WP_REST_Response(['message' => 'Split já processado para este pedido.'], 200);
        }
        $percentage     = Settings::get_split_percentage();
        $payload_log    = is_array($payload) ? $payload : [];
        $cycle_start    = $this->get_cycle_start($order);
        $process_result = $this->process_order_payment($order, $percentage, $payload_log, $cycle_start);

        if ($process_result['error'] !== '') {
            return new WP_REST_Response(['message' => $process_result['error']], 500);
        }

        return new WP_REST_Response([
            'message'   => 'Split registrado com sucesso.',
            'processed' => $process_result['processed'],
        ], 200);
    }

    public function handle_paid_order(int $order_id): void
    {
        $order = wc_get_order($order_id);

        if (!$order instanceof WC_Order) {
            return;
        }

        if (!in_array($order->get_status(), ['processing', 'completed'], true)) {
            return;
        }

        if (!$this->is_mercado_pago_order($order)) {
            return;
        }

        if (!empty($order->get_meta('_juntaplay_split_processed', true)) || PaymentSplits::has_completed_for_order($order->get_id())) {
            $order->update_meta_data(self::CALLBACK_META_KEY, 'yes');
            $order->update_meta_data(self::ORDER_HANDLED_META_KEY, 'yes');
            if (empty($order->get_meta('_juntaplay_split_processed', true))) {
                $order->update_meta_data('_juntaplay_split_processed', current_time('mysql'));
            }
            $order->save();

            return;
        }

        if ((string) $order->get_meta(self::ORDER_HANDLED_META_KEY, true) === 'yes') {
            return;
        }

        $percentage  = Settings::get_split_percentage();
        $cycle_start = $this->get_cycle_start($order);
        $result      = $this->process_order_payment($order, $percentage, ['source' => 'status_hook'], $cycle_start);

        if ($result['error'] !== '') {
            $order->add_order_note(sprintf('JuntaPlay: falha ao processar pagamento pós-webhook: %s', $result['error']));

            return;
        }

        $order->update_meta_data(self::CALLBACK_META_KEY, 'yes');
        $order->update_meta_data(self::ORDER_HANDLED_META_KEY, 'yes');
        if (empty($order->get_meta('_juntaplay_split_processed', true))) {
            $order->update_meta_data('_juntaplay_split_processed', current_time('mysql'));
        }
        $order->save();
    }

    /**
     * @param array<string, mixed>|null $payload
     */
    private function is_payment_approved(WP_REST_Request $request, ?array $payload): bool
    {
        $status = '';
        if ($payload && isset($payload['data']) && is_array($payload['data'])) {
            $status = (string) ($payload['data']['status'] ?? '');
        }

        if ($status === '' && $payload) {
            $status = (string) ($payload['status'] ?? '');
        }

        if ($status === '' && $request->get_param('status')) {
            $status = sanitize_text_field(wp_unslash((string) $request->get_param('status')));
        }

        $topics = [];

        if ($payload) {
            if (isset($payload['type'])) {
                $topics[] = (string) $payload['type'];
            }

            if (isset($payload['topic'])) {
                $topics[] = (string) $payload['topic'];
            }

            if (isset($payload['action'])) {
                $topics[] = (string) $payload['action'];
            }

            if (isset($payload['notification_type'])) {
                $topics[] = (string) $payload['notification_type'];
            }

            if (isset($payload['resource'])) {
                $topics[] = (string) $payload['resource'];
            }

            if (isset($payload['data']) && is_array($payload['data'])) {
                if (isset($payload['data']['topic'])) {
                    $topics[] = (string) $payload['data']['topic'];
                }

                if (isset($payload['data']['type'])) {
                    $topics[] = (string) $payload['data']['type'];
                }

                if (isset($payload['data']['action'])) {
                    $topics[] = (string) $payload['data']['action'];
                }
            }
        }

        foreach (['type', 'topic', 'action', 'notification_type'] as $query_key) {
            if ($request->get_param($query_key)) {
                $topics[] = sanitize_text_field(wp_unslash((string) $request->get_param($query_key)));
            }
        }

        $is_payment_topic = false;

        foreach ($topics as $topic) {
            if ($topic !== '' && stripos((string) $topic, 'payment') !== false) {
                $is_payment_topic = true;
                break;
            }
        }

        return strtolower($status) === 'approved' && $is_payment_topic;
    }

    private function order_paid_via_callback(WC_Order $order): bool
    {
        $flag = $order->get_meta(self::CALLBACK_META_KEY, true);

        if (is_string($flag)) {
            $flag = strtolower(trim($flag));
        }

        return $flag === 'yes' || $flag === '1' || $flag === 'true' || $flag === 1 || $flag === true;
    }

    private function is_mercado_pago_order(WC_Order $order): bool
    {
        $method = (string) $order->get_payment_method();

        if ($method === '') {
            return false;
        }

        return stripos($method, 'mercado') !== false || stripos($method, 'mp') !== false;
    }

    private function has_gateway_payment_reference(WC_Order $order): bool
    {
        $transaction_id = (string) $order->get_transaction_id();

        if ($transaction_id !== '') {
            return true;
        }

        $meta_tx = (string) $order->get_meta('_transaction_id', true);

        return $meta_tx !== '';
    }

    /**
     * @param array<string, mixed>|null $payload_log
     * @return array{processed: array<int, array<string, mixed>>, error: string}
     */
    private function process_order_payment(WC_Order $order, float $percentage, ?array $payload_log, string $cycle_start): array
    {
        if (!empty($order->get_meta('_juntaplay_split_processed', true)) || PaymentSplits::has_completed_for_order($order->get_id())) {
            return ['processed' => [], 'error' => ''];
        }

        $summaries = $this->build_group_summaries($order);

        if (!$summaries) {
            return ['processed' => [], 'error' => 'Nenhum grupo associado ao pedido.'];
        }

        $order_id       = $order->get_id();
        $payload_log    = $payload_log ?? [];
        $participant_id = (int) $order->get_user_id();
        $superadmin_id  = $this->get_superadmin_user_id();
        $records        = [];
        $created_splits = [];
        $error_message  = '';
        $created_at     = current_time('mysql');

        if ($participant_id <= 0) {
            return ['processed' => [], 'error' => 'Usuário do pedido ausente.'];
        }

        foreach ($summaries as $summary) {
            $group_id    = isset($summary['group_id']) ? (int) $summary['group_id'] : 0;
            $group_title = isset($summary['title']) ? (string) $summary['title'] : '';
            $base_value  = isset($summary['subscription_total']) ? (float) $summary['subscription_total'] : 0.0;
            $fee_total   = isset($summary['fee_total']) ? (float) $summary['fee_total'] : 0.0;

            if ($group_id <= 0 || $base_value <= 0) {
                $error_message = 'Dados do grupo inválidos na notificação.';
                break;
            }

            $group = Groups::get($group_id);
            if (!$group || !isset($group->owner_id)) {
                $error_message = 'Administrador do grupo ausente.';
                break;
            }

            $admin_id       = (int) $group->owner_id;
            $fee_total      = max(0.0, $fee_total);
            $superadmin_base = $base_value * ($percentage / 100);
            $superadmin_amt = $superadmin_base + $fee_total;
            $admin_amt      = max(0.0, $base_value - $superadmin_base);
            $deposit_amt    = $base_value;

            $split_id = PaymentSplits::create([
                'order_id'          => $order_id,
                'group_id'          => $group_id,
                'group_title'       => $group_title,
                'base_amount'       => $this->format_decimal($base_value),
                'superadmin_id'     => $superadmin_id,
                'admin_id'          => $admin_id,
                'participant_id'    => $participant_id,
                'percentage'        => $percentage,
                'superadmin_amount' => $this->format_decimal($superadmin_amt),
                'admin_amount'      => $this->format_decimal($admin_amt),
                'deposit_amount'    => $this->format_decimal($deposit_amt),
                'created_at'        => $created_at,
                'status'            => PaymentSplits::STATUS_PENDING,
                'payload'           => $payload_log + [
                    'superadmin_id' => $superadmin_id,
                    'fee_total'     => $fee_total,
                ],
            ]);

            if ($split_id <= 0) {
                $error_message = 'Falha ao registrar log de split.';
                break;
            }

            $created_splits[] = $split_id;

            $record = [
                'split_id'          => $split_id,
                'group_id'          => $group_id,
                'group_title'       => $group_title,
                'superadmin_id'     => $superadmin_id,
                'admin_id'          => $admin_id,
                'participant_id'    => $participant_id,
                'percentage'        => $percentage,
                'base_amount'       => $base_value,
                'superadmin_amount' => $superadmin_amt,
                'admin_amount'      => $admin_amt,
                'deposit_amount'    => $deposit_amt,
                'operations'        => [],
                'caucao_cycle_id'   => 0,
            ];

            try {
                if ($superadmin_id > 0 && $superadmin_amt > 0) {
                    $record['operations'][] = $this->credit_user($superadmin_id, $superadmin_amt, [
                        'order_id'    => $order_id,
                        'group_id'    => $group_id,
                        'group_title' => $group_title,
                        'split_id'    => $split_id,
                        'role'        => 'superadmin',
                        'reference'   => sprintf('JPS-%d', $order_id),
                    ]);
                }

                if ($admin_id > 0 && $admin_amt > 0) {
                    $record['operations'][] = $this->credit_user($admin_id, $admin_amt, [
                        'order_id'    => $order_id,
                        'group_id'    => $group_id,
                        'group_title' => $group_title,
                        'split_id'    => $split_id,
                        'role'        => 'admin',
                        'reference'   => sprintf('JPS-%d', $order_id),
                    ]);
                }

                $caucao_id = CaucaoManager::retain($participant_id, $group_id, $order_id, $deposit_amt, $cycle_start);
                if ($caucao_id <= 0) {
                    throw new RuntimeException('Não foi possível registrar o caução retido.');
                }

                $record['caucao_cycle_id'] = $caucao_id;
                $records[]                 = $record;
            } catch (\Throwable $e) {
                $error_message = $e->getMessage();
                $this->rollback_entry($record);
                PaymentSplits::mark_failed((int) $record['split_id'], $error_message);
                break;
            }
        }

        if ($error_message !== '') {
            foreach ($records as $record) {
                $this->rollback_entry($record);
                PaymentSplits::mark_failed((int) $record['split_id'], $error_message);
            }

            $pending_only = array_diff($created_splits, array_column($records, 'split_id'));
            foreach ($pending_only as $split_id) {
                PaymentSplits::mark_failed((int) $split_id, $error_message);
            }

            return ['processed' => [], 'error' => $error_message];
        }

        $processed = [];

        foreach ($records as $record) {
            PaymentSplits::mark_completed((int) $record['split_id']);
            $processed[] = $record + ['deposit_status' => CaucaoCycles::STATUS_RETIDO];
        }

        if ($processed) {
            $order->update_meta_data(self::CALLBACK_META_KEY, 'yes');
            $order->update_meta_data('_juntaplay_split_processed', current_time('mysql'));
            $order->add_order_note($this->build_order_note($processed));
            $order->save();

            do_action('juntaplay/split/completed', $order, $processed, [
                'percentage' => $percentage,
            ]);
        }

        return ['processed' => $processed, 'error' => ''];
    }

    /**
     * @param array<string, mixed>|null $payload
     */
    private function resolve_order_id(WP_REST_Request $request, ?array $payload): int
    {
        $candidates = [];

        if ($payload) {
            $data = isset($payload['data']) && is_array($payload['data']) ? $payload['data'] : $payload;

            if (isset($data['order_id'])) {
                $candidates[] = $data['order_id'];
            }

            if (isset($data['metadata']) && is_array($data['metadata']) && isset($data['metadata']['order_id'])) {
                $candidates[] = $data['metadata']['order_id'];
            }

            if (isset($data['external_reference'])) {
                $candidates[] = $data['external_reference'];
            }
        }

        if ($request->get_param('order_id')) {
            $candidates[] = $request->get_param('order_id');
        }

        if ($request->get_param('external_reference')) {
            $candidates[] = $request->get_param('external_reference');
        }

        foreach ($candidates as $candidate) {
            $order_id = $this->normalize_order_id($candidate);

            if ($order_id > 0) {
                return $order_id;
            }
        }

        return 0;
    }

    private function normalize_order_id($value): int
    {
        if (is_numeric($value)) {
            return absint($value);
        }

        $value = (string) $value;

        if ($value !== '') {
            $order_id = wc_get_order_id_by_order_key($value);

            if ($order_id > 0) {
                return $order_id;
            }
        }

        return 0;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function build_group_summaries(WC_Order $order): array
    {
        $items      = [];
        $order_meta = [
            'deposit'        => (float) $order->get_meta('_juntaplay_group_deposit', true),
            'processing_fee' => (float) $order->get_meta('_juntaplay_processing_fee', true),
            'wallet_used'    => (float) $order->get_meta('_juntaplay_wallet_used', true),
        ];

        $subscription_total = 0.0;

        foreach ($order->get_items() as $item) {
            if (!$item instanceof WC_Order_Item_Product) {
                continue;
            }

            $group_id = (int) $item->get_meta('_juntaplay_group_id', true);

            if ($group_id <= 0) {
                continue;
            }

            $quantity           = max(1, (int) $item->get_quantity());
            $line_total         = (float) $item->get_subtotal();
            $subscription_total += $line_total;
            $group_title        = (string) $item->get_meta('JuntaPlay Grupo', true);
            $fee_per_member     = (float) $item->get_meta('_juntaplay_fee_per_member', true);
            $fee_total          = $fee_per_member > 0 ? $fee_per_member * $quantity : 0.0;

            if ($group_title === '') {
                $group_title = $item->get_name();
            }

            $items[] = [
                'group_id'          => $group_id,
                'quantity'          => $quantity,
                'subscription_total'=> $line_total,
                'title'             => $group_title,
                'fee_per_member'    => $fee_per_member,
                'fee_total'         => $fee_total,
            ];
        }

        if (!$items) {
            return [];
        }

        if ($order_meta['deposit'] <= 0.0) {
            $order_meta['deposit'] = $subscription_total;
        }

        foreach ($items as &$summary) {
            $line_total = isset($summary['subscription_total']) ? (float) $summary['subscription_total'] : 0.0;
            $ratio      = $subscription_total > 0 ? $line_total / $subscription_total : 0.0;

            $summary['deposit']        = $order_meta['deposit'] * $ratio;
            $summary['processing_fee'] = $order_meta['processing_fee'] * $ratio;
            $summary['wallet_used']    = $order_meta['wallet_used'] * $ratio;
        }
        unset($summary);

        return $items;
    }

    /**
     * @param array<string, mixed> $context
     */
    private function credit_user(int $user_id, float $amount, array $context): array
    {
        if ($user_id <= 0 || $amount <= 0) {
            throw new RuntimeException('Usuário ou valor inválido para crédito.');
        }

        $operation = Wallet::credit($user_id, $this->format_decimal($amount), CreditTransactions::TYPE_SPLIT, $context);

        if (!$operation) {
            throw new RuntimeException('Não foi possível aplicar crédito na carteira.');
        }

        return $operation;
    }

    /**
     * @param array<string, mixed> $record
     */
    private function rollback_entry(array $record): void
    {
        if (!empty($record['operations']) && is_array($record['operations'])) {
            foreach ($record['operations'] as $operation) {
                Wallet::rollback(is_array($operation) ? $operation : []);
            }
        }

        if (!empty($record['caucao_cycle_id'])) {
            CaucaoCycles::delete((int) $record['caucao_cycle_id']);
        }
    }

    private function get_superadmin_user_id(): int
    {
        $admin_email = sanitize_email((string) get_option('admin_email'));

        if ($admin_email !== '') {
            $user = get_user_by('email', $admin_email);
            if ($user instanceof WP_User) {
                return (int) $user->ID;
            }
        }

        return 0;
    }

    private function format_decimal(float $value): float
    {
        return (float) number_format($value, 2, '.', '');
    }

    private function get_cycle_start(WC_Order $order): string
    {
        $paid = $order->get_date_paid();

        if ($paid) {
            return $paid->date('Y-m-d H:i:s');
        }

        $created = $order->get_date_created();

        if ($created) {
            return $created->date('Y-m-d H:i:s');
        }

        return current_time('mysql');
    }

    /**
     * @param array<int, array<string, mixed>> $entries
     */
    private function build_order_note(array $entries): string
    {
        $lines = [];

        foreach ($entries as $entry) {
            $lines[] = sprintf(
                '%1$s (ID %2$s): base %3$s | superadmin %4$s | admin %5$s | caução %6$s (%7$s)',
                $entry['group_title'] ?? __('Grupo', 'juntaplay'),
                $entry['group_id'] ?? '—',
                wc_price((float) ($entry['base_amount'] ?? 0.0)),
                wc_price((float) ($entry['superadmin_amount'] ?? 0.0)),
                wc_price((float) ($entry['admin_amount'] ?? 0.0)),
                wc_price((float) ($entry['deposit_amount'] ?? 0.0)),
                $entry['deposit_status'] ?? CaucaoCycles::STATUS_RETIDO
            );
        }

        return sprintf(
            "JuntaPlay: split interno registrado.\n%s",
            implode("\n", $lines)
        );
    }
}
