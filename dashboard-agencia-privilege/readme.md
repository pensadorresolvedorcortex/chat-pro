# Dashboard Agência Privilege

Plugin WordPress que aplica o visual do template **Ubold** ao painel administrativo e substitui o dashboard nativo por um painel moderno com KPIs, gráficos ApexCharts e widgets personalizados.

## Instalação

1. Faça o upload da pasta `dashboard-agencia-privilege` para o diretório `wp-content/plugins/` ou compacte-a em `.zip` e instale via painel do WordPress.
2. No WordPress, acesse **Plugins → Adicionar novo → Enviar plugin** e ative o **Dashboard Agência Privilege**.
3. (Opcional) Ajuste as configurações em **Configurações → Agência Privilege** para personalizar o destino do botão principal ou desativar a skin global (ela agora vem habilitada por padrão para tematizar o wp-admin inteiro).

### Ativos do Ubold

Por questões de licença, **nenhum arquivo proprietário do Ubold acompanha este repositório**. O plugin funciona com um conjunto mínimo de fallbacks (Bootstrap 5, Remix Icon e ApexCharts via CDN), mas para obter a aparência idêntica ao HTML Modern você precisa copiar seus próprios assets licenciados.

1. Localize no pacote oficial do Ubold a pasta `Docs/assets`.
2. Copie o conteúdo dela para `wp-content/plugins/dashboard-agencia-privilege/assets/ubold/assets/` (mantendo subpastas como `css/`, `js/` e `vendor/`).
3. Como alternativa, você pode colocar os arquivos diretamente em `assets/css`, `assets/js` e `assets/vendor` dentro do plugin — ambas as estruturas são detectadas automaticamente.
4. Certifique-se de incluir os arquivos principais (`css/app.min.css`, `css/vendor.min.css` ou `css/icons.min.css`, `js/vendor.min.js`, `js/app.min.js` e `vendor/apexcharts/apexcharts.min.js`). O plugin identifica quais arquivos estão presentes e usa os CDN apenas quando algo estiver faltando.

## Funcionalidades

- Skin completa do Ubold aplicada ao `/wp-admin/index.php` com wrapper namespaced `.dap-admin`, fallback automático via CDN quando os arquivos oficiais não estiverem presentes **e skin global habilitada por padrão** para manter consistência nas demais telas.
- Dashboard Modern inspirado no HTML do Ubold com hero gradiente, KPIs, gráficos ApexCharts e listas atualizadas.
- Indicadores, gráficos e tabelas abastecidos automaticamente com dados reais do WordPress (posts publicados, posts recentes, comentários e categorias mais usadas).
- Integração nativa com WooCommerce: cards de vendas, inventário, pedidos recentes, notificações e atividade usam pedidos reais quando a extensão estiver ativa (com fallbacks automáticos quando não estiver).
- Dataset do dashboard cacheado automaticamente por alguns minutos e invalidado quando posts ou comentários mudam, reduzindo consultas repetidas sem deixar os dados defasados.
- Indicador de "última atualização" no topo do painel com botão **Atualizar agora** para limpar o cache manualmente e reconstruir os dados em um clique.
- Carregamento condicional de assets apenas no Dashboard (e CSS opcional em outras telas).
- API REST (`/wp-json/dap/v1/stats`) retorna exatamente o mesmo dataset do dashboard (KPIs, tabelas, gráficos e últimos logs), permitindo integrações reais sem duplicar consultas.
- Página de configurações simples para personalizar o comportamento do painel.
- Área dedicada "Widgets Elementor" para construir os widgets introdutórios com Elementor, antes do layout analítico principal.
- Card "Plugin Logs" com limpeza rápida e suporte à função `dap_record_error_log()` para inspecionar problemas diretamente do dashboard.
- Card "Status do painel" exibindo diagnóstico em tempo real (tema Ubold detectado, skin global, canvas Elementor e cache dos dados) com atalhos para configurações e widgets.
- Menu de idiomas no topbar Modern que altera imediatamente o locale do usuário dentro do wp-admin, mantendo o badge "Atual" para destacar o idioma ativo.

### Extensão de dados

- Use o filtro `dap_dashboard_post_types` para informar quais tipos de post o painel deve monitorar ao calcular KPIs e gráficos (por padrão, `post` e `page`).
- Personalize toda a estrutura enviada ao layout com o filtro `dap_dashboard_data`, permitindo injetar KPIs próprios, linhas de projetos, atividades ou dados de gráficos vindos de APIs externas.
- Intercepte a resposta do endpoint REST com o filtro `dap_rest_stats_response` para acrescentar propriedades personalizadas antes de expor os dados para integrações.
- Ajuste o TTL do cache de dados com o filtro `dap_dashboard_cache_ttl` ou invalide manualmente chamando `dap_invalidate_dashboard_cache()` após sincronizações externas.

### Widgets iniciais com Elementor

1. Após ativar o plugin, acesse **Painel → Widgets Elementor** para abrir a tela administrativa da área.
2. Clique em **Editar com Elementor** para lançar o construtor visual. Tudo o que for salvo ali será renderizado no topo do Dashboard, replicando os cards Modern do HTML original.
3. Caso o Elementor não esteja ativo, utilize o editor padrão para inserir shortcodes, blocos ou HTML.

## Testes recomendados

- Ative o plugin e visite `/wp-admin/index.php` para verificar o layout Ubold.
- Copie os ativos do Ubold e confirme se estilos e scripts são carregados sem erros no console.
- Gere uma entrada de log com `dap_record_error_log( 'Minha mensagem' );` e verifique se o card **Plugin Logs** exibe e permite limpar o histórico.
- Navegue em outras telas do admin (WooCommerce, Elementor, etc.) para validar que o layout permanece intacto.

> Dica: substitua `assets/images/hero-placeholder.svg` por uma arte do Ubold (ex.: `assets/ubold/assets/images/hero-1.png` ou `assets/images/hero-1.png`) para reproduzir o visual original do hero.

## Licença

Este plugin não distribui os arquivos proprietários do tema Ubold. Certifique-se de possuir a licença adequada antes de copiar os assets do tema. O restante do código deste plugin pode ser utilizado conforme as políticas internas da sua agência.
