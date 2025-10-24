<?php
/**
 * Template para o shortcode de perfil, exibindo formulários de login e registro.
 *
 * @var string $shortcode_tag Tag do shortcode invocada.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'introducao_perfil_redirect' ) ) {
    /**
     * Realiza o redirecionamento garantindo fallback mesmo se os headers já tiverem sido enviados.
     *
     * @param string $url Destino desejado.
     */
    function introducao_perfil_redirect( $url ) {
        $safe_url = esc_url_raw( $url );

        if ( empty( $safe_url ) ) {
            $safe_url = home_url();
        }

        wp_safe_redirect( $safe_url );

        if ( headers_sent() ) {
            printf(
                '<script>window.location.replace(%1$s);</script><noscript><meta http-equiv="refresh" content="0;url=%2$s"></noscript>',
                wp_json_encode( $safe_url ),
                esc_attr( $safe_url )
            );
        }

        exit;
    }
}

if ( ! function_exists( 'introducao_perfil_prepare_otp_state' ) ) {
    /**
     * Atualiza metadados de controle para blocos de confirmação OTP.
     *
     * @param string $challenge_id Identificador do desafio atual.
     * @param array  $state        Estado parcial do desafio.
     *
     * @return array
     */
    function introducao_perfil_prepare_otp_state( $challenge_id, $state ) {
        $state['resend_in'] = Introducao_Auth::seconds_until_resend( $challenge_id );
        $state['ttl']       = Introducao_Auth::seconds_until_expiration( $challenge_id );
        $state['ttl_text']  = $state['ttl'] > 0
            ? sprintf( __( 'O código expira em %s.', 'introducao' ), Introducao_Auth::format_duration( $state['ttl'] ) )
            : '';

        return $state;
    }
}

if ( ! function_exists( 'introducao_perfil_clean_error' ) ) {
    /**
     * Remove marcações HTML de mensagens de erro para evitar exibição duplicada.
     *
     * @param string|WP_Error $error Mensagem original ou objeto de erro.
     *
     * @return string Mensagem sanitizada pronta para exibição.
     */
    function introducao_perfil_clean_error( $error ) {
        if ( class_exists( 'Introducao_Auth' ) && method_exists( 'Introducao_Auth', 'clean_auth_error' ) ) {
            return Introducao_Auth::clean_auth_error( $error );
        }

        if ( $error instanceof WP_Error ) {
            $error = $error->get_error_message();
        }

        if ( ! is_string( $error ) || '' === $error ) {
            return '';
        }

        return trim( wp_strip_all_tags( wp_specialchars_decode( $error ) ) );
    }
}

if ( ! function_exists( 'introducao_perfil_normalize_error' ) ) {
    /**
     * Normaliza mensagens de erro conhecidas para remover links auxiliares e garantir textos diretos.
     *
     * @param string|WP_Error $error Mensagem original ou objeto de erro.
     *
     * @return string Mensagem ajustada pronta para exibição.
     */
    function introducao_perfil_normalize_error( $error ) {
        if ( class_exists( 'Introducao_Auth' ) && method_exists( 'Introducao_Auth', 'normalize_auth_error' ) ) {
            return Introducao_Auth::normalize_auth_error( $error );
        }

        $message = introducao_perfil_clean_error( $error );

        if ( '' === $message ) {
            return '';
        }

        $code = '';

        if ( $error instanceof WP_Error ) {
            $code = $error->get_error_code();

            if ( ! $code ) {
                $codes = $error->get_error_codes();

                if ( ! empty( $codes ) ) {
                    $code = reset( $codes );
                }
            }
        }

        switch ( $code ) {
            case 'incorrect_password':
                return __( 'A senha informada está incorreta.', 'introducao' );
            case 'invalid_email':
                return __( 'Não encontramos uma conta com esse e-mail.', 'introducao' );
            case 'invalid_username':
            case 'invalidcombo':
                return __( 'Não encontramos uma conta com essas credenciais.', 'introducao' );
        }

        $message = preg_replace(
            '/\s*(Perdeu a senha\?|Esqueceu sua senha\?|Lost your password\?|Registre-se.*|Cadastre-se.*|Register.*)$/iu',
            '',
            $message
        );

        return trim( $message );
    }
}

if ( ! function_exists( 'introducao_perfil_hint_id' ) ) {
    /**
     * Gera identificadores únicos para associações de aria-describedby em campos do formulário.
     *
     * @param string $key Rótulo de referência para o campo atual.
     *
     * @return string Identificador único e reutilizável durante a requisição.
     */
    function introducao_perfil_hint_id( $key ) {
        static $generated = array();

        $normalized = preg_replace( '/[^a-z0-9_-]+/i', '-', strtolower( (string) $key ) );

        if ( '' === $normalized ) {
            $normalized = 'field';
        }

        if ( isset( $generated[ $normalized ] ) ) {
            return $generated[ $normalized ];
        }

        $prefix = 'introducao-hint-' . $normalized . '-';

        $generated[ $normalized ] = function_exists( 'wp_unique_id' )
            ? wp_unique_id( $prefix )
            : uniqid( $prefix );

        return $generated[ $normalized ];
    }
}

if ( ! function_exists( 'introducao_perfil_apply_pending_meta' ) ) {
    /**
     * Aplica metadados pendentes ao usuário recém-criado.
     *
     * @param int   $user_id ID do usuário.
     * @param array $meta    Metadados sanitizados.
     */
    function introducao_perfil_apply_pending_meta( $user_id, $meta ) {
        if ( empty( $user_id ) || empty( $meta ) || ! is_array( $meta ) ) {
            return;
        }

        if ( isset( $meta['first_name'] ) && '' !== $meta['first_name'] ) {
            update_user_meta( $user_id, 'first_name', sanitize_text_field( $meta['first_name'] ) );
        }

        if ( isset( $meta['last_name'] ) && '' !== $meta['last_name'] ) {
            update_user_meta( $user_id, 'last_name', sanitize_text_field( $meta['last_name'] ) );
        }

        if ( isset( $meta['display_name'] ) && '' !== $meta['display_name'] ) {
            wp_update_user(
                array(
                    'ID'           => $user_id,
                    'display_name' => sanitize_text_field( $meta['display_name'] ),
                )
            );
        }
    }
}

