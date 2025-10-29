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
            <?php if ($support_channel !== '') : ?>
                <div>
                    <dt><?php esc_html_e('Suporte aos membros', 'juntaplay'); ?></dt>
                    <dd><?php echo esc_html($support_channel); ?></dd>
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
            </section>
        <?php endif; ?>

        <?php if ($is_owner) : ?>
            <p class="juntaplay-group-modal__hint"><?php esc_html_e('Use o botão “Editar grupo” no cartão para atualizar as informações.', 'juntaplay'); ?></p>
        <?php endif; ?>
    </div>
</div>
