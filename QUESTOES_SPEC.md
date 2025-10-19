# Questões Academia da Comunicação — Especificação do Plugin WordPress

## Identidade e Metadados
- **Nome do plugin:** Questões Academia da Comunicação
- **Pasta do plugin:** `questoes`
- **Slug:** `questoes`
- **Text domain:** `questoes`

### Paleta oficial
| Uso | Hex |
| --- | --- |
| Preto / Texto principal / Primária | `#242142` |
| Clara (background de destaques) | `#c3e3f3` |
| Secundária / Acento | `#bf83ff` |
| Neutros permitidos (bordas/sombras) | `#E5E7EB`, `#F1F5F9` |

**Regras de contraste**
- Fundo base branco.
- Relação de contraste mínima 4.5:1 para textos.
- Nenhuma cor fora da paleta acima.

## Estrutura de Pastas do Plugin
```
questoes/
  assets/
    css/
      frontend.css
      block-editor.css
    js/
      frontend.js
      block.js
    admin/
      admin.css
      admin.js
      questions.js
  includes/
    class-admin.php
    class-questions.php
    class-frontend.php
    class-renderer.php
    class-accessibility.php
    class-rest.php
    class-settings.php
    class-block.php
    helpers.php
    schema.php
  languages/
  views/
    frontend-map.php
    frontend-org.php
    tabs.php
  questoes.php
  readme.txt
```

## Modelo de Dados (JSON)
O plugin consome um JSON com dois ramos principais, `mindmap` e `orgchart`.

```json
{
  "title": "Questões — Academia da Comunicação",
  "mindmap": {
    "id": "root",
    "label": "Tema Central",
    "children": [
      {
        "id": "pilar1",
        "label": "Pilar 1",
        "children": [
          { "id": "topico1", "label": "Tópico 1" },
          { "id": "topico2", "label": "Tópico 2" }
        ]
      }
    ]
  },
  "orgchart": {
    "id": "diretoria",
    "label": "Diretoria",
    "meta": { "role": "Nível 1" },
    "children": []
  }
}
```

### Regras de validação
- `id`: obrigatório, string única.
- `label`: obrigatório, string curta (máx. 60 caracteres; quebra automática).
- `children`: opcional, array de nós.
- `meta`: opcional (ex.: `role`, `url`, `badge`).
- Profundidade máxima recomendada: 6 níveis.
- Sanitização: remover scripts embutidos em `meta`.

## UX e Comportamentos
### Layout geral
- Abas: “Mapa Mental” e “Organograma”. Aba ativa recebe fundo `#bf83ff`, texto branco e bordas suaves.
- Layout de duas vistas com alternância por abas.
- Container responsivo com rolagem horizontal quando necessário.
- Cabeçalho persistente com título e botões alinhados à direita; altura máxima 72 px para preservar área útil do canvas.

### Interações
- Zoom e Pan com botões “Zoom +”, “Zoom –”, “Centralizar”, “Imprimir”, além de arrasto do mouse e gesto de pinça/touch.
- Reset de zoom via botão ou tecla `Esc`.
- Clique/toque em nós expande ou recolhe filhos.
- Navegação por teclado: setas movimentam foco, `Enter` expande/contrai, `Esc` reseta zoom.
- Tooltip opcional ao focar nós exibindo `meta.role` ou outras entradas permitidas, respeitando atraso de 200 ms para acessibilidade.
- Estados de carregamento devem bloquear interações e informar progresso com barra discreta quando os dados vêm de endpoint remoto.

### Estilização dos nós
- Raiz destacada com borda de 2px na Primária `#242142`.
- Nós filhos: borda de 1px, cantos arredondados (12–16px).
- Estados: hover com sombra sutil, focus com outline 3px em `#bf83ff` (WCAG 2.1 AA).
- Destaques usam a cor secundária `#bf83ff`.
- Etiquetas longas quebram em múltiplas linhas com `max-width: 220px` e alinhamento centralizado; aplicar `hyphens: auto`.
- Ícones de expansão (+/–) utilizam fonte ícone embutida (SVG) com tamanho fixo 16 px.

