# WC PIX Live Status – Mercado Pago Auto Update

Plugin do WordPress/WooCommerce que adiciona polling automático no checkout para pedidos pagos via PIX do Mercado Pago. A página de pagamento permanece aberta e redireciona automaticamente para a página final quando o status muda para `processing` ou `completed`.

## Instalação
1. Copie a pasta `wc-pix-live-status` para `wp-content/plugins/`.
2. Ative o plugin no painel **Plugins** do WordPress.
3. Certifique-se de que o webhook do Mercado Pago está configurado normalmente (o plugin não altera o webhook).
4. O plugin declara compatibilidade com HPOS (Custom Order Tables) e Checkout Blocks do WooCommerce para evitar avisos de incompatibilidade no painel.

## Como funciona
- O script de polling é carregado somente em páginas de pagamento (`checkout/pay`) ou `order-received` quando o método selecionado é Mercado Pago PIX e existe um `order_id` válido acompanhado da `order_key` do pedido.
- A cada 5 segundos o script chama o endpoint REST `wc-pix-check/v1/status` e, ao identificar status `processing` ou `completed`, redireciona automaticamente para a página final de agradecimento.
- O endpoint valida o ID do pedido, confirma a existência do pedido, exige a `order_key` para clientes não logados e checa a propriedade quando o usuário está logado.
- O redirecionamento final usa a URL de order-received do WooCommerce (a mesma que o JuntaPlay personaliza). A função helper `juntaplay_get_thankyou_url( $order_id )` pode ser sobrescrita pelo próprio JuntaPlay, mas sempre retorna a URL de `get_checkout_order_received_url()`.

### Integração com a página de thank-you do JuntaPlay
- O fluxo do JuntaPlay já controla a página de order-received do WooCommerce, portanto o polling sempre redireciona para `get_checkout_order_received_url()`.
- A função `juntaplay_get_thankyou_url( $order_id )` mantém compatibilidade com implementações que a declarem, mas neste plugin retorna a URL de thank-you do WooCommerce.
- O filtro `wc_pix_live_status_redirect` permite substituir a URL retornada pelo helper, facilitando integrações customizadas do fluxo de agradecimento.

## Filtros
- `wc_pix_live_status_polling_interval`: altera o intervalo (em segundos) do polling.
- `wc_pix_live_status_redirect`: altera a URL de redirecionamento final. Por padrão usa `juntaplay_get_thankyou_url( $order_id )`,
  que retorna a URL de thank-you (order-received) do WooCommerce, personalizada pelo JuntaPlay.
