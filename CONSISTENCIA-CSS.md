# Consistência de CSS (Tema Padrão 3.0)

## Lista de arquivos CSS
- `cpapjf/wm-global.css`
- `cpapjf/wm-micro.css`
- `cpapjf/hero-woodmart.css`
- `cpapjf/hero-woodmart-micro.css`
- `cpapjf/cat-woodmart.css`
- `cpapjf/cat-woodmart-micro.css`
- `cpapjf/cat-page-woodmart.css`
- `cpapjf/cat-page-woodmart-micro.css`
- `cpapjf/prod-woodmart.css`
- `cpapjf/prod-woodmart-micro.css`
- `cpapjf/prod-page-woodmart.css`
- `cpapjf/prod-page-woodmart-micro.css`
- `cpapjf/coll-woodmart.css`
- `cpapjf/coll-woodmart-micro.css`
- `cpapjf/brand-woodmart.css`
- `cpapjf/brand-woodmart-micro.css`
- `cpapjf/inst-woodmart.css`
- `cpapjf/inst-woodmart-micro.css`

## Verificação

### Sobreposição indevida
- Não há uso de seletor universal.
- Não há regras globais fora do escopo `.wm-global-scope` no `wm-global.css`.
- Os blocos usam prefixos próprios, reduzindo colisões entre seções.

### Duplicação de regra
- Há repetição intencional de transições e hovers entre arquivos `-micro.css` e `wm-micro.css`.
- A duplicação é aceitável porque reforça o padrão de microinterações sem alterar layout.

### Conflitos de hover/focus
- `wm-micro.css` aplica hovers e focos aos mesmos seletores de blocos.
- Como ele é carregado por último, pode sobrescrever valores de hover definidos nos `-micro.css`.
- Os valores são compatíveis (mesmo tipo de efeito), evitando divergência visual grave.

## Pontos seguros
- Escopo por prefixo de bloco (`*-woodmart`) reduz conflitos.
- `wm-global.css` está restrito ao escopo `.wm-global-scope`.
- Estruturas e layouts permanecem no CSS base, microinterações ficam em arquivos separados.

## Pontos de atenção (sem alterar código)
- Manter a ordem de carregamento com `wm-micro.css` por último para evitar perdas de microinterações.
- Evitar adicionar novas regras globais fora do escopo `wm-global-scope`.
- Conferir consistência de hover/foco caso novos micro CSS sejam adicionados.
