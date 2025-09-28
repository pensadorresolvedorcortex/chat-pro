# 🚀 Mega Resumo – Academia da Comunicação (para Codex)

## 🎯 Objetivo

Criar um clone moderno do **QConcursos**, chamado **Academia da Comunicação**, composto por:

* **App Mobile (Flutter 3)** para iOS/Android
* **Painel Web CMS (Strapi 5 + PostgreSQL + Redis)** para cadastrar e gerenciar questões, filtros, simulados, cadernos, desafios, metas, cursos e assinaturas
* **Integrações**: Firebase (notificações, analytics), Meilisearch (busca), Pix (pagamentos)
* **Design System**: SaaS clean, fluido, com cores institucionais

---

## 📈 Status Atual

* **Progresso estimado para APK Android:** 45 %
* **Estrutura do repositório:**
  * `docs/` – contrato OpenAPI, seeds Pix e mega resumo
  * `web/` – CMS Strapi 5 com configurações iniciais (PostgreSQL, Redis, Meilisearch)
  * `flutter/` – app Flutter 3 com tema, roteamento e telas base de onboarding/dashboard/paywall
* **Frentes ativas:** geração dos content-types Strapi, implementação das rotas Pix server-side, conectar as telas Flutter aos provedores reais e instrumentar métricas.
* **Cobranças Pix no CMS:** rotas `/assinaturas/pix/cobrancas` e `/assinaturas/pix/chave-principal` expostas com geração, consulta e atualização de status integradas às novas coleções de Assinaturas e Cobranças Pix.
* **Home navegável:** a tela inicial replica o QConcursos com destaques de alunos, quick actions, métricas semanais e cards conectados aos exemplos de usuários (`docs/examples/usuarios.json`) e dashboard (`docs/examples/dashboard_home.json`).
* **Relatórios e guias:** checklist de finalização em [`docs/finalizacao-app.md`](./finalizacao-app.md), runbook operacional em [`docs/release-runbook.md`](./release-runbook.md) e relatório de status em [`docs/go-live-report.md`](./go-live-report.md).
* **Exemplos de seed:** JSONs prontos em `docs/examples` cobrindo planos, assinaturas, cobranças Pix, usuários, onboarding, perfil, configurações, dashboard, questões, resoluções, filtros, cadernos, simulados, desafios, metas, cursos, lives, mentorias, biblioteca, notificações, NPS e suporte.
* **Sincronia app ↔ CMS:** relatório em [`docs/app-panel-sync-report.md`](./app-panel-sync-report.md) e script de validação `python scripts/validate_sync.py` garantem que usuários, planos, assinaturas e cobranças compartilham os mesmos IDs.
* **Monitoramento:** endpoint autenticado `/health` no Strapi expõe o estado do banco de dados, Redis e Meilisearch — com estes serviços desabilitados por padrão no `.env` local — para checkers externos e alertas.

---

## ✅ Checklist para concluir o app

> Consulte o guia completo em [`docs/finalizacao-app.md`](./finalizacao-app.md) para detalhamento das ações e responsáveis e o [runbook de lançamento](./release-runbook.md) para a cadência operacional até o go-live.

### App mobile (Flutter)

* 🚧 Conectar o paywall ao backend Pix (atualmente somente mock com assets locais).
* 🚧 Implementar o cache offline de cobranças e planos (Hive/Isar ainda não configurados).
* 🚧 Configurar notificações push para Pix, mentorias e lives (Firebase Auth/FCM pendentes).
* 📊 Instrumentar métricas de conversão e falhas Pix nos painéis Firebase/Sentry.

### CMS / Backend (Strapi)

* 🚧 Criar content-types e coleções (planos, assinaturas, cobranças, desafios, mentorias) no Strapi.
* 🚧 Implementar serviços Pix (`/assinaturas/pix/*`) com geração de cobrança e chave principal.
* 🚧 Publicar webhooks Pix para atualização de assinaturas e disparo de notificações.
* 📊 Evoluir relatórios analíticos e integrações com Data Studio/Grafana.

