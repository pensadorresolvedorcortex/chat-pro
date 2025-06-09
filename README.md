# Chat-Pro

Este repositório contém um bot simples em Node.js. Ele pode rodar apenas no terminal ou integrado à API oficial do WhatsApp Business.

## Executar no terminal
Instale o Node.js (versão 16 ou superior) e execute:

```
npm start
```

O bot exibirá o menu interativo. Use os números, digite `menu` para recomeçar e `sair` para encerrar.

## Integrar ao WhatsApp Business
1. Instale as dependências com `npm install` e inicie o servidor:
   ```
   npm run server
   ```
2. Acesse `http://localhost:3000` (ou o domínio configurado, como `https://bot.studiolotus.com.br`) e preencha o token, o ID do telefone e o *verify token* nos campos da página.
3. Salve as configurações e informe a mesma URL `/webhook` no painel do WhatsApp Business Cloud API.

Depois de configurado, o bot responderá automaticamente às mensagens usando o fluxo definido em `Bot.js`.