### Acessibilidade
- Labels descritivos (`aria-label`) para containers e nós: “Nó: {label} – nível {n}`.
- Suporte completo a teclado sem armadilhas de foco.
- Garantir contraste mínimo 3:1 em ícones e bordas; quando necessário, aplicar `box-shadow: 0 0 0 3px rgba(36,33,66,0.15)`.

### Modo Impressão/PDF
- Remove abas e botões de controle.
- Mantém contraste alto e estrutura limpa.
- Inserir cabeçalho com título e data de exportação acima do gráfico.
- Ajustar espaçamento entre níveis para no mínimo 24 px a fim de evitar sobreposição ao imprimir.

## Banco de Questões
- Registrar Custom Post Type `questao` com suporte a editor rico, autor, miniatura, comentários e REST (`show_in_rest: true`).
- Taxonomias:
  - `questao_categoria` (hierárquica) para trilhas, assuntos e módulos.
  - `questao_banca` (não hierárquica) para registrar banca/examinadora.
- Metacampos associados (todos expostos via REST):
  - `questoes_answers`: array de objetos `{ id, text, is_correct, feedback }` (uma única alternativa correta).
  - `questoes_difficulty`: `easy|medium|hard`.
  - `questoes_reference`: referência bibliográfica ou observações.
  - `questoes_source`: concurso/prova de origem.
  - `questoes_estimated_time`: tempo estimado em minutos.
  - `questoes_explanation`: comentário pós-resposta.
- Interface administrativa dedicada:
  - Metabox "Detalhes da questão" com grade responsiva seguindo a paleta #242142/#bf83ff/#c3e3f3.
  - Gerenciador de alternativas em JavaScript (`assets/admin/questions.js`) com duplicação, remoção e marcação da correta.
  - Validação mínima: ao menos duas alternativas, sempre uma correta.
- Listagem no admin com colunas adicionais (dificuldade, número de alternativas) e ordenação por dificuldade.
- Função utilitária `upsert_question_from_array()` para importar/exportar questões em lote.
- Comentários habilitados por padrão para permitir discussões quando respostas forem publicadas.

### API REST — Banco de Questões
- `GET /questoes/v1/questions`
  - Parâmetros opcionais: `search`, `per_page` (1–100), `page`, `category` (slug ou múltiplos separados por vírgula), `banca`, `difficulty`.
  - Retorno: `{ items: [...], total, pages }` com payload preparado por `prepare_question_for_response()`.
- `POST /questoes/v1/questions`
  - Requer `manage_options`.
  - Body: `{ title, content, excerpt?, status?, answers[], difficulty?, reference?, source?, estimated_time?, explanation?, categories?, bancas?, id? }`.
  - Permite criar ou atualizar questões (passando `id`).
  - Mensagens de erro claras para título ausente, permissão insuficiente e payload inválido.

## Integrações WordPress
### Shortcode
```
[questoes modo="mapa|organograma|ambos" titulo="..."]conteudo_json[/questoes]
```
- JSON inline sobrescreve fonte padrão.
- Sem conteúdo: usa JSON salvo nas configurações.

### Bloco Gutenberg — "Questões – Mapa/Organograma"
- Painel lateral: título, modo, fonte de dados (colar JSON, upload `.json`, endpoint).
- Pré-visualização ao vivo leve.

#### Atributos do bloco
```json
{
  "title": { "type": "string", "default": "Questões — Academia da Comunicação" },
  "mode": { "type": "string", "default": "ambos", "enum": ["mapa", "organograma", "ambos"] },
  "dataSource": { "type": "string", "default": "settings", "enum": ["inline", "upload", "endpoint", "settings"] },
  "inlineJson": { "type": "string" },
  "endpointUrl": { "type": "string" },
  "allowComments": { "type": "boolean", "default": true }
}
```
- `render_callback` usa `class-frontend.php` para compartilhar lógica com o shortcode.
- Upload no editor utiliza `wp.data.dispatch('core').uploadMedia` com tipo `application/json`.
- Botão “Testar renderização” roda validação rápida em `schema.php` antes de salvar.

### Configurações (WP-Admin > Questões)
- Campo “Título padrão”.
- Paleta bloqueada (com ícone de cadeado) e opção “Permitir personalização avançada”.
- Área para colar JSON com validação imediata e mensagem “Cole seu JSON no campo abaixo e clique em Testar.”
- Upload `.json` e botão “Testar renderização”.
- Botões “Exportar JSON” e “Exportar Configurações”.
- Banner informativo opcional para mostrar status de sincronização com endpoint remoto (última atualização, próximo refresh).

#### Layout da página de configurações
1. **Header**: título da página + botão “Exportar Configurações”.
2. **Card “Dados do Gráfico”** (bordas 1px `#E5E7EB`):
   - Toggle “Fonte: JSON salvo / Endpoint remoto”.
   - Textarea 2 colunas × 8 linhas com contador de caracteres (máx. 10 000).
   - Botões “Testar renderização” e “Importar JSON”.
