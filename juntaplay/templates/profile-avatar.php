<?php
/**
 * Profile avatar management template.
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$avatar_url        = isset($group_context['avatar_url']) ? (string) $group_context['avatar_url'] : '';
$avatar_has_custom = !empty($group_context['avatar_has_custom']);
$avatar_initial    = isset($group_context['avatar_initial']) ? (string) $group_context['avatar_initial'] : '';
$avatar_label      = isset($group_context['avatar_label']) ? (string) $group_context['avatar_label'] : '';
$errors            = isset($group_context['errors']) && is_array($group_context['errors']) ? $group_context['errors'] : [];
$upload_nonce      = wp_create_nonce('juntaplay_profile_update');
$remove_nonce      = wp_create_nonce('juntaplay_profile_update');

$avatar_label = $avatar_label !== '' ? $avatar_label : __('assinante', 'juntaplay');
?>
<div class="juntaplay-avatar-manager">
    <div class="juntaplay-avatar-manager__preview" role="img" aria-label="<?php echo esc_attr(sprintf(__('Foto de perfil de %s', 'juntaplay'), $avatar_label)); ?>">
        <?php if ($avatar_url !== '') : ?>
            <img src="<?php echo esc_url($avatar_url); ?>" alt="" loading="lazy" aria-hidden="true" />
        <?php elseif ($avatar_initial !== '') : ?>
            <span aria-hidden="true"><?php echo esc_html($avatar_initial); ?></span>
        <?php else : ?>
            <span aria-hidden="true"><?php echo esc_html__('JP', 'juntaplay'); ?></span>
        <?php endif; ?>
    </div>
    <div class="juntaplay-avatar-manager__actions">
        <?php if ($errors) : ?>
            <div class="juntaplay-profile__alerts">
                <?php foreach ($errors as $error_message) : ?>
                    <div class="juntaplay-alert juntaplay-alert--error"><?php echo esc_html($error_message); ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <p class="juntaplay-avatar-manager__hint"><?php echo esc_html__('Adicione, atualize ou remova sua foto de perfil usando as ações abaixo.', 'juntaplay'); ?></p>

        <form
            method="post"
            class="juntaplay-form juntaplay-avatar-manager__form"
            enctype="multipart/form-data"
            id="juntaplay-avatar-upload"
        >
            <input type="hidden" name="jp_profile_action" value="1" />
            <input type="hidden" name="jp_profile_section" value="avatar_upload" />
            <input type="hidden" name="jp_profile_nonce" value="<?php echo esc_attr($upload_nonce); ?>" />
            <div class="juntaplay-form__group">
                <label for="juntaplay-avatar-file"><?php echo esc_html__('Adicionar ou atualizar foto', 'juntaplay'); ?></label>
                <input
                    type="file"
                    id="juntaplay-avatar-file"
                    name="jp_profile_avatar_file"
                    class="juntaplay-form__input"
                    accept="image/*"
                />
                <p class="juntaplay-form__help"><?php echo esc_html__('Escolha uma imagem JPG, PNG ou WEBP de até 5MB e clique em "Salvar foto de perfil".', 'juntaplay'); ?></p>
            </div>
        </form>

        <?php if ($avatar_has_custom) : ?>
            <form method="post" class="juntaplay-avatar-manager__remove-form" id="juntaplay-avatar-remove">
                <input type="hidden" name="jp_profile_action" value="1" />
                <input type="hidden" name="jp_profile_section" value="avatar_remove" />
                <input type="hidden" name="jp_profile_nonce" value="<?php echo esc_attr($remove_nonce); ?>" />
            </form>
        <?php endif; ?>

        <div class="juntaplay-avatar-manager__button-row">
            <button type="submit" class="juntaplay-button juntaplay-button--primary" form="juntaplay-avatar-upload">
                <?php echo esc_html__('Salvar foto de perfil', 'juntaplay'); ?>
            </button>
            <?php if ($avatar_has_custom) : ?>
                <button type="submit" class="juntaplay-button juntaplay-button--ghost" form="juntaplay-avatar-remove">
                    <?php echo esc_html__('Remover foto atual', 'juntaplay'); ?>
                </button>
            <?php endif; ?>
        </div>
    </div>
</div>
