# ZX Tec

Versao 2.6.2

Este plugin oferece um sistema de gerenciamento interno com dois paineis: administrativo e dashboard do colaborador.

Sao registrados os tipos de post **Clientes**, **Servicos**, **Ordens** e **Contratos** com campos personalizados.
O dashboard de colaboradores lista as ordens designadas ao usuario logado, permitindo confirmar, recusar ou finalizar cada servico e abrindo a rota no Google Maps.
O sistema possui notificacoes internas para informar tecnicos e administradores sobre novas ordens e mudancas de status.

Ha tambem o tipo de post **Despesas** para registrar gastos de cada colaborador.

Ative o plugin no WordPress e acesse as paginas em **ZX Tec** dentro do menu administrativo.

O cadastro de ordens possui campos de geolocalizacao e um mapa interativo com Leaflet para selecionar a coordenada. A pagina **Relatorio Financeiro** exibe o total de servicos concluidos por colaborador e calcula a comissao configurada pelo administrador. Ha tambem uma pagina de **Historico de Servicos** mostrando todas as ordens finalizadas. O dashboard de colaboradores pode ser exibido em qualquer pagina usando o shortcode `[zxtec_colaborador_dashboard]`.
O relatorio financeiro agora permite **exportar os dados em CSV**. Sempre que uma ordem for atribuida ou tiver o status modificado, o tecnico recebe uma notificacao por e-mail.
Ha tambem um botao para **exportar o relatorio em PDF**, uma pagina de **Contratos Ativos** listando contratos em andamento e uma pagina de **Agenda** exibindo ordens confirmadas pelo tecnico.
A versao 0.7 inclui campos de localizacao nos perfis dos colaboradores, um **Mapa de Tecnicos** para visualizar todos no painel administrativo e atribuicao automatica de ordens ao tecnico mais proximo.
Na versao 0.8 adicionamos campos de **especialidade** para servicos e colaboradores, permitindo que a atribuicao automatica considere a area de atuacao alem da proximidade geográfica.
Agora na versao 0.9 adicionamos um campo de **custo por Km** para cada colaborador, melhorando a escolha automatica do tecnico de acordo com o menor custo estimado. O agendamento tambem pode ser definido automaticamente para o proximo dia livre do tecnico e o relatorio financeiro oferece opcao de **exportacao em Excel**.

Na versao 1.0 incluimos um **relatorio financeiro individual** no dashboard do colaborador, permitindo exportar seus ganhos em CSV. A recusa de ordens agora exige uma justificativa que fica registrada na ordem.
Na versao 1.1 adicionamos filtros de data e tecnico ao **Historico de Servicos**, alem de um botao para **exportar o historico em CSV**.
Na versao 1.2 foi implementado um controle de **despesas** por colaborador com relatorio e exportacao em CSV. O saldo liquido agora desconta as despesas registradas.
Na versao 1.3 o plugin passou a carregar o framework **Bootstrap** para deixar o painel administrativo e o dashboard responsivos.
Na versao 1.4 foi adicionada opcao de **exportar o financeiro individual em PDF**, acessivel no dashboard do colaborador.
Na versao 1.5 criamos um sistema de **notificacoes internas** para avisar tecnicos e administradores sobre novas ordens e mudancas de status.
Na versao 1.6 adicionamos uma pagina de **Notificacoes** no painel administrativo para visualizar e limpar alertas de todos os colaboradores. Na versao 1.7 passa a existir um script de desinstalacao que remove todos os dados.
Na versao 1.8 e possivel definir o percentual de comissao na pagina de configuracoes do plugin.
Na versao 1.9 adicionamos um widget no painel inicial do WordPress exibindo o total de ordens pendentes, confirmadas e concluidas.
Na versao 2.0 o Relatorio Financeiro passou a aceitar filtros de data e manteve as opcoes de exportacao.
Na versao 2.1 e possivel definir um percentual de comissao individual para cada colaborador.
Na versao 2.2 incluimos um **painel analitico** com graficos interativos de receita e despesas.
Na versao 2.3 aplicamos o tema Flatly do Bootswatch para modernizar a interface e adicionamos campos de endereco e CEP nas ordens de servico.
Na versao 2.4 incorporamos estilos proprios para todas as paginas do plugin, oferecendo um visual mais robusto e organizado.
Na versao 2.4.1 priorizamos o carregamento desses estilos para que se sobreponham ao visual padrao do WordPress.
Na versao 2.4.2 aumentamos a prioridade desse carregamento garantindo que o visual do plugin prevaleça sobre o CSS do WordPress.
Na versao 2.5 adicionamos preenchimento automatico de endereco via CEP e aplicamos o estilo moderno em todas as telas dos tipos de post personalizados.
Na versao 2.6 os campos de endereco foram separados (rua, numero, bairro, complemento, cidade, estado e pais) e sao preenchidos automaticamente ao digitar o CEP. A versao 2.6.1 corrigiu o carregamento automatico do endereco e suavizou o visual com cantos arredondados. A versao 2.6.2 inclui uma folha de estilo adicional baseada em templates SaaS, com barra lateral moderna e cor principal #ff7700.

Ao desinstalar o plugin, todos os registros e metas personalizados sao removidos automaticamente.