if ( ! function_exists( 'introducao_perfil_resolve_redirect' ) ) {
    /**
     * Resolve a URL de redirecionamento considerando contexto compartilhado.
     *
     * @param array  $context Dados adicionais armazenados no desafio.
     * @param string $fallback URL padrão.
     *
     * @return string
     */
    function introducao_perfil_resolve_redirect( $context, $fallback ) {
        $target = '';

        if ( is_array( $context ) ) {
            if ( empty( $target ) && ! empty( $context['redirect'] ) ) {
                $target = esc_url_raw( $context['redirect'] );
            }

            if ( empty( $target ) && ! empty( $context['redirect_to'] ) ) {
                $target = esc_url_raw( $context['redirect_to'] );
            }
        }

        if ( empty( $target ) ) {
            $target = $fallback;
        }

        return $target;
    }
}

$redirect_url = isset( $redirect_url ) ? $redirect_url : '';

if ( $redirect_url ) {
    $redirect_url = esc_url_raw( $redirect_url );
}

if ( empty( $redirect_url ) ) {
    $redirect_url = home_url( '/questoes-de-concursos/' );
}
$register_errors      = array();
$login_errors         = array();
$form_action_url      = '';
$context_slug         = isset( $render_context ) && $render_context ? sanitize_html_class( $render_context ) : 'page';
$is_modal_context     = ( 'modal' === $context_slug );
$component_prefix     = wp_unique_id( 'introducao-perfil-' . ( $context_slug ? $context_slug . '-' : '' ) );

if ( isset( $form_action ) && $form_action ) {
    $form_action_url = esc_url_raw( $form_action );
}

if ( ! $form_action_url && function_exists( 'get_permalink' ) ) {
    $maybe_permalink = get_permalink();

    if ( $maybe_permalink ) {
        $form_action_url = $maybe_permalink;
    }
}

if ( ! $form_action_url ) {
    $form_action_url = home_url( user_trailingslashit( 'dados-conta' ) );
}
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
    'resend_in' => 0,
    'ttl'       => 0,
    'ttl_text'  => '',
);
$register_otp_state   = array(
    'challenge' => '',
    'message'   => '',
    'email'     => '',
    'resend_in' => 0,
    'ttl'       => 0,
    'ttl_text'  => '',
);

if ( in_array( $requested_tab, array( 'login', 'register' ), true ) ) {
    $active_panel = $requested_tab;
}

$current_user = wp_get_current_user();
$is_logged    = $current_user instanceof WP_User && $current_user->exists();

if ( $is_logged ) {
    $display_name   = $current_user->display_name ? $current_user->display_name : $current_user->user_login;
    $first_name     = get_user_meta( $current_user->ID, 'first_name', true );
    $greeting_name  = $first_name ? $first_name : $display_name;
    $charset        = get_bloginfo( 'charset' );
    $initial        = '';
    $account_email  = $current_user->user_email;
    $registration   = $current_user->user_registered ? mysql2date( get_option( 'date_format' ), $current_user->user_registered ) : '';
    $last_login     = '';
    $last_login_raw = '';
    $last_login_keys = array( 'last_login_at', 'last_login', 'last_activity', 'wc_last_active' );

    if ( $greeting_name ) {
        $first_char = function_exists( 'mb_substr' ) ? mb_substr( $greeting_name, 0, 1, $charset ) : substr( $greeting_name, 0, 1 );
        $initial    = function_exists( 'mb_strtoupper' ) ? mb_strtoupper( $first_char, $charset ) : strtoupper( $first_char );
    }

    foreach ( $last_login_keys as $meta_key ) {
        $value = get_user_meta( $current_user->ID, $meta_key, true );

        if ( '' !== $value && null !== $value ) {
            $last_login_raw = $value;
            break;
        }
    }

    if ( $last_login_raw ) {
        if ( is_numeric( $last_login_raw ) ) {
            $timestamp = (int) $last_login_raw;
        } else {
            $timestamp = strtotime( $last_login_raw );
        }

        if ( $timestamp ) {
            $last_login = date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $timestamp );
        }
    }

    $two_factor_enabled  = class_exists( 'Introducao_Auth' ) ? Introducao_Auth::should_require_two_factor( $current_user ) : false;
    $two_factor_label    = $two_factor_enabled ? __( 'Ativada', 'introducao' ) : __( 'Desativada', 'introducao' );
    $two_factor_last_sent = get_user_meta( $current_user->ID, 'lae_two_factor_last_sent', true );

    if ( $two_factor_last_sent ) {
        if ( is_numeric( $two_factor_last_sent ) ) {
            $two_factor_time = (int) $two_factor_last_sent;
        } else {
            $two_factor_time = strtotime( $two_factor_last_sent );
        }

        if ( $two_factor_time ) {
            $two_factor_last_sent = date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $two_factor_time );
        }
    }

    $avatar_url = get_avatar_url( $current_user->ID, array( 'size' => 160 ) );
    $edit_link  = get_edit_user_link( $current_user->ID );
    $logout_url = wp_logout_url( $form_action_url );
}

