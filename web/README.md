# Academia da Comunicação – CMS (Strapi 5)

Este diretório concentra o backend administrativo do projeto, implementado com **Strapi 5**, PostgreSQL, Redis e Meilisearch conforme descrito no mega resumo em `../docs/mega-resumo-codex.md`.

## Estrutura inicial

```
web/
├── config/               # Configurações de banco, cache, plugins e middlewares
├── src/                  # Schemas, controllers, serviços e extensões do Strapi
├── .env.example          # Variáveis de ambiente padrão para desenvolvimento
├── package.json          # Dependências, scripts e versão alvo do Node.js
└── README.md             # Esta visão geral
```

A árvore acima será expandida com os content types (Questões, Simulados, Planos, etc.), APIs customizadas e hooks definidos no contrato `docs/openapi.yaml`.

## Requisitos

- Node.js 18 LTS
- Yarn 1.22+
- PostgreSQL 15
- Redis 7
- Meilisearch 1.6+

## Primeiros passos

1. Copie o arquivo `.env.example` para `.env` e ajuste credenciais.
   * Use `REDIS_ENABLED=false` ou `MEILISEARCH_ENABLED=false` caso ainda não tenha esses serviços localmente; o Strapi passa a desligar os plugins automaticamente.
2. Instale as dependências: `yarn install`.
3. Execute as migrações e inicialize o Strapi: `yarn develop`.
4. Configure usuários e permissões no painel administrativo para os papéis definidos.

Consulte `../docs/mega-resumo-codex.md` para os content types, workflows e dados de seed que precisam ser implementados.

## Planos Pix implementados

O content type **Plano** já está disponível em `src/api/plano` com os campos descritos no contrato OpenAPI:

- diferenciação entre planos pagos e **Planos Grátis para Alunos**, com workflow de aprovação manual;
- preço armazenado com histórico de ajustes, responsável e motivo para auditoria;
- metadados Pix (chave, tipo, código copia e cola, QR code) utilizados nas cobranças;
- registros de aprovação para rastrear quem liberou o plano gratuito.

Também foram expostas as rotas customizadas documentadas na API:

- `GET /planos/dashboard` — resume métricas e o histórico de ajustes para o painel administrativo;
- `POST /planos/:id/aprovar` — aprova Planos Grátis para Alunos e registra o log;
- `PATCH /planos/:id/preco` — atualiza o preço mantendo o histórico.

Ambas exigem autenticação de administrador (`admin::isAuthenticatedAdmin`).

## Cobranças Pix com assinatura

Foram adicionados os content types **Assinatura** e **Cobrança Pix**, permitindo emitir cobranças diretamente pelo CMS e acompanhar seus status:

- `POST /assinaturas/pix/cobrancas` — gera uma nova cobrança para o plano informado (cria a assinatura caso não exista). 
- `GET /assinaturas/pix/cobrancas/:id` — consulta a cobrança por ID numérico ou Txid.
- `PATCH /assinaturas/pix/cobrancas/:id/status` — atualiza manualmente o status (`pendente`, `confirmado`, `expirado`, `cancelado`, `reembolsado`).
- `GET /assinaturas/pix/chave-principal` — expõe a chave Pix configurada e o QR code pronto para renderização.

Use as variáveis de ambiente abaixo para definir a chave principal:

```env
PIX_PRIMARY_KEY=pix@academiadacomunicacao.com
PIX_PRIMARY_KEY_TYPE=email
PIX_RECEBEDOR_NOME="Academia da Comunicação"
PIX_RECEBEDOR_CIDADE="Sao Paulo"
```

Por padrão, as rotas de emissão/consulta são abertas para consumo do app móvel, enquanto a atualização de status exige autenticação administrativa.

## Dashboard do app mobile

- `GET /dashboard/home` — agrega dados do usuário autenticado, planos Pix, assinaturas recentes, métricas semanais e destaques de alunos conforme o esquema `DashboardHomeResponse` do OpenAPI.
- O controlador (`web/src/api/dashboard-home/controllers/dashboard-home.ts`) enriquece o payload com planos/assinaturas reais e complementa lacunas com o fallback documentado em `docs/examples/dashboard_home.json`.
- A rota requer autenticação (`plugin::users-permissions.isAuthenticated`) e mantém o formato achatado esperado pelo Flutter (`flutter/lib/features/dashboard`).

## Prontidão operacional

- `GET /operations/readiness` — consolida percentuais baseline/computados das frentes Flutter iOS, Strapi e operações, além de contagens Pix e pendências.
- Implementado em `web/src/api/operations-readiness`, reaproveitando o exemplo `docs/examples/operations_readiness.json` e verificações automáticas dos scripts em `scripts/ops/`.
- A rota é pública por padrão para alimentar o card de prontidão do app; use gateways/restrições externas caso precise limitar o acesso.
