<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$user       = wp_get_current_user();
$is_logged  = $user instanceof WP_User && $user->exists();
$avatar_url         = '';
$custom_avatar_id   = 0;
$custom_avatar_url  = '';
$has_custom_avatar  = false;

if ( $is_logged ) {
    $avatar_url = get_avatar_url( $user->ID, array( 'size' => 240 ) );
    $custom_avatar_id = (int) get_user_meta( $user->ID, 'lae_custom_avatar_id', true );

    if ( $custom_avatar_id ) {
        $custom_avatar_url = wp_get_attachment_image_url( $custom_avatar_id, 'full' );

        if ( $custom_avatar_url ) {
            $avatar_url        = $custom_avatar_url;
            $has_custom_avatar = true;
        }
    }
}

if ( ! $avatar_url ) {
    $avatar_url = LAE_PLUGIN_URL . 'assets/img/default-avatar.svg';
}

$full_name = '';

if ( $is_logged ) {
    $full_name = trim( $user->first_name . ' ' . $user->last_name );
    $full_name = $full_name ? $full_name : ( $user->display_name ? $user->display_name : $user->user_login );
} else {
    $full_name = __( 'Visitante', 'login-academia-da-educacao' );
}

$email = $is_logged ? $user->user_email : '';

$get_meta_value = function( $keys ) use ( $is_logged, $user ) {
    if ( ! $is_logged ) {
        return '';
    }

    $keys = (array) $keys;

    foreach ( $keys as $key ) {
        $value = get_user_meta( $user->ID, $key, true );

        if ( '' !== $value && null !== $value ) {
            return $value;
        }
    }

    return '';
};

$format_meta = function( $value ) {
    return $value ? $value : __( 'Não informado', 'login-academia-da-educacao' );
};

$birth_date       = $get_meta_value( array( 'birth_date', 'birthday', 'billing_birthdate' ) );
$nationality      = $get_meta_value( array( 'nationality', 'billing_country' ) );
$document_type    = $get_meta_value( array( 'document_type', 'billing_document_type' ) );
$document_number  = $get_meta_value( array( 'document_number', 'cpf', 'billing_cpf' ) );
$admission_date   = $get_meta_value( array( 'admission_date', 'data_de_ingresso' ) );
$address_line_1   = $get_meta_value( array( 'billing_address_1', 'address_line_1' ) );
$address_number   = $get_meta_value( array( 'billing_number', 'address_number' ) );
$address_line_2   = $get_meta_value( array( 'billing_address_2', 'address_line_2' ) );
$address_city     = $get_meta_value( array( 'billing_city', 'city' ) );
$address_state    = $get_meta_value( array( 'billing_state', 'state' ) );
$address_postcode = $get_meta_value( array( 'billing_postcode', 'postcode', 'zip' ) );
$address_country  = $get_meta_value( array( 'billing_country', 'country' ) );
$phone            = $get_meta_value( array( 'billing_phone', 'phone', 'mobile' ) );
$gender           = $get_meta_value( array( 'gender', 'billing_gender' ) );
$notes            = $is_logged ? $user->description : '';

$address_parts = array_filter(
    array(
        $address_line_1,
        $address_number,
        $address_line_2,
        $address_city,
        $address_state,
        $address_postcode,
        $address_country,
    )
);

$address = implode( ', ', $address_parts );
$address = $address ? $address : '';

$registration_date = '';

if ( $is_logged ) {
    $registration_date = mysql2date( get_option( 'date_format' ), $user->user_registered );
}

$password_last_changed = $get_meta_value( array( 'password_last_changed', 'last_password_change' ) );
$last_login            = $get_meta_value( array( 'last_login', 'last_login_at', 'last_login_time', 'last_activity', 'wc_last_active' ) );
$two_factor_status     = $get_meta_value( array( 'two_factor_status', '2fa_enabled' ) );
$two_factor_last_sent  = $get_meta_value( array( 'lae_two_factor_last_sent', 'two_factor_last_sent' ) );
$two_factor_enabled    = false;

if ( $last_login ) {
    if ( is_numeric( $last_login ) ) {
        $timestamp = (int) $last_login;

        if ( $timestamp > 0 ) {
            $last_login = date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $timestamp );
        }
    } else {
        $timestamp = strtotime( $last_login );

        if ( $timestamp ) {
            $last_login = date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $timestamp );
        }
    }
}

if ( $is_logged ) {
    $two_factor_enabled = (bool) get_user_meta( $user->ID, 'lae_two_factor_enabled', true );
}

if ( $two_factor_enabled ) {
    $two_factor_status = __( 'Ativa', 'login-academia-da-educacao' );
} elseif ( ! $two_factor_status ) {
    $two_factor_status = __( 'Desativada', 'login-academia-da-educacao' );
}

