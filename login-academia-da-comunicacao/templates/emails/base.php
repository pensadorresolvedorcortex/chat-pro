<?php
/**
 * Base email template.
 *
 * Variables: $content, $logo, $palette.
 *
 * @package ADC\Login\Emails
 */
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>" />
    <title><?php echo esc_html( get_bloginfo( 'name' ) ); ?></title>
</head>
<body style="margin:0;padding:0;background:#f5f4ff;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;color:<?php echo esc_attr( $palette['ink'] ); ?>;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f5f4ff;padding:32px 0;">
    <tr>
        <td align="center">
            <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:24px;overflow:hidden;box-shadow:0 20px 40px rgba(36,33,66,0.12);">
                <tr>
                    <td style="padding:40px 40px 24px 40px;text-align:center;background:<?php echo esc_attr( $palette['primary'] ); ?>;">
                        <img src="<?php echo esc_url( $logo ); ?>" alt="<?php esc_attr_e( 'Logo', 'login-academia-da-comunicacao' ); ?>" style="max-width:160px;height:auto;" />
                    </td>
                </tr>
                <tr>
                    <td style="padding:32px 40px;">
                        <?php echo wp_kses_post( $content ); ?>
                    </td>
                </tr>
                <tr>
                    <td style="padding:24px 40px;text-align:center;background:#f5f4ff;">
                        <p style="margin:0;font-size:13px;color:rgba(36,33,66,0.6);">
                            &copy; <?php echo esc_html( date_i18n( 'Y' ) ); ?> <?php echo esc_html( get_bloginfo( 'name' ) ); ?>
                        </p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
