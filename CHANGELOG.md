# Changelog final — Woodmart-like (Tray Tema Padrão 3.0)

## Resumo do que foi construído
- Home completa com hero, categorias, coleções, produtos, marcas e institucional.
- Página de categoria com sidebar, toolbar e grid de produtos.
- Página de produto com galeria, opções, preços e CTAs.
- Camada de microinterações por bloco e microinterações globais.
- Documentação completa de inventário, load order, design system e checklists.

## Principais decisões técnicas
- CSS isolado por bloco com prefixo `*-woodmart`.
- Variáveis locais por bloco para consistência de cores e sombras.
- Separação entre CSS base e CSS micro.
- Utilitários globais restritos ao escopo `.wm-global-scope`.

## Estrutura final dos blocos
- Hero (`hero-woodmart`)
- Categorias (`cat-woodmart`)
- Coleções (`coll-woodmart`)
- Produtos (`prod-woodmart`)
- Marcas (`brand-woodmart`)
- Institucional (`inst-woodmart`)
- Página de categoria (`cat-page-woodmart`)
- Página de produto (`prod-page-woodmart`)

## Observações sobre compatibilidade Tray
- Tema Padrão 3.0 com CSS isolado e sem dependência de JS.
- Ordem de carregamento crítica para microinterações.
- Sem alterações em `body` ou `html`.

## Estado final do projeto
- **Stable**: layout finalizado, microinterações aplicadas e documentação concluída.
