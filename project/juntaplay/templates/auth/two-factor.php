<?php
/**
 * JuntaPlay two-factor verification template.
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$errors   = isset($errors) && is_array($errors) ? $errors : [];
$context  = isset($context) && is_array($context) ? $context : [];
$login_url = isset($login_url) ? (string) $login_url : wp_login_url();

$challenge    = isset($context['challenge']) ? (string) $context['challenge'] : '';
$destination  = isset($context['destination']) ? (string) $context['destination'] : '';
$method       = isset($context['method']) ? (string) $context['method'] : 'email';
$resent       = !empty($context['resent']);
$attempts     = isset($context['attempts']) ? (int) $context['attempts'] : 0;
$remaining    = isset($context['expires']) ? max(0, (int) $context['expires'] - time()) : 0;
$method_label = match ($method) {
    'whatsapp' => __('WhatsApp cadastrado', 'juntaplay'),
    default    => __('E-mail cadastrado', 'juntaplay'),
};
$destination_display = $destination !== '' ? $destination : __('seu contato cadastrado', 'juntaplay');
?>
<div class="juntaplay-two-factor" data-jp-two-factor data-remaining="<?php echo esc_attr((string) $remaining); ?>" data-cooldown="45">
    <div class="juntaplay-two-factor__card">
        <h1><?php esc_html_e('Confirme seu acesso', 'juntaplay'); ?></h1>
        <p><?php printf(esc_html__('Enviamos um código de verificação para %1$s (%2$s). Digite abaixo para finalizar o login.', 'juntaplay'), esc_html($destination_display), esc_html($method_label)); ?></p>

        <?php if (!empty($errors)) : ?>
            <div class="juntaplay-two-factor__alert" role="alert">
                <ul>
                    <?php foreach ($errors as $message) : ?>
                        <li><?php echo esc_html($message); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($resent) : ?>
            <p class="juntaplay-two-factor__notice"><?php esc_html_e('Enviamos um novo código. Verifique seu contato.', 'juntaplay'); ?></p>
        <?php endif; ?>

        <?php if ($challenge === '') : ?>
            <p class="juntaplay-two-factor__empty"><?php esc_html_e('Sua sessão de verificação expirou. Faça login novamente para receber um novo código.', 'juntaplay'); ?></p>
            <p><a class="juntaplay-link" href="<?php echo esc_url($login_url); ?>"><?php esc_html_e('Voltar para o login', 'juntaplay'); ?></a></p>
        <?php else : ?>
            <form method="post" class="juntaplay-two-factor__form" novalidate>
                <label for="jp-two-factor-code" class="juntaplay-two-factor__label"><?php esc_html_e('Código de verificação', 'juntaplay'); ?></label>
                <input
                    id="jp-two-factor-code"
                    name="jp_two_factor_code"
                    type="text"
                    inputmode="numeric"
                    pattern="[0-9]*"
                    maxlength="6"
                    autocomplete="one-time-code"
                    class="juntaplay-two-factor__input"
                    placeholder="000000"
                    data-jp-two-factor-input
                    required
                />
                <input type="hidden" name="jp_two_factor_challenge" value="<?php echo esc_attr($challenge); ?>" />
                <input type="hidden" name="jp_two_factor_action" value="verify" />
                <?php wp_nonce_field('juntaplay_two_factor', 'jp_two_factor_nonce'); ?>

                <button type="submit" class="juntaplay-button juntaplay-button--primary juntaplay-two-factor__submit"><?php esc_html_e('Confirmar acesso', 'juntaplay'); ?></button>
            </form>

            <div class="juntaplay-two-factor__meta">
                <?php if ($attempts > 0) : ?>
                    <p class="juntaplay-two-factor__attempts"><?php printf(esc_html__('Tentativas restantes: %d', 'juntaplay'), max(0, 5 - $attempts)); ?></p>
                <?php endif; ?>
                <p class="juntaplay-two-factor__timer" data-jp-two-factor-timer<?php echo $remaining > 0 ? '' : ' hidden'; ?>><?php esc_html_e('O código expira em instantes.', 'juntaplay'); ?></p>
                <form method="post" class="juntaplay-two-factor__resend" data-jp-two-factor-resend>
                    <input type="hidden" name="jp_two_factor_challenge" value="<?php echo esc_attr($challenge); ?>" />
                    <input type="hidden" name="jp_two_factor_action" value="resend" />
                    <?php wp_nonce_field('juntaplay_two_factor', 'jp_two_factor_nonce'); ?>
                    <button type="submit" class="juntaplay-button juntaplay-button--ghost" data-jp-two-factor-resend-button><?php esc_html_e('Enviar novo código', 'juntaplay'); ?></button>
                </form>
            </div>

            <p class="juntaplay-two-factor__back"><a class="juntaplay-link" href="<?php echo esc_url($login_url); ?>"><?php esc_html_e('Voltar para o login', 'juntaplay'); ?></a></p>
        <?php endif; ?>
    </div>
</div>
