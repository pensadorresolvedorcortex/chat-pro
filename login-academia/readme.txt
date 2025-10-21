=== Login Academia da Educação ===
Contributors: chatgpt-codex
Requires at least: 5.8
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Plugin WordPress que adiciona um menu de usuário estilizado com notificações e cria páginas essenciais para a Academia da Educação. Inclui shortcodes para uso no Elementor e filtro para personalização da contagem de notificações.

== Descrição ==

* Shortcode principal `[lae_user_menu]` com saudação, notificações e dropdown.
* Shortcodes individuais para cada página: `[lae_teoria]`, `[lae_pratica]`, `[lae_meus_conhecimentos]`, `[lae_treinador]`, `[lae_planos]`, `[lae_configuracoes]`, `[lae_suporte]`.
* Novo tour guiado com o shortcode `[lae_onboarding_slider]`, incluindo popup para entrar ou criar conta.
* Criação automática das páginas publicadas na ativação do plugin.
* Estilos modernos com foco em usabilidade, responsividade e efeito glassmorphism.
* Filtros `lae_notification_count`, `lae_show_notifications`, `lae_user_menu_items`, `lae_user_menu_greeting`, `lae_user_avatar_url` e `lae_user_avatar_initial` para integrações avançadas.

== Instalação ==

1. Envie a pasta "Login Academia da Educação" para o diretório `wp-content/plugins/`.
2. Ative o plugin no painel do WordPress.
3. Utilize o shortcode `[lae_user_menu]` em qualquer página ou template via Elementor ou editor padrão.
4. Edite as páginas criadas automaticamente para personalizar o conteúdo.
5. Insira `[lae_onboarding_slider]` para exibir o passo a passo com popup de autenticação.

== Changelog ==

= 1.2.0 =
* Adiciona slider onboarding de três etapas com botão que abre popup de login ou cadastro.
* Melhora acessibilidade com navegação por teclado no tour e foco controlado dentro do popup.

= 1.1.0 =
* Aperfeiçoa a experiência glass com camadas extras de brilho e avatares reais dos usuários.
* Adiciona novos filtros para personalizar saudação, itens do menu e avatar.
* Melhora acessibilidade com suporte ampliado ao teclado e metadados ARIA.

= 1.0.0 =
* Lançamento inicial.
