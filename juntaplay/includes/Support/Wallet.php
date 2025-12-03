<?php
declare(strict_types=1);

namespace JuntaPlay\Support;

use JuntaPlay\Data\CreditTransactions;

use function current_time;
use function get_user_meta;
use function number_format;
use function update_user_meta;

defined('ABSPATH') || exit;

class Wallet
{
    /**
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    public static function credit(int $user_id, float $amount, string $type, array $context = []): array
    {
        if ($user_id <= 0 || $amount <= 0) {
            return [];
        }

        $balance_before = (float) get_user_meta($user_id, 'juntaplay_credit_balance', true);
        $balance_after  = $balance_before + $amount;

        update_user_meta($user_id, 'juntaplay_credit_balance', number_format($balance_after, 2, '.', ''));
        update_user_meta($user_id, 'juntaplay_credit_updated_at', current_time('mysql'));

        $transaction_id = CreditTransactions::create([
            'user_id'       => $user_id,
            'type'          => $type,
            'status'        => CreditTransactions::STATUS_COMPLETED,
            'amount'        => $amount,
            'balance_after' => $balance_after,
            'reference'     => isset($context['reference']) ? (string) $context['reference'] : '',
            'context'       => $context,
        ]);

        return [
            'type'             => 'credit',
            'user_id'          => $user_id,
            'amount'           => $amount,
            'previous_balance' => $balance_before,
            'transaction_id'   => $transaction_id,
        ];
    }

    /**
     * @param array<string, mixed> $operation
     */
    public static function rollback(array $operation): void
    {
        if (($operation['type'] ?? '') !== 'credit') {
            return;
        }

        $user_id          = (int) ($operation['user_id'] ?? 0);
        $previous_balance = isset($operation['previous_balance']) ? (float) $operation['previous_balance'] : null;
        $transaction_id   = isset($operation['transaction_id']) ? (int) $operation['transaction_id'] : 0;

        if ($user_id <= 0 || $previous_balance === null) {
            return;
        }

        update_user_meta($user_id, 'juntaplay_credit_balance', number_format($previous_balance, 2, '.', ''));
        update_user_meta($user_id, 'juntaplay_credit_updated_at', current_time('mysql'));

        if ($transaction_id > 0) {
            CreditTransactions::delete($transaction_id);
        }
    }
}
