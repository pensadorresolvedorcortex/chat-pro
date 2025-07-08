<?php
/*
Plugin Name: Studio Privilege SEO
Description: Ferramentas de SEO dedicadas a https://www.studioprivilege.com.br.
Version: 1.3.0
Author: Studio Privilege
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Acesso direto nao permitido.
}

define( 'SP_SEO_DOMAIN', 'https://www.studioprivilege.com.br' );
define( 'SP_SEO_KEYWORDS', 'criação de sites São Paulo, criação de sites SP, criar site São Paulo, criar site SP, desenvolvimento de sites São Paulo, desenvolvimento de sites SP, site profissional São Paulo, site profissional SP, agência de sites São Paulo, agência de sites SP, agência digital São Paulo, agência digital SP, criação de sites Rio de Janeiro, criação de sites RJ, criar site Rio de Janeiro, criar site RJ, desenvolvimento de sites Rio de Janeiro, desenvolvimento de sites RJ, site profissional Rio de Janeiro, site profissional RJ, agência de sites Rio de Janeiro, agência de sites RJ, agência digital Rio de Janeiro, agência digital RJ' );
define( 'SP_SEO_ETAG_OPTION', 'sp_seo_etag' );
define( 'SP_SEO_LASTMOD_OPTION', 'sp_seo_last_modified' );
define( 'SP_SEO_CLICK_TABLE', 'sp_seo_clicks' );

// Registra informações em arquivo de log dentro de wp-content.
function sp_seo_log( $message ) {
    $file = WP_CONTENT_DIR . '/sp-seo.log';
    $date = date_i18n( 'Y-m-d H:i:s' );
    @file_put_contents( $file, "[$date] $message\n", FILE_APPEND );
}


function sp_seo_create_table() {
    global $wpdb;
    $table = $wpdb->prefix . SP_SEO_CLICK_TABLE;
    $charset = $wpdb->get_charset_collate();
    $sql = 'CREATE TABLE IF NOT EXISTS ' . $table . ' (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        city VARCHAR(100) DEFAULT "",
        region VARCHAR(100) DEFAULT "",
        country VARCHAR(100) DEFAULT "",
        ip VARCHAR(45) DEFAULT "",
        ts DATETIME NOT NULL,
        PRIMARY KEY (id)
    ) ' . $charset . ';';
    require_once ABSPATH . "wp-admin/includes/upgrade.php";
    dbDelta( $sql );
}
// Aciona a coleta de dados a cada 12 horas via WP Cron.
function sp_seo_schedule_refresh() {
    if ( ! wp_next_scheduled( 'sp_seo_refresh_event' ) ) {
        wp_schedule_event( time(), 'twicedaily', 'sp_seo_refresh_event' );
    }
}
add_action( 'wp', 'sp_seo_schedule_refresh' );
add_action( 'sp_seo_refresh_event', 'sp_seo_fetch_site_data' );

// Busca dados do site no dominio dedicado e armazena as principais tags.
function sp_seo_fetch_site_data( $force = false ) {
    sp_seo_log( 'Iniciando coleta de dados' );
    $headers = array();
    if ( ! $force ) {
        $etag = get_option( SP_SEO_ETAG_OPTION );
        $mod  = get_option( SP_SEO_LASTMOD_OPTION );
        if ( $etag ) {
            $headers['If-None-Match'] = $etag;
        }
        if ( $mod ) {
            $headers['If-Modified-Since'] = $mod;
        }
    }

    $response = wp_remote_get( SP_SEO_DOMAIN, array( 'headers' => $headers ) );
    if ( is_wp_error( $response ) ) {
        sp_seo_log( 'Falha na requisicao: ' . $response->get_error_message() );
        return;
    }
    $code = wp_remote_retrieve_response_code( $response );
    if ( 304 === (int) $code ) {
        update_option( 'sp_seo_last_fetch', time() );
        sp_seo_log( 'Conteudo nao modificado' );
        return;
    } elseif ( 200 !== (int) $code ) {
        sp_seo_log( 'Codigo HTTP inesperado: ' . $code );
        return;
    }
    $body = wp_remote_retrieve_body( $response );
    if ( $body ) {
        libxml_use_internal_errors( true );
        $dom = new DOMDocument();
        $dom->loadHTML( $body );

        $title      = '';
        $meta_tags  = array();
        $jsonld     = array();
        $canonical  = SP_SEO_DOMAIN;

        $titles = $dom->getElementsByTagName( 'title' );
        if ( $titles->length > 0 ) {
            $title = $titles->item( 0 )->textContent;
        }

        foreach ( $dom->getElementsByTagName( 'meta' ) as $meta ) {
            $attrs = array();
            foreach ( $meta->attributes as $attr ) {
                $name              = $attr->nodeName; // Preserve attribute name, e.g. "property".
                $attrs[ $name ] = sanitize_text_field( $attr->nodeValue );
            }
            $meta_tags[] = $attrs;
        }

        foreach ( $dom->getElementsByTagName( 'link' ) as $link ) {
            if ( strtolower( $link->getAttribute( 'rel' ) ) === 'canonical' ) {
                $canonical = $link->getAttribute( 'href' );
                break;
            }
        }

        foreach ( $dom->getElementsByTagName( 'script' ) as $script ) {
            if ( strtolower( $script->getAttribute( 'type' ) ) === 'application/ld+json' ) {
                $jsonld[] = $script->textContent;
            }
        }

        libxml_clear_errors();

        update_option( 'sp_seo_title', sanitize_text_field( $title ) );
        update_option( 'sp_seo_meta_tags', $meta_tags );
        update_option( 'sp_seo_jsonld', $jsonld );
        update_option( 'sp_seo_canonical', esc_url_raw( $canonical ) );
        update_option( 'sp_seo_last_fetch', time() );
        $etag = wp_remote_retrieve_header( $response, 'etag' );
        $mod  = wp_remote_retrieve_header( $response, 'last-modified' );
        if ( $etag ) {
            update_option( SP_SEO_ETAG_OPTION, $etag );
        }
        if ( $mod ) {
            update_option( SP_SEO_LASTMOD_OPTION, $mod );
        }
        sp_seo_ping_search_engines();
        sp_seo_log( 'Dados atualizados com sucesso' );
    }
}

// Informa buscadores sobre o sitemap atualizado.
function sp_seo_ping_search_engines() {
    $sitemap = home_url( '/sp-sitemap.xml' );
    $urls    = array(
        'https://www.google.com/ping?sitemap=' . urlencode( $sitemap ),
        'https://www.bing.com/ping?sitemap=' . urlencode( $sitemap ),
    );
    foreach ( $urls as $url ) {
        wp_remote_get( $url );
    }
}

// Adiciona metadados ao cabecalho da pagina.
function sp_seo_add_meta_tags() {
    $home      = get_option( 'sp_seo_canonical', SP_SEO_DOMAIN );
    $title     = get_option( 'sp_seo_title' );
    $meta_tags = get_option( 'sp_seo_meta_tags', array() );
    $jsonld    = get_option( 'sp_seo_jsonld', array() );
    $has_keywords = false;

    foreach ( (array) $meta_tags as $tag ) {
        if ( isset( $tag['name'] ) && strtolower( $tag['name'] ) === 'keywords' ) {
            $has_keywords = true;
            break;
        }
    }

    if ( $title ) {
        echo '<title>' . esc_html( $title ) . "</title>\n";
    }
    if ( ! $has_keywords && defined( 'SP_SEO_KEYWORDS' ) && SP_SEO_KEYWORDS ) {
        echo "<meta name='keywords' content='" . esc_attr( SP_SEO_KEYWORDS ) . "' />\n";
    }
    foreach ( (array) $meta_tags as $tag ) {
        $attr_str = '';
        foreach ( $tag as $name => $val ) {
            $attr_str .= sprintf( "%s='%s' ", esc_attr( $name ), esc_attr( $val ) );
        }
        echo '<meta ' . trim( $attr_str ) . "/>\n";
    }
    if ( $home ) {
        echo "<link rel='canonical' href='" . esc_url( $home ) . "' />\n";
    }
    foreach ( (array) $jsonld as $block ) {
        echo "<script type='application/ld+json'>" . $block . "</script>\n";
    }
}
add_action( 'wp_head', 'sp_seo_add_meta_tags' );

function sp_seo_log_click() {
    global $wpdb;
    $table = $wpdb->prefix . SP_SEO_CLICK_TABLE;
    $ip = $_SERVER['REMOTE_ADDR'];
    $city = $region = $country = '';
    $res = wp_remote_get('https://ipapi.co/' . $ip . '/json/');
    if ( ! is_wp_error( $res ) && 200 == wp_remote_retrieve_response_code( $res ) ) {
        $data = json_decode( wp_remote_retrieve_body( $res ), true );
        if ( is_array( $data ) ) {
            $city    = isset( $data['city'] ) ? sanitize_text_field( $data['city'] ) : '';
            $region  = isset( $data['region'] ) ? sanitize_text_field( $data['region'] ) : '';
            $country = isset( $data['country_name'] ) ? sanitize_text_field( $data['country_name'] ) : '';
        }
    }
    $wpdb->insert(
        $table,
        array(
            'city'    => $city,
            'region'  => $region,
            'country' => $country,
            'ip'      => $ip,
            'ts'      => current_time( 'mysql' )
        ),
        array( '%s', '%s', '%s', '%s', '%s' )
    );
    wp_send_json_success();
}
add_action( 'wp_ajax_nopriv_sp_seo_log_click', 'sp_seo_log_click' );
add_action( 'wp_ajax_sp_seo_log_click', 'sp_seo_log_click' );

// Gera sitemap listando posts e paginas publicados.
function sp_seo_click_tracker_script() {
    if ( is_admin() ) {
        return;
    }
    $ajax = admin_url( 'admin-ajax.php' );
    echo "<script>document.addEventListener('click',function(){if(window.spSeoTracked)return;window.spSeoTracked=true;var x=new XMLHttpRequest();x.open('POST','" . $ajax . "');x.setRequestHeader('Content-Type','application/x-www-form-urlencoded');x.send('action=sp_seo_log_click');});</script>";
}
add_action( 'wp_footer', 'sp_seo_click_tracker_script' );
function sp_seo_generate_sitemap() {
    if ( 'sp-sitemap.xml' !== basename( $_SERVER['REQUEST_URI'] ) ) {
        return;
    }

    header( 'Content-Type: application/xml; charset=utf-8' );
    echo "<?xml version='1.0' encoding='UTF-8'?>\n";
    echo "<urlset xmlns='http://www.sitemaps.org/schemas/sitemap/0.9'>\n";

    $args  = array(
        'post_type'      => array( 'post', 'page' ),
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'fields'         => 'ids',
    );
    $posts = get_posts( $args );

    array_unshift( $posts, 0 ); // represent home page.

    foreach ( $posts as $post_id ) {
        if ( $post_id ) {
            $loc     = get_permalink( $post_id );
            $lastmod = get_post_modified_time( 'c', true, $post_id );
        } else {
            $loc     = home_url( '/' );
            $lastmod = current_time( 'c' );
        }
        echo '  <url>' .
             '<loc>' . esc_url( $loc ) . '</loc>' .
             '<lastmod>' . esc_html( $lastmod ) . '</lastmod>' .
             "</url>\n";
    }

    echo "</urlset>\n";
    exit;
}

// Garante que o sitemap possa ser acessado diretamente.
function sp_seo_add_rewrite_rule() {
    add_rewrite_rule( '^sp-sitemap\.xml$', 'index.php?sp_seo_sitemap=1', 'top' );
}
add_action( 'init', 'sp_seo_add_rewrite_rule' );

function sp_seo_query_vars( $vars ) {
    $vars[] = 'sp_seo_sitemap';
    return $vars;
}
add_filter( 'query_vars', 'sp_seo_query_vars' );

function sp_seo_template_redirect() {
    if ( get_query_var( 'sp_seo_sitemap' ) ) {
        sp_seo_generate_sitemap();
    }
}
add_action( 'template_redirect', 'sp_seo_template_redirect' );

function sp_seo_activate() {
    sp_seo_add_rewrite_rule();
    sp_seo_create_table();
    sp_seo_fetch_site_data( true );
    if ( ! wp_next_scheduled( 'sp_seo_refresh_event' ) ) {
        wp_schedule_event( time(), 'twicedaily', 'sp_seo_refresh_event' );
    }
    flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'sp_seo_activate' );

function sp_seo_deactivate() {
    wp_clear_scheduled_hook( 'sp_seo_refresh_event' );
    flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'sp_seo_deactivate' );

// Exibe aviso se o plugin estiver em dominio diferente.
function sp_seo_domain_warning() {
    if ( strpos( home_url(), 'studioprivilege.com.br' ) === false ) {
        echo '<div class="notice notice-warning"><p>Studio Privilege SEO foi projetado para ' . SP_SEO_DOMAIN . '.</p></div>';
    }
}
add_action( 'admin_notices', 'sp_seo_domain_warning' );

// Pagina de status no painel para forcar atualizacao manual.
function sp_seo_admin_menu() {
    add_options_page( 'Studio Privilege SEO', 'Studio Privilege SEO', 'manage_options', 'sp-seo', 'sp_seo_admin_page' );
    add_submenu_page( 'sp-seo', 'Relatório de Cliques', 'Relatório de Cliques', 'manage_options', 'sp-seo-report', 'sp_seo_click_report_page' );
}
add_action( 'admin_menu', 'sp_seo_admin_menu' );

function sp_seo_admin_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    if ( isset( $_POST['sp_seo_refresh_now'] ) && check_admin_referer( 'sp_seo_refresh' ) ) {
        sp_seo_fetch_site_data( true );
        echo '<div class="updated"><p>Dados atualizados com sucesso.</p></div>';
    }
    $last = get_option( 'sp_seo_last_fetch' );
    echo '<div class="wrap"><h1>Studio Privilege SEO</h1>';
    if ( $last ) {
        echo '<p><strong>Ultima atualizacao:</strong> ' . date_i18n( 'd/m/Y H:i', $last ) . '</p>';
    }
    echo '<form method="post">';
    wp_nonce_field( 'sp_seo_refresh' );
    submit_button( 'Atualizar agora', 'primary', 'sp_seo_refresh_now' );
    echo '</form></div>';
}

function sp_seo_click_report_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    global $wpdb;
    $table = $wpdb->prefix . SP_SEO_CLICK_TABLE;
    $rows = $wpdb->get_results( "SELECT city, COUNT(*) as c FROM $table GROUP BY city ORDER BY c DESC LIMIT 10", ARRAY_A );
    echo '<div class="wrap"><h1>Relatório de Cliques</h1><canvas id="sp-seo-chart" width="600" height="300"></canvas>';
    echo '<style>.sp-seo-bar{background:#4e73df;height:20px;margin:5px 0;color:#fff;padding:3px;}</style>';
    echo '<script>const spData=' . json_encode( $rows ) . ';const canvas=document.getElementById("sp-seo-chart");const ctx=canvas.getContext("2d");const max=Math.max(...spData.map(r=>parseInt(r.c)));const barWidth=canvas.width/spData.length;ctx.fillStyle="#4e73df";spData.forEach((r,i)=>{const h=(r.c/max)*(canvas.height-20);ctx.fillRect(i*barWidth,canvas.height-h,barWidth-4,h);ctx.fillStyle="#000";ctx.fillText(r.city,i*barWidth,canvas.height-5);ctx.fillStyle="#4e73df";});</script>';
    echo '</div>';
}
// Comando WP-CLI para atualizar manualmente.
if ( defined( 'WP_CLI' ) && WP_CLI ) {
    WP_CLI::add_command( 'sp-seo refresh', function() {
        sp_seo_fetch_site_data( true );
        WP_CLI::success( 'Studio Privilege SEO atualizado.' );
    } );
}
