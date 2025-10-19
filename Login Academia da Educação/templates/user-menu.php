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
$has_avatar_image   = ! empty( $avatar_url );
$avatar_classes     = 'lae-avatar' . ( $has_avatar_image ? ' has-image' : '' );
$notification_value = $notification_count > 99 ? '99+' : number_format_i18n( $notification_count );

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
                <span class="lae-greeting" id="<?php echo esc_attr( $greeting_id ); ?>"><?php echo esc_html( $greeting ); ?></span>
                <span class="<?php echo esc_attr( $avatar_classes ); ?>" aria-hidden="true">
                    <?php if ( $has_avatar_image ) : ?>
                        <span class="lae-avatar-image" style="background-image: url('<?php echo esc_url( $avatar_url ); ?>');"></span>
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
