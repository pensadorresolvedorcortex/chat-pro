import 'package:characters/characters.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';

import '../../../core/operations/operations_status.dart';
import '../../../shared/widgets/dashboard_card.dart';
import '../../paywall/presentation/paywall_page.dart';
import '../../operations/data/operations_readiness_providers.dart';
import '../../operations/presentation/operations_page.dart';
import '../data/dashboard_models.dart';
import '../data/dashboard_repository.dart';

class DashboardPage extends ConsumerWidget {
  const DashboardPage({super.key});

  static const routePath = '/dashboard';
  static const routeName = 'dashboard';

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final dashboardAsync = ref.watch(dashboardProvider);
    final theme = Theme.of(context);

    DashboardHomeData? data;
    DashboardFetchResult? result;
    Widget body;

    dashboardAsync.when(
      data: (value) {
        result = value;
        data = value.data;
        body = _DashboardContent(
          data: value,
          onRefresh: () => ref.refresh(dashboardProvider.future),
        );
      },
      loading: () {
        body = const Center(child: CircularProgressIndicator());
      },
      error: (error, stackTrace) {
        body = _DashboardError(
          error: error,
          onRetry: () => ref.refresh(dashboardProvider.future),
        );
      },
    );

    final greeting = data?.user.greeting.isNotEmpty == true
        ? data!.user.greeting
        : 'Olá';
    final parts = data?.user.name.split(' ') ?? const [];
    final firstName = parts.isNotEmpty ? parts.first : 'Academia';

    return Scaffold(
      appBar: AppBar(
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Academia da Comunicação',
              style: theme.textTheme.titleMedium?.copyWith(
                fontWeight: FontWeight.w600,
              ),
            ),
            Text(
              '$greeting, $firstName',
              style: theme.textTheme.bodySmall,
            ),
          ],
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.notifications_outlined),
            onPressed: () {},
          ),
          const SizedBox(width: 8),
        ],
      ),
      body: body,
      backgroundColor: theme.colorScheme.surface,
    );
  }
}

class _DashboardContent extends ConsumerWidget {
  const _DashboardContent({
    required this.data,
    required this.onRefresh,
  });

  final DashboardFetchResult data;
  final Future<DashboardFetchResult> Function() onRefresh;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final theme = Theme.of(context);
    final home = data.data;
    final readinessAsync = ref.watch(operationsReadinessControllerProvider);

    Future<void> refreshOperations() async {
      final messenger = ScaffoldMessenger.of(context);
      try {
        await ref
            .read(operationsReadinessControllerProvider.notifier)
            .refresh();
        messenger.hideCurrentSnackBar();
      } catch (error) {
        debugPrint('Falha ao atualizar prontidão: $error');
        messenger.showSnackBar(
          SnackBar(
            content: const Text(
              'Não foi possível atualizar a prontidão agora. Tente novamente em instantes.',
            ),
          ),
        );
      }
    }

