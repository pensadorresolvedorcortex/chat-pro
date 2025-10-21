<?php
/**
 * Principal handler para inicialização e assets.
 */
class LAE_Plugin {

    /**
     * Instância singleton.
     *
     * @var LAE_Plugin
     */
    private static $instance;

    /**
     * Instância do gerenciador de páginas.
     *
     * @var LAE_Pages
     */
    private $pages;

    /**
     * Instância do registrador de shortcodes.
     *
     * @var LAE_Shortcodes
     */
    private $shortcodes;

    /**
     * Recupera a instância única do plugin.
     *
     * @return LAE_Plugin
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Inicializa hooks principais do plugin.
     */
    public function init() {
        $this->pages      = new LAE_Pages();
        $this->shortcodes = new LAE_Shortcodes( $this, $this->pages );

        add_action( 'init', array( $this, 'load_textdomain' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );
        add_action( 'admin_menu', array( $this, 'register_admin_page' ) );
        add_action( 'wp', array( $this, 'protect_profile_from_woocommerce' ), 1 );
        add_action( 'wp_ajax_lae_upload_avatar', array( $this, 'handle_avatar_upload' ) );
        add_action( 'wp_ajax_lae_remove_avatar', array( $this, 'handle_avatar_remove' ) );
        add_filter( 'pre_get_avatar_data', array( $this, 'filter_custom_avatar' ), 10, 2 );
    }

    /**
     * Carrega o textdomain do plugin.
     */
    public function load_textdomain() {
        load_plugin_textdomain( 'login-academia-da-educacao', false, dirname( plugin_basename( LAE_PLUGIN_FILE ) ) . '/languages' );
    }

    /**
     * Registra os assets de frontend.
     */
    public function register_assets() {
        wp_register_style(
            'lae-user-menu',
            LAE_PLUGIN_URL . 'assets/css/style.css',
            array(),
            LAE_PLUGIN_VERSION
        );

        wp_register_script(
            'lae-user-menu',
            LAE_PLUGIN_URL . 'assets/js/script.js',
            array(),
            LAE_PLUGIN_VERSION,
            true
        );
    }

    /**
     * Registra a página administrativa do plugin.
     */
    public function register_admin_page() {
        add_menu_page(
            __( 'Login Academia da Educação', 'login-academia-da-educacao' ),
            __( 'Login Hub', 'login-academia-da-educacao' ),
            'manage_options',
            'adc-login-hub',
            array( $this, 'render_admin_page' ),
            'dashicons-admin-users',
            58
        );
    }

    /**
     * Renderiza a página administrativa principal.
     */
    public function render_admin_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to access this page.', 'login-academia-da-educacao' ) );
        }

        $registered_pages = $this->pages instanceof LAE_Pages ? $this->pages->get_registered_pages() : array();
        $pages            = array();

        foreach ( $registered_pages as $slug => $data ) {
            $pages[] = array(
                'title'     => isset( $data['title'] ) ? $data['title'] : '',
                'shortcode' => isset( $data['shortcode'] ) ? $data['shortcode'] : '',
                'url'       => $this->pages instanceof LAE_Pages ? $this->pages->get_page_url( $slug ) : '',
            );
        }

