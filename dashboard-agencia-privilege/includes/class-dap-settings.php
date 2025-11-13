<?php
/**
 * Plugin settings page.
 *
 * @package DashboardAgenciaPrivilege
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DAP_Settings {
    /**
     * Constructor.
     */
    public function __construct() {
        add_action( 'admin_menu', [ $this, 'register_settings_page' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
    }

    /**
     * Adds the settings page under the Settings menu.
     */
    public function register_settings_page() {
        add_options_page(
            __( 'Agência Privilege', 'dap' ),
            __( 'Agência Privilege', 'dap' ),
            'manage_options',
            'dap-settings',
            [ $this, 'render_settings_page' ]
        );
    }

    /**
     * Registers plugin settings, sections and fields.
     */
    public function register_settings() {
        register_setting( 'dap_settings_group', 'dap_settings', [ $this, 'sanitize_settings' ] );

        add_settings_section(
            'dap_settings_section',
            __( 'Dashboard Options', 'dap' ),
            function() {
                echo '<p>' . esc_html__( 'Configure how the dashboard behaves.', 'dap' ) . '</p>';
            },
            'dap-settings'
        );

        add_settings_field(
            'dap_hero_button_slug',
            __( 'Slug do botão Hero', 'dap' ),
            [ $this, 'render_hero_button_field' ],
            'dap-settings',
            'dap_settings_section'
        );

        add_settings_field(
            'dap_enable_global_skin',
            __( 'Habilitar skin global', 'dap' ),
            [ $this, 'render_global_skin_field' ],
            'dap-settings',
            'dap_settings_section'
        );
    }

    /**
     * Sanitizes plugin settings.
     *
     * @param array $input Raw input.
     *
     * @return array
     */
    public function sanitize_settings( $input ) {
        $defaults = dap_get_default_settings();
        $output   = $defaults;

        if ( isset( $input['hero_button_slug'] ) ) {
            $output['hero_button_slug'] = sanitize_text_field( $input['hero_button_slug'] );
        }

        $output['enable_global_skin'] = isset( $input['enable_global_skin'] ) ? 1 : 0;

        return $output;
    }

    /**
     * Renders the hero button slug field.
     */
    public function render_hero_button_field() {
        $settings = dap_get_settings();
        $value    = isset( $settings['hero_button_slug'] ) ? $settings['hero_button_slug'] : '';
        ?>
        <input type="text" class="regular-text" name="dap_settings[hero_button_slug]" value="<?php echo esc_attr( $value ); ?>" />
        <p class="description"><?php echo esc_html__( 'Destino do botão principal do painel. Use caminhos relativos ao admin (ex.: admin.php?page=juntaplay-groups).', 'dap' ); ?></p>
        <?php
    }

    /**
     * Renders the global skin checkbox field.
     */
    public function render_global_skin_field() {
        $settings = dap_get_settings();
        $checked  = ! empty( $settings['enable_global_skin'] );
        ?>
        <label for="dap-enable-global-skin">
            <input type="checkbox" id="dap-enable-global-skin" name="dap_settings[enable_global_skin]" value="1" <?php checked( $checked ); ?> />
            <?php echo esc_html__( 'Aplicar ajustes visuais leves em todas as páginas do admin.', 'dap' ); ?>
        </label>
        <?php
    }

    /**
     * Outputs the settings page markup.
     */
    public function render_settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Dashboard Agência Privilege', 'dap' ); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields( 'dap_settings_group' );
                do_settings_sections( 'dap-settings' );
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
}
