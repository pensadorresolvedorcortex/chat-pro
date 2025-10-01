import 'dart:async';

import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';

import '../../../core/operations/operations_status.dart';
import '../data/operations_readiness_providers.dart';

class OperationsPage extends ConsumerWidget {
  const OperationsPage({super.key});

  static const routePath = '/operacoes/readiness';
  static const routeName = 'operations-readiness';

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final readinessAsync = ref.watch(operationsReadinessControllerProvider);

    Future<void> triggerRefresh({bool preferNative = true}) async {
      final messenger = ScaffoldMessenger.of(context);
      try {
        await ref
            .read(operationsReadinessControllerProvider.notifier)
            .refresh(preferNativeChannel: preferNative);
        messenger.hideCurrentSnackBar();
      } catch (error) {
        debugPrint('Falha ao atualizar prontidão operacional: $error');
        messenger.showSnackBar(
          const SnackBar(
            content: Text(
              'Não foi possível atualizar a prontidão agora. Tente novamente em instantes.',
            ),
          ),
        );
      }
    }

    return Scaffold(
      appBar: AppBar(
        title: const Text('Prontidão operacional'),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            tooltip: 'Atualizar status',
            onPressed: () {
              unawaited(triggerRefresh());
            },
          ),
          PopupMenuButton<_OperationsMenuAction>(
            onSelected: (action) {
              switch (action) {
                case _OperationsMenuAction.forceApi:
                  unawaited(triggerRefresh(preferNative: false));
                  break;
              }
            },
            itemBuilder: (context) => const [
              PopupMenuItem(
                value: _OperationsMenuAction.forceApi,
                child: Text('Forçar atualização pela API'),
              ),
            ],
          ),
        ],
      ),
      body: readinessAsync.when(
        data: (snapshot) => _OperationsContent(
          snapshot: snapshot,
          onRefresh: triggerRefresh,
        ),
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (error, stackTrace) => _OperationsErrorView(
          onRetry: triggerRefresh,
        ),
      ),
    );
  }
}

enum _OperationsMenuAction { forceApi }

class _OperationsContent extends StatelessWidget {
  const _OperationsContent({
    required this.snapshot,
    required this.onRefresh,
  });

  final OperationsReadinessSnapshot snapshot;
  final Future<void> Function({bool preferNative}) onRefresh;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final components = [...snapshot.components]
      ..sort((a, b) => a.percentage.compareTo(b.percentage));
    final hasAlerts = snapshot.alerts.isNotEmpty;
    final hasMilestones = snapshot.milestones.isNotEmpty;
    final hasIncidents = snapshot.incidents.isNotEmpty;
    final hasMaintenance = snapshot.maintenanceWindows.isNotEmpty;
    final hasOnCall = snapshot.onCall.isNotEmpty;
    final hasAutomations = snapshot.automations.isNotEmpty;
    final hasSlos = snapshot.slos.isNotEmpty;
    final hasSloBreaches = snapshot.sloBreaches.isNotEmpty;

    final children = <Widget>[
      _OperationsOverviewCard(snapshot: snapshot),
      const SizedBox(height: 16),
      if (hasAlerts) ...[
        _OperationsAlertsCard(alerts: snapshot.alerts),
        const SizedBox(height: 16),
      ],
      if (hasMilestones) ...[
        _OperationsMilestonesCard(milestones: snapshot.milestones),
        const SizedBox(height: 16),
      ],
      if (hasIncidents) ...[
        _OperationsIncidentsCard(incidents: snapshot.incidents),
        const SizedBox(height: 16),
      ],
      if (hasMaintenance) ...[
        _OperationsMaintenanceCard(windows: snapshot.maintenanceWindows),
        const SizedBox(height: 16),
      ],
      if (hasOnCall) ...[
        _OperationsOnCallCard(entries: snapshot.onCall),
        const SizedBox(height: 16),
      ],
      if (hasAutomations) ...[
        _OperationsAutomationsCard(automations: snapshot.automations),
        const SizedBox(height: 16),
      ],
      if (hasSlos) ...[
        _OperationsSloCard(slos: snapshot.slos),
        const SizedBox(height: 16),
      ],
      if (hasSloBreaches) ...[
        _OperationsSloBreachesCard(breaches: snapshot.sloBreaches),
        const SizedBox(height: 16),
      ],
      if (snapshot.counts.isNotEmpty) ...[
        _OperationsCountsCard(counts: snapshot.counts),
        const SizedBox(height: 16),
      ],
      for (final component in components) ...[
        _OperationsComponentCard(component: component),
        const SizedBox(height: 16),
      ],
      if (snapshot.sources.isNotEmpty)
        _OperationsSourcesCard(sources: snapshot.sources),
      if (snapshot.sources.isNotEmpty) const SizedBox(height: 16),
      Padding(
        padding: const EdgeInsets.only(bottom: 40),
        child: Text(
          'Última atualização em ${_formatDate(snapshot.timestamp)}',
          style: theme.textTheme.bodySmall?.copyWith(
            color: theme.colorScheme.onSurfaceVariant,
          ),
        ),
      ),
    ];

    return RefreshIndicator(
      onRefresh: () => onRefresh(preferNative: true),
      child: CustomScrollView(
        physics: const AlwaysScrollableScrollPhysics(),
        slivers: [
          SliverPadding(
            padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 24),
            sliver: SliverList(
              delegate: SliverChildListDelegate(children),
            ),
          ),
        ],
      ),
    );
  }
}

class _OperationsOverviewCard extends StatelessWidget {
  const _OperationsOverviewCard({required this.snapshot});

  final OperationsReadinessSnapshot snapshot;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final percentage = snapshot.overall.percentage.clamp(0, 100);
    final progress = percentage / 100;
    final baseline = snapshot.overall.baseline;
    final computed = snapshot.overall.computed;
    final pendingCount = snapshot.pendingCount;
    final totalMilestones = snapshot.milestones.length;
    final openMilestones = snapshot.openMilestones;
    final alertCount = snapshot.alerts.length;
    final incidentCount = snapshot.activeIncidentCount;
    final maintenanceCount = snapshot.activeMaintenanceCount;

