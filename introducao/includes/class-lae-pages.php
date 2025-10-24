<?php
/**
 * Manipulação das páginas criadas pelo plugin.
 */
class Introducao_Pages {

    /**
     * Dados das páginas a serem criadas.
     *
     * @var array
     */
    private $pages = array(
        'teoria'              => array(
            'title'     => 'Teoria',
            'shortcode' => '[introducao_teoria]',
        ),
        'pratica'             => array(
            'title'     => 'Prática',
            'shortcode' => '[introducao_pratica]',
        ),
        'meus-conhecimentos'  => array(
            'title'     => 'Meus Conhecimentos',
            'shortcode' => '[introducao_meus_conhecimentos]',
        ),
        'treinador'           => array(
            'title'     => 'Treinador',
            'shortcode' => '[introducao_treinador]',
        ),
        'planos'              => array(
            'title'     => 'Planos',
            'shortcode' => '[introducao_planos]',
        ),
        'configuracoes'       => array(
            'title'     => 'Configurações',
            'shortcode' => '[introducao_configuracoes]',
        ),
        'suporte'             => array(
            'title'     => 'Suporte',
            'shortcode' => '[introducao_suporte]',
        ),
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
            $page = get_page_by_path( $slug, OBJECT, 'page' );

            if ( $page instanceof WP_Post ) {
                if ( 'trash' === $page->post_status ) {
                    wp_untrash_post( $page->ID );
                }

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
            update_option( 'introducao_page_ids', $page_ids );
            delete_option( 'lae_page_ids' );
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
        $stored = get_option( 'introducao_page_ids', array() );

        if ( empty( $stored ) ) {
            $stored = get_option( 'lae_page_ids', array() );
        }

        if ( isset( $stored[ $slug ] ) ) {
            return (int) $stored[ $slug ];
        }

        $page = get_page_by_path( $slug, OBJECT, 'page' );

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
}
