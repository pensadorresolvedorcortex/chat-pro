<?php
/**
 * Plugin Name: RMA Admin Settings
 * Description: Configurações centralizadas para Equipe RMA (anuidade, API Maps, PIX e notificações).
 * Version: 0.1.0
 * Author: RMA
 */

if (! defined('ABSPATH')) {
    exit;
}

final class RMA_Admin_Settings {
    private const OPTION_GROUP = 'rma_admin_settings_group';

    private const OPTIONS = [
        'rma_annual_due_value',
        'rma_annual_dues_product_id',
        'rma_due_day_month',
        'rma_pix_key',
        'rma_google_maps_api_key',
        'rma_maps_only_adimplente',
        'rma_institutional_email',
        'rma_notifications_api_url',
        'rma_email_sender_mode',
        'rma_email_verification_header_image',
        'rma_email_verification_logo',
        'rma_email_verification_bg_color',
        'rma_email_verification_button_color',
        'rma_email_verification_body',
        'rma_email_verification_footer',
        'rma_email_verification_company',
    ];

    public function __construct() {
        add_action('admin_menu', [$this, 'register_menu']);
        add_action('admin_init', [$this, 'register_settings']);
    }

    public function register_menu(): void {
        add_menu_page(
            'RMA Configurações',
            'RMA Configurações',
            'manage_options',
            'rma-admin-settings',
            [$this, 'render_page'],
            'dashicons-admin-generic',
            58
        );
    }

    public function register_settings(): void {
        foreach (self::OPTIONS as $option) {
            register_setting(self::OPTION_GROUP, $option, [
                'type' => 'string',
                'sanitize_callback' => function ($value) use ($option) {
                    return $this->sanitize_option($option, $value);
                },
                'show_in_rest' => false,
            ]);
        }

        add_settings_section(
            'rma_admin_main',
            'Parâmetros principais da operação RMA',
            static function () {
                echo '<p>Campos personalizáveis para operação do ciclo anual, mapa, financeiro e notificações.</p>';
            },
            'rma-admin-settings'
        );

        $this->add_field('rma_annual_due_value', 'Valor da anuidade (R$)', 'number', 'Ex.: 1200.00');
        $this->add_field('rma_annual_dues_product_id', 'ID do produto Woo da anuidade', 'number', 'Ex.: 123');
        $this->add_field('rma_due_day_month', 'Data de início do ciclo anual (dd-mm)', 'text', 'Ex.: 01-02');
        $this->add_field('rma_pix_key', 'Chave PIX institucional', 'text', 'Ex.: financeiro@rma.org.br');
        $this->add_field('rma_google_maps_api_key', 'Google Maps API Key', 'text', 'Usada pelo tema para renderização do mapa');
        $this->add_field('rma_maps_only_adimplente', 'Diretório mostra apenas adimplentes por padrão', 'checkbox', '');
        $this->add_field('rma_institutional_email', 'E-mail institucional (notificações)', 'email', 'Ex.: secretaria@rma.org.br');
        $this->add_field('rma_notifications_api_url', 'URL da API de notificações (opcional)', 'url', 'Ex.: https://api.seudominio/notify');
        $this->add_field('rma_email_sender_mode', 'Motor de envio de e-mails', 'select', '');

        add_settings_section(
            'rma_admin_emails_verification',
            'Configurações > Emails > Verificação',
            static function () {
                echo '<p>Personalize o template do e-mail de verificação em 2 fatores. Variáveis suportadas: <code>{{nome}}</code>, <code>{{codigo}}</code>, <code>{{data}}</code>, <code>{{empresa}}</code>.</p>';
            },
            'rma-admin-settings'
        );

        $this->add_field('rma_email_verification_header_image', 'Imagem de header (URL)', 'url', 'https://.../header.jpg', 'rma_admin_emails_verification');
        $this->add_field('rma_email_verification_logo', 'Logo (URL)', 'url', 'https://.../logo.png', 'rma_admin_emails_verification');
        $this->add_field('rma_email_verification_bg_color', 'Cor de fundo do e-mail', 'text', '#f8fafb', 'rma_admin_emails_verification');
        $this->add_field('rma_email_verification_button_color', 'Cor do botão', 'text', '#7bad39', 'rma_admin_emails_verification');
        $this->add_field('rma_email_verification_body', 'Texto editável do corpo', 'textarea', 'Olá {{nome}}, seu código é {{codigo}}.', 'rma_admin_emails_verification');
        $this->add_field('rma_email_verification_footer', 'Footer editável', 'textarea', 'Equipe RMA • {{data}}', 'rma_admin_emails_verification');
        $this->add_field('rma_email_verification_company', 'Nome da empresa', 'text', 'RMA', 'rma_admin_emails_verification');

    }