if ( ! $is_logged ) {
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
                $register_otp_state = introducao_perfil_prepare_otp_state( $challenge_id, $register_otp_state );
                $registration_request['user_login'] = isset( $challenge_data['pending_user']['user_login'] ) ? $challenge_data['pending_user']['user_login'] : '';
                $registration_request['user_email'] = isset( $challenge_data['pending_user']['user_email'] ) ? $challenge_data['pending_user']['user_email'] : '';
            }

            $otp_code    = '';

            if ( isset( $_POST['lae_otp_code'] ) ) {
                $otp_code = sanitize_text_field( wp_unslash( $_POST['lae_otp_code'] ) );
            } elseif ( isset( $_POST['code'] ) ) {
                $otp_code = sanitize_text_field( wp_unslash( $_POST['code'] ) );
            }
            $verification = Introducao_Auth::verify_code( $challenge_id, $otp_code );

            if ( is_wp_error( $verification ) ) {
                $message = introducao_perfil_clean_error( $verification );

                if ( $message ) {
                    $register_errors[] = $message;
                }

                if ( in_array( $verification->get_error_code(), array( 'introducao_otp_invalid', 'introducao_otp_required' ), true ) ) {
                    $challenge_data = Introducao_Auth::get_challenge( $challenge_id );

                    if ( $challenge_data ) {
                        $register_otp_state['message'] = sprintf(
                            __( 'Enviamos um código de ativação para %s.', 'introducao' ),
                            Introducao_Auth::mask_email( isset( $challenge_data['user_email'] ) ? $challenge_data['user_email'] : '' )
                        );
                        $register_otp_state = introducao_perfil_prepare_otp_state( $challenge_id, $register_otp_state );
                    }
                } else {
                    $register_otp_state = array(
                        'challenge' => '',
                        'message'   => '',
                        'email'     => '',
                        'resend_in' => 0,
                        'ttl'       => 0,
                        'ttl_text'  => '',
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
                        'resend_in' => 0,
                        'ttl'       => 0,
                        'ttl_text'  => '',
                    );
                } elseif ( username_exists( $user_login ) ) {
                    $register_errors[] = __( 'Esse nome de usuário já está em uso. Faça login ou escolha outro.', 'introducao' );
                    $register_otp_state = array(
                        'challenge' => '',
                        'message'   => '',
                        'email'     => '',
                        'resend_in' => 0,
                        'ttl'       => 0,
                        'ttl_text'  => '',
                    );
                } elseif ( email_exists( $user_email ) ) {
                    $register_errors[] = __( 'Esse e-mail já possui cadastro. Faça login para continuar.', 'introducao' );
                    $register_otp_state = array(
                        'challenge' => '',
                        'message'   => '',
                        'email'     => '',
                        'resend_in' => 0,
                        'ttl'       => 0,
                        'ttl_text'  => '',
                    );
                } else {
                    $context         = isset( $verification['context'] ) && is_array( $verification['context'] ) ? $verification['context'] : array();
                    $redirect_target = introducao_perfil_resolve_redirect( $context, $redirect_url );
                    $pending_meta    = isset( $verification['pending_meta'] ) && is_array( $verification['pending_meta'] ) ? $verification['pending_meta'] : array();
                    $remember        = isset( $verification['remember'] ) ? (bool) $verification['remember'] : false;

                    $user_id = wp_create_user( $user_login, $user_pass, $user_email );

                    if ( is_wp_error( $user_id ) ) {
                        $message = introducao_perfil_normalize_error( $user_id );

                        if ( $message ) {
                            $register_errors[] = $message;
                        }
                        $register_otp_state = array(
                            'challenge' => '',
                            'message'   => '',
                            'email'     => '',
                            'resend_in' => 0,
                            'ttl'       => 0,
                            'ttl_text'  => '',
                        );
                    } else {
                        if ( ! empty( $pending_meta ) ) {
                            introducao_perfil_apply_pending_meta( $user_id, $pending_meta );
                        }

                        wp_set_current_user( $user_id );
                        wp_set_auth_cookie( $user_id, $remember );

                        $new_user = get_user_by( 'id', $user_id );

                        if ( $new_user instanceof WP_User ) {
                            if ( class_exists( 'Introducao_Auth' ) ) {
                                Introducao_Auth::set_login_context(
                                    array(
                                        'source'   => 'introducao',
                                        'method'   => 'register',
                                        'redirect' => $redirect_target,
                                    )
                                );
                            }

                            do_action( 'wp_login', $new_user->user_login, $new_user );
                        }

                        introducao_perfil_redirect( $redirect_target );
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
                $challenge = Introducao_Auth::create_register_challenge(
                    $user_login,
                    $user_email,
                    $user_pass,
                    array(),
                    array(
                        'source'      => 'introducao',
                        'redirect_to' => $redirect_url,
                    )
                );

                if ( is_wp_error( $challenge ) ) {
                    $message = introducao_perfil_clean_error( $challenge );

                    if ( $message ) {
                        $register_errors[] = $message;
                    }
                } else {
                    $register_otp_state = array(
                        'challenge' => $challenge['challenge_id'],
                        'email'     => $challenge['email'],
                        'message'   => sprintf(
                            __( 'Enviamos um código de ativação para %s. Informe-o abaixo para concluir seu cadastro.', 'introducao' ),
                            Introducao_Auth::mask_email( $challenge['email'] )
                        ),
                    );
                    $register_otp_state = introducao_perfil_prepare_otp_state( $challenge['challenge_id'], $register_otp_state );
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
                $login_otp_state = introducao_perfil_prepare_otp_state( $challenge_id, $login_otp_state );
                if ( isset( $challenge_data['submitted_login'] ) ) {
                    $login_request['user_login'] = $challenge_data['submitted_login'];
                } elseif ( isset( $challenge_data['user_login'] ) ) {
                    $login_request['user_login'] = $challenge_data['user_login'];
                }
            } elseif ( isset( $_POST['log'] ) ) {
                $login_request['user_login'] = sanitize_text_field( wp_unslash( $_POST['log'] ) );
            }

            $otp_code     = '';

            if ( isset( $_POST['lae_otp_code'] ) ) {
                $otp_code = sanitize_text_field( wp_unslash( $_POST['lae_otp_code'] ) );
            } elseif ( isset( $_POST['code'] ) ) {
                $otp_code = sanitize_text_field( wp_unslash( $_POST['code'] ) );
            }
            $verification = Introducao_Auth::verify_code( $challenge_id, $otp_code );

            if ( is_wp_error( $verification ) ) {
                $message = introducao_perfil_clean_error( $verification );

                if ( $message ) {
                    $login_errors[] = $message;
                }

                if ( in_array( $verification->get_error_code(), array( 'introducao_otp_invalid', 'introducao_otp_required' ), true ) ) {
                    $challenge_data = Introducao_Auth::get_challenge( $challenge_id );

                    if ( $challenge_data ) {
                        $login_otp_state['message'] = sprintf(
                            __( 'Enviamos um código de acesso para %s.', 'introducao' ),
                            Introducao_Auth::mask_email( isset( $challenge_data['user_email'] ) ? $challenge_data['user_email'] : '' )
                        );
                        $login_otp_state = introducao_perfil_prepare_otp_state( $challenge_id, $login_otp_state );
                    }
                } else {
                    $login_otp_state = array(
                        'challenge' => '',
                        'message'   => '',
                        'email'     => '',
                        'resend_in' => 0,
                        'ttl'       => 0,
                        'ttl_text'  => '',
                    );
                }
            } else {
                $user_id  = isset( $verification['user_id'] ) ? (int) $verification['user_id'] : 0;
                $remember = ! empty( $verification['remember'] );

                if ( $user_id && get_user_by( 'id', $user_id ) ) {
                    $context         = isset( $verification['context'] ) && is_array( $verification['context'] ) ? $verification['context'] : array();
                    $redirect_target = introducao_perfil_resolve_redirect( $context, $redirect_url );

                    wp_set_current_user( $user_id );
                    wp_set_auth_cookie( $user_id, $remember );

                    $login_user = get_user_by( 'id', $user_id );

                    if ( $login_user instanceof WP_User ) {
                        if ( class_exists( 'Introducao_Auth' ) ) {
                            Introducao_Auth::set_login_context(
                                array(
                                    'source'   => 'introducao',
                                    'method'   => 'login',
                                    'redirect' => $redirect_target,
                                )
                            );
                        }

                        do_action( 'wp_login', $login_user->user_login, $login_user );
                    }

                    introducao_perfil_redirect( $redirect_target );
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
                    $message = introducao_perfil_normalize_error( $user );

                    if ( $message ) {
                        $login_errors[] = $message;
                    }
                } elseif ( ! Introducao_Auth::should_require_two_factor( $user ) ) {
                    wp_set_current_user( $user->ID );
                    wp_set_auth_cookie( $user->ID, $login_data['remember'] );
                    if ( class_exists( 'Introducao_Auth' ) ) {
                        Introducao_Auth::set_login_context(
                            array(
                                'source'   => 'introducao',
                                'method'   => 'login',
                                'redirect' => $redirect_url,
                            )
                        );
                    }
                    do_action( 'wp_login', $user->user_login, $user );
                    introducao_perfil_redirect( $redirect_url );
                } else {
                    $challenge = Introducao_Auth::create_login_challenge(
                        $user,
                        $login_data['remember'],
                        $login_data['user_login'],
                        array(
                            'source'      => 'introducao',
                            'redirect_to' => $redirect_url,
                        )
                    );

                    if ( is_wp_error( $challenge ) ) {
                        $message = introducao_perfil_clean_error( $challenge );

                        if ( $message ) {
                            $login_errors[] = $message;
                        }
                    } else {
                        $login_otp_state = array(
                            'challenge' => $challenge['challenge_id'],
                            'email'     => $challenge['email'],
                            'message'   => sprintf(
                                __( 'Enviamos um código de acesso para %s. Digite-o para concluir a entrada.', 'introducao' ),
                                Introducao_Auth::mask_email( $challenge['email'] )
                            ),
                        );
                        $login_otp_state = introducao_perfil_prepare_otp_state( $challenge['challenge_id'], $login_otp_state );
                    }
                }
            }
        }
    }
}

