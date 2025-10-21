<?php
/**
 * Content manager responsible for storing and exposing customizable copy and imagery.
 *
 * @package ADC\Login\Content
 */

namespace ADC\Login\Content;

use function ADC\Login\get_asset_url;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Persist and expose customizable content tokens for the plugin templates.
 */
class Manager {

    /**
     * Option key used to persist content settings.
     */
    const OPTION_KEY = 'adc_login_content';

    /**
     * Cached defaults array.
     *
     * @var array
     */
    protected $defaults = array();

    /**
     * Retrieve default content values.
     *
     * @return array
     */
    public function get_defaults() {
        if ( empty( $this->defaults ) ) {
            $this->defaults = $this->get_default_content();
        }

        return $this->defaults;
    }

    /**
     * Bootstrap content filters.
     */
    public function init() {
        $this->defaults = $this->get_default_content();

        add_filter( 'adc_login_onboarding_slides', array( $this, 'filter_onboarding_slides' ), 5 );
        add_filter( 'adc_login_onboarding_brand_label', array( $this, 'filter_onboarding_brand_label' ), 5 );
        add_filter( 'adc_login_onboarding_skip_label', array( $this, 'filter_onboarding_skip_label' ), 5 );
        add_filter( 'adc_login_onboarding_login_link_label', array( $this, 'filter_onboarding_login_label' ), 5 );
        add_filter( 'adc_login_onboarding_signup_link_label', array( $this, 'filter_onboarding_signup_label' ), 5 );

        add_filter( 'adc_login_login_badge', array( $this, 'filter_login_badge' ), 5 );
        add_filter( 'adc_login_login_headline', array( $this, 'filter_login_headline' ), 5 );
        add_filter( 'adc_login_login_subtitle', array( $this, 'filter_login_subtitle' ), 5 );
        add_filter( 'adc_login_login_card_kicker', array( $this, 'filter_login_card_kicker' ), 5 );
        add_filter( 'adc_login_login_card_title', array( $this, 'filter_login_card_title' ), 5 );
        add_filter( 'adc_login_login_card_description', array( $this, 'filter_login_card_description' ), 5 );
        add_filter( 'adc_login_login_forgot_link_label', array( $this, 'filter_login_forgot_label' ), 5 );
        add_filter( 'adc_login_login_remember_label', array( $this, 'filter_login_remember_label' ), 5 );
        add_filter( 'adc_login_login_submit_label', array( $this, 'filter_login_submit_label' ), 5 );
        add_filter( 'adc_login_login_footer_prompt', array( $this, 'filter_login_footer_prompt' ), 5 );
        add_filter( 'adc_login_login_footer_link_label', array( $this, 'filter_login_footer_link' ), 5 );
        add_filter( 'adc_login_login_features', array( $this, 'filter_login_features' ), 5 );
        add_filter( 'adc_login_login_illustration', array( $this, 'filter_login_illustration' ), 5 );

        add_filter( 'adc_login_signup_badge', array( $this, 'filter_signup_badge' ), 5 );
        add_filter( 'adc_login_signup_headline', array( $this, 'filter_signup_headline' ), 5 );
        add_filter( 'adc_login_signup_subtitle', array( $this, 'filter_signup_subtitle' ), 5 );
        add_filter( 'adc_login_signup_card_kicker', array( $this, 'filter_signup_card_kicker' ), 5 );
        add_filter( 'adc_login_signup_card_title', array( $this, 'filter_signup_card_title' ), 5 );
        add_filter( 'adc_login_signup_card_description', array( $this, 'filter_signup_card_description' ), 5 );
        add_filter( 'adc_login_signup_submit_label', array( $this, 'filter_signup_submit_label' ), 5 );
        add_filter( 'adc_login_signup_footer_prompt', array( $this, 'filter_signup_footer_prompt' ), 5 );
        add_filter( 'adc_login_signup_footer_link_label', array( $this, 'filter_signup_footer_link' ), 5 );
        add_filter( 'adc_login_signup_terms_text', array( $this, 'filter_signup_terms_text' ), 5 );
        add_filter( 'adc_login_signup_terms_link_label', array( $this, 'filter_signup_terms_link' ), 5 );
        add_filter( 'adc_login_signup_features', array( $this, 'filter_signup_features' ), 5 );
        add_filter( 'adc_login_signup_illustration', array( $this, 'filter_signup_illustration' ), 5 );

        add_filter( 'adc_login_forgot_badge', array( $this, 'filter_forgot_badge' ), 5 );
        add_filter( 'adc_login_forgot_headline', array( $this, 'filter_forgot_headline' ), 5 );
        add_filter( 'adc_login_forgot_subtitle', array( $this, 'filter_forgot_subtitle' ), 5 );
        add_filter( 'adc_login_forgot_card_kicker', array( $this, 'filter_forgot_card_kicker' ), 5 );
        add_filter( 'adc_login_forgot_card_title', array( $this, 'filter_forgot_card_title' ), 5 );
        add_filter( 'adc_login_forgot_card_description', array( $this, 'filter_forgot_card_description' ), 5 );
        add_filter( 'adc_login_forgot_submit_label', array( $this, 'filter_forgot_submit_label' ), 5 );
        add_filter( 'adc_login_forgot_footer_link_label', array( $this, 'filter_forgot_footer_link' ), 5 );
        add_filter( 'adc_login_forgot_features', array( $this, 'filter_forgot_features' ), 5 );
        add_filter( 'adc_login_forgot_illustration', array( $this, 'filter_forgot_illustration' ), 5 );

        add_filter( 'adc_login_twofa_badge', array( $this, 'filter_twofa_badge' ), 5 );
        add_filter( 'adc_login_twofa_headline', array( $this, 'filter_twofa_headline' ), 5 );
        add_filter( 'adc_login_twofa_subtitle', array( $this, 'filter_twofa_subtitle' ), 5 );
        add_filter( 'adc_login_twofa_card_kicker', array( $this, 'filter_twofa_card_kicker' ), 5 );
        add_filter( 'adc_login_twofa_card_title', array( $this, 'filter_twofa_card_title' ), 5 );
        add_filter( 'adc_login_twofa_card_description', array( $this, 'filter_twofa_card_description' ), 5 );
        add_filter( 'adc_login_twofa_submit_label', array( $this, 'filter_twofa_submit_label' ), 5 );
        add_filter( 'adc_login_twofa_resend_label', array( $this, 'filter_twofa_resend_label' ), 5 );
        add_filter( 'adc_login_twofa_features', array( $this, 'filter_twofa_features' ), 5 );
        add_filter( 'adc_login_twofa_illustration', array( $this, 'filter_twofa_illustration' ), 5 );

        add_filter( 'adc_login_email_account_created_content', array( $this, 'filter_email_account_created_content' ), 5, 3 );
        add_filter( 'adc_login_email_password_reminder_content', array( $this, 'filter_email_password_reminder_content' ), 5, 3 );
        add_filter( 'adc_login_email_twofa_content', array( $this, 'filter_email_twofa_content' ), 5, 3 );
    }

