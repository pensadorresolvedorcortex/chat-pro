<?php
/**
 * Plugin Name: Addon Ahousi
 * Description: Instant search plugin providing autocomplete and quick results.
 * Version: 0.1.0
 * Author: ChatGPT
 * License: GPL2
 */

if (!defined('ABSPATH')) {
    exit;
}

class Addon_Ahousi {
    public function __construct() {
        add_action('init', [ $this, 'register_shortcodes' ]);
        add_action('wp_enqueue_scripts', [ $this, 'register_assets' ]);
        add_action('rest_api_init', [ $this, 'register_routes' ]);
    }

    public function register_shortcodes() {
        add_shortcode('addon_ahousi_search', [ $this, 'render_search' ]);
    }

    public function render_search() {
        ob_start();
        ?>
        <div class="ahousi-search">
            <div class="ahousi-search__field">
                <span class="ahousi-search__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" width="16" height="16">
                        <path fill="currentColor" d="M15.5 14h-.79l-.28-.27a6.471 6.471 0 001.48-5.34C15.22 5.01 12.21 2 8.61 2S2 5.01 2 8.39s3.01 6.39 6.39 6.39a6.471 6.471 0 005.34-1.48l.27.28v.79l4.99 4.98L20.49 19l-4.99-4.99zm-6.89 0C6.01 14 4 11.99 4 9.39S6.01 4.78 8.61 4.78s4.61 2.01 4.61 4.61-2.01 4.61-4.61 4.61z"/>
                    </svg>
                </span>
                <input type="text" class="ahousi-search__input" placeholder="Buscar..." aria-label="Buscar" />
            </div>
            <ul class="ahousi-search__suggestions" role="listbox"></ul>
            <div class="ahousi-search__results"></div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function register_assets() {
        if (!is_admin()) {
            wp_enqueue_style('addon-ahousi-style', plugin_dir_url(__FILE__) . 'assets/addon-ahousi.css', [], '0.1.0');
            wp_enqueue_script('addon-ahousi-script', plugin_dir_url(__FILE__) . 'assets/addon-ahousi.js', [ 'wp-util' ], '0.1.0', true);
        }
    }

    public function register_routes() {
        register_rest_route('addon-ahousi/v1', '/query', [
            'methods' => 'GET',
            'callback' => [ $this, 'handle_query' ],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('addon-ahousi/v1', '/suggest', [
            'methods' => 'GET',
            'callback' => [ $this, 'handle_suggest' ],
            'permission_callback' => '__return_true',
        ]);
    }

    public function handle_query(\WP_REST_Request $request) {
        $term = sanitize_text_field($request->get_param('q'));
        $page = max(1, (int) $request->get_param('page'));
        $post_types = $request->get_param('post_type');
        $post_types = $post_types ? array_map('sanitize_key', (array) $post_types) : [ 'post', 'page' ];

        $query = new \WP_Query([
            's' => $term,
            'post_type' => $post_types,
            'posts_per_page' => 20,
            'paged' => $page,
        ]);

        $items = [];
        foreach ($query->posts as $post) {
            $items[] = [
                'title' => get_the_title($post),
                'url' => get_permalink($post),
                'snippet' => wp_html_excerpt($post->post_content, 120, '...'),
                'type' => $post->post_type,
                'category' => ( $cat = get_the_category($post->ID) ) ? $cat[0]->name : '',
            ];
        }

        return rest_ensure_response([
            'items' => $items,
            'total' => (int) $query->found_posts,
        ]);
    }

    public function handle_suggest(\WP_REST_Request $request) {
        $term = sanitize_text_field($request->get_param('q'));
        $query = new \WP_Query([
            's' => $term,
            'posts_per_page' => 8,
            'post_type' => [ 'post', 'page' ],
        ]);

        $items = [];
        foreach ($query->posts as $post) {
            $items[] = [
                'title' => get_the_title($post),
                'url' => get_permalink($post),
            ];
        }

        return rest_ensure_response([ 'items' => $items ]);
    }
}

new Addon_Ahousi();
