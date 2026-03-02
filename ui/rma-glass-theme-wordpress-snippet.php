<?php
/**
 * Snippet completo para integrar o fluxo RMA no tema (functions.php).
 *
 * Inclui:
 * - Enqueue do UI kit (CSS/JS + Federo)
 * - Shortcode visual de validação [rma_glass_card_demo]
 * - Shortcode de onboarding da entidade [rma_conta_setup]
 * - Redirect automático para /conta/ quando usuário logado ainda não tem rma_entidade
 * - Anti-loop de redirect
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Resolve base do tema contendo a pasta /ui (child primeiro, depois parent).
 */
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

/**
 * URL da página de onboarding da conta da entidade.
 * No cenário informado, a URL final é /rma/conta/ (com WP em subdiretório /rma).
 */
function rma_account_setup_url() {
    return trailingslashit(home_url('/conta/'));
}

/**
 * Path normalizado da URL de onboarding para comparação anti-loop.
 */
function rma_account_setup_path() {
    $path = wp_parse_url(rma_account_setup_url(), PHP_URL_PATH);
    return is_string($path) ? untrailingslashit($path) : '';
}

/**
 * Busca entidade do usuário por post_author.
 */
function rma_get_entity_id_by_author($user_id) {
    $user_id = (int) $user_id;
    if ($user_id <= 0) {
        return 0;
    }

    $query = new WP_Query([
        'post_type'              => 'rma_entidade',
        'post_status'            => ['publish', 'draft', 'pending', 'private'],
        'author'                 => $user_id,
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

/**
 * Enfileira UI kit e fonte Federo.
 */
add_action('wp_enqueue_scripts', function () {
    $base = rma_ui_theme_base();
    if (! $base) {
        return;
    }

    $css_file = trailingslashit($base['path']) . 'rma-glass-theme.css';
    $js_file  = trailingslashit($base['path']) . 'rma-glass-theme.js';

    if (file_exists($css_file)) {
        wp_enqueue_style(
            'rma-glass-theme',
            trailingslashit($base['uri']) . 'rma-glass-theme.css',
            [],
            (string) filemtime($css_file)
        );
    }

    if (file_exists($js_file)) {
        wp_enqueue_script(
            'rma-glass-theme-js',
            trailingslashit($base['uri']) . 'rma-glass-theme.js',
            [],
            (string) filemtime($js_file),
            true
        );
    }

    wp_enqueue_style(
        'rma-federo-font',
        'https://fonts.googleapis.com/css2?family=Federo&display=swap',
        [],
        null
    );
});

/**
 * Shortcode visual simples para validação do estilo.
 */
add_shortcode('rma_glass_card_demo', function () {
    ob_start();
    ?>
    <section class="rma-glass-card" style="margin:20px 0;">
        <span class="rma-badge">RMA • Glasmorphism Ultra White</span>
        <h2 class="rma-glass-title">Card de demonstração</h2>
        <p class="rma-glass-subtitle">
            Este bloco usa Federo, #7bad39 e #37302c com acabamento translúcido branco.
        </p>
        <div class="rma-actions">
            <a class="rma-button" href="<?php echo esc_url(rma_account_setup_url()); ?>">Ir para conta</a>
        </div>
    </section>
    <?php
    return (string) ob_get_clean();
});

/**
 * Shortcode de onboarding da entidade para usar na página /conta/.
 */
add_shortcode('rma_conta_setup', function () {
    if (! is_user_logged_in()) {
        return '<p>Você precisa estar logado para completar o cadastro da entidade.</p>';
    }

    $entity_id = rma_get_entity_id_by_author(get_current_user_id());
    if ($entity_id > 0) {
        return '<p>Cadastro da entidade já criado. Você pode seguir para o dashboard.</p>';
    }

    $rest_base     = rest_url('rma/v1');
    $rest_nonce    = wp_create_nonce('wp_rest');
    $dashboard_url = home_url('/dashboard/');

    ob_start();
    ?>
    <section class="rma-glass-card" style="margin:20px 0;padding:24px;">
        <span class="rma-badge">RMA • Conta da Entidade</span>
        <h2 class="rma-glass-title">Complete seu cadastro institucional</h2>
        <p class="rma-glass-subtitle">Valide o CNPJ, confirme dados e envie para análise.</p>

        <form id="rma-conta-setup-form" style="display:grid;gap:10px;margin-top:12px;">
            <input type="text" id="rma-cnpj" placeholder="CNPJ" required />
            <input type="text" id="rma-razao-social" placeholder="Razão social" required />
            <input type="text" id="rma-nome-fantasia" placeholder="Nome fantasia" />
            <input type="email" id="rma-email" placeholder="E-mail de contato" required />
            <input type="text" id="rma-cidade" placeholder="Cidade" />
            <input type="text" id="rma-uf" placeholder="UF" maxlength="2" />
            <label><input type="checkbox" id="rma-consent-lgpd" required /> Concordo com LGPD</label>
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <button class="rma-button" type="button" id="rma-buscar-cnpj">Buscar CNPJ</button>
                <button class="rma-button" type="submit">Salvar entidade</button>
            </div>
        </form>

        <div id="rma-feedback" style="margin-top:10px;"></div>
    </section>

    <script>
    (function () {
        var base = <?php echo wp_json_encode($rest_base); ?>;
        var nonce = <?php echo wp_json_encode($rest_nonce); ?>;
        var dashboardUrl = <?php echo wp_json_encode($dashboard_url); ?>;

        var form = document.getElementById('rma-conta-setup-form');
        if (!form) { return; }

        var feedback = document.getElementById('rma-feedback');
        var fields = {
            cnpj: document.getElementById('rma-cnpj'),
            razao_social: document.getElementById('rma-razao-social'),
            nome_fantasia: document.getElementById('rma-nome-fantasia'),
            email_contato: document.getElementById('rma-email'),
            cidade: document.getElementById('rma-cidade'),
            uf: document.getElementById('rma-uf'),
            consent_lgpd: document.getElementById('rma-consent-lgpd')
        };

        function cleanCnpj(value) {
            return (value || '').replace(/\D+/g, '');
        }

        function showMessage(message, ok) {
            feedback.innerHTML = '<div style="padding:10px;border-radius:10px;background:' + (ok ? '#edf9ec' : '#fdecec') + ';">' + message + '</div>';
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
            .then(function (res) { return res.json().then(function (json) { return { ok: res.ok, json: json }; }); })
            .then(function (result) {
                if (!result.ok) {
                    showMessage(result.json && result.json.message ? result.json.message : 'Não foi possível consultar o CNPJ.', false);
                    return;
                }
                fields.razao_social.value = result.json.razao_social || fields.razao_social.value;
                fields.nome_fantasia.value = result.json.nome_fantasia || fields.nome_fantasia.value;
                fields.cidade.value = result.json.cidade || fields.cidade.value;
                fields.uf.value = (result.json.uf || fields.uf.value || '').toUpperCase();
                showMessage('CNPJ validado e dados preenchidos.', true);
            })
            .catch(function () {
                showMessage('Erro de conexão ao consultar CNPJ.', false);
            });
        });

        form.addEventListener('submit', function (event) {
            event.preventDefault();

            var payload = {
                cnpj: cleanCnpj(fields.cnpj.value),
                razao_social: (fields.razao_social.value || '').trim(),
                nome_fantasia: (fields.nome_fantasia.value || '').trim(),
                email_contato: (fields.email_contato.value || '').trim(),
                cidade: (fields.cidade.value || '').trim(),
                uf: (fields.uf.value || '').trim().toUpperCase(),
                consent_lgpd: !!fields.consent_lgpd.checked
            };

            if (!payload.consent_lgpd) {
                showMessage('É obrigatório aceitar LGPD para continuar.', false);
                return;
            }

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
            .then(function (res) { return res.json().then(function (json) { return { ok: res.ok, json: json }; }); })
            .then(function (result) {
                if (!result.ok) {
                    showMessage(result.json && result.json.message ? result.json.message : 'Falha ao salvar entidade.', false);
                    return;
                }

                showMessage('Entidade criada com sucesso. Redirecionando...', true);
                setTimeout(function () {
                    window.location.href = dashboardUrl;
                }, 900);
            })
            .catch(function () {
                showMessage('Erro de conexão ao salvar entidade.', false);
            });
        });
    })();
    </script>
    <?php

    return (string) ob_get_clean();
});

/**
 * Redirect para /conta/ quando usuário logado ainda não possui entidade.
 * Inclui anti-loop e exclusões de rotas sensíveis.
 */
add_action('template_redirect', function () {
    if (is_admin() || wp_doing_ajax() || wp_doing_cron()) {
        return;
    }

    if (defined('REST_REQUEST') && REST_REQUEST) {
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

    // Anti-loop: já está na página de onboarding.
    if ($account_path !== '' && $request_path === $account_path) {
        return;
    }

    // Não forçar redirect nas rotas de autenticação.
    $safe_suffixes = ['/login', '/register', '/wp-login.php'];
    foreach ($safe_suffixes as $safe_suffix) {
        if (substr($request_path, -strlen($safe_suffix)) === $safe_suffix) {
            return;
        }
    }

    // Já possui entidade: segue fluxo normal do tema.
    if (rma_get_entity_id_by_author(get_current_user_id()) > 0) {
        return;
    }

    wp_safe_redirect(rma_account_setup_url());
    exit;
}, 20);
