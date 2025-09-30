# Relatório de sincronia – App Flutter x Painel Strapi

## Objetivo
Validar que os dados de referência usados no app Flutter (`flutter/`) e no painel Strapi (`web/`) estão alinhados, com as mesmas entidades (usuários, planos, assinaturas, cobranças e widgets de dashboard) apontando para os mesmos identificadores.

## Execução automatizada
Use o script [`scripts/validate_sync.py`](../scripts/validate_sync.py) para garantir que as seeds continuam coerentes:

```bash
python scripts/validate_sync.py
```

O script confere:

- Usuário destacado na home do app existe em `docs/examples/usuarios.json`.
- Destaques de alunos trazem `usuarioId` válido e sincronizado com os dados do CMS.
- Planos em destaque, assinaturas recentes e cobranças Pix referenciam o mesmo `planoId` nas seeds do app e do Strapi.
- Cards de planos destacados conferem preço, moeda e status de aprovação com a fonte oficial em `docs/examples/planos.json`.
- Assinaturas listadas nos exemplos apontam para usuários válidos e planos cadastrados.
- Cobranças Pix ligam-se às assinaturas corretas e aos mesmos usuários.
- Todos os planos documentados estão presentes no asset local `flutter/assets/data/planos.json` para fallback offline e com os
  mesmos campos/valores descritos em `docs/examples/planos.json`.
- Os planos têm regras de negócio validadas: IDs únicos, benefícios preenchidos, preços/valores Pix alinhados e aprovação dos
  Planos Grátis para Alunos registrada corretamente.
- O asset `flutter/assets/data/dashboard_home.json` é mantido idêntico ao exemplo compartilhado para garantir que a home do app
  reflita os mesmos dados do relatório.
- O snapshot de prontidão operacional (`flutter/assets/data/operations_readiness.json`) permanece alinhado com `docs/examples/operations_readiness.json` para alimentar o card de operações no dashboard e o endpoint `/operations/readiness` do Strapi.
- Os assets `flutter/assets/data/questoes.json` e `flutter/assets/data/simulados.json` espelham os exemplos públicos para abastecer a tela de questões recomendadas e os simulados express, mantendo o fluxo navegável mesmo sem conexão.
- A assinatura atual mostrada no dashboard é verificada contra `docs/examples/assinaturas_pix.json`, alinhando status e data de
  renovação com o cadastro real.
- Todos os identificadores `user-*`, `tutor-*` e `agente-*` referenciados nos exemplos (dashboard, planos, cadernos, simulados,
  mentorias, biblioteca, suporte etc.) existem em `docs/examples/usuarios.json`.
- Assinaturas e cobranças Pix respeitam o tipo de plano: PIX apenas para planos pagos com `pixInfo` completo, Planos Grátis para
  Alunos exigem aprovação do super admin e cobranças replicam o valor e QR Code dos planos.
- Assinaturas Pix validam o vínculo com a cobrança ativa (`cobrancaPixId`/`ultimaCobrancaId`), garantindo que usuário, plano,
  valor, moeda e payloads de QR Code estejam sincronizados com `docs/examples/cobrancas_pix.json`.
- Lives exibidas no dashboard precisam existir em `docs/examples/lives.json`, reutilizando título, instrutor e data.
- Materiais da biblioteca mantêm formato, tags, autor e métricas numéricas alinhadas com `docs/examples/biblioteca.json`.
- Metas, cadernos, simulados, desafios e mentorias confirmam a presença do usuário destacado na home para tornar os fluxos
  navegáveis, com rankings e reservas validando IDs e métricas numéricas.
- Cada caderno confere se as questões apontadas existem em `docs/examples/questoes.json`, garantindo que os IDs consumidos pelos
  cards também estejam disponíveis no catálogo principal de questões.
- O percentual de prontidão rumo ao APK Android permanece alinhado entre `docs/openapi.yaml`, `docs/mega-resumo-codex.md` e o
  `README.md` raiz.
- Cada `externalValue` declarado no contrato OpenAPI aponta para um arquivo existente e, quando for JSON, o conteúdo é validado
  automaticamente.

Quando todos os vínculos estão corretos, o script encerra com código `0` e a mensagem “Todas as referências entre app e CMS estão sincronizadas.”

## Mapeamento por área

