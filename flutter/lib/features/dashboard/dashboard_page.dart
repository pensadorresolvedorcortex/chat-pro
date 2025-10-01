import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/data/asset_loader.dart';
import '../../shared/widgets/section_title.dart';

class DashboardData {
  DashboardData({
    required this.user,
    required this.quickActions,
    required this.weeklyMetrics,
    required this.highlights,
  });

  final DashboardUser user;
  final List<DashboardQuickAction> quickActions;
  final List<DashboardMetric> weeklyMetrics;
  final List<DashboardHighlight> highlights;

  factory DashboardData.fromMap(Map<String, dynamic> map) {
    return DashboardData(
      user: DashboardUser.fromMap(map['usuario'] as Map<String, dynamic>),
      quickActions: (map['atalhosRapidos'] as List<dynamic>)
          .map((item) => DashboardQuickAction.fromMap(item as Map<String, dynamic>))
          .toList(),
      weeklyMetrics: (map['metricasSemana'] as List<dynamic>)
          .map((item) => DashboardMetric.fromMap(item as Map<String, dynamic>))
          .toList(),
      highlights: (map['destaquesAlunos'] as List<dynamic>)
          .map((item) => DashboardHighlight.fromMap(item as Map<String, dynamic>))
          .toList(),
    );
  }
}

class DashboardUser {
  const DashboardUser({
    required this.name,
    required this.greeting,
    required this.goal,
    required this.streakDays,
  });

  final String name;
  final String greeting;
  final String goal;
  final int streakDays;

  factory DashboardUser.fromMap(Map<String, dynamic> map) {
    return DashboardUser(
      name: map['nome'] as String,
      greeting: map['saudacao'] as String,
      goal: map['objetivo'] as String,
      streakDays: map['streakDias'] as int,
    );
  }
}

class DashboardQuickAction {
  const DashboardQuickAction({
    required this.icon,
    required this.title,
    required this.description,
    required this.route,
  });

  final String icon;
  final String title;
  final String description;
  final String route;

  factory DashboardQuickAction.fromMap(Map<String, dynamic> map) {
    return DashboardQuickAction(
      icon: map['icone'] as String,
      title: map['titulo'] as String,
      description: map['descricao'] as String,
      route: map['rota'] as String,
    );
  }
}

class DashboardMetric {
  const DashboardMetric({
    required this.label,
    required this.value,
    required this.comment,
  });

  final String label;
  final String value;
  final String comment;

  factory DashboardMetric.fromMap(Map<String, dynamic> map) {
    return DashboardMetric(
      label: map['rotulo'] as String,
      value: map['valor'] as String,
      comment: map['comentario'] as String,
    );
  }
}

class DashboardHighlight {
  const DashboardHighlight({
    required this.name,
    required this.goal,
    required this.summary,
    required this.trend,
    required this.badge,
  });

  final String name;
  final String goal;
  final String summary;
  final String trend;
  final String badge;

  factory DashboardHighlight.fromMap(Map<String, dynamic> map) {
    return DashboardHighlight(
      name: map['nome'] as String,
      goal: map['objetivo'] as String,
      summary: map['resumo'] as String,
      trend: map['tendencia'] as String,
      badge: map['badge'] as String,
    );
  }
}

final dashboardDataProvider = FutureProvider<DashboardData>((ref) async {
  final loader = AssetLoader(rootBundle);
  final map = await loader.loadJson('assets/data/dashboard_home.json');
  return DashboardData.fromMap(map);
});

class DashboardPage extends ConsumerWidget {
  const DashboardPage({super.key});

