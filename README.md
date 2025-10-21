# Slider Revolution step-by-step template

This repository contains a three-step onboarding Slider Revolution template.
Because binary attachments are not accepted, the slider export is stored as
plain text. You now have multiple ways to turn that source into the `.zip`
archive that Slider Revolution expects during import.

## Option 1: Double-click `build_slider_zip.bat` (Windows)

1. Double-click `build_slider_zip.bat`.
2. Watch the console window: it reports every attempt (CertUtil → PowerShell →
   Python) and tells you which one succeeded or why it failed.
3. When you see the success message, confirm that `slider_step_by_step.zip`
   sits next to the script and then import it into Slider Revolution.

If anything fails, keep the window open and follow the on-screen hints. The
following sections describe the same steps in more detail so you can run them
manually.

This approach works even when PowerShell execution policies block scripts, as
long as `certutil` is present (it ships with Windows).

## Option 2: Manual step-by-step (Windows, Português)

1. Extraia o conteúdo do repositório para uma pasta acessível, por exemplo,
   `C:\Users\seu-usuario\Downloads\chat-pro`.
2. Pressione `Win + R`, digite `cmd` e pressione Enter para abrir o Prompt de
   Comando.
3. Navegue até a pasta do projeto:
   ```bat
   cd /d C:\Users\seu-usuario\Downloads\chat-pro
   ```
4. Rode o comando integrado do Windows para gerar o arquivo `.zip`:
   ```bat
   certutil -decode slider_step_by_step.zip.base64 slider_step_by_step.zip
   ```
   Você deverá ver a mensagem `CertUtil: -decode command completed successfully.`
5. Confira se `slider_step_by_step.zip` apareceu na pasta. O arquivo deve ter
   cerca de 11 kB.
6. Importe `slider_step_by_step.zip` no Slider Revolution.

Se o comando exibir um erro dizendo que `certutil` não foi encontrado, verifique
se está executando o Prompt de Comando padrão do Windows. Caso o arquivo `.zip`
não apareça, confira se você está dentro da pasta correta (passo 3) ou tente o
script Python descrito na opção 3.

## Option 3: One-click decode via `certutil` helper (Windows)

1. Coloque `decode_slider_zip.cmd` e `slider_step_by_step.zip.base64` na mesma
   pasta (eles já ficam lado a lado neste repositório).
2. Clique duas vezes em `decode_slider_zip.cmd`.
3. Ao ver a mensagem de sucesso, importe o arquivo `slider_step_by_step.zip`
   criado ao lado do script.

Esse assistente usa apenas o `certutil`, portanto funciona mesmo quando scripts
PowerShell são bloqueados.

## Option 4: Build with Python (macOS, Linux, or Windows)

1. Make sure Python 3.8+ is available (check with `python3 --version`).
2. Run the helper script from the repository root:
   ```bash
   python3 build_slider_zip.py
   ```
3. Import the generated `slider_step_by_step.zip` file into Slider Revolution.

The resulting archive contains the `slider_export.txt` manifest with embedded
inline SVG backgrounds, so there are no binary image files to manage.
