<?php
declare(strict_types=1);

namespace JuntaPlay\Admin;

defined('ABSPATH') || exit;

class Settings
{
    public const OPTION_GENERAL         = 'juntaplay_general';
    public const OPTION_SMTP            = 'juntaplay_smtp';
    public const OPTION_RESERVE         = 'juntaplay_reservations';
    public const OPTION_CSS             = 'juntaplay_custom_css';
    public const OPTION_SOCIAL          = 'juntaplay_social';
    public const OPTION_PROCESSING_FEE  = 'juntaplay_processing_fee';

    private const DEFAULT_PROCESSING_FEE = 0.68;

    public function init(): void
    {
        add_action('admin_init', [$this, 'register_settings']);
        add_action('juntaplay/admin/settings_page', [$this, 'render']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);

        add_action('phpmailer_init', [$this, 'configure_phpmailer']);
        add_action('wp_head', [$this, 'output_custom_css']);
    }

    public function register_settings(): void
    {
        register_setting('juntaplay_settings', self::OPTION_GENERAL, ['sanitize_callback' => [$this, 'sanitize_general']]);
        register_setting('juntaplay_settings', self::OPTION_SMTP, ['sanitize_callback' => [$this, 'sanitize_smtp']]);
        register_setting('juntaplay_settings', self::OPTION_RESERVE, ['sanitize_callback' => [$this, 'sanitize_reservations']]);
        register_setting('juntaplay_settings', self::OPTION_CSS, ['sanitize_callback' => 'wp_kses_post']);
        register_setting('juntaplay_settings', self::OPTION_SOCIAL, ['sanitize_callback' => [$this, 'sanitize_social']]);
        register_setting('juntaplay_settings', self::OPTION_PROCESSING_FEE, ['sanitize_callback' => [$this, 'sanitize_processing_fee']]);
    }

    public function enqueue_assets(string $hook): void
    {
        if ($hook !== 'juntaplay_page_juntaplay-settings') {
            return;
        }

        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
    }

