# Checkout media

Os placeholders vetoriais usados pelo plugin agora são gerados inline pelo código-fonte, evitando
qualquer arquivo binário no repositório. Caso prefira substituí-los por ilustrações próprias, atualize
as funções em `JuntaPlay\Assets\Illustrations` com o novo SVG sanitizado.

## Assets externos obrigatórios

Arquivos raster como `.png`, `.gif` ou vídeos continuam fora do controle de versão. Publique-os em um CDN
ou em outra infraestrutura da Juntaplay e aponte as URLs correspondentes pelo painel administrativo.

### Referências esperadas em produção

- `agradecimento.gif` — utilizado na segunda etapa da tela de agradecimento do checkout.
- `juntaplay.png` — logotipo exibido no cabeçalho dos e-mails transacionais.

Garanta que esses arquivos estejam disponíveis manualmente no ambiente final, mantendo os mesmos nomes
para preservar a compatibilidade com o código-fonte.
