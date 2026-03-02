# RMA ONGs Platform (subdomínio `https://ongs.`)

Este repositório contém um **MVP modular** para o ecossistema de ONGs da RMA em WordPress, seguindo a estratégia por domínio (plugins desacoplados por hooks/meta/REST).

## Módulos implementados

- `rma-core-entities`
  - CPT `rma_entidade`
  - Metadados padronizados (CNPJ, contato, endereço, status de governança e financeiro)
  - Endpoint para validação/autopreenchimento de CNPJ (`/wp-json/rma/v1/cnpj/{cnpj}`) com cache de validação oficial
  - Tratamento explícito de falha na consulta oficial de CNPJ com resposta `503` quando o serviço externo está indisponível
  - Erros `429/5xx` do provedor oficial são tratados como indisponibilidade temporária (não como CNPJ inexistente)
  - Endpoint de cadastro de entidade com consentimento LGPD (`/wp-json/rma/v1/entities`)
  - Criação de entidade aceita payload JSON e fallback para parâmetros de request quando JSON não estiver presente
  - Consentimento LGPD validado de forma estrita (`rest_sanitize_boolean`)
  - Endpoint de criação reforça autenticação no handler (`401`) como defesa em profundidade
  - Endpoint de status da entidade para painel/checklist (`/wp-json/rma/v1/entities/{id}/status`) com `pending_items`, `next_actions`, `documents_count`, `cnpj_validated_at`, `approvals_remaining` e `publish_eligible`
  - Endpoints de documentos privados por entidade (`GET/POST/DELETE /wp-json/rma/v1/entities/{id}/documents (DELETE em /{doc_id})` e download autenticado)
  - Endpoints de documentos/status reforçam validação de entidade inválida com `404` (defesa em profundidade)
  - Limite de 30 documentos por entidade no upload (com resposta `409` quando excedido)
  - Validação de limite ocorre antes da escrita física do arquivo (evita IO/descarte desnecessário)
  - Armazenamento privado de PDFs com validação de tipo/tamanho e bloqueio de acesso direto
  - Verificação adicional de origem do upload (`is_uploaded_file`) antes de mover para área privada
  - Verificação de legibilidade do temporário antes das validações de MIME/extensão
  - Nome de arquivo é saneado com fallback seguro (`documento.pdf`) quando vazio
  - Validação de PDF usa `finfo` com fallback para checagem nativa do WordPress (`wp_check_filetype_and_ext`)
  - Remoção segura de documentos com validação de propriedade/caminho
  - Download valida tamanho/legibilidade do arquivo antes de enviar headers para evitar resposta corrompida
  - Rate-limit por usuário autenticado (ou IP/fallback anônimo) para consulta CNPJ, criação de entidade e upload/download/remoção de documentos
  - Em documentos, validação de entidade ocorre antes do rate-limit para evitar consumo desnecessário com IDs inválidos
  - Bloqueio de CNPJ duplicado no cadastro
  - Validação adicional de CNPJ ativo na criação (consulta oficial)
  - Enriquecimento automático com dados oficiais (razão social/UF/cidade) e priorização de dados oficiais no cadastro

- `rma-governance`
  - Workflow de 3 aceites com usuários distintos
  - Bloqueio de recusa direta para entidade já aprovada (exige novo ciclo)
  - Reprovação com motivo obrigatório
  - Comentários/motivos de governança são sanitizados e limitados a 1000 caracteres
  - Limitação textual compatível com ambientes sem `mbstring` (fallback seguro para `substr`)
  - Log de auditoria de ações de governança (incluindo IP em rejeição/reenvio), com retenção das 200 entradas mais recentes
  - Endpoints de governança reforçam validação de autenticação no handler (401 em defesa em profundidade)
  - Publicação automática após o 3º aceite
  - Reenvio para análise após recusa via endpoint de governança
  - Bloqueio de auto-aceite (autor da entidade não pode aprovar a própria submissão)
  - Bloqueio de novos aceites enquanto a entidade permanecer com status `recusado` (exige reenvio)
  - Bloqueio de recusa repetida para entidade já `recusado` (mantém fluxo consistente)
  - Recusa permitida apenas em estados `pendente`/`em_analise`
  - Compatibilidade com entidades legadas sem `governance_status` (tratadas como `pendente`)