3. **Card “Visual”**:
   - Swatches bloqueados (`#242142`, `#bf83ff`, `#c3e3f3`).
   - Toggle “Permitir personalização avançada” habilitando `wp-color-picker` com validação de contraste.
4. **Card “Acessibilidade”**:
   - Checkbox “Ativar descrições textuais abaixo do gráfico”.
   - Campo de legenda geral (máx. 160 caracteres).
5. **Card “Comentários”**:
   - Checkbox “Habilitar comentários quando respostas estiverem publicadas”.
   - Texto de ajuda: “Utiliza o sistema padrão de comentários do WordPress na mesma página.”
6. **Footer**: botão primário “Salvar alterações” + botão secundário “Exportar JSON”.
7. **Avisos**: área inferior para mensagens de sucesso/erro persistentes, reutilizando componentes `notice notice-success|error`.

#### Exportação e importação
- **Exportar JSON**: baixa arquivo `questoes-data.json` com objeto completo (title, mindmap, orgchart).
- **Exportar Configurações**: gera `questoes-settings.json` incluindo título padrão, flags de acessibilidade, fonte de dados e timestamp.
- **Importar JSON**: aceita arquivos até 1 MB, valida contra schema antes de salvar.
- **Importar Configurações**: sobrescreve opções após confirmação modal, preservando dados se checkbox “Manter dados atuais” estiver marcada.

### REST API
- `GET /questoes/v1/data` → retorna JSON ativo.
- `POST /questoes/v1/data` → valida e salva (capability `manage_options`, nonce obrigatório).

#### Contratos detalhados
| Método & rota | Request | Response (200) | Erros (códigos & payload) |
| --- | --- | --- | --- |
| `GET /questoes/v1/data` | Nenhum parâmetro obrigatório. Aceita `If-Modified-Since` opcional. | ```json
{ "title": "Questões — Academia da Comunicação", "mindmap": {…}, "orgchart": {…} }
``` | `401` (se autenticação exigida por configuração) → `{ "code": "questoes_forbidden", "message": "Acesso restrito." }`.<br>`500` → `{ "code": "questoes_unexpected_error", "message": "Erro interno ao recuperar dados." }` |
| `POST /questoes/v1/data` | Headers: `X-WP-Nonce` válido, `Content-Type: application/json`.<br>Body: JSON completo conforme schema. | `{ "success": true, "data": { "title": "…" } }` | `400` → `{ "code": "questoes_invalid_json", "message": "JSON inválido: verifique vírgulas e chaves.", "data": { "field": "mindmap.children[0]" } }`.<br>`403` → `{ "code": "questoes_forbidden", "message": "Permissões insuficientes." }`.<br>`409` → `{ "code": "questoes_duplicate_id", "message": "IDs devem ser únicos.", "data": { "id": "estrategia" } }`.<br>`413` → `{ "code": "questoes_payload_too_large", "message": "Arquivo excede o limite permitido (1 MB)." }` |

#### Regras adicionais
- Limitar payload a 1 MB; exibir erro amigável quando excedido.
- Enviar cabeçalhos `Last-Modified` e `ETag` para facilitar caching.
- Logar tentativas inválidas apenas quando `WP_DEBUG` estiver ativo.
- Sanitizar `meta` removendo HTML/JS antes de persistir.

## Camadas Técnicas
- `class-renderer.php`: transforma JSON em estruturas de nós/arestas para a biblioteca gráfica.
- `class-frontend.php`: registra shortcode, enfileira assets e monta dados.
  - Integra comentários padrão do WordPress abaixo do componente quando habilitado nas configurações.
