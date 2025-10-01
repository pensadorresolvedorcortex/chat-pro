import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/data/asset_loader.dart';
import '../../shared/widgets/app_preloader.dart';
import '../../shared/widgets/app_scaffold.dart';
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
      loading: () => AppScaffold(
        body: const Center(child: AppPreloader(message: 'Carregando dashboard...')),
      ),
      error: (error, stackTrace) => AppScaffold(
        body: Center(
          child: Padding(
            padding: const EdgeInsets.all(24),
            child: Text('Não foi possível carregar o dashboard.\n$error'),
          ),
        ),
      ),
      data: (dashboard) {
        return AppScaffold(
          actions: [
            Padding(
              padding: const EdgeInsets.only(right: 16),
              child: Chip(
                label: Text('🔥 ${dashboard.user.streakDays} dias'),
                backgroundColor:
                    Theme.of(context).colorScheme.secondary.withOpacity(0.18),
              ),
            ),
          ],
          body: ListView(
            padding: EdgeInsets.zero,
            children: [
              _DashboardHero(user: dashboard.user),
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
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
                    const SizedBox(height: 32),
                  ],
                ),
              ),
            ],
          ),
        );
      },
    );
  }
}

class _DashboardHero extends StatelessWidget {
  const _DashboardHero({required this.user});

  final DashboardUser user;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Container(
      margin: const EdgeInsets.fromLTRB(16, 16, 16, 24),
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
            color: const Color(0xFF6645f6).withOpacity(0.24),
            blurRadius: 24,
            offset: const Offset(0, 16),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            '${user.greeting}, ${user.name}!',
            style: theme.textTheme.headlineSmall?.copyWith(
              color: Colors.white,
              fontWeight: FontWeight.w700,
            ),
          ),
          const SizedBox(height: 12),
          Text(
            user.goal,
            style: theme.textTheme.titleMedium?.copyWith(
              color: Colors.white.withOpacity(0.9),
              fontWeight: FontWeight.w500,
            ),
          ),
          const SizedBox(height: 20),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
            decoration: BoxDecoration(
              color: Colors.white.withOpacity(0.12),
              borderRadius: BorderRadius.circular(18),
              border: Border.all(color: Colors.white.withOpacity(0.2)),
            ),
            child: Row(
              children: [
                Icon(Icons.local_fire_department, color: Colors.white.withOpacity(0.9)),
                const SizedBox(width: 12),
                Expanded(
                  child: Text(
                    'Sequência de estudos ativa há ${user.streakDays} dias. Continue acelerando!',
                    style: theme.textTheme.bodyMedium?.copyWith(
                      color: Colors.white.withOpacity(0.85),
                      fontWeight: FontWeight.w500,
                    ),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
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
          borderRadius: BorderRadius.circular(20),
          gradient: LinearGradient(
            colors: [
              theme.colorScheme.primaryContainer,
              theme.colorScheme.primaryContainer.withOpacity(0.7),
            ],
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
          ),
          boxShadow: [
            BoxShadow(
              color: theme.colorScheme.primary.withOpacity(0.08),
              blurRadius: 16,
              offset: const Offset(0, 8),
            ),
          ],
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
        borderRadius: BorderRadius.circular(20),
        color: theme.colorScheme.secondaryContainer,
        boxShadow: [
          BoxShadow(
            color: theme.colorScheme.secondary.withOpacity(0.08),
            blurRadius: 16,
            offset: const Offset(0, 12),
          ),
        ],
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
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(20),
        color: theme.colorScheme.surface,
        border: Border.all(color: theme.colorScheme.outlineVariant),
        boxShadow: [
          BoxShadow(
            color: theme.colorScheme.shadow.withOpacity(0.04),
            blurRadius: 12,
            offset: const Offset(0, 6),
          ),
        ],
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
