<?php
/**
 * Admin chat grid selector for JuntaPlay.
 */

if (!defined('ABSPATH')) {
    exit;
}

$members = $members ?? [];
$group_id = isset($group_id) ? (int) $group_id : 0;
$group_title = isset($group_title) ? (string) $group_title : __('Grupo', 'juntaplay');
?>
<div id="jp-chat-wrapper" class="jp-chat-admin-state">
    <div class="jp-chat-heading">
        <h3><?php esc_html_e('Converse com seus participantes', 'juntaplay'); ?></h3>
        <p class="jp-chat-subtitle"><?php esc_html_e('Selecione um participante para abrir o chat do grupo.', 'juntaplay'); ?></p>
    </div>
    <div class="jp-chat-admin-grid">
        <?php foreach ($members as $member) :
            $participant_id = isset($member['user_id']) ? (int) $member['user_id'] : (isset($member['id']) ? (int) $member['id'] : 0);
            $participant    = isset($member['user_name']) ? (string) $member['user_name'] : __('Participante', 'juntaplay');
            $participant_avatar = isset($member['user_avatar']) ? (string) $member['user_avatar'] : '';
            $member_group_title = isset($member['group_title']) ? (string) $member['group_title'] : $group_title;
            $member_group_id    = isset($member['group_id']) ? (int) $member['group_id'] : $group_id;
            $member_group_cover = isset($member['group_cover']) ? (string) $member['group_cover'] : '';
            $chat_url = add_query_arg(
                [
                    'section'        => 'juntaplay-chat',
                    'participant_id' => $participant_id,
                    'group_id'       => $member_group_id,
                ],
                esc_url_raw(remove_query_arg(['participant_id', 'group_id']))
            );
            ?>
            <a class="jp-chat-admin-card" href="<?php echo esc_url($chat_url); ?>">
                <span class="jp-chat-admin-glow" aria-hidden="true"></span>
                <div class="jp-chat-admin-card-body">
                    <div class="jp-chat-admin-avatar-frame">
                        <?php if ($participant_avatar !== '') : ?>
                            <img src="<?php echo esc_url($participant_avatar); ?>" alt="" class="jp-chat-admin-avatar" loading="lazy">
                        <?php else : ?>
                            <div class="jp-chat-admin-avatar jp-chat-admin-avatar--placeholder" aria-hidden="true">👤</div>
                        <?php endif; ?>
                    </div>
                    <div class="jp-chat-admin-card-content">
                        <div class="jp-chat-admin-name"><?php echo esc_html($participant); ?></div>
                        <div class="jp-chat-admin-group" title="<?php echo esc_attr($member_group_title); ?>"><?php echo esc_html($member_group_title); ?></div>
                    </div>
                    <div class="jp-chat-admin-group-thumb">
                        <?php if ($member_group_cover !== '') : ?>
                            <img src="<?php echo esc_url($member_group_cover); ?>" alt="" loading="lazy">
                        <?php else : ?>
                            <span aria-hidden="true">💬</span>
                        <?php endif; ?>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</div>