  static const routePath = '/dashboard';
  static const routeName = 'dashboard';

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final asyncDashboard = ref.watch(dashboardDataProvider);
    return asyncDashboard.when(
      loading: () => const Scaffold(
        body: Center(child: CircularProgressIndicator()),
      ),
      error: (error, stackTrace) => Scaffold(
        body: Center(
          child: Padding(
            padding: const EdgeInsets.all(24),
            child: Text('Não foi possível carregar o dashboard.\n$error'),
          ),
        ),
      ),
      data: (dashboard) {
        return Scaffold(
          appBar: AppBar(
            title: Text('${dashboard.user.greeting}, ${dashboard.user.name}!'),
            actions: [
              Padding(
                padding: const EdgeInsets.only(right: 16),
                child: Chip(
                  label: Text('🔥 ${dashboard.user.streakDays} dias'),
                ),
              ),
            ],
          ),
          body: ListView(
            padding: const EdgeInsets.all(16),
            children: [
              Text(
                'Objetivo atual',
                style: Theme.of(context).textTheme.titleMedium,
              ),
              const SizedBox(height: 4),
              Text(
                dashboard.user.goal,
                style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                      fontWeight: FontWeight.bold,
                    ),
              ),
              const SizedBox(height: 24),
              const SectionTitle('Atalhos rápidos'),
              const SizedBox(height: 12),
              Wrap(
                spacing: 12,
                runSpacing: 12,
                children: [
                  for (final action in dashboard.quickActions)
                    _QuickActionCard(action: action),
                ],
              ),
              const SizedBox(height: 24),
              const SectionTitle('Indicadores da semana'),
              const SizedBox(height: 12),
              Wrap(
                spacing: 12,
                runSpacing: 12,
                children: [
                  for (final metric in dashboard.weeklyMetrics)
                    _MetricCard(metric: metric),
                ],
              ),
              const SizedBox(height: 24),
              const SectionTitle('Destaques dos alunos'),
              const SizedBox(height: 12),
              ...dashboard.highlights.map(_HighlightCard.new),
            ],
          ),
        );
      },
    );
  }
}

class _QuickActionCard extends StatelessWidget {
  const _QuickActionCard({required this.action});

  final DashboardQuickAction action;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final icon = _iconForName(action.icon);
    return InkWell(
      borderRadius: BorderRadius.circular(16),
      onTap: () => context.go(action.route),
      child: Container(
        width: 160,
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(16),
          color: theme.colorScheme.primaryContainer,
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Icon(icon, color: theme.colorScheme.primary),
            const SizedBox(height: 12),
            Text(
              action.title,
              style: theme.textTheme.titleMedium?.copyWith(
                fontWeight: FontWeight.w600,
              ),
            ),
            const SizedBox(height: 8),
            Text(action.description),
          ],
        ),
      ),
    );
  }
}

class _MetricCard extends StatelessWidget {
  const _MetricCard({required this.metric});

  final DashboardMetric metric;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Container(
      width: 160,
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(16),
        color: theme.colorScheme.secondaryContainer,
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            metric.label,
            style: theme.textTheme.titleMedium,
          ),
          const SizedBox(height: 12),
          Text(
            metric.value,
            style: theme.textTheme.headlineSmall?.copyWith(
              fontWeight: FontWeight.bold,
            ),
          ),
          const SizedBox(height: 8),
          Text(metric.comment),
        ],
      ),
    );
  }
}

class _HighlightCard extends StatelessWidget {
  const _HighlightCard(this.highlight);

  final DashboardHighlight highlight;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(16),
        color: theme.colorScheme.surface,
        border: Border.all(color: theme.colorScheme.outlineVariant),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            highlight.name,
            style: theme.textTheme.titleMedium?.copyWith(
              fontWeight: FontWeight.w600,
            ),
          ),
          const SizedBox(height: 4),
          Text(highlight.goal),
          const SizedBox(height: 8),
          Text(highlight.summary),
          const SizedBox(height: 8),
          Text(
            highlight.trend,
            style: theme.textTheme.bodyMedium?.copyWith(
              color: theme.colorScheme.primary,
              fontWeight: FontWeight.bold,
            ),
          ),
          const SizedBox(height: 8),
          Chip(label: Text(highlight.badge)),
        ],
      ),
    );
  }
}

IconData _iconForName(String name) {
  switch (name) {
    case 'rocket_launch':
      return Icons.rocket_launch;
    case 'quiz':
      return Icons.quiz;
    case 'military_tech':
      return Icons.military_tech;
    case 'local_library':
      return Icons.local_library;
    case 'track_changes':
      return Icons.track_changes;
    default:
      return Icons.circle;
  }
}