- `class-block.php`: registra bloco Gutenberg e define `render_callback`.
- `class-admin.php`: constrói página de configurações, valida entradas e expõe ações de exportação/importação.
- `class-accessibility.php`: provê atributos ARIA, gerenciamento de foco, descrições auxiliares e interações de teclado.
- `class-rest.php`: implementa rotas REST com autenticação, nonces e sanitização.
- `class-settings.php`: registra opções do plugin (`register_setting`) e lida com defaults.
- `schema.php`: define e valida o schema JSON (tamanho de rótulo, profundidade, campos obrigatórios).
- `helpers.php`: funções de utilidade (sanitização, normalização, cache leve, logging).

## Renderização e Performance
- Utilizar biblioteca de gráficos hierárquicos (SVG/canvas) com suporte a pan/zoom via `requestAnimationFrame`.
- Cache leve de nós, re-render parcial.
- Virtualização/lazy expand para conjuntos acima de ~500 nós.
- Respeitar `prefers-reduced-motion` diminuindo animações e desativando transições não essenciais.
- Usar `ResizeObserver` para reajustar viewport ao mudar container ou ao alternar abas.
- Disponibilizar `window.Questoes.refresh()` para re-renderizar após mudanças externas (ex.: AJAX, filtros).
- Disponibilizar `window.Questoes.getState()` retornando modo ativo, nível de zoom e IDs expandidos para diagnósticos e logs.
- Registrar métricas leves (tempo de parse, render e hidratação) no console quando `WP_DEBUG` estiver ativo.

## Segurança
- Sanitizar e validar JSON antes de salvar.
- Restringir salvamento a `manage_options`.
- Usar nonces em formulários e endpoints.
- Escapar saída ao renderizar.
- Aplicar `wp_verify_nonce` e `current_user_can` em todas as ações sensíveis.
- Armazenar dados via `update_option` com `autoload => false` para evitar carga desnecessária.
- Restringir uploads `.json` a usuários com `upload_files` e validar MIME/assinatura do arquivo.
- Desabilitar execução de HTML em campos de configuração usando `wp_kses_post` com lista vazia.
- Rotas REST devem retornar cabeçalhos `X-Content-Type-Options: nosniff` e `Content-Security-Policy: default-src 'none'` (ajustado pelo core).

## Internacionalização
- Text domain `questoes`.
- Envolver todas as strings com funções de tradução (`__`, `_e`, etc.).
- Gerar arquivos `.po/.mo` na pasta `languages/`.
- Incluir arquivo base `languages/questoes.pot`.
- Carregar traduções JS via `wp_set_script_translations` para assets enfileirados.

## Fluxos de Dados e Estados
### Pipeline de renderização
1. **Fonte de dados** — leitura conforme prioridade: conteúdo do shortcode/bloco → dados salvos nas opções → endpoint remoto configurado.
2. **Validação** — `schema.php` valida estrutura, profundidade, tamanho de rótulo e sanitiza `meta`.
3. **Normalização** — `helpers.php` ajusta chaves ausentes, assegura IDs únicos e aplica valores padrão (ex.: `children: []`).
4. **Transformação** — `class-renderer.php` gera estruturas consumidas pela biblioteca gráfica (ex.: listas de nós com coordenadas virtuais).
5. **Cache** — resultados são armazenados em `transients` para acelerar subsequentes renderizações.
6. **Renderização** — `class-frontend.php` injeta templates em `views/`, carrega assets e inicializa scripts de pan/zoom.
- **Comentários** — após renderização, `class-frontend.php` verifica flag de comentários e invoca `comments_template()` quando dados de respostas estão publicados.

### Estados do componente
- **Carregando**: spinner minimalista usando `#242142`, texto “Carregando visualização…”.
- **Vazio**: mensagem “Nenhum dado disponível. Configure no painel Questões.”.
- **Erro**: destaque em `#bf83ff` com texto “JSON inválido: verifique vírgulas e chaves.” e botão “Tentar novamente”.
- **Impressão**: classe `is-print` remove controles e ajusta margens, mantendo focos visíveis.
- **Modo Acessibilidade Ativa**: quando habilitado nas configurações, aumenta espaçamento entre nós e ativa resumo textual abaixo do gráfico.
- **Endpoint indisponível**: banner amarelo claro (gerado com `mix(#bf83ff, #fff, 20%)`) informando última sincronização bem-sucedida e opção de tentar novamente.
- **Comentários fechados**: aviso discreto abaixo do componente “Comentários encerrados para este conteúdo.” quando `comments_open` retornar `false`.

