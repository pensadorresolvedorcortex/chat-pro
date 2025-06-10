# Passo a passo de configuração

Este guia orienta como preparar e executar o bot em Node.js contido neste repositório.

## 1. Requisitos

- **Node.js 16 ou superior** instalado no servidor ou computador.

## 2. Obter os arquivos

1. Faça o download deste repositório ou envie os arquivos via FTP/SSH para o servidor.
2. Coloque todo o conteúdo em uma pasta de sua escolha (ex.: `chat-pro/`).

## 3. Instalar as dependências

Acesse o terminal no diretório do projeto e execute:

```bash
npm install --silent
```

Esse comando prepara o `node_modules/` mesmo não havendo dependências externas.

## 4. Executar o bot

Ainda no terminal, rode:

```bash
npm start
```

Ou, se preferir especificar o arquivo manualmente:

```bash
node app.js
```

O menu inicial aparecerá no terminal e você poderá interagir digitando os números das opções. Use `menu` para recomeçar e `sair` para encerrar.

## 5. Testar (opcional)

Para executar os testes automáticos:

```bash
npm test --silent
```

Isso valida algumas respostas básicas do bot.

## 6. Personalização

- Edite `Bot.js` para ajustar a árvore de conversa.
- O arquivo `userDatabase.js` demonstra como buscar o nome do usuário de forma assíncrona.

Com esses passos, o bot estará pronto para uso no terminal. A integração com a API do WhatsApp Business pode ser adicionada futuramente conforme suas necessidades.

