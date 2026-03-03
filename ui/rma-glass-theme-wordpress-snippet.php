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
    $checkout_url = apply_filters('rma_checkout_url', home_url('/checkout/'));

    $layout_tune_css = '<style id="rma-layout-tune">
'
        . '.rma-premium-card,.rma-premium-card--setup{max-width:1060px;margin-left:auto;margin-right:auto;}
'
        . '.rma-premium-form{display:grid;gap:14px;}
'
        . '.rma-grid-2{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;}
'
        . '.rma-grid-3{display:grid;grid-template-columns:1.2fr .6fr .8fr;gap:12px;}
'
        . '.rma-field-label{display:block;font-size:.9rem;color:#6b6b6b;margin:0 0 6px;}\n'
        . '.rma-flow-stepper{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;margin:10px 0 16px;}\n'
        . '.rma-flow-step{border:1px solid #dfe5f3;background:#fff;border-radius:12px;padding:10px 12px;font-size:.83rem;color:#6b6b6b;text-align:center;display:flex;align-items:center;justify-content:center;gap:7px;}\n'
        . '.rma-flow-step-icon{font-size:.9rem;line-height:1;}\n'
        . '.rma-flow-step.is-done{border-color:#9fd38a;color:#2f7d32;background:#f4fbef;}\n'
        . '.rma-flow-step.is-current{border-color:#7bad39;color:#fff;background:linear-gradient(135deg,#7bad39,#5ddabb);font-weight:700;box-shadow:0 8px 16px rgba(93,218,187,.26);}\n'
        . '.rma-flow-step.is-locked{opacity:.8;background:#f2f4f7;color:#8d97a5;}\n'
        . '.rma-primary-cta{margin-top:12px;display:flex;gap:10px;align-items:center;flex-wrap:wrap;}\n'
        . '.rma-primary-cta .rma-button{min-width:240px;justify-content:center;}\n'
        . '.rma-nav-actions{margin-top:8px;display:flex;gap:10px;flex-wrap:wrap;}\n'
        . '.btn-rma-primary{background:linear-gradient(135deg,#7bad39,#5ddabb)!important;border:none!important;color:#fff!important;font-weight:600;padding:12px 24px;border-radius:12px;transition:all .3s ease;box-shadow:0 4px 12px rgba(0,0,0,.08);}\n'
        . '.btn-rma-primary:hover{transform:translateY(-2px);filter:brightness(1.05);box-shadow:0 8px 18px rgba(0,0,0,.12);}\n'
        . '.btn-rma-secondary{background:#fff!important;border:1px solid #7bad39!important;color:#7bad39!important;font-weight:600;padding:12px 24px;border-radius:12px;transition:all .3s ease;}\n'
        . '.btn-rma-secondary:hover{transform:translateY(-2px);box-shadow:0 6px 14px rgba(123,173,57,.16);}\n'
        . '@media(max-width:860px){.rma-grid-2,.rma-grid-3,.rma-flow-stepper{grid-template-columns:1fr;}}\n'
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
            <span class="rma-badge">RMA • Próximos passos</span>
            <h2 class="rma-glass-title">Fluxo de conclusão da filiação</h2>
            <p class="rma-glass-subtitle">Você está na etapa final do seu processo de filiação. Avance para concluir seu cadastro e ativar seu acesso completo à plataforma.</p>

            <div class="rma-flow-stepper" id="rma-flow-stepper">
                <div class="rma-flow-step is-done" data-step="1"><span class="rma-flow-step-icon">✔</span> Status</div>
                <div class="rma-flow-step is-done" data-step="2"><span class="rma-flow-step-icon">✔</span> Documentos</div>
                <div class="rma-flow-step is-current" data-step="3"><span class="rma-flow-step-icon">●</span> Financeiro</div>
                <div class="rma-flow-step is-locked" data-step="4"><span class="rma-flow-step-icon">🔒</span> Checkout</div>
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

                    <ul class="rma-docs-list" id="rma-doc-upload-block">
                        <?php foreach ($docs_to_render as $doc_key => $doc_meta) : ?>
                            <li>
                                <label style="display:flex;align-items:center;gap:8px;">
                                    <span><?php echo esc_html($doc_meta['label']); ?></span>
                                    <span title="<?php echo esc_attr($doc_meta['tip']); ?>" style="cursor:help;">ⓘ</span>
                                </label>
                                <input type="file" data-doc-key="<?php echo esc_attr($doc_key); ?>" class="rma-doc-file" accept="application/pdf,image/*,.doc,.docx,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document" />
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
                <button class="rma-button btn-rma-primary" type="button" id="rma-primary-action">Avançar para Pagamento</button>
                <span id="rma-primary-hint" style="font-size:.92rem;color:#6b6b6b;"></span>
            </div>

            <div class="rma-nav-actions">
                <button class="rma-button btn-rma-secondary" type="button" id="rma-back-action">Voltar</button>
            </div>

            <div id="rma-flow-feedback" class="rma-feedback"></div>
        </section>

        <script>
        (function () {
            var entityId = <?php echo (int) $entity_id; ?>;
            var base = <?php echo wp_json_encode($rest_base); ?>;
            var nonce = <?php echo wp_json_encode($rest_nonce); ?>;
            var feedback = document.getElementById('rma-flow-feedback');
            var primaryAction = document.getElementById('rma-primary-action');
            var primaryHint = document.getElementById('rma-primary-hint');
            var checkoutUrl = <?php echo wp_json_encode($checkout_url); ?>;
            var dashboardUrl = <?php echo wp_json_encode($dashboard_url); ?>;
            var stepper = document.getElementById('rma-flow-stepper');
            var backAction = document.getElementById('rma-back-action');
            var currentUiStep = 1;

            function showFeedback(message, ok) {
                if (!feedback) return;
                feedback.innerHTML = '<div style="padding:10px;border-radius:10px;background:' + (ok ? '#edf9ec' : '#fdecec') + ';">' + message + '</div>';
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
                        if (icon) icon.textContent = '🔒';
                    }
                });

                if (backAction) {
                    backAction.disabled = currentStep <= 1;
                }
            }

            function applyPrimaryAction(payload) {
                if (!primaryAction) return;
                var docs = payload.documentos_status || 'pendente';
                var finance = payload.finance_status || 'pendente';
                var governance = payload.governance_status || 'pendente';
                var rejected = Array.isArray(payload.rejected_document_types) ? payload.rejected_document_types : [];
                var docsApproved = ['aprovado', 'validado', 'aceito'].indexOf(docs) !== -1;
                var docsWaiting = docs === 'enviado' && rejected.length === 0;
                var docsNeedUpload = (docs !== 'enviado' && !docsApproved) || rejected.length > 0;

                primaryAction.disabled = false;
                primaryAction.textContent = 'Continuar';
                primaryAction.onclick = null;
                if (primaryHint) primaryHint.textContent = '';

                if (docsNeedUpload) {
                    applyStepper(2);
                    primaryAction.textContent = 'Reenviar Documentos';
                    primaryAction.onclick = function () {
                        var el = document.getElementById('rma-doc-upload-block');
                        if (el) el.scrollIntoView({behavior: 'smooth', block: 'start'});
                    };
                    if (primaryHint) primaryHint.textContent = 'Identificamos ajustes necessários na documentação enviada. Revise as orientações e realize o reenvio para continuidade do processo.';
                    return;
                }

                if (docsWaiting) {
                    applyStepper(3);
                    primaryAction.textContent = 'Avançar para Pagamento';
                    primaryAction.onclick = function () { window.location.href = checkoutUrl; };
                    if (primaryHint) primaryHint.textContent = 'Seus documentos foram recebidos e estão em análise pela Equipe RMA. Você pode avançar para a próxima etapa enquanto realizamos a validação.';
                    return;
                }

                if (docsApproved && finance !== 'adimplente') {
                    applyStepper(3);
                    primaryAction.textContent = 'Concluir Pagamento';
                    primaryAction.onclick = function () { window.location.href = checkoutUrl; };
                    if (primaryHint) primaryHint.textContent = 'Próxima etapa: concluir pagamento no checkout.';
                    return;
                }

                if (docsApproved && finance === 'adimplente' && governance === 'aprovado') {
                    applyStepper(4);
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
                refreshStatus();
            }, 15000);
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
                <input type="text" id="rma-telefone" placeholder="Telefone principal" />
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
                            <input type="file" class="rma-pre-doc-file" data-doc-key="<?php echo esc_attr($doc_key); ?>" accept="application/pdf,image/*,.doc,.docx,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document" />
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
            telefone_contato: document.getElementById('rma-telefone'),
            data_fundacao: document.getElementById('rma-data-fundacao'),
            atividades: document.getElementById('rma-atividades'),
            consent_lgpd: document.getElementById('rma-consent-lgpd')
        };

        function cleanCnpj(value) { return (value || '').replace(/\D+/g, ''); }
        function showMessage(message, ok) {
            if (!feedback) return;
            feedback.innerHTML = '<div style="padding:10px;border-radius:10px;background:' + (ok ? '#edf9ec' : '#fdecec') + ';">' + message + '</div>';
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
                telefone_contato: (fields.telefone_contato.value || '').trim(),
                cidade: (fields.cidade.value || '').trim(),
                uf: (fields.uf.value || '').trim().toUpperCase(),
                endereco: (fields.endereco.value || '').trim(),
                bairro: (fields.bairro.value || '').trim(),
                cep: (fields.cep.value || '').trim(),
                consent_lgpd: true,
                observacoes: [
                    'Representante legal: ' + (fields.representante.value || '').trim(),
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