- `rma-maps-directory`
  - Endpoint público para mapa/diretório (`/wp-json/rma-public/v1/entities`) com paginação (`page`/`per_page`), validação de UF, filtros `area`/`situacao` (`ativa|inadimplente|todas`) e headers `X-WP-Total`/`X-WP-TotalPages`
  - Endpoint público de perfil (`/wp-json/rma-public/v1/entities/{id}`) para página individual da entidade (dados institucionais + localização + áreas + `profile_url`)
  - UF também normalizada na resposta pública para consistência do consumidor
  - Campos textuais públicos (`nome`, `cidade`, `nome_fantasia`, `slug`) são normalizados/sanitizados na resposta
  - Filtros por UF, área de interesse e busca textual
  - Termo de busca normalizado (lowercase/trim) para reduzir variação desnecessária de chaves de cache
  - Regra configurável para exibir apenas adimplentes
  - Cache com transients e invalidação por índice de chaves (inclui eventos de trash/untrash/delete), com retenção das 500 chaves mais recentes
  - Invalidação de cache também em criação/remoção de metas relevantes (`added_post_meta`/`deleted_post_meta`)
  - Índice de chaves atualizado apenas quando há alteração (evita escrita desnecessária em option)

- `rma-woo-sync`
  - Escuta mudanças de pedido WooCommerce
  - Atualiza `finance_status` para `adimplente`/`inadimplente`
  - Endpoint de polling de pagamento para UX de PIX em tempo real (`GET /wp-json/rma/v1/entities/{id}/finance/payment-status`) com `should_redirect` para Minha Conta
  - Geração automática anual de cobranças via cron (`rma_generate_annual_dues`) para entidades aprovadas, com controle de ciclo por `rma_due_year` e data configurável (`rma_due_day_month`)
  - Registra histórico anual financeiro (ano com base no pagamento do pedido quando disponível), com retenção das 500 entradas mais recentes
  - Ano do histórico usa timestamp de pagamento em formato canônico (`Y`) para evitar variações locale-dependentes

- `rma-automations`
- `rma-admin-settings`
  - Painel de campos personalizáveis para Superadmin (`RMA Configurações`)
  - Campos: valor da anuidade, ID produto Woo da anuidade, data de início do ciclo (`dd-mm`), chave PIX institucional, Google Maps API Key, e-mail institucional, URL API de notificações e toggle “somente adimplentes no diretório”

  - Rotina diária via WP-Cron
  - Processamento paginado de entidades na automação diária (evita truncar execuções com base >500 registros)
  - Alertas de mandato (60/30/7 dias) para entidades aprovadas
  - Alertas de anuidade pendente (com janela de vencimento quando `anuidade_vencimento` existir)
  - Log de envios por entidade, com retenção das 200 entradas mais recentes
  - Evita disparos duplicados no mesmo dia/contexto

- `rma-analytics`
  - Endpoint de resumo administrativo (inclui `faturamento_total`, `faturamento_pago`, `ticket_medio_pago`, `inadimplencia_percentual`, `adimplencia_percentual` e ordenação por UF)
  - Normalização de UF no resumo (agrega caixa mista/inválida em `N/D` para consistência)
  - Exportação CSV reutiliza a mesma normalização de UF para manter consistência com o resumo
  - Endpoint de exportação CSV
  - Exportação CSV com escaping/quoting consistente de colunas textuais para reduzir ambiguidades de parsing
  - Exportação CSV usa consulta por IDs (`fields=ids`) para reduzir uso de memória em bases grandes
  - Consultas de analytics desativam cálculos/caches desnecessários (`no_found_rows` e caches de termo/meta)
  - Indicador agregado de faturamento por histórico financeiro

- `rma-demo-seeder`
  - WP-CLI para homologação: `wp rma seed-demo --entities=60 --simulate-emails`, setup incremental via `wp rma setup-next --entities=60 --simulate-emails` e limpeza via `wp rma clear-demo`
  - Página admin `RMA → Dados Demonstrativos` com botões para gerar/limpar massa de dados demo
  - Gera entidades demo (`rma_is_demo=1`), aprovações, pedidos Woo, documentos dummy, logs de e-mail e validações de consistência (inclui contagem por UF e matriz de segurança por autor)

## Como instalar

1. Copie os diretórios de `wp-content/plugins/*` para o WordPress do subdomínio.
2. Ative os plugins na seguinte ordem:
   1. RMA Core Entities
   2. RMA Governance
   3. RMA Maps & Directory
   4. RMA Woo Sync
   5. RMA Automations
   6. RMA Analytics
   7. RMA Admin Settings
   8. (Opcional homologação) RMA Demo Seeder
