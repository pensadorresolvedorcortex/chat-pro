
# 🧠 Mapa Mental – Sistema de Sorteio de Jogadores com Estilo Moderno

## 1. 🎯 Objetivo Geral
- Criar um site para **sortear 3 times equilibrados automaticamente**
- Divisão com base em:
  - 📌 Posição do jogador
  - 🪨 Qualidade (Pedra 1, 2, 3)
- Usar base com **17 jogadores predefinidos e editáveis**
- Estilo visual semelhante ao site: https://wgl-dsites.net/poity/

## 2. 📊 Estrutura de Dados e Interface de Entrada
### 🗂️ Tabela Principal (Visual)
| Nº | Nome do Jogador | Posição | Pedra |
|----|------------------|---------|--------|
| 1  | Dheniell         | Goleiro | Pedra 1 |
| ...| ...              | ...     | ...     |

Edição interativa:
- ✅ Nome → campo de texto
- ✅ Posição → dropdown
- ✅ Pedra → opções visuais

17 jogadores predefinidos:  
Dheniell, Dario, Papel, Wallace, Matheus, Kloh, Bebeto, Custela, Diego, Matheus MP, Gabriel, Bolo, Geisel, Caputo, Fred, Darlan, Baiano

## 3. ⚙️ Parâmetros do Sorteio
- 3 times com 5 jogadores cada
- Apenas 2 goleiros
- Distribuição:
  - 1 Goleiro (em 2 dos 3 times)
  - 1 Fixo
  - 1 LD
  - 1 LE
  - 1 Meia ou Pivô

## 4. 🧮 Lógica do Sorteio
- Agrupar jogadores por posição
- Classificar por pedra
- Balancear aleatoriamente:
  - 1 jogador por posição
  - Equilíbrio de Pedras

## 5. 🖥️ Interface do Usuário (UX/UI)
### Área Principal
- Tabela com colunas fixas
- Inputs editáveis
- Validação visual por cor

### Botões Funcionais
- 🎲 Sortear Times
- 🔄 Resetar Tabela
- 💾 Salvar Times
- ⏪ Resetar Times Sorteados

### Resultado do Sorteio
- Exibição dos 3 times
- Exportação em PDF

## 6. 🎨 Design System (Estilo Poity)
- Paleta suave
- Fonte: Outfit, Plus Jakarta Sans
- Componentes: botões, cards, inputs
- Responsivo: mobile-first

## 7. ⚙️ Componentização (React ou Next.js)
Estrutura:
- /components (InputJogador, DropdownPosicao, etc.)
- /pages, /hooks, /styles, /assets

## 8. 🔐 Validações e Segurança
- 17 jogadores
- 2 goleiros
- Campos preenchidos
- Sanitização e localStorage

## 9. 🔄 Fluxo do Usuário
[Início] → Edita → Sortear → Resultado  
         ↳ Resetar / Salvar / Limpar Times

## 10. 🔒 Acessibilidade
- Navegação por teclado
- Aria-labels
- Contraste garantido
- Feedback visual

## ✅ Prompt Codex Sugerido
Crie uma aplicação web inspirada no visual do site https://wgl-dsites.net/poity/, usando React + Tailwind ou styled-components. Deve conter uma tabela com 17 jogadores predefinidos e editáveis (nome, posição, pedra), botões para sortear, resetar e salvar times. A interface deve ser minimalista, responsiva, acessível e com animações suaves. O sorteio deve balancear posições e qualidade (Pedras). Estrutura modular e reutilizável com lógica clara de sorteio.
