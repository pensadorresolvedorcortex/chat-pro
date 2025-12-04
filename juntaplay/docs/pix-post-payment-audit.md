# Auditoria do fluxo pós-pagamento PIX (Mercado Pago)

Esta auditoria cobre o comportamento atual do fluxo de pós-pagamento PIX do plugin JuntaPlay, avaliando 11 áreas obrigatórias e apontando status, pontos de atenção e referências de código.

## Status por área

1. **Ativação pós-pagamento** — **SIM**
   - A ativação de itens de grupo é executada apenas após o pedido chegar a `processing`/`completed` com flag de callback Mercado Pago, adicionando o membro ao grupo, registrando evento de entrada e evitando reprocessamento por item via metadado `_juntaplay_group_item_processed`. Ver: `Hooks::on_order_status_changed` e auxiliares de ativação.  
   - Reservas de cotas são marcadas como pagas e eventos de membership são gravados uma única vez por item.  
   Referências: `includes/Woo/Hooks.php` (ativação e marcação de itens processados).

2. **Gatilho webhook Mercado Pago** — **SIM**
   - Endpoint REST `juntaplay/v1/mercadopago/webhook` aceita POST/GET, valida tópico de pagamento aprovado e resolve o pedido antes de iniciar split/caução.  
   Referência: `includes/Front/MercadoPagoWebhook.php` (`register_routes`, `handle_notification`).

3. **Tratamento de order_status processing/completed** — **SIM**
   - Hooks em `woocommerce_order_status_processing` e `completed` chamam `handle_paid_order`, que só roda para pedidos Mercado Pago com flag de callback e evita duplicidade.  
   Referência: `includes/Front/MercadoPagoWebhook.php` (`init`, `handle_paid_order`).

4. **Controle `_payment_via_mp_callback`** — **SIM**
   - Meta definida após processamento bem-sucedido do webhook e checada antes de executar overrides de thank-you ou replays por status.  
   Referências: `includes/Front/MercadoPagoWebhook.php` (meta set em `process_order_payment`), `includes/Front/CheckoutThankYou.php` (checagem antes de renderizar templates).

5. **Controle `_juntaplay_order_handled`** — **SIM**
   - Marcado após reprocesso por status processing/completed para impedir múltiplas execuções do fluxo pago.  
   Referência: `includes/Front/MercadoPagoWebhook.php` (`handle_paid_order`).

6. **Execução do split** — **SIM**
   - Para cada item de grupo, calcula percentual superadmin/admin, registra em `jp_payment_splits`, aplica créditos e marca registro como `completed` somente após sucesso; falhas geram `failed` e rollback.  
   Referência: `includes/Front/MercadoPagoWebhook.php` (`process_order_payment`).

7. **Retenção do caução** — **SIM**
   - Caução é retido imediatamente (sem crédito) via `CaucaoManager::retain`, associado a usuário, grupo e pedido, com status inicial `retido`.  
   Referências: `includes/Front/MercadoPagoWebhook.php` (chamada para retenção) e `includes/Support/CaucaoManager.php` (`retain`).

8. **Criação do ciclo de 31 dias** — **SIM**
   - Cada caução retido cria ciclo com `cycle_start` na data do pagamento, `cycle_end` +31 dias e status `retido`, registrado em `jp_caucao_cycles`.  
   Referência: `includes/Data/CaucaoCycles.php` (armazenamento) e `includes/Support/CaucaoManager.php` (`retain`).

9. **Thank-you final** — **SIM**
   - Templates customizados só são renderizados quando o pedido está `processing`/`completed`, contém itens de grupo e possui `_payment_via_mp_callback`. Pendências ou on-hold mantêm thank-you padrão do WooCommerce/Mercado Pago.  
   Referência: `includes/Front/CheckoutThankYou.php` (`should_render_custom_template`).

10. **E-mails pós-pagamento** — **SIM**
    - E-mail personalizado de pedido pago é disparado após renderização thank-you validada; e-mails de split para superadmin/admin são enviados via hook `juntaplay/split/completed` após sucesso do split.  
    Referências: `includes/Front/CheckoutThankYou.php` (controle de e-mail) e `includes/Notifications/Splits.php`.

11. **Atomicidade, rollback e logs** — **SIM**
    - `process_order_payment` usa bloco try/catch por item, cria split com status `pending`, aplica créditos, registra caução; em falha reverte créditos, marca splits `failed`, remove caução e aborta. Pedido recebe nota e meta `_juntaplay_split_processed` apenas após concluir todos os itens.  
    Referência: `includes/Front/MercadoPagoWebhook.php` (seção de processamento/rollback).

## Observações adicionais

- **Compatibilidade com múltiplos itens:** o processamento percorre apenas itens que tenham meta `_juntaplay_group_id`, mantendo outros itens do pedido sem acionar split/caução.  
- **Idempotência:** além de `_juntaplay_split_processed`, existe verificação em `PaymentSplits::has_completed_for_order` e marcação `_juntaplay_order_handled` para replays via status sem duplicar resultados.  
- **Retenção e validação de caução:** `CaucaoManager::process_due_cycles` roda via cron, analisando disputas, cancelamentos e status do membro antes de liberar ou reter definitivamente.

