<?php
/**
 * Functions/snippet completo do fluxo RMA para colar no functions.php.
 *
 * O que entrega:
 * - Enqueue do UI kit + fonte Federo
 * - Shortcode [rma_glass_card_demo]
 * - Shortcode [rma_conta_setup] com formulário atualizado (novos inputs)
 * - Checklist de documentos (PDF, imagem ou Word) com tooltip + upload
 * - Redirect forçado para /conta/ até concluir governança + docs + financeiro
 */

if (! defined('ABSPATH')) {
    exit;
}

function rma_ui_theme_base() {
    $child_path = trailingslashit(get_stylesheet_directory()) . 'ui';
    if (is_dir($child_path)) {
        return [
            'uri'  => trailingslashit(get_stylesheet_directory_uri()) . 'ui',
            'path' => $child_path,
        ];
    }

    $parent_path = trailingslashit(get_template_directory()) . 'ui';
    if (is_dir($parent_path)) {
        return [
            'uri'  => trailingslashit(get_template_directory_uri()) . 'ui',
            'path' => $parent_path,
        ];
    }

    return null;
}

function rma_account_setup_url() {
    return trailingslashit(home_url('/conta/'));
}

function rma_account_setup_path() {
    $path = wp_parse_url(rma_account_setup_url(), PHP_URL_PATH);
    return is_string($path) ? untrailingslashit($path) : '';
}

function rma_get_entity_id_by_author($user_id) {
    $query = new WP_Query([
        'post_type'              => 'rma_entidade',
        'post_status'            => ['publish', 'draft', 'pending', 'private'],
        'author'                 => (int) $user_id,
        'posts_per_page'         => 1,
        'fields'                 => 'ids',
        'no_found_rows'          => true,
        'update_post_meta_cache' => false,
        'update_post_term_cache' => false,
    ]);

    $entity_id = ! empty($query->posts) ? (int) $query->posts[0] : 0;
    wp_reset_postdata();

    return $entity_id;
}

function rma_flow_debug_enabled() {
    return defined('WP_DEBUG') && WP_DEBUG && isset($_GET['rma_debug_flow']) && sanitize_text_field((string) $_GET['rma_debug_flow']) === '1';
}

function rma_flow_debug_log($message, array $context = []) {
    if (! rma_flow_debug_enabled()) {
        return;
    }

    if (! empty($context)) {
        $message .= ' | ' . wp_json_encode($context);
    }

    error_log('[RMA_FLOW] ' . $message);
}


function rma_otp_transient_key(int $user_id): string {
    return 'rma_otp_code_' . $user_id;
}

function rma_otp_send_lock_key(int $user_id): string {
    return 'rma_otp_send_lock_' . $user_id;
}

function rma_send_otp_code_for_user(int $user_id) {
    $user = get_user_by('id', $user_id);
    if (! $user || ! $user->user_email) {
        return new WP_Error('rma_otp_user_invalid', 'Usuário inválido para verificação.');
    }

    if (get_transient(rma_otp_send_lock_key($user_id))) {
        return new WP_Error('rma_otp_rate_limited', 'Aguarde alguns segundos antes de solicitar um novo código.');
    }

    set_transient(rma_otp_send_lock_key($user_id), '1', 30);

    $code = (string) wp_rand(100000, 999999);
    $payload = [
        'code' => $code,
        'expires_at' => time() + (10 * MINUTE_IN_SECONDS),
        'attempts' => 0,
    ];
    set_transient(rma_otp_transient_key($user_id), $payload, 10 * MINUTE_IN_SECONDS);

    $subject = 'Código de verificação de segurança - RMA';
    $message = function_exists('rma_render_verification_email_template')
        ? rma_render_verification_email_template([
            'nome' => (string) $user->display_name,
            'codigo' => $code,
            'data' => wp_date('d/m/Y H:i'),
            'empresa' => (string) get_option('rma_email_verification_company', 'RMA'),
        ])
        : ('Seu código de verificação é: ' . $code);

    $headers = ['Content-Type: text/html; charset=UTF-8'];
    $sent = wp_mail((string) $user->user_email, $subject, $message, $headers);

    if (! $sent) {
        delete_transient(rma_otp_transient_key($user_id));
        return new WP_Error('rma_otp_send_failed', 'Não foi possível enviar o código no momento. Tente novamente ou verifique sua caixa de spam.');
    }

    return true;
}

add_action('rest_api_init', function () {
    register_rest_route('rma/v1', '/otp/send', [
        'methods' => 'POST',
        'permission_callback' => function () {
            return is_user_logged_in();
        },
        'callback' => function () {
            $result = rma_send_otp_code_for_user(get_current_user_id());
            if (is_wp_error($result)) {
                return new WP_REST_Response(['message' => $result->get_error_message()], 503);
            }
            return new WP_REST_Response(['sent' => true, 'message' => 'Código enviado para seu email institucional.']);
        },
    ]);


    register_rest_route('rma/v1', '/otp/status', [
        'methods' => 'GET',
        'permission_callback' => function () {
            return is_user_logged_in();
        },
        'callback' => function () {
            $verified_at = (int) get_user_meta(get_current_user_id(), 'rma_otp_verified_at', true);
            $valid_until = $verified_at > 0 ? ($verified_at + (30 * MINUTE_IN_SECONDS)) : 0;
            $is_valid = $valid_until > time();
            return new WP_REST_Response([
                'verified' => $is_valid,
                'verified_at' => $verified_at,
                'valid_until' => $valid_until,
            ]);
        },
    ]);

    register_rest_route('rma/v1', '/otp/verify', [
        'methods' => 'POST',
        'permission_callback' => function () {
            return is_user_logged_in();
        },
        'callback' => function (WP_REST_Request $request) {
            $user_id = get_current_user_id();
            $code = preg_replace('/\D+/', '', (string) $request->get_param('code'));

            $payload = get_transient(rma_otp_transient_key($user_id));
            if (! is_array($payload) || empty($payload['code'])) {
                return new WP_REST_Response(['verified' => false, 'message' => 'Código expirado. Solicite um novo envio.'], 410);
            }

            $attempts = (int) ($payload['attempts'] ?? 0) + 1;
            $payload['attempts'] = $attempts;
            set_transient(rma_otp_transient_key($user_id), $payload, max(30, ((int) ($payload['expires_at'] ?? time())) - time()));

            if ($attempts > 5) {
                delete_transient(rma_otp_transient_key($user_id));
                return new WP_REST_Response(['verified' => false, 'message' => 'Muitas tentativas inválidas. Solicite novo código.'], 429);
            }

            if ((int) ($payload['expires_at'] ?? 0) < time()) {
                delete_transient(rma_otp_transient_key($user_id));
                return new WP_REST_Response(['verified' => false, 'message' => 'Código expirado. Solicite novo envio.'], 410);
            }

            if (! hash_equals((string) $payload['code'], (string) $code)) {
                return new WP_REST_Response(['verified' => false, 'message' => 'Código inválido. Digite os 6 dígitos enviados por email.'], 422);
            }

            update_user_meta($user_id, 'rma_otp_verified_at', time());
            delete_transient(rma_otp_transient_key($user_id));

            return new WP_REST_Response(['verified' => true, 'message' => 'Verificação confirmada.']);
        },
    ]);
});

