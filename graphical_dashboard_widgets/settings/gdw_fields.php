<?php

/**
 * WordPress settings API demo class
 *
 * @author Tareq Hasan
 */
if ( !class_exists('Gdw_Settings_API_Test' ) ):
class Gdw_Settings_API_Test {

    private $settings_api;

    function __construct() {
        $this->settings_api = new Gdw_Settings_API;

        add_action( 'admin_init', array($this, 'admin_init') );
        add_action( 'admin_menu', array($this, 'admin_menu') );
    }

    function admin_init() {

        //set the settings
        $this->settings_api->set_sections( $this->get_settings_sections() );
        $this->settings_api->set_fields( $this->get_settings_fields() );

        //initialize settings
        $this->settings_api->admin_init();
    }

    function admin_menu() {
        add_menu_page( __('Widgets do Painel','gdwlang'), __('Widgets do Painel','gdwlang'), 'delete_posts', 'gdw_settings', array($this, 'plugin_page') );
    }

    function get_settings_sections() {
        $sections = array(
            
            array(
                'id'    => 'gdwids_options',
                'title' => __( 'Estatísticas Gráficas – Configurações', 'gdwlang' )
            )
        );
        return $sections;
    }

    /**
     * Returns all the settings fields
     *
     * @return array settings fields
     */
    function get_settings_fields() {
        $settings_fields = array(
          
            'gdwids_options' => array(
               
                array(
                    'name'    => 'dashboard-widget-colors',
                    'label'   => __( 'Escolha as cores dos widgets do painel', 'gdwlang' ),
                    'desc'    => __( 'Escolha as cores dos widgets. Selecione <strong>ao menos 6</strong> para aplicar totalmente o gradiente neon; caso contrário, as cores padrão do plugin serão usadas.', 'gdwlang' ),
                    'type'    => 'text_color',
                    'default' => '#7986CB,#4dd0e1,#9575CD,#4FC3F7,#64B5F6,#4DB6AC'
                ),
                array(
                    'name'    => 'front-usertracking',
                    'label'   => __( 'Ativar rastreamento de localização', 'gdwlang' ),
                    'desc'    => __( 'Marque “Sim” para registrar IP, país, cidade e região do visitante; “Não” desativa o rastreamento.', 'gdwlang' ),
                    'type'    => 'radio',
                    'default' => 'yes',
                    'options' => array(
                        'yes' => __('Sim', 'gdwlang' ),
                        'no'  => __('Não', 'gdwlang' )
                    )
                ),
                array(
                    'name'    => 'dashboard-widgets',
                    'label'   => __( 'Habilitar widgets do painel', 'gdwlang' ),
                    'desc'    => __( 'Selecione os widgets exibidos no painel. Estes widgets são fornecidos por este plugin.', 'gdwlang' ),
                    'type'    => 'multicheck',
                    "default" => array(
                            "gdw_visitors_type" => "gdw_visitors_type",
                            "gdw_user_type" => "gdw_user_type",
                            "gdw_browser_type" => "gdw_browser_type",
                            "gdw_platform_type" => "gdw_platform_type",
                            "gdw_country_type" => "gdw_country_type",
                            "gdw_today_visitors" => "gdw_today_visitors",
                            "gdw_pagestats_add_dashboard" => "gdw_pagestats_add_dashboard",
                            "gdw_poststats_add_dashboard" => "gdw_poststats_add_dashboard",
                            "gdw_commentstats_add_dashboard" => "gdw_commentstats_add_dashboard",
                            "gdw_catstats_add_dashboard" => "gdw_catstats_add_dashboard",
                            "gdw_userstats_add_dashboard" => "gdw_userstats_add_dashboard",
                            "gdw_browser_type" => "gdw_browser_type",
                        ),
                    'options' => array(
                            'gdw_visitors_type' => __('Visitantes nos últimos 15 dias','gdwlang'),
                            'gdw_user_type' =>__('Usuários nos últimos 15 dias','gdwlang'),
                            'gdw_browser_type' => __('Navegadores utilizados','gdwlang'),
                            'gdw_platform_type' => __('Plataformas utilizadas','gdwlang'),
                            'gdw_country_type' => __('Visitas por país','gdwlang'),
                            'gdw_today_visitors' => __('Visualizações de hoje e usuários online','gdwlang'),

                            'gdw_pagestats_add_dashboard' => __('Contagem e tipo de páginas','gdwlang'),
                            'gdw_poststats_add_dashboard' => __('Estatísticas de posts','gdwlang'),
                            'gdw_commentstats_add_dashboard' => __('Comentários de usuários','gdwlang'),
                            'gdw_catstats_add_dashboard' => __('Estatísticas por categoria','gdwlang'),
                            'gdw_userstats_add_dashboard' => __('Estatísticas de usuários','gdwlang'),
                    )
                ),



                array(
                    'name'    => 'dashboard-default-widgets',
                    'label'   => __( 'Ativar widgets padrão do WordPress', 'gdwlang' ),
                    'desc'    => __( 'Selecione os widgets nativos para exibir no painel. Alguns podem não existir na sua versão atual do WordPress.', 'gdwlang' ),
                    'type'    => 'multicheck',
                    "default" => array(
                            'welcome_panel' => 'welcome_panel',
                            'dashboard_primary' => 'dashboard_primary',
                            'dashboard_quick_press' => 'dashboard_quick_press',
                            'dashboard_recent_drafts' => 'dashboard_recent_drafts',
                            'dashboard_recent_comments' => 'dashboard_recent_comments',
                            'dashboard_right_now' => 'dashboard_right_now',
                            'dashboard_activity' => 'dashboard_activity',
                            'dashboard_incoming_links' => 'dashboard_incoming_links',
                            'dashboard_plugins' => 'dashboard_plugins',
                            'dashboard_secondary' => 'dashboard_secondary',
                            'e-dashboard-overview' => 'e-dashboard-overview',
                        ),
                    'options' => array(
                            'welcome_panel' => __('Painel de boas-vindas', 'gdwlang' ),
                            'dashboard_primary' => __('Notícias do WordPress', 'gdwlang' ),
                            'dashboard_quick_press' => __('Rascunho rápido', 'gdwlang' ),
                            'dashboard_recent_drafts' => __('Rascunhos recentes', 'gdwlang' ),
                            'dashboard_recent_comments' => __('Comentários recentes', 'gdwlang' ),
                            'dashboard_right_now' => __('Visão geral', 'gdwlang' ),
                            'dashboard_activity' => __('Atividade', 'gdwlang' ),
                            'dashboard_incoming_links' => __('Links recebidos', 'gdwlang' ),
                            'dashboard_plugins' => __('Widget de plugins', 'gdwlang' ),
                            'dashboard_secondary' => __('Widget secundário', 'gdwlang' ),
                            'e-dashboard-overview' => __('Resumo do Elementor', 'gdwlang' ),
                    )
                ),
            )
        );

        return $settings_fields;
    }

    function plugin_page() {
        echo '<div class="wrap">';

        $this->settings_api->show_navigation();
        $this->settings_api->show_forms();

        echo '</div>';
    }

    /**
     * Get all the pages
     *
     * @return array page names with key value pairs
     */
    function get_pages() {
        $pages = get_pages();
        $pages_options = array();
        if ( $pages ) {
            foreach ($pages as $page) {
                $pages_options[$page->ID] = $page->post_title;
            }
        }

        return $pages_options;
    }

}
endif;
