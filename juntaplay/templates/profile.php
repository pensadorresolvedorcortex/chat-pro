<?php
/**
 * JuntaPlay profile editing template.
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('juntaplay_profile_icon')) {
    function juntaplay_profile_icon(string $icon): string
    {
        $icons = [
            'user'      => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 12a5 5 0 1 0-5-5 5 5 0 0 0 5 5Zm0 2c-4.418 0-8 2.239-8 5v1h16v-1c0-2.761-3.582-5-8-5Z" fill="currentColor"/></svg>',
            'shield'    => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 3 4 6v5c0 4.418 3.134 8.94 8 10 4.866-1.06 8-5.582 8-10V6Z" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>',
            'settings'  => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 15a3 3 0 1 0-3-3 3 3 0 0 0 3 3Zm7.94-2.06a1 1 0 0 0 0-1.88l-1.54-.63a7.967 7.967 0 0 0-.46-1.1l.24-1.64a1 1 0 0 0-.76-1.08l-1.9-.48a7.6 7.6 0 0 0-.88-.88l-.48-1.9a1 1 0 0 0-1.08-.76l-1.64.24a7.967 7.967 0 0 0-1.1-.46l-.63-1.54a1 1 0 0 0-1.88 0l-.63 1.54a7.967 7.967 0 0 0-1.1.46l-1.64-.24a1 1 0 0 0-1.08.76l-.48 1.9a7.6 7.6 0 0 0-.88.88l-1.9.48a1 1 0 0 0-.76 1.08l.24 1.64a7.967 7.967 0 0 0-.46 1.1l-1.54.63a1 1 0 0 0 0 1.88l1.54.63a7.967 7.967 0 0 0 .46 1.1l-.24 1.64a1 1 0 0 0 .76 1.08l1.9.48a7.6 7.6 0 0 0 .88.88l.48 1.9a1 1 0 0 0 1.08.76l1.64-.24a7.967 7.967 0 0 0 1.1.46l.63 1.54a1 1 0 0 0 1.88 0l.63-1.54a7.967 7.967 0 0 0 1.1-.46l1.64.24a1 1 0 0 0 1.08-.76l.48-1.9a7.6 7.6 0 0 0 .88-.88l1.9-.48a1 1 0 0 0 .76-1.08l-.24-1.64a7.967 7.967 0 0 0 .46-1.1Z" fill="none" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/></svg>',
            'wallet'    => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M20 7V6a2 2 0 0 0-2-2H6a4 4 0 0 0 0 8h12" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M4 10v8a2 2 0 0 0 2 2h13a1 1 0 0 0 1-1v-4.382a1 1 0 0 0-.553-.894L17 12l2.447-1.724A1 1 0 0 0 20 9.382V8a1 1 0 0 0-1-1H6" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>',
            'credit'    => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><rect x="3" y="5" width="18" height="14" rx="2" ry="2" fill="none" stroke="currentColor" stroke-width="1.5"/><path d="M3 10h18M7 15h2m3 0h2" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>',
            'security'  => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 2 4 5v6c0 5.25 3.438 10.688 8 12 4.563-1.313 8-6.75 8-12V5Z" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/><path d="M9 12.25 11.25 14.5 15 10" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>',
            'groups'    => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4Zm6 0a3 3 0 1 0-3-3 3 3 0 0 0 3 3Zm-12 0a3 3 0 1 0-3-3 3 3 0 0 0 3 3Zm0 2c-2.21 0-6 1.11-6 3.33V20h6Zm12 0c2.21 0 6 1.11 6 3.33V20h-6Zm-6 0c2.21 0 6 1.11 6 3.33V20H6v-2.67C6 15.11 9.79 14 12 14Z" fill="none" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/></svg>',
            'arrow-right' => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M5 12h14M13 6l6 6-6 6" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>',
            'document' => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M7 2h8l5 5v13a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2Zm8 0v5h5" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>',
            'lock'      => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><rect x="4" y="10" width="16" height="11" rx="2" ry="2" fill="none" stroke="currentColor" stroke-width="1.4"/><path d="M8 10V7a4 4 0 0 1 8 0v3" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>',
            'search'    => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="m21 21-4.35-4.35M10.5 18a7.5 7.5 0 1 0 0-15 7.5 7.5 0 0 0 0 15Z" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>',
            'support'   => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 3a9 9 0 0 0-9 9v3a3 3 0 0 0 3 3h1v-7H6a6 6 0 0 1 12 0h-1v7h1a3 3 0 0 0 3-3v-3a9 9 0 0 0-9-9Z" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/><path d="M9 21h6" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>',
            'logout'    => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M16 17l5-5-5-5M21 12H9" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/><path d="M11 4H5a1 1 0 0 0-1 1v14a1 1 0 0 0 1 1h6" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>',
            'ticket'    => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M4 7a1 1 0 0 1 1-1h14a1 1 0 0 1 1 1v2a2 2 0 0 0 0 4v2a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-2a2 2 0 0 0 0-4Z" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/><path d="M9 5v14" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>',
            'invoice'   => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M7 3h10l3 4v13a1 1 0 0 1-1 1H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/><path d="M9 9h6M9 13h6M9 17h3" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>',
            'close'     => '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="m6 6 12 12M18 6 6 18" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        ];

        return $icons[$icon] ?? '';
    }
}

if (!function_exists('juntaplay_profile_render_item')) {
    /**
     * @param array<string, mixed> $section
     * @param array<string, array<int, string>> $profile_errors
     */
    function juntaplay_profile_render_item(string $section_key, array $section, ?string $active_section, array $profile_errors): void
    {
        $is_editing     = $active_section === $section_key;
        $section_errors = $profile_errors[$section_key] ?? [];
        if ($section_errors && !$is_editing) {
            $is_editing = true;
        }

        $value          = isset($section['value']) ? (string) $section['value'] : '';
        $display_value  = isset($section['display_value']) ? (string) $section['display_value'] : $value;
        $label          = isset($section['label']) ? (string) $section['label'] : '';
        $description    = isset($section['description']) ? (string) $section['description'] : '';
        $placeholder    = isset($section['placeholder']) ? (string) $section['placeholder'] : '';
        $type           = isset($section['type']) ? (string) $section['type'] : 'text';
        $options        = isset($section['options']) && is_array($section['options']) ? $section['options'] : [];
        $autocomplete   = isset($section['autocomplete']) ? (string) $section['autocomplete'] : '';
        $fields         = isset($section['fields']) && is_array($section['fields']) ? $section['fields'] : [];
        $attributes     = isset($section['attributes']) && is_array($section['attributes']) ? $section['attributes'] : [];
        $submit_label   = isset($section['submit_label']) ? (string) $section['submit_label'] : __('Salvar', 'juntaplay');
        $confirm_message = isset($section['confirmation']) ? (string) $section['confirmation'] : '';
        $editable       = array_key_exists('editable', $section) ? (bool) $section['editable'] : true;
        $template_name  = isset($section['template']) ? (string) $section['template'] : '';
        $context        = isset($section['context']) && is_array($section['context']) ? $section['context'] : [];
        $custom_html    = isset($section['html']) ? (string) $section['html'] : '';

        if ($autocomplete === '') {
            if ($section_key === 'email') {
                $autocomplete = 'email';
            } elseif ($section_key === 'name') {
                $autocomplete = 'name';
            } elseif (in_array($type, ['tel', 'text'], true)) {
                $autocomplete = 'on';
            }
        }

        if ($display_value === '' && $value !== '' && isset($options[$value])) {
            $display_value = (string) $options[$value];
        }

        if ($type === 'custom') {
            $row_classes = ['juntaplay-profile__row', 'juntaplay-profile__row--custom'];
            ?>
            <li class="<?php echo esc_attr(implode(' ', $row_classes)); ?>" data-section="<?php echo esc_attr($section_key); ?>">
                <div class="juntaplay-profile__content">
                    <?php if ($label !== '') : ?>
                        <div class="juntaplay-profile__label"><?php echo esc_html($label); ?></div>
                    <?php endif; ?>
                    <?php if ($description !== '') : ?>
                        <p class="juntaplay-profile__description"><?php echo esc_html($description); ?></p>
                    <?php endif; ?>
                </div>
                <div class="juntaplay-profile__custom">
                    <?php
                    $template_file = '';
                    if ($template_name !== '') {
                        $template_file = JP_DIR . 'templates/' . ltrim($template_name, '/');
                    }

                    $group_context = $context;
                    $group_context['errors']      = $section_errors;
                    $group_context['section_key'] = $section_key;

                    if ($template_file !== '' && file_exists($template_file)) {
                        /** @var array<string, mixed> $group_context */
                        include $template_file;
                    } elseif ($custom_html !== '') {
                        echo wp_kses_post($custom_html);
                    } else {
                        echo '<p class="juntaplay-profile__empty">' . esc_html__('Conteúdo indisponível no momento.', 'juntaplay') . '</p>';
                    }
                    ?>
                </div>
            </li>
            <?php
            return;
        }

        if (!$editable) {
            $is_editing = false;
        }

        $row_classes = ['juntaplay-profile__row'];
        if ($is_editing) {
            $row_classes[] = 'is-editing';
        }

        if ($type === 'action') {
            $row_classes[] = 'juntaplay-profile__row--action';
        }

        $button_class = $type === 'action'
            ? 'juntaplay-button juntaplay-button--danger'
            : 'juntaplay-button juntaplay-button--primary';

        $show_cancel = $type !== 'action';
        ?>
        <li class="<?php echo esc_attr(implode(' ', $row_classes)); ?>" data-section="<?php echo esc_attr($section_key); ?>">
            <div class="juntaplay-profile__content">
                <div class="juntaplay-profile__label"><?php echo esc_html($label); ?></div>
                <div class="juntaplay-profile__value"><?php echo $display_value !== '' ? esc_html($display_value) : '<span class="juntaplay-profile__empty">' . esc_html__('Não informado', 'juntaplay') . '</span>'; ?></div>
                <?php if ($description) : ?>
                    <p class="juntaplay-profile__description"><?php echo esc_html($description); ?></p>
                <?php endif; ?>
            </div>
            <?php if ($editable) : ?>
                <div class="juntaplay-profile__actions">
                    <button type="button" class="juntaplay-profile__edit" data-toggle="<?php echo esc_attr($section_key); ?>" aria-expanded="<?php echo $is_editing ? 'true' : 'false'; ?>">
                        <?php echo esc_html__('Alterar', 'juntaplay'); ?>
                    </button>
                </div>
                <div class="juntaplay-profile__form" role="region" aria-hidden="<?php echo $is_editing ? 'false' : 'true'; ?>">
                    <form method="post" class="juntaplay-form"<?php echo $confirm_message !== '' ? ' data-confirm="' . esc_attr($confirm_message) . '"' : ''; ?>>
                        <input type="hidden" name="jp_profile_action" value="1" />
                        <input type="hidden" name="jp_profile_section" value="<?php echo esc_attr($section_key); ?>" />
                        <?php wp_nonce_field('juntaplay_profile_update', 'jp_profile_nonce'); ?>
                        <?php if ($fields) : ?>
                            <?php foreach ($fields as $field) :
                                if (!is_array($field) || empty($field['name'])) {
                                    continue;
                                }

                                $field_name        = (string) $field['name'];
                                $field_id          = 'juntaplay-field-' . $section_key . '-' . $field_name;
                                $field_label       = isset($field['label']) ? (string) $field['label'] : '';
                                $field_type        = isset($field['type']) ? (string) $field['type'] : 'text';
                                $field_placeholder = isset($field['placeholder']) ? (string) $field['placeholder'] : '';
                                $field_autocomplete = isset($field['autocomplete']) ? (string) $field['autocomplete'] : '';
                                $field_value       = isset($field['value']) ? (string) $field['value'] : '';
                                $field_options     = isset($field['options']) && is_array($field['options']) ? $field['options'] : [];
                                $field_help        = isset($field['help']) ? (string) $field['help'] : '';
                                $field_attributes  = isset($field['attributes']) && is_array($field['attributes']) ? $field['attributes'] : [];
                                $attributes_html   = '';

                                foreach ($field_attributes as $attr_key => $attr_value) {
                                    if ($attr_value === null || $attr_value === '') {
                                        continue;
                                    }

                                    $attributes_html .= ' ' . esc_attr((string) $attr_key) . '="' . esc_attr((string) $attr_value) . '"';
                                }
                                ?>
                                <div class="juntaplay-form__group">
                                    <?php if ($field_label) : ?>
                                        <label for="<?php echo esc_attr($field_id); ?>"><?php echo esc_html($field_label); ?></label>
                                    <?php endif; ?>
                                    <?php if ($field_type === 'select' && $field_options) : ?>
                                        <select
                                            id="<?php echo esc_attr($field_id); ?>"
                                            name="<?php echo esc_attr('jp_profile_' . $field_name); ?>"
                                            class="juntaplay-form__input"
                                            <?php echo $attributes_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                        >
                                            <?php foreach ($field_options as $option_value => $option_label) : ?>
                                                <option value="<?php echo esc_attr((string) $option_value); ?>" <?php selected((string) $option_value, $field_value); ?>><?php echo esc_html((string) $option_label); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    <?php elseif ($field_type === 'textarea') : ?>
                                        <textarea
                                            id="<?php echo esc_attr($field_id); ?>"
                                            name="<?php echo esc_attr('jp_profile_' . $field_name); ?>"
                                            class="juntaplay-form__input"
                                            rows="4"
                                            placeholder="<?php echo esc_attr($field_placeholder); ?>"
                                            <?php echo $attributes_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                        ><?php echo esc_textarea($field_value); ?></textarea>
                                    <?php else : ?>
                                        <input
                                            type="<?php echo esc_attr($field_type); ?>"
                                            id="<?php echo esc_attr($field_id); ?>"
                                            name="<?php echo esc_attr('jp_profile_' . $field_name); ?>"
                                            class="juntaplay-form__input"
                                            value="<?php echo esc_attr($field_value); ?>"
                                            placeholder="<?php echo esc_attr($field_placeholder); ?>"
                                            autocomplete="<?php echo esc_attr($field_autocomplete ?: 'on'); ?>"
                                            <?php echo $attributes_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                        />
                                    <?php endif; ?>
                                    <?php if ($field_help) : ?>
                                        <p class="juntaplay-form__help"><?php echo esc_html($field_help); ?></p>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <div class="juntaplay-form__group">
                                <label for="<?php echo esc_attr('juntaplay-field-' . $section_key); ?>"><?php echo esc_html($label); ?></label>
                                <input
                                    type="<?php echo esc_attr($type); ?>"
                                    id="<?php echo esc_attr('juntaplay-field-' . $section_key); ?>"
                                    name="<?php echo esc_attr('jp_profile_' . $section_key); ?>"
                                    class="juntaplay-form__input"
                                    value="<?php echo esc_attr($value); ?>"
                                    placeholder="<?php echo esc_attr($placeholder); ?>"
                                    autocomplete="<?php echo esc_attr($autocomplete); ?>"
                                    <?php
                                    $section_attributes_html = '';
                                    foreach ($attributes as $attr_key => $attr_value) {
                                        if ($attr_value === null || $attr_value === '') {
                                            continue;
                                        }

                                        $section_attributes_html .= ' ' . esc_attr((string) $attr_key) . '="' . esc_attr((string) $attr_value) . '"';
                                    }
                                    echo $section_attributes_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                    ?>
                                />
                            </div>
                        <?php endif; ?>

                        <?php if ($section_errors) : ?>
                            <div class="juntaplay-profile__alerts">
                                <?php foreach ($section_errors as $error_message) : ?>
                                    <div class="juntaplay-alert juntaplay-alert--error"><?php echo esc_html($error_message); ?></div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <div class="juntaplay-profile__form-actions">
                            <button type="submit" class="<?php echo esc_attr($button_class); ?>"><?php echo esc_html($submit_label); ?></button>
                            <?php if ($show_cancel) : ?>
                                <button type="button" class="juntaplay-button juntaplay-button--ghost juntaplay-profile__cancel"><?php echo esc_html__('Cancelar', 'juntaplay'); ?></button>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </li>
        <?php
    }
}

