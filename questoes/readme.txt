=== Questões Academia da Comunicação ===
Contributors: academiadacomunicacao
Requires at least: 6.0
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Plugin para exibir mapas mentais e organogramas responsivos a partir de dados JSON validados.

== Descrição ==

O plugin Questões Academia da Comunicação fornece um shortcode e um bloco Gutenberg para incorporar visualizações de mapa mental e organograma em páginas e posts. Os dados podem ser armazenados nas configurações do plugin, enviados por arquivo JSON ou carregados de um endpoint remoto.

== Instalação ==

1. Faça o upload da pasta `questoes` para o diretório `/wp-content/plugins/`.
2. Ative o plugin através do menu "Plugins" no WordPress.
3. Acesse "Questões" no menu do administrador para configurar título padrão, dados e preferências de acessibilidade.

== Shortcode ==

Use `[questoes modo="mapa|organograma|ambos" titulo="Título opcional"]{JSON opcional}[/questoes]`.

== Bloco Gutenberg ==

Adicione o bloco "Questões – Mapa/Organograma" e configure o modo, o título e os dados diretamente no editor.

== Changelog ==

= 0.1.0 =
* Versão inicial com shortcode, bloco e página de configurações.
