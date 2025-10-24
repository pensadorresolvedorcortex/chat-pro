<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$generate_id = static function ( $prefix ) {
    if ( function_exists( 'wp_unique_id' ) ) {
        return wp_unique_id( $prefix );
    }

    static $counters = array();

    if ( ! isset( $counters[ $prefix ] ) ) {
        $counters[ $prefix ] = 0;
    }

    $counters[ $prefix ]++;

    return $prefix . $counters[ $prefix ];
};

$show_notifications = isset( $show_notifications ) ? (bool) $show_notifications : false;
$notification_count = isset( $notification_count ) ? (int) $notification_count : 0;
$greeting           = isset( $greeting ) ? $greeting : '';
$greeting_id        = isset( $greeting_id ) ? $greeting_id : $generate_id( 'lae-label-' );
$avatar_initial     = isset( $avatar_initial ) ? $avatar_initial : 'U';
$avatar_url         = isset( $avatar_url ) ? $avatar_url : '';
$menu_items         = isset( $menu_items ) ? (array) $menu_items : array();
$menu_id            = isset( $menu_id ) ? $menu_id : $generate_id( 'lae-menu-' );
$dropdown_id        = isset( $dropdown_id ) ? $dropdown_id : $generate_id( 'lae-dropdown-' );
$display_name       = isset( $display_name ) ? $display_name : '';
$has_custom_greeting = isset( $has_custom_greeting ) ? (bool) $has_custom_greeting : false;
$has_avatar_image   = ! empty( $avatar_url );
$avatar_classes     = 'lae-avatar' . ( $has_avatar_image ? ' has-image' : '' );
$notification_value = $notification_count > 99 ? '99+' : number_format_i18n( $notification_count );
$client_identity    = isset( $client_identity ) && is_array( $client_identity ) ? $client_identity : array();
$identity_dataset   = ! empty( $client_identity ) ? esc_attr( wp_json_encode( $client_identity ) ) : '';

/* translators: %s: quantidade de notificações. */
$notification_label = $notification_count
    ? sprintf(
        _n(
            'Abrir notificações, %s nova',
            'Abrir notificações, %s novas',
            $notification_count,
            'introducao'
        ),
        number_format_i18n( $notification_count )
    )
    : __( 'Abrir notificações, nenhuma nova', 'introducao' );
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
    $greeting_primary = __( 'Bem-vindo,', 'introducao' );
}

if ( ( '' === $display_name || $display_name === __( 'Visitante', 'introducao' ) ) && ! empty( $client_identity['display_name'] ) ) {
    $display_name = sanitize_text_field( $client_identity['display_name'] );
    if ( ! $has_custom_greeting ) {
        $greeting = sprintf( __( 'Bem-vindo, %s', 'introducao' ), $display_name );
    }
} elseif ( ( '' === $display_name || $display_name === __( 'Visitante', 'introducao' ) ) && ! empty( $client_identity['user_login'] ) ) {
    $display_name = sanitize_text_field( $client_identity['user_login'] );
    if ( ! $has_custom_greeting ) {
        $greeting = sprintf( __( 'Bem-vindo, %s', 'introducao' ), $display_name );
    }
}

if ( '' === $greeting_secondary && ! $has_custom_greeting && $display_name ) {
    $greeting_secondary = $display_name;
}

$greeting_secondary_attr = $greeting_secondary;

?>
<div class="lae-user-menu-shell">
    <nav class="lae-user-menu" data-lae-menu id="<?php echo esc_attr( $menu_id ); ?>" aria-label="<?php esc_attr_e( 'Menu do usuário', 'introducao' ); ?>">
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
                <span class="<?php echo esc_attr( $avatar_classes ); ?>" aria-hidden="true">
                    <?php if ( $has_avatar_image ) :
                        $avatar_alt = $display_name ? sprintf( __( 'Avatar de %s', 'introducao' ), $display_name ) : '';
                        ?>
                        <img class="lae-avatar-image" src="<?php echo esc_url( $avatar_url ); ?>" alt="<?php echo esc_attr( $avatar_alt ); ?>" />
                    <?php endif; ?>
                    <span class="lae-avatar-initial"><?php echo esc_html( $avatar_initial ); ?></span>
                </span>
                <span class="lae-caret" aria-hidden="true"></span>
            </button>
            <div class="lae-dropdown-menu" data-lae-dropdown id="<?php echo esc_attr( $dropdown_id ); ?>">
                <ul role="menu" aria-labelledby="<?php echo esc_attr( $greeting_id ); ?>">
                    <?php foreach ( $menu_items as $item ) :
                        $label = isset( $item['label'] ) ? $item['label'] : '';
                        $url   = isset( $item['url'] ) ? $item['url'] : '#';
                        ?>
                        <li role="none">
                            <a href="<?php echo esc_url( $url ); ?>" role="menuitem" tabindex="0"><?php echo esc_html( $label ); ?></a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </nav>
</div>