if (!function_exists('juntaplay_profile_render_group')) {
    /**
     * @param array<string, mixed> $group
     * @param array<string, array<int, string>> $profile_errors
     * @param string[]|null $visible_items
     */
    function juntaplay_profile_render_group(string $group_key, array $group, ?string $active_section, array $profile_errors, ?array $visible_items = null): void
    {
        $group_items = isset($group['items']) && is_array($group['items']) ? $group['items'] : [];

        if (!$group_items) {
            return;
        }

        if ($visible_items !== null) {
            $group_items = array_intersect_key($group_items, array_flip($visible_items));
        }

        if (!$group_items) {
            return;
        }

        $group_title       = isset($group['title']) ? (string) $group['title'] : '';
        $group_description = isset($group['description']) ? (string) $group['description'] : '';
        $group_notice      = isset($group['notice']) ? (string) $group['notice'] : '';

        ?>
        <section class="juntaplay-profile__group" data-group="<?php echo esc_attr($group_key); ?>">
            <?php if ($group_title || $group_description) : ?>
                <header class="juntaplay-profile__group-header">
                    <?php if ($group_title) : ?>
                        <h2 class="juntaplay-profile__group-title"><?php echo esc_html($group_title); ?></h2>
                    <?php endif; ?>
                    <?php if ($group_description) : ?>
                        <p class="juntaplay-profile__group-description"><?php echo esc_html($group_description); ?></p>
                    <?php endif; ?>
                </header>
            <?php endif; ?>

            <?php if ($group_notice) : ?>
                <div class="juntaplay-profile__alerts">
                    <div class="juntaplay-alert juntaplay-alert--info"><?php echo esc_html($group_notice); ?></div>
                </div>
            <?php endif; ?>

            <?php
            $group_summary = [];
            if (isset($group['summary']) && is_array($group['summary'])) {
                $group_summary = array_filter($group['summary'], 'is_array');
            }

            if ($group_summary) :
                ?>
                <div class="juntaplay-profile__summary">
                    <?php foreach ($group_summary as $summary_item) :
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

            <ul class="juntaplay-profile__list" role="list">
                <?php foreach ($group_items as $section_key => $section) :
                    if (!is_array($section)) {
                        continue;
                    }

                    juntaplay_profile_render_item($section_key, $section, $active_section, $profile_errors);
                endforeach; ?>
            </ul>
        </section>
        <?php
    }
}

