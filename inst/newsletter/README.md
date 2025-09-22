# Newsletter Guell Almeida — Pacote Final

Este diretório traz a versão final da newsletter em HTML, pronta para uso em ESPs como SFMC/Mailchimp, sem dependência de arquivos binários.

## Como usar

1. Gere ou selecione as imagens seguindo as dimensões indicadas na seção **Prompts de imagem IA**.
2. Publique cada arquivo no seu CDN, S3 ou biblioteca de mídia e anote as URLs públicas resultantes.
3. No arquivo [`guell-newsletter.html`](guell-newsletter.html), substitua cada placeholder `{{url_*}}` pela URL hospedada correspondente.
4. Ajuste os placeholders `{{link_versao_web}}`, `{{link_descadastro}}`, `{{email_guell}}` e `{{ano_atual}}` de acordo com a sua operação.
5. Valide o HTML em ferramentas de inbox preview/QA e faça o disparo com os assuntos/preheaders abaixo.

> **Observação:** o HTML mantém todos os links de CTA já com UTMs padrão. Altere apenas se sua campanha utilizar parâmetros diferentes.

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
- [ ] Validar links de versão web e descadastro conforme sua operação.
- [ ] Submeter a testes de renderização/spam antes do disparo.

## Prompts de imagem IA (referência para geração)

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

Pronto! Basta gerar as imagens preferidas, apontar as URLs nos placeholders e colar o HTML no seu ESP. 🚀
