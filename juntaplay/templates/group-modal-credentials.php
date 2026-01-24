<?php
/**
 * Group credentials form modal content.
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$group_id = isset($group_id) ? (int) $group_id : 0;
$title = isset($title) ? (string) $title : '';
$access_login = isset($access_login) ? (string) $access_login : '';
$access_password = isset($access_password) ? (string) $access_password : '';
$access_observations = isset($access_observations) ? (string) $access_observations : '';
?>
<div class="juntaplay-group-modal__detail juntaplay-group-modal__detail--credentials" data-group-modal-view="credentials">
    <header class="juntaplay-group-modal__header">
        <div class="juntaplay-group-modal__headline">
            <h3 class="juntaplay-group-modal__title"><?php esc_html_e('Cadastrar credenciais', 'juntaplay'); ?></h3>
            <?php if ($title !== '') : ?>
                <span class="juntaplay-group-modal__meta"><?php echo esc_html($title); ?></span>
            <?php endif; ?>
        </div>
    </header>

    <div class="juntaplay-group-modal__body">
        <form class="juntaplay-group-credentials__form" data-group-credentials-form>
            <input type="hidden" name="group_id" value="<?php echo esc_attr((string) $group_id); ?>" />

            <label class="juntaplay-form__label" for="jp-group-credentials-login"><?php esc_html_e('Login / Usuário', 'juntaplay'); ?></label>
            <input
                id="jp-group-credentials-login"
                type="text"
                class="juntaplay-form__input"
                name="access_login"
                value="<?php echo esc_attr($access_login); ?>"
                required
            />

            <label class="juntaplay-form__label" for="jp-group-credentials-password"><?php esc_html_e('Senha', 'juntaplay'); ?></label>
            <input
                id="jp-group-credentials-password"
                type="text"
                class="juntaplay-form__input"
                name="access_password"
                value="<?php echo esc_attr($access_password); ?>"
                required
            />

            <label class="juntaplay-form__label" for="jp-group-credentials-observations"><?php esc_html_e('Observações', 'juntaplay'); ?></label>
            <textarea
                id="jp-group-credentials-observations"
                class="juntaplay-form__input"
                name="access_observations"
                rows="3"
                placeholder="<?php echo esc_attr__('Observações adicionais (opcional).', 'juntaplay'); ?>"
            ><?php echo esc_textarea($access_observations); ?></textarea>

            <div class="juntaplay-group-modal__cta">
                <button type="submit" class="juntaplay-button juntaplay-button--primary">
                    <?php esc_html_e('Salvar credenciais', 'juntaplay'); ?>
                </button>
                <button type="button" class="juntaplay-button juntaplay-button--ghost" data-group-access-cancel>
                    <?php esc_html_e('Voltar aos dados de acesso', 'juntaplay'); ?>
                </button>
            </div>
        </form>
    </div>
</div>