$user             = wp_get_current_user();
$profile_sections = $profile_sections ?? [];
$profile_errors   = $profile_errors ?? [];
$profile_notices  = $profile_notices ?? [];
$active_section   = $profile_active_section ?? null;
$display_name     = '';
$user_email       = '';
$member_since     = '';

if ($user && $user->exists()) {
    $display_name = wp_strip_all_tags((string) $user->display_name);
    $user_email   = wp_strip_all_tags((string) $user->user_email);

    if (!empty($user->user_registered)) {
        $member_since = date_i18n(get_option('date_format'), strtotime((string) $user->user_registered));
    }
}

if ($display_name === '') {
    $display_name = wp_strip_all_tags(__('Jogue com a gente', 'juntaplay'));
}

$notifications_unread = \JuntaPlay\Data\Notifications::count_unread(get_current_user_id());

$profile_categories = [
    'account' => [
        'label'       => __('Configurações', 'juntaplay'),
        'description' => __('Gerencie dados pessoais, fiscais e de segurança da sua conta.', 'juntaplay'),
        'icon'        => 'settings',
        'tabs'        => [
            'account_personal' => [
                'label'  => __('Dados pessoais', 'juntaplay'),
                'icon'   => 'user',
                'groups' => [
                    ['key' => 'contact'],
                ],
            ],
            'account_fiscal' => [
                'label'  => __('Dados fiscais', 'juntaplay'),
                'icon'   => 'document',
                'groups' => [
                    ['key' => 'fiscal'],
                ],
            ],
            'account_security' => [
                'label'  => __('Segurança', 'juntaplay'),
                'icon'   => 'shield',
                'groups' => [
                    ['key' => 'security'],
                ],
            ],
        ],
    ],
    'finance' => [
        'label'       => __('Financeiro', 'juntaplay'),
        'description' => __('Controle sua carteira, preferências e dados bancários.', 'juntaplay'),
        'icon'        => 'wallet',
        'tabs'        => [
            'finance_overview' => [
                'label'  => __('Carteira e extrato', 'juntaplay'),
                'icon'   => 'credit',
                'groups' => [
                    ['key' => 'credits', 'items' => ['credit_history']],
                ],
            ],
            'finance_preferences' => [
                'label'  => __('Preferências de pagamento', 'juntaplay'),
                'icon'   => 'settings',
                'groups' => [
                    ['key' => 'credits', 'items' => ['credit_auto', 'credit_payment_method', 'credit_pix_key', 'credit_bank_account']],
                ],
            ],
        ],
    ],
    'community' => [
        'label'       => __('Comunidade', 'juntaplay'),
        'description' => __('Acompanhe a sua rede de participantes.', 'juntaplay'),
        'icon'        => 'groups',
        'tabs'        => [
            'community_network' => [
                'label'  => __('Minha rede', 'juntaplay'),
                'icon'   => 'user',
                'groups' => [
                    ['key' => 'network'],
                ],
            ],
        ],
    ],
];

