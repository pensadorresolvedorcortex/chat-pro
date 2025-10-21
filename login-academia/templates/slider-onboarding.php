<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$slides = array(
    array(
        'title'       => __( 'Organize sua preparação', 'login-academia-da-educacao' ),
        'description' => __( 'Conheça o plano de estudos inteligente da Academia da Comunicação e centralize teoria, prática e simulados em um só lugar.', 'login-academia-da-educacao' ),
    ),
    array(
        'title'       => __( 'Acompanhe o seu progresso', 'login-academia-da-educacao' ),
        'description' => __( 'Receba lembretes personalizados, veja sua evolução em tempo real e desbloqueie recomendações alinhadas às suas metas.', 'login-academia-da-educacao' ),
    ),
    array(
        'title'       => __( 'Comece agora mesmo', 'login-academia-da-educacao' ),
        'description' => __( 'Crie sua conta ou acesse para liberar turmas, simulados e o apoio do nosso treinador virtual 24h.', 'login-academia-da-educacao' ),
    ),
);

$slider_id = wp_unique_id( 'lae-onboarding-' );
$popup_id  = $slider_id . '-popup';
?>
<section class="lae-onboarding" data-lae-slider aria-label="<?php esc_attr_e( 'Apresentação da Academia da Comunicação', 'login-academia-da-educacao' ); ?>">
    <div class="lae-onboarding__content">
        <div class="lae-onboarding__progress" role="tablist" aria-label="<?php esc_attr_e( 'Passos de boas-vindas', 'login-academia-da-educacao' ); ?>">
            <?php foreach ( $slides as $index => $slide ) :
                $step_id = $slider_id . '-step-' . $index;
                ?>
                <button
                    type="button"
                    class="lae-onboarding__step"
                    data-lae-step
                    data-lae-target="<?php echo esc_attr( $index ); ?>"
                    id="<?php echo esc_attr( $step_id ); ?>"
                    role="tab"
                    aria-selected="<?php echo 0 === $index ? 'true' : 'false'; ?>"
                    aria-controls="<?php echo esc_attr( $step_id . '-panel' ); ?>"
                    tabindex="<?php echo 0 === $index ? '0' : '-1'; ?>"
                >
                    <span class="lae-onboarding__step-index"><?php echo esc_html( $index + 1 ); ?></span>
                    <span class="lae-onboarding__step-label"><?php esc_html_e( 'Passo', 'login-academia-da-educacao' ); ?> <?php echo esc_html( $index + 1 ); ?></span>
                </button>
            <?php endforeach; ?>
        </div>

        <div class="lae-onboarding__slides">
            <?php foreach ( $slides as $index => $slide ) :
                $panel_id = $slider_id . '-step-' . $index . '-panel';
                ?>
                <article
                    class="lae-onboarding__slide<?php echo 0 === $index ? ' is-active' : ''; ?>"
                    data-lae-slide
                    data-lae-index="<?php echo esc_attr( $index ); ?>"
                    id="<?php echo esc_attr( $panel_id ); ?>"
                    role="tabpanel"
                    aria-labelledby="<?php echo esc_attr( $slider_id . '-step-' . $index ); ?>"
                    aria-hidden="<?php echo 0 === $index ? 'false' : 'true'; ?>"
                >
                    <h2 class="lae-onboarding__title"><?php echo esc_html( $slide['title'] ); ?></h2>
                    <p class="lae-onboarding__description"><?php echo esc_html( $slide['description'] ); ?></p>

                    <?php if ( 2 === $index ) : ?>
                        <div class="lae-onboarding__actions" data-lae-finish>
                            <button type="button" class="lae-onboarding__cta" data-lae-open-popup data-lae-popup-target="<?php echo esc_attr( $popup_id ); ?>">
                                <?php esc_html_e( 'Entrar ou criar conta', 'login-academia-da-educacao' ); ?>
                            </button>
                            <span class="lae-onboarding__cta-hint"><?php esc_html_e( 'Leva menos de 1 minuto para começar.', 'login-academia-da-educacao' ); ?></span>
                        </div>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>

        <div class="lae-onboarding__controls">
            <button type="button" class="lae-onboarding__control lae-onboarding__control--prev" data-lae-prev>
                <span aria-hidden="true">&larr;</span>
                <span><?php esc_html_e( 'Voltar', 'login-academia-da-educacao' ); ?></span>
            </button>
            <button type="button" class="lae-onboarding__control lae-onboarding__control--next" data-lae-next>
                <span><?php esc_html_e( 'Próximo', 'login-academia-da-educacao' ); ?></span>
                <span aria-hidden="true">&rarr;</span>
            </button>
        </div>
    </div>

    <div class="lae-onboarding__visual" aria-hidden="true">
        <div class="lae-onboarding__visual-glow"></div>
        <svg class="lae-onboarding__visual-owl" viewBox="0 0 320 340" role="img" aria-hidden="true" focusable="false">
            <defs>
                <linearGradient id="lae-owl-gradient" x1="50%" x2="50%" y1="0%" y2="100%">
                    <stop offset="0%" stop-color="#ffffff" stop-opacity="0.95" />
                    <stop offset="100%" stop-color="#d7ccff" stop-opacity="0.75" />
                </linearGradient>
                <linearGradient id="lae-owl-accent" x1="0%" x2="100%" y1="0%" y2="100%">
                    <stop offset="0%" stop-color="#6a5ae0" />
                    <stop offset="100%" stop-color="#bf83ff" />
                </linearGradient>
            </defs>
            <g fill="none" fill-rule="evenodd">
                <path d="M160 32c68 0 124 52 124 116 0 56-40 90-88 136-20 20-52 20-72 0-48-46-88-80-88-136 0-64 56-116 124-116Z" fill="url(#lae-owl-gradient)" />
                <path d="M160 52c-48 0-86 36-86 80 0 40 32 72 72 72h28c40 0 72-32 72-72 0-44-38-80-86-80Z" fill="#ffffff" opacity="0.82" />
                <g transform="translate(78 104)">
                    <circle cx="42" cy="42" r="42" fill="#6a5ae0" opacity="0.9" />
                    <circle cx="122" cy="42" r="42" fill="#6a5ae0" opacity="0.9" />
                    <circle cx="42" cy="42" r="20" fill="#fff" />
                    <circle cx="122" cy="42" r="20" fill="#fff" />
                    <circle cx="48" cy="44" r="10" fill="#1f1f35" />
                    <circle cx="116" cy="44" r="10" fill="#1f1f35" />
                    <path d="M82 28c10 0 18 8 18 18s-8 18-18 18-18-8-18-18 8-18 18-18Z" fill="#bf83ff" opacity="0.7" />
                    <path d="M80 94c18 0 34 10 40 26 4 10-2 20-12 20H52c-10 0-16-10-12-20 6-16 22-26 40-26Z" fill="url(#lae-owl-accent)" />
                </g>
                <path d="M94 210c16 42 40 74 66 74s50-32 66-74" stroke="url(#lae-owl-accent)" stroke-width="18" stroke-linecap="round" opacity="0.75" />
            </g>
        </svg>
        <div class="lae-onboarding__visual-base"></div>
    </div>

    <div class="lae-onboarding__popup" id="<?php echo esc_attr( $popup_id ); ?>" data-lae-popup hidden aria-hidden="true">
        <div class="lae-onboarding__popup-overlay" data-lae-popup-close></div>
        <div class="lae-onboarding__popup-dialog" role="dialog" aria-modal="true" aria-labelledby="<?php echo esc_attr( $popup_id ); ?>-title">
            <button type="button" class="lae-onboarding__popup-close" data-lae-popup-close aria-label="<?php esc_attr_e( 'Fechar', 'login-academia-da-educacao' ); ?>">
                <span aria-hidden="true">&times;</span>
            </button>
            <h3 class="lae-onboarding__popup-title" id="<?php echo esc_attr( $popup_id ); ?>-title"><?php esc_html_e( 'Vamos começar?', 'login-academia-da-educacao' ); ?></h3>
            <p class="lae-onboarding__popup-subtitle"><?php esc_html_e( 'Acesse com sua conta ou crie um novo cadastro para desbloquear todos os recursos.', 'login-academia-da-educacao' ); ?></p>

            <div class="lae-onboarding__popup-tabs" role="tablist" aria-label="<?php esc_attr_e( 'Escolha como deseja continuar', 'login-academia-da-educacao' ); ?>">
                <button type="button" class="lae-onboarding__popup-tab is-active" role="tab" aria-selected="true" id="<?php echo esc_attr( $popup_id ); ?>-login-tab" data-lae-auth-tab="login" aria-controls="<?php echo esc_attr( $popup_id ); ?>-login" tabindex="0">
                    <?php esc_html_e( 'Entrar', 'login-academia-da-educacao' ); ?>
                </button>
                <button type="button" class="lae-onboarding__popup-tab" role="tab" aria-selected="false" id="<?php echo esc_attr( $popup_id ); ?>-register-tab" data-lae-auth-tab="register" aria-controls="<?php echo esc_attr( $popup_id ); ?>-register" tabindex="-1">
                    <?php esc_html_e( 'Criar conta', 'login-academia-da-educacao' ); ?>
                </button>
            </div>

            <form class="lae-onboarding__form is-active" data-lae-auth-panel="login" id="<?php echo esc_attr( $popup_id ); ?>-login" role="tabpanel" aria-labelledby="<?php echo esc_attr( $popup_id ); ?>-login-tab" aria-hidden="false">
                <label class="lae-onboarding__field">
                    <span><?php esc_html_e( 'E-mail', 'login-academia-da-educacao' ); ?></span>
                    <input type="email" name="email" autocomplete="email" required />
                </label>
                <label class="lae-onboarding__field">
                    <span><?php esc_html_e( 'Senha', 'login-academia-da-educacao' ); ?></span>
                    <input type="password" name="password" autocomplete="current-password" required />
                </label>
                <button type="submit" class="lae-onboarding__submit">
                    <?php esc_html_e( 'Entrar', 'login-academia-da-educacao' ); ?>
                </button>
                <p class="lae-onboarding__legal">
                    <?php esc_html_e( 'Esqueceu a senha? Você poderá redefini-la após enviar o formulário.', 'login-academia-da-educacao' ); ?>
                </p>
            </form>

            <form class="lae-onboarding__form" data-lae-auth-panel="register" id="<?php echo esc_attr( $popup_id ); ?>-register" role="tabpanel" aria-labelledby="<?php echo esc_attr( $popup_id ); ?>-register-tab" aria-hidden="true" hidden>
                <label class="lae-onboarding__field">
                    <span><?php esc_html_e( 'Nome completo', 'login-academia-da-educacao' ); ?></span>
                    <input type="text" name="name" autocomplete="name" required />
                </label>
                <label class="lae-onboarding__field">
                    <span><?php esc_html_e( 'E-mail', 'login-academia-da-educacao' ); ?></span>
                    <input type="email" name="register_email" autocomplete="email" required />
                </label>
                <label class="lae-onboarding__field">
                    <span><?php esc_html_e( 'Crie uma senha', 'login-academia-da-educacao' ); ?></span>
                    <input type="password" name="register_password" autocomplete="new-password" required />
                </label>
                <button type="submit" class="lae-onboarding__submit">
                    <?php esc_html_e( 'Criar conta', 'login-academia-da-educacao' ); ?>
                </button>
                <p class="lae-onboarding__legal">
                    <?php esc_html_e( 'Ao continuar você concorda com os Termos de Uso e Política de Privacidade.', 'login-academia-da-educacao' ); ?>
                </p>
            </form>
        </div>
    </div>
</section>
