# WC PIX Live Status – Mercado Pago Auto Update

Plugin do WordPress/WooCommerce que adiciona polling automático no checkout para pedidos pagos via PIX do Mercado Pago. A página de pagamento permanece aberta e redireciona automaticamente para a página final quando o status muda para `processing` ou `completed`.

## Instalação
1. Copie a pasta `wc-pix-live-status` para `wp-content/plugins/`.
2. Ative o plugin no painel **Plugins** do WordPress.
3. Certifique-se de que o webhook do Mercado Pago está configurado normalmente (o plugin não altera o webhook).

## Como funciona
- O script de polling é carregado somente em páginas de pagamento (`checkout/pay`) ou `order-received` quando o método selecionado é Mercado Pago PIX e existe um `order_id` válido acompanhado da `order_key` do pedido.
- A cada 5 segundos o script chama o endpoint REST `wc-pix-check/v1/status` e, ao identificar status `processing` ou `completed`, redireciona automaticamente para a página final de agradecimento.
- O endpoint valida o ID do pedido, confirma a existência do pedido, exige a `order_key` para clientes não logados e checa a propriedade quando o usuário está logado.

## Filtros
- `wc_pix_live_status_polling_interval`: altera o intervalo (em segundos) do polling.
- `wc_pix_live_status_redirect_url`: altera a URL de redirecionamento final.