    private function add_field(string $name, string $label, string $type, string $placeholder, string $section = 'rma_admin_main'): void {
        add_settings_field(
            $name,
            $label,
            function () use ($name, $type, $placeholder) {
                $value = (string) get_option($name, '');
                if ($type === 'checkbox') {
                    echo '<input type="hidden" name="' . esc_attr($name) . '" value="0" />';
                    echo '<label><input type="checkbox" name="' . esc_attr($name) . '" value="1" ' . checked($value, '1', false) . ' /> Sim</label>';
                    return;
                }

                if ($type === 'textarea') {
                    echo '<textarea class="large-text" rows="5" name="' . esc_attr($name) . '" placeholder="' . esc_attr($placeholder) . '">' . esc_textarea($value) . '</textarea>';
                    return;
                }

                if ($type === 'select' && $name === 'rma_email_sender_mode') {
                    $options = [
                        'wp_mail' => 'WordPress padrão (wp_mail)',
                        'woo_mail' => 'WooCommerce (layout/template de e-mail)',
                    ];
                    echo '<select name="' . esc_attr($name) . '">';
                    foreach ($options as $option_value => $option_label) {
                        echo '<option value="' . esc_attr($option_value) . '" ' . selected($value ?: 'wp_mail', $option_value, false) . '>' . esc_html($option_label) . '</option>';
                    }
                    echo '</select>';
                    return;
                }

                echo '<input type="' . esc_attr($type) . '" class="regular-text" name="' . esc_attr($name) . '" value="' . esc_attr($value) . '" placeholder="' . esc_attr($placeholder) . '" />';
            },
            'rma-admin-settings',
            $section
        );
    }

    private function sanitize_option(string $option, $value): string {
        if ($option === 'rma_maps_only_adimplente') {
            return rest_sanitize_boolean($value) ? '1' : '0';
        }

        if ($option === 'rma_annual_due_value') {
            $numeric = is_numeric($value) ? (float) $value : 0;
            return number_format(max(0, $numeric), 2, '.', '');
        }

        if ($option === 'rma_annual_dues_product_id') {
            return (string) absint((int) $value);
        }

        if ($option === 'rma_due_day_month') {
            $v = sanitize_text_field((string) $value);
            if (! preg_match('/^(\d{2})-(\d{2})$/', $v, $matches)) {
                return '01-01';
            }

            $day = (int) $matches[1];
            $month = (int) $matches[2];

            return checkdate($month, $day, 2024) ? sprintf('%02d-%02d', $day, $month) : '01-01';
        }

        if ($option === 'rma_institutional_email') {
            return sanitize_email((string) $value);
        }

        if ($option === 'rma_notifications_api_url') {
            return esc_url_raw((string) $value);
        }

        if (in_array($option, ['rma_email_verification_header_image', 'rma_email_verification_logo'], true)) {
            return esc_url_raw((string) $value);
        }

        if (in_array($option, ['rma_email_verification_bg_color', 'rma_email_verification_button_color'], true)) {
            $color = sanitize_hex_color((string) $value);
            return $color ?: '#7bad39';
        }

        if (in_array($option, ['rma_email_verification_body', 'rma_email_verification_footer'], true)) {
            return wp_kses_post((string) $value);
        }

        if ($option === 'rma_email_sender_mode') {
            $mode = sanitize_key((string) $value);
            return in_array($mode, ['wp_mail', 'woo_mail'], true) ? $mode : 'wp_mail';
        }

        return sanitize_text_field((string) $value);
    }

    public function render_page(): void {
        if (! current_user_can('manage_options')) {
            return;
        }
        ?>
        <div class="wrap">
          <h1>RMA Configurações (Equipe RMA)</h1>
          <form method="post" action="options.php">
            <?php
            settings_fields(self::OPTION_GROUP);
            do_settings_sections('rma-admin-settings');
            submit_button('Salvar configurações');
            ?>
          </form>
        </div>
        <?php
    }
}