    return Card(
      elevation: 0,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
      child: Padding(
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'Prontidão consolidada',
                        style: theme.textTheme.titleMedium?.copyWith(
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                      const SizedBox(height: 12),
                      Text(
                        '$percentage%',
                        style: theme.textTheme.displaySmall?.copyWith(
                          color: theme.colorScheme.primary,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                    ],
                  ),
                ),
                Column(
                  crossAxisAlignment: CrossAxisAlignment.end,
                  children: [
                    _OverviewInfoRow(
                      label: 'Baseline',
                      value: '$baseline%',
                    ),
                    const SizedBox(height: 4),
                    _OverviewInfoRow(
                      label: 'Cálculo atual',
                      value: '$computed%',
                    ),
                    const SizedBox(height: 4),
                    _OverviewInfoRow(
                      label: 'Snapshot inicial',
                      value: snapshot.baselineTimestamp != null
                          ? _formatDate(snapshot.baselineTimestamp!)
                          : '—',
                    ),
                  ],
                ),
              ],
            ),
            const SizedBox(height: 16),
            ClipRRect(
              borderRadius: BorderRadius.circular(999),
              child: LinearProgressIndicator(
                value: progress,
                minHeight: 10,
                backgroundColor: theme.colorScheme.surfaceVariant.withOpacity(0.4),
                valueColor: AlwaysStoppedAnimation<Color>(theme.colorScheme.primary),
              ),
            ),
            const SizedBox(height: 16),
            Wrap(
              spacing: 8,
              runSpacing: 8,
              children: [
                _OperationsInfoChip(
                  label: 'Baseline ponderado',
                  value: '$baseline%',
                  color: theme.colorScheme.secondaryContainer,
                  textColor: theme.colorScheme.onSecondaryContainer,
                ),
                _OperationsInfoChip(
                  label: 'Cálculo atual',
                  value: '$computed%',
                  color: theme.colorScheme.primaryContainer,
                  textColor: theme.colorScheme.onPrimaryContainer,
                ),
                if (pendingCount > 0)
                  _OperationsInfoChip(
                    label: 'Pendências',
                    value: pendingCount.toString(),
                    color: theme.colorScheme.errorContainer,
                    textColor: theme.colorScheme.onErrorContainer,
                  ),
                if (totalMilestones > 0)
                  _OperationsInfoChip(
                    label: 'Milestones',
                    value: '${totalMilestones - openMilestones}/$totalMilestones',
                    color: theme.colorScheme.tertiaryContainer,
                    textColor: theme.colorScheme.onTertiaryContainer,
                  ),
                if (alertCount > 0)
                  _OperationsInfoChip(
                    label: alertCount == 1 ? 'Alerta ativo' : 'Alertas ativos',
                    value: alertCount.toString(),
                    color: theme.colorScheme.errorContainer.withOpacity(0.85),
                    textColor: theme.colorScheme.onErrorContainer,
                  ),
                if (incidentCount > 0)
                  _OperationsInfoChip(
                    label: incidentCount == 1 ? 'Incidente ativo' : 'Incidentes ativos',
                    value: incidentCount.toString(),
                    color: theme.colorScheme.surfaceTint.withOpacity(0.15),
                    textColor: theme.colorScheme.onSurface,
                  ),
                if (maintenanceCount > 0)
                  _OperationsInfoChip(
                    label: maintenanceCount == 1
                        ? 'Janela em andamento'
                        : 'Janelas em andamento',
                    value: maintenanceCount.toString(),
                    color: theme.colorScheme.secondaryContainer.withOpacity(0.6),
                    textColor: theme.colorScheme.onSecondaryContainer,
                  ),
              ],
            ),
            if (snapshot.overall.notes.isNotEmpty) ...[
              const SizedBox(height: 16),
              Text(
                'Notas em destaque',
                style: theme.textTheme.titleSmall?.copyWith(
                  fontWeight: FontWeight.w600,
                ),
              ),
              const SizedBox(height: 8),
              ...snapshot.overall.notes.map(
                (note) => Padding(
                  padding: const EdgeInsets.only(bottom: 4),
                  child: Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Container(
                        width: 6,
                        height: 6,
                        margin: const EdgeInsets.only(top: 8, right: 8),
                        decoration: BoxDecoration(
                          color: theme.colorScheme.primary,
                          shape: BoxShape.circle,
                        ),
                      ),
                      Expanded(
                        child: Text(
                          note,
                          style: theme.textTheme.bodyMedium,
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }
}

class _OperationsAlertsCard extends StatelessWidget {
  const _OperationsAlertsCard({required this.alerts});

  final List<OperationsReadinessAlert> alerts;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final ordered = [...alerts]
      ..sort((a, b) => _alertOrder(b.level).compareTo(_alertOrder(a.level)));

    return Card(
      elevation: 0,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
      child: Padding(
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              ordered.length == 1
                  ? '1 alerta operacional ativo'
                  : '${ordered.length} alertas operacionais ativos',
              style: theme.textTheme.titleMedium?.copyWith(
                fontWeight: FontWeight.w700,
                color: theme.colorScheme.error,
              ),
            ),
            const SizedBox(height: 12),
            ...ordered.map(
              (alert) => Padding(
                padding: const EdgeInsets.only(bottom: 12),
                child: _AlertTile(alert: alert),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _OperationsIncidentsCard extends StatelessWidget {
  const _OperationsIncidentsCard({required this.incidents});

  final List<OperationsIncident> incidents;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final ordered = [...incidents]
      ..sort((a, b) {
        final activeComparison = (b.isActive ? 1 : 0) - (a.isActive ? 1 : 0);
        if (activeComparison != 0) {
          return activeComparison;
        }
        final bDate = b.startedAt ?? b.updatedAt ?? DateTime.fromMillisecondsSinceEpoch(0);
        final aDate = a.startedAt ?? a.updatedAt ?? DateTime.fromMillisecondsSinceEpoch(0);
        return bDate.compareTo(aDate);
      });

    return Card(
      elevation: 0,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
      child: Padding(
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Incidentes operacionais',
              style: theme.textTheme.titleMedium?.copyWith(
                fontWeight: FontWeight.w700,
              ),
            ),
            const SizedBox(height: 12),
            ...List.generate(ordered.length, (index) {
              final incident = ordered[index];
              final isLast = index == ordered.length - 1;
              return Column(
                children: [
                  _IncidentTile(incident: incident),
                  if (!isLast) const Divider(height: 24),
                ],
              );
            }),
          ],
        ),
      ),
    );
  }
}

class _IncidentTile extends StatelessWidget {
  const _IncidentTile({required this.incident});

  final OperationsIncident incident;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final statusColor = _incidentStatusColor(incident, theme);
    final statusLabel = _incidentStatusLabel(incident);
    final statusIcon = _incidentStatusIcon(incident);
    final impactColor = _incidentImpactColor(incident.impact, theme);
    final impactLabel = _incidentImpactLabel(incident.impact);
    final timeline = _incidentTimeline(incident);
    final durationLabel = incident.durationMinutes != null && incident.durationMinutes! > 0
        ? '${incident.durationMinutes} min'
        : null;
    final componentLabel = incident.component != null && incident.component!.isNotEmpty
        ? 'Frente: ${_describeComponent(incident.component!)}'
        : null;

    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Container(
          width: 40,
          height: 40,
          decoration: BoxDecoration(
            color: statusColor.withOpacity(0.12),
            borderRadius: BorderRadius.circular(12),
          ),
          child: Icon(statusIcon, color: statusColor),
        ),
        const SizedBox(width: 12),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Expanded(
                    child: Text(
                      incident.title,
                      style: theme.textTheme.titleSmall?.copyWith(
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                  ),
                  const SizedBox(width: 8),
                  _StatusBadge(label: statusLabel, color: statusColor),
                ],
              ),
              const SizedBox(height: 8),
              Wrap(
                spacing: 8,
                runSpacing: 8,
                children: [
                  _StatusBadge(label: impactLabel, color: impactColor),
                  if (durationLabel != null)
                    _StatusBadge(
                      label: durationLabel,
                      color: theme.colorScheme.primary,
                    ),
                  if (componentLabel != null)
                    _StatusBadge(
                      label: componentLabel,
                      color: theme.colorScheme.surfaceVariant,
                      textColor: theme.colorScheme.onSurfaceVariant,
                    ),
                ],
              ),
              if (timeline != null) ...[
                const SizedBox(height: 8),
                Text(
                  timeline,
                  style: theme.textTheme.bodySmall?.copyWith(
                    color: theme.colorScheme.onSurfaceVariant,
                  ),
                ),
              ],
              if (incident.summary != null && incident.summary!.isNotEmpty) ...[
                const SizedBox(height: 8),
                Text(
                  incident.summary!,
                  style: theme.textTheme.bodyMedium,
                ),
              ],
              if (incident.actions.isNotEmpty) ...[
                const SizedBox(height: 8),
                ...incident.actions.map(
                  (action) => Padding(
                    padding: const EdgeInsets.only(bottom: 4),
                    child: Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Icon(
                          Icons.check_circle,
                          size: 16,
                          color: theme.colorScheme.primary,
                        ),
                        const SizedBox(width: 6),
                        Expanded(
                          child: Text(
                            action,
                            style: theme.textTheme.bodySmall,
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              ],
            ],
          ),
        ),
      ],
    );
  }
}

class _OperationsMaintenanceCard extends StatelessWidget {
  const _OperationsMaintenanceCard({required this.windows});

  final List<OperationsMaintenanceWindow> windows;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final ordered = [...windows]
      ..sort((a, b) {
        final activeComparison = (b.isActive ? 1 : 0) - (a.isActive ? 1 : 0);
        if (activeComparison != 0) {
          return activeComparison;
        }
        final upcomingComparison = (b.isUpcoming ? 1 : 0) - (a.isUpcoming ? 1 : 0);
        if (upcomingComparison != 0) {
          return upcomingComparison;
        }
        final aStart = a.windowStart ?? DateTime.fromMillisecondsSinceEpoch(0);
        final bStart = b.windowStart ?? DateTime.fromMillisecondsSinceEpoch(0);
        return aStart.compareTo(bStart);
      });

    return Card(
      elevation: 0,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
      child: Padding(
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Janelas de manutenção e rollout',
              style: theme.textTheme.titleMedium?.copyWith(
                fontWeight: FontWeight.w700,
              ),
            ),
            const SizedBox(height: 12),
            ...List.generate(ordered.length, (index) {
              final window = ordered[index];
              final isLast = index == ordered.length - 1;
              return Column(
                children: [
                  _MaintenanceTile(window: window),
                  if (!isLast) const Divider(height: 24),
                ],
              );
            }),
          ],
        ),
      ),
    );
  }
}

class _MaintenanceTile extends StatelessWidget {
  const _MaintenanceTile({required this.window});

