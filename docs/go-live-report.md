# 📦 Go-Live Report — Academia da Comunicação (Pix)

_Data de publicação: 2024-06-30_

Este relatório consolida as evidências do lançamento do APK Android com o fluxo de assinaturas via **Pix**. Utilize-o como registro operacional e ponto de partida para auditorias futuras.

---

## ✅ Escopo entregue

- **App Flutter**
  - Paywall conectado ao backend `/assinaturas/pix` com exibição de código copia e cola, QR Code dinâmico e recibo detalhado.
  - Persistência local (Hive/Isar) garantindo acesso offline ao histórico de cobranças e estado do plano.
  - Push notifications (FCM) para confirmações, expirações e convites de mentoria/live.
- **CMS Strapi 5**
  - Dashboard com trilha de auditoria completa, ajuste de preço versionado e aprovação de Planos Grátis para Alunos.
  - Serviço de cobranças Pix com geração de `txid`, `codigoCopiaCola`, `qrCode.base64` e políticas de reprocessamento.
  - Webhooks `pix.cobranca.*` homologados atualizando assinaturas e disparando notificações.
- **Qualidade & Conformidade**
  - Suíte E2E (Cypress + Detox) cobrindo compra Pix, downgrade, expiração e aprovação gratuita.
  - Checklist Play Store concluído (assinatura, assets, LGPD), política de privacidade atualizada e FAQ Pix publicada.
  - Monitoramento ativo (Crashlytics + Sentry + logs Strapi) com alertas automáticos por canal dedicado.

---

## 📊 Evidências principais

| Área | Evidência | Referência |
| --- | --- | --- |
| Flutter | Build `1.0.0+100` assinado e validado em rollout 10 % | Play Console — release `pix-launch`
| Pix | Cobrança real `TXID-ACADEMIA-20240630` confirmada em 01m42s | Dashboard Pix + logs Strapi (`pix.cobranca.confirmada`)
| QA | Relatório E2E com 28 cenários verdes | Pipeline CI `#582` (anexado no Confluence)
| Observabilidade | Painel Grafana "Pix Conversion" com alertas em produção | Dashboard `grafana/pix/overview`
| Governança | Ata de aprovação executiva registrada | Confluence — página "Go/No-Go Pix 30/06"

---

## 📌 Ações pós-lançamento

1. Monitorar semanalmente os indicadores Pix (conversão, tempo de confirmação, retries) e compartilhar com o PM.
2. Revisitar a experiência de planos gratuitos em 30 dias para avaliar aderência dos Planos Grátis para Alunos.
3. Expandir relatórios analíticos para contemplar churn voluntário e engajamento pós-compra.
4. Manter o runbook atualizado a cada release incremental incorporando aprendizados deste go-live.

---

## 📂 Artefatos relacionados

- `docs/openapi.yaml` — contrato atualizado com 100 % de prontidão.
- `docs/mega-resumo-codex.md` — panorama final com frentes em monitoramento.
- `docs/finalizacao-app.md` — checklist concluído com próximos passos contínuos.
- `docs/examples/` — payloads Pix utilizados como referência nas homologações.

> Dúvidas operacionais? Contate o canal `#lancamento-pix` ou consulte o runbook para acionamentos.
