# JuntaPlay Chat – gaps e aprimoramentos sugeridos

## Pontos que ainda requerem implementação
- **Provedores reais de grupos/assinantes**: a UI depende inteiramente dos filtros `juntaplay_chat_groups_for_user` e `juntaplay_chat_group_subscribers` para preencher os seletores. Sem esses filtros implementados no plugin principal, os arrays `$chat_groups`/`$chat_member_groups` ficam vazios e o painel não carrega participantes reais. 【F:juntaplay/templates/profile-groups.php†L351-L412】
- **Upload não conectado**: o botão de upload no compositor não possui `<input type="file">` nem lógica de envio; o payload REST (`sendMessage`) envia apenas texto, então anexos nunca chegam ao backend apesar do campo `attachment_url` existir. 【F:juntaplay/templates/profile-groups.php†L943-L956】【F:juntaplay/templates/profile-groups.php†L1220-L1273】
- **Tratamento de erro da API**: chamadas REST de carga/ envio só verificam `response.ok` e retornam um status genérico na thread, sem exibir mensagens de falha ou tentar revalidar contexto (ex.: nonce expirado, relação inválida). Isso deixa o usuário sem feedback se o envio falhar. 【F:juntaplay/templates/profile-groups.php†L1185-L1249】

## Ajustes de UX/fluxo
- **Exibição de avatares e nomes em contexto parcial**: ao selecionar um grupo, o header é refeito com textos placeholders até que um assinante seja escolhido; caso o filtro de assinantes retorne vazio, o header permanece com dados genéricos. É recomendável validar o retorno e sinalizar ao admin que precisa configurar assinantes para o grupo. 【F:juntaplay/templates/profile-groups.php†L1060-L1106】
- **Sincronização de não lidas**: a marcação de leitura ocorre apenas via `/chat/messages` com `mark_read` padrão; se a UI não chamar esse endpoint (por erro de rede ou ausência de seleção), as metas de não lidas permanecem. Pode-se considerar uma chamada de marcação ao focar a janela do chat. 【F:juntaplay/chat.php†L369-L422】【F:juntaplay/chat.php†L624-L674】

## Recomendação imediata
1. Implementar os filtros de contexto no plugin principal para garantir listas populadas e evitar UI vazia.
2. Conectar upload (input de arquivo + envio para REST) ou remover o botão até a funcionalidade existir.
3. Melhorar feedback de erro nas chamadas REST e, opcionalmente, marcar leituras também na entrada da janela para manter o sino sincronizado.