  final OperationsMaintenanceWindow window;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final statusColor = _maintenanceStatusColor(window, theme);
    final statusLabel = _maintenanceStatusLabel(window);
    final statusIcon = _maintenanceStatusIcon(window);
    final impactColor = _maintenanceImpactColor(window.impact, theme);
    final impactLabel = _maintenanceImpactLabel(window.impact);
    final range = _maintenanceRange(window);
    final durationLabel = window.durationMinutes != null && window.durationMinutes! > 0
        ? '${window.durationMinutes} min'
        : null;

    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Container(
          width: 40,
          height: 40,
          decoration: BoxDecoration(
            color: statusColor.withOpacity(0.12),
            borderRadius: BorderRadius.circular(12),
          ),
          child: Icon(statusIcon, color: statusColor),
        ),
        const SizedBox(width: 12),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Expanded(
                    child: Text(
                      window.title,
                      style: theme.textTheme.titleSmall?.copyWith(
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                  ),
                  const SizedBox(width: 8),
                  _StatusBadge(label: statusLabel, color: statusColor),
                ],
              ),
              const SizedBox(height: 8),
              Wrap(
                spacing: 8,
                runSpacing: 8,
                children: [
                  _StatusBadge(label: impactLabel, color: impactColor),
                  if (durationLabel != null)
                    _StatusBadge(
                      label: durationLabel,
                      color: theme.colorScheme.primary,
                    ),
                  if (window.isUpcoming)
                    _StatusBadge(
                      label: 'Agendado',
                      color: theme.colorScheme.secondary,
                    ),
                ],
              ),
              if (range != null) ...[
                const SizedBox(height: 8),
                Text(
                  range,
                  style: theme.textTheme.bodySmall?.copyWith(
                    color: theme.colorScheme.onSurfaceVariant,
                  ),
                ),
              ],
              if (window.description != null && window.description!.isNotEmpty) ...[
                const SizedBox(height: 8),
                Text(
                  window.description!,
                  style: theme.textTheme.bodyMedium,
                ),
              ],
              if (window.notes != null && window.notes!.isNotEmpty) ...[
                const SizedBox(height: 8),
                Text(
                  window.notes!,
                  style: theme.textTheme.bodySmall?.copyWith(
                    color: theme.colorScheme.onSurfaceVariant,
                  ),
                ),
              ],
              if (window.systems.isNotEmpty) ...[
                const SizedBox(height: 8),
                Wrap(
                  spacing: 8,
                  runSpacing: 8,
                  children: window.systems
                      .map(
                        (system) => Chip(
                          label: Text(system),
                          visualDensity: VisualDensity.compact,
                        ),
                      )
                      .toList(),
                ),
              ],
            ],
          ),
        ),
      ],
    );
  }
}

class _OperationsOnCallCard extends StatelessWidget {
  const _OperationsOnCallCard({required this.entries});

  final List<OperationsOnCall> entries;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final ordered = [...entries]
      ..sort((a, b) {
        final primaryComparison = (b.primary ? 1 : 0) - (a.primary ? 1 : 0);
        if (primaryComparison != 0) {
          return primaryComparison;
        }
        final statusComparison = _onCallStatusOrder(a.status) - _onCallStatusOrder(b.status);
        if (statusComparison != 0) {
          return statusComparison;
        }
        final aStart = a.startedAt ?? DateTime.fromMillisecondsSinceEpoch(0);
        final bStart = b.startedAt ?? DateTime.fromMillisecondsSinceEpoch(0);
        return aStart.compareTo(bStart);
      });

    return Card(
      elevation: 0,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
      child: Padding(
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Escala de plantão Pix',
              style: theme.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w700),
            ),
            const SizedBox(height: 12),
            ...List.generate(ordered.length, (index) {
              final entry = ordered[index];
              final isLast = index == ordered.length - 1;
              return Column(
                children: [
                  _OnCallTile(entry: entry),
                  if (!isLast) const Divider(height: 24),
                ],
              );
            }),
          ],
        ),
      ),
    );
  }
}

class _OnCallTile extends StatelessWidget {
  const _OnCallTile({required this.entry});

  final OperationsOnCall entry;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final statusColor = _onCallStatusColor(entry.status, theme);
    final statusLabel = _onCallStatusLabel(entry.status);
    final shiftLabel = _onCallShiftLabel(entry);

    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Container(
          width: 40,
          height: 40,
          decoration: BoxDecoration(
            color: statusColor.withOpacity(0.12),
            borderRadius: BorderRadius.circular(12),
          ),
          child: Icon(_onCallStatusIcon(entry.status), color: statusColor),
        ),
        const SizedBox(width: 12),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Expanded(
                    child: Text(
                      entry.name,
                      style: theme.textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w700),
                    ),
                  ),
                  const SizedBox(width: 8),
                  _StatusBadge(label: statusLabel, color: statusColor),
                ],
              ),
              const SizedBox(height: 4),
              Text(
                entry.role,
                style: theme.textTheme.bodyMedium,
              ),
              if (shiftLabel != null) ...[
                const SizedBox(height: 8),
                Text(
                  shiftLabel,
                  style: theme.textTheme.bodySmall?.copyWith(
                    color: theme.colorScheme.onSurfaceVariant,
                  ),
                ),
              ],
              if (entry.contact.isNotEmpty) ...[
                const SizedBox(height: 8),
                Row(
                  children: [
                    Icon(Icons.call, size: 16, color: theme.colorScheme.primary),
                    const SizedBox(width: 6),
                    Expanded(
                      child: Text(
                        entry.contact,
                        style: theme.textTheme.bodySmall,
                      ),
                    ),
                  ],
                ),
              ],
              if (entry.escalationPolicy != null && entry.escalationPolicy!.isNotEmpty) ...[
                const SizedBox(height: 8),
                Text(
                  entry.escalationPolicy!,
                  style: theme.textTheme.bodySmall?.copyWith(
                    color: theme.colorScheme.onSurfaceVariant,
                  ),
                ),
              ],
              if (entry.primary)
                Padding(
                  padding: const EdgeInsets.only(top: 8),
                  child: _StatusBadge(
                    label: 'Primário',
                    color: theme.colorScheme.secondary,
                  ),
                ),
            ],
          ),
        ),
      ],
    );
  }
}

class _OperationsAutomationsCard extends StatelessWidget {
  const _OperationsAutomationsCard({required this.automations});

  final List<OperationsAutomation> automations;

  static final DateFormat _dateFormatter = DateFormat('dd/MM HH:mm');
  static final NumberFormat _percentFormatter = NumberFormat.percentPattern('pt_BR');

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final ordered = [...automations]
      ..sort((a, b) {
        final statusComparison =
            _automationStatusOrder(a.status) - _automationStatusOrder(b.status);
        if (statusComparison != 0) {
          return statusComparison;
        }
        final aLast = a.lastRunAt ?? DateTime.fromMillisecondsSinceEpoch(0);
        final bLast = b.lastRunAt ?? DateTime.fromMillisecondsSinceEpoch(0);
        return bLast.compareTo(aLast);
      });

    return Card(
      elevation: 0,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
      child: Padding(
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Automação operacional Pix',
              style: theme.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w700),
            ),
            const SizedBox(height: 8),
            Text(
              'Bots, workers e rotinas que mantêm o Pix saudável e acionam alertas preventivos.',
              style: theme.textTheme.bodyMedium?.copyWith(
                color: theme.colorScheme.onSurfaceVariant,
              ),
            ),
            const SizedBox(height: 16),
            ...List.generate(ordered.length, (index) {
              final automation = ordered[index];
              final isLast = index == ordered.length - 1;
              return Column(
                children: [
                  _AutomationTile(
                    automation: automation,
                    dateFormatter: _dateFormatter,
                    percentFormatter: _percentFormatter,
                  ),
                  if (!isLast) const Divider(height: 24),
                ],
              );
            }),
          ],
        ),
      ),
    );
  }
}

class _AutomationTile extends StatelessWidget {
  const _AutomationTile({
    required this.automation,
    required this.dateFormatter,
    required this.percentFormatter,
  });

  final OperationsAutomation automation;
  final DateFormat dateFormatter;
  final NumberFormat percentFormatter;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final color = _automationStatusColor(automation.status, theme);
    final statusLabel = _automationStatusLabel(automation.status);

    final lastRun = automation.lastRunAt != null
        ? dateFormatter.format(automation.lastRunAt!.toLocal())
        : null;
    final nextRun = automation.nextRunAt != null
        ? dateFormatter.format(automation.nextRunAt!.toLocal())
        : null;
    final successRate = automation.successRate != null
        ? percentFormatter.format(automation.successRate)
        : null;
    final coverage = automation.coverage != null
        ? '${_numberFormatter.format(automation.coverage)}%'
        : null;

