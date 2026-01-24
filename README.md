# Projeto Woodmart-like (Tray Tema Padrão 3.0)

## Visão geral do projeto
Projeto front-end finalizado para a plataforma Tray (Tema Padrão 3.0) com layout Woodmart-like, incluindo Home, página de categoria e página de produto, além de microinterações e documentação completa.

## Objetivo
Entregar um visual Woodmart-like na Tray com CSS isolado por bloco, microinterações suaves e documentação de hardening para manutenção segura.

## Estrutura geral do código
- **HTML**: blocos isolados por prefixo `*-woodmart`.
- **CSS base**: estrutura visual de cada bloco.
- **CSS micro**: microinterações (hover/foco/transições) por bloco.
- **CSS global**: utilitários e microinterações compartilhadas sob escopo `.wm-global-scope`.

## Tipos de arquivos CSS
- **Base**: define layout, tipografia, grids, espaçamentos e cores por bloco.
- **Micro**: define transições, hover e foco por bloco.
- **Global**: utilitários e microinterações compartilhadas (`wm-global.css` e `wm-micro.css`).

## Como instalar/aplicar na Tray
1. Inserir `wm-global.css` no editor de CSS da Tray antes dos blocos.
2. Inserir os CSS base de cada bloco na ordem do layout.
3. Inserir os CSS micro de cada bloco na mesma ordem.
4. Inserir `wm-micro.css` por último.
5. Garantir que o container principal da Home tenha a classe `.wm-global-scope`.

## Ordem correta de colagem dos CSS
1. `cpapjf/wm-global.css`
2. `cpapjf/hero-woodmart.css`
3. `cpapjf/cat-woodmart.css`
4. `cpapjf/coll-woodmart.css`
5. `cpapjf/prod-woodmart.css`
6. `cpapjf/brand-woodmart.css`
7. `cpapjf/inst-woodmart.css`
8. `cpapjf/cat-page-woodmart.css`
9. `cpapjf/prod-page-woodmart.css`
10. `cpapjf/hero-woodmart-micro.css`
11. `cpapjf/cat-woodmart-micro.css`
12. `cpapjf/coll-woodmart-micro.css`
13. `cpapjf/prod-woodmart-micro.css`
14. `cpapjf/brand-woodmart-micro.css`
15. `cpapjf/inst-woodmart-micro.css`
16. `cpapjf/cat-page-woodmart-micro.css`
17. `cpapjf/prod-page-woodmart-micro.css`
18. `cpapjf/wm-micro.css`

## Regras de manutenção
- Ajustar cores apenas via variáveis locais de cada bloco (`--*-woodmart-*`).
- Manter a tipografia e espaçamentos definidos pelo hero.
- Separar CSS base e micro por bloco.
- Preservar o escopo `.wm-global-scope` para utilitários.

## Avisos importantes (o que NÃO fazer)
- Não alterar `body` ou `html`.
- Não usar seletor universal (`*`).
- Não renomear classes existentes.
- Não remover código existente.
- Não introduzir JavaScript ou PHP.
- Não sobrescrever regras existentes (apenas CSS complementar).
