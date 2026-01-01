<?php
/** @var array<string, mixed> $context */

declare(strict_types=1);

defined('ABSPATH') || exit;

$notice     = $context['notice'] ?? null;
$pools      = $context['pools'] ?? [];
$categories = $context['categories'] ?? [];
$editing    = $context['editing'] ?? null;
$plans      = $context['plans'] ?? [];
$pool_type  = $context['pool_type'] ?? 'simple';
if (!in_array($pool_type, ['simple', 'variable'], true)) {
    $pool_type = 'simple';
}
?>
<div class="wrap">
    <h1><?php echo esc_html__('Serviços pré-aprovados', 'juntaplay'); ?></h1>

    <?php if ($notice) : ?>
        <div class="notice notice-<?php echo esc_attr($notice['type'] ?? 'info'); ?> is-dismissible">
            <p><?php echo esc_html($notice['message'] ?? ''); ?></p>
        </div>
    <?php endif; ?>

    <div style="margin-top: 20px;">
        <h2><?php echo $editing ? esc_html__('Editar serviço', 'juntaplay') : esc_html__('Criar serviço', 'juntaplay'); ?></h2>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('juntaplay_pool_save'); ?>
            <input type="hidden" name="action" value="juntaplay_pool_save" />
            <input type="hidden" name="pool_id" value="<?php echo esc_attr($editing->id ?? 0); ?>" />

            <?php
            $icon_meta = is_object($editing) && isset($editing->icon_meta) && is_array($editing->icon_meta) ? $editing->icon_meta : [];
            ?>

            <div class="card juntaplay-admin-section">
                <h3><?php esc_html_e('Identidade do serviço', 'juntaplay'); ?></h3>
                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row"><label for="title"><?php esc_html_e('Nome do serviço', 'juntaplay'); ?></label></th>
                            <td>
                                <input type="text" class="regular-text" id="title" name="title" value="<?php echo esc_attr($editing->title ?? ''); ?>" required />
                                <p class="description"><?php esc_html_e('Esse nome aparece no card aprovado e nas comunicações.', 'juntaplay'); ?></p>
                            </td>
                        </tr>
                        <tr style="display:none;">
                            <th scope="row"><label for="slug"><?php esc_html_e('Slug', 'juntaplay'); ?></label></th>
                            <td><input type="text" class="regular-text" id="slug" name="slug" value="<?php echo esc_attr($editing->slug ?? ''); ?>" /></td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="icon_id"><?php esc_html_e('Ícone do serviço', 'juntaplay'); ?></label>
                            </th>
                            <td>
                                <p class="description"><?php esc_html_e('O ícone é exibido no card do grupo e no catálogo de serviços.', 'juntaplay'); ?></p>
                                <div
                                    class="juntaplay-media-picker"
                                    data-media-field="icon_id"
                                    data-media-size="1:1"
                                    data-media-default-hint="<?php echo esc_attr__('Utilize arte quadrada (1:1) para manter o ícone nítido nos cards aprovados.', 'juntaplay'); ?>"
                                    data-media-current-width="<?php echo isset($icon_meta['width']) ? esc_attr((string) $icon_meta['width']) : ''; ?>"
                                    data-media-current-height="<?php echo isset($icon_meta['height']) ? esc_attr((string) $icon_meta['height']) : ''; ?>"
                                >
                                    <div class="juntaplay-media-picker__preview" data-media-preview>
                                        <?php if (!empty($editing->icon)) : ?>
                                            <img src="<?php echo esc_url($editing->icon); ?>" alt="" />
                                        <?php else : ?>
                                            <span class="juntaplay-media-picker__placeholder"><?php esc_html_e('Nenhum ícone selecionado', 'juntaplay'); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <input type="hidden" id="icon_id" name="icon_id" value="<?php echo esc_attr($editing->icon_id ?? ''); ?>" />
                                    <div class="juntaplay-admin-asset-card__actions">
                                        <button type="button" class="button" data-media-select><?php esc_html_e('Escolher ícone', 'juntaplay'); ?></button>
                                        <button type="button" class="button button-secondary" data-media-remove><?php esc_html_e('Remover', 'juntaplay'); ?></button>
                                    </div>
                                    <p class="juntaplay-admin-asset-card__hint" data-media-hint>
                                        <?php echo esc_html__('Utilize arte quadrada (1:1) para manter o ícone nítido nos cards aprovados.', 'juntaplay'); ?>
                                    </p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="card juntaplay-admin-section">
                <h3><?php esc_html_e('Configuração do serviço', 'juntaplay'); ?></h3>
                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row"><?php esc_html_e('Tipo do serviço', 'juntaplay'); ?></th>
                            <td>
                                <fieldset class="juntaplay-admin-type">
                                    <label>
                                        <input type="radio" name="pool_type" value="simple" <?php checked($pool_type, 'simple'); ?> />
                                        <?php esc_html_e('Serviço simples', 'juntaplay'); ?>
                                    </label>
                                    <label>
                                        <input type="radio" name="pool_type" value="variable" <?php checked($pool_type, 'variable'); ?> />
                                        <?php esc_html_e('Serviço com variações', 'juntaplay'); ?>
                                    </label>
                                </fieldset>
                                <p class="description"><?php esc_html_e('Defina se o serviço possui planos diferentes.', 'juntaplay'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="category"><?php esc_html_e('Categoria', 'juntaplay'); ?></label></th>
                            <td>
                                <select id="category" name="category">
                                    <?php foreach ($categories as $key => $label) : ?>
                                        <option value="<?php echo esc_attr($key); ?>" <?php selected($editing->category ?? '', $key); ?>><?php echo esc_html($label); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="excerpt"><?php esc_html_e('Descrição curta', 'juntaplay'); ?></label></th>
                            <td><textarea id="excerpt" name="excerpt" rows="3" class="large-text"><?php echo esc_textarea($editing->excerpt ?? ''); ?></textarea></td>
                        </tr>
                        <tr data-simple-field>
                            <th scope="row"><label for="quota_start"><?php esc_html_e('Quantidade mínima', 'juntaplay'); ?></label></th>
                            <td><input type="number" min="1" id="quota_start" name="quota_start" value="<?php echo esc_attr($editing->quota_start ?? '1'); ?>" required /></td>
                        </tr>
                        <tr data-simple-field>
                            <th scope="row"><label for="quota_end"><?php esc_html_e('Quantidade máxima', 'juntaplay'); ?></label></th>
                            <td><input type="number" min="1" id="quota_end" name="quota_end" value="<?php echo esc_attr($editing->quota_end ?? '1'); ?>" required /></td>
                        </tr>
                        <?php
                        $status_value = isset($editing->status) && in_array($editing->status, ['draft', 'inactive'], true)
                            ? 'inactive'
                            : 'active';
                        ?>
                        <tr>
                            <th scope="row"><label for="status"><?php esc_html_e('Status', 'juntaplay'); ?></label></th>
                            <td>
                                <select id="status" name="status">
                                    <option value="active" <?php selected($status_value, 'active'); ?>><?php esc_html_e('Ativo', 'juntaplay'); ?></option>
                                    <option value="inactive" <?php selected($status_value, 'inactive'); ?>><?php esc_html_e('Inativo', 'juntaplay'); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="service_url"><?php esc_html_e('Site oficial', 'juntaplay'); ?></label></th>
                            <td>
                                <input type="url" class="regular-text" id="service_url" name="service_url" value="<?php echo esc_attr($editing->service_url ?? ''); ?>" placeholder="https://" />
                                <p class="description"><?php esc_html_e('Link exibido no card aprovado e nos e-mails.', 'juntaplay'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">&nbsp;</th>
                            <td>
                                <label><input type="checkbox" name="is_featured" value="1" <?php checked(!empty($editing->is_featured)); ?> /> <?php esc_html_e('Destacar serviço', 'juntaplay'); ?></label>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <?php
            $plans_display = is_array($plans) ? $plans : [];
            ?>

            <div class="card juntaplay-admin-section juntaplay-admin-section--plans" data-plan-section>
                <div class="juntaplay-admin-section__header">
                    <div>
                        <h3><?php esc_html_e('Variações do serviço', 'juntaplay'); ?></h3>
                        <p class="description"><?php esc_html_e('Cadastre variações de preço e quantidade de membros para este serviço.', 'juntaplay'); ?></p>
                    </div>
                    <button type="button" class="button button-primary" data-plan-add><?php esc_html_e('Adicionar variação', 'juntaplay'); ?></button>
                </div>
                <div class="juntaplay-admin-plans">
                    <table class="widefat fixed striped juntaplay-admin-plans__table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Ativo', 'juntaplay'); ?></th>
                                <th><?php esc_html_e('Nome do plano', 'juntaplay'); ?></th>
                                <th><?php esc_html_e('Preço', 'juntaplay'); ?></th>
                                <th><?php esc_html_e('Máx. usuários', 'juntaplay'); ?></th>
                                <th><?php esc_html_e('Ordem', 'juntaplay'); ?></th>
                                <th><?php esc_html_e('Ações', 'juntaplay'); ?></th>
                            </tr>
                        </thead>
                        <tbody data-plan-list>
                            <?php if (empty($plans_display)) : ?>
                                <tr data-plan-empty>
                                    <td colspan="6"><?php esc_html_e('Nenhuma variação cadastrada.', 'juntaplay'); ?></td>
                                </tr>
                            <?php else : ?>
                                <?php foreach ($plans_display as $index => $plan) : ?>
                                    <?php
                                    $plan_status = $plan['status'] ?? 'active';
                                    $plan_price = isset($plan['price']) ? (float) $plan['price'] : 0.0;
                                    $plan_price_display = function_exists('wc_price')
                                        ? wc_price($plan_price)
                                        : number_format($plan_price, 2, ',', '.');
                                    ?>
                                    <tr data-plan-item data-plan-index="<?php echo esc_attr((string) $index); ?>">
                                        <td>
                                            <span class="juntaplay-admin-status juntaplay-admin-status--<?php echo esc_attr($plan_status === 'inactive' ? 'inactive' : 'active'); ?>">
                                                <?php echo esc_html($plan_status === 'inactive' ? __('Inativo', 'juntaplay') : __('Ativo', 'juntaplay')); ?>
                                            </span>
                                        </td>
                                        <td data-plan-name><?php echo esc_html((string) ($plan['name'] ?? '')); ?></td>
                                        <td data-plan-price><?php echo wp_kses_post($plan_price_display); ?></td>
                                        <td data-plan-max><?php echo esc_html((string) ($plan['max_members'] ?? 0)); ?></td>
                                        <td data-plan-order><?php echo esc_html((string) ($plan['order'] ?? 0)); ?></td>
                                        <td class="juntaplay-admin-plans__actions">
                                            <button type="button" class="button button-secondary" data-plan-edit><?php esc_html_e('Editar', 'juntaplay'); ?></button>
                                            <button type="button" class="button-link-delete" data-plan-remove><?php esc_html_e('Excluir', 'juntaplay'); ?></button>
                                        </td>
                                        <td class="juntaplay-admin-plans__hidden">
                                            <input type="hidden" name="jp_plans[<?php echo esc_attr((string) $index); ?>][name]" value="<?php echo esc_attr((string) ($plan['name'] ?? '')); ?>" />
                                            <input type="hidden" name="jp_plans[<?php echo esc_attr((string) $index); ?>][description]" value="<?php echo esc_attr((string) ($plan['description'] ?? '')); ?>" />
                                            <input type="hidden" name="jp_plans[<?php echo esc_attr((string) $index); ?>][price]" value="<?php echo esc_attr((string) ($plan['price'] ?? '')); ?>" />
                                            <input type="hidden" name="jp_plans[<?php echo esc_attr((string) $index); ?>][max_members]" value="<?php echo esc_attr((string) ($plan['max_members'] ?? '')); ?>" />
                                            <input type="hidden" name="jp_plans[<?php echo esc_attr((string) $index); ?>][status]" value="<?php echo esc_attr((string) $plan_status); ?>" />
                                            <input type="hidden" name="jp_plans[<?php echo esc_attr((string) $index); ?>][order]" value="<?php echo esc_attr((string) ($plan['order'] ?? '')); ?>" />
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <p class="description juntaplay-admin-plans__hint"><?php esc_html_e('Os planos ativos aparecerão no wizard de criação de grupos.', 'juntaplay'); ?></p>
            </div>

            <div class="card juntaplay-admin-section" data-simple-card>
                <h3><?php esc_html_e('Configuração financeira', 'juntaplay'); ?></h3>
                <table class="form-table" role="presentation">
                    <tbody>
                        <tr data-simple-field>
                            <th scope="row"><label for="price"><?php esc_html_e('Valor base do serviço', 'juntaplay'); ?></label></th>
                            <td>
                                <input type="number" step="0.01" min="0" id="price" name="price" value="<?php echo esc_attr($editing->price ?? '0'); ?>" required />
                                <p class="description"><?php esc_html_e('Este valor é usado como referência para a divisão entre participantes.', 'juntaplay'); ?></p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="card juntaplay-admin-section juntaplay-admin-actions">
                <h3><?php esc_html_e('Ações', 'juntaplay'); ?></h3>
                <p class="submit">
                    <button type="submit" class="button button-primary"><?php echo $editing ? esc_html__('Salvar alterações', 'juntaplay') : esc_html__('Criar serviço', 'juntaplay'); ?></button>
                    <?php if ($editing) : ?>
                        <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=juntaplay-pools')); ?>"><?php esc_html_e('Cancelar edição', 'juntaplay'); ?></a>
                    <?php endif; ?>
                </p>
            </div>

            <template id="jp-plan-template">
                <tr data-plan-item data-plan-index="__INDEX__">
                    <td>
                        <span class="juntaplay-admin-status juntaplay-admin-status--__STATUS__">__STATUS_LABEL__</span>
                    </td>
                    <td data-plan-name>__NAME__</td>
                    <td data-plan-price>__PRICE__</td>
                    <td data-plan-max>__MAX__</td>
                    <td data-plan-order>__ORDER__</td>
                    <td class="juntaplay-admin-plans__actions">
                        <button type="button" class="button button-secondary" data-plan-edit><?php esc_html_e('Editar', 'juntaplay'); ?></button>
                        <button type="button" class="button-link-delete" data-plan-remove><?php esc_html_e('Excluir', 'juntaplay'); ?></button>
                    </td>
                    <td class="juntaplay-admin-plans__hidden">
                        <input type="hidden" name="jp_plans[__INDEX__][name]" value="__NAME__" />
                        <input type="hidden" name="jp_plans[__INDEX__][description]" value="__DESCRIPTION__" />
                        <input type="hidden" name="jp_plans[__INDEX__][price]" value="__PRICE_RAW__" />
                        <input type="hidden" name="jp_plans[__INDEX__][max_members]" value="__MAX_RAW__" />
                        <input type="hidden" name="jp_plans[__INDEX__][status]" value="__STATUS_RAW__" />
                        <input type="hidden" name="jp_plans[__INDEX__][order]" value="__ORDER_RAW__" />
                    </td>
                </tr>
            </template>

            <div class="juntaplay-admin-modal is-hidden" data-plan-modal>
                <div class="juntaplay-admin-modal__backdrop" data-plan-close></div>
                <div class="juntaplay-admin-modal__content" role="dialog" aria-modal="true" aria-labelledby="jp-plan-modal-title">
                    <header class="juntaplay-admin-modal__header">
                        <h3 id="jp-plan-modal-title"><?php esc_html_e('Adicionar variação', 'juntaplay'); ?></h3>
                        <button type="button" class="button-link" data-plan-close aria-label="<?php esc_attr_e('Fechar', 'juntaplay'); ?>">✕</button>
                    </header>
                    <div class="juntaplay-admin-modal__body">
                        <div class="juntaplay-admin-field">
                            <label for="jp-plan-name"><?php esc_html_e('Nome do plano', 'juntaplay'); ?></label>
                            <input type="text" id="jp-plan-name" class="regular-text" required />
                        </div>
                        <div class="juntaplay-admin-field">
                            <label for="jp-plan-description"><?php esc_html_e('Descrição curta', 'juntaplay'); ?></label>
                            <textarea id="jp-plan-description" rows="2" class="large-text"></textarea>
                        </div>
                        <div class="juntaplay-admin-field">
                            <label for="jp-plan-price"><?php esc_html_e('Preço base', 'juntaplay'); ?></label>
                            <input type="number" step="0.01" min="0" id="jp-plan-price" required />
                        </div>
                        <div class="juntaplay-admin-field">
                            <label for="jp-plan-max"><?php esc_html_e('Máx. de usuários', 'juntaplay'); ?></label>
                            <input type="number" min="1" id="jp-plan-max" required />
                        </div>
                        <div class="juntaplay-admin-field">
                            <label for="jp-plan-status"><?php esc_html_e('Status', 'juntaplay'); ?></label>
                            <select id="jp-plan-status">
                                <option value="active"><?php esc_html_e('Ativo', 'juntaplay'); ?></option>
                                <option value="inactive"><?php esc_html_e('Inativo', 'juntaplay'); ?></option>
                            </select>
                        </div>
                        <div class="juntaplay-admin-field">
                            <label for="jp-plan-order"><?php esc_html_e('Ordem', 'juntaplay'); ?></label>
                            <input type="number" min="0" id="jp-plan-order" />
                        </div>
                        <p class="juntaplay-admin-modal__error" data-plan-error></p>
                    </div>
                    <footer class="juntaplay-admin-modal__footer">
                        <button type="button" class="button" data-plan-close><?php esc_html_e('Cancelar', 'juntaplay'); ?></button>
                        <button type="button" class="button button-primary" data-plan-save><?php esc_html_e('Salvar variação', 'juntaplay'); ?></button>
                    </footer>
                </div>
            </div>

            <script>
                (function () {
                    var list = document.querySelector('[data-plan-list]');
                    var modal = document.querySelector('[data-plan-modal]');
                    var section = document.querySelector('[data-plan-section]');
                    var typeRadios = document.querySelectorAll('input[name="pool_type"]');

                    if (!list || !modal || !section || !typeRadios.length) {
                        return;
                    }

                    var modalTitle = modal.querySelector('#jp-plan-modal-title');
                    var nameField = modal.querySelector('#jp-plan-name');
                    var descField = modal.querySelector('#jp-plan-description');
                    var priceField = modal.querySelector('#jp-plan-price');
                    var maxField = modal.querySelector('#jp-plan-max');
                    var statusField = modal.querySelector('#jp-plan-status');
                    var orderField = modal.querySelector('#jp-plan-order');
                    var errorField = modal.querySelector('[data-plan-error]');
                    var saveButton = modal.querySelector('[data-plan-save]');
                    var closeButtons = modal.querySelectorAll('[data-plan-close]');
                    var currentRow = null;

                    function getNextIndex() {
                        var indexes = Array.from(list.querySelectorAll('[data-plan-item]'))
                            .map(function (row) { return parseInt(row.getAttribute('data-plan-index'), 10); })
                            .filter(function (value) { return !Number.isNaN(value); });
                        if (!indexes.length) {
                            return 0;
                        }
                        return Math.max.apply(null, indexes) + 1;
                    }

                    function toggleSection() {
                        var isVariable = document.querySelector('input[name="pool_type"]:checked');
                        var show = isVariable && isVariable.value === 'variable';
                        section.classList.toggle('is-hidden', !show);

                        var simpleRows = document.querySelectorAll('[data-simple-field]');
                        simpleRows.forEach(function (row) {
                            row.classList.toggle('is-hidden', show);
                            row.querySelectorAll('input, select, textarea').forEach(function (field) {
                                if (show) {
                                    field.removeAttribute('required');
                                } else {
                                    field.setAttribute('required', 'required');
                                }
                            });
                        });

                        document.querySelectorAll('[data-simple-card]').forEach(function (card) {
                            card.classList.toggle('is-hidden', show);
                        });

                        list.querySelectorAll('input, select, textarea').forEach(function (field) {
                            field.disabled = !show;
                        });
                    }

                    function openModal(mode, row) {
                        currentRow = row || null;
                        errorField.textContent = '';
                        if (mode === 'edit' && currentRow) {
                            modalTitle.textContent = '<?php echo esc_js(__('Editar variação', 'juntaplay')); ?>';
                            nameField.value = currentRow.querySelector('input[name*="[name]"]').value || '';
                            descField.value = currentRow.querySelector('input[name*="[description]"]').value || '';
                            priceField.value = currentRow.querySelector('input[name*="[price]"]').value || '';
                            maxField.value = currentRow.querySelector('input[name*="[max_members]"]').value || '';
                            statusField.value = currentRow.querySelector('input[name*="[status]"]').value || 'active';
                            orderField.value = currentRow.querySelector('input[name*="[order]"]').value || '';
                        } else {
                            modalTitle.textContent = '<?php echo esc_js(__('Adicionar variação', 'juntaplay')); ?>';
                            nameField.value = '';
                            descField.value = '';
                            priceField.value = '';
                            maxField.value = '';
                            statusField.value = 'active';
                            orderField.value = '';
                        }

                        modal.classList.remove('is-hidden');
                        nameField.focus();
                    }

                    function closeModal() {
                        modal.classList.add('is-hidden');
                        currentRow = null;
                    }

                    function renderRow(data, index) {
                        var template = document.getElementById('jp-plan-template');
                        if (!template) {
                            return null;
                        }

                        var priceDisplay = data.price !== '' ? parseFloat(data.price).toLocaleString('pt-BR', { minimumFractionDigits: 2 }) : '';
                        var statusLabel = data.status === 'inactive' ? '<?php echo esc_js(__('Inativo', 'juntaplay')); ?>' : '<?php echo esc_js(__('Ativo', 'juntaplay')); ?>';
                        var statusClass = data.status === 'inactive' ? 'inactive' : 'active';

                        var html = template.innerHTML
                            .replace(/__INDEX__/g, index)
                            .replace(/__NAME__/g, data.name)
                            .replace(/__DESCRIPTION__/g, data.description)
                            .replace(/__PRICE__/g, priceDisplay !== '' ? 'R$ ' + priceDisplay : '')
                            .replace(/__PRICE_RAW__/g, data.price)
                            .replace(/__MAX__/g, data.max_members)
                            .replace(/__MAX_RAW__/g, data.max_members)
                            .replace(/__STATUS__/g, statusClass)
                            .replace(/__STATUS_LABEL__/g, statusLabel)
                            .replace(/__STATUS_RAW__/g, data.status)
                            .replace(/__ORDER__/g, data.order || '0')
                            .replace(/__ORDER_RAW__/g, data.order || '0');

                        var wrapper = document.createElement('tbody');
                        wrapper.innerHTML = html;
                        return wrapper.querySelector('[data-plan-item]');
                    }

                    function updateRow(row, data) {
                        row.querySelector('[data-plan-name]').textContent = data.name;
                        row.querySelector('[data-plan-price]').textContent = data.price !== '' ? 'R$ ' + parseFloat(data.price).toLocaleString('pt-BR', { minimumFractionDigits: 2 }) : '';
                        row.querySelector('[data-plan-max]').textContent = data.max_members;
                        row.querySelector('[data-plan-order]').textContent = data.order || '0';

                        var statusBadge = row.querySelector('.juntaplay-admin-status');
                        statusBadge.classList.toggle('juntaplay-admin-status--inactive', data.status === 'inactive');
                        statusBadge.classList.toggle('juntaplay-admin-status--active', data.status !== 'inactive');
                        statusBadge.textContent = data.status === 'inactive' ? '<?php echo esc_js(__('Inativo', 'juntaplay')); ?>' : '<?php echo esc_js(__('Ativo', 'juntaplay')); ?>';

                        row.querySelector('input[name*="[name]"]').value = data.name;
                        row.querySelector('input[name*="[description]"]').value = data.description;
                        row.querySelector('input[name*="[price]"]').value = data.price;
                        row.querySelector('input[name*="[max_members]"]').value = data.max_members;
                        row.querySelector('input[name*="[status]"]').value = data.status;
                        row.querySelector('input[name*="[order]"]').value = data.order || '0';
                    }

                    function validate() {
                        if (!nameField.value.trim()) {
                            return '<?php echo esc_js(__('O nome do plano é obrigatório.', 'juntaplay')); ?>';
                        }
                        if (priceField.value === '' || parseFloat(priceField.value) <= 0) {
                            return '<?php echo esc_js(__('Informe um preço válido (maior que zero).', 'juntaplay')); ?>';
                        }
                        if (maxField.value === '' || parseInt(maxField.value, 10) < 1) {
                            return '<?php echo esc_js(__('Informe o máximo de usuários (mínimo 1).', 'juntaplay')); ?>';
                        }
                        if (orderField.value !== '' && !Number.isInteger(parseFloat(orderField.value))) {
                            return '<?php echo esc_js(__('A ordem deve ser um número inteiro.', 'juntaplay')); ?>';
                        }
                        return '';
                    }

                    document.addEventListener('click', function (event) {
                        var addButton = event.target.closest('[data-plan-add]');
                        if (addButton) {
                            event.preventDefault();
                            openModal('add');
                            return;
                        }

                        var editButton = event.target.closest('[data-plan-edit]');
                        if (editButton) {
                            event.preventDefault();
                            openModal('edit', editButton.closest('[data-plan-item]'));
                            return;
                        }

                        var removeButton = event.target.closest('[data-plan-remove]');
                        if (removeButton) {
                            event.preventDefault();
                            var row = removeButton.closest('[data-plan-item]');
                            if (row) {
                                row.remove();
                                var emptyRow = list.querySelector('[data-plan-empty]');
                                if (!list.querySelector('[data-plan-item]')) {
                                    if (emptyRow) {
                                        emptyRow.classList.remove('is-hidden');
                                    } else {
                                        var empty = document.createElement('tr');
                                        empty.setAttribute('data-plan-empty', '');
                                        empty.innerHTML = '<td colspan="6"><?php echo esc_js(__('Nenhuma variação cadastrada.', 'juntaplay')); ?></td>';
                                        list.appendChild(empty);
                                    }
                                }
                            }
                        }
                    });

                    saveButton.addEventListener('click', function () {
                        var error = validate();
                        if (error) {
                            errorField.textContent = error;
                            return;
                        }

                        var data = {
                            name: nameField.value.trim(),
                            description: descField.value.trim(),
                            price: priceField.value,
                            max_members: maxField.value,
                            status: statusField.value,
                            order: orderField.value || '0'
                        };

                        var emptyRow = list.querySelector('[data-plan-empty]');
                        if (emptyRow) {
                            emptyRow.classList.add('is-hidden');
                        }

                        if (currentRow) {
                            updateRow(currentRow, data);
                        } else {
                            var index = getNextIndex();
                            var row = renderRow(data, index);
                            if (row) {
                                list.appendChild(row);
                            }
                        }

                        closeModal();
                    });

                    closeButtons.forEach(function (button) {
                        button.addEventListener('click', function (event) {
                            event.preventDefault();
                            closeModal();
                        });
                    });

                    typeRadios.forEach(function (radio) {
                        radio.addEventListener('change', toggleSection);
                    });

                    toggleSection();
                })();
            </script>
        </form>
    </div>

    <h2 style="margin-top: 30px;"> <?php esc_html_e('Serviços cadastrados', 'juntaplay'); ?> </h2>
    <table class="wp-list-table widefat fixed striped juntaplay-admin-assets__table">
        <thead>
            <tr>
                <th class="column-icon"><?php esc_html_e('Ícone', 'juntaplay'); ?></th>
                <th class="column-cover"><?php esc_html_e('Capa', 'juntaplay'); ?></th>
                <th><?php esc_html_e('Título', 'juntaplay'); ?></th>
                <th><?php esc_html_e('Categoria', 'juntaplay'); ?></th>
                <th><?php esc_html_e('Site oficial', 'juntaplay'); ?></th>
                <th><?php esc_html_e('Preço', 'juntaplay'); ?></th>
                <th><?php esc_html_e('Status', 'juntaplay'); ?></th>
                <th><?php esc_html_e('Ações', 'juntaplay'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($pools)) : ?>
                <tr><td colspan="8"><?php esc_html_e('Nenhum serviço cadastrado ainda.', 'juntaplay'); ?></td></tr>
            <?php else : ?>
                <?php foreach ($pools as $pool) : ?>
                    <?php
                    $icon_url  = $pool['icon'] ?? '';
                    $cover_url = $pool['cover'] ?? '';
                    ?>
                    <tr>
                        <td class="column-icon">
                            <span class="juntaplay-admin-asset-thumb<?php echo $icon_url ? ' has-image' : ''; ?>" aria-hidden="true">
                                <?php if ($icon_url) : ?>
                                    <img src="<?php echo esc_url($icon_url); ?>" alt="" />
                                <?php else : ?>
                                    <span class="juntaplay-admin-asset-thumb__placeholder">—</span>
                                <?php endif; ?>
                            </span>
                        </td>
                        <td class="column-cover">
                            <span class="juntaplay-admin-asset-thumb<?php echo $cover_url ? ' has-image' : ''; ?>" aria-hidden="true">
                                <?php if ($cover_url) : ?>
                                    <img src="<?php echo esc_url($cover_url); ?>" alt="" />
                                <?php else : ?>
                                    <span class="juntaplay-admin-asset-thumb__placeholder">—</span>
                                <?php endif; ?>
                            </span>
                        </td>
                        <td><?php echo esc_html($pool['title'] ?? ''); ?></td>
                        <td><?php echo esc_html($categories[$pool['category'] ?? ''] ?? ''); ?></td>
                        <td class="column-site">
                            <?php if (!empty($pool['service_url'])) : ?>
                                <a href="<?php echo esc_url($pool['service_url']); ?>" target="_blank" rel="noopener noreferrer">
                                    <?php echo esc_html($pool['service_url']); ?>
                                </a>
                            <?php else : ?>
                                <span class="juntaplay-admin-asset-thumb__placeholder">—</span>
                            <?php endif; ?>
                        </td>
                        <?php
                        $display_price = function_exists('wc_price')
                            ? wc_price((float) ($pool['price'] ?? 0))
                            : number_format((float) ($pool['price'] ?? 0), 2, ',', '.');
                        ?>
                        <td><?php echo wp_kses_post($display_price); ?></td>
                        <td>
                            <?php
                            $status_label = ($pool['status'] ?? '') === 'draft'
                                ? esc_html__('Inativo', 'juntaplay')
                                : esc_html__('Ativo', 'juntaplay');
                            echo esc_html($status_label);
                            ?>
                        </td>
                        <td>
                            <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=juntaplay-pools&edit=' . absint($pool['id'] ?? 0))); ?>"><?php esc_html_e('Editar', 'juntaplay'); ?></a>
                            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline-block; margin-left:5px;">
                                <?php wp_nonce_field('juntaplay_pool_delete'); ?>
                                <input type="hidden" name="action" value="juntaplay_pool_delete" />
                                <input type="hidden" name="pool_id" value="<?php echo esc_attr($pool['id'] ?? 0); ?>" />
                                <button type="submit" class="button-link-delete" onclick="return confirm('<?php echo esc_js(__('Tem certeza que deseja excluir?', 'juntaplay')); ?>');"><?php esc_html_e('Excluir', 'juntaplay'); ?></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <style>
        .juntaplay-admin-section {
            margin-bottom: 20px;
            padding: 18px 20px;
        }

        .juntaplay-admin-section h3 {
            margin-top: 0;
        }

        .juntaplay-admin-section .is-hidden {
            display: none;
        }

        .juntaplay-admin-section__header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
        }

        .juntaplay-admin-type {
            display: flex;
            gap: 20px;
        }

        .juntaplay-admin-section--plans.is-hidden {
            display: none;
        }

        .juntaplay-admin-plans__table th,
        .juntaplay-admin-plans__table td {
            vertical-align: middle;
        }

        .juntaplay-admin-plans__actions {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .juntaplay-admin-plans__hidden {
            display: none;
        }

        .juntaplay-admin-status {
            display: inline-flex;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
        }

        .juntaplay-admin-status--active {
            background: #ecfdf5;
            color: #047857;
        }

        .juntaplay-admin-status--inactive {
            background: #fef2f2;
            color: #b91c1c;
        }

        .juntaplay-admin-plans__hint {
            margin-top: 12px;
        }

        .juntaplay-admin-modal {
            position: fixed;
            inset: 0;
            z-index: 10000;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .juntaplay-admin-modal.is-hidden {
            display: none;
        }

        .juntaplay-admin-modal__backdrop {
            position: absolute;
            inset: 0;
            background: rgba(0, 0, 0, 0.4);
        }

        .juntaplay-admin-modal__content {
            position: relative;
            z-index: 2;
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            width: min(560px, 90vw);
            box-shadow: 0 20px 40px rgba(15, 23, 42, 0.2);
        }

        .juntaplay-admin-modal__header {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .juntaplay-admin-modal__body {
            display: grid;
            gap: 12px;
            margin-top: 12px;
        }

        .juntaplay-admin-modal__footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 16px;
        }

        .juntaplay-admin-field label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
        }

        .juntaplay-admin-modal__error {
            color: #b91c1c;
            font-weight: 600;
            min-height: 18px;
        }

        .juntaplay-admin-asset-card__header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
        }

        .juntaplay-admin-asset-card__header h3 {
            margin: 0;
        }

        .juntaplay-admin-asset-card__badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 4px 8px;
            border-radius: 999px;
            background: #eef2ff;
            color: #1f2937;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.02em;
        }

        .juntaplay-admin-asset-card__actions {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }

        .juntaplay-admin-asset-card__hint {
            margin: 0;
            font-size: 12px;
            color: #6b7280;
        }

        .juntaplay-admin-asset-card__hint.is-error {
            color: #b91c1c;
            font-weight: 600;
        }

        .juntaplay-media-picker {
            display: grid;
            grid-template-columns: 120px 1fr;
            gap: 12px 16px;
            align-items: start;
        }

        .juntaplay-media-picker__preview {
            width: 120px;
            height: 120px;
            border: 1px solid #ccd0d4;
            border-radius: 6px;
            background: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .juntaplay-media-picker.is-invalid .juntaplay-media-picker__preview {
            border-color: #ef4444;
            box-shadow: 0 0 0 1px #fecdd3;
        }

        .juntaplay-media-picker__preview img {
            max-width: 100%;
            height: auto;
            display: block;
        }

        .juntaplay-media-picker__placeholder {
            color: #555;
            font-size: 12px;
            text-align: center;
            padding: 0 6px;
        }

        .juntaplay-media-picker button + button {
            margin-left: 0;
        }

        .juntaplay-admin-assets__table .column-icon,
        .juntaplay-admin-assets__table .column-cover {
            width: 72px;
        }

        .juntaplay-admin-assets__table .column-site {
            width: 18%;
            word-break: break-word;
        }

        .juntaplay-admin-asset-thumb {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 48px;
            height: 48px;
            border-radius: 8px;
            border: 1px solid #d8dce3;
            background: #f8fafc;
            overflow: hidden;
        }

        .juntaplay-admin-asset-thumb.has-image {
            background: #fff;
        }

        .juntaplay-admin-asset-thumb img {
            max-width: 100%;
            max-height: 100%;
            display: block;
        }

        .juntaplay-admin-asset-thumb__placeholder {
            color: #6b7280;
        }
    </style>

</div>
