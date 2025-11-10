<?php

declare(strict_types=1);

namespace JuntaPlay\Notifications;

use JuntaPlay\Data\GroupComplaints as GroupComplaintsData;
use JuntaPlay\Data\GroupMembers as GroupMembersData;
use JuntaPlay\Data\Groups as GroupsData;
use JuntaPlay\Data\Notifications as NotificationsData;

use function add_action;
use function add_query_arg;
use function admin_url;
use function apply_filters;
use function esc_url;
use function get_bloginfo;
use function get_option;
use function get_permalink;
use function get_userdata;
use function home_url;
use function number_format_i18n;
use function wp_get_attachment_url;
use function wp_strip_all_tags;
use function wp_trim_words;
use function sprintf;
use function ucwords;
use function __;
use function wc_get_order;
use function wc_date_format;
use function wc_format_datetime;
use WC_Order;

defined('ABSPATH') || exit;

class Groups
{
    public function init(): void
    {
        add_action('juntaplay/profile/groups/created', [$this, 'on_group_created'], 10, 3);
        add_action('juntaplay/groups/status_changed', [$this, 'on_status_changed'], 10, 4);
        add_action('juntaplay/groups/complaint_created', [$this, 'on_complaint_created'], 10, 4);
        add_action('juntaplay/groups/complaint_reply', [$this, 'on_complaint_reply'], 10, 4);
        add_action('juntaplay/groups/complaint_proposal', [$this, 'on_complaint_proposal'], 10, 4);
        add_action('juntaplay/groups/complaint_resolved', [$this, 'on_complaint_resolved'], 10, 4);
        add_action('juntaplay/profile/groups/updated', [$this, 'on_group_updated'], 10, 4);
        add_action('juntaplay/group_members/added', [$this, 'on_member_added'], 10, 4);
        add_action('juntaplay/group_members/status_changed', [$this, 'on_member_status_changed'], 10, 5);
        add_action('juntaplay/groups/order_purchase_email', [$this, 'on_order_purchase_email'], 10, 3);
        add_action('juntaplay/groups/send_access', [$this, 'on_send_access'], 10, 4);
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
        $category_labels = GroupsData::get_category_labels();

        $category_label = $category !== '' && isset($category_labels[$category])
            ? (string) $category_labels[$category]
            : ($category !== '' ? ucwords(str_replace(['-', '_'], ' ', $category)) : __('Outros serviços', 'juntaplay'));
        $instant_text = $instant ? __('Ativado', 'juntaplay') : __('Desativado', 'juntaplay');
        $promo_flag   = ($promo_value !== null && $promo_value > 0) ? __('Sim', 'juntaplay') : __('Não', 'juntaplay');

        if ($admin_email !== '') {
            $review_url  = esc_url(admin_url('admin.php?page=juntaplay-groups&status=pending'));
            $admin_lines = [];

            if ($service !== '') {
                $admin_lines[] = sprintf(__('Serviço: %s', 'juntaplay'), $service);
            }
            if ($service_url !== '') {
                $admin_lines[] = sprintf(__('Site oficial: %s', 'juntaplay'), $service_url);
            }
            if ($category_label !== '') {
                $admin_lines[] = sprintf(__('Categoria: %s', 'juntaplay'), $category_label);
            }
            if ($price > 0) {
                $admin_lines[] = sprintf(__('Valor do serviço: R$ %s', 'juntaplay'), number_format_i18n($price, 2));
            }
            if ($promo_value !== null) {
                $admin_lines[] = sprintf(__('Valor promocional: R$ %s', 'juntaplay'), number_format_i18n($promo_value, 2));
            }
            if ($member_price > 0) {
                $admin_lines[] = sprintf(__('Cota sugerida por membro: R$ %s', 'juntaplay'), number_format_i18n($member_price, 2));
            }
            if ($slots_total > 0) {
                $admin_lines[] = sprintf(__('Vagas totais/reservadas: %1$d / %2$d', 'juntaplay'), $slots_total, $slots_reserved);
            }
            if ($support !== '') {
                $admin_lines[] = sprintf(__('Suporte a membros: %s', 'juntaplay'), $support);
            }
            if ($delivery !== '') {
                $admin_lines[] = sprintf(__('Entrega do acesso: %s', 'juntaplay'), $delivery);
            }
            if ($access !== '') {
                $admin_lines[] = sprintf(__('Forma de acesso: %s', 'juntaplay'), $access);
            }
            $admin_lines[] = sprintf(__('É valor promocional?: %s', 'juntaplay'), $promo_flag);
            $admin_lines[] = sprintf(__('Acesso instantâneo: %s', 'juntaplay'), $instant_text);

            if (!$admin_lines) {
                $admin_lines[] = __('Nenhum detalhe adicional informado.', 'juntaplay');
            }

            $admin_blocks = [
                [
                    'type'    => 'paragraph',
                    'content' => sprintf(__('Um novo grupo público chamado “%s” precisa ser analisado.', 'juntaplay'), $group_title),
                ],
            ];

            if ($description !== '') {
                $admin_blocks[] = [
                    'type'    => 'paragraph',
                    'content' => sprintf(__('Descrição enviada: %s', 'juntaplay'), $description),
                ];
            }

            $admin_blocks[] = [
                'type'  => 'list',
                'items' => $admin_lines,
            ];

            $admin_blocks[] = [
                'type'  => 'button',
                'label' => __('Revisar no painel', 'juntaplay'),
                'url'   => $review_url,
            ];

            EmailHelper::send(
                $admin_email,
                sprintf(__('Novo grupo aguardando aprovação — %s', 'juntaplay'), $site_name),
                $admin_blocks,
                [
                    'headline'  => __('Grupo aguardando aprovação', 'juntaplay'),
                    'preheader' => sprintf(__('O grupo %s aguarda moderação no JuntaPlay.', 'juntaplay'), $group_title),
                ]
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
                $summary_lines[] = sprintf(__('Suporte a membros: %s', 'juntaplay'), $support);
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

            if (!$summary_lines) {
                $summary_lines[] = __('Nenhum detalhe adicional informado.', 'juntaplay');
            }

            $user_blocks = [
                [
                    'type'    => 'paragraph',
                    'content' => sprintf(__('Olá %s, recebemos o seu grupo público “%s”.', 'juntaplay'), $user->display_name ?: $user->user_login, $group_title),
                ],
                [
                    'type'    => 'paragraph',
                    'content' => __('Ele está em análise e avisaremos assim que for aprovado.', 'juntaplay'),
                ],
                ['type' => 'divider'],
                [
                    'type'    => 'heading',
                    'content' => __('Resumo cadastrado', 'juntaplay'),
                ],
                [
                    'type'  => 'list',
                    'items' => $summary_lines,
                ],
            ];

            EmailHelper::send(
                (string) $user->user_email,
                sprintf(__('Resumo do seu novo grupo — %s', 'juntaplay'), $site_name),
                $user_blocks,
                [
                    'headline'  => __('Seu grupo está em análise', 'juntaplay'),
                    'preheader' => sprintf(__('O grupo %s está aguardando aprovação.', 'juntaplay'), $group_title),
                ]
            );

            if ($validation_code !== '') {
                EmailHelper::send(
                    (string) $user->user_email,
                    sprintf(__('Código de validação de e-mail — %s', 'juntaplay'), $site_name),
                    [
                        [
                            'type'    => 'paragraph',
                            'content' => sprintf(__('Use o código abaixo para validar o e-mail associado ao grupo “%s”.', 'juntaplay'), $group_title),
                        ],
                        [
                            'type'    => 'code',
                            'content' => $validation_code,
                        ],
                        [
                            'type'    => 'paragraph',
                            'content' => __('Informe este código apenas dentro do painel do JuntaPlay. Caso você não tenha solicitado a criação deste grupo, ignore esta mensagem.', 'juntaplay'),
                        ],
                    ],
                    [
                        'headline'  => __('Confirme o e-mail do grupo', 'juntaplay'),
                        'preheader' => __('Seu código de validação expira em alguns minutos.', 'juntaplay'),
                    ]
                );
            }
        }

        NotificationsData::add($user_id, [
            'type'       => 'group',
            'title'      => __('Grupo enviado para análise', 'juntaplay'),
            'message'    => sprintf(__('Recebemos seu grupo “%s”. Avisaremos quando for aprovado.', 'juntaplay'), $group_title),
            'action_url' => $this->get_my_groups_url(),
        ]);
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
            $admin_blocks = [
                [
                    'type'    => 'paragraph',
                    'content' => sprintf(__('Nova reclamação #%1$d registrada para o grupo “%2$s”.', 'juntaplay'), $complaint_id, $group_title),
                ],
                [
                    'type'  => 'list',
                    'items' => $admin_lines,
                ],
            ];

            if ($message !== '') {
                $admin_blocks[] = [
                    'type'    => 'paragraph',
                    'content' => sprintf(__('Descrição enviada: %s', 'juntaplay'), $message),
                ];
            } else {
                $admin_blocks[] = [
                    'type'    => 'paragraph',
                    'content' => __('O cliente não adicionou detalhes adicionais.', 'juntaplay'),
                ];
            }

            $admin_blocks[] = [
                'type'  => 'button',
                'label' => __('Abrir reclamação no painel', 'juntaplay'),
                'url'   => esc_url(admin_url('admin.php?page=juntaplay-groups')),
            ];

            EmailHelper::send(
                $admin_email,
                sprintf(__('Reclamação #%1$d registrada — %2$s', 'juntaplay'), $complaint_id, $site_name),
                $admin_blocks,
                [
                    'headline'  => __('Nova reclamação de grupo', 'juntaplay'),
                    'preheader' => sprintf(__('O grupo %s recebeu uma nova reclamação.', 'juntaplay'), $group_title),
                ]
            );
        }

        if ($user_email !== '') {
            $user_summary = [
                sprintf(__('Grupo: %s', 'juntaplay'), $group_title),
                sprintf(__('Motivo selecionado: %s', 'juntaplay'), $reason_label),
            ];

            if ($order_id > 0) {
                $user_summary[] = sprintf(__('Pedido relacionado: #%d', 'juntaplay'), $order_id);
            }

            $user_blocks = [
                [
                    'type'    => 'paragraph',
                    'content' => sprintf(__('Olá %s, recebemos sua reclamação.', 'juntaplay'), $user_name),
                ],
                [
                    'type'  => 'list',
                    'items' => $user_summary,
                ],
            ];

            if ($message !== '') {
                $user_blocks[] = [
                    'type'    => 'paragraph',
                    'content' => sprintf(__('Descrição enviada: %s', 'juntaplay'), $message),
                ];
            }

            $user_blocks[] = [
                'type'    => 'paragraph',
                'content' => __('Nossa equipe e o administrador foram notificados e responderão em breve.', 'juntaplay'),
            ];

            $user_blocks[] = [
                'type'    => 'paragraph',
                'content' => __('Você pode acompanhar o andamento acessando o seu painel em Minha Conta > Meus Grupos.', 'juntaplay'),
            ];

            EmailHelper::send(
                $user_email,
                sprintf(__('Recebemos sua reclamação — %s', 'juntaplay'), $site_name),
                $user_blocks,
                [
                    'headline'  => __('Estamos analisando sua reclamação', 'juntaplay'),
                    'preheader' => sprintf(__('Estamos avaliando sua solicitação sobre o grupo %s.', 'juntaplay'), $group_title),
                ]
            );
        }

        $owner_id      = $group && isset($group->owner_id) ? (int) $group->owner_id : 0;
        $ticket_number = GroupComplaintsData::get_ticket_number($complaint_id);

        if ($owner_id > 0 && $owner_id !== $user_id) {
            $owner_message = sprintf(__('%1$s registrou a reclamação #%2$d no grupo “%3$s”.', 'juntaplay'), $user_name, $complaint_id, $group_title);

            NotificationsData::add($owner_id, [
                'type'       => 'group',
                'title'      => __('Nova reclamação recebida', 'juntaplay'),
                'message'    => $owner_message,
                'action_url' => $this->get_complaints_url([
                    'jp_ticket' => $ticket_number,
                ]),
            ]);
        }

        if ($user_id > 0) {
            NotificationsData::add($user_id, [
                'type'       => 'group',
                'title'      => __('Reclamação registrada', 'juntaplay'),
                'message'    => sprintf(__('Recebemos a reclamação #%1$d sobre o grupo “%2$s”. Acompanhe as próximas atualizações pelo painel.', 'juntaplay'), $complaint_id, $group_title),
                'action_url' => $this->get_complaints_url([
                    'jp_ticket' => $ticket_number,
                ]),
            ]);
        }
    }

