# Academia da Comunicação – Workspace

Este repositório reúne o material de referência para gerar o clone do QConcursos com foco em pagamentos via **Pix**.

## Estrutura

- `docs/` – Mega resumo, contrato OpenAPI 3.0.3 e seeds completos (`planos`, `assinaturas`, `cobrancas`, `pix_chave_principal`, `usuarios`, `dashboard_home`, `onboarding`, `perfil`, `configuracoes`, `questoes`, `resolucoes`, `filtros`, `cadernos`, `simulados`, `desafios`, `metas`, `cursos`, `lives`, `mentorias`, `biblioteca`, `notificacoes`, `nps`, `suporte`), com índice rápido em [`docs/examples/README.md`](docs/examples/README.md).
- `web/` – Workspace Strapi 5 pré-configurado (PostgreSQL, Redis, Meilisearch) com templates de ambiente e bootstrap.
- `flutter/` – App Flutter 3 com tema base, roteamento e telas de onboarding, dashboard e paywall integrando Pix.

## Como usar

1. Leia o [`docs/mega-resumo-codex.md`](docs/mega-resumo-codex.md) para entender escopo, stack e fluxos.
2. Consulte o contrato [`docs/openapi.yaml`](docs/openapi.yaml) e o catálogo [`docs/examples/README.md`](docs/examples/README.md) para navegar pelos JSONs de exemplo.
3. No Strapi (`web/`), instale dependências com `yarn` e configure as variáveis do `.env.example`.
4. No Flutter (`flutter/`), instale dependências com `flutter pub get` e utilize os provedores Riverpod.
5. Siga os checklists finais em [`docs/finalizacao-app.md`](docs/finalizacao-app.md) e o [runbook de lançamento](docs/release-runbook.md) para liberar o APK.
6. Revise o [relatório de melhorias](docs/improvement-audit.md) para acompanhar próximas ações no frontend e backend.
7. Rode `scripts/validate_sync.py` para garantir que os exemplos do app e do CMS continuam apontando para os mesmos usuários, planos e cobranças.

## Status

O metadado `x-projectStatus` no OpenAPI indica **45 %** de prontidão para o build de APK Android. Ainda faltam implementar content-types Strapi, fluxos Pix server-side e conectar as telas Flutter além dos mockups; acompanhe as pendências nos documentos de melhoria e finalização.
