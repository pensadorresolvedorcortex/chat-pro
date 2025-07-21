=== Bolao X ===
Contributors: bolaox
Tags: lottery, bolao, mercadopago, csv, pdf, excel
Requires at least: 6.0
Tested up to: 6.5
Stable tag: 2.8.60
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Sistema completo para gerenciamento de bolão semanal. Permite cadastro de apostas, conferência automática, exportação de resultados e pagamento via Mercado Pago.

== Description ==
Plugin para gerenciamento de bolão com cadastro de apostas e conferência automática. Permite exportar resultados em CSV, Excel e PDF e gera um link de pagamento do Mercado Pago.
* Shortcode [bolao_x_login] para login e cadastro usando telefone e senha
* Após login ou cadastro o usuário é enviado ao formulário de apostas
* Shortcode [bolao_x_dashboard] mostra painel do apostador com ícones e atalhos
* Shortcode [bolao_x_minha_conta] exibe área do cliente para editar perfil e senha
* Estatísticas com gráfico de barras
* Interface 2025 com efeito de vidro, botões em gradiente e layout responsivo estilo aplicativo
* Áreas dos shortcodes com visual claro e animações de entrada
* Shortcodes exibidos em contêiner “app” para visual mais moderno
* Escolha das dezenas em grade clicável
* Widget de resumo no painel e envio de e-mails automáticos com barras de progresso
* Premiação por "Menos Pontos" com acúmulo em caso de empate
* Pagamento via Pix usando o e-mail do usuário logado
* Credenciais de produção e teste (Public Key e Access Token) com modo ativo
* Valor da aposta configurável e logs de pagamento acessíveis no admin
* Validador de credenciais do Mercado Pago e logs gerais no painel
* Chave Pix configurável para exibição no modal de pagamento
* Pagamentos Pix usam X-Idempotency-Key único e logs são truncados para melhor
  leitura
* As chamadas usam a constante `MP_API_URL` que aponta para `https://api.mercadopago.com`
* Botão "Pagar com Pix" mostra o QR Code em um modal e permite copiar o código pelo botão "Copie o Código" antes de apostar
* O QR Code é exibido usando a imagem base64 retornada pelo Mercado Pago
* Todos os dados são removidos na desinstalação
* Pronto para tradução com arquivos `.pot` e `.po` em `/languages`
* Tradução brasileira disponível com o arquivo-fonte `bolao-x-pt_BR.po`. O `.mo` gerado deve permanecer fora do repositório

== Installation ==
1. Envie a pasta `bolao-x` para o diretório `wp-content/plugins`.
2. Ative o plugin no menu Plugins do WordPress.
3. Acesse o menu Bolao X para configurar e começar a usar.

== Usage ==
1. No menu **Bolao X**, abra a tela **Configurações**.
2. Informe as credenciais do Mercado Pago para produção e teste (Public Key e Access Token).
3. Escolha o modo ativo (Teste ou Produção) e defina o valor da aposta em reais.
4. Opcionalmente, informe a chave Pix que será exibida ao gerar o QR Code.
5. Valide as credenciais pelo botão disponível e salve as alterações.

== Development ==
Instale o PHP CLI e extensões necessárias executando `../scripts/install-deps.sh`.
Depois execute `scripts/test.sh` para validar o plugin.

== Changelog ==
= 2.8.60 =
* Seleção de dezenas exibe contagem quando o mesmo número é escolhido mais de uma vez
= 2.8.59 =
* Permite pagar apostas pendentes diretamente em "Minhas Apostas"
* Corrige pagamento via Pix para usuários logados
* Seleção de dezenas aceita números repetidos
* Relatórios ignoram apostas não pagas
* Repetidos analisam apenas os últimos 5 resultados
= 2.8.58 =
* Ajusta o filtro de concursos exibindo sempre os 15 mais recentes com resultados

= 2.8.57 =
* Filtro de concurso exibe até 15 concursos recentes na página Contemplados
= 2.8.56 =
* "NÚMEROS REPETIDOS" em Contemplados exibe as dezenas em círculos novamente
= 2.8.55 =
* Evita sobrescrever as dezenas do resultado em salvamentos automáticos
= 2.8.54 =
* Filtro por concurso adicionado à lista de apostas no painel

