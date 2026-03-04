<?php
/**
 * Plugin Name: RMA Automations
 * Description: Rotinas de e-mail via WP-Cron para renovação e mandato com logs de envio.
 * Version: 0.5.0
 * Author: RMA
 */

if (! defined('ABSPATH')) {
    exit;
}

final class RMA_Automations {
    public function __construct() {
        add_action('init', [$this, 'schedule_cron']);
        add_action('rma_daily_automation', [$this, 'run_daily_automation']);
    }

    public function schedule_cron(): void {
        if (! wp_next_scheduled('rma_daily_automation')) {
            wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', 'rma_daily_automation');
        }
    }

    public function run_daily_automation(): void {
        $paged = 1;
        do {
            $query = new WP_Query([
                'post_type' => 'rma_entidade',
                'post_status' => ['publish', 'draft'],
                'posts_per_page' => 500,
                'paged' => $paged,
                'fields' => 'ids',
            ]);

            foreach ($query->posts as $entity_id) {
                $entity_id = (int) $entity_id;
                $author_id = (int) get_post_field('post_author', $entity_id);
                $email = get_the_author_meta('user_email', $author_id);
                if (! is_email($email)) {
                    continue;
                }

                $governance = get_post_meta($entity_id, 'governance_status', true);
                $mandato_fim = (string) get_post_meta($entity_id, 'mandato_fim', true);
                if ($governance === 'aprovado' && $this->is_days_before($mandato_fim, [60, 30, 7])) {
                    $this->notify_once_daily($email, 'Mandato próximo do vencimento', 'Seu mandato está próximo do vencimento.', $entity_id, 'mandato');
                }

                $finance = get_post_meta($entity_id, 'finance_status', true);
                $anuidade_vencimento = (string) get_post_meta($entity_id, 'anuidade_vencimento', true);
                $should_notify_anuidade = $governance === 'aprovado' && $finance !== 'adimplente';
                if ($should_notify_anuidade && $anuidade_vencimento !== '') {
                    $should_notify_anuidade = $this->is_days_before($anuidade_vencimento, [30, 7, 0, -1, -7]);
                }

                if ($should_notify_anuidade) {
                    $this->notify_once_daily($email, 'Anuidade pendente', 'Identificamos pendência na sua anuidade. Gere seu PIX no painel.', $entity_id, 'anuidade');
                }
            }

            $paged++;
        } while ($paged <= (int) $query->max_num_pages);

        wp_reset_postdata();
    }

    private function is_days_before(string $date, array $days): bool {
        if ($date === '') {
            return false;
        }

        $target = strtotime($date);
        if (! $target) {
            return false;
        }

        $today = strtotime(gmdate('Y-m-d'));
        $diff_days = (int) floor(($target - $today) / DAY_IN_SECONDS);

        return in_array($diff_days, $days, true);
    }

    private function notify_once_daily(string $email, string $subject, string $message, int $entity_id, string $context): void {
        $day_key = 'rma_mail_sent_' . md5($entity_id . '|' . $context . '|' . gmdate('Y-m-d'));
        if (get_transient($day_key)) {
            return;
        }

        $sent = $this->send_email($email, $subject, $message);
        if ($sent) {
            set_transient($day_key, 1, DAY_IN_SECONDS + HOUR_IN_SECONDS);
        }

        $logs = get_post_meta($entity_id, 'automation_logs', true);
        $logs = is_array($logs) ? $logs : [];
        $logs[] = [
            'context' => $context,
            'email' => $email,
            'subject' => $subject,
            'sent' => $sent,
            'datetime' => current_time('mysql', true),
        ];

        $max_logs = 200;
        if (count($logs) > $max_logs) {
            $logs = array_slice($logs, -1 * $max_logs);
        }

        update_post_meta($entity_id, 'automation_logs', $logs);
    }

    private function send_email(string $email, string $subject, string $message): bool {
        $sender_mode = (string) get_option('rma_email_sender_mode', 'wp_mail');

        if ($sender_mode === 'woo_mail' && function_exists('WC') && WC() && method_exists(WC(), 'mailer')) {
            $mailer = WC()->mailer();
            if ($mailer) {
                $wrapped = method_exists($mailer, 'wrap_message') ? $mailer->wrap_message($subject, nl2br(esc_html($message))) : $message;
                $headers = ['Content-Type: text/html; charset=UTF-8'];

                return (bool) $mailer->send($email, $subject, $wrapped, $headers, []);
            }
        }

        return (bool) wp_mail($email, $subject, $message);
    }

    public static function deactivate(): void {
        wp_clear_scheduled_hook('rma_daily_automation');
    }
}

register_deactivation_hook(__FILE__, ['RMA_Automations', 'deactivate']);
new RMA_Automations();
