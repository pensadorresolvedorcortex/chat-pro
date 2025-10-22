=== Introdução Academia da Educação ===
Contributors: chatgpt-codex
Requires at least: 5.8
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.5.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Plugin WordPress que adiciona um menu de usuário estilizado com notificações e cria páginas essenciais para a Academia da Educação. Inclui shortcodes para uso no Elementor e filtro para personalização da contagem de notificações.

== Descrição ==

* Shortcode principal `[introducao_user_menu]` com saudação, notificações e dropdown.
* Shortcodes individuais para cada página: `[introducao_teoria]`, `[introducao_pratica]`, `[introducao_meus_conhecimentos]`, `[introducao_treinador]`, `[introducao_planos]`, `[introducao_configuracoes]`, `[introducao_suporte]`.
* Novo tour guiado com o shortcode `[introducao_onboarding_slider]`, incluindo popup para entrar ou criar conta.
* Criação automática das páginas publicadas na ativação do plugin.
* Estilos modernos com foco em usabilidade, responsividade e efeito glassmorphism.
* Filtros `introducao_notification_count`, `introducao_show_notifications`, `introducao_user_menu_items`, `introducao_user_menu_greeting`, `introducao_user_avatar_url` e `introducao_user_avatar_initial` para integrações avançadas.

== Instalação ==

1. Envie a pasta "Introdução Academia da Educação" para o diretório `wp-content/plugins/`.
2. Ative o plugin no painel do WordPress.
3. Utilize o shortcode `[introducao_user_menu]` em qualquer página ou template via Elementor ou editor padrão.
4. Edite as páginas criadas automaticamente para personalizar o conteúdo.
5. Insira `[introducao_onboarding_slider]` para exibir o passo a passo com popup de autenticação.

== Changelog ==

= 1.5.0 =
* Sincroniza metadados de login (último acesso, origem e IP) e expiração de códigos entre desafios aprovados para que menus de outros plugins reflitam imediatamente o status do usuário.
* Expõe nomes amigáveis e contexto compartilhado dos desafios 2FA, eliminando saudação "Visitante" enquanto o código está pendente e mantendo dados alinhados em fluxos AJAX.

= 1.4.0 =
* Adiciona reenvio seguro de códigos de autenticação com contagem regressiva, feedback imediato e mensagens dinâmicas no shortcode `[introducao_perfil]`.
* Inclui endpoint AJAX protegido por nonce para regenerar desafios 2FA sem recarregar a página e manter o foco na confirmação.
* Amplia o layout desktop com cartões centralizados mais largos e meta-informações atualizadas sobre expiração do código.

= 1.3.0 =
* Inclui verificação em duas etapas para login e criação de conta diretamente no shortcode `[introducao_perfil]` com envio de código via e-mail.
* Adiciona layout aprimorado para a introdução do painel e feedback visual dedicado ao fluxo de autenticação segura.
* Disponibiliza modelo de e-mail responsivo com a identidade visual da Academia destacando o código de validação.

= 1.2.0 =
* Adiciona slider onboarding de três etapas com botão que abre popup de login ou cadastro.
* Melhora acessibilidade com navegação por teclado no tour e foco controlado dentro do popup.

= 1.1.0 =
* Aperfeiçoa a experiência glass com camadas extras de brilho e avatares reais dos usuários.
* Adiciona novos filtros para personalizar saudação, itens do menu e avatar.
* Melhora acessibilidade com suporte ampliado ao teclado e metadados ARIA.

= 1.0.0 =
* Lançamento inicial.