if ( $two_factor_last_sent ) {
    if ( is_numeric( $two_factor_last_sent ) ) {
        $timestamp = (int) $two_factor_last_sent;
    } else {
        $timestamp = strtotime( $two_factor_last_sent );
    }

    if ( $timestamp ) {
        $two_factor_last_sent = date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $timestamp );
    }
}

if ( ! $password_last_changed && $is_logged && $registration_date ) {
    $password_last_changed = $registration_date;
}

$account_status = $is_logged ? __( 'Ativa', 'login-academia-da-educacao' ) : __( 'Inativa', 'login-academia-da-educacao' );

?>
<section class="lae-page lae-page-perfil">
    <div class="lae-profile-hero">
        <div class="lae-profile-hero__content">
            <div class="lae-profile-hero__identity">
                <div class="lae-profile-hero__avatar" data-lae-avatar-manager>
                    <div class="lae-profile-hero__avatar-frame" data-lae-avatar-container>
                        <img src="<?php echo esc_url( $avatar_url ); ?>" data-lae-avatar-preview data-lae-avatar-sync="image" alt="<?php echo esc_attr( sprintf( __( 'Avatar de %s', 'login-academia-da-educacao' ), $full_name ) ); ?>">
                    </div>
                    <?php if ( $is_logged ) : ?>
                        <div class="lae-avatar-controls">
                            <button type="button" class="lae-profile-button lae-profile-button--secondary" data-lae-avatar-upload><?php esc_html_e( 'Alterar foto', 'login-academia-da-educacao' ); ?></button>
                            <button type="button" class="lae-profile-button lae-profile-button--ghost" data-lae-avatar-remove<?php echo $has_custom_avatar ? '' : ' hidden'; ?>><?php esc_html_e( 'Remover foto', 'login-academia-da-educacao' ); ?></button>
                        </div>
                        <input type="file" accept="image/*" data-lae-avatar-input hidden>
                        <p class="lae-avatar-message" data-lae-avatar-message role="status" aria-live="polite"></p>
                    <?php endif; ?>
                </div>
                <div class="lae-profile-hero__text">
                    <h2><?php esc_html_e( 'Minha Conta', 'login-academia-da-educacao' ); ?></h2>
                    <p class="lae-profile-hero__name"><?php echo esc_html( $full_name ); ?></p>
                    <?php if ( $email ) : ?>
                        <p class="lae-profile-hero__meta"><?php echo esc_html( $email ); ?></p>
                    <?php else : ?>
                        <p class="lae-profile-hero__meta"><?php esc_html_e( 'Faça login para visualizar os detalhes completos da sua conta.', 'login-academia-da-educacao' ); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="lae-profile-hero__actions">
                <?php if ( $is_logged ) : ?>
                    <a class="lae-profile-button" href="<?php echo esc_url( get_edit_user_link( $user->ID ) ); ?>"><?php esc_html_e( 'Editar Perfil', 'login-academia-da-educacao' ); ?></a>
                <?php else : ?>
                    <a class="lae-profile-button" href="<?php echo esc_url( wp_login_url( home_url( '/minha-conta-academia' ) ) ); ?>"><?php esc_html_e( 'Fazer login', 'login-academia-da-educacao' ); ?></a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if ( ! $is_logged ) : ?>
        <div class="lae-profile-empty">
            <p><?php esc_html_e( 'Entre na sua conta para acessar informações pessoais, endereço e configurações de segurança.', 'login-academia-da-educacao' ); ?></p>
        </div>
        <?php return; ?>
    <?php endif; ?>

    <?php
    $password_field_prefix = wp_unique_id( 'lae-password-' );
    $current_field_id      = $password_field_prefix . '-current';
    $new_field_id          = $password_field_prefix . '-new';
    $confirm_field_id      = $password_field_prefix . '-confirm';
    ?>

    <div class="lae-profile-section">
        <div class="lae-profile-section__header">
            <h3><?php esc_html_e( 'Informações do Usuário', 'login-academia-da-educacao' ); ?></h3>
            <a class="lae-profile-link" href="<?php echo esc_url( get_edit_user_link( $user->ID ) ); ?>"><?php esc_html_e( 'Editar', 'login-academia-da-educacao' ); ?></a>
        </div>
        <div class="lae-profile-grid">
            <div class="lae-profile-field">
                <span class="lae-profile-field__label"><?php esc_html_e( 'Nome', 'login-academia-da-educacao' ); ?></span>
                <span class="lae-profile-field__value"><?php echo esc_html( $format_meta( $user->first_name ? $user->first_name : $full_name ) ); ?></span>
            </div>
            <div class="lae-profile-field">
                <span class="lae-profile-field__label"><?php esc_html_e( 'Sobrenome', 'login-academia-da-educacao' ); ?></span>
                <span class="lae-profile-field__value"><?php echo esc_html( $format_meta( $user->last_name ) ); ?></span>
            </div>
            <div class="lae-profile-field">
                <span class="lae-profile-field__label"><?php esc_html_e( 'Email', 'login-academia-da-educacao' ); ?></span>
                <span class="lae-profile-field__value"><?php echo esc_html( $format_meta( $email ) ); ?></span>
            </div>
            <div class="lae-profile-field">
                <span class="lae-profile-field__label"><?php esc_html_e( 'Data de Nascimento', 'login-academia-da-educacao' ); ?></span>
                <span class="lae-profile-field__value"><?php echo esc_html( $format_meta( $birth_date ) ); ?></span>
            </div>
            <div class="lae-profile-field">
                <span class="lae-profile-field__label"><?php esc_html_e( 'Nacionalidade', 'login-academia-da-educacao' ); ?></span>
                <span class="lae-profile-field__value"><?php echo esc_html( $format_meta( $nationality ) ); ?></span>
            </div>
            <div class="lae-profile-field">
                <span class="lae-profile-field__label"><?php esc_html_e( 'Tipo de Documento', 'login-academia-da-educacao' ); ?></span>
                <span class="lae-profile-field__value"><?php echo esc_html( $format_meta( $document_type ) ); ?></span>
            </div>
            <div class="lae-profile-field">
                <span class="lae-profile-field__label"><?php esc_html_e( 'CPF', 'login-academia-da-educacao' ); ?></span>
                <span class="lae-profile-field__value"><?php echo esc_html( $format_meta( $document_number ) ); ?></span>
            </div>
            <div class="lae-profile-field">
                <span class="lae-profile-field__label"><?php esc_html_e( 'Data de Ingresso', 'login-academia-da-educacao' ); ?></span>
                <span class="lae-profile-field__value"><?php echo esc_html( $format_meta( $admission_date ? $admission_date : $registration_date ) ); ?></span>
            </div>
            <div class="lae-profile-field lae-profile-field--wide">
                <span class="lae-profile-field__label"><?php esc_html_e( 'Sobre', 'login-academia-da-educacao' ); ?></span>
                <span class="lae-profile-field__value"><?php echo esc_html( $format_meta( $notes ) ); ?></span>
            </div>
        </div>
    </div>

    <div class="lae-profile-section">
        <div class="lae-profile-section__header">
            <h3><?php esc_html_e( 'Endereço', 'login-academia-da-educacao' ); ?></h3>
            <a class="lae-profile-link" href="<?php echo esc_url( get_edit_user_link( $user->ID ) ); ?>#contact"><?php esc_html_e( 'Editar', 'login-academia-da-educacao' ); ?></a>
        </div>
        <div class="lae-profile-grid">
            <div class="lae-profile-field lae-profile-field--wide">
                <span class="lae-profile-field__label"><?php esc_html_e( 'Endereço Completo', 'login-academia-da-educacao' ); ?></span>
                <span class="lae-profile-field__value"><?php echo esc_html( $format_meta( $address ) ); ?></span>
            </div>
            <div class="lae-profile-field">
                <span class="lae-profile-field__label"><?php esc_html_e( 'Telefone', 'login-academia-da-educacao' ); ?></span>
                <span class="lae-profile-field__value"><?php echo esc_html( $format_meta( $phone ) ); ?></span>
            </div>
            <div class="lae-profile-field">
                <span class="lae-profile-field__label"><?php esc_html_e( 'Gênero', 'login-academia-da-educacao' ); ?></span>
                <span class="lae-profile-field__value"><?php echo esc_html( $format_meta( $gender ) ); ?></span>
            </div>
        </div>
    </div>

    <div class="lae-profile-section">
        <div class="lae-profile-section__header">
            <h3><?php esc_html_e( 'Segurança', 'login-academia-da-educacao' ); ?></h3>
        </div>
        <div class="lae-profile-grid">
            <div class="lae-profile-field">
                <span class="lae-profile-field__label"><?php esc_html_e( 'Status da Conta', 'login-academia-da-educacao' ); ?></span>
                <span class="lae-profile-field__value"><?php echo esc_html( $account_status ); ?></span>
            </div>
            <div class="lae-profile-field">
                <span class="lae-profile-field__label"><?php esc_html_e( 'Última alteração de senha', 'login-academia-da-educacao' ); ?></span>
                <span class="lae-profile-field__value"><?php echo esc_html( $format_meta( $password_last_changed ) ); ?></span>
            </div>
            <div class="lae-profile-field">
                <span class="lae-profile-field__label"><?php esc_html_e( 'Último acesso', 'login-academia-da-educacao' ); ?></span>
                <span class="lae-profile-field__value"><?php echo esc_html( $format_meta( $last_login ) ); ?></span>
            </div>
            <div class="lae-profile-field">
                <span class="lae-profile-field__label"><?php esc_html_e( 'Autenticação em duas etapas', 'login-academia-da-educacao' ); ?></span>
                <span class="lae-profile-field__value"><?php echo esc_html( $format_meta( $two_factor_status ) ); ?></span>
            </div>
            <div class="lae-profile-field">
                <span class="lae-profile-field__label"><?php esc_html_e( 'Último envio de código 2FA', 'login-academia-da-educacao' ); ?></span>
                <span class="lae-profile-field__value"><?php echo esc_html( $format_meta( $two_factor_last_sent ) ); ?></span>
            </div>
        </div>
        <div class="lae-security-actions">
            <div class="lae-security-card" data-lae-two-factor data-lae-2fa-enabled="<?php echo $two_factor_enabled ? '1' : '0'; ?>">
                <h4><?php esc_html_e( 'Autenticação em duas etapas', 'login-academia-da-educacao' ); ?></h4>
                <p class="lae-security-description"><?php esc_html_e( 'Adicione uma camada extra de segurança solicitando um código enviado por email sempre que você fizer login.', 'login-academia-da-educacao' ); ?></p>
                <p class="lae-security-status"><strong><?php esc_html_e( 'Status:', 'login-academia-da-educacao' ); ?></strong> <span data-lae-2fa-status><?php echo esc_html( $two_factor_status ); ?></span></p>
                <button type="button" class="lae-profile-button lae-profile-button--secondary" data-lae-2fa-toggle>
                    <?php echo $two_factor_enabled ? esc_html__( 'Desativar', 'login-academia-da-educacao' ) : esc_html__( 'Ativar', 'login-academia-da-educacao' ); ?>
                </button>
                <p class="lae-security-message" data-lae-2fa-message role="status" aria-live="polite"></p>
            </div>

            <form class="lae-security-card" data-lae-password-form>
                <h4><?php esc_html_e( 'Alterar senha', 'login-academia-da-educacao' ); ?></h4>
                <p class="lae-security-description"><?php esc_html_e( 'Atualize sua senha regularmente para manter sua conta protegida.', 'login-academia-da-educacao' ); ?></p>
                <label class="lae-profile-label" for="<?php echo esc_attr( $current_field_id ); ?>"><?php esc_html_e( 'Senha atual', 'login-academia-da-educacao' ); ?></label>
                <input type="password" id="<?php echo esc_attr( $current_field_id ); ?>" name="current_password" class="lae-profile-input" autocomplete="current-password" required>

                <label class="lae-profile-label" for="<?php echo esc_attr( $new_field_id ); ?>"><?php esc_html_e( 'Nova senha', 'login-academia-da-educacao' ); ?></label>
                <input type="password" id="<?php echo esc_attr( $new_field_id ); ?>" name="new_password" class="lae-profile-input" autocomplete="new-password" required>

                <label class="lae-profile-label" for="<?php echo esc_attr( $confirm_field_id ); ?>"><?php esc_html_e( 'Confirmar nova senha', 'login-academia-da-educacao' ); ?></label>
                <input type="password" id="<?php echo esc_attr( $confirm_field_id ); ?>" name="confirm_password" class="lae-profile-input" autocomplete="new-password" required>

                <button type="submit" class="lae-profile-button" data-lae-password-submit><?php esc_html_e( 'Salvar nova senha', 'login-academia-da-educacao' ); ?></button>
                <p class="lae-security-message" data-lae-password-message role="status" aria-live="polite"></p>
            </form>
        </div>
    </div>

    <div class="lae-profile-section lae-profile-section--danger">
        <div class="lae-profile-section__header">
            <h3><?php esc_html_e( 'Cancelar Conta', 'login-academia-da-educacao' ); ?></h3>
        </div>
        <p class="lae-profile-danger-text"><?php esc_html_e( 'Ao cancelar sua conta, todos os seus dados poderão ser removidos permanentemente. Essa ação não pode ser desfeita.', 'login-academia-da-educacao' ); ?></p>
        <a class="lae-profile-button lae-profile-button--danger" href="<?php echo esc_url( wp_logout_url( home_url() ) ); ?>"><?php esc_html_e( 'Encerrar Sessão', 'login-academia-da-educacao' ); ?></a>
    </div>

    <?php do_action( 'lae_profile_after_sections', $user ); ?>
</section>
