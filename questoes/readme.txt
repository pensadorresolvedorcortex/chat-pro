=== Questões Academia da Comunicação ===
Contributors: academiadacomunicacao
Requires at least: 6.0
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 0.14.0
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
* Catálogo de cursos alimentado pelas categorias, com destaques configuráveis e integração com Elementor.
* Ordenação manual dos destaques de cursos, resumos numéricos de disponibilidade e filtros com contadores dinâmicos.
* Shortcode “Em alta” reunindo automaticamente as questões, cursos e disciplinas mais acessados com indicadores de desempenho.
* Painel “Meus Conhecimentos” com resumo das sessões iniciadas, retomada rápida de estudos e remoção manual do progresso.

== Instalação ==

1. Faça o upload da pasta `questoes` para o diretório `/wp-content/plugins/`.
2. Ative o plugin através do menu "Plugins" no WordPress.
3. Acesse "Questões" no menu do administrador para configurar título padrão, dados e preferências de acessibilidade.

== Shortcode ==

Use `[questoes modo="mapa|organograma|ambos" titulo="Título opcional"]{JSON opcional}[/questoes]`.

Para listar o banco de questões com filtros e paginação utilize `[questoes_banco titulo="Banco de Questões" mostrar_filtros="sim" por_pagina="10"]`.

Para exibir apenas o formulário de busca e carregar resultados após a pesquisa use `[questoes_busca titulo="Encontre Questões" mostrar_filtros="sim" por_pagina="10"]`.

Para destacar as disciplinas por categoria com filtros de palavra-chave e área utilize `[questoes_disciplinas titulo="Disciplinas" descricao="Principais áreas"]`.

Para apresentar o catálogo de cursos com destaques e cards modernizados utilize `[questoes_cursos titulo_destaques="Cursos em alta" limite_destaques="6"]` ou o alias `[academia_cursos]`.

Para reunir automaticamente questões, cursos e disciplinas mais acessados utilize `[questoes_em_alta titulo="Em alta agora" limite_questoes="6" limite_cursos="6" limite_disciplinas="6"]`.

Para listar conteúdos em andamento de cada usuário, exibindo sessões de questões e cursos com retomada rápida, utilize `[questoes_meus_conhecimentos titulo="Meus conhecimentos" descricao="Retome rapidamente seus estudos"]`.

== Bloco Gutenberg ==

Adicione o bloco "Questões – Mapa/Organograma" e configure o modo, o título e os dados diretamente no editor.

== Elementor ==

No Elementor você pode utilizar os widgets "Questões – Mapa/Organograma", "Questões – Banco" e "Questões – Cursos" para configurar cada experiência visual diretamente no editor, incluindo filtros, paginação e dados personalizados. Alternativamente, use o widget de shortcode padrão com os exemplos listados na página de configurações do plugin.

== Changelog ==

= 0.14.0 =
* Cria o shortcode `[questoes_meus_conhecimentos]` com painel pessoal de retomada, listando sessões de questões e cursos iniciadas.
* Registra o progresso de questões e cursos no navegador e na REST API (quando autenticado) para manter o painel sincronizado entre visitas.
* Atualiza os shortcodes de banco de questões para emitir metadados de sessão, permitindo retomar filtros e página a partir do painel.

= 0.13.0 =
* Cria o shortcode `[questoes_em_alta]` para montar a página "Em alta" com cards de questões, cursos e disciplinas mais acessados.
* Registra visualizações de questões e cursos tanto nas requisições REST quanto nos shortcodes para alimentar os rankings automáticos.
* Inclui o novo layout público com seção em destaque, cards analíticos e estilos responsivos alinhados à largura de 1370px.

= 0.12.2 =
* Atualiza o pacote de cursos de exemplo com seis cards alinhados ao layout de referência para importação direta pelo dashboard.
* Documenta o conteúdo do pacote sugerido na página administrativa para facilitar a validação visual.

= 0.12.1 =
* Adiciona formulários de cadastro rápido para cursos, categorias e bancas diretamente na página do plugin.
* Atualiza os cartões do catálogo de cursos com novos títulos auxiliares, destaques e indicadores alinhados ao referencial enviado.
* Ajusta o container principal `.questoes-stage` e os estilos administrativos para manter a legibilidade no novo layout.

= 0.12.0 =
* Adiciona o cadastro de cursos (CPT, categorias e bancas) com metacampos para faixa salarial, oportunidades, destaque e prioridade.
* Inclui importador de cursos no painel com pacote de exemplo para validar rapidamente o layout.
* Atualiza os shortcodes `[questoes_cursos]` e `[academia_cursos]` para exibir os novos cards e estatísticas do catálogo dedicado.
* Simplifica a vitrine pública removendo filtros regionais e refinando o design dos cards conforme o referencial enviado.

= 0.11.1 =
* Redesenha o herói e os cartões do catálogo de cursos para refletir com fidelidade o layout de referência, com contraste e espaçamento aprimorados.
* Atualiza filtros regionais, resumos numéricos e estados responsivos para garantir legibilidade em telas largas e mobile.

= 0.11.0 =
* Permite definir a ordem dos cursos em destaque diretamente nos metadados da categoria.
* Exibe resumos de cursos e questões catalogadas, filtros com contadores visuais e status atualizado conforme a região.
* Ajusta estilos e scripts para alinhar o catálogo de cursos ao layout de referência, mantendo acessibilidade e contraste.

= 0.10.0 =
* Adiciona o shortcode `[questoes_cursos]` com vitrine de destaques, filtros por região e integração com as categorias do banco de questões.
* Inclui metadados configuráveis para as categorias (faixa salarial, badge, região, link e ícone) e novo widget Elementor "Questões – Cursos".
* Cria layout responsivo para o catálogo de cursos com cartões acessíveis, contadores e mensagens amigáveis quando não houver resultados.

= 0.9.0 =
* Cria o shortcode `[questoes_disciplinas]` para listar disciplinas com filtros por palavra-chave e área de formação antes de exibir as questões.
* Adiciona painel de disciplinas com contagem de questões e comentários, estilizado conforme a identidade visual para páginas como "Disciplinas".
* Atualiza scripts, estilos, traduções e a página administrativa para documentar o novo shortcode e manter a experiência consistente.

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
