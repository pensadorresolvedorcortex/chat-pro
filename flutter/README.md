# Academia da Comunicação – App Flutter

Este diretório contém o aplicativo mobile construído com **Flutter 3**, arquitetura limpa e Riverpod, conforme descrito no mega resumo em `../docs/mega-resumo-codex.md`.

## Estrutura inicial

```
flutter/
├── lib/
│   ├── app.dart           # Configuração principal do MaterialApp/GoRouter
│   ├── main.dart          # Ponto de entrada com bootstrap de dependências
│   └── features/          # Módulos do app (questões, simulados, planos, etc.)
├── assets/
│   └── images/            # Placeholders para os assets de UI
├── analysis_options.yaml  # Regras de lint recomendadas
├── pubspec.yaml           # Dependências Flutter/Dart
└── README.md
```

Os módulos serão preenchidos gradualmente seguindo os fluxos exibidos nos prints compartilhados no mega resumo.

## Requisitos

- Flutter 3.35+
- Dart 3.9+
- FVM (opcional) para gerenciar versões

## Primeiros passos

1. Instale as dependências: `flutter pub get` (a pubspec já está otimizada para o Flutter 3.35 e o Dart 3.9).
2. Rode os analisadores: `flutter analyze` e `dart run build_runner build --delete-conflicting-outputs` quando houver código gerado.
3. Valide os assets mockados: `python ../scripts/validate_sync.py`.
4. Gere os binários nativos ausentes: `python ../scripts/bootstrap_gradle_wrapper.py` e `python ../scripts/generate_mobile_assets.py`.
5. Execute o app: `flutter run -d chrome` ou `flutter run -d emulator-5554`.

> ✅ **Compatibilidade 3.35**: a camada Android utiliza AGP 8.5.2, Kotlin 2.0.21 e Gradle 8.7 — combinação suportada oficialmente pelo Flutter 3.35. Caso você atualize o SDK Flutter, execute novamente os scripts de bootstrap para alinhar o wrapper Gradle local.
>
> ⚠️ **Erro `com/android/builder/model/BuildType`**: se você observar esse stack trace ao rodar `assembleRelease`, verifique se está usando exatamente o trio AGP 8.5.2 + Gradle 8.7. Versões mais recentes (8.6+/8.8+) removem APIs que o plugin do Flutter 3.35 ainda referencia.

> 🛠️ **Gradle alinhado ao Flutter**: o `android/settings.gradle` segue o modelo oficial da linha 3.35, aplicando o plugin `dev.flutter.flutter-gradle-plugin` pelo bloco `plugins {}`. Basta garantir que o `local.properties` possua a entrada `flutter.sdk` apontando para o diretório do SDK no Windows (ex.: `C:\\dev\\flutter`) antes de abrir o projeto no Android Studio ou rodar qualquer tarefa via VS Code.

> ℹ️ **Fonte New Science**: para evitar versionar binários incompatíveis com o Codex, as variantes Regular/Medium/Bold foram incorporadas em `lib/core/theme/app_fonts.dart` codificadas em Base64. O bootstrap (`main.dart`) chama `AppFonts.ensureLoaded()` antes de renderizar o app, garantindo que o `FontLoader` registre a família `New Science` dinamicamente.

## Fluxo sugerido no VS Code

Este repositório mantém o app Flutter dentro de `flutter/`, por isso o plugin da Dart-Code pode não detectar automaticamente o projeto quando você abre a raiz (`chat-pro/`). Para resolver:

1. Abra a pasta raiz no VS Code (`Arquivo > Abrir Pasta`).
2. Aceite a recomendação de extensões (Flutter, Dart e Error Lens) apresentada pela IDE.
3. Utilize o comando **Flutter: Run app** na aba _Run and Debug_ (atalho `F5`). Os arquivos `.vscode/launch.json` e `.vscode/tasks.json` já encaminham os comandos para a subpasta `flutter/`.
4. Para obter dependências ou gerar builds via _Terminal > Run Task_, escolha as tarefas `Flutter: pub get`, `Flutter: run` ou `Flutter: build apk`. Elas executam `flutter` com o diretório atual configurado para `flutter/`, evitando erros de caminho.
5. Se necessário, ajuste `dart.flutterSdkPath` nas configurações do VS Code (padrão: detecta automaticamente o SDK instalado). A configuração `dart.flutterProjectBasePath` definida em `.vscode/settings.json` já aponta para `flutter/` para que o analisador reconheça o projeto.

As exclusões em `.vscode/settings.json` reduzem alertas de arquivos gerados (`.dart_tool/`, `.gradle/`, `ios/.symlinks`) e desativam watchers redundantes, melhorando o desempenho em Windows.

Para mais detalhes sobre fluxos, integrações (Pix, Firebase) e design system, consulte `../docs/mega-resumo-codex.md`.

## Configuração adicional no iOS

O diretório `ios/` já traz um projeto Xcode configurado com ícones, telas de lançamento e suporte a push notifications/Firebase. Para rodá-lo em um Mac:

