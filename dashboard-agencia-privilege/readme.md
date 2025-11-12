# Dashboard Agência Privilege

Plugin WordPress que aplica o visual do template **Ubold** ao painel administrativo e substitui o dashboard nativo por um painel moderno com KPIs, gráficos ApexCharts e widgets personalizados.

## Instalação

1. Faça o upload da pasta `dashboard-agencia-privilege` para o diretório `wp-content/plugins/` ou compacte-a em `.zip` e instale via painel do WordPress.
2. No WordPress, acesse **Plugins → Adicionar novo → Enviar plugin** e ative o **Dashboard Agência Privilege**.
3. (Opcional) Ajuste as configurações em **Configurações → Agência Privilege** para personalizar o destino do botão principal e habilitar a skin global.

### Ativos do Ubold

Para reproduzir fielmente o visual do Ubold:

1. Obtenha o pacote original do Ubold (licença necessária).
2. Copie a pasta `Docs/assets` do Ubold para `wp-content/plugins/dashboard-agencia-privilege/assets/ubold/assets`.
3. Certifique-se de que os arquivos `css/app.min.css`, `css/icons.min.css`, `js/vendor.min.js`, `js/app.min.js` e `vendor/apexcharts/apexcharts.min.js` estejam presentes. O plugin detecta automaticamente esses arquivos e utiliza fallbacks caso estejam ausentes.

> ⚠️ A pasta `assets/ubold/assets` incluída no plugin contém apenas um arquivo `.gitkeep` como placeholder. Nenhum ativo proprietário do Ubold é distribuído junto com o plugin.

## Funcionalidades

- Skin completa do Ubold aplicada ao `/wp-admin/index.php` com wrapper namespaced `.dap-admin`.
- Dashboard Modern inspirado no HTML do Ubold com hero gradiente, KPIs, gráficos ApexCharts e listas atualizadas.
- Carregamento condicional de assets apenas no Dashboard (e CSS opcional em outras telas).
- API REST (`/wp-json/dap/v1/stats`) preparada para integração futura.
- Página de configurações simples para personalizar o comportamento do painel.
- Área dedicada "Widgets Elementor" para construir os widgets introdutórios com Elementor, antes do layout analítico principal.

### Widgets iniciais com Elementor

1. Após ativar o plugin, acesse **Painel → Widgets Elementor** para abrir a tela administrativa da área.
2. Clique em **Editar com Elementor** para lançar o construtor visual. Tudo o que for salvo ali será renderizado no topo do Dashboard, replicando os cards Modern do HTML original.
3. Caso o Elementor não esteja ativo, utilize o editor padrão para inserir shortcodes, blocos ou HTML.

## Testes recomendados

- Ative o plugin e visite `/wp-admin/index.php` para verificar o layout Ubold.
- Copie os ativos do Ubold e confirme se estilos e scripts são carregados sem erros no console.
- Navegue em outras telas do admin (WooCommerce, Elementor, etc.) para validar que o layout permanece intacto.

> Dica: substitua `assets/images/hero-placeholder.svg` por uma arte do Ubold (ex.: `assets/ubold/assets/images/hero-1.png`) para reproduzir o visual original do hero.

## Licença

Este plugin não distribui os arquivos proprietários do tema Ubold. Certifique-se de possuir a licença adequada antes de copiar os assets do tema. O restante do código deste plugin pode ser utilizado conforme as políticas internas da sua agência.
