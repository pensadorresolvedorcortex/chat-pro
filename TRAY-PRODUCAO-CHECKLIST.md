# Checklist final de produção (Tray)

## Checklist pré-publicação
- [ ] Verificar a ordem correta de inclusão dos CSS no editor da Tray.
- [ ] Garantir que `wm-global.css` está carregado antes dos blocos.
- [ ] Garantir que cada `-micro.css` está após o base do mesmo bloco.
- [ ] Confirmar aplicação do `.wm-global-scope` no container correto.
- [ ] Validar que não houve alteração em `body` ou `html`.

## Checklist de regressão visual
- [ ] Conferir espaçamentos verticais entre seções (80px/60px).
- [ ] Validar consistência de sombras em cards e mídias.
- [ ] Verificar comportamento de hover em CTAs, cards e imagens.
- [ ] Conferir alinhamentos dos grids (desktop e mobile).

## Checklist de responsividade
- [ ] `max-width: 1024px`: grids reduzem colunas e layouts em duas colunas viram uma.
- [ ] `max-width: 768px`: padding de seção reduzido para 60px.
- [ ] `max-width: 768px`: grids de produtos/categorias/marcas com 2 colunas.
- [ ] Toolbar de categoria empilha corretamente em telas menores.

## Checklist de acessibilidade
- [ ] Foco visível nos CTAs e cards clicáveis.
- [ ] Contraste adequado em textos sobre imagens (overlays ativos).
- [ ] Legibilidade dos textos pequenos (12px/13px).

## Checklist de manutenção futura
- [ ] Manter prefixos de classe por bloco (`*-woodmart`).
- [ ] Preservar variáveis locais de cada bloco.
- [ ] Separar CSS base e microinterações em arquivos distintos.
- [ ] Evitar dependências de estilos globais não documentados.
