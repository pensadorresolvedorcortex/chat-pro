# Chat-Pro Bot via WhatsApp Web

Este projeto demonstra um bot de atendimento controlado por PHP que utiliza um navegador automatizado para se conectar ao WhatsApp Web. Dessa forma nenhuma chave de API ou token é necessária.

## Requisitos

- PHP 8 ou superior
- Composer para instalar as dependências
- Chrome/Chromium e `chromedriver` executando em `http://localhost:9515`

## Instalação

1. Instale as dependências com o Composer:
   ```bash
   composer install
   ```
2. Inicie o bot manualmente no terminal:
   ```bash
   php app.php
   ```
   ou acesse `index.php` em um servidor web e clique em **Iniciar Bot** para executá-lo em segundo plano.
3. O QR Code será gerado automaticamente e exibido na própria página `index.php` para ser escaneado com o WhatsApp.

Após autenticado, toda mensagem recebida poderá ser tratada pela árvore de diálogos definida em `Bot.php`.