## Plano de Testes Funcionais
1. **Shortcode**: inserir cada modo (`mapa`, `organograma`, `ambos`) com JSON inline e validar renderização.
2. **Bloco Gutenberg**: testar troca de fonte (colar JSON, upload, endpoint) com pré-visualização e salvar/publicar.
3. **Configurações**: salvar título, alternar bloqueio de paleta, importar/exportar configurações e dados.
4. **REST API**: `GET` sem autenticação deve funcionar; `POST` exige nonce e capability. Validar mensagens de erro.
5. **Acessibilidade**: navegação por teclado entre nós, leituras por leitores de tela, foco visível.
6. **Modo Impressão**: acionar `Ctrl+P` ou comando de impressão e conferir layout limpo.
7. **Performance**: carregar dataset de ~500 nós e garantir fluidez de pan/zoom.
- **Comentários**: publicar respostas, habilitar flag no painel, validar exibição e comportamento de formulário padrão do WordPress.
- **Sincronização**: simular falha no endpoint remoto e garantir mensagens de aviso/documentação de última atualização.

## Checklist de QA
- [ ] Shortcode aceita JSON inline e renderiza corretamente.
- [ ] Bloco Gutenberg atualiza prévia ao alterar dados.
- [ ] Validação de JSON exibe mensagens amigáveis.
- [ ] Botões de zoom/reset/impressão respondem imediatamente.
- [ ] Acessibilidade (foco, ARIA, teclas) cumpre WCAG 2.1 AA.
- [ ] Impressão remove elementos de controle e mantém contraste.
- [ ] Exportação/Importação funciona com arquivos `.json`.
- [ ] REST POST bloqueia usuários sem permissão.
- [ ] Comentários aparecem conforme configuração quando respostas estiverem publicadas.
- [ ] `prefers-reduced-motion` respeitado.
- [ ] Respostas REST retornam códigos e payloads documentados.
- [ ] Banner de sincronização com endpoint exibe status correto e se oculta quando dados estão atualizados.
- [ ] Tooltips e ícones mantêm contraste e tempo de exibição dentro das normas de acessibilidade.

## Roadmap de Implementação
1. **Configurações base** — registrar opções, construir página admin, preparar esquema de validação, export/import.
2. **REST & Helpers** — implementar schema, sanitização, rotas REST com mensagens documentadas.
3. **Shortcode e bloco** — desenvolver renderização compartilhada e lógica de seleção de fonte de dados.
4. **Camada de renderização** — integrar biblioteca gráfica, responsividade e suporte a `prefers-reduced-motion`.
5. **Acessibilidade e comentários** — foco, ARIA, descrições textuais, interação por teclado e integração com comentários.
6. **Performance e cache** — otimizar pan/zoom, lazy expand, transients e `window.Questoes.refresh()`.
7. **QA completo** — testes manuais, impressão, performance, checklist WCAG e validação do modo impressão.
- **Observabilidade opcional** — integrar métricas no console, preparar hooks para logging externo quando necessário.

## Progresso Atual
- Especificação documental expandida e refinada em **115 %** (inclui aprimoramentos visuais, fluxos de comentários e monitoramento).
- Materiais prontos para handoff de desenvolvimento e QA.

## Critérios de Aceite (QA)
1. Shortcode e bloco funcionam nos três modos (mapa, organograma, ambos).
2. JSON inválido mostra mensagem sem quebrar layout.
3. Componente mantém contraste tanto em temas claros quanto escuros.
4. Impressão gera visual limpo (sem abas/botões) com contraste adequado.
5. Paleta respeitada (apenas `#242142`, `#bf83ff`, `#c3e3f3`, `#E5E7EB`, `#F1F5F9`).
6. Navegação por teclado completa e sem armadilhas.
7. Comentários habilitados para respostas (conforme requisitos adicionais).

## Exemplos de Dados
### Mindmap
```json
{
  "title": "Questões — Academia da Comunicação",
  "mindmap": {
    "id": "raiz",
    "label": "Comunicação",
    "children": [
      {
        "id": "estrategia",
        "label": "Estratégia",
        "children": [
          { "id": "publico", "label": "Público-alvo" },
          { "id": "mensagem", "label": "Mensagem-chave" }
        ]
      },
      {
        "id": "canais",
        "label": "Canais",
        "children": [
          { "id": "redes", "label": "Redes Sociais" },
          { "id": "email", "label": "Email Marketing" }
        ]
      }
    ]
  }
}
```

