<?php
namespace Kalil;

class Plugin {
    private static $instance = null;

    private $admin_id = 0;
    private $register_error = '';

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->admin_id = $this->get_admin_id();
        add_action( 'init', [ $this, 'register_post_type' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
        add_shortcode( 'kalil_member_area', [ $this, 'member_area_shortcode' ] );
        add_shortcode( 'kalil_register', [ $this, 'register_form_shortcode' ] );
        add_action( 'template_redirect', [ $this, 'maybe_handle_registration' ] );
        add_action( 'wp_ajax_kalil_send_message', [ $this, 'handle_send_message' ] );
        add_action( 'wp_ajax_nopriv_kalil_send_message', [ $this, 'handle_send_message' ] );
        add_action( 'wp_ajax_kalil_get_messages', [ $this, 'get_messages' ] );
        add_action( 'wp_ajax_nopriv_kalil_get_messages', [ $this, 'get_messages' ] );
    }

    public function register_post_type() {
        register_post_type( 'kalil_message', [
            'public' => false,
            'show_ui' => true,
            'label' => 'Kalil Messages',
            'supports' => [ 'editor', 'author' ],
        ] );
    }

    public function enqueue_assets() {
        wp_enqueue_style( 'kalil-css', plugins_url( 'assets/css/kalil.css', dirname( __DIR__ ) . '/kalil.php' ) );
        wp_enqueue_script( 'kalil-js', plugins_url( 'assets/js/kalil.js', dirname( __DIR__ ) . '/kalil.php' ), [ 'jquery' ], null, true );
        wp_localize_script( 'kalil-js', 'kalilVars', [
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'kalil_nonce' ),
        ] );
    }

