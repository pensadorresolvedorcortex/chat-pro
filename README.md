# Chat-Pro

Este repositório armazena plugins do WordPress utilizados pelo Studio Privilege.

## Plugins

 - **Studio Privilege SEO** (versão 1.3.0): plugin localizado em `studio-privilege-seo/`, dedicado ao domínio https://www.studioprivilege.com.br. Todas as meta tags do site são replicadas integralmente e atualizadas automaticamente a cada 12 horas via WP&nbsp;Cron, dispensando preenchimento manual. O plugin inclui uma meta tag de palavras-chave com termos populares, oferece uma página de status no painel para atualização manual, gera um sitemap com todas as páginas e posts publicados, notifica os buscadores sempre que os dados são atualizados e grava um log em `wp-content/sp-seo.log`. Suporta requisições condicionais via ETag e Last-Modified e um comando WP‑CLI para forçar a atualização. Auxilia na otimização de SEO, mas não garante posição específica nos resultados do Google.
 Agora também registra cliques com localização e exibe um gráfico no painel.
## Development

Execute `scripts/test.sh` para verificar os arquivos PHP. O script exige o PHP
CLI; se não estiver instalado, exibirá "php: command not found" e retornará
erro.