    /**
     * @param array<string, mixed> $context
     */
    public function on_complaint_reply(int $complaint_id, int $group_id, int $user_id, array $context): void
    {
        $complaint = GroupComplaintsData::get($complaint_id);
        if (!$complaint) {
            return;
        }

        $ticket      = GroupComplaintsData::get_ticket_number($complaint_id);
        $group_title = isset($complaint['group_title']) && $complaint['group_title'] !== ''
            ? (string) $complaint['group_title']
            : __('Grupo', 'juntaplay');

        $complaint_owner = isset($complaint['user_id']) ? (int) $complaint['user_id'] : 0;
        $group_owner     = isset($complaint['owner_id']) ? (int) $complaint['owner_id'] : 0;
        $role            = isset($context['role']) ? (string) $context['role'] : '';

        $recipients = [];

        if ($role === 'owner') {
            if ($complaint_owner > 0 && $complaint_owner !== $user_id) {
                $recipients[] = $complaint_owner;
            }
        } elseif ($role === 'participant') {
            if ($group_owner > 0 && $group_owner !== $user_id) {
                $recipients[] = $group_owner;
            }
        } else {
            if ($group_owner > 0 && $group_owner !== $user_id) {
                $recipients[] = $group_owner;
            }
            if ($complaint_owner > 0 && $complaint_owner !== $user_id) {
                $recipients[] = $complaint_owner;
            }
        }

        if (!$recipients) {
            return;
        }

        $message  = isset($context['message']) ? (string) $context['message'] : '';
        $excerpt  = $this->summarize_message($message);
        $action   = $this->get_complaints_url(['jp_ticket' => $ticket]);
        $fallback = sprintf(__('O ticket %s recebeu uma nova atualização.', 'juntaplay'), $ticket);

        foreach (array_unique($recipients) as $recipient) {
            NotificationsData::add((int) $recipient, [
                'type'       => 'complaint',
                'title'      => sprintf(__('Nova mensagem no ticket %s', 'juntaplay'), $ticket),
                'message'    => $excerpt !== '' ? $excerpt : $fallback,
                'action_url' => $action,
            ]);
        }
    }