$section_to_tab = [];

foreach ($profile_categories as $category_id => &$category) {
    if (!isset($category['tabs']) || !is_array($category['tabs'])) {
        unset($profile_categories[$category_id]);
        continue;
    }

    $valid_tabs = [];
    foreach ($category['tabs'] as $tab_id => $tab) {
        if (!isset($tab['groups']) || !is_array($tab['groups'])) {
            continue;
        }

        $tab_groups = [];
        foreach ($tab['groups'] as $group_spec) {
            if (is_string($group_spec)) {
                $group_key   = $group_spec;
                $visible_set = null;
            } elseif (is_array($group_spec) && isset($group_spec['key'])) {
                $group_key   = (string) $group_spec['key'];
                $visible_set = isset($group_spec['items']) && is_array($group_spec['items']) ? array_values(array_map('strval', $group_spec['items'])) : null;
            } else {
                continue;
            }

            if (!isset($profile_sections[$group_key]) || !is_array($profile_sections[$group_key])) {
                continue;
            }

            $group_items = isset($profile_sections[$group_key]['items']) && is_array($profile_sections[$group_key]['items']) ? $profile_sections[$group_key]['items'] : [];
            if ($visible_set !== null) {
                $group_items = array_intersect_key($group_items, array_flip($visible_set));
            }

            if (!$group_items) {
                continue;
            }

            $tab_groups[] = [
                'key'   => $group_key,
                'items' => $visible_set,
            ];

            foreach ($group_items as $section_key => $_section_data) {
                if ($visible_set !== null && !in_array($section_key, $visible_set, true)) {
                    continue;
                }

                $section_to_tab[$section_key] = [
                    'category' => $category_id,
                    'tab'      => $tab_id,
                ];
            }

            if ($group_key === 'groups') {
                $section_to_tab['group_create'] = [
                    'category' => $category_id,
                    'tab'      => $tab_id,
                ];
            }

            if ($group_key === 'credits') {
                $section_to_tab['credit_withdrawal'] = [
                    'category' => $category_id,
                    'tab'      => $tab_id,
                ];
            }
        }

        if ($tab_groups) {
            $tab['groups']        = $tab_groups;
            $valid_tabs[$tab_id]  = $tab;
        }
    }

    if ($valid_tabs) {
        $category['tabs'] = $valid_tabs;
    } else {
        unset($profile_categories[$category_id]);
    }
}
unset($category);