    return RefreshIndicator(
      onRefresh: () async {
        await onRefresh();
      },
      child: ListView(
        padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 24),
        physics: const AlwaysScrollableScrollPhysics(),
        children: [
          if (data.isFallback)
            _FallbackBanner(reason: data.fallbackReason),
          _HeroHeader(profile: home.user),
          const SizedBox(height: 24),
          _SectionHeader(
            title: 'Quem está voando nesta semana',
            subtitle:
                'Destaques reais da comunidade com base em desempenho e Pix.',
          ),
          const SizedBox(height: 12),
          _LearnerSpotlightScroller(spotlights: home.learnerSpotlights),
          const SizedBox(height: 32),
          _SectionHeader(
            title: 'Atalhos rápidos',
            subtitle: 'Continue seus estudos com um toque.',
          ),
          const SizedBox(height: 12),
          _QuickActionsRow(actions: home.quickActions),
          const SizedBox(height: 32),
          readinessAsync.when(
            data: (snapshot) => _OperationsReadinessSection(
              snapshot: snapshot,
              onRefresh: refreshOperations,
            ),
            loading: () => const _OperationsReadinessLoading(),
            error: (error, stackTrace) => _OperationsReadinessError(
              onRetry: refreshOperations,
            ),
          ),
          const SizedBox(height: 32),
          if (home.weeklyMetrics.isNotEmpty) ...[
            _SectionHeader(
              title: 'Seu ritmo nesta semana',
              subtitle: 'Metas atualizadas automaticamente com base no Pix.',
            ),
            const SizedBox(height: 12),
            _MetricsRow(metrics: home.weeklyMetrics),
            const SizedBox(height: 32),
          ],
          if (home.modules.isNotEmpty)
            ...home.modules.expand((module) {
              return [
                _SectionHeader(
                  title: module.title,
                  subtitle: 'Atualizado em ${_formatLastSync(home.lastSync)}.',
                ),
                const SizedBox(height: 12),
                ...module.cards.map(
                  (card) => Padding(
                    padding: const EdgeInsets.only(bottom: 12),
                    child: _ModuleCard(data: card),
                  ),
                ),
                const SizedBox(height: 24),
              ];
            }),
          DashboardCard(
            title: 'Planos e assinaturas',
            description: _planStatusDescription(home),
            icon: Icons.workspace_premium_outlined,
            onTap: () => context.push(PaywallPage.routePath),
          ),
          const SizedBox(height: 16),
          _PlanHighlightsRow(highlights: home.planHighlights),
          const SizedBox(height: 32),
          if (home.upcomingLives.isNotEmpty) ...[
            _SectionHeader(
              title: 'Mentorias e lives ao vivo',
              subtitle: 'Garanta presença nos próximos encontros com especialistas.',
            ),
            const SizedBox(height: 12),
            ...home.upcomingLives.map(
              (live) => Padding(
                padding: const EdgeInsets.only(bottom: 12),
                child: _LiveTile(live: live),
              ),
            ),
            const SizedBox(height: 32),
          ],
          if (home.news.isNotEmpty) ...[
            _SectionHeader(
              title: 'Notícias e relatórios',
              subtitle: home.source ?? 'Última atualização automática.',
            ),
            const SizedBox(height: 12),
            ...home.news.map(
              (news) => Padding(
                padding: const EdgeInsets.only(bottom: 12),
                child: _NewsTile(news: news),
              ),
            ),
          ],
        ],
      ),
    );
  }

  static String _formatLastSync(DateTime? date) {
    if (date == null) {
      return 'tempo real';
    }
    final formatter = DateFormat('dd/MM • HH:mm');
    return formatter.format(date);
  }

  static String _planStatusDescription(DashboardHomeData data) {
    final subscription = data.user.currentSubscription;
    if (subscription == null) {
      return 'Nenhum plano ativo';
    }

    DashboardPlanHighlight? highlight;
    if (data.planHighlights.isNotEmpty) {
      highlight = data.planHighlights.firstWhere(
        (item) => item.planId == subscription.planId,
        orElse: () => data.planHighlights.first,
      );
    }

    final renewText = subscription.renewsAt != null
        ? 'Renova em ${DateFormat('dd/MM/yyyy').format(subscription.renewsAt!)}'
        : subscription.status.characters.isNotEmpty
            ? subscription.status
            : 'Status indisponível';

    if (highlight == null) {
      return renewText;
    }

    return '${highlight.title} • $renewText';
  }
}

class _FallbackBanner extends StatelessWidget {
  const _FallbackBanner({this.reason});

  final String? reason;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Container(
      margin: const EdgeInsets.only(bottom: 16),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: theme.colorScheme.secondaryContainer.withOpacity(0.25),
        borderRadius: BorderRadius.circular(16),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(Icons.cloud_off_outlined, color: theme.colorScheme.secondary),
          const SizedBox(width: 12),
          Expanded(
            child: Text(
              reason != null
                  ? 'Mostrando dados offline enquanto reconectamos ao dashboard. $reason'
                  : 'Mostrando dados offline enquanto reconectamos ao dashboard.',
              style: theme.textTheme.bodyMedium,
            ),
          ),
        ],
      ),
    );
  }
}

class _DashboardError extends StatelessWidget {
  const _DashboardError({
    required this.error,
    required this.onRetry,
  });

  final Object error;
  final Future<DashboardFetchResult> Function() onRetry;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(Icons.wifi_off_outlined, size: 48, color: theme.colorScheme.error),
            const SizedBox(height: 12),
            Text(
              'Não foi possível carregar o dashboard.',
              style: theme.textTheme.titleMedium,
            ),
            const SizedBox(height: 8),
            Text(
              error.toString(),
              style: theme.textTheme.bodySmall,
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 16),
            FilledButton(
              onPressed: () => onRetry(),
              child: const Text('Tentar novamente'),
            ),
          ],
        ),
      ),
    );
  }
}