= 2.8.53 =
* Corrige salvamento dos números do resultado e calcula acertos das apostas ao publicar
= 2.8.52 =
* Campo de dezenas do resultado agora orienta usar apenas 5 números, evitando falha ao salvar
= 2.8.51 =
* Tabela "NÚMEROS REPETIDOS" em Contemplados mostra dezenas duplicadas ou avisa quando não há repetições
= 2.8.50 =
* Remove o botão "Exportar Relatório" da página de Contemplados
= 2.8.49 =
* Corrige erro ao exportar PDF quando havia saída prévia
= 2.8.48 =
* Relatório em PDF inclui colunas de concurso e números sorteados
= 2.8.47 =
* Exporta relatório em PDF com seções de vencedores e todas as apostas
= 2.8.46 =
* Coluna "Números sorteados" exibe apenas texto, sem círculos, em "Contemplados"
= 2.8.45 =
* Corrige posicionamento das colunas de "Contemplados" com os acertos e números sorteados em suas colunas
= 2.8.44 =
* Ajusta colunas das tabelas em "Contemplados" e remove a coluna de tipo
= 2.8.43 =
* Permite salvar resultados com 5 dezenas e corrige validação ao editar resultados
= 2.8.42 =
* Corrige exibição das dezenas no relatório de contemplados
* Exportação em PDF do concurso selecionado
= 2.8.41 =
* Tela "Criar Concurso" exibe "Insira o Nome do Concurso" e página de Contemplados mostra vencedores com detalhes
= 2.8.40 =
* Filtro de concurso em "Minhas Apostas" agora lista apenas concursos com apostas do usuário
= 2.8.39 =
* Correção do filtro de concurso em "Minhas Apostas" e mensagem de "Nenhuma aposta encontrada" quando não há jogos
= 2.8.38 =
* Graficos de visitas, usuarios e plataformas na pagina principal do plugin

= 2.8.37 =
* Sessão agora usa cookie padrão do WordPress para evitar erro "Erro ao carregar apostas"
* Autenticação limpa a sessão e os cookies corretamente ao sair
= 2.8.36 =
* Ajuste no filtro "Minhas Apostas" com nova mensagem de erro e estilo
= 2.8.35 =
* Corrige erro ao carregar apostas ao mudar de concurso
= 2.8.34 =
* Corrige erro ao filtrar "Minhas Apostas" por concurso
= 2.8.33 =
* "Minhas Apostas" exibe filtro de concurso com visual moderno e atualização instantânea
= 2.8.32 =
* "Minhas Apostas" agora permite filtrar por concurso para consultar jogos de edições anteriores
= 2.8.31 =
* Novo shortcode `[bolao_x_minha_conta]` para exibir e editar o perfil do usuário
= 2.8.30 =
* Corrigida criação da sessão quando o login é enviado após o carregamento da página
* Plugin agora define cookie via JavaScript e redireciona corretamente
= 2.8.29 =
* Aposta grava telefone informado e redireciona para o login com o número pré-preenchido
* Login ou cadastro levam o apostador para "Minhas Apostas" mostrando apostas do telefone
= 2.8.28 =
* Formulário de apostas acessível sem login
= 2.8.27 =
* Painel inicial removido: acesso direto ao formulário de apostas
= 2.8.26 =
* Redirecionamento imediato para o formulário de apostas após login ou cadastro
= 2.8.25 =
* Cadastro e login agora redirecionam para a escolha das dezenas
* Campo "Como quer ser chamado?" utilizado como nome do apostador
= 2.8.24 =
* Login obrigatório antes de apostar com formulário de cadastro exibido no lugar do formulário de apostas
= 2.8.23 =
* Campo "Como quer ser chamado?" aparece antes de "Telefone" nos formulários
= 2.8.22 =
* Removido campo de e-mail do formulário de aposta e notificação de senha temporária
= 2.8.21 =
* Campo de e-mail no formulário de aposta para criar conta automática
* Mensagem informando o envio da senha temporária por e-mail
= 2.8.20 =
* Cadastro automático de usuário ao pagar aposta quando não estiver logado
= 2.8.19 =
* Redireciona automaticamente para /login-cadastro ao acessar "Minhas Apostas" ou "Cadastro" sem estar logado
= 2.8.18 =
* Aprimora login e cadastro com validações e sessão de 1h
= 2.8.17 =
* Corrige exibição das apostas do usuário após login
= 2.8.16 =
* Aprimora visual do carrinho de apostas e esconde botão Pix quando vazio
= 2.8.15 =
* Ajusta página de Contemplados com filtro por concurso e opção de exportar relatório
= 2.8.14 =
* Corrige página em branco após login usando redirecionamento dinâmico
= 2.8.13 =
* Corrige o redirecionamento após login para a página solicitada
= 2.8.2 =
* Configuração do valor da aposta e página de logs do Mercado Pago
= 2.8.1 =
* Barras de porcentagem com animação gradiente
= 2.8.0 =
* Pagamentos integrados ao Mercado Pago com seleção de conta ativa

= 2.7.1 =
* Webhook de confirmação automática de pagamentos Pix via token no código
= 2.7.0 =
* Primeiro suporte a pagamento via Pix com QR Code
= 3.12.1 =
* Correção de avisos de índice indefinido ao gerar o QR Code

= 3.12.0 =
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

