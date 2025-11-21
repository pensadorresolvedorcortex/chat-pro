# Mega resumo de contexto para novo chat

Repositorio: https://github.com/pensadorresolvedorcortex/chat-pro/blob/main/juntaplay.zip

## Pedidos do usuario
- Criar Grupo: remover a Regra 2 e renumerar a antiga Regra 3 como Regra 2.
- Criar Grupo: na seção "Só mais essas perguntinhas – Qual a categoria?", atualizar os ícones para refletirem melhor cada categoria (ícones bem acabados e coerentes).
- Criar Grupo: em "Qual é o relacionamento entre os participantes?", trocar ícones por versões lógicas e bem desenhadas.
- Criar Grupo: em "Como os participantes podem falar com você?", substituir ícones por alternativas mais coerentes e refinadas.
- Criar Grupo: em "Quando os participantes vão receber o acesso ao serviço?", ajustar ícones para opções claras e bem acabadas.
- Criar Grupo: em "Como será disponibilizado o acesso ao serviço?", melhorar os ícones conforme a categoria/ação.
- Criar Grupo: em "Dados para acesso imediato – Observações e notas – Observações para os participantes", remover completamente essa seção.
- Criar Grupo: remover a barra/percentuais (25%, 50%, 75%, 100%) e o rótulo "Passo 4" dessa etapa.
- Importar/carregar somente os assets necessários: evitar sobrecarga no navegador.
- Manter cada evolução separada (registrar mudanças de forma granular).

## Trabalho realizado ate agora (commits anteriores)
- Refino de regras: fluxo passou a ter somente duas regras obrigatorias com renumeracao automatica ao remover a regra 2.
- Atualizacao dos icones para categorias, relacionamentos, canais de suporte, prazos e metodos de acesso no formulario de Criar Grupo (icones mais coerentes e polidos).
- Remocao do rotulo "Passo 4" e das porcentagens de progresso; barra visual de progresso ocultada.
- Otimizacao de assets: registro de scripts/estilos so quando shortcodes do Juntaplay sao detectados; nao enfileirar em admin ou AJAX; reutilizacao de lista de shortcodes conhecida; exploracao de paginas Elementor (incluindo widgets de shortcode) para detectar shortcodes e carregar assets apenas quando necessario; helper compartilhado para extrair e deduplicar shortcodes.
- Vinculo do walker de shortcodes do Elementor ao contexto da classe para garantir acesso a metodos auxiliares.

## Pendencias conhecidas
- Confirmar e remover a secao "Observacoes para os participantes" em "Dados para acesso imediato" no modal/formulario de Criar Grupo (foi identificado como ainda presente em commit anterior).
- Revisar se todos os icones solicitados estao aplicados em todos os pontos do fluxo de Criar Grupo.
- Verificar se ha outros pontos carregando assets desnecessarios.

Use este resumo para continuar o trabalho no novo chat sem precisar reabrir a conversa original.
