# Plano de manutenção futura

## Como alterar cores sem quebrar o padrão
- Alterar variáveis locais dentro do seletor raiz de cada bloco (`--*-woodmart-*`).
- Manter contraste entre texto principal e fundo.
- Evitar alterar cores diretamente em seletores fora das variáveis locais.

## Como ajustar espaçamentos com segurança
- Ajustar `padding` apenas nos seletores de bloco (`*.woodmart` e seus wrappers).
- Preservar `max-width: 1200px` e `padding: 0 24px` dos containers.
- Manter a escala de gaps entre `16px` e `48px` conforme o design system.

## Como criar novo bloco seguindo o design system
- Criar prefixo exclusivo no formato `novo-woodmart__*`.
- Definir variáveis locais no bloco para cores, sombras e fundos.
- Criar CSS base e arquivo `-micro.css` dedicado.
- Respeitar tipografia e espaçamentos definidos pelo hero.

## Como remover um bloco sem causar regressão
- Remover o HTML do bloco e seus CSS base/micro correspondentes.
- Garantir que nenhuma dependência global (`wm-global`/`wm-micro`) esteja usando seletores do bloco removido.
- Validar a ordem de carregamento após remoção.

## Erros comuns que devem ser evitados
- Carregar micro antes do base.
- Introduzir regras globais fora de `.wm-global-scope`.
- Alterar `hero-woodmart.html` ou `hero-woodmart.css`.
- Criar novas classes estruturais sem prefixo.
- Sobrescrever regras existentes em vez de complementar.