new RMA_Admin_Settings();


function rma_render_verification_email_template(array $context = []): string {
    $context = wp_parse_args($context, [
        'nome' => 'Associado',
        'codigo' => '000000',
        'data' => wp_date('d/m/Y H:i'),
        'empresa' => (string) get_option('rma_email_verification_company', 'RMA'),
    ]);

    $logo = (string) get_option('rma_email_verification_logo', 'https://www.agenciadigitalsaopaulo.com.br/rma/wp-content/uploads/2021/02/logo-.png');
    $favicon = 'https://www.agenciadigitalsaopaulo.com.br/rma/wp-content/uploads/2021/02/favicon.png';
    $body = (string) get_option('rma_email_verification_body', 'Utilize o código abaixo para confirmar seu acesso à plataforma RMA.');

    $replace = [
        '{{nome}}' => (string) $context['nome'],
        '{{codigo}}' => (string) $context['codigo'],
        '{{data}}' => (string) $context['data'],
        '{{empresa}}' => (string) $context['empresa'],
    ];

    $body = strtr($body, $replace);

    ob_start();
    ?>
    <div style="background:#ffffff;padding:24px 12px;font-family:'Maven Pro','Segoe UI',Arial,sans-serif;">
      <div style="max-width:520px;margin:0 auto;background:rgba(255,255,255,.94);border-radius:20px;overflow:hidden;border:1px solid #e9eef3;box-shadow:0 16px 42px rgba(15,23,42,.10);">
        <div style="background-image:linear-gradient(135deg,#7bad39,#5ddabb);padding:24px 20px;text-align:center;color:#fff;">
          <?php if ($logo) : ?><img src="<?php echo esc_url($logo); ?>" alt="Logo RMA" style="display:block;max-width:180px;width:100%;height:auto;margin:0 auto 14px;" /><?php endif; ?>
          <p style="margin:0;font-size:12px;line-height:1.4;letter-spacing:.12em;text-transform:uppercase;font-weight:700;opacity:.95;color:#fff;text-align:center;">Verificação em 2 fatores</p>
          <h2 style="margin:8px 0 0;font-size:34px;line-height:1.1;font-weight:800;color:#ffffff;text-align:center;">Proteja seu acesso</h2>
        </div>

        <div style="padding:26px 24px 20px;text-align:center;background:rgba(255,255,255,.92);">
          <p style="margin:0 0 16px;color:#334155;line-height:1.7;font-size:16px;"><?php echo wp_kses_post($body); ?></p>

          <div style="display:inline-block;margin:0 auto 16px;padding:16px 24px;border-radius:14px;background:rgba(255,255,255,.96);border:1px solid #dbe7f3;color:#0f172a;font-size:34px;letter-spacing:9px;font-weight:800;box-shadow:0 10px 24px rgba(15,23,42,.08);">
            <?php echo esc_html((string) $context['codigo']); ?>
          </div>

          <p style="margin:0 0 16px;color:#64748b;font-size:13px;line-height:1.5;">Este código expira em poucos minutos. Nunca compartilhe com terceiros.</p>

          <div style="margin:0 0 2px;">
            <a href="#" style="display:inline-block;text-decoration:none;background-image:linear-gradient(135deg,#7bad39,#5ddabb);color:#fff;padding:12px 24px;border-radius:999px;font-weight:700;font-size:14px;letter-spacing:.01em;box-shadow:0 10px 24px rgba(93,218,187,.35);">Confirmar verificação</a>
          </div>
        </div>

        <div style="background-image:linear-gradient(135deg,#7bad39,#5ddabb);padding:14px 24px;text-align:center;">
          <img src="<?php echo esc_url($favicon); ?>" alt="RMA" style="display:block;max-width:20px;width:20px;height:20px;margin:0 auto 6px;" />
          <p style="margin:0;color:#ffffff;font-size:16px;line-height:1.4;font-weight:700;letter-spacing:.02em;">rma.org.br</p>
        </div>
      </div>
    </div>
    <?php
    return (string) ob_get_clean();
}
