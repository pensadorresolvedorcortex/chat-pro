import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:go_router/go_router.dart';

import 'core/theme/app_theme.dart';
import 'features/dashboard/dashboard_page.dart';
import 'features/onboarding/onboarding_page.dart';
import 'features/operations/operations_page.dart';
import 'features/paywall/paywall_page.dart';
import 'features/questions/questions_page.dart';
import 'features/simulados/simulados_page.dart';
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
        path: QuestionsPage.routePath,
        name: QuestionsPage.routeName,
        builder: (context, state) => const QuestionsPage(),
      ),
      GoRoute(
        path: SimuladosPage.routePath,
        name: SimuladosPage.routeName,
        builder: (context, state) => const SimuladosPage(),
      ),
      GoRoute(
        path: OperationsPage.routePath,
        name: OperationsPage.routeName,
        builder: (context, state) => const OperationsPage(),
      ),
      GoRoute(
        path: '/biblioteca',
        name: 'biblioteca',
        builder: (context, state) => const FeaturePlaceholderPage(
          title: 'Biblioteca',
          description:
              'A biblioteca será conectada ao CMS quando as APIs estiverem prontas.',
        ),
      ),
      GoRoute(
        path: '/mentorias',
        name: 'mentorias',
        builder: (context, state) => const FeaturePlaceholderPage(
          title: 'Mentorias',
          description:
              'Mentorias guiadas serão disponibilizadas com agendamento em uma próxima fase.',
        ),
      ),
    ],
  );
});

class AcademiaApp extends ConsumerWidget {
  const AcademiaApp({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final router = ref.watch(_routerProvider);
    return MaterialApp.router(
      title: 'Academia da Comunicação',
      theme: AppTheme.light,
      darkTheme: AppTheme.dark,
      routerConfig: router,
      debugShowCheckedModeBanner: false,
      locale: const Locale('pt', 'BR'),
      localizationsDelegates: const [
        GlobalMaterialLocalizations.delegate,
        GlobalWidgetsLocalizations.delegate,
        GlobalCupertinoLocalizations.delegate,
      ],
      supportedLocales: const [Locale('pt', 'BR')],
    );
  }
}
