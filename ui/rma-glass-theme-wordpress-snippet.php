<?php
/**
 * Functions/snippet completo do fluxo RMA para colar no functions.php.
 *
 * O que entrega:
 * - Enqueue do UI kit + fonte Federo
 * - Shortcode [rma_glass_card_demo]
 * - Shortcode [rma_conta_setup] com formulário atualizado (novos inputs)
 * - Checklist de documentos com tooltip + upload
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

    if ($entity_id > 0) {
        $governance = (string) get_post_meta($entity_id, 'governance_status', true);
        $finance = (string) get_post_meta($entity_id, 'finance_status', true);
        $docs_status = (string) get_post_meta($entity_id, 'documentos_status', true);
        $all_steps_done = ($governance === 'aprovado' && $finance === 'adimplente' && $docs_status === 'enviado');

        ob_start();
        ?>
        <section class="rma-glass-card rma-premium-card" style="margin:20px 0;">
            <span class="rma-badge">RMA • Próximos passos</span>
            <h2 class="rma-glass-title">Acompanhe status, documentos e financeiro</h2>
            <ul style="margin:12px 0 16px 18px;">
                <li><strong>Governança:</strong> <span id="rma-status-governanca"><?php echo esc_html($governance ?: 'pendente'); ?></span></li>
                <li><strong>Documentos:</strong> <span id="rma-status-documentos"><?php echo esc_html($docs_status ?: 'pendente'); ?></span></li>
                <li><strong>Financeiro:</strong> <span id="rma-status-financeiro"><?php echo esc_html($finance ?: 'pendente'); ?></span></li>
            </ul>

            <div style="margin:0 0 14px;">
                <h3 class="rma-premium-section-title">Checklist de documentos</h3>
                <ul class="rma-docs-list">
                    <?php foreach ($required_docs as $doc_key => $doc_meta) : ?>
                        <li>
                            <label style="display:flex;align-items:center;gap:8px;">
                                <span><?php echo esc_html($doc_meta['label']); ?></span>
                                <span title="<?php echo esc_attr($doc_meta['tip']); ?>" style="cursor:help;">ⓘ</span>
                            </label>
                            <input type="file" data-doc-key="<?php echo esc_attr($doc_key); ?>" class="rma-doc-file" accept="application/pdf" />
                            <button class="rma-button rma-doc-upload" type="button" data-doc-key="<?php echo esc_attr($doc_key); ?>">Enviar PDF</button>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="rma-actions rma-actions--spread">
                <a class="rma-button" href="<?php echo esc_url(rma_account_setup_url()); ?>">Status</a>
                <a class="rma-button" href="<?php echo esc_url($docs_url); ?>">Documentos</a>
                <a class="rma-button" href="<?php echo esc_url($finance_url); ?>">Financeiro</a>
                <button class="rma-button" type="button" id="rma-refresh-status">Atualizar status</button>
            </div>

            <div id="rma-flow-feedback" class="rma-feedback"></div>

            <div style="margin-top:14px;">
                <?php if ($all_steps_done) : ?>
                    <a class="rma-button" id="rma-dashboard-link" href="<?php echo esc_url($dashboard_url); ?>">Ir para o dashboard</a>
                <?php else : ?>
                    <button class="rma-button" id="rma-dashboard-link" type="button" disabled style="opacity:.6;cursor:not-allowed;">Ir para o dashboard (libera após concluir etapas)</button>
                <?php endif; ?>
            </div>
        </section>

        <script>
        (function () {
            var entityId = <?php echo (int) $entity_id; ?>;
            var base = <?php echo wp_json_encode($rest_base); ?>;
            var nonce = <?php echo wp_json_encode($rest_nonce); ?>;
            var feedback = document.getElementById('rma-flow-feedback');
            var dashboardLink = document.getElementById('rma-dashboard-link');

            function showFeedback(message, ok) {
                if (!feedback) return;
                feedback.innerHTML = '<div style="padding:10px;border-radius:10px;background:' + (ok ? '#edf9ec' : '#fdecec') + ';">' + message + '</div>';
            }

            function updateState(payload) {
                var g = document.getElementById('rma-status-governanca');
                var d = document.getElementById('rma-status-documentos');
                var f = document.getElementById('rma-status-financeiro');
                if (g) g.textContent = payload.governance_status || 'pendente';
                if (d) d.textContent = payload.documentos_status || 'pendente';
                if (f) f.textContent = payload.finance_status || 'pendente';

                var allDone = payload.governance_status === 'aprovado' && payload.documentos_status === 'enviado' && payload.finance_status === 'adimplente';
                if (allDone && dashboardLink && dashboardLink.tagName === 'BUTTON') {
                    var a = document.createElement('a');
                    a.className = dashboardLink.className;
                    a.id = dashboardLink.id;
                    a.href = <?php echo wp_json_encode($dashboard_url); ?>;
                    a.textContent = 'Ir para o dashboard';
                    dashboardLink.parentNode.replaceChild(a, dashboardLink);
                }
            }

            function refreshStatus() {
                showFeedback('Atualizando status...', true);
                fetch(base + '/entities/' + entityId + '/status', {
                    credentials: 'same-origin',
                    headers: { 'X-WP-Nonce': nonce }
                })
                .then(function (res) { return res.json().then(function (json) { return {ok: res.ok, json: json}; }); })
                .then(function (result) {
                    if (!result.ok) {
                        showFeedback((result.json && result.json.message) ? result.json.message : 'Falha ao atualizar status.', false);
                        return;
                    }
                    updateState(result.json || {});
                    showFeedback('Status atualizado.', true);
                })
                .catch(function () {
                    showFeedback('Erro de conexão ao atualizar status.', false);
                });
            }

            function uploadDoc(docKey, file) {
                if (!file) {
                    showFeedback('Selecione um PDF antes de enviar.', false);
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

            var refreshButton = document.getElementById('rma-refresh-status');
            if (refreshButton) {
                refreshButton.addEventListener('click', refreshStatus);
            }

            document.querySelectorAll('.rma-doc-upload').forEach(function (button) {
                button.addEventListener('click', function () {
                    var key = button.getAttribute('data-doc-key');
                    var fileInput = document.querySelector('.rma-doc-file[data-doc-key="' + key + '"]');
                    uploadDoc(key, fileInput && fileInput.files ? fileInput.files[0] : null);
                });
            });
        })();
        </script>
        <?php
        return (string) ob_get_clean();
    }

    ob_start();
    ?>
    <section class="rma-glass-card rma-premium-card rma-premium-card--setup" style="margin:20px 0;">
        <span class="rma-badge">RMA • Conta da Entidade</span>
        <h2 class="rma-glass-title">Complete seu cadastro institucional</h2>
        <p class="rma-glass-subtitle">Valide o CNPJ, confirme dados e envie para análise.</p>

        <form id="rma-conta-setup-form" class="rma-premium-form">
            <div class="rma-cnpj-row">
                <input type="text" id="rma-cnpj" placeholder="CNPJ" required />
                <button class="rma-button rma-button--ghost" type="button" id="rma-buscar-cnpj">Buscar CNPJ</button>
            </div>
            <input type="text" id="rma-razao-social" placeholder="Razão social" required />
            <input type="text" id="rma-nome-fantasia" placeholder="Nome fantasia" />
            <input type="email" id="rma-email" placeholder="E-mail de contato" required />
            <input type="text" id="rma-representante" placeholder="Nome do representante legal" />
            <input type="text" id="rma-endereco" placeholder="Endereço" />
            <input type="text" id="rma-bairro" placeholder="Bairro" />
            <div class="rma-grid-city-uf">
                <input type="text" id="rma-cidade" placeholder="Cidade" />
                <input type="text" id="rma-uf" placeholder="UF" maxlength="2" />
            </div>
            <input type="text" id="rma-cep" placeholder="CEP" />
            <input type="text" id="rma-telefone" placeholder="Telefone principal" />
            <input type="date" id="rma-data-fundacao" placeholder="Data de fundação" />
            <textarea id="rma-atividades" placeholder="Resumo de atividades (últimos 2 anos)" rows="4"></textarea>

            <div class="rma-docs-block">
                <p class="rma-premium-section-title"><strong>Documentos obrigatórios (PDF)</strong></p>
                <ul class="rma-docs-list">
                    <?php foreach ($required_docs as $doc_key => $doc_meta) : ?>
                        <li>
                            <label style="display:flex;align-items:center;gap:8px;">
                                <span><?php echo esc_html($doc_meta['label']); ?></span>
                                <span title="<?php echo esc_attr($doc_meta['tip']); ?>" style="cursor:help;">ⓘ</span>
                            </label>
                            <input type="file" class="rma-pre-doc-file" data-doc-key="<?php echo esc_attr($doc_key); ?>" accept="application/pdf" />
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

                var entityId = result.json && result.json.id ? result.json.id : 0;
                if (!entityId) {
                    showMessage('Entidade criada, mas não foi possível identificar o ID para upload dos documentos.', false);
                    return Promise.reject(new Error('missing-id'));
                }

                return uploadPreDocs(entityId, docs).then(function () {
                    showMessage('Entidade criada com sucesso. Redirecionando...', true);
                    setTimeout(function () {
                        window.location.replace(contaUrl + '?rma_flow=1');
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