    public function member_area_shortcode( $atts ) {
        if ( ! is_user_logged_in() ) {
            return '<p>Por favor, faça login para acessar.</p>';
        }

        $atts = shortcode_atts( [ 'patient' => 0 ], $atts );
        $current = wp_get_current_user();
        $is_admin = user_can( $current, 'manage_options' );
        $patient  = $is_admin ? absint( $atts['patient'] ) : $current->ID;

        if ( $is_admin && ! $patient ) {
            return '<p>Defina o paciente com o atributo patient.</p>';
        }

        wp_localize_script( 'kalil-js', 'kalilPatient', [ 'id' => $patient ] );

        ob_start();
        ?>
        <div class="kalil-container">
            <nav class="kalil-menu">
                <ul>
                    <li data-tab="document">Documentos</li>
                    <li data-tab="video">Vídeos</li>
                    <li data-tab="conversas" class="active">Conversas</li>
                </ul>
            </nav>
            <div id="kalil-messages"></div>
            <form id="kalil-form" enctype="multipart/form-data">
                <textarea name="message" placeholder="Digite sua mensagem"></textarea>
                <input type="file" name="file" accept="video/*,image/*" />
                <button type="submit">Enviar</button>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    public function handle_send_message() {
        check_ajax_referer( 'kalil_nonce', 'nonce' );

        if ( ! is_user_logged_in() ) {
            wp_send_json_error( 'auth' );
        }

        $current_user = wp_get_current_user();
        $patient_id   = absint( $_POST['patient'] ?? 0 );
        if ( ! $patient_id ) {
            wp_send_json_error( 'no_patient' );
        }

        $is_admin = user_can( $current_user, 'manage_options' );
        if ( ! $is_admin && $current_user->ID !== $patient_id ) {
            wp_send_json_error( 'forbidden' );
        }

        $content   = sanitize_textarea_field( $_POST['message'] ?? '' );
        $file_url  = '';

        if ( ! empty( $_FILES['file']['name'] ) ) {
            $uploaded = wp_handle_upload( $_FILES['file'], [ 'test_form' => false ] );
            if ( ! isset( $uploaded['error'] ) ) {
                $file_url = $uploaded['url'];
            }
        }

        $recipient = user_can( $current_user, 'manage_options' ) ? $patient_id : $this->admin_id;

        $post_id = wp_insert_post( [
            'post_type'   => 'kalil_message',
            'post_title'  => 'Mensagem de ' . $current_user->user_login,
            'post_content'=> $content,
            'post_status' => 'publish',
            'post_author' => $current_user->ID,
        ] );

        if ( $file_url ) {
            add_post_meta( $post_id, '_kalil_file', esc_url_raw( $file_url ) );
        }

        add_post_meta( $post_id, '_kalil_patient', $patient_id );
        add_post_meta( $post_id, '_kalil_recipient', $recipient );

        wp_send_json_success();
    }

    public function get_messages() {
        check_ajax_referer( 'kalil_nonce', 'nonce' );

        if ( ! is_user_logged_in() ) {
            wp_send_json_error();
        }

        $current = wp_get_current_user();
        $patient_id = absint( $_REQUEST['patient'] ?? 0 );
        if ( ! $patient_id ) {
            wp_send_json_error();
        }

        $is_admin = user_can( $current, 'manage_options' );
        if ( ! $is_admin && $current->ID !== $patient_id ) {
            wp_send_json_error();
        }

        $args = [
            'post_type'   => 'kalil_message',
            'numberposts' => -1,
            'meta_key'    => '_kalil_patient',
            'meta_value'  => $patient_id,
            'order'       => 'ASC',
        ];
        $posts = get_posts( $args );
        ob_start();
        foreach ( $posts as $post ) {
            $file     = get_post_meta( $post->ID, '_kalil_file', true );
            $mime     = $file ? wp_check_filetype( $file )['type'] : '';
            $class    = $post->post_author == $this->admin_id ? 'kalil-msg-admin' : 'kalil-msg-patient';
            $type     = 'conversas';
            if ( $file ) {
                $type = strpos( $mime, 'video/' ) === 0 ? 'video' : 'document';
            }
            echo '<div class="kalil-msg ' . esc_attr( $class ) . '" data-type="' . esc_attr( $type ) . '">';
            echo '<p class="kalil-msg-text">' . esc_html( $post->post_content ) . '</p>';
            if ( $file ) {
                if ( strpos( $mime, 'image/' ) === 0 ) {
                    echo '<img src="' . esc_url( $file ) . '" class="kalil-file" alt="" />';
                } elseif ( strpos( $mime, 'video/' ) === 0 ) {
                    echo '<video controls class="kalil-file"><source src="' . esc_url( $file ) . '" type="' . esc_attr( $mime ) . '" /></video>';
                } else {
                    echo '<a href="' . esc_url( $file ) . '" target="_blank">' . esc_html__( 'Download', 'kalil' ) . '</a>';
                }
            }
            echo '<span class="kalil-msg-time">' . esc_html( get_the_date( '', $post ) ) . '</span>';
            echo '</div>';
        }
        $html = ob_get_clean();
        echo $html;
        wp_die();
    }

    private function get_admin_id() {
        $admins = get_users( [ 'role' => 'administrator', 'number' => 1 ] );
        return $admins ? $admins[0]->ID : 1;
    }

    public function maybe_handle_registration() {
        if ( 'POST' !== $_SERVER['REQUEST_METHOD'] || empty( $_POST['kalil_register'] ) ) {
            return;
        }

        check_admin_referer( 'kalil_register_action', 'kalil_register_nonce' );

        $redirect = esc_url_raw( $_POST['kalil_redirect'] ?? home_url( '/member-area' ) );

        $username  = sanitize_user( $_POST['username'] ?? '' );
        $email     = sanitize_email( $_POST['email'] ?? '' );
        $password  = sanitize_text_field( $_POST['password'] ?? '' );
        $full_name = sanitize_text_field( $_POST['full_name'] ?? '' );

        if ( ! $username || ! $email || ! $password || ! $full_name ) {
            $this->register_error = 'Preencha todos os campos.';
            return;
        }

        $user_id = wp_insert_user( [
            'user_login'   => $username,
            'user_email'   => $email,
            'user_pass'    => $password,
            'role'         => 'subscriber',
            'display_name' => $full_name,
            'first_name'   => $full_name,
        ] );

        if ( is_wp_error( $user_id ) ) {
            $this->register_error = $user_id->get_error_message();
            return;
        }

        wp_set_current_user( $user_id );
        wp_set_auth_cookie( $user_id );

        wp_safe_redirect( $redirect );
        exit;
    }

    public function register_form_shortcode( $atts ) {
        if ( is_user_logged_in() ) {
            return '<p>Você já possui conta.</p>';
        }

        $atts     = shortcode_atts( [ 'redirect' => home_url( '/member-area' ) ], $atts );
        $redirect = esc_url( $atts['redirect'] );

        ob_start();
        ?>
        <form method="post" class="kalil-register-form">
            <?php wp_nonce_field( 'kalil_register_action', 'kalil_register_nonce' ); ?>
            <input type="hidden" name="kalil_register" value="1" />
            <input type="hidden" name="kalil_redirect" value="<?php echo esc_attr( $redirect ); ?>" />
            <p><input type="text" name="full_name" placeholder="Nome completo" required></p>
            <p><input type="text" name="username" placeholder="Usuário" required></p>
            <p><input type="email" name="email" placeholder="Email" required></p>
            <p><input type="password" name="password" placeholder="Senha" required></p>
            <p><button type="submit">Cadastrar</button></p>
        </form>
        <?php if ( $this->register_error ) : ?>
            <p class="kalil-error"><?php echo esc_html( $this->register_error ); ?></p>
        <?php endif; ?>
        <?php
        return ob_get_clean();
    }
}

