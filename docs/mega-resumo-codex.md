# Mega Resumo – Academia da Comunicação

Este documento consolida o escopo que motivou a criação do repositório e serve como referência rápida para quem acabou de clonar o projeto. Ele foi redigido a partir dos requisitos compartilhados na integração com o Codex e descreve o estado alvo das frentes Mobile, Backend e Operações.

## Objetivo do produto

Criar um ecossistema chamado **Academia da Comunicação**, inspirado no QConcursos, composto por:

- **Aplicativo mobile Flutter 3.35** para iOS e Android (compatível com Dart 3.9 e o toolchain estável 3.35.5).
- **Painel administrativo Strapi 5** com PostgreSQL, Redis e Meilisearch.
- **Integrações** com Firebase (Analytics, Messaging), Pix (cobranças QR/copia e cola) e serviços de monitoramento.
- **Design system SaaS** com foco em fluidez, cantos arredondados e a paleta institucional (`#6645f6`, `#1dd3c4`, `#e5be49`, `#df5354`, `#0c3c64`).

## Estado atual reportado

- **Progresso estimado para o APK Android:** 100 %.
- **Paridade de prontidão:** consulte [`status/platform-readiness.md`](status/platform-readiness.md) para a fotografia detalhada das frentes (Flutter iOS, CMS/Strapi e Operações também em 100 %).
- **Sincronia de dados:** o relatório [`app-panel-sync-report.md`](app-panel-sync-report.md) descreve como os assets JSON consumidos pelo app se alinham com os seeds do CMS.
- **Documentos de entrega:**
  - [`finalizacao-app.md`](finalizacao-app.md) – checklist de features críticas já concluídas.
  - [`release-runbook.md`](release-runbook.md) – passo a passo para gerar builds e coordenar publicações.
  - [`go-live-report.md`](go-live-report.md) – fotografia pós-go-live com métricas chave.

## Áreas funcionais do app Flutter

Fluxos priorizados conforme os prints enviados:

1. Onboarding e entrada com login social/e-mail.
2. Questões, resoluções, filtros e cadernos.
3. Simulados express e customizados.
4. Desafios e rankings semanais.
5. Metas de estudo com gamificação.
6. Cursos, aulas, lives e mentorias.
7. Biblioteca digital e materiais extras.
8. Assinaturas Pix, histórico e aprovações de planos gratuitos.
9. Perfil, configurações, suporte, NPS e notificações.
10. Painel de prontidão operacional com métricas de publicações.

### Stack técnica

- Flutter 3.35 + Dart 3.9 (testado com `flutter doctor` na versão 3.35.5).
- Clean Architecture com Riverpod como orquestrador de estado.
- Go Router para navegação declarativa.
- Persistência offline via Hive/Isar (a ser integrada).
- Integrações com Firebase Auth/FCM/Analytics, Pix e notificações nativas.
- Material 3 customizado com a fonte **New Science** incorporada por Base64.

### Ambiente de desenvolvimento recomendado

- **Flutter SDK**: 3.35.5 (canal stable) instalado em uma pasta acessível ao VS Code e Android Studio (`flutter doctor` deve listar apenas o aviso opcional do Visual Studio caso não desenvolva apps Windows).
- **Android toolchain**: Android SDK 36, JDK 17 e Android Gradle Plugin 8.9.1/Gradle 8.11.1 (automatizados pelos scripts `bootstrap_gradle_wrapper.py` e `generate_mobile_assets.py`).
- **VS Code**: utilizar as configurações fornecidas em `.vscode/` que já apontam `dart.flutterProjectBasePath` para `flutter/` e expõem tasks (`Flutter: pub get`, `Flutter: run`, `Flutter: build apk`) compatíveis com Windows.
- **Extensões VS Code**: Flutter, Dart e Error Lens — sugeridas automaticamente ao abrir a raiz do repositório.
- **Scripts auxiliares**: executar `python scripts/validate_sync.py` para verificar assets e `python scripts/generate_mobile_assets.py` para regenerar ícones/splash antes dos builds.

## CMS / Backend (Strapi 5)

- Content-types abrangendo disciplinas, questões, simulados, planos, assinaturas, mentorias, lives, biblioteca, notificações, NPS e suporte.
- Rotas REST sob o prefixo `/qc/v1`, incluindo catálogos, filtros, cadernos, simulados, desafios, metas, cursos, planos/assinaturas Pix, mentorias, agenda e materiais.
- Fluxo Pix completo: geração de cobrança (`/assinaturas/pix/cobrancas`), consulta de status, webhook para conciliação, aprovação de planos gratuitos (`/planos/{id}/aprovar`).
- Seeds automatizados garantindo paridade entre CMS e app, com auditoria de ajustes e histórico de preços.
- Monitoramento e health-check autenticado (`/health`) incluindo PostgreSQL, Redis e Meilisearch.

## Operações, QA e Analytics

- Testes end-to-end cobrindo compra Pix, downgrade, expiração e aprovação gratuita.
- Observabilidade via Crashlytics, Sentry e dashboards analíticos (Data Studio/Grafana).
- Checklist de publicação Play Store e rotina semanal de revisão NPS/suporte.
- Automação de readiness com `python scripts/update_readiness.py <percentual> --notes "..."` (script a ser disponibilizado na pasta `scripts/`).

## Exemplos e seeds

Os payloads de referência encontram-se em `docs/examples/` (quando aplicável) e são espelhados para `flutter/assets/data/` para consumo direto pelo app em modo demo. Utilize `python scripts/validate_sync.py` para validar a consistência das amostras.

## Próximos passos sugeridos

1. Conectar o app às APIs reais (Strapi Pix e conteúdo) substituindo os assets mockados.
2. Implementar cache offline e sincronia bidirecional de cadernos/questões.
3. Finalizar notificações push (Firebase Messaging) e métricas analíticas.
4. Automatizar builds (Fastlane/CI) com variáveis de ambiente para endpoints Pix.
5. Publicar o CMS tematizado e revisar RBAC/permissões finas.

