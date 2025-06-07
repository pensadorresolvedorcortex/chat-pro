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


