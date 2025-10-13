<?php
/**
 * Admin dashboard to manage plugin copy and imagery without touching code.
 *
 * @package ADC\Login\Admin
 */

namespace ADC\Login\Admin;

use ADC\Login\Content\Manager as Content_Manager;
use function ADC\Login\get_asset_url;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Render a friendly dashboard for editing onboarding and authentication content.
 */
class Content_Dashboard {

    /**
     * Content manager instance.
     *
     * @var Content_Manager
     */
    protected $content_manager;

    /**
     * Constructor.
     *
     * @param Content_Manager $content_manager Content manager.
     */
    public function __construct( Content_Manager $content_manager ) {
        $this->content_manager = $content_manager;
    }

    /**
     * Wire admin hooks.
     */
    public function init() {
        add_filter( 'adc_login_settings_parent_slug', array( $this, 'filter_settings_parent_slug' ) );
        add_action( 'admin_menu', array( $this, 'register_menu' ) );
        add_action( 'admin_post_adc_login_save_content', array( $this, 'handle_save' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
    }

    /**
     * Ensure the Settings screen lives under the plugin hub.
     *
     * @param string $parent Existing parent slug.
     *
     * @return string
     */
    public function filter_settings_parent_slug( $parent ) {
        return 'adc-login-dashboard';
    }

    /**
     * Register dashboard menu entry.
     */
    public function register_menu() {
        add_menu_page(
            __( 'Login Academia da Comunicação', 'login-academia-da-comunicacao' ),
            __( 'Login ADC', 'login-academia-da-comunicacao' ),
            'manage_options',
            'adc-login-dashboard',
            array( $this, 'render_page' ),
            'dashicons-admin-customizer'
        );

        add_submenu_page(
            'adc-login-dashboard',
            __( 'Dashboard de Conteúdo', 'login-academia-da-comunicacao' ),
            __( 'Dashboard', 'login-academia-da-comunicacao' ),
            'manage_options',
            'adc-login-dashboard',
            array( $this, 'render_page' )
        );

        remove_submenu_page( 'adc-login-dashboard', 'adc-login-dashboard' );
    }

    /**
     * Handle form submissions from the dashboard.
     */
    public function handle_save() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Você não tem permissão para executar esta ação.', 'login-academia-da-comunicacao' ) );
        }

        check_admin_referer( 'adc_login_save_content', 'adc_login_content_nonce' );

        $payload = isset( $_POST['adc_content'] ) ? wp_unslash( $_POST['adc_content'] ) : array(); // phpcs:ignore WordPress.Security.NonceVerification
        $content = $this->content_manager->sanitize( $payload );

        update_option( Content_Manager::OPTION_KEY, $content );

        add_settings_error(
            'adc_login_dashboard',
            'adc_login_dashboard_saved',
            __( 'Conteúdo atualizado com sucesso.', 'login-academia-da-comunicacao' ),
            'updated'
        );

        set_transient( 'settings_errors', get_settings_errors( 'adc_login_dashboard' ), 30 );

        $redirect = add_query_arg(
            array(
                'page'    => 'adc-login-dashboard',
                'updated' => 'true',
            ),
            admin_url( 'admin.php' )
        );

        wp_safe_redirect( $redirect );
        exit;
    }

