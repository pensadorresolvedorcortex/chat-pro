<?php
/**
 * Registro e renderização dos shortcodes do plugin.
 */
class LAE_Shortcodes {

    /**
     * Instância principal do plugin.
     *
     * @var LAE_Plugin
     */
    private $plugin;

    /**
     * Gerenciador de páginas.
     *
     * @var LAE_Pages
     */
    private $pages;

    /**
     * Mapeamento dos shortcodes de páginas para seus templates.
     *
     * @var array
     */
    private $page_shortcodes = array(
        'lae_teoria'              => 'page-teoria.php',
        'lae_pratica'             => 'page-pratica.php',
        'lae_meus_conhecimentos'  => 'page-meus-conhecimentos.php',
        'lae_treinador'           => 'page-treinador.php',
        'lae_planos'              => 'page-planos.php',
        'lae_configuracoes'       => 'page-configuracoes.php',
        'lae_suporte'             => 'page-suporte.php',
        'lae_onboarding_slider'   => 'slider-onboarding.php',
    );

    /**
     * Construtor.
     *
     * @param LAE_Plugin $plugin Instância principal.
     * @param LAE_Pages  $pages  Gerenciador de páginas.
     */
    public function __construct( LAE_Plugin $plugin, LAE_Pages $pages ) {
        $this->plugin = $plugin;
        $this->pages  = $pages;

        add_shortcode( 'lae_user_menu', array( $this, 'render_user_menu' ) );

        foreach ( $this->page_shortcodes as $tag => $template ) {
            add_shortcode( $tag, function( $atts = array(), $content = '', $shortcode_tag = '' ) use ( $template ) {
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

        $atts = shortcode_atts( $defaults, $atts, 'lae_user_menu' );

        $show_notifications   = 'no' !== strtolower( sanitize_text_field( $atts['show_notifications'] ) );
        $notification_count   = absint( $atts['notification_count'] );
        $custom_greeting_raw  = trim( $atts['greeting'] );
        $custom_greeting      = $custom_greeting_raw ? sanitize_text_field( $custom_greeting_raw ) : '';
        $user                 = wp_get_current_user();

        if ( $user instanceof WP_User && $user->exists() ) {
            $display_name = $user->display_name ? $user->display_name : $user->user_login;
            $greeting     = $custom_greeting ? $custom_greeting : sprintf( __( 'Bem-vindo, %s', 'introducao' ), $display_name );
        } else {
            $display_name = __( 'Visitante', 'introducao' );
            $greeting     = $custom_greeting ? $custom_greeting : sprintf( __( 'Bem-vindo, %s', 'introducao' ), $display_name );
        }

        $notification_count = apply_filters( 'lae_notification_count', $notification_count, $user );
        $notification_count = max( 0, (int) $notification_count );
        $show_notifications = (bool) apply_filters( 'lae_show_notifications', $show_notifications, $user, $notification_count );

        if ( $display_name ) {
            $charset     = get_bloginfo( 'charset' );
            $first_char  = function_exists( 'mb_substr' ) ? mb_substr( $display_name, 0, 1, $charset ) : substr( $display_name, 0, 1 );
            $avatar_initial = function_exists( 'mb_strtoupper' ) ? mb_strtoupper( $first_char, $charset ) : strtoupper( $first_char );
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

        $menu_items = apply_filters( 'lae_user_menu_items', $menu_items, $user );

        if ( ! is_array( $menu_items ) ) {
            $menu_items = array();
        }

        $avatar_id  = ( $user instanceof WP_User && $user->exists() ) ? $user->ID : 0;
        $avatar_url = $avatar_id ? get_avatar_url( $avatar_id, array( 'size' => 160 ) ) : '';
        $avatar_url = apply_filters( 'lae_user_avatar_url', $avatar_url, $user );

        $greeting = apply_filters( 'lae_user_menu_greeting', $greeting, $user, $custom_greeting );

        if ( isset( $avatar_initial ) ) {
            $avatar_initial = apply_filters( 'lae_user_avatar_initial', $avatar_initial, $user, $display_name );
        }

        $menu_id      = wp_unique_id( 'lae-menu-' );
        $dropdown_id  = wp_unique_id( 'lae-dropdown-' );
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
        if ( 'slider-onboarding.php' === $template ) {
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
}
