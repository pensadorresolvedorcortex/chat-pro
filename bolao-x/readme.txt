=== Bolao X ===
Contributors: bolaox
Tags: lottery, bolao, pix, csv, pdf, excel
Requires at least: 6.0
Tested up to: 6.5
Stable tag: 3.12.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Sistema completo para gerenciamento de bolão semanal. Permite cadastro de apostas, conferência automática, exportação de resultados e pagamento via Pix.

== Description ==
* Cadastro manual ou importação de apostas via CSV
* Shortcodes para formulário, resultados, histórico, minhas apostas e estatísticas
* Exportação em CSV, Excel e PDF e JSON
* Pagamento via Pix com QR Code
* QR Code gera TXID aleatório exclusivo para cada aposta
* Suporte a múltiplas chaves Pix e escolha da conta ativa
* Nome e cidade do recebedor configuráveis
* Botão para copiar a chave Pix após enviar a aposta
* Logs de erros do Pix disponíveis no submenu Logs
* Shortcode [bolao_x_login] para login e cadastro usando telefone e senha
* Após login ou cadastro o usuário é enviado ao formulário de apostas
* Shortcode [bolao_x_dashboard] mostra painel do apostador com ícones e atalhos
* Estatísticas com gráfico de barras
* Interface 2025 com efeito de vidro, botões em gradiente e layout responsivo estilo aplicativo
* Áreas dos shortcodes com visual claro e animações de entrada
* Shortcodes exibidos em contêiner “app” para visual mais moderno
* Escolha das dezenas em grade clicável
* Widget de resumo no painel e envio de e-mails automáticos com barras de progresso
* Premiação por "Menos Pontos" com acúmulo em caso de empate
* Todos os dados são removidos na desinstalação
* Pronto para tradução com arquivos `.pot` e `.po` em `/languages`
* Tradução brasileira disponível com o arquivo-fonte `bolao-x-pt_BR.po`. O `.mo` gerado deve permanecer fora do repositório

== Installation ==
1. Envie a pasta `bolao-x` para o diretório `wp-content/plugins`.
2. Ative o plugin no menu Plugins do WordPress.
3. Acesse o menu Bolao X para configurar e começar a usar.

== Usage ==
1. No menu **Bolao X**, abra a tela **Configurações**.
2. Insira uma chave Pix por linha no campo **Chaves Pix** e escolha qual delas ficará ativa.
3. Informe opcionalmente o nome e a cidade do recebedor.

== Development ==
Certifique-se de ter o PHP CLI e a extensão GD instalados (`apt-get install php-cli php8.3-gd zbar-tools`).
Execute `scripts/test.sh` para validar o plugin e `scripts/test_pix.sh` para gerar e verificar um QR Code de exemplo.

== Changelog ==
= 3.12.0 =
* Registro de erros do Pix gravado em `wp-content/uploads/bolao-x/pix-error.log` e visualização no submenu **Logs**
= 3.11.9 =
* Removido o suporte à confirmação automática de pagamentos via webhook
= 3.11.8 =
* Confirmação de aposta com números em círculos e sem exibir o TXID
= 3.11.7 =
* Ícones do painel do apostador alinhados verticalmente e novo ícone para resultados
= 3.11.6 =
* Novo shortcode `[bolao_x_dashboard]` com painel do apostador e ícones premium
= 3.11.5 =
* Resultados anteriores mostram data e dezenas em círculos destacados
= 3.11.4 =
* Resultados, repetidos e apostadores alinhados à esquerda
= 3.11.3 =
* Texto "RESULTADO DA SEMANA" exibido em maiúsculas
* Barra de porcentagem com fundo verde para melhor leitura
= 3.11.2 =
* Título "Resultado da Semana" usa o mesmo estilo de "NÚMEROS REPETIDOS"
* Card especial para a premiação "Menos Pontos"
= 3.11.0 =
* Quadro de dezenas marcando os números sorteados
* Área de "NÚMEROS REPETIDOS" exibindo os mais escolhidos
* Listagem de apostas com números em círculos e destaque nos acertos
= 3.8.3 =
* QR Code inclui TXID único ligado à aposta e webhook reconhece por TXID
= 3.8.2 =
* Webhook Pix agora usa assinatura HMAC configurada no admin
= 3.8.1 =
* Token do webhook movido para constante no código
= 3.8.0 =
* Integracao Pix reescrita do zero com payload e QR code validos
= 3.7.7 =
* Pagamentos Pix reprogramados para evitar erros de leitura do QR Code
= 3.7.6 =
* Correção no cálculo do CRC do QR Code Pix
= 3.7.5 =
* QR Code Pix gerado com payload padrao e imagem maior para melhor leitura
= 3.7.4 =
* Mensagens de "Login realizado com sucesso." e "Cadastro realizado com sucesso." redirecionam automaticamente para /participe
= 3.7.3 =
* Redireciona para a página do formulário após login ou cadastro
= 3.7.2 =
* Shortcode [bolao_x_login] com tela de login e cadastro via telefone
= 3.7.1 =
* Grade de dezenas ampliada com círculos maiores e animações
= 3.7.0 =
* Webhook para confirmação automática de pagamentos
* Token configurável e chave Pix editável
= 3.6.3 =
* Campo "Como quer ser chamado?" no formulário de aposta
* Área do apostador com login animado e troca de senha
= 3.6.2 =
* Premiação "Menos Pontos" com acúmulo em caso de empate
= 3.6.1 =
* Largura ampliada do layout mantendo responsividade
= 3.6.0 =
* Visual mais claro sem áreas escuras
* Animações de entrada e seleção aprimoradas nos shortcodes
= 3.5.0 =
* Contêiner app adiciona visual de aplicativo e responsividade extra
= 3.4.0 =
* Layout responsivo para dispositivos móveis
= 3.3.0 =
* Perfil do participante com atualização de dados
* Status do pagamento visível em [bolao_x_my_bets]
* Contagem regressiva até o horário limite
= 3.2.4 =
* Texto "Pague com Pix" acima do QR Code no formulário
= 3.2.3 =
* Campo "Nome Completo" agora usa input em largura total
= 3.2.2 =
* Ajuste de rótulos: "Nome Completo" e "Escolha 10 dezenas"
* Botão "APOSTE AGORA" em largura total
= 3.2.0 =
* Grade de dezenas clicável para facilitar a seleção.
= 3.1.0 =
* Visual atualizado com efeito de vidro e botões em gradiente.
= 3.0.0 =
* Todas as mensagens internas preparadas para tradução.
= 2.9.0 =
* Suporte a internacionalização com carregamento de text domain.
= 2.8.0 =
* Gráficos de barra nas estatísticas.

