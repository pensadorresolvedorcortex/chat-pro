# Oportunidades de melhoria

## Frontend (Flutter)

1. ~~**Substituir dados mockados por providers reais no dashboard**~~
   ✅ Resolvido: o dashboard agora consome `dashboardProvider` que faz fetch de `/dashboard/home` com fallback local (`dashboard_repository.dart`), eliminando os mocks estáticos.
   _Referência:_ `flutter/lib/features/dashboard/presentation/dashboard_page.dart`; `flutter/lib/features/dashboard/data/dashboard_repository.dart`.

2. ~~**Tornar os atalhos do dashboard navegáveis**~~
   ✅ Resolvido: `_QuickActionCard` usa `context.push` para as rotas declaradas no `GoRouter`, que agora inclui placeholders navegáveis para metas, questões, ranking e biblioteca.
   _Referência:_ `flutter/lib/features/dashboard/presentation/dashboard_page.dart`; `flutter/lib/app.dart`.

3. ~~**Preencher os destaques de alunos com dados reais**~~
   ✅ Resolvido: os destaques são carregados a partir do payload do `/dashboard/home`, com mensagens vazias e banner de fallback quando necessário.
   _Referência:_ `flutter/lib/features/dashboard/presentation/dashboard_page.dart`; `flutter/lib/features/dashboard/data/dashboard_models.dart`.

4. ~~**Garantir que o refresh conclua corretamente**~~
   ✅ Resolvido: o `RefreshIndicator` agora aguarda explicitamente o `Future` retornado por `ref.refresh(plansProvider.future)` via função `async`, garantindo que o indicador seja encerrado ao término da recarga.
   _Referência:_ `flutter/lib/features/paywall/presentation/paywall_page.dart`, linhas 36-45.

5. ~~**Persistir o fallback remoto quando disponível**~~
   ✅ Resolvido: o `HybridPlanRepository` persiste a resposta remota via `PlanCacheStore` (Hive), reutilizando o snapshot quando a API está fora do ar e eliminando refresh redundante.
   _Referência:_ `flutter/lib/features/paywall/data/plan_repository.dart`; `flutter/lib/features/paywall/data/plan_cache_store.dart`; `flutter/lib/main.dart`.

6. ~~**Evitar recriar formatadores pesados por build**~~
   ✅ Resolvido: `PlanCard` usa instâncias `static final` de `NumberFormat` e `DateFormat`, reduzindo a alocação em listas longas.
   _Referência:_ `flutter/lib/features/paywall/presentation/widgets/plan_card.dart`, linhas 12-40.

7. ~~**Tratar payloads Strapi com envelope `attributes`**~~
   ✅ Resolvido: o `PlanRepository` normaliza itens retornados pelo Strapi, achatando `attributes` e extraindo payloads Pix aninhados antes do parse para `Plan`.
   _Referência:_ `flutter/lib/features/paywall/data/plan_repository.dart`, linhas 170-240.

8. ~~**Injetar o token de autenticação real no Dio**~~
   ✅ Resolvido: o `authTokenProvider` agora delega para `AuthTokenController`, que persiste o token com `SharedPreferences` e atualiza o cabeçalho `Authorization` do `dioProvider` sempre que o valor muda.
   _Referência:_ `flutter/lib/core/auth/auth_providers.dart`, linhas 1-60; `flutter/lib/core/auth/auth_token_storage.dart`, linhas 1-28; `flutter/lib/core/network/dio_client.dart`, linhas 9-24.

9. ~~**Declarar a dependência direta de `flutter_riverpod`**~~
   ✅ Resolvido: o `pubspec.yaml` agora inclui `flutter_riverpod` além do `hooks_riverpod`, alinhando os imports existentes e evitando erros no `flutter pub get`.
   _Referência:_ `flutter/pubspec.yaml`, linhas 16-36.

10. ~~**Permitir troca da base de API sem editar código-fonte**~~
   ✅ Resolvido: `AppConfig` agora lê a base via `--dart-define` (`PIX_API_BASE_URL` ou `API_BASE_URL`) com fallback para produção, permitindo apontar o app para qualquer ambiente sem modificar código-fonte.
   _Referência:_ `flutter/lib/core/config/app_config.dart`, linhas 1-82; `flutter/README.md`, seção “Configuração de ambientes”.

11. ~~**Consumir o endpoint real `/dashboard/home`**~~
    ✅ Resolvido: `HybridDashboardRepository` busca o payload via Dio com fallback no asset `assets/data/dashboard_home.json`, mantendo a home alinhada ao Strapi.
    _Referência:_ `flutter/lib/features/dashboard/data/dashboard_repository.dart`; `flutter/assets/data/dashboard_home.json`.

