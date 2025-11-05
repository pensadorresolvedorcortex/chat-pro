<?php
/**
 * Group relationship confirmation page.
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$relationship_cards    = isset($relationship_cards) && is_array($relationship_cards) ? $relationship_cards : [];
$relationship_errors   = isset($relationship_errors) && is_array($relationship_errors) ? $relationship_errors : [];
$relationship_accept   = !empty($relationship_accept);
$relationship_title    = isset($relationship_title) ? (string) $relationship_title : '';
$relationship_service  = isset($relationship_service) ? (string) $relationship_service : '';
$relationship_price    = isset($relationship_price) ? (string) $relationship_price : '';
$relationship_label    = isset($relationship_label) ? (string) $relationship_label : '';
$relationship_required = isset($relationship_required) ? (string) $relationship_required : '';
$relationship_back     = isset($relationship_back) ? (string) $relationship_back : '';
?>
<section class="juntaplay-relationship">
    <div class="juntaplay-relationship__container">
        <header class="juntaplay-relationship__header">
            <p class="juntaplay-relationship__eyebrow"><?php echo esc_html__('Passo 1 de 2', 'juntaplay'); ?></p>
            <h1 class="juntaplay-relationship__title">
                <?php echo esc_html($relationship_title !== '' ? $relationship_title : $relationship_service); ?>
            </h1>
            <p class="juntaplay-relationship__subtitle">
                <?php
                if ($relationship_label !== '') {
                    echo esc_html(sprintf(
                        /* translators: %s: relationship label */
                        __('Este grupo aceita apenas participantes com relacionamento de %s com o administrador.', 'juntaplay'),
                        $relationship_label
                    ));
                } else {
                    esc_html_e('Informe seu vínculo com o administrador antes de continuar para o checkout.', 'juntaplay');
                }
                ?>
            </p>
            <?php if ($relationship_price !== '') : ?>
                <p class="juntaplay-relationship__price">
                    <strong><?php echo esc_html__('Valor por participante', 'juntaplay'); ?>:</strong>
                    <?php echo wp_kses_post($relationship_price); ?>
                </p>
            <?php endif; ?>
        </header>

        <?php if ($relationship_errors) : ?>
            <div class="juntaplay-relationship__alerts" role="alert">
                <ul>
                    <?php foreach ($relationship_errors as $error_message) : ?>
                        <li><?php echo esc_html((string) $error_message); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" class="juntaplay-relationship__form">
            <input type="hidden" name="jp_relationship_action" value="confirm" />
            <?php wp_nonce_field('juntaplay_relationship_confirm', 'jp_relationship_nonce'); ?>

            <div class="juntaplay-relationship__cards">
                <?php foreach ($relationship_cards as $card) :
                    $card_key   = isset($card['key']) ? (string) $card['key'] : '';
                    $card_label = isset($card['label']) ? (string) $card['label'] : '';
                    $is_active  = !empty($card['active']);

                    $card_classes = ['juntaplay-relationship-card'];
                    $card_classes[] = 'juntaplay-relationship-card--' . sanitize_html_class($card_key !== '' ? $card_key : 'default');
                    if ($is_active) {
                        $card_classes[] = 'is-active';
                    } else {
                        $card_classes[] = 'is-muted';
                    }
                    ?>
                    <label class="<?php echo esc_attr(implode(' ', $card_classes)); ?>">
                        <input
                            type="radio"
                            name="jp_relationship_choice"
                            value="<?php echo esc_attr($card_key); ?>"
                            <?php checked($is_active); ?>
                            <?php disabled(!$is_active); ?>
                        />
                        <span class="juntaplay-relationship-card__visual" aria-hidden="true"></span>
                        <span class="juntaplay-relationship-card__label"><?php echo esc_html($card_label); ?></span>
                    </label>
                <?php endforeach; ?>
            </div>

            <p class="juntaplay-relationship__description">
                <?php
                if ($relationship_label !== '') {
                    echo esc_html__(
                        'Ao continuar você confirma que possui o vínculo indicado e que cumprirá as regras do serviço.',
                        'juntaplay'
                    );
                } else {
                    esc_html_e('Revise os termos e confirme a participação antes de prosseguir.', 'juntaplay');
                }
                ?>
            </p>

            <label class="juntaplay-relationship__consent">
                <input
                    type="checkbox"
                    name="jp_relationship_accept"
                    value="1"
                    <?php checked($relationship_accept); ?>
                    required
                />
                <span>
                    <?php
                    $service_fragment = $relationship_title !== '' ? $relationship_title : $relationship_service;
                    if ($service_fragment !== '') {
                        echo esc_html(
                            sprintf(
                                /* translators: %s: group/service title */
                                __('Confirmo estar ciente de que a plataforma JuntaPlay não possui vínculo com o serviço %s e concordo com os termos do grupo e da plataforma.', 'juntaplay'),
                                $service_fragment
                            )
                        );
                    } else {
                        esc_html_e('Confirmo estar ciente de que a plataforma JuntaPlay não é afiliada ao serviço contratado e concordo com os termos do grupo e da plataforma.', 'juntaplay');
                    }
                    ?>
                </span>
            </label>

            <div class="juntaplay-relationship__actions">
                <?php if ($relationship_back !== '') : ?>
                    <a class="juntaplay-button juntaplay-button--ghost" href="<?php echo esc_url($relationship_back); ?>">
                        <?php esc_html_e('Voltar', 'juntaplay'); ?>
                    </a>
                <?php else : ?>
                    <a class="juntaplay-button juntaplay-button--ghost" href="<?php echo esc_url(home_url('/grupos')); ?>">
                        <?php esc_html_e('Voltar', 'juntaplay'); ?>
                    </a>
                <?php endif; ?>
                <button type="submit" class="juntaplay-button juntaplay-button--primary">
                    <?php esc_html_e('Próximo', 'juntaplay'); ?>
                </button>
            </div>
        </form>
    </div>
</section>
