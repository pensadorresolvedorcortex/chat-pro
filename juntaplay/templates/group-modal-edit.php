<?php
/**
 * Group edit modal form.
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$group       = isset($group) && is_array($group) ? $group : [];
$categories  = isset($categories) && is_array($categories) ? $categories : [];

$group_id        = isset($group['id']) ? (int) $group['id'] : 0;
$title           = isset($group['title']) ? (string) $group['title'] : '';
$description     = isset($group['description']) ? (string) $group['description'] : '';
$rules           = isset($group['rules']) ? (string) $group['rules'] : '';
$service_name    = isset($group['service_name']) ? (string) $group['service_name'] : '';
$service_url     = isset($group['service_url']) ? (string) $group['service_url'] : '';
$price_regular   = isset($group['price_regular']) ? (float) $group['price_regular'] : 0.0;
$price_promo     = isset($group['price_promotional']) ? (float) $group['price_promotional'] : 0.0;
$member_price    = isset($group['member_price']) ? (float) $group['member_price'] : 0.0;
$slots_total     = isset($group['slots_total']) ? (int) $group['slots_total'] : 0;
$slots_reserved  = isset($group['slots_reserved']) ? (int) $group['slots_reserved'] : 0;
$support_channel = isset($group['support_channel']) ? (string) $group['support_channel'] : '';
$delivery_time   = isset($group['delivery_time']) ? (string) $group['delivery_time'] : '';
$access_method   = isset($group['access_method']) ? (string) $group['access_method'] : '';
$category        = isset($group['category']) ? (string) $group['category'] : '';
$instant_access  = !empty($group['instant_access']);
$cover_id        = isset($group['cover_id']) ? (int) $group['cover_id'] : 0;
$cover_url       = isset($group['cover_url']) ? (string) $group['cover_url'] : '';
$cover_placeholder = defined('JP_GROUP_COVER_PLACEHOLDER') && JP_GROUP_COVER_PLACEHOLDER !== ''
    ? JP_GROUP_COVER_PLACEHOLDER
    : '';
if ($cover_placeholder === '' && defined('JP_URL') && JP_URL !== '') {
    $cover_placeholder = trailingslashit(JP_URL) . 'assets/img/group-cover-placeholder.svg';
}
if ($cover_placeholder === '') {
    $cover_placeholder = plugins_url('../assets/img/group-cover-placeholder.svg', __FILE__);
}
if ($cover_placeholder === '') {
    $cover_placeholder = 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCA2IDUiIHByZXNlcnZlQXNwZWN0UmF0aW89InhNaWRZTWlkIHNsaWNlIj48ZGVmcz48bGluZWFyR3JhZGllbnQgaWQ9ImpwQ292ZXIiIHgxPSIwIiB4Mj0iMSIgeTE9IjAiIHkyPSIxIj48c3RvcCBvZmZzZXQ9IjAlIiBzdG9wLWNvbG9yPSIjRERFM0VBIiAvPjxzdG9wIG9mZnNldD0iMTAwJSIgc3RvcC1jb2xvcj0iI0YxRjNGNiIgLz48L2xpbmVhckdyYWRpZW50PjwvZGVmcz48cmVjdCB3aWR0aD0iNiIgaGVpZ2h0PSI1IiBmaWxsPSJ1cmwoI2pwQ292ZXIpIiAvPjxnIGZpbGw9IiM5MEE0QjgiIG9wYWNpdHk9IjAuOCI+PHBhdGggZD0iTTEgMy4yIDIuNCAxLjZsLjguOS44LS43TDUgMy40di44SDF6IiAvPjxjaXJjbGUgY3g9IjEuNCIgY3k9IjEuMyIgcj0iMC41IiAvPjwvZz48L3N2Zz4=';
}
$cover_preview   = $cover_url !== '' ? $cover_url : $cover_placeholder;
$current_user_id = get_current_user_id();

if ($category === '' || !isset($categories[$category])) {
    $category = 'other';
}

?>
<form id="juntaplay-group-edit-form" class="juntaplay-group-edit" method="post" enctype="multipart/form-data">
    <input type="hidden" name="group_id" value="<?php echo esc_attr((string) $group_id); ?>" />

    <div class="juntaplay-form__field">
        <label for="jp-group-edit-title"><?php esc_html_e('Nome do grupo', 'juntaplay'); ?></label>
        <input type="text" id="jp-group-edit-title" name="title" class="juntaplay-form__input" value="<?php echo esc_attr($title); ?>" maxlength="200" required />
    </div>

    <div class="juntaplay-form__grid">
        <div class="juntaplay-form__field">
            <label for="jp-group-edit-service"><?php esc_html_e('Serviço compartilhado', 'juntaplay'); ?></label>
            <input type="text" id="jp-group-edit-service" name="service_name" class="juntaplay-form__input" value="<?php echo esc_attr($service_name); ?>" maxlength="200" required />
        </div>
        <div class="juntaplay-form__field">
            <label for="jp-group-edit-service-url"><?php esc_html_e('Link do serviço (opcional)', 'juntaplay'); ?></label>
            <input type="url" id="jp-group-edit-service-url" name="service_url" class="juntaplay-form__input" value="<?php echo esc_attr($service_url); ?>" placeholder="https://" />
        </div>
    </div>

    <div class="juntaplay-form__field">
        <label for="jp-group-edit-description"><?php esc_html_e('Descrição para os participantes', 'juntaplay'); ?></label>
        <textarea id="jp-group-edit-description" name="description" class="juntaplay-form__input" rows="4" required><?php echo esc_textarea($description); ?></textarea>
    </div>

    <div class="juntaplay-form__field">
        <label for="jp-group-edit-rules"><?php esc_html_e('Regras e orientações', 'juntaplay'); ?></label>
        <textarea id="jp-group-edit-rules" name="rules" class="juntaplay-form__input" rows="4" required><?php echo esc_textarea($rules); ?></textarea>
    </div>

    <div class="juntaplay-form__grid">
        <div class="juntaplay-form__field">
            <label for="jp-group-edit-price"><?php esc_html_e('Valor do serviço (R$)', 'juntaplay'); ?></label>
            <input type="text" id="jp-group-edit-price" name="price_regular" class="juntaplay-form__input" inputmode="decimal" value="<?php echo esc_attr(number_format_i18n($price_regular, 2)); ?>" required />
        </div>
        <div class="juntaplay-form__field">
            <label for="jp-group-edit-promo"><?php esc_html_e('Valor promocional (R$)', 'juntaplay'); ?></label>
            <input type="text" id="jp-group-edit-promo" name="price_promotional" class="juntaplay-form__input" inputmode="decimal" value="<?php echo $price_promo > 0 ? esc_attr(number_format_i18n($price_promo, 2)) : ''; ?>" placeholder="0,00" />
        </div>
        <div class="juntaplay-form__field">
            <label for="jp-group-edit-member-price"><?php esc_html_e('Valor por participante (R$)', 'juntaplay'); ?></label>
            <input type="text" id="jp-group-edit-member-price" name="member_price" class="juntaplay-form__input" inputmode="decimal" value="<?php echo esc_attr(number_format_i18n($member_price, 2)); ?>" required />
        </div>
    </div>

    <div class="juntaplay-form__grid">
        <div class="juntaplay-form__field">
            <label for="jp-group-edit-total"><?php esc_html_e('Vagas totais', 'juntaplay'); ?></label>
            <input type="number" id="jp-group-edit-total" name="slots_total" class="juntaplay-form__input" value="<?php echo esc_attr((string) max(0, $slots_total)); ?>" min="1" required />
        </div>
        <div class="juntaplay-form__field">
            <label for="jp-group-edit-reserved"><?php esc_html_e('Reservadas para você', 'juntaplay'); ?></label>
            <input type="number" id="jp-group-edit-reserved" name="slots_reserved" class="juntaplay-form__input" value="<?php echo esc_attr((string) max(0, $slots_reserved)); ?>" min="0" required />
        </div>
    </div>

    <div class="juntaplay-form__grid">
        <div class="juntaplay-form__field">
            <label for="jp-group-edit-support"><?php esc_html_e('Canal de suporte', 'juntaplay'); ?></label>
            <input type="text" id="jp-group-edit-support" name="support_channel" class="juntaplay-form__input" value="<?php echo esc_attr($support_channel); ?>" maxlength="160" required />
        </div>
        <div class="juntaplay-form__field">
            <label for="jp-group-edit-delivery"><?php esc_html_e('Prazo para liberar acesso', 'juntaplay'); ?></label>
            <input type="text" id="jp-group-edit-delivery" name="delivery_time" class="juntaplay-form__input" value="<?php echo esc_attr($delivery_time); ?>" maxlength="160" required />
        </div>
        <div class="juntaplay-form__field">
            <label for="jp-group-edit-access"><?php esc_html_e('Forma de entrega', 'juntaplay'); ?></label>
            <input type="text" id="jp-group-edit-access" name="access_method" class="juntaplay-form__input" value="<?php echo esc_attr($access_method); ?>" maxlength="160" required />
        </div>
    </div>

    <div class="juntaplay-form__grid">
        <div class="juntaplay-form__field">
            <label for="jp-group-edit-category"><?php esc_html_e('Categoria', 'juntaplay'); ?></label>
            <select id="jp-group-edit-category" name="category" class="juntaplay-form__input">
                <?php foreach ($categories as $category_key => $category_label) : ?>
                    <option value="<?php echo esc_attr((string) $category_key); ?>" <?php selected($category, (string) $category_key); ?>><?php echo esc_html((string) $category_label); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="juntaplay-form__field juntaplay-form__field--checkbox">
            <label>
                <input type="checkbox" name="instant_access" value="1" <?php checked($instant_access); ?> />
                <span><?php esc_html_e('Acesso instantâneo ativado', 'juntaplay'); ?></span>
            </label>
        </div>
    </div>

    <div class="juntaplay-form__field juntaplay-form__field--cover"
        data-group-cover
        data-upload-context="profile-group-cover"
        data-placeholder="<?php echo esc_url($cover_placeholder); ?>"
        data-media-author="<?php echo esc_attr((string) $current_user_id); ?>"
        data-group-cover-ready="0">
        <label for="jp-group-edit-cover-id"><?php esc_html_e('Capa do grupo', 'juntaplay'); ?></label>
        <div class="juntaplay-cover-picker" data-group-cover-wrapper>
            <div class="juntaplay-cover-picker__media" data-group-cover-preview style="background-image: url('<?php echo esc_url($cover_preview); ?>');">
                <img src="<?php echo esc_url($cover_preview); ?>" alt="<?php esc_attr_e('Pré-visualização da capa do grupo', 'juntaplay'); ?>" loading="lazy" />
            </div>
            <input type="hidden" id="jp-group-edit-cover-id" name="cover_id" value="<?php echo esc_attr((string) $cover_id); ?>" data-group-cover-input />
            <div class="juntaplay-cover-picker__actions">
                <button type="button" class="juntaplay-button juntaplay-button--ghost" data-group-cover-select><?php esc_html_e('Escolher imagem', 'juntaplay'); ?></button>
                <button type="button" class="juntaplay-button juntaplay-button--subtle" data-group-cover-remove <?php disabled($cover_id === 0); ?>><?php esc_html_e('Remover', 'juntaplay'); ?></button>
            </div>
            <p class="juntaplay-form__help"><?php esc_html_e('Envie uma imagem JPG ou PNG com proporção semelhante a 495 x 370 px.', 'juntaplay'); ?></p>
        </div>
    </div>

    <div class="juntaplay-form__actions">
        <button type="submit" class="juntaplay-button juntaplay-button--primary"><?php esc_html_e('Salvar alterações', 'juntaplay'); ?></button>
        <button type="button" class="juntaplay-button juntaplay-button--ghost" data-group-modal-close><?php esc_html_e('Cancelar', 'juntaplay'); ?></button>
    </div>
</form>
