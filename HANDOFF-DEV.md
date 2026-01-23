# Guia de handoff para desenvolvimento

## Como entender rapidamente o projeto
- Leia `INVENTARIO-PROJETO.md` para identificar blocos, prefixos e arquivos CSS.
- Leia `CSS-LOAD-ORDER.md` para a ordem oficial de carregamento dos estilos.
- Consulte `WOODMART-DESIGN-SYSTEM.md` para regras de tipografia, espaçamento e microinterações.

## Onde NÃO mexer
- `hero-woodmart.html` e `hero-woodmart.css` (padrão visual base).
- `body` e `html`.
- Seletores universais (`*`).
- Classes existentes e estruturas HTML dos blocos já implementados.

## Onde pode mexer com segurança
- Variáveis locais dentro do seletor raiz de cada bloco (`--*-woodmart-*`).
- Ajustes de `padding`/`gap` dentro dos próprios blocos.
- Conteúdo textual dentro dos HTMLs dos blocos.

## Como criar novo bloco seguindo o padrão
1. Criar prefixo no formato `novo-woodmart__*`.
2. Definir variáveis locais no seletor raiz do bloco.
3. Criar CSS base e arquivo `-micro.css` dedicado.
4. Manter a tipografia e espaçamento alinhados ao hero.
5. Inserir o CSS no load order após `wm-global.css` e antes do `wm-micro.css`.

## Como depurar problemas visuais
- Confirmar a ordem de carregamento dos CSS.
- Verificar se `.wm-global-scope` está no container correto.
- Checar se os arquivos `-micro.css` estão após seus bases.
- Validar se não há regras globais fora do escopo.

## Erros comuns e como evitar
- Carregar micro antes do base → sempre manter a ordem base → micro.
- Alterar o hero → não modificar o padrão visual base.
- Usar seletor global → manter escopo por bloco.
- Sobrescrever regras existentes → apenas complementar.
