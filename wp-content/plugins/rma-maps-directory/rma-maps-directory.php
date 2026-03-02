<?php
/**
 * Plugin Name: RMA Maps & Directory
 * Description: Endpoint público para diretório/mapa com filtros de UF e regras de visibilidade.
 * Version: 0.6.0
 * Author: RMA
 */

if (! defined('ABSPATH')) {
    exit;
}

final class RMA_Maps_Directory {
    private const CPT = 'rma_entidade';
    private const CACHE_INDEX_OPTION = 'rma_public_entities_cache_keys';

    public function __construct() {
        add_action('rest_api_init', [$this, 'register_routes']);
        add_action('save_post_' . self::CPT, [$this, 'clear_cache']);
        add_action('trashed_post', [$this, 'clear_cache_on_post_event'], 10, 2);
        add_action('untrashed_post', [$this, 'clear_cache_on_post_event'], 10, 2);
        add_action('deleted_post', [$this, 'clear_cache_on_post_event'], 10, 2);
        add_action('updated_post_meta', [$this, 'clear_cache_on_meta'], 10, 4);
        add_action('added_post_meta', [$this, 'clear_cache_on_meta'], 10, 4);
        add_action('deleted_post_meta', [$this, 'clear_cache_on_meta'], 10, 4);
        add_action('update_option_rma_maps_only_adimplente', [$this, 'clear_cache_on_option_change'], 10, 2);
    }