    /**
     * Retrieve sanitized options merged with defaults.
     *
     * @return array
     */
    public function get_options() {
        $defaults = $this->get_defaults();
        $stored   = get_option( self::OPTION_KEY, array() );

        if ( ! is_array( $stored ) ) {
            $stored = array();
        }

        return $this->merge_defaults( $defaults, $stored );
    }

    /**
     * Sanitize and persist incoming dashboard payloads.
     *
     * @param array $payload Raw request payload.
     *
     * @return array
     */
    public function sanitize( $payload ) {
        $sanitized = $this->get_defaults();

        if ( ! is_array( $payload ) ) {
            return $sanitized;
        }

        // Onboarding copy.
        if ( isset( $payload['onboarding'] ) && is_array( $payload['onboarding'] ) ) {
            $sanitized['onboarding'] = $this->sanitize_onboarding( $payload['onboarding'] );
        }

        // Auth flows copy.
        foreach ( array( 'login', 'signup', 'forgot', 'twofa' ) as $section ) {
            if ( isset( $payload[ $section ] ) && is_array( $payload[ $section ] ) ) {
                $sanitized[ $section ] = $this->sanitize_auth_section( $section, $payload[ $section ] );
            }
        }

        if ( isset( $payload['emails'] ) && is_array( $payload['emails'] ) ) {
            $sanitized['emails'] = $this->sanitize_emails( $payload['emails'] );
        }

        return $sanitized;
    }