| Área do app | Fonte Strapi | Exemplo de dado | Observações |
| --- | --- | --- | --- |
| Home / Dashboard | `/dashboard/home` | [`docs/examples/dashboard_home.json`](examples/dashboard_home.json) | Endpoint implementado em `web/src/api/dashboard-home` agrega planos, assinaturas e cobranças para alimentar os cards sincronizados com os seeds. |
| Planos e assinaturas | `/planos`, `/assinaturas/pix` | [`docs/examples/planos.json`](examples/planos.json), [`docs/examples/assinaturas_pix.json`](examples/assinaturas_pix.json) | IDs `plano-mensal-plus`, `plano-pro-anual` e `plano-gratis-alunos` são reaproveitados pelo asset Flutter `planos.json`. |
| Cobranças Pix | `/assinaturas/pix/cobrancas` | [`docs/examples/cobrancas_pix.json`](examples/cobrancas_pix.json) | Cobranças mantêm `assinaturaId` e `usuarioId` para rastreabilidade no painel. |
| Usuários | `/usuarios` | [`docs/examples/usuarios.json`](examples/usuarios.json) | Amostra cobre premium, gratuitos, mentor e fila do Plano Grátis para Alunos. |
| Conteúdos de estudo | `/questoes`, `/cadernos`, `/simulados`, `/cursos` | [`docs/examples/questoes.json`](examples/questoes.json) etc. | Seeds já validadas manualmente para espelhar os fluxos dos prints e continuam referenciadas nos cards da home. |
| Biblioteca | `/biblioteca/materiais` | [`docs/examples/biblioteca.json`](examples/biblioteca.json) | Controller `web/src/api/biblioteca` aplica filtros por formato, tag e disciplina e retorna métricas achatadas conforme o contrato. |
| Mentorias e lives | `/mentorias`, `/lives` | [`docs/examples/mentorias.json`](examples/mentorias.json), [`docs/examples/lives.json`](examples/lives.json) | Controllers `web/src/api/mentoria` e `web/src/api/live` normalizam slots, capacidade e links para consumo direto pelo app. |
| Comunicação | `/notificacoes`, `/nps`, `/suporte` | [`docs/examples/notificacoes.json`](examples/notificacoes.json), [`docs/examples/nps.json`](examples/nps.json), [`docs/examples/suporte.json`](examples/suporte.json) | Seeds carregadas via `seedExamples`, expondo endpoints achatados para notificações push, pesquisas NPS e tickets de suporte. |
| Prontidão operacional | `/operations/readiness` | [`docs/examples/operations_readiness.json`](examples/operations_readiness.json) | Endpoint custom consolida percentuais, contagens Pix, escala de plantão e SLOs das frentes Flutter, Strapi e Operações, agora expostos também na tela Flutter `OperationsPage` (`/operacoes/readiness`) com refresh nativo e via API. |
| Catálogo completo de exemplos | — | [`docs/examples/README.md`](examples/README.md) | Visão geral das seeds por área do app e vínculo com o CMS. |

## Próximos passos

1. ~~Implementar no Strapi o endpoint `GET /dashboard/home` espelhando o contrato descrito no [OpenAPI](openapi.yaml) e consumindo as coleções reais.~~
   ✅ Resolvido em `web/src/api/dashboard-home`, com fallback documentado em `docs/examples/dashboard_home.json`.
2. ~~Expor os scripts de seed no Strapi (`web/`) para carregar os JSON acima nas coleções correspondentes.~~
   ✅ `web/src/bootstrap/seed.ts` importa planos, assinaturas, cobranças, simulados, desafios, metas, biblioteca, cadernos, filtros, cursos, lives, mentorias, notificações, NPS e suporte a partir dos JSONs compartilhados.
3. ~~Integrar o app Flutter aos endpoints reais substituindo os providers mockados (`dashboard_demo_data.dart`) pelos repositórios que consomem o novo contrato.~~
   ✅ Concluído — o dashboard usa `dashboardProvider` conectado ao `/dashboard/home`.
4. Publicar um endpoint operacional que agregue progresso das frentes restantes e sirva o app. ✅ Implementado em `web/src/api/operations-readiness`, consumindo o novo exemplo `operations_readiness.json`.

## Melhorias identificadas

- **Frontend:** ligar os widgets da home ao endpoint `/dashboard/home` para reduzir duplicidade de dados e eliminar mocks locais.
- **Backend:** publicar resolvers no Strapi que descompactem o envelope `attributes` e retornem o payload achatado conforme o contrato `DashboardHomeResponse`. ✅ Implementado para o dashboard em `web/src/api/dashboard-home`, restando aplicar o mesmo padrão às demais coleções.
- **Dados:** manter o script de validação no CI/CD para impedir regressões quando novos planos/usuários forem adicionados.
