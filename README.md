# Widgets Gráficos do Painel (Graphical Dashboard Widgets)

Este repositório reúne o código-fonte do plugin **Graphical Dashboard Widgets** para WordPress e o prompt de redesign de UX/UI em estilo premium e futurista. Todo o material está em **português do Brasil** e não há mais outro idioma incluso. Use-o para instalar o plugin manualmente e aplicar o visual neon descrito no prompt.

## Conteúdo
- `graphical_dashboard_widgets/` – arquivos do plugin, com widgets estatísticos, análises de visitantes e páginas de configuração.
- `luxury_ui_prompt.md` – prompt ultra detalhado em português descrevendo o redesign de luxo (cores neon, glassmorphism, animações e gráficos dinâmicos).

## Instalação manual do plugin
1. Compacte a pasta `graphical_dashboard_widgets/` em um arquivo ZIP, se necessário.
2. No painel WordPress, acesse **Plugins → Adicionar novo → Enviar plugin**.
3. Envie o ZIP e ative **Graphical Dashboard Widgets**.
4. Vá até as configurações do plugin para escolher as cores dos widgets e quais módulos exibir no painel.

## Tradução e localização
- O plugin está preparado para **pt-BR** e carrega o domínio de texto `gdwlang` automaticamente.
- `graphical_dashboard_widgets/languages/` inclui apenas o arquivo de texto `gdwlang-pt_BR.po` (o `.mo` é gerado automaticamente em tempo de execução no diretório de idiomas do WordPress).
- O carregamento forçado do idioma pt-BR garante que a interface permaneça em português mesmo se o site estiver configurado em outro idioma.

## Aplicando o redesign de luxo
O prompt em `luxury_ui_prompt.md` orienta um visual high-end com:
- Paleta escura com destaques neon e efeitos de brilho suave.
- Gráficos animados (linhas, barras, radares/gauges) e cartões interativos.
- Glassmorphism, microinterações polidas e layout modular de dashboard.
- Fundo com auroras em movimento, granulado suave e cartões translúcidos para um visual totalmente "glass".

Um tema neon/glassmorphism acompanha o plugin e é carregado automaticamente no painel do WordPress (dashboard e página de configurações),
estilizando os widgets e formulários com gradientes difusos, cartões translúcidos, brilho elegante e tipografia premium. Os gráficos usam paleta neon vibrante,
tooltips com blur e animação contínua para manter os widgets em movimento, incluindo laços automáticos de destaque sem vazamentos de memória.

## Estrutura dos fontes
- PHP principal: `graphical_dashboard_widgets/gdw-core.php`.
- Funções e hooks: `graphical_dashboard_widgets/lib/`.
- Configurações: `graphical_dashboard_widgets/settings/`.
- Estatísticas do site: `graphical_dashboard_widgets/site-stats/`.
- Estatísticas de visitantes (frontend e admin): `graphical_dashboard_widgets/visitor-stats/`.
- Scripts JS: `graphical_dashboard_widgets/visitor-stats/js/`.

## Notas
- Não há testes automatizados inclusos; execute lint de PHP ou verificações específicas do WordPress conforme necessário.