        echo $this->render_template(
            'admin-overview.php',
            array(
                'pages'          => $pages,
                'menu_shortcode' => '[lae_user_menu]',
            )
        );
    }

    /**
     * Enfileira os assets necessários para o menu do usuário.
     */
    public function enqueue_assets() {
        $this->enqueue_style();
        wp_enqueue_script( 'lae-user-menu' );

        wp_localize_script(
            'lae-user-menu',
            'LAEAvatar',
            array(
                'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
                'nonce'          => wp_create_nonce( 'lae_avatar_nonce' ),
                'defaultAvatar'  => LAE_PLUGIN_URL . 'assets/img/default-avatar.svg',
                'messages'       => array(
                    'uploading'     => __( 'Enviando foto...', 'login-academia-da-educacao' ),
                    'removing'      => __( 'Removendo foto...', 'login-academia-da-educacao' ),
                    'uploadSuccess' => __( 'Foto atualizada com sucesso!', 'login-academia-da-educacao' ),
                    'removeSuccess' => __( 'Foto removida com sucesso.', 'login-academia-da-educacao' ),
                    'error'         => __( 'Não foi possível atualizar a foto de perfil.', 'login-academia-da-educacao' ),
                ),
            )
        );
    }

    /**
     * Enfileira apenas as folhas de estilo do plugin.
     */
    public function enqueue_style() {
        wp_enqueue_style( 'lae-user-menu' );
    }

    /**
     * Impede que o WooCommerce sobrescreva a página de perfil do plugin.
     */
    public function protect_profile_from_woocommerce() {
        if ( is_admin() ) {
            return;
        }

        if ( ! ( class_exists( 'WooCommerce' ) || function_exists( 'WC' ) ) ) {
            return;
        }

        if ( ! $this->pages instanceof LAE_Pages ) {
            return;
        }

        if ( ! $this->pages->is_current_page_slug( 'minha-conta-academia' ) ) {
            return;
        }

        add_filter( 'pre_option_woocommerce_myaccount_page_id', array( $this, 'filter_wc_myaccount_page_id' ) );

        if ( function_exists( 'remove_filter' ) && has_filter( 'the_content', 'woocommerce_account_content' ) ) {
            remove_filter( 'the_content', 'woocommerce_account_content', 10 );
        }
    }

    /**
     * Retorna 0 para o ID da página Minha Conta do WooCommerce durante a renderização do perfil personalizado.
     *
     * @return int
     */
    public function filter_wc_myaccount_page_id( $pre ) {
        return 0;
    }

    /**
     * Processa o upload do avatar personalizado do usuário.
     */
    public function handle_avatar_upload() {
        if ( ! is_user_logged_in() ) {
            wp_send_json_error(
                array(
                    'message' => __( 'Você precisa estar logado para atualizar a foto.', 'login-academia-da-educacao' ),
                ),
                403
            );
        }

        check_ajax_referer( 'lae_avatar_nonce', 'nonce' );

        if ( empty( $_FILES['avatar'] ) || ! is_array( $_FILES['avatar'] ) ) {
            wp_send_json_error(
                array(
                    'message' => __( 'Nenhum arquivo foi enviado.', 'login-academia-da-educacao' ),
                )
            );
        }

        $file       = $_FILES['avatar'];
        $upload_err = isset( $file['error'] ) ? (int) $file['error'] : UPLOAD_ERR_OK;

        if ( UPLOAD_ERR_OK !== $upload_err ) {
            wp_send_json_error(
                array(
                    'message' => __( 'Falha ao enviar o arquivo. Tente novamente.', 'login-academia-da-educacao' ),
                )
            );
        }

        $file_type = wp_check_filetype( isset( $file['name'] ) ? $file['name'] : '', null );

        if ( empty( $file_type['type'] ) || 0 !== strpos( $file_type['type'], 'image/' ) ) {
            wp_send_json_error(
                array(
                    'message' => __( 'Envie apenas arquivos de imagem (JPG, PNG, GIF ou WEBP).', 'login-academia-da-educacao' ),
                )
            );
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $uploaded = wp_handle_upload(
            $file,
            array(
                'test_form' => false,
            )
        );

        if ( ! $uploaded || isset( $uploaded['error'] ) ) {
            wp_send_json_error(
                array(
                    'message' => __( 'Não foi possível salvar a imagem enviada.', 'login-academia-da-educacao' ),
                )
            );
        }

        $user_id = get_current_user_id();

        $attachment = array(
            'post_mime_type' => $file_type['type'],
            'post_title'     => isset( $uploaded['file'] ) ? sanitize_file_name( basename( $uploaded['file'] ) ) : '',
            'post_content'   => '',
            'post_status'    => 'inherit',
        );

        $attachment_id = wp_insert_attachment( $attachment, $uploaded['file'], 0 );

        if ( is_wp_error( $attachment_id ) || ! $attachment_id ) {
            if ( isset( $uploaded['file'] ) ) {
                wp_delete_file( $uploaded['file'] );
            }

            wp_send_json_error(
                array(
                    'message' => __( 'Não foi possível registrar a imagem enviada.', 'login-academia-da-educacao' ),
                )
            );
        }

        $metadata = wp_generate_attachment_metadata( $attachment_id, $uploaded['file'] );

        if ( is_wp_error( $metadata ) ) {
            wp_delete_attachment( $attachment_id, true );

            wp_send_json_error(
                array(
                    'message' => __( 'Não foi possível processar a imagem enviada.', 'login-academia-da-educacao' ),
                )
            );
        }

        wp_update_attachment_metadata( $attachment_id, $metadata );

        $previous_avatar_id = (int) get_user_meta( $user_id, 'lae_custom_avatar_id', true );

        update_user_meta( $user_id, 'lae_custom_avatar_id', $attachment_id );

        if ( $previous_avatar_id && $previous_avatar_id !== $attachment_id ) {
            wp_delete_attachment( $previous_avatar_id, true );
        }

        $url = wp_get_attachment_image_url( $attachment_id, 'full' );

        if ( ! $url ) {
            $url = LAE_PLUGIN_URL . 'assets/img/default-avatar.svg';
        }

        wp_send_json_success(
            array(
                'url'       => $url,
                'hasCustom' => true,
                'message'   => __( 'Foto atualizada com sucesso!', 'login-academia-da-educacao' ),
            )
        );
    }

    /**
     * Remove o avatar personalizado do usuário.
     */
    public function handle_avatar_remove() {
        if ( ! is_user_logged_in() ) {
            wp_send_json_error(
                array(
                    'message' => __( 'Você precisa estar logado para remover a foto.', 'login-academia-da-educacao' ),
                ),
                403
            );
        }

        check_ajax_referer( 'lae_avatar_nonce', 'nonce' );

        $user_id     = get_current_user_id();
        $avatar_id   = (int) get_user_meta( $user_id, 'lae_custom_avatar_id', true );
        $default_url = LAE_PLUGIN_URL . 'assets/img/default-avatar.svg';

        if ( $avatar_id ) {
            delete_user_meta( $user_id, 'lae_custom_avatar_id' );
            wp_delete_attachment( $avatar_id, true );
        }

        $current_url = get_avatar_url( $user_id, array( 'size' => 240 ) );

        if ( ! $current_url ) {
            $current_url = $default_url;
        }

        wp_send_json_success(
            array(
                'url'       => $current_url,
                'hasCustom' => false,
                'message'   => __( 'Foto removida com sucesso.', 'login-academia-da-educacao' ),
            )
        );
    }

    /**
     * Substitui o avatar padrão pelo avatar personalizado, quando disponível.
     *
     * @param array|false  $args        Dados atuais do avatar.
     * @param int|object   $id_or_email Usuário, email ou ID alvo.
     *
     * @return array|false
     */
    public function filter_custom_avatar( $args, $id_or_email ) {
        $user_id = 0;

        if ( is_numeric( $id_or_email ) ) {
            $user_id = (int) $id_or_email;
        } elseif ( $id_or_email instanceof WP_User ) {
            $user_id = $id_or_email->ID;
        } elseif ( $id_or_email instanceof WP_Post ) {
            $user_id = (int) $id_or_email->post_author;
        } elseif ( $id_or_email instanceof WP_Comment ) {
            $user_id = (int) $id_or_email->user_id;
        } elseif ( is_string( $id_or_email ) ) {
            $user     = get_user_by( 'email', $id_or_email );
            $user_id  = $user instanceof WP_User ? $user->ID : 0;
        }

        if ( ! $user_id ) {
            return $args;
        }

        $avatar_id = (int) get_user_meta( $user_id, 'lae_custom_avatar_id', true );

        if ( ! $avatar_id ) {
            return $args;
        }

        $avatar_url = wp_get_attachment_image_url( $avatar_id, 'full' );

        if ( ! $avatar_url ) {
            delete_user_meta( $user_id, 'lae_custom_avatar_id' );
            return $args;
        }

        if ( ! is_array( $args ) ) {
            $args = array();
        }

        $args['url']          = $avatar_url;
        $args['found_avatar'] = true;

        return $args;
    }

    /**
     * Retorna o gerenciador de páginas.
     *
     * @return LAE_Pages
     */
    public function get_pages() {
        return $this->pages;
    }

    /**
     * Renderiza um template e retorna o HTML resultante.
     *
     * @param string $template Arquivo de template em templates/.
     * @param array  $vars     Variáveis a serem extraídas para o template.
     *
     * @return string
     */
    public function render_template( $template, $vars = array() ) {
        $file = LAE_PLUGIN_DIR . 'templates/' . $template;

        if ( ! file_exists( $file ) ) {
            return '';
        }

        if ( ! empty( $vars ) ) {
            extract( $vars, EXTR_SKIP );
        }

        ob_start();
        include $file;

        return ob_get_clean();
    }
}