    /**
     * Provide default onboarding and authentication content.
     *
     * @return array
     */
    protected function get_default_content() {
        return array(
            'onboarding' => array(
                'brand_label'       => __( 'Academia da Comunicação', 'login-academia-da-comunicacao' ),
                'skip_label'        => __( 'Pular', 'login-academia-da-comunicacao' ),
                'login_link_label'  => __( 'Já tem conta? Faça login', 'login-academia-da-comunicacao' ),
                'signup_link_label' => __( 'Nova por aqui? Crie sua conta', 'login-academia-da-comunicacao' ),
                'slides'            => array(
                    array(
                        'title'     => __( 'Quiz On the Go', 'login-academia-da-comunicacao' ),
                        'text'      => __( 'Responda quizzes rápidos onde estiver e mantenha sua mente afiada.', 'login-academia-da-comunicacao' ),
                        'cta'       => '',
                        'image_id'  => 0,
                        'image_alt' => __( 'Ilustração de uma pessoa respondendo a um quiz no celular.', 'login-academia-da-comunicacao' ),
                        'fallback'  => get_asset_url( 'assets/img/onboarding-quiz.svg' ),
                    ),
                    array(
                        'title'     => __( 'Knowledge Boosting', 'login-academia-da-comunicacao' ),
                        'text'      => __( 'Descubra conteúdos envolventes para turbinar seus estudos diariamente.', 'login-academia-da-comunicacao' ),
                        'cta'       => '',
                        'image_id'  => 0,
                        'image_alt' => __( 'Ilustração abstrata representando crescimento de conhecimento.', 'login-academia-da-comunicacao' ),
                        'fallback'  => get_asset_url( 'assets/img/onboarding-knowledge.svg' ),
                    ),
                    array(
                        'title'     => __( 'Win Rewards Galore', 'login-academia-da-comunicacao' ),
                        'text'      => __( 'Ganhe recompensas enquanto aprende com desafios pensados para você.', 'login-academia-da-comunicacao' ),
                        'cta'       => __( 'Get Started', 'login-academia-da-comunicacao' ),
                        'image_id'  => 0,
                        'image_alt' => __( 'Ilustração com medalhas e troféus simbolizando recompensas.', 'login-academia-da-comunicacao' ),
                        'fallback'  => get_asset_url( 'assets/img/onboarding-rewards.svg' ),
                    ),
                ),
            ),
            'login'      => array(
                'badge'            => __( 'Aprenda sem limites', 'login-academia-da-comunicacao' ),
                'headline'         => __( 'Bem-vindo de volta! 💜', 'login-academia-da-comunicacao' ),
                'subtitle'         => __( 'Retome seus cursos exatamente de onde parou e acompanhe seu progresso em tempo real.', 'login-academia-da-comunicacao' ),
                'card_kicker'      => __( 'Bem-vindo de volta', 'login-academia-da-comunicacao' ),
                'card_title'       => __( 'Entrar', 'login-academia-da-comunicacao' ),
                'card_description' => __( 'Acesse sua conta para continuar aprendendo.', 'login-academia-da-comunicacao' ),
                'forgot_label'     => __( 'Esqueceu a senha?', 'login-academia-da-comunicacao' ),
                'remember_label'   => __( 'Manter conectado', 'login-academia-da-comunicacao' ),
                'submit_label'     => __( 'Entrar', 'login-academia-da-comunicacao' ),
                'footer_prompt'    => __( 'Não tem conta?', 'login-academia-da-comunicacao' ),
                'footer_link'      => __( 'Cadastre-se', 'login-academia-da-comunicacao' ),
                'features'         => array(
                    __( 'Planos de estudo personalizados em minutos.', 'login-academia-da-comunicacao' ),
                    __( 'Aulas mobile-first para aprender onde estiver.', 'login-academia-da-comunicacao' ),
                    __( 'Desafios gamificados com recompensas reais.', 'login-academia-da-comunicacao' ),
                ),
                'illustration'     => array(
                    'image_id' => 0,
                    'image_alt' => __( 'Pessoas conectadas celebrando conquistas de aprendizagem.', 'login-academia-da-comunicacao' ),
                    'fallback' => get_asset_url( 'assets/img/auth-illustration.svg' ),
                ),
            ),
            'signup'     => array(
                'badge'            => __( 'Jornada personalizada', 'login-academia-da-comunicacao' ),
                'headline'         => __( 'Crie sua conta e desbloqueie o melhor da comunicação.', 'login-academia-da-comunicacao' ),
                'subtitle'         => __( 'Domine apresentações, storytelling e técnicas de influência com roteiros feitos para você.', 'login-academia-da-comunicacao' ),
                'card_kicker'      => __( 'Vamos começar', 'login-academia-da-comunicacao' ),
                'card_title'       => __( 'Criar conta', 'login-academia-da-comunicacao' ),
                'card_description' => __( 'Complete seus dados para personalizarmos sua experiência.', 'login-academia-da-comunicacao' ),
                'submit_label'     => __( 'Criar conta', 'login-academia-da-comunicacao' ),
                'footer_prompt'    => __( 'Já tem conta?', 'login-academia-da-comunicacao' ),
                'footer_link'      => __( 'Entrar', 'login-academia-da-comunicacao' ),
                'terms_text'       => __( 'Li e aceito os Termos, Política e eventuais taxas.', 'login-academia-da-comunicacao' ),
                'terms_link'       => __( 'Saiba mais', 'login-academia-da-comunicacao' ),
                'features'         => array(
                    __( 'Aulas ao vivo e gravadas em um só lugar.', 'login-academia-da-comunicacao' ),
                    __( 'Metas semanais para manter sua motivação.', 'login-academia-da-comunicacao' ),
                    __( 'Suporte da comunidade e mentores certificados.', 'login-academia-da-comunicacao' ),
                ),
                'illustration'     => array(
                    'image_id' => 0,
                    'image_alt' => __( 'Pessoas conectadas celebrando conquistas de aprendizagem.', 'login-academia-da-comunicacao' ),
                    'fallback' => get_asset_url( 'assets/img/auth-illustration.svg' ),
                ),
            ),
            'forgot'     => array(
                'badge'            => __( 'Tudo sob controle', 'login-academia-da-comunicacao' ),
                'headline'         => __( 'Vamos recuperar seu acesso.', 'login-academia-da-comunicacao' ),
                'subtitle'         => __( 'Não se preocupe, enviaremos um link seguro para você redefinir a senha em instantes.', 'login-academia-da-comunicacao' ),
                'card_kicker'      => __( 'Esqueceu a senha?', 'login-academia-da-comunicacao' ),
                'card_title'       => __( 'Recuperar senha', 'login-academia-da-comunicacao' ),
                'card_description' => __( 'Informe seu e-mail para receber um link de redefinição.', 'login-academia-da-comunicacao' ),
                'submit_label'     => __( 'Enviar instruções', 'login-academia-da-comunicacao' ),
                'footer_link'      => __( 'Voltar ao login', 'login-academia-da-comunicacao' ),
                'features'         => array(
                    __( 'Receba o passo a passo por e-mail em poucos segundos.', 'login-academia-da-comunicacao' ),
                    __( 'Proteção reforçada com 2FA e verificações inteligentes.', 'login-academia-da-comunicacao' ),
                    __( 'Equipe de suporte pronta para ajudar quando precisar.', 'login-academia-da-comunicacao' ),
                ),
                'illustration'     => array(
                    'image_id' => 0,
                    'image_alt' => __( 'Pessoas conectadas celebrando conquistas de aprendizagem.', 'login-academia-da-comunicacao' ),
                    'fallback' => get_asset_url( 'assets/img/auth-illustration.svg' ),
                ),
            ),
            'twofa'      => array(
                'badge'            => __( 'Segurança reforçada', 'login-academia-da-comunicacao' ),
                'headline'         => __( 'Confirme que é você.', 'login-academia-da-comunicacao' ),
                'subtitle'         => __( 'Use o código enviado por e-mail para validar seu acesso e continuar aprendendo com tranquilidade.', 'login-academia-da-comunicacao' ),
                'card_kicker'      => __( 'Passo extra para proteger sua conta', 'login-academia-da-comunicacao' ),
                'card_title'       => __( 'Verificação em duas etapas', 'login-academia-da-comunicacao' ),
                'card_description' => __( 'Enviamos um código de 6 dígitos para o seu e-mail. Ele expira em 10 minutos.', 'login-academia-da-comunicacao' ),
                'submit_label'     => __( 'Validar código', 'login-academia-da-comunicacao' ),
                'resend_label'     => __( 'Reenviar código', 'login-academia-da-comunicacao' ),
                'features'         => array(
                    __( 'Seus dados protegidos com verificação em dois passos.', 'login-academia-da-comunicacao' ),
                    __( 'Códigos com validade de 10 minutos para mais segurança.', 'login-academia-da-comunicacao' ),
                    __( 'Você pode reenviar o código quantas vezes precisar.', 'login-academia-da-comunicacao' ),
                ),
                'illustration'     => array(
                    'image_id' => 0,
                    'image_alt' => __( 'Pessoas conectadas celebrando conquistas de aprendizagem.', 'login-academia-da-comunicacao' ),
                    'fallback' => get_asset_url( 'assets/img/auth-illustration.svg' ),
                ),
            ),
            'emails'     => array(
                'account_created'   => array(
                    'headline' => __( 'Bem-vindo à Academia da Comunicação!', 'login-academia-da-comunicacao' ),
                    'intro'    => __( 'Olá %s, sua conta está ativa e pronta para turbinar seus estudos.', 'login-academia-da-comunicacao' ),
                    'body'     => __( 'Use o botão abaixo para acessar a plataforma ou, se preferir, faça login pelo aplicativo quando quiser.', 'login-academia-da-comunicacao' ),
                    'cta_label' => __( 'Acessar minha conta', 'login-academia-da-comunicacao' ),
                    'cta_url'   => '',
                    'footer'   => __( 'Precisa de ajuda? Responda este e-mail e nossa equipe retorna rapidinho.', 'login-academia-da-comunicacao' ),
                    'hero'     => array(
                        'image_id'  => 0,
                        'image_alt' => __( 'Equipe celebrando a criação de uma nova conta.', 'login-academia-da-comunicacao' ),
                        'fallback'  => get_asset_url( 'assets/img/auth-illustration.svg' ),
                    ),
                ),
                'password_reminder' => array(
                    'headline' => __( 'Vamos redefinir sua senha?', 'login-academia-da-comunicacao' ),
                    'intro'    => __( 'Olá %s, recebemos seu pedido para redefinir a senha.', 'login-academia-da-comunicacao' ),
                    'body'     => __( 'Clique no botão abaixo para criar uma nova senha segura. Este link é válido por tempo limitado.', 'login-academia-da-comunicacao' ),
                    'cta_label' => __( 'Criar nova senha', 'login-academia-da-comunicacao' ),
                    'cta_url'   => '',
                    'footer'   => __( 'Se você não solicitou esta alteração, ignore este e-mail ou altere sua senha imediatamente.', 'login-academia-da-comunicacao' ),
                    'hero'     => array(
                        'image_id'  => 0,
                        'image_alt' => __( 'Pessoa redefinindo senha em um laptop.', 'login-academia-da-comunicacao' ),
                        'fallback'  => get_asset_url( 'assets/img/onboarding-knowledge.svg' ),
                    ),
                ),
                'twofa'              => array(
                    'headline' => __( 'Seu código de verificação', 'login-academia-da-comunicacao' ),
                    'intro'    => __( 'Use o código abaixo para confirmar que é você acessando a Academia da Comunicação.', 'login-academia-da-comunicacao' ),
                    'body'     => __( 'Este código expira em {{expires}}. Se não foi você, recomendamos redefinir sua senha imediatamente.', 'login-academia-da-comunicacao' ),
                    'cta_label' => __( 'Proteger minha conta', 'login-academia-da-comunicacao' ),
                    'cta_url'   => '',
                    'footer'   => __( 'Dica: ative o 2FA sempre que possível para manter sua conta mais segura.', 'login-academia-da-comunicacao' ),
                    'hero'     => array(
                        'image_id'  => 0,
                        'image_alt' => __( 'Escudo representando segurança da conta.', 'login-academia-da-comunicacao' ),
                        'fallback'  => get_asset_url( 'assets/img/onboarding-rewards.svg' ),
                    ),
                ),
            ),
        );
    }

