import 'dart:collection';
import 'dart:convert';

import 'package:dio/dio.dart';
import 'package:flutter/services.dart';

import '../../../core/operations/operations_channel.dart';
import '../../../core/operations/operations_status.dart';

class OperationsReadinessRepository {
  OperationsReadinessRepository(this._dio, this._channel);

  final Dio _dio;
  final OperationsChannel _channel;

  Future<OperationsReadinessSnapshot> fetch() async {
    final fallback = await _loadFallback();
    final remote = await _loadRemote();
    if (remote != null) {
      return _merge(remote, fallback);
    }

    final native = await _loadNative();
    if (native != null) {
      return _merge(native, fallback);
    }

    return fallback;
  }

  Future<OperationsReadinessSnapshot> refresh({bool preferNativeChannel = true}) async {
    final fallback = await _loadFallback();

    if (preferNativeChannel) {
      final nativePayload = await _channel.refreshStatus();
      if (nativePayload != null) {
        try {
          final nativeSnapshot = OperationsReadinessSnapshot.fromJson(nativePayload);
          return _merge(nativeSnapshot, fallback);
        } catch (_) {
          // Ignore payload parse errors and fall back to HTTP.
        }
      }
    }

    final remote = await _loadRemote();
    if (remote != null) {
      return _merge(remote, fallback);
    }

    final native = await _loadNative();
    if (native != null) {
      return _merge(native, fallback);
    }

    return fallback;
  }

  Future<OperationsReadinessSnapshot> _loadFallback() async {
    final content = await rootBundle.loadString('assets/data/operations_readiness.json');
    final data = jsonDecode(content) as Map<String, dynamic>;
    return OperationsReadinessSnapshot.fromJson(data);
  }

  Future<OperationsReadinessSnapshot?> _loadNative() async {
    final payload = await _channel.fetchBundledStatus();
    if (payload == null) {
      return null;
    }
    try {
      return OperationsReadinessSnapshot.fromJson(payload);
    } catch (_) {
      return null;
    }
  }

  Future<OperationsReadinessSnapshot?> _loadRemote() async {
    try {
      final response = await _dio.get<Map<String, dynamic>>('/operations/readiness');
      final data = response.data;
      if (data == null) {
        return null;
      }
      return OperationsReadinessSnapshot.fromJson(data);
    } on DioError {
      return null;
    } catch (_) {
      return null;
    }
  }

  OperationsReadinessSnapshot _merge(
    OperationsReadinessSnapshot primary,
    OperationsReadinessSnapshot fallback,
  ) {
    final Map<String, OperationsReadinessComponent> fallbackByKey = {
      for (final component in fallback.components) component.key: component,
    };

    final mergedComponents = <OperationsReadinessComponent>[];
    for (final component in primary.components) {
      mergedComponents.add(component.mergeWith(fallbackByKey[component.key]));
    }

    for (final fallbackComponent in fallback.components) {
      if (primary.componentByKey(fallbackComponent.key) == null) {
        mergedComponents.add(fallbackComponent);
      }
    }

    final mergedCounts = HashMap<String, num>()
      ..addAll(fallback.counts)
      ..addAll(primary.counts);

    final mergedSources = _mergeSources(primary.sources, fallback.sources);
    final mergedOverall = primary.overall.mergeWith(fallback.overall);
    final mergedMilestones = _mergeMilestones(primary.milestones, fallback.milestones);
    final mergedAlerts = _mergeAlerts(primary.alerts, fallback.alerts);
    final mergedIncidents = _mergeIncidents(primary.incidents, fallback.incidents);
    final mergedMaintenance =
        _mergeMaintenance(primary.maintenanceWindows, fallback.maintenanceWindows);
    final mergedOnCall = _mergeOnCall(primary.onCall, fallback.onCall);
    final mergedAutomations = _mergeAutomations(primary.automations, fallback.automations);
    final mergedSlos = _mergeSlos(primary.slos, fallback.slos);
    final mergedSloBreaches = _mergeSloBreaches(primary.sloBreaches, fallback.sloBreaches);

    final baselineTimestamp = primary.baselineTimestamp ?? fallback.baselineTimestamp;

    return primary.copyWith(
      overall: mergedOverall,
      components: mergedComponents,
      counts: mergedCounts,
      sources: mergedSources,
      milestones: mergedMilestones,
      alerts: mergedAlerts,
      incidents: mergedIncidents,
      maintenanceWindows: mergedMaintenance,
      baselineTimestamp: baselineTimestamp,
      onCall: mergedOnCall,
      automations: mergedAutomations,
      slos: mergedSlos,
      sloBreaches: mergedSloBreaches,
    );
  }

  List<OperationsReadinessSource> _mergeSources(
    List<OperationsReadinessSource> primary,
    List<OperationsReadinessSource> fallback,
  ) {
    final identitySet = <String>{};
    final merged = <OperationsReadinessSource>[];

    for (final source in [...fallback, ...primary]) {
      final identity = source.identity;
      if (identitySet.add(identity)) {
        merged.add(source);
      }
    }

    return merged;
  }

  List<OperationsReadinessMilestone> _mergeMilestones(
    List<OperationsReadinessMilestone> primary,
    List<OperationsReadinessMilestone> fallback,
  ) {
    final fallbackById = {for (final milestone in fallback) milestone.id: milestone};
    final merged = <OperationsReadinessMilestone>[];

    for (final milestone in primary) {
      final fallbackMilestone = fallbackById.remove(milestone.id);
      if (fallbackMilestone != null) {
        merged.add(milestone.mergeWith(fallbackMilestone));
      } else {
        merged.add(milestone);
      }
    }

    for (final leftover in fallbackById.values) {
      merged.add(leftover);
    }

    return merged;
  }

