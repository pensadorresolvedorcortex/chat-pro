<?php
/**
 * Manipulação das páginas criadas pelo plugin.
 */
class LAE_Pages {

    /**
     * Dados das páginas a serem criadas.
     *
     * @var array
     */
    private $pages = array(
        'minha-conta-academia' => array(
            'title'     => 'Minha Conta',
            'shortcode' => '[lae_perfil]',
        ),
        'academia-aulas-teoricas' => array(
            'title'     => 'Teoria',
            'shortcode' => '[lae_teoria]',
        ),
        'pratica'             => array(
            'title'     => 'Prática',
            'shortcode' => '[lae_pratica]',
        ),
        'meus-conhecimentos'  => array(
            'title'     => 'Meus Conhecimentos',
            'shortcode' => '[lae_meus_conhecimentos]',
        ),
        'planos'              => array(
            'title'     => 'Planos',
            'shortcode' => '[lae_planos]',
        ),
        'suporte'             => array(
            'title'     => 'Suporte',
            'shortcode' => '[lae_suporte]',
        ),
    );

    /**
     * Slugs antigos que devem ser migrados para os slugs oficiais atuais.
     *
     * @var array
     */
    private $slug_aliases = array(
        'minha-conta-academia'    => array( 'perfil', 'minha-conta' ),
        'academia-aulas-teoricas' => array( 'teoria' ),
    );

    /**
     * Hook de ativação do plugin.
     */
    public static function activate() {
        $self = new self();
        $self->create_pages();
    }

    /**
     * Cria ou atualiza as páginas necessárias.
     */
    public function create_pages() {
        $page_ids = array();

        foreach ( $this->pages as $slug => $data ) {
            $page = $this->locate_page( $slug, true );

            if ( $page instanceof WP_Post ) {
                if ( 'trash' === $page->post_status ) {
                    wp_untrash_post( $page->ID );
                    $page = get_post( $page->ID );
                }

                if ( $page instanceof WP_Post ) {
                    $page_ids[ $slug ] = $page->ID;

                    $updated = array(
                        'ID'           => $page->ID,
                        'post_status'  => 'publish',
                        'post_title'   => $data['title'],
                        'post_content' => wp_slash( $data['shortcode'] ),
                    );

                    wp_update_post( $updated );
                    continue;
                }
            }

            $page_args = array(
                'post_title'   => $data['title'],
                'post_name'    => $slug,
                'post_content' => wp_slash( $data['shortcode'] ),
                'post_status'  => 'publish',
                'post_type'    => 'page',
            );

            $page_id = wp_insert_post( $page_args );

            if ( $page_id && ! is_wp_error( $page_id ) ) {
                $page_ids[ $slug ] = $page_id;
            }
        }

        if ( ! empty( $page_ids ) ) {
            update_option( 'lae_page_ids', $page_ids );
        }
    }

    /**
     * Retorna os dados das páginas registradas.
     *
     * @return array
     */
    public function get_registered_pages() {
        return $this->pages;
    }

    /**
     * Retorna o ID da página pelo slug.
     *
     * @param string $slug Slug da página.
     *
     * @return int
     */
    public function get_page_id( $slug ) {
        $stored = get_option( 'lae_page_ids', array() );

        if ( isset( $stored[ $slug ] ) ) {
            return (int) $stored[ $slug ];
        }

        $page = $this->locate_page( $slug, true );

        return $page instanceof WP_Post ? (int) $page->ID : 0;
    }

    /**
     * Retorna a URL de uma página registrada.
     *
     * @param string $slug Slug da página.
     *
     * @return string
     */
    public function get_page_url( $slug ) {
        $page_id = $this->get_page_id( $slug );

        if ( $page_id ) {
            return get_permalink( $page_id );
        }

        return home_url( user_trailingslashit( $slug ) );
    }

    /**
     * Retorna os itens para o dropdown do menu do usuário.
     *
     * @return array
     */
    public function get_menu_items() {
        $items = array();

        foreach ( $this->pages as $slug => $data ) {
            $items[] = array(
                'slug'  => $slug,
                'label' => $data['title'],
                'url'   => $this->get_page_url( $slug ),
            );
        }

        return $items;
    }

    /**
     * Marca os itens de menu com o status de página atual.
     *
     * @param array $items Itens do menu.
     *
     * @return array
     */
    public function annotate_menu_items( $items ) {
        if ( ! is_array( $items ) ) {
            return array();
        }

        foreach ( $items as $index => $item ) {
            if ( is_array( $item ) ) {
                $items[ $index ]['is_current'] = $this->is_current_menu_item( $item );
            }
        }

        return $items;
    }

