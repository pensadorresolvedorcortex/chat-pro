# JuntaPlay

## 1. Mapa Mental

- **Frontend**
- Home / Lista de Campanhas (cotas)
- Página da Campanha (detalhes + seletor de cotas)
- Rotador de Grupos (cards 495x370 em destaque na home)
- Carrinho & Checkout (WooCommerce)
- Painel do Usuário (pós-login)
- Perfil do Usuário (edição)
- Créditos & Carteira (saldo, bônus, recargas)
- Dados Fiscais (PF/PJ e faturamento)
- Segurança da Conta (senha, 2FA, sessões ativas)
- Meus Grupos (campanhas criadas ou participações)
- Dúvidas frequentes e bloco de compartilhamento nos grupos
- Minhas Cotas (painel do cliente)
- Extrato de Pedido (detalhes de pagamento e cotas)
- Confirmação & E-mails transacionais
- **Backend (WP Admin + páginas utilitárias)**
  - Dashboard JuntaPlay
  - Campanhas (CRUD)
  - Importar CSV (campanhas/cotas)
  - Gerar Páginas (instalador de shortcodes)
  - Grupos (aprovação e auditoria)
  - Configurações (Gerais, Pagamentos, E-mail/SMTP, Reservas)
- **Núcleo**
  - Tabelas: `jp_pools`, `jp_quotas`
  - Estados: disponível, reservado, pago, cancelado, expirado
  - Reserva temporária, expiração via WP-Cron
  - API REST/AJAX
  - Cache / transients
- **Integrações**
  - WooCommerce (validação e baixa de cotas)
  - Elementor (widgets)
  - SMTP (phpmailer_init)
- **Segurança & Compliance**
  - Nonces, capabilities, prepared statements
  - Logs e trilhas
  - LGPD/Consentimento
- **Desempenho**
  - Índices SQL, paginação, lazy loading
  - Cache de disponibilidade
  - CSS utilitário

## 2. Organograma

- **Produto/Negócio**
  - Product Owner
  - Atendimento/Operações
- **Tecnologia**
  - Tech Lead WP/PHP
  - Dev Backend (plugin + hooks Woo)
  - Dev Frontend (Elementor, CSS, shortcodes)
  - QA (funcional e carga)
- **Financeiro**
  - Conciliação de pagamentos
  - Reembolsos/cancelamentos
- **Marketing**
  - SEO/Analytics
  - E-mail marketing

## 3. Arquitetura Técnica

### 3.1 Entidades

- `jp_pools`: metadados das campanhas.
- `jp_quotas`: números de cotas com status e vínculo a usuários/pedidos.
- `jp_groups`: grupos criados por usuários para organizar compras coletivas.
- `jp_group_members`: associação usuário ↔ grupo com papéis e status.
- `jp_group_complaints`: protocolos de reclamação com motivo, anexos, status e vínculo opcional ao pedido WooCommerce.
- *Dados de demonstração*: o botão "Criar dados de demonstração" no painel gera usuários fictícios e grupos populares (YouTube Premium, Mubi, NBA League Pass, Spotify, Brainly, Canva, ExpressVPN, entre outros) para acelerar testes de busca, aprovação e notificações.

### 3.2 Fluxos

1. Reserva atômica via `UPDATE ... WHERE status='available'` + `reserved_until`.
2. Expiração automática via cron liberando cotas reservadas expiradas.
3. Checkout: revalidação, marcação como pagas e liberação em cancelamentos.
4. Grupos: solicitação entra como `pending`, dispara alerta para o super admin, aprovação/rejeição via painel altera para `approved|rejected|archived` e dispara e-mail ao criador (com observação quando houver motivo). Cada grupo requer uma capa 495x370 enviada pelo criador (obrigatória no formulário de criação, com seletor de mídia e aviso quando o placeholder estiver ativo), usada na vitrine interna e no rotador público de destaques. O criador e o administrador recebem um resumo completo com categoria, status de acesso instantâneo, valores promocionais e regras cadastradas. No painel do usuário cada grupo apresenta resumo financeiro (total da inscrição e aviso sobre caução bloqueada), vitrine de participantes, bloco de compartilhamento com cópia rápida e uma FAQ contextual que reforça os meios de pagamento habilitados no WooCommerce.
   - Assim que o grupo é enviado, o dono recebe **dois e-mails automáticos**: um resumo detalhado com todos os campos preenchidos e um **código de validação de e-mail** (6 dígitos) que fica registrado na tabela `jp_groups` (`email_validation_hash`, `email_validation_sent_at`).
   - Ao aprovar, recusar ou arquivar, o sistema envia mensagens com cabeçalho em texto plano UTF-8 e link direto para o painel/perfil para que o criador libere o grupo.
