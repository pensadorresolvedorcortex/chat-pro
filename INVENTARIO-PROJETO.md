# Inventário final do projeto (Tema Padrão 3.0)

## Blocos existentes e prefixos de classes

- **Hero**
  - Prefixo: `hero-woodmart`
  - HTML: `cpapjf/hero-woodmart.html`

- **Categorias (bloco de categorias)**
  - Prefixo: `cat-woodmart`
  - HTML: `cpapjf/cat-woodmart.html`

- **Página de categoria (layout + cards)**
  - Prefixo: `cat-page-woodmart`
  - HTML: `cpapjf/cat-page-woodmart.html`

- **Produtos (grid home)**
  - Prefixo: `prod-woodmart`
  - HTML: `cpapjf/prod-woodmart.html`

- **Página de produto (topo do produto)**
  - Prefixo: `prod-page-woodmart`
  - HTML: `cpapjf/prod-page-woodmart.html`

- **Coleções (mosaico editorial)**
  - Prefixo: `coll-woodmart`
  - HTML: `cpapjf/coll-woodmart.html`

- **Marcas (grid de logos)**
  - Prefixo: `brand-woodmart`
  - HTML: `cpapjf/brand-woodmart.html`

- **Institucional (conteúdo + mídia)**
  - Prefixo: `inst-woodmart`
  - HTML: `cpapjf/inst-woodmart.html`

- **Camada global (utilitários e microinterações)**
  - Escopo: `.wm-global-scope`
  - Prefixos: `wm-global-` e `wm-micro-`

## Arquivos CSS base existentes (finalidade)

- `cpapjf/wm-global.css` — utilitários globais: tipografia, espaçamento, botões e ajustes finos sob `.wm-global-scope`.
- `cpapjf/hero-woodmart.css` — estrutura visual do hero (tipografia, layout, frame e formas decorativas).
- `cpapjf/cat-woodmart.css` — grid de categorias em círculos, overlay, ring e rótulos.
- `cpapjf/cat-page-woodmart.css` — layout da página de categoria: sidebar, filtros, toolbar e cards.
- `cpapjf/prod-woodmart.css` — grid de produtos, cards, imagem, preços e CTA.
- `cpapjf/prod-page-woodmart.css` — topo da página de produto: galeria, opções, preços e CTAs.
- `cpapjf/coll-woodmart.css` — mosaico editorial com cards grandes/médios/pequenos.
- `cpapjf/brand-woodmart.css` — grid de marcas e cards com logos.
- `cpapjf/inst-woodmart.css` — bloco institucional com texto, lista e mídia.

## Arquivos CSS de microinterações existentes (finalidade)

- `cpapjf/wm-micro.css` — microinterações compartilhadas (hover/foco) para botões, cards e CTAs.
- `cpapjf/hero-woodmart-micro.css` — transições e foco do CTA, realce do frame e shapes.
- `cpapjf/cat-woodmart-micro.css` — hover/foco dos itens de categoria e rótulos.
- `cpapjf/cat-page-woodmart-micro.css` — hover dos cards e filtros, foco acessível.
- `cpapjf/prod-woodmart-micro.css` — hover nos cards, zoom da imagem e CTA.
- `cpapjf/prod-page-woodmart-micro.css` — microinterações do CTA e galeria.
- `cpapjf/coll-woodmart-micro.css` — hover no mosaico e revelação do CTA.
- `cpapjf/brand-woodmart-micro.css` — hover nos cards e realce do logo.
- `cpapjf/inst-woodmart-micro.css` — hover na mídia e foco do CTA.

## O que NÃO deve ser alterado

- `hero-woodmart.html` e `hero-woodmart.css` (definem o padrão visual do projeto).
- Classes existentes e seus nomes.
- Estruturas HTML dos blocos já implementados.
- Qualquer regra aplicada a `body` ou `html`.
- Qualquer uso de seletor universal (`*`).
- Qualquer sobrescrita de regras existentes (novos CSS apenas complementares).
