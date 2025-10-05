<?php
/**
 * Plugin Name: JuntaPlay Newsletter
 * Description: Newsletter signup form and data export for super admins.
 * Version: 1.2.1
 * Author: OpenAI Assistant
 * Text Domain: juntaplay-newsletter
 */

if (!defined('ABSPATH')) {
    exit;
}

class JuntaPlay_Newsletter {
    const VERSION = '1.2.1';
    const TABLE_NAME = 'juntaplay_newsletter';
    const NONCE_ACTION = 'juntaplay_newsletter_submit';
    const NONCE_NAME = 'juntaplay_newsletter_nonce';
    const OPTION_VERSION = 'juntaplay_newsletter_version';

    /** @var string */
    private $admin_capability;

    /** @var string */
    private $table_name;

    public function __construct() {
        $this->admin_capability = is_multisite() ? 'manage_network_options' : 'manage_options';
        $this->table_name = $this->resolve_table_name();

        register_activation_hook(__FILE__, [$this, 'activate']);
        add_action('plugins_loaded', [$this, 'load_textdomain']);
        add_shortcode('juntaplay_newsletter_form', [$this, 'render_form']);
        add_action('init', [$this, 'handle_submission']);

        if (is_multisite()) {
            add_action('network_admin_menu', [$this, 'register_admin_page']);
        } else {
            add_action('admin_menu', [$this, 'register_admin_page']);
        }

        add_action('admin_post_juntaplay_newsletter_export', [$this, 'export_csv']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('wp_enqueue_scripts', [$this, 'register_frontend_assets']);
    }

    public function activate() {
        $table_name = $this->get_table_name();
        $charset_collate = $this->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(190) NOT NULL,
            email VARCHAR(190) NOT NULL,
            consent TINYINT(1) DEFAULT 0,
            created_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY email (email)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);

        if (is_multisite()) {
            update_site_option(self::OPTION_VERSION, self::VERSION);
        } else {
            update_option(self::OPTION_VERSION, self::VERSION);
        }
    }

    public function load_textdomain() {
        load_plugin_textdomain(
            'juntaplay-newsletter',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages/'
        );
    }

    public function register_frontend_assets() {
        $style_path = plugin_dir_path(__FILE__) . 'assets/frontend.css';
        if (file_exists($style_path)) {
            wp_register_style(
                'juntaplay-newsletter-style',
                plugins_url('assets/frontend.css', __FILE__),
                [],
                filemtime($style_path)
            );
        }

        $script_path = plugin_dir_path(__FILE__) . 'assets/frontend.js';
        if (file_exists($script_path)) {
            wp_register_script(
                'juntaplay-newsletter-script',
                plugins_url('assets/frontend.js', __FILE__),
                [],
                filemtime($script_path),
                true
            );
        }
    }

    public function enqueue_admin_assets($hook_suffix) {
        if ($hook_suffix !== 'toplevel_page_juntaplay-newsletter') {
            return;
        }

        wp_register_style(
            'juntaplay-newsletter-admin-style',
            plugins_url('assets/admin.css', __FILE__),
            [],
            filemtime(plugin_dir_path(__FILE__) . 'assets/admin.css')
        );
        wp_enqueue_style('juntaplay-newsletter-admin-style');
    }

