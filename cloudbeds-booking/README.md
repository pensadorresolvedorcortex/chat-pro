# Cloudbeds Booking Shortcodes

Fornece dois shortcodes que enviam reservas para o Cloudbeds:

- `[cloudbeds_booking_horizontal]` gera o formulário horizontal com campos de check-in, check-out, quartos e hóspedes.
- `[cloudbeds_booking_vertical]` gera o formulário vertical com os mesmos campos.

Ao ativar o plugin, o elemento `.cs-room-booking` do tema é substituído automaticamente pela versão vertical. O CSS usa a mesma tipografia das classes `.cs-title` e `.cs-info-box-title` e define botões em rosa claro para seguir o visual original. A partir da versão 1.8.1, o redirecionamento só ocorre ao clicar no botão **CHECAR VAGAS** ou enviar formulários que contenham os campos de check-in, check-out, quartos e hóspedes completamente preenchidos, mesmo em versões do formulário fornecidas pelo tema.
