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
    }

    private function add_field(string $name, string $label, string $type, string $placeholder): void {
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
            'rma_admin_main'
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
