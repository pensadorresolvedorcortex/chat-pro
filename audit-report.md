# JuntaPlay Chat – Auditoria das Fases 1–8 (Relatório Completo)

## 1) Confirmação da interpretação (estado real do plugin)
A leitura fornecida pelo solicitante está correta. O que existe e o que falta em cada fase:
- **Fase 1 — Não implementada:** nenhuma tabela de mensagens nem funções de chat foram criadas; toda a UI atual está solta, sem persistência ou validação de relação admin↔assinante.
- **Fase 2 — Incompleta:** apenas o parâmetro `section=juntaplay-chat` e a montagem de URL estão presentes; não há registro de endpoint, página ou placeholder carregável.
- **Fase 3 — Não implementada:** o header segue sem item/ícone dedicado de "Mensagens"; não há navegação direta para o chat.
- **Fase 4 — Incompleta:** os seletores glassmorphism usam arrays do template; não há busca real de grupos/assinantes, não abre chat de verdade e não exclui de forma confiável o próprio admin.
- **Fase 5 — Incompleta:** os botões condicionais existem, porém o assinante continua vendo listagens, não inicia conversa direta com o dono e o fluxo não é isolado por papel.
- **Fase 6 — Implementada de forma frágil:** a regra evita apenas o owner; faltam bloqueios para admins que também são assinantes, validação de assinatura ativa e prevenção ampla de autochat/botões incorretos.
- **Fase 7 — Incompleta:** o sino depende de metadados prévios; não há gravação de novas mensagens nem incremento real de não lidas, apenas limpeza ao abrir a seção.
- **Fase 8 — Incompleta:** o layout glassmorphism foi adicionado, mas segue desacoplado de dados reais e convive com resquícios da UI antiga fora da seção dedicada.

## 2) Pontos implementados, pendências e ajustes necessários
- **Implementado:**
  - Flag/URL `section=juntaplay-chat` e visuais glass para seletores e chat.
  - Botões condicionais básicos de admin/assinante.
  - Marcadores de leitura zerados ao entrar no chat (sino).
- **Pendente ou insuficiente:**
  - Criação da tabela `wp_juntaplay_chat_messages` e serviços `validar_relacao_entre_admin_assinante`, `enviar_mensagem`, `obter_mensagens`.
  - Registro do endpoint/página do perfil e item “Mensagens” no header.
  - Carregamento real de grupos/assinantes e abertura de conversas Admin↔Assinante.
  - Isolamento do fluxo do assinante (sem ver grupos/assinantes) e bloqueio de autochat.
  - Integração de notificações com o envio real de mensagens e contagem de não lidas.
  - Remoção da UI legada fora de `section=juntaplay-chat` e vínculo dos componentes ao backend.
- **Correções específicas:**
  - Reforçar regras de exibição (Fase 6) validando assinatura ativa e evitando todos os cenários de autochat.
  - Substituir arrays estáticos por consultas reais e estados vazios claros.
  - Garantir que o sino só sinalize quando houver contador > 0 alimentado pelo backend.

## 3) Riscos e dependências críticas
- **Falta de backend:** sem a Fase 1, nenhuma mensagem ou notificação funciona e todo o restante permanece visual apenas.
- **Navegação ausente:** sem o endpoint (Fase 2) e o item no header (Fase 3), os usuários não chegam ao chat.
- **Permissões frágeis:** regras incompletas podem expor botões errados ou permitir autochat, afetando privacidade.
- **Notificações desconectadas:** o sino depende de metadados inexistentes; sem integração com o envio, ficará mudo ou incoerente.
- **UI concorrente:** convivência com layouts antigos pode levar o usuário a fluxos mortos ou inconsistentes.

## 4) Próximo passo seguro e sequência de correção
1. **Fase 1 (fundação):** criar tabela `wp_juntaplay_chat_messages` com índices adequados; implementar funções de validação, envio e obtenção de mensagens com checagem de permissão e sanitização.
2. **Fase 2 (entrada) + Fase 3 (navegação):** registrar página/endpoint `juntaplay-chat` no perfil e adicionar o item/ícone “Mensagens” no header apontando para essa rota.
3. **Fase 4 (admin) + Fase 5 (assinante):** carregar dados reais de grupos/assinantes, ocultar o próprio admin, isolar a visão do assinante e abrir conversas consumindo o backend.
4. **Fase 6 (regras):** aplicar validações de assinatura ativa e bloqueio de autochat para todos os papéis antes de exibir botões.
5. **Fase 7 (notificações):** integrar o envio de mensagens ao incremento de não lidas do destinatário; marcar como lidas ao abrir o chat e mostrar alerta somente quando houver contador.
6. **Fase 8 (UI):** conectar o layout glass aos dados reais, remover UIs antigas fora da seção e garantir placeholders seguros para avatares e nomes.

## 5) Revisão dos itens sensíveis apontados
- **Dependência de filtros externos (grupos/assinantes):** confirmação — sem `juntaplay_chat_groups_for_user` e `juntaplay_chat_group_subscribers`, as listas ficam vazias, impedindo seleção e abertura de conversas.
- **Validação via filtro `juntaplay_chat_validate_relation`:** procede — se o plugin principal não aplicar regras de assinatura/pertencimento, a relação pode ser permissiva, mas é ajustável adicionando o filtro.
- **Criação da tabela via dbDelta:** correto — instalações que bloqueiem `dbDelta` impedem a criação de `wp_juntaplay_chat_messages`, travando o backend.
- **Cache de user_meta impactando contadores:** procede — ambientes com cache agressivo podem atrasar atualização/visualização de não lidas, embora seja comportamento esperado de cache.