class _HeroHeader extends StatelessWidget {
  const _HeroHeader({required this.profile});

  final DashboardUserProfile profile;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final initials = profile.name.characters
        .where((char) => char.trim().isNotEmpty)
        .take(2)
        .map((char) => char.characters.first)
        .join()
        .toUpperCase();

    return Container(
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: [
            theme.colorScheme.primary,
            theme.colorScheme.primaryContainer.withOpacity(0.85),
          ],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(28),
      ),
      padding: const EdgeInsets.all(24),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              CircleAvatar(
                radius: 32,
                backgroundColor: Colors.white.withOpacity(0.2),
                child: Text(
                  initials,
                  style: theme.textTheme.titleMedium?.copyWith(
                    color: Colors.white,
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ),
              const SizedBox(width: 16),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      profile.name,
                      style: theme.textTheme.titleLarge?.copyWith(
                        color: Colors.white,
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      profile.goal,
                      style: theme.textTheme.bodyMedium?.copyWith(
                        color: Colors.white.withOpacity(0.85),
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 24),
          Row(
            children: [
              _HeroChip(
                icon: Icons.local_fire_department,
                label: '${profile.streakDays} dias de foco',
              ),
              const SizedBox(width: 12),
              _HeroChip(
                icon: Icons.emoji_events_outlined,
                label: profile.badge,
              ),
            ],
          ),
        ],
      ),
    );
  }
}

class _HeroChip extends StatelessWidget {
  const _HeroChip({required this.icon, required this.label});

  final IconData icon;
  final String label;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
      decoration: BoxDecoration(
        color: Colors.white.withOpacity(0.18),
        borderRadius: BorderRadius.circular(16),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, color: Colors.white, size: 18),
          const SizedBox(width: 6),
          Text(
            label,
            style: theme.textTheme.bodySmall?.copyWith(
              color: Colors.white,
              fontWeight: FontWeight.w600,
            ),
          ),
        ],
      ),
    );
  }
}

class _SectionHeader extends StatelessWidget {
  const _SectionHeader({
    required this.title,
    required this.subtitle,
  });

  final String title;
  final String subtitle;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          title,
          style: theme.textTheme.titleLarge?.copyWith(
            fontWeight: FontWeight.w700,
          ),
        ),
        const SizedBox(height: 4),
        Text(
          subtitle,
          style: theme.textTheme.bodyMedium?.copyWith(
            color: theme.textTheme.bodyMedium?.color?.withOpacity(0.7),
          ),
        ),
      ],
    );
  }
}

class _LearnerSpotlightScroller extends StatelessWidget {
  const _LearnerSpotlightScroller({required this.spotlights});

  final List<DashboardLearnerSpotlight> spotlights;

  static final _palette = [
    const Color(0xFF6645F6),
    const Color(0xFF1DD3C4),
    const Color(0xFFE5BE49),
    const Color(0xFF0C3C64),
  ];

  @override
  Widget build(BuildContext context) {
    if (spotlights.isEmpty) {
      return _EmptyCard(
        icon: Icons.groups_outlined,
        message: 'Nenhum destaque disponível por enquanto.',
      );
    }

    return SizedBox(
      height: 140,
      child: ListView.separated(
        scrollDirection: Axis.horizontal,
        itemCount: spotlights.length,
        separatorBuilder: (_, __) => const SizedBox(width: 16),
        itemBuilder: (context, index) {
          final spotlight = spotlights[index];
          final color = _palette[index % _palette.length];
          return _LearnerCard(spotlight: spotlight, color: color);
        },
      ),
    );
  }
}

class _LearnerCard extends StatelessWidget {
  const _LearnerCard({required this.spotlight, required this.color});

