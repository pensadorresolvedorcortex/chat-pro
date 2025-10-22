<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$show_notifications = isset( $show_notifications ) ? (bool) $show_notifications : false;
$notification_count = isset( $notification_count ) ? (int) $notification_count : 0;
$greeting           = isset( $greeting ) ? $greeting : '';
$greeting_id        = isset( $greeting_id ) ? $greeting_id : wp_unique_id( 'lae-label-' );
$avatar_initial     = isset( $avatar_initial ) ? $avatar_initial : 'U';
$avatar_url         = isset( $avatar_url ) ? $avatar_url : '';
$menu_items         = isset( $menu_items ) ? (array) $menu_items : array();
$menu_id            = isset( $menu_id ) ? $menu_id : wp_unique_id( 'lae-menu-' );
$dropdown_id        = isset( $dropdown_id ) ? $dropdown_id : wp_unique_id( 'lae-dropdown-' );
$display_name       = isset( $display_name ) ? $display_name : '';
$has_custom_greeting = isset( $has_custom_greeting ) ? (bool) $has_custom_greeting : false;
$has_avatar_image   = ! empty( $avatar_url );
$avatar_classes     = 'lae-avatar' . ( $has_avatar_image ? ' has-image' : '' );
$notification_value = $notification_count > 99 ? '99+' : number_format_i18n( $notification_count );
$challenge_context  = class_exists( 'Introducao_Auth' ) ? Introducao_Auth::get_client_challenge_context() : array();
$has_active_challenge = ! empty( $challenge_context ) && ! empty( $challenge_context['challenge'] );
$challenge_flow        = ( $has_active_challenge && isset( $challenge_context['type'] ) && 'register' === $challenge_context['type'] ) ? 'register' : 'login';
$challenge_payload     = array();

if ( $has_active_challenge ) {
    $challenge_payload = array(
        'challenge'     => $challenge_context['challenge'],
        'type'          => $challenge_flow,
        'masked_email'  => isset( $challenge_context['masked_email'] ) ? $challenge_context['masked_email'] : '',
        'email'         => isset( $challenge_context['email'] ) ? $challenge_context['email'] : '',
        'identifier'    => isset( $challenge_context['identifier'] ) ? $challenge_context['identifier'] : '',
        'pending_login' => isset( $challenge_context['pending_login'] ) ? $challenge_context['pending_login'] : '',
        'pending_email' => isset( $challenge_context['pending_email'] ) ? $challenge_context['pending_email'] : '',
        'pending_name'  => isset( $challenge_context['pending_name'] ) ? $challenge_context['pending_name'] : '',
        'remember'      => ! empty( $challenge_context['remember'] ),
        'resend_in'     => isset( $challenge_context['resend_in'] ) ? (int) $challenge_context['resend_in'] : 0,
        'ttl'           => isset( $challenge_context['ttl'] ) ? (int) $challenge_context['ttl'] : 0,
        'ttl_label'     => isset( $challenge_context['ttl_label'] ) ? $challenge_context['ttl_label'] : '',
        'redirect'      => isset( $challenge_context['redirect'] ) ? $challenge_context['redirect'] : '',
        'message'       => isset( $challenge_context['message'] ) ? $challenge_context['message'] : '',
    );
}

$initial_login_value   = ( $has_active_challenge && 'login' === $challenge_flow && ! empty( $challenge_payload['identifier'] ) ) ? $challenge_payload['identifier'] : '';
$initial_remember_state = ( $has_active_challenge && 'login' === $challenge_flow && ! empty( $challenge_payload['remember'] ) );
$initial_register_name  = ( $has_active_challenge && 'register' === $challenge_flow && ! empty( $challenge_payload['pending_name'] ) ) ? $challenge_payload['pending_name'] : '';
$initial_register_email = ( $has_active_challenge && 'register' === $challenge_flow && ! empty( $challenge_payload['pending_email'] ) ) ? $challenge_payload['pending_email'] : '';
$challenge_dataset      = $has_active_challenge ? esc_attr( wp_json_encode( $challenge_payload ) ) : '';

/* translators: %s: quantidade de notificações. */
$notification_label = $notification_count
    ? sprintf(
        _n(
            'Abrir notificações, %s nova',
            'Abrir notificações, %s novas',
            $notification_count,
            'login-academia-da-educacao'
        ),
        number_format_i18n( $notification_count )
    )
    : __( 'Abrir notificações, nenhuma nova', 'login-academia-da-educacao' );