3. Garanta que WooCommerce + gateway PIX estejam ativos para o fluxo financeiro.
4. Configure no menu `RMA Configurações` os campos de operação (anuidade, PIX, Google Maps API Key, notificações e motor de e-mail: WordPress ou WooCommerce).
5. (Opcional financeiro anual) Configure as options `rma_annual_dues_product_id` e `rma_annual_due_value` para geração automática anual de pedidos.

## Pontos para o tema

Como cadastro/login serão gerenciados no tema, o tema deve:

- Consumir `/rma/v1/cnpj/{cnpj}` no passo 1 do wizard.
- Enviar payload final para `/rma/v1/entities`.
- Exibir status e pendências lendo os metas da entidade vinculada ao usuário.
- Acionar endpoints de governança apenas em telas administrativas (RMA).

### Diretriz visual oficial (tema)

Para as telas do tema que consumirem este MVP, seguir o padrão visual abaixo:

- **Fonte principal:** Federo
- **Cor primária:** `#7bad39`
- **Cor escura/base (preto):** `#37302c`
- **Estilo de interface:** glasmorphism branco “ultra” (cartões/containers translúcidos claros, blur de fundo e bordas suaves)

Tokens sugeridos para implementação no tema:

```css
:root {
  --rma-font-family: 'Federo', sans-serif;
  --rma-color-primary: #7bad39;
  --rma-color-dark: #37302c;
  --rma-glass-bg: rgba(255, 255, 255, 0.72);
  --rma-glass-border: rgba(255, 255, 255, 0.58);
  --rma-glass-shadow: 0 12px 40px rgba(55, 48, 44, 0.18);
  --rma-glass-blur: blur(18px);
}
```


Para acelerar a implementação no tema, há um kit inicial em `ui/`:

- `ui/rma-glass-theme.css` (tokens e componentes base, incluindo estado de foco acessível e responsividade)
- `ui/rma-glass-theme-preview.html` (preview estático com topbar, status chips, KPIs, wizard de cadastro e formulário)
- `ui/rma-glass-theme-wordpress-snippet.php` (snippet de integração completo: enqueue, shortcodes `[rma_glass_card_demo]` e `[rma_conta_setup]`, redirect para `/conta/` e anti-loop)
- `ui/rma-glass-theme.js` (comportamento opcional do wizard: avançar/voltar/reiniciar, suporte a múltiplos wizards, sync de etapa atual em `data-rma-current-step` e atalhos ←/→ e ESC no preview)
- `ui/assets/` com SVGs de apoio visual (`rma-logo-mark.svg`, `rma-map-pin.svg`, `rma-document.svg`) para UI mais profissional


Uso rápido no tema (WordPress):

1. Copie `ui/rma-glass-theme.css` e `ui/rma-glass-theme.js` para dentro do tema (ex.: `wp-content/themes/seu-tema/ui/`).
2. Adapte o conteúdo de `ui/rma-glass-theme-wordpress-snippet.php` no `functions.php`.
3. Crie a página `/conta/` com shortcode `[rma_conta_setup]` para onboarding da entidade (CNPJ + criação via REST).
4. Após criar a entidade, a própria página `/conta/` vira hub de fluxo com links de Status, Documentos e Financeiro; enquanto as etapas não forem concluídas, o usuário permanece no fluxo e qualquer tentativa de abrir outras rotas (incluindo `/dashboard/`) volta para `/conta/`.
5. Para validar rapidamente o visual, use `[rma_glass_card_demo]` em uma página de teste.
- O bloco `/conta/` inclui checklist de documentos obrigatórios com tooltips orientativos e upload de PDFs por item (pré-seleção no cadastro e envio direto no passo de documentos).

Debug de fluxo (quando ainda redirecionar para dashboard):

- Ative `WP_DEBUG` no `wp-config.php`.
- Acesse a URL com `?rma_debug_flow=1` (ex.: `/rma/conta/?rma_debug_flow=1`).
- O snippet registra no `error_log` eventos com prefixo `[RMA_FLOW]` explicando por que permitiu/negou rota e quando redirecionou para `/conta/`.

Checklist visual (100%):

