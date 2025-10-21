# Slider Revolution step-by-step template

Este repositório contém um template de onboarding em três etapas para o Slider
Revolution. O conteúdo do slider permanece em texto puro no arquivo
`slider_step_by_step_export.txt`, permitindo o versionamento no GitHub sem
arquivos binários. A geração do pacote `.zip` agora acontece sob demanda através
de um único utilitário PHP.

## Geração rápida via PHP (macOS, Linux ou Windows)

1. Certifique-se de que o PHP 7.4 ou superior está instalado e que a extensão
   `ZipArchive` está ativa (`php -m | findstr Zip` no Windows ou
   `php -m | grep Zip` no macOS/Linux).
2. No diretório do repositório, execute:
   ```bash
   php generate_slider.php
   ```
3. O comando cria `slider_step_by_step.zip` na mesma pasta. Em seguida importe o
   arquivo no Slider Revolution.

Se preferir escolher outro nome ou pasta de saída:
```bash
php generate_slider.php /caminho/para/minha_pasta/onboarding.zip
```
O utilitário acrescenta automaticamente a extensão `.zip` caso esteja ausente.

## Download direto a partir do seu servidor (fluxo 100% web)

1. Envie os arquivos `generate_slider.php` e `slider_step_by_step_export.txt`
   para uma pasta acessível no seu site (por exemplo, usando o Gerenciador de
   Arquivos do cPanel ou um cliente FTP).
2. Acesse a URL correspondente no navegador, algo como:
   ```
   https://seusite.com.br/slider/generate_slider.php
   ```
3. O download de `slider_step_by_step.zip` iniciará automaticamente. Importe o
   arquivo no Slider Revolution.

Você pode personalizar o nome final acrescentando `?filename=meu_template` à URL:
```
https://seusite.com.br/slider/generate_slider.php?filename=onboarding
```
O script valida o parâmetro e gera `onboarding.zip`.

## Mensagens de erro comuns

| Situação | Como resolver |
| --- | --- |
| `ZipArchive` não disponível | Habilite a extensão no `php.ini` ou solicite ao provedor de hospedagem que ative o módulo. |
| Arquivo de exportação ausente | Confirme que `generate_slider.php` e `slider_step_by_step_export.txt` estão lado a lado. |
| Download vazio | Verifique o log de erros do servidor para identificar permissões de escrita na pasta temporária utilizada pelo PHP. |

## Estrutura do slider

O arquivo `slider_step_by_step_export.txt` contém o JSON completo do slider, com
backgrounds em SVG inline e três slides:

1. **Conheça o produto** – apresenta a jornada e destaca o botão “Próximo”.
2. **Personalize a experiência** – explica a etapa de configuração.
3. **Crie ou acesse sua conta** – inclui o botão que abre o popup para login ou
   cadastro.

Esses detalhes serão restaurados automaticamente ao importar o `.zip` gerado
pelo script.
