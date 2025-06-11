# Passo a passo de configuração

Este guia mostra como executar o bot desta aplicação, seja via terminal ou pelo
navegador.

## 1. Requisitos

- **Node.js 16 ou superior** instalado no servidor ou computador.

## 2. Obter os arquivos

1. Baixe este repositório ou envie os arquivos via FTP/SSH para o servidor.
2. Coloque todo o conteúdo em uma pasta de sua escolha (ex.: `chat-pro/`).

## 3. Instalar as dependências

Abra o terminal no diretório do projeto e execute:

```bash
npm install
```

Isso criará o diretório `node_modules/` com o Express e demais dependências.

## 4. Executar o bot no navegador

Ainda no terminal, rode:

```bash
npm start
```

O servidor Express iniciará na porta 3000. Acesse
`http://localhost:3000` (ou o domínio configurado) e converse com o bot pela
página exibida.

Para usar o bot diretamente no terminal, execute:

```bash
npm run cli
```

## 5. Testar (opcional)

Para rodar os testes automáticos:

```bash
npm test --silent
```

Isso verifica algumas respostas básicas do bot.

## 6. Personalização

- Edite `Bot.js` para ajustar a árvore de conversa.
- `userDatabase.js` demonstra como buscar o nome do usuário de forma assíncrona.

Com esses passos, você terá um bot funcionando pelo navegador ou terminal.
