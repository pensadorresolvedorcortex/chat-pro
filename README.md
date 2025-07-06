# Chat-Pro Bot

Este repositório contém um exemplo de bot para atendimento via **API do WhatsApp Business**. Consulte `bot/README.md` para detalhes de configuração e execução.

## Development

Há também um plugin WordPress localizado em `wp-waba-bot` que permite usar o bot diretamente no site através do shortcode `[waba_bot]`. O plugin inclui estilo próprio e melhor UX.
O mesmo plugin expõe um endpoint de webhook em `wp-json/waba-bot/v1/webhook` para receber mensagens que chegam diretamente ao número e iniciar o atendimento.
Os arquivos do bot ficam no diretório `bot/`; não há binários nem arquivos ZIP necessários para executá-lo.
Instale o PHP 8 ou versão mais recente e execute `scripts/test.sh` para verificar todos os arquivos PHP.