    /**
     * Sanitize onboarding section payload.
     *
     * @param array $payload Onboarding payload.
     *
     * @return array
     */
    protected function sanitize_onboarding( array $payload ) {
        $defaults = $this->get_defaults()['onboarding'];

        $sanitized = array(
            'brand_label'       => isset( $payload['brand_label'] ) ? sanitize_text_field( $payload['brand_label'] ) : $defaults['brand_label'],
            'skip_label'        => isset( $payload['skip_label'] ) ? sanitize_text_field( $payload['skip_label'] ) : $defaults['skip_label'],
            'login_link_label'  => isset( $payload['login_link_label'] ) ? sanitize_text_field( $payload['login_link_label'] ) : $defaults['login_link_label'],
            'signup_link_label' => isset( $payload['signup_link_label'] ) ? sanitize_text_field( $payload['signup_link_label'] ) : $defaults['signup_link_label'],
            'slides'            => $defaults['slides'],
        );

        if ( isset( $payload['slides'] ) && is_array( $payload['slides'] ) ) {
            $sanitized['slides'] = $this->sanitize_slides( $payload['slides'], $defaults['slides'] );
        }

        return $sanitized;
    }

    /**
     * Sanitize onboarding slides payload.
     *
     * @param array $slides   Incoming slides.
     * @param array $defaults Default slide structure.
     *
     * @return array
     */
    protected function sanitize_slides( array $slides, array $defaults ) {
        $sanitized = array();

        foreach ( $defaults as $index => $default_slide ) {
            $slide_payload = isset( $slides[ $index ] ) && is_array( $slides[ $index ] ) ? $slides[ $index ] : array();

            $sanitized_slide = $default_slide;
            $sanitized_slide['title']     = isset( $slide_payload['title'] ) ? sanitize_text_field( $slide_payload['title'] ) : $default_slide['title'];
            $sanitized_slide['text']      = isset( $slide_payload['text'] ) ? sanitize_textarea_field( $slide_payload['text'] ) : $default_slide['text'];
            $sanitized_slide['cta']       = isset( $slide_payload['cta'] ) ? sanitize_text_field( $slide_payload['cta'] ) : $default_slide['cta'];
            $sanitized_slide['image_id']  = isset( $slide_payload['image_id'] ) ? absint( $slide_payload['image_id'] ) : 0;
            $sanitized_slide['image_alt'] = isset( $slide_payload['image_alt'] ) ? sanitize_text_field( $slide_payload['image_alt'] ) : $default_slide['image_alt'];

            $sanitized[] = $sanitized_slide;
        }

        return $sanitized;
    }

