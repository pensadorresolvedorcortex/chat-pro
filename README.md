# Chat-Pro Bot via WhatsApp Web

Este projeto demonstra um bot de atendimento que utiliza a biblioteca
[whatsapp-web.js](https://github.com/pedroslopez/whatsapp-web.js) para se
conectar ao WhatsApp Web. Assim, nenhuma chave de API ou token é
necessário.

## Requisitos

- Node.js 16 ou superior

## Instalação

1. Instale as dependências. Caso não deseje baixar o Chromium usado pelo
   `whatsapp-web.js`, defina a variável `PUPPETEER_SKIP_DOWNLOAD=1`:
   ```bash
   PUPPETEER_SKIP_DOWNLOAD=1 npm install
   ```
2. Inicie o bot:
   ```bash
   npm start
   ```
3. Será exibido um QR Code no terminal. Escaneie com o WhatsApp e aguarde a
   confirmação de que o cliente está conectado. As credenciais ficam salvas
   localmente, evitando escanear novamente em execuções futuras.

Também é possível iniciar o bot a partir de uma página web em `index.php` (requer PHP 7.4 ou superior).
Ao acessar essa página e clicar em **Iniciar Bot**, o script `wweb-bot.mjs`
será executado em segundo plano e o QR Code aparecerá no terminal do servidor
(arquivo `wweb.log`).
Se o arquivo `wweb.log` indicar erro de dependências ausentes, execute `npm install` conforme instruções acima.

Após autenticado, toda mensagem recebida será processada pelo fluxo definido em
`Bot.js` e o bot responderá automaticamente.
