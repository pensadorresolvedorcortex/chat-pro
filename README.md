# Chat-Pro

Este é o repositório inicial para o Codex do ChatGPT.

## Plugins

 - **Bolao X** (v3.11.9): plugin WordPress localizado em `bolao-x/`.
   Principais recursos:
   - Cadastro manual ou importação de apostas via CSV
   - Shortcodes `[bolao_x_form]`, `[bolao_x_results]`, `[bolao_x_history]`, `[bolao_x_my_bets]` e `[bolao_x_stats]`
   - Exportação dos resultados em CSV, Excel ou PDF
   - Histórico de concursos e estatísticas de frequência
   - Horários limite configuráveis para recebimento de novas apostas
   - Exportação também disponível em JSON
   - Pagamento via Pix com QR Code e barra de progresso animada no relatório
  - Múltiplas chaves Pix com seleção da conta ativa
    (insira uma chave por linha nas configurações)
   - Nome e cidade do recebedor configuráveis no admin
   - Botão para copiar a chave Pix após enviar a aposta
   - QR Code gerado com payload padrão para leitura correta
   - TXID aleatório único para cada aposta, facilitando a confirmação
   - Integração Pix reescrita do zero para gerar códigos válidos
   - Formulário de perfil para atualizar seus dados
   - Login estilizado com link "Perdeu a senha?" e formulário para troca de senha
  - Shortcode `[bolao_x_login]` permite login e cadastro usando apenas telefone
  - Após login ou cadastro o usuário é redirecionado para a página /participe
   - Status de pagamento mostrado em "Minhas Apostas"
  - Shortcode `[bolao_x_dashboard]` exibe um painel com ícones alinhados para
    acessar apostas, resultados, estatísticas, perfil e logout
   - Contagem regressiva até o horário limite
   - Estatísticas exibem gráfico de barras para cada dezena
   - Interface moderna com efeito de vidro, botões em gradiente e layout responsivo estilo aplicativo
   - Áreas dos shortcodes claras e com animações de entrada
   - Todos os shortcodes agora são renderizados em um contêiner “app” para visual de aplicativo
   - Widget no painel com barras de progresso dos acertos
   - Seleção visual das dezenas em grade clicável, agora com área ampliada e animações
   - Pronto para tradução via text domain `bolao-x` (arquivos em `bolao-x/languages`)
   - Tradução brasileira disponível com o arquivo-fonte `bolao-x-pt_BR.po`; gere o `.mo` fora do repositório com `msgfmt`
   - Widget de resumo no painel e envio automático de e-mails aos participantes
   - Sistema de premiação por "Menos Pontos" com acúmulo em caso de empate
   - Quadro com todas as dezenas marcando as sorteadas e lista de números repetidos
   - Todos os dados (incluindo a chave Pix) são removidos na desinstalação

## Development
Instale o PHP CLI e extensões necessárias (`apt-get install php-cli php8.3-gd zbar-tools`).
Execute `scripts/test.sh` para validar o código PHP e `scripts/test_pix.sh` para gerar e ler um QR Code Pix de exemplo.