    /**
     * Sanitize authentication section payloads (login/signup/forgot/twofa).
     *
     * @param string $section Section key.
     * @param array  $payload Section payload.
     *
     * @return array
     */
    protected function sanitize_auth_section( $section, array $payload ) {
        $defaults = $this->get_defaults()[ $section ];
        $sanitized = $defaults;

        foreach ( $defaults as $key => $default_value ) {
            if ( 'features' === $key ) {
                $sanitized['features'] = $this->sanitize_features( isset( $payload['features'] ) ? $payload['features'] : array(), $default_value );
                continue;
            }

            if ( 'illustration' === $key ) {
                $sanitized['illustration'] = $this->sanitize_illustration( isset( $payload['illustration'] ) ? $payload['illustration'] : array(), $default_value );
                continue;
            }

            if ( 'terms_text' === $key ) {
                $sanitized[ $key ] = isset( $payload[ $key ] ) ? sanitize_textarea_field( $payload[ $key ] ) : $default_value;
                continue;
            }

            if ( in_array( $key, array( 'subtitle', 'card_description' ), true ) ) {
                $sanitized[ $key ] = isset( $payload[ $key ] ) ? sanitize_textarea_field( $payload[ $key ] ) : $default_value;
                continue;
            }

            $sanitized[ $key ] = isset( $payload[ $key ] ) ? sanitize_text_field( $payload[ $key ] ) : $default_value;
        }

        return $sanitized;
    }

    /**
     * Sanitize transactional email content payload.
     *
     * @param array $payload Raw payload.
     *
     * @return array
     */
    protected function sanitize_emails( array $payload ) {
        $defaults = $this->get_defaults();
        $emails   = isset( $defaults['emails'] ) ? $defaults['emails'] : array();

        if ( empty( $emails ) ) {
            return array();
        }

        $sanitized = $emails;

        foreach ( $emails as $key => $default ) {
            if ( isset( $payload[ $key ] ) && is_array( $payload[ $key ] ) ) {
                $sanitized[ $key ] = $this->sanitize_email_section( $payload[ $key ], $default );
            }
        }

        return $sanitized;
    }

    /**
     * Sanitize an individual transactional email payload.
     *
     * @param array $payload  Raw payload.
     * @param array $defaults Default values.
     *
     * @return array
     */
    protected function sanitize_email_section( array $payload, array $defaults ) {
        $sanitized = $defaults;

        $text_fields = array(
            'headline'  => 'sanitize_text_field',
            'cta_label' => 'sanitize_text_field',
        );

        $textarea_fields = array( 'intro', 'body', 'footer' );

        foreach ( $text_fields as $field => $callback ) {
            if ( isset( $payload[ $field ] ) ) {
                $sanitized[ $field ] = call_user_func( $callback, $payload[ $field ] );
            }
        }

        foreach ( $textarea_fields as $field ) {
            if ( isset( $payload[ $field ] ) ) {
                $sanitized[ $field ] = sanitize_textarea_field( $payload[ $field ] );
            }
        }

        if ( isset( $payload['cta_url'] ) ) {
            $sanitized['cta_url'] = esc_url_raw( $payload['cta_url'] );
        }

        $sanitized['hero'] = $this->sanitize_illustration(
            isset( $payload['hero'] ) && is_array( $payload['hero'] ) ? $payload['hero'] : array(),
            isset( $defaults['hero'] ) ? $defaults['hero'] : array()
        );

        return $sanitized;
    }

    /**
     * Sanitize feature list payload.
     *
     * @param mixed $features Payload features.
     * @param array $default  Default feature list.
     *
     * @return array
     */
    protected function sanitize_features( $features, array $default ) {
        $items = array();

        if ( is_string( $features ) ) {
            $features = preg_split( '/\r\n|\r|\n/', $features );
        }

        if ( is_array( $features ) ) {
            foreach ( $features as $feature ) {
                $feature = sanitize_text_field( $feature );
                if ( '' !== $feature ) {
                    $items[] = $feature;
                }
            }
        }

        if ( empty( $items ) ) {
            return $default;
        }

        return array_values( $items );
    }

    /**
     * Sanitize illustration payload.
     *
     * @param array $illustration Incoming illustration data.
     * @param array $default      Default illustration data.
     *
     * @return array
     */
    protected function sanitize_illustration( $illustration, array $default ) {
        $sanitized = $default;

        if ( ! is_array( $illustration ) ) {
            return $sanitized;
        }

        $sanitized['image_id']  = isset( $illustration['image_id'] ) ? absint( $illustration['image_id'] ) : 0;
        $sanitized['image_alt'] = isset( $illustration['image_alt'] ) ? sanitize_text_field( $illustration['image_alt'] ) : $default['image_alt'];

        return $sanitized;
    }

    /**
     * Merge arbitrary options with defaults recursively.
     *
     * @param array $options Custom options array.
     *
     * @return array
     */
    protected function merge_defaults( array $defaults, array $options ) {
        return array_replace_recursive( $defaults, $options );
    }

    /**
     * Retrieve a section configuration.
     *
     * @param string $section Section key.
     *
     * @return array
     */
    protected function get_section( $section ) {
        $options = $this->get_options();

        $defaults = $this->get_defaults();

        return isset( $options[ $section ] ) && is_array( $options[ $section ] ) ? $options[ $section ] : ( isset( $defaults[ $section ] ) ? $defaults[ $section ] : array() );
    }

