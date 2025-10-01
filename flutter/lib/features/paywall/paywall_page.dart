import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/data/asset_loader.dart';
import '../../shared/widgets/section_title.dart';

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
      loading: () => const Scaffold(
        body: Center(child: CircularProgressIndicator()),
      ),
      error: (error, stackTrace) => Scaffold(
        body: Center(
          child: Padding(
            padding: const EdgeInsets.all(24),
            child: Text('Não foi possível carregar os planos.\n$error'),
          ),
        ),
      ),
      data: (plans) {
        return Scaffold(
          appBar: AppBar(
            title: const Text('Assinaturas e Pix'),
          ),
          body: ListView(
            padding: const EdgeInsets.all(16),
            children: [
              const SectionTitle('Escolha o plano ideal'),
              const SizedBox(height: 12),
              Text(
                'Os planos estão sincronizados com o CMS e usam Pix com QR Code dinâmico.',
                style: Theme.of(context).textTheme.bodyMedium,
              ),
              const SizedBox(height: 24),
              ...plans.map((plan) => _PlanCard(plan: plan)),
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
    return Card(
      margin: const EdgeInsets.only(bottom: 16),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
      child: Padding(
        padding: const EdgeInsets.all(20),
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
                ),
              ],
            ),
            const SizedBox(height: 8),
            Text(plan.description),
            const SizedBox(height: 16),
            Text(
              plan.isFree
                  ? 'R\$ 0 / ${plan.periodicity}'
                  : 'R\$ ${plan.price.toStringAsFixed(2)} / ${plan.periodicity}',
              style: theme.textTheme.headlineSmall?.copyWith(
                fontWeight: FontWeight.bold,
                color: plan.isFree
                    ? theme.colorScheme.secondary
                    : theme.colorScheme.primary,
              ),
            ),
            const SizedBox(height: 16),
            Text(
              'Benefícios',
              style: theme.textTheme.titleMedium,
            ),
            const SizedBox(height: 8),
            ...plan.benefits.map(
              (benefit) => Padding(
                padding: const EdgeInsets.only(bottom: 8),
                child: Row(
                  children: [
                    Icon(Icons.check_circle,
                        color: theme.colorScheme.secondary, size: 20),
                    const SizedBox(width: 8),
                    Expanded(child: Text(benefit)),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 12),
            FilledButton(
              onPressed: () {},
              style: FilledButton.styleFrom(
                minimumSize: const Size.fromHeight(48),
              ),
              child: Text(plan.isFree ? 'Solicitar aprovação' : 'Gerar cobrança Pix'),
            ),
          ],
        ),
      ),
    );
  }
}
