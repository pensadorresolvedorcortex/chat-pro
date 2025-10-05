<?php

declare(strict_types=1);

namespace JuntaPlay\Notifications;

use JuntaPlay\Data\GroupComplaints as GroupComplaintsData;
use JuntaPlay\Data\Groups as GroupsData;

use function add_action;
use function admin_url;
use function apply_filters;
use function esc_url;
use function get_bloginfo;
use function get_option;
use function get_permalink;
use function get_userdata;
use function number_format_i18n;
use function wp_get_attachment_url;
use function sprintf;
use function ucwords;
use function wp_mail;
use function __;

defined('ABSPATH') || exit;

class Groups
{
    public function init(): void
    {
        add_action('juntaplay/profile/groups/created', [$this, 'on_group_created'], 10, 3);
        add_action('juntaplay/groups/status_changed', [$this, 'on_status_changed'], 10, 4);
        add_action('juntaplay/groups/complaint_created', [$this, 'on_complaint_created'], 10, 4);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function on_group_created(int $user_id, int $group_id, array $data): void
    {
        $admin_email = (string) get_option('admin_email');
        $site_name   = get_bloginfo('name');
        $group_title  = isset($data['title']) ? (string) $data['title'] : __('Grupo', 'juntaplay');
        $description  = isset($data['description']) ? (string) $data['description'] : '';
        $service      = isset($data['service_name']) ? (string) $data['service_name'] : '';
        $service_url  = isset($data['service_url']) ? (string) $data['service_url'] : '';
        $price        = isset($data['price_regular']) ? (float) $data['price_regular'] : 0.0;
        $promo        = isset($data['price_promotional']) ? $data['price_promotional'] : null;
        $promo_value  = $promo !== null ? (float) $promo : null;
        $member_price = isset($data['member_price']) ? (float) $data['member_price'] : 0.0;
        $slots_total  = isset($data['slots_total']) ? (int) $data['slots_total'] : 0;
        $slots_reserved = isset($data['slots_reserved']) ? (int) $data['slots_reserved'] : 0;
        $support      = isset($data['support_channel']) ? (string) $data['support_channel'] : '';
        $delivery     = isset($data['delivery_time']) ? (string) $data['delivery_time'] : '';
        $access       = isset($data['access_method']) ? (string) $data['access_method'] : '';
        $rules        = isset($data['rules']) ? (string) $data['rules'] : '';
        $category     = isset($data['category']) ? (string) $data['category'] : '';
        $instant      = !empty($data['instant_access']);
        $validation_code = isset($data['validation_code']) ? (string) $data['validation_code'] : '';
        $headers = ['Content-Type: text/plain; charset=UTF-8'];

        $category_labels = GroupsData::get_category_labels();

        $category_label = $category !== '' && isset($category_labels[$category])
            ? (string) $category_labels[$category]
            : ($category !== '' ? ucwords(str_replace(['-', '_'], ' ', $category)) : __('Outros serviços', 'juntaplay'));
        $instant_text = $instant ? __('Ativado', 'juntaplay') : __('Desativado', 'juntaplay');
        $promo_flag   = ($promo_value !== null && $promo_value > 0) ? __('Sim', 'juntaplay') : __('Não', 'juntaplay');

        if ($admin_email !== '') {
            $review_url = esc_url(admin_url('admin.php?page=juntaplay-groups&status=pending'));
            $lines = [];
            if ($service !== '') {
                $lines[] = sprintf(__('Serviço: %s', 'juntaplay'), $service);
            }
            if ($service_url !== '') {
                $lines[] = sprintf(__('Site oficial: %s', 'juntaplay'), $service_url);
            }
            if ($category_label !== '') {
                $lines[] = sprintf(__('Categoria: %s', 'juntaplay'), $category_label);
            }
            if ($price > 0) {
                $lines[] = sprintf(__('Valor do serviço: R$ %s', 'juntaplay'), number_format_i18n($price, 2));
            }
            if ($promo_value !== null) {
                $lines[] = sprintf(__('Valor promocional: R$ %s', 'juntaplay'), number_format_i18n($promo_value, 2));
            }
            if ($member_price > 0) {
                $lines[] = sprintf(__('Cota sugerida por membro: R$ %s', 'juntaplay'), number_format_i18n($member_price, 2));
            }
            if ($slots_total > 0) {
                $lines[] = sprintf(__('Vagas totais/reservadas: %1$d / %2$d', 'juntaplay'), $slots_total, $slots_reserved);
            }
            if ($support !== '') {
                $lines[] = sprintf(__('Suporte aos membros: %s', 'juntaplay'), $support);
            }
            if ($delivery !== '') {
                $lines[] = sprintf(__('Entrega do acesso: %s', 'juntaplay'), $delivery);
            }
            if ($access !== '') {
                $lines[] = sprintf(__('Forma de acesso: %s', 'juntaplay'), $access);
            }
            $lines[] = sprintf(__('É valor promocional?: %s', 'juntaplay'), $promo_flag);
            $lines[] = sprintf(__('Acesso instantâneo: %s', 'juntaplay'), $instant_text);

            $admin_message = sprintf(
                "%s\n\n%s\n%s\n%s",
                sprintf(__('Novo grupo “%s” aguarda aprovação no JuntaPlay.', 'juntaplay'), $group_title),
                $description !== '' ? sprintf(__('Descrição: %s', 'juntaplay'), $description) : __('Descrição não informada.', 'juntaplay'),
                $lines ? implode("\n", $lines) : __('Nenhum detalhe adicional informado.', 'juntaplay'),
                sprintf(__('Revise agora: %s', 'juntaplay'), $review_url)
            );

            wp_mail(
                $admin_email,
                sprintf(__('Novo grupo aguardando aprovação — %s', 'juntaplay'), $site_name),
                $admin_message,
                $headers
            );
        }

        $user = get_userdata($user_id);
        if ($user && !empty($user->user_email)) {
            $summary_lines = [
                sprintf(__('Serviço: %s', 'juntaplay'), $service !== '' ? $service : __('Não informado', 'juntaplay')),
                sprintf(__('Tipo: %s', 'juntaplay'), __('Público', 'juntaplay')),
                sprintf(__('Categoria: %s', 'juntaplay'), $category_label),
                sprintf(__('Acesso instantâneo: %s', 'juntaplay'), $instant_text),
                sprintf(__('Valor do serviço: R$ %s', 'juntaplay'), number_format_i18n($price, 2)),
                sprintf(__('É valor promocional?: %s', 'juntaplay'), $promo_flag),
            ];

            if ($promo_value !== null) {
                $summary_lines[] = sprintf(__('Valor promocional: R$ %s', 'juntaplay'), number_format_i18n($promo_value, 2));
            }

            $summary_lines[] = sprintf(__('Vagas totais: %d', 'juntaplay'), $slots_total);
            $summary_lines[] = sprintf(__('Reservadas para você: %d', 'juntaplay'), $slots_reserved);
            $summary_lines[] = sprintf(__('Os membros irão pagar: R$ %s', 'juntaplay'), number_format_i18n($member_price, 2));

            if ($service_url !== '') {
                $summary_lines[] = sprintf(__('Site oficial: %s', 'juntaplay'), $service_url);
            }

            if ($support !== '') {
                $summary_lines[] = sprintf(__('Suporte aos membros: %s', 'juntaplay'), $support);
            }

            if ($delivery !== '') {
                $summary_lines[] = sprintf(__('Envio de acesso: %s', 'juntaplay'), $delivery);
            }

            if ($access !== '') {
                $summary_lines[] = sprintf(__('Forma de acesso: %s', 'juntaplay'), $access);
            }

            if ($rules !== '') {
                $summary_lines[] = sprintf(__('Regras: %s', 'juntaplay'), $rules);
            }

            if ($description !== '') {
                $summary_lines[] = sprintf(__('Descrição: %s', 'juntaplay'), $description);
            }

            $summary_message = implode("\n", [
                sprintf(__('Olá %s, aqui estão as informações do seu novo grupo.', 'juntaplay'), $user->display_name ?: $user->user_login),
                sprintf(__('Seu grupo público “%s” foi criado e está aguardando análise para aprovação.', 'juntaplay'), $group_title),
                __('Você será notificado em breve com mais informações.', 'juntaplay'),
                '',
                __('Resumo cadastrado:', 'juntaplay'),
                implode("\n", $summary_lines),
                '',
                sprintf(__('Equipe %s', 'juntaplay'), $site_name),
            ]);

            wp_mail(
                (string) $user->user_email,
                sprintf(__('Resumo do seu novo grupo — %s', 'juntaplay'), $site_name),
                $summary_message,
                $headers
            );

            if ($validation_code !== '') {
                $validation_message = implode("\n", [
                    sprintf(__('Use o código abaixo para validar o e-mail associado ao grupo “%s”.', 'juntaplay'), $group_title),
                    '',
                    $validation_code,
                    '',
                    __('Informe este código apenas dentro do painel do JuntaPlay. Caso você não tenha solicitado a criação deste grupo, ignore esta mensagem.', 'juntaplay'),
                    '',
                    sprintf(__('Equipe %s', 'juntaplay'), $site_name),
                ]);

                wp_mail(
                    (string) $user->user_email,
                    sprintf(__('Código de validação de e-mail — %s', 'juntaplay'), $site_name),
                    $validation_message,
                    $headers
                );
            }
        }
    }

    /**
     * @param array<string, mixed> $context
     */
    public function on_complaint_created(int $complaint_id, int $group_id, int $user_id, array $context): void
    {
        $admin_email = (string) get_option('admin_email');
        $site_name   = get_bloginfo('name');

        $group       = GroupsData::get($group_id);
        $group_title = isset($context['group_title']) && $context['group_title'] !== ''
            ? (string) $context['group_title']
            : ($group && isset($group->title) ? (string) $group->title : __('Grupo', 'juntaplay'));

        $reason_key   = isset($context['reason']) ? (string) $context['reason'] : 'other';
        $reason_label = GroupComplaintsData::get_reason_label($reason_key);
        $order_id     = isset($context['order_id']) ? (int) $context['order_id'] : 0;
        $message      = isset($context['message']) ? (string) $context['message'] : '';
        $attachments  = isset($context['attachments']) && is_array($context['attachments'])
            ? array_filter(array_map('intval', $context['attachments']))
            : [];

        $user      = get_userdata($user_id);
        $user_name = $user ? ($user->display_name ?: $user->user_login) : __('Cliente', 'juntaplay');
        $user_email = $user ? (string) $user->user_email : '';

        $attachment_lines = [];
        foreach ($attachments as $attachment_id) {
            $url = wp_get_attachment_url($attachment_id);
            if ($url) {
                $attachment_lines[] = $url;
            }
        }

        $admin_lines = [
            sprintf(__('Grupo: %s', 'juntaplay'), $group_title),
            sprintf(__('Motivo: %s', 'juntaplay'), $reason_label),
            sprintf(__('Cliente: %1$s (ID %2$d)', 'juntaplay'), $user_name, $user_id),
        ];

        if ($order_id > 0) {
            $admin_lines[] = sprintf(__('Pedido relacionado: #%d', 'juntaplay'), $order_id);
        }

        if ($attachment_lines) {
            $admin_lines[] = __('Anexos enviados:', 'juntaplay');
            $admin_lines   = array_merge($admin_lines, $attachment_lines);
        }

        if ($admin_email !== '') {
            $admin_message = implode("\n", [
                sprintf(__('Nova reclamação #%1$d registrada para o grupo “%2$s”.', 'juntaplay'), $complaint_id, $group_title),
                '',
                implode("\n", $admin_lines),
                '',
                __('Descrição enviada:', 'juntaplay'),
                $message !== '' ? $message : __('O cliente não adicionou detalhes adicionais.', 'juntaplay'),
                '',
                sprintf(__('Revise no painel: %s', 'juntaplay'), esc_url(admin_url('admin.php?page=juntaplay-groups'))),
            ]);

            wp_mail(
                $admin_email,
                sprintf(__('Reclamação #%1$d registrada — %2$s', 'juntaplay'), $complaint_id, $site_name),
                $admin_message,
                ['Content-Type: text/plain; charset=UTF-8']
            );
        }

        if ($user_email !== '') {
            $user_lines = [
                sprintf(__('Olá %s, recebemos sua reclamação.', 'juntaplay'), $user_name),
                sprintf(__('Grupo: %s', 'juntaplay'), $group_title),
                sprintf(__('Motivo selecionado: %s', 'juntaplay'), $reason_label),
            ];

            if ($order_id > 0) {
                $user_lines[] = sprintf(__('Pedido relacionado: #%d', 'juntaplay'), $order_id);
            }

            if ($message !== '') {
                $user_lines[] = '';
                $user_lines[] = __('Descrição enviada:', 'juntaplay');
                $user_lines[] = $message;
            }

            $user_lines[] = '';
            $user_lines[] = __('Nossa equipe e o administrador foram notificados e responderão em breve.', 'juntaplay');
            $user_lines[] = __('Você pode acompanhar o andamento acessando o seu painel em Minha Conta > Meus Grupos.', 'juntaplay');
            $user_lines[] = '';
            $user_lines[] = sprintf(__('Equipe %s', 'juntaplay'), $site_name);

            wp_mail(
                $user_email,
                sprintf(__('Recebemos sua reclamação — %s', 'juntaplay'), $site_name),
                implode("\n", $user_lines),
                ['Content-Type: text/plain; charset=UTF-8']
            );
        }
    }


    /**
     * @param array<string, mixed> $context
     */
    public function on_status_changed(int $group_id, string $old_status, string $new_status, array $context = []): void
    {
        $group = GroupsData::get($group_id);
        if (!$group || empty($group->owner_id)) {
            return;
        }

        $owner = get_userdata((int) $group->owner_id);
        if (!$owner || empty($owner->user_email)) {
            return;
        }

        $site_name   = get_bloginfo('name');
        $group_title = isset($group->title) ? (string) $group->title : __('Grupo', 'juntaplay');
        $note        = isset($context['note']) ? (string) $context['note'] : '';
        $headers     = ['Content-Type: text/plain; charset=UTF-8'];

        switch ($new_status) {
            case GroupsData::STATUS_APPROVED:
                $subject = sprintf(__('Seu grupo “%s” foi aprovado!', 'juntaplay'), $group_title);
                $message = sprintf(
                    "%s\n\n%s",
                    sprintf(__('Parabéns! O grupo “%s” foi aprovado e já está disponível para convidar participantes.', 'juntaplay'), $group_title),
                    __('Acesse o painel do JuntaPlay para gerenciar convites e acompanhar as cotas.', 'juntaplay')
                );
                $profile_page_id = (int) get_option('juntaplay_page_perfil');
                if ($profile_page_id > 0) {
                    $profile_url = get_permalink($profile_page_id);
                    if ($profile_url) {
                        $message .= "\n\n" . sprintf(__('Libere o grupo acessando: %s', 'juntaplay'), esc_url($profile_url));
                    }
                }
                break;
            case GroupsData::STATUS_REJECTED:
                $subject = sprintf(__('Seu grupo “%s” foi recusado', 'juntaplay'), $group_title);
                $message = sprintf(
                    "%s\n\n%s",
                    sprintf(__('O grupo “%s” não foi aprovado neste momento.', 'juntaplay'), $group_title),
                    $note !== '' ? sprintf(__('Motivo informado: %s', 'juntaplay'), $note) : __('Entre em contato com o suporte para mais detalhes.', 'juntaplay')
                );
                break;
            case GroupsData::STATUS_ARCHIVED:
                $subject = sprintf(__('Seu grupo “%s” foi arquivado', 'juntaplay'), $group_title);
                $message = sprintf(
                    "%s\n\n%s",
                    sprintf(__('O grupo “%s” foi arquivado pelo super administrador.', 'juntaplay'), $group_title),
                    __('Nenhuma nova cota poderá ser reservada até que ele seja reativado.', 'juntaplay')
                );
                if ($note !== '') {
                    $message .= "\n\n" . sprintf(__('Observação: %s', 'juntaplay'), $note);
                }
                break;
            case GroupsData::STATUS_PENDING:
                $subject = sprintf(__('O grupo “%s” voltou para análise', 'juntaplay'), $group_title);
                $message = __('Atualizamos o status do seu grupo para análise novamente. Em breve entraremos em contato.', 'juntaplay');
                break;
            default:
                return;
        }

        wp_mail(
            (string) $owner->user_email,
            sprintf('%s — %s', $subject, $site_name),
            $message,
            $headers
        );
    }
}