$client_challenge      = Introducao_Auth::get_client_challenge_context();
$modal_context_payload = '';

if ( $is_modal_context && ! empty( $client_challenge ) ) {
    $modal_context_payload = esc_attr( wp_json_encode( $client_challenge ) );
}

if ( ! empty( $client_challenge ) ) {
    $masked_email = '';

    if ( isset( $client_challenge['masked_email'] ) && $client_challenge['masked_email'] ) {
        $masked_email = $client_challenge['masked_email'];
    } elseif ( isset( $client_challenge['email'] ) ) {
        $masked_email = Introducao_Auth::mask_email( $client_challenge['email'] );
    }

    if ( 'register' === $client_challenge['type'] && empty( $register_otp_state['challenge'] ) ) {
        $active_panel = 'register';

        $register_otp_state = array(
            'challenge' => isset( $client_challenge['challenge'] ) ? $client_challenge['challenge'] : '',
            'email'     => isset( $client_challenge['email'] ) ? $client_challenge['email'] : '',
            'message'   => sprintf(
                __( 'Enviamos um código de ativação para %s. Informe-o abaixo para concluir seu cadastro.', 'introducao' ),
                $masked_email
            ),
        );
        if ( ! empty( $register_otp_state['challenge'] ) ) {
            $register_otp_state = introducao_perfil_prepare_otp_state( $register_otp_state['challenge'], $register_otp_state );
        }

        if ( isset( $client_challenge['pending_login'] ) && $client_challenge['pending_login'] ) {
            $registration_request['user_login'] = $client_challenge['pending_login'];
        }

        if ( isset( $client_challenge['pending_email'] ) && $client_challenge['pending_email'] ) {
            $registration_request['user_email'] = $client_challenge['pending_email'];
        }
    } elseif ( empty( $login_otp_state['challenge'] ) ) {
        $active_panel = 'login';

        $login_otp_state = array(
            'challenge' => isset( $client_challenge['challenge'] ) ? $client_challenge['challenge'] : '',
            'email'     => isset( $client_challenge['email'] ) ? $client_challenge['email'] : '',
            'message'   => sprintf(
                __( 'Enviamos um código de acesso para %s.', 'introducao' ),
                $masked_email
            ),
        );

        if ( ! empty( $login_otp_state['challenge'] ) ) {
            $login_otp_state = introducao_perfil_prepare_otp_state( $login_otp_state['challenge'], $login_otp_state );
        }

        if ( isset( $client_challenge['identifier'] ) && $client_challenge['identifier'] ) {
            $login_request['user_login'] = $client_challenge['identifier'];
        }

        if ( isset( $client_challenge['remember'] ) ) {
            $login_request['remember'] = (bool) $client_challenge['remember'];
        }
    }
}
}

