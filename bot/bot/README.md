# Chat-Pro Bot via WhatsApp Business

Este projeto demonstra um bot de atendimento escrito em PHP que se comunica diretamente com a **API do WhatsApp Business**. Dessa forma não é necessário recorrer a plataformas externas como Blip ou Zendesk, evitando custos adicionais.

## Requisitos

- PHP 8 ou superior com extensão cURL habilitada
- Token de acesso e ID do número de telefone obtidos na Cloud API do WhatsApp Business

## Uso rápido

1. Copie o arquivo `.env.example` para `.env` e preencha `WABA_TOKEN` e `WABA_PHONE_ID` com seus dados.
2. Execute o bot via terminal:
   ```bash
   php waba-bot.php
   ```
3. Informe o telefone do destinatário com código do país. As mensagens serão enviadas através da API e o fluxo de atendimento é definido em `Bot.php`.

O arquivo `wweb-bot.php` permanece como exemplo de integração via WhatsApp Web, caso seja necessário.
