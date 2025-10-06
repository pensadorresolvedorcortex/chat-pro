<?php
declare(strict_types=1);

namespace JuntaPlay\Notifications;

use JuntaPlay\Data\Notifications as NotificationsData;

use function add_action;
use function admin_url;
use function esc_url;
use function get_bloginfo;
use function get_option;
use function get_permalink;
use function get_userdata;
use function home_url;
use function number_format_i18n;
use function sprintf;
use function wp_mail;
use function __;

defined('ABSPATH') || exit;

class Credits
{
    public function init(): void
    {
        add_action('juntaplay/credits/withdrawal_requested', [$this, 'on_withdrawal_requested'], 10, 3);
        add_action('juntaplay/credits/deposit_completed', [$this, 'on_deposit_completed'], 10, 2);
        add_action('juntaplay/credits/deposit_reversed', [$this, 'on_deposit_reversed'], 10, 2);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function on_withdrawal_requested(int $user_id, int $withdrawal_id, array $data): void
    {
        $user = get_userdata($user_id);
        if (!$user) {
            return;
        }

        $amount    = isset($data['amount']) ? (float) $data['amount'] : 0.0;
        $method    = isset($data['method']) ? (string) $data['method'] : 'pix';
        $reference = isset($data['reference']) ? (string) $data['reference'] : '';
        $site_name = get_bloginfo('name');
        $currency  = number_format_i18n($amount, 2);

        $dashboard_id  = (int) get_option('juntaplay_page_painel');
        $dashboard_url = $dashboard_id ? get_permalink($dashboard_id) : home_url('/painel');

        NotificationsData::add($user_id, [
            'type'       => 'wallet',
            'title'      => __('Solicitação de retirada recebida', 'juntaplay'),
            'message'    => sprintf(__('Recebemos seu pedido de saque no valor de R$ %s. Assim que analisarmos você será notificado.', 'juntaplay'), $currency),
            'action_url' => $dashboard_url,
        ]);

        $admin_email = (string) get_option('admin_email');
        if ($admin_email !== '') {
            $review_url = esc_url(admin_url('admin.php?page=juntaplay-groups'));
            $message = implode("\n", [
                sprintf(__('Nova solicitação de retirada no valor de R$ %s.', 'juntaplay'), $currency),
                sprintf(__('Usuário: %s (%s)', 'juntaplay'), $user->display_name ?: $user->user_login, $user->user_email),
                sprintf(__('Método: %s', 'juntaplay'), strtoupper($method)),
                $reference !== '' ? sprintf(__('Referência: %s', 'juntaplay'), $reference) : '',
                '',
                sprintf(__('Gerencie as retiradas no painel do WordPress: %s', 'juntaplay'), $review_url),
            ]);

            $headers = ['Content-Type: text/plain; charset=UTF-8'];
            wp_mail(
                $admin_email,
                sprintf(__('Solicitação de retirada no JuntaPlay — %s', 'juntaplay'), $site_name),
                $message,
                $headers
            );
        }

        $user_message = implode("\n", [
            sprintf(__('Olá %s, recebemos seu pedido de retirada.', 'juntaplay'), $user->display_name ?: $user->user_login),
            sprintf(__('Valor solicitado: R$ %s', 'juntaplay'), $currency),
            sprintf(__('Método escolhido: %s', 'juntaplay'), strtoupper($method)),
            $reference !== '' ? sprintf(__('Protocolo: %s', 'juntaplay'), $reference) : '',
            '',
            __('Você receberá um novo aviso quando o pagamento for processado.', 'juntaplay'),
            sprintf(__('Acompanhe pelo painel: %s', 'juntaplay'), $dashboard_url),
            '',
            sprintf(__('Equipe %s', 'juntaplay'), $site_name),
        ]);

        $headers = ['Content-Type: text/plain; charset=UTF-8'];
        wp_mail(
            (string) $user->user_email,
            sprintf(__('Seu pedido de retirada foi registrado — %s', 'juntaplay'), $site_name),
            $user_message,
            $headers
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    public function on_deposit_completed(int $user_id, array $data): void
    {
        $user = get_userdata($user_id);
        if (!$user) {
            return;
        }

        $amount    = isset($data['amount']) ? (float) $data['amount'] : 0.0;
        $reference = isset($data['reference']) ? (string) $data['reference'] : '';
        $site_name = get_bloginfo('name');
        $currency  = number_format_i18n($amount, 2);

        $dashboard_id  = (int) get_option('juntaplay_page_painel');
        $dashboard_url = $dashboard_id ? get_permalink($dashboard_id) : home_url('/painel');

        NotificationsData::add($user_id, [
            'type'       => 'wallet',
            'title'      => __('Créditos adicionados com sucesso', 'juntaplay'),
            'message'    => sprintf(__('Recebemos seu pagamento de R$ %s e seu saldo foi atualizado.', 'juntaplay'), $currency),
            'action_url' => $dashboard_url,
        ]);

        $message = implode("\n", [
            sprintf(__('Olá %s, confirmamos a entrada de créditos na sua carteira.', 'juntaplay'), $user->display_name ?: $user->user_login),
            sprintf(__('Valor creditado: R$ %s', 'juntaplay'), $currency),
            $reference !== '' ? sprintf(__('Protocolo: %s', 'juntaplay'), $reference) : '',
            '',
            sprintf(__('Você pode consultar o extrato em: %s', 'juntaplay'), $dashboard_url),
            '',
            sprintf(__('Equipe %s', 'juntaplay'), $site_name),
        ]);

        $headers = ['Content-Type: text/plain; charset=UTF-8'];
        wp_mail(
            (string) $user->user_email,
            sprintf(__('Créditos confirmados — %s', 'juntaplay'), $site_name),
            $message,
            $headers
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    public function on_deposit_reversed(int $user_id, array $data): void
    {
        $user = get_userdata($user_id);
        if (!$user) {
            return;
        }

        $amount    = isset($data['amount']) ? (float) $data['amount'] : 0.0;
        $reference = isset($data['reference']) ? (string) $data['reference'] : '';
        $site_name = get_bloginfo('name');
        $currency  = number_format_i18n($amount, 2);

        $dashboard_id  = (int) get_option('juntaplay_page_painel');
        $dashboard_url = $dashboard_id ? get_permalink($dashboard_id) : home_url('/painel');

        NotificationsData::add($user_id, [
            'type'       => 'wallet',
            'title'      => __('Recarga cancelada', 'juntaplay'),
            'message'    => sprintf(__('A recarga de R$ %s foi estornada e o saldo ajustado.', 'juntaplay'), $currency),
            'action_url' => $dashboard_url,
        ]);

        $message = implode("\n", [
            sprintf(__('Olá %s, a recarga de créditos não pôde ser concluída.', 'juntaplay'), $user->display_name ?: $user->user_login),
            sprintf(__('Valor estornado: R$ %s', 'juntaplay'), $currency),
            $reference !== '' ? sprintf(__('Protocolo: %s', 'juntaplay'), $reference) : '',
            '',
            __('Caso o pagamento tenha sido realizado, o estorno será processado pelo mesmo meio utilizado.', 'juntaplay'),
            sprintf(__('Acompanhe o status pelo painel: %s', 'juntaplay'), $dashboard_url),
            '',
            sprintf(__('Equipe %s', 'juntaplay'), $site_name),
        ]);

        $headers = ['Content-Type: text/plain; charset=UTF-8'];
        wp_mail(
            (string) $user->user_email,
            sprintf(__('Recarga cancelada — %s', 'juntaplay'), $site_name),
            $message,
            $headers
        );
    }
}
