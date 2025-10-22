<?php
/**
 * Registro e renderização dos shortcodes do plugin.
 */
class Introducao_Shortcodes {

    /**
     * Instância principal do plugin.
     *
     * @var Introducao_Plugin
     */
    private $plugin;

    /**
     * Gerenciador de páginas.
     *
     * @var Introducao_Pages
     */
    private $pages;

    /**
     * Mapeamento dos shortcodes de páginas para seus templates.
     *
     * @var array
     */
    private $page_shortcodes = array(
        'introducao_teoria'              => 'page-teoria.php',
        'introducao_pratica'             => 'page-pratica.php',
        'introducao_meus_conhecimentos'  => 'page-meus-conhecimentos.php',
        'introducao_treinador'           => 'page-treinador.php',
        'introducao_planos'              => 'page-planos.php',
        'introducao_configuracoes'       => 'page-configuracoes.php',
        'introducao_suporte'             => 'page-suporte.php',
        'introducao_perfil'              => 'page-perfil.php',
        'introducao_onboarding_slider'   => 'slider-onboarding.php',
    );

    /**
     * Shortcodes legados para compatibilidade.
     *
     * @var array
     */
    private $legacy_page_shortcodes = array(
        'lae_teoria'             => 'page-teoria.php',
        'lae_pratica'            => 'page-pratica.php',
        'lae_meus_conhecimentos' => 'page-meus-conhecimentos.php',
        'lae_treinador'          => 'page-treinador.php',
        'lae_planos'             => 'page-planos.php',
        'lae_configuracoes'      => 'page-configuracoes.php',
        'lae_suporte'            => 'page-suporte.php',
        'lae_perfil'             => 'page-perfil.php',
        'lae_onboarding_slider'  => 'slider-onboarding.php',
    );

    /**
     * Construtor.
     *
     * @param Introducao_Plugin $plugin Instância principal.
     * @param Introducao_Pages  $pages  Gerenciador de páginas.
     */
    public function __construct( Introducao_Plugin $plugin, Introducao_Pages $pages ) {
        $this->plugin = $plugin;
        $this->pages  = $pages;

        add_shortcode( 'introducao_user_menu', array( $this, 'render_user_menu' ) );

        if ( ! shortcode_exists( 'lae_user_menu' ) ) {
            add_shortcode( 'lae_user_menu', array( $this, 'render_user_menu' ) );
        }

        foreach ( $this->page_shortcodes as $tag => $template ) {
            add_shortcode( $tag, function( $atts = array(), $content = '', $shortcode_tag = '' ) use ( $template ) {
                return $this->render_page_template( $template, $shortcode_tag );
            } );
        }

        foreach ( $this->legacy_page_shortcodes as $legacy_tag => $template ) {
            if ( shortcode_exists( $legacy_tag ) ) {
                continue;
            }

            add_shortcode( $legacy_tag, function( $atts = array(), $content = '', $shortcode_tag = '' ) use ( $template ) {
                return $this->render_page_template( $template, $shortcode_tag );
            } );
        }
    }

