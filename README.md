# Chat-Pro

Este é o repositório inicial para o Codex do ChatGPT.
Este repositório inclui a versão do plugin **Bolao X** com pagamento via Mercado Pago.
Todos os recursos listados abaixo estão disponíveis na release 2.8.3.


## Plugins

 - **Bolao X** (v2.8.3): plugin WordPress localizado em `bolao-x/`.
   Principais recursos:
   - Cadastro manual ou importação de apostas via CSV
   - Shortcodes `[bolao_x_form]`, `[bolao_x_results]`, `[bolao_x_history]`, `[bolao_x_my_bets]` e `[bolao_x_stats]`
   - Exportação dos resultados em CSV, Excel ou PDF
   - Histórico de concursos e estatísticas de frequência
   - Horários limite configuráveis para recebimento de novas apostas
   - Exportação também disponível em JSON
  - Pagamento via Mercado Pago com Pix e QR Code (usando o e-mail do usuário logado)
  - Credenciais de produção e teste (Public Key e Access Token) com modo ativo
  - Valor da aposta configurável e página de logs de pagamento
  - Validador de credenciais do Mercado Pago e logs gerais no painel
  - Campo para definir a chave Pix exibida junto ao QR Code
  - Pagamentos Pix enviam um cabeçalho X-Idempotency-Key único e os logs têm
    tamanho limitado para facilitar a leitura
  - As requisições usam a constante `MP_API_URL` apontando para `https://api.mercadopago.com`
  - O QR Code é exibido usando a imagem base64 retornada pelo Mercado Pago,
    garantindo a visualização mesmo sem acesso externo
  - Botão **Pagar com Pix** exibe um modal com a chave configurada e o QR Code,
    incluindo um botão de "Pix Copia e Cola" para copiar o código antes de registrar a aposta
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
   - Todas as configurações de pagamento são removidas na desinstalação

## Development
Instale o PHP CLI e extensões necessárias executando `scripts/install-deps.sh`.
Depois rode `scripts/test.sh` para validar o código PHP.

## Test Site
Um exemplo de site estático encontra-se em `test-site/`. Abra `index.html` para visualizar a página de demonstração.

