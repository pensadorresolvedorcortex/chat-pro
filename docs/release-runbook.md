# 🚀 Runbook de Lançamento – Academia da Comunicação

> Status: Go-live Pix concluído em 2024-06-30. Utilize este runbook como base para lançamentos futuros e exercícios de recuperação.

Este runbook consolida a rotina operacional para publicar o APK Android com o fluxo de assinaturas via **Pix** em produção. Ele está organizado por janelas relativas ao go-live (`T`) e deve ser seguido em conjunto com o checklist detalhado em [`docs/finalizacao-app.md`](./finalizacao-app.md).

---

## 📆 Linha do tempo

### T-5 dias — Convergência técnica
- [ ] Congelar backlog: apenas correções bloqueadoras entram após o T-5.
- [ ] Validar builds Flutter `release` (Android) e `profile` (QA) com o backend Pix apontando para staging.
- [ ] Rodar suíte E2E com cobranças Pix (pagamento confirmado, expiração e reenvio) e arquivar evidências.
- [ ] Revisar checklist LGPD e criptografia de `chavePix`/`codigoCopiaCola` em repouso.

### T-3 dias — Ensaios de operação
- [ ] Executar tabletop dos webhooks `pix.cobranca.*` no Strapi, garantindo monitoração e alertas.
- [ ] Verificar se o dashboard registra auditoria completa para ajustes de preço e aprovações de Planos Grátis para Alunos.
- [ ] Importar seeds finais (planos, assinaturas, cobranças, mentorias) em staging e coletar aceite dos squads.
- [ ] Preparar mensagem de anúncio e materiais de suporte (FAQ Pix, tutorial paywall).

### T-1 dia — Preparação final
- [ ] Gerar APK assinado + bundle `aab` e armazenar no cofre de artefatos.
- [ ] Conferir assets de loja (screenshots, descrição, política de privacidade atualizada).
- [ ] Rodar checklist de smoke manual em dispositivos físicos (Android 13/14) com foco em paywall Pix.
- [ ] Confirmar disponibilidade dos times de prontidão (mobile, backend, suporte) para a janela de lançamento.

### T (Lançamento)
- [ ] Publicar versão na Play Store em rollout controlado (porcentagem inicial ≤10 %).
- [ ] Monitorar dashboards (Crashlytics, Sentry, métricas Pix) e canais de alerta.
- [ ] Validar primeira cobrança real com QR Code e copia e cola em produção, registrando `txid` de referência.
- [ ] Atualizar `docs/openapi.yaml` e `docs/mega-resumo-codex.md` com a data/hora do go-live e status final.

### T+1 dia — Pós-lançamento
- [ ] Reunir métricas iniciais (conversão Pix, ativação de Planos Grátis para Alunos, NPS) e documentar aprendizados.
- [ ] Priorizar ajustes rápidos (<1 dia) identificados em canais de suporte/comunidade.
- [ ] Agendar retro com stakeholders e registrar próximos experimentos de monetização.

---

## 🧭 Responsabilidades chave

| Função | Responsável | Principais tarefas |
| --- | --- | --- |
| **Tech Lead Mobile** | Squad Flutter | Build final, smoke tests, monitoração Crashlytics |
| **Tech Lead Backend** | Squad CMS/Strapi | Webhooks Pix, auditoria, seeds finais |
| **QA Lead** | Chapter Qualidade | Suíte E2E, evidências, checklist de publicação |
| **Product Marketing** | PM + Marketing | Assets de loja, comunicação, FAQ Pix |
| **Suporte/CS** | Chapter Suporte | Monitoramento pós-lançamento, playbook de atendimento |

---

## ✅ Pré-requisitos obrigatórios
- Matriz RACI validada para decisões durante o rollout.
- Acesso ao painel Pix de produção (com contingência documentada).
- Alertas configurados em canais dedicados (`#lancamento-pix`, PagerDuty, e-mail).
- Política de privacidade e termos atualizados com o fluxo Pix e retenção de dados.
- Backup verificado das bases PostgreSQL/Redis pré-go-live.

---

## 📎 Artefatos de apoio
- Checklist detalhado: [`docs/finalizacao-app.md`](./finalizacao-app.md)
- Mega resumo com contexto completo: [`docs/mega-resumo-codex.md`](./mega-resumo-codex.md)
- Contrato de API e exemplos Pix: [`docs/openapi.yaml`](./openapi.yaml) & [`docs/examples`](./examples)

> Atualize este runbook após cada lançamento para manter o histórico de lições aprendidas e garantir repetibilidade.

