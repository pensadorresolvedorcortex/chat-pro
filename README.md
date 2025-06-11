# Chat-Pro Bot (Node.js)

Este repositório contém um bot de atendimento executado no terminal utilizando Node.js. A lógica segue uma árvore de diálogos baseada nas soluções oferecidas pela Agência Privilége.

## Requisitos

- Node.js 16 ou superior
- PHP 7.4 ou superior (apenas para a página de configuração)

## Instalação rápida

1. Instale as dependências (nenhuma é necessária além do Node.js, mas o comando prepara o projeto):
   ```bash
   npm install
   ```
2. Execute o bot:
   ```bash
   npm start
   ```
   O menu inicial será exibido e você poderá interagir digitando os números das opções. Digite `menu` para recomeçar ou `sair` para encerrar.

## Configuração via navegador

Envie todos os arquivos do projeto para o servidor e acesse `index.php` em seu domínio. Nessa página é possível informar o **Token do WhatsApp Business**, o **ID do telefone** e o **Verify Token**. As informações são salvas em `config.json` (que permanece fora do controle de versões).

## Passo a passo detalhado

Consulte o arquivo **SETUP.md** para instruções completas de configuração.

## Testes

Execute `npm test` para validar algumas interações básicas do bot.
