import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

import '../../../shared/widgets/primary_button.dart';
import '../../dashboard/presentation/dashboard_page.dart';

class OnboardingPage extends StatelessWidget {
  const OnboardingPage({super.key});

  static const routePath = '/onboarding';
  static const routeName = 'onboarding';

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Scaffold(
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const SizedBox(height: 32),
              Text(
                'Bem-vindo à Academia da Comunicação',
                style: theme.textTheme.headlineMedium?.copyWith(
                  fontWeight: FontWeight.w700,
                ),
              ),
              const SizedBox(height: 12),
              Text(
                'Personalize seus estudos escolhendo o objetivo principal para liberar playlists, simulados express e trilhas orientadas.',
                style: theme.textTheme.bodyLarge,
              ),
              const Spacer(),
              PrimaryButton(
                label: 'Começar agora',
                onPressed: () => context.go(DashboardPage.routePath),
              ),
              const SizedBox(height: 24),
            ],
          ),
        ),
      ),
    );
  }
}
