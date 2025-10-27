<?php
/**
 * JuntaPlay my groups page shortcode template.
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$profile = isset($context['profile']) && is_array($context['profile']) ? $context['profile'] : [];
$summary = isset($context['summary']) && is_array($context['summary']) ? array_filter($context['summary'], 'is_array') : [];
$actions = isset($context['actions']) && is_array($context['actions']) ? array_filter($context['actions'], 'is_array') : [];
$group_context = isset($context['group_context']) && is_array($context['group_context']) ? $context['group_context'] : [];
$counts = isset($context['counts']) && is_array($context['counts']) ? $context['counts'] : [];
$open_complaints = isset($context['open_complaints']) ? (int) $context['open_complaints'] : 0;
$complaint_hint  = isset($context['complaint_hint']) ? (string) $context['complaint_hint'] : '';

$profile_name        = isset($profile['name']) ? (string) $profile['name'] : '';
$profile_email       = isset($profile['email']) ? (string) $profile['email'] : '';
$profile_description = isset($profile['description']) ? (string) $profile['description'] : '';

if ($profile_description === '') {
    $profile_description = $profile_name !== ''
        ? sprintf(__('Olá, %s! Organize os grupos que você administra ou participa.', 'juntaplay'), $profile_name)
        : __('Organize os grupos que você administra ou participa.', 'juntaplay');
}
?>
<section class="juntaplay-profile__group juntaplay-profile__group--groups">
    <header class="juntaplay-profile__group-header">
        <h2 class="juntaplay-profile__group-title"><?php esc_html_e('Meus grupos', 'juntaplay'); ?></h2>
        <p class="juntaplay-profile__group-description"><?php echo esc_html($profile_description); ?></p>
        <?php if ($profile_email !== '') : ?>
            <p class="juntaplay-profile__group-description"><?php echo esc_html(sprintf(__('Conta vinculada: %s', 'juntaplay'), $profile_email)); ?></p>
        <?php endif; ?>
        <?php if (isset($counts['total'])) : ?>
            <p class="juntaplay-profile__group-description"><?php echo esc_html(sprintf(__('Total de grupos listados: %s', 'juntaplay'), number_format_i18n((int) $counts['total']))); ?></p>
        <?php endif; ?>
    </header>

    <?php if ($summary) : ?>
        <div class="juntaplay-profile__summary">
            <?php foreach ($summary as $summary_item) :
                $summary_label = isset($summary_item['label']) ? (string) $summary_item['label'] : '';
                $summary_value = isset($summary_item['value']) ? (string) $summary_item['value'] : '';
                $summary_hint  = isset($summary_item['hint']) ? (string) $summary_item['hint'] : '';
                $summary_tone  = isset($summary_item['tone']) ? (string) $summary_item['tone'] : '';

                $summary_classes = ['juntaplay-profile__summary-item'];
                if ($summary_tone !== '') {
                    $summary_classes[] = 'juntaplay-profile__summary-item--' . sanitize_html_class($summary_tone);
                }
                ?>
                <article class="<?php echo esc_attr(implode(' ', $summary_classes)); ?>">
                    <?php if ($summary_label !== '') : ?>
                        <span class="juntaplay-profile__summary-label"><?php echo esc_html($summary_label); ?></span>
                    <?php endif; ?>
                    <?php if ($summary_value !== '') : ?>
                        <span class="juntaplay-profile__summary-value"><?php echo esc_html($summary_value); ?></span>
                    <?php endif; ?>
                    <?php if ($summary_hint !== '') : ?>
                        <span class="juntaplay-profile__summary-hint"><?php echo esc_html($summary_hint); ?></span>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($actions) : ?>
        <div class="juntaplay-profile__quick-nav juntaplay-profile__quick-nav--inline" role="navigation" aria-label="<?php esc_attr_e('Ações rápidas de grupos', 'juntaplay'); ?>">
            <?php foreach ($actions as $action) :
                $action_label   = isset($action['label']) ? (string) $action['label'] : '';
                $action_desc    = isset($action['description']) ? (string) $action['description'] : '';
                $action_variant = isset($action['variant']) ? (string) $action['variant'] : '';
                $action_icon    = isset($action['icon']) ? (string) $action['icon'] : '';
                $action_url     = isset($action['url']) ? (string) $action['url'] : '';
                $action_attrs   = isset($action['attributes']) && is_array($action['attributes']) ? $action['attributes'] : [];

                if ($action_label === '') {
                    continue;
                }

                $card_classes = ['juntaplay-profile__quick-card'];
                if ($action_variant !== '') {
                    $card_classes[] = 'juntaplay-profile__quick-card--' . sanitize_html_class($action_variant);
                }

                $attributes_markup = '';
                foreach ($action_attrs as $attr_name => $attr_value) {
                    if (!is_scalar($attr_value)) {
                        continue;
                    }

                    $attr_key = preg_replace('~[^a-z0-9_-]~i', '', (string) $attr_name);
                    if ($attr_key === '') {
                        continue;
                    }

                    $attributes_markup .= ' ' . esc_attr($attr_key) . '="' . esc_attr((string) $attr_value) . '"';
                }

                $icon_markup = '';
                switch ($action_icon) {
                    case 'plus':
                        $icon_markup = '<svg viewBox="0 0 20 20" aria-hidden="true" focusable="false"><path d="M10 4v12M4 10h12" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>';
                        break;
                    case 'search':
                        $icon_markup = '<svg viewBox="0 0 20 20" aria-hidden="true" focusable="false"><path d="m18 18-4.5-4.5M11 16a5 5 0 1 1 0-10 5 5 0 0 1 0 10Z" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>';
                        break;
                }

                if ($action_url !== '') : ?>
                    <a class="<?php echo esc_attr(implode(' ', $card_classes)); ?>" href="<?php echo esc_url($action_url); ?>">
                        <?php if ($icon_markup !== '') : ?>
                            <span class="juntaplay-profile__quick-icon"><?php echo $icon_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
                        <?php endif; ?>
                        <span class="juntaplay-profile__quick-text">
                            <span class="juntaplay-profile__quick-label"><?php echo esc_html($action_label); ?></span>
                            <?php if ($action_desc !== '') : ?>
                                <span class="juntaplay-profile__quick-summary"><?php echo esc_html($action_desc); ?></span>
                            <?php endif; ?>
                        </span>
                    </a>
                <?php else : ?>
                    <button type="button" class="<?php echo esc_attr(implode(' ', $card_classes)); ?>"<?php echo $attributes_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
                        <?php if ($icon_markup !== '') : ?>
                            <span class="juntaplay-profile__quick-icon"><?php echo $icon_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
                        <?php endif; ?>
                        <span class="juntaplay-profile__quick-text">
                            <span class="juntaplay-profile__quick-label"><?php echo esc_html($action_label); ?></span>
                            <?php if ($action_desc !== '') : ?>
                                <span class="juntaplay-profile__quick-summary"><?php echo esc_html($action_desc); ?></span>
                            <?php endif; ?>
                        </span>
                    </button>
                <?php endif;
            endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($open_complaints > 0 || $complaint_hint !== '') : ?>
        <div class="juntaplay-profile__alerts">
            <?php if ($open_complaints > 0) : ?>
                <div class="juntaplay-alert juntaplay-alert--warning">
                    <?php echo esc_html(sprintf(_n('Você possui %d reclamação aberta.', 'Você possui %d reclamações abertas.', $open_complaints, 'juntaplay'), $open_complaints)); ?>
                </div>
            <?php endif; ?>
            <?php if ($complaint_hint !== '') : ?>
                <div class="juntaplay-alert juntaplay-alert--info">
                    <?php echo esc_html($complaint_hint); ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</section>

<div class="juntaplay-profile__panels juntaplay-profile__panels--groups">
    <?php
    $group_context = $group_context ?: [];
    include JP_DIR . 'templates/profile-groups.php';
    ?>
</div>

<?php
$ajax_endpoint = admin_url('admin-ajax.php');
$ajax_nonce    = wp_create_nonce('jp_nonce');
?>
<div
    id="juntaplay-group-modal"
    class="juntaplay-modal"
    role="dialog"
    aria-modal="true"
    tabindex="-1"
    hidden
    data-ajax-endpoint="<?php echo esc_url($ajax_endpoint); ?>"
    data-ajax-nonce="<?php echo esc_attr($ajax_nonce); ?>"
>
    <div class="juntaplay-modal__overlay" data-group-modal-close></div>
    <div class="juntaplay-modal__dialog" role="document">
        <button type="button" class="juntaplay-modal__close" aria-label="<?php echo esc_attr__('Fechar', 'juntaplay'); ?>">&times;</button>
        <div class="juntaplay-modal__messages" data-modal-messages></div>
        <div class="juntaplay-modal__content"></div>
    </div>
</div>
