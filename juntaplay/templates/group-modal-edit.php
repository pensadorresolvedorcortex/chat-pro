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

if ($category === '' || !isset($categories[$category])) {
    $category = 'other';
}

?>
<form id="juntaplay-group-edit-form" class="juntaplay-group-edit" method="post" enctype="multipart/form-data">
    <input type="hidden" name="group_id" value="<?php echo esc_attr((string) $group_id); ?>" />
    <input type="hidden" name="cover_id" value="<?php echo esc_attr((string) $cover_id); ?>" />

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

    <div class="juntaplay-form__field">
        <label for="jp-group-edit-cover"><?php esc_html_e('Capa do grupo', 'juntaplay'); ?></label>
        <?php if ($cover_url !== '') : ?>
            <div class="juntaplay-group-edit__cover-preview">
                <img src="<?php echo esc_url($cover_url); ?>" alt="<?php esc_attr_e('Pré-visualização da capa atual', 'juntaplay'); ?>" />
            </div>
        <?php endif; ?>
        <input type="file" id="jp-group-edit-cover" name="group_cover" class="juntaplay-form__input" accept="image/*" />
        <p class="juntaplay-form__help"><?php esc_html_e('Envie uma imagem JPG ou PNG (495 x 370 px).', 'juntaplay'); ?></p>
    </div>

    <div class="juntaplay-form__actions">
        <button type="submit" class="juntaplay-button juntaplay-button--primary"><?php esc_html_e('Salvar alterações', 'juntaplay'); ?></button>
        <button type="button" class="juntaplay-button juntaplay-button--ghost" data-group-modal-close><?php esc_html_e('Cancelar', 'juntaplay'); ?></button>
    </div>
</form>