    final metrics = <Widget>[];
    if (lastRun != null) {
      metrics.add(_OperationsInfoChip(
        label: 'Última execução',
        value: lastRun,
        color: color.withOpacity(0.08),
        textColor: color,
      ));
    }
    if (nextRun != null) {
      metrics.add(_OperationsInfoChip(
        label: 'Próxima execução',
        value: nextRun,
        color: color.withOpacity(0.08),
        textColor: color,
      ));
    }
    if (successRate != null) {
      metrics.add(_OperationsInfoChip(
        label: 'Taxa de sucesso',
        value: successRate,
        color: theme.colorScheme.secondaryContainer,
        textColor: theme.colorScheme.onSecondaryContainer,
      ));
    }
    if (coverage != null) {
      metrics.add(_OperationsInfoChip(
        label: 'Cobertura',
        value: coverage,
        color: theme.colorScheme.tertiaryContainer,
        textColor: theme.colorScheme.onTertiaryContainer,
      ));
    }

    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Container(
          width: 44,
          height: 44,
          decoration: BoxDecoration(
            color: color.withOpacity(0.12),
            borderRadius: BorderRadius.circular(14),
          ),
          child: Icon(_automationStatusIcon(automation.status), color: color),
        ),
        const SizedBox(width: 12),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Expanded(
                    child: Text(
                      automation.title,
                      style: theme.textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w700),
                    ),
                  ),
                  const SizedBox(width: 8),
                  _StatusBadge(label: statusLabel, color: color),
                ],
              ),
              if (automation.description != null && automation.description!.isNotEmpty) ...[
                const SizedBox(height: 6),
                Text(
                  automation.description!,
                  style: theme.textTheme.bodyMedium,
                ),
              ],
              if (automation.owners.isNotEmpty) ...[
                const SizedBox(height: 8),
                _AutomationMetaRow(
                  icon: Icons.groups_rounded,
                  text: 'Responsáveis: ${automation.owners.join(', ')}',
                ),
              ],
              if (metrics.isNotEmpty) ...[
                const SizedBox(height: 12),
                Wrap(
                  spacing: 12,
                  runSpacing: 12,
                  children: metrics,
                ),
              ],
              if (automation.signals.isNotEmpty) ...[
                const SizedBox(height: 12),
                _AutomationMetaRow(
                  icon: Icons.monitor_heart,
                  text: 'Monitoramentos: ${automation.signals.join(', ')}',
                ),
              ],
              if (automation.playbooks.isNotEmpty) ...[
                const SizedBox(height: 8),
                _AutomationMetaRow(
                  icon: Icons.menu_book,
                  text: 'Playbooks: ${automation.playbooks.join(', ')}',
                ),
              ],
            ],
          ),
        ),
      ],
    );
  }
}

class _AutomationMetaRow extends StatelessWidget {
  const _AutomationMetaRow({
    required this.icon,
    required this.text,
  });

  final IconData icon;
  final String text;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Icon(icon, size: 18, color: theme.colorScheme.primary),
        const SizedBox(width: 8),
        Expanded(
          child: Text(
            text,
            style: theme.textTheme.bodySmall?.copyWith(
              color: theme.colorScheme.onSurfaceVariant,
            ),
          ),
        ),
      ],
    );
  }
}

class _OperationsSloCard extends StatelessWidget {
  const _OperationsSloCard({required this.slos});

  final List<OperationsSlo> slos;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final ordered = [...slos]
      ..sort((a, b) {
        final statusComparison = _sloStatusOrder(a.status) - _sloStatusOrder(b.status);
        if (statusComparison != 0) {
          return statusComparison;
        }
        return a.service.compareTo(b.service);
      });

    return Card(
      elevation: 0,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
      child: Padding(
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'SLOs Pix monitorados',
              style: theme.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w700),
            ),
            const SizedBox(height: 12),
            ...List.generate(ordered.length, (index) {
              final slo = ordered[index];
              final isLast = index == ordered.length - 1;
              return Column(
                children: [
                  _SloTile(slo: slo),
                  if (!isLast) const Divider(height: 24),
                ],
              );
            }),
          ],
        ),
      ),
    );
  }
}

class _SloTile extends StatelessWidget {
  const _SloTile({required this.slo});

  final OperationsSlo slo;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final statusColor = _sloStatusColor(slo.status, theme);
    final statusLabel = _sloStatusLabel(slo.status);

    final targetLabel = slo.direction == OperationsSloDirection.above
        ? 'Meta ≥ ${slo.target.toStringAsFixed(2)}%'
        : 'Meta ≤ ${slo.target.toStringAsFixed(2)}';
    final currentLabel = slo.direction == OperationsSloDirection.above
        ? 'Atual ${slo.current.toStringAsFixed(2)}%'
        : 'Atual ${slo.current.toStringAsFixed(2)}';

    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Container(
          width: 40,
          height: 40,
          decoration: BoxDecoration(
            color: statusColor.withOpacity(0.12),
            borderRadius: BorderRadius.circular(12),
          ),
          child: Icon(_sloStatusIcon(slo.status), color: statusColor),
        ),
        const SizedBox(width: 12),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Expanded(
                    child: Text(
                      slo.service,
                      style: theme.textTheme.titleSmall?.copyWith(fontWeight: FontWeight.w700),
                    ),
                  ),
                  const SizedBox(width: 8),
                  _StatusBadge(label: statusLabel, color: statusColor),
                ],
              ),
              const SizedBox(height: 4),
              Text(
                slo.indicator,
                style: theme.textTheme.bodyMedium,
              ),
              const SizedBox(height: 8),
              Wrap(
                spacing: 8,
                runSpacing: 8,
                children: [
                  _StatusBadge(label: targetLabel, color: theme.colorScheme.primary),
                  _StatusBadge(label: currentLabel, color: theme.colorScheme.secondary),
                  if (slo.windowDays != null)
                    _StatusBadge(
                      label: '${slo.windowDays}-dias',
                      color: theme.colorScheme.tertiary,
                    ),
                  if ((slo.breaches ?? 0) > 0)
                    _StatusBadge(
                      label: '${slo.breaches} violações',
                      color: theme.colorScheme.error,
                    ),
                ],
              ),
              if (slo.notes.isNotEmpty) ...[
                const SizedBox(height: 8),
                ...slo.notes.map(
                  (note) => Padding(
                    padding: const EdgeInsets.only(bottom: 4),
                    child: Text(
                      note,
                      style: theme.textTheme.bodySmall?.copyWith(
                        color: theme.colorScheme.onSurfaceVariant,
                      ),
                    ),
                  ),
                ),
              ],
            ],
          ),
        ),
      ],
    );
  }
}

class _OperationsSloBreachesCard extends StatelessWidget {
  const _OperationsSloBreachesCard({required this.breaches});

  final List<OperationsSloBreach> breaches;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final ordered = [...breaches]
      ..sort((a, b) {
        final statusComparison =
            _sloBreachStatusOrder(a.status) - _sloBreachStatusOrder(b.status);
        if (statusComparison != 0) {
          return statusComparison;
        }
        final aDetected = a.detectedAt ?? DateTime.fromMillisecondsSinceEpoch(0);
        final bDetected = b.detectedAt ?? DateTime.fromMillisecondsSinceEpoch(0);
        return bDetected.compareTo(aDetected);
      });

    return Card(
      elevation: 0,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
      child: Padding(
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Violações recentes de SLO',
              style: theme.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w700),
            ),
            const SizedBox(height: 12),
            if (ordered.isEmpty)
              Text(
                'Nenhuma violação aberta no período monitorado.',
                style: theme.textTheme.bodyMedium,
              )
            else
              ...List.generate(ordered.length, (index) {
                final breach = ordered[index];
                final isLast = index == ordered.length - 1;
                return Column(
                  children: [
                    _SloBreachTile(breach: breach),
                    if (!isLast) const Divider(height: 24),
                  ],
                );
              }),
          ],
        ),
      ),
    );
  }
}

class _SloBreachTile extends StatelessWidget {
  const _SloBreachTile({required this.breach});