    /**
     * @param array<string, mixed> $context
     */
    public function on_complaint_proposal(int $complaint_id, int $group_id, int $user_id, array $context): void
    {
        $complaint = GroupComplaintsData::get($complaint_id);
        if (!$complaint) {
            return;
        }

        $ticket      = GroupComplaintsData::get_ticket_number($complaint_id);
        $group_title = isset($complaint['group_title']) && $complaint['group_title'] !== ''
            ? (string) $complaint['group_title']
            : __('Grupo', 'juntaplay');

        $complaint_owner = isset($complaint['user_id']) ? (int) $complaint['user_id'] : 0;
        $group_owner     = isset($complaint['owner_id']) ? (int) $complaint['owner_id'] : 0;
        $role            = isset($context['role']) ? (string) $context['role'] : '';

        $recipient = $role === 'participant' ? $group_owner : $complaint_owner;

        if ($recipient <= 0 || $recipient === $user_id) {
            return;
        }

        $message  = isset($context['message']) ? (string) $context['message'] : '';
        $excerpt  = $this->summarize_message($message);
        $action   = $this->get_complaints_url(['jp_ticket' => $ticket]);
        $fallback = sprintf(__('Uma proposta foi registrada no ticket %s.', 'juntaplay'), $ticket);

        NotificationsData::add((int) $recipient, [
            'type'       => 'complaint',
            'title'      => sprintf(__('Nova proposta no ticket %s', 'juntaplay'), $ticket),
            'message'    => $excerpt !== '' ? $excerpt : $fallback,
            'action_url' => $action,
        ]);
    }

