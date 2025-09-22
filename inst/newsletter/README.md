# Newsletter Guell Almeida — Pacote Final

Este diretório agora contém a newsletter concluída com imagens reais hospedadas on-line, pronta para ser importada em plataformas como SFMC, Mailchimp ou Braze. O HTML está limpo, responsivo (largura fixa de 600&nbsp;px), com UTMs padronizadas e CTAs de WhatsApp funcionais.

## Como usar

1. Abra [`guell-newsletter.html`](guell-newsletter.html) e personalize os campos operacionais restantes (`{{link_versao_web}}`, `{{link_descadastro}}` e `{{email_guell}}`). Caso já tenha o e-mail oficial, substitua o placeholder diretamente.
2. Se preferir usar outros criativos, troque as URLs das imagens pelos seus próprios arquivos hospedados (veja a tabela abaixo com os tamanhos e fontes atuais).
3. Faça o QA em inbox preview/spam checker, valide os links e parta para o disparo utilizando os assuntos e preheaders sugeridos.

> **Observação:** todas as URLs já possuem o modelo de UTM `?utm_source=newsletter&utm_medium=email&utm_campaign=nl_guell_consultoria&utm_content={{bloco}}`. Ajuste apenas se a nomenclatura da campanha mudar.

## Imagens aplicadas (pode substituir se desejar)

| Bloco | Dimensão sugerida | URL utilizada | Fonte |
|-------|-------------------|---------------|-------|
| Logo | 160&nbsp;px de largura (máx. 600&nbsp;px) | `https://guellalmeida.com.br/images/logo.png` | Site oficial |
| Faixa topo | 600×82 (entregue @2x) | `https://dummyimage.com/1200x164/25d366/ffffff&text=Agende+sua+consultoria+on-line+gr%C3%A1tis` | DummyImage (texto customizável) |
| Hero | 600×400 | `https://images.pexels.com/photos/3184465/pexels-photo-3184465.jpeg?auto=compress&cs=tinysrgb&w=1200` | Pexels — Fauxels |
| Serviços — Mídia Social | 600×180 | `https://guellalmeida.com.br/images/portfolio/post1.jpg` | Site oficial |
| Serviços — Logotipos | 600×180 | `https://guellalmeida.com.br/images/portfolio/logotipo2.jpg` | Site oficial |
| Serviços — Marketing Digital | 600×180 | `https://images.pexels.com/photos/6476584/pexels-photo-6476584.jpeg?auto=compress&cs=tinysrgb&w=1200` | Pexels — Mikael Blomkvist |
| Serviços — Site Responsivo | 600×180 | `https://guellalmeida.com.br/images/portfolio/site2.jpg` | Site oficial |
| Serviços — Consultoria | 600×180 | `https://guellalmeida.com.br/images/portfolio/consultoria3.jpg` | Site oficial |
| Serviços — Vídeos Animados | 600×180 | `https://guellalmeida.com.br/images/portfolio/video2.jpg` | Site oficial |
| Portfólio 1 | 600×300 | `https://guellalmeida.com.br/images/portfolio/site3.jpg` | Site oficial |
| Portfólio 2 | 600×300 | `https://guellalmeida.com.br/images/portfolio/post5.jpg` | Site oficial |
| Portfólio 3 | 600×300 | `https://guellalmeida.com.br/images/portfolio/logotipo6.jpg` | Site oficial |
| Ícone Instagram | 28×28 | `https://cdn-icons-png.flaticon.com/512/2111/2111463.png` | Flaticon |
| Ícone Facebook | 28×28 | `https://cdn-icons-png.flaticon.com/512/733/733547.png` | Flaticon |
| Ícone YouTube | 28×28 | `https://cdn-icons-png.flaticon.com/512/733/733646.png` | Flaticon |

## Assuntos e preheaders sugeridos