  final DashboardLearnerSpotlight spotlight;
  final Color color;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Container(
      width: 220,
      decoration: BoxDecoration(
        color: color.withOpacity(0.1),
        borderRadius: BorderRadius.circular(24),
        border: Border.all(color: color.withOpacity(0.3)),
      ),
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            spotlight.name,
            style: theme.textTheme.titleMedium?.copyWith(
              fontWeight: FontWeight.w600,
            ),
          ),
          const SizedBox(height: 6),
          Text(
            spotlight.goal,
            style: theme.textTheme.bodySmall,
          ),
          const Spacer(),
          Text(
            spotlight.summary,
            style: theme.textTheme.bodySmall?.copyWith(
              fontWeight: FontWeight.w600,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            spotlight.trend,
            style: theme.textTheme.bodySmall?.copyWith(
              color: color,
            ),
          ),
          const SizedBox(height: 6),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
            decoration: BoxDecoration(
              color: color,
              borderRadius: BorderRadius.circular(12),
            ),
            child: Text(
              spotlight.badge,
              style: theme.textTheme.labelSmall?.copyWith(
                color: Colors.white,
                fontWeight: FontWeight.w600,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _QuickActionsRow extends StatelessWidget {
  const _QuickActionsRow({required this.actions});

  final List<DashboardQuickAction> actions;

  @override
  Widget build(BuildContext context) {
    if (actions.isEmpty) {
      return _EmptyCard(
        icon: Icons.lightbulb_outline,
        message: 'Configure atalhos no painel para aparecerem aqui.',
      );
    }

    return SizedBox(
      height: 144,
      child: ListView.separated(
        scrollDirection: Axis.horizontal,
        itemCount: actions.length,
        separatorBuilder: (_, __) => const SizedBox(width: 16),
        itemBuilder: (context, index) {
          final action = actions[index];
          return _QuickActionCard(action: action);
        },
      ),
    );
  }
}

class _QuickActionCard extends StatelessWidget {
  const _QuickActionCard({required this.action});

  final DashboardQuickAction action;

  static const _iconMap = {
    'rocket_launch': Icons.rocket_launch_outlined,
    'quiz': Icons.quiz_outlined,
    'military_tech': Icons.military_tech_outlined,
    'local_library': Icons.local_library_outlined,
    'assignment': Icons.assignment_outlined,
    'calendar_month': Icons.calendar_month_outlined,
  };

  IconData get _icon => _iconMap[action.icon] ?? Icons.bolt_outlined;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return InkWell(
      onTap: () => context.push(action.route),
      borderRadius: BorderRadius.circular(24),
      child: Ink(
        width: 220,
        padding: const EdgeInsets.all(18),
        decoration: BoxDecoration(
          color: theme.colorScheme.primaryContainer.withOpacity(0.2),
          borderRadius: BorderRadius.circular(24),
          border: Border.all(
            color: theme.colorScheme.primaryContainer.withOpacity(0.4),
          ),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Icon(_icon, color: theme.colorScheme.primary),
            const SizedBox(height: 12),
            Text(
              action.title,
              style: theme.textTheme.titleMedium?.copyWith(
                fontWeight: FontWeight.w600,
              ),
            ),
            const SizedBox(height: 6),
            Text(
              action.description,
              style: theme.textTheme.bodySmall,
            ),
          ],
        ),
      ),
    );
  }
}

class _MetricsRow extends StatelessWidget {
  const _MetricsRow({required this.metrics});

  final List<DashboardMetric> metrics;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Row(
      children: metrics.map((metric) {
        return Expanded(
          child: Container(
            margin: const EdgeInsets.only(right: 12),
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: theme.colorScheme.surfaceVariant.withOpacity(0.4),
              borderRadius: BorderRadius.circular(20),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  metric.label,
                  style: theme.textTheme.bodyMedium,
                ),
                const SizedBox(height: 8),
                Text(
                  metric.value,
                  style: theme.textTheme.titleLarge?.copyWith(
                    fontWeight: FontWeight.w700,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  metric.caption,
                  style: theme.textTheme.bodySmall?.copyWith(
                    color: theme.textTheme.bodySmall?.color?.withOpacity(0.65),
                  ),
                ),
              ],
            ),
          ),
        );
      }).toList(),
    );
  }
}

class _OperationsReadinessSection extends StatelessWidget {
  const _OperationsReadinessSection({
    required this.snapshot,
    required this.onRefresh,
  });

  final OperationsReadinessSnapshot snapshot;
  final Future<void> Function() onRefresh;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final components = [...snapshot.components]
      ..sort((a, b) => b.percentage.compareTo(a.percentage));

