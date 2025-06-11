# Chat-Pro Bot (Node.js)

Este repositório contém um bot de atendimento em Node.js. Agora ele inclui um
servidor HTTP simples com **Express**, permitindo conversar com o bot pelo
navegador.

## Requisitos

- Node.js 16 ou superior
- PHP 7.4 ou superior (apenas para a página de configuração opcional)

## Instalação rápida

1. Instale as dependências:
   ```bash
   npm install
   ```
2. Inicie o servidor web do bot:
   ```bash
   npm start
   ```
   O servidor escutará na porta `3000`. Acesse `http://localhost:3000` e envie
   mensagens para o bot pela página inicial.

Para interagir diretamente pelo terminal, use:
```bash
npm run cli
```

## Configuração via navegador

Envie todos os arquivos para o servidor e abra `index.php`. Essa página permite
informar o **Token do WhatsApp Business**, o **ID do telefone** e o
**Verify Token**. As informações ficam em `config.json` (ignorado no Git).

## Passo a passo detalhado

Veja **SETUP.md** para instruções completas de instalação.

## Testes

Execute `npm test` para validar algumas interações básicas do bot.