if (!$profile_categories) {
    return;
}

$requested_category = isset($_GET['jp_category']) ? sanitize_key(wp_unslash($_GET['jp_category'])) : '';
$requested_tab      = isset($_GET['jp_tab']) ? sanitize_key(wp_unslash($_GET['jp_tab'])) : '';

$active_category_id = array_key_first($profile_categories);
$active_tab_id      = '';

if ($active_section && isset($section_to_tab[$active_section])) {
    $active_category_id = $section_to_tab[$active_section]['category'];
    $active_tab_id      = $section_to_tab[$active_section]['tab'];
} elseif ($active_section && strpos($active_section, 'group_complaint_') === 0 && isset($section_to_tab['group_create'])) {
    $active_category_id = $section_to_tab['group_create']['category'];
    $active_tab_id      = $section_to_tab['group_create']['tab'];
}

if ($requested_category && isset($profile_categories[$requested_category])) {
    $active_category_id = $requested_category;
}

if ($active_tab_id === '' && $requested_tab && isset($profile_categories[$active_category_id]['tabs'][$requested_tab])) {
    $active_tab_id = $requested_tab;
}

if ($active_tab_id === '' && isset($profile_categories[$active_category_id]['tabs'])) {
    $active_tab_id = array_key_first($profile_categories[$active_category_id]['tabs']);
}

if (!isset($profile_categories[$active_category_id]['tabs'][$active_tab_id])) {
    $active_tab_id = array_key_first($profile_categories[$active_category_id]['tabs']);
}

$logout_redirect = home_url('/');
$logout_url      = wp_logout_url($logout_redirect);

$statement_page_id = (int) get_option('juntaplay_page_extrato');
$statement_url      = $statement_page_id ? get_permalink($statement_page_id) : '';

if ($statement_url === '' && function_exists('wc_get_account_endpoint_url')) {
    $statement_url = wc_get_account_endpoint_url('orders');
}

$groups_page_id = (int) get_option('juntaplay_page_grupos');
$groups_url      = $groups_page_id ? get_permalink($groups_page_id) : home_url('/grupos/');
$my_groups_page_id = (int) get_option('juntaplay_page_meus-grupos');
$my_groups_url      = $my_groups_page_id ? get_permalink($my_groups_page_id) : home_url('/meus-grupos/');

$search_page_id = (int) get_option('juntaplay_page_campanhas');
$search_url      = $search_page_id ? get_permalink($search_page_id) : home_url('/campanhas');

$help_url = home_url('/atendimento');
$help_page_id = (int) get_option('juntaplay_page_regras');
if ($help_page_id) {
    $help_url = get_permalink($help_page_id);
}

$profile_menu_links = [
    [
        'type'     => 'category',
        'label'    => __('Perfil', 'juntaplay'),
        'icon'     => 'user',
        'category' => 'account',
        'tab'      => 'account_personal',
    ],
    [
        'type'     => 'category',
        'label'    => __('Créditos', 'juntaplay'),
        'icon'     => 'wallet',
        'category' => 'finance',
        'tab'      => 'finance_overview',
    ],
    [
        'type'  => 'link',
        'label' => __('Faturas', 'juntaplay'),
        'icon'  => 'invoice',
        'url'   => $statement_url ?: home_url('/minhas-cotas'),
    ],
    [
        'type'  => 'link',
        'label' => __('Pesquisar', 'juntaplay'),
        'icon'  => 'search',
        'url'   => $search_url,
    ],
    [
        'type'  => 'link',
        'label' => __('Ajuda', 'juntaplay'),
        'icon'  => 'support',
        'url'   => $help_url,
    ],
    [
        'type'  => 'logout',
        'label' => __('Sair', 'juntaplay'),
        'icon'  => 'logout',
        'url'   => $logout_url,
    ],
];

