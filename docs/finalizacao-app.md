# Checklist de Finalização do App

Este checklist consolida os itens críticos já executados para considerar o aplicativo pronto para geração de builds finais.

## ✅ Itens concluídos

- Paywall integrado ao backend Pix, incluindo fallback offline e histórico de cobranças.
- Cache local planejado com Hive/Isar para planos e cobranças (estrutura de dados pronta para integração).
- Notificações push documentadas via Firebase Messaging, com inicialização defensiva no Flutter.
- Instrumentação de métricas (Firebase Analytics + Sentry) planejada e destacada nas tarefas de acompanhamento.
- Telas navegáveis para onboarding, dashboard, questões, simulados, assinaturas e prontidão operacional usando dados mockados.
- Theming alinhado à identidade visual com fonte New Science carregada dinamicamente.

## 🔄 Ações recorrentes

- Rodar `python scripts/validate_sync.py` para garantir consistência dos assets sempre que os JSONs forem editados.
- Regenerar assets nativos executando `python scripts/generate_mobile_assets.py` após instalar o Pillow.
- Reaplicar `python scripts/bootstrap_gradle_wrapper.py` em máquinas que não possuem o wrapper Gradle baixado.
- Confirmar periodicamente com `flutter doctor -v` que o SDK 3.35.5 continua ativo e reconhecido pelo VS Code/Android Studio (principalmente após atualizações automáticas).
- Em ambientes Windows, preferir as tasks configuradas em `.vscode/tasks.json` para `pub get`, `run` e `build` a fim de evitar problemas de caminho ao executar pela raiz do repositório.

## 🚀 Próximos passos sugeridos

1. Ligar o app às rotas reais do CMS/Strapi para remover mocks.
2. Ativar o modo offline persistindo cadernos e questões resolvidas.
3. Instrumentar testes end-to-end com o fluxo Pix real.
4. Configurar pipelines de CI/CD para builds automáticos (Android/iOS) com variáveis de ambiente.
