# Pesquisa de Preços no PNCP

Esta aplicação web permite consultar contratações e contratos diretamente no Portal Nacional de Contratações Públicas (PNCP), com filtros, paginação e exibição de detalhes dos registros. O projeto inclui um servidor Node/Express que atua como orquestrador das chamadas às APIs públicas do PNCP e uma interface estática em HTML/CSS/JS hospedada pelo mesmo servidor.

## Pré-requisitos

- [Node.js](https://nodejs.org/) 20 ou superior (recomendado) e npm
- Acesso HTTPS à internet ou a um proxy corporativo capaz de alcançar `https://pncp.gov.br`

## Instalação

```bash
npm install
```

## Execução local

### Modo padrão

```bash
npm start
```

O servidor será iniciado em `http://localhost:3000` (ou na porta definida pela variável `PORT`). A página inicial renderiza a interface de pesquisa, enquanto as rotas `/api/*` fazem proxy para as APIs do PNCP.

### Desenvolvimento com recarga automática

```bash
npm run dev
```

Esse comando inicia o servidor com `node --watch`, reiniciando automaticamente ao detectar alterações nos arquivos.

## Variáveis de ambiente suportadas

| Variável | Descrição |
| --- | --- |
| `PORT` | Porta local em que o servidor Express será iniciado. Valor padrão: `3000`. |
| `PNCP_TIMEOUT_MS` | Tempo máximo (em ms) para aguardar a resposta das APIs do PNCP. Valor padrão: `20000`. |
| `HTTP_PROXY` / `HTTPS_PROXY` | URLs de proxy para ambientes corporativos. Quando definidos, as requisições ao PNCP são roteadas por esse proxy. |

As variáveis podem ser definidas inline ao executar os comandos, por exemplo:

```bash
PNCP_TIMEOUT_MS=30000 npm start
```

## Testando a API

Após iniciar o servidor, você pode validar rapidamente as rotas internas usando `curl`:

```bash
# Lista modalidades de contratação
curl -s "http://localhost:3000/api/modalidades" | jq '.[0]'

# Consulta publicações de contratações em um intervalo de datas
curl -s "http://localhost:3000/api/contratacoes/publicacao?dataInicial=2024-01-01&dataFinal=2024-01-31&modalidadeId=6&pagina=1&tamanhoPagina=10" \
  | jq '.data | length'

# Consulta contratos publicados no período
curl -s "http://localhost:3000/api/contratos?dataInicial=2024-01-01&dataFinal=2024-01-31&pagina=1&tamanhoPagina=10" \
  | jq '.data | length'
```

A interface web consome essas mesmas rotas por meio de requisições AJAX. Ao abrir `http://localhost:3000` no navegador, você poderá selecionar filtros, navegar pelas páginas de resultados e visualizar detalhes de cada registro.

## Execução em endereço temporário

Caso precise compartilhar rapidamente a aplicação em um endereço público temporário sem infraestrutura dedicada, é possível utilizar ferramentas como [LocalTunnel](https://github.com/localtunnel/localtunnel) ou [ngrok](https://ngrok.com/). Exemplo usando LocalTunnel (não requer instalação global):

```bash
# Em um terminal, inicie o servidor normalmente
npm start

# Em outro terminal, exponha a porta 3000
npx localtunnel --port 3000
```

O comando exibirá uma URL pública (ex.: `https://seu-subdominio.loca.lt`) que pode ser compartilhada para acesso temporário. Encerrar o processo do LocalTunnel ou do servidor local invalida o endereço.

> **Observação:** use essas ferramentas apenas se a política de segurança da sua organização permitir, pois o tráfego passará pelos servidores do serviço escolhido.

## Estrutura do projeto

- `server.js`: servidor Express que fornece as rotas `/api` para o PNCP e serve os arquivos estáticos.
- `public/index.html`: página principal com o layout do formulário de busca e resultados.
- `public/app.js`: lógica de front-end responsável por carregar filtros, realizar buscas e exibir detalhes.
- `public/styles.css`: estilos da aplicação.

Com esses passos você conseguirá implementar ajustes na interface/servidor, testar localmente e disponibilizar temporariamente o sistema para homologação.