    /**
     * Renderiza o shortcode do menu do usuário.
     *
     * @param array $atts Atributos do shortcode.
     *
     * @return string
     */
    public function render_user_menu( $atts ) {
        $defaults = array(
            'show_notifications' => 'yes',
            'notification_count' => 0,
            'greeting'           => '',
        );

        $atts = shortcode_atts( $defaults, $atts, 'introducao_user_menu' );

        $show_notifications   = 'no' !== strtolower( sanitize_text_field( $atts['show_notifications'] ) );
        $notification_count   = absint( $atts['notification_count'] );
        $custom_greeting_raw  = trim( $atts['greeting'] );
        $custom_greeting      = $custom_greeting_raw ? sanitize_text_field( $custom_greeting_raw ) : '';
        $user            = wp_get_current_user();
        $client_identity = class_exists( 'Introducao_Auth' ) ? Introducao_Auth::get_client_identity() : array();
        $identity_name   = isset( $client_identity['display_name'] ) ? $client_identity['display_name'] : '';
        $identity_login  = isset( $client_identity['user_login'] ) ? $client_identity['user_login'] : '';
        $identity_initial = isset( $client_identity['initial'] ) ? $client_identity['initial'] : '';
        $identity_avatar  = isset( $client_identity['avatar_url'] ) ? $client_identity['avatar_url'] : '';

        if ( $user instanceof WP_User && $user->exists() ) {
            $display_name = $user->display_name ? $user->display_name : $user->user_login;
            $greeting     = $custom_greeting ? $custom_greeting : sprintf( __( 'Bem-vindo, %s', 'introducao' ), $display_name );
        } elseif ( $identity_name || $identity_login ) {
            $display_name = $identity_name ? sanitize_text_field( $identity_name ) : sanitize_text_field( $identity_login );
            $greeting     = $custom_greeting ? $custom_greeting : sprintf( __( 'Bem-vindo, %s', 'introducao' ), $display_name );
        } else {
            $display_name = __( 'Visitante', 'introducao' );
            $greeting     = $custom_greeting ? $custom_greeting : sprintf( __( 'Bem-vindo, %s', 'introducao' ), $display_name );
        }

        $notification_count = apply_filters( 'introducao_notification_count', $notification_count, $user );

        if ( has_filter( 'lae_notification_count' ) ) {
            $notification_count = apply_filters( 'lae_notification_count', $notification_count, $user );
        }
        $notification_count = max( 0, (int) $notification_count );
        $show_notifications = (bool) apply_filters( 'introducao_show_notifications', $show_notifications, $user, $notification_count );

        if ( has_filter( 'lae_show_notifications' ) ) {
            $show_notifications = (bool) apply_filters( 'lae_show_notifications', $show_notifications, $user, $notification_count );
        }

        if ( $display_name ) {
            $charset = get_bloginfo( 'charset' );
            $first_char = function_exists( 'mb_substr' ) ? mb_substr( $display_name, 0, 1, $charset ) : substr( $display_name, 0, 1 );
            $avatar_initial = function_exists( 'mb_strtoupper' ) ? mb_strtoupper( $first_char, $charset ) : strtoupper( $first_char );
        } elseif ( $identity_initial ) {
            $avatar_initial = $identity_initial;
        } else {
            $avatar_initial = 'U';
        }

        $menu_items = $this->pages->get_menu_items();

        if ( $user instanceof WP_User && $user->exists() ) {
            $menu_items[] = array(
                'slug'  => 'logout',
                'label' => __( 'Sair', 'introducao' ),
                'url'   => wp_logout_url( home_url() ),
            );
        } else {
            $menu_items[] = array(
                'slug'  => 'login',
                'label' => __( 'Entrar', 'introducao' ),
                'url'   => wp_login_url(),
            );
        }

        $menu_items = apply_filters( 'introducao_user_menu_items', $menu_items, $user );

        if ( has_filter( 'lae_user_menu_items' ) ) {
            $menu_items = apply_filters( 'lae_user_menu_items', $menu_items, $user );
        }

        if ( ! is_array( $menu_items ) ) {
            $menu_items = array();
        }

        $avatar_id  = ( $user instanceof WP_User && $user->exists() ) ? $user->ID : 0;
        $avatar_url = $avatar_id ? get_avatar_url( $avatar_id, array( 'size' => 160 ) ) : '';

        if ( ! $avatar_url && $identity_avatar ) {
            $avatar_url = esc_url_raw( $identity_avatar );
        }

        $avatar_url = apply_filters( 'introducao_user_avatar_url', $avatar_url, $user );

        if ( has_filter( 'lae_user_avatar_url' ) ) {
            $avatar_url = apply_filters( 'lae_user_avatar_url', $avatar_url, $user );
        }

        $greeting = apply_filters( 'introducao_user_menu_greeting', $greeting, $user, $custom_greeting );

        if ( has_filter( 'lae_user_menu_greeting' ) ) {
            $greeting = apply_filters( 'lae_user_menu_greeting', $greeting, $user, $custom_greeting );
        }

        if ( isset( $avatar_initial ) ) {
            $avatar_initial = apply_filters( 'introducao_user_avatar_initial', $avatar_initial, $user, $display_name );

            if ( has_filter( 'lae_user_avatar_initial' ) ) {
                $avatar_initial = apply_filters( 'lae_user_avatar_initial', $avatar_initial, $user, $display_name );
            }
        }

        $menu_id      = $this->generate_unique_id( 'introducao-menu-' );
        $dropdown_id  = $this->generate_unique_id( 'introducao-dropdown-' );
        $greeting_id  = $menu_id . '-label';

        $this->plugin->enqueue_assets();

        return $this->plugin->render_template(
            'user-menu.php',
            array(
                'show_notifications'   => $show_notifications,
                'notification_count'   => $notification_count,
                'greeting'             => $greeting,
                'greeting_id'          => $greeting_id,
                'avatar_initial'       => $avatar_initial,
                'avatar_url'           => $avatar_url,
                'menu_items'           => $menu_items,
                'menu_id'              => $menu_id,
                'dropdown_id'          => $dropdown_id,
                'display_name'         => $display_name,
                'has_custom_greeting'  => (bool) $custom_greeting,
                'client_identity'      => $client_identity,
            )
        );
    }

    /**
     * Renderiza um template simples para os shortcodes de página.
     *
     * @param string $template       Nome do arquivo de template.
     * @param string $shortcode_tag  Tag do shortcode invocada.
     *
     * @return string
     */
    private function render_page_template( $template, $shortcode_tag ) {
        $templates_requiring_scripts = array(
            'slider-onboarding.php',
            'page-perfil.php',
        );

        if ( in_array( $template, $templates_requiring_scripts, true ) ) {
            $this->plugin->enqueue_assets();
        } else {
            $this->plugin->enqueue_style();
        }

        return $this->plugin->render_template(
            $template,
            array(
                'shortcode_tag' => $shortcode_tag,
            )
        );
    }

    /**
     * Gera um identificador único compatível com versões antigas do WordPress.
     *
     * @param string $prefix Prefixo para o identificador.
     *
     * @return string
     */
    private function generate_unique_id( $prefix ) {
        if ( function_exists( 'wp_unique_id' ) ) {
            return wp_unique_id( $prefix );
        }

        static $counters = array();

        if ( ! isset( $counters[ $prefix ] ) ) {
            $counters[ $prefix ] = 0;
        }

        $counters[ $prefix ]++;

        return $prefix . $counters[ $prefix ];
    }
}