| # | Assunto (50–60 c.) | Preheader (35–70 c.) |
|---|--------------------|-----------------------|
| 1 | Design que vende: agende sua consultoria on-line | Atendimento on-line, rápido e sem complicação. |
| 2 | Seu marketing pronto para crescer? Fale com o Guell | WhatsApp aberto: tire dúvidas e peça orçamento. |
| 3 | Do logotipo ao site: vamos tirar sua marca do papel | Mídia social, logotipos, sites, vídeos e mais. |
| 4 | Precisa de posts, site e anúncios? Eu cuido disso | Criação + performance com foco em resultado. |
| 5 | Resultados com design e estratégia — vamos juntos | Veja trabalhos e peça sua consultoria grátis. |
| 6 | Consultoria grátis: plano de ação para sua marca | Design profissional para sua empresa crescer. |
| 7 | Mais vendas com conteúdo e identidade consistentes | Layouts limpos, copy direto e CTAs que convertem. |
| 8 | Portfólio + serviços: veja como posso ajudar | Vamos começar hoje? Clique e fale comigo. |

## Mapeamento de links + UTMs

Todos os links seguem o padrão `?utm_source=newsletter&utm_medium=email&utm_campaign=nl_guell_consultoria&utm_content={{bloco}}`.

| Bloco | URL Base |
|-------|----------|
| `logo` | `https://guellalmeida.com.br/` |
| `faixa_topo`, `hero`, `cta_hero`, `cta_secundario` | `https://api.whatsapp.com/send?phone=5511985830211&text=Olá! Quero uma consultoria para minha empresa.` |
| `servico_midias` | `https://guellalmeida.com.br/#services` |
| `servico_logotipos` | `https://guellalmeida.com.br/#services` |
| `servico_mkt` | `https://guellalmeida.com.br/#services` |
| `servico_site` | `https://guellalmeida.com.br/#services` |
| `servico_consultoria` | `https://guellalmeida.com.br/#services` |
| `servico_videos` | `https://guellalmeida.com.br/#services` |
| `port1`, `port2`, `port3` | `https://guellalmeida.com.br/#portfolio` |
| `ig` | `https://www.instagram.com/guellalmeida/` |
| `fb` | `https://www.facebook.com/guell.almeida.3` |
| `yt` | `https://www.youtube.com/channel/UCUKVyxn5psJhLyxjn3c33qg/videos` |

## Checklist rápido de QA

- [x] Alt text aplicado em todas as imagens.
- [x] Botões com fallback em HTML (sem dependência de imagem).
- [x] Largura máxima 600&nbsp;px + imagens responsivas.
- [x] UTMs padronizadas em todos os links.
- [x] CTA WhatsApp presente no topo e no rodapé.
- [ ] Atualizar links de versão web, descadastro e e-mail oficial antes do disparo.
- [ ] Submeter a testes de renderização/spam antes do disparo.

## Prompts de imagem IA (caso queira gerar novas peças)

Mesmo com a versão final pronta, mantivemos os prompts originais para facilitar a produção de alternativas visuais, caso prefira trocar os criativos:

1. **Faixa topo (600×82)** — “Faixa horizontal clean, fundo claro com sutil textura, ícones minimalistas de design/marketing à esquerda, texto legível à direita ‘Agende sua consultoria on-line grátis’, estilo moderno, tipografia sem serifa, alto contraste, composição para e-mail 600×82.”
2. **Hero (600×400)** — “Banner hero para newsletter, conceito ‘Design que vende’, workspace criativo (laptop, layout, paleta de cores, post-its), luz natural suave, estética profissional, espaço negativo para texto, paleta neutra com acento verde, proporção 600×400, foco em clareza para e-mail.”
3. **Serviços (600×180)**
   - Mídia Social — “Ícones de redes sociais minimalistas em linha, gradiente sutil, estilo flat, 600×180, espaço para título.”
   - Logotipos — “Processo de marca: grids, caneta, curvas vetoriais, fundo claro, 600×180.”
   - Marketing Digital — “Tela com dashboard de métricas, setas de crescimento, clean, 600×180.”
   - Site Responsivo — “Mockups responsivos (desktop/tablet/mobile) alinhados, 600×180.”
   - Consultoria — “Mãos planejando estratégia com bloco de notas, café, 600×180, minimal.”
   - Vídeos Animados — “Clapboard/frames estilizados, linhas dinâmicas, 600×180.”
4. **Portfólio (600×300)** — “Mockup elegante de projeto de design (post de rede/logotipo/site), fundo neutro, 600×300, foco no layout.”

Pronto! Agora é só ajustar os detalhes operacionais e publicar. 🚀
