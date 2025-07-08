# Studio Privilege SEO (v1.3.0)

Plugin de otimização dedicado ao site [Studio Privilege](https://www.studioprivilege.com.br).
Todas as meta tags e blocos JSON-LD presentes no site são replicados integralmente e atualizados a cada 12 horas por meio do WP&nbsp;Cron.

Além disso, o sitemap é notificado aos buscadores sempre que os dados são atualizados.
Este plugin auxilia nas práticas de SEO copiando automaticamente os dados do site de origem, mas **não há garantia** de alcançar a primeira posição nos resultados do Google.

Funcionalidades principais:

- Inclusão automática de todas as meta tags (incluindo Open Graph) do domínio
- Geração de sitemap em `sp-sitemap.xml` listando posts e páginas
- Dados estruturados via JSON-LD replicados do site
- Atualização automática agendada a cada 12 horas
- Página de status no painel com botão para atualizar manualmente
- Comando `wp sp-seo refresh` para atualizar via WP‑CLI
- Envio do sitemap aos buscadores após cada atualização
- Validação do código de resposta HTTP antes de salvar os dados
- Meta tag adicional de "keywords" com termos populares
- Registro de atividades em `wp-content/sp-seo.log`
- Script de desinstalação remove todas as opções salvas
- Uso de ETag e Last-Modified para evitar downloads quando o conteúdo não muda
- Rastreamento de cliques com localização e gráfico no painel

