<?php
/**
 * JuntaPlay profile editing template.
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$user             = wp_get_current_user();
$profile_sections = $profile_sections ?? [];
$profile_errors   = $profile_errors ?? [];
$profile_notices  = $profile_notices ?? [];
$active_section   = $profile_active_section ?? null;
$display_name     = '';

if ($user && $user->exists()) {
    $display_name = wp_strip_all_tags((string) $user->display_name);
}

if ($display_name === '') {
    $display_name = wp_strip_all_tags(__('Jogue com a gente', 'juntaplay'));
}

$notifications_unread = \JuntaPlay\Data\Notifications::count_unread(get_current_user_id());
?>
<div class="juntaplay-profile" data-profile>
    <div class="juntaplay-profile__toolbar">
        <button type="button" class="juntaplay-notification-bell" data-jp-notifications aria-haspopup="true" aria-expanded="false"<?php if ($notifications_unread > 0) : ?> data-count="<?php echo esc_attr($notifications_unread); ?>"<?php endif; ?>>
            <span class="screen-reader-text"><?php esc_html_e('Abrir notificações', 'juntaplay'); ?></span>
            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                <path d="M12 22a2 2 0 0 0 1.995-1.85L14 20h-4a2 2 0 0 0 1.85 1.995L12 22Zm7-6v-5a7 7 0 0 0-5-6.708V4a2 2 0 1 0-4 0v.292A7.002 7.002 0 0 0 6 11v5l-1.447 2.894A1 1 0 0 0 5.447 20h13.106a1 1 0 0 0 .894-1.447Z" fill="none" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
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
        <div class="juntaplay-profile__eyebrow"><?php echo esc_html__('Meu perfil', 'juntaplay'); ?></div>
        <h1><?php echo esc_html(sprintf(__('Olá, %s', 'juntaplay'), $display_name)); ?></h1>
        <p><?php echo esc_html__('Atualize seus dados de contato para aproveitar as oportunidades do JuntaPlay.', 'juntaplay'); ?></p>
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

    <div class="juntaplay-card juntaplay-profile__card">
        <div class="juntaplay-card__body">
            <?php foreach ($profile_sections as $group_key => $group) :
                if (!is_array($group)) {
                    continue;
                }

                $group_items = isset($group['items']) && is_array($group['items']) ? $group['items'] : [];

                if (!$group_items) {
                    continue;
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

                                  continue;
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

                            $show_cancel = true;
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
                                        <?php elseif ($type !== 'action') : ?>
                                            <div class="juntaplay-form__group">
                                                <label for="juntaplay-field-<?php echo esc_attr($section_key); ?>"><?php echo esc_html($label); ?></label>
                                                  <?php if ($type === 'select') : ?>
                                                      <select
                                                          id="juntaplay-field-<?php echo esc_attr($section_key); ?>"
                                                          name="<?php echo esc_attr('jp_profile_' . $section_key); ?>"
                                                          class="juntaplay-form__input"
                                                      >
                                                          <?php foreach ($options as $option_value => $option_label) : ?>
                                                              <option value="<?php echo esc_attr((string) $option_value); ?>" <?php selected((string) $option_value, $value); ?>><?php echo esc_html((string) $option_label); ?></option>
                                                          <?php endforeach; ?>
                                                      </select>
                                                  <?php elseif ($type === 'textarea') : ?>
                                                      <textarea
                                                          id="juntaplay-field-<?php echo esc_attr($section_key); ?>"
                                                          name="<?php echo esc_attr('jp_profile_' . $section_key); ?>"
                                                          class="juntaplay-form__input"
                                                          rows="4"
                                                          placeholder="<?php echo esc_attr($placeholder); ?>"
                                                      ><?php echo esc_textarea($value); ?></textarea>
                                                  <?php else : ?>
                                                      <input
                                                          type="<?php echo esc_attr($type); ?>"
                                                          id="juntaplay-field-<?php echo esc_attr($section_key); ?>"
                                                          name="<?php echo esc_attr('jp_profile_' . $section_key); ?>"
                                                        class="juntaplay-form__input"
                                                        value="<?php echo esc_attr($value); ?>"
                                                        placeholder="<?php echo esc_attr($placeholder); ?>"
                                                        autocomplete="<?php echo esc_attr($autocomplete); ?>"
                                                    />
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($section_errors) : ?>
                                            <ul class="juntaplay-form__errors" role="alert">
                                                <?php foreach ($section_errors as $error_message) : ?>
                                                    <li><?php echo esc_html($error_message); ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php endif; ?>
                                        <div class="juntaplay-form__actions">
                                            <button type="submit" class="<?php echo esc_attr($button_class); ?>"><?php echo esc_html($submit_label); ?></button>
                                            <?php if ($show_cancel) : ?>
                                                <button type="button" class="juntaplay-button juntaplay-button--ghost juntaplay-profile__cancel"><?php echo esc_html__('Cancelar', 'juntaplay'); ?></button>
                                            <?php endif; ?>
                                          </div>
                                      </form>
                                      </div>
                                  <?php endif; ?>
                              </li>
                        <?php endforeach; ?>
                    </ul>
                </section>
            <?php endforeach; ?>
        </div>
    </div>
</div>
