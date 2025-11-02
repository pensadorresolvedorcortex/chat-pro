<?php
/**
 * Complaint center detail template.
 */

declare(strict_types=1);

use JuntaPlay\Data\GroupComplaintMessages;

if (!defined('ABSPATH')) {
    exit;
}

$context = isset($complaint_detail_context) && is_array($complaint_detail_context) ? $complaint_detail_context : [];

$active_ticket   = isset($context['active_ticket']) ? (string) $context['active_ticket'] : '';
$detail          = isset($context['detail']) && is_array($context['detail']) ? $context['detail'] : null;
$detail_role     = isset($context['detail_role']) ? (string) $context['detail_role'] : 'viewer';
$global_errors   = isset($context['global_errors']) && is_array($context['global_errors']) ? $context['global_errors'] : [];
$current_errors  = isset($context['current_errors']) && is_array($context['current_errors']) ? $context['current_errors'] : [];
$current_success = isset($context['current_success']) && is_array($context['current_success']) ? $context['current_success'] : [];
$messages        = isset($context['messages']) && is_array($context['messages']) ? $context['messages'] : [];
$can_reply       = !empty($context['can_reply']);
$can_propose     = !empty($context['can_propose']);
$can_accept      = !empty($context['can_accept']);
$has_proposal    = !empty($context['has_proposal']);
$latest_proposal = isset($context['latest_proposal']) && is_array($context['latest_proposal']) ? $context['latest_proposal'] : null;
$reply_nonce     = isset($context['reply_nonce']) ? (string) $context['reply_nonce'] : '';
$accept_nonce    = isset($context['accept_nonce']) ? (string) $context['accept_nonce'] : '';
$max_files       = isset($context['max_files']) ? (int) $context['max_files'] : 3;
$max_size_mb     = isset($context['max_size_mb']) ? (float) $context['max_size_mb'] : 5.0;
$base_url        = isset($context['base_url']) ? (string) $context['base_url'] : '';
$empty_image     = isset($context['empty_image']) ? (string) $context['empty_image'] : '';
?>
<section class="juntaplay-complaints__detail" data-complaint-detail>
    <header class="juntaplay-complaints__detail-header">
        <button type="button" class="juntaplay-button juntaplay-button--ghost juntaplay-complaints__back" data-complaint-drawer-toggle>
            <?php esc_html_e('Voltar para a lista', 'juntaplay'); ?>
        </button>
        <div class="juntaplay-complaints__detail-heading">
            <h3><?php echo esc_html($active_ticket !== '' ? $active_ticket : __('Selecione uma reclamação', 'juntaplay')); ?></h3>
            <?php if ($detail && isset($detail['status_label'])) : ?>
                <span class="juntaplay-badge juntaplay-badge--<?php echo esc_attr($detail['status_tone'] ?? 'info'); ?>"><?php echo esc_html((string) $detail['status_label']); ?></span>
            <?php endif; ?>
        </div>
    </header>
    <?php if ($global_errors) : ?>
        <div class="juntaplay-alert juntaplay-alert--danger">
            <ul>
                <?php foreach ($global_errors as $error_message) : ?>
                    <li><?php echo esc_html((string) $error_message); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    <?php if ($detail) : ?>
        <?php
        $detail_id        = isset($detail['id']) ? (int) $detail['id'] : 0;
        $detail_group     = isset($detail['group_title']) ? (string) $detail['group_title'] : '';
        $detail_reason    = isset($detail['reason_label']) ? (string) $detail['reason_label'] : '';
        $detail_summary   = isset($detail['status_message']) ? (string) $detail['status_message'] : '';
        $detail_created   = isset($detail['opened_label']) ? (string) $detail['opened_label'] : '';
        $detail_updated   = isset($detail['updated_label']) ? (string) $detail['updated_label'] : '';
        $detail_order     = isset($detail['order_label']) ? (string) $detail['order_label'] : '';
        $detail_group_id  = isset($detail['group_id']) ? (int) $detail['group_id'] : 0;
        $detail_attachments = isset($detail['attachments_list']) && is_array($detail['attachments_list']) ? $detail['attachments_list'] : [];
        ?>
        <article class="juntaplay-complaints__card">
            <div class="juntaplay-complaints__meta">
                <?php if ($detail_group !== '') : ?>
                    <p><strong><?php esc_html_e('Grupo', 'juntaplay'); ?>:</strong> <?php echo esc_html($detail_group); ?></p>
                <?php endif; ?>
                <?php if ($detail_reason !== '') : ?>
                    <p><strong><?php esc_html_e('Motivo', 'juntaplay'); ?>:</strong> <?php echo esc_html($detail_reason); ?></p>
                <?php endif; ?>
                <?php if ($detail_summary !== '') : ?>
                    <p><strong><?php esc_html_e('Situação', 'juntaplay'); ?>:</strong> <?php echo esc_html($detail_summary); ?></p>
                <?php endif; ?>
                <ul class="juntaplay-complaints__timestamps">
                    <?php if ($detail_created !== '') : ?>
                        <li><?php echo esc_html(sprintf(__('Aberta em %s', 'juntaplay'), $detail_created)); ?></li>
                    <?php endif; ?>
                    <?php if ($detail_updated !== '') : ?>
                        <li><?php echo esc_html(sprintf(__('Atualizada em %s', 'juntaplay'), $detail_updated)); ?></li>
                    <?php endif; ?>
                    <?php if ($detail_order !== '') : ?>
                        <li><?php echo esc_html(sprintf(__('Pedido %s', 'juntaplay'), $detail_order)); ?></li>
                    <?php endif; ?>
                </ul>
            </div>
            <?php if ($detail_attachments) : ?>
                <div class="juntaplay-complaints__attachments">
                    <h4><?php esc_html_e('Anexos iniciais', 'juntaplay'); ?></h4>
                    <ul>
                        <?php foreach ($detail_attachments as $file) :
                            if (!is_array($file)) {
                                continue;
                            }
                            $file_url   = isset($file['url']) ? (string) $file['url'] : '';
                            $file_title = isset($file['title']) ? (string) $file['title'] : '';
                            ?>
                            <li><a href="<?php echo esc_url($file_url); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html($file_title !== '' ? $file_title : basename($file_url)); ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </article>
        <?php if ($current_success) : ?>
            <div class="juntaplay-alert juntaplay-alert--success">
                <ul>
                    <?php foreach ($current_success as $success_message) : ?>
                        <li><?php echo esc_html((string) $success_message); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        <?php if ($current_errors) : ?>
            <div class="juntaplay-alert juntaplay-alert--danger">
                <ul>
                    <?php foreach ($current_errors as $error_message) : ?>
                        <li><?php echo esc_html((string) $error_message); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        <div class="juntaplay-complaints__timeline" data-group-complaint>
            <h4><?php esc_html_e('Histórico de mensagens', 'juntaplay'); ?></h4>
            <?php if ($messages) : ?>
                <ul class="juntaplay-complaints__messages" role="list">
                    <?php foreach ($messages as $message) :
                        if (!is_array($message)) {
                            continue;
                        }
                        $message_role  = isset($message['role']) ? (string) $message['role'] : 'participant';
                        $message_time  = isset($message['time']) ? (string) $message['time'] : '';
                        $message_body  = isset($message['message']) ? (string) $message['message'] : '';
                        $message_type  = isset($message['type']) ? (string) $message['type'] : GroupComplaintMessages::TYPE_MESSAGE;
                        $message_files = isset($message['attachments']) && is_array($message['attachments']) ? $message['attachments'] : [];
                        $message_author = isset($message['author_name']) ? (string) $message['author_name'] : '';
                        ?>
                        <li class="juntaplay-complaints__message juntaplay-complaints__message--<?php echo esc_attr($message_role); ?>">
                            <header>
                                <strong><?php echo esc_html($message_author !== '' ? $message_author : ($message_role === 'owner' ? __('Administrador', 'juntaplay') : ($message_role === 'participant' ? __('Participante', 'juntaplay') : __('Equipe JuntaPlay', 'juntaplay')))); ?></strong>
                                <?php if ($message_time !== '') : ?>
                                    <time datetime="<?php echo esc_attr($message['created_at'] ?? ''); ?>"><?php echo esc_html($message_time); ?></time>
                                <?php endif; ?>
                                <?php if ($message_type === GroupComplaintMessages::TYPE_PROPOSAL) : ?>
                                    <span class="juntaplay-badge juntaplay-badge--accent"><?php esc_html_e('Proposta', 'juntaplay'); ?></span>
                                <?php elseif ($message_type === GroupComplaintMessages::TYPE_SYSTEM) : ?>
                                    <span class="juntaplay-badge juntaplay-badge--info"><?php esc_html_e('Atualização', 'juntaplay'); ?></span>
                                <?php endif; ?>
                            </header>
                            <?php if ($message_body !== '') : ?>
                                <p><?php echo esc_html($message_body); ?></p>
                            <?php endif; ?>
                            <?php if ($message_files) : ?>
                                <ul class="juntaplay-complaints__message-files">
                                    <?php foreach ($message_files as $file) :
                                        if (!is_array($file)) {
                                            continue;
                                        }
                                        $file_url   = isset($file['url']) ? (string) $file['url'] : '';
                                        $file_title = isset($file['title']) ? (string) $file['title'] : '';
                                        ?>
                                        <li><a href="<?php echo esc_url($file_url); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html($file_title !== '' ? $file_title : basename($file_url)); ?></a></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else : ?>
                <p class="juntaplay-complaints__empty-timeline"><?php esc_html_e('Ainda não existem interações registradas neste ticket.', 'juntaplay'); ?></p>
            <?php endif; ?>
            <?php if ($can_reply) : ?>
                <form class="juntaplay-complaints__form" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="jp_profile_action" value="complaint_reply" data-complaint-action />
                    <input type="hidden" name="jp_profile_complaint_id" value="<?php echo esc_attr((string) $detail_id); ?>" />
                    <input type="hidden" name="jp_profile_complaint_group" value="<?php echo esc_attr((string) $detail_group_id); ?>" />
                    <input type="hidden" name="jp_profile_complaint_action_nonce" value="<?php echo esc_attr($reply_nonce); ?>" />
                    <label for="jp-complaint-message"><?php esc_html_e('Escreva sua mensagem', 'juntaplay'); ?></label>
                    <textarea id="jp-complaint-message" name="jp_profile_complaint_message" rows="4" class="juntaplay-form__input" placeholder="<?php esc_attr_e('Detalhe o andamento, negociações ou anexos que deseja compartilhar.', 'juntaplay'); ?>" required></textarea>
                    <label for="jp-complaint-files" class="juntaplay-complaints__upload">
                        <?php esc_html_e('Anexar arquivos (opcional)', 'juntaplay'); ?>
                        <input type="file" id="jp-complaint-files" name="jp_profile_complaint_message_files[]" class="juntaplay-form__input" accept="image/*,.pdf" multiple data-group-complaint-files />
                        <small><?php echo esc_html(sprintf(_n('Até %1$d arquivo de até %2$s MB.', 'Até %1$d arquivos de até %2$s MB cada.', $max_files, 'juntaplay'), $max_files, number_format_i18n($max_size_mb, 1))); ?></small>
                    </label>
                    <ul class="juntaplay-complaints__upload-preview" data-group-complaint-preview></ul>
                    <div class="juntaplay-complaints__form-actions">
                        <button type="submit" class="juntaplay-button juntaplay-button--primary" data-complaint-submit="complaint_reply"><?php esc_html_e('Enviar mensagem', 'juntaplay'); ?></button>
                        <?php if ($can_propose) : ?>
                            <button type="submit" class="juntaplay-button juntaplay-button--accent" data-complaint-submit="complaint_proposal"><?php esc_html_e('Enviar proposta', 'juntaplay'); ?></button>
                        <?php endif; ?>
                    </div>
                </form>
            <?php endif; ?>
            <?php if ($can_accept) : ?>
                <form class="juntaplay-complaints__accept" method="post">
                    <input type="hidden" name="jp_profile_action" value="complaint_accept" />
                    <input type="hidden" name="jp_profile_complaint_id" value="<?php echo esc_attr((string) $detail_id); ?>" />
                    <input type="hidden" name="jp_profile_complaint_group" value="<?php echo esc_attr((string) $detail_group_id); ?>" />
                    <input type="hidden" name="jp_profile_complaint_accept_nonce" value="<?php echo esc_attr($accept_nonce); ?>" />
                    <h4><?php esc_html_e('Aceitar proposta do administrador', 'juntaplay'); ?></h4>
                    <?php if ($latest_proposal && isset($latest_proposal['message'])) : ?>
                        <p class="juntaplay-complaints__accept-summary"><?php echo esc_html(sprintf(__('Proposta enviada: %s', 'juntaplay'), (string) $latest_proposal['message'])); ?></p>
                    <?php elseif ($has_proposal) : ?>
                        <p class="juntaplay-complaints__accept-summary"><?php esc_html_e('Uma proposta aguarda sua confirmação.', 'juntaplay'); ?></p>
                    <?php endif; ?>
                    <label for="jp-complaint-accept-note"><?php esc_html_e('Observações (opcional)', 'juntaplay'); ?></label>
                    <textarea id="jp-complaint-accept-note" name="jp_profile_complaint_accept_note" rows="3" class="juntaplay-form__input" placeholder="<?php esc_attr_e('Descreva como o acordo será concluído ou se há novas orientações.', 'juntaplay'); ?>"></textarea>
                    <div class="juntaplay-complaints__form-actions">
                        <button type="submit" class="juntaplay-button juntaplay-button--positive"><?php esc_html_e('Aceitar proposta', 'juntaplay'); ?></button>
                        <a class="juntaplay-button juntaplay-button--ghost" href="<?php echo esc_url($base_url); ?>"><?php esc_html_e('Voltar', 'juntaplay'); ?></a>
                    </div>
                </form>
            <?php elseif ($detail_role === 'participant' && !$has_proposal && $can_reply) : ?>
                <p class="juntaplay-complaints__accept-hint"><?php esc_html_e('Assim que o administrador enviar uma proposta, você poderá avaliá-la por aqui.', 'juntaplay'); ?></p>
            <?php endif; ?>
        </div>
    <?php else : ?>
        <div class="juntaplay-complaints__placeholder">
            <img src="<?php echo esc_url($empty_image); ?>" alt="" width="320" height="220" loading="lazy" />
            <p><?php esc_html_e('Selecione um ticket para ver detalhes, mensagens e anexos.', 'juntaplay'); ?></p>
        </div>
    <?php endif; ?>
</section>
