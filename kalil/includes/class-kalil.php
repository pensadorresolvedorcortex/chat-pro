<?php
namespace Kalil;

class Plugin {
    private static $instance = null;

    private $admin_id = 0;
    private $register_error = '';
    private $login_error = '';

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
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
        add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
        add_action( 'admin_post_kalil_save_access', [ $this, 'save_access_period' ] );
        add_shortcode( 'kalil_member_area', [ $this, 'member_area_shortcode' ] );
        add_shortcode( 'kalil_register', [ $this, 'register_form_shortcode' ] );
        add_action( 'template_redirect', [ $this, 'maybe_handle_registration' ] );
        add_action( 'template_redirect', [ $this, 'maybe_handle_login' ] );
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

    public function add_admin_menu() {
        add_menu_page(
            'Kalil Messages',
            'Kalil Messages',
            'manage_options',
            'kalil-messages',
            [ $this, 'render_admin_page' ],
            'dashicons-format-chat'
        );
    }

    public function render_admin_page() {
        $period = $this->get_access_period();
        ?>
        <div class="wrap">
            <h1>Kalil Messages</h1>
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="kalil-access-form">
                <?php wp_nonce_field( 'kalil_save_access' ); ?>
                <input type="hidden" name="action" value="kalil_save_access" />
                <label for="kalil_access_period"><strong>Tempo que o paciente terá acesso:</strong></label>
                <select name="kalil_access_period" id="kalil_access_period">
                    <option value="3" <?php selected( $period, 3 ); ?>>3 meses</option>
                    <option value="6" <?php selected( $period, 6 ); ?>>6 meses</option>
                    <option value="12" <?php selected( $period, 12 ); ?>>12 meses</option>
                </select>
                <button type="submit" class="button button-primary">Salvar</button>
            </form>
        </div>
        <?php
        echo $this->member_area_shortcode( [] );
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

        $atts    = shortcode_atts( [ 'patient' => 0 ], $atts );
        $current = wp_get_current_user();
        $is_admin = user_can( $current, 'manage_options' );
        $patient  = $is_admin ? absint( $atts['patient'] ) : $current->ID;

        $patients = [];
        if ( $is_admin ) {
            $patients = get_users( [ 'role__in' => [ 'subscriber' ], 'orderby' => 'display_name', 'order' => 'ASC' ] );
        }

        wp_localize_script( 'kalil-js', 'kalilPatient', [ 'id' => $patient ] );

        ob_start();
        ?>
        <div class="kalil-container">
            <?php if ( $is_admin ) : ?>
                <div class="kalil-patient-select">
                    <select id="kalil-patient-select">
                        <option value="">Selecione um paciente</option>
                        <?php foreach ( $patients as $p ) : ?>
                            <option value="<?php echo esc_attr( $p->ID ); ?>" <?php selected( $patient, $p->ID ); ?>><?php echo esc_html( $p->display_name ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
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
        if ( ! $is_admin ) {
            $period = $this->get_access_period();
            $after  = date( 'Y-m-d H:i:s', strtotime( '-' . $period . ' months', current_time( 'timestamp' ) ) );
            $args['date_query'] = [ [ 'after' => $after ] ];
        }
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

    private function get_access_period() {
        $period = (int) get_option( 'kalil_access_period', 12 );
        return in_array( $period, [ 3, 6, 12 ], true ) ? $period : 12;
    }

    public function save_access_period() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Sem permissão' );
        }
        check_admin_referer( 'kalil_save_access' );
        $period = isset( $_POST['kalil_access_period'] ) ? (int) $_POST['kalil_access_period'] : 12;
        if ( ! in_array( $period, [ 3, 6, 12 ], true ) ) {
            $period = 12;
        }
        update_option( 'kalil_access_period', $period );
        wp_safe_redirect( admin_url( 'admin.php?page=kalil-messages&saved=1' ) );
        exit;
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

    public function maybe_handle_login() {
        if ( 'POST' !== $_SERVER['REQUEST_METHOD'] || empty( $_POST['kalil_login'] ) ) {
            return;
        }

        check_admin_referer( 'kalil_login_action', 'kalil_login_nonce' );

        $redirect = esc_url_raw( $_POST['kalil_redirect'] ?? home_url( '/member-area' ) );
        $creds = [
            'user_login'    => sanitize_user( $_POST['login_username'] ?? '' ),
            'user_password' => $_POST['login_password'] ?? '',
            'remember'      => true,
        ];

        $user = wp_signon( $creds, false );

        if ( is_wp_error( $user ) ) {
            $this->login_error = $user->get_error_message();
            return;
        }

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
        <div class="kalil-register-tabs">
            <ul>
                <li data-tab="login" class="active">Entrar no Cadastro</li>
                <li data-tab="register">Cadastrar no site</li>
            </ul>
        </div>
        <div id="kalil-login-form" class="kalil-register-content">
            <form method="post" class="kalil-login-form">
                <?php wp_nonce_field( 'kalil_login_action', 'kalil_login_nonce' ); ?>
                <input type="hidden" name="kalil_login" value="1" />
                <input type="hidden" name="kalil_redirect" value="<?php echo esc_attr( $redirect ); ?>" />
                <p><input type="text" name="login_username" placeholder="Usuário" required></p>
                <p><input type="password" name="login_password" placeholder="Senha" required></p>
                <p><button type="submit">Entrar</button></p>
            </form>
            <?php if ( $this->login_error ) : ?>
                <p class="kalil-error"><?php echo esc_html( $this->login_error ); ?></p>
            <?php endif; ?>
        </div>
        <div id="kalil-register-form" class="kalil-register-content">
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
        </div>
        <?php
        return ob_get_clean();
    }
}

