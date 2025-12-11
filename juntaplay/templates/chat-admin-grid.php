<?php
/**
 * Admin chat selector (glassmorphism list) for JuntaPlay.
 */

if (!defined('ABSPATH')) {
    exit;
}

$members      = $members ?? [];
$group_id     = isset($group_id) ? (int) $group_id : 0;
$group_title  = isset($group_title) ? (string) $group_title : __('Grupo', 'juntaplay');
$group_cover  = isset($group_cover) ? (string) $group_cover : '';
$admin_notice = isset($admin_notice) ? (string) $admin_notice : '';
$has_messages = false;
?>
<div id="jp-chat-wrapper" class="jp-chat-selector">
    <div class="jp-chat-selector-hero">
        <div class="jp-chat-selector-title">
            <h2><?php esc_html_e('Falar com Assinantes', 'juntaplay'); ?></h2>
            <span class="jp-chat-selector-icon" aria-hidden="true">🔔</span>
        </div>
        <div class="jp-chat-selector-meta">
            <div class="jp-chat-selector-chip"><?php esc_html_e('Central de Mensagens', 'juntaplay'); ?></div>
            <p class="jp-chat-selector-lead"><?php esc_html_e('Selecione um assinante para iniciar o chat.', 'juntaplay'); ?></p>
        </div>
    </div>

    <div class="jp-chat-selector-card">
        <div class="jp-chat-selector-card-header">
            <div class="jp-chat-selector-group">
                <div class="jp-chat-selector-group-cover">
                    <?php if ($group_cover !== '') : ?>
                        <img src="<?php echo esc_url($group_cover); ?>" alt="" loading="lazy">
                    <?php else : ?>
                        <span aria-hidden="true">💬</span>
                    <?php endif; ?>
                </div>
                <div class="jp-chat-selector-group-meta">
                    <span class="jp-chat-selector-group-label"><?php esc_html_e('Grupo', 'juntaplay'); ?></span>
                    <span class="jp-chat-selector-group-title"><?php echo esc_html($group_title); ?></span>
                </div>
            </div>
            <div class="jp-chat-selector-hint"><?php esc_html_e('Escolha um assinante para abrir a conversa privada.', 'juntaplay'); ?></div>
        </div>

        <?php if (!empty($admin_notice)) : ?>
            <div class="jp-chat-selector-notice" role="alert">
                <?php echo esc_html($admin_notice); ?>
            </div>
        <?php endif; ?>

        <div class="jp-chat-contact-list">
            <?php if (empty($members)) : ?>
                <div class="jp-chat-selector-empty">
                    <?php esc_html_e('Ainda não há assinantes neste grupo.', 'juntaplay'); ?>
                </div>
            <?php endif; ?>

            <?php foreach ($members as $member) :
                $participant_id = isset($member['user_id']) ? (int) $member['user_id'] : (isset($member['id']) ? (int) $member['id'] : 0);
                $participant    = isset($member['user_name']) ? (string) $member['user_name'] : __('Participante', 'juntaplay');
                $participant_avatar = isset($member['user_avatar']) ? (string) $member['user_avatar'] : '';
                $member_group_title = isset($member['group_title']) ? (string) $member['group_title'] : $group_title;
                $member_group_id    = isset($member['group_id']) ? (int) $member['group_id'] : $group_id;
                $last_message       = isset($member['last_message']) ? (string) $member['last_message'] : '';
                $has_messages       = $has_messages || $last_message !== '';
                $chat_url = add_query_arg(
                    [
                        'section'        => 'juntaplay-chat',
                        'participant_id' => $participant_id,
                        'group_id'       => $member_group_id,
                    ],
                    esc_url_raw(remove_query_arg(['participant_id', 'group_id']))
                );
                ?>
                <a class="jp-chat-contact-row" href="<?php echo esc_url($chat_url); ?>">
                    <div class="jp-chat-contact-avatar-frame">
                        <?php if ($participant_avatar !== '') : ?>
                            <img class="jp-chat-contact-avatar" src="<?php echo esc_url($participant_avatar); ?>" alt="" loading="lazy">
                        <?php else : ?>
                            <span class="jp-chat-contact-avatar jp-chat-contact-avatar--placeholder" aria-hidden="true">👤</span>
                        <?php endif; ?>
                    </div>
                    <div class="jp-chat-contact-body">
                        <div class="jp-chat-contact-name"><?php echo esc_html($participant); ?></div>
                        <div class="jp-chat-contact-group" title="<?php echo esc_attr($member_group_title); ?>"><?php echo esc_html($member_group_title); ?></div>
                        <div class="jp-chat-contact-preview">
                            <?php echo esc_html($last_message !== '' ? $last_message : __('Iniciar conversa', 'juntaplay')); ?>
                        </div>
                    </div>
                    <span class="jp-chat-contact-chevron" aria-hidden="true">›</span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>
