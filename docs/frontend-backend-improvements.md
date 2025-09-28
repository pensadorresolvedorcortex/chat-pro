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

5. **Persistir o fallback remoto quando disponível**
   O `HybridPlanRepository` opta entre API e asset local, mas não salva em cache os dados vindos do backend para uso offline. Persistir o resultado remoto (por exemplo, com Hive/Isar, já previstos no projeto) reduziria o custo de rede e manteria a UI atualizada em reaberturas.
   _Referência:_ `flutter/lib/features/paywall/data/plan_repository.dart`, linhas 52-81.

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

## Backend (Strapi)

1. **Provisionar seeds e fixtures no Strapi para cobrir as novas áreas**
   Foram adicionados JSONs de usuários, dashboard, filtros, cadernos, simulados, desafios, metas, cursos, lives, mentorias, biblioteca, notificações, NPS e suporte em `docs/examples/`, mas não há comando de seed correspondente no workspace Strapi. Sem migrar esses dados para collections ou scripts de bootstrap, o CMS continuará vazio e o app depende de mocks. Criar migrações ou seeds oficiais garante alinhamento com os exemplos.
   _Referência:_ `docs/examples`, arquivos recém-adicionados; `web/src/index.ts`, linhas 1-8.

2. ~~**Facilitar setup local desligando integrações opcionais por padrão**~~
   ✅ Resolvido: `web/.env.example` agora desabilita Redis e Meilisearch por padrão e traz comentários orientando quando habilitar cada serviço, permitindo subir o Strapi apenas com PostgreSQL local.
   _Referência:_ `web/.env.example`, linhas 18-31.

3. **Implementar os content-types e workflows prometidos**
   O bootstrap do Strapi atualmente apenas escreve um log e não registra content-types, controladores ou lógica de aprovação de planos/pagamentos descritas na OpenAPI. É necessário gerar os schemas de Questões, Planos, cobranças Pix etc. para que o CMS corresponda ao contrato.
   _Referência:_ `web/src/index.ts`, linhas 1-8.

4. ~~**Prever um endpoint de saúde operacional**~~
   ✅ Resolvido: rota autenticada `GET /health` implementada no Strapi e documentada no OpenAPI com payload detalhando banco de dados, Redis e Meilisearch.
   _Referência:_ `web/src/api/health/controllers/health.ts`; `docs/openapi.yaml`, linhas 1-120 e 2574-5200.

5. **Achatamento das respostas para aderir ao contrato OpenAPI**
   O contrato publica `Plano` com campos no topo (id, nome, pix, status), mas o Strapi entrega `data[].attributes`. Sem um transformador ou controlador customizado, o app quebra no parsing (vide item Frontend 6). Criar serializers que removam o envelope ou publicar endpoints dedicados garante que backend e contrato estejam alinhados.
   _Referência:_ `docs/openapi.yaml`, linhas 3549-3576; `web/src/index.ts`, linhas 1-8.

6. **Disponibilizar as rotas Pix documentadas**
   A API descreve `/assinaturas/pix/cobrancas` e `/assinaturas/pix/chave-principal`, mas não existe nenhuma implementação no workspace. Sem controladores para gerar cobranças, o app cai no fallback offline imediatamente. Priorizar essas rotas garante o fluxo completo de checkout.
   _Referência:_ `docs/openapi.yaml`, linhas 1566-1622; `web/src/index.ts`, linhas 1-8.

7. **Instalar os plugins correspondentes às integrações configuradas**
   O `plugins.ts` habilita Redis, Meilisearch e Sentry, porém o `package.json` não inclui os pacotes de plugin do Strapi (por exemplo, `@strapi/plugin-redis` ou `strapi-plugin-meilisearch`). Sem as dependências, o `strapi develop` interrompe com erros de resolução. Liste e instale os plugins necessários ou remova a configuração até que estejam disponíveis.
   _Referência:_ `web/config/plugins.ts`, linhas 1-55; `web/package.json`, linhas 1-36.

8. **Alinhar o status de prontidão com a realidade do projeto**
   Documentos como o mega resumo e o `x-projectStatus` do OpenAPI apontavam 100 % de prontidão, mas as frentes críticas seguem abertas. Manter os percentuais atualizados — já ajustados para 45 % — evita decisões equivocadas de stakeholders.
   _Referência:_ `docs/mega-resumo-codex.md`, linhas 12-80; `docs/openapi.yaml`, linhas 1-24.

9. ~~**Fornecer API para destaques de alunos no dashboard**~~
   ✅ Resolvido: o agregador agora está disponível em `web/src/api/dashboard-home`, retornando o payload `DashboardHomeResponse` com destaques, planos e assinaturas sincronizados com os seeds.
   _Referência:_ `web/src/api/dashboard-home/controllers/dashboard-home.ts`; `docs/examples/dashboard_home.json`.

10. ~~**Materializar o controlador `/dashboard/home` no Strapi**~~
    ✅ Resolvido: a rota `GET /dashboard/home` foi criada com fallback baseado nos exemplos e enriquecimento dinâmico a partir de planos, assinaturas e cobranças Pix.
    _Referência:_ `web/src/api/dashboard-home/controllers/dashboard-home.ts`; `docs/openapi.yaml` (rota `/dashboard/home`).