    return DashboardCard(
      title: 'Prontidão operacional',
      description:
          'Acompanhe Flutter iOS, Strapi e operações rumo ao 100 % de prontidão.',
      icon: Icons.track_changes_outlined,
      onTap: () {
        context.push(OperationsPage.routePath);
      },
      trailing: Text(
        '${snapshot.overall.percentage}%',
        style: theme.textTheme.titleMedium?.copyWith(
          fontWeight: FontWeight.w700,
          color: theme.colorScheme.primary,
        ),
      ),
      footer: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          ...components.map(
            (component) => Padding(
              padding: const EdgeInsets.only(bottom: 12),
              child: _OperationsComponentRow(component: component),
            ),
          ),
          if (snapshot.sources.isNotEmpty)
            Wrap(
              spacing: 8,
              runSpacing: 8,
              children: snapshot.sources
                  .map(
                    (source) => Container(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 12,
                        vertical: 6,
                      ),
                      decoration: BoxDecoration(
                        color: theme.colorScheme.secondary.withOpacity(0.12),
                        borderRadius: BorderRadius.circular(999),
                      ),
                      child: Text(
                        source.value,
                        style: theme.textTheme.labelSmall?.copyWith(
                          color: theme.colorScheme.secondary,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                    ),
                  )
                  .toList(),
            ),
          Align(
            alignment: Alignment.centerLeft,
            child: TextButton.icon(
              onPressed: () async {
                await onRefresh();
              },
              icon: const Icon(Icons.refresh),
              label: const Text('Atualizar status'),
            ),
          ),
        ],
      ),
    );
  }
}

class _OperationsComponentRow extends StatelessWidget {
  const _OperationsComponentRow({required this.component});

  final OperationsReadinessComponent component;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final label = component.label ?? component.key;
    final progress = component.percentage.clamp(0, 100) / 100;
    final nextStep = component.nextSteps.isNotEmpty ? component.nextSteps.first : null;
    final pendingCount = component.pending.length;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            Expanded(
              child: Text(
                label,
                style: theme.textTheme.bodyMedium?.copyWith(
                  fontWeight: FontWeight.w600,
                ),
              ),
            ),
            Text(
              '${component.percentage}%',
              style: theme.textTheme.bodyMedium?.copyWith(
                color: theme.colorScheme.primary,
                fontWeight: FontWeight.w600,
              ),
            ),
          ],
        ),
        const SizedBox(height: 6),
        ClipRRect(
          borderRadius: BorderRadius.circular(999),
          child: LinearProgressIndicator(
            value: progress,
            minHeight: 6,
            backgroundColor: theme.colorScheme.surfaceVariant.withOpacity(0.4),
            valueColor: AlwaysStoppedAnimation<Color>(theme.colorScheme.primary),
          ),
        ),
        if (nextStep != null)
          Padding(
            padding: const EdgeInsets.only(top: 6),
            child: Text(
              nextStep,
              style: theme.textTheme.bodySmall?.copyWith(
                color: theme.colorScheme.onSurfaceVariant,
              ),
            ),
          ),
        if (pendingCount > 0)
          Padding(
            padding: const EdgeInsets.only(top: 4),
            child: Text(
              '$pendingCount pendência(s) aberta(s)',
              style: theme.textTheme.bodySmall?.copyWith(
                color: theme.colorScheme.error,
                fontWeight: FontWeight.w500,
              ),
            ),
          ),
      ],
    );
  }
}

class _OperationsReadinessLoading extends StatelessWidget {
  const _OperationsReadinessLoading();

  @override
  Widget build(BuildContext context) {
    return DashboardCard(
      title: 'Prontidão operacional',
      description: 'Carregando o snapshot das frentes ativas…',
      icon: Icons.track_changes_outlined,
      trailing: const SizedBox(
        width: 20,
        height: 20,
        child: CircularProgressIndicator(strokeWidth: 2),
      ),
    );
  }
}

class _OperationsReadinessError extends StatelessWidget {
  const _OperationsReadinessError({required this.onRetry});

  final Future<void> Function() onRetry;

  @override
  Widget build(BuildContext context) {
    return DashboardCard(
      title: 'Prontidão operacional',
      description: 'Não foi possível atualizar o status agora.',
      icon: Icons.warning_amber_outlined,
      trailing: TextButton(
        onPressed: () async {
          await onRetry();
        },
        child: const Text('Tentar novamente'),
      ),
    );
  }
}

class _ModuleCard extends StatelessWidget {
  const _ModuleCard({required this.data});

  final DashboardModuleCard data;