$lost_password_url  = wp_lostpassword_url( $redirect_url );
$login_errors_id    = $component_prefix . '-login-errors';
$register_errors_id = $component_prefix . '-register-errors';
$login_tab_id       = $component_prefix . '-login-tab';
$register_tab_id    = $component_prefix . '-register-tab';
$login_panel_id     = $component_prefix . '-login-panel';
$register_panel_id  = $component_prefix . '-register-panel';
$is_login_active    = 'login' === $active_panel;
$is_register_active = 'register' === $active_panel;

$login_has_otp    = ! empty( $login_otp_state['challenge'] );
$register_has_otp = ! empty( $register_otp_state['challenge'] );

$root_classes_raw = array( 'introducao-perfil' );

if ( $context_slug && 'page' !== $context_slug ) {
    $root_classes_raw[] = 'introducao-perfil--context-' . $context_slug;
}

$root_class_attr = implode(
    ' ',
    array_unique(
        array_filter(
            array_map( 'sanitize_html_class', $root_classes_raw )
        )
    )
);

if ( ! $root_class_attr ) {
    $root_class_attr = 'introducao-perfil';
}

if ( $is_logged ) {
    $logged_classes_raw = array_merge( $root_classes_raw, array( 'introducao-perfil--logged' ) );
    $logged_class_attr  = implode(
        ' ',
        array_unique(
            array_filter(
                array_map( 'sanitize_html_class', $logged_classes_raw )
            )
        )
    );

    if ( ! $logged_class_attr ) {
        $logged_class_attr = 'introducao-perfil introducao-perfil--logged';
    }

    if ( 'modal' === $context_slug ) {
        ?>
        <div class="<?php echo esc_attr( $logged_class_attr ); ?>" data-render-context="<?php echo esc_attr( $context_slug ); ?>">
            <div class="introducao-perfil__modal-message">
                <h2><?php esc_html_e( 'Você já está conectado!', 'introducao' ); ?></h2>
                <p><?php esc_html_e( 'Abra a área do aluno ou finalize a sessão abaixo.', 'introducao' ); ?></p>
                <div class="introducao-perfil__modal-actions">
                    <a class="introducao-button introducao-button--primary" href="<?php echo esc_url( $redirect_url ); ?>">
                        <?php esc_html_e( 'Ir para simulados', 'introducao' ); ?>
                    </a>
                    <?php if ( $edit_link ) : ?>
                        <a class="introducao-button introducao-button--ghost" href="<?php echo esc_url( $edit_link ); ?>">
                            <?php esc_html_e( 'Minha conta', 'introducao' ); ?>
                        </a>
                    <?php endif; ?>
                    <a class="introducao-button introducao-button--secondary" href="<?php echo esc_url( $logout_url ); ?>">
                        <?php esc_html_e( 'Sair da conta', 'introducao' ); ?>
                    </a>
                </div>
            </div>
        </div>
        <?php
        return;
    }
    ?>
    <div class="<?php echo esc_attr( $logged_class_attr ); ?>" data-render-context="<?php echo esc_attr( $context_slug ); ?>">
        <section class="introducao-perfil__summary" aria-labelledby="introducao-perfil-summary-title">
            <div class="introducao-perfil__summary-header">
                <span class="introducao-perfil__summary-avatar" aria-hidden="true">
                    <?php if ( $avatar_url ) : ?>
                        <img src="<?php echo esc_url( $avatar_url ); ?>" alt="" />
                    <?php else : ?>
                        <span class="introducao-perfil__avatar-fallback"><?php echo esc_html( $initial ? $initial : __( 'A', 'introducao' ) ); ?></span>
                    <?php endif; ?>
                </span>
                <div class="introducao-perfil__summary-info">
                    <span class="introducao-perfil__summary-eyebrow"><?php esc_html_e( 'Conta confirmada', 'introducao' ); ?></span>
                    <h1 id="introducao-perfil-summary-title"><?php printf( esc_html__( 'Olá, %s', 'introducao' ), esc_html( $greeting_name ) ); ?></h1>
                    <?php if ( $account_email ) : ?>
                        <p><?php echo esc_html( $account_email ); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="introducao-perfil__summary-actions">
                <a class="introducao-button introducao-button--primary" href="<?php echo esc_url( $redirect_url ); ?>">
                    <?php esc_html_e( 'Ir para simulados', 'introducao' ); ?>
                </a>
                <?php if ( $edit_link ) : ?>
                    <a class="introducao-button introducao-button--secondary" href="<?php echo esc_url( $edit_link ); ?>">
                        <?php esc_html_e( 'Gerenciar perfil', 'introducao' ); ?>
                    </a>
                <?php endif; ?>
                <a class="introducao-perfil__link" href="<?php echo esc_url( $logout_url ); ?>">
                    <?php esc_html_e( 'Sair da conta', 'introducao' ); ?>
                </a>
            </div>
        </section>

        <section class="introducao-perfil__metrics" aria-label="<?php esc_attr_e( 'Resumo da conta', 'introducao' ); ?>">
            <article class="introducao-perfil__metric">
                <span class="introducao-perfil__metric-label"><?php esc_html_e( 'Status do 2FA', 'introducao' ); ?></span>
                <strong class="introducao-perfil__metric-value"><?php echo esc_html( $two_factor_label ); ?></strong>
                <?php if ( $two_factor_last_sent ) : ?>
                    <span class="introducao-perfil__metric-note"><?php printf( esc_html__( 'Último código enviado em %s', 'introducao' ), esc_html( $two_factor_last_sent ) ); ?></span>
                <?php endif; ?>
            </article>
            <?php if ( $registration ) : ?>
                <article class="introducao-perfil__metric">
                    <span class="introducao-perfil__metric-label"><?php esc_html_e( 'Aluno desde', 'introducao' ); ?></span>
                    <strong class="introducao-perfil__metric-value"><?php echo esc_html( $registration ); ?></strong>
                </article>
            <?php endif; ?>
            <?php if ( $last_login ) : ?>
                <article class="introducao-perfil__metric">
                    <span class="introducao-perfil__metric-label"><?php esc_html_e( 'Último acesso', 'introducao' ); ?></span>
                    <strong class="introducao-perfil__metric-value"><?php echo esc_html( $last_login ); ?></strong>
                </article>
            <?php endif; ?>
        </section>
    </div>
    <?php
    return;
}

