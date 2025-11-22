# Juntaplay Next

Experiência totalmente reformulada da Juntaplay em React + Vite, com foco em impacto visual, IA assistiva e performance. O front entrega glassmorphism coerente com a paleta original, animações leves e uma arquitetura enxuta para facilitar manutenção.

## Scripts
- `npm install` – instala dependências
- `npm run dev` – inicia ambiente de desenvolvimento
- `npm run build` – gera build otimizado
- `npm run preview` – visualiza build
- `npm run lint` – checa tipos

## Estrutura
- **src/components** – blocos reutilizáveis (hero, painel de IA, grade de serviços, timeline/progresso, destaques e navegação)
- **src/data** – fontes estáticas como catálogos de serviços e sugestões inteligentes
- **public/services** – ícones SVG usados na grade dinâmica

## Destaques de UX
- Layout responsivo com hierarquias visuais fortes
- Glassmorphism claro, com camadas translucidas e bordas suaves
- Microinterações com Framer Motion para foco/hover
- Painel de IA com autocomplete e respostas prontas
- Fluxos guiados e CTA com contraste alto para reduzir atrito

## Design & Performance
- Paleta moderna em gradientes, sombras suaves e tipografia elegante
- Tokens de espaçamento e raios consistentes para manter harmonia visual
- Lazy loading para assets estáticos e build otimizado via Vite
- Componentes separados por responsabilidade para facilitar evolução

## Como contribuir
1) Faça o fork e crie um branch descritivo
2) Rode `npm run lint` e `npm run build` antes do commit
3) Descreva alterações no PR incluindo pontos de UX e README quando houver ajustes
