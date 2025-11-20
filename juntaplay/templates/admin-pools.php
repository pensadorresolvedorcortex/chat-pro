<?php
/** @var array<string, mixed> $context */

declare(strict_types=1);

defined('ABSPATH') || exit;

$notice     = $context['notice'] ?? null;
$pools      = $context['pools'] ?? [];
$categories = $context['categories'] ?? [];
$editing    = $context['editing'] ?? null;
?>
<div class="wrap">
    <h1><?php echo esc_html__('Serviços pré-aprovados', 'juntaplay'); ?></h1>

    <?php if ($notice) : ?>
        <div class="notice notice-<?php echo esc_attr($notice['type'] ?? 'info'); ?> is-dismissible">
            <p><?php echo esc_html($notice['message'] ?? ''); ?></p>
        </div>
    <?php endif; ?>

    <div class="card" style="margin-top: 20px;">
        <h2><?php echo $editing ? esc_html__('Editar serviço', 'juntaplay') : esc_html__('Criar serviço', 'juntaplay'); ?></h2>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('juntaplay_pool_save'); ?>
            <input type="hidden" name="action" value="juntaplay_pool_save" />
            <input type="hidden" name="pool_id" value="<?php echo esc_attr($editing->id ?? 0); ?>" />

            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row"><label for="title"><?php esc_html_e('Título', 'juntaplay'); ?></label></th>
                        <td><input type="text" class="regular-text" id="title" name="title" value="<?php echo esc_attr($editing->title ?? ''); ?>" required /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="slug"><?php esc_html_e('Slug', 'juntaplay'); ?></label></th>
                        <td><input type="text" class="regular-text" id="slug" name="slug" value="<?php echo esc_attr($editing->slug ?? ''); ?>" required /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="price"><?php esc_html_e('Preço', 'juntaplay'); ?></label></th>
                        <td><input type="number" step="0.01" min="0" id="price" name="price" value="<?php echo esc_attr($editing->price ?? '0'); ?>" required /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="service_url"><?php esc_html_e('Site oficial do serviço', 'juntaplay'); ?></label></th>
                        <td><input type="url" class="regular-text" id="service_url" name="service_url" value="<?php echo esc_attr($editing->service_url ?? ''); ?>" placeholder="https://" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="icon_id"><?php esc_html_e('Ícone', 'juntaplay'); ?></label></th>
                        <td>
                            <div class="juntaplay-media-picker" data-media-field="icon_id">
                                <div class="juntaplay-media-picker__preview" data-media-preview>
                                    <?php if (!empty($editing->icon)) : ?>
                                        <img src="<?php echo esc_url($editing->icon); ?>" alt="" />
                                    <?php else : ?>
                                        <span class="juntaplay-media-picker__placeholder"><?php esc_html_e('Nenhum ícone selecionado', 'juntaplay'); ?></span>
                                    <?php endif; ?>
                                </div>
                                <input type="hidden" id="icon_id" name="icon_id" value="<?php echo esc_attr($editing->icon_id ?? ''); ?>" />
                                <button type="button" class="button" data-media-select><?php esc_html_e('Escolher ícone', 'juntaplay'); ?></button>
                                <button type="button" class="button button-secondary" data-media-remove><?php esc_html_e('Remover', 'juntaplay'); ?></button>
                                <p class="description"><?php esc_html_e('Envie o ícone na biblioteca de mídia (arte quadrada) e selecione aqui.', 'juntaplay'); ?></p>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="cover_id"><?php esc_html_e('Capa do grupo (495x370 px)', 'juntaplay'); ?></label></th>
                        <td>
                            <div class="juntaplay-media-picker" data-media-field="cover_id">
                                <div class="juntaplay-media-picker__preview" data-media-preview>
                                    <?php if (!empty($editing->cover)) : ?>
                                        <img src="<?php echo esc_url($editing->cover); ?>" alt="" />
                                    <?php else : ?>
                                        <span class="juntaplay-media-picker__placeholder"><?php esc_html_e('Nenhuma capa selecionada', 'juntaplay'); ?></span>
                                    <?php endif; ?>
                                </div>
                                <input type="hidden" id="cover_id" name="cover_id" value="<?php echo esc_attr($editing->cover_id ?? ''); ?>" />
                                <button type="button" class="button" data-media-select><?php esc_html_e('Escolher capa', 'juntaplay'); ?></button>
                                <button type="button" class="button button-secondary" data-media-remove><?php esc_html_e('Remover', 'juntaplay'); ?></button>
                                <p class="description"><?php esc_html_e('Selecione uma imagem 495×370 px para preencher os cards aprovados.', 'juntaplay'); ?></p>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="quota_start"><?php esc_html_e('Cota inicial', 'juntaplay'); ?></label></th>
                        <td><input type="number" min="1" id="quota_start" name="quota_start" value="<?php echo esc_attr($editing->quota_start ?? '1'); ?>" required /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="quota_end"><?php esc_html_e('Cota final', 'juntaplay'); ?></label></th>
                        <td><input type="number" min="1" id="quota_end" name="quota_end" value="<?php echo esc_attr($editing->quota_end ?? '1'); ?>" required /></td>
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
                        <th scope="row"><label for="status"><?php esc_html_e('Status', 'juntaplay'); ?></label></th>
                        <td>
                            <select id="status" name="status">
                                <option value="publish" <?php selected($editing->status ?? 'publish', 'publish'); ?>><?php esc_html_e('Publicado', 'juntaplay'); ?></option>
                                <option value="draft" <?php selected($editing->status ?? '', 'draft'); ?>><?php esc_html_e('Rascunho', 'juntaplay'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="excerpt"><?php esc_html_e('Descrição curta', 'juntaplay'); ?></label></th>
                        <td><textarea id="excerpt" name="excerpt" rows="3" class="large-text"><?php echo esc_textarea($editing->excerpt ?? ''); ?></textarea></td>
                    </tr>
                    <tr>
                        <th scope="row">&nbsp;</th>
                        <td>
                            <label><input type="checkbox" name="is_featured" value="1" <?php checked(!empty($editing->is_featured)); ?> /> <?php esc_html_e('Destacar serviço', 'juntaplay'); ?></label>
                        </td>
                    </tr>
                </tbody>
            </table>

            <p class="submit">
                <button type="submit" class="button button-primary"><?php echo $editing ? esc_html__('Salvar alterações', 'juntaplay') : esc_html__('Criar serviço', 'juntaplay'); ?></button>
                <?php if ($editing) : ?>
                    <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=juntaplay-pools')); ?>"><?php esc_html_e('Cancelar edição', 'juntaplay'); ?></a>
                <?php endif; ?>
            </p>
        </form>
    </div>

    <h2 style="margin-top: 30px;"> <?php esc_html_e('Serviços cadastrados', 'juntaplay'); ?> </h2>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php esc_html_e('Título', 'juntaplay'); ?></th>
                <th><?php esc_html_e('Slug', 'juntaplay'); ?></th>
                <th><?php esc_html_e('Categoria', 'juntaplay'); ?></th>
                <th><?php esc_html_e('Preço', 'juntaplay'); ?></th>
                <th><?php esc_html_e('Status', 'juntaplay'); ?></th>
                <th><?php esc_html_e('Ações', 'juntaplay'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($pools)) : ?>
                <tr><td colspan="6"><?php esc_html_e('Nenhum serviço cadastrado ainda.', 'juntaplay'); ?></td></tr>
            <?php else : ?>
                <?php foreach ($pools as $pool) : ?>
                    <tr>
                        <td><?php echo esc_html($pool['title'] ?? ''); ?></td>
                        <td><?php echo esc_html($pool['slug'] ?? ''); ?></td>
                        <td><?php echo esc_html($categories[$pool['category'] ?? ''] ?? ''); ?></td>
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
            border-radius: 4px;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .juntaplay-media-picker__preview img {
            max-width: 100%;
            height: auto;
            display: block;
        }

        .juntaplay-media-picker__placeholder {
            color: #777;
            font-size: 12px;
            text-align: center;
            padding: 0 6px;
        }

        .juntaplay-media-picker button + button {
            margin-left: 6px;
        }
    </style>

    <script>
        (function() {
            if (!window.wp || !wp.media) {
                return;
            }

            const pickers = document.querySelectorAll('.juntaplay-media-picker');

            pickers.forEach((picker) => {
                const field    = picker.getAttribute('data-media-field');
                const input    = picker.querySelector('input[name="' + field + '"]');
                const preview  = picker.querySelector('[data-media-preview]');
                const select   = picker.querySelector('[data-media-select]');
                const remove   = picker.querySelector('[data-media-remove]');
                const empty    = preview ? preview.innerHTML : '';
                let frame      = null;

                const render = (url) => {
                    if (!preview) {
                        return;
                    }

                    if (url) {
                        preview.innerHTML = '<img src="' + url + '" alt="" />';
                    } else {
                        preview.innerHTML = empty;
                    }
                };

                if (select) {
                    select.addEventListener('click', () => {
                        if (!frame) {
                            frame = wp.media({
                                title: select.textContent || '',
                                button: { text: select.textContent || '' },
                                multiple: false,
                            });

                            frame.on('select', () => {
                                const attachment = frame.state().get('selection').first();
                                if (!attachment) {
                                    return;
                                }

                                const data = attachment.toJSON();
                                if (input) {
                                    input.value = data.id || '';
                                }
                                render(data.url || '');
                            });
                        }

                        frame.open();
                    });
                }

                if (remove) {
                    remove.addEventListener('click', () => {
                        if (input) {
                            input.value = '';
                        }
                        render('');
                    });
                }
            });
        })();
    </script>
</div>
