# Auditoria de Melhorias – Frontend e Backend

Este relatório captura ajustes aplicados nesta iteração e oportunidades adicionais para evoluir o app Flutter e o backend Strapi/API.

## ✅ Ajustes aplicados agora

| Área | O que foi feito | Impacto |
| --- | --- | --- |
| Paywall Flutter | Tratamento resiliente para coleções de planos vindas do backend ou do asset local, ignorando entradas inválidas em vez de falhar o carregamento inteiro. | Evita travamentos quando o CMS devolve dados incompletos e mantém a listagem disponível com o que for válido. |
| Paywall Flutter | `RefreshIndicator` na listagem de planos com `ref.refresh(plansProvider.future)` para permitir recarregar dados Pix manualmente. | Garante UX melhor ao testar integrações do backend sem precisar reiniciar o app. |
| Dashboard Flutter | Seção “Quem está voando” com cards de alunos destacados, aproveitando os exemplos de usuários para deixar a home navegável como no QConcursos. | Aumenta a percepção de comunidade e dá contexto imediato sobre desempenho real. |
| Documentação | Novos JSONs (`onboarding`, `perfil`, `configuracoes`) e enriquecimento de `usuarios`/`dashboard_home` para cobrir todas as áreas do app. | Facilita visualizar o estado de cada tela no Codex e no backend Strapi. |

## 🔍 Itens priorizados para as próximas entregas

### Frontend (Flutter)

1. **Estados Pix persistentes** – Persistir cobranças Pix geradas (por Hive/Isar) para restaurar QR Code e código copia e cola após fechar o app.
2. **Telemetry de erros** – Integrar `Firebase Crashlytics` ou `Sentry` para capturar exceções de parsing ignoradas agora via log.
3. **Indicadores de fallback** – Sinalizar visualmente quando o app estiver exibindo dados offline para apoiar QA.

### Backend (Strapi/API)

1. **Normalização de payloads** – Garantir que o CMS devolva listas consistentes (`planos`, `data`, `items`) para reduzir ramificações no cliente.
2. **Endpoint de auditoria de preços** – Implementar histórico das edições de preço já documentado no OpenAPI (`/planos/{id}/preco`) para que o app reflita mudanças em tempo real.
3. **Testes automatizados de contrato** – Configurar suíte que valide exemplos JSON (`docs/examples/*.json`) contra o schema do OpenAPI para evitar regressões de formato.

### Observabilidade e qualidade

- **Alertas Pix** – Adicionar métricas para tempo de geração de cobrança e taxa de erro por plano (Prometheus/Grafana).
- **CI lint/format** – Automatizar `flutter analyze` e `dart format` no pipeline para preservar a padronização.

## 📌 Referências rápidas

- Paywall Flutter: `flutter/lib/features/paywall/*`
- Repositórios de planos e fallback Pix: `flutter/lib/features/paywall/data`
- Contrato da API e exemplos: `docs/openapi.yaml`, `docs/examples/`

