=== Login Academia da Educação ===
Contributors: chatgpt-codex
Requires at least: 5.8
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.2.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Plugin WordPress que adiciona um menu de usuário estilizado com notificações e cria páginas essenciais para a Academia da Educação. Inclui shortcodes para uso no Elementor, migração automática de slugs legados e filtros para personalização da experiência do aluno.

== Descrição ==

* Shortcode principal `[lae_user_menu]` com saudação, notificações e dropdown.
* Shortcodes individuais para cada página: `[lae_perfil]`, `[lae_teoria]`, `[lae_pratica]`, `[lae_meus_conhecimentos]`, `[lae_planos]`, `[lae_suporte]`.
* Criação e atualização automática das páginas publicadas na ativação do plugin.
* Migração de slugs antigos (como `/teoria` ou `/perfil`) para os novos caminhos oficiais sem perder conteúdo existente.
* Estilos modernos com foco em usabilidade, responsividade e efeito glassmorphism, além de avatar padrão para visitantes.
* Filtros `lae_notification_count`, `lae_show_notifications`, `lae_user_menu_items`, `lae_user_menu_greeting`, `lae_user_avatar_url` e `lae_user_avatar_initial` para integrações avançadas.

== Instalação ==

1. Envie a pasta "Login Academia da Educação" para o diretório `wp-content/plugins/`.
2. Ative o plugin no painel do WordPress.
3. Utilize o shortcode `[lae_user_menu]` em qualquer página ou template via Elementor ou editor padrão.
4. Edite as páginas criadas automaticamente para personalizar o conteúdo.

== Changelog ==

= 1.2.4 =
* Impede que o WooCommerce assuma a renderização da página "Minha Conta" da academia, mantendo o layout personalizado ativo.
* Evita migrar a página "Minha Conta" nativa do WooCommerce ao atualizar instalações existentes.

= 1.2.3 =
* Atualiza o slug da página "Minha Conta" para `/minha-conta-academia`, evitando conflitos com a página padrão do WooCommerce.
* Garante a migração automática das instalações existentes a partir dos slugs antigos `/perfil` e `/minha-conta`.

= 1.2.2 =
* Realça automaticamente no dropdown o item referente à página visitada e adiciona suporte a `aria-current`.
* Fecha o menu após a seleção de um item e mantém a navegação totalmente acessível por teclado e mouse.
* Amplia a seção de segurança da página "Minha Conta" com o registro do último acesso formatado.

= 1.2.1 =
* Migra automaticamente o slug legado `teoria` para `academia-aulas-teoricas`, garantindo que o menu Teoria leve ao endereço correto.
* Remove templates obsoletos (Treinador e Configurações) para evitar o reaparecimento de abas desativadas.
* Atualiza a documentação com a nova lista de shortcodes e destaque para o avatar padrão de visitantes.

= 1.2.0 =
* Adiciona a página "Minha Conta" (`/perfil`) com seções de dados pessoais, endereço e segurança.
* Fornece imagem padrão para usuários deslogados no menu e na página de perfil.
* Ajusta o menu do usuário para remover Treinador e Configurações e direciona Suporte para `/suporte`.

= 1.1.0 =
* Aperfeiçoa a experiência glass com camadas extras de brilho e avatares reais dos usuários.
* Adiciona novos filtros para personalizar saudação, itens do menu e avatar.
* Melhora acessibilidade com suporte ampliado ao teclado e metadados ARIA.

= 1.0.0 =
* Lançamento inicial.