    /**
     * Verifica se um slug corresponde à página atual.
     *
     * @param string $slug Slug para verificar.
     *
     * @return bool
     */
    public function is_current_page_slug( $slug ) {
        if ( ! $slug || ! function_exists( 'is_page' ) ) {
            return false;
        }

        $page_id = $this->get_page_id( $slug );

        if ( $page_id && is_page( $page_id ) ) {
            return true;
        }

        if ( is_page( $slug ) ) {
            return true;
        }

        if ( isset( $this->slug_aliases[ $slug ] ) ) {
            foreach ( $this->slug_aliases[ $slug ] as $legacy_slug ) {
                if ( is_page( $legacy_slug ) ) {
                    $legacy_page = get_page_by_path( $legacy_slug, OBJECT, 'page' );

                    if ( $legacy_page instanceof WP_Post && $this->is_woocommerce_my_account_page( $legacy_page->ID ) ) {
                        continue;
                    }

                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Localiza uma página existente pelo slug oficial ou por slugs antigos.
     *
     * @param string $slug         Slug atual esperado.
     * @param bool   $migrate_slug Se verdadeiro, migra slugs antigos para o novo.
     *
     * @return WP_Post|null
     */
    private function locate_page( $slug, $migrate_slug = false ) {
        $page = get_page_by_path( $slug, OBJECT, 'page' );

        if ( $page instanceof WP_Post ) {
            return $page;
        }

        if ( ! $migrate_slug ) {
            return null;
        }

        if ( isset( $this->slug_aliases[ $slug ] ) ) {
            foreach ( $this->slug_aliases[ $slug ] as $legacy_slug ) {
                $legacy_page = get_page_by_path( $legacy_slug, OBJECT, 'page' );

                if ( $legacy_page instanceof WP_Post ) {
                    if ( $this->is_woocommerce_my_account_page( $legacy_page->ID ) ) {
                        continue;
                    }

                    if ( 'trash' === $legacy_page->post_status ) {
                        wp_untrash_post( $legacy_page->ID );
                    }

                    $update_args = array(
                        'ID'         => $legacy_page->ID,
                        'post_name'  => $slug,
                        'post_status'=> 'publish',
                    );

                    wp_update_post( $update_args );
                    clean_post_cache( $legacy_page->ID );

                    $page = get_post( $legacy_page->ID );

                    if ( $page instanceof WP_Post ) {
                        return $page;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Verifica se o ID informado corresponde à página "Minha Conta" do WooCommerce.
     *
     * @param int $page_id ID da página a ser verificada.
     *
     * @return bool
     */
    private function is_woocommerce_my_account_page( $page_id ) {
        if ( ! $page_id ) {
            return false;
        }

        if ( ! class_exists( 'WooCommerce' ) && ! function_exists( 'WC' ) ) {
            return false;
        }

        $my_account_id = get_option( 'woocommerce_myaccount_page_id' );

        if ( ! $my_account_id ) {
            return false;
        }

        return (int) $my_account_id === (int) $page_id;
    }

    /**
     * Normaliza uma URL para comparação interna.
     *
     * @param string $url URL a ser normalizada.
     *
     * @return string
     */
    private function normalize_url( $url ) {
        $raw = esc_url_raw( $url );

        if ( ! $raw ) {
            return '';
        }

        $parts = wp_parse_url( $raw );

        if ( ! is_array( $parts ) ) {
            return '';
        }

        $path = isset( $parts['path'] ) ? '/' . ltrim( strtolower( $parts['path'] ), '/' ) : '/';

        return trailingslashit( $path );
    }

    /**
     * Determina se um item do menu representa a página atual.
     *
     * @param array $item Item de menu.
     *
     * @return bool
     */
    private function is_current_menu_item( array $item ) {
        if ( isset( $item['slug'] ) && $this->is_current_page_slug( $item['slug'] ) ) {
            return true;
        }

        $current_id = get_queried_object_id();
        $current_url = $current_id ? get_permalink( $current_id ) : '';

        if ( ! $current_url && isset( $_SERVER['REQUEST_URI'] ) ) {
            $request_uri = wp_unslash( $_SERVER['REQUEST_URI'] );
            $current_url = home_url( $request_uri );
        }

        $item_url = isset( $item['url'] ) ? $item['url'] : '';

        if ( $current_url && $item_url ) {
            $current_normalized = $this->normalize_url( $current_url );
            $item_normalized    = $this->normalize_url( $item_url );

            if ( $current_normalized && $item_normalized && $current_normalized === $item_normalized ) {
                return true;
            }
        }

        return false;
    }
}
