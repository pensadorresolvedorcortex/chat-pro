<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$user       = wp_get_current_user();
$is_logged  = $user instanceof WP_User && $user->exists();
$avatar_url = '';

if ( $is_logged ) {
    $avatar_url = get_avatar_url( $user->ID, array( 'size' => 240 ) );
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

if ( ! $two_factor_status ) {
    $two_factor_status = __( 'Não configurado', 'login-academia-da-educacao' );
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
                <span class="lae-profile-hero__avatar" aria-hidden="true">
                    <img src="<?php echo esc_url( $avatar_url ); ?>" alt="<?php echo esc_attr( sprintf( __( 'Avatar de %s', 'login-academia-da-educacao' ), $full_name ) ); ?>">
                </span>
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
            <a class="lae-profile-link" href="<?php echo esc_url( wp_lostpassword_url() ); ?>"><?php esc_html_e( 'Atualizar Senha', 'login-academia-da-educacao' ); ?></a>
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