  final OperationsSloBreach breach;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final statusColor = _sloBreachStatusColor(breach.status, theme);
    final statusLabel = _sloBreachStatusLabel(breach.status);
    final impactColor = _sloBreachImpactColor(breach.impact, theme);
    final impactLabel = _sloBreachImpactLabel(breach.impact);
    final detectedLabel =
        breach.detectedAt != null ? _formatDate(breach.detectedAt!) : null;
    final resolvedLabel =
        breach.resolvedAt != null ? _formatDate(breach.resolvedAt!) : null;
    final windowLabel =
        breach.windowDays != null ? '${breach.windowDays}-dias' : null;
    final deviation = breach.breachPercentage;

    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Container(
          width: 40,
          height: 40,
          decoration: BoxDecoration(
            color: statusColor.withOpacity(0.12),
            borderRadius: BorderRadius.circular(12),
          ),
          child: Icon(_sloBreachStatusIcon(breach.status), color: statusColor),
        ),
        const SizedBox(width: 12),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Expanded(
                    child: Text(
                      breach.service,
                      style: theme.textTheme.titleSmall?.copyWith(
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                  ),
                  const SizedBox(width: 8),
                  _StatusBadge(label: statusLabel, color: statusColor),
                ],
              ),
              const SizedBox(height: 4),
              Text(
                breach.indicator,
                style: theme.textTheme.bodyMedium,
              ),
              const SizedBox(height: 8),
              Wrap(
                spacing: 8,
                runSpacing: 8,
                children: [
                  _StatusBadge(label: impactLabel, color: impactColor),
                  if (windowLabel != null)
                    _StatusBadge(
                      label: windowLabel,
                      color: theme.colorScheme.tertiary,
                    ),
                  if (deviation != null)
                    _StatusBadge(
                      label: 'Desvio ${deviation.toStringAsFixed(1)}%',
                      color: theme.colorScheme.error,
                    ),
                  if (breach.owner != null && breach.owner!.isNotEmpty)
                    _StatusBadge(
                      label: 'Owner ${breach.owner}',
                      color: theme.colorScheme.primary,
                    ),
                ],
              ),
              if (detectedLabel != null || resolvedLabel != null) ...[
                const SizedBox(height: 8),
                Text(
                  resolvedLabel != null
                      ? 'Detectado $detectedLabel · Resolvido $resolvedLabel'
                      : 'Detectado $detectedLabel',
                  style: theme.textTheme.bodySmall?.copyWith(
                    color: theme.colorScheme.onSurfaceVariant,
                  ),
                ),
              ],
              if (breach.actions.isNotEmpty) ...[
                const SizedBox(height: 8),
                ...breach.actions.map(
                  (action) => Padding(
                    padding: const EdgeInsets.only(bottom: 4),
                    child: Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Padding(
                          padding: const EdgeInsets.only(top: 4),
                          child: Icon(
                            Icons.check,
                            size: 14,
                            color: theme.colorScheme.primary,
                          ),
                        ),
                        const SizedBox(width: 6),
                        Expanded(
                          child: Text(
                            action,
                            style: theme.textTheme.bodySmall?.copyWith(
                              color: theme.colorScheme.onSurfaceVariant,
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              ],
            ],
          ),
        ),
      ],
    );
  }
}

class _AlertTile extends StatelessWidget {
  const _AlertTile({required this.alert});

  final OperationsReadinessAlert alert;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final color = _alertColor(alert.level, theme);

    return DecoratedBox(
      decoration: BoxDecoration(
        color: color.withOpacity(0.1),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: color.withOpacity(0.4)),
      ),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Icon(
              _alertIcon(alert.level),
              color: color,
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    alert.message,
                    style: theme.textTheme.titleSmall?.copyWith(
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                  if (alert.details != null) ...[
                    const SizedBox(height: 6),
                    Text(
                      alert.details!,
                      style: theme.textTheme.bodyMedium,
                    ),
                  ],
                  if (alert.component != null || alert.actionLabel != null) ...[
                    const SizedBox(height: 12),
                    Wrap(
                      spacing: 8,
                      runSpacing: 8,
                      children: [
                        if (alert.component != null)
                          _OperationsInfoChip(
                            label: 'Componente',
                            value: _describeComponent(alert.component!),
                            color: color.withOpacity(0.15),
                            textColor: color,
                          ),
                        if (alert.actionLabel != null && alert.actionUrl != null)
                          ActionChip(
                            label: Text(alert.actionLabel!),
                            onPressed: () {
                              debugPrint('Abrir ${alert.actionUrl}');
                            },
                          ),
                      ],
                    ),
                  ],
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _OperationsMilestonesCard extends StatelessWidget {
  const _OperationsMilestonesCard({required this.milestones});

  final List<OperationsReadinessMilestone> milestones;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final sorted = [...milestones]
      ..sort((a, b) {
        final statusCompare = _milestoneOrder(a.status).compareTo(_milestoneOrder(b.status));
        if (statusCompare != 0) {
          return statusCompare;
        }
        final aDate = a.targetDate ?? a.completedAt;
        final bDate = b.targetDate ?? b.completedAt;
        if (aDate == null && bDate == null) {
          return a.title.compareTo(b.title);
        }
        if (aDate == null) {
          return 1;
        }
        if (bDate == null) {
          return -1;
        }
        return aDate.compareTo(bDate);
      });

    return Card(
      elevation: 0,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
      child: Padding(
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Linha do tempo de prontidão',
              style: theme.textTheme.titleMedium?.copyWith(
                fontWeight: FontWeight.w700,
              ),
            ),
            const SizedBox(height: 12),
            ...sorted.map(
              (milestone) => Padding(
                padding: const EdgeInsets.only(bottom: 16),
                child: _MilestoneTile(milestone: milestone),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _MilestoneTile extends StatelessWidget {
  const _MilestoneTile({required this.milestone});

  final OperationsReadinessMilestone milestone;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final color = _milestoneColor(milestone.status, theme);
    final completion = (milestone.completion ?? (milestone.isDone
            ? 100
            : milestone.isInProgress
                ? 60
                : 0)) /
        100;

    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Padding(
          padding: const EdgeInsets.only(top: 4),
          child: Icon(
            _milestoneIcon(milestone.status),
            color: color,
          ),
        ),
        const SizedBox(width: 12),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                milestone.title,
                style: theme.textTheme.titleSmall?.copyWith(
                  fontWeight: FontWeight.w700,
                ),
              ),
              const SizedBox(height: 4),
              Wrap(
                spacing: 8,
                runSpacing: 8,
                crossAxisAlignment: WrapCrossAlignment.center,
                children: [
                  _OperationsInfoChip(
                    label: 'Status',
                    value: _milestoneStatusLabel(milestone),
                    color: color.withOpacity(0.15),
                    textColor: color,
                  ),
                  if (milestone.owner != null)
                    _OperationsInfoChip(
                      label: 'Responsável',
                      value: milestone.owner!,
                      color: theme.colorScheme.surfaceVariant,
                      textColor: theme.colorScheme.onSurfaceVariant,
                    ),
                  if (milestone.targetDate != null)
                    _OperationsInfoChip(
                      label: milestone.isDone ? 'Concluído' : 'Entrega alvo',
                      value: milestone.isDone
                          ? _formatDate(milestone.completedAt ?? milestone.targetDate!)
                          : _formatShortDate(milestone.targetDate!),
                      color: milestone.overdue && !milestone.isDone
                          ? theme.colorScheme.errorContainer
                          : theme.colorScheme.primaryContainer.withOpacity(0.35),
                      textColor: milestone.overdue && !milestone.isDone
                          ? theme.colorScheme.onErrorContainer
                          : theme.colorScheme.onPrimaryContainer,
                    ),
                ],
              ),
              if (milestone.description != null) ...[
                const SizedBox(height: 8),
                Text(
                  milestone.description!,
                  style: theme.textTheme.bodyMedium,
                ),
              ],
              const SizedBox(height: 12),
              ClipRRect(
                borderRadius: BorderRadius.circular(999),
                child: LinearProgressIndicator(
                  value: completion.clamp(0.0, 1.0),
                  minHeight: 8,
                  backgroundColor: theme.colorScheme.surfaceVariant.withOpacity(0.4),
                  valueColor: AlwaysStoppedAnimation<Color>(color),
                ),
              ),
              if (milestone.blockers.isNotEmpty) ...[
                const SizedBox(height: 12),
                Text(
                  'Bloqueadores',
                  style: theme.textTheme.labelLarge?.copyWith(
                    color: theme.colorScheme.error,
                    fontWeight: FontWeight.w600,
                  ),
                ),
                const SizedBox(height: 4),
                ...milestone.blockers.map(
                  (blocker) => Padding(
                    padding: const EdgeInsets.only(bottom: 2),
                    child: Text(
                      '• $blocker',
                      style: theme.textTheme.bodyMedium?.copyWith(
                        color: theme.colorScheme.error,
                      ),
                    ),
                  ),
                ),
              ],
            ],
          ),
        ),
      ],
    );
  }
}

