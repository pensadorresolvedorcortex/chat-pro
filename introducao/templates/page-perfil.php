<?php
/**
 * Template para o shortcode de perfil, exibindo formulários de login e registro.
 *
 * @var string $shortcode_tag Tag do shortcode invocada.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$redirect_url = home_url( '/questoes-de-concursos/' );
$errors       = array();
$login_errors = array();
$active_panel = 'login';
$requested_tab = isset( $_GET['perfil_tab'] ) ? sanitize_key( wp_unslash( $_GET['perfil_tab'] ) ) : '';

if ( in_array( $requested_tab, array( 'login', 'register' ), true ) ) {
    $active_panel = $requested_tab;
}

$login_request = array(
    'user_login' => '',
    'remember'   => false,
);
$registration_request = array(
    'user_login' => '',
    'user_email' => '',
);
if ( is_user_logged_in() ) {
    wp_safe_redirect( $redirect_url );
    exit;
}

if ( 'POST' === $_SERVER['REQUEST_METHOD'] && isset( $_POST['lae_perfil_nonce'] ) && wp_verify_nonce( wp_unslash( $_POST['lae_perfil_nonce'] ), 'lae_perfil_action' ) ) {
    $action = isset( $_POST['lae_perfil_action'] ) ? sanitize_text_field( wp_unslash( $_POST['lae_perfil_action'] ) ) : '';

    if ( 'register' === $action ) {
        $active_panel = 'register';

        $user_login = isset( $_POST['lae_user_login'] ) ? sanitize_user( wp_unslash( $_POST['lae_user_login'] ) ) : '';
        $user_email = isset( $_POST['lae_user_email'] ) ? sanitize_email( wp_unslash( $_POST['lae_user_email'] ) ) : '';
        $user_pass  = isset( $_POST['lae_user_pass'] ) ? wp_unslash( $_POST['lae_user_pass'] ) : '';

        $registration_request['user_login'] = $user_login;
        $registration_request['user_email'] = $user_email;

        if ( empty( $user_login ) ) {
            $errors[] = __( 'Informe um nome de usuário.', 'introducao' );
        }

        if ( empty( $user_email ) || ! is_email( $user_email ) ) {
            $errors[] = __( 'Informe um e-mail válido.', 'introducao' );
        }

        if ( empty( $user_pass ) ) {
            $errors[] = __( 'Informe uma senha.', 'introducao' );
        }

        if ( empty( $errors ) ) {
            if ( username_exists( $user_login ) ) {
                $errors[] = __( 'Esse nome de usuário já está em uso.', 'introducao' );
            }

            if ( email_exists( $user_email ) ) {
                $errors[] = __( 'Esse e-mail já está cadastrado.', 'introducao' );
            }
        }

        if ( empty( $errors ) ) {
            $user_id = wp_create_user( $user_login, $user_pass, $user_email );

            if ( is_wp_error( $user_id ) ) {
                $errors[] = $user_id->get_error_message();
            } else {
                wp_set_current_user( $user_id );
                wp_set_auth_cookie( $user_id );
                wp_safe_redirect( $redirect_url );
                exit;
            }
        }
    } elseif ( 'login' === $action ) {
        $active_panel = 'login';

        $login_data = array(
            'user_login'    => isset( $_POST['log'] ) ? sanitize_text_field( wp_unslash( $_POST['log'] ) ) : '',
            'user_password' => isset( $_POST['pwd'] ) ? wp_unslash( $_POST['pwd'] ) : '',
            'remember'      => ! empty( $_POST['rememberme'] ),
        );

        $login_request['user_login'] = $login_data['user_login'];
        $login_request['remember']   = (bool) $login_data['remember'];

        if ( empty( $login_data['user_login'] ) ) {
            $login_errors[] = __( 'Informe seu usuário ou e-mail.', 'introducao' );
        }

        if ( empty( $login_data['user_password'] ) ) {
            $login_errors[] = __( 'Informe sua senha.', 'introducao' );
        }

        if ( empty( $login_errors ) ) {
            $user = wp_signon( $login_data, false );

            if ( is_wp_error( $user ) ) {
                $login_errors[] = $user->get_error_message();
            } else {
                wp_safe_redirect( $redirect_url );
                exit;
            }
        }
    }
}

$lost_password_url   = wp_lostpassword_url( $redirect_url );
$login_errors_id     = 'introducao-perfil-login-errors';
$register_errors_id  = 'introducao-perfil-register-errors';
$login_tab_id        = 'introducao-perfil-login-tab';
$register_tab_id     = 'introducao-perfil-register-tab';
$login_panel_id      = 'introducao-perfil-login-panel';
$register_panel_id   = 'introducao-perfil-register-panel';
$is_login_active     = 'login' === $active_panel;
$is_register_active  = 'register' === $active_panel;
?>
<div class="introducao-perfil" data-perfil-container data-active-panel="<?php echo esc_attr( $active_panel ); ?>">
    <div class="introducao-perfil__intro">
        <span class="introducao-perfil__eyebrow"><?php esc_html_e( 'Área do aluno', 'introducao' ); ?></span>
        <h1 class="introducao-perfil__title"><?php esc_html_e( 'Conecte-se à sua evolução', 'introducao' ); ?></h1>
        <p class="introducao-perfil__subtitle">
            <?php esc_html_e( 'Entre com sua conta ou crie um acesso gratuito para acompanhar simulados, desempenho e as próximas etapas dos seus estudos.', 'introducao' ); ?>
        </p>
        <ul class="introducao-perfil__highlights">
            <li><?php esc_html_e( 'Painéis inteligentes com análise de progresso em tempo real.', 'introducao' ); ?></li>
            <li><?php esc_html_e( 'Trilhas atualizadas semanalmente com foco nos principais editais.', 'introducao' ); ?></li>
            <li><?php esc_html_e( 'Suporte personalizado com especialistas da Academia.', 'introducao' ); ?></li>
        </ul>
    </div>

    <div class="introducao-perfil__switch" role="tablist" aria-label="<?php esc_attr_e( 'Escolha entre entrar ou criar uma nova conta', 'introducao' ); ?>">
        <button
            type="button"
            class="introducao-perfil__switch-button<?php echo $is_login_active ? ' is-active' : ''; ?>"
            data-perfil-toggle="login"
            role="tab"
            id="<?php echo esc_attr( $login_tab_id ); ?>"
            aria-controls="<?php echo esc_attr( $login_panel_id ); ?>"
            aria-selected="<?php echo $is_login_active ? 'true' : 'false'; ?>"
            tabindex="<?php echo $is_login_active ? '0' : '-1'; ?>"
        >
            <?php esc_html_e( 'Entrar', 'introducao' ); ?>
        </button>
        <button
            type="button"
            class="introducao-perfil__switch-button<?php echo $is_register_active ? ' is-active' : ''; ?>"
            data-perfil-toggle="register"
            role="tab"
            id="<?php echo esc_attr( $register_tab_id ); ?>"
            aria-controls="<?php echo esc_attr( $register_panel_id ); ?>"
            aria-selected="<?php echo $is_register_active ? 'true' : 'false'; ?>"
            tabindex="<?php echo $is_register_active ? '0' : '-1'; ?>"
        >
            <?php esc_html_e( 'Criar conta', 'introducao' ); ?>
        </button>
    </div>

    <div
        class="introducao-perfil__column introducao-perfil__login<?php echo $is_login_active ? ' is-active' : ''; ?>"
        id="<?php echo esc_attr( $login_panel_id ); ?>"
        role="tabpanel"
        data-perfil-panel="login"
        aria-labelledby="<?php echo esc_attr( $login_tab_id ); ?>"
        aria-hidden="<?php echo $is_login_active ? 'false' : 'true'; ?>"
    >
        <h2><?php esc_html_e( 'Já tem uma conta?', 'introducao' ); ?></h2>

        <?php if ( ! empty( $login_errors ) ) : ?>
            <div class="introducao-perfil__errors" id="<?php echo esc_attr( $login_errors_id ); ?>" role="alert" aria-live="assertive">
                <ul>
                    <?php foreach ( $login_errors as $message ) : ?>
                        <li><?php echo esc_html( $message ); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" action="<?php echo esc_url( get_permalink() ); ?>"<?php if ( ! empty( $login_errors ) ) : ?> aria-describedby="<?php echo esc_attr( $login_errors_id ); ?>"<?php endif; ?>>
            <?php wp_nonce_field( 'lae_perfil_action', 'lae_perfil_nonce' ); ?>
            <input type="hidden" name="lae_perfil_action" value="login" />

            <p>
                <label for="lae-login-user"><?php esc_html_e( 'Usuário ou e-mail', 'introducao' ); ?></label>
                <input
                    type="text"
                    id="lae-login-user"
                    name="log"
                    value="<?php echo esc_attr( $login_request['user_login'] ); ?>"
                    autocomplete="username"
                    required
                />
            </p>

            <p>
                <label for="lae-login-pass"><?php esc_html_e( 'Senha', 'introducao' ); ?></label>
                <input
                    type="password"
                    id="lae-login-pass"
                    name="pwd"
                    autocomplete="current-password"
                    required
                />
            </p>

            <p class="introducao-perfil__remember">
                <label>
                    <input type="checkbox" name="rememberme" value="forever" <?php checked( $login_request['remember'] ); ?> />
                    <?php esc_html_e( 'Lembrar de mim', 'introducao' ); ?>
                </label>
                <a class="introducao-perfil__link" href="<?php echo esc_url( $lost_password_url ); ?>">
                    <?php esc_html_e( 'Esqueci minha senha', 'introducao' ); ?>
                </a>
            </p>

            <p>
                <button type="submit" class="introducao-button introducao-button--primary">
                    <?php esc_html_e( 'Entrar', 'introducao' ); ?>
                </button>
            </p>
        </form>
    </div>

    <div
        class="introducao-perfil__column introducao-perfil__register<?php echo $is_register_active ? ' is-active' : ''; ?>"
        id="<?php echo esc_attr( $register_panel_id ); ?>"
        role="tabpanel"
        data-perfil-panel="register"
        aria-labelledby="<?php echo esc_attr( $register_tab_id ); ?>"
        aria-hidden="<?php echo $is_register_active ? 'false' : 'true'; ?>"
    >
        <h2><?php esc_html_e( 'Criar nova conta', 'introducao' ); ?></h2>

        <?php if ( ! empty( $errors ) ) : ?>
            <div class="introducao-perfil__errors" id="<?php echo esc_attr( $register_errors_id ); ?>" role="alert" aria-live="assertive">
                <ul>
                    <?php foreach ( $errors as $message ) : ?>
                        <li><?php echo esc_html( $message ); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" action="<?php echo esc_url( get_permalink() ); ?>"<?php if ( ! empty( $errors ) ) : ?> aria-describedby="<?php echo esc_attr( $register_errors_id ); ?>"<?php endif; ?>>
            <?php wp_nonce_field( 'lae_perfil_action', 'lae_perfil_nonce' ); ?>
            <input type="hidden" name="lae_perfil_action" value="register" />

            <p>
                <label for="lae-register-user"><?php esc_html_e( 'Nome de usuário', 'introducao' ); ?></label>
                <input
                    type="text"
                    id="lae-register-user"
                    name="lae_user_login"
                    value="<?php echo esc_attr( $registration_request['user_login'] ); ?>"
                    autocomplete="username"
                    required
                />
            </p>

            <p>
                <label for="lae-register-email"><?php esc_html_e( 'E-mail', 'introducao' ); ?></label>
                <input
                    type="email"
                    id="lae-register-email"
                    name="lae_user_email"
                    value="<?php echo esc_attr( $registration_request['user_email'] ); ?>"
                    autocomplete="email"
                    required
                />
            </p>

            <p>
                <label for="lae-register-pass"><?php esc_html_e( 'Senha', 'introducao' ); ?></label>
                <input
                    type="password"
                    id="lae-register-pass"
                    name="lae_user_pass"
                    autocomplete="new-password"
                    required
                />
            </p>

            <?php $password_hint = wp_get_password_hint(); ?>
            <?php if ( $password_hint ) : ?>
                <p class="introducao-perfil__hint"><?php echo wp_kses_post( $password_hint ); ?></p>
            <?php endif; ?>

            <p>
                <button type="submit" class="introducao-button introducao-button--secondary">
                    <?php esc_html_e( 'Criar conta', 'introducao' ); ?>
                </button>
            </p>
        </form>
    </div>
</div>
