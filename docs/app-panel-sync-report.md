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
- Assinaturas listadas nos exemplos apontam para usuários válidos e planos cadastrados.
- Cobranças Pix ligam-se às assinaturas corretas e aos mesmos usuários.
- Todos os planos documentados estão presentes no asset local `flutter/assets/data/planos.json` para fallback offline e com os
  mesmos campos/valores descritos em `docs/examples/planos.json`.
- O asset `flutter/assets/data/dashboard_home.json` é mantido idêntico ao exemplo compartilhado para garantir que a home do app
  reflita os mesmos dados do relatório.
- Todos os identificadores `user-*` referenciados nos exemplos (dashboard, planos, cadernos, simulados, mentorias, resoluções
  etc.) existem em `docs/examples/usuarios.json`.

Quando todos os vínculos estão corretos, o script encerra com código `0` e a mensagem “Todas as referências entre app e CMS estão sincronizadas.”

## Mapeamento por área

| Área do app | Fonte Strapi | Exemplo de dado | Observações |
| --- | --- | --- | --- |
| Home / Dashboard | `/dashboard/home` | [`docs/examples/dashboard_home.json`](examples/dashboard_home.json) | Endpoint implementado em `web/src/api/dashboard-home` agrega planos, assinaturas e cobranças para alimentar os cards sincronizados com os seeds. |
| Planos e assinaturas | `/planos`, `/assinaturas/pix` | [`docs/examples/planos.json`](examples/planos.json), [`docs/examples/assinaturas_pix.json`](examples/assinaturas_pix.json) | IDs `plano-mensal-plus`, `plano-pro-anual` e `plano-gratis-alunos` são reaproveitados pelo asset Flutter `planos.json`. |
| Cobranças Pix | `/assinaturas/pix/cobrancas` | [`docs/examples/cobrancas_pix.json`](examples/cobrancas_pix.json) | Cobranças mantêm `assinaturaId` e `usuarioId` para rastreabilidade no painel. |
| Usuários | `/usuarios` | [`docs/examples/usuarios.json`](examples/usuarios.json) | Amostra cobre premium, gratuitos, mentor e fila do Plano Grátis para Alunos. |
| Conteúdos de estudo | `/questoes`, `/cadernos`, `/simulados`, `/cursos` | [`docs/examples/questoes.json`](examples/questoes.json) etc. | Seeds já validadas manualmente para espelhar os fluxos dos prints e continuam referenciadas nos cards da home. |
| Catálogo completo de exemplos | — | [`docs/examples/README.md`](examples/README.md) | Visão geral das seeds por área do app e vínculo com o CMS. |

## Próximos passos

1. ~~Implementar no Strapi o endpoint `GET /dashboard/home` espelhando o contrato descrito no [OpenAPI](openapi.yaml) e consumindo as coleções reais.~~
   ✅ Resolvido em `web/src/api/dashboard-home`, com fallback documentado em `docs/examples/dashboard_home.json`.
2. Expor os scripts de seed no Strapi (`web/`) para carregar os JSON acima nas coleções correspondentes.
3. Integrar o app Flutter aos endpoints reais substituindo os providers mockados (`dashboard_demo_data.dart`) pelos repositórios que consomem o novo contrato. ✅ Concluído — o dashboard usa `dashboardProvider` conectado ao `/dashboard/home`.

## Melhorias identificadas

- **Frontend:** ligar os widgets da home ao endpoint `/dashboard/home` para reduzir duplicidade de dados e eliminar mocks locais.
- **Backend:** publicar resolvers no Strapi que descompactem o envelope `attributes` e retornem o payload achatado conforme o contrato `DashboardHomeResponse`. ✅ Implementado para o dashboard em `web/src/api/dashboard-home`, restando aplicar o mesmo padrão às demais coleções.
- **Dados:** manter o script de validação no CI/CD para impedir regressões quando novos planos/usuários forem adicionados.
