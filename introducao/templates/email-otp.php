<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$code = isset( $code ) ? preg_replace( '/[^0-9]/', '', (string) $code ) : '';
$type = isset( $type ) ? $type : 'login';
$name = isset( $name ) ? $name : '';

$headline = ( 'register' === $type )
    ? __( 'Confirme sua nova conta', 'introducao' )
    : __( 'Autenticação em duas etapas', 'introducao' );

$intro = ( 'register' === $type )
    ? __( 'Para finalizar seu cadastro e liberar o acesso completo à Academia, informe o código abaixo na página em que você iniciou o processo.', 'introducao' )
    : __( 'Para proteger sua conta, confirme que é você informando o código abaixo na página de acesso.', 'introducao' );

$footer = __( 'Se você não solicitou este código, ignore este e-mail ou altere sua senha imediatamente.', 'introducao' );
$logo   = 'https://www.agenciadigitalsaopaulo.com.br/app/wp-content/uploads/2025/10/logo-sidebar.png';
$greeting_name = $name ? sprintf( __( 'Olá, %s!', 'introducao' ), esc_html( $name ) ) : __( 'Olá!', 'introducao' );
?>
<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <title><?php echo esc_html( $headline ); ?></title>
    </head>
    <body style="margin:0;padding:0;background:#f4f5fb;font-family:'Inter','Segoe UI',Roboto,sans-serif;color:#1f1f35;">
        <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background:#f4f5fb;padding:32px 0;">
            <tr>
                <td align="center">
                    <table role="presentation" cellpadding="0" cellspacing="0" width="640" style="max-width:640px;background:#ffffff;border-radius:28px;overflow:hidden;box-shadow:0 24px 60px rgba(106,90,224,0.2);">
                        <tr>
                            <td style="padding:32px 40px 16px 40px;background:linear-gradient(135deg,rgba(106,90,224,0.12),rgba(191,131,255,0.18));text-align:center;">
                                <img src="<?php echo esc_url( $logo ); ?>" alt="Academia" width="160" style="max-width:100%;height:auto;display:inline-block;" />
                            </td>
                        </tr>
                        <tr>
                            <td style="padding:24px 40px 12px 40px;">
                                <p style="margin:0 0 12px 0;font-size:16px;font-weight:600;color:#6a5ae0;text-transform:uppercase;letter-spacing:0.08em;">
                                    <?php echo esc_html( $greeting_name ); ?>
                                </p>
                                <h1 style="margin:0 0 16px 0;font-size:26px;color:#1f1f35;line-height:1.3;">
                                    <?php echo esc_html( $headline ); ?>
                                </h1>
                                <p style="margin:0 0 24px 0;font-size:16px;line-height:1.6;color:#4b4b6f;">
                                    <?php echo esc_html( $intro ); ?>
                                </p>
                                <div style="margin:0 auto 28px auto;padding:24px;border-radius:24px;background:linear-gradient(135deg,#6a5ae0,#bf83ff);color:#ffffff;text-align:center;box-shadow:0 20px 44px rgba(106,90,224,0.3);">
                                    <p style="margin:0 0 12px 0;font-size:14px;letter-spacing:0.4em;text-transform:uppercase;opacity:0.85;">
                                        <?php esc_html_e( 'Seu código', 'introducao' ); ?>
                                    </p>
                                    <p style="margin:0;font-size:38px;font-weight:700;letter-spacing:0.12em;">
                                        <?php echo esc_html( $code ); ?>
                                    </p>
                                </div>
                                <p style="margin:0;font-size:14px;line-height:1.6;color:#4b4b6f;">
                                    <?php esc_html_e( 'Por segurança, o código expira em 10 minutos.', 'introducao' ); ?>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding:16px 40px 32px 40px;color:#4b4b6f;font-size:13px;line-height:1.6;background:linear-gradient(135deg,rgba(106,90,224,0.08),rgba(191,131,255,0.12));">
                                <p style="margin:0 0 12px 0;">
                                    <?php echo esc_html( $footer ); ?>
                                </p>
                                <p style="margin:0;font-size:12px;color:rgba(31,31,53,0.65);text-align:center;">
                                    <?php esc_html_e( 'Academia da Educação • Plataforma oficial de estudos', 'introducao' ); ?>
                                </p>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </body>
</html>
