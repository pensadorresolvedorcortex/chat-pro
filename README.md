# Chat-Pro

Este é o repositório inicial para o Codex do ChatGPT.

## Plugins

 - **ZX Tec - Intranet de Servicos e Colaboradores**: plugin WordPress localizado em `zxtec-intranet/` com gerenciamento de clientes, servicos, ordens e contratos. Inclui painel do colaborador com confirmacao e finalizacao de ordens, mapa de geolocalizacao, exportacao financeira em CSV, PDF e Excel, notificacoes por e-mail, historico de servicos com filtros e exportacao em CSV, contratos ativos, agenda de ordens confirmadas e mapa de tecnicos com atribuicao automatica pelo GPS. A versao 0.9 considerava tambem o custo por Km do colaborador e definia o agendamento automaticamente. A versao 1.0 adicionou relatorio financeiro individual e justificativa obrigatoria ao recusar servicos. A versao 1.1 ampliou o historico com filtros por data e tecnico. A versao 1.2 traz controle de despesas com relatorio e saldo liquido. A versao 1.3 inclui o framework Bootstrap para deixar o painel responsivo. A versao 1.4 permite exportar o financeiro individual em PDF. A versao 1.5 adiciona notificacoes internas sobre ordens e atualizacoes de status. A versao 1.6 inclui uma pagina de Notificacoes para administradores gerenciarem alertas dos colaboradores.
 A versao 1.7 adiciona limpeza completa de dados ao desinstalar o plugin.
 A versao 1.8 permite configurar o percentual de comissao na pagina de configuracoes do plugin.
 A versao 1.9 traz um widget de resumo no painel inicial do WordPress exibindo quantas ordens estao pendentes, confirmadas e concluidas.
 A versao 2.0 permite filtrar o Relatorio Financeiro por datas antes de exportar os arquivos.
 A versao 2.1 permite definir comissao individual para cada colaborador.

## Development
Run `scripts/test.sh` to lint all PHP files.