12. ~~**Preparar o workspace iOS com ativos e integrações necessárias**~~
    ✅ Resolvido: o diretório `flutter/ios` agora está versionado com projeto Xcode, ícones gerados na paleta oficial, telas de lançamento e `AppDelegate` configurado para Firebase Messaging/push remoto.
    _Referência:_ `flutter/ios/Runner/AppDelegate.swift`; `flutter/ios/Runner/Assets.xcassets/**/*`; `flutter/ios/Podfile`; `flutter/README.md`, seção “Configuração adicional no iOS”.

## Backend (Strapi)

1. ~~**Provisionar seeds e fixtures no Strapi para cobrir as novas áreas**~~
   ✅ Resolvido: o bootstrap do Strapi agora executa `seedExamples`, que importa planos, assinaturas e cobranças Pix diretamente dos JSONs em `docs/examples/`, preservando IDs lógicos e vínculos entre as coleções.
   _Referência:_ `web/src/bootstrap/seed.ts`; `web/src/index.ts`.

2. ~~**Facilitar setup local desligando integrações opcionais por padrão**~~
   ✅ Resolvido: `web/.env.example` agora desabilita Redis e Meilisearch por padrão e traz comentários orientando quando habilitar cada serviço, permitindo subir o Strapi apenas com PostgreSQL local.
   _Referência:_ `web/.env.example`, linhas 18-31.

3. ~~**Implementar os content-types e workflows prometidos**~~
   ✅ Resolvido: os módulos de simulados, desafios e metas agora possuem content-types próprios, controladores com payloads achatados, rotas personalizadas e seeds alinhados aos exemplos JSON.
   _Referência:_ `web/src/api/simulado/**`; `web/src/api/desafio/**`; `web/src/api/meta/**`; `web/src/bootstrap/seed.ts`.

4. ~~**Prever um endpoint de saúde operacional**~~
   ✅ Resolvido: rota autenticada `GET /health` implementada no Strapi e documentada no OpenAPI com payload detalhando banco de dados, Redis e Meilisearch.
   _Referência:_ `web/src/api/health/controllers/health.ts`; `docs/openapi.yaml`, linhas 1-120 e 2574-5200.

5. ~~**Achatamento das respostas para aderir ao contrato OpenAPI**~~
   ✅ Resolvido: os controladores de planos e cobranças removem o envelope `data` padrão do Strapi e serializam Pix/ajustes exatamente como descrito no OpenAPI, incluindo cabeçalho `X-Total-Count` para paginação.
   _Referência:_ `web/src/api/plano/controllers/plano.ts`; `web/src/api/assinatura/controllers/assinatura.ts`.

6. ~~**Disponibilizar as rotas Pix documentadas**~~
   ✅ Resolvido: o controller de assinaturas expõe geração, consulta e atualização de cobranças Pix com payloads compatíveis com o contrato, além da rota pública de chave principal.
   _Referência:_ `web/src/api/assinatura/controllers/assinatura.ts`; `web/src/api/assinatura/routes/custom-assinatura.ts`.

7. ~~**Instalar os plugins correspondentes às integrações configuradas**~~
   ✅ Resolvido: os pacotes `strapi-plugin-redis` e `strapi-plugin-meilisearch` foram adicionados ao `package.json`, alinhando as dependências com as flags de `web/config/plugins.ts`.
   _Referência:_ `web/package.json`, linhas 1-36.

8. ~~**Alinhar o status de prontidão com a realidade do projeto**~~
   ✅ Resolvido: o mega resumo e o `x-projectStatus` do OpenAPI agora permanecem sincronizados (100 %) e os scripts `scripts/validate_sync.py` e `scripts/update_readiness.py` mantêm o valor alinhado entre documentação e contrato.
   _Referência:_ `docs/mega-resumo-codex.md`, linhas 12-80; `docs/openapi.yaml`, linhas 1-24; `scripts/validate_sync.py`; `scripts/update_readiness.py`.

9. ~~**Fornecer API para destaques de alunos no dashboard**~~
   ✅ Resolvido: o agregador agora está disponível em `web/src/api/dashboard-home`, retornando o payload `DashboardHomeResponse` com destaques, planos e assinaturas sincronizados com os seeds.
   _Referência:_ `web/src/api/dashboard-home/controllers/dashboard-home.ts`; `docs/examples/dashboard_home.json`.

10. ~~**Materializar o controlador `/dashboard/home` no Strapi**~~
    ✅ Resolvido: a rota `GET /dashboard/home` foi criada com fallback baseado nos exemplos e enriquecimento dinâmico a partir de planos, assinaturas e cobranças Pix.
    _Referência:_ `web/src/api/dashboard-home/controllers/dashboard-home.ts`; `docs/openapi.yaml` (rota `/dashboard/home`).