### Organograma
```json
{
  "orgchart": {
    "id": "diretoria",
    "label": "Diretoria",
    "children": [
      {
        "id": "coordenacao",
        "label": "Coordenação",
        "children": [
          { "id": "doc1", "label": "Docência 1" },
          { "id": "doc2", "label": "Docência 2" }
        ]
      }
    ]
  }
}
```

### Microcópias
- Abas: “Mapa Mental” | “Organograma”.
- Botões: “Zoom +”, “Zoom –”, “Centralizar”, “Imprimir”.
- Mensagens:
  - “Cole seu JSON no campo abaixo e clique em Testar.”
  - “JSON inválido: verifique vírgulas e chaves.”
  - “Dados salvos com sucesso.”

## Biblioteca de Questões (Frontend)
- Shortcode dedicado `[questoes_banco]` aceita atributos `titulo`, `mostrar_filtros`, `categoria`, `banca`, `dificuldade` e `por_pagina` (1 a 50).
- Renderização inicial SSR para SEO e acessibilidade, com hydration via JS utilizando `questoes/v1/questions`.
- Filtros responsivos com selects (categoria, banca, dificuldade) e botão “Aplicar filtros”.
- Paginação leve com botões anterior/próximo, status “Página X de Y” e bloqueio de navegação quando fora do intervalo.
- Cards de questão seguem a paleta: título, badge de dificuldade (#bf83ff), conteúdo, botão “Mostrar alternativas”, lista de alternativas acionável (letras A–E) com feedback individual e `<details>` para comentário do gabarito.
- Alternativas exibem o estado correto/aprendido apenas após o clique do usuário: o botão escolhido mostra se acertou ou errou, o badge “Correta” revela-se somente quando a interação ocorre e o comentário do gabarito é aberto automaticamente.
- Metadados exibidos em linha (categorias, bancas, referência, fonte, tempo estimado em minutos, link “Ver questão completa”).
- Mensageria acessível (`aria-live`) para estados de carregamento (“Carregando questões…”), erro (“Não foi possível carregar as questões…”) e vazio (“Nenhuma questão encontrada.”).
- Widget Elementor “Questões – Banco” com controles para título, filtros (on/off), slugs de categoria/banca, dificuldade e quantidade de itens.
- Frontend respeita contraste 4.5:1, outline visível em focus, tecla Enter ativa toggles, conteúdo pronto para impressão (sem filtros/botões).

## Vitrine de Disciplinas
- Shortcode `[questoes_disciplinas]` apresenta painel com filtros (“Palavra-chave”, “Área de Formação”) inspirados no layout referencial, inicialmente exibindo o formulário, banner opcional (conteúdo entre as tags) e a tabela de disciplinas.
- Cada linha mostra nome da disciplina, área mãe, total de questões publicadas e número de questões com comentários aprovados; botão “Ver questões” linka para o arquivo da categoria.
- Contadores agregados (“%1$s disciplinas…”, “%s questões ao todo.”) são atualizados pela camada JS conforme filtros; mensagens vazias informam quando nenhum resultado corresponde aos critérios.
- Template aplica a paleta oficial (#242142 primário, #bf83ff secundário, #c3e3f3 destaques) com grid responsivo (4 colunas desktop → lista empilhada <=900px), campos arredondados (12–16px) e outlines acessíveis.
- Shortcode aceita atributos `titulo`, `descricao` e `mostrar_vazias` (`sim|nao`, padrão `nao`), permitindo personalizar título/descricao exibidos acima do filtro; conteúdo entre as tags é renderizado como destaque (por exemplo, banner).

## Comentários e Feedback
- Permitir que usuários comentem diretamente em cada questão, exibindo um `<details>` “Comentários” com contador dinâmico (“Comentários (n)”) quando houver interações aprovadas.
- Publicar lista de comentários aprovados em cartão com bordas neutras, seguida do formulário WordPress nativo estilizado (inputs arredondados, botão primário `#242142`/hover em roxo secundário).
- Garantir que comentários respeitem capabilities padrão do WordPress; quando desativados nas configurações, esconder a seção nos cards.

