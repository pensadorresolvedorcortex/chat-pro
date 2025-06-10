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

## Integrar ao WhatsApp Business (PHP)
1. Envie todos os arquivos deste projeto para `https://www.studioprivilege.com.br/bot`.
2. Acesse `https://www.studioprivilege.com.br/bot/index.php` e preencha o **Token**,
   o **ID do Telefone** e o **Verify Token** fornecidos pela API do WhatsApp Business.
3. No painel da API, cadastre `https://www.studioprivilege.com.br/bot/webhook.php`
   como URL do webhook, utilizando o mesmo Verify Token definido no passo anterior.
4. Após salvar, o bot passará a responder automaticamente às mensagens
   utilizando o fluxo definido em `Bot.php`.

### Integrar via Node.js (opcional)
Caso prefira utilizar o servidor em JavaScript, instale o Node.js 16 ou superior
e execute os comandos abaixo:

```
npm install
npm run server
```

Em seguida acesse `http://localhost:3000` (ou o domínio configurado) para
informar as credenciais e utilize a mesma URL `/webhook` no painel do WhatsApp
Business Cloud API.
