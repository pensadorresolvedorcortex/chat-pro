<?php
declare(strict_types=1);

namespace JuntaPlay\Admin;

use JuntaPlay\Data\Pools;
use JuntaPlay\Data\Quotas;
use JuntaPlay\Setup\DemoSeeder;
use WC_Product_Simple;

use function absint;
use function add_query_arg;
use function check_admin_referer;
use function current_user_can;
use function delete_transient;
use function esc_html;
use function esc_html_e;
use function esc_url;
use function get_transient;
use function is_wp_error;
use function sanitize_key;
use function sanitize_text_field;
use function sanitize_title;
use function set_transient;
use function submit_button;
use function wp_die;
use function wp_get_referer;
use function wp_safe_redirect;
use function wp_unslash;

use const MINUTE_IN_SECONDS;

defined('ABSPATH') || exit;

class Importer
{
    public function init(): void
    {
        add_action('admin_post_juntaplay_import_csv', [$this, 'handle_import']);
        add_action('admin_post_juntaplay_generate_pages', [$this, 'handle_generate_pages']);
        add_action('admin_post_juntaplay_seed_demo', [$this, 'handle_seed_demo']);
        add_action('juntaplay/admin/import_page', [$this, 'render']);
    }

    public function render(): void
    {
        $success = isset($_GET['jp_success']) ? sanitize_key(wp_unslash((string) $_GET['jp_success'])) : '';
        $error   = isset($_GET['jp_error']) ? sanitize_key(wp_unslash((string) $_GET['jp_error'])) : '';

        $demo_result = null;
        if ($success === 'demo') {
            $demo_result = get_transient('juntaplay_demo_seed_result');
            if ($demo_result !== false) {
                delete_transient('juntaplay_demo_seed_result');
            }
        }

        $demo_error = '';
        if ($error === 'demo') {
            $stored_error = get_transient('juntaplay_demo_seed_error');
            if ($stored_error !== false) {
                $demo_error = (string) $stored_error;
                delete_transient('juntaplay_demo_seed_error');
            }
        }

        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Importação e Páginas Automáticas', 'juntaplay'); ?></h1>

            <?php if ($demo_result && is_array($demo_result)) :
                $created_users = array_filter($demo_result['users'], static fn ($u) => isset($u['status']) && $u['status'] === 'created');
                $skipped_users = array_filter($demo_result['users'], static fn ($u) => isset($u['status']) && $u['status'] === 'existing');
                $created_groups = array_filter($demo_result['groups'], static fn ($g) => isset($g['status']) && $g['status'] === 'created');
                $skipped_groups = array_filter($demo_result['groups'], static fn ($g) => isset($g['status']) && $g['status'] !== 'created');
            ?>
                <div class="notice notice-success is-dismissible">
                    <p><strong><?php esc_html_e('Dados de demonstração criados com sucesso.', 'juntaplay'); ?></strong></p>
                    <p><?php echo esc_html(sprintf(
                        /* translators: 1: count of users created, 2: count of groups created */
                        __('Usuários criados: %1$d · Grupos criados: %2$d.', 'juntaplay'),
                        count($created_users),
                        count($created_groups)
                    )); ?></p>
                    <p><?php echo esc_html(sprintf(
                        /* translators: %s: demo password */
                        __('Senha de demonstração para todos os perfis: %s', 'juntaplay'),
                        $demo_result['demo_password']
                    )); ?></p>
                    <?php if ($skipped_users) : ?>
                        <p><?php echo esc_html(sprintf(
                            /* translators: %d: count of existing users */
                            __('Usuários já existentes preservados: %d.', 'juntaplay'),
                            count($skipped_users)
                        )); ?></p>
                    <?php endif; ?>
                    <?php if ($skipped_groups) : ?>
                        <p><?php esc_html_e('Alguns grupos foram ignorados por já existirem ou por falta do responsável.', 'juntaplay'); ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ($demo_error !== '') : ?>
                <div class="notice notice-error">
                    <p><strong><?php esc_html_e('Não foi possível gerar os dados de demonstração.', 'juntaplay'); ?></strong></p>
                    <p><?php echo esc_html($demo_error); ?></p>
                </div>
            <?php endif; ?>

            <h2><?php esc_html_e('Importar Campanhas via CSV', 'juntaplay'); ?></h2>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data">
                <?php wp_nonce_field('juntaplay_import_csv'); ?>
                <input type="hidden" name="action" value="juntaplay_import_csv" />
                <p>
                    <input type="file" name="juntaplay_csv" accept="text/csv" required />
                </p>
                <p class="description"><?php esc_html_e('Colunas esperadas: title, slug, price, quota_start, quota_end, category (opcional), excerpt, thumbnail_id, is_featured.', 'juntaplay'); ?></p>
                <?php submit_button(__('Importar CSV', 'juntaplay')); ?>
            </form>

            <hr />

            <h2><?php esc_html_e('Gerar/Recriar Páginas com Shortcodes', 'juntaplay'); ?></h2>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('juntaplay_generate_pages'); ?>
                <input type="hidden" name="action" value="juntaplay_generate_pages" />
                <?php submit_button(__('Gerar Páginas Padrão', 'juntaplay'), 'secondary'); ?>
            </form>

            <hr />

            <h2><?php esc_html_e('Popular dados de demonstração', 'juntaplay'); ?></h2>
            <p class="description">
                <?php esc_html_e('Cria usuários fictícios, grupos populares (YouTube Premium, Spotify, Canva, ExpressVPN e outros) e relacionamentos para testar buscas e aprovações.', 'juntaplay'); ?>
            </p>
            <p class="description">
                <?php esc_html_e('A senha padrão utilizada para todos os perfis de exemplo é JuntaPlay#2024. Execute apenas em ambientes de testes.', 'juntaplay'); ?>
            </p>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('juntaplay_seed_demo'); ?>
                <input type="hidden" name="action" value="juntaplay_seed_demo" />
                <?php submit_button(__('Criar dados de demonstração', 'juntaplay'), 'secondary'); ?>
            </form>
        </div>
        <?php
    }

