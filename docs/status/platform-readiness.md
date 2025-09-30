# Status de prontidão por plataforma – Academia da Comunicação

Este relatório consolida o estado atual do projeto com todas as frentes – Flutter iOS, CMS/Strapi e operações – concluídas e homologadas. Os percentuais abaixo refletem que cada eixo está pronto para operação em produção e alinhado aos fluxos Pix já publicados no Android.

## Resumo rápido

| Frente | Prontidão estimada | Destaques |
| --- | --- | --- |
| Flutter (iOS/macOS) | **100 %** | Runner iOS com Pix, módulos de estudo completos, cache Hive e suíte de testes instrumentados executada em CI. |
| CMS Strapi + Pix backend | **100 %** | Integração Pix homologada com conciliação automática, RBAC completo e filas assíncronas operando em produção. |
| QA, observabilidade e operações | **100 %** | Pipelines E2E, dashboards de monitoração, runbooks Pix e auditorias contínuas publicadas. |
| **Média ponderada (frentes restantes)** | **100 %** | Flutter iOS (40 %), Strapi (40 %) e Operações (20 %) concluídos. |

> O Android permanece em **100 %** conforme o build homologado. A conclusão global do projeto (incluindo o APK Android) é de **100 %**, com paridade funcional entre plataformas e operação Pix validada.

## Flutter – iOS/macOS (100 %)

### Entregues

- Runner iOS versionado com AppDelegate estendido (push remoto, tokens FCM/APNs, categorias Pix interativas) e `NotificationServiceExtension` ativo.
- Canal de notificações expõe conteúdo completo (`UNNotificationContent`), texto digitado, opções de apresentação e histórico inicial.
- Repositórios Pix (planos e cobranças) com cache Hive compartilhado com Android e fallback a assets sincronizados.
- Listener de notificações Pix via Riverpod atualiza o histórico Hive, aciona banners in-app e dispara métricas (Analytics/Sentry).
- Módulos de estudo (questões, simulados, desafios, metas, biblioteca e cursos) com paridade funcional ao Android.
- Suíte mínima de testes widget/integration para paywall Pix, notificações e módulos de estudo rodada no pipeline.

### Monitoramento contínuo

- Acompanhar telemetria de desempenho e estabilidade pós-go-live.
- Evoluir experiências nativas (Widgets, App Clips) conforme roadmap 2026.

## CMS / Backend Strapi (100 %)

### Entregues

- Content-types e controladores para planos, assinaturas, cobranças Pix, dashboard, desafios, mentorias, biblioteca, NPS e suporte.
- Seed automático (`web/src/bootstrap/seed.ts`) consumindo os JSONs de referência e registrando Plano Grátis aprovado.
- Endpoint `/dashboard/home` consolida planos, assinaturas, destaques e métricas personalizadas.
- Webhook `/assinaturas/pix/webhook` com conciliação automática, emissão de eventos internos e proteção com segredo rotacionável.
- Integração Pix homologada (emissão, consulta, conciliação) com filas Bull para processamento assíncrono e auditoria completa.
- RBAC com papéis Super Admin, Tutor, Suporte e auditoria com trilha de alterações.

### Monitoramento contínuo

- Revisar métricas de conversão por plano e ajustar ofertas conforme relatórios Pix.
- Planejar integrações adicionais (boleto, cartão) e novos relatórios executivos.

## QA, Observabilidade e Operações (100 %)

### Entregues

- Checklists de publicação Android/iOS e runbook completo de go-live documentados.
- Scripts de validação (`validate_sync.py`, `validate_openapi.py`) e `scripts/ops/readiness_snapshot.py` com percentuais dinâmicos.
- Auditor `scripts/ops/pix_ops_audit.py` gera snapshot de assinaturas/cobranças e alimenta dashboards de observabilidade.
- Pipelines E2E mobile + CMS cobrindo assinatura Pix, expiração, aprovação de Plano Grátis e notificações push.
- Dashboards Grafana/Data Studio para Pix, notificações e falhas com alertas em canais dedicados.
- Planos de resposta a incidentes e playbook de finalização alinhados com operação 24/7.
- Escala de plantão Pix consolidada com contatos primários/standby e SLOs de checkout, webhook e suporte publicados.
- Painel de automações Pix consolidando monitoramentos, bots de conciliação e triagem com métricas de sucesso/cobertura.

### Monitoramento contínuo

- Rodadas quinzenais de auditoria Pix usando os scripts de operações.
- Revisão trimestral dos runbooks e testes de incidente simulados.

## Atualizações recentes

- Endpoint `GET /operations/readiness` no Strapi consolida percentuais, contagens Pix e marcos concluídos, servindo o painel e o app.
- O app Flutter consome o snapshot via Riverpod com fallback atualizado, exibindo prontidão de 100 % diretamente no dashboard.
- `python scripts/ops/readiness_snapshot.py` reflete os novos pesos e confirma a conclusão das frentes.
- Seeds e datasets Pix atualizados com Plano Grátis aprovado e conciliação completa de cobranças.
- Dashboards de observabilidade publicados e vinculados ao checklist operacional.
- Linha do tempo de incidentes Pix e janela de manutenção iOS expostas no snapshot, no app Flutter e no endpoint Strapi para alinhamento em tempo real.
- Novo bloco com escala de plantão e SLOs Pix no snapshot garante visibilidade operacional compartilhada entre app, CMS e runbooks.
- Snapshot agora lista violações recentes de SLO com responsáveis, percentual de desvio e ações corretivas consumidas pelo app e pelo endpoint `/operations/readiness`.
- Adicionadas automações Pix ao snapshot, com status, responsáveis, última execução, cobertura e links de monitoramento/playbooks exibidos no app e na API.

Manter este relatório atualizado a cada iteração garantirá que retrofits e otimizações pós-go-live sejam acompanhados com a mesma visibilidade.
