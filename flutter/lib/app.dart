import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import 'core/notifications/fcm_token_provider.dart';
import 'core/theme/app_theme.dart';
import 'features/dashboard/presentation/dashboard_page.dart';
import 'features/onboarding/presentation/onboarding_page.dart';
import 'features/operations/presentation/operations_page.dart';
import 'features/paywall/application/pix_charge_notification_listener.dart';
import 'features/paywall/presentation/paywall_page.dart';
import 'features/questions/presentation/questions_page.dart';
import 'features/simulados/presentation/simulados_page.dart';
import 'shared/pages/feature_placeholder_page.dart';

final _routerProvider = Provider<GoRouter>((ref) {
  return GoRouter(
    initialLocation: OnboardingPage.routePath,
    routes: [
      GoRoute(
        path: OnboardingPage.routePath,
        name: OnboardingPage.routeName,
        builder: (context, state) => const OnboardingPage(),
      ),
      GoRoute(
        path: DashboardPage.routePath,
        name: DashboardPage.routeName,
        builder: (context, state) => const DashboardPage(),
      ),
      GoRoute(
        path: PaywallPage.routePath,
        name: PaywallPage.routeName,
        builder: (context, state) => const PaywallPage(),
      ),
      GoRoute(
        path: '/metas/criar',
        name: 'metas-criar',
        builder: (context, state) => const FeaturePlaceholderPage(
          title: 'Planejar metas',
          description:
              'O fluxo de metas será conectado ao backend nesta etapa final.',
        ),
      ),
      GoRoute(
        path: '/questoes/recomendadas',
        name: 'questoes-recomendadas',
        builder: (context, state) => const QuestionsPage(),
      ),
      GoRoute(
        path: SimuladosExpressPage.routePath,
        name: SimuladosExpressPage.routeName,
        builder: (context, state) => const SimuladosExpressPage(),
      ),
      GoRoute(
        path: '/desafios/ranking',
        name: 'desafios-ranking',
        builder: (context, state) => const FeaturePlaceholderPage(
          title: 'Ranking TJ-CE',
          description:
              'Esta tela exibirá o ranking sincronizado com os desafios Pix.',
        ),
      ),
      GoRoute(
        path: '/biblioteca',
        name: 'biblioteca',
        builder: (context, state) => const FeaturePlaceholderPage(
          title: 'Biblioteca',
          description:
              'A biblioteca móvel será liberada após a sincronização com o CMS.',
        ),
      ),
      GoRoute(
        path: OperationsPage.routePath,
        name: OperationsPage.routeName,
        builder: (context, state) => const OperationsPage(),
      ),
    ],
  );
});

class AcademiaApp extends ConsumerWidget {
  const AcademiaApp({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final router = ref.watch(_routerProvider);
    ref.watch(pixChargeNotificationListenerProvider);
    ref.listen<AsyncValue<String>>(
      fcmTokenStreamProvider,
      (_, value) {
        value.whenData(debugPrint);
      },
    );

    return MaterialApp.router(
      title: 'Academia da Comunicação',
      theme: AppTheme.light,
      darkTheme: AppTheme.dark,
      routerConfig: router,
      debugShowCheckedModeBanner: false,
      locale: const Locale('pt', 'BR'),
      supportedLocales: const [Locale('pt', 'BR')],
    );
  }
}