    /**
     * Retrieve a field from a section, falling back to defaults.
     *
     * @param string $section Section key.
     * @param string $field   Field key.
     *
     * @return string
     */
    protected function get_string_field( $section, $field ) {
        $section_data = $this->get_section( $section );

        if ( isset( $section_data[ $field ] ) && '' !== $section_data[ $field ] ) {
            return $section_data[ $field ];
        }

        $defaults = $this->get_defaults();

        return isset( $defaults[ $section ][ $field ] ) ? $defaults[ $section ][ $field ] : '';
    }

    /**
     * Retrieve features array for a section.
     *
     * @param string $section Section key.
     *
     * @return array
     */
    protected function get_features( $section ) {
        $section_data = $this->get_section( $section );

        if ( isset( $section_data['features'] ) && is_array( $section_data['features'] ) && ! empty( $section_data['features'] ) ) {
            return array_values( array_filter( $section_data['features'], 'strlen' ) );
        }

        $defaults = $this->get_defaults();

        return isset( $defaults[ $section ]['features'] ) ? $defaults[ $section ]['features'] : array();
    }

    /**
     * Retrieve illustration config for a section.
     *
     * @param string $section Section key.
     *
     * @return array
     */
    protected function get_illustration( $section ) {
        $section_data = $this->get_section( $section );
        $defaults     = $this->get_defaults();
        $default      = isset( $defaults[ $section ]['illustration'] ) ? $defaults[ $section ]['illustration'] : array();

        if ( isset( $section_data['illustration'] ) && is_array( $section_data['illustration'] ) ) {
            $data = array_merge( $default, $section_data['illustration'] );
        } else {
            $data = $default;
        }

        $attachment_id = isset( $data['image_id'] ) ? absint( $data['image_id'] ) : 0;
        $src           = $attachment_id ? wp_get_attachment_image_url( $attachment_id, 'full' ) : '';

        if ( ! $src ) {
            $src = isset( $default['fallback'] ) ? $default['fallback'] : '';
        }

        return array(
            'src' => $src,
            'alt' => isset( $data['image_alt'] ) ? $data['image_alt'] : '',
        );
    }

    /**
     * Filter onboarding slides with stored content.
     *
     * @param array $slides Existing slides.
     *
     * @return array
     */
    public function filter_onboarding_slides( $slides ) {
        $options = $this->get_section( 'onboarding' );
        $stored  = isset( $options['slides'] ) && is_array( $options['slides'] ) ? $options['slides'] : array();

        if ( empty( $stored ) ) {
            return $slides;
        }

        $custom = array();

        $defaults = $this->get_defaults();

        foreach ( $defaults['onboarding']['slides'] as $index => $default_slide ) {
            $base   = isset( $slides[ $index ] ) ? $slides[ $index ] : $default_slide;
            $stored_slide = isset( $stored[ $index ] ) && is_array( $stored[ $index ] ) ? $stored[ $index ] : array();

            $title = isset( $stored_slide['title'] ) && '' !== $stored_slide['title'] ? $stored_slide['title'] : ( isset( $base['title'] ) ? $base['title'] : '' );
            $text  = isset( $stored_slide['text'] ) && '' !== $stored_slide['text'] ? $stored_slide['text'] : ( isset( $base['text'] ) ? $base['text'] : '' );
            $cta   = isset( $stored_slide['cta'] ) ? $stored_slide['cta'] : ( isset( $base['cta'] ) ? $base['cta'] : '' );

            $attachment_id = isset( $stored_slide['image_id'] ) ? absint( $stored_slide['image_id'] ) : 0;
            $image_src     = $attachment_id ? wp_get_attachment_image_url( $attachment_id, 'full' ) : '';
            if ( ! $image_src ) {
                $image_src = isset( $base['image']['src'] ) ? $base['image']['src'] : ( isset( $default_slide['fallback'] ) ? $default_slide['fallback'] : '' );
            }

            $image_alt = isset( $stored_slide['image_alt'] ) && '' !== $stored_slide['image_alt'] ? $stored_slide['image_alt'] : ( isset( $base['image']['alt'] ) ? $base['image']['alt'] : $default_slide['image_alt'] );

            $custom_slide = $base;
            $custom_slide['title'] = $title;
            $custom_slide['text']  = $text;

            if ( '' !== $cta ) {
                $custom_slide['cta'] = $cta;
            } elseif ( isset( $custom_slide['cta'] ) ) {
                unset( $custom_slide['cta'] );
            }

            $custom_slide['image'] = array(
                'src' => $image_src,
                'alt' => $image_alt,
            );

            $custom[] = $custom_slide;
        }

        return $custom;
    }

    /**
     * Filter onboarding brand label.
     *
     * @param string $label Default label.
     *
     * @return string
     */
    public function filter_onboarding_brand_label( $label ) {
        $value = $this->get_string_field( 'onboarding', 'brand_label' );

        return '' !== $value ? $value : $label;
    }

    /**
     * Filter onboarding skip label.
     *
     * @param string $label Default label.
     *
     * @return string
     */
    public function filter_onboarding_skip_label( $label ) {
        $value = $this->get_string_field( 'onboarding', 'skip_label' );

        return '' !== $value ? $value : $label;
    }

    /**
     * Filter onboarding login link label.
     *
     * @param string $label Default label.
     *
     * @return string
     */
    public function filter_onboarding_login_label( $label ) {
        $value = $this->get_string_field( 'onboarding', 'login_link_label' );

        return '' !== $value ? $value : $label;
    }

    /**
     * Filter onboarding signup link label.
     *
     * @param string $label Default label.
     *
     * @return string
     */
    public function filter_onboarding_signup_label( $label ) {
        $value = $this->get_string_field( 'onboarding', 'signup_link_label' );

        return '' !== $value ? $value : $label;
    }

    /**
     * Filter login badge.
     */
    public function filter_login_badge( $value ) {
        $stored = $this->get_string_field( 'login', 'badge' );

        return '' !== $stored ? $stored : $value;
    }