?>
<div
    class="<?php echo esc_attr( $root_class_attr ); ?>"
    data-perfil-container
    data-render-context="<?php echo esc_attr( $context_slug ); ?>"
    <?php if ( $modal_context_payload ) : ?>data-lae-login-context="<?php echo $modal_context_payload; ?>"<?php endif; ?>
    data-active-panel="<?php echo esc_attr( $active_panel ); ?>"
    data-login-otp="<?php echo $login_has_otp ? 'true' : 'false'; ?>"
    data-register-otp="<?php echo $register_has_otp ? 'true' : 'false'; ?>"
>
    <div class="introducao-perfil__switch" role="tablist" aria-label="<?php esc_attr_e( 'Escolha entre entrar ou criar uma nova conta', 'introducao' ); ?>">
        <button
            type="button"
            class="introducao-perfil__switch-button<?php echo $is_login_active ? ' is-active' : ''; ?>"
            data-perfil-toggle="login"
            <?php if ( $is_modal_context ) : ?>data-lae-login-tab="login" data-lae-auth-tab="login"<?php endif; ?>
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
            <?php if ( $is_modal_context ) : ?>data-lae-login-tab="register" data-lae-auth-tab="register"<?php endif; ?>
            role="tab"
            id="<?php echo esc_attr( $register_tab_id ); ?>"
            aria-controls="<?php echo esc_attr( $register_panel_id ); ?>"
            aria-selected="<?php echo $is_register_active ? 'true' : 'false'; ?>"
            tabindex="<?php echo $is_register_active ? '0' : '-1'; ?>"
        >
            <?php esc_html_e( 'Criar conta', 'introducao' ); ?>
        </button>
    </div>

    <div class="introducao-perfil__panels">
        <div
            class="introducao-perfil__column introducao-perfil__login<?php echo $is_login_active ? ' is-active' : ''; ?><?php echo $login_has_otp ? ' has-otp' : ''; ?>"
            id="<?php echo esc_attr( $login_panel_id ); ?>"
            role="tabpanel"
            data-perfil-panel="login"
            <?php if ( $is_modal_context ) : ?>data-lae-auth-panel="login"<?php endif; ?>
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
            <div
                class="introducao-perfil__otp"
                <?php if ( $is_modal_context ) : ?>data-lae-login-2fa<?php endif; ?>
                data-perfil-otp-card
                data-context="login"
                data-challenge="<?php echo esc_attr( $login_otp_state['challenge'] ); ?>"
                data-email="<?php echo esc_attr( Introducao_Auth::mask_email( $login_otp_state['email'] ) ); ?>"
                data-resend="<?php echo esc_attr( max( 0, (int) $login_otp_state['resend_in'] ) ); ?>"
                data-ttl="<?php echo esc_attr( max( 0, (int) $login_otp_state['ttl'] ) ); ?>"
            >
                <h3><?php esc_html_e( 'Verificação em duas etapas', 'introducao' ); ?></h3>
                <p class="introducao-perfil__otp-message" data-perfil-otp-message<?php if ( $is_modal_context ) : ?> data-lae-login-2fa-hint<?php endif; ?>><?php echo esc_html( $login_otp_state['message'] ); ?></p>
                <p
                    class="introducao-perfil__otp-meta"
                    data-perfil-ttl
                    role="status"
                    aria-live="polite"
                    <?php if ( empty( $login_otp_state['ttl_text'] ) ) : ?>hidden<?php endif; ?>
                >
                    <?php echo esc_html( $login_otp_state['ttl_text'] ); ?>
                </p>
                <div class="introducao-perfil__otp-actions">
                    <button type="button" class="introducao-perfil__link-button" data-perfil-resend<?php if ( $is_modal_context ) : ?> data-lae-login-2fa-resend<?php endif; ?>>
                        <?php esc_html_e( 'Reenviar código', 'introducao' ); ?>
                    </button>
                    <span class="introducao-perfil__otp-countdown" data-perfil-countdown<?php if ( $is_modal_context ) : ?> data-lae-login-2fa-countdown<?php endif; ?><?php if ( empty( $login_otp_state['resend_in'] ) ) : ?> hidden<?php endif; ?>></span>
                </div>
                <p class="introducao-perfil__otp-feedback" data-perfil-otp-feedback role="status" aria-live="polite" hidden></p>
            </div>
        <?php endif; ?>

        <form method="post" action="<?php echo esc_url( $form_action_url ); ?>"<?php if ( ! empty( $login_errors ) ) : ?> aria-describedby="<?php echo esc_attr( $login_errors_id ); ?>"<?php endif; ?><?php if ( $is_modal_context ) : ?> data-lae-login-form="login"<?php endif; ?>>
            <?php wp_nonce_field( 'lae_perfil_action', 'lae_perfil_nonce' ); ?>
            <input type="hidden" name="lae_perfil_action" value="login" />

            <?php if ( $login_has_otp ) : ?>
                <input type="hidden" name="lae_otp_challenge" value="<?php echo esc_attr( $login_otp_state['challenge'] ); ?>" />
            <?php endif; ?>

            <p class="introducao-field">
                <label class="introducao-field__label" for="lae-login-user"><?php esc_html_e( 'Usuário ou e-mail', 'introducao' ); ?></label>
                <input
                    type="text"
                    id="lae-login-user"
                    name="log"
                    class="introducao-field__input"
                    value="<?php echo esc_attr( $login_request['user_login'] ); ?>"
                    autocomplete="username"
                    aria-describedby="<?php echo esc_attr( introducao_perfil_hint_id( 'login-user' ) ); ?>"
                    <?php echo $login_has_otp ? 'readonly' : 'required'; ?>
                />
                <small class="introducao-field__hint" id="<?php echo esc_attr( introducao_perfil_hint_id( 'login-user' ) ); ?>"><?php esc_html_e( 'Digite o e-mail cadastrado ou seu usuário.', 'introducao' ); ?></small>
            </p>

            <?php if ( ! $login_has_otp ) : ?>
                <p class="introducao-field introducao-field--password"<?php if ( $is_modal_context ) : ?> data-lae-login-password<?php endif; ?>>
                    <label class="introducao-field__label" for="lae-login-pass"><?php esc_html_e( 'Senha', 'introducao' ); ?></label>
                    <input
                        type="password"
                        id="lae-login-pass"
                        name="pwd"
                        class="introducao-field__input"
                        autocomplete="current-password"
                        aria-describedby="<?php echo esc_attr( introducao_perfil_hint_id( 'login-pass' ) ); ?>"
                        required
                    />
                    <small class="introducao-field__hint" id="<?php echo esc_attr( introducao_perfil_hint_id( 'login-pass' ) ); ?>"><?php esc_html_e( 'Sua senha diferencia maiúsculas e minúsculas.', 'introducao' ); ?></small>
                </p>
            <?php endif; ?>

            <?php if ( $login_has_otp ) : ?>
                <p class="introducao-field introducao-field--otp">
                    <label class="introducao-field__label" for="lae-login-otp"><?php esc_html_e( 'Código de acesso', 'introducao' ); ?></label>
                    <input
                        type="text"
                        id="lae-login-otp"
                        name="lae_otp_code"
                        class="introducao-field__input"
                        inputmode="numeric"
                        pattern="[0-9]*"
                        maxlength="6"
                        data-perfil-otp-focus="true"
                        <?php if ( $is_modal_context ) : ?>data-lae-login-2fa-input<?php endif; ?>
                        aria-describedby="<?php echo esc_attr( introducao_perfil_hint_id( 'login-otp' ) ); ?>"
                        required
                    />
                    <small class="introducao-field__hint" id="<?php echo esc_attr( introducao_perfil_hint_id( 'login-otp' ) ); ?>"><?php esc_html_e( 'O código tem 6 dígitos e expira rapidamente por segurança.', 'introducao' ); ?></small>
                </p>
            <?php endif; ?>

            <p class="introducao-perfil__footnote">
                <a href="<?php echo esc_url( $lost_password_url ); ?>" class="introducao-perfil__link"><?php esc_html_e( 'Esqueceu a senha?', 'introducao' ); ?></a>
            </p>

            <p class="introducao-perfil__actions">
                <button type="submit" class="introducao-perfil__submit">
                    <?php echo $login_has_otp ? esc_html__( 'Validar código e entrar', 'introducao' ) : esc_html__( 'Entrar', 'introducao' ); ?>
                </button>
                <?php if ( ! $login_has_otp ) : ?>
                    <label class="introducao-perfil__remember">
                        <input type="checkbox" name="rememberme" value="forever" <?php checked( $login_request['remember'] ); ?> />
                        <span><?php esc_html_e( 'Manter-me conectado', 'introducao' ); ?></span>
                    </label>
                <?php endif; ?>
            </p>
        </form>
        </div>

        <div
            class="introducao-perfil__column introducao-perfil__register<?php echo $is_register_active ? ' is-active' : ''; ?><?php echo $register_has_otp ? ' has-otp' : ''; ?>"
            id="<?php echo esc_attr( $register_panel_id ); ?>"
            role="tabpanel"
            data-perfil-panel="register"
            <?php if ( $is_modal_context ) : ?>data-lae-auth-panel="register"<?php endif; ?>
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
            <div
                class="introducao-perfil__otp"
                <?php if ( $is_modal_context ) : ?>data-lae-register-2fa<?php endif; ?>
                data-perfil-otp-card
                data-context="register"
                data-challenge="<?php echo esc_attr( $register_otp_state['challenge'] ); ?>"
                data-email="<?php echo esc_attr( Introducao_Auth::mask_email( $register_otp_state['email'] ) ); ?>"
                data-resend="<?php echo esc_attr( max( 0, (int) $register_otp_state['resend_in'] ) ); ?>"
                data-ttl="<?php echo esc_attr( max( 0, (int) $register_otp_state['ttl'] ) ); ?>"
            >
                <h3><?php esc_html_e( 'Confirmação em duas etapas', 'introducao' ); ?></h3>
                <p class="introducao-perfil__otp-message" data-perfil-otp-message<?php if ( $is_modal_context ) : ?> data-lae-register-2fa-hint<?php endif; ?>><?php echo esc_html( $register_otp_state['message'] ); ?></p>
                <p
                    class="introducao-perfil__otp-meta"
                    data-perfil-ttl
                    role="status"
                    aria-live="polite"
                    <?php if ( empty( $register_otp_state['ttl_text'] ) ) : ?>hidden<?php endif; ?>
                >
                    <?php echo esc_html( $register_otp_state['ttl_text'] ); ?>
                </p>
                <div class="introducao-perfil__otp-actions">
                    <button type="button" class="introducao-perfil__link-button" data-perfil-resend<?php if ( $is_modal_context ) : ?> data-lae-register-2fa-resend<?php endif; ?>>
                        <?php esc_html_e( 'Reenviar código', 'introducao' ); ?>
                    </button>
                    <span class="introducao-perfil__otp-countdown" data-perfil-countdown<?php if ( $is_modal_context ) : ?> data-lae-register-2fa-countdown<?php endif; ?><?php if ( empty( $register_otp_state['resend_in'] ) ) : ?> hidden<?php endif; ?>></span>
                </div>
                <p class="introducao-perfil__otp-feedback" data-perfil-otp-feedback role="status" aria-live="polite" hidden></p>
            </div>
        <?php endif; ?>

        <form method="post" action="<?php echo esc_url( $form_action_url ); ?>"<?php if ( ! empty( $register_errors ) ) : ?> aria-describedby="<?php echo esc_attr( $register_errors_id ); ?>"<?php endif; ?><?php if ( $is_modal_context ) : ?> data-lae-login-form="register"<?php endif; ?>>
            <?php wp_nonce_field( 'lae_perfil_action', 'lae_perfil_nonce' ); ?>
            <input type="hidden" name="lae_perfil_action" value="register" />

            <?php if ( $register_has_otp ) : ?>
                <input type="hidden" name="lae_otp_challenge" value="<?php echo esc_attr( $register_otp_state['challenge'] ); ?>" />
            <?php endif; ?>

            <div class="introducao-perfil__fields"<?php if ( $is_modal_context ) : ?> data-lae-register-fields<?php endif; ?>>
                <p class="introducao-field">
                    <label class="introducao-field__label" for="lae-register-user"><?php esc_html_e( 'Nome de usuário', 'introducao' ); ?></label>
                    <input
                        type="text"
                        id="lae-register-user"
                        name="lae_user_login"
                        class="introducao-field__input"
                        value="<?php echo esc_attr( $registration_request['user_login'] ); ?>"
                        autocomplete="username"
                        aria-describedby="<?php echo esc_attr( introducao_perfil_hint_id( 'register-user' ) ); ?>"
                        <?php echo $register_has_otp ? 'readonly' : 'required'; ?>
                    />
                    <small class="introducao-field__hint" id="<?php echo esc_attr( introducao_perfil_hint_id( 'register-user' ) ); ?>"><?php esc_html_e( 'Escolha um nome fácil de lembrar. Você poderá alterá-lo depois.', 'introducao' ); ?></small>
                </p>

                <p class="introducao-field">
                    <label class="introducao-field__label" for="lae-register-email"><?php esc_html_e( 'E-mail', 'introducao' ); ?></label>
                    <input
                        type="email"
                        id="lae-register-email"
                        name="lae_user_email"
                        class="introducao-field__input"
                        value="<?php echo esc_attr( $registration_request['user_email'] ); ?>"
                        autocomplete="email"
                        aria-describedby="<?php echo esc_attr( introducao_perfil_hint_id( 'register-email' ) ); ?>"
                        <?php echo $register_has_otp ? 'readonly' : 'required'; ?>
                    />
                    <small class="introducao-field__hint" id="<?php echo esc_attr( introducao_perfil_hint_id( 'register-email' ) ); ?>"><?php esc_html_e( 'Usaremos este e-mail para enviar o código de verificação e novidades da plataforma.', 'introducao' ); ?></small>
                </p>

                <?php if ( ! $register_has_otp ) : ?>
                    <p class="introducao-field introducao-field--password introducao-field--wide">
                        <label class="introducao-field__label" for="lae-register-pass"><?php esc_html_e( 'Senha', 'introducao' ); ?></label>
                        <input
                            type="password"
                            id="lae-register-pass"
                            name="lae_user_pass"
                            class="introducao-field__input"
                            autocomplete="new-password"
                            aria-describedby="<?php echo esc_attr( introducao_perfil_hint_id( 'register-pass' ) ); ?>"
                            required
                        />
                        <small
                            class="introducao-field__hint"<?php if ( $is_modal_context ) : ?> data-lae-password-strength<?php endif; ?>
                            id="<?php echo esc_attr( introducao_perfil_hint_id( 'register-pass' ) ); ?>"
                        ><?php esc_html_e( 'Use ao menos 8 caracteres com letras, números e símbolos.', 'introducao' ); ?></small>
                    </p>
                <?php endif; ?>
            </div>

            <?php if ( $register_has_otp ) : ?>
                <p class="introducao-field introducao-field--otp">
                    <label class="introducao-field__label" for="lae-register-otp"><?php esc_html_e( 'Código de confirmação', 'introducao' ); ?></label>
                    <input
                        type="text"
                        id="lae-register-otp"
                        name="lae_otp_code"
                        class="introducao-field__input"
                        inputmode="numeric"
                        pattern="[0-9]*"
                        maxlength="6"
                        data-perfil-otp-focus="true"
                        <?php if ( $is_modal_context ) : ?>data-lae-register-2fa-input<?php endif; ?>
                        aria-describedby="<?php echo esc_attr( introducao_perfil_hint_id( 'register-otp' ) ); ?>"
                        required
                    />
                    <small class="introducao-field__hint" id="<?php echo esc_attr( introducao_perfil_hint_id( 'register-otp' ) ); ?>"><?php esc_html_e( 'O código tem 6 dígitos e expira rapidamente por segurança.', 'introducao' ); ?></small>
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
</div>