add_action('wp_enqueue_scripts', function () {
    $base = rma_ui_theme_base();
    if (! $base) {
        return;
    }

    $css_file = trailingslashit($base['path']) . 'rma-glass-theme.css';
    $js_file  = trailingslashit($base['path']) . 'rma-glass-theme.js';

    if (file_exists($css_file)) {
        wp_enqueue_style('rma-glass-theme', trailingslashit($base['uri']) . 'rma-glass-theme.css', [], (string) filemtime($css_file));
    }

    if (file_exists($js_file)) {
        wp_enqueue_script('rma-glass-theme-js', trailingslashit($base['uri']) . 'rma-glass-theme.js', [], (string) filemtime($js_file), true);
    }

    wp_enqueue_style('rma-federo-font', 'https://fonts.googleapis.com/css2?family=Federo&display=swap', [], null);
    wp_enqueue_style('rma-mavenpro-font', 'https://fonts.googleapis.com/css2?family=Maven+Pro:wght@400;500;600;700&display=swap', [], null);
});

add_shortcode('rma_glass_card_demo', function () {
    ob_start();
    ?>
    <section class="rma-glass-card" style="margin:20px 0;">
        <span class="rma-badge">RMA • Glasmorphism Ultra White</span>
        <h2 class="rma-glass-title">Card de demonstração</h2>
        <p class="rma-glass-subtitle">Este bloco usa Federo, #7bad39 e #37302c com acabamento translúcido branco.</p>
        <div class="rma-actions">
            <a class="rma-button" href="<?php echo esc_url(rma_account_setup_url()); ?>">Ir para conta</a>
        </div>
    </section>
    <?php
    return (string) ob_get_clean();
});

