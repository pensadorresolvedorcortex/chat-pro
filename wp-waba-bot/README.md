# Plugin WABA Bot

Este plugin do WordPress integra o bot de atendimento usando a API do WhatsApp Business.

## Instalação
1. Copie o diretório `wp-waba-bot` para a pasta `wp-content/plugins` de seu WordPress.
2. Ative o plugin no painel de administração.
3. Acesse *Configurações > WABA Bot* e informe o token de acesso e o ID do número de telefone obtidos na Cloud API.

## Uso
Insira o shortcode `[waba_bot]` em qualquer página ou post para exibir um chat.
O campo de telefone deve ser preenchido com o número no formato internacional.

O chat possui estilo próprio em `waba-bot.css` e permite enviar mensagens com
um clique ou pressionando **Enter**. As mensagens são processadas conforme
definido em `Bot.php` e encaminhadas para o WhatsApp Business API.

Este plugin é apenas um exemplo e pode ser adaptado conforme a necessidade do
seu projeto.
