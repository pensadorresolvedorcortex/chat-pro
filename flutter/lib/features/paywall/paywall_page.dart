import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/data/asset_loader.dart';
import '../../shared/widgets/app_preloader.dart';
import '../../shared/widgets/app_scaffold.dart';

class Plan {
  const Plan({
    required this.id,
    required this.name,
    required this.description,
    required this.type,
    required this.periodicity,
    required this.price,
    required this.currency,
    required this.benefits,
  });

  final String id;
  final String name;
  final String description;
  final String type;
  final String periodicity;
  final double price;
  final String currency;
  final List<String> benefits;

  bool get isFree => price == 0;

  factory Plan.fromMap(Map<String, dynamic> map) {
    return Plan(
      id: map['id'] as String,
      name: map['nome'] as String,
      description: map['descricao'] as String,
      type: map['tipo'] as String,
      periodicity: map['periodicidade'] as String,
      price: (map['preco'] as num).toDouble(),
      currency: map['moeda'] as String,
      benefits:
          (map['beneficios'] as List<dynamic>).map((e) => e as String).toList(),
    );
  }
}

final plansProvider = FutureProvider<List<Plan>>((ref) async {
  final loader = AssetLoader(rootBundle);
  final map = await loader.loadJson('assets/data/planos.json');
  final plans = (map['planos'] as List<dynamic>)
      .map((item) => Plan.fromMap(item as Map<String, dynamic>))
      .toList();
  plans.sort((a, b) => a.price.compareTo(b.price));
  return plans;
});

class PaywallPage extends ConsumerWidget {
  const PaywallPage({super.key});

  static const routePath = '/paywall';
  static const routeName = 'paywall';

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final asyncPlans = ref.watch(plansProvider);
    return asyncPlans.when(
      loading: () => AppScaffold(
        body: const Center(
          child: AppPreloader(message: 'Carregando planos Pix...'),
        ),
      ),
      error: (error, stackTrace) => AppScaffold(
        body: Center(
          child: Padding(
            padding: const EdgeInsets.all(24),
            child: Text('Não foi possível carregar os planos.\n$error'),
          ),
        ),
      ),
      data: (plans) {
        return AppScaffold(
          body: ListView(
            padding: const EdgeInsets.fromLTRB(16, 24, 16, 32),
            children: [
              Container(
                padding: const EdgeInsets.all(24),
                decoration: BoxDecoration(
                  borderRadius: BorderRadius.circular(28),
                  gradient: const LinearGradient(
                    colors: [Color(0xFF6645f6), Color(0xFF1dd3c4)],
                    begin: Alignment.topLeft,
                    end: Alignment.bottomRight,
                  ),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Escolha o plano ideal',
                      style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                            color: Colors.white,
                            fontWeight: FontWeight.w700,
                          ),
                    ),
                    const SizedBox(height: 12),
                    Text(
                      'Sincronizado com o CMS e pronto para gerar cobranças Pix dinâmicas.',
                      style: Theme.of(context).textTheme.bodyLarge?.copyWith(
                            color: Colors.white.withOpacity(0.85),
                          ),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 24),
              ...plans.map((plan) => _PlanCard(plan: plan)),
              const SizedBox(height: 16),
            ],
          ),
        );
      },
    );
  }
}

class _PlanCard extends StatelessWidget {
  const _PlanCard({required this.plan});

  final Plan plan;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final colorScheme = theme.colorScheme;
    return Container(
      margin: const EdgeInsets.only(bottom: 20),
      padding: const EdgeInsets.all(22),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(24),
        color: colorScheme.surface,
        border: Border.all(color: colorScheme.outlineVariant),
        boxShadow: [
          BoxShadow(
            color: colorScheme.primary.withOpacity(0.06),
            blurRadius: 18,
            offset: const Offset(0, 12),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(
                child: Text(
                  plan.name,
                  style: theme.textTheme.titleLarge?.copyWith(
                    fontWeight: FontWeight.w700,
                  ),
                ),
              ),
              Chip(
                label: Text(plan.type == 'pago' ? 'Plano pago' : 'Plano grátis'),
                backgroundColor: plan.isFree
                    ? colorScheme.secondary.withOpacity(0.16)
                    : colorScheme.primary.withOpacity(0.16),
              ),
            ],
          ),
          const SizedBox(height: 12),
          Text(
            plan.description,
            style: theme.textTheme.bodyMedium,
          ),
          const SizedBox(height: 18),
          Row(
            crossAxisAlignment: CrossAxisAlignment.end,
            children: [
              Text(
                plan.isFree ? 'R\$ 0' : 'R\$ ${plan.price.toStringAsFixed(2)}',
                style: theme.textTheme.displaySmall?.copyWith(
                  fontWeight: FontWeight.w700,
                  color: plan.isFree ? colorScheme.secondary : colorScheme.primary,
                  height: 1,
                ),
              ),
              const SizedBox(width: 8),
              Text(
                '/ ${plan.periodicity}',
                style: theme.textTheme.titleMedium?.copyWith(
                  color: colorScheme.outline,
                ),
              ),
            ],
          ),
          const SizedBox(height: 18),
          Text(
            'Benefícios',
            style: theme.textTheme.titleMedium?.copyWith(
              fontWeight: FontWeight.w600,
              color: colorScheme.primary,
            ),
          ),
          const SizedBox(height: 10),
          ...plan.benefits.map(
            (benefit) => Padding(
              padding: const EdgeInsets.only(bottom: 8),
              child: Row(
                children: [
                  Icon(Icons.check_circle, color: colorScheme.secondary, size: 22),
                  const SizedBox(width: 10),
                  Expanded(child: Text(benefit)),
                ],
              ),
            ),
          ),
          const SizedBox(height: 20),
          FilledButton(
            onPressed: () {},
            style: FilledButton.styleFrom(
              minimumSize: const Size.fromHeight(48),
              backgroundColor:
                  plan.isFree ? colorScheme.secondary : colorScheme.primary,
              foregroundColor: Colors.white,
            ),
            child: Text(plan.isFree ? 'Solicitar aprovação' : 'Gerar cobrança Pix'),
          ),
        ],
      ),
    );
  }
}