- [x] Fonte Federo aplicada
- [x] Cor primária `#7bad39` em ações/destaques
- [x] Cor base `#37302c` para leitura principal
- [x] Cartões glasmorphism branco ultra com blur e sombra
- [x] Componentes responsivos (desktop/mobile)
- [x] Foco acessível em campos e botões
- [x] Exemplo de wizard de 5 etapas com estados (done/current/pending)
- [x] Navegação do wizard por botões e teclado (setas ←/→ + ESC para reiniciar), com `aria-keyshortcuts` no preview

Exemplo de container visual:

```css
.rma-glass-card {
  font-family: var(--rma-font-family);
  background: var(--rma-glass-bg);
  border: 1px solid var(--rma-glass-border);
  backdrop-filter: var(--rma-glass-blur);
  -webkit-backdrop-filter: var(--rma-glass-blur);
  box-shadow: var(--rma-glass-shadow);
  border-radius: 20px;
  color: var(--rma-color-dark);
}

.rma-glass-card .rma-accent {
  color: var(--rma-color-primary);
}
```

## Próximos passos recomendados

1. Criar UI do wizard (5 passos) no tema com nonces e mensagens de erro/pendência.
2. Implementar gestão de documentos privados com endpoint autenticado (download controlado).
3. Adicionar geocoding automático em criação/edição de endereço.
4. Criar tela administrativa de governança com trilha de auditoria visual.
5. Integrar geração de pedido da anuidade por ano no painel da entidade.
6. Adicionar testes automatizados (WP-CLI + integração REST).


## Validação local

- `find wp-content -name '*.php' -print0 | xargs -0 -n1 php -l`



## Checklist de homologação (meta 100%)

Use este roteiro para validar o MVP no WordPress de ponta a ponta.

1. **Setup de ambiente**
   - Ativar plugins na ordem recomendada.
   - Garantir WooCommerce + gateway PIX + método de envio de e-mail funcionando.
   - Configurar `RMA Configurações` (anuidade, ciclo, PIX, API Maps e motor de e-mail).

2. **Massa de dados**
   - Gerar base demo: `wp rma seed-demo --entities=60 --simulate-emails`
   - (Opcional incremental): `wp rma setup-next --entities=60 --simulate-emails`

3. **REST (core + diretório)**
   - Validar CNPJ: `GET /wp-json/rma/v1/cnpj/{cnpj}`
   - Cadastrar entidade: `POST /wp-json/rma/v1/entities`
   - Listar diretório público: `GET /wp-json/rma-public/v1/entities?page=1&per_page=20`
   - Validar perfil público: `GET /wp-json/rma-public/v1/entities/{id}`

4. **Governança**
   - Executar fluxo de 3 aprovações com usuários distintos.
   - Validar recusa com motivo e reenvio para nova análise.

5. **Financeiro (Woo + PIX)**
   - Confirmar mudança de `finance_status` por alteração de status do pedido Woo.
   - Testar polling de pagamento: `GET /wp-json/rma/v1/entities/{id}/finance/payment-status`.
   - Rodar ciclo anual manualmente: `wp cron event run rma_generate_annual_dues`.

6. **Automações/e-mail**
   - Rodar automação manual: `wp cron event run rma_daily_automation`.
   - Testar em `RMA Configurações` os 2 motores de e-mail:
     - `WordPress padrão (wp_mail)`
     - `WooCommerce (layout/template de e-mail)`

7. **Analytics/CSV**
   - Conferir resumo administrativo.
   - Exportar CSV e validar colunas, escaping e consistência dos números.

8. **Limpeza de homologação**
   - Limpar massa demo: `wp rma clear-demo`

Critério de aceite para 100%: todos os itens acima executados sem erro funcional e com evidência (print/log/comando) por etapa.

## Dados demonstrativos (homologação)

1. Ative o plugin **RMA Demo Seeder**.
2. Gere dados demo via WP-CLI:
   - `wp rma seed-demo --entities=60 --simulate-emails`
3. Ou via Admin: `RMA → Dados Demonstrativos` (com botão **Avançar setup demo** para instalar os demos em etapas).
4. Para limpar somente massa demo:
   - `wp rma clear-demo`

Observações:
- Dados demo são marcados com meta `rma_is_demo=1`.
- Limpeza remove entidades demo, pedidos Woo demo, anexos demo e logs demo auxiliares.
- Usuários demo criados: `admin_rma_demo`, `aprovador_demo`, `entidade_demo_a` e `entidade_demo_b`.
