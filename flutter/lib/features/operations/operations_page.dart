import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';

import '../../core/data/asset_loader.dart';

class OperationsSnapshot {
  OperationsSnapshot({
    required this.timestamp,
    required this.overallPercentage,
    required this.notes,
    required this.components,
  });

  final DateTime timestamp;
  final double overallPercentage;
  final List<String> notes;
  final List<OperationsComponent> components;

  factory OperationsSnapshot.fromMap(Map<String, dynamic> map) {
    final overall = map['overall'] as Map<String, dynamic>;
    return OperationsSnapshot(
      timestamp: DateTime.parse(map['timestamp'] as String),
      overallPercentage: (overall['percentage'] as num).toDouble(),
      notes: (overall['notes'] as List<dynamic>).map((e) => e as String).toList(),
      components: (map['components'] as List<dynamic>)
          .map((item) => OperationsComponent.fromMap(item as Map<String, dynamic>))
          .toList(),
    );
  }
}

class OperationsComponent {
  const OperationsComponent({
    required this.label,
    required this.percentage,
    required this.notes,
    required this.nextSteps,
  });

  final String label;
  final double percentage;
  final List<String> notes;
  final List<String> nextSteps;

  factory OperationsComponent.fromMap(Map<String, dynamic> map) {
    return OperationsComponent(
      label: map['label'] as String,
      percentage: (map['percentage'] as num).toDouble(),
      notes: (map['notes'] as List<dynamic>).map((e) => e as String).toList(),
      nextSteps:
          (map['nextSteps'] as List<dynamic>).map((e) => e as String).toList(),
    );
  }
}

final operationsSnapshotProvider =
    FutureProvider<OperationsSnapshot>((ref) async {
  final loader = AssetLoader(rootBundle);
  final map = await loader.loadJson('assets/data/operations_readiness.json');
  return OperationsSnapshot.fromMap(map);
});

class OperationsPage extends ConsumerWidget {
  const OperationsPage({super.key});

  static const routePath = '/operacoes/readiness';
  static const routeName = 'operations-readiness';

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final asyncSnapshot = ref.watch(operationsSnapshotProvider);
    return asyncSnapshot.when(
      loading: () => const Scaffold(
        body: Center(child: CircularProgressIndicator()),
      ),
      error: (error, stackTrace) => Scaffold(
        body: Center(
          child: Padding(
            padding: const EdgeInsets.all(24),
            child: Text('Não foi possível carregar o status operacional.\n$error'),
          ),
        ),
      ),
      data: (snapshot) => Scaffold(
        appBar: AppBar(
          title: const Text('Status operacional'),
          bottom: PreferredSize(
            preferredSize: const Size.fromHeight(48),
            child: Padding(
              padding: const EdgeInsets.only(bottom: 16),
              child: Text(
                "Atualizado em ${DateFormat('dd/MM/yyyy HH:mm').format(snapshot.timestamp.toLocal())}",
                style: Theme.of(context).textTheme.bodySmall,
              ),
            ),
          ),
        ),
        body: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            Card(
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(16),
              ),
              child: Padding(
                padding: const EdgeInsets.all(24),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Prontidão consolidada',
                      style: Theme.of(context).textTheme.titleLarge,
                    ),
                    const SizedBox(height: 8),
                    Text(
                      '${snapshot.overallPercentage.toStringAsFixed(0)} %',
                      style: Theme.of(context).textTheme.headlineMedium?.copyWith(
                            fontWeight: FontWeight.w700,
                          ),
                    ),
                    const SizedBox(height: 12),
                    ...snapshot.notes.map(
                      (note) => Padding(
                        padding: const EdgeInsets.only(bottom: 8),
                        child: Text(note),
                      ),
                    ),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 24),
            for (final component in snapshot.components)
              _ComponentCard(component: component),
          ],
        ),
      ),
    );
  }
}

class _ComponentCard extends StatelessWidget {
  const _ComponentCard({required this.component});

  final OperationsComponent component;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Card(
      margin: const EdgeInsets.only(bottom: 16),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      child: Padding(
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Expanded(
                  child: Text(
                    component.label,
                    style: theme.textTheme.titleMedium?.copyWith(
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                ),
                Text(
                  '${component.percentage.toStringAsFixed(0)} %',
                  style: theme.textTheme.titleMedium,
                ),
              ],
            ),
            const SizedBox(height: 12),
            Text(
              'Notas recentes',
              style: theme.textTheme.labelLarge,
            ),
            const SizedBox(height: 4),
            ...component.notes.map(
              (note) => Padding(
                padding: const EdgeInsets.only(bottom: 4),
                child: Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text('• '),
                    Expanded(child: Text(note)),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 12),
            Text(
              'Próximos passos',
              style: theme.textTheme.labelLarge,
            ),
            const SizedBox(height: 4),
            ...component.nextSteps.map(
              (step) => Padding(
                padding: const EdgeInsets.only(bottom: 4),
                child: Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text('→ '),
                    Expanded(child: Text(step)),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
