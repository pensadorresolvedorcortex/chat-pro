# Sorteio de Times

Interface web para sortear três times equilibrados. Baseado no mapa mental do projeto.

Os jogadores editados e os últimos times sorteados ficam salvos no seu navegador (localStorage).

Os botões permitem salvar a lista de times na área de transferência ou limpar apenas os times sorteados. A interface utiliza as fontes Outfit e Plus Jakarta Sans para um visual moderno.

Se a página não carregar ou ficar em branco, tente limpar os dados salvos do navegador (localStorage) e recarregue o aplicativo.

O aplicativo é responsivo e funciona em telas de desktop e dispositivos móveis. Para distribuir os arquivos de forma estática, execute a etapa de build e abra o arquivo `dist/index.html`.

## Desenvolvimento

```bash
npm install
npm run dev
```

Para gerar os arquivos de produção:

```bash
npm run build
npm run preview
```

Após a instalação das dependências, abra `http://localhost:5173` para acessar a interface.
