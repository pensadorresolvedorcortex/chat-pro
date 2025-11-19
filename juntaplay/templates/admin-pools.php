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
</div>