    /**
     * Filter login headline.
     */
    public function filter_login_headline( $value ) {
        $stored = $this->get_string_field( 'login', 'headline' );

        return '' !== $stored ? $stored : $value;
    }

    /**
     * Filter login subtitle.
     */
    public function filter_login_subtitle( $value ) {
        $stored = $this->get_string_field( 'login', 'subtitle' );

        return '' !== $stored ? $stored : $value;
    }

    /**
     * Filter login card kicker.
     */
    public function filter_login_card_kicker( $value ) {
        $stored = $this->get_string_field( 'login', 'card_kicker' );

        return '' !== $stored ? $stored : $value;
    }

    /**
     * Filter login card title.
     */
    public function filter_login_card_title( $value ) {
        $stored = $this->get_string_field( 'login', 'card_title' );

        return '' !== $stored ? $stored : $value;
    }

    /**
     * Filter login card description.
     */
    public function filter_login_card_description( $value ) {
        $stored = $this->get_string_field( 'login', 'card_description' );

        return '' !== $stored ? $stored : $value;
    }

    /**
     * Filter login forgot label.
     */
    public function filter_login_forgot_label( $value ) {
        $stored = $this->get_string_field( 'login', 'forgot_label' );

        return '' !== $stored ? $stored : $value;
    }

    /**
     * Filter login remember label.
     */
    public function filter_login_remember_label( $value ) {
        $stored = $this->get_string_field( 'login', 'remember_label' );

        return '' !== $stored ? $stored : $value;
    }

    /**
     * Filter login submit label.
     */
    public function filter_login_submit_label( $value ) {
        $stored = $this->get_string_field( 'login', 'submit_label' );

        return '' !== $stored ? $stored : $value;
    }

    /**
     * Filter login footer prompt.
     */
    public function filter_login_footer_prompt( $value ) {
        $stored = $this->get_string_field( 'login', 'footer_prompt' );

        return '' !== $stored ? $stored : $value;
    }

    /**
     * Filter login footer link label.
     */
    public function filter_login_footer_link( $value ) {
        $stored = $this->get_string_field( 'login', 'footer_link' );

        return '' !== $stored ? $stored : $value;
    }

    /**
     * Filter login features.
     */
    public function filter_login_features( $features ) {
        $stored = $this->get_features( 'login' );

        return ! empty( $stored ) ? $stored : $features;
    }

    /**
     * Filter login illustration.
     */
    public function filter_login_illustration( $illustration ) {
        $stored = $this->get_illustration( 'login' );

        return ! empty( $stored['src'] ) ? $stored : $illustration;
    }

    /** Signup filters */
    public function filter_signup_badge( $value ) {
        $stored = $this->get_string_field( 'signup', 'badge' );

        return '' !== $stored ? $stored : $value;
    }

    public function filter_signup_headline( $value ) {
        $stored = $this->get_string_field( 'signup', 'headline' );

        return '' !== $stored ? $stored : $value;
    }

    public function filter_signup_subtitle( $value ) {
        $stored = $this->get_string_field( 'signup', 'subtitle' );

        return '' !== $stored ? $stored : $value;
    }

    public function filter_signup_card_kicker( $value ) {
        $stored = $this->get_string_field( 'signup', 'card_kicker' );

        return '' !== $stored ? $stored : $value;
    }

    public function filter_signup_card_title( $value ) {
        $stored = $this->get_string_field( 'signup', 'card_title' );

        return '' !== $stored ? $stored : $value;
    }

    public function filter_signup_card_description( $value ) {
        $stored = $this->get_string_field( 'signup', 'card_description' );

        return '' !== $stored ? $stored : $value;
    }

    public function filter_signup_submit_label( $value ) {
        $stored = $this->get_string_field( 'signup', 'submit_label' );

        return '' !== $stored ? $stored : $value;
    }

    public function filter_signup_footer_prompt( $value ) {
        $stored = $this->get_string_field( 'signup', 'footer_prompt' );

        return '' !== $stored ? $stored : $value;
    }

    public function filter_signup_footer_link( $value ) {
        $stored = $this->get_string_field( 'signup', 'footer_link' );

        return '' !== $stored ? $stored : $value;
    }

    public function filter_signup_terms_text( $value ) {
        $stored = $this->get_string_field( 'signup', 'terms_text' );

        return '' !== $stored ? $stored : $value;
    }

    public function filter_signup_terms_link( $value ) {
        $stored = $this->get_string_field( 'signup', 'terms_link' );

        return '' !== $stored ? $stored : $value;
    }

    public function filter_signup_features( $features ) {
        $stored = $this->get_features( 'signup' );

        return ! empty( $stored ) ? $stored : $features;
    }

    public function filter_signup_illustration( $illustration ) {
        $stored = $this->get_illustration( 'signup' );

        return ! empty( $stored['src'] ) ? $stored : $illustration;
    }

    /** Forgot filters */
    public function filter_forgot_badge( $value ) {
        $stored = $this->get_string_field( 'forgot', 'badge' );

        return '' !== $stored ? $stored : $value;
    }

    public function filter_forgot_headline( $value ) {
        $stored = $this->get_string_field( 'forgot', 'headline' );

        return '' !== $stored ? $stored : $value;
    }

    public function filter_forgot_subtitle( $value ) {
        $stored = $this->get_string_field( 'forgot', 'subtitle' );

        return '' !== $stored ? $stored : $value;
    }

    public function filter_forgot_card_kicker( $value ) {
        $stored = $this->get_string_field( 'forgot', 'card_kicker' );

        return '' !== $stored ? $stored : $value;
    }

    public function filter_forgot_card_title( $value ) {
        $stored = $this->get_string_field( 'forgot', 'card_title' );

        return '' !== $stored ? $stored : $value;
    }