add_shortcode('rma_conta_setup', function () {
    if (! is_user_logged_in()) {
        return '<p>Você precisa estar logado para completar o cadastro da entidade.</p>';
    }

    $required_docs = [
        'ficha_inscricao' => ['label' => 'Ficha de inscrição cadastral', 'tip' => 'Ficha preenchida e assinada. Assinatura gov.br é aceita.'],
        'comprovante_cnpj' => ['label' => 'Comprovante de CNPJ', 'tip' => 'Comprovante de Inscrição e Situação Cadastral (Receita Federal).'],
        'ata_fundacao' => ['label' => 'Ata de fundação', 'tip' => 'Ata registrada ou PDF escaneado legível.'],
        'ata_diretoria' => ['label' => 'Ata da diretoria atual', 'tip' => 'Ata da eleição da diretoria atual.'],
        'estatuto' => ['label' => 'Estatuto e alterações', 'tip' => 'Estatuto e alterações consolidadas.'],
        'relatorio_atividades' => ['label' => 'Relatório de atividades', 'tip' => 'Últimos 2 anos de atividades.'],
        'cartas_recomendacao' => ['label' => '2 cartas de recomendação', 'tip' => 'De organizações filiadas à RMA.'],
    ];

    $entity_id = rma_get_entity_id_by_author(get_current_user_id());
    $rest_base = rest_url('rma/v1');
    $rest_nonce = wp_create_nonce('wp_rest');

    $dashboard_url = home_url('/dashboard/');
    $docs_url = apply_filters('rma_docs_page_url', home_url('/documentos/'));
    $finance_url = apply_filters('rma_finance_page_url', home_url('/financeiro/'));
    $checkout_url = apply_filters('rma_checkout_url', home_url('/rma/checkout/'));
    $layout_tune_css = '<style id="rma-layout-tune">
'
        . '.rma-premium-card,.rma-premium-card--setup{max-width:1060px;margin:0 auto;border-radius:18px;background:#fff;border:1px solid #edf1f4;box-shadow:0 16px 40px rgba(0,0,0,.05);padding:28px;}
'
        . '.rma-premium-form{display:grid;gap:16px;}
'
        . '.rma-grid-2{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px;}
'
        . '.rma-grid-3{display:grid;grid-template-columns:1.2fr .6fr .8fr;gap:14px;}
'
        . '.rma-field-label{display:block;font-size:.88rem;color:#5a6472;margin:0 0 6px;}
'
        . '.rma-auth-card{background:#fff;border:1px solid #edf1f4;border-radius:18px;padding:20px;margin:14px 0 18px;}
'
        . '.rma-auth-title{margin:0 0 6px;font-size:1.35rem;font-weight:700;color:#1f2937;text-align:center;}
'
        . '.rma-auth-subtitle{margin:0 0 14px;color:#4b5563;font-size:.95rem;text-align:center;}
'
        . '.rma-otp-grid{display:grid;grid-template-columns:repeat(6,minmax(0,50px));gap:10px;margin:0 auto 12px;justify-content:center;}
'
        . '.rma-otp-input{height:50px;border:1px solid #d7dee7;border-radius:12px;text-align:center;font-size:1.1rem;font-weight:600;outline:none;transition:border-color .2s ease,box-shadow .2s ease;}
'
        . '.rma-otp-input:focus{border-color:#7bad39;box-shadow:0 0 0 3px rgba(123,173,57,.16);}
'
        . '.rma-otp-input.is-error{border-color:#ef4444;}
'
        . '.rma-otp-input.is-success{border-color:#22c55e;}
'
        . '.rma-resend-link{color:#4b5563;font-size:.86rem;text-decoration:none;display:inline-block;margin-top:8px;}
'
        . '.rma-resend-link[aria-disabled="true"]{pointer-events:none;opacity:.55;}
'
        . '.rma-flow-stepper{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:10px;margin:14px 0 16px;}
'
        . '.rma-flow-step{border:1px solid #e5e9ef;background:#fff;border-radius:12px;padding:10px 12px;font-size:.82rem;color:#6b7280;text-align:center;display:flex;align-items:center;justify-content:center;gap:6px;}
'
        . '.rma-flow-step.is-done{color:#2f7d32;border-color:#bfe3b0;}
'
        . '.rma-flow-step.is-current{color:#111827;border-color:#7bad39;background:#f6fbf4;font-weight:600;}
'
        . '.rma-flow-step.is-locked{color:#98a2b3;background:#f5f7fa;}
'
        . '.rma-primary-cta,.rma-nav-actions{display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin-top:10px;}\n'
        . '.rma-auth-actions{display:flex;justify-content:space-between;align-items:center;gap:10px;margin:8px 0 2px;}\n'
        . '.rma-auth-card{text-align:center;}\n'
        . '.rma-glass-title{color:#1f2937;font-weight:700;}\n'
        . '.rma-glass-subtitle{color:#4b5563;}\n'
        . '.btn-rma-primary{background:linear-gradient(135deg,#7bad39,#5ddabb)!important;border:none!important;color:#fff!important;font-weight:600;padding:12px 24px;border-radius:14px;transition:all .3s ease;box-shadow:0 8px 18px rgba(0,0,0,.08);}
'
        . '.btn-rma-primary:hover{transform:translateY(-2px);filter:brightness(1.04);box-shadow:0 12px 24px rgba(0,0,0,.12);}
'
        . '.btn-rma-secondary{background:#fff!important;border:1px solid #d7dee7!important;color:#4b5563!important;font-weight:600;padding:12px 20px;border-radius:14px;transition:all .3s ease;}
'
        . '.btn-rma-secondary:hover{transform:translateY(-2px);box-shadow:0 8px 16px rgba(0,0,0,.06);}
'
        . '.rma-modern-dropzone{border:1px dashed #cfd8e3;border-radius:14px;padding:12px;background:#fbfcfd;}
'
        . '.rma-phone-row{display:grid;grid-template-columns:120px 1fr;gap:10px;}\n'
        . '.rma-drop-item{position:relative;}\n'
        . '.rma-dropzone{border:1px dashed #cfd8e3;border-radius:12px;padding:12px;background:#fbfcfd;display:flex;flex-direction:column;gap:8px;}\n'
        . '.rma-dropzone.is-drag{border-color:#7bad39;background:#f4fbef;}\n'
        . '.rma-file-preview{font-size:.82rem;color:#4b5563;word-break:break-word;}\n'
        . '@media(max-width:860px){.rma-grid-2,.rma-grid-3,.rma-flow-stepper{grid-template-columns:1fr;}.rma-otp-grid{grid-template-columns:repeat(6,minmax(0,1fr));}}
'
        . '</style>';

    if ($entity_id > 0) {
        $governance = (string) get_post_meta($entity_id, 'governance_status', true);
        $finance = (string) get_post_meta($entity_id, 'finance_status', true);
        $docs_status = (string) get_post_meta($entity_id, 'documentos_status', true);
        $all_steps_done = ($governance === 'aprovado' && $finance === 'adimplente' && $docs_status === 'enviado');

        $docs_reupload_statuses = ['rejeitado', 'negado', 'pendente_reenvio', 'correcao', 'reprovado'];
        $docs_upload_sent = ($docs_status === 'enviado');
        $show_docs_upload = (! $docs_upload_sent) || in_array($docs_status, $docs_reupload_statuses, true);

        $rejected_document_types = get_post_meta($entity_id, 'documentos_reprovados', true);
        $rejected_document_types = is_array($rejected_document_types)
            ? array_values(array_filter(array_map('sanitize_key', $rejected_document_types)))
            : [];

        $docs_to_render = $required_docs;
        $docs_approved_statuses = ['aprovado', 'validado', 'aceito'];
        $docs_approved = in_array($docs_status, $docs_approved_statuses, true);
        $docs_waiting_approval = ($docs_status === 'enviado' && empty($rejected_document_types));

        if ($show_docs_upload && ! empty($rejected_document_types)) {
            $docs_to_render = [];
            foreach ($rejected_document_types as $doc_key) {
                if (isset($required_docs[$doc_key])) {
                    $docs_to_render[$doc_key] = $required_docs[$doc_key];
                }
            }
            if (empty($docs_to_render)) {
                $docs_to_render = $required_docs;
            }
        }

        ob_start();
        ?>
        <?php echo $layout_tune_css; ?>
        <section class="rma-glass-card rma-premium-card" style="margin:20px 0;">
            <h2 class="rma-glass-title">Central de Ativação RMA</h2>
            <p class="rma-glass-subtitle">Finalize as etapas pendentes para liberar seu ambiente exclusivo na plataforma RMA.</p>

            <section class="rma-auth-card" id="rma-auth-card">
                <h3 class="rma-auth-title">Confirmação de Identidade</h3>
                <p class="rma-auth-subtitle">Para sua segurança, valide o código enviado ao seu email institucional.</p>
                <div class="rma-otp-grid" id="rma-otp-grid">
                    <input class="rma-otp-input" inputmode="numeric" maxlength="1" data-otp-index="0" />
                    <input class="rma-otp-input" inputmode="numeric" maxlength="1" data-otp-index="1" />
                    <input class="rma-otp-input" inputmode="numeric" maxlength="1" data-otp-index="2" />
                    <input class="rma-otp-input" inputmode="numeric" maxlength="1" data-otp-index="3" />
                    <input class="rma-otp-input" inputmode="numeric" maxlength="1" data-otp-index="4" />
                    <input class="rma-otp-input" inputmode="numeric" maxlength="1" data-otp-index="5" />
                </div>
                <div class="rma-auth-actions">
                    <button class="rma-button btn-rma-secondary" type="button" id="btnVoltar">Voltar</button>
                    <button class="rma-button btn-rma-primary" type="button" id="rma-validate-code">Validar Código</button>
                </div>
                <a href="#" class="rma-resend-link" id="rma-resend-code" aria-disabled="true">Reenviar código em <span id="rma-resend-timer">60</span>s</a>
            </section>

            <div id="rma-onboarding-main" style="display:none;">
            <div class="rma-flow-stepper" id="rma-flow-stepper">
                <div class="rma-flow-step is-done" data-step="1"><span class="rma-flow-step-icon">✔</span> Autenticação</div>
                <div class="rma-flow-step is-done" data-step="2"><span class="rma-flow-step-icon">✔</span> Dados</div>
                <div class="rma-flow-step is-current" data-step="3"><span class="rma-flow-step-icon">●</span> Documentos</div>
                <div class="rma-flow-step is-locked" data-step="4"><span class="rma-flow-step-icon">○</span> Financeiro</div>
                <div class="rma-flow-step is-locked" data-step="5"><span class="rma-flow-step-icon">○</span> Checkout</div>
            </div>

            <ul style="margin:12px 0 16px 18px;">
                <li><strong>Governança:</strong> <span id="rma-status-governanca"><?php echo esc_html($governance ?: 'pendente'); ?></span></li>
                <li><strong>Documentos:</strong> <span id="rma-status-documentos"><?php echo esc_html($docs_status ?: 'pendente'); ?></span></li>
                <li><strong>Financeiro:</strong> <span id="rma-status-financeiro"><?php echo esc_html($finance ?: 'pendente'); ?></span></li>
            </ul>

            <div style="margin:0 0 14px;">
                <h3 class="rma-premium-section-title">Etapa de documentos (PDF, imagem ou Word)</h3>

                <?php if ($show_docs_upload) : ?>
                <?php if (! empty($rejected_document_types)) : ?>
                    <div class="rma-alert" style="margin:8px 0 10px;">
                        A Equipe RMA identificou ajustes necessários. Apenas os itens abaixo precisam ser reenviados para continuidade da validação.
                    </div>
                <?php endif; ?>

                    <ul class="rma-docs-list rma-modern-dropzone" id="rma-doc-upload-block">
                        <?php foreach ($docs_to_render as $doc_key => $doc_meta) : ?>
                            <li>
                                <label style="display:flex;align-items:center;gap:8px;">
                                    <span><?php echo esc_html($doc_meta['label']); ?></span>
                                    <span title="<?php echo esc_attr($doc_meta['tip']); ?>" style="cursor:help;">ⓘ</span>
                                </label>
                                <div class="rma-drop-item"><label class="rma-dropzone" for="rma-doc-file-<?php echo esc_attr($doc_key); ?>"><span>Arraste e solte ou selecione arquivo</span><input type="file" id="rma-doc-file-<?php echo esc_attr($doc_key); ?>" data-doc-key="<?php echo esc_attr($doc_key); ?>" class="rma-doc-file" accept="application/pdf,image/*,.doc,.docx,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document" /><small class="rma-file-preview" id="rma-preview-<?php echo esc_attr($doc_key); ?>">Nenhum arquivo selecionado</small></label></div>
                                <button class="rma-button rma-doc-upload" type="button" data-doc-key="<?php echo esc_attr($doc_key); ?>">Enviar arquivo</button>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else : ?>
                    <div class="rma-alert" id="rma-doc-upload-block" style="margin-top:8px;">
                        Seus documentos foram recebidos e estão em análise pela Equipe RMA. Você pode avançar para a próxima etapa enquanto realizamos a validação.
                    </div>
                <?php endif; ?>
            </div>

            <div class="rma-primary-cta">
                <button class="rma-button btn-rma-primary" type="button" id="btnPagamento">Avançar para Pagamento</button>
                <span id="rma-primary-hint" style="font-size:.92rem;color:#6b6b6b;"></span>
            </div>

            <div class="rma-nav-actions">
                <button class="rma-button btn-rma-secondary" type="button" id="rma-back-action">Voltar</button>
            </div>

            <div id="rma-flow-feedback" class="rma-feedback"></div>
            </div>
        </section>

        <script>
        (function () {
            var entityId = <?php echo (int) $entity_id; ?>;
            var base = <?php echo wp_json_encode($rest_base); ?>;
            var nonce = <?php echo wp_json_encode($rest_nonce); ?>;
            var feedback = document.getElementById('rma-flow-feedback');
            var primaryAction = document.getElementById('btnPagamento') || document.getElementById('rma-primary-action');
            var primaryHint = document.getElementById('rma-primary-hint');
            var checkoutUrl = <?php echo wp_json_encode($checkout_url); ?>;
            var dashboardUrl = <?php echo wp_json_encode($dashboard_url); ?>;
            var stepper = document.getElementById('rma-flow-stepper');
            var backAction = document.getElementById('rma-back-action');
            var authBackButton = document.getElementById('btnVoltar');
            var onboardingMain = document.getElementById('rma-onboarding-main');
            var currentUiStep = 1;
            var isOtpVerified = false;

            function revealMainFlow() {
                var card = document.getElementById('rma-auth-card');
                if (card) card.style.display = 'none';
                if (onboardingMain) onboardingMain.style.display = 'block';
            }

            function sendOtpCode(initial) {
                return fetch(base + '/otp/send', { method: 'POST', credentials: 'same-origin', headers: { 'X-WP-Nonce': nonce } })
                    .then(function (res) { return res.json().then(function (json) { return {ok: res.ok, json: json}; }); })
                    .then(function (result) {
                        if (!result.ok) {
                            showFeedback((result.json && result.json.message) ? result.json.message : 'Não foi possível enviar o código no momento. Tente novamente ou verifique sua caixa de spam.', false);
                            return false;
                        }
                        if (!initial) showFeedback('Novo código enviado para seu email.', true);
                        return true;
                    })
                    .catch(function () {
                        showFeedback('Não foi possível enviar o código no momento. Tente novamente ou verifique sua caixa de spam.', false);
                        return false;
                    });
            }

            var paymentListenerBound = false;
            function bindPaymentButton() {
                if (!primaryAction || paymentListenerBound) return;
                primaryAction.addEventListener('click', function () {
                    if (primaryAction.getAttribute('data-rma-pay') === '1') {
                        window.location.href = checkoutUrl;
                    }
                });
                paymentListenerBound = true;
            }
            bindPaymentButton();

            if (authBackButton) {
                authBackButton.addEventListener('click', function () {
                    window.history.back();
                });
            }

            function showFeedback(message, ok) {
                if (!feedback) return;
                feedback.innerHTML = '<div style="padding:10px;border-radius:10px;background:' + (ok ? '#edf9ec' : '#fdecec') + ';">' + message + '</div>';
            }


            function initDropzones(selector) {
                document.querySelectorAll(selector).forEach(function (input) {
                    var zone = input.closest('.rma-dropzone');
                    if (!zone || zone.getAttribute('data-drop-init') === '1') return;
                    zone.setAttribute('data-drop-init', '1');
                    var preview = zone.querySelector('.rma-file-preview');
                    function update() {
                        var file = input.files && input.files[0] ? input.files[0] : null;
                        if (preview) preview.textContent = file ? (file.name + ' • ' + Math.ceil(file.size / 1024) + ' KB') : 'Nenhum arquivo selecionado';
                    }
                    input.addEventListener('change', update);
                    zone.addEventListener('dragover', function (event) { event.preventDefault(); zone.classList.add('is-drag'); });
                    zone.addEventListener('dragleave', function () { zone.classList.remove('is-drag'); });
                    zone.addEventListener('drop', function (event) {
                        event.preventDefault();
                        zone.classList.remove('is-drag');
                        if (event.dataTransfer && event.dataTransfer.files && event.dataTransfer.files[0]) {
                            input.files = event.dataTransfer.files;
                            update();
                        }
                    });
                });
            }

            function applyStepper(currentStep) {
                if (!stepper) return;
                currentUiStep = currentStep;
                stepper.querySelectorAll('.rma-flow-step').forEach(function (item) {
                    var n = parseInt(item.getAttribute('data-step') || '0', 10);
                    var icon = item.querySelector('.rma-flow-step-icon');
                    item.classList.remove('is-done', 'is-current', 'is-locked');
                    if (n < currentStep) {
                        item.classList.add('is-done');
                        if (icon) icon.textContent = '✔';
                    } else if (n === currentStep) {
                        item.classList.add('is-current');
                        if (icon) icon.textContent = '●';
                    } else {
                        item.classList.add('is-locked');
                        if (icon) icon.textContent = '○';
                    }
                });

            if (backAction) {
                    backAction.disabled = currentStep <= 1;
                }
            }

            function applyPrimaryAction(payload) {
                if (!primaryAction) return;
                if (!isOtpVerified) {
                    primaryAction.disabled = true;
                    primaryAction.textContent = 'Valide o código para continuar';
                    primaryAction.removeAttribute('data-rma-pay');
                    if (primaryHint) primaryHint.textContent = 'Confirme o código enviado para seu email para liberar as próximas etapas.';
                    return;
                }
                var docs = payload.documentos_status || 'pendente';
                var finance = payload.finance_status || 'pendente';
                var governance = payload.governance_status || 'pendente';
                var rejected = Array.isArray(payload.rejected_document_types) ? payload.rejected_document_types : [];
                var docsApproved = ['aprovado', 'validado', 'aceito'].indexOf(docs) !== -1;
                var docsWaiting = docs === 'enviado' && rejected.length === 0;
                var docsNeedUpload = (docs !== 'enviado' && !docsApproved) || rejected.length > 0;

                primaryAction.disabled = false;
                primaryAction.textContent = 'Continuar';
                primaryAction.removeAttribute('data-rma-pay');
                if (primaryHint) primaryHint.textContent = '';

                if (docsNeedUpload) {
                    applyStepper(3);
                    primaryAction.textContent = 'Reenviar Documentos';
                    primaryAction.onclick = function () {
                        var el = document.getElementById('rma-doc-upload-block');
                        if (el) el.scrollIntoView({behavior: 'smooth', block: 'start'});
                    };
                    if (primaryHint) primaryHint.textContent = 'Identificamos ajustes necessários na documentação enviada. Revise as orientações e realize o reenvio para continuidade do processo.';
                    return;
                }

                if (docsWaiting) {
                    applyStepper(4);
                    primaryAction.textContent = 'Avançar para Pagamento';
                    primaryAction.setAttribute('data-rma-pay', '1');
                    if (primaryHint) primaryHint.textContent = 'Seus documentos foram recebidos e estão em análise pela Equipe RMA. Você pode avançar para a próxima etapa enquanto realizamos a validação.';
                    return;
                }

                if (docsApproved && finance !== 'adimplente') {
                    applyStepper(4);
                    primaryAction.textContent = 'Avançar para Pagamento';
                    primaryAction.id = 'btnPagamento';
                    primaryAction.setAttribute('data-rma-pay', '1');
                    if (primaryHint) primaryHint.textContent = 'Próxima etapa: concluir pagamento no checkout.';
                    return;
                }

                if (docsApproved && finance === 'adimplente' && governance === 'aprovado') {
                    applyStepper(5);
                    primaryAction.textContent = 'Finalizando...';
                    primaryAction.disabled = true;
                    if (primaryHint) primaryHint.textContent = 'Tudo concluído. Redirecionando ao dashboard...';
                    setTimeout(function () { window.location.replace(dashboardUrl); }, 900);
                    return;
                }

                applyStepper(1);
                primaryAction.textContent = 'Aguardando Status';
                primaryAction.disabled = true;
                if (primaryHint) primaryHint.textContent = 'Acompanhe a etapa de status/governança.';
            }

            function updateState(payload) {
                var g = document.getElementById('rma-status-governanca');
                var d = document.getElementById('rma-status-documentos');
                var f = document.getElementById('rma-status-financeiro');
                if (g) g.textContent = payload.governance_status || 'pendente';
                if (d) d.textContent = payload.documentos_status || 'pendente';
                if (f) f.textContent = payload.finance_status || 'pendente';
                applyPrimaryAction(payload || {});
            }

            function refreshStatus() {
                fetch(base + '/entities/' + entityId + '/status', {
                    credentials: 'same-origin',
                    headers: { 'X-WP-Nonce': nonce }
                })
                .then(function (res) { return res.json().then(function (json) { return {ok: res.ok, json: json}; }); })
                .then(function (result) {
                    if (!result.ok) {
                        showFeedback((result.json && result.json.message) ? result.json.message : 'Falha ao atualizar status automaticamente.', false);
                        return;
                    }
                    updateState(result.json || {});
                })
                .catch(function () {
                    showFeedback('Erro de conexão ao atualizar status automaticamente.', false);
                });
            }

            function uploadDoc(docKey, file) {
                if (!file) {
                    showFeedback('Selecione um arquivo antes de enviar.', false);
                    return;
                }

                var data = new FormData();
                data.append('document_type', docKey);
                data.append('file', file);

                showFeedback('Enviando ' + docKey + '...', true);
                fetch(base + '/entities/' + entityId + '/documents', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'X-WP-Nonce': nonce },
                    body: data
                })
                .then(function (res) { return res.json().then(function (json) { return {ok: res.ok, json: json}; }); })
                .then(function (result) {
                    if (!result.ok) {
                        showFeedback((result.json && result.json.message) ? result.json.message : 'Falha no upload do documento.', false);
                        return;
                    }
                    showFeedback('Documento enviado com sucesso (' + docKey + ').', true);
                    refreshStatus();
                })
                .catch(function () {
                    showFeedback('Erro de conexão no upload.', false);
                });
            }

            initDropzones('.rma-doc-file');

            if (backAction) {
                backAction.addEventListener('click', function () {
                    if (currentUiStep <= 1) {
                        return;
                    }

                    if (currentUiStep <= 2) {
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                        return;
                    }

                    if (document.referrer) {
                        window.history.back();
                        return;
                    }

                    var uploadBlock = document.getElementById('rma-doc-upload-block');
                    if (uploadBlock) uploadBlock.scrollIntoView({ behavior: 'smooth', block: 'start' });
                });
            }


            var otpInputs = Array.prototype.slice.call(document.querySelectorAll('.rma-otp-input'));
            var validateCodeButton = document.getElementById('rma-validate-code');
            var resendCodeLink = document.getElementById('rma-resend-code');
            var resendTimerElement = document.getElementById('rma-resend-timer');
            var resendSeconds = 60;

            function setOtpState(state) {
                otpInputs.forEach(function (input) {
                    input.classList.remove('is-error', 'is-success');
                    if (state) input.classList.add(state);
                });
            }

            function otpCode() {
                return otpInputs.map(function (input) { return (input.value || '').trim(); }).join('');
            }

            otpInputs.forEach(function (input, index) {
                input.addEventListener('input', function () {
                    input.value = (input.value || '').replace(/\D+/g, '').slice(0, 1);
                    if (input.value && otpInputs[index + 1]) otpInputs[index + 1].focus();
                });
                input.addEventListener('keydown', function (event) {
                    if (event.key === 'Backspace' && !input.value && otpInputs[index - 1]) {
                        otpInputs[index - 1].focus();
                    }
                });
                input.addEventListener('paste', function (event) {
                    var text = (event.clipboardData || window.clipboardData).getData('text') || '';
                    var digits = text.replace(/\D+/g, '').slice(0, 6).split('');
                    if (!digits.length) return;
                    event.preventDefault();
                    otpInputs.forEach(function (el, i) { el.value = digits[i] || ''; });
                    var next = otpInputs[Math.min(digits.length, 5)];
                    if (next) next.focus();
                });
            });

            if (validateCodeButton) {
                validateCodeButton.addEventListener('click', function () {
                    if (otpCode().length !== 6) {
                        setOtpState('is-error');
                        showFeedback('Código inválido. Digite os 6 dígitos enviados por email.', false);
                        return;
                    }

                    fetch(base + '/otp/verify', {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': nonce },
                        body: JSON.stringify({ code: otpCode() })
                    })
                    .then(function (res) { return res.json().then(function (json) { return {ok: res.ok, json: json}; }); })
                    .then(function (result) {
                        if (!result.ok || !result.json || !result.json.verified) {
                            setOtpState('is-error');
                            showFeedback((result.json && result.json.message) ? result.json.message : 'Código inválido. Digite os 6 dígitos enviados por email.', false);
                            return;
                        }

                        isOtpVerified = true;
                        setOtpState('is-success');
                    var card = document.getElementById('rma-auth-card');
                    if (card) card.style.display = 'none';
                    if (onboardingMain) onboardingMain.style.display = 'block';
                        showFeedback('Verificação confirmada. Fluxo liberado.', true);
                        refreshStatus();
                    })
                    .catch(function () {
                        setOtpState('is-error');
                        showFeedback('Não foi possível validar o código no momento. Tente novamente.', false);
                    });
                });
            }

            var resendTimerInterval = setInterval(function () {
                resendSeconds = Math.max(0, resendSeconds - 1);
                if (resendTimerElement) resendTimerElement.textContent = String(resendSeconds);
                if (resendSeconds === 0 && resendCodeLink) {
                    resendCodeLink.setAttribute('aria-disabled', 'false');
                    resendCodeLink.textContent = 'Reenviar código';
                    clearInterval(resendTimerInterval);
                }
            }, 1000);

            if (resendCodeLink) {
                resendCodeLink.addEventListener('click', function (event) {
                    if (resendCodeLink.getAttribute('aria-disabled') === 'true') {
                        event.preventDefault();
                        return;
                    }
                    event.preventDefault();
                    resendSeconds = 60;
                    resendCodeLink.setAttribute('aria-disabled', 'true');
                    resendCodeLink.innerHTML = 'Reenviar código em <span id="rma-resend-timer">60</span>s';
                    resendTimerElement = document.getElementById('rma-resend-timer');
sendOtpCode(false);
                    resendTimerInterval = setInterval(function () {
                        resendSeconds = Math.max(0, resendSeconds - 1);
                        if (resendTimerElement) resendTimerElement.textContent = String(resendSeconds);
                        if (resendSeconds === 0) {
                            resendCodeLink.setAttribute('aria-disabled', 'false');
                            resendCodeLink.textContent = 'Reenviar código';
                            clearInterval(resendTimerInterval);
                        }
                    }, 1000);
                });
            }


            if (onboardingMain) onboardingMain.style.display = 'none';

            fetch(base + '/otp/status', { credentials: 'same-origin', headers: { 'X-WP-Nonce': nonce } })
                .then(function (res) { return res.json().then(function (json) { return {ok: res.ok, json: json}; }); })
                .then(function (result) {
                    if (result.ok && result.json && result.json.verified) {
                        isOtpVerified = true;
                        revealMainFlow();
                        return;
                    }
                    sendOtpCode(true).then(function (sent) {
                        if (sent) showFeedback('Código enviado para seu email institucional.', true);
                    });
                })
                .catch(function () {
                    sendOtpCode(true).then(function (sent) {
                        if (sent) showFeedback('Código enviado para seu email institucional.', true);
                    });
                });

            updateState({
                governance_status: <?php echo wp_json_encode($governance); ?>,
                documentos_status: <?php echo wp_json_encode($docs_status); ?>,
                finance_status: <?php echo wp_json_encode($finance); ?>,
                rejected_document_types: <?php echo wp_json_encode($rejected_document_types); ?>
            });

            document.querySelectorAll('.rma-doc-upload').forEach(function (button) {
                button.addEventListener('click', function () {
                    var key = button.getAttribute('data-doc-key');
                    var fileInput = document.querySelector('.rma-doc-file[data-doc-key="' + key + '"]');
                    uploadDoc(key, fileInput && fileInput.files ? fileInput.files[0] : null);
                });
            });

            setInterval(function () {
                fetch(base + '/onboarding/status', { credentials: 'same-origin', headers: { 'X-WP-Nonce': nonce } })
                    .then(function (res) { return res.ok ? res.json() : Promise.reject(); })
                    .then(function (data) { updateState(data || {}); })
                    .catch(function () { refreshStatus(); });
            }, 10000);
        })();
        </script>
        <?php
        return (string) ob_get_clean();
    }

    ob_start();
    ?>
    <?php echo $layout_tune_css; ?>
    <section class="rma-glass-card rma-premium-card rma-premium-card--setup" style="margin:20px 0;">
        <span class="rma-badge">RMA • Conta da Entidade</span>
        <h2 class="rma-glass-title">Complete seu cadastro institucional</h2>
        <p class="rma-glass-subtitle">Valide o CNPJ, confirme dados e envie para análise.</p>

        <form id="rma-conta-setup-form" class="rma-premium-form">
            <div class="rma-cnpj-row">
                <input type="text" id="rma-cnpj" placeholder="CNPJ" required />
                <button class="rma-button rma-button--ghost" type="button" id="rma-buscar-cnpj">Buscar CNPJ</button>
            </div>

            <div class="rma-grid-2">
                <input type="text" id="rma-razao-social" placeholder="Razão social" required />
                <input type="text" id="rma-nome-fantasia" placeholder="Nome fantasia" />
            </div>

            <div class="rma-grid-2">
                <input type="email" id="rma-email" placeholder="E-mail de contato" required />
                <input type="text" id="rma-representante" placeholder="Nome do representante legal" />
            </div>

            <div class="rma-grid-2">
                <input type="text" id="rma-endereco" placeholder="Endereço" />
                <input type="text" id="rma-bairro" placeholder="Bairro" />
            </div>

            <div class="rma-grid-3">
                <input type="text" id="rma-cidade" placeholder="Cidade" />
                <input type="text" id="rma-uf" placeholder="UF" maxlength="2" />
                <input type="text" id="rma-cep" placeholder="CEP" />
            </div>

            <div class="rma-grid-2">
                <div class="rma-phone-row"><select id="rma-phone-country"><option value="55">🇧🇷 +55</option><option value="1">🇺🇸 +1</option><option value="351">🇵🇹 +351</option><option value="34">🇪🇸 +34</option></select><input type="tel" id="rma-telefone" placeholder="(11) 99999-9999" /></div>
                <div>
                    <label for="rma-data-fundacao" class="rma-field-label">Data de Criação da Entidade</label>
                    <input type="date" id="rma-data-fundacao" />
                </div>
            </div>

            <textarea id="rma-atividades" placeholder="Resumo de atividades (últimos 2 anos)" rows="4"></textarea>

            <div class="rma-docs-block">
                <p class="rma-premium-section-title"><strong>Documentos obrigatórios (PDF, imagem ou Word)</strong></p>
                <ul class="rma-docs-list">
                    <?php foreach ($required_docs as $doc_key => $doc_meta) : ?>
                        <li>
                            <label style="display:flex;align-items:center;gap:8px;">
                                <span><?php echo esc_html($doc_meta['label']); ?></span>
                                <span title="<?php echo esc_attr($doc_meta['tip']); ?>" style="cursor:help;">ⓘ</span>
                            </label>
                            <div class="rma-drop-item"><label class="rma-dropzone" for="rma-pre-doc-file-<?php echo esc_attr($doc_key); ?>"><span>Arraste e solte ou selecione arquivo</span><input type="file" id="rma-pre-doc-file-<?php echo esc_attr($doc_key); ?>" class="rma-pre-doc-file" data-doc-key="<?php echo esc_attr($doc_key); ?>" accept="application/pdf,image/*,.doc,.docx,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document" /><small class="rma-file-preview" id="rma-pre-preview-<?php echo esc_attr($doc_key); ?>">Nenhum arquivo selecionado</small></label></div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <label><input type="checkbox" id="rma-consent-lgpd" required /> Concordo com LGPD</label>

            <div class="rma-actions rma-actions--left">
                <button class="rma-button" type="submit">Salvar entidade</button>
            </div>
        </form>

        <div id="rma-feedback" class="rma-feedback"></div>
    </section>

    <script>
    (function () {
        var base = <?php echo wp_json_encode($rest_base); ?>;
        var nonce = <?php echo wp_json_encode($rest_nonce); ?>;
        var contaUrl = <?php echo wp_json_encode(rma_account_setup_url()); ?>;

        var form = document.getElementById('rma-conta-setup-form');
        if (!form) return;

        var feedback = document.getElementById('rma-feedback');
        var fields = {
            cnpj: document.getElementById('rma-cnpj'),
            razao_social: document.getElementById('rma-razao-social'),
            nome_fantasia: document.getElementById('rma-nome-fantasia'),
            email_contato: document.getElementById('rma-email'),
            representante: document.getElementById('rma-representante'),
            endereco: document.getElementById('rma-endereco'),
            bairro: document.getElementById('rma-bairro'),
            cidade: document.getElementById('rma-cidade'),
            uf: document.getElementById('rma-uf'),
            cep: document.getElementById('rma-cep'),
            phone_country: document.getElementById('rma-phone-country'),
            telefone_contato: document.getElementById('rma-telefone'),
            data_fundacao: document.getElementById('rma-data-fundacao'),
            atividades: document.getElementById('rma-atividades'),
            consent_lgpd: document.getElementById('rma-consent-lgpd')
        };

        if (fields.telefone_contato) {
            fields.telefone_contato.addEventListener('input', function () {
                var country = fields.phone_country ? fields.phone_country.value : '55';
                var digits = normalizePhone(fields.telefone_contato.value);
                fields.telefone_contato.value = formatPhoneByCountry(country, digits);
            });
        }

        initDropzones('.rma-pre-doc-file');

        function cleanCnpj(value) { return (value || '').replace(/\D+/g, ''); }
        function showMessage(message, ok) {
            if (!feedback) return;
            feedback.innerHTML = '<div style="padding:10px;border-radius:10px;background:' + (ok ? '#edf9ec' : '#fdecec') + ';">' + message + '</div>';
        }

        function normalizePhone(raw) {
            return (raw || '').replace(/\D+/g, '');
        }

        function formatPhoneByCountry(country, digits) {
            if (country === '55') {
                if (digits.length >= 11) return '(' + digits.slice(0,2) + ') ' + digits.slice(2,7) + '-' + digits.slice(7,11);
                if (digits.length >= 10) return '(' + digits.slice(0,2) + ') ' + digits.slice(2,6) + '-' + digits.slice(6,10);
            }
            return digits;
        }

        function initDropzones(selector) {
            document.querySelectorAll(selector).forEach(function (input) {
                var zone = input.closest('.rma-dropzone');
                if (!zone) return;
                var preview = zone.querySelector('.rma-file-preview');
                function update() {
                    var file = input.files && input.files[0] ? input.files[0] : null;
                    if (preview) preview.textContent = file ? (file.name + ' • ' + Math.ceil(file.size / 1024) + ' KB') : 'Nenhum arquivo selecionado';
                }
                input.addEventListener('change', update);
                zone.addEventListener('dragover', function (event) { event.preventDefault(); zone.classList.add('is-drag'); });
                zone.addEventListener('dragleave', function () { zone.classList.remove('is-drag'); });
                zone.addEventListener('drop', function (event) {
                    event.preventDefault();
                    zone.classList.remove('is-drag');
                    if (event.dataTransfer && event.dataTransfer.files && event.dataTransfer.files[0]) {
                        input.files = event.dataTransfer.files;
                        update();
                    }
                });
            });
        }

        function collectPreSelectedDocs() {
            var items = [];
            document.querySelectorAll('.rma-pre-doc-file').forEach(function (input) {
                if (input.files && input.files[0]) {
                    items.push({ key: input.getAttribute('data-doc-key'), file: input.files[0] });
                }
            });
            return items;
        }

        function uploadPreDocs(entityId, docs) {
            if (!docs.length) {
                return Promise.resolve();
            }

            var chain = Promise.resolve();
            docs.forEach(function (doc) {
                chain = chain.then(function () {
                    var data = new FormData();
                    data.append('document_type', doc.key);
                    data.append('file', doc.file);
                    showMessage('Enviando documento: ' + doc.key + '...', true);
                    return fetch(base + '/entities/' + entityId + '/documents', {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: { 'X-WP-Nonce': nonce },
                        body: data
                    })
                    .then(function (res) { return res.json().then(function (json) { return {ok: res.ok, json: json}; }); })
                    .then(function (result) {
                        if (!result.ok) {
                            throw new Error((result.json && result.json.message) ? result.json.message : 'Falha no upload de ' + doc.key);
                        }
                    });
                });
            });
            return chain;
        }

        document.getElementById('rma-buscar-cnpj').addEventListener('click', function () {
            var cnpj = cleanCnpj(fields.cnpj.value);
            if (!cnpj) {
                showMessage('Informe um CNPJ válido.', false);
                return;
            }

            showMessage('Consultando CNPJ...', true);
            fetch(base + '/cnpj/' + encodeURIComponent(cnpj), {
                credentials: 'same-origin',
                headers: { 'X-WP-Nonce': nonce }
            })
            .then(function (res) { return res.json().then(function (json) { return {ok: res.ok, json: json}; }); })
            .then(function (result) {
                if (!result.ok) {
                    showMessage((result.json && result.json.message) ? result.json.message : 'Não foi possível consultar o CNPJ.', false);
                    return;
                }
                fields.razao_social.value = result.json.razao_social || fields.razao_social.value;
                fields.nome_fantasia.value = result.json.nome_fantasia || fields.nome_fantasia.value;
                fields.cidade.value = result.json.cidade || fields.cidade.value;
                fields.uf.value = (result.json.uf || fields.uf.value || '').toUpperCase();
                fields.endereco.value = result.json.logradouro || fields.endereco.value;
                fields.bairro.value = result.json.bairro || fields.bairro.value;
                fields.cep.value = result.json.cep || fields.cep.value;
                showMessage('CNPJ validado e dados preenchidos.', true);
            })
            .catch(function () {
                showMessage('Erro de conexão ao consultar CNPJ.', false);
            });
        });

        form.addEventListener('submit', function (event) {
            event.preventDefault();

            if (!fields.consent_lgpd.checked) {
                showMessage('É obrigatório aceitar LGPD para continuar.', false);
                return;
            }

            var docs = collectPreSelectedDocs();
            var payload = {
                cnpj: cleanCnpj(fields.cnpj.value),
                razao_social: (fields.razao_social.value || '').trim(),
                nome_fantasia: (fields.nome_fantasia.value || '').trim(),
                email_contato: (fields.email_contato.value || '').trim(),
                telefone_contato: '+' + (fields.phone_country ? fields.phone_country.value : '55') + ' ' + (fields.telefone_contato.value || '').trim(),
                cidade: (fields.cidade.value || '').trim(),
                uf: (fields.uf.value || '').trim().toUpperCase(),
                endereco: (fields.endereco.value || '').trim(),
                bairro: (fields.bairro.value || '').trim(),
                cep: (fields.cep.value || '').trim(),
                consent_lgpd: true,
                observacoes: [
                    'Representante legal: ' + (fields.representante.value || '').trim(),
                    'DDI telefone: +' + (fields.phone_country ? fields.phone_country.value : '55'),
                    'Telefone limpo: ' + normalizePhone(fields.telefone_contato.value || ''),
                    'Data de fundação: ' + (fields.data_fundacao.value || '').trim(),
                    'Atividades: ' + (fields.atividades.value || '').trim()
                ].join(' | ')
            };

            showMessage('Salvando entidade...', true);

            fetch(base + '/entities', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': nonce
                },
                body: JSON.stringify(payload)
            })
            .then(function (res) { return res.json().then(function (json) { return {ok: res.ok, json: json}; }); })
            .then(function (result) {
                if (!result.ok) {
                    showMessage((result.json && result.json.message) ? result.json.message : 'Falha ao salvar entidade.', false);
                    return Promise.reject(new Error('create-failed'));
                }

                var entityId = result.json && (result.json.id || result.json.post_id) ? (result.json.id || result.json.post_id) : 0;
                if (!entityId) {
                    showMessage('Entidade criada, mas não foi possível identificar o ID (id/post_id) para upload dos documentos.', false);
                    return Promise.reject(new Error('missing-id'));
                }

                return uploadPreDocs(entityId, docs).then(function () {
                    showMessage('Entidade criada e documentos enviados. Redirecionando para checkout...', true);
                    setTimeout(function () {
                        window.location.replace(<?php echo wp_json_encode($checkout_url); ?>);
                    }, 900);
                });
            })
            .catch(function (error) {
                if (error && (error.message === 'create-failed' || error.message === 'missing-id')) {
                    return;
                }
                showMessage(error && error.message ? error.message : 'Erro de conexão ao salvar entidade.', false);
            });
        });
    })();
    </script>
    <?php

    return (string) ob_get_clean();
});

