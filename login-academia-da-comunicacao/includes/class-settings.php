<?php
/**
 * Settings page using WordPress Settings API.
 *
 * @package ADC\Login\Admin
 */

namespace ADC\Login\Admin;

use function ADC\Login\get_admin_capability;
use function ADC\Login\get_options;
use function ADC\Login\get_option_value;
use function ADC\Login\get_logo_url;
use function ADC\Login\get_allowed_logo_mimes;
use function ADC\Login\is_allowed_logo_attachment;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Settings
 */
class Settings {

    /**
     * Hook initialization.
     */
    public function init() {
        add_action( 'admin_menu', array( $this, 'register_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
    }

    /**
     * Add settings page.
     */
    public function register_menu() {
        $capability = get_admin_capability();
        $parent     = apply_filters( 'adc_login_settings_parent_slug', 'options-general.php' );

        if ( 'options-general.php' === $parent ) {
            add_options_page(
                __( 'Login Academia da Comunicação', 'login-academia-da-comunicacao' ),
                __( 'Login Academia da Comunicação', 'login-academia-da-comunicacao' ),
                $capability,
                'adc-login-hub',
                array( $this, 'render_page' )
            );

            return;
        }

        add_submenu_page(
            $parent,
            __( 'Configurações do Login', 'login-academia-da-comunicacao' ),
            __( 'Configurações', 'login-academia-da-comunicacao' ),
            $capability,
            'adc-login-hub',
            array( $this, 'render_page' )
        );
    }

    /**
     * Register settings sections and fields.
     */
    public function register_settings() {
        register_setting( 'adc_login_options', 'adc_login_options', array( $this, 'sanitize' ) );

        add_settings_section(
            'adc_login_general',
            __( 'Configurações Gerais', 'login-academia-da-comunicacao' ),
            '__return_false',
            'adc-login-hub'
        );

        add_settings_field(
            'onboarding_page',
            __( 'Página de Onboarding', 'login-academia-da-comunicacao' ),
            array( $this, 'field_page_dropdown' ),
            'adc-login-hub',
            'adc_login_general',
            array( 'key' => 'onboarding_page' )
        );

        add_settings_field(
            'onboarding_skip_url',
            __( 'Pular', 'login-academia-da-comunicacao' ),
            array( $this, 'field_text' ),
            'adc-login-hub',
            'adc_login_general',
            array(
                'key'         => 'onboarding_skip_url',
                'type'        => 'url',
                'description' => __( 'Informe a URL utilizada ao clicar no botão “Pular”.', 'login-academia-da-comunicacao' ),
            )
        );

        add_settings_field(
            'onboarding_start_url',
            __( 'Iniciar', 'login-academia-da-comunicacao' ),
            array( $this, 'field_text' ),
            'adc-login-hub',
            'adc_login_general',
            array(
                'key'         => 'onboarding_start_url',
                'type'        => 'url',
                'description' => __( 'Informe a URL aberta quando o usuário toca em “Iniciar”.', 'login-academia-da-comunicacao' ),
            )
        );

        add_settings_field(
            'onboarding_login_url',
            __( 'Entrar', 'login-academia-da-comunicacao' ),
            array( $this, 'field_text' ),
            'adc-login-hub',
            'adc_login_general',
            array(
                'key'         => 'onboarding_login_url',
                'type'        => 'url',
                'description' => __( 'Defina a URL utilizada ao selecionar “Entrar”.', 'login-academia-da-comunicacao' ),
            )
        );

        add_settings_field(
            'onboarding_signup_url',
            __( 'Cadastre-se', 'login-academia-da-comunicacao' ),
            array( $this, 'field_text' ),
            'adc-login-hub',
            'adc_login_general',
            array(
                'key'         => 'onboarding_signup_url',
                'type'        => 'url',
                'description' => __( 'Informe a URL aberta ao clicar em “Cadastre-se”.', 'login-academia-da-comunicacao' ),
            )
        );

        add_settings_field(
            'post_login_redirect',
            __( 'Após criar conta ou fazer login', 'login-academia-da-comunicacao' ),
            array( $this, 'field_text' ),
            'adc-login-hub',
            'adc_login_general',
            array(
                'key'         => 'post_login_redirect',
                'type'        => 'url',
                'description' => __( 'Defina a página para a qual os usuários serão enviados depois de criar a conta ou fazer login.', 'login-academia-da-comunicacao' ),
            )
        );

        add_settings_field(
            'terms_url',
            __( 'URL dos Termos e Privacidade', 'login-academia-da-comunicacao' ),
            array( $this, 'field_text' ),
            'adc-login-hub',
            'adc_login_general',
            array( 'key' => 'terms_url', 'type' => 'url' )
        );

        add_settings_field(
            'force_onboarding',
            __( 'Forçar onboarding para visitantes', 'login-academia-da-comunicacao' ),
            array( $this, 'field_checkbox' ),
            'adc-login-hub',
            'adc_login_general',
            array( 'key' => 'force_onboarding' )
        );

        add_settings_field(
            'enable_2fa',
            __( 'Exigir verificação em duas etapas', 'login-academia-da-comunicacao' ),
            array( $this, 'field_checkbox' ),
            'adc-login-hub',
            'adc_login_general',
            array( 'key' => 'enable_2fa' )
        );

        add_settings_field(
            'recaptcha_site_key',
            __( 'reCAPTCHA Site Key', 'login-academia-da-comunicacao' ),
            array( $this, 'field_text' ),
            'adc-login-hub',
            'adc_login_general',
            array( 'key' => 'recaptcha_site_key' )
        );

        add_settings_field(
            'recaptcha_secret_key',
            __( 'reCAPTCHA Secret Key', 'login-academia-da-comunicacao' ),
            array( $this, 'field_text' ),
            'adc-login-hub',
            'adc_login_general',
            array( 'key' => 'recaptcha_secret_key' )
        );

        add_settings_section(
            'adc_login_branding',
            __( 'Branding', 'login-academia-da-comunicacao' ),
            '__return_false',
            'adc-login-hub'
        );

        add_settings_field(
            'logo_id',
            __( 'Logo', 'login-academia-da-comunicacao' ),
            array( $this, 'field_logo' ),
            'adc-login-hub',
            'adc_login_branding',
            array( 'key' => 'logo_id' )
        );

        $color_fields = array(
            'color_primary' => __( 'Cor primária', 'login-academia-da-comunicacao' ),
            'color_accent'  => __( 'Cor de destaque', 'login-academia-da-comunicacao' ),
            'color_ink'     => __( 'Cor de texto', 'login-academia-da-comunicacao' ),
        );

        foreach ( $color_fields as $key => $label ) {
            add_settings_field(
                $key,
                $label,
                array( $this, 'field_color' ),
                'adc-login-hub',
                'adc_login_branding',
                array( 'key' => $key )
            );
        }

        add_settings_section(
            'adc_login_emails',
            __( 'E-mails', 'login-academia-da-comunicacao' ),
            '__return_false',
            'adc-login-hub'
        );

        add_settings_field(
            'email_from_name',
            __( 'Nome do remetente', 'login-academia-da-comunicacao' ),
            array( $this, 'field_text' ),
            'adc-login-hub',
            'adc_login_emails',
            array( 'key' => 'email_from_name' )
        );

        add_settings_field(
            'email_from_address',
            __( 'E-mail do remetente', 'login-academia-da-comunicacao' ),
            array( $this, 'field_text' ),
            'adc-login-hub',
            'adc_login_emails',
            array( 'key' => 'email_from_address', 'type' => 'email' )
        );

        $subjects = array(
            'subject_account_created'  => __( 'Assunto: Conta criada', 'login-academia-da-comunicacao' ),
            'subject_password_reminder' => __( 'Assunto: Lembrete de senha', 'login-academia-da-comunicacao' ),
            'subject_twofa_code'       => __( 'Assunto: Código 2FA', 'login-academia-da-comunicacao' ),
        );

        foreach ( $subjects as $key => $label ) {
            add_settings_field(
                $key,
                $label,
                array( $this, 'field_text' ),
                'adc-login-hub',
                'adc_login_emails',
                array( 'key' => $key )
            );
        }

        add_settings_field(
            'email_tools',
            __( 'Pré-visualização e testes', 'login-academia-da-comunicacao' ),
            array( $this, 'field_email_tools' ),
            'adc-login-hub',
            'adc_login_emails'
        );

        add_settings_section(
            'adc_login_integrations',
            __( 'Integrações', 'login-academia-da-comunicacao' ),
            '__return_false',
            'adc-login-hub'
        );

        add_settings_field(
            'enable_social_google',
            __( 'Ativar botão Google', 'login-academia-da-comunicacao' ),
            array( $this, 'field_checkbox' ),
            'adc-login-hub',
            'adc_login_integrations',
            array( 'key' => 'enable_social_google' )
        );

        add_settings_field(
            'enable_social_apple',
            __( 'Ativar botão Apple', 'login-academia-da-comunicacao' ),
            array( $this, 'field_checkbox' ),
            'adc-login-hub',
            'adc_login_integrations',
            array( 'key' => 'enable_social_apple' )
        );

        add_settings_field(
            'rest_allowed_origins',
            __( 'Origens permitidas na API', 'login-academia-da-comunicacao' ),
            array( $this, 'field_textarea' ),
            'adc-login-hub',
            'adc_login_integrations',
            array(
                'key'         => 'rest_allowed_origins',
                'description' => __( 'Informe uma origem por linha (ex: https://app.exemplo.com).', 'login-academia-da-comunicacao' ),
            )
        );
    }

    /**
     * Sanitize options before saving.
     *
     * @param array $input Raw submitted values.
     *
     * @return array
     */
    public function sanitize( $input ) {
        $options = get_options();

        $sanitized = array(
            'onboarding_page'         => isset( $input['onboarding_page'] ) ? absint( $input['onboarding_page'] ) : $options['onboarding_page'],
            'post_login_redirect'     => isset( $input['post_login_redirect'] ) ? esc_url_raw( $input['post_login_redirect'] ) : $options['post_login_redirect'],
            'force_onboarding'        => ! empty( $input['force_onboarding'] ) ? 1 : 0,
            'enable_2fa'              => ! empty( $input['enable_2fa'] ) ? 1 : 0,
            'enable_social_google'    => ! empty( $input['enable_social_google'] ) ? 1 : 0,
            'enable_social_apple'     => ! empty( $input['enable_social_apple'] ) ? 1 : 0,
            'recaptcha_site_key'      => isset( $input['recaptcha_site_key'] ) ? sanitize_text_field( $input['recaptcha_site_key'] ) : '',
            'recaptcha_secret_key'    => isset( $input['recaptcha_secret_key'] ) ? sanitize_text_field( $input['recaptcha_secret_key'] ) : '',
            'color_primary'           => isset( $input['color_primary'] ) ? sanitize_hex_color( $input['color_primary'] ) : $options['color_primary'],
            'color_accent'            => isset( $input['color_accent'] ) ? sanitize_hex_color( $input['color_accent'] ) : $options['color_accent'],
            'color_ink'               => isset( $input['color_ink'] ) ? sanitize_hex_color( $input['color_ink'] ) : $options['color_ink'],
            'email_from_name'         => isset( $input['email_from_name'] ) ? sanitize_text_field( $input['email_from_name'] ) : $options['email_from_name'],
            'email_from_address'      => isset( $input['email_from_address'] ) ? sanitize_email( $input['email_from_address'] ) : $options['email_from_address'],
            'subject_account_created' => isset( $input['subject_account_created'] ) ? sanitize_text_field( $input['subject_account_created'] ) : $options['subject_account_created'],
            'subject_password_reminder' => isset( $input['subject_password_reminder'] ) ? sanitize_text_field( $input['subject_password_reminder'] ) : $options['subject_password_reminder'],
            'subject_twofa_code'      => isset( $input['subject_twofa_code'] ) ? sanitize_text_field( $input['subject_twofa_code'] ) : $options['subject_twofa_code'],
            'terms_url'               => isset( $input['terms_url'] ) ? esc_url_raw( $input['terms_url'] ) : $options['terms_url'],
            'onboarding_skip_url'     => isset( $input['onboarding_skip_url'] ) ? esc_url_raw( $input['onboarding_skip_url'] ) : $options['onboarding_skip_url'],
            'onboarding_start_url'    => isset( $input['onboarding_start_url'] ) ? esc_url_raw( $input['onboarding_start_url'] ) : $options['onboarding_start_url'],
            'onboarding_login_url'    => isset( $input['onboarding_login_url'] ) ? esc_url_raw( $input['onboarding_login_url'] ) : $options['onboarding_login_url'],
            'onboarding_signup_url'   => isset( $input['onboarding_signup_url'] ) ? esc_url_raw( $input['onboarding_signup_url'] ) : $options['onboarding_signup_url'],
            'rest_allowed_origins'    => isset( $input['rest_allowed_origins'] ) ? $this->sanitize_origins( $input['rest_allowed_origins'] ) : $options['rest_allowed_origins'],
        );

        $logo_id = isset( $input['logo_id'] ) ? absint( $input['logo_id'] ) : 0;

        if ( $logo_id && ! $this->is_valid_logo( $logo_id ) ) {
            add_settings_error(
                'adc_login_options',
                'adc-login-logo',
                esc_html__( 'O arquivo selecionado para o logo não é uma imagem compatível. Utilize PNG, JPG, SVG ou WebP.', 'login-academia-da-comunicacao' ),
                'error'
            );

            $logo_id = 0;
        }

        $sanitized['logo_id'] = $logo_id;

        return $sanitized;
    }

    /**
     * Validate the uploaded logo attachment.
     *
     * @param int $attachment_id Attachment ID selected by the user.
     *
     * @return bool
     */
    protected function is_valid_logo( $attachment_id ) {
        return is_allowed_logo_attachment( $attachment_id );
    }

    /**
     * Render settings page.
     */
    public function render_page() {
        $capability = get_admin_capability();

        if ( ! current_user_can( $capability ) ) {
            return;
        }
        ?>
        <div class="wrap adc-login-hub">
            <h1><?php esc_html_e( 'Login Academia da Comunicação', 'login-academia-da-comunicacao' ); ?></h1>
            <?php
            settings_errors( 'adc_login_options' );
            settings_errors( 'adc_login_emails' );
            ?>
            <form method="post" action="options.php">
                <?php
                settings_fields( 'adc_login_options' );
                do_settings_sections( 'adc-login-hub' );
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Render text input field.
     *
     * @param array $args Field args.
     */
    public function field_text( $args ) {
        $key         = $args['key'];
        $type        = isset( $args['type'] ) ? $args['type'] : 'text';
        $description = isset( $args['description'] ) ? $args['description'] : '';
        $value       = get_option_value( $key );
        ?>
        <input type="<?php echo esc_attr( $type ); ?>" name="adc_login_options[<?php echo esc_attr( $key ); ?>]" id="adc-login-<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $value ); ?>" class="regular-text" />
        <?php if ( $description ) : ?>
            <p class="description"><?php echo esc_html( $description ); ?></p>
        <?php endif; ?>
        <?php
    }

    /**
     * Render checkbox field.
     *
     * @param array $args Field args.
     */
    public function field_checkbox( $args ) {
        $key   = $args['key'];
        $value = get_option_value( $key );
        ?>
        <label for="adc-login-<?php echo esc_attr( $key ); ?>">
            <input type="checkbox" name="adc_login_options[<?php echo esc_attr( $key ); ?>]" id="adc-login-<?php echo esc_attr( $key ); ?>" value="1" <?php checked( $value, 1 ); ?> />
            <?php esc_html_e( 'Ativar', 'login-academia-da-comunicacao' ); ?>
        </label>
        <?php
    }

    /**
     * Render textarea field.
     *
     * @param array $args Field args.
     */
    public function field_textarea( $args ) {
        $key         = $args['key'];
        $description = isset( $args['description'] ) ? $args['description'] : '';
        $value       = get_option_value( $key );
        ?>
        <textarea name="adc_login_options[<?php echo esc_attr( $key ); ?>]" id="adc-login-<?php echo esc_attr( $key ); ?>" rows="4" class="large-text code"><?php echo esc_textarea( $value ); ?></textarea>
        <?php if ( $description ) : ?>
            <p class="description"><?php echo esc_html( $description ); ?></p>
        <?php endif; ?>
        <?php
    }

    /**
     * Render dropdown of pages.
     *
     * @param array $args Field args.
     */
    public function field_page_dropdown( $args ) {
        $key   = $args['key'];
        $value = absint( get_option_value( $key ) );
        wp_dropdown_pages(
            array(
                'name'             => 'adc_login_options[' . esc_attr( $key ) . ']',
                'id'               => 'adc-login-' . esc_attr( $key ),
                'selected'         => $value,
                'show_option_none' => __( 'Selecionar página', 'login-academia-da-comunicacao' ),
            )
        );
    }

    /**
     * Render color picker field.
     *
     * @param array $args Field args.
     */
    public function field_color( $args ) {
        $key   = $args['key'];
        $value = get_option_value( $key );
        ?>
        <input type="text" class="adc-color-field" name="adc_login_options[<?php echo esc_attr( $key ); ?>]" id="adc-login-<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $value ); ?>" data-default-color="<?php echo esc_attr( $value ); ?>" />
        <script>
            ( function( $ ) {
                $( function() {
                    $( '.adc-color-field' ).wpColorPicker();
                } );
            } )( jQuery );
        </script>
        <?php
    }

    /**
     * Render media uploader field for logo.
     *
     * @param array $args Field args.
     */
    public function field_logo( $args ) {
        $key      = $args['key'];
        $value    = absint( get_option_value( $key ) );
        $image    = $value ? wp_get_attachment_image( $value, 'medium' ) : '<img src="' . esc_url( get_logo_url() ) . '" alt="" style="max-width:150px;height:auto;" />';
        $allowed  = array_values( get_allowed_logo_mimes() );
        $allowed  = array_map( 'sanitize_text_field', $allowed );
        $js_array = wp_json_encode( $allowed );
        ?>
        <div class="adc-logo-picker">
            <div class="adc-logo-preview"><?php echo wp_kses_post( $image ); ?></div>
            <input type="hidden" name="adc_login_options[<?php echo esc_attr( $key ); ?>]" id="adc-login-<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $value ); ?>" />
            <button type="button" class="button adc-upload-logo"><?php esc_html_e( 'Escolher logo', 'login-academia-da-comunicacao' ); ?></button>
            <button type="button" class="button adc-remove-logo"><?php esc_html_e( 'Remover', 'login-academia-da-comunicacao' ); ?></button>
        </div>
        <p class="description">
            <?php esc_html_e( 'Formatos compatíveis: PNG, JPG, GIF, SVG ou WebP.', 'login-academia-da-comunicacao' ); ?>
        </p>
        <script>
            ( function( $ ) {
                $( function() {
                    var frame;
                    $( '.adc-upload-logo' ).on( 'click', function( e ) {
                        e.preventDefault();
                        if ( frame ) {
                            frame.open();
                            return;
                        }
                        frame = wp.media({
                            title: '<?php echo esc_js( __( 'Selecionar logo', 'login-academia-da-comunicacao' ) ); ?>',
                            button: {
                                text: '<?php echo esc_js( __( 'Usar este logo', 'login-academia-da-comunicacao' ) ); ?>'
                            },
                            multiple: false
                        });
                        frame.on( 'select', function() {
                            var attachment = frame.state().get( 'selection' ).first().toJSON();
                            var allowedMimes = <?php echo $js_array ? $js_array : '[]'; ?>;
                            if ( attachment.mime && allowedMimes.indexOf( attachment.mime ) === -1 ) {
                                window.alert( '<?php echo esc_js( __( 'O arquivo escolhido não é uma imagem compatível. Utilize PNG, JPG, GIF, SVG ou WebP.', 'login-academia-da-comunicacao' ) ); ?>' );
                                return;
                            }
                            if ( attachment.type && attachment.type !== 'image' && allowedMimes.indexOf( attachment.mime ) === -1 ) {
                                window.alert( '<?php echo esc_js( __( 'O arquivo escolhido não é uma imagem compatível. Utilize PNG, JPG, GIF, SVG ou WebP.', 'login-academia-da-comunicacao' ) ); ?>' );
                                return;
                            }
                            $( '#adc-login-<?php echo esc_js( $key ); ?>' ).val( attachment.id );
                            $( '.adc-logo-preview' ).html( '<img src="' + attachment.url + '" alt="" style="max-width:150px;height:auto;" />' );
                        });
                        frame.open();
                    });
                    $( '.adc-remove-logo' ).on( 'click', function( e ) {
                        e.preventDefault();
                        $( '#adc-login-<?php echo esc_js( $key ); ?>' ).val( '' );
                        $( '.adc-logo-preview' ).html( '<img src="' + '<?php echo esc_js( get_logo_url() ); ?>' + '" alt="" style="max-width:150px;height:auto;" />' );
                    });
                });
            } )( jQuery );
        </script>
        <?php
    }

    /**
     * Render helpers for previewing and testing email templates.
     */
    public function field_email_tools() {
        $types = array(
            'account-created'     => __( 'Conta criada', 'login-academia-da-comunicacao' ),
            'password-reminder'   => __( 'Lembrete de senha', 'login-academia-da-comunicacao' ),
            'twofa-code'          => __( 'Código 2FA', 'login-academia-da-comunicacao' ),
        );

        echo '<p>' . esc_html__( 'Use os botões abaixo para visualizar o HTML dos modelos ou enviar um e-mail de teste para o seu usuário.', 'login-academia-da-comunicacao' ) . '</p>';

        foreach ( $types as $type => $label ) {
            $preview_url = wp_nonce_url(
                add_query_arg(
                    array(
                        'action' => 'adc_login_email_preview',
                        'type'   => $type,
                    ),
                    admin_url( 'admin-post.php' )
                ),
                'adc_login_email_preview_' . $type
            );

            $test_url = wp_nonce_url(
                add_query_arg(
                    array(
                        'action' => 'adc_login_email_test',
                        'type'   => $type,
                    ),
                    admin_url( 'admin-post.php' )
                ),
                'adc_login_email_test_' . $type
            );

            ?>
            <p class="adc-email-tools">
                <strong><?php echo esc_html( $label ); ?>:</strong>
                <a class="button button-secondary" href="<?php echo esc_url( $preview_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Pré-visualizar', 'login-academia-da-comunicacao' ); ?></a>
                <a class="button" href="<?php echo esc_url( $test_url ); ?>"><?php esc_html_e( 'Enviar e-mail de teste', 'login-academia-da-comunicacao' ); ?></a>
            </p>
            <?php
        }
    }

    /**
     * Sanitize newline separated origins for REST access control.
     *
     * @param string $value Raw textarea value.
     *
     * @return string
     */
    protected function sanitize_origins( $value ) {
        $lines   = preg_split( '/[\r\n]+/', (string) $value );
        $origins = array();

        foreach ( $lines as $line ) {
            $line = trim( $line );

            if ( empty( $line ) ) {
                continue;
            }

            $origin = esc_url_raw( $line );

            if ( empty( $origin ) ) {
                continue;
            }

            $origins[] = $origin;
        }

        $origins = array_filter( array_unique( $origins ) );

        return implode( "\n", $origins );
    }
}