  List<OperationsReadinessAlert> _mergeAlerts(
    List<OperationsReadinessAlert> primary,
    List<OperationsReadinessAlert> fallback,
  ) {
    final fallbackById = {for (final alert in fallback) alert.id: alert};
    final merged = <OperationsReadinessAlert>[];

    for (final alert in primary) {
      final fallbackAlert = fallbackById.remove(alert.id);
      if (fallbackAlert != null) {
        merged.add(alert.mergeWith(fallbackAlert));
      } else {
        merged.add(alert);
      }
    }

    for (final leftover in fallbackById.values) {
      merged.add(leftover);
    }

    return merged.where((alert) => alert.active).toList();
  }

  List<OperationsIncident> _mergeIncidents(
    List<OperationsIncident> primary,
    List<OperationsIncident> fallback,
  ) {
    final fallbackById = {for (final incident in fallback) incident.id: incident};
    final merged = <OperationsIncident>[];

    for (final incident in primary) {
      final fallbackIncident = fallbackById.remove(incident.id);
      if (fallbackIncident != null) {
        merged.add(incident.mergeWith(fallbackIncident));
      } else {
        merged.add(incident);
      }
    }

    for (final leftover in fallbackById.values) {
      merged.add(leftover);
    }

    merged.sort((a, b) {
      final aDate = a.startedAt ?? a.updatedAt ?? DateTime.fromMillisecondsSinceEpoch(0);
      final bDate = b.startedAt ?? b.updatedAt ?? DateTime.fromMillisecondsSinceEpoch(0);
      return bDate.compareTo(aDate);
    });

    return merged;
  }

  List<OperationsMaintenanceWindow> _mergeMaintenance(
    List<OperationsMaintenanceWindow> primary,
    List<OperationsMaintenanceWindow> fallback,
  ) {
    final fallbackById = {for (final window in fallback) window.id: window};
    final merged = <OperationsMaintenanceWindow>[];

    for (final window in primary) {
      final fallbackWindow = fallbackById.remove(window.id);
      if (fallbackWindow != null) {
        merged.add(window.mergeWith(fallbackWindow));
      } else {
        merged.add(window);
      }
    }

    for (final leftover in fallbackById.values) {
      merged.add(leftover);
    }

    merged.sort((a, b) {
      final aStart = a.windowStart ?? DateTime.fromMillisecondsSinceEpoch(0);
      final bStart = b.windowStart ?? DateTime.fromMillisecondsSinceEpoch(0);
      return aStart.compareTo(bStart);
    });

    return merged;
  }

  List<OperationsOnCall> _mergeOnCall(
    List<OperationsOnCall> primary,
    List<OperationsOnCall> fallback,
  ) {
    if (primary.isEmpty) {
      return fallback;
    }

    final fallbackById = {for (final entry in fallback) entry.id: entry};
    final merged = <OperationsOnCall>[];
    for (final entry in primary) {
      merged.add(entry.mergeWith(fallbackById[entry.id]));
    }

    for (final fallbackEntry in fallback) {
      if (primary.where((entry) => entry.id == fallbackEntry.id).isEmpty) {
        merged.add(fallbackEntry);
      }
    }

    return merged;
  }

  List<OperationsAutomation> _mergeAutomations(
    List<OperationsAutomation> primary,
    List<OperationsAutomation> fallback,
  ) {
    if (primary.isEmpty) {
      return fallback;
    }

    final fallbackById = {for (final automation in fallback) automation.id: automation};
    final merged = <OperationsAutomation>[];

    for (final automation in primary) {
      merged.add(automation.mergeWith(fallbackById[automation.id]));
    }

    for (final fallbackAutomation in fallback) {
      if (primary.where((automation) => automation.id == fallbackAutomation.id).isEmpty) {
        merged.add(fallbackAutomation);
      }
    }

    return merged;
  }

  List<OperationsSlo> _mergeSlos(
    List<OperationsSlo> primary,
    List<OperationsSlo> fallback,
  ) {
    if (primary.isEmpty) {
      return fallback;
    }

    final fallbackById = {for (final slo in fallback) slo.id: slo};
    final merged = <OperationsSlo>[];
    for (final slo in primary) {
      merged.add(slo.mergeWith(fallbackById[slo.id]));
    }

    for (final fallbackSlo in fallback) {
      if (primary.where((slo) => slo.id == fallbackSlo.id).isEmpty) {
        merged.add(fallbackSlo);
      }
    }

    return merged;
  }

  List<OperationsSloBreach> _mergeSloBreaches(
    List<OperationsSloBreach> primary,
    List<OperationsSloBreach> fallback,
  ) {
    if (primary.isEmpty) {
      return fallback;
    }

    final fallbackById = {for (final breach in fallback) breach.id: breach};
    final merged = <OperationsSloBreach>[];
    for (final breach in primary) {
      merged.add(breach.mergeWith(fallbackById[breach.id]));
    }

    for (final fallbackBreach in fallback) {
      if (primary.where((breach) => breach.id == fallbackBreach.id).isEmpty) {
        merged.add(fallbackBreach);
      }
    }

    merged.sort((a, b) {
      final statusOrder = {
        OperationsSloBreachStatus.open: 0,
        OperationsSloBreachStatus.acknowledged: 1,
        OperationsSloBreachStatus.resolved: 2,
      };
      final statusComparison = statusOrder[a.status]! - statusOrder[b.status]!;
      if (statusComparison != 0) {
        return statusComparison;
      }
      final aDetected = a.detectedAt ?? DateTime.fromMillisecondsSinceEpoch(0);
      final bDetected = b.detectedAt ?? DateTime.fromMillisecondsSinceEpoch(0);
      return bDetected.compareTo(aDetected);
    });

    return merged;
  }
}