### QA, Observabilidade e Lançamento

* 🚧 Montar suíte de testes end-to-end para compra Pix, downgrade, expiração e aprovação gratuita.
* 🚧 Ativar monitoramento (Crashlytics, Sentry, logs Strapi) com alertas de QR Code e retries.
* 🚧 Preparar assets, política LGPD e FAQ Pix para submissão na Play Store.
* 📊 Planejar acompanhamento semanal de NPS e feedbacks de suporte.

### 🚧 Plano de ataque final (72h)

| Status | Entrega | Dono | Dependências |
| --- | --- | --- | --- |
| 🚧 Em andamento | Integração Pix no app (paywall, recibo, histórico) com fallback offline | Squad Flutter | Rotas `/assinaturas/pix/*`, exemplos em `docs/examples` |
| 🚧 Em andamento | Tela de aprovação dos Planos Grátis para Alunos + logs de auditoria | Time CMS/Strapi | Workflow publicado no ambiente admin + seeds de planos |
| ✅ Concluído | Seeds Pix (planos, assinaturas, cobranças) e payloads de mentoria/live | Backend | Merge nos ambientes de staging |
| 📊 Monitorando | Observabilidade Pix (Crashlytics, Sentry/Strapi, alertas expirados) | Mobile + DevOps | Evento de webhook `pix.cobranca.expirada` documentado |
| 🚧 Em andamento | Testes end-to-end automatizados (pagamento, downgrade, aprovação gratuita) | QA | Dados seed + mocks Pix homologação |
| 🚧 Em andamento | Checklist de publicação Android (assets, política, revisão LGPD) | PM | Aprovação legal e marketing |

> Sempre sincronizar status no stand-up diário e atualizar o `x-projectStatus` após cada marco acima.

---

## 🎨 Identidade Visual

Paleta de cores oficial:

* **Primária:** `#6645f6`
* **Secundária:** `#1dd3c4`
* **Terciária:** `#e5be49`
* **Quaternária (erro):** `#df5354`
* **Preto institucional:** `#0c3c64`
* **Neutros:** derivados (cinza 50–700)

Dark Mode → base em `#0c3c64`, botões e destaques em cores da paleta.

---

## 📱 App Mobile (Flutter)

* Flutter 3 + Dart
* Arquitetura: Clean Architecture + Riverpod
* Roteamento: go_router
* Cache offline: Hive/Isar
* Integrações: Firebase Auth, FCM, Analytics, Pagamentos via Pix (copia e cola + QR Code)
* Design: Material 3, cantos arredondados, cards, animações leves

Fluxos principais (baseados nos prints):

1. **Onboarding** (escolha de objetivo: concurso, explorar, estudar livre)
2. **Questões** (resolver, comentar, favoritar, revisar, feedback imediato de certo/errado)
3. **Filtros** (simples, avançados, salvos, baixados)
4. **Cadernos** (criar, gerenciar, offline)
5. **Simulados express** (wizard em 3 etapas: disciplinas, banca/dificuldade, tempo)
6. **Desempenho** (estatísticas + ranking por período)
7. **Desafios coletivos** (criar, participar, ranking de participantes)
8. **Metas de estudo** (questões, horas, rendimento, progresso gamificado)
9. **Cursos/Aulas** (top em alta, recomendados, busca)
10. **Assinaturas** (planos mensal/anual, básico/avançado, paywall integrado)
11. **Perfil/Configurações** (dark mode, suporte, termos, política)
12. **Suporte/NPS** (chat de ajuda, pesquisas de satisfação)
13. **Mentorias guiadas** (slots, reservas com tutores e acompanhamento pós-sessão)
14. **Lives e eventos síncronos** (agenda centralizada, inscrições, replays)