    public function filter_forgot_card_description( $value ) {
        $stored = $this->get_string_field( 'forgot', 'card_description' );

        return '' !== $stored ? $stored : $value;
    }

    public function filter_forgot_submit_label( $value ) {
        $stored = $this->get_string_field( 'forgot', 'submit_label' );

        return '' !== $stored ? $stored : $value;
    }

    public function filter_forgot_footer_link( $value ) {
        $stored = $this->get_string_field( 'forgot', 'footer_link' );

        return '' !== $stored ? $stored : $value;
    }

    public function filter_forgot_features( $features ) {
        $stored = $this->get_features( 'forgot' );

        return ! empty( $stored ) ? $stored : $features;
    }

    public function filter_forgot_illustration( $illustration ) {
        $stored = $this->get_illustration( 'forgot' );

        return ! empty( $stored['src'] ) ? $stored : $illustration;
    }

    /** Two-factor filters */
    public function filter_twofa_badge( $value ) {
        $stored = $this->get_string_field( 'twofa', 'badge' );

        return '' !== $stored ? $stored : $value;
    }

    public function filter_twofa_headline( $value ) {
        $stored = $this->get_string_field( 'twofa', 'headline' );

        return '' !== $stored ? $stored : $value;
    }

    public function filter_twofa_subtitle( $value ) {
        $stored = $this->get_string_field( 'twofa', 'subtitle' );

        return '' !== $stored ? $stored : $value;
    }

    public function filter_twofa_card_kicker( $value ) {
        $stored = $this->get_string_field( 'twofa', 'card_kicker' );

        return '' !== $stored ? $stored : $value;
    }

    public function filter_twofa_card_title( $value ) {
        $stored = $this->get_string_field( 'twofa', 'card_title' );

        return '' !== $stored ? $stored : $value;
    }

    public function filter_twofa_card_description( $value ) {
        $stored = $this->get_string_field( 'twofa', 'card_description' );

        return '' !== $stored ? $stored : $value;
    }

    public function filter_twofa_submit_label( $value ) {
        $stored = $this->get_string_field( 'twofa', 'submit_label' );

        return '' !== $stored ? $stored : $value;
    }

    public function filter_twofa_resend_label( $value ) {
        $stored = $this->get_string_field( 'twofa', 'resend_label' );

        return '' !== $stored ? $stored : $value;
    }

    public function filter_twofa_features( $features ) {
        $stored = $this->get_features( 'twofa' );

        return ! empty( $stored ) ? $stored : $features;
    }

    public function filter_twofa_illustration( $illustration ) {
        $stored = $this->get_illustration( 'twofa' );

        return ! empty( $stored['src'] ) ? $stored : $illustration;
    }

    /**
     * Filter account created email copy.
     *
     * @param array    $copy    Default copy.
     * @param \WP_User $user    User object.
     * @param array    $context Additional context.
     *
     * @return array
     */
    public function filter_email_account_created_content( $copy, $user = null, $context = array() ) {
        return $this->merge_email_copy( 'account_created', $copy );
    }

    /**
     * Filter password reminder email copy.
     *
     * @param array    $copy    Default copy.
     * @param \WP_User $user    User object.
     * @param array    $context Additional context.
     *
     * @return array
     */
    public function filter_email_password_reminder_content( $copy, $user = null, $context = array() ) {
        return $this->merge_email_copy( 'password_reminder', $copy );
    }

    /**
     * Filter 2FA email copy.
     *
     * @param array    $copy    Default copy.
     * @param \WP_User $user    User object.
     * @param array    $context Additional context.
     *
     * @return array
     */
    public function filter_email_twofa_content( $copy, $user = null, $context = array() ) {
        return $this->merge_email_copy( 'twofa', $copy );
    }

    /**
     * Merge stored email content onto defaults.
     *
     * @param string $section Email section key.
     * @param array  $copy    Default copy array.
     *
     * @return array
     */
    protected function merge_email_copy( $section, array $copy ) {
        $stored = $this->get_email_content( $section );

        foreach ( array( 'headline', 'intro', 'body', 'cta_label', 'cta_url', 'footer' ) as $field ) {
            if ( isset( $stored[ $field ] ) && '' !== $stored[ $field ] ) {
                $copy[ $field ] = $stored[ $field ];
            }
        }

        if ( isset( $stored['hero'] ) && ! empty( $stored['hero']['src'] ) ) {
            $copy['hero'] = $stored['hero'];
        }

        return $copy;
    }

    /**
     * Retrieve stored email content merged with defaults.
     *
     * @param string $section Email section key.
     *
     * @return array
     */
    protected function get_email_content( $section ) {
        $defaults = $this->get_defaults();
        $default  = isset( $defaults['emails'][ $section ] ) ? $defaults['emails'][ $section ] : array();

        $options = $this->get_options();
        $stored  = isset( $options['emails'][ $section ] ) && is_array( $options['emails'][ $section ] ) ? $options['emails'][ $section ] : array();

        $data = array_replace_recursive( $default, $stored );

        $hero_default = isset( $default['hero'] ) ? $default['hero'] : array();
        $hero         = isset( $stored['hero'] ) && is_array( $stored['hero'] ) ? array_replace_recursive( $hero_default, $stored['hero'] ) : $hero_default;

        $attachment_id = isset( $hero['image_id'] ) ? absint( $hero['image_id'] ) : 0;
        $src           = $attachment_id ? wp_get_attachment_image_url( $attachment_id, 'full' ) : '';

        if ( ! $src && isset( $hero['fallback'] ) ) {
            $src = $hero['fallback'];
        }

        $data['hero'] = array(
            'src' => $src,
            'alt' => isset( $hero['image_alt'] ) ? $hero['image_alt'] : ( isset( $hero_default['image_alt'] ) ? $hero_default['image_alt'] : '' ),
        );

        return $data;
    }
}
