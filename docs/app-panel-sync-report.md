# Relatório de Sincronia App ↔ Painel

Este relatório resume como os dados expostos no CMS/Strapi estão refletidos nos assets que o app Flutter consome no modo demo.

## Panorama geral

- Os planos Pix publicados no CMS estão espelhados em `flutter/assets/data/planos.json`, preservando `id`, `tipo`, `periodicidade`, benefícios e metadados de aprovação.
- As métricas de dashboard (usuário, atalhos rápidos, indicadores e destaques) são copiadas de `docs/examples/dashboard_home.json` para `flutter/assets/data/dashboard_home.json`.
- O painel de prontidão operacional segue a mesma estrutura exposta pela API `/operacoes/readiness` e foi serializado em `flutter/assets/data/operations_readiness.json`.
- Questões e simulados utilizam os exemplos descritos em `docs/examples/questoes.json` e `docs/examples/simulados.json` (mantendo `id` e relacionamentos previstos no CMS).

## Procedimento de validação

1. Execute `python scripts/validate_sync.py`. O script garante que:
   - Todos os planos possuem identificadores únicos e que o `planoId` ativo no dashboard existe na lista de planos.
   - Há pelo menos um plano gratuito e um plano pago com bloco Pix completo (chave, valor, código copia e cola).
   - Os arquivos `dashboard_home.json`, `operations_readiness.json`, `questoes.json` e `simulados.json` contêm os campos mínimos esperados.
2. Caso algum check falhe, o script exibirá mensagens indicando o arquivo e o campo que precisam de revisão.
3. Após ajustes, rode novamente até obter a mensagem **"Validação concluída sem erros"**.

## Próximos passos

- Substituir os assets mockados por chamadas reais assim que as rotas Strapi estiverem acessíveis.
- Estender o script para cruzar IDs de usuários, simulados e cobranças com o banco real.
- Automatizar a verificação no pipeline de CI para evitar divergências entre CMS e app.
