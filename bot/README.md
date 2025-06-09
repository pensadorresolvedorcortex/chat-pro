# Exemplo de Bot de Atendimento

Este diretório contém um bot em Node.js que demonstra um fluxo de atendimento baseado em menus. Ele simula a interação via WhatsApp Business, mas não está integrado a nenhuma API.

O usuário recebe uma mensagem inicial com várias opções. Ao selecionar uma delas, o bot apresenta submenus até encaminhar a conversa para um analista humano.

## Como executar

Instale o Node.js (versão 16 ou superior) e execute:

```
npm start
```

Use os números do menu ou digite `menu` para retornar à tela inicial. Digite `sair` para encerrar a conversa.

O bot busca o nome do usuário de forma assíncrona em `userDatabase.js`, permitindo integrar com bancos de dados reais ou APIs no futuro.

Este código pode servir de base para integrar futuramente a um webhook do WhatsApp Business ou serviços como Twilio.
