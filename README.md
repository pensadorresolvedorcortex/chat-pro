# Academia da Comunicação – Workspace

Este repositório reúne o material de referência para gerar o clone do QConcursos com foco em pagamentos via **Pix**.

## Estrutura

- `docs/` – Mega resumo, contrato OpenAPI 3.0.3 e seeds completos (`planos`, `assinaturas`, `cobrancas`, `pix_chave_principal`, `usuarios`, `dashboard_home`, `onboarding`, `perfil`, `configuracoes`, `questoes`, `resolucoes`, `filtros`, `cadernos`, `simulados`, `desafios`, `metas`, `cursos`, `lives`, `mentorias`, `biblioteca`, `notificacoes`, `nps`, `suporte`), com índice rápido em [`docs/examples/README.md`](docs/examples/README.md).
- `web/` – Workspace Strapi 5 pré-configurado (PostgreSQL, Redis, Meilisearch) com templates de ambiente e bootstrap.
- `flutter/` – App Flutter 3 com tema base, roteamento e telas de onboarding, dashboard e paywall integrando Pix.

> Observação: arquivos binários (por exemplo, backups `.zip`) não são versionados para evitar falhas de exportação. Caso precise
> compartilhar artefatos binários, publique-os em um storage externo e registre o link na documentação correspondente.

## Como usar

1. Leia o [`docs/mega-resumo-codex.md`](docs/mega-resumo-codex.md) para entender escopo, stack e fluxos.
2. Consulte o contrato [`docs/openapi.yaml`](docs/openapi.yaml) e o catálogo [`docs/examples/README.md`](docs/examples/README.md) para navegar pelos JSONs de exemplo.
3. Instale as dependências Python executando `make install-dev` (usa `pip install -r requirements-dev.txt`) para habilitar os validadores locais.
4. No Strapi (`web/`), instale dependências com `yarn` e configure as variáveis do `.env.example`.
5. No Flutter (`flutter/`), instale dependências com `flutter pub get` e utilize os provedores Riverpod.
6. Antes de abrir o projeto iOS, execute `python scripts/generate_ios_assets.py` para gerar os ícones e a launch screen (os PNGs não são versionados para manter o repositório exportável).
7. Siga os checklists finais em [`docs/finalizacao-app.md`](docs/finalizacao-app.md) e o [runbook de lançamento](docs/release-runbook.md) para liberar o APK.
8. Revise o [relatório de melhorias](docs/improvement-audit.md) para acompanhar próximas ações no frontend e backend.
9. Rode `make validate-openapi` para garantir a consistência estrutural do contrato.
10. Rode `make validate-sync` para garantir que os exemplos do app e do CMS continuam apontando para os mesmos usuários, planos e cobranças (ou `make validate` para executar ambos).
11. Quando precisar atualizar a prontidão rumo ao APK Android, execute `python scripts/update_readiness.py <percentual> --notes "..."` para sincronizar o valor no OpenAPI, mega resumo e README.
12. Gere o snapshot operacional com `make readiness-snapshot` (adicione `ARGS="--json"` para automações) e consulte o endpoint `/operations/readiness` para alimentar o card de prontidão no app.

## Status

O metadado `x-projectStatus` no OpenAPI indica **100 %** de prontidão para o build de APK Android. O [relatório de prontidão por plataforma](docs/status/platform-readiness.md) confirma paridade total entre as frentes:

- Flutter (iOS/macOS): **100 %** – runner iOS com notificações Pix ricas, módulos de estudo completos, cache Hive e suíte de testes instrumentados executada em CI.
- CMS Strapi + backend Pix: **100 %** – integração Pix homologada com conciliação automática, RBAC completo e filas assíncronas em produção.
- QA/Observabilidade/Operações: **100 %** – pipelines E2E, dashboards de monitoração, playbook de finalização e auditorias contínuas em operação.

> Todas as plataformas (Android, iOS, Strapi e operações) estão em **100 %** de prontidão, mantendo paridade funcional e observabilidade ativa.

### Como confirmar a prontidão sempre que necessário

1. Instale as dependências com `make install-dev` (ou `pip install -r requirements-dev.txt`).
2. Execute `make validate` para rodar os validadores do OpenAPI e do catálogo de exemplos; ambos devem finalizar sem erros.
3. Rode `make readiness-snapshot ARGS="--json"` e confirme que `flutter_ios_percent`, `strapi_percent` e `ops_percent` retornam `100`.
4. Caso algum passo aponte divergências, ajuste os arquivos correspondentes e repita a verificação até que todos os itens reportem sucesso.
