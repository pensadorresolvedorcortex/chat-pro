<?php
namespace Juntaplay\Forms;

use Juntaplay\Shortcodes\GroupRelationship;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Formulário de criação de grupos Juntaplay.
 */
class CreateGroupForm {
    const ACTION = 'jplay_create_group';
    const SUPPORT_CHANNEL_WHATSAPP = 'whatsapp';
    const SUPPORT_CHANNEL_EMAIL    = 'email';

    /**
     * Registra shortcodes relacionados ao formulário.
     */
    public static function register_shortcodes() {
        add_shortcode( 'juntaplay_create_group_form', array( __CLASS__, 'render_form' ) );
    }

    /**
     * Registra handlers do formulário.
     */
    public static function register_handlers() {
        add_action( 'admin_post_' . self::ACTION, array( __CLASS__, 'handle_submission' ) );
        add_action( 'admin_post_nopriv_' . self::ACTION, array( __CLASS__, 'handle_submission' ) );
    }

    /**
     * Renderiza o formulário de criação de grupo.
     *
     * @return string
     */
    public static function render_form() {
        if ( wp_style_is( 'juntaplay-frontend', 'registered' ) ) {
            wp_enqueue_style( 'juntaplay-frontend' );
        }

        if ( wp_script_is( 'juntaplay-form', 'registered' ) ) {
            wp_enqueue_script( 'juntaplay-form' );
        }

        $relationship_field = class_exists( '\\Juntaplay\\Shortcodes\\GroupRelationship' )
            ? GroupRelationship::render()
            : '';

        $success_code = isset( $_GET['jplay_success'] ) ? sanitize_text_field( wp_unslash( $_GET['jplay_success'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $error_code   = isset( $_GET['jplay_error'] ) ? sanitize_text_field( wp_unslash( $_GET['jplay_error'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $post_id      = isset( $_GET['jplay_post'] ) ? absint( $_GET['jplay_post'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

        $notices = self::prepare_notices( $success_code, $error_code );

        if ( 'created' === $success_code && $post_id && wp_script_is( 'juntaplay-2fa', 'registered' ) ) {
            wp_enqueue_script( 'juntaplay-2fa' );
            wp_localize_script(
                'juntaplay-2fa',
                'jplay2faData',
                array(
                    'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
                    'nonce'     => wp_create_nonce( 'jplay_2fa_nonce' ),
                    'postId'    => $post_id,
                    'messages'  => array(
                        'sending'  => __( 'Enviando código…', 'juntaplay' ),
                        'sent'     => __( 'Enviamos um código de 6 dígitos para o seu e-mail. Digite-o para confirmar o envio para análise.', 'juntaplay' ),
                        'success'  => __( '✅ Recebemos sua submissão. A equipe Juntaplay vai avaliar se o grupo cumpre todos os requisitos e você será notificado por e-mail.', 'juntaplay' ),
                        'error'    => __( 'Código inválido ou expirado. Solicite um novo código.', 'juntaplay' ),
                        'retry'    => __( 'Solicitar novo código', 'juntaplay' ),
                    ),
                    'i18n'      => array(
                        'close' => __( 'Fechar', 'juntaplay' ),
                        'submit' => __( 'Confirmar código', 'juntaplay' ),
                    ),
                )
            );
        }

        ob_start();
        ?>
        <?php if ( ! empty( $notices ) ) : ?>
            <div class="jplay-notices" aria-live="polite">
                <?php foreach ( $notices as $notice ) : ?>
                    <div class="jplay-notice jplay-notice--<?php echo esc_attr( $notice['type'] ); ?>">
                        <?php echo esc_html( $notice['message'] ); ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="jplay-form jplay-form--create-group">
            <div class="jplay-field">
                <label for="jplay_group_title" class="jplay-label">
                    <?php esc_html_e( 'Nome do grupo', 'juntaplay' ); ?>
                    <span class="jplay-required" aria-hidden="true">*</span>
                </label>
                <input
                    type="text"
                    id="jplay_group_title"
                    name="jplay_group_title"
                    class="jplay-input"
                    required
                    aria-required="true"
                />
            </div>

            <?php echo $relationship_field; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

            <div class="jplay-field">
                <label for="jplay_shared_url" class="jplay-label">
                    <?php esc_html_e( 'URL de acesso compartilhado', 'juntaplay' ); ?>
                    <span class="jplay-required" aria-hidden="true">*</span>
                </label>
                <input
                    type="url"
                    id="jplay_shared_url"
                    name="jplay_shared_url"
                    class="jplay-input"
                    required
                    aria-required="true"
                />
            </div>

            <div class="jplay-field">
                <label for="jplay_shared_user" class="jplay-label">
                    <?php esc_html_e( 'Usuário compartilhado', 'juntaplay' ); ?>
                    <span class="jplay-required" aria-hidden="true">*</span>
                </label>
                <input
                    type="text"
                    id="jplay_shared_user"
                    name="jplay_shared_user"
                    class="jplay-input"
                    required
                    aria-required="true"
                />
            </div>

            <div class="jplay-field">
                <label for="jplay_shared_pass" class="jplay-label">
                    <?php esc_html_e( 'Senha compartilhada', 'juntaplay' ); ?>
                    <span class="jplay-required" aria-hidden="true">*</span>
                </label>
                <input
                    type="password"
                    id="jplay_shared_pass"
                    name="jplay_shared_pass"
                    class="jplay-input"
                    autocomplete="new-password"
                    required
                    aria-required="true"
                />
            </div>

            <div class="jplay-field">
                <label for="jplay_shared_notes" class="jplay-label">
                    <?php esc_html_e( 'Notas adicionais (opcional)', 'juntaplay' ); ?>
                </label>
                <textarea
                    id="jplay_shared_notes"
                    name="jplay_shared_notes"
                    class="jplay-textarea"
                    rows="4"
                ></textarea>
            </div>

            <div class="jplay-field">
                <label for="jplay_support_channel" class="jplay-label">
                    <?php esc_html_e( 'Suporte a membros', 'juntaplay' ); ?>
                    <span class="jplay-required" aria-hidden="true">*</span>
                </label>
                <select
                    id="jplay_support_channel"
                    name="jplay_support_channel"
                    class="jplay-input"
                    required
                    aria-required="true"
                >
                    <option value=""><?php esc_html_e( 'Selecione…', 'juntaplay' ); ?></option>
                    <option value="whatsapp"><?php esc_html_e( 'WhatsApp', 'juntaplay' ); ?></option>
                    <option value="email"><?php esc_html_e( 'E-mail', 'juntaplay' ); ?></option>
                </select>
            </div>

            <div class="jplay-field jplay-support-details jplay-support-details--whatsapp" data-support-target="whatsapp" hidden>
                <label for="jplay_support_whatsapp" class="jplay-label">
                    <span class="jplay-flag" aria-hidden="true">🇧🇷</span>
                    <?php esc_html_e( 'Número de WhatsApp', 'juntaplay' ); ?>
                    <span class="jplay-required" aria-hidden="true">*</span>
                </label>
                <div class="jplay-support-input-group">
                    <label for="jplay_support_country" class="screen-reader-text">
                        <?php esc_html_e( 'Código do país', 'juntaplay' ); ?>
                    </label>
                    <select id="jplay_support_country" name="jplay_support_country" class="jplay-input jplay-input--compact">
                        <option value="+55">🇧🇷 +55</option>
                        <option value="+351">🇵🇹 +351</option>
                        <option value="+1">🇺🇸 +1</option>
                    </select>
                    <label for="jplay_support_whatsapp" class="screen-reader-text">
                        <?php esc_html_e( 'Número com DDD', 'juntaplay' ); ?>
                    </label>
                    <input
                        type="tel"
                        id="jplay_support_whatsapp"
                        name="jplay_support_whatsapp"
                        class="jplay-input jplay-input--flex"
                        inputmode="tel"
                        placeholder="(11) 91234-5678"
                        aria-describedby="jplay_support_whatsapp_help"
                    />
                </div>
                <p id="jplay_support_whatsapp_help" class="jplay-help-text">
                    <?php esc_html_e( 'Informe o número com DDI e DDD. Será visível apenas para participantes aprovados.', 'juntaplay' ); ?>
                </p>
            </div>

            <div class="jplay-field jplay-support-details jplay-support-details--email" data-support-target="email" hidden>
                <label for="jplay_support_email" class="jplay-label">
                    <?php esc_html_e( 'E-mail de suporte', 'juntaplay' ); ?>
                    <span class="jplay-required" aria-hidden="true">*</span>
                </label>
                <input
                    type="email"
                    id="jplay_support_email"
                    name="jplay_support_email"
                    class="jplay-input"
                    placeholder="suporte@exemplo.com"
                />
            </div>

            <?php wp_nonce_field( self::ACTION, 'jplay_create_group_nonce' ); ?>
            <input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION ); ?>" />

            <div class="jplay-actions">
                <button type="submit" class="jplay-button jplay-button--primary">
                    <?php esc_html_e( 'Criar grupo', 'juntaplay' ); ?>
                </button>
            </div>
        </form>

        <?php if ( 'created' === $success_code && $post_id ) : ?>
            <div class="jplay-actions jplay-actions--secondary">
                <button
                    type="button"
                    class="jplay-button jplay-button--secondary"
                    data-jplay-2fa-trigger="true"
                    data-jplay-post-id="<?php echo esc_attr( $post_id ); ?>"
                >
                    <?php esc_html_e( 'Enviar para análise', 'juntaplay' ); ?>
                </button>
            </div>

            <?php self::render_two_factor_modal(); ?>
        <?php endif; ?>
        <?php
        return ob_get_clean();
    }

    /**
     * Manipula o envio do formulário de criação de grupo.
     */
    public static function handle_submission() {
        if ( ! isset( $_POST['jplay_create_group_nonce'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
            wp_die( esc_html__( 'Requisição inválida.', 'juntaplay' ), 400 );
        }

        check_admin_referer( self::ACTION, 'jplay_create_group_nonce' );

        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_die( esc_html__( 'Você não tem permissão para criar grupos.', 'juntaplay' ), 403 );
        }

        $title = isset( $_POST['jplay_group_title'] ) ? sanitize_text_field( wp_unslash( $_POST['jplay_group_title'] ) ) : '';

        $relationship = isset( $_POST['jplay_relationship_admin'] ) ? sanitize_text_field( wp_unslash( $_POST['jplay_relationship_admin'] ) ) : '';
        $allowed_relationships = class_exists( '\\Juntaplay\\Shortcodes\\GroupRelationship' )
            ? GroupRelationship::get_relationship_options()
            : array();

        if ( '' === $relationship || ! in_array( $relationship, $allowed_relationships, true ) ) {
            self::redirect_with_message( array( 'jplay_error' => 'relationship' ) );
        }

        $shared_url   = isset( $_POST['jplay_shared_url'] ) ? esc_url_raw( wp_unslash( $_POST['jplay_shared_url'] ) ) : '';
        $shared_user  = isset( $_POST['jplay_shared_user'] ) ? sanitize_text_field( wp_unslash( $_POST['jplay_shared_user'] ) ) : '';
        $shared_pass  = isset( $_POST['jplay_shared_pass'] ) ? sanitize_text_field( wp_unslash( $_POST['jplay_shared_pass'] ) ) : '';
        $shared_notes = isset( $_POST['jplay_shared_notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['jplay_shared_notes'] ) ) : '';

        $support_channel = isset( $_POST['jplay_support_channel'] ) ? sanitize_text_field( wp_unslash( $_POST['jplay_support_channel'] ) ) : '';
        $allowed_support = self::get_support_channels();

        if ( '' === $support_channel || ! in_array( $support_channel, $allowed_support, true ) ) {
            self::redirect_with_message( array( 'jplay_error' => 'support_channel' ) );
        }

        $support_contact = '';
        $support_country = isset( $_POST['jplay_support_country'] ) ? sanitize_text_field( wp_unslash( $_POST['jplay_support_country'] ) ) : '+55';
        $support_country = preg_replace( '/[^+\d]/', '', $support_country );

        if ( self::SUPPORT_CHANNEL_WHATSAPP === $support_channel ) {
            $whatsapp_number = isset( $_POST['jplay_support_whatsapp'] ) ? sanitize_text_field( wp_unslash( $_POST['jplay_support_whatsapp'] ) ) : '';
            $digits          = preg_replace( '/\D+/', '', $whatsapp_number );

            if ( strlen( $digits ) < 10 ) {
                self::redirect_with_message( array( 'jplay_error' => 'support_whatsapp' ) );
            }

            if ( '' === $support_country ) {
                $support_country = '+55';
            }

            $support_contact = $support_country . $digits;
        } elseif ( self::SUPPORT_CHANNEL_EMAIL === $support_channel ) {
            $support_email = isset( $_POST['jplay_support_email'] ) ? sanitize_email( wp_unslash( $_POST['jplay_support_email'] ) ) : '';

            if ( empty( $support_email ) || ! is_email( $support_email ) ) {
                self::redirect_with_message( array( 'jplay_error' => 'support_email' ) );
            }

            $support_contact = $support_email;
            $support_country = '';
        }

        if ( '' === $title || '' === $shared_url || '' === $shared_user || '' === $shared_pass ) {
            self::redirect_with_message( array( 'jplay_error' => 'required' ) );
        }

        $post_data = array(
            'post_title'   => $title,
            'post_type'    => 'juntaplay_group',
            'post_status'  => 'draft',
            'post_content' => '',
            'post_author'  => get_current_user_id(),
        );

        $post_id = wp_insert_post( $post_data, true );

        if ( is_wp_error( $post_id ) ) {
            self::redirect_with_message( array( 'jplay_error' => 'create_failed' ) );
        }

        // Garante que os dados sanitizados sejam utilizados no salvamento das metas.
        $_POST['jplay_relationship_admin'] = $relationship; // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $_POST['jplay_shared_url']         = $shared_url; // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $_POST['jplay_shared_user']        = $shared_user; // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $_POST['jplay_shared_pass']        = $shared_pass; // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $_POST['jplay_shared_notes']       = $shared_notes; // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $_POST['jplay_support_channel']    = $support_channel; // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $_POST['jplay_support_contact']    = $support_contact; // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $_POST['jplay_support_country']    = $support_country; // phpcs:ignore WordPress.Security.NonceVerification.Missing

        self::redirect_with_message(
            array(
                'jplay_success' => 'created',
                'jplay_post'    => absint( $post_id ),
            )
        );
    }

    /**
     * Retorna os canais de suporte válidos.
     *
     * @return array
     */
    protected static function get_support_channels() {
        return array(
            self::SUPPORT_CHANNEL_WHATSAPP,
            self::SUPPORT_CHANNEL_EMAIL,
        );
    }

    /**
     * Monta a lista de avisos para exibir acima do formulário.
     *
     * @param string $success_code Código de sucesso.
     * @param string $error_code   Código de erro.
     *
     * @return array
     */
    protected static function prepare_notices( $success_code, $error_code ) {
        $notices = array();

        if ( 'created' === $success_code ) {
            $notices[] = array(
                'type'    => 'success',
                'message' => __( 'Grupo criado com sucesso! Revise os dados e envie para análise quando estiver pronto.', 'juntaplay' ),
            );
        }

        if ( '' !== $error_code ) {
            $messages = array(
                'relationship'     => __( 'Selecione a relação com o administrador.', 'juntaplay' ),
                'required'         => __( 'Preencha todos os campos obrigatórios.', 'juntaplay' ),
                'support_channel'  => __( 'Selecione o canal de suporte aos membros.', 'juntaplay' ),
                'support_whatsapp' => __( 'Informe um número de WhatsApp válido com DDI e DDD.', 'juntaplay' ),
                'support_email'    => __( 'Informe um e-mail de suporte válido.', 'juntaplay' ),
                'create_failed'    => __( 'Não foi possível criar o grupo. Tente novamente.', 'juntaplay' ),
            );

            if ( isset( $messages[ $error_code ] ) ) {
                $notices[] = array(
                    'type'    => 'error',
                    'message' => $messages[ $error_code ],
                );
            }
        }

        return $notices;
    }

    /**
     * Renderiza o modal de verificação em duas etapas.
     */
    protected static function render_two_factor_modal() {
        $template = JPLAY_PLUGIN_PATH . 'templates/modal-2fa.php';

        if ( file_exists( $template ) ) {
            include $template;
        }
    }

    /**
     * Retorna a URL de redirecionamento após o envio.
     *
     * @return string
     */
    protected static function get_redirect_url() {
        $referer = wp_get_referer();

        if ( $referer ) {
            return $referer;
        }

        return home_url( '/' );
    }

    /**
     * Redireciona o usuário com query args específicos.
     *
     * @param array $args Lista de argumentos de query.
     */
    protected static function redirect_with_message( $args ) {
        $redirect_url = add_query_arg( $args, self::get_redirect_url() );

        wp_safe_redirect( $redirect_url );
        exit;
    }
}
