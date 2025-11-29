<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div id="jplay-2fa-modal" class="jplay-modal" role="dialog" aria-modal="true" aria-labelledby="jplay-2fa-title" hidden>
    <div class="jplay-modal__overlay" data-jplay-close="true"></div>
    <div class="jplay-modal__content" role="document">
        <button type="button" class="jplay-modal__close" data-jplay-close="true">
            <span class="screen-reader-text"><?php esc_html_e( 'Fechar modal', 'juntaplay' ); ?></span>
            ×
        </button>
        <h2 id="jplay-2fa-title" class="jplay-modal__title"><?php esc_html_e( 'Confirme o envio para análise', 'juntaplay' ); ?></h2>
        <p class="jplay-modal__description" data-jplay-2fa-status aria-live="polite"></p>
        <form class="jplay-modal__form" novalidate>
            <label for="jplay_2fa_code" class="jplay-label">
                <?php esc_html_e( 'Código de verificação', 'juntaplay' ); ?>
                <span class="jplay-required" aria-hidden="true">*</span>
            </label>
            <input
                type="text"
                id="jplay_2fa_code"
                name="jplay_2fa_code"
                class="jplay-input"
                inputmode="numeric"
                pattern="[0-9]*"
                maxlength="6"
                autocomplete="one-time-code"
                required
                aria-required="true"
            />
            <div class="jplay-modal__actions">
                <button type="submit" class="jplay-button jplay-button--primary" data-jplay-submit>
                    <?php esc_html_e( 'Confirmar código', 'juntaplay' ); ?>
                </button>
                <button type="button" class="jplay-button jplay-button--ghost" data-jplay-resend>
                    <?php esc_html_e( 'Solicitar novo código', 'juntaplay' ); ?>
                </button>
            </div>
            <div class="jplay-modal__feedback" role="alert" data-jplay-modal-feedback></div>
        </form>
    </div>
</div>
<div class="jplay-2fa-feedback" data-jplay-2fa-feedback aria-live="polite"></div>