    /**
     * @param array<string, mixed> $context
     */
    public function on_complaint_resolved(int $complaint_id, int $group_id, int $user_id, array $context): void
    {
        $complaint = GroupComplaintsData::get($complaint_id);
        if (!$complaint) {
            return;
        }

        $ticket      = GroupComplaintsData::get_ticket_number($complaint_id);
        $complaint_owner = isset($complaint['user_id']) ? (int) $complaint['user_id'] : 0;
        $group_owner     = isset($complaint['owner_id']) ? (int) $complaint['owner_id'] : 0;

        $recipients = [];

        if ($complaint_owner > 0 && $complaint_owner !== $user_id) {
            $recipients[] = $complaint_owner;
        }

        if ($group_owner > 0 && $group_owner !== $user_id) {
            $recipients[] = $group_owner;
        }

        if (!$recipients) {
            return;
        }

        $note       = isset($context['note']) ? (string) $context['note'] : '';
        $note_text  = $this->summarize_message($note);
        $actor      = get_userdata($user_id);
        $actor_name = $actor ? ($actor->display_name ?: $actor->user_login) : __('Participante', 'juntaplay');

        $action  = $this->get_complaints_url(['jp_ticket' => $ticket]);
        $message = $note_text !== ''
            ? sprintf(__('Acordo registrado por %1$s: %2$s', 'juntaplay'), $actor_name, $note_text)
            : sprintf(__('Proposta aceita por %s. Ticket finalizado.', 'juntaplay'), $actor_name);

        foreach (array_unique($recipients) as $recipient) {
            NotificationsData::add((int) $recipient, [
                'type'       => 'complaint',
                'title'      => sprintf(__('Ticket %s concluído', 'juntaplay'), $ticket),
                'message'    => $message,
                'action_url' => $action,
            ]);
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

        $profile_url     = '';
        $profile_page_id = (int) get_option('juntaplay_page_perfil');
        if ($profile_page_id > 0) {
            $possible_url = get_permalink($profile_page_id);
            if ($possible_url) {
                $profile_url = (string) $possible_url;
            }
        }

        $headline  = '';
        $preheader = '';
        $blocks    = [];
        $notification_message = '';

        switch ($new_status) {
            case GroupsData::STATUS_APPROVED:
                $headline  = sprintf(__('Seu grupo “%s” foi aprovado!', 'juntaplay'), $group_title);
                $preheader = __('O grupo foi liberado para convidar participantes.', 'juntaplay');
                $blocks    = [
                    [
                        'type'    => 'paragraph',
                        'content' => sprintf(__('Parabéns! O grupo “%s” foi aprovado e já está disponível para convidar participantes.', 'juntaplay'), $group_title),
                    ],
                    [
                        'type'    => 'paragraph',
                        'content' => __('Acesse o painel do JuntaPlay para gerenciar convites e acompanhar as cotas.', 'juntaplay'),
                    ],
                ];
                $notification_message = __('Convide participantes e mantenha o grupo atualizado para garantir o melhor desempenho.', 'juntaplay');
                if ($profile_url !== '') {
                    $blocks[] = [
                        'type'  => 'button',
                        'label' => __('Gerenciar meu grupo', 'juntaplay'),
                        'url'   => esc_url($profile_url),
                    ];
                }
                break;
            case GroupsData::STATUS_REJECTED:
                $headline  = sprintf(__('Seu grupo “%s” foi recusado', 'juntaplay'), $group_title);
                $preheader = __('Temos orientações para ajustar seu cadastro.', 'juntaplay');
                $blocks    = [
                    [
                        'type'    => 'paragraph',
                        'content' => sprintf(__('O grupo “%s” não foi aprovado neste momento.', 'juntaplay'), $group_title),
                    ],
                    [
                        'type'    => 'paragraph',
                        'content' => $note !== '' ? sprintf(__('Motivo informado: %s', 'juntaplay'), $note) : __('Entre em contato com o suporte para mais detalhes.', 'juntaplay'),
                    ],
                ];
                $notification_message = $note !== ''
                    ? sprintf(__('Motivo informado pela moderação: %s', 'juntaplay'), $note)
                    : __('Entre em contato com o suporte para ajustar o cadastro do grupo.', 'juntaplay');
                break;
            case GroupsData::STATUS_ARCHIVED:
                $headline  = sprintf(__('Seu grupo “%s” foi arquivado', 'juntaplay'), $group_title);
                $preheader = __('Nenhuma nova cota poderá ser reservada até que ele seja reativado.', 'juntaplay');
                $blocks    = [
                    [
                        'type'    => 'paragraph',
                        'content' => sprintf(__('O grupo “%s” foi arquivado pelo super administrador.', 'juntaplay'), $group_title),
                    ],
                    [
                        'type'    => 'paragraph',
                        'content' => __('Nenhuma nova cota poderá ser reservada até que ele seja reativado.', 'juntaplay'),
                    ],
                ];
                $notification_message = __('O grupo está arquivado e não pode receber novos participantes até nova liberação.', 'juntaplay');
                if ($note !== '') {
                    $blocks[] = [
                        'type'    => 'paragraph',
                        'content' => sprintf(__('Observação: %s', 'juntaplay'), $note),
                    ];
                }
                break;
            case GroupsData::STATUS_PENDING:
                $headline  = sprintf(__('O grupo “%s” voltou para análise', 'juntaplay'), $group_title);
                $preheader = __('Estamos revisando novamente o cadastro.', 'juntaplay');
                $blocks    = [
                    [
                        'type'    => 'paragraph',
                        'content' => __('Atualizamos o status do seu grupo para análise novamente. Em breve entraremos em contato.', 'juntaplay'),
                    ],
                ];
                $notification_message = __('O grupo voltou para análise. Acompanhe sua caixa de entrada para novas orientações.', 'juntaplay');
                break;
            default:
                return;
        }

        if (!$blocks) {
            return;
        }

        EmailHelper::send(
            (string) $owner->user_email,
            sprintf('%s — %s', $headline, $site_name),
            $blocks,
            [
                'headline'  => $headline,
                'preheader' => $preheader,
            ]
        );

        if ($notification_message !== '') {
            NotificationsData::add((int) $owner->ID, [
                'type'       => 'group',
                'title'      => $headline,
                'message'    => $notification_message,
                'action_url' => $this->get_my_groups_url(),
            ]);
        }
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $previous
     */
    public function on_group_updated(int $user_id, int $group_id, array $data, array $previous = []): void
    {
        $group = GroupsData::get($group_id);
        if (!$group) {
            return;
        }

        $changes = $this->describe_group_changes($data, $previous, $group);

        $current_credentials  = $this->extract_credentials($group);
        $previous_credentials = $this->extract_credentials($previous);
        $credentials_updated  = $this->credentials_changed($current_credentials, $previous_credentials);

        if (!$changes && !$credentials_updated) {
            return;
        }

        $actor      = get_userdata($user_id);
        $actor_name = $actor ? ($actor->display_name ?: $actor->user_login) : __('Administrador do grupo', 'juntaplay');
        $group_title = isset($group->title) ? (string) $group->title : __('Grupo', 'juntaplay');

        $message = sprintf(
            __('%1$s atualizou o grupo “%2$s”: %3$s', 'juntaplay'),
            $actor_name,
            $group_title,
            implode(' • ', $changes)
        );

        $members = GroupMembersData::get_user_ids($group_id, 'active');
        if ($members) {
            $recipients = array_diff(array_map('intval', $members), [$user_id]);

            if ($recipients) {
                $title = sprintf(__('Atualizações no grupo “%s”', 'juntaplay'), $group_title);
                $url   = $this->get_my_groups_url();

                foreach ($recipients as $recipient) {
                    NotificationsData::add($recipient, [
                        'type'       => 'group',
                        'title'      => $title,
                        'message'    => $message,
                        'action_url' => $url,
                    ]);
                }
            }
        }

        if ($credentials_updated && $members) {
            foreach ($members as $member_id) {
                $member_id = (int) $member_id;

                if ($member_id <= 0 || $member_id === $user_id) {
                    continue;
                }

                do_action('juntaplay/groups/send_access', $group_id, $member_id, $current_credentials, [
                    'source'   => 'manager_update',
                    'actor_id' => $user_id,
                ]);
            }
        }
    }

    public function on_member_added(int $group_id, int $user_id, string $role, string $status): void
    {
        if ($status !== 'active' || $role === 'owner') {
            return;
        }

        $group = GroupsData::get($group_id);
        if (!$group) {
            return;
        }

        $group_title = isset($group->title) ? (string) $group->title : __('Grupo', 'juntaplay');
        $member      = get_userdata($user_id);
        $member_name = $member ? ($member->display_name ?: $member->user_login) : __('Novo participante', 'juntaplay');
        $url         = $this->get_my_groups_url();

        NotificationsData::add($user_id, [
            'type'       => 'group',
            'title'      => sprintf(__('Você entrou no grupo “%s”', 'juntaplay'), $group_title),
            'message'    => __('Agora você pode acompanhar as instruções do administrador na área Meus Grupos.', 'juntaplay'),
            'action_url' => $url,
        ]);

        $members = GroupMembersData::get_user_ids($group_id, 'active');
        if ($members) {
            $recipients = array_diff(array_map('intval', $members), [$user_id]);

            if ($recipients) {
                $message = sprintf(__('%1$s entrou no grupo “%2$s”.', 'juntaplay'), $member_name, $group_title);

                foreach ($recipients as $recipient) {
                    NotificationsData::add($recipient, [
                        'type'       => 'group',
                        'title'      => __('Novo participante no seu grupo', 'juntaplay'),
                        'message'    => $message,
                        'action_url' => $url,
                    ]);
                }
            }
        }
    }

    public function on_member_status_changed(int $group_id, int $user_id, string $old_status, string $new_status, string $role): void
    {
        if ($new_status === 'active' && $old_status !== 'active') {
            $this->on_member_added($group_id, $user_id, $role, $new_status);
        }
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $previous
     * @return string[]
     */
    private function describe_group_changes(array $data, array $previous, $current): array
    {
        $changes = [];

        $support = trim((string) ($data['support_channel'] ?? ''));
        if ($support !== '' && $support !== trim((string) ($previous['support_channel'] ?? ''))) {
            $changes[] = sprintf(__('canal de suporte atualizado para %s', 'juntaplay'), $support);
        }

        $delivery = trim((string) ($data['delivery_time'] ?? ''));
        if ($delivery !== '' && $delivery !== trim((string) ($previous['delivery_time'] ?? ''))) {
            $changes[] = sprintf(__('prazo de entrega agora é %s', 'juntaplay'), $delivery);
        }

        $access = trim((string) ($data['access_method'] ?? ''));
        if ($access !== '' && $access !== trim((string) ($previous['access_method'] ?? ''))) {
            $changes[] = sprintf(__('acesso será entregue por %s', 'juntaplay'), $access);
        }

        $service = trim((string) ($data['service_name'] ?? ''));
        if ($service !== '' && $service !== trim((string) ($previous['service_name'] ?? ''))) {
            $changes[] = sprintf(__('serviço atualizado para %s', 'juntaplay'), $service);
        }

        $service_url = trim((string) ($data['service_url'] ?? ''));
        if ($service_url !== '' && $service_url !== trim((string) ($previous['service_url'] ?? ''))) {
            $changes[] = __('link oficial foi atualizado', 'juntaplay');
        }

        if (isset($data['member_price'])) {
            $new_price = (float) $data['member_price'];
            $old_price = isset($previous['member_price']) ? (float) $previous['member_price'] : null;

            if ($new_price > 0 && $new_price !== $old_price) {
                $changes[] = sprintf(__('mensalidade por participante: %s', 'juntaplay'), $this->format_currency($new_price));
            }
        }

        $rules = trim((string) ($data['rules'] ?? ''));
        if ($rules !== '' && $rules !== trim((string) ($previous['rules'] ?? ''))) {
            $changes[] = __('regras do grupo foram revisadas', 'juntaplay');
        }

        if (isset($data['instant_access'])) {
            $new_instant = (bool) $data['instant_access'];
            $old_instant = !empty($previous['instant_access']);

            if ($new_instant !== $old_instant) {
                $changes[] = $new_instant
                    ? __('acesso instantâneo ativado', 'juntaplay')
                    : __('acesso instantâneo desativado', 'juntaplay');
            }
        }

        $current_credentials  = $this->extract_credentials($current);
        $previous_credentials = $this->extract_credentials($previous);

        if ($this->credentials_changed($current_credentials, $previous_credentials)) {
            $changes[] = __('dados de acesso atualizados', 'juntaplay');
        }

        return array_slice($changes, 0, 5);
    }

    private function get_my_groups_url(): string
    {
        $page_id = (int) get_option('juntaplay_page_meus-grupos');
        $url     = $page_id ? get_permalink($page_id) : '';

        if (!$url) {
            $url = home_url('/meus-grupos');
        }

        return (string) apply_filters('juntaplay/groups/my_groups_url', $url, $page_id);
    }

    private function get_complaints_url(array $args = []): string
    {
        $page_id = (int) get_option('juntaplay_page_perfil');
        $base    = $page_id ? get_permalink($page_id) : '';

        if (!$base) {
            $base = home_url('/perfil');
        }

        if (!$base) {
            $base = home_url('/');
        }

        $url = add_query_arg([
            'jp_category' => 'support',
            'jp_tab'      => 'support_complaints',
        ], $base);

        if ($args) {
            $url = add_query_arg($args, $url);
        }

        return $url;
    }

    private function summarize_message(string $message): string
    {
        $clean = trim(wp_strip_all_tags($message));

        if ($clean === '') {
            return '';
        }

        return wp_trim_words($clean, 18, '…');
    }

    private function format_currency(float $amount): string
    {
        return 'R$ ' . number_format_i18n($amount, 2);
    }

    /**
     * @param array<int, array<string, mixed>> $items
     * @param array<string, mixed> $totals
     */
    public function on_order_purchase_email($order, array $items, array $totals): void
    {
        if (!$order instanceof WC_Order) {
            $order = wc_get_order($order);
        }

        if (!$order instanceof WC_Order) {
            return;
        }

        $user_id = (int) $order->get_user_id();

        if ($user_id <= 0) {
            return;
        }

        $user = get_userdata($user_id);

        if (!$user || empty($user->user_email)) {
            return;
        }

        $site_name = get_bloginfo('name');

        $customer_first = trim((string) $order->get_billing_first_name());
        if ($customer_first === '') {
            $customer_first = trim((string) $order->get_shipping_first_name());
        }

        $overview_lines = [
            sprintf(__('Número da assinatura: #%s', 'juntaplay'), $order->get_order_number()),
        ];

        $created = $order->get_date_created();
        if ($created) {
            $overview_lines[] = sprintf(
                __('Data: %s', 'juntaplay'),
                wc_format_datetime($created, wc_date_format())
            );
        }

        $payment_method = $order->get_payment_method_title();
        if ($payment_method) {
            $overview_lines[] = sprintf(__('Forma de pagamento: %s', 'juntaplay'), $payment_method);
        }

        $group_lines = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $title    = isset($item['title']) ? (string) $item['title'] : __('Grupo', 'juntaplay');
            $quantity = isset($item['quantity']) ? max(1, (int) $item['quantity']) : 1;

            $unit_price = isset($item['subscription_price']) ? (float) $item['subscription_price'] : 0.0;
            $deposit    = isset($item['deposit']) ? (float) $item['deposit'] : 0.0;
            $processing = isset($item['processing_fee']) ? (float) $item['processing_fee'] : 0.0;
            $wallet     = isset($item['wallet_used']) ? (float) $item['wallet_used'] : 0.0;
            $line_total = isset($item['total']) ? (float) $item['total'] : max(0.0, $unit_price * $quantity + $deposit + $processing - $wallet);

            $parts = [sprintf(__('assinatura %s', 'juntaplay'), $this->format_currency($unit_price))];

            if ($deposit > 0) {
                $parts[] = sprintf(__('caução %s', 'juntaplay'), $this->format_currency($deposit));
            }

            if ($processing > 0) {
                $parts[] = sprintf(__('taxas %s', 'juntaplay'), $this->format_currency($processing));
            }

            if ($wallet > 0) {
                $parts[] = sprintf(__('créditos -%s', 'juntaplay'), $this->format_currency($wallet));
            }

            $parts[] = sprintf(__('total %s', 'juntaplay'), $this->format_currency($line_total));

            $group_lines[] = sprintf(
                /* translators: 1: group title, 2: quantity, 3: payment details */
                __('%1$s — %2$d cotas (%3$s)', 'juntaplay'),
                $title,
                $quantity,
                implode(', ', $parts)
            );
        }

        $subscription_total = isset($totals['subscription_total']) ? (float) $totals['subscription_total'] : 0.0;
        $deposit_total      = isset($totals['deposit_total']) ? (float) $totals['deposit_total'] : 0.0;
        $processing_total   = isset($totals['processing_fee']) ? (float) $totals['processing_fee'] : 0.0;
        $wallet_used        = isset($totals['wallet_used']) ? (float) $totals['wallet_used'] : 0.0;
        $grand_total        = isset($totals['grand_total']) ? (float) $totals['grand_total'] : max(0.0, $subscription_total + $deposit_total + $processing_total - $wallet_used);

        $totals_lines = [];
        if ($subscription_total > 0) {
            $totals_lines[] = sprintf(__('Assinaturas: %s', 'juntaplay'), $this->format_currency($subscription_total));
        }

        if ($deposit_total > 0) {
            $totals_lines[] = sprintf(__('Caução: %s', 'juntaplay'), $this->format_currency($deposit_total));
        }

        if ($processing_total > 0) {
            $totals_lines[] = sprintf(__('Custos de processamento: %s', 'juntaplay'), $this->format_currency($processing_total));
        }

        if ($wallet_used > 0) {
            $totals_lines[] = sprintf(__('Créditos utilizados: -%s', 'juntaplay'), $this->format_currency($wallet_used));
        }

        $totals_lines[] = sprintf(__('Total pago: %s', 'juntaplay'), $this->format_currency(max(0.0, $grand_total)));

        $greeting = $customer_first !== ''
            ? sprintf(__('Olá, %s! Recebemos o pagamento da sua assinatura no JuntaPlay.', 'juntaplay'), $customer_first)
            : __('Recebemos o pagamento da sua assinatura no JuntaPlay.', 'juntaplay');

        $blocks = [
            [
                'type'    => 'paragraph',
                'content' => $greeting,
            ],
            [
                'type'    => 'paragraph',
                'content' => __('Agora é só aguardar: o administrador do grupo vai compartilhar os dados de acesso por e-mail e na área “Meus grupos”.', 'juntaplay'),
            ],
        ];

        if ($overview_lines) {
            $blocks[] = [
                'type'  => 'list',
                'items' => $overview_lines,
            ];
        }

        if ($group_lines) {
            $blocks[] = [
                'type'    => 'heading',
                'content' => __('Grupos incluídos neste pedido', 'juntaplay'),
            ];
            $blocks[] = [
                'type'  => 'list',
                'items' => $group_lines,
            ];
        }

        if ($totals_lines) {
            $blocks[] = [
                'type'    => 'heading',
                'content' => __('Resumo financeiro', 'juntaplay'),
            ];
            $blocks[] = [
                'type'  => 'list',
                'items' => $totals_lines,
            ];
        }

        $blocks[] = [
            'type'    => 'paragraph',
            'content' => __('Quando o administrador liberar o acesso você receberá outro e-mail automático.', 'juntaplay'),
        ];

        $blocks[] = [
            'type'  => 'button',
            'label' => __('Acessar Meus Grupos', 'juntaplay'),
            'url'   => $this->get_my_groups_url(),
        ];

        EmailHelper::send(
            $user->user_email,
            sprintf(__('Confirmação da compra — %s', 'juntaplay'), $site_name),
            $blocks,
            [
                'headline'  => __('Assinatura confirmada!', 'juntaplay'),
                'preheader' => __('Pagamento aprovado e assinatura liberada.', 'juntaplay'),
            ]
        );
    }

    /**
     * @param array<string, mixed> $credentials
     * @param array<string, mixed> $context
     */
    public function on_send_access(int $group_id, int $user_id, array $credentials, array $context = []): void
    {
        $user = get_userdata($user_id);

        if (!$user || empty($user->user_email)) {
            return;
        }

        $group = GroupsData::get($group_id);

        if (!$group) {
            return;
        }

        $group_title = isset($group->title) ? (string) $group->title : __('Grupo', 'juntaplay');

        $credential_values = $this->extract_credentials($credentials);

        if (!$this->credentials_available($credential_values)) {
            return;
        }

        $access_url   = $credential_values['access_url'];
        $access_login = $credential_values['access_login'];
        $access_pass  = $credential_values['access_password'];
        $access_notes = $credential_values['access_notes'];
        $access_obs   = $credential_values['access_observations'];

        $credential_lines = [];

        if ($access_url !== '') {
            $credential_lines[] = sprintf(__('Endereço: %s', 'juntaplay'), $access_url);
        }

        if ($access_login !== '') {
            $credential_lines[] = sprintf(__('Login: %s', 'juntaplay'), $access_login);
        }

        if ($access_pass !== '') {
            $credential_lines[] = sprintf(__('Senha: %s', 'juntaplay'), $access_pass);
        }

        $source = isset($context['source']) ? (string) $context['source'] : '';

        switch ($source) {
            case 'manager_update':
                $intro = sprintf(
                    __('O administrador atualizou os dados de acesso do grupo %s.', 'juntaplay'),
                    $group_title
                );
                $preheader = __('O administrador compartilhou novos dados de acesso.', 'juntaplay');
                break;
            case 'profile':
                $intro = sprintf(
                    __('Conforme solicitado, aqui estão novamente os dados de acesso do grupo %s.', 'juntaplay'),
                    $group_title
                );
                $preheader = __('Reenvio solicitado pela área do assinante.', 'juntaplay');
                break;
            default:
                $intro = sprintf(
                    __('Aqui estão os dados de acesso do grupo %s.', 'juntaplay'),
                    $group_title
                );
                $preheader = __('Enviamos automaticamente as credenciais do seu novo grupo.', 'juntaplay');
                break;
        }

        $blocks = [
            [
                'type'    => 'paragraph',
                'content' => $intro,
            ],
        ];

        if ($credential_lines) {
            $blocks[] = [
                'type'  => 'list',
                'items' => $credential_lines,
            ];
        }

        if ($access_notes !== '') {
            $blocks[] = [
                'type'    => 'paragraph',
                'content' => sprintf(__('Observações: %s', 'juntaplay'), $access_notes),
            ];
        }

        if ($access_obs !== '') {
            $blocks[] = [
                'type'    => 'paragraph',
                'content' => sprintf(__('Notas adicionais: %s', 'juntaplay'), $access_obs),
            ];
        }

        $blocks[] = [
            'type'    => 'paragraph',
            'content' => __('Guarde essas informações e, se algo estiver incorreto, fale com o administrador do grupo.', 'juntaplay'),
        ];

        if ($access_url !== '') {
            $blocks[] = [
                'type'  => 'button',
                'label' => __('Acessar agora', 'juntaplay'),
                'url'   => $access_url,
            ];
        }

        $blocks[] = [
            'type'  => 'button',
            'label' => __('Ver meus grupos', 'juntaplay'),
            'url'   => $this->get_my_groups_url(),
        ];

        EmailHelper::send(
            $user->user_email,
            sprintf(__('Dados de acesso do grupo — %s', 'juntaplay'), get_bloginfo('name')),
            $blocks,
            [
                'headline'  => sprintf(__('Acesso ao grupo %s', 'juntaplay'), $group_title),
                'preheader' => $preheader,
            ]
        );
    }

    /**
     * @param array<string, mixed>|object|null $source
     * @return array<string, string>
     */
    private function extract_credentials($source): array
    {
        $keys = ['access_url', 'access_login', 'access_password', 'access_notes', 'access_observations'];
        $credentials = [];

        foreach ($keys as $key) {
            $value = '';

            if (is_array($source) && isset($source[$key])) {
                $value = (string) $source[$key];
            } elseif (is_object($source) && isset($source->{$key})) {
                $value = (string) $source->{$key};
            }

            $credentials[$key] = trim($value);
        }

        return $credentials;
    }

    /**
     * @param array<string, string> $credentials
     */
    private function credentials_available(array $credentials): bool
    {
        foreach ($credentials as $value) {
            if ($value !== '') {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, string> $current
     * @param array<string, string> $previous
     */
    private function credentials_changed(array $current, array $previous): bool
    {
        if (!$this->credentials_available($current)) {
            return false;
        }

        foreach ($current as $key => $value) {
            if ($value !== ($previous[$key] ?? '')) {
                return true;
            }
        }

        return false;
    }
}
