<?php
/**
 * Onboarding carousel template.
 *
 * @package ADC\Login\Templates
 */

use function ADC\Login\get_asset_url;
use function ADC\Login\get_logo_url;
use function ADC\Login\get_onboarding_skip_url;
use function ADC\Login\get_onboarding_url;
use function ADC\Login\locate_template;

$step = isset( $_GET['step'] ) ? sanitize_key( wp_unslash( $_GET['step'] ) ) : 'intro'; // phpcs:ignore WordPress.Security.NonceVerification

if ( in_array( $step, array( 'login', 'auth' ), true ) ) {
    echo do_shortcode( '[adc_login]' );
    return;
}

if ( 'signup' === $step ) {
    echo do_shortcode( '[adc_signup]' );
    return;
}

if ( 'forgot' === $step ) {
    echo do_shortcode( '[adc_forgot]' );
    return;
}

if ( '2fa' === $step ) {
    echo do_shortcode( '[adc_2fa]' );
    return;
}

$slides = apply_filters(
    'adc_login_onboarding_slides',
    array(
        array(
            'title' => __( 'Quiz On the Go', 'login-academia-da-comunicacao' ),
            'text'  => __( 'Responda quizzes rápidos onde estiver e mantenha sua mente afiada.', 'login-academia-da-comunicacao' ),
            'image' => array(
                'src' => get_asset_url( 'assets/img/onboarding-quiz.svg' ),
                'alt' => __( 'Ilustração de uma pessoa respondendo a um quiz no celular.', 'login-academia-da-comunicacao' ),
            ),
        ),
        array(
            'title' => __( 'Knowledge Boosting', 'login-academia-da-comunicacao' ),
            'text'  => __( 'Descubra conteúdos envolventes para turbinar seus estudos diariamente.', 'login-academia-da-comunicacao' ),
            'image' => array(
                'src' => get_asset_url( 'assets/img/onboarding-knowledge.svg' ),
                'alt' => __( 'Ilustração abstrata representando crescimento de conhecimento.', 'login-academia-da-comunicacao' ),
            ),
        ),
        array(
            'title' => __( 'Win Rewards Galore', 'login-academia-da-comunicacao' ),
            'text'  => __( 'Ganhe recompensas enquanto aprende com desafios pensados para você.', 'login-academia-da-comunicacao' ),
            'cta'   => __( 'Iniciar', 'login-academia-da-comunicacao' ),
            'image' => array(
                'src' => get_asset_url( 'assets/img/onboarding-rewards.svg' ),
                'alt' => __( 'Ilustração com medalhas e troféus simbolizando recompensas.', 'login-academia-da-comunicacao' ),
            ),
        ),
    )
);

$brand_label       = apply_filters( 'adc_login_onboarding_brand_label', __( 'Academia da Comunicação', 'login-academia-da-comunicacao' ) );
$skip_label        = apply_filters( 'adc_login_onboarding_skip_label', __( 'Pular', 'login-academia-da-comunicacao' ) );
$login_link_label  = apply_filters( 'adc_login_onboarding_login_link_label', __( 'Entrar', 'login-academia-da-comunicacao' ) );
$signup_link_label = apply_filters( 'adc_login_onboarding_signup_link_label', __( 'Cadastre-se', 'login-academia-da-comunicacao' ) );

$cta_url  = apply_filters( 'adc_login_onboarding_cta_url', home_url( '/login' ) );
$skip_url = apply_filters( 'adc_login_onboarding_skip_url', get_onboarding_skip_url() );
?>
<div class="adc-onboarding-viewport">
    <div class="adc-onboarding-shell">
        <div class="adc-onboarding">
            <div class="adc-onboarding-glow" aria-hidden="true"></div>
            <div class="adc-carousel" data-slide-count="<?php echo esc_attr( count( $slides ) ); ?>">
                <header class="adc-carousel-header">
                    <div class="adc-onboarding-brand">
                        <img src="<?php echo esc_url( get_logo_url() ); ?>" alt="<?php esc_attr_e( 'Logo da Academia da Comunicação', 'login-academia-da-comunicacao' ); ?>" loading="lazy" width="140" height="42" />
                        <span><?php echo esc_html( $brand_label ); ?></span>
                    </div>
                    <a class="adc-skip" data-target="<?php echo esc_url( $skip_url ); ?>" href="<?php echo esc_url( $skip_url ); ?>"><?php echo esc_html( $skip_label ); ?></a>
                </header>
                <div class="adc-carousel-body">
                    <?php foreach ( $slides as $index => $slide ) : ?>
                        <div class="adc-slide" aria-hidden="true">
                            <?php if ( isset( $slide['image'] ) ) :
                                $image     = $slide['image'];
                                $image_src = is_array( $image ) && isset( $image['src'] ) ? $image['src'] : $image;
                                $image_alt = is_array( $image ) && isset( $image['alt'] ) ? $image['alt'] : '';
                                ?>
                                <figure class="adc-slide-illustration">
                                    <img src="<?php echo esc_url( $image_src ); ?>" alt="<?php echo esc_attr( $image_alt ); ?>" loading="lazy" width="360" height="260" />
                                </figure>
                            <?php endif; ?>
                            <div class="adc-slide-content">
                                <span class="adc-slide-kicker"><?php printf( esc_html__( 'Passo %1$d de %2$d', 'login-academia-da-comunicacao' ), (int) $index + 1, count( $slides ) ); ?></span>
                                <h2><?php echo esc_html( $slide['title'] ); ?></h2>
                                <p><?php echo esc_html( $slide['text'] ); ?></p>
                            </div>
                            <?php if ( isset( $slide['cta'] ) ) : ?>
                                <a class="adc-button adc-button-secondary adc-next" data-target="<?php echo esc_url( $cta_url ); ?>" href="<?php echo esc_url( $cta_url ); ?>"><?php echo esc_html( $slide['cta'] ); ?></a>
                            <?php else : ?>
                                <button class="adc-button adc-next" type="button"><?php esc_html_e( 'Próximo', 'login-academia-da-comunicacao' ); ?></button>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="adc-carousel-footer">
                    <div class="adc-progress" role="tablist">
                        <?php foreach ( $slides as $index => $slide ) : ?>
                            <span class="<?php echo 0 === $index ? 'is-active' : ''; ?>" aria-hidden="true"></span>
                        <?php endforeach; ?>
                    </div>
                    <div class="adc-onboarding-cta">
                        <a class="adc-link" data-modal-trigger="login" href="<?php echo esc_url( add_query_arg( 'step', 'login', get_onboarding_url() ) ); ?>"><?php echo esc_html( $login_link_label ); ?></a>
                        <span aria-hidden="true">•</span>
                        <a class="adc-link" data-modal-trigger="signup" href="<?php echo esc_url( add_query_arg( 'step', 'signup', get_onboarding_url() ) ); ?>"><?php echo esc_html( $signup_link_label ); ?></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include locate_template( 'modal-auth.php' ); ?>