    public function render_form($atts = []) {
        $atts = shortcode_atts([
            'redirect' => ''
        ], $atts, 'juntaplay_newsletter_form');

        static $instance = 0;
        $instance++;

        $redirect = $atts['redirect'] ?: wp_get_referer();
        $message = '';
        static $message_displayed = false;
        $title_id = 'juntaplay-newsletter-title-' . $instance;

        if (!empty($_GET['juntaplay-newsletter'])) {
            $status = sanitize_text_field(wp_unslash($_GET['juntaplay-newsletter']));
            if ($status === 'success') {
                $message = __('Obrigado por se cadastrar!', 'juntaplay-newsletter');
            } elseif ($status === 'duplicate') {
                $message = __('Este e-mail já está cadastrado.', 'juntaplay-newsletter');
            } elseif ($status === 'error') {
                $message = __('Não foi possível concluir seu cadastro. Tente novamente.', 'juntaplay-newsletter');
            }
        }

        if ($message_displayed) {
            $message = '';
        } elseif ($message) {
            $message_displayed = true;
        }

        if (!wp_style_is('juntaplay-newsletter-style', 'enqueued') || !wp_script_is('juntaplay-newsletter-script', 'enqueued')) {
            if (!wp_style_is('juntaplay-newsletter-style', 'registered') || !wp_script_is('juntaplay-newsletter-script', 'registered')) {
                $this->register_frontend_assets();
            }

            if (wp_style_is('juntaplay-newsletter-style', 'registered') && !wp_style_is('juntaplay-newsletter-style', 'enqueued')) {
                wp_enqueue_style('juntaplay-newsletter-style');
            }

            if (wp_script_is('juntaplay-newsletter-script', 'registered') && !wp_script_is('juntaplay-newsletter-script', 'enqueued')) {
                wp_enqueue_script('juntaplay-newsletter-script');
            }
        }

        ob_start();
        ?>
        <div class="juntaplay-newsletter-overlay" tabindex="-1" data-has-message="<?php echo $message ? '1' : '0'; ?>">
            <div class="juntaplay-newsletter-modal" role="dialog" aria-modal="true" aria-labelledby="<?php echo esc_attr($title_id); ?>">
                <button type="button" class="juntaplay-newsletter-close" aria-label="<?php esc_attr_e('Fechar', 'juntaplay-newsletter'); ?>">&times;</button>
                <div class="juntaplay-newsletter-wrapper">
                    <h2 id="<?php echo esc_attr($title_id); ?>" class="juntaplay-newsletter-title"><?php esc_html_e('Assine a Newsletter JuntaPlay', 'juntaplay-newsletter'); ?></h2>
                    <p class="juntaplay-newsletter-description"><?php esc_html_e('Receba novidades e conteúdos exclusivos diretamente no seu e-mail.', 'juntaplay-newsletter'); ?></p>
                    <?php if ($message) : ?>
                        <p class="juntaplay-newsletter-message"><?php echo esc_html($message); ?></p>
                    <?php endif; ?>
                    <form class="juntaplay-newsletter-form" method="post">
                        <?php wp_nonce_field(self::NONCE_ACTION, self::NONCE_NAME); ?>
                        <input type="hidden" name="juntaplay_newsletter" value="1">
                        <?php if ($redirect) : ?>
                            <input type="hidden" name="redirect_to" value="<?php echo esc_url($redirect); ?>">
                        <?php endif; ?>
                        <label>
                            <span><?php esc_html_e('Nome', 'juntaplay-newsletter'); ?></span>
                            <input type="text" name="name" required>
                        </label>
                        <label>
                            <span><?php esc_html_e('E-mail', 'juntaplay-newsletter'); ?></span>
                            <input type="email" name="email" required>
                        </label>
                        <label class="juntaplay-newsletter-consent">
                            <input type="checkbox" name="consent" value="1" required>
                            <span><?php esc_html_e('Concordo em receber novidades da JuntaPlay.', 'juntaplay-newsletter'); ?></span>
                        </label>
                        <button type="submit"><?php esc_html_e('Inscrever-se', 'juntaplay-newsletter'); ?></button>
                    </form>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function handle_submission() {
        if (empty($_POST['juntaplay_newsletter'])) {
            return;
        }

        if (!isset($_POST[self::NONCE_NAME]) || !wp_verify_nonce(wp_unslash($_POST[self::NONCE_NAME]), self::NONCE_ACTION)) {
            $this->redirect_with_status('error');
        }

        $name   = isset($_POST['name']) ? sanitize_text_field(wp_unslash($_POST['name'])) : '';
        $email  = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';
        $consent = isset($_POST['consent']) ? 1 : 0;

        if (!$name || !$email || !is_email($email)) {
            $this->redirect_with_status('error');
        }

        global $wpdb;
        $table_name = $this->get_table_name();

        $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_name WHERE email = %s", $email));
        if ($exists) {
            $this->redirect_with_status('duplicate');
        }

        $inserted = $wpdb->insert(
            $table_name,
            [
                'name' => $name,
                'email' => $email,
                'consent' => $consent,
                'created_at' => current_time('mysql'),
            ],
            [
                '%s',
                '%s',
                '%d',
                '%s',
            ]
        );

        if ($inserted) {
            $this->redirect_with_status('success');
        }

        $this->redirect_with_status('error');
    }

    private function redirect_with_status($status) {
        $redirect = isset($_POST['redirect_to']) ? esc_url_raw(wp_unslash($_POST['redirect_to'])) : wp_get_referer();

        if (!$redirect) {
            $redirect = home_url('/');
        }

        wp_safe_redirect(add_query_arg('juntaplay-newsletter', $status, $redirect));
        exit;
    }

    public function register_admin_page() {
        if (is_multisite() && !is_super_admin()) {
            return;
        }

        add_menu_page(
            __('Newsletter JuntaPlay', 'juntaplay-newsletter'),
            __('Newsletter', 'juntaplay-newsletter'),
            $this->admin_capability,
            'juntaplay-newsletter',
            [$this, 'render_admin_page'],
            'dashicons-email-alt2'
        );
    }

    public function render_admin_page() {
        if (!current_user_can($this->admin_capability) || (is_multisite() && !is_super_admin())) {
            wp_die(__('Você não tem permissão para acessar esta página.', 'juntaplay-newsletter'));
        }

        global $wpdb;
        $table_name = $this->get_table_name();

        $items = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC");

        ?>
        <div class="wrap juntaplay-newsletter-admin">
            <h1><?php esc_html_e('Inscritos na Newsletter JuntaPlay', 'juntaplay-newsletter'); ?></h1>
            <form method="post" action="<?php echo esc_url($this->get_admin_post_url()); ?>">
                <?php wp_nonce_field('juntaplay_newsletter_export', 'juntaplay_newsletter_export_nonce'); ?>
                <input type="hidden" name="action" value="juntaplay_newsletter_export">
                <p>
                    <button type="submit" class="button button-primary">
                        <?php esc_html_e('Exportar CSV', 'juntaplay-newsletter'); ?>
                    </button>
                </p>
            </form>
            <table class="widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Nome', 'juntaplay-newsletter'); ?></th>
                        <th><?php esc_html_e('E-mail', 'juntaplay-newsletter'); ?></th>
                        <th><?php esc_html_e('Consentimento', 'juntaplay-newsletter'); ?></th>
                        <th><?php esc_html_e('Data de Cadastro', 'juntaplay-newsletter'); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($items) : ?>
                    <?php foreach ($items as $item) : ?>
                        <tr>
                            <td><?php echo esc_html($item->name); ?></td>
                            <td><?php echo esc_html($item->email); ?></td>
                            <td><?php echo $item->consent ? esc_html__('Sim', 'juntaplay-newsletter') : esc_html__('Não', 'juntaplay-newsletter'); ?></td>
                            <td><?php echo esc_html(get_date_from_gmt($item->created_at, get_option('date_format') . ' ' . get_option('time_format'))); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="4"><?php esc_html_e('Nenhum inscrito encontrado.', 'juntaplay-newsletter'); ?></td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function export_csv() {
        if (!current_user_can($this->admin_capability) || (is_multisite() && !is_super_admin())) {
            wp_die(__('Você não tem permissão para executar esta ação.', 'juntaplay-newsletter'));
        }

        if (!isset($_POST['juntaplay_newsletter_export_nonce']) || !wp_verify_nonce(wp_unslash($_POST['juntaplay_newsletter_export_nonce']), 'juntaplay_newsletter_export')) {
            wp_die(__('Solicitação inválida.', 'juntaplay-newsletter'));
        }

        global $wpdb;
        $table_name = $this->get_table_name();
        $items = $wpdb->get_results("SELECT name, email, consent, created_at FROM $table_name ORDER BY created_at DESC", ARRAY_A);

        $filename = 'juntaplay-newsletter-' . gmdate('Y-m-d-His') . '.csv';

        nocache_headers();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);

        $output = fopen('php://output', 'w');
        fputcsv($output, [
            __('Nome', 'juntaplay-newsletter'),
            __('E-mail', 'juntaplay-newsletter'),
            __('Consentimento', 'juntaplay-newsletter'),
            __('Data de Cadastro', 'juntaplay-newsletter'),
        ]);

        foreach ($items as $item) {
            fputcsv($output, [
                $item['name'],
                $item['email'],
                $item['consent'] ? __('Sim', 'juntaplay-newsletter') : __('Não', 'juntaplay-newsletter'),
                get_date_from_gmt($item['created_at'], get_option('date_format') . ' ' . get_option('time_format')),
            ]);
        }

        fclose($output);
        exit;
    }

    private function get_table_name() {
        return $this->table_name;
    }

    private function get_charset_collate() {
        global $wpdb;

        return $wpdb->get_charset_collate();
    }

    private function get_admin_post_url() {
        return is_multisite() ? network_admin_url('admin-post.php') : admin_url('admin-post.php');
    }

    private function resolve_table_name() {
        global $wpdb;

        $primary = (is_multisite() ? $wpdb->base_prefix : $wpdb->prefix) . self::TABLE_NAME;

        if (!is_multisite()) {
            return $primary;
        }

        $table_exists = $wpdb->get_var(
            $wpdb->prepare(
                'SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s',
                $wpdb->dbname,
                $primary
            )
        );
        if ($table_exists) {
            return $primary;
        }

        $legacy = $wpdb->prefix . self::TABLE_NAME;
        $legacy_exists = $wpdb->get_var(
            $wpdb->prepare(
                'SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s',
                $wpdb->dbname,
                $legacy
            )
        );

        return $legacy_exists ? $legacy : $primary;
    }
}

new JuntaPlay_Newsletter();
