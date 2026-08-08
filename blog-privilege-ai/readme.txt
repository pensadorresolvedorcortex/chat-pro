=== Blog Privilege AI — SEO para Yoast ===
Contributors: agenciaprivilege
Tags: seo, yoast, inteligência artificial, blog
Requires at least: 6.2
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.1.1
License: GPLv2 or later

Preenche automaticamente os principais campos do Yoast SEO para novos artigos e oferece otimização em massa para o conteúdo existente.

== Description ==

O plugin usa o título e o conteúdo de cada artigo para configurar:

* frase-chave de foco curta e natural;
* título SEO com nome do site;
* meta description com até 155 caracteres;
* título e descrição para Open Graph e Twitter;
* texto alternativo da imagem destacada, quando estiver vazio.

Valores preenchidos manualmente são preservados. Em Ferramentas > SEO do Blog Privilege AI, um editor pode processar posts antigos e, opcionalmente, sobrescrever metadados existentes.

Desenvolvedores podem usar o filtro `bpai_seo_focus_keyphrase` para informar a mesma frase-chave empregada pelo gerador no título, na introdução e nos subtítulos. Isso evita divergências entre a análise do Yoast e o texto produzido pela IA.

== Installation ==

1. Copie a pasta `blog-privilege-ai` para `wp-content/plugins` ou gere o pacote executando `bin/build-plugin-zip.sh` na raiz do projeto.
2. Se usar o pacote, envie `blog-privilege-ai.zip` em Plugins > Adicionar plugin > Enviar plugin.
3. Ative o plugin.
4. Acesse Ferramentas > SEO do Blog Privilege AI para otimizar os artigos existentes.
5. Abra um artigo no editor, confira a análise do Yoast e ajuste o texto quando necessário.

== Frequently Asked Questions ==

= O plugin garante o semáforo verde? =

Ele resolve os campos técnicos que normalmente ficam vazios. O Yoast também analisa a qualidade do texto, portanto o artigo precisa usar naturalmente a frase-chave no início, em um subtítulo e no corpo, além de conter links e imagens relevantes.

= Meus campos preenchidos serão apagados? =

Não. Por padrão, o plugin só preenche campos vazios. A substituição precisa ser marcada explicitamente na ferramenta de otimização em massa.

== Changelog ==

= 1.1.1 =

* Extração aprimorada de frases-chave para títulos em formato de lista ou com barras.
* Filtro para o gerador informar explicitamente a frase-chave usada no conteúdo.
* Compatibilidade com servidores sem a extensão PHP mbstring.

= 1.1.0 =

* Integração automática com os metadados do Yoast SEO.
* Ferramenta segura de otimização em massa.
* Metadados sociais e texto alternativo da imagem destacada.
