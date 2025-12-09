<?php
/** @var array<string, mixed> $context */

declare(strict_types=1);

defined('ABSPATH') || exit;

$notice     = $context['notice'] ?? null;
$categories = $context['categories'] ?? [];
$editing    = $context['editing'] ?? '';
$editing_label = $editing && isset($categories[$editing]) ? (string) $categories[$editing] : '';
?>
<div class="wrap">
    <h1><?php echo esc_html__('Categorias', 'juntaplay'); ?></h1>

    <?php if ($notice) : ?>
        <div class="notice notice-<?php echo esc_attr($notice['type'] ?? 'info'); ?> is-dismissible">
            <p><?php echo esc_html($notice['message'] ?? ''); ?></p>
        </div>
    <?php endif; ?>

    <div class="card" style="margin-top:20px;">
        <h2><?php echo $editing ? esc_html__('Editar categoria', 'juntaplay') : esc_html__('Nova categoria', 'juntaplay'); ?></h2>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('juntaplay_pool_category_save'); ?>
            <input type="hidden" name="action" value="juntaplay_pool_category_save" />
            <input type="hidden" name="category_slug" value="<?php echo esc_attr($editing); ?>" />

            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row"><label for="category_label"><?php esc_html_e('Nome da categoria', 'juntaplay'); ?></label></th>
                        <td><input type="text" class="regular-text" id="category_label" name="category_label" value="<?php echo esc_attr($editing_label); ?>" required /></td>
                    </tr>
                </tbody>
            </table>

            <p class="submit">
                <button type="submit" class="button button-primary"><?php echo $editing ? esc_html__('Salvar alterações', 'juntaplay') : esc_html__('Adicionar categoria', 'juntaplay'); ?></button>
                <?php if ($editing) : ?>
                    <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=juntaplay-pool-categories')); ?>"><?php esc_html_e('Cancelar edição', 'juntaplay'); ?></a>
                <?php endif; ?>
            </p>
        </form>
    </div>

    <h2 style="margin-top:30px;"> <?php esc_html_e('Categorias cadastradas', 'juntaplay'); ?> </h2>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php esc_html_e('Nome', 'juntaplay'); ?></th>
                <th><?php esc_html_e('Slug', 'juntaplay'); ?></th>
                <th><?php esc_html_e('Ações', 'juntaplay'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($categories)) : ?>
                <tr>
                    <td colspan="3"><?php esc_html_e('Nenhuma categoria encontrada.', 'juntaplay'); ?></td>
                </tr>
            <?php else : ?>
                <?php foreach ($categories as $slug => $label) : ?>
                    <tr>
                        <td><?php echo esc_html($label); ?></td>
                        <td><code><?php echo esc_html($slug); ?></code></td>
                        <td>
                            <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=juntaplay-pool-categories&edit=' . rawurlencode((string) $slug))); ?>"><?php esc_html_e('Editar', 'juntaplay'); ?></a>
                            <?php if ($slug !== 'other') : ?>
                                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline-block; margin-left:6px;">
                                    <?php wp_nonce_field('juntaplay_pool_category_delete'); ?>
                                    <input type="hidden" name="action" value="juntaplay_pool_category_delete" />
                                    <input type="hidden" name="category_slug" value="<?php echo esc_attr((string) $slug); ?>" />
                                    <button type="submit" class="button button-link-delete" onclick="return confirm('<?php echo esc_attr__('Tem certeza que deseja excluir esta categoria?', 'juntaplay'); ?>');"><?php esc_html_e('Excluir', 'juntaplay'); ?></button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
