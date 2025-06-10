# Chat-Pro Bot via WhatsApp Web

Este projeto demonstra um bot de atendimento que utiliza a biblioteca
[whatsapp-web.js](https://github.com/pedroslopez/whatsapp-web.js) para se
conectar ao WhatsApp Web. Assim, nenhuma chave de API ou token é
necessário.

## Requisitos

- Node.js 16 ou superior

## Instalação

1. Instale as dependências:
   ```bash
   npm install
   ```
2. Inicie o bot:
   ```bash
   npm start
   ```
3. Será exibido um QR Code no terminal. Escaneie com o WhatsApp e aguarde a
   confirmação de que o cliente está conectado. As credenciais ficam salvas
   localmente, evitando escanear novamente em execuções futuras.

Após autenticado, toda mensagem recebida será processada pelo fluxo definido em
`Bot.js` e o bot responderá automaticamente.
