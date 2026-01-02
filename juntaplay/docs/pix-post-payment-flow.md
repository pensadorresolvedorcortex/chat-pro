# Fluxo pós-pagamento PIX – auditoria e status

## Gatilhos confirmados
- Webhook REST `juntaplay/v1/mercadopago/webhook` valida notificações aprovadas e resolve o pedido antes de processar o split.
- Hooks `woocommerce_order_status_processing` e `woocommerce_order_status_completed` chamam o mesmo fluxo quando a mudança de status vem do callback do Mercado Pago, evitando execução para pedidos sem aprovação.

## Processamento após aprovação
- `process_order_payment` evita retrabalho em pedidos já processados, calcula o split com percentual configurado, cria logs em `jp_payment_splits`, credita superadmin/admin e registra o caução como **retido** para o participante.
- Em caso de falha em qualquer etapa, as operações creditadas são revertidas, os registros são marcados como `failed` e o erro retorna; quando bem-sucedido, o pedido recebe as metas `_payment_via_mp_callback` e `_juntaplay_split_processed`, além de nota detalhada e acionamento do hook `juntaplay/split/completed`.

## Ativação de grupo e confirmação de cotas
- Na transição para `processing`/`completed`, o handler WooCommerce confirma o fluxo pago apenas se o callback do Mercado Pago estiver marcado; então adiciona/reativa o usuário no grupo, registra eventos de associação, quita cotas pendentes e evita duplicidade por item com a flag `_juntaplay_group_item_processed`.

## Ciclo de caução
- `CaucaoManager::retain` grava o caução como **retido**, amarrando usuário, grupo e pedido, e calcula `cycle_end` para 31 dias após a data de pagamento; liberações futuras são tratadas separadamente pelo validador de ciclo.

## Thank-you condicionado
- A página personalizada de sucesso só é renderizada quando o pedido está pago (`processing`/`completed`), contém itens de grupo e possui a marcação `_payment_via_mp_callback`, evitando telas prematuras durante o fluxo PIX.

## Pontos restantes
- O fluxo está alinhado ao status `processing` disparado pelo PIX; não há pendências adicionais mapeadas na auditoria para o pós-pagamento, além de manter a validação do callback do Mercado Pago como requisito para todos os gatilhos.