> Exemplos para cada fluxo: consulte `docs/examples/onboarding.json`, `usuarios.json`, `dashboard_home.json`, `perfil.json`, `configuracoes.json`, `questoes.json`, `cadernos.json`, `simulados.json`, `desafios.json`, `metas.json`, `cursos.json`, `lives.json`, `mentorias.json`, `biblioteca.json`, `notificacoes.json`, `nps.json` e `suporte.json`.

---

## 🌐 Painel Web (CMS – Strapi 5)

* Banco: PostgreSQL
* Cache: Redis
* Busca: Meilisearch/Elastic
* RBAC: Admin, Editor, Tutor, Suporte, Aluno
* Plugins customizados: Questões, Simulados, Desafios, Cursos, Planos
* Painel tematizado com as cores do app

### Content Types

* **Disciplina**: nome, slug
* **Assunto**: nome, slug, disciplinaId, parentId
* **Banca**: nome, sigla
* **Cargo**: nome, área, nível
* **Concurso**: órgão, UF, bancaId, cargos[], dataProva, temporada
* **Questão**: enunciado (MD/HTML), alternativas A–E, correta, explicação, dificuldade, ano, disciplinaId, assuntoIds, bancaId, concursoId?, tags, mídia, estatísticas
* **FiltroSalvo**: userId, nome, queryJson
* **Caderno**: userId, título, questãoIds[]
* **Simulado**: tipo (express/manual), distribuição, dificuldade, banca, nQuestoes, tempo, questãoIds[], resultados
* **ResoluçãoQuestão**: userId, questaoId, simuladoId?, status, tempoSegundos
* **Desafio**: nome, duração, organizadorId, regras, status, participantes, ranking
* **MetaEstudo**: userId, tipo (horas/questões/acertos/rendimento), alvo, janela, progresso
* **Curso**: título, descrição, tags, destaque
* **Aula**: cursoId, título, tipo (vídeo/pdf), url, duração, anexos
* **Plano**: nome, periodicidade, preço, benefícios, `chavePix`, tipo (pago ou gratis_aluno), aprovadoEm?, aprovadoPor?
* **Assinatura**: userId, planoId, metodoPagamento (pix|gratis_aluno), status, início/fim, cobrancaPixId?, codigoCopiaCola, qrCodeUrl/base64, expiracaoPagamento
* **Notificação/NPS**: título, mensagem, tipo (promo/nps/sistema), agendamento, público-alvo
* **Mentoria**: título, descrição, mentorId, modalidade, duração, status, tags
* **MentoriaSlot**: mentoriaId, início, fim, capacidade, status
* **MentoriaAgendamento**: mentoriaId, slotId, userId, status, notas
* **LiveEvento**: título, descrição, apresentador, início/fim, links, status, capacidade, tags
* **LiveInscricao**: liveId, userId, status, timestamps
* **MaterialBiblioteca**: título, descrição, formato, arquivo/link, disciplinas, assuntos, tags, destaque, publicadoEm, métricas

### Planos e Assinaturas

* **Planos pagos** (mensal, trimestral, anual) ficam disponíveis imediatamente após a criação, com cobrança via Pix usando chave dedicada.
* **Planos Grátis para Alunos** iniciam como `pendente` e só aparecem no app depois da aprovação manual do super admin (auditoria com timestamp e responsável).
* Cada plano guarda `chavePix` e benefícios ativos, exibidos no dashboard e usados nas cobranças.
* O dashboard administrativo permite editar valores e benefícios, mantendo histórico de ajustes com responsável, motivo, timestamps e diff de preços.
* Rotas-chave: `/planos/{id}/preco` para ajustes rápidos, `/planos/{id}/aprovar` para liberar Planos Grátis para Alunos e `/assinaturas/pix/cobrancas` para emitir códigos copia e cola com QR dinâmico.
* Cada cobrança Pix gera `codigoCopiaCola`, `qrCodeUrl`/`qrCodeBase64`, metadados do pagador e status em tempo real para sincronizar app e CMS.

### Rotas REST /qc/v1

