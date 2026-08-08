=== Blog Privilege AI - SEO ===
Contributors: agenciaprivilege
Tags: seo, yoast, inteligência artificial, conteúdo
Requires at least: 6.2
Requires PHP: 7.4
Stable tag: 1.2.0
License: GPLv2 or later

Otimiza os artigos gerados pelo Blog Privilege AI para a análise on-page do Yoast SEO.

== Description ==

O módulo preenche frase-chave, título SEO, slug e metadescrição e melhora a estrutura
on-page de conteúdos gerados. Para proteger artigos escritos manualmente, ele atua
somente em posts com o metadado `_bpai_generated`.

O gerador pode informar uma frase-chave mais precisa em `_bpai_focus_keyphrase`.
Sem esse valor, a primeira parte do título (antes de dois-pontos, hífen ou barra
vertical) é usada, limitada a oito palavras.

Como posts criados pela API não passam pelo editor JavaScript do Yoast, o plugin
calcula um score transparente com critérios mensuráveis. O linkdex verde só é
gravado quando título, introdução, subtítulos, descrição, extensão, links,
imagens e densidade somam pelo menos 75 pontos. O score também fica disponível
em `_bpai_seo_score` para auditoria.

== Installation ==

1. Envie a pasta para `/wp-content/plugins/` ou instale o arquivo ZIP.
2. Ative o plugin.
3. Mantenha o Yoast SEO ativo.
4. Ao inserir um post gerado, envie `_bpai_generated => 1` em `meta_input`.

== Changelog ==

= 1.2.0 =
* Corrige a primeira publicação usando um hook executado após salvar `meta_input`.
* Score auditável para posts programáticos, sem forçar verde incondicionalmente.
* Texto alternativo em imagens e contagem de palavras compatível com português.
* Preserva slugs de posts já publicados para evitar mudanças de URL.

= 1.1.0 =
* Integração automática com os campos de SEO do Yoast.
* Frase-chave no início, subtítulos, link interno e conclusão quando ausentes.
* Proteção de conteúdo editorial não gerado.
