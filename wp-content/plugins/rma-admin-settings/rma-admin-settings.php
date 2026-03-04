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

    $header = (string) get_option('rma_email_verification_header_image', '');
    $logo = (string) get_option('rma_email_verification_logo', 'https://www.agenciadigitalsaopaulo.com.br/rma/wp-content/uploads/2021/02/logo-1.png');
    $bg = (string) get_option('rma_email_verification_bg_color', '#f6f8fb');
    $button = (string) get_option('rma_email_verification_button_color', '#7bad39');
    $body = (string) get_option('rma_email_verification_body', 'Utilize o código abaixo para confirmar seu acesso à plataforma RMA.');
    $footer = (string) get_option('rma_email_verification_footer', 'Se você não solicitou esta verificação, ignore este email.');

    $replace = [
        '{{nome}}' => (string) $context['nome'],
        '{{codigo}}' => (string) $context['codigo'],
        '{{data}}' => (string) $context['data'],
        '{{empresa}}' => (string) $context['empresa'],
    ];

    $body = strtr($body, $replace);
    $footer = strtr($footer, $replace);

    ob_start();
    ?>
    <div style="background:<?php echo esc_attr($bg); ?>;padding:28px 14px;font-family:Inter,Arial,sans-serif;">
      <div style="max-width:520px;margin:0 auto;background:#fff;border-radius:16px;padding:28px 24px;border:1px solid #e9eef3;box-shadow:0 10px 28px rgba(0,0,0,.06);text-align:center;">
        <?php if ($logo) : ?><img src="<?php echo esc_url($logo); ?>" alt="Logo RMA" style="display:block;max-width:180px;width:100%;height:auto;margin:0 auto 24px;" /><?php endif; ?>
        <?php if ($header) : ?><img src="<?php echo esc_url($header); ?>" alt="Header" style="width:100%;border-radius:10px;margin:0 auto 16px;" /><?php endif; ?>

        <h2 style="margin:0 0 10px;color:#1f2937;font-size:28px;line-height:1.2;font-weight:700;">Verificação de segurança</h2>
        <p style="margin:0 0 18px;color:#4b5563;line-height:1.6;font-size:15px;"><?php echo wp_kses_post($body); ?></p>

        <div style="margin:0 auto 18px;display:inline-block;padding:14px 20px;border-radius:12px;background:#f3f6fa;border:1px solid #e3e9f0;color:#1f2937;font-size:32px;letter-spacing:8px;font-weight:700;">
          <?php echo esc_html((string) $context['codigo']); ?>
        </div>

        <div style="margin:2px 0 12px;">
          <a href="#" style="display:inline-block;text-decoration:none;background:linear-gradient(135deg, #7bad39, #5ddabb);color:#fff;padding:12px 18px;border-radius:12px;font-weight:600;">Confirmar verificação</a>
        </div>

        <p style="color:#4b5563;font-size:13px;line-height:1.5;margin:16px 0 4px;"><?php echo wp_kses_post($footer); ?></p>
      </div>
    </div>
    <?php
    return (string) ob_get_clean();
}
