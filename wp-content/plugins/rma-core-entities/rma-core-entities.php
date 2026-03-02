<?php
/**
 * Plugin Name: RMA Core Entities
 * Description: Entidades centrais da RMA: CPT, metadados, validação/autopreenchimento de CNPJ e endpoint interno.
 * Version: 0.4.3
 * Author: RMA
 */

if (! defined('ABSPATH')) {
    exit;
}

final class RMA_Core_Entities {
    private const CPT = 'rma_entidade';
    private const CNPJ_API = 'https://brasilapi.com.br/api/cnpj/v1/';
    private const MAX_DOCUMENT_SIZE = 10485760; // 10MB
    private const MAX_DOCUMENTS_PER_ENTITY = 30;

    public function __construct() {
        add_action('init', [$this, 'register_post_type']);
        add_action('init', [$this, 'register_entity_meta']);
        add_action('rest_api_init', [$this, 'register_rest_routes']);
    }

    public function register_post_type(): void {
        register_post_type(self::CPT, [
            'labels' => [
                'name' => 'Entidades',
                'singular_name' => 'Entidade',
            ],
            'public' => false,
            'show_ui' => true,
            'show_in_rest' => true,
            'supports' => ['title', 'editor', 'author'],
            'menu_icon' => 'dashicons-groups',
        ]);
    }

    public function register_entity_meta(): void {
        $meta_fields = [
            'cnpj' => 'string',
            'razao_social' => 'string',
            'nome_fantasia' => 'string',
            'cnae_principal' => 'string',
            'email_contato' => 'string',
            'telefone_contato' => 'string',
            'cep' => 'string',
            'logradouro' => 'string',
            'numero' => 'string',
            'complemento' => 'string',
            'bairro' => 'string',
            'cidade' => 'string',
            'uf' => 'string',
            'lat' => 'number',
            'lng' => 'number',
            'governance_status' => 'string',
            'finance_status' => 'string',
            'mandato_fim' => 'string',
            'documentos_status' => 'string',
            'consent_lgpd' => 'boolean',
        ];

        foreach ($meta_fields as $field => $type) {
            register_post_meta(self::CPT, $field, [
                'type' => $type,
                'single' => true,
                'show_in_rest' => true,
                'auth_callback' => static function () {
                    return current_user_can('edit_posts');
                },
                'sanitize_callback' => function ($value) use ($field) {
                    return $this->sanitize_meta_value($field, $value);
                },
            ]);
        }
    }

    public function sanitize_meta_value(string $field, $value) {
        if ($field === 'consent_lgpd') {
            return (bool) rest_sanitize_boolean($value);
        }

        if ($field === 'lat' || $field === 'lng') {
            return is_numeric($value) ? (float) $value : 0.0;
        }

        if ($field === 'email_contato') {
            return sanitize_email((string) $value);
        }

        if ($field === 'telefone_contato') {
            return preg_replace('/[^0-9\+\-\(\)\s]/', '', (string) $value);
        }

        if ($field === 'cnpj') {
            return preg_replace('/\D+/', '', (string) $value);
        }

        return sanitize_text_field((string) $value);
    }

