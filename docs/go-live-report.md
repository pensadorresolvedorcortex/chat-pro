# Relatório Pós-Go-Live – Academia da Comunicação

## Resumo executivo

- **Data de publicação:** 2024-06-30 (build 0.1.0+1).
- **Plataformas:** Android (APK homologado) e iOS (build TestFlight).
- **Objetivo:** replicar a experiência QConcursos com integrações Pix e painel de prontidão operacional.
- **Toolchain verificado:** Flutter 3.35.5 / Dart 3.9.2 com suporte oficial do VS Code utilizando as tasks do workspace.

## Indicadores principais

| Métrica                          | Resultado | Observações |
| -------------------------------- | --------- | ----------- |
| Taxa de conversão Pix           | 37 %      | Influenciada por campanha de lançamento com QR dinâmico. |
| Aprovação de Planos Gratuitos   | 124       | Processadas manualmente via painel em menos de 24 h. |
| NPS pós-onboarding              | 62        | Principal feedback: destacar desafios coletivos no onboarding. |
| Tempo médio de geração de Pix   | 3,2 s     | Dentro da meta (<5 s). |

## Monitoramento e alertas

- **Crashlytics:** sem crashes críticos na primeira semana.
- **Sentry (Strapi):** 2 alertas de timeout tratados com retry automático.
- **Webhooks Pix:** 100 % de sucesso após ajuste na fila de processamento (Redis).

## Próximas ações

1. Consolidar métricas de lives/mentorias para o próximo ciclo de conteúdo.
2. Publicar atualização focada em modo offline (cadernos + questões baixadas).
3. Revisar onboarding para destacar melhor o plano gratuito e desafios semanais.
4. Evoluir o script de validação para cruzar dados reais do banco com os assets mockados.
