# Chat-Pro

Este repositório contém um bot simples para demonstração de atendimento.
Ele pode ser executado de duas formas: via PHP diretamente no terminal ou,
caso tenha Node.js disponível, através do script em JavaScript.

## Executar com PHP
Instale o PHP 7.4 ou superior e rode:

```
php app.php
```

O bot exibirá o menu interativo. Use os números, digite `menu` para
recomeçar e `sair` para encerrar.

## Executar com Node.js (opcional)
Se preferir usar a versão em JavaScript, instale o Node.js (versão 16 ou
superior) e execute:

```
npm start
```

## Integrar ao WhatsApp Business
1. Instale as dependências com `npm install` e inicie o servidor:
   ```
   npm run server
   ```
2. Acesse `http://localhost:3000` (ou o domínio configurado, como
   `https://bot.studiolotus.com.br`) e preencha o token, o ID do telefone e o
   *verify token* nos campos da página.
3. Salve as configurações e informe a mesma URL `/webhook` no painel do
   WhatsApp Business Cloud API.

Depois de configurado, o bot responderá automaticamente às mensagens usando o
fluxo definido em `Bot.js` (ou `Bot.php`).