5. E-mails transacionais disparados após confirmação ou expiração.
6. Reclamações: o painel "Meus Grupos" oferece formulário com upload de evidências, grava registros em `jp_group_complaints`, atualiza o cartão do grupo com status/histórico e dispara alertas para o admin e para o cliente.

### 3.3 Páginas & Shortcodes

| Página / Área | Shortcode |
| ------------- | --------- |
| Campanhas | `[juntaplay_pools]` |
| Detalhe da Campanha | `[juntaplay_pool id="{pool_id}"]` |
| Seletor de Cotas | `[juntaplay_quota_selector id="{pool_id}" per_page="100"]` |
| Dashboard do Cliente | `[juntaplay_dashboard]` |
| Minhas Cotas | `[juntaplay_my_quotas]` |
| Extrato de Pedido | `[juntaplay_statement order_id=""]` |
| Perfil do Usuário | `[juntaplay_profile]` |
| Diretório de Grupos | `[juntaplay_groups]` |
| Busca hero de Grupos | `[juntaplay_group_search]` |
| Rotador de Grupos | `[juntaplay_group_rotator limit="12" category=""]` |
| Entrar / Criar Conta | `[juntaplay_login_form]` |
| Desafio 2FA | `[juntaplay_two_factor]` |
| Termos & Regras | `[juntaplay_terms]` |
| Painel Operacional | `[juntaplay_admin]` |

> O perfil reúne os blocos de **contato**, **créditos e carteira** (saldo disponível, reservas, bônus, recarga automática e chave Pix), **dados fiscais** (CPF/CNPJ, razão social, inscrição estadual e endereço de faturamento), **meus grupos** (listagem de bolões criados ou ingressados, pedidos pendentes e criação de novos grupos públicos) e a aba de **segurança** (alteração de senha, 2FA, alertas e sessões) com validação e máscaras de exibição inspiradas no Freeio.
> O módulo de **Créditos e Carteira** ganhou um modal de recarga compatível com o WooCommerce: o shortcode cria um produto virtual `juntaplay_credit_topup`, envia o usuário direto para o checkout (Pix/cartão/boleto) e, ao confirmar o pagamento, registra o crédito na tabela `jp_credit_transactions`, atualiza o saldo/meta-dados e dispara notificações/e-mails; cancelamentos ou estornos fazem o ajuste inverso automaticamente.
> O criador de grupos coleta o nome do serviço, URL oficial, regras principais, preço cheio e promocional, divisão sugerida por membro, vagas totais/reservadas, canal de suporte, prazo de entrega e formato de acesso — além de selecionar uma **categoria** do catálogo sugerido e indicar se o grupo terá **acesso instantâneo** após a aprovação. Uma prévia dinâmica de compartilhamento monta o texto de convite com todos os campos preenchidos e permite copiar o resumo para divulgar em redes sociais ou mensageiros, enquanto cartões de inspiração preenchem rapidamente o formulário com serviços populares. O cartão exibe ainda um bloco “Precisa de ajuda?” com contador de protocolos, FAQ específica e formulário de reclamação (com upload de prints/PDF) que alimenta `jp_group_complaints` e envia notificações imediatas para o super admin e para o participante.

As categorias padrão cobrem **Bolões**, **Vídeo**, **Música**, **Cursos**, **Leitura**, **Escritório**, **Jogos/Esportes**, **Ferramentas de IA**, **Segurança/VPN**, além de lifestyle, marketplace e a opção genérica de outros serviços. Os cards de inspiração incluem serviços como YouTube Premium, Mubi, NBA League Pass, PlayPlus, Spotify, Tidal, Brainly Premium, Ubook, Super Interessante, Veja Saúde, Perplexity Pro, Canva, Google One, ExpressVPN e o Bolão Mega da Virada.

### 3.4 Widgets Elementor

- Lista de Campanhas
- Hero da Campanha
- Seletor de Cotas
- Contador / Progresso (futuro)
- Mural de Ganhadores (futuro)

## 4. Roadmap

1. Base do plugin (bootstrap, tabelas, shortcodes, instalador de páginas).
2. Integração WooCommerce (produto custom, hooks de reserva/pagamento).
3. Widgets Elementor.
4. Importador CSV e gerador de páginas.
5. SMTP & e-mails.
6. CSS de identidade visual.
7. QA e Go-live (testes de carga, sandbox, compliance LGPD).
