# Chat-Pro Bot via WhatsApp Web

Este projeto demonstra um bot de atendimento controlado por PHP que utiliza um navegador automatizado para se conectar ao WhatsApp Web. Dessa forma nenhuma chave de API ou token é necessária.

## Requisitos

- PHP 8 ou superior
- Composer para instalar as dependências
- Chrome/Chromium
- `chromedriver` em `./bin/` ou disponível em `http://localhost:9515`

## Instalação

1. Instale as dependências com o Composer:
   ```bash
   composer install
   ```
2. Baixe o Chromedriver adequado para seu sistema (Linux x64 por padrão):
   ```bash
   bash scripts/install_chromedriver.sh
   ```
   Esse comando fará o download para o diretório `bin/`.
3. Inicie o bot manualmente no terminal:
   ```bash
   php app.php
   ```
   ou acesse `index.php` em um servidor web e clique em **Iniciar Bot** para executá-lo em segundo plano.
4. Você também pode abrir `wweb-bot.php` diretamente no navegador para executar o processo e gerar o QR Code na tela.
   Em ambos os casos o QR Code será salvo como `qr.png` e exibido na página `index.php`.

### Observação sobre hospedagem

O script `wweb-bot.php` tenta iniciar automaticamente o `chromedriver` localizado em `bin/chromedriver`. Caso não exista um serviço escutando em `http://localhost:9515` e o binário não seja encontrado, o processo será abortado registrando o erro em `wweb.log`.
Você pode iniciar o driver manualmente executando:
```bash
bash scripts/start_chromedriver.sh
```

O botão de `index.php` apenas inicia esse script em segundo plano. Verifique o log caso o QR Code não seja gerado.

Após autenticado, toda mensagem recebida poderá ser tratada pela árvore de diálogos definida em `Bot.php`.