1. Garanta que o CocoaPods esteja instalado (`sudo gem install cocoapods`).
2. Instale as dependências nativas executando `cd ios && pod install`.
3. Defina o bundle identifier e a team ID em **Runner > Signing & Capabilities**.
4. Ajuste o arquivo `Runner/Runner.entitlements` para o ambiente desejado (`development`/`production`) e confirme que ele está associado às configurações de build.
5. Adicione seu `GoogleService-Info.plist` em `ios/Runner/` para inicializar o Firebase no iOS.
6. Verifique em **Signing & Capabilities** se **Push Notifications** e **Background Modes (Remote notifications)** estão ativos; o projeto já inclui as entitlements necessárias e o `AppDelegate` envia o token atualizado pelo `NotificationCenter` (`FCMTokenRefreshed`).
7. O `AppDelegate` também publica o token do Firebase Messaging via `FlutterEventChannel` (`academy.flutter/fcm_token/events`) e expõe o método `getToken` para consultas pontuais — no Flutter use `fcmTokenStreamProvider` para reagir às atualizações.
8. Há um `FlutterMethodChannel` dedicado (`academy.flutter/notifications/methods`) para consultar, solicitar e abrir as configurações de autorização de notificações diretamente do iOS. No Flutter, utilize `notificationPermissionControllerProvider` para gerenciar o ciclo de permissão.
9. O canal `academy.flutter/config/methods` expõe os valores definidos no `Info.plist` (por padrão, `AcademiaApiBaseUrl` e `AcademiaEnvironment`). Ajuste os valores `ACADEMIA_API_BASE_URL` e `ACADEMIA_ENVIRONMENT` em `ios/Flutter/Debug.xcconfig` e `ios/Flutter/Release.xcconfig` para personalizar os ambientes, ou utilize `--dart-define` para sobrescrever em tempo de execução.
10. Há também canais (`academy.flutter/apns_token/events` e `academy.flutter/apns_token/methods`) que expõem o token APNs bruto para integrações que precisam registrar o dispositivo diretamente com backends próprios; em Dart utilize `apnsTokenStreamProvider` para acompanhar atualizações.
11. Eventos de notificações (`academy.flutter/notifications/events`) são propagados para o Flutter com metadados de origem (`apns` ou `fcm`), trigger (`willPresent`, `didReceive`, `remote`, `launch`), estado do app no recebimento (`active`, `inactive`, `background`), timestamp (`receivedAt`) e `userInfo` sanitizado. A payload agora inclui também o conteúdo do `UNNotificationContent` (título, corpo, badge, identificadores de thread, anexos com URL/UTI) e texto digitado em ações `UNTextInputNotificationResponse`, permitindo reproduzir a experiência completa no Dart.
12. Para detectar o push que abriu o app a partir de um cold start, utilize `remoteInitialNotificationProvider` ou simplesmente ouça o `remoteNotificationStreamProvider`, que agora reenvia o evento inicial antes de transmitir os eventos em tempo real.
13. Use `RemoteNotificationChannel.instance.setForegroundPresentationOptions(...)` para definir como notificações devem aparecer em primeiro plano (banner, lista, alerta, som, badge) ou até ocultá-las completamente — combine com o provider `remoteNotificationStreamProvider` para tratar eventos manualmente no Flutter.
14. Categorias interativas de notificação podem ser registradas em tempo de execução via `RemoteNotificationChannel.instance.setCategories(...)`, permitindo ações rápidas (`Copiar código`, `Marcar como pago`, `Responder suporte`) diretamente das notificações Pix no iOS.
15. O canal expõe utilitários adicionais para iOS/macOS: liste notificações entregues/pedidas, remova-as por identificador (`removeDeliveredNotifications`/`removePendingNotificationRequests`) e administre o badge com `badgeCount`, `setBadgeCount`, `incrementBadgeCount` e `clearBadgeCount` — úteis para sincronizar o contador após confirmar cobranças Pix.

Os assets do app seguem a paleta oficial (primária `#6645f6`, secundária `#1dd3c4`) e devem ser gerados localmente com `python ../scripts/generate_mobile_assets.py` antes de abrir o projeto no Xcode ou compilar o Android (os PNGs não são versionados para facilitar exportações do repositório). O script depende do [Pillow](https://pypi.org/project/Pillow/) apenas em tempo de desenvolvimento:

```
pip install pillow
python ../scripts/generate_mobile_assets.py
```

Ele renderiza o ícone e a tela de lançamento diretamente a partir das formas vetoriais definidas no repositório para iOS e Android, preservando a exigência de não versionar arquivos binários.

## Bootstrap do Android

Para manter o wrapper Gradle fora do controle de versão, execute `python ../scripts/bootstrap_gradle_wrapper.py` antes de abrir o projeto no Android Studio ou rodar `./gradlew`. O script lê `gradle/gradle-wrapper.properties`, baixa o artefato oficial e grava `gradle-wrapper.jar` localmente.

> ℹ️ O arquivo `android/gradle.properties` já está versionado com as flags recomendadas (`android.useAndroidX=true`, `android.nonTransitiveRClass=true`, cache do Gradle e JVM args ajustados). Ajuste-o caso seu ambiente precise de parâmetros diferentes.


## Configuração de ambientes

O app consulta a API do Pix usando a URL definida via `--dart-define`:

```
flutter run --dart-define=PIX_API_BASE_URL=https://sua-api-pix.dev/qc/v1
```

Se nenhuma variável for informada, o app utilizará `https://api.academiadacomunicacao.com/qc/v1`. Também é possível usar `API_BASE_URL` como alias.
