# Exemplo de Bot de Atendimento

Este diretório contém um bot em Node.js que demonstra um fluxo de atendimento baseado em menus. Ele pode rodar somente no terminal ou integrado ao WhatsApp Business através do servidor em `../server.mjs`.

O usuário recebe uma mensagem inicial com várias opções. Ao selecionar uma delas, o bot apresenta submenus até encaminhar a conversa para um analista humano.

## Executar no terminal
Instale o Node.js (versão 16 ou superior) e execute:

```
npm start
```

## Executar integrado ao WhatsApp Business
1. Instale as dependências e inicie o servidor:
   ```
   npm install
   npm run server
   ```
2. Abra `http://localhost:3000` e informe as credenciais da API na página de configuração. Use a mesma URL `/webhook` no painel do WhatsApp Business.

O bot buscará o nome do usuário de forma assíncrona em `userDatabase.js`, permitindo integrar com bancos de dados reais ou APIs no futuro.

