<?php
/**
 * Group access modal content.
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$group_id    = isset($group_id) ? (int) $group_id : 0;
$title       = isset($title) ? (string) $title : '';
$credentials = isset($credentials) && is_array($credentials) ? $credentials : [];
$hint        = isset($hint) ? (string) $hint : '';
$hint_html   = isset($hint_html) ? (string) $hint_html : '';
?>
<div class="juntaplay-group-modal__detail juntaplay-group-modal__detail--access" data-group-modal-view="access">
    <header class="juntaplay-group-modal__header">
        <div class="juntaplay-group-modal__headline">
            <h3 class="juntaplay-group-modal__title"><?php esc_html_e('Dados de acesso', 'juntaplay'); ?></h3>
            <?php if ($title !== '') : ?>
                <span class="juntaplay-group-modal__meta"><?php echo esc_html($title); ?></span>
            <?php endif; ?>
        </div>
    </header>

    <div class="juntaplay-group-modal__body">
        <div class="juntaplay-group-card__access-panel" data-group-access-panel>
            <h4 class="juntaplay-group-card__access-title"><?php esc_html_e('Dados de acesso do grupo', 'juntaplay'); ?></h4>
            <dl class="juntaplay-group-card__access-list" data-group-access-details<?php echo $credentials ? '' : ' hidden'; ?>>
                <?php foreach ($credentials as $field) : ?>
                    <?php
                    if (!is_array($field)) {
                        continue;
                    }
                    $label = isset($field['label']) ? (string) $field['label'] : '';
                    $value = isset($field['value']) ? (string) $field['value'] : '';
                    $type  = isset($field['type']) ? (string) $field['type'] : '';
                    $html  = isset($field['html']) ? (string) $field['html'] : '';

                    if ($label === '' && $value === '') {
                        continue;
                    }
                    ?>
                    <dt class="juntaplay-group-card__access-label"><?php echo esc_html($label); ?></dt>
                    <dd class="juntaplay-group-card__access-value">
                        <?php if ($type === 'url' && $value !== '') : ?>
                            <a href="<?php echo esc_url($value); ?>" target="_blank" rel="noopener"><?php echo esc_html($value); ?></a>
                        <?php elseif ($html !== '') : ?>
                            <?php echo wp_kses_post($html); ?>
                        <?php else : ?>
                            <?php echo esc_html($value); ?>
                        <?php endif; ?>
                    </dd>
                <?php endforeach; ?>
            </dl>
            <p class="juntaplay-group-card__access-hint" data-group-access-hint<?php echo $hint !== '' ? '' : ' hidden'; ?>>
                <?php echo $hint_html !== '' ? wp_kses_post($hint_html) : esc_html($hint); ?>
            </p>
        </div>

        <div class="juntaplay-group-modal__cta">
            <button
                type="button"
                class="juntaplay-button juntaplay-button--ghost"
                data-group-modal-back
                data-group-id="<?php echo esc_attr((string) $group_id); ?>"
            >
                <?php esc_html_e('Voltar aos detalhes do grupo', 'juntaplay'); ?>
            </button>
        </div>
    </div>
</div>
