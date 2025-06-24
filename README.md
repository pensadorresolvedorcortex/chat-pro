# Chat-Pro

Este é o repositório inicial para o Codex do ChatGPT.

## Plugins

 - **Bolao X** (v3.1): plugin WordPress localizado em `bolao-x/`.
   Principais recursos:
   - Cadastro manual ou importação de apostas via CSV
   - Shortcodes `[bolao_x_form]`, `[bolao_x_results]`, `[bolao_x_history]`, `[bolao_x_my_bets]` e `[bolao_x_stats]`
   - Exportação dos resultados em CSV, Excel ou PDF
   - Histórico de concursos e estatísticas de frequência
   - Horários limite configuráveis para recebimento de novas apostas
   - Pagamento via Pix com QR Code e barra de progresso animada no relatório
   - Botão para copiar a chave Pix após enviar a aposta
   - Estatísticas exibem gráfico de barras para cada dezena
   - Interface moderna com efeito de vidro e botões em gradiente
   - Pronto para tradução via text domain `bolao-x` (arquivos em `bolao-x/languages`)
   - Inclui um exemplo de tradução em português (`bolao-x-pt_BR.po`)
   - Widget de resumo no painel e envio automático de e-mails aos participantes
   - Todos os dados (incluindo a chave Pix) são removidos na desinstalação

## Development
Run `scripts/test.sh` para validar os arquivos PHP.
