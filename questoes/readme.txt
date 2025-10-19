=== Questões Academia da Comunicação ===
Contributors: academiadacomunicacao
Requires at least: 6.0
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 0.8.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Plugin para exibir mapas mentais e organogramas responsivos a partir de dados JSON validados.

== Descrição ==

O plugin Questões Academia da Comunicação fornece shortcodes, um bloco Gutenberg e widgets Elementor para incorporar visualizações de mapa mental/organograma e um catálogo navegável de questões diretamente em páginas e posts. Os dados podem ser armazenados nas configurações do plugin, enviados por arquivo JSON ou carregados de um endpoint remoto.

Além das visualizações, o plugin oferece um banco de questões completo com cadastro de alternativas, gabarito, comentários, tempo estimado, categorias e bancas. As questões podem ser criadas via painel do WordPress ou pela API REST (`/questoes/v1/questions`), permitindo integrações com plataformas externas de preparação e disponibilização em uma biblioteca filtrável para os estudantes.

Recursos principais:

* Cadastro de questões com editor rico, comentários e anexos.
* Alternativas ilimitadas com feedback individual e validação de gabarito.
* Classificação por categoria hierárquica, banca, assuntos temáticos e tipo de questão.
* Campos adicionais para dificuldade, referência bibliográfica, fonte, ano de aplicação, links externos, vídeo de comentário e tempo estimado.
* Endpoints REST para consulta e criação/atualização programática das questões.
* Interface administrativa seguindo a identidade visual (#242142, #bf83ff, #c3e3f3) com foco em acessibilidade e contraste.
* Shortcode e widget Elementor para exibir o banco de questões com filtros, paginação e destaque de gabarito.
* Shortcode de busca dedicado que carrega as questões somente após a pesquisa do usuário.
* Importação em massa por JSON com pacote de 20 questões comentadas pronto para uso.
* Alternativas interativas: respostas reveladas somente após seleção, feedback imediato e comentários por questão com formulário integrado.

== Instalação ==

1. Faça o upload da pasta `questoes` para o diretório `/wp-content/plugins/`.
2. Ative o plugin através do menu "Plugins" no WordPress.
3. Acesse "Questões" no menu do administrador para configurar título padrão, dados e preferências de acessibilidade.

== Shortcode ==

Use `[questoes modo="mapa|organograma|ambos" titulo="Título opcional"]{JSON opcional}[/questoes]`.

Para listar o banco de questões com filtros e paginação utilize `[questoes_banco titulo="Banco de Questões" mostrar_filtros="sim" por_pagina="10"]`.

Para exibir apenas o formulário de busca e carregar resultados após a pesquisa use `[questoes_busca titulo="Encontre Questões" mostrar_filtros="sim" por_pagina="10"]`.

== Bloco Gutenberg ==

Adicione o bloco "Questões – Mapa/Organograma" e configure o modo, o título e os dados diretamente no editor.

== Elementor ==

No Elementor você pode utilizar os widgets "Questões – Mapa/Organograma" e "Questões – Banco" para configurar cada experiência visual diretamente no editor, incluindo filtros, paginação e dados personalizados. Alternativamente, use o widget de shortcode padrão com os exemplos listados na página de configurações do plugin.

== Changelog ==

= 0.8.0 =
* Cria o shortcode `[questoes_busca]` com formulário dedicado que mantém o catálogo oculto até que o visitante pesquise ou aplique filtros.
* Adiciona campo de palavra-chave e suporte ao parâmetro `search` na camada JavaScript para filtrar questões via REST.
* Ajusta estilos e mensagens informativas para guiar os usuários na nova experiência de busca.

= 0.7.0 =
* Atualiza a experiência de estudo: alternativas passam a revelar acerto/erro apenas após a seleção, com feedback individual e gabarito expandido automaticamente.
* Habilita comentários dentro de cada cartão de questão com contador, formulário estilizado e suporte ao script `comment-reply` nas requisições AJAX.
* Renova o pacote de 20 questões comentadas com conteúdo inédito alinhado ao novo fluxo de interação.

= 0.6.0 =
* Inclui pacote com 20 questões comentadas e importação guiada diretamente na página do plugin.
* Adiciona importação por arquivo ou JSON colado com criação/atualização em massa das questões existentes.
* Exibe avisos de sucesso/erro após a importação e link para o modelo JSON alinhado à identidade Questões.

= 0.5.0 =
* Adiciona taxonomia de assuntos, filtros por formato e ano, além de campos para vídeo e links oficiais em cada questão.
* Expande o shortcode e o widget do banco de questões com filtros avançados, seleção por assunto e suporte a Elementor/REST.
* Melhora os cartões das questões com metadados adicionais e links de apoio para provas e comentários em vídeo.

= 0.4.1 =
* Corrige a detecção do Elementor para evitar erros fatais quando o construtor não está ativo e exibe aviso amigável no painel.

= 0.4.0 =
* Adiciona shortcode e widget Elementor para exibição do banco de questões com filtros dinâmicos, paginação e destaque de gabarito.
* Cria cartões acessíveis para questões com botão de revelar resposta, metadados e comentários do gabarito.
* Atualiza scripts e estilos para suportar carregamento assíncrono de questões via REST e visualizações no Elementor.

= 0.3.0 =
* Adiciona integração nativa com Elementor, incluindo widget dedicado, categoria e carregamento automático de estilos/scripts para pré-visualização.
* Registra os shortcodes disponíveis diretamente no painel administrativo com exemplos prontos para uso.

= 0.2.0 =
* Adiciona banco de questões com post type dedicado, taxonomias de categoria e banca, e metadados de dificuldade, referência e tempo.
* Inclui interface de alternativas com validação de gabarito, feedback individual e experiência administrativa coerente com a paleta definida.
* Disponibiliza endpoints REST para listagem e criação de questões, facilitando integrações externas e automações.

= 0.1.0 =
* Versão inicial com shortcode, bloco e página de configurações.