if ( $greeting ) {
    $parts             = explode( ',', $greeting, 2 );
    $greeting_primary   = trim( $parts[0] );
    $greeting_secondary = isset( $parts[1] ) ? trim( $parts[1] ) : '';

    if ( false !== strpos( $greeting, ',' ) && '' !== $greeting_primary && ',' !== substr( $greeting_primary, -1 ) ) {
        $greeting_primary .= ',';
    }

    if ( '' === $greeting_secondary ) {
        $line_parts = preg_split( '/[\r\n]+/', $greeting );

        if ( is_array( $line_parts ) && count( $line_parts ) > 1 ) {
            $greeting_primary   = trim( $line_parts[0] );
            $greeting_secondary = trim( $line_parts[1] );

            if ( '' !== $greeting_primary && ',' !== substr( $greeting_primary, -1 ) ) {
                $greeting_primary .= ',';
            }
        }
    }
} else {
    $greeting_primary   = '';
    $greeting_secondary = '';
}

if ( '' === $greeting_primary && ! $has_custom_greeting ) {
    $greeting_primary = __( 'Bem-vindo,', 'login-academia-da-educacao' );
}

if ( '' === $greeting_secondary && ! $has_custom_greeting && $display_name ) {
    $greeting_secondary = $display_name;
}

$greeting_secondary_attr = $greeting_secondary;

?>
<div class="lae-user-menu-shell">
    <nav class="lae-user-menu" data-lae-menu id="<?php echo esc_attr( $menu_id ); ?>" aria-label="<?php esc_attr_e( 'Menu do usuário', 'login-academia-da-educacao' ); ?>">
        <?php if ( $show_notifications ) : ?>
            <button type="button" class="lae-notification-button" aria-label="<?php echo esc_attr( $notification_label ); ?>">
                <svg viewBox="0 0 24 24" role="img" aria-hidden="true" focusable="false">
                    <path fill="currentColor" d="M12 22a2.25 2.25 0 0 0 2.25-2.25h-4.5A2.25 2.25 0 0 0 12 22Zm6.75-6v-4.5a6.75 6.75 0 0 0-5.25-6.6V4.5a1.5 1.5 0 1 0-3 0v.4a6.75 6.75 0 0 0-5.25 6.6V16l-1.5 1.5v.75h18v-.75Z" />
                </svg>
                <span class="lae-notification-badge" aria-live="polite"><?php echo esc_html( $notification_value ); ?></span>
            </button>
        <?php endif; ?>

        <div class="lae-user-dropdown">
            <button type="button" class="lae-user-toggle" data-lae-toggle aria-haspopup="true" aria-expanded="false" aria-controls="<?php echo esc_attr( $dropdown_id ); ?>" aria-labelledby="<?php echo esc_attr( $greeting_id ); ?>">
                <span class="lae-greeting" id="<?php echo esc_attr( $greeting_id ); ?>">
                    <?php if ( '' !== $greeting_primary ) : ?>
                        <span class="lae-greeting-line lae-greeting-line--welcome"><?php echo esc_html( $greeting_primary ); ?></span>
                    <?php endif; ?>
                    <?php if ( '' !== $greeting_secondary ) : ?>
                        <span class="lae-greeting-line lae-greeting-line--name" title="<?php echo esc_attr( $greeting_secondary_attr ); ?>"><?php echo esc_html( $greeting_secondary ); ?></span>
                    <?php endif; ?>
                </span>
                <span class="<?php echo esc_attr( $avatar_classes ); ?>" aria-hidden="true" data-lae-avatar-container>
                    <?php if ( $has_avatar_image ) :
                        $avatar_alt = $display_name ? sprintf( __( 'Avatar de %s', 'login-academia-da-educacao' ), $display_name ) : '';
                        ?>
                        <img class="lae-avatar-image" data-lae-avatar-sync="image" src="<?php echo esc_url( $avatar_url ); ?>" alt="<?php echo esc_attr( $avatar_alt ); ?>" />
                    <?php endif; ?>
                    <span class="lae-avatar-initial"><?php echo esc_html( $avatar_initial ); ?></span>
                </span>
                <span class="lae-caret" aria-hidden="true"></span>
            </button>
            <div class="lae-dropdown-menu" data-lae-dropdown id="<?php echo esc_attr( $dropdown_id ); ?>">
                <ul role="menu" aria-labelledby="<?php echo esc_attr( $greeting_id ); ?>">
                    <?php foreach ( $menu_items as $item ) :
                        $label        = isset( $item['label'] ) ? $item['label'] : '';
                        $url          = isset( $item['url'] ) ? $item['url'] : '#';
                        $is_current   = ! empty( $item['is_current'] );
                        $aria_current = $is_current ? ' aria-current="page"' : '';
                        $item_classes = 'lae-menu-item' . ( $is_current ? ' is-current' : '' );
                        $type         = isset( $item['type'] ) ? $item['type'] : 'link';
                        ?>
                        <li class="<?php echo esc_attr( $item_classes ); ?>" role="none">
                            <?php if ( 'modal' === $type ) : ?>
                                <button type="button" class="lae-menu-link" data-lae-login-trigger role="menuitem" tabindex="0"><?php echo esc_html( $label ); ?></button>
                            <?php else : ?>
                                <a href="<?php echo esc_url( $url ); ?>" role="menuitem" tabindex="0"<?php echo $aria_current; ?>><?php echo esc_html( $label ); ?></a>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </nav>