    public function register_routes(): void {
        register_rest_route('rma-public/v1', '/entities', [
            'methods' => 'GET',
            'callback' => [$this, 'list_entities'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('rma-public/v1', '/entities/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_entity_profile'],
            'permission_callback' => '__return_true',
        ]);
    }

    public function list_entities(WP_REST_Request $request): WP_REST_Response {
        $uf = strtoupper(sanitize_text_field((string) $request->get_param('uf')));
        if ($uf !== '' && ! preg_match('/^[A-Z]{2}$/', $uf)) {
            return new WP_REST_Response(['message' => 'UF inválida. Use sigla com 2 letras.'], 422);
        }

        $search = sanitize_text_field((string) $request->get_param('q'));
        $search = $this->normalize_search_term($search);

        $area = sanitize_text_field((string) $request->get_param('area'));
        $area = $this->normalize_search_term($area);

        $situacao = $this->normalize_situacao((string) $request->get_param('situacao'));
        if ($situacao === '') {
            return new WP_REST_Response(['message' => 'Situação inválida. Use: ativa, inadimplente ou todas.'], 422);
        }

        $only_adimplente = (bool) get_option('rma_maps_only_adimplente', true);
        $page = max(1, absint((int) $request->get_param('page')));
        $per_page = absint((int) $request->get_param('per_page'));
        if ($per_page <= 0) {
            $per_page = 100;
        }
        $per_page = min(200, $per_page);

        $cache_key = 'rma_public_entities_' . md5($uf . '|' . $search . '|' . $area . '|' . $situacao . '|' . $page . '|' . $per_page . '|' . ($only_adimplente ? '1' : '0'));
        $cached = get_transient($cache_key);

        if (is_array($cached) && isset($cached['items'], $cached['total'], $cached['total_pages'])) {
            $response = new WP_REST_Response($cached['items']);
            $response->header('X-WP-Total', (string) $cached['total']);
            $response->header('X-WP-TotalPages', (string) $cached['total_pages']);

            return $response;
        }

        $meta_query = [
            'relation' => 'AND',
            [
                'key' => 'governance_status',
                'value' => 'aprovado',
            ],
        ];

        $effective_situacao = $situacao;
        if ($effective_situacao === 'default') {
            $effective_situacao = $only_adimplente ? 'ativa' : 'todas';
        }

        if ($effective_situacao === 'ativa') {
            $meta_query[] = [
                'key' => 'finance_status',
                'value' => 'adimplente',
            ];
        } elseif ($effective_situacao === 'inadimplente') {
            $meta_query[] = [
                'key' => 'finance_status',
                'value' => 'inadimplente',
            ];
        }

        if ($uf !== '') {
            $meta_query[] = [
                'key' => 'uf',
                'value' => $uf,
            ];
        }

        if ($area !== '') {
            $meta_query[] = [
                'relation' => 'OR',
                [
                    'key' => 'area_interesse',
                    'value' => $area,
                    'compare' => 'LIKE',
                ],
                [
                    'key' => 'areas_interesse',
                    'value' => $area,
                    'compare' => 'LIKE',
                ],
            ];
        }

        $query = new WP_Query([
            'post_type' => self::CPT,
            'post_status' => 'publish',
            'posts_per_page' => $per_page,
            'paged' => $page,
            's' => $search,
            'meta_query' => $meta_query,
        ]);

        $entities = [];
        foreach ($query->posts as $post) {
            $id = $post->ID;
            $entities[] = [
                'id' => $id,
                'nome' => sanitize_text_field((string) get_the_title($id)),
                'slug' => sanitize_title((string) $post->post_name),
                'cidade' => sanitize_text_field((string) get_post_meta($id, 'cidade', true)),
                'uf' => $this->normalize_uf((string) get_post_meta($id, 'uf', true)),
                'lat' => (float) get_post_meta($id, 'lat', true),
                'lng' => (float) get_post_meta($id, 'lng', true),
                'nome_fantasia' => sanitize_text_field((string) get_post_meta($id, 'nome_fantasia', true)),
                'finance_status' => sanitize_text_field((string) get_post_meta($id, 'finance_status', true)),
                'area_interesse' => sanitize_text_field((string) get_post_meta($id, 'area_interesse', true)),
                'areas_interesse' => sanitize_text_field((string) get_post_meta($id, 'areas_interesse', true)),
            ];
        }

        wp_reset_postdata();

        $payload = [
            'items' => $entities,
            'total' => (int) $query->found_posts,
            'total_pages' => (int) $query->max_num_pages,
        ];

        set_transient($cache_key, $payload, 5 * MINUTE_IN_SECONDS);
        $this->remember_cache_key($cache_key);

        $response = new WP_REST_Response($entities);
        $response->header('X-WP-Total', (string) $payload['total']);
        $response->header('X-WP-TotalPages', (string) $payload['total_pages']);

        return $response;
    }



    public function get_entity_profile(WP_REST_Request $request): WP_REST_Response {
        $entity_id = absint((int) $request->get_param('id'));
        if ($entity_id <= 0 || get_post_type($entity_id) !== self::CPT || get_post_status($entity_id) !== 'publish') {
            return new WP_REST_Response(['message' => 'Entidade não encontrada.'], 404);
        }

        $governance = (string) get_post_meta($entity_id, 'governance_status', true);
        if ($governance !== 'aprovado') {
            return new WP_REST_Response(['message' => 'Entidade indisponível no diretório público.'], 404);
        }

        $data = [
            'id' => $entity_id,
            'nome' => sanitize_text_field((string) get_the_title($entity_id)),
            'slug' => sanitize_title((string) get_post_field('post_name', $entity_id)),
            'razao_social' => sanitize_text_field((string) get_post_meta($entity_id, 'razao_social', true)),
            'nome_fantasia' => sanitize_text_field((string) get_post_meta($entity_id, 'nome_fantasia', true)),
            'descricao' => sanitize_textarea_field((string) get_post_field('post_content', $entity_id)),
            'cidade' => sanitize_text_field((string) get_post_meta($entity_id, 'cidade', true)),
            'uf' => $this->normalize_uf((string) get_post_meta($entity_id, 'uf', true)),
            'logradouro' => sanitize_text_field((string) get_post_meta($entity_id, 'logradouro', true)),
            'bairro' => sanitize_text_field((string) get_post_meta($entity_id, 'bairro', true)),
            'lat' => (float) get_post_meta($entity_id, 'lat', true),
            'lng' => (float) get_post_meta($entity_id, 'lng', true),
            'area_interesse' => sanitize_text_field((string) get_post_meta($entity_id, 'area_interesse', true)),
            'areas_interesse' => sanitize_text_field((string) get_post_meta($entity_id, 'areas_interesse', true)),
            'finance_status' => sanitize_text_field((string) get_post_meta($entity_id, 'finance_status', true)),
            'profile_url' => get_permalink($entity_id) ?: '',
        ];

        return new WP_REST_Response($data);
    }

    private function normalize_uf(string $uf): string {
        $normalized = strtoupper(trim($uf));

        if (! preg_match('/^[A-Z]{2}$/', $normalized)) {
            return '';
        }

        return $normalized;
    }

    private function normalize_search_term(string $search): string {
        $search = trim($search);
        $search = function_exists('mb_substr') ? mb_substr($search, 0, 100) : substr($search, 0, 100);

        if (function_exists('mb_strtolower')) {
            return mb_strtolower($search);
        }

        return strtolower($search);
    }

    private function normalize_situacao(string $situacao): string {
        $situacao = trim(strtolower($situacao));

        if ($situacao === '') {
            return 'default';
        }

        $allowed = ['ativa', 'inadimplente', 'todas'];
        if (! in_array($situacao, $allowed, true)) {
            return '';
        }

        return $situacao;
    }

    public function clear_cache(int $post_id): void {
        if (get_post_type($post_id) !== self::CPT) {
            return;
        }

        $this->clear_cache_for_all();
    }

    public function clear_cache_on_post_event(int $post_id, $post = null): void {
        $post_type = '';
        if ($post instanceof WP_Post) {
            $post_type = (string) $post->post_type;
        } else {
            $post_type = (string) get_post_type($post_id);
        }

        if ($post_type !== self::CPT) {
            return;
        }

        $this->clear_cache_for_all();
    }


    public function clear_cache_on_option_change($old_value, $value): void {
        if ((bool) $old_value === (bool) $value) {
            return;
        }

        $this->clear_cache_for_all();
    }

    public function clear_cache_on_meta($meta_id, $post_id, $meta_key, $meta_value): void {
        if (get_post_type((int) $post_id) !== self::CPT) {
            return;
        }

        $keys = ['governance_status', 'finance_status', 'lat', 'lng', 'cidade', 'uf', 'area_interesse', 'areas_interesse'];
        if (in_array($meta_key, $keys, true)) {
            $this->clear_cache((int) $post_id);
        }
    }


    private function clear_cache_for_all(): void {
        $keys = get_option(self::CACHE_INDEX_OPTION, []);
        if (! is_array($keys)) {
            $keys = [];
        }

        foreach ($keys as $key) {
            delete_transient((string) $key);
        }

        update_option(self::CACHE_INDEX_OPTION, [], false);
    }

    private function remember_cache_key(string $cache_key): void {
        $keys = get_option(self::CACHE_INDEX_OPTION, []);
        $keys = is_array($keys) ? $keys : [];

        $changed = false;
        if (! in_array($cache_key, $keys, true)) {
            $keys[] = $cache_key;
            $changed = true;
        }

        $max_keys = 500;
        if (count($keys) > $max_keys) {
            $keys = array_slice($keys, -1 * $max_keys);
            $changed = true;
        }

        if ($changed) {
            update_option(self::CACHE_INDEX_OPTION, $keys, false);
        }
    }
}

new RMA_Maps_Directory();