  IconData get _icon {
    switch (data.type) {
      case 'caderno':
        return Icons.book_outlined;
      case 'simulado':
        return Icons.pending_actions_outlined;
      case 'curso':
        return Icons.play_circle_outline;
      case 'meta':
        return Icons.flag_outlined;
      default:
        return Icons.layers_outlined;
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return DashboardCard(
      title: data.title,
      description: data.description,
      icon: _icon,
      trailing: data.tag != null
          ? Container(
              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
              decoration: BoxDecoration(
                color: theme.colorScheme.secondaryContainer,
                borderRadius: BorderRadius.circular(12),
              ),
              child: Text(
                data.tag!,
                style: theme.textTheme.labelSmall,
              ),
            )
          : null,
      footer: data.progress != null
          ? Padding(
              padding: const EdgeInsets.only(top: 12),
              child: LinearProgressIndicator(
                value: data.progress!.clamp(0, 1),
                minHeight: 8,
                borderRadius: BorderRadius.circular(8),
              ),
            )
          : null,
      onTap: () {},
    );
  }
}

class _PlanHighlightsRow extends StatelessWidget {
  const _PlanHighlightsRow({required this.highlights});

  final List<DashboardPlanHighlight> highlights;

  @override
  Widget build(BuildContext context) {
    if (highlights.isEmpty) {
      return _EmptyCard(
        icon: Icons.workspace_premium_outlined,
        message: 'Nenhum plano Pix em destaque.',
      );
    }

    final formatter = NumberFormat.currency(locale: 'pt_BR', symbol: 'R\$');

    return SizedBox(
      height: 180,
      child: ListView.separated(
        scrollDirection: Axis.horizontal,
        itemCount: highlights.length,
        separatorBuilder: (_, __) => const SizedBox(width: 16),
        itemBuilder: (context, index) {
          final highlight = highlights[index];
          final price = highlight.price <= 0
              ? 'Gratuito'
              : formatter.format(highlight.price);
          return Container(
            width: 240,
            decoration: BoxDecoration(
              color: Theme.of(context)
                  .colorScheme
                  .primaryContainer
                  .withOpacity(0.18),
              borderRadius: BorderRadius.circular(24),
              border: Border.all(
                color: Theme.of(context)
                    .colorScheme
                    .primaryContainer
                    .withOpacity(0.35),
              ),
            ),
            padding: const EdgeInsets.all(18),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  highlight.tag,
                  style: Theme.of(context).textTheme.labelSmall?.copyWith(
                        fontWeight: FontWeight.w600,
                      ),
                ),
                const SizedBox(height: 8),
                Text(
                  highlight.title,
                  style: Theme.of(context).textTheme.titleMedium?.copyWith(
                        fontWeight: FontWeight.w700,
                      ),
                ),
                const Spacer(),
                Text(
                  price,
                  style: Theme.of(context).textTheme.titleLarge?.copyWith(
                        fontWeight: FontWeight.w700,
                      ),
                ),
                const SizedBox(height: 4),
                Text(
                  'Status: ${highlight.approvalStatus.toUpperCase()}',
                  style: Theme.of(context).textTheme.bodySmall,
                ),
              ],
            ),
          );
        },
      ),
    );
  }
}

class _LiveTile extends StatelessWidget {
  const _LiveTile({required this.live});

  final DashboardLiveHighlight live;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final formattedDate = live.dateTime != null
        ? DateFormat('dd/MM • HH:mm').format(live.dateTime!)
        : 'Horário a confirmar';

    return DashboardCard(
      title: live.title,
      description: '$formattedDate • ${live.instructor}',
      icon: Icons.live_tv_outlined,
      trailing: Text(
        '${live.durationMinutes} min',
        style: theme.textTheme.bodySmall,
      ),
      onTap: () {},
    );
  }
}

class _NewsTile extends StatelessWidget {
  const _NewsTile({required this.news});

  final DashboardNewsItem news;

  @override
  Widget build(BuildContext context) {
    return DashboardCard(
      title: news.title,
      description: news.summary,
      icon: Icons.article_outlined,
      onTap: () {},
    );
  }
}

class _EmptyCard extends StatelessWidget {
  const _EmptyCard({required this.icon, required this.message});

  final IconData icon;
  final String message;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Container(
      padding: const EdgeInsets.all(24),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(24),
        color: theme.colorScheme.surfaceVariant.withOpacity(0.35),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(icon, color: theme.colorScheme.onSurfaceVariant),
          const SizedBox(height: 12),
          Text(
            message,
            style: theme.textTheme.bodyMedium,
          ),
        ],
      ),
    );
  }
}
