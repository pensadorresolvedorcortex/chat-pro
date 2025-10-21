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
        'lae_perfil'              => 'page-perfil.php',
        'lae_teoria'              => 'page-teoria.php',
        'lae_pratica'             => 'page-pratica.php',
        'lae_meus_conhecimentos'  => 'page-meus-conhecimentos.php',
        'lae_planos'              => 'page-planos.php',
        'lae_suporte'             => 'page-suporte.php',
    );

    /**
     * Taxonomia utilizada para identificar cursos por categoria.
     *
     * @var string
     */
    private $course_taxonomy = 'categoria-questao';

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

        add_shortcode( 'lae_teoria_cursos', array( $this, 'render_teoria_courses' ) );
        add_shortcode( 'lae_pratica_cursos', array( $this, 'render_pratica_courses' ) );
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
            $greeting     = $custom_greeting ? $custom_greeting : sprintf( __( 'Bem-vindo, %s', 'login-academia-da-educacao' ), $display_name );
        } else {
            $display_name = __( 'Visitante', 'login-academia-da-educacao' );
            $greeting     = $custom_greeting ? $custom_greeting : sprintf( __( 'Bem-vindo, %s', 'login-academia-da-educacao' ), $display_name );
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
                'label' => __( 'Sair', 'login-academia-da-educacao' ),
                'url'   => wp_logout_url( home_url() ),
            );
        } else {
            $menu_items[] = array(
                'slug'  => 'login',
                'label' => __( 'Entrar', 'login-academia-da-educacao' ),
                'url'   => '#',
                'type'  => 'modal',
            );
        }

        $menu_items = apply_filters( 'lae_user_menu_items', $menu_items, $user );

        if ( ! is_array( $menu_items ) ) {
            $menu_items = array();
        }

        if ( $this->pages instanceof LAE_Pages ) {
            $menu_items = $this->pages->annotate_menu_items( $menu_items );
        }

        $avatar_id  = ( $user instanceof WP_User && $user->exists() ) ? $user->ID : 0;
        $avatar_url = $avatar_id ? get_avatar_url( $avatar_id, array( 'size' => 160 ) ) : '';

        if ( ! $avatar_url ) {
            $avatar_url = LAE_PLUGIN_URL . 'assets/img/default-avatar.svg';
        }

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
        $this->plugin->enqueue_style();

        return $this->plugin->render_template(
            $template,
            array(
                'shortcode_tag' => $shortcode_tag,
            )
        );
    }

    /**
     * Renderiza a listagem de cursos teóricos.
     *
     * @param array $atts Atributos do shortcode.
     *
     * @return string
     */
    public function render_teoria_courses( $atts = array() ) {
        $atts['category'] = isset( $atts['category'] ) ? $atts['category'] : '/categoria-questao/teoria/';

        return $this->render_course_collection( $atts );
    }

    /**
     * Renderiza a listagem de cursos práticos.
     *
     * @param array $atts Atributos do shortcode.
     *
     * @return string
     */
    public function render_pratica_courses( $atts = array() ) {
        $atts['category'] = isset( $atts['category'] ) ? $atts['category'] : '/categoria-questao/pratica/';

        return $this->render_course_collection( $atts );
    }

    /**
     * Renderiza uma coleção de cursos filtrada por categoria.
     *
     * @param array $atts Atributos do shortcode.
     *
     * @return string
     */
    private function render_course_collection( $atts ) {
        $defaults = array(
            'category'   => '',
            'taxonomy'   => $this->course_taxonomy,
            'post_type'  => apply_filters( 'lae_course_post_types', array( 'course', 'sfwd-courses', 'lp_course', 'product', 'post' ) ),
            'per_page'   => 12,
            'show_meta'  => 'yes',
            'show_empty' => 'no',
        );

        $atts = shortcode_atts( $defaults, $atts );

        $category = $this->normalize_category_slug( $atts['category'], $atts['taxonomy'] );
        $taxonomy = sanitize_title( $atts['taxonomy'] );

        if ( empty( $category ) || empty( $taxonomy ) ) {
            return '';
        }

        $post_types = $atts['post_type'];

        if ( is_string( $post_types ) ) {
            $post_types = array_map( 'trim', explode( ',', $post_types ) );
        }

        if ( ! is_array( $post_types ) || empty( $post_types ) ) {
            $post_types = array( 'post' );
        }

        $post_types = array_values( array_filter( array_map( 'sanitize_key', $post_types ) ) );

        if ( empty( $post_types ) ) {
            $post_types = array( 'post' );
        }

        $per_page  = max( 1, (int) $atts['per_page'] );
        $show_meta = 'yes' === strtolower( $atts['show_meta'] );

        $query_args = array(
            'post_type'      => $post_types,
            'posts_per_page' => $per_page,
            'tax_query'      => array(
                array(
                    'taxonomy'         => $taxonomy,
                    'field'            => 'slug',
                    'terms'            => (array) $category,
                    'include_children' => true,
                ),
            ),
            'post_status'    => 'publish',
        );

        $query_args = apply_filters( 'lae_course_query_args', $query_args, $category, $taxonomy, $post_types );

        $query = new WP_Query( $query_args );

        ob_start();

        $this->plugin->enqueue_style();

        echo '<div class="lae-course-section" data-lae-course-category="' . esc_attr( $category ) . '">';

        if ( $query->have_posts() ) {
            echo '<div class="lae-course-grid">';

            while ( $query->have_posts() ) {
                $query->the_post();
                $post_id    = get_the_ID();
                $title      = get_the_title();
                $permalink  = get_permalink();
                $excerpt    = get_the_excerpt();
                $thumbnail  = get_the_post_thumbnail_url( $post_id, 'medium_large' );
                $meta_html  = '';

                if ( $show_meta ) {
                    $meta = array();

                    $terms = get_the_terms( $post_id, $taxonomy );

                    if ( $terms && ! is_wp_error( $terms ) ) {
                        $term_names = wp_list_pluck( $terms, 'name' );
                        if ( ! empty( $term_names ) ) {
                            $meta[] = esc_html( implode( ', ', $term_names ) );
                        }
                    }

                    $author = get_the_author();

                    if ( $author ) {
                        $meta[] = sprintf( esc_html__( 'por %s', 'login-academia-da-educacao' ), esc_html( $author ) );
                    }

                    if ( ! empty( $meta ) ) {
                        $meta_html = '<p class="lae-course-card__meta">' . implode( ' • ', $meta ) . '</p>';
                    }
                }

                echo '<article class="lae-course-card">';

                if ( $thumbnail ) {
                    echo '<a class="lae-course-card__thumb" href="' . esc_url( $permalink ) . '">';
                    echo '<span class="lae-course-card__image" style="background-image: url(' . esc_url( $thumbnail ) . ');"></span>';
                    echo '</a>';
                } else {
                    echo '<a class="lae-course-card__thumb has-placeholder" href="' . esc_url( $permalink ) . '">';
                    echo '<span class="lae-course-card__placeholder" aria-hidden="true">';
                    echo '<span class="lae-course-card__placeholder-icon"></span>';
                    echo '</span>';
                    echo '</a>';
                }

                echo '<div class="lae-course-card__body">';
                echo '<h3 class="lae-course-card__title"><a href="' . esc_url( $permalink ) . '">' . esc_html( $title ) . '</a></h3>';

                if ( $excerpt ) {
                    echo '<p class="lae-course-card__excerpt">' . esc_html( wp_trim_words( $excerpt, 24, '…' ) ) . '</p>';
                }

                echo $meta_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                echo '<a class="lae-course-card__action" href="' . esc_url( $permalink ) . '">' . esc_html__( 'Acessar curso', 'login-academia-da-educacao' ) . '</a>';
                echo '</div>';
                echo '</article>';
            }

            echo '</div>';
        } else {
            if ( 'yes' === strtolower( $atts['show_empty'] ) ) {
                echo '<p class="lae-course-empty">' . esc_html__( 'Nenhum curso encontrado nesta categoria.', 'login-academia-da-educacao' ) . '</p>';
            }
        }

        echo '</div>';

        wp_reset_postdata();

        return ob_get_clean();
    }

    /**
     * Normaliza um slug de categoria aceitando caminhos hierárquicos.
     *
     * @param string $raw_slug  Slug informado no shortcode.
     * @param string $taxonomy  Taxonomia alvo.
     *
     * @return string
     */
    private function normalize_category_slug( $raw_slug, $taxonomy ) {
        $raw_slug = is_string( $raw_slug ) ? trim( $raw_slug ) : '';

        if ( '' === $raw_slug ) {
            return '';
        }

        $trimmed = trim( $raw_slug, " \/" );

        if ( '' === $trimmed ) {
            return '';
        }

        $parts = array_filter( array_map( 'sanitize_title', explode( '/', $trimmed ) ) );
        $taxonomy_slug = sanitize_title( $taxonomy );

        if ( empty( $parts ) ) {
            return '';
        }

        if ( $taxonomy_slug && $parts && reset( $parts ) === $taxonomy_slug ) {
            array_shift( $parts );
        }

        if ( empty( $parts ) ) {
            return '';
        }

        return array_pop( $parts );
    }
}
