# Kalil

Plugin WordPress para area de membros com troca de arquivos, envio de vídeos e bate-papo entre administrador e paciente.

## Uso

- Ative o plugin em seu WordPress.
- Crie uma pagina e adicione o shortcode `[kalil_member_area]` para exibir a area de membros.
  - Administradores podem definir `patient="ID"` para conversar com um paciente específico.
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


