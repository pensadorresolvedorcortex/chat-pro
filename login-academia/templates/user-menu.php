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
$client_identity    = isset( $client_identity ) && is_array( $client_identity ) ? $client_identity : array();
$identity_dataset   = ! empty( $client_identity ) ? esc_attr( wp_json_encode( $client_identity ) ) : '';
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
        'friendly_name' => isset( $challenge_context['friendly_name'] ) ? $challenge_context['friendly_name'] : '',
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

if ( $has_active_challenge && ( '' === $display_name || $display_name === __( 'Visitante', 'login-academia-da-educacao' ) ) ) {
    if ( ! empty( $challenge_payload['friendly_name'] ) ) {
        $display_name = $challenge_payload['friendly_name'];
    } elseif ( 'register' === $challenge_flow && ! empty( $challenge_payload['pending_name'] ) ) {
        $display_name = $challenge_payload['pending_name'];
    } elseif ( 'login' === $challenge_flow && ! empty( $challenge_payload['identifier'] ) ) {
        $display_name = $challenge_payload['identifier'];
    } elseif ( ! empty( $challenge_payload['pending_login'] ) ) {
        $display_name = $challenge_payload['pending_login'];
    }

    if ( $display_name && ! $has_custom_greeting ) {
        $greeting = sprintf( __( 'Bem-vindo, %s', 'login-academia-da-educacao' ), $display_name );
    }
}

if ( ( '' === $display_name || $display_name === __( 'Visitante', 'login-academia-da-educacao' ) ) && ! empty( $client_identity['display_name'] ) ) {
    $display_name = sanitize_text_field( $client_identity['display_name'] );

    if ( ! $has_custom_greeting ) {
        $greeting = sprintf( __( 'Bem-vindo, %s', 'login-academia-da-educacao' ), $display_name );
    }
} elseif ( ( '' === $display_name || $display_name === __( 'Visitante', 'login-academia-da-educacao' ) ) && ! empty( $client_identity['user_login'] ) ) {
    $display_name = sanitize_text_field( $client_identity['user_login'] );

    if ( ! $has_custom_greeting ) {
        $greeting = sprintf( __( 'Bem-vindo, %s', 'login-academia-da-educacao' ), $display_name );
    }
}

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
            <button type="button" class="lae-user-toggle" data-lae-toggle aria-haspopup="true" aria-expanded="false" aria-controls="<?php echo esc_attr( $dropdown_id ); ?>" aria-labelledby="<?php echo esc_attr( $greeting_id ); ?>"<?php echo $identity_dataset ? ' data-lae-identity="' . $identity_dataset . '"' : ''; ?>>
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
    $modal_title_id          = wp_unique_id( 'lae-login-modal-title-' );
    $introducao_form_action  = '';
    $introducao_modal_markup = '';

    if ( class_exists( 'LAE_Plugin' ) ) {
        $lae_pages = LAE_Plugin::get_instance()->get_pages();

        if ( $lae_pages instanceof LAE_Pages ) {
            $introducao_form_action = $lae_pages->get_account_page_url();

            if ( ! $introducao_form_action ) {
                $introducao_form_action = $lae_pages->get_page_url( 'minha-conta-academia' );
            }
        }
    }

    if ( class_exists( 'Introducao_Plugin' ) ) {
        $introducao_modal_markup = Introducao_Plugin::get_instance()->render_template(
            'page-perfil.php',
            array(
                'shortcode_tag'  => 'introducao_perfil',
                'form_action'    => $introducao_form_action,
                'render_context' => 'modal',
            )
        );
    }
    ?>
    <div
        class="lae-login-modal lae-login-modal--introducao"
        data-lae-login-modal
        aria-hidden="true"
        data-lae-login-flow="login"
        data-lae-login-awaiting="0"
    >
        <div class="lae-login-modal__overlay" data-lae-login-close></div>
        <div class="lae-login-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="<?php echo esc_attr( $modal_title_id ); ?>">
            <button type="button" class="lae-login-modal__close" data-lae-login-close aria-label="<?php esc_attr_e( 'Fechar painel de acesso', 'login-academia-da-educacao' ); ?>">
                <span aria-hidden="true">&times;</span>
            </button>
            <div class="lae-login-panel lae-login-panel--introducao">
                <div class="lae-login-panel__intro">
                    <h2 id="<?php echo esc_attr( $modal_title_id ); ?>"><?php esc_html_e( 'Bem-vindo de volta!', 'login-academia-da-educacao' ); ?></h2>
                    <p><?php esc_html_e( 'Entre ou crie sua conta para acessar simulados, videoaulas e todo o conteúdo exclusivo da Academia.', 'login-academia-da-educacao' ); ?></p>
                    <ul class="lae-login-benefits">
                        <li><?php esc_html_e( 'Painéis personalizados com foco no seu edital', 'login-academia-da-educacao' ); ?></li>
                        <li><?php esc_html_e( 'Desempenho atualizado em tempo real', 'login-academia-da-educacao' ); ?></li>
                        <li><?php esc_html_e( 'Suporte dedicado e materiais premium', 'login-academia-da-educacao' ); ?></li>
                    </ul>
                </div>
                <div class="lae-login-panel__content lae-login-panel__content--introducao">
                    <?php if ( $introducao_modal_markup ) : ?>
                        <?php echo $introducao_modal_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    <?php else : ?>
                        <p class="lae-login-empty"><?php esc_html_e( 'Não foi possível carregar o formulário de acesso. Recarregue a página e tente novamente.', 'login-academia-da-educacao' ); ?></p>
                    <?php endif; ?>
                </div>
                <div class="lae-login-feedback" data-lae-login-message role="status" aria-live="polite"></div>
            </div>
        </div>
    </div>
<?php endif; ?>
