<?php
/**
 * Template para o shortcode de perfil, exibindo formulários de login e registro.
 *
 * @var string $shortcode_tag Tag do shortcode invocada.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$redirect_url         = home_url( '/questoes-de-concursos/' );
$register_errors      = array();
$login_errors         = array();
$active_panel         = 'login';
$requested_tab        = isset( $_GET['perfil_tab'] ) ? sanitize_key( wp_unslash( $_GET['perfil_tab'] ) ) : '';
$login_request        = array(
    'user_login' => '',
    'remember'   => false,
);
$registration_request = array(
    'user_login' => '',
    'user_email' => '',
);
$login_otp_state      = array(
    'challenge' => '',
    'message'   => '',
    'email'     => '',
);
$register_otp_state   = array(
    'challenge' => '',
    'message'   => '',
    'email'     => '',
);

if ( in_array( $requested_tab, array( 'login', 'register' ), true ) ) {
    $active_panel = $requested_tab;
}

if ( is_user_logged_in() ) {
    wp_safe_redirect( $redirect_url );
    exit;
}

if ( 'POST' === $_SERVER['REQUEST_METHOD'] && isset( $_POST['lae_perfil_nonce'] ) && wp_verify_nonce( wp_unslash( $_POST['lae_perfil_nonce'] ), 'lae_perfil_action' ) ) {
    $action       = isset( $_POST['lae_perfil_action'] ) ? sanitize_text_field( wp_unslash( $_POST['lae_perfil_action'] ) ) : '';
    $challenge_id = isset( $_POST['lae_otp_challenge'] ) ? sanitize_text_field( wp_unslash( $_POST['lae_otp_challenge'] ) ) : '';

    if ( 'register' === $action ) {
        $active_panel = 'register';

        if ( $challenge_id ) {
            $challenge_data = Introducao_Auth::get_challenge( $challenge_id );

            if ( $challenge_data ) {
                $register_otp_state = array(
                    'challenge' => $challenge_id,
                    'email'     => isset( $challenge_data['user_email'] ) ? $challenge_data['user_email'] : '',
                    'message'   => sprintf(
                        __( 'Enviamos um código de ativação para %s.', 'introducao' ),
                        Introducao_Auth::mask_email( isset( $challenge_data['user_email'] ) ? $challenge_data['user_email'] : '' )
                    ),
                );
                $registration_request['user_login'] = isset( $challenge_data['pending_user']['user_login'] ) ? $challenge_data['pending_user']['user_login'] : '';
                $registration_request['user_email'] = isset( $challenge_data['pending_user']['user_email'] ) ? $challenge_data['pending_user']['user_email'] : '';
            }

            $otp_code    = isset( $_POST['lae_otp_code'] ) ? sanitize_text_field( wp_unslash( $_POST['lae_otp_code'] ) ) : '';
            $verification = Introducao_Auth::verify_code( $challenge_id, $otp_code );

            if ( is_wp_error( $verification ) ) {
                $register_errors[] = $verification->get_error_message();

                if ( in_array( $verification->get_error_code(), array( 'introducao_otp_invalid', 'introducao_otp_required' ), true ) ) {
                    $challenge_data = Introducao_Auth::get_challenge( $challenge_id );

                    if ( $challenge_data ) {
                        $register_otp_state['message'] = sprintf(
                            __( 'Enviamos um código de ativação para %s.', 'introducao' ),
                            Introducao_Auth::mask_email( isset( $challenge_data['user_email'] ) ? $challenge_data['user_email'] : '' )
                        );
                    }
                } else {
                    $register_otp_state = array(
                        'challenge' => '',
                        'message'   => '',
                        'email'     => '',
                    );
                }
            } else {
                $pending_user = isset( $verification['pending_user'] ) ? $verification['pending_user'] : array();
                $user_login   = isset( $pending_user['user_login'] ) ? $pending_user['user_login'] : '';
                $user_email   = isset( $pending_user['user_email'] ) ? $pending_user['user_email'] : '';
                $user_pass    = isset( $pending_user['user_pass'] ) ? $pending_user['user_pass'] : '';

                if ( empty( $user_login ) || empty( $user_email ) || empty( $user_pass ) ) {
                    $register_errors[] = __( 'Dados de cadastro incompletos. Inicie novamente o processo.', 'introducao' );
                    $register_otp_state = array(
                        'challenge' => '',
                        'message'   => '',
                        'email'     => '',
                    );
                } elseif ( username_exists( $user_login ) ) {
                    $register_errors[] = __( 'Esse nome de usuário já está em uso. Faça login ou escolha outro.', 'introducao' );
                    $register_otp_state = array(
                        'challenge' => '',
                        'message'   => '',
                        'email'     => '',
                    );
                } elseif ( email_exists( $user_email ) ) {
                    $register_errors[] = __( 'Esse e-mail já possui cadastro. Faça login para continuar.', 'introducao' );
                    $register_otp_state = array(
                        'challenge' => '',
                        'message'   => '',
                        'email'     => '',
                    );
                } else {
                    $user_id = wp_create_user( $user_login, $user_pass, $user_email );

                    if ( is_wp_error( $user_id ) ) {
                        $register_errors[] = $user_id->get_error_message();
                        $register_otp_state = array(
                            'challenge' => '',
                            'message'   => '',
                            'email'     => '',
                        );
                    } else {
                        wp_set_current_user( $user_id );
                        wp_set_auth_cookie( $user_id );
                        wp_safe_redirect( $redirect_url );
                        exit;
                    }
                }
            }
        } else {
            $user_login = isset( $_POST['lae_user_login'] ) ? sanitize_user( wp_unslash( $_POST['lae_user_login'] ), true ) : '';
            $user_email = isset( $_POST['lae_user_email'] ) ? sanitize_email( wp_unslash( $_POST['lae_user_email'] ) ) : '';
            $user_pass  = isset( $_POST['lae_user_pass'] ) ? wp_unslash( $_POST['lae_user_pass'] ) : '';

            $registration_request['user_login'] = $user_login;
            $registration_request['user_email'] = $user_email;

            if ( empty( $user_login ) ) {
                $register_errors[] = __( 'Informe um nome de usuário.', 'introducao' );
            }

            if ( empty( $user_email ) || ! is_email( $user_email ) ) {
                $register_errors[] = __( 'Informe um e-mail válido.', 'introducao' );
            }

            if ( empty( $user_pass ) ) {
                $register_errors[] = __( 'Informe uma senha.', 'introducao' );
            }

            if ( empty( $register_errors ) ) {
                if ( username_exists( $user_login ) ) {
                    $register_errors[] = __( 'Esse nome de usuário já está em uso.', 'introducao' );
                }

                if ( email_exists( $user_email ) ) {
                    $register_errors[] = __( 'Esse e-mail já está cadastrado.', 'introducao' );
                }
            }

            if ( empty( $register_errors ) ) {
                $challenge = Introducao_Auth::create_register_challenge( $user_login, $user_email, $user_pass );

                if ( is_wp_error( $challenge ) ) {
                    $register_errors[] = $challenge->get_error_message();
                } else {
                    $register_otp_state = array(
                        'challenge' => $challenge['challenge_id'],
                        'email'     => $challenge['email'],
                        'message'   => sprintf(
                            __( 'Enviamos um código de ativação para %s. Informe-o abaixo para concluir seu cadastro.', 'introducao' ),
                            Introducao_Auth::mask_email( $challenge['email'] )
                        ),
                    );
                }
            }
        }
    } elseif ( 'login' === $action ) {
        $active_panel = 'login';

        if ( $challenge_id ) {
            $challenge_data = Introducao_Auth::get_challenge( $challenge_id );

            if ( $challenge_data ) {
                $login_otp_state = array(
                    'challenge' => $challenge_id,
                    'email'     => isset( $challenge_data['user_email'] ) ? $challenge_data['user_email'] : '',
                    'message'   => sprintf(
                        __( 'Enviamos um código de acesso para %s.', 'introducao' ),
                        Introducao_Auth::mask_email( isset( $challenge_data['user_email'] ) ? $challenge_data['user_email'] : '' )
                    ),
                );
                if ( isset( $challenge_data['submitted_login'] ) ) {
                    $login_request['user_login'] = $challenge_data['submitted_login'];
                } elseif ( isset( $challenge_data['user_login'] ) ) {
                    $login_request['user_login'] = $challenge_data['user_login'];
                }
            } elseif ( isset( $_POST['log'] ) ) {
                $login_request['user_login'] = sanitize_text_field( wp_unslash( $_POST['log'] ) );
            }

            $otp_code     = isset( $_POST['lae_otp_code'] ) ? sanitize_text_field( wp_unslash( $_POST['lae_otp_code'] ) ) : '';
            $verification = Introducao_Auth::verify_code( $challenge_id, $otp_code );

            if ( is_wp_error( $verification ) ) {
                $login_errors[] = $verification->get_error_message();

                if ( in_array( $verification->get_error_code(), array( 'introducao_otp_invalid', 'introducao_otp_required' ), true ) ) {
                    $challenge_data = Introducao_Auth::get_challenge( $challenge_id );

                    if ( $challenge_data ) {
                        $login_otp_state['message'] = sprintf(
                            __( 'Enviamos um código de acesso para %s.', 'introducao' ),
                            Introducao_Auth::mask_email( isset( $challenge_data['user_email'] ) ? $challenge_data['user_email'] : '' )
                        );
                    }
                } else {
                    $login_otp_state = array(
                        'challenge' => '',
                        'message'   => '',
                        'email'     => '',
                    );
                }
            } else {
                $user_id  = isset( $verification['user_id'] ) ? (int) $verification['user_id'] : 0;
                $remember = ! empty( $verification['remember'] );

                if ( $user_id && get_user_by( 'id', $user_id ) ) {
                    wp_set_current_user( $user_id );
                    wp_set_auth_cookie( $user_id, $remember );
                    wp_safe_redirect( $redirect_url );
                    exit;
                } else {
                    $login_errors[] = __( 'Não foi possível completar o login. Solicite um novo código.', 'introducao' );
                }
            }
        } else {
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
                $user = wp_authenticate( $login_data['user_login'], $login_data['user_password'] );

                if ( is_wp_error( $user ) ) {
                    $login_errors[] = $user->get_error_message();
                } else {
                    $challenge = Introducao_Auth::create_login_challenge( $user, $login_data['remember'], $login_data['user_login'] );

                    if ( is_wp_error( $challenge ) ) {
                        $login_errors[] = $challenge->get_error_message();
                    } else {
                        $login_otp_state = array(
                            'challenge' => $challenge['challenge_id'],
                            'email'     => $challenge['email'],
                            'message'   => sprintf(
                                __( 'Enviamos um código de acesso para %s. Digite-o para concluir a entrada.', 'introducao' ),
                                Introducao_Auth::mask_email( $challenge['email'] )
                            ),
                        );
                    }
                }
            }
        }
    }
}

$lost_password_url  = wp_lostpassword_url( $redirect_url );
$login_errors_id    = 'introducao-perfil-login-errors';
$register_errors_id = 'introducao-perfil-register-errors';
$login_tab_id       = 'introducao-perfil-login-tab';
$register_tab_id    = 'introducao-perfil-register-tab';
$login_panel_id     = 'introducao-perfil-login-panel';
$register_panel_id  = 'introducao-perfil-register-panel';
$is_login_active    = 'login' === $active_panel;
$is_register_active = 'register' === $active_panel;

$login_has_otp    = ! empty( $login_otp_state['challenge'] );
$register_has_otp = ! empty( $register_otp_state['challenge'] );
?>
<div
    class="introducao-perfil"
    data-perfil-container
    data-active-panel="<?php echo esc_attr( $active_panel ); ?>"
    data-login-otp="<?php echo $login_has_otp ? 'true' : 'false'; ?>"
    data-register-otp="<?php echo $register_has_otp ? 'true' : 'false'; ?>"
>
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
        class="introducao-perfil__column introducao-perfil__login<?php echo $is_login_active ? ' is-active' : ''; ?><?php echo $login_has_otp ? ' has-otp' : ''; ?>"
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

        <?php if ( $login_has_otp && ! empty( $login_otp_state['message'] ) ) : ?>
            <div class="introducao-perfil__otp">
                <h3><?php esc_html_e( 'Verificação em duas etapas', 'introducao' ); ?></h3>
                <p><?php echo esc_html( $login_otp_state['message'] ); ?></p>
            </div>
        <?php endif; ?>

        <form method="post" action="<?php echo esc_url( get_permalink() ); ?>"<?php if ( ! empty( $login_errors ) ) : ?> aria-describedby="<?php echo esc_attr( $login_errors_id ); ?>"<?php endif; ?>>
            <?php wp_nonce_field( 'lae_perfil_action', 'lae_perfil_nonce' ); ?>
            <input type="hidden" name="lae_perfil_action" value="login" />

            <?php if ( $login_has_otp ) : ?>
                <input type="hidden" name="lae_otp_challenge" value="<?php echo esc_attr( $login_otp_state['challenge'] ); ?>" />
            <?php endif; ?>

            <p>
                <label for="lae-login-user"><?php esc_html_e( 'Usuário ou e-mail', 'introducao' ); ?></label>
                <input
                    type="text"
                    id="lae-login-user"
                    name="log"
                    value="<?php echo esc_attr( $login_request['user_login'] ); ?>"
                    autocomplete="username"
                    <?php echo $login_has_otp ? 'readonly' : 'required'; ?>
                />
            </p>

            <?php if ( ! $login_has_otp ) : ?>
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

                <p class="introducao-perfil__options">
                    <label class="introducao-perfil__remember">
                        <input type="checkbox" name="rememberme" value="forever" <?php checked( $login_request['remember'] ); ?> />
                        <span><?php esc_html_e( 'Manter conectado', 'introducao' ); ?></span>
                    </label>
                    <a href="<?php echo esc_url( $lost_password_url ); ?>" class="introducao-perfil__link"><?php esc_html_e( 'Esqueci minha senha', 'introducao' ); ?></a>
                </p>
            <?php endif; ?>

            <?php if ( $login_has_otp ) : ?>
                <p>
                    <label for="lae-login-otp"><?php esc_html_e( 'Código de verificação', 'introducao' ); ?></label>
                    <input
                        type="text"
                        id="lae-login-otp"
                        name="lae_otp_code"
                        inputmode="numeric"
                        pattern="[0-9]*"
                        maxlength="6"
                        data-perfil-otp-focus="true"
                        required
                    />
                    <small class="introducao-perfil__hint"><?php esc_html_e( 'O código tem 6 dígitos e expira em poucos minutos.', 'introducao' ); ?></small>
                </p>
            <?php endif; ?>

            <p class="introducao-perfil__actions">
                <button type="submit" class="introducao-perfil__submit">
                    <?php echo $login_has_otp ? esc_html__( 'Validar código e entrar', 'introducao' ) : esc_html__( 'Continuar com segurança', 'introducao' ); ?>
                </button>
            </p>
        </form>
    </div>

    <div
        class="introducao-perfil__column introducao-perfil__register<?php echo $is_register_active ? ' is-active' : ''; ?><?php echo $register_has_otp ? ' has-otp' : ''; ?>"
        id="<?php echo esc_attr( $register_panel_id ); ?>"
        role="tabpanel"
        data-perfil-panel="register"
        aria-labelledby="<?php echo esc_attr( $register_tab_id ); ?>"
        aria-hidden="<?php echo $is_register_active ? 'false' : 'true'; ?>"
    >
        <h2><?php esc_html_e( 'Comece agora', 'introducao' ); ?></h2>

        <?php if ( ! empty( $register_errors ) ) : ?>
            <div class="introducao-perfil__errors" id="<?php echo esc_attr( $register_errors_id ); ?>" role="alert" aria-live="assertive">
                <ul>
                    <?php foreach ( $register_errors as $message ) : ?>
                        <li><?php echo esc_html( $message ); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ( $register_has_otp && ! empty( $register_otp_state['message'] ) ) : ?>
            <div class="introducao-perfil__otp">
                <h3><?php esc_html_e( 'Confirmação em duas etapas', 'introducao' ); ?></h3>
                <p><?php echo esc_html( $register_otp_state['message'] ); ?></p>
            </div>
        <?php endif; ?>

        <form method="post" action="<?php echo esc_url( get_permalink() ); ?>"<?php if ( ! empty( $register_errors ) ) : ?> aria-describedby="<?php echo esc_attr( $register_errors_id ); ?>"<?php endif; ?>>
            <?php wp_nonce_field( 'lae_perfil_action', 'lae_perfil_nonce' ); ?>
            <input type="hidden" name="lae_perfil_action" value="register" />

            <?php if ( $register_has_otp ) : ?>
                <input type="hidden" name="lae_otp_challenge" value="<?php echo esc_attr( $register_otp_state['challenge'] ); ?>" />
            <?php endif; ?>

            <p>
                <label for="lae-register-user"><?php esc_html_e( 'Nome de usuário', 'introducao' ); ?></label>
                <input
                    type="text"
                    id="lae-register-user"
                    name="lae_user_login"
                    value="<?php echo esc_attr( $registration_request['user_login'] ); ?>"
                    autocomplete="username"
                    <?php echo $register_has_otp ? 'readonly' : 'required'; ?>
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
                    <?php echo $register_has_otp ? 'readonly' : 'required'; ?>
                />
            </p>

            <?php if ( ! $register_has_otp ) : ?>
                <p>
                    <label for="lae-register-pass"><?php esc_html_e( 'Senha', 'introducao' ); ?></label>
                    <input
                        type="password"
                        id="lae-register-pass"
                        name="lae_user_pass"
                        autocomplete="new-password"
                        required
                    />
                    <small class="introducao-perfil__hint"><?php esc_html_e( 'Use ao menos 8 caracteres com letras, números e símbolos.', 'introducao' ); ?></small>
                </p>
            <?php endif; ?>

            <?php if ( $register_has_otp ) : ?>
                <p>
                    <label for="lae-register-otp"><?php esc_html_e( 'Código de confirmação', 'introducao' ); ?></label>
                    <input
                        type="text"
                        id="lae-register-otp"
                        name="lae_otp_code"
                        inputmode="numeric"
                        pattern="[0-9]*"
                        maxlength="6"
                        data-perfil-otp-focus="true"
                        required
                    />
                    <small class="introducao-perfil__hint"><?php esc_html_e( 'O código tem 6 dígitos e expira rapidamente por segurança.', 'introducao' ); ?></small>
                </p>
            <?php endif; ?>

            <p class="introducao-perfil__actions">
                <button type="submit" class="introducao-perfil__submit">
                    <?php echo $register_has_otp ? esc_html__( 'Confirmar código e criar conta', 'introducao' ) : esc_html__( 'Criar conta gratuita', 'introducao' ); ?>
                </button>
            </p>
        </form>
    </div>
</div>
