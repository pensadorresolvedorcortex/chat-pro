<?php
/** @var array<string, mixed> $context */

declare(strict_types=1);

defined('ABSPATH') || exit;

$notice     = $context['notice'] ?? null;
$pools      = $context['pools'] ?? [];
$categories = $context['categories'] ?? [];
$editing    = $context['editing'] ?? null;
$plans      = $context['plans'] ?? [];
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
                        <tr>
                            <th scope="row"><label for="quota_start"><?php esc_html_e('Quantidade mínima', 'juntaplay'); ?></label></th>
                            <td><input type="number" min="1" id="quota_start" name="quota_start" value="<?php echo esc_attr($editing->quota_start ?? '1'); ?>" required /></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="quota_end"><?php esc_html_e('Quantidade máxima', 'juntaplay'); ?></label></th>
                            <td><input type="number" min="1" id="quota_end" name="quota_end" value="<?php echo esc_attr($editing->quota_end ?? '1'); ?>" required /></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="status"><?php esc_html_e('Status', 'juntaplay'); ?></label></th>
                            <td>
                                <select id="status" name="status">
                                    <option value="publish" <?php selected($editing->status ?? 'publish', 'publish'); ?>><?php esc_html_e('Publicado', 'juntaplay'); ?></option>
                                    <option value="draft" <?php selected($editing->status ?? '', 'draft'); ?>><?php esc_html_e('Rascunho', 'juntaplay'); ?></option>
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
            if (empty($plans_display)) {
                $plans_display[] = [
                    'name'        => '',
                    'description' => '',
                    'price'       => '',
                    'max_members' => '',
                    'status'      => 'active',
                    'order'       => '',
                ];
            }
            ?>

            <div class="card juntaplay-admin-section">
                <h3><?php esc_html_e('Planos do serviço', 'juntaplay'); ?></h3>
                <p class="description"><?php esc_html_e('Cadastre variações de preço e quantidade de membros para este serviço.', 'juntaplay'); ?></p>
                <div class="juntaplay-admin-plans" data-plan-list>
                    <?php foreach ($plans_display as $index => $plan) : ?>
                        <div class="juntaplay-admin-plan" data-plan-item>
                            <div class="juntaplay-admin-plan__header">
                                <strong><?php echo esc_html(sprintf(__('Plano %d', 'juntaplay'), $index + 1)); ?></strong>
                                <button type="button" class="button-link-delete" data-plan-remove><?php esc_html_e('Remover', 'juntaplay'); ?></button>
                            </div>
                            <table class="form-table" role="presentation">
                                <tbody>
                                    <tr>
                                        <th scope="row"><label><?php esc_html_e('Nome do plano', 'juntaplay'); ?></label></th>
                                        <td>
                                            <input
                                                type="text"
                                                class="regular-text"
                                                name="jp_plans[<?php echo esc_attr((string) $index); ?>][name]"
                                                value="<?php echo esc_attr((string) ($plan['name'] ?? '')); ?>"
                                                required
                                            />
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><label><?php esc_html_e('Descrição curta', 'juntaplay'); ?></label></th>
                                        <td>
                                            <textarea
                                                name="jp_plans[<?php echo esc_attr((string) $index); ?>][description]"
                                                rows="2"
                                                class="large-text"
                                            ><?php echo esc_textarea((string) ($plan['description'] ?? '')); ?></textarea>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><label><?php esc_html_e('Preço base', 'juntaplay'); ?></label></th>
                                        <td>
                                            <input
                                                type="number"
                                                step="0.01"
                                                min="0"
                                                name="jp_plans[<?php echo esc_attr((string) $index); ?>][price]"
                                                value="<?php echo esc_attr((string) ($plan['price'] ?? '')); ?>"
                                                required
                                            />
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><label><?php esc_html_e('Máx. de usuários', 'juntaplay'); ?></label></th>
                                        <td>
                                            <input
                                                type="number"
                                                min="1"
                                                name="jp_plans[<?php echo esc_attr((string) $index); ?>][max_members]"
                                                value="<?php echo esc_attr((string) ($plan['max_members'] ?? '')); ?>"
                                                required
                                            />
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><label><?php esc_html_e('Status', 'juntaplay'); ?></label></th>
                                        <td>
                                            <?php $plan_status = $plan['status'] ?? 'active'; ?>
                                            <select name="jp_plans[<?php echo esc_attr((string) $index); ?>][status]">
                                                <option value="active" <?php selected($plan_status, 'active'); ?>><?php esc_html_e('Ativo', 'juntaplay'); ?></option>
                                                <option value="inactive" <?php selected($plan_status, 'inactive'); ?>><?php esc_html_e('Inativo', 'juntaplay'); ?></option>
                                            </select>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><label><?php esc_html_e('Ordem', 'juntaplay'); ?></label></th>
                                        <td>
                                            <input
                                                type="number"
                                                min="0"
                                                name="jp_plans[<?php echo esc_attr((string) $index); ?>][order]"
                                                value="<?php echo esc_attr((string) ($plan['order'] ?? '')); ?>"
                                            />
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="button" data-plan-add><?php esc_html_e('Adicionar plano', 'juntaplay'); ?></button>
            </div>

            <div class="card juntaplay-admin-section">
                <h3><?php esc_html_e('Configuração financeira', 'juntaplay'); ?></h3>
                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
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
                <div class="juntaplay-admin-plan" data-plan-item>
                    <div class="juntaplay-admin-plan__header">
                        <strong><?php echo esc_html__('Novo plano', 'juntaplay'); ?></strong>
                        <button type="button" class="button-link-delete" data-plan-remove><?php esc_html_e('Remover', 'juntaplay'); ?></button>
                    </div>
                    <table class="form-table" role="presentation">
                        <tbody>
                            <tr>
                                <th scope="row"><label><?php esc_html_e('Nome do plano', 'juntaplay'); ?></label></th>
                                <td>
                                    <input type="text" class="regular-text" name="jp_plans[__INDEX__][name]" required />
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label><?php esc_html_e('Descrição curta', 'juntaplay'); ?></label></th>
                                <td>
                                    <textarea name="jp_plans[__INDEX__][description]" rows="2" class="large-text"></textarea>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label><?php esc_html_e('Preço base', 'juntaplay'); ?></label></th>
                                <td>
                                    <input type="number" step="0.01" min="0" name="jp_plans[__INDEX__][price]" required />
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label><?php esc_html_e('Máx. de usuários', 'juntaplay'); ?></label></th>
                                <td>
                                    <input type="number" min="1" name="jp_plans[__INDEX__][max_members]" required />
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label><?php esc_html_e('Status', 'juntaplay'); ?></label></th>
                                <td>
                                    <select name="jp_plans[__INDEX__][status]">
                                        <option value="active"><?php esc_html_e('Ativo', 'juntaplay'); ?></option>
                                        <option value="inactive"><?php esc_html_e('Inativo', 'juntaplay'); ?></option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label><?php esc_html_e('Ordem', 'juntaplay'); ?></label></th>
                                <td>
                                    <input type="number" min="0" name="jp_plans[__INDEX__][order]" />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </template>

            <script>
                (function () {
                    var list = document.querySelector('[data-plan-list]');
                    if (!list) {
                        return;
                    }

                    function nextIndex() {
                        return list.querySelectorAll('[data-plan-item]').length;
                    }

                    document.addEventListener('click', function (event) {
                        var addButton = event.target.closest('[data-plan-add]');
                        if (addButton) {
                            event.preventDefault();
                            var template = document.getElementById('jp-plan-template');
                            if (!template) {
                                return;
                            }
                            var index = nextIndex();
                            var html = template.innerHTML.replace(/__INDEX__/g, index);
                            var wrapper = document.createElement('div');
                            wrapper.innerHTML = html;
                            var node = wrapper.firstElementChild;
                            if (node) {
                                list.appendChild(node);
                            }
                        }

                        var removeButton = event.target.closest('[data-plan-remove]');
                        if (removeButton) {
                            event.preventDefault();
                            var item = removeButton.closest('[data-plan-item]');
                            if (item) {
                                item.remove();
                            }
                        }
                    });
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
                        <td><?php echo esc_html($pool['status'] ?? ''); ?></td>
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
