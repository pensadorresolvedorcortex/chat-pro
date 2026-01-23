# Woodmart Design System real (Tema Padrão 3.0)

## Padrões reais já implementados
- **Paleta base:**
  - Fundo: `#f7f2ec`
  - Texto principal: `#2a2420`
  - Texto muted: `#6a5d54`
  - Accent: `#8b6a4d`
  - Accent hover: `#6d523d`
  - Rating: `#d9a441`
- **Raios de borda:** `14px`, `16px`, `18px`, `20px`, `24px`, `32px`, `999px`.
- **Containers:** `max-width: 1200px` com `padding: 0 24px`.
- **Seções:** `padding: 80px 0` (desktop) e `60px 0` (mobile).

## Regras de tipografia
- Títulos grandes: `clamp(32px–56px)`, `font-weight: 600`, `line-height: 1.1`.
- Títulos médios: `clamp(28px–44px)`, `font-weight: 600`, `line-height: 1.1–1.2`.
- Texto corrido e subtítulos: `15px–16px`, `line-height: 1.6–1.7`.
- Eyebrow/legendas: `12px–13px` com `letter-spacing: 0.1em–0.2em` e `uppercase`.

## Regras de espaçamento
- Gaps principais: `10px`, `12px`, `16px`, `18px`, `20px`, `24px`, `28px`, `32px`, `36px`, `40px`, `48px`.
- Grid de cards: `gap` entre `20px–28px` (desktop) e `16px–20px` (mobile).

## Regras de microinterações
- Transições suaves entre `0.2s` e `0.4s`.
- Elevação leve: `translateY(-2px)` a `translateY(-4px)`.
- Zoom de imagem: `scale(1.04–1.05)`.
- Sombras de hover entre `0 16px 30px` e `0 26px 44px` (rgba 0.18–0.22).

## Padrão de hover/foco
- Hover de CTA: troca de background `#8b6a4d` → `#6d523d`.
- Foco acessível: `outline: 2px solid rgba(139, 106, 77, 0.6)` com `outline-offset: 3px`.

## Como criar novos blocos sem quebrar o sistema
- Criar prefixo próprio no formato `novo-woodmart__*`.
- Definir variáveis locais no seletor raiz do bloco (`--novo-woodmart-*`).
- Manter o mesmo ritmo de espaçamento e tipografia do hero.
- Separar CSS base e microinterações em arquivos distintos.
- Usar apenas seletores já presentes no HTML do bloco.

## O que é expressamente proibido
- Alterar `hero-woodmart.html` ou `hero-woodmart.css`.
- Modificar `body` ou `html`.
- Usar seletor universal (`*`).
- Renomear classes existentes.
- Remover código existente.
- Sobrescrever regras existentes (somente CSS complementar).
- Introduzir JavaScript ou PHP.