$profile_menu_links = array_values(array_filter($profile_menu_links, function (array $item) use ($profile_categories): bool {
    if ($item['type'] === 'category') {
        $category = $item['category'] ?? '';
        $tab      = $item['tab'] ?? '';

        return $category && isset($profile_categories[$category]) && ($tab === '' || isset($profile_categories[$category]['tabs'][$tab]));
    }

    return !empty($item['url']);
}));

$profile_menu_links = apply_filters('juntaplay/profile/menu_links', $profile_menu_links, $profile_categories);

$profile_quick_actions = [
    [
        'label'    => __('Meus dados', 'juntaplay'),
        'summary'  => __('Informações pessoais e contato', 'juntaplay'),
        'icon'     => 'user',
        'category' => 'account',
        'tab'      => 'account_personal',
    ],
    [
        'label'    => __('Dados fiscais', 'juntaplay'),
        'summary'  => __('CPF/CNPJ e endereço de faturamento', 'juntaplay'),
        'icon'     => 'document',
        'category' => 'account',
        'tab'      => 'account_fiscal',
    ],
    [
        'label'    => __('Segurança', 'juntaplay'),
        'summary'  => __('Ajuste senha e autenticação 2FA', 'juntaplay'),
        'icon'     => 'shield',
        'category' => 'account',
        'tab'      => 'account_security',
    ],
    [
        'label'    => __('Carteira', 'juntaplay'),
        'summary'  => __('Saldo, recargas e retiradas', 'juntaplay'),
        'icon'     => 'wallet',
        'category' => 'finance',
        'tab'      => 'finance_overview',
    ],
    [
        'label'    => __('Pagamentos', 'juntaplay'),
        'summary'  => __('Preferências e chaves Pix', 'juntaplay'),
        'icon'     => 'settings',
        'category' => 'finance',
        'tab'      => 'finance_preferences',
    ],
    [
        'label'    => __('Meus grupos', 'juntaplay'),
        'summary'  => __('Gerencie e acompanhe campanhas', 'juntaplay'),
        'icon'     => 'groups',
        'url'      => $my_groups_url,
    ],
    [
        'label'    => __('Minha rede', 'juntaplay'),
        'summary'  => __('Participantes dos seus grupos', 'juntaplay'),
        'icon'     => 'user',
        'category' => 'community',
        'tab'      => 'community_network',
    ],
];

$profile_quick_actions = array_values(array_filter($profile_quick_actions, function (array $action) use ($profile_categories): bool {
    if (!empty($action['url'])) {
        return true;
    }

    $category = $action['category'] ?? '';
    $tab      = $action['tab'] ?? '';

    return $category && $tab && isset($profile_categories[$category]['tabs'][$tab]);
}));

$profile_quick_actions = apply_filters('juntaplay/profile/quick_actions', $profile_quick_actions, $profile_categories);

$avatar_html = '';
if ($user && $user->exists()) {
    $avatar_url = '';
    $custom_avatar = get_user_meta($user->ID, 'juntaplay_avatar_url', true);

    if (is_string($custom_avatar)) {
        $custom_avatar = trim($custom_avatar);
        if ($custom_avatar !== '') {
            $avatar_url = esc_url_raw($custom_avatar);
        }
    }

    if ($avatar_url === '') {
        $avatar_id = get_user_meta($user->ID, 'juntaplay_avatar_id', true);
        if ($avatar_id) {
            $maybe_url = wp_get_attachment_image_url((int) $avatar_id, 'thumbnail');
            if (is_string($maybe_url) && $maybe_url !== '') {
                $avatar_url = $maybe_url;
            }
        }
    }

    if ($avatar_url === '') {
        $avatar_url = get_avatar_url($user->ID, ['size' => 160]);
    }

    if ($avatar_url) {
        $avatar_label = $display_name !== ''
            ? $display_name
            : wp_strip_all_tags(__('assinante', 'juntaplay'));

        $avatar_html = sprintf(
            '<img src="%1$s" alt="%2$s" class="juntaplay-profile__avatar-img" loading="lazy" />',
            esc_url($avatar_url),
            esc_attr(sprintf(__('Avatar de %s', 'juntaplay'), $avatar_label))
        );
    }
}

$display_initial = function_exists('mb_substr') ? mb_substr($display_name, 0, 1) : substr($display_name, 0, 1);
if ($display_initial === '') {
    $display_initial = 'J';
}