    public function render(): void
    {
        $general   = get_option(self::OPTION_GENERAL, []);
        $smtp      = get_option(self::OPTION_SMTP, []);
        $reserve   = get_option(self::OPTION_RESERVE, ['minutes' => 15]);
        $css       = get_option(self::OPTION_CSS, '');
        $social    = get_option(self::OPTION_SOCIAL, []);
        $processing_fee = get_option(self::OPTION_PROCESSING_FEE, self::DEFAULT_PROCESSING_FEE);
        if (!is_numeric($processing_fee)) {
            $processing_fee = self::DEFAULT_PROCESSING_FEE;
        }
        $google  = isset($social['google']) && is_array($social['google']) ? $social['google'] : [];
        $facebook = isset($social['facebook']) && is_array($social['facebook']) ? $social['facebook'] : [];
        $google_callback   = add_query_arg(['juntaplay_social' => 'google', 'callback' => '1'], home_url('/'));
        $facebook_callback = add_query_arg(['juntaplay_social' => 'facebook', 'callback' => '1'], home_url('/'));
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Configurações do JuntaPlay', 'juntaplay'); ?></h1>
            <form method="post" action="options.php">
                <?php settings_fields('juntaplay_settings'); ?>
                <h2 class="title"><?php esc_html_e('Gerais', 'juntaplay'); ?></h2>
                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row"><?php esc_html_e('Cor principal (hex)', 'juntaplay'); ?></th>
                            <td>
                                <input type="text" name="<?php echo esc_attr(self::OPTION_GENERAL); ?>[primary_color]" value="<?php echo esc_attr($general['primary_color'] ?? '#ff5a5f'); ?>" class="regular-text" />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e('Cards na vitrine de grupos', 'juntaplay'); ?></th>
                            <td>
                                <?php $rotator_limit = isset($general['group_rotator_limit']) ? (int) $general['group_rotator_limit'] : 12; ?>
                                <input type="number" min="4" max="40" step="1" name="<?php echo esc_attr(self::OPTION_GENERAL); ?>[group_rotator_limit]" value="<?php echo esc_attr((string) $rotator_limit); ?>" />
                                <p class="description"><?php esc_html_e('Quantidade padrão de grupos exibidos no carrossel rotativo (entre 4 e 40).', 'juntaplay'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e('Custos de processamento (R$)', 'juntaplay'); ?></th>
                            <td>
                                <input type="number" min="0" step="0.01" name="<?php echo esc_attr(self::OPTION_PROCESSING_FEE); ?>" value="<?php echo esc_attr(number_format((float) $processing_fee, 2, '.', '')); ?>" />
                                <p class="description"><?php esc_html_e('Taxa fixa aplicada no checkout, em e-mails e resumos de pedidos.', 'juntaplay'); ?></p>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <h2 class="title"><?php esc_html_e('Reservas', 'juntaplay'); ?></h2>
                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row"><?php esc_html_e('Tempo de bloqueio (minutos)', 'juntaplay'); ?></th>
                            <td>
                                <input type="number" min="1" step="1" name="<?php echo esc_attr(self::OPTION_RESERVE); ?>[minutes]" value="<?php echo esc_attr((string) ($reserve['minutes'] ?? 15)); ?>" />
                                <p class="description"><?php esc_html_e('Após esse tempo, cotas reservadas e não pagas serão liberadas automaticamente.', 'juntaplay'); ?></p>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <h2 class="title"><?php esc_html_e('SMTP', 'juntaplay'); ?></h2>
                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row"><?php esc_html_e('Ativar SMTP', 'juntaplay'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="<?php echo esc_attr(self::OPTION_SMTP); ?>[enabled]" value="1" <?php checked(!empty($smtp['enabled'])); ?> />
                                    <?php esc_html_e('Forçar envio via SMTP configurado abaixo.', 'juntaplay'); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e('Host', 'juntaplay'); ?></th>
                            <td><input type="text" name="<?php echo esc_attr(self::OPTION_SMTP); ?>[host]" value="<?php echo esc_attr($smtp['host'] ?? ''); ?>" class="regular-text" /></td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e('Porta', 'juntaplay'); ?></th>
                            <td><input type="number" name="<?php echo esc_attr(self::OPTION_SMTP); ?>[port]" value="<?php echo esc_attr($smtp['port'] ?? 587); ?>" /></td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e('Criptografia', 'juntaplay'); ?></th>
                            <td>
                                <select name="<?php echo esc_attr(self::OPTION_SMTP); ?>[secure]">
                                    <option value="tls" <?php selected(($smtp['secure'] ?? 'tls'), 'tls'); ?>>TLS</option>
                                    <option value="ssl" <?php selected(($smtp['secure'] ?? '') === 'ssl'); ?>>SSL</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e('Usuário', 'juntaplay'); ?></th>
                            <td><input type="text" name="<?php echo esc_attr(self::OPTION_SMTP); ?>[user]" value="<?php echo esc_attr($smtp['user'] ?? ''); ?>" class="regular-text" /></td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e('Senha', 'juntaplay'); ?></th>
                            <td><input type="password" name="<?php echo esc_attr(self::OPTION_SMTP); ?>[pass]" value="<?php echo esc_attr($smtp['pass'] ?? ''); ?>" class="regular-text" autocomplete="new-password" /></td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e('Remetente (nome)', 'juntaplay'); ?></th>
                            <td><input type="text" name="<?php echo esc_attr(self::OPTION_SMTP); ?>[from_name]" value="<?php echo esc_attr($smtp['from_name'] ?? get_bloginfo('name')); ?>" class="regular-text" /></td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e('Remetente (e-mail)', 'juntaplay'); ?></th>
                            <td><input type="email" name="<?php echo esc_attr(self::OPTION_SMTP); ?>[from_email]" value="<?php echo esc_attr($smtp['from_email'] ?? get_option('admin_email')); ?>" class="regular-text" /></td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e('Exigir autenticação', 'juntaplay'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="<?php echo esc_attr(self::OPTION_SMTP); ?>[auth]" value="1" <?php checked(!empty($smtp['auth'])); ?> />
                                    <?php esc_html_e('Habilitar autenticação SMTP (recomendado)', 'juntaplay'); ?>
                                </label>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <h2 class="title"><?php esc_html_e('Login social', 'juntaplay'); ?></h2>
                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row"><?php esc_html_e('Google', 'juntaplay'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="<?php echo esc_attr(self::OPTION_SOCIAL); ?>[google][enabled]" value="1" <?php checked(!empty($google['enabled'])); ?> />
                                    <?php esc_html_e('Permitir login com Google', 'juntaplay'); ?>
                                </label>
                                <p><label for="jp-google-client" class="screen-reader-text"><?php esc_html_e('Client ID do Google', 'juntaplay'); ?></label>
                                    <input id="jp-google-client" type="text" name="<?php echo esc_attr(self::OPTION_SOCIAL); ?>[google][client_id]" value="<?php echo esc_attr((string) ($google['client_id'] ?? '')); ?>" class="regular-text" placeholder="client-id.apps.googleusercontent.com" /></p>
                                <p><label for="jp-google-secret" class="screen-reader-text"><?php esc_html_e('Client secret do Google', 'juntaplay'); ?></label>
                                    <input id="jp-google-secret" type="text" name="<?php echo esc_attr(self::OPTION_SOCIAL); ?>[google][client_secret]" value="<?php echo esc_attr((string) ($google['client_secret'] ?? '')); ?>" class="regular-text" placeholder="••••••" /></p>
                                <p class="description"><?php printf(esc_html__('URL de retorno: %s', 'juntaplay'), '<code>' . esc_html($google_callback) . '</code>'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e('Facebook', 'juntaplay'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="<?php echo esc_attr(self::OPTION_SOCIAL); ?>[facebook][enabled]" value="1" <?php checked(!empty($facebook['enabled'])); ?> />
                                    <?php esc_html_e('Permitir login com Facebook', 'juntaplay'); ?>
                                </label>
                                <p><label for="jp-facebook-client" class="screen-reader-text"><?php esc_html_e('App ID do Facebook', 'juntaplay'); ?></label>
                                    <input id="jp-facebook-client" type="text" name="<?php echo esc_attr(self::OPTION_SOCIAL); ?>[facebook][client_id]" value="<?php echo esc_attr((string) ($facebook['client_id'] ?? '')); ?>" class="regular-text" placeholder="1234567890" /></p>
                                <p><label for="jp-facebook-secret" class="screen-reader-text"><?php esc_html_e('App secret do Facebook', 'juntaplay'); ?></label>
                                    <input id="jp-facebook-secret" type="text" name="<?php echo esc_attr(self::OPTION_SOCIAL); ?>[facebook][client_secret]" value="<?php echo esc_attr((string) ($facebook['client_secret'] ?? '')); ?>" class="regular-text" placeholder="••••••" /></p>
                                <p class="description"><?php printf(esc_html__('URL de retorno: %s', 'juntaplay'), '<code>' . esc_html($facebook_callback) . '</code>'); ?></p>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <h2 class="title"><?php esc_html_e('CSS Personalizado', 'juntaplay'); ?></h2>
                <p><?php esc_html_e('Cole abaixo trechos adicionais de CSS que serão carregados após o tema.', 'juntaplay'); ?></p>
                <textarea name="<?php echo esc_attr(self::OPTION_CSS); ?>" rows="10" cols="120" class="large-text code"><?php echo esc_textarea($css); ?></textarea>

                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function sanitize_general(array $input): array
    {
        $color = sanitize_hex_color($input['primary_color'] ?? '#ff5a5f') ?: '#ff5a5f';
        $limit = isset($input['group_rotator_limit']) ? absint($input['group_rotator_limit']) : 12;

        if ($limit < 4) {
            $limit = 4;
        } elseif ($limit > 40) {
            $limit = 40;
        }

        return [
            'primary_color'       => $color,
            'group_rotator_limit' => $limit,
        ];
    }

    public function sanitize_smtp(array $input): array
    {
        return [
            'enabled'    => !empty($input['enabled']) ? 1 : 0,
            'host'       => sanitize_text_field($input['host'] ?? ''),
            'port'       => absint($input['port'] ?? 587),
            'secure'     => in_array($input['secure'] ?? 'tls', ['tls', 'ssl'], true) ? $input['secure'] : 'tls',
            'user'       => sanitize_text_field($input['user'] ?? ''),
            'pass'       => sanitize_text_field($input['pass'] ?? ''),
            'from_name'  => sanitize_text_field($input['from_name'] ?? ''),
            'from_email' => sanitize_email($input['from_email'] ?? ''),
            'auth'       => !empty($input['auth']) ? 1 : 0,
        ];
    }

    public function sanitize_reservations(array $input): array
    {
        $minutes = max(1, (int) ($input['minutes'] ?? 15));

        return [
            'minutes' => $minutes,
        ];
    }

    public function sanitize_social(array $input): array
    {
        $providers = ['google', 'facebook'];
        $output    = [];

        foreach ($providers as $provider) {
            $raw = isset($input[$provider]) && is_array($input[$provider]) ? $input[$provider] : [];
            $output[$provider] = [
                'enabled'       => !empty($raw['enabled']) ? 1 : 0,
                'client_id'     => sanitize_text_field($raw['client_id'] ?? ''),
                'client_secret' => sanitize_text_field($raw['client_secret'] ?? ''),
            ];
        }

        return $output;
    }

    public function sanitize_processing_fee($input): float
    {
        $value = is_scalar($input) ? (float) $input : 0.0;

        if ($value < 0) {
            $value = 0.0;
        }

        return (float) number_format($value, 2, '.', '');
    }

    public static function get_processing_fee(): float
    {
        $fee = get_option(self::OPTION_PROCESSING_FEE, self::DEFAULT_PROCESSING_FEE);

        if (!is_numeric($fee)) {
            return self::DEFAULT_PROCESSING_FEE;
        }

        $fee = (float) $fee;

        if ($fee < 0) {
            $fee = 0.0;
        }

        return (float) number_format($fee, 2, '.', '');
    }

    public function configure_phpmailer(\PHPMailer\PHPMailer\PHPMailer $phpmailer): void
    {
        $smtp = get_option(self::OPTION_SMTP, []);

        if (empty($smtp['enabled'])) {
            return;
        }

        $phpmailer->isSMTP();
        $phpmailer->Host       = $smtp['host'] ?? '';
        $phpmailer->Port       = (int) ($smtp['port'] ?? 587);
        $phpmailer->SMTPAuth   = !empty($smtp['auth']);
        $phpmailer->SMTPSecure = $smtp['secure'] ?? 'tls';
        $phpmailer->Username   = $smtp['user'] ?? '';
        $phpmailer->Password   = $smtp['pass'] ?? '';
        $phpmailer->setFrom($smtp['from_email'] ?? get_option('admin_email'), $smtp['from_name'] ?? 'JuntaPlay');
    }

    public function output_custom_css(): void
    {
        $css = get_option(self::OPTION_CSS, '');

        if (empty($css)) {
            return;
        }

        echo '<style id="juntaplay-custom-css">' . wp_strip_all_tags($css) . '</style>';
    }
}
