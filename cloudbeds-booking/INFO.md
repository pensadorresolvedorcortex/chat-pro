# Informações do Plugin Cloudbeds Booking

## Visão geral
- Plugin WordPress que fornece shortcodes para formulários de reserva integrados ao Cloudbeds.
- Versão atual 1.8.1, autores listados como ChatGPT Codex.

## Shortcodes disponíveis
| Shortcode | Layout | Campos | Ação |
|-----------|--------|--------|------|
| `[cloudbeds_booking_horizontal]` | Formulário horizontal compacto | Check-in, Check-out, Quartos, Hóspedes | Envia dados via GET para `https://hotels.cloudbeds.com/reservas/VA2vgp` |
| `[cloudbeds_booking_vertical]` | Formulário vertical responsivo | Check-in, Check-out, Quartos, Hóspedes | Envia dados via GET para `https://hotels.cloudbeds.com/reservas/VA2vgp` |

## Funcionalidades automáticas
- Substitui o conteúdo do seletor `.cs-room-booking` pelo formulário vertical ao carregar o `wp_footer` (exceto em área administrativa).
- Intercepta o envio do formulário e redireciona ao Cloudbeds somente após clicar no botão **CHECAR VAGAS** ou enviar formulários que tenham todos os campos preenchidos, inclusive versões fornecidas pelo tema.

## Estilos aplicados
- Tipografia herda a variável `--heading-font` com fallback para "Marcellus".
- Botões estilizados com fundo rosa claro `#e6d9d3`, sem borda e com padding de 30px.
- Layout horizontal usa `flex` com quebra automática e responsividade para telas até 480px.
- Layout vertical empilha campos com espaçamento de 12px e largura total para entradas e botão.

## Arquivos
- `cloudbeds-booking.php`: Registro de shortcodes, injeção de scripts e substituição de formulário padrão.
- `style.css`: Estilos compartilhados dos formulários.
- `README.md`: Resumo rápido dos shortcodes e comportamento principal.