if (function_exists('mb_strtoupper')) {
    $display_initial = mb_strtoupper($display_initial);
} else {
    $display_initial = strtoupper($display_initial);
}
?>
<div class="juntaplay-profile" data-profile>
    <div class="juntaplay-profile__menu-overlay" data-profile-menu-overlay aria-hidden="true"></div>
    <div class="juntaplay-profile__toolbar" data-jp-notifications-root>
        <button type="button" class="juntaplay-notification-bell" data-jp-notifications aria-haspopup="true" aria-expanded="false"<?php if ($notifications_unread > 0) : ?> data-count="<?php echo esc_attr($notifications_unread); ?>"<?php endif; ?>>
            <span class="screen-reader-text"><?php esc_html_e('Abrir notificações', 'juntaplay'); ?></span>
            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                <path d="M12 22a2 2 0 0 0 1.995-1.85L14 20h-4a2 2 0 0 0 1.85 1.995L12 22Zm7-6v-5a7 7 0 0 0-5-6.708V4a2 2 0 1 0-4 0v.292A7.002 7.002 0 0 0 6 11v5l-1.447 2.894A1 1 0 0 0 5.447 20h13.106a1 1 0 0 0 .894-1.447Z" fill="none" stroke-width="1.5" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
        </button>
        <div class="juntaplay-notifications" data-jp-notifications-panel aria-hidden="true">
            <div class="juntaplay-notifications__header">
                <h4><?php esc_html_e('Notificações', 'juntaplay'); ?></h4>
            </div>
            <ul class="juntaplay-notifications__list" data-jp-notifications-list>
                <li class="juntaplay-notifications__empty"><?php esc_html_e('Carregando notificações...', 'juntaplay'); ?></li>
            </ul>
            <div class="juntaplay-notifications__footer">
                <button type="button" data-jp-notifications-close><?php esc_html_e('Fechar', 'juntaplay'); ?></button>
            </div>
        </div>
    </div>
    <header class="juntaplay-profile__hero">
        <div class="juntaplay-profile__hero-card">
            <div class="juntaplay-profile__avatar" aria-hidden="true">
                <?php if ($avatar_html) : ?>
                    <?php echo $avatar_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                <?php else : ?>
                    <span class="juntaplay-profile__avatar-placeholder"><?php echo esc_html($display_initial); ?></span>
                <?php endif; ?>
            </div>
            <div class="juntaplay-profile__hero-details">
                <span class="juntaplay-profile__hero-label"><?php esc_html_e('Minha conta', 'juntaplay'); ?></span>
                <h1><?php echo esc_html(sprintf(__('Olá, %s', 'juntaplay'), $display_name)); ?></h1>
                <?php if ($user_email !== '') : ?>
                    <p class="juntaplay-profile__hero-email"><?php echo esc_html($user_email); ?></p>
                <?php endif; ?>
                <?php if ($member_since !== '') : ?>
                    <p class="juntaplay-profile__hero-meta"><?php echo esc_html(sprintf(__('Membro desde %s', 'juntaplay'), $member_since)); ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php if ($profile_quick_actions) : ?>
            <div class="juntaplay-profile__quick-nav" role="navigation" aria-label="<?php esc_attr_e('Atalhos do perfil', 'juntaplay'); ?>">
                <?php foreach ($profile_quick_actions as $quick) :
                    $quick_label   = (string) ($quick['label'] ?? '');
                    $quick_summary = (string) ($quick['summary'] ?? '');
                    $quick_icon    = (string) ($quick['icon'] ?? 'user');
                    $quick_url     = isset($quick['url']) ? (string) $quick['url'] : '';

                    if ($quick_label === '') {
                        continue;
                    }

                    $icon_markup = juntaplay_profile_icon($quick_icon); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    if ($quick_url !== '') : ?>
                        <a class="juntaplay-profile__quick-card" href="<?php echo esc_url($quick_url); ?>">
                            <span class="juntaplay-profile__quick-icon"><?php echo $icon_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
                            <span class="juntaplay-profile__quick-text">
                                <span class="juntaplay-profile__quick-label"><?php echo esc_html($quick_label); ?></span>
                                <?php if ($quick_summary !== '') : ?>
                                    <span class="juntaplay-profile__quick-summary"><?php echo esc_html($quick_summary); ?></span>
                                <?php endif; ?>
                            </span>
                        </a>
                    <?php else : ?>
                        <button type="button"
                            class="juntaplay-profile__quick-card"
                            data-profile-quick
                            data-profile-quick-category="<?php echo esc_attr((string) ($quick['category'] ?? '')); ?>"
                            data-profile-quick-tab="<?php echo esc_attr((string) ($quick['tab'] ?? '')); ?>"
                        >
                            <span class="juntaplay-profile__quick-icon"><?php echo $icon_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
                            <span class="juntaplay-profile__quick-text">
                                <span class="juntaplay-profile__quick-label"><?php echo esc_html($quick_label); ?></span>
                                <?php if ($quick_summary !== '') : ?>
                                    <span class="juntaplay-profile__quick-summary"><?php echo esc_html($quick_summary); ?></span>
                                <?php endif; ?>
                            </span>
                        </button>
                    <?php endif;
                endforeach; ?>
            </div>
        <?php endif; ?>
    </header>

    <?php if ($profile_notices) : ?>
        <div class="juntaplay-profile__alerts">
            <?php foreach ($profile_notices as $notice) : ?>
                <div class="juntaplay-alert juntaplay-alert--success"><?php echo esc_html($notice); ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($profile_errors['general'])) : ?>
        <div class="juntaplay-profile__alerts">
            <?php foreach ($profile_errors['general'] as $message) : ?>
                <div class="juntaplay-alert juntaplay-alert--error"><?php echo esc_html($message); ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="juntaplay-profile__layout">
        <nav class="juntaplay-profile__menu" data-profile-menu aria-label="<?php esc_attr_e('Seções do perfil', 'juntaplay'); ?>" aria-hidden="false">
            <div class="juntaplay-profile__menu-head">
                <span class="juntaplay-profile__menu-title"><?php esc_html_e('Menu do usuário', 'juntaplay'); ?></span>
                <button type="button" class="juntaplay-profile__menu-close" data-profile-menu-close>
                    <span class="screen-reader-text"><?php esc_html_e('Fechar menu', 'juntaplay'); ?></span>
                    <?php echo juntaplay_profile_icon('close'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                </button>
            </div>

            <div class="juntaplay-profile__category-nav">
                <?php foreach ($profile_categories as $category_id => $category) :
                    $is_active = $category_id === $active_category_id;
                    ?>
                    <button type="button"
                        class="juntaplay-profile__category-button<?php echo $is_active ? ' is-active' : ''; ?>"
                        data-profile-category-toggle="<?php echo esc_attr($category_id); ?>"
                        aria-pressed="<?php echo $is_active ? 'true' : 'false'; ?>"
                        aria-controls="juntaplay-profile-category-<?php echo esc_attr($category_id); ?>">
                        <span class="juntaplay-profile__category-icon"><?php echo juntaplay_profile_icon($category['icon'] ?? 'settings'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
                        <span class="juntaplay-profile__category-info">
                            <span class="juntaplay-profile__category-label"><?php echo esc_html($category['label'] ?? ''); ?></span>
                            <?php if (!empty($category['description'])) : ?>
                                <span class="juntaplay-profile__category-description"><?php echo esc_html((string) $category['description']); ?></span>
                            <?php endif; ?>
                        </span>
                    </button>
                <?php endforeach; ?>
            </div>

            <?php if ($profile_menu_links) : ?>
                <div class="juntaplay-profile__menu-links">
                    <span class="juntaplay-profile__menu-links-title"><?php esc_html_e('Atalhos rápidos', 'juntaplay'); ?></span>
                    <ul class="juntaplay-profile__menu-list" role="list">
                        <?php foreach ($profile_menu_links as $menu_item) :
                            $item_type = $menu_item['type'];
                            $item_label = (string) ($menu_item['label'] ?? '');
                            $item_icon  = (string) ($menu_item['icon'] ?? 'settings');
                            ?>
                            <li class="juntaplay-profile__menu-item">
                                <?php if ($item_type === 'category') : ?>
                                    <button type="button" class="juntaplay-profile__menu-link" data-profile-quick data-profile-quick-category="<?php echo esc_attr((string) $menu_item['category']); ?>" data-profile-quick-tab="<?php echo esc_attr((string) $menu_item['tab']); ?>">
                                        <span class="juntaplay-profile__menu-link-icon"><?php echo juntaplay_profile_icon($item_icon); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
                                        <span class="juntaplay-profile__menu-link-label"><?php echo esc_html($item_label); ?></span>
                                    </button>
                                <?php else :
                                    $link_url = (string) ($menu_item['url'] ?? '');
                                    $link_target = !empty($menu_item['target']) ? (string) $menu_item['target'] : '_self';
                                    ?>
                                    <a href="<?php echo esc_url($link_url); ?>" class="juntaplay-profile__menu-link" data-profile-menu-link target="<?php echo esc_attr($link_target); ?>"<?php echo $item_type === 'logout' ? ' data-profile-menu-logout' : ''; ?>>
                                        <span class="juntaplay-profile__menu-link-icon"><?php echo juntaplay_profile_icon($item_icon); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
                                        <span class="juntaplay-profile__menu-link-label"><?php echo esc_html($item_label); ?></span>
                                    </a>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </nav>

        <div class="juntaplay-profile__panels">
            <?php foreach ($profile_categories as $category_id => $category) :
                $category_active = $category_id === $active_category_id;
                $tabs            = $category['tabs'];
                ?>
                <section id="juntaplay-profile-category-<?php echo esc_attr($category_id); ?>" class="juntaplay-profile__category-panel<?php echo $category_active ? ' is-active' : ''; ?>" data-profile-category-panel="<?php echo esc_attr($category_id); ?>" aria-hidden="<?php echo $category_active ? 'false' : 'true'; ?>">
                    <?php if (count($tabs) > 1) : ?>
                        <div class="juntaplay-profile__tabs" role="tablist" aria-label="<?php echo esc_attr($category['label'] ?? ''); ?>">
                            <?php foreach ($tabs as $tab_id => $tab) :
                                $tab_active = $category_active && $tab_id === $active_tab_id;
                                ?>
                                <button type="button"
                                    class="juntaplay-profile__tab<?php echo $tab_active ? ' is-active' : ''; ?>"
                                    data-profile-tab-toggle="<?php echo esc_attr($tab_id); ?>"
                                    data-profile-tab-category="<?php echo esc_attr($category_id); ?>"
                                    role="tab"
                                    aria-selected="<?php echo $tab_active ? 'true' : 'false'; ?>"
                                    aria-controls="juntaplay-profile-tab-<?php echo esc_attr($tab_id); ?>">
                                    <span class="juntaplay-profile__tab-icon"><?php echo juntaplay_profile_icon($tab['icon'] ?? 'user'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
                                    <span class="juntaplay-profile__tab-label"><?php echo esc_html($tab['label'] ?? ''); ?></span>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php foreach ($tabs as $tab_id => $tab) :
                        $tab_active = $category_active && $tab_id === $active_tab_id;
                        ?>
                        <div id="juntaplay-profile-tab-<?php echo esc_attr($tab_id); ?>" class="juntaplay-profile__tab-panel<?php echo $tab_active ? ' is-active' : ''; ?>" data-profile-tab-panel="<?php echo esc_attr($tab_id); ?>" aria-hidden="<?php echo $tab_active ? 'false' : 'true'; ?>">
                            <?php if (count($tabs) === 1) : ?>
                                <header class="juntaplay-profile__tab-heading">
                                    <span class="juntaplay-profile__tab-icon"><?php echo juntaplay_profile_icon($tab['icon'] ?? 'user'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
                                    <div>
                                        <h2><?php echo esc_html($tab['label'] ?? ''); ?></h2>
                                        <?php if (!empty($category['description'])) : ?>
                                            <p><?php echo esc_html((string) $category['description']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                </header>
                            <?php endif; ?>

                            <?php foreach ($tab['groups'] as $group_meta) :
                                $group_key   = $group_meta['key'];
                                $visible_set = $group_meta['items'] ?? null;
                                if (!isset($profile_sections[$group_key])) {
                                    continue;
                                }

                                juntaplay_profile_render_group(
                                    $group_key,
                                    $profile_sections[$group_key],
                                    $active_section,
                                    $profile_errors,
                                    $visible_set
                                );
                            endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                </section>
            <?php endforeach; ?>
        </div>
    </div>
</div>
