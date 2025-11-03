<?php
/**
 * Group detail modal content.
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$group    = isset($group) && is_array($group) ? $group : [];
$is_owner = !empty($is_owner);

$title            = isset($group['title']) ? (string) $group['title'] : '';
$cover_url        = isset($group['cover_url']) ? (string) $group['cover_url'] : '';
$cover_alt        = isset($group['cover_alt']) ? (string) $group['cover_alt'] : esc_html__('Capa do grupo', 'juntaplay');
$description      = isset($group['description']) ? (string) $group['description'] : '';
$rules            = isset($group['rules']) ? (string) $group['rules'] : '';
$service_name     = isset($group['service_name']) ? (string) $group['service_name'] : '';
$service_url      = isset($group['service_url']) ? (string) $group['service_url'] : '';
$price_regular    = isset($group['price_regular_display']) ? (string) $group['price_regular_display'] : '';
$price_promo      = isset($group['price_promotional_display']) ? (string) $group['price_promotional_display'] : '';
$member_price     = isset($group['member_price_display']) ? (string) $group['member_price_display'] : '';
$slots_summary    = isset($group['slots_summary']) ? (string) $group['slots_summary'] : '';
$support_channel  = isset($group['support_channel']) ? (string) $group['support_channel'] : '';
$delivery_time    = isset($group['delivery_time']) ? (string) $group['delivery_time'] : '';
$access_method    = isset($group['access_method']) ? (string) $group['access_method'] : '';
$category_label   = isset($group['category_label']) ? (string) $group['category_label'] : '';
$instant_label    = isset($group['instant_access_label']) ? (string) $group['instant_access_label'] : '';
$pool_title       = isset($group['pool_title']) ? (string) $group['pool_title'] : '';
$pool_link        = isset($group['pool_link']) ? (string) $group['pool_link'] : '';
$members_count    = isset($group['members_count']) ? (int) $group['members_count'] : 0;
$availability     = isset($group['availability_label']) ? (string) $group['availability_label'] : '';
$availabilityTone = isset($group['availability_tone']) ? (string) $group['availability_tone'] : '';
$share_snippet    = isset($group['share_snippet']) ? (string) $group['share_snippet'] : '';
$share_domain     = isset($group['share_domain']) ? (string) $group['share_domain'] : '';
$price_highlight  = isset($group['price_highlight']) ? (string) $group['price_highlight'] : '';
$cta_label        = isset($group['cta_label']) ? (string) $group['cta_label'] : '';
$cta_url          = isset($group['cta_url']) ? (string) $group['cta_url'] : '';
$cta_disabled     = !empty($group['cta_disabled']);
$blocked_notice   = isset($group['blocked_notice']) ? (string) $group['blocked_notice'] : '';
$support_display  = isset($group['support_channel_display']) ? (string) $group['support_channel_display'] : $support_channel;
$support_type     = isset($group['support_channel_type']) ? (string) $group['support_channel_type'] : '';
$support_masked   = !empty($group['support_channel_masked']);
$support_notice   = isset($group['support_channel_notice']) ? (string) $group['support_channel_notice'] : '';
$support_label    = isset($group['support_channel_label']) ? (string) $group['support_channel_label'] : __('Suporte a Membros', 'juntaplay');
$share_url        = isset($group['share_url']) ? (string) $group['share_url'] : (isset($group['pool_link']) ? (string) $group['pool_link'] : '');
$share_message    = $title !== ''
    ? sprintf(__('Confira o grupo %s na JuntaPlay.', 'juntaplay'), $title)
    : __('Confira este grupo na JuntaPlay.', 'juntaplay');

$support_icon_svg = '';
switch ($support_type) {
    case 'whatsapp':
        $support_icon_svg = '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M12 2a10 10 0 0 0-8.66 15.21L2 22l4.93-1.3A10 10 0 1 0 12 2Zm0 18.2a8.2 8.2 0 0 1-4.18-1.14l-.3-.18-2.93.78.79-2.86-.19-.3A8.2 8.2 0 1 1 12 20.2Zm4.6-6.1c-.25-.13-1.47-.72-1.7-.8s-.4-.12-.57.13-.65.8-.8.97-.3.19-.55.06a6.7 6.7 0 0 1-2-1.24 7.48 7.48 0 0 1-1.4-1.74c-.15-.26 0-.4.11-.53.11-.11.26-.3.38-.45a1.74 1.74 0 0 0 .25-.42.48.48 0 0 0 0-.45c0-.13-.57-1.37-.78-1.88s-.41-.44-.57-.45h-.49a.94.94 0 0 0-.68.32A2.85 2.85 0 0 0 6 8.7a5 5 0 0 0 1.05 2.65 11.32 11.32 0 0 0 4.32 3.61c.43.19.76.3 1 .38a2.39 2.39 0 0 0 1.1.07 1.84 1.84 0 0 0 1.2-.86 1.5 1.5 0 0 0 .1-.86c-.04-.07-.22-.13-.37-.2Z"/></svg>';
        break;
    case 'email':
        $support_icon_svg = '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M4 5h16a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2Zm0 2v.2l8 4.8 8-4.8V7H4Zm16 10V9.62l-7.37 4.42a1 1 0 0 1-1.26 0L4 9.62V17h16Z"/></svg>';
        break;
    default:
        $support_icon_svg = '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M12 3a9 9 0 1 1-4.5 16.8L3 21l1.3-4.5A9 9 0 0 1 12 3Zm0 2a7 7 0 1 0 0 14 7 7 0 0 0 0-14Zm0 3a1 1 0 0 1 1 1v3h2a1 1 0 1 1 0 2h-3a1 1 0 0 1-1-1V9a1 1 0 0 1 1-1Z"/></svg>';
        break;
}

?>
<div class="juntaplay-group-modal__detail">
    <header class="juntaplay-group-modal__header">
        <?php if ($cover_url !== '') : ?>
            <img class="juntaplay-group-modal__cover" src="<?php echo esc_url($cover_url); ?>" alt="<?php echo esc_attr($cover_alt); ?>" />
        <?php endif; ?>
        <div class="juntaplay-group-modal__headline">
            <h3 class="juntaplay-group-modal__title"><?php echo esc_html($title !== '' ? $title : esc_html__('Grupo sem nome', 'juntaplay')); ?></h3>
            <?php if ($availability !== '') : ?>
                <span class="juntaplay-badge juntaplay-badge--<?php echo esc_attr($availabilityTone !== '' ? $availabilityTone : 'info'); ?>"><?php echo esc_html($availability); ?></span>
            <?php endif; ?>
            <?php if ($members_count > 0) : ?>
                <span class="juntaplay-group-modal__meta"><?php echo esc_html(sprintf(_n('%d participante', '%d participantes', $members_count, 'juntaplay'), $members_count)); ?></span>
            <?php endif; ?>
            <?php if ($share_domain !== '') : ?>
                <span class="juntaplay-group-modal__meta"><?php echo esc_html(sprintf(__('Link público: %s', 'juntaplay'), $share_domain)); ?></span>
            <?php endif; ?>
        </div>
    </header>

    <div class="juntaplay-group-modal__body">
        <?php if ($price_highlight !== '' || $cta_label !== '') : ?>
            <div class="juntaplay-group-modal__cta">
                <?php if ($price_highlight !== '') : ?>
                    <span class="juntaplay-group-modal__cta-price"><?php echo esc_html($price_highlight); ?></span>
                <?php endif; ?>
                <?php if ($cta_label !== '') : ?>
                    <?php if ($cta_url !== '' && !$cta_disabled) : ?>
                        <a class="juntaplay-button juntaplay-button--primary" href="<?php echo esc_url($cta_url); ?>" rel="nofollow noopener"><?php echo esc_html($cta_label); ?></a>
                    <?php else : ?>
                        <button type="button" class="juntaplay-button juntaplay-button--primary" disabled><?php echo esc_html($cta_label); ?></button>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($blocked_notice !== '') : ?>
            <p class="juntaplay-group-modal__notice"><?php echo esc_html($blocked_notice); ?></p>
        <?php endif; ?>

        <dl class="juntaplay-group-modal__info">
            <?php if ($service_name !== '') : ?>
                <div>
                    <dt><?php esc_html_e('Serviço', 'juntaplay'); ?></dt>
                    <dd>
                        <?php if ($service_url !== '') : ?>
                            <a href="<?php echo esc_url($service_url); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html($service_name); ?></a>
                        <?php else : ?>
                            <?php echo esc_html($service_name); ?>
                        <?php endif; ?>
                    </dd>
                </div>
            <?php endif; ?>
            <?php if ($category_label !== '') : ?>
                <div>
                    <dt><?php esc_html_e('Categoria', 'juntaplay'); ?></dt>
                    <dd><?php echo esc_html($category_label); ?></dd>
                </div>
            <?php endif; ?>
            <?php if ($price_regular !== '') : ?>
                <div>
                    <dt><?php esc_html_e('Valor do serviço', 'juntaplay'); ?></dt>
                    <dd><?php echo esc_html($price_regular); ?></dd>
                </div>
            <?php endif; ?>
            <?php if ($price_promo !== '') : ?>
                <div>
                    <dt><?php esc_html_e('Oferta promocional', 'juntaplay'); ?></dt>
                    <dd><?php echo esc_html($price_promo); ?></dd>
                </div>
            <?php endif; ?>
            <?php if ($member_price !== '') : ?>
                <div>
                    <dt><?php esc_html_e('Valor por participante', 'juntaplay'); ?></dt>
                    <dd><?php echo esc_html($member_price); ?></dd>
                </div>
            <?php endif; ?>
            <?php if ($slots_summary !== '') : ?>
                <div>
                    <dt><?php esc_html_e('Vagas', 'juntaplay'); ?></dt>
                    <dd><?php echo esc_html($slots_summary); ?></dd>
                </div>
            <?php endif; ?>
            <?php if ($support_display !== '') : ?>
                <div>
                    <dt><?php echo esc_html($support_label); ?></dt>
                    <dd class="juntaplay-group-modal__support">
                        <span class="juntaplay-group-modal__support-line">
                            <?php if ($support_icon_svg !== '') : ?>
                                <span class="juntaplay-group-modal__support-icon" aria-hidden="true">
                                    <?php echo $support_icon_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                </span>
                            <?php endif; ?>
                            <span class="juntaplay-group-modal__support-value"><?php echo esc_html($support_display); ?></span>
                        </span>
                        <?php if ($support_masked && $support_notice !== '') : ?>
                            <span class="juntaplay-group-modal__support-hint"><?php echo esc_html($support_notice); ?></span>
                        <?php endif; ?>
                    </dd>
                </div>
            <?php endif; ?>
            <?php if ($delivery_time !== '') : ?>
                <div>
                    <dt><?php esc_html_e('Prazo de entrega', 'juntaplay'); ?></dt>
                    <dd><?php echo esc_html($delivery_time); ?></dd>
                </div>
            <?php endif; ?>
            <?php if ($access_method !== '') : ?>
                <div>
                    <dt><?php esc_html_e('Forma de acesso', 'juntaplay'); ?></dt>
                    <dd><?php echo esc_html($access_method); ?></dd>
                </div>
            <?php endif; ?>
            <?php if ($instant_label !== '') : ?>
                <div>
                    <dt><?php esc_html_e('Acesso instantâneo', 'juntaplay'); ?></dt>
                    <dd><?php echo esc_html($instant_label); ?></dd>
                </div>
            <?php endif; ?>
            <?php if ($pool_title !== '') : ?>
                <div>
                    <dt><?php esc_html_e('Grupo vinculado', 'juntaplay'); ?></dt>
                    <dd>
                        <?php if ($pool_link !== '') : ?>
                            <a href="<?php echo esc_url($pool_link); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html($pool_title); ?></a>
                        <?php else : ?>
                            <?php echo esc_html($pool_title); ?>
                        <?php endif; ?>
                    </dd>
                </div>
            <?php endif; ?>
        </dl>

        <?php if ($description !== '') : ?>
            <section class="juntaplay-group-modal__section">
                <h4><?php esc_html_e('Descrição do grupo', 'juntaplay'); ?></h4>
                <?php echo wp_kses_post(wpautop($description)); ?>
            </section>
        <?php endif; ?>

        <?php if ($rules !== '') : ?>
            <section class="juntaplay-group-modal__section">
                <h4><?php esc_html_e('Regras para participantes', 'juntaplay'); ?></h4>
                <?php echo wp_kses_post(wpautop($rules)); ?>
            </section>
        <?php endif; ?>

        <?php if ($share_snippet !== '') : ?>
            <section class="juntaplay-group-modal__section">
                <h4><?php esc_html_e('Resumo para divulgação', 'juntaplay'); ?></h4>
                <pre class="juntaplay-group-modal__share" aria-label="<?php esc_attr_e('Copiar resumo', 'juntaplay'); ?>"><?php echo esc_html($share_snippet); ?></pre>
                <?php if ($share_url !== '') : ?>
                    <div class="juntaplay-share" data-jp-share>
                        <p class="juntaplay-share__title"><?php esc_html_e('Compartilhar anúncio', 'juntaplay'); ?></p>
                        <div class="juntaplay-share__actions" role="group" aria-label="<?php esc_attr_e('Opções de compartilhamento', 'juntaplay'); ?>">
                            <button
                                type="button"
                                class="juntaplay-share__button juntaplay-share__button--whatsapp"
                                data-jp-share-network="whatsapp"
                                data-jp-share-url="<?php echo esc_url($share_url); ?>"
                                data-jp-share-text="<?php echo esc_attr($share_message); ?>"
                            >
                                <span class="juntaplay-share__icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" focusable="false"><path fill="currentColor" d="M12 2a10 10 0 0 0-8.6 15.2L2 22l4.9-1.3A10 10 0 1 0 12 2Zm0 1.8A8.2 8.2 0 0 1 20.2 12a8.2 8.2 0 0 1-12.4 6.9l-.3-.2-2.9.8.8-2.9-.2-.3A8.2 8.2 0 0 1 12 3.8Zm3.6 11.1c-.2-.1-1.4-.7-1.7-.8s-.4-.1-.5.1-.6.8-.8 1-.3.2-.5.1a6.7 6.7 0 0 1-2-1.2 7.5 7.5 0 0 1-1.4-1.7c-.1-.2 0-.4.1-.5l.4-.4c.1-.2.2-.3.2-.4s0-.3 0-.4-.6-1.4-.8-1.9-.3-.5-.5-.5h-.5a.9.9 0 0 0-.6.3A2.8 2.8 0 0 0 8.7 10a5 5 0 0 0 1 2.6 11.3 11.3 0 0 0 4.3 3.6c.4.2.8.3 1 .4a2.4 2.4 0 0 0 1.1.1 1.8 1.8 0 0 0 1.2-.9 1.5 1.5 0 0 0 .1-.8c0-.1-.2-.2-.3-.2Z"/></svg>
                                </span>
                                <span class="juntaplay-share__label"><?php esc_html_e('Enviar no WhatsApp', 'juntaplay'); ?></span>
                            </button>
                            <button
                                type="button"
                                class="juntaplay-share__button juntaplay-share__button--telegram"
                                data-jp-share-network="telegram"
                                data-jp-share-url="<?php echo esc_url($share_url); ?>"
                                data-jp-share-text="<?php echo esc_attr($share_message); ?>"
                            >
                                <span class="juntaplay-share__icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" focusable="false"><path fill="currentColor" d="m20.7 3.3-18 7a1 1 0 0 0 .1 1.9l4.8 1.4 2.2 4.4a1 1 0 0 0 1.7.2l2.5-3.1 4.2 2.6a1 1 0 0 0 1.5-.6l2.9-12a1 1 0 0 0-1.9-.8Zm-3.3 3.3-6.9 6.8a1 1 0 0 0-.3.7l.1 1.8-1.5-3a1 1 0 0 0-.6-.5l-3.2-.9 12.4-4.9Z"/></svg>
                                </span>
                                <span class="juntaplay-share__label"><?php esc_html_e('Compartilhar no Telegram', 'juntaplay'); ?></span>
                            </button>
                            <button
                                type="button"
                                class="juntaplay-share__button juntaplay-share__button--facebook"
                                data-jp-share-network="facebook"
                                data-jp-share-url="<?php echo esc_url($share_url); ?>"
                                data-jp-share-text="<?php echo esc_attr($share_message); ?>"
                            >
                                <span class="juntaplay-share__icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" focusable="false"><path fill="currentColor" d="M22 12a10 10 0 1 0-11.6 9.9v-7h-2.2V12h2.2V9.6c0-2.2 1.3-3.4 3.3-3.4.9 0 1.8.2 1.8.2v2H14c-1.1 0-1.4.7-1.4 1.3V12h2.5l-.4 2.9h-2.1v7A10 10 0 0 0 22 12Z"/></svg>
                                </span>
                                <span class="juntaplay-share__label"><?php esc_html_e('Compartilhar no Facebook', 'juntaplay'); ?></span>
                            </button>
                            <button
                                type="button"
                                class="juntaplay-share__button juntaplay-share__button--copy"
                                data-jp-share-copy
                                data-jp-share-url="<?php echo esc_url($share_url); ?>"
                                data-jp-share-label="<?php esc_attr_e('Copiar link', 'juntaplay'); ?>"
                                data-jp-share-copied="<?php esc_attr_e('Link copiado!', 'juntaplay'); ?>"
                            >
                                <span class="juntaplay-share__icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" focusable="false"><path fill="currentColor" d="M15 3h-8a3 3 0 0 0-3 3v11h2V6a1 1 0 0 1 1-1h8V3Zm3 4h-8a3 3 0 0 0-3 3v8a3 3 0 0 0 3 3h8a3 3 0 0 0 3-3v-8a3 3 0 0 0-3-3Zm1 11a1 1 0 0 1-1 1h-8a1 1 0 0 1-1-1v-8a1 1 0 0 1 1-1h8a1 1 0 0 1 1 1v8Z"/></svg>
                                </span>
                                <span class="juntaplay-share__label"><?php esc_html_e('Copiar link', 'juntaplay'); ?></span>
                            </button>
                        </div>
                    </div>
                <?php endif; ?>
            </section>
        <?php endif; ?>

        <?php if ($is_owner) : ?>
            <p class="juntaplay-group-modal__hint"><?php esc_html_e('Use o botão “Editar grupo” no cartão para atualizar as informações.', 'juntaplay'); ?></p>
        <?php endif; ?>
    </div>
</div>