class _OverviewInfoRow extends StatelessWidget {
  const _OverviewInfoRow({
    required this.label,
    required this.value,
  });

  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Column(
      crossAxisAlignment: CrossAxisAlignment.end,
      children: [
        Text(
          label,
          style: theme.textTheme.labelSmall?.copyWith(
            color: theme.colorScheme.onSurfaceVariant,
          ),
        ),
        Text(
          value,
          style: theme.textTheme.bodyMedium?.copyWith(
            fontWeight: FontWeight.w600,
          ),
        ),
      ],
    );
  }
}

class _OperationsCountsCard extends StatelessWidget {
  const _OperationsCountsCard({required this.counts});

  final Map<String, num> counts;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Card(
      elevation: 0,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
      child: Padding(
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Indicadores Pix',
              style: theme.textTheme.titleMedium?.copyWith(
                fontWeight: FontWeight.w700,
              ),
            ),
            const SizedBox(height: 12),
            Wrap(
              spacing: 12,
              runSpacing: 12,
              children: counts.entries.map((entry) {
                final label = _describeCountKey(entry.key);
                final value = _numberFormatter.format(entry.value);
                return _OperationsInfoChip(
                  label: label,
                  value: value,
                  color: theme.colorScheme.surfaceVariant,
                  textColor: theme.colorScheme.onSurfaceVariant,
                );
              }).toList(),
            ),
          ],
        ),
      ),
    );
  }
}

class _OperationsInfoChip extends StatelessWidget {
  const _OperationsInfoChip({
    required this.label,
    required this.value,
    required this.color,
    required this.textColor,
  });

  final String label;
  final String value;
  final Color color;
  final Color textColor;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
      decoration: BoxDecoration(
        color: color,
        borderRadius: BorderRadius.circular(16),
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            label,
            style: Theme.of(context).textTheme.labelSmall?.copyWith(
                  color: textColor.withOpacity(0.8),
                ),
          ),
          const SizedBox(height: 4),
          Text(
            value,
            style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                  fontWeight: FontWeight.w700,
                  color: textColor,
                ),
          ),
        ],
      ),
    );
  }
}

class _StatusBadge extends StatelessWidget {
  const _StatusBadge({
    required this.label,
    required this.color,
    this.textColor,
  });

  final String label;
  final Color color;
  final Color? textColor;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final baseColor = color;
    final resolvedTextColor = textColor ?? baseColor;

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
      decoration: BoxDecoration(
        color: baseColor.withOpacity(0.12),
        borderRadius: BorderRadius.circular(999),
        border: Border.all(color: baseColor.withOpacity(0.32)),
      ),
      child: Text(
        label,
        style: theme.textTheme.labelSmall?.copyWith(
          color: resolvedTextColor,
          fontWeight: FontWeight.w600,
        ),
      ),
    );
  }
}

class _OperationsComponentCard extends StatelessWidget {
  const _OperationsComponentCard({required this.component});

  final OperationsReadinessComponent component;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final label = component.label ?? _describeCountKey(component.key);
    final percentage = component.percentage.clamp(0, 100);
    final progress = percentage / 100;
    final pendingChecks = component.checks
        .where((check) => component.pending.contains(check.key))
        .toList();

    return Card(
      elevation: 0,
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
                    label,
                    style: theme.textTheme.titleMedium?.copyWith(
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                ),
                Text(
                  '$percentage%',
                  style: theme.textTheme.titleMedium?.copyWith(
                    color: theme.colorScheme.primary,
                    fontWeight: FontWeight.w700,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 12),
            ClipRRect(
              borderRadius: BorderRadius.circular(999),
              child: LinearProgressIndicator(
                value: progress,
                minHeight: 8,
                backgroundColor: theme.colorScheme.surfaceVariant.withOpacity(0.4),
                valueColor: AlwaysStoppedAnimation<Color>(theme.colorScheme.primary),
              ),
            ),
            const SizedBox(height: 12),
            Wrap(
              spacing: 8,
              runSpacing: 8,
              children: [
                _OperationsInfoChip(
                  label: 'Baseline',
                  value: '${component.baseline}%',
                  color: theme.colorScheme.surfaceVariant,
                  textColor: theme.colorScheme.onSurfaceVariant,
                ),
                _OperationsInfoChip(
                  label: 'Cálculo atual',
                  value: '${component.computed}%',
                  color: theme.colorScheme.primaryContainer,
                  textColor: theme.colorScheme.onPrimaryContainer,
                ),
                _OperationsInfoChip(
                  label: 'Peso',
                  value: component.weight.toStringAsFixed(2),
                  color: theme.colorScheme.secondaryContainer,
                  textColor: theme.colorScheme.onSecondaryContainer,
                ),
              ],
            ),
            if (component.nextSteps.isNotEmpty) ...[
              const SizedBox(height: 16),
              Text(
                'Próximos passos',
                style: theme.textTheme.titleSmall?.copyWith(
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
                      const Padding(
                        padding: EdgeInsets.only(right: 8, top: 6),
                        child: Icon(
                          Icons.arrow_forward_ios,
                          size: 12,
                        ),
                      ),
                      Expanded(
                        child: Text(
                          step,
                          style: theme.textTheme.bodyMedium,
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            ],
            if (pendingChecks.isNotEmpty) ...[
              const SizedBox(height: 16),
              Text(
                'Pendências',
                style: theme.textTheme.titleSmall?.copyWith(
                  fontWeight: FontWeight.w600,
                  color: theme.colorScheme.error,
                ),
              ),
              const SizedBox(height: 8),
              ...pendingChecks.map(
                (check) => Padding(
                  padding: const EdgeInsets.only(bottom: 4),
                  child: Text(
                    '• ${check.label}',
                    style: theme.textTheme.bodyMedium?.copyWith(
                      color: theme.colorScheme.error,
                    ),
                  ),
                ),
              ),
            ],
            if (component.notes.isNotEmpty) ...[
              const SizedBox(height: 16),
              Text(
                'Notas',
                style: theme.textTheme.titleSmall?.copyWith(
                  fontWeight: FontWeight.w600,
                ),
              ),
              const SizedBox(height: 8),
              ...component.notes.map(
                (note) => Padding(
                  padding: const EdgeInsets.only(bottom: 4),
                  child: Text(
                    note,
                    style: theme.textTheme.bodyMedium,
                  ),
                ),
              ),
            ],
            if (component.checks.isNotEmpty) ...[
              const SizedBox(height: 16),
              Text(
                'Checklist',
                style: theme.textTheme.titleSmall?.copyWith(
                  fontWeight: FontWeight.w600,
                ),
              ),
              const SizedBox(height: 8),
              ...component.checks.map(
                (check) => _OperationsCheckRow(check: check),
              ),
            ],
          ],
        ),
      ),
    );
  }
}

class _OperationsCheckRow extends StatelessWidget {
  const _OperationsCheckRow({required this.check});