    public function handle_import(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('Você não tem permissão para executar esta ação.', 'juntaplay'));
        }

        check_admin_referer('juntaplay_import_csv');

        if (empty($_FILES['juntaplay_csv']['tmp_name'])) {
            wp_safe_redirect(add_query_arg('jp_error', 'no_file', wp_get_referer()));
            exit;
        }

        $file = fopen($_FILES['juntaplay_csv']['tmp_name'], 'r');

        if (!$file) {
            wp_safe_redirect(add_query_arg('jp_error', 'invalid_file', wp_get_referer()));
            exit;
        }

        $header = fgetcsv($file, 0, ',');
        $rows   = [];

        while (($data = fgetcsv($file, 0, ',')) !== false) {
            $row = array_combine($header, $data);
            if (!$row) {
                continue;
            }

            $rows[] = $row;
        }

        fclose($file);

        foreach ($rows as $row) {
            $pool_id = Pools::create_or_update([
                'title'        => sanitize_text_field($row['title'] ?? ''),
                'slug'         => sanitize_title($row['slug'] ?? ''),
                'price'        => (float) ($row['price'] ?? 0),
                'quota_start'  => (int) ($row['quota_start'] ?? 1),
                'quota_end'    => (int) ($row['quota_end'] ?? 1),
                'category'     => sanitize_key($row['category'] ?? ''),
                'excerpt'      => sanitize_text_field($row['excerpt'] ?? ''),
                'thumbnail_id' => isset($row['thumbnail_id']) ? absint($row['thumbnail_id']) : null,
                'is_featured'  => !empty($row['is_featured']) && in_array(strtolower((string) $row['is_featured']), ['1', 'yes', 'true'], true),
            ]);

            if ($pool_id) {
                Pools::ensure_product((int) $pool_id);
                Quotas::seed((int) $pool_id);
            }
        }

        wp_safe_redirect(add_query_arg('jp_success', 'import', admin_url('admin.php?page=juntaplay-import')));
        exit;
    }

    public function handle_generate_pages(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('Você não tem permissão para executar esta ação.', 'juntaplay'));
        }

        check_admin_referer('juntaplay_generate_pages');

        (new \JuntaPlay\Installer())->activate();

        wp_safe_redirect(add_query_arg('jp_success', 'pages', admin_url('admin.php?page=juntaplay-import')));
        exit;
    }

    public function handle_seed_demo(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('Você não tem permissão para executar esta ação.', 'juntaplay'));
        }

        check_admin_referer('juntaplay_seed_demo');

        $seeder = new DemoSeeder();
        $result = $seeder->seed();

        if (is_wp_error($result)) {
            set_transient('juntaplay_demo_seed_error', $result->get_error_message(), 5 * MINUTE_IN_SECONDS);
            wp_safe_redirect(add_query_arg('jp_error', 'demo', admin_url('admin.php?page=juntaplay-import')));
            exit;
        }

        set_transient('juntaplay_demo_seed_result', $result, 5 * MINUTE_IN_SECONDS);

        wp_safe_redirect(add_query_arg('jp_success', 'demo', admin_url('admin.php?page=juntaplay-import')));
        exit;
    }
}
