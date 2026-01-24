# Mapa de dependência entre blocos

## Blocos existentes e CSS associado

- **Hero**
  - Base: `cpapjf/hero-woodmart.css`
  - Micro: `cpapjf/hero-woodmart-micro.css`

- **Categorias**
  - Base: `cpapjf/cat-woodmart.css`
  - Micro: `cpapjf/cat-woodmart-micro.css`

- **Página de categoria**
  - Base: `cpapjf/cat-page-woodmart.css`
  - Micro: `cpapjf/cat-page-woodmart-micro.css`

- **Produtos**
  - Base: `cpapjf/prod-woodmart.css`
  - Micro: `cpapjf/prod-woodmart-micro.css`

- **Página de produto**
  - Base: `cpapjf/prod-page-woodmart.css`
  - Micro: `cpapjf/prod-page-woodmart-micro.css`

- **Coleções**
  - Base: `cpapjf/coll-woodmart.css`
  - Micro: `cpapjf/coll-woodmart-micro.css`

- **Marcas**
  - Base: `cpapjf/brand-woodmart.css`
  - Micro: `cpapjf/brand-woodmart-micro.css`

- **Institucional**
  - Base: `cpapjf/inst-woodmart.css`
  - Micro: `cpapjf/inst-woodmart-micro.css`

- **Globais**
  - Base: `cpapjf/wm-global.css`
  - Micro: `cpapjf/wm-micro.css`

## Dependências implícitas
- Todo arquivo `-micro.css` depende do CSS base do mesmo bloco.
- `wm-micro.css` depende de todos os CSS base e micros anteriores.
- `wm-global.css` deve ser carregado antes dos blocos para utilitários e consistência.

## Ordem lógica de carregamento por bloco
1. `cpapjf/wm-global.css`
2. CSS base por bloco (na ordem do layout da página)
3. CSS micro por bloco (na mesma ordem)
4. `cpapjf/wm-micro.css`
