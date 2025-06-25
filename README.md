# Chat-Pro

Este é o repositório inicial para o Codex do ChatGPT.

## Plugins

 - **Bolao X** (v3.7.1): plugin WordPress localizado em `bolao-x/`.
   Principais recursos:
   - Cadastro manual ou importação de apostas via CSV
   - Shortcodes `[bolao_x_form]`, `[bolao_x_results]`, `[bolao_x_history]`, `[bolao_x_my_bets]` e `[bolao_x_stats]`
   - Exportação dos resultados em CSV, Excel ou PDF
   - Histórico de concursos e estatísticas de frequência
   - Horários limite configuráveis para recebimento de novas apostas
   - Pagamento via Pix com QR Code e barra de progresso animada no relatório
   - Chave Pix pode ser trocada no admin conforme necessário
   - Botão para copiar a chave Pix após enviar a aposta
   - Formulário de perfil para atualizar seus dados
   - Login estilizado com link "Perdeu a senha?" e formulário para troca de senha
   - Status de pagamento mostrado em "Minhas Apostas"
   - Contagem regressiva até o horário limite
   - Estatísticas exibem gráfico de barras para cada dezena
   - Interface moderna com efeito de vidro, botões em gradiente e layout responsivo estilo aplicativo
   - Áreas dos shortcodes claras e com animações de entrada
   - Todos os shortcodes agora são renderizados em um contêiner “app” para visual de aplicativo
   - Seleção visual das dezenas em grade clicável, agora com área ampliada e animações
   - Pronto para tradução via text domain `bolao-x` (arquivos em `bolao-x/languages`)
   - Tradução brasileira disponível com o arquivo-fonte `bolao-x-pt_BR.po`
   - Widget de resumo no painel e envio automático de e-mails aos participantes
   - Sistema de premiação por "Menos Pontos" com acúmulo em caso de empate
   - Confirmação automática de pagamentos via webhook Pix
   - Todos os dados (incluindo a chave Pix) são removidos na desinstalação

## Development
Run `scripts/test.sh` para validar os arquivos PHP.