add_action('template_redirect', function () {
    if (is_admin() || wp_doing_ajax() || wp_doing_cron() || (defined('REST_REQUEST') && REST_REQUEST)) {
        return;
    }

    if (! is_user_logged_in()) {
        return;
    }

    $request_uri  = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';
    $request_path = wp_parse_url($request_uri, PHP_URL_PATH);
    $request_path = is_string($request_path) ? untrailingslashit($request_path) : '';

    if ($request_path === '') {
        return;
    }

    $account_path = rma_account_setup_path();
    if ($account_path !== '' && $request_path === $account_path) {
        return;
    }

    foreach (['/login', '/register', '/wp-login.php'] as $safe_suffix) {
        if (substr($request_path, -strlen($safe_suffix)) === $safe_suffix) {
            return;
        }
    }

    $entity_id = rma_get_entity_id_by_author(get_current_user_id());
    if ($entity_id <= 0) {
        rma_flow_debug_log('redirect_no_entity', ['to' => rma_account_setup_url()]);
        wp_safe_redirect(rma_account_setup_url());
        exit;
    }

    $governance = (string) get_post_meta($entity_id, 'governance_status', true);
    $finance = (string) get_post_meta($entity_id, 'finance_status', true);
    $docs_status = (string) get_post_meta($entity_id, 'documentos_status', true);

    $all_steps_done = ($governance === 'aprovado' && $finance === 'adimplente' && $docs_status === 'enviado');
    if ($all_steps_done) {
        return;
    }

    $allowed_paths = array_filter(array_map('untrailingslashit', [
        rma_account_setup_path(),
        (string) wp_parse_url(home_url('/documentos/'), PHP_URL_PATH),
        (string) wp_parse_url(home_url('/financeiro/'), PHP_URL_PATH),
        (string) wp_parse_url(home_url('/status/'), PHP_URL_PATH),
    ]));

    foreach ($allowed_paths as $allowed_path) {
        if ($allowed_path !== '' && $request_path === $allowed_path) {
            return;
        }
    }

    rma_flow_debug_log('redirect_incomplete_flow', [
        'to' => rma_account_setup_url(),
        'entity_id' => $entity_id,
        'governance' => $governance,
        'finance' => $finance,
        'docs_status' => $docs_status,
    ]);
    wp_safe_redirect(rma_account_setup_url());
    exit;
}, 20);