</div>
<?php if ( ! is_user_logged_in() ) :
    $login_form_id          = wp_unique_id( 'lae-login-form-' );
    $register_form_id       = wp_unique_id( 'lae-register-form-' );
    $two_factor_field       = wp_unique_id( 'lae-2fa-field-' );
    $register_two_factor_id = wp_unique_id( 'lae-2fa-register-' );
    $redirect_url      = apply_filters( 'lae_login_redirect', home_url( '/questoes-de-concursos/' ) );
    $lost_password_url = wp_lostpassword_url();
    ?>
    <div
        class="lae-login-modal"
        data-lae-login-modal
        aria-hidden="true"
        data-lae-login-flow="<?php echo esc_attr( $has_active_challenge ? $challenge_flow : 'login' ); ?>"
        data-lae-login-awaiting="<?php echo $has_active_challenge ? '1' : '0'; ?>"
        <?php if ( $has_active_challenge && $challenge_dataset ) : ?>data-lae-login-context="<?php echo $challenge_dataset; ?>"<?php endif; ?>
    >
        <div class="lae-login-modal__overlay" data-lae-login-close></div>
        <div class="lae-login-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="<?php echo esc_attr( $login_form_id ); ?>-title">
            <button type="button" class="lae-login-modal__close" data-lae-login-close aria-label="<?php esc_attr_e( 'Fechar painel de acesso', 'login-academia-da-educacao' ); ?>">
                <span aria-hidden="true">&times;</span>
            </button>
            <div class="lae-login-panel">
                <div class="lae-login-panel__intro">
                    <h2 id="<?php echo esc_attr( $login_form_id ); ?>-title"><?php esc_html_e( 'Bem-vindo de volta!', 'login-academia-da-educacao' ); ?></h2>
                    <p><?php esc_html_e( 'Entre com suas credenciais para desbloquear simulados, videoaulas e todo o conteúdo exclusivo da Academia.', 'login-academia-da-educacao' ); ?></p>
                    <ul class="lae-login-benefits">
                        <li><?php esc_html_e( 'Painel personalizado com seus cursos favoritos', 'login-academia-da-educacao' ); ?></li>
                        <li><?php esc_html_e( 'Histórico de desempenho e estatísticas atualizadas', 'login-academia-da-educacao' ); ?></li>
                        <li><?php esc_html_e( 'Comunidade, suporte prioritário e materiais premium', 'login-academia-da-educacao' ); ?></li>
                    </ul>
                </div>
                <div class="lae-login-panel__content">
                    <div class="lae-login-tabs" role="tablist">
                        <button type="button" class="lae-login-tab is-active" role="tab" aria-selected="true" data-lae-login-tab="login" aria-controls="<?php echo esc_attr( $login_form_id ); ?>"><?php esc_html_e( 'Entrar', 'login-academia-da-educacao' ); ?></button>
                        <button type="button" class="lae-login-tab" role="tab" aria-selected="false" data-lae-login-tab="register" aria-controls="<?php echo esc_attr( $register_form_id ); ?>"><?php esc_html_e( 'Criar conta', 'login-academia-da-educacao' ); ?></button>
                    </div>
                    <div class="lae-login-forms">
                        <form id="<?php echo esc_attr( $login_form_id ); ?>" class="lae-login-form is-active" data-lae-login-form="login">
                            <div class="lae-login-field">
                                <label for="<?php echo esc_attr( $login_form_id ); ?>-user"><?php esc_html_e( 'Email ou usuário', 'login-academia-da-educacao' ); ?></label>
                                <input
                                    type="text"
                                    id="<?php echo esc_attr( $login_form_id ); ?>-user"
                                    name="login"
                                    autocomplete="username"
                                    value="<?php echo esc_attr( $initial_login_value ); ?>"
                                    <?php echo $has_active_challenge && 'login' === $challenge_flow ? 'readonly' : 'required'; ?>
                                />
                            </div>
                            <div class="lae-login-field" data-lae-login-password data-lae-password-field<?php echo $has_active_challenge && 'login' === $challenge_flow ? ' hidden' : ''; ?>>
                                <label for="<?php echo esc_attr( $login_form_id ); ?>-pass"><?php esc_html_e( 'Senha', 'login-academia-da-educacao' ); ?></label>
                                <input type="password" id="<?php echo esc_attr( $login_form_id ); ?>-pass" name="password" autocomplete="current-password" <?php echo $has_active_challenge && 'login' === $challenge_flow ? 'disabled' : 'required'; ?> />
                                <button type="button" class="lae-password-toggle" data-lae-password-toggle aria-pressed="false">
                                    <span data-lae-password-toggle-label><?php esc_html_e( 'Mostrar', 'login-academia-da-educacao' ); ?></span>
                                </button>
                            </div>
                            <div class="lae-login-two-factor" data-lae-login-2fa<?php echo $has_active_challenge && 'login' === $challenge_flow ? '' : ' hidden'; ?>>
                                <label for="<?php echo esc_attr( $two_factor_field ); ?>"><?php esc_html_e( 'Código de verificação', 'login-academia-da-educacao' ); ?></label>
                                <input type="text" inputmode="numeric" pattern="[0-9]*" maxlength="6" id="<?php echo esc_attr( $two_factor_field ); ?>" name="code" autocomplete="one-time-code" />
                                <p class="lae-login-two-factor__hint" data-lae-login-2fa-hint></p>
                                <button type="button" class="lae-login-two-factor__resend" data-lae-login-2fa-resend hidden><?php esc_html_e( 'Reenviar código', 'login-academia-da-educacao' ); ?></button>
                            </div>
                            <div class="lae-login-form__row">
                                <label class="lae-login-remember">
                                    <input type="checkbox" name="remember" value="1" <?php checked( $initial_remember_state ); ?> />
                                    <span><?php esc_html_e( 'Manter-me conectado', 'login-academia-da-educacao' ); ?></span>
                                </label>
                                <a class="lae-login-link" href="<?php echo esc_url( $lost_password_url ); ?>"><?php esc_html_e( 'Esqueci minha senha', 'login-academia-da-educacao' ); ?></a>
                            </div>
                            <input type="hidden" name="redirect_to" value="<?php echo esc_url( $redirect_url ); ?>" />
                            <button type="submit" class="lae-login-submit" data-lae-login-submit>
                                <span class="lae-login-submit__label"><?php esc_html_e( 'Entrar na plataforma', 'login-academia-da-educacao' ); ?></span>
                                <span class="lae-login-submit__spinner" aria-hidden="true"></span>
                            </button>
                        </form>
                        <form id="<?php echo esc_attr( $register_form_id ); ?>" class="lae-login-form" data-lae-login-form="register" hidden>
                            <div class="lae-register-fields" data-lae-register-fields<?php echo $has_active_challenge && 'register' === $challenge_flow ? ' hidden' : ''; ?>>
                                <div class="lae-login-field">
                                    <label for="<?php echo esc_attr( $register_form_id ); ?>-name"><?php esc_html_e( 'Nome completo', 'login-academia-da-educacao' ); ?></label>
                                    <input
                                        type="text"
                                        id="<?php echo esc_attr( $register_form_id ); ?>-name"
                                        name="name"
                                        autocomplete="name"
                                        value="<?php echo esc_attr( $initial_register_name ); ?>"
                                        <?php echo $has_active_challenge && 'register' === $challenge_flow ? 'readonly' : 'required'; ?>
                                    />
                                </div>
                                <div class="lae-login-field">
                                    <label for="<?php echo esc_attr( $register_form_id ); ?>-email"><?php esc_html_e( 'Email', 'login-academia-da-educacao' ); ?></label>
                                    <input
                                        type="email"
                                        id="<?php echo esc_attr( $register_form_id ); ?>-email"
                                        name="email"
                                        autocomplete="email"
                                        value="<?php echo esc_attr( $initial_register_email ); ?>"
                                        <?php echo $has_active_challenge && 'register' === $challenge_flow ? 'readonly' : 'required'; ?>
                                    />
                                </div>
                                <div class="lae-login-field" data-lae-password-field>
                                    <label for="<?php echo esc_attr( $register_form_id ); ?>-pass"><?php esc_html_e( 'Criar senha', 'login-academia-da-educacao' ); ?></label>
                                    <input type="password" id="<?php echo esc_attr( $register_form_id ); ?>-pass" name="password" autocomplete="new-password" <?php echo $has_active_challenge && 'register' === $challenge_flow ? 'disabled' : 'required'; ?> />
                                    <button type="button" class="lae-password-toggle" data-lae-password-toggle aria-pressed="false">
                                        <span data-lae-password-toggle-label><?php esc_html_e( 'Mostrar', 'login-academia-da-educacao' ); ?></span>
                                    </button>
                                    <p class="lae-login-field__hint"><?php esc_html_e( 'Utilize pelo menos 8 caracteres com letras e números.', 'login-academia-da-educacao' ); ?></p>
                                    <p class="lae-password-strength" data-lae-password-strength></p>
                                </div>
                                <div class="lae-login-field" data-lae-password-field>
                                    <label for="<?php echo esc_attr( $register_form_id ); ?>-confirm"><?php esc_html_e( 'Confirmar senha', 'login-academia-da-educacao' ); ?></label>
                                    <input type="password" id="<?php echo esc_attr( $register_form_id ); ?>-confirm" name="confirm" autocomplete="new-password" <?php echo $has_active_challenge && 'register' === $challenge_flow ? 'disabled' : 'required'; ?> />
                                    <button type="button" class="lae-password-toggle" data-lae-password-toggle aria-pressed="false">
                                        <span data-lae-password-toggle-label><?php esc_html_e( 'Mostrar', 'login-academia-da-educacao' ); ?></span>
                                    </button>
                                    <p class="lae-login-field__error" data-lae-password-mismatch hidden></p>
                                </div>
                            </div>
                            <div class="lae-login-two-factor" data-lae-register-2fa<?php echo $has_active_challenge && 'register' === $challenge_flow ? '' : ' hidden'; ?>>
                                <label for="<?php echo esc_attr( $register_two_factor_id ); ?>"><?php esc_html_e( 'Código de ativação', 'login-academia-da-educacao' ); ?></label>
                                <input type="text" inputmode="numeric" pattern="[0-9]*" maxlength="6" id="<?php echo esc_attr( $register_two_factor_id ); ?>" data-lae-register-2fa-input name="code" autocomplete="one-time-code" />
                                <p class="lae-login-two-factor__hint" data-lae-register-2fa-hint></p>
                                <button type="button" class="lae-login-two-factor__resend" data-lae-register-2fa-resend hidden><?php esc_html_e( 'Reenviar código', 'login-academia-da-educacao' ); ?></button>
                            </div>
                            <input type="hidden" name="redirect_to" value="<?php echo esc_url( $redirect_url ); ?>" />
                            <button type="submit" class="lae-login-submit" data-lae-login-submit>
                                <span class="lae-login-submit__label"><?php esc_html_e( 'Criar conta e acessar', 'login-academia-da-educacao' ); ?></span>
                                <span class="lae-login-submit__spinner" aria-hidden="true"></span>
                            </button>
                            <p class="lae-login-form__terms"><?php esc_html_e( 'Ao continuar você concorda com os termos de uso e a política de privacidade da Academia.', 'login-academia-da-educacao' ); ?></p>
                        </form>
                    </div>
                    <div class="lae-login-feedback" data-lae-login-message role="status" aria-live="polite">
                        <?php if ( $has_active_challenge && ! empty( $challenge_payload['message'] ) ) : ?>
                            <?php echo esc_html( $challenge_payload['message'] ); ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>
