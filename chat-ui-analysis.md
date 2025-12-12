# Observações sobre a Central de Mensagens

## Porque só os seletores aparecem
- A aba "Central de Mensagens" renderiza apenas a casca (placeholders) quando o contexto de grupos/assinantes vem vazio. O template (`juntaplay/templates/profile-groups.php`) preenche as listas com estados "Nenhum grupo selecionado" / "Nenhum assinante encontrado" e não avança para carregar mensagens sem IDs válidos de admin + assinante.
- O carregamento de participantes depende do endpoint REST `juntaplay/v1/chat/context`, que por sua vez usa filtros externos para receber dados reais de grupos e assinantes. Se esses filtros não retornarem resultados, o JS mantém os seletores vazios.

## O que precisa existir para dados reais aparecerem
- Implementar os filtros `juntaplay_chat_groups_for_user` e `juntaplay_chat_group_subscribers` no plugin/tema principal, retornando arrays com `id`, `title`, `subtitle`, `owner_id`, `avatar` (para grupos) e `id`, `name`, `group`, `avatar` (para assinantes).
- Garantir que, ao selecionar um grupo (admin) ou ao entrar como assinante, o contexto REST devolva `owner_id` e a lista de assinantes para que o front-end atribua `adminId`/`subscriberId` e chame `juntaplay/v1/chat/messages`.
- Confirmar que a página é aberta com `section=juntaplay-chat` ou pelo endpoint `/perfil/juntaplay-chat/` para que o script seja executado.

## Por que a interface mostra vários títulos repetidos
- A estrutura da aba inclui título da categoria, cabeçalho da seção e rótulo da linha personalizada (três blocos distintos). Esse comportamento vem diretamente do HTML do template e não indica erro de dados; apenas a casca sem mensagens.
