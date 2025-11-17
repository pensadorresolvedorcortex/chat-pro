# WC Ingresso Shortcode

## Onde encontrar o plugin no WordPress

1. Acesse o painel administrativo do WordPress e navegue até **Plugins ▸ Plugins instalados**.
2. Procure pelo plugin **WC Ingresso Shortcode**. Ele aparecerá utilizando as informações definidas no cabeçalho do arquivo `wc-ingresso-shortcode.php`, como nome, descrição e versão.
3. Clique em **Ativar** (se ainda não estiver ativo) ou em **Configurações** conforme necessário para usar o shortcode `[wc_mentoria]` em suas páginas ou posts.

As metainformações exibidas na tela de plugins — título, descrição, autor, requisitos — são as mesmas declaradas no cabeçalho padrão do plugin localizado no arquivo principal `wc-ingresso-shortcode.php`.

## Dashboard do shortcode

Após ativar o plugin, um item de menu chamado **WC Ingresso** será exibido no painel. Nele você encontra um pequeno dashboard onde é possível:

- Definir o link permanente do produto WooCommerce que será utilizado como padrão pelo shortcode.
- Salvar as alterações clicando em **Salvar link do produto**.
- Consultar instruções rápidas de uso do shortcode `[wc_mentoria]`.

O link definido nesse dashboard será aplicado automaticamente sempre que o shortcode for usado sem informar explicitamente o atributo `product_link`. Ainda é possível sobrescrever o valor padrão passando outro link diretamente no shortcode quando necessário.

## Parâmetros adicionais do shortcode

Além do atributo `product_link`, você pode controlar:

- `button_label`: ajusta o texto do botão de chamada para ação.
- `installments`: define o número máximo de parcelas exibidas ao lado do preço.
- `sales_until`: informa manualmente o texto mostrado abaixo do preço (ex.: `sales_until="Vendas até 21/10/2025"`). Caso não seja definido e o produto possua uma data final de promoção, o plugin exibirá automaticamente "Vendas até" seguido da data configurada no WooCommerce.
