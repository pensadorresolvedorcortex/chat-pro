import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';

import '../../core/data/asset_loader.dart';
import '../../shared/widgets/app_preloader.dart';
import '../../shared/widgets/app_scaffold.dart';

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
      loading: () => AppScaffold(
        body: const Center(
          child: AppPreloader(message: 'Sincronizando status operacional...'),
        ),
      ),
      error: (error, stackTrace) => AppScaffold(
        body: Center(
          child: Padding(
            padding: const EdgeInsets.all(24),
            child: Text('Não foi possível carregar o status operacional.\n$error'),
          ),
        ),
      ),
      data: (snapshot) => AppScaffold(
        actions: [
          Padding(
            padding: const EdgeInsets.only(right: 16),
            child: Chip(
              label: Text(DateFormat('dd/MM • HH:mm').format(snapshot.timestamp.toLocal())),
              backgroundColor:
                  Theme.of(context).colorScheme.tertiary.withOpacity(0.18),
            ),
          ),
        ],
        body: ListView(
          padding: const EdgeInsets.fromLTRB(16, 24, 16, 32),
          children: [
            _OperationsOverview(snapshot: snapshot),
            const SizedBox(height: 24),
            for (final component in snapshot.components)
              _ComponentCard(component: component),
          ],
        ),
      ),
    );
  }
}

class _OperationsOverview extends StatelessWidget {
  const _OperationsOverview({required this.snapshot});

  final OperationsSnapshot snapshot;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final colorScheme = theme.colorScheme;
    return Container(
      padding: const EdgeInsets.all(24),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(28),
        gradient: const LinearGradient(
          colors: [Color(0xFF6645f6), Color(0xFF1dd3c4)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        boxShadow: [
          BoxShadow(
            color: const Color(0xFF6645f6).withOpacity(0.2),
            blurRadius: 24,
            offset: const Offset(0, 16),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            'Status operacional',
            style: theme.textTheme.headlineSmall?.copyWith(
              color: Colors.white,
              fontWeight: FontWeight.w700,
            ),
          ),
          const SizedBox(height: 12),
          Text(
            'Prontidão consolidada',
            style: theme.textTheme.titleMedium?.copyWith(
              color: Colors.white.withOpacity(0.85),
            ),
          ),
          const SizedBox(height: 8),
          Row(
            crossAxisAlignment: CrossAxisAlignment.end,
            children: [
              Text(
                snapshot.overallPercentage.toStringAsFixed(0),
                style: theme.textTheme.displaySmall?.copyWith(
                  color: Colors.white,
                  fontWeight: FontWeight.w700,
                  height: 1,
                ),
              ),
              const SizedBox(width: 4),
              Text(
                '%',
                style: theme.textTheme.titleLarge?.copyWith(
                  color: Colors.white.withOpacity(0.9),
                  fontWeight: FontWeight.w600,
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),
          ClipRRect(
            borderRadius: BorderRadius.circular(12),
            child: LinearProgressIndicator(
              value: snapshot.overallPercentage / 100,
              minHeight: 8,
              color: colorScheme.secondary,
              backgroundColor: Colors.white.withOpacity(0.18),
            ),
          ),
          const SizedBox(height: 16),
          ...snapshot.notes.map(
            (note) => Padding(
              padding: const EdgeInsets.only(bottom: 6),
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Icon(Icons.check_circle_outline, color: Colors.white, size: 18),
                  const SizedBox(width: 8),
                  Expanded(
                    child: Text(
                      note,
                      style: theme.textTheme.bodyMedium?.copyWith(
                        color: Colors.white.withOpacity(0.85),
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ),
        ],
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
    return Container(
      margin: const EdgeInsets.only(bottom: 20),
      padding: const EdgeInsets.all(22),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(24),
        color: theme.colorScheme.surface,
        border: Border.all(color: theme.colorScheme.outlineVariant),
        boxShadow: [
          BoxShadow(
            color: theme.colorScheme.shadow.withOpacity(0.04),
            blurRadius: 14,
            offset: const Offset(0, 8),
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
                  component.label,
                  style: theme.textTheme.titleMedium?.copyWith(
                    fontWeight: FontWeight.w600,
                  ),
                ),
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                decoration: BoxDecoration(
                  color: theme.colorScheme.secondary.withOpacity(0.12),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Text(
                  '${component.percentage.toStringAsFixed(0)}%',
                  style: theme.textTheme.labelLarge?.copyWith(
                    color: theme.colorScheme.secondary,
                    fontWeight: FontWeight.w700,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),
          Text(
            'Notas recentes',
            style: theme.textTheme.labelLarge?.copyWith(
              color: theme.colorScheme.primary,
              fontWeight: FontWeight.w600,
            ),
          ),
          const SizedBox(height: 8),
          ...component.notes.map(
            (note) => Padding(
              padding: const EdgeInsets.only(bottom: 6),
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text('• '),
                  Expanded(child: Text(note)),
                ],
              ),
            ),
          ),
          const SizedBox(height: 16),
          Text(
            'Próximos passos',
            style: theme.textTheme.labelLarge?.copyWith(
              color: theme.colorScheme.primary,
              fontWeight: FontWeight.w600,
            ),
          ),
          const SizedBox(height: 8),
          ...component.nextSteps.map(
            (step) => Padding(
              padding: const EdgeInsets.only(bottom: 6),
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
    );
  }
}
