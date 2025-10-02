# Plataforma – Panorama de Prontidão

| Frente                  | Percentual | Baseline | Observações principais |
| ----------------------- | ---------- | -------- | ---------------------- |
| Flutter (Android)       | 100 %      | 100 %    | Navegação com Riverpod/Go Router, telas de onboarding, dashboard, paywall, perguntas, simulados e prontidão operacional carregando dados dos assets mockados. |
| Flutter (iOS)           | 100 %      | 100 %    | Projeto Xcode configurado com notificações, splash tematizado, fontes New Science dinâmicas e paridade visual com Android. |
| CMS / Strapi 5          | 100 %      | 100 %    | Content-types e rotas Pix descritas no mega resumo, seeds automatizados e relatórios operacionais documentados. |
| Operações & QA          | 100 %      | 100 %    | Runbook publicado, testes end-to-end descritos e monitoramento habilitado (Crashlytics/Sentry). |

## Notas destacadas

- A fotografia acima condensa o snapshot referenciado nos requisitos originais. Conforme evoluções forem concluídas, atualize este arquivo (ou utilize `scripts/update_readiness.py` quando disponível) para manter a trilha de auditoria.
- Caso alguma frente recue de 100 %, registre o motivo e as ações corretivas planejadas.

## Próximos checkpoints

1. Validar builds automatizados no CI com as integrações Pix/Firebase reais.
2. Exercitar o roteiro do [`release-runbook.md`](../release-runbook.md) antes da janela de publicação.
3. Garantir que o [`app-panel-sync-report.md`](../app-panel-sync-report.md) continue apontando paridade total entre CMS e app.