* Catálogos: `/catalogos`
* Questões: `/questoes`, `/questoes/import`
* Filtros: `/filtros`
* Cadernos: `/cadernos`
* Simulados: `/simulados/gerar`, `/simulados/:id/responder`
* Desafios: `/desafios`, `/desafios/:id/ranking`
* Metas: `/metas`
* Cursos/Aulas: `/cursos`
* Planos/Assinaturas: `/planos`, `/planos/{id}/preco`, `/planos/{id}/aprovar`, `/assinaturas/minha`, `/assinaturas/pix/cobrancas`, `/assinaturas/pix/cobrancas/{cobrancaId}`, `/assinaturas/pix/chave-principal`
* Mentorias: `/mentorias`
* Agenda de lives: `/agenda/lives`
* Biblioteca: `/biblioteca/materiais`
* Notificações: `/notificacoes/enviar`

---

## 🔔 Integrações

* **Firebase Cloud Messaging (FCM)** → push notifications
* **Pix** → cobranças instantâneas com código copia e cola e QR Code dinâmico
* **Cron Jobs** → ranking diário, estatísticas agregadas

---

## 📊 Prints de referência

Os prints completos enviados nesta conversa ilustram todos os fluxos:

* Questões, filtros, simulados, cadernos, desafios, cursos, planos de assinatura, desempenho, metas de estudo, onboarding, suporte, dark mode.
* **Links (internos desta conversa)**:

  * [Primeira remessa de prints](#)
  * [Segunda remessa](#)
  * [Terceira remessa](#)
  * [Última remessa](#)

> ⚠️ No ambiente Codex, insira os prints manualmente ou cole as imagens no mesmo prompt, pois ele não tem acesso a links externos desta conversa.

---

## ✅ Tarefas iniciais para Codex

1. **Gerar scaffold Strapi 5** com PostgreSQL, Redis, Meilisearch, RBAC
2. **Criar Content Types** listados acima
3. **Implementar rotas REST /qc/v1** conforme descrito
4. **Tema do painel** com as cores do app
5. **Seed inicial**: 3 disciplinas, 10 assuntos, 2 bancas, 50 questões dummy, 2 cursos com 4 aulas
6. **Setup Mobile Flutter** com ThemeData e rota inicial
7. **Configurar módulos de mentorias, lives e biblioteca** com content types, rotas REST e exemplos no CMS
8. **Implementar fluxos Pix** com geração de cobranças (código copia e cola + QR), aprovação de Planos Grátis para Alunos e histórico de ajustes no dashboard

9. **Popular payloads de exemplo** abaixo no seed inicial para garantir paridade com as telas dos prints

---

## 🧭 Checklist de finalização do app

### Mobile (Flutter)

* Implementar o fluxo completo de cobrança Pix in-app (gerar cobrança, polling do status, exibir QR/copia e cola e confirmar matrícula).
* Conectar telas de Planos/Assinaturas aos endpoints `/planos`, `/planos/{id}/preco`, `/planos/{id}/aprovar` e `/assinaturas/pix/cobrancas` com estados de carregamento e erros.
* Finalizar integrações de notificações (push + in-app) para confirmações de pagamento, metas atingidas e eventos de mentorias/lives.
* Ajustar as telas de desempenho e ranking para consumirem os relatórios consolidados da API e permitir filtros por período.
* Concluir o modo offline (Hive/Isar) para cadernos e questões salvas, incluindo sincronização bidirecional.

### Backend / CMS (Strapi)

* Publicar workflows de aprovação para Planos Grátis para Alunos com histórico e notificação automática para o app.
* Garantir que o plugin Pix gerencie a emissão de cobranças, callbacks de status e arquivamento das cobranças expiradas.
* Revisar RBAC e permissões finas (admin, editor, tutor, suporte, aluno) cobrindo mentorias, lives, biblioteca e relatórios.
* Configurar jobs assíncronos para atualizar rankings de desafios, métricas de metas e expiração de cobranças Pix.
* Indexar conteúdos chave (questões, cursos, biblioteca) no Meilisearch com sincronia incremental.
* Publicar o endpoint `/assinaturas/pix/chave-principal` no CMS para facilitar cópia da chave Pix com QR embutido.

### QA, Analytics e Publicação

* Executar testes end-to-end (mobile + backend) cobrindo onboarding, resolução de questões, assinatura via Pix e participação em mentorias/lives.
* Configurar monitoramento no Firebase Analytics com eventos para Pix, desafios, metas e NPS.
* Preparar checklists de revisão visual (tema claro/escuro) e acessibilidade antes do build final.
* Automatizar geração do APK/Bundle com fastlane ou CI, incluindo variáveis de ambiente para endpoints e chaves Pix.
* Validar os webhooks Pix em ambiente de staging e documentar o procedimento de contingência.

> Cumprindo os itens acima, o app estará pronto para gerar o APK Android e seguir para homologação.

---

## 🧾 Exemplos de Payloads para o Seed Inicial

```json
{
  "questao": {
    "id": "qst-2024-0001",
    "enunciado": "No contexto das campanhas de comunicação, qual etapa garante o alinhamento entre mensagem e público-alvo?",
    "alternativas": [
      "A) Definição de KPIs",
      "B) Pesquisa de persona",
      "C) Criação de peças",
      "D) Distribuição de mídia",
      "E) Monitoramento de métricas"
    ],
    "correta": "B",
    "explicacao": "A pesquisa de persona determina necessidades e linguagem do público, guiando toda a campanha.",
    "disciplina": "Planejamento de Comunicação",
    "assuntos": ["Segmentação", "Brand Voice"],
    "banca": "ESPCOM",
    "ano": 2024,
    "dificuldade": "intermediaria",
    "estatisticas": {
      "taxaAcerto": 0.64,
      "respondida": 1820,
      "favoritos": 312
    }
  },
  "resposta": {
    "usuarioId": "usr-8841",
    "questaoId": "qst-2024-0001",
    "alternativa": "B",
    "correta": true,
    "tempoSegundos": 73,
    "comentario": "Mapeamento das personas impactou diretamente nossa escolha de canais.",
    "registradoEm": "2024-06-14T12:47:03Z"
  }
}
```

```json
{
  "planoPago": {
    "id": "pln-premium-anual",
    "nome": "Premium Anual",
    "periodicidade": "anual",
    "preco": 699.9,
    "moeda": "BRL",
    "beneficios": [
      "Questões ilimitadas",
      "Simulados express e personalizados",
      "Relatórios avançados",
      "Mentorias coletivas mensais"
    ],
    "chavePix": "00020126360014BR.GOV.BCB.PIX0114academia@pix.com0208Academia5204000053039865802BR5917Academia Cursos6009SAO PAULO61080540900062070503***6304ABCD",
    "codigoCopiaCola": "00020101021226730014BR.GOV.BCB.PIX...",
    "qrCodeUrl": "https://cdn.academia/pix/premium-anual.png",
    "histPrecos": [
      {"valor": 649.9, "vigencia": "2023-11-01", "responsavel": "ana.costa"},
      {"valor": 699.9, "vigencia": "2024-05-10", "responsavel": "rafa.souza"}
    ]
  },
  "planoGratis": {
    "id": "pln-gratis-alunos",
    "nome": "Planos Grátis para Alunos",
    "status": "pendente_aprovacao",
    "beneficios": [
      "Acesso a 30 questões por dia",
      "2 simulados express por mês",
      "Estudo guiado inicial"
    ],
    "regras": "Disponível para estudantes comprovando matrícula ativa.",
    "aprovacao": {
      "solicitadoEm": "2024-06-12T14:20:00Z",
      "aprovadoEm": null,
      "aprovadoPor": null
    }
  }
}
```

---

## 📦 Como usar este resumo

Cole este arquivo junto do `docs/openapi.yaml` quando for acionar o Codex. Ele fornece o panorama do produto, enquanto a especificação OpenAPI detalha os contratos das rotas. Combine ambos para gerar backend, CMS e app com mais contexto.