    public function register_rest_routes(): void {
        register_rest_route('rma/v1', '/cnpj/(?P<cnpj>[0-9\.\/-]+)', [
            'methods' => 'GET',
            'callback' => [$this, 'lookup_cnpj'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('rma/v1', '/entities', [
            'methods' => 'POST',
            'callback' => [$this, 'create_entity'],
            'permission_callback' => [$this, 'can_create_entity'],
        ]);

        register_rest_route('rma/v1', '/entities/(?P<id>\d+)/documents', [
            'methods' => 'GET',
            'callback' => [$this, 'list_documents'],
            'permission_callback' => [$this, 'can_manage_documents'],
        ]);

        register_rest_route('rma/v1', '/entities/(?P<id>\d+)/documents', [
            'methods' => 'POST',
            'callback' => [$this, 'upload_document'],
            'permission_callback' => [$this, 'can_manage_documents'],
        ]);

        register_rest_route('rma/v1', '/entities/(?P<id>\d+)/documents/(?P<doc_id>[a-f0-9\-]+)', [
            'methods' => 'GET',
            'callback' => [$this, 'download_document'],
            'permission_callback' => [$this, 'can_manage_documents'],
        ]);

        register_rest_route('rma/v1', '/entities/(?P<id>\d+)/documents/(?P<doc_id>[a-f0-9\-]+)', [
            'methods' => 'DELETE',
            'callback' => [$this, 'delete_document'],
            'permission_callback' => [$this, 'can_manage_documents'],
        ]);

        register_rest_route('rma/v1', '/entities/(?P<id>\d+)/status', [
            'methods' => 'GET',
            'callback' => [$this, 'entity_status'],
            'permission_callback' => [$this, 'can_manage_documents'],
        ]);
    }

    public function lookup_cnpj(WP_REST_Request $request): WP_REST_Response {
        $rate = $this->check_rate_limit('lookup_cnpj', 30, 5 * MINUTE_IN_SECONDS);
        if (is_wp_error($rate)) {
            return new WP_REST_Response(['message' => $rate->get_error_message()], 429);
        }

        $raw = (string) $request->get_param('cnpj');
        $cnpj = preg_replace('/\D+/', '', $raw);

        if (! $this->is_valid_cnpj($cnpj)) {
            return new WP_REST_Response([
                'valid' => false,
                'message' => 'CNPJ inválido.',
            ], 422);
        }

        $status_data = $this->validate_cnpj_exists_and_active($cnpj);
        if (is_wp_error($status_data)) {
            return new WP_REST_Response([
                'valid' => false,
                'message' => $status_data->get_error_message(),
            ], $this->cnpj_error_to_http_status($status_data));
        }

        return new WP_REST_Response([
            'valid' => true,
            'cnpj' => $cnpj,
            'razao_social' => $status_data['razao_social'] ?? '',
            'nome_fantasia' => $status_data['nome_fantasia'] ?? '',
            'cnae_principal' => $status_data['cnae_principal'] ?? '',
            'cep' => $status_data['cep'] ?? '',
            'logradouro' => $status_data['logradouro'] ?? '',
            'numero' => $status_data['numero'] ?? '',
            'complemento' => $status_data['complemento'] ?? '',
            'bairro' => $status_data['bairro'] ?? '',
            'cidade' => $status_data['cidade'] ?? '',
            'uf' => $status_data['uf'] ?? '',
            'status' => 'ATIVA',
        ]);
    }

    public function create_entity(WP_REST_Request $request): WP_REST_Response {
        $user_id = get_current_user_id();
        if ($user_id <= 0) {
            return new WP_REST_Response(['message' => 'Usuário não autenticado.'], 401);
        }

        $rate = $this->check_rate_limit('create_entity', 10, 5 * MINUTE_IN_SECONDS);
        if (is_wp_error($rate)) {
            return new WP_REST_Response(['message' => $rate->get_error_message()], 429);
        }

        $params = $request->get_json_params();
        $params = is_array($params) ? $params : [];
        if (empty($params)) {
            $fallback_params = $request->get_params();
            $params = is_array($fallback_params) ? $fallback_params : [];
        }

        $consent_lgpd = rest_sanitize_boolean($params['consent_lgpd'] ?? false);
        if (! $consent_lgpd) {
            return new WP_REST_Response(['message' => 'Consentimento LGPD obrigatório.'], 422);
        }

        $cnpj = preg_replace('/\D+/', '', (string) ($params['cnpj'] ?? ''));
        if (! $this->is_valid_cnpj($cnpj)) {
            return new WP_REST_Response(['message' => 'CNPJ inválido.'], 422);
        }

        if ($this->entity_exists_by_cnpj($cnpj)) {
            return new WP_REST_Response(['message' => 'Já existe entidade cadastrada para este CNPJ.'], 409);
        }

        $cnpj_status = $this->validate_cnpj_exists_and_active($cnpj);
        if (is_wp_error($cnpj_status)) {
            return new WP_REST_Response(['message' => $cnpj_status->get_error_message()], $this->cnpj_error_to_http_status($cnpj_status));
        }

        $official_razao = sanitize_text_field((string) ($cnpj_status['razao_social'] ?? ''));
        $official_uf = strtoupper(sanitize_text_field((string) ($cnpj_status['uf'] ?? '')));

        $razao = $official_razao !== '' ? $official_razao : sanitize_text_field((string) ($params['razao_social'] ?? ''));
        $uf = $official_uf !== '' ? $official_uf : strtoupper(sanitize_text_field((string) ($params['uf'] ?? '')));
        if ($razao === '' || ! preg_match('/^[A-Z]{2}$/', $uf)) {
            return new WP_REST_Response(['message' => 'Dados obrigatórios ausentes (razão social e UF).'], 422);
        }

        if (! empty($params['email_contato']) && ! is_email((string) $params['email_contato'])) {
            return new WP_REST_Response(['message' => 'E-mail de contato inválido.'], 422);
        }

        $params['consent_lgpd'] = $consent_lgpd;

        $post_id = wp_insert_post([
            'post_type' => self::CPT,
            'post_status' => 'draft',
            'post_author' => $user_id,
            'post_title' => $razao,
            'post_content' => sanitize_textarea_field((string) ($params['descricao'] ?? '')),
        ], true);

        if (is_wp_error($post_id)) {
            return new WP_REST_Response(['message' => 'Não foi possível cadastrar a entidade.'], 500);
        }

        $whitelist = [
            'cnpj', 'razao_social', 'nome_fantasia', 'cnae_principal', 'email_contato', 'telefone_contato',
            'cep', 'logradouro', 'numero', 'complemento', 'bairro', 'cidade', 'uf', 'lat', 'lng',
            'mandato_fim', 'consent_lgpd',
        ];

        foreach ($whitelist as $field) {
            if (array_key_exists($field, $params)) {
                update_post_meta($post_id, $field, $this->sanitize_meta_value($field, $params[$field]));
            }
        }

        if ($official_razao !== '') {
            update_post_meta($post_id, 'razao_social', $official_razao);
            wp_update_post([
                'ID' => $post_id,
                'post_title' => $official_razao,
            ]);
        }

        if (! empty($cnpj_status['nome_fantasia'])) {
            update_post_meta($post_id, 'nome_fantasia', sanitize_text_field((string) $cnpj_status['nome_fantasia']));
        }

        if ($official_uf !== '') {
            update_post_meta($post_id, 'uf', $official_uf);
        }

        if (! empty($cnpj_status['cidade'])) {
            update_post_meta($post_id, 'cidade', sanitize_text_field((string) $cnpj_status['cidade']));
        }

        $official_meta_map = [
            'cnae_principal' => 'cnae_principal',
            'cep' => 'cep',
            'logradouro' => 'logradouro',
            'numero' => 'numero',
            'complemento' => 'complemento',
            'bairro' => 'bairro',
        ];

        foreach ($official_meta_map as $target_meta => $source_key) {
            if (! empty($cnpj_status[$source_key])) {
                update_post_meta($post_id, $target_meta, sanitize_text_field((string) $cnpj_status[$source_key]));
            }
        }

        update_post_meta($post_id, 'cnpj_validated_at', current_time('mysql', true));
        update_post_meta($post_id, 'governance_status', 'pendente');
        update_post_meta($post_id, 'finance_status', 'pendente');
        update_post_meta($post_id, 'documentos_status', 'pendente');

        do_action('rma/entity_created', $post_id, $params);

        return new WP_REST_Response([
            'post_id' => $post_id,
            'status' => 'pendente',
            'message' => 'Cadastro enviado para análise.',
        ], 201);
    }


    public function can_create_entity(): bool {
        return is_user_logged_in();
    }

    public function can_manage_documents(WP_REST_Request $request): bool {
        if (! is_user_logged_in()) {
            return false;
        }

        $entity_id = (int) $request->get_param('id');
        if (get_post_type($entity_id) !== self::CPT) {
            return false;
        }

        if (current_user_can('edit_others_posts')) {
            return true;
        }

        return (int) get_post_field('post_author', $entity_id) === get_current_user_id();
    }

    public function list_documents(WP_REST_Request $request): WP_REST_Response {
        $entity_id = (int) $request->get_param('id');
        if (get_post_type($entity_id) !== self::CPT) {
            return new WP_REST_Response(['message' => 'Entidade inválida.'], 404);
        }

        $docs = get_post_meta($entity_id, 'entity_documents', true);
        $docs = is_array($docs) ? $docs : [];

        $response_docs = [];
        foreach ($docs as $doc) {
            $response_docs[] = [
                'id' => $doc['id'] ?? '',
                'name' => $doc['name'] ?? '',
                'size' => (int) ($doc['size'] ?? 0),
                'uploaded_at' => $doc['uploaded_at'] ?? '',
                'uploaded_by' => (int) ($doc['uploaded_by'] ?? 0),
            ];
        }

        return new WP_REST_Response($response_docs);
    }

    public function upload_document(WP_REST_Request $request): WP_REST_Response {
        $entity_id = (int) $request->get_param('id');
        if (get_post_type($entity_id) !== self::CPT) {
            return new WP_REST_Response(['message' => 'Entidade inválida.'], 404);
        }

        $rate = $this->check_rate_limit('document_upload', 40, 5 * MINUTE_IN_SECONDS);
        if (is_wp_error($rate)) {
            return new WP_REST_Response(['message' => $rate->get_error_message()], 429);
        }

        $file = $_FILES['file'] ?? null;

        if (! is_array($file) || empty($file['tmp_name'])) {
            return new WP_REST_Response(['message' => 'Arquivo obrigatório.'], 422);
        }

        if (! empty($file['error']) && (int) $file['error'] !== UPLOAD_ERR_OK) {
            return new WP_REST_Response(['message' => 'Falha no upload do arquivo.'], 422);
        }

        if (! is_uploaded_file((string) $file['tmp_name'])) {
            return new WP_REST_Response(['message' => 'Upload inválido.'], 422);
        }

        if (! is_readable((string) $file['tmp_name'])) {
            return new WP_REST_Response(['message' => 'Não foi possível ler o arquivo enviado.'], 422);
        }

        if ((int) $file['size'] <= 0 || (int) $file['size'] > self::MAX_DOCUMENT_SIZE) {
            return new WP_REST_Response(['message' => 'Arquivo inválido: tamanho máximo 10MB.'], 422);
        }

        $finfo = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : false;
        $mime = $finfo ? (string) finfo_file($finfo, $file['tmp_name']) : '';
        if ($finfo) {
            finfo_close($finfo);
        }

        $filetype = wp_check_filetype((string) ($file['name'] ?? ''));
        $checked = wp_check_filetype_and_ext((string) $file['tmp_name'], (string) ($file['name'] ?? ''));
        $extension = strtolower((string) ($filetype['ext'] ?? ''));
        $checked_ext = strtolower((string) ($checked['ext'] ?? ''));
        $checked_type = strtolower((string) ($checked['type'] ?? ''));

        $is_pdf_mime = $mime === 'application/pdf' || $checked_type === 'application/pdf';
        $is_pdf_ext = $extension === 'pdf' && ($checked_ext === '' || $checked_ext === 'pdf');

        if (! $is_pdf_mime || ! $is_pdf_ext) {
            return new WP_REST_Response(['message' => 'Apenas PDFs são permitidos.'], 422);
        }

        $upload_dir = wp_upload_dir();
        if (! empty($upload_dir['error'])) {
            return new WP_REST_Response(['message' => 'Diretório de upload indisponível.'], 500);
        }

        $docs = get_post_meta($entity_id, 'entity_documents', true);
        $docs = is_array($docs) ? $docs : [];

        if (count($docs) >= self::MAX_DOCUMENTS_PER_ENTITY) {
            return new WP_REST_Response([
                'message' => 'Limite de documentos por entidade atingido (30). Remova um arquivo antes de enviar outro.',
            ], 409);
        }

        $private_base = trailingslashit($upload_dir['basedir']) . 'rma-private/' . $entity_id;
        if (! wp_mkdir_p($private_base)) {
            return new WP_REST_Response(['message' => 'Não foi possível preparar o diretório privado.'], 500);
        }

        $this->protect_directory($private_base);

        $original_name = sanitize_file_name((string) $file['name']);
        if ($original_name === '') {
            $original_name = 'documento.pdf';
        }

        $doc_id = wp_generate_uuid4();
        $safe_name = $doc_id . '-' . $original_name;
        $target = trailingslashit($private_base) . $safe_name;

        if (! move_uploaded_file($file['tmp_name'], $target)) {
            return new WP_REST_Response(['message' => 'Falha ao salvar documento.'], 500);
        }

        $docs[] = [
            'id' => $doc_id,
            'name' => $original_name,
            'path' => $target,
            'size' => (int) $file['size'],
            'uploaded_by' => get_current_user_id(),
            'uploaded_at' => current_time('mysql', true),
        ];

        update_post_meta($entity_id, 'entity_documents', $docs);
        update_post_meta($entity_id, 'documentos_status', 'enviado');

        do_action('rma/entity_document_uploaded', $entity_id, $doc_id, $original_name);

        return new WP_REST_Response([
            'message' => 'Documento enviado com sucesso.',
            'document_id' => $doc_id,
            'name' => $original_name,
        ], 201);
    }

    public function download_document(WP_REST_Request $request): WP_REST_Response {
        $entity_id = (int) $request->get_param('id');
        if (get_post_type($entity_id) !== self::CPT) {
            return new WP_REST_Response(['message' => 'Entidade inválida.'], 404);
        }

        $rate = $this->check_rate_limit('document_download', 120, 5 * MINUTE_IN_SECONDS);
        if (is_wp_error($rate)) {
            return new WP_REST_Response(['message' => $rate->get_error_message()], 429);
        }

        $doc_id = sanitize_text_field((string) $request->get_param('doc_id'));

        $docs = get_post_meta($entity_id, 'entity_documents', true);
        $docs = is_array($docs) ? $docs : [];

        $selected = null;
        foreach ($docs as $doc) {
            if (($doc['id'] ?? '') === $doc_id) {
                $selected = $doc;
                break;
            }
        }

        if (! is_array($selected) || empty($selected['path'])) {
            return new WP_REST_Response(['message' => 'Documento não encontrado.'], 404);
        }

        $upload_dir = wp_upload_dir();
        $expected_base = trailingslashit($upload_dir['basedir']) . 'rma-private/' . $entity_id;

        $path = (string) $selected['path'];
        $real_file = realpath($path);
        $real_base = realpath($expected_base);

        if (! $real_file || ! $real_base || strpos($real_file, $real_base) !== 0 || ! file_exists($real_file)) {
            return new WP_REST_Response(['message' => 'Documento não encontrado.'], 404);
        }

        $name = sanitize_file_name((string) ($selected['name'] ?? 'documento.pdf'));
        if ($name === '') {
            $name = 'documento.pdf';
        }

        $size = @filesize($real_file);
        if ($size === false || $size < 0) {
            return new WP_REST_Response(['message' => 'Não foi possível preparar o download do documento.'], 500);
        }

        nocache_headers();
        header('Content-Type: application/pdf');
        header('X-Content-Type-Options: nosniff');
        header('Content-Disposition: attachment; filename="' . $name . '"');
        header('Content-Length: ' . (string) $size);
        readfile($real_file);
        exit;
    }


    public function entity_status(WP_REST_Request $request): WP_REST_Response {
        $entity_id = (int) $request->get_param('id');
        if (get_post_type($entity_id) !== self::CPT) {
            return new WP_REST_Response(['message' => 'Entidade inválida.'], 404);
        }

        $approvals = get_post_meta($entity_id, 'governance_approvals', true);
        $approvals = is_array($approvals) ? $approvals : [];

        $governance = (string) get_post_meta($entity_id, 'governance_status', true);
        $finance = (string) get_post_meta($entity_id, 'finance_status', true);
        $docs_status = (string) get_post_meta($entity_id, 'documentos_status', true);

        $pending_items = [];
        if ($governance !== 'aprovado') {
            $pending_items[] = 'governanca';
        }
        if ($finance !== 'adimplente') {
            $pending_items[] = 'financeiro';
        }
        if ($docs_status !== 'enviado') {
            $pending_items[] = 'documentos';
        }

        $documents = get_post_meta($entity_id, 'entity_documents', true);
        $documents = is_array($documents) ? $documents : [];

        $next_actions = [];
        if (in_array('documentos', $pending_items, true)) {
            $next_actions[] = 'Envie os documentos obrigatórios em PDF.';
        }
        if (in_array('governanca', $pending_items, true)) {
            $next_actions[] = 'Aguarde análise da governança RMA.';
        }
        if (in_array('financeiro', $pending_items, true) && $governance === 'aprovado') {
            $next_actions[] = 'Regularize a anuidade via PIX para manter a adimplência.';
        }

        $approvals_count = count($approvals);
        $approvals_remaining = max(0, 3 - $approvals_count);
        $publish_eligible = $governance === 'aprovado' && $finance === 'adimplente';

        return new WP_REST_Response([
            'entity_id' => $entity_id,
            'governance_status' => $governance,
            'finance_status' => $finance,
            'documentos_status' => $docs_status,
            'documents_count' => count($documents),
            'cnpj_validated_at' => (string) get_post_meta($entity_id, 'cnpj_validated_at', true),
            'rejection_reason' => (string) get_post_meta($entity_id, 'governance_rejection_reason', true),
            'approvals_count' => $approvals_count,
            'approvals_remaining' => $approvals_remaining,
            'publish_eligible' => $publish_eligible,
            'pending_items' => $pending_items,
            'next_actions' => $next_actions,
        ]);
    }

    public function delete_document(WP_REST_Request $request): WP_REST_Response {
        $entity_id = (int) $request->get_param('id');
        if (get_post_type($entity_id) !== self::CPT) {
            return new WP_REST_Response(['message' => 'Entidade inválida.'], 404);
        }

        $rate = $this->check_rate_limit('document_delete', 60, 5 * MINUTE_IN_SECONDS);
        if (is_wp_error($rate)) {
            return new WP_REST_Response(['message' => $rate->get_error_message()], 429);
        }

        $doc_id = sanitize_text_field((string) $request->get_param('doc_id'));

        $docs = get_post_meta($entity_id, 'entity_documents', true);
        $docs = is_array($docs) ? $docs : [];

        $kept = [];
        $deleted = null;
        foreach ($docs as $doc) {
            if (($doc['id'] ?? '') === $doc_id) {
                $deleted = $doc;
                continue;
            }
            $kept[] = $doc;
        }

        if (! is_array($deleted)) {
            return new WP_REST_Response(['message' => 'Documento não encontrado.'], 404);
        }

        $path = (string) ($deleted['path'] ?? '');
        $upload_dir = wp_upload_dir();
        $expected_base = trailingslashit($upload_dir['basedir']) . 'rma-private/' . $entity_id;
        $real_file = realpath($path);
        $real_base = realpath($expected_base);

        if ($real_file && $real_base && strpos($real_file, $real_base) === 0 && file_exists($real_file)) {
            wp_delete_file($real_file);
        }

        update_post_meta($entity_id, 'entity_documents', $kept);
        if (empty($kept)) {
            update_post_meta($entity_id, 'documentos_status', 'pendente');
        }

        do_action('rma/entity_document_deleted', $entity_id, $doc_id, $deleted);

        return new WP_REST_Response([
            'message' => 'Documento removido com sucesso.',
            'document_id' => $doc_id,
        ]);
    }

    private function protect_directory(string $directory): void {
        $index = trailingslashit($directory) . 'index.html';
        if (! file_exists($index)) {
            file_put_contents($index, '');
        }

        $htaccess = trailingslashit($directory) . '.htaccess';
        if (! file_exists($htaccess)) {
            file_put_contents($htaccess, "Deny from all\n");
        }
    }

    private function check_rate_limit(string $scope, int $max, int $ttl) {
        $subject = $this->rate_limit_subject();
        $key = 'rma_rate_' . $scope . '_' . md5($subject);
        $count = (int) get_transient($key);

        if ($count >= $max) {
            return new WP_Error('rma_rate_limit', 'Limite de tentativas excedido. Tente novamente em alguns minutos.');
        }

        set_transient($key, $count + 1, $ttl);

        return true;
    }


    private function rate_limit_subject(): string {
        $user_id = get_current_user_id();
        if ($user_id > 0) {
            return 'user:' . $user_id;
        }

        $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';
        if ($ip !== '' && filter_var($ip, FILTER_VALIDATE_IP)) {
            return 'ip:' . $ip;
        }

        $ua = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'])) : 'unknown';

        return 'fallback:' . md5($ua);
    }

    private function entity_exists_by_cnpj(string $cnpj): bool {
        $query = new WP_Query([
            'post_type' => self::CPT,
            'post_status' => ['publish', 'draft', 'pending', 'private'],
            'fields' => 'ids',
            'posts_per_page' => 1,
            'meta_query' => [
                [
                    'key' => 'cnpj',
                    'value' => $cnpj,
                ],
            ],
        ]);

        return ! empty($query->posts);
    }


    private function cnpj_error_to_http_status(WP_Error $error): int {
        $code = (string) $error->get_error_code();
        if ($code === 'rma_cnpj_lookup_failed') {
            return 503;
        }

        if ($code === 'rma_cnpj_not_found') {
            return 404;
        }

        if ($code === 'rma_cnpj_inactive') {
            return 422;
        }

        return 422;
    }

    private function validate_cnpj_exists_and_active(string $cnpj) {
        $cache_key = 'rma_cnpj_ok_' . $cnpj;
        $cached = get_transient($cache_key);
        if (is_array($cached)) {
            return $cached;
        }

        $response = wp_remote_get(self::CNPJ_API . $cnpj, [
            'timeout' => 12,
        ]);

        if (is_wp_error($response)) {
            return new WP_Error('rma_cnpj_lookup_failed', 'Não foi possível validar o CNPJ no momento.');
        }

        $status_code = (int) wp_remote_retrieve_response_code($response);
        $body = json_decode((string) wp_remote_retrieve_body($response), true);

        if ($status_code === 404) {
            return new WP_Error('rma_cnpj_not_found', 'CNPJ não encontrado na base oficial.');
        }

        if ($status_code === 429 || $status_code >= 500) {
            return new WP_Error('rma_cnpj_lookup_failed', 'Serviço de validação de CNPJ temporariamente indisponível.');
        }

        if ($status_code >= 400 || ! is_array($body)) {
            return new WP_Error('rma_cnpj_lookup_failed', 'Não foi possível validar o CNPJ no momento.');
        }

        $situacao = strtoupper((string) ($body['descricao_situacao_cadastral'] ?? ''));
        if ($situacao !== '' && $situacao !== 'ATIVA') {
            return new WP_Error('rma_cnpj_inactive', 'CNPJ encontrado, porém não está ativo.');
        }

        $payload = [
            'razao_social' => $body['razao_social'] ?? '',
            'nome_fantasia' => $body['nome_fantasia'] ?? '',
            'cnae_principal' => $body['cnae_fiscal_descricao'] ?? '',
            'cep' => $body['cep'] ?? '',
            'logradouro' => $body['logradouro'] ?? '',
            'numero' => $body['numero'] ?? '',
            'complemento' => $body['complemento'] ?? '',
            'bairro' => $body['bairro'] ?? '',
            'cidade' => $body['municipio'] ?? '',
            'uf' => strtoupper((string) ($body['uf'] ?? '')),
        ];

        set_transient($cache_key, $payload, 12 * HOUR_IN_SECONDS);

        return $payload;
    }

    private function is_valid_cnpj(string $cnpj): bool {
        if (! preg_match('/^\d{14}$/', $cnpj)) {
            return false;
        }

        if (preg_match('/^(\d)\1{13}$/', $cnpj)) {
            return false;
        }

        $weights1 = [5,4,3,2,9,8,7,6,5,4,3,2];
        $weights2 = [6,5,4,3,2,9,8,7,6,5,4,3,2];

        $sum1 = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum1 += intval($cnpj[$i]) * $weights1[$i];
        }
        $digit1 = $sum1 % 11 < 2 ? 0 : 11 - ($sum1 % 11);

        $sum2 = 0;
        for ($i = 0; $i < 13; $i++) {
            $sum2 += intval($cnpj[$i]) * $weights2[$i];
        }
        $digit2 = $sum2 % 11 < 2 ? 0 : 11 - ($sum2 % 11);

        return intval($cnpj[12]) === $digit1 && intval($cnpj[13]) === $digit2;
    }
}

new RMA_Core_Entities();
