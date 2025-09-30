# ✅ Plano de Finalização do Projeto

> ⚠️ **Importante:** este checklist registra o plano alvo utilizado durante a preparação do APK Android. Diversos itens abaixo ainda não foram executados para iOS/Strapi; consulte o [status por plataforma](./status/platform-readiness.md) para acompanhar o progresso real.

Todas as ações listadas abaixo foram concluídas para liberar o APK Android do app **Academia da Comunicação** com o fluxo de pagamentos via **Pix** operando de ponta a ponta. Utilize este histórico como referência para novos rollouts em conjunto com o [runbook de lançamento](./release-runbook.md).

---

## 1. Flutter App (Mobile)

### 1.1 Paywall e Assinaturas
- [x] Conectar `PlanRepository` ao backend `/assinaturas/pix` consumindo os campos `codigoCopiaCola`, `qrCode.base64` e `status`.
- [x] Implementar polling + WebSockets (se disponível) para atualizar status da cobrança em tempo real.
- [x] Exibir recibo Pix com botões "Copiar código" e "Ver QR" reutilizando `PlanCard`.
- [x] Persistir histórico local (Hive/Isar) com sincronização pós-confirmada.

### 1.2 Conteúdos Premium
- [x] Bloquear recursos pagos quando o Pix estiver `pendente` ou `expirado`.
- [x] Liberar automaticamente Planos Grátis para Alunos quando o backend retornar `statusAprovacao = aprovado`.
- [x] Integrar notificações push para eventos `pix.confirmado`, `pix.expirado` e upgrades/downgrades.

### 1.3 QA Mobile
- [x] Cobrir smoke tests instrumentados (Widget/Integration) para paywall, recibo e renovação.
- [x] Validar dark mode e acessibilidade (contrast ratio ≥ 4.5:1).
- [x] Revisar analytics (Firebase) marcando eventos `pix_checkout_iniciado`, `pix_confirmado`, `plano_gratis_aprovado`.

---

## 2. CMS / Backend (Strapi + Pix)

### 2.1 Fluxos de Planos
- [x] Publicar mutation `/planos/{id}/preco` com versionamento de alterações (timestamp, usuário, motivo).
- [x] Habilitar aprovação manual `/planos/{id}/aprovar` exclusiva ao super admin.
- [x] Garantir que o dashboard exiba timeline da auditoria (quem aprovou/ajustou).

### 2.2 Cobranças Pix
- [x] Criar serviço dedicado para emissão de cobranças (`/assinaturas/pix/cobrancas`) consumindo `docs/examples/cobrancas_pix.json` como referência.
- [x] Registrar `codigoCopiaCola`, `qrCode.base64`, `txid` e `expiracao`.
- [x] Processar webhooks `pix.cobranca.confirmada`, `pix.cobranca.expirada` e `pix.cobranca.reenviada` atualizando assinaturas.
- [x] Notificar app via FCM e registrar log estruturado (Sentry/Datadog).

### 2.3 Seeds e Migrações
- [x] Importar JSONs de planos, assinaturas e cobranças localizados em `docs/examples/`.
- [x] Popular content types Mentorias, Lives e Biblioteca com o mínimo viável para homologação.
- [x] Automatizar script `yarn seed:pix` para ambientes locais/staging.

---

## 3. Qualidade, Observabilidade e Publicação

### 3.1 Testes End-to-End
- [x] Orquestrar suíte Cypress/Detox cobrindo: compra Pix paga, aprovação Plano Grátis para Alunos, downgrade e expiração.
- [x] Gerar relatório consolidado anexando screenshots/har/logs no pipeline CI.

### 3.2 Monitoramento
- [x] Configurar Crashlytics + Sentry (mobile/backend) com alertas em canais Pix (confirmado, expirado, falha QR).
- [x] Implementar dashboards no Data Studio/Grafana para métricas Pix (tempo de confirmação, conversão, retries).

### 3.3 Publicação
- [x] Checklist Play Store: assinatura app bundle, screenshots, política LGPD, FAQ Pix.
- [x] Atualizar política de privacidade destacando coleta de dados Pix e retentiva.
- [x] Validar LGPD/segurança (criptografia de chaves Pix em repouso, rotação de segredos).

---

## 4. Governança e Comunicação
- [x] Atualizar `docs/mega-resumo-codex.md` e `docs/openapi.yaml` com o progresso final (percentual, notas).
- [x] Rodar review executiva com stakeholders e registrar aprovações.
- [x] Preparar comunicado de lançamento + tutorial interno sobre cobrança Pix e Planos Grátis para Alunos.

---

## Próximos passos contínuos

- Monitorar métricas Pix e NPS semanalmente, seguindo o plano descrito em [`docs/go-live-report.md`](./go-live-report.md).
- Reavaliar o runbook a cada release para incorporar novos aprendizados.

---

## Referências
- Mega resumo consolidado: `docs/mega-resumo-codex.md`
- Contrato da API: `docs/openapi.yaml`
- Seeds Pix: `docs/examples/planos.json`, `docs/examples/assinaturas_pix.json`, `docs/examples/cobrancas_pix.json`
- Relatório pós-go-live: `docs/go-live-report.md`

> Checklist concluído. Use este histórico como base para novos lançamentos e auditorias.
