# Release Runbook – Academia da Comunicação

Este runbook orienta a preparação e publicação de uma nova versão do app mobile, considerando os ambientes configurados no projeto.

## 1. Preparação

1. Validar o ambiente com `flutter doctor -v` garantindo que a versão estável 3.35.5 esteja ativa e que somente o aviso opcional do Visual Studio apareça.
2. Atualizar dependências Flutter com `flutter pub upgrade` e validar o `pubspec.lock` (se versionado).
3. Rodar `python scripts/validate_sync.py` para confirmar que os assets continuam consistentes com o CMS.
4. Garantir que `python scripts/generate_mobile_assets.py` foi executado localmente para gerar ícones/telas antes do build.
5. Revisar métricas de prontidão em [`docs/status/platform-readiness.md`](status/platform-readiness.md) e atualizar notas, se necessário.
6. Confirmar que o CMS/Strapi está sincronizado e que os planos Pix ativos possuem chave e QR atualizados.
7. Se o build for disparado pelo VS Code, usar as tasks `Flutter: pub get` e `Flutter: build apk` disponíveis em `.vscode/tasks.json` para evitar problemas de diretório em Windows.

## 2. Build Android

1. Executar `python scripts/bootstrap_gradle_wrapper.py` caso esteja em uma máquina nova (o script baixa a versão 8.9 compatível com o AGP 8.7.3 exigido pelo Flutter 3.35).
2. Configurar variáveis de ambiente (ou `--dart-define`) para apontar para a API correta.
3. Rodar `flutter build apk --release` ou `flutter build appbundle --release` (pelo terminal ou via tarefa do VS Code).
4. Validar o artefato em um dispositivo físico usando `flutter install` ou `adb install`.

## 3. Build iOS

1. Rodar `pod install` dentro de `flutter/ios` após garantir que o `GoogleService-Info.plist` esteja presente.
2. Abrir `Runner.xcworkspace`, selecionar o esquema `Runner` e assinar com a equipe correta.
3. Executar `Product > Archive` e enviar para o TestFlight/App Store.

## 4. Pós-build

1. Executar smoke tests (login, dashboard, geração de cobrança Pix, leitura de questões, simulados, prontidão operacional).
2. Revisar logs e métricas (Sentry, Crashlytics, Analytics) para confirmar ausência de erros críticos.
3. Atualizar [`docs/go-live-report.md`](go-live-report.md) com data da publicação, versão e destaques.
4. Comunicar stakeholders e atualizar indicadores no painel interno.

## 5. Contingência

- Em caso de falha no Pix, seguir o playbook descrito em [`docs/go-live-report.md`](go-live-report.md) na seção de monitoramento.
- Caso a publicação precise ser revertida, utilizar as versões anteriores armazenadas no TestFlight/Play Console e revogar os planos Pix recém-criados no CMS.
