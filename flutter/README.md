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

- Flutter 3.16+
- Dart 3.2+
- FVM (opcional) para gerenciar versões

## Primeiros passos

1. Instale as dependências: `flutter pub get`.
2. Rode os analisadores: `flutter analyze` e `dart run build_runner build --delete-conflicting-outputs` quando houver código gerado.
3. Execute o app: `flutter run -d chrome` ou `flutter run -d emulator-5554`.

Para mais detalhes sobre fluxos, integrações (Pix, Firebase) e design system, consulte `../docs/mega-resumo-codex.md`.

## Configuração de ambientes

O app consulta a API do Pix usando a URL definida via `--dart-define`:

```
flutter run --dart-define=PIX_API_BASE_URL=https://sua-api-pix.dev/qc/v1
```

Se nenhuma variável for informada, o app utilizará `https://api.academiadacomunicacao.com/qc/v1`. Também é possível usar `API_BASE_URL` como alias.