    /**
     * Render the admin dashboard page.
     */
    public function render_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Você não tem permissão para acessar esta página.', 'login-academia-da-comunicacao' ) );
        }

        $content = $this->content_manager->get_options();

        $onboarding_slides = isset( $content['onboarding']['slides'] ) ? $content['onboarding']['slides'] : array();
        $default_content   = $this->content_manager->get_defaults();
        $default_slides    = isset( $default_content['onboarding']['slides'] ) ? $default_content['onboarding']['slides'] : array();
        ?>
        <div class="wrap adc-dashboard">
            <h1 class="wp-heading-inline"><?php esc_html_e( 'Dashboard de Conteúdo', 'login-academia-da-comunicacao' ); ?></h1>
            <p class="description"><?php esc_html_e( 'Atualize textos e imagens das telas de onboarding e autenticação da Academia da Comunicação.', 'login-academia-da-comunicacao' ); ?></p>

            <?php settings_errors( 'adc_login_dashboard' ); ?>

            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="adc-dashboard-form">
                <?php wp_nonce_field( 'adc_login_save_content', 'adc_login_content_nonce' ); ?>
                <input type="hidden" name="action" value="adc_login_save_content" />

                <section class="adc-dashboard-section">
                    <header>
                        <h2><?php esc_html_e( 'Onboarding', 'login-academia-da-comunicacao' ); ?></h2>
                        <p><?php esc_html_e( 'Personalize os slides de boas-vindas exibidos antes do login.', 'login-academia-da-comunicacao' ); ?></p>
                    </header>

                    <div class="adc-field-grid">
                        <label class="adc-field">
                            <span class="adc-field-label"><?php esc_html_e( 'Nome exibido ao lado do logo', 'login-academia-da-comunicacao' ); ?></span>
                            <input type="text" name="adc_content[onboarding][brand_label]" value="<?php echo esc_attr( $content['onboarding']['brand_label'] ); ?>" />
                        </label>
                        <label class="adc-field">
                            <span class="adc-field-label"><?php esc_html_e( 'Rótulo do botão Pular', 'login-academia-da-comunicacao' ); ?></span>
                            <input type="text" name="adc_content[onboarding][skip_label]" value="<?php echo esc_attr( $content['onboarding']['skip_label'] ); ?>" />
                        </label>
                        <label class="adc-field">
                            <span class="adc-field-label"><?php esc_html_e( 'Texto do link de login', 'login-academia-da-comunicacao' ); ?></span>
                            <input type="text" name="adc_content[onboarding][login_link_label]" value="<?php echo esc_attr( $content['onboarding']['login_link_label'] ); ?>" />
                        </label>
                        <label class="adc-field">
                            <span class="adc-field-label"><?php esc_html_e( 'Texto do link de cadastro', 'login-academia-da-comunicacao' ); ?></span>
                            <input type="text" name="adc_content[onboarding][signup_link_label]" value="<?php echo esc_attr( $content['onboarding']['signup_link_label'] ); ?>" />
                        </label>
                    </div>

                    <div class="adc-slides">
                        <?php foreach ( $onboarding_slides as $index => $slide ) :
                            $fallback = isset( $default_slides[ $index ]['fallback'] ) ? $default_slides[ $index ]['fallback'] : '';
                            $image    = $this->resolve_media_preview( $slide, $fallback );
                            ?>
                            <div class="adc-slide-card">
                                <h3><?php printf( esc_html__( 'Slide %d', 'login-academia-da-comunicacao' ), $index + 1 ); ?></h3>
                                <label class="adc-field">
                                    <span class="adc-field-label"><?php esc_html_e( 'Título', 'login-academia-da-comunicacao' ); ?></span>
                                    <input type="text" name="adc_content[onboarding][slides][<?php echo esc_attr( $index ); ?>][title]" value="<?php echo esc_attr( $slide['title'] ); ?>" />
                                </label>
                                <label class="adc-field">
                                    <span class="adc-field-label"><?php esc_html_e( 'Descrição', 'login-academia-da-comunicacao' ); ?></span>
                                    <textarea name="adc_content[onboarding][slides][<?php echo esc_attr( $index ); ?>][text]" rows="3"><?php echo esc_textarea( $slide['text'] ); ?></textarea>
                                </label>
                                <label class="adc-field">
                                    <span class="adc-field-label"><?php esc_html_e( 'Texto do CTA (opcional)', 'login-academia-da-comunicacao' ); ?></span>
                                    <input type="text" name="adc_content[onboarding][slides][<?php echo esc_attr( $index ); ?>][cta]" value="<?php echo esc_attr( $slide['cta'] ); ?>" />
                                </label>
                                <div class="adc-media-field" data-default-src="<?php echo esc_url( $image['fallback'] ); ?>">
                                    <span class="adc-field-label"><?php esc_html_e( 'Ilustração', 'login-academia-da-comunicacao' ); ?></span>
                                    <div class="adc-media-preview">
                                        <img src="<?php echo esc_url( $image['src'] ); ?>" alt="" />
                                    </div>
                                    <div class="adc-media-actions">
                                        <button type="button" class="button adc-media-select" data-target="adc_content_onboarding_slides_<?php echo esc_attr( $index ); ?>_image_id"><?php esc_html_e( 'Selecionar imagem', 'login-academia-da-comunicacao' ); ?></button>
                                        <button type="button" class="button-link adc-media-remove"><?php esc_html_e( 'Restaurar padrão', 'login-academia-da-comunicacao' ); ?></button>
                                    </div>
                                    <input type="hidden" id="adc_content_onboarding_slides_<?php echo esc_attr( $index ); ?>_image_id" class="adc-media-input" name="adc_content[onboarding][slides][<?php echo esc_attr( $index ); ?>][image_id]" value="<?php echo esc_attr( $slide['image_id'] ); ?>" />
                                    <label class="adc-field adc-field-inline">
                                        <span class="adc-field-label"><?php esc_html_e( 'Texto alternativo', 'login-academia-da-comunicacao' ); ?></span>
                                        <input type="text" name="adc_content[onboarding][slides][<?php echo esc_attr( $index ); ?>][image_alt]" value="<?php echo esc_attr( $slide['image_alt'] ); ?>" />
                                    </label>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>

                <?php $this->render_auth_section( 'login', $content['login'] ); ?>
                <?php $this->render_auth_section( 'signup', $content['signup'] ); ?>
                <?php $this->render_auth_section( 'forgot', $content['forgot'] ); ?>
                <?php $this->render_auth_section( 'twofa', $content['twofa'] ); ?>

                <p class="submit">
                    <button type="submit" class="button button-primary button-hero"><?php esc_html_e( 'Salvar alterações', 'login-academia-da-comunicacao' ); ?></button>
                </p>
            </form>
        </div>
        <?php
    }

    /**
     * Render shared authentication section controls.
     *
     * @param string $section Section key.
     * @param array  $data    Section data.
     */
    protected function render_auth_section( $section, array $data ) {
        $titles = array(
            'login'  => array(
                'title'       => __( 'Tela de Login', 'login-academia-da-comunicacao' ),
                'description' => __( 'Texto motivador, destaques e imagem lateral do formulário de login.', 'login-academia-da-comunicacao' ),
            ),
            'signup' => array(
                'title'       => __( 'Tela de Cadastro', 'login-academia-da-comunicacao' ),
                'description' => __( 'Defina a narrativa da etapa de criação de conta e personalize o apoio visual.', 'login-academia-da-comunicacao' ),
            ),
            'forgot' => array(
                'title'       => __( 'Recuperação de Senha', 'login-academia-da-comunicacao' ),
                'description' => __( 'Oriente o usuário durante o processo de solicitação de redefinição.', 'login-academia-da-comunicacao' ),
            ),
            'twofa'  => array(
                'title'       => __( 'Verificação em Duas Etapas', 'login-academia-da-comunicacao' ),
                'description' => __( 'Ajuste as instruções apresentadas durante a validação do código 2FA.', 'login-academia-da-comunicacao' ),
            ),
        );

        $default_content   = $this->content_manager->get_defaults();
        $section_defaults  = isset( $default_content[ $section ] ) ? $default_content[ $section ] : array();
        $data              = wp_parse_args( $data, $section_defaults );
        $features          = isset( $data['features'] ) && is_array( $data['features'] ) ? $data['features'] : array();
        $illustration      = isset( $data['illustration'] ) && is_array( $data['illustration'] ) ? $data['illustration'] : array();
        $fallback          = isset( $section_defaults['illustration']['fallback'] ) ? $section_defaults['illustration']['fallback'] : '';
        $image_id          = isset( $illustration['image_id'] ) ? absint( $illustration['image_id'] ) : 0;
        $image_src         = $image_id ? wp_get_attachment_image_url( $image_id, 'full' ) : '';
        if ( ! $image_src ) {
            $image_src = $fallback;
        }
        ?>
        <section class="adc-dashboard-section">
            <header>
                <h2><?php echo esc_html( $titles[ $section ]['title'] ); ?></h2>
                <p><?php echo esc_html( $titles[ $section ]['description'] ); ?></p>
            </header>

            <div class="adc-field-grid">
                <label class="adc-field">
                    <span class="adc-field-label"><?php esc_html_e( 'Badge/Chamada curta', 'login-academia-da-comunicacao' ); ?></span>
                    <input type="text" name="adc_content[<?php echo esc_attr( $section ); ?>][badge]" value="<?php echo esc_attr( $data['badge'] ); ?>" />
                </label>
                <label class="adc-field">
                    <span class="adc-field-label"><?php esc_html_e( 'Headline', 'login-academia-da-comunicacao' ); ?></span>
                    <input type="text" name="adc_content[<?php echo esc_attr( $section ); ?>][headline]" value="<?php echo esc_attr( $data['headline'] ); ?>" />
                </label>
                <label class="adc-field adc-field-wide">
                    <span class="adc-field-label"><?php esc_html_e( 'Subtítulo', 'login-academia-da-comunicacao' ); ?></span>
                    <textarea name="adc_content[<?php echo esc_attr( $section ); ?>][subtitle]" rows="3"><?php echo esc_textarea( $data['subtitle'] ); ?></textarea>
                </label>
            </div>

            <div class="adc-field-grid">
                <label class="adc-field">
                    <span class="adc-field-label"><?php esc_html_e( 'Kicker do cartão', 'login-academia-da-comunicacao' ); ?></span>
                    <input type="text" name="adc_content[<?php echo esc_attr( $section ); ?>][card_kicker]" value="<?php echo esc_attr( $data['card_kicker'] ); ?>" />
                </label>
                <label class="adc-field">
                    <span class="adc-field-label"><?php esc_html_e( 'Título do cartão', 'login-academia-da-comunicacao' ); ?></span>
                    <input type="text" name="adc_content[<?php echo esc_attr( $section ); ?>][card_title]" value="<?php echo esc_attr( $data['card_title'] ); ?>" />
                </label>
                <label class="adc-field adc-field-wide">
                    <span class="adc-field-label"><?php esc_html_e( 'Descrição do formulário', 'login-academia-da-comunicacao' ); ?></span>
                    <textarea name="adc_content[<?php echo esc_attr( $section ); ?>][card_description]" rows="3"><?php echo esc_textarea( $data['card_description'] ); ?></textarea>
                </label>
            </div>

            <div class="adc-field-grid">
                <?php if ( 'login' === $section ) : ?>
                    <label class="adc-field">
                        <span class="adc-field-label"><?php esc_html_e( 'Texto do link "Esqueceu a senha?"', 'login-academia-da-comunicacao' ); ?></span>
                        <input type="text" name="adc_content[login][forgot_label]" value="<?php echo esc_attr( $data['forgot_label'] ); ?>" />
                    </label>
                    <label class="adc-field">
                        <span class="adc-field-label"><?php esc_html_e( 'Texto do checkbox Lembrar', 'login-academia-da-comunicacao' ); ?></span>
                        <input type="text" name="adc_content[login][remember_label]" value="<?php echo esc_attr( $data['remember_label'] ); ?>" />
                    </label>
                <?php endif; ?>

                <?php if ( in_array( $section, array( 'login', 'signup', 'twofa' ), true ) ) : ?>
                    <label class="adc-field">
                        <span class="adc-field-label"><?php esc_html_e( 'Texto do botão principal', 'login-academia-da-comunicacao' ); ?></span>
                        <input type="text" name="adc_content[<?php echo esc_attr( $section ); ?>][submit_label]" value="<?php echo esc_attr( $data['submit_label'] ); ?>" />
                    </label>
                <?php endif; ?>

                <?php if ( 'twofa' === $section ) : ?>
                    <label class="adc-field">
                        <span class="adc-field-label"><?php esc_html_e( 'Texto do botão "Reenviar código"', 'login-academia-da-comunicacao' ); ?></span>
                        <input type="text" name="adc_content[twofa][resend_label]" value="<?php echo esc_attr( $data['resend_label'] ); ?>" />
                    </label>
                <?php endif; ?>

                <?php if ( 'signup' === $section ) : ?>
                    <label class="adc-field adc-field-wide">
                        <span class="adc-field-label"><?php esc_html_e( 'Texto dos termos (antes do link)', 'login-academia-da-comunicacao' ); ?></span>
                        <textarea name="adc_content[signup][terms_text]" rows="2"><?php echo esc_textarea( $data['terms_text'] ); ?></textarea>
                    </label>
                    <label class="adc-field">
                        <span class="adc-field-label"><?php esc_html_e( 'Texto do link dos termos', 'login-academia-da-comunicacao' ); ?></span>
                        <input type="text" name="adc_content[signup][terms_link]" value="<?php echo esc_attr( $data['terms_link'] ); ?>" />
                    </label>
                <?php endif; ?>

                <?php if ( in_array( $section, array( 'login', 'signup' ), true ) ) : ?>
                    <label class="adc-field">
                        <span class="adc-field-label"><?php esc_html_e( 'Texto auxiliar do rodapé', 'login-academia-da-comunicacao' ); ?></span>
                        <input type="text" name="adc_content[<?php echo esc_attr( $section ); ?>][footer_prompt]" value="<?php echo esc_attr( $data['footer_prompt'] ); ?>" />
                    </label>
                    <label class="adc-field">
                        <span class="adc-field-label"><?php esc_html_e( 'Texto do link do rodapé', 'login-academia-da-comunicacao' ); ?></span>
                        <input type="text" name="adc_content[<?php echo esc_attr( $section ); ?>][footer_link]" value="<?php echo esc_attr( $data['footer_link'] ); ?>" />
                    </label>
                <?php endif; ?>

                <?php if ( 'forgot' === $section ) : ?>
                    <label class="adc-field">
                        <span class="adc-field-label"><?php esc_html_e( 'Texto do botão de envio', 'login-academia-da-comunicacao' ); ?></span>
                        <input type="text" name="adc_content[forgot][submit_label]" value="<?php echo esc_attr( $data['submit_label'] ); ?>" />
                    </label>
                    <label class="adc-field">
                        <span class="adc-field-label"><?php esc_html_e( 'Texto do link para voltar', 'login-academia-da-comunicacao' ); ?></span>
                        <input type="text" name="adc_content[forgot][footer_link]" value="<?php echo esc_attr( $data['footer_link'] ); ?>" />
                    </label>
                <?php endif; ?>
            </div>

            <label class="adc-field adc-field-wide">
                <span class="adc-field-label"><?php esc_html_e( 'Lista de destaques (um por linha)', 'login-academia-da-comunicacao' ); ?></span>
                <textarea name="adc_content[<?php echo esc_attr( $section ); ?>][features]" rows="3"><?php echo esc_textarea( implode( "\n", $features ) ); ?></textarea>
            </label>

            <div class="adc-media-field" data-default-src="<?php echo esc_url( $fallback ); ?>">
                <span class="adc-field-label"><?php esc_html_e( 'Ilustração lateral', 'login-academia-da-comunicacao' ); ?></span>
                <div class="adc-media-preview">
                    <img src="<?php echo esc_url( $image_src ); ?>" alt="" />
                </div>
                <div class="adc-media-actions">
                    <button type="button" class="button adc-media-select" data-target="adc_content_<?php echo esc_attr( $section ); ?>_illustration_image_id"><?php esc_html_e( 'Selecionar imagem', 'login-academia-da-comunicacao' ); ?></button>
                    <button type="button" class="button-link adc-media-remove"><?php esc_html_e( 'Restaurar padrão', 'login-academia-da-comunicacao' ); ?></button>
                </div>
                <input type="hidden" id="adc_content_<?php echo esc_attr( $section ); ?>_illustration_image_id" class="adc-media-input" name="adc_content[<?php echo esc_attr( $section ); ?>][illustration][image_id]" value="<?php echo esc_attr( $image_id ); ?>" />
                <label class="adc-field adc-field-inline">
                    <span class="adc-field-label"><?php esc_html_e( 'Texto alternativo da imagem', 'login-academia-da-comunicacao' ); ?></span>
                    <input type="text" name="adc_content[<?php echo esc_attr( $section ); ?>][illustration][image_alt]" value="<?php echo esc_attr( isset( $illustration['image_alt'] ) ? $illustration['image_alt'] : '' ); ?>" />
                </label>
            </div>
        </section>
        <?php
    }

    /**
     * Resolve preview data for slide media fields.
     *
     * @param array  $slide    Slide data.
     * @param string $fallback Fallback asset URL.
     *
     * @return array
     */
    protected function resolve_media_preview( array $slide, $fallback ) {
        $attachment_id = isset( $slide['image_id'] ) ? absint( $slide['image_id'] ) : 0;
        $src           = $attachment_id ? wp_get_attachment_image_url( $attachment_id, 'full' ) : '';

        if ( ! $src ) {
            $src = $fallback;
        }

        return array(
            'src'      => $src,
            'fallback' => $fallback,
        );
    }

    /**
     * Enqueue dashboard specific assets.
     *
     * @param string $hook Current admin page hook.
     */
    public function enqueue_assets( $hook ) {
        $valid_hooks = array(
            'toplevel_page_adc-login-dashboard',
            'login-academia-da-comunicacao_page_adc-login-dashboard',
        );

        if ( ! in_array( $hook, $valid_hooks, true ) ) {
            return;
        }

        wp_enqueue_style( 'adc-login-frontend' );
        wp_enqueue_style( 'adc-login-dashboard', get_asset_url( 'assets/css/admin-dashboard.css' ), array(), '1.0.0' );
        wp_enqueue_media();
        wp_enqueue_script( 'adc-login-dashboard', get_asset_url( 'assets/js/admin-dashboard.js' ), array( 'jquery' ), '1.0.0', true );

        wp_localize_script(
            'adc-login-dashboard',
            'ADCLoginDashboard',
            array(
                'chooseImage' => __( 'Escolher imagem', 'login-academia-da-comunicacao' ),
                'setImage'    => __( 'Usar esta imagem', 'login-academia-da-comunicacao' ),
                'removeImage' => __( 'Remover imagem', 'login-academia-da-comunicacao' ),
            )
        );
    }
}