  final OperationsReadinessCheck check;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final isDone = check.status == OperationsCheckStatus.done;

    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(
            isDone ? Icons.check_circle : Icons.radio_button_unchecked,
            color: isDone ? theme.colorScheme.secondary : theme.colorScheme.outline,
            size: 20,
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  check.label,
                  style: theme.textTheme.bodyMedium?.copyWith(
                    fontWeight: isDone ? FontWeight.w600 : FontWeight.w500,
                  ),
                ),
                if (check.details != null)
                  Padding(
                    padding: const EdgeInsets.only(top: 2),
                    child: Text(
                      check.details!,
                      style: theme.textTheme.bodySmall?.copyWith(
                        color: theme.colorScheme.onSurfaceVariant,
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

class _OperationsSourcesCard extends StatelessWidget {
  const _OperationsSourcesCard({required this.sources});

  final List<OperationsReadinessSource> sources;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Card(
      elevation: 0,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
      child: Padding(
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Fontes do snapshot',
              style: theme.textTheme.titleMedium?.copyWith(
                fontWeight: FontWeight.w700,
              ),
            ),
            const SizedBox(height: 12),
            ...sources.map(
              (source) => ListTile(
                contentPadding: EdgeInsets.zero,
                leading: Icon(_iconForSource(source.type)),
                title: Text(source.value),
                subtitle: source.description != null ? Text(source.description!) : null,
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _OperationsErrorView extends StatelessWidget {
  const _OperationsErrorView({required this.onRetry});

  final Future<void> Function({bool preferNative}) onRetry;

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(32),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Icon(Icons.warning_amber_outlined, size: 48),
            const SizedBox(height: 16),
            const Text(
              'Não foi possível carregar o snapshot de prontidão.',
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 24),
            FilledButton(
              onPressed: () async {
                await onRetry(preferNative: true);
              },
              child: const Text('Tentar novamente'),
            ),
            const SizedBox(height: 12),
            TextButton(
              onPressed: () async {
                await onRetry(preferNative: false);
              },
              child: const Text('Forçar atualização pela API'),
            ),
          ],
        ),
      ),
    );
  }
}

IconData _iconForSource(String type) {
  switch (type) {
    case 'script':
      return Icons.code_outlined;
    case 'document':
      return Icons.description_outlined;
    case 'runbook':
      return Icons.checklist_outlined;
    case 'report':
      return Icons.insert_chart_outlined;
    default:
      return Icons.link_outlined;
  }
}

int _alertOrder(OperationsAlertLevel level) {
  switch (level) {
    case OperationsAlertLevel.critical:
      return 3;
    case OperationsAlertLevel.warning:
      return 2;
    case OperationsAlertLevel.info:
      return 1;
  }
}

Color _alertColor(OperationsAlertLevel level, ThemeData theme) {
  switch (level) {
    case OperationsAlertLevel.critical:
      return theme.colorScheme.error;
    case OperationsAlertLevel.warning:
      return theme.colorScheme.tertiary;
    case OperationsAlertLevel.info:
      return theme.colorScheme.primary;
  }
}

IconData _alertIcon(OperationsAlertLevel level) {
  switch (level) {
    case OperationsAlertLevel.critical:
      return Icons.error_outline;
    case OperationsAlertLevel.warning:
      return Icons.warning_amber_outlined;
    case OperationsAlertLevel.info:
      return Icons.info_outline;
  }
}

String _describeComponent(String key) {
  switch (key) {
    case 'flutter_ios':
      return 'Flutter iOS';
    case 'strapi_backend':
      return 'Strapi backend';
    case 'operations':
      return 'Operações';
    default:
      return key;
  }
}

String _incidentStatusLabel(OperationsIncident incident) {
  switch (incident.status) {
    case OperationsIncidentStatus.investigating:
      return 'Investigação';
    case OperationsIncidentStatus.monitoring:
      return incident.isActive ? 'Monitorando' : 'Monitorado';
    case OperationsIncidentStatus.resolved:
      return 'Resolvido';
  }
}

Color _incidentStatusColor(OperationsIncident incident, ThemeData theme) {
  switch (incident.status) {
    case OperationsIncidentStatus.investigating:
      return theme.colorScheme.error;
    case OperationsIncidentStatus.monitoring:
      return theme.colorScheme.primary;
    case OperationsIncidentStatus.resolved:
      return theme.colorScheme.secondary;
  }
}

IconData _incidentStatusIcon(OperationsIncident incident) {
  switch (incident.status) {
    case OperationsIncidentStatus.investigating:
      return Icons.report_gmailerrorred_outlined;
    case OperationsIncidentStatus.monitoring:
      return Icons.remove_red_eye_outlined;
    case OperationsIncidentStatus.resolved:
      return Icons.check_circle_outline;
  }
}

String _incidentImpactLabel(OperationsIncidentImpact impact) {
  switch (impact) {
    case OperationsIncidentImpact.none:
      return 'Sem impacto';
    case OperationsIncidentImpact.minor:
      return 'Impacto leve';
    case OperationsIncidentImpact.major:
      return 'Impacto moderado';
    case OperationsIncidentImpact.critical:
      return 'Impacto crítico';
  }
}

Color _incidentImpactColor(OperationsIncidentImpact impact, ThemeData theme) {
  switch (impact) {
    case OperationsIncidentImpact.none:
      return theme.colorScheme.surfaceTint;
    case OperationsIncidentImpact.minor:
      return theme.colorScheme.tertiary;
    case OperationsIncidentImpact.major:
      return theme.colorScheme.error;
    case OperationsIncidentImpact.critical:
      return theme.colorScheme.error;
  }
}

String? _incidentTimeline(OperationsIncident incident) {
  final started = incident.startedAt;
  final resolved = incident.resolvedAt;
  final updated = incident.updatedAt;

  if (started != null && resolved != null) {
    return 'Iniciado ${_formatDate(started)} · Resolvido ${_formatDate(resolved)}';
  }
  if (started != null && updated != null) {
    return 'Iniciado ${_formatDate(started)} · Atualizado ${_formatDate(updated)}';
  }
  if (updated != null) {
    return 'Atualizado ${_formatDate(updated)}';
  }
  return null;
}

String _maintenanceStatusLabel(OperationsMaintenanceWindow window) {
  switch (window.status) {
    case OperationsMaintenanceStatus.scheduled:
      return window.isUpcoming ? 'Agendado' : 'Planejado';
    case OperationsMaintenanceStatus.inProgress:
      return 'Em andamento';
    case OperationsMaintenanceStatus.completed:
      return 'Concluído';
  }
}

Color _maintenanceStatusColor(OperationsMaintenanceWindow window, ThemeData theme) {
  switch (window.status) {
    case OperationsMaintenanceStatus.inProgress:
      return theme.colorScheme.primary;
    case OperationsMaintenanceStatus.scheduled:
      return theme.colorScheme.secondary;
    case OperationsMaintenanceStatus.completed:
      return theme.colorScheme.surfaceTint;
  }
}

IconData _maintenanceStatusIcon(OperationsMaintenanceWindow window) {
  switch (window.status) {
    case OperationsMaintenanceStatus.inProgress:
      return Icons.build_circle_outlined;
    case OperationsMaintenanceStatus.scheduled:
      return Icons.event_outlined;
    case OperationsMaintenanceStatus.completed:
      return Icons.check_circle_outline;
  }
}

String _maintenanceImpactLabel(OperationsMaintenanceImpact impact) {
  switch (impact) {
    case OperationsMaintenanceImpact.none:
      return 'Sem impacto';
    case OperationsMaintenanceImpact.minor:
      return 'Impacto leve';
    case OperationsMaintenanceImpact.major:
      return 'Impacto moderado';
    case OperationsMaintenanceImpact.critical:
      return 'Impacto crítico';
  }
}

Color _maintenanceImpactColor(OperationsMaintenanceImpact impact, ThemeData theme) {
  switch (impact) {
    case OperationsMaintenanceImpact.none:
      return theme.colorScheme.surfaceTint;
    case OperationsMaintenanceImpact.minor:
      return theme.colorScheme.tertiary;
    case OperationsMaintenanceImpact.major:
      return theme.colorScheme.error;
    case OperationsMaintenanceImpact.critical:
      return theme.colorScheme.error;
  }
}

int _onCallStatusOrder(OperationsOnCallStatus status) {
  switch (status) {
    case OperationsOnCallStatus.active:
      return 0;
    case OperationsOnCallStatus.standby:
      return 1;
    case OperationsOnCallStatus.offline:
      return 2;
  }
}

Color _onCallStatusColor(OperationsOnCallStatus status, ThemeData theme) {
  switch (status) {
    case OperationsOnCallStatus.active:
      return theme.colorScheme.primary;
    case OperationsOnCallStatus.standby:
      return theme.colorScheme.secondary;
    case OperationsOnCallStatus.offline:
      return theme.colorScheme.outline;
  }
}

String _onCallStatusLabel(OperationsOnCallStatus status) {
  switch (status) {
    case OperationsOnCallStatus.active:
      return 'Ativo';
    case OperationsOnCallStatus.standby:
      return 'Standby';
    case OperationsOnCallStatus.offline:
      return 'Offline';
  }
}

IconData _onCallStatusIcon(OperationsOnCallStatus status) {
  switch (status) {
    case OperationsOnCallStatus.active:
      return Icons.support_agent;
    case OperationsOnCallStatus.standby:
      return Icons.schedule;
    case OperationsOnCallStatus.offline:
      return Icons.nightlight_round;
  }
}

String? _onCallShiftLabel(OperationsOnCall entry) {
  final start = entry.startedAt?.toLocal();
  final end = entry.endsAt?.toLocal();
  final formatter = DateFormat('dd/MM HH:mm');

  if (start != null && end != null) {
    return '${formatter.format(start)} – ${formatter.format(end)}';
  }

  if (start != null) {
    return 'Início ${formatter.format(start)}';
  }

  if (end != null) {
    return 'Até ${formatter.format(end)}';
  }

  final duration = entry.shiftDuration;
  if (duration != null) {
    return 'Duração ${_formatDuration(duration)}';
  }

  return null;
}

int _automationStatusOrder(OperationsAutomationStatus status) {
  switch (status) {
    case OperationsAutomationStatus.blocked:
      return 0;
    case OperationsAutomationStatus.degraded:
      return 1;
    case OperationsAutomationStatus.inProgress:
      return 2;
    case OperationsAutomationStatus.operational:
      return 3;
  }
}

Color _automationStatusColor(OperationsAutomationStatus status, ThemeData theme) {
  switch (status) {
    case OperationsAutomationStatus.blocked:
      return theme.colorScheme.error;
    case OperationsAutomationStatus.degraded:
      return theme.colorScheme.tertiary;
    case OperationsAutomationStatus.inProgress:
      return theme.colorScheme.secondary;
    case OperationsAutomationStatus.operational:
      return theme.colorScheme.primary;
  }
}

String _automationStatusLabel(OperationsAutomationStatus status) {
  switch (status) {
    case OperationsAutomationStatus.blocked:
      return 'Parada';
    case OperationsAutomationStatus.degraded:
      return 'Degradada';
    case OperationsAutomationStatus.inProgress:
      return 'Em progresso';
    case OperationsAutomationStatus.operational:
      return 'Operacional';
  }
}

IconData _automationStatusIcon(OperationsAutomationStatus status) {
  switch (status) {
    case OperationsAutomationStatus.blocked:
      return Icons.block;
    case OperationsAutomationStatus.degraded:
      return Icons.warning_amber_rounded;
    case OperationsAutomationStatus.inProgress:
      return Icons.autorenew;
    case OperationsAutomationStatus.operational:
      return Icons.check_circle_outline;
  }
}

int _sloStatusOrder(OperationsSloStatus status) {
  switch (status) {
    case OperationsSloStatus.healthy:
      return 0;
    case OperationsSloStatus.atRisk:
      return 1;
    case OperationsSloStatus.breaching:
      return 2;
  }
}

Color _sloStatusColor(OperationsSloStatus status, ThemeData theme) {
  switch (status) {
    case OperationsSloStatus.healthy:
      return theme.colorScheme.primary;
    case OperationsSloStatus.atRisk:
      return theme.colorScheme.tertiary;
    case OperationsSloStatus.breaching:
      return theme.colorScheme.error;
  }
}

String _sloStatusLabel(OperationsSloStatus status) {
  switch (status) {
    case OperationsSloStatus.healthy:
      return 'Saudável';
    case OperationsSloStatus.atRisk:
      return 'Em risco';
    case OperationsSloStatus.breaching:
      return 'Violando meta';
  }
}

IconData _sloStatusIcon(OperationsSloStatus status) {
  switch (status) {
    case OperationsSloStatus.healthy:
      return Icons.check_circle;
    case OperationsSloStatus.atRisk:
      return Icons.warning_amber_rounded;
    case OperationsSloStatus.breaching:
      return Icons.error_outline;
  }
}

int _sloBreachStatusOrder(OperationsSloBreachStatus status) {
  switch (status) {
    case OperationsSloBreachStatus.open:
      return 0;
    case OperationsSloBreachStatus.acknowledged:
      return 1;
    case OperationsSloBreachStatus.resolved:
      return 2;
  }
}

Color _sloBreachStatusColor(OperationsSloBreachStatus status, ThemeData theme) {
  switch (status) {
    case OperationsSloBreachStatus.open:
      return theme.colorScheme.error;
    case OperationsSloBreachStatus.acknowledged:
      return theme.colorScheme.tertiary;
    case OperationsSloBreachStatus.resolved:
      return theme.colorScheme.secondary;
  }
}

String _sloBreachStatusLabel(OperationsSloBreachStatus status) {
  switch (status) {
    case OperationsSloBreachStatus.open:
      return 'Aberta';
    case OperationsSloBreachStatus.acknowledged:
      return 'Reconhecida';
    case OperationsSloBreachStatus.resolved:
      return 'Resolvida';
  }
}

IconData _sloBreachStatusIcon(OperationsSloBreachStatus status) {
  switch (status) {
    case OperationsSloBreachStatus.open:
      return Icons.report_problem_outlined;
    case OperationsSloBreachStatus.acknowledged:
      return Icons.support_agent;
    case OperationsSloBreachStatus.resolved:
      return Icons.verified_outlined;
  }
}

Color _sloBreachImpactColor(OperationsSloBreachImpact impact, ThemeData theme) {
  switch (impact) {
    case OperationsSloBreachImpact.none:
      return theme.colorScheme.outline;
    case OperationsSloBreachImpact.minor:
      return theme.colorScheme.primary;
    case OperationsSloBreachImpact.major:
      return theme.colorScheme.errorContainer;
    case OperationsSloBreachImpact.critical:
      return theme.colorScheme.error;
  }
}

String _sloBreachImpactLabel(OperationsSloBreachImpact impact) {
  switch (impact) {
    case OperationsSloBreachImpact.none:
      return 'Impacto nenhum';
    case OperationsSloBreachImpact.minor:
      return 'Impacto baixo';
    case OperationsSloBreachImpact.major:
      return 'Impacto alto';
    case OperationsSloBreachImpact.critical:
      return 'Impacto crítico';
  }
}

String _formatDuration(Duration duration) {
  final hours = duration.inHours;
  final minutes = duration.inMinutes.remainder(60);
  final parts = <String>[];
  if (hours > 0) {
    parts.add('$hours h');
  }
  if (minutes > 0 || parts.isEmpty) {
    parts.add('$minutes min');
  }
  return parts.join(' ');
}

String? _maintenanceRange(OperationsMaintenanceWindow window) {
  final start = window.windowStart;
  final end = window.windowEnd;

  if (start != null && end != null) {
    return '${_formatDate(start)} · ${_formatDate(end)}';
  }
  if (start != null) {
    return 'Início ${_formatDate(start)}';
  }
  if (end != null) {
    return 'Conclusão ${_formatDate(end)}';
  }
  return null;
}

int _milestoneOrder(OperationsMilestoneStatus status) {
  switch (status) {
    case OperationsMilestoneStatus.pending:
      return 0;
    case OperationsMilestoneStatus.inProgress:
      return 1;
    case OperationsMilestoneStatus.done:
      return 2;
  }
}

Color _milestoneColor(OperationsMilestoneStatus status, ThemeData theme) {
  switch (status) {
    case OperationsMilestoneStatus.done:
      return theme.colorScheme.secondary;
    case OperationsMilestoneStatus.inProgress:
      return theme.colorScheme.primary;
    case OperationsMilestoneStatus.pending:
      return theme.colorScheme.outline;
  }
}

IconData _milestoneIcon(OperationsMilestoneStatus status) {
  switch (status) {
    case OperationsMilestoneStatus.done:
      return Icons.check_circle_outline;
    case OperationsMilestoneStatus.inProgress:
      return Icons.timelapse;
    case OperationsMilestoneStatus.pending:
      return Icons.radio_button_unchecked;
  }
}

String _milestoneStatusLabel(OperationsReadinessMilestone milestone) {
  switch (milestone.status) {
    case OperationsMilestoneStatus.done:
      return 'Concluído';
    case OperationsMilestoneStatus.inProgress:
      return milestone.overdue ? 'Em progresso (atrasado)' : 'Em progresso';
    case OperationsMilestoneStatus.pending:
      return milestone.overdue ? 'Pendente (atrasado)' : 'Pendente';
  }
}

String _formatShortDate(DateTime date) {
  return _shortDateFormatter.format(date.toLocal());
}

String _formatDate(DateTime dateTime) {
  return _dateFormatter.format(dateTime.toLocal());
}

String _describeCountKey(String key) {
  const labels = {
    'planosTotal': 'Planos Pix',
    'planosGratisAprovados': 'Planos Grátis aprovados',
    'assinaturasAtivas': 'Assinaturas ativas',
    'assinaturasPendentes': 'Assinaturas pendentes',
    'cobrancasConfirmadas': 'Cobranças confirmadas',
    'cobrancasPendentes': 'Cobranças pendentes',
  };

  final mapped = labels[key];
  if (mapped != null) {
    return mapped;
  }

  final withSpaces = key
      .replaceAllMapped(RegExp('([A-Z])'), (match) => ' ${match.group(1)}')
      .replaceAll('_', ' ')
      .trim();
  if (withSpaces.isEmpty) {
    return key;
  }

  return withSpaces
      .split(' ')
      .map((word) => word.isEmpty
          ? word
          : '${word[0].toUpperCase()}${word.substring(1).toLowerCase()}')
      .join(' ');
}

final DateFormat _dateFormatter = DateFormat('dd/MM/yyyy HH:mm');
final DateFormat _shortDateFormatter = DateFormat('dd/MM');
final NumberFormat _numberFormatter = NumberFormat.decimalPattern('pt_BR');
