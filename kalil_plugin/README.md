# Kalil

Plugin WordPress para area de membros com troca de arquivos, envio de vídeos e bate-papo entre administrador e paciente.

## Uso

- Ative o plugin em seu WordPress.
- Crie uma pagina e adicione o shortcode `[kalil_member_area]` para exibir a area de membros.
  - Administradores possuem um campo de seleção para escolher o paciente com quem desejam conversar.
  - A lista exibe todos os pacientes cadastrados (usuários com papel de subscriber).
- Para registrar novos pacientes, use o shortcode `[kalil_register redirect="URL"]` informando a URL da pagina onde o shortcode `[kalil_member_area]` está presente.


O chat e as mensagens enviadas são armazenadas como um tipo de post personalizado.
Os anexos podem ser imagens ou vídeos, exibidos diretamente na conversa.

### Atualizações

Versão 1.0.1 adiciona visualização de imagens e vídeos no histórico de mensagens e exibe a data de envio.
Versão 1.1.0 permite conversas entre administrador e paciente especificando o atributo `patient` no shortcode.
Versão 1.2.0 adiciona o shortcode `[kalil_register]` para criação de contas e redirecionamento automático para a área de membros.
Versão 1.2.1 corrige acentuação e melhora o estilo do botão.
Versão 1.2.2 adiciona campo "Nome completo" no cadastro e aplica estilos com !important.
Versão 1.2.3 renomeia o botão de registro para "Cadastrar" e garante o redirecionamento para a área de membros após o cadastro.
Versão 1.2.4 corrige o processo de cadastro para exibir erros e redirecionar corretamente usando `wp_safe_redirect`.
Versão 1.2.5 trata o cadastro antes da saída de página e inclui campo oculto de redirecionamento para evitar tela em branco.
Versão 1.2.6 adiciona navegação em abas na área de membros para filtrar Documentos, Vídeos e Conversas e atualiza o layout para 1200px com fundo branco.
Versão 1.2.7 permite que administradores selecionem o paciente em um menu de busca na área de membros.
Versão 1.2.8 adiciona uma página "Kalil Messages" no painel administrativo com o mesmo campo de seleção de pacientes.
Versão 1.2.9 permite definir quanto tempo o paciente tem acesso às mensagens (3, 6 ou 12 meses) e deixa a fonte mais grossa.
Versão 1.3.0 adiciona abas "Entrar no Cadastro" e "Cadastrar no site" no shortcode `[kalil_register]`, permitindo login ou registro na mesma página.
Versão 1.3.1 corrige as abas do formulário de cadastro para funcionarem mesmo quando a página não contém o shortcode `[kalil_member_area]`.
Versão 1.3.2 melhora o estilo dos formulários de login e cadastro e arredonda o botão "Cadastrar".


