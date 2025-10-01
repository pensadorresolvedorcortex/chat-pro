class OperationsReadinessSnapshot {
  OperationsReadinessSnapshot({
    required this.timestamp,
    this.baselineTimestamp,
    required this.overall,
    required this.components,
    required this.counts,
    required this.sources,
    required this.milestones,
    required this.alerts,
    required this.incidents,
    required this.maintenanceWindows,
    required this.onCall,
    required this.automations,
    required this.slos,
    required this.sloBreaches,
  });

  factory OperationsReadinessSnapshot.fromJson(Map<String, dynamic> json) {
    final timestampRaw = json['timestamp'] as String?;
    final baselineRaw = json['baselineTimestamp'] as String?;
    final overallJson = json['overall'] as Map<String, dynamic>? ?? const {};
    final componentList = (json['components'] as List?) ?? const [];
    final countsMap = (json['counts'] as Map?) ?? const {};
    final sourcesList = (json['sources'] as List?) ?? const [];

    return OperationsReadinessSnapshot(
      timestamp: _parseDate(timestampRaw) ?? DateTime.now(),
      baselineTimestamp: _parseDate(baselineRaw),
      overall: OperationsReadinessOverall.fromJson(overallJson),
      components: componentList
          .whereType<Map>()
          .map((component) => OperationsReadinessComponent.fromJson(
                Map<String, dynamic>.from(component as Map),
              ))
          .toList(),
      counts: countsMap.map<String, num>((key, value) {
        if (value is num) {
          return MapEntry(key.toString(), value);
        }
        if (value is String) {
          final parsed = num.tryParse(value);
          return MapEntry(key.toString(), parsed ?? 0);
        }
        return MapEntry(key.toString(), 0);
      }),
      sources: sourcesList
          .whereType<Map>()
          .map((item) => OperationsReadinessSource.fromJson(
                Map<String, dynamic>.from(item as Map),
              ))
          .toList(),
      milestones: (json['milestones'] as List?)
              ?.whereType<Map>()
              .map((item) => OperationsReadinessMilestone.fromJson(
                    Map<String, dynamic>.from(item as Map),
                  ))
              .toList() ??
          const [],
      alerts: (json['alerts'] as List?)
              ?.whereType<Map>()
              .map((item) => OperationsReadinessAlert.fromJson(
                    Map<String, dynamic>.from(item as Map),
                  ))
              .where((alert) => alert.active)
              .toList() ??
          const [],
      incidents: (json['incidents'] as List?)
              ?.whereType<Map>()
              .map((item) => OperationsIncident.fromJson(
                    Map<String, dynamic>.from(item as Map),
                  ))
              .toList() ??
          const [],
      maintenanceWindows: (json['maintenanceWindows'] as List?)
              ?.whereType<Map>()
              .map((item) => OperationsMaintenanceWindow.fromJson(
                    Map<String, dynamic>.from(item as Map),
                  ))
              .toList() ??
          const [],
      onCall: (json['onCall'] as List?)
              ?.whereType<Map>()
              .map((item) => OperationsOnCall.fromJson(
                    Map<String, dynamic>.from(item as Map),
                  ))
              .toList() ??
          const [],
      automations: (json['automations'] as List?)
              ?.whereType<Map>()
              .map((item) => OperationsAutomation.fromJson(
                    Map<String, dynamic>.from(item as Map),
                  ))
              .toList() ??
          const [],
      slos: (json['slos'] as List?)
              ?.whereType<Map>()
              .map((item) => OperationsSlo.fromJson(
                    Map<String, dynamic>.from(item as Map),
                  ))
              .toList() ??
          const [],
      sloBreaches: (json['sloBreaches'] as List?)
              ?.whereType<Map>()
              .map((item) => OperationsSloBreach.fromJson(
                    Map<String, dynamic>.from(item as Map),
                  ))
              .toList() ??
          const [],
    );
  }

  final DateTime timestamp;
  final DateTime? baselineTimestamp;
  final OperationsReadinessOverall overall;
  final List<OperationsReadinessComponent> components;
  final Map<String, num> counts;
  final List<OperationsReadinessSource> sources;
  final List<OperationsReadinessMilestone> milestones;
  final List<OperationsReadinessAlert> alerts;
  final List<OperationsIncident> incidents;
  final List<OperationsMaintenanceWindow> maintenanceWindows;
  final List<OperationsOnCall> onCall;
  final List<OperationsAutomation> automations;
  final List<OperationsSlo> slos;
  final List<OperationsSloBreach> sloBreaches;

  OperationsReadinessSnapshot copyWith({
    DateTime? timestamp,
    DateTime? baselineTimestamp,
    OperationsReadinessOverall? overall,
    List<OperationsReadinessComponent>? components,
    Map<String, num>? counts,
    List<OperationsReadinessSource>? sources,
    List<OperationsReadinessMilestone>? milestones,
    List<OperationsReadinessAlert>? alerts,
    List<OperationsIncident>? incidents,
    List<OperationsMaintenanceWindow>? maintenanceWindows,
    List<OperationsOnCall>? onCall,
    List<OperationsAutomation>? automations,
    List<OperationsSlo>? slos,
    List<OperationsSloBreach>? sloBreaches,
  }) {
    return OperationsReadinessSnapshot(
      timestamp: timestamp ?? this.timestamp,
      baselineTimestamp: baselineTimestamp ?? this.baselineTimestamp,
      overall: overall ?? this.overall,
      components: components ?? this.components,
      counts: counts ?? this.counts,
      sources: sources ?? this.sources,
      milestones: milestones ?? this.milestones,
      alerts: alerts ?? this.alerts,
      incidents: incidents ?? this.incidents,
      maintenanceWindows: maintenanceWindows ?? this.maintenanceWindows,
      onCall: onCall ?? this.onCall,
      automations: automations ?? this.automations,
      slos: slos ?? this.slos,
      sloBreaches: sloBreaches ?? this.sloBreaches,
    );
  }

  OperationsReadinessComponent? componentByKey(String key) {
    for (final component in components) {
      if (component.key == key) {
        return component;
      }
    }
    return null;
  }

  int get pendingCount {
    return components.fold<int>(0, (total, component) => total + component.pending.length);
  }

  int get openMilestones {
    return milestones.where((milestone) => !milestone.isDone).length;
  }

  int get activeIncidentCount {
    return incidents.where((incident) => incident.isActive).length;
  }

  int get activeMaintenanceCount {
    return maintenanceWindows
        .where((window) => window.isActive || window.isUpcoming)
        .length;
  }

  static DateTime? _parseDate(String? value) {
    if (value == null || value.isEmpty) {
      return null;
    }
    final parsed = DateTime.tryParse(value);
    return parsed?.toUtc();
  }
}

class OperationsReadinessOverall {
  const OperationsReadinessOverall({
    required this.percentage,
    required this.baseline,
    required this.computed,
    required this.weights,
    required this.notes,
  });

  factory OperationsReadinessOverall.fromJson(Map<String, dynamic> json) {
    final weightsJson = json['weights'] as Map? ?? const {};
    return OperationsReadinessOverall(
      percentage: _parseInt(json['percentage']),
      baseline: _parseInt(json['baseline']),
      computed: _parseInt(json['computed']),
      weights: weightsJson.map<String, double>((key, value) {
        if (value is num) {
          return MapEntry(key.toString(), value.toDouble());
        }
        if (value is String) {
          return MapEntry(key.toString(), double.tryParse(value) ?? 0);
        }
        return MapEntry(key.toString(), 0);
      }),
      notes: (json['notes'] as List?)?.whereType<String>().toList() ?? const [],
    );
  }

  final int percentage;
  final int baseline;
  final int computed;
  final Map<String, double> weights;
  final List<String> notes;

  OperationsReadinessOverall mergeWith(OperationsReadinessOverall fallback) {
    final mergedWeights = <String, double>{...fallback.weights, ...weights};
    final mergedNotes = <String>{...fallback.notes, ...notes}.toList();
    return OperationsReadinessOverall(
      percentage: percentage,
      baseline: baseline != 0 ? baseline : fallback.baseline,
      computed: computed != 0 ? computed : fallback.computed,
      weights: mergedWeights,
      notes: mergedNotes,
    );
  }
}

class OperationsReadinessComponent {
  const OperationsReadinessComponent({
    required this.key,
    this.label,
    required this.percentage,
    required this.baseline,
    required this.computed,
    required this.weight,
    required this.notes,
    required this.nextSteps,
    required this.checks,
    required this.pending,
  });

  factory OperationsReadinessComponent.fromJson(Map<String, dynamic> json) {
    return OperationsReadinessComponent(
      key: json['key']?.toString() ?? 'unknown',
      label: json['label'] as String?,
      percentage: _parseInt(json['percentage']),
      baseline: _parseInt(json['baseline']),
      computed: _parseInt(json['computed']),
      weight: _parseDouble(json['weight']),
      notes: (json['notes'] as List?)?.whereType<String>().toList() ?? const [],
      nextSteps: (json['nextSteps'] as List?)?.whereType<String>().toList() ?? const [],
      checks: (json['checks'] as List?)
              ?.whereType<Map>()
              .map((item) => OperationsReadinessCheck.fromJson(
                    Map<String, dynamic>.from(item as Map),
                  ))
              .toList() ??
          const [],
      pending: (json['pending'] as List?)?.whereType<String>().toList() ?? const [],
    );
  }

  final String key;
  final String? label;
  final int percentage;
  final int baseline;
  final int computed;
  final double weight;
  final List<String> notes;
  final List<String> nextSteps;
  final List<OperationsReadinessCheck> checks;
  final List<String> pending;

  OperationsReadinessComponent mergeWith(OperationsReadinessComponent? fallback) {
    if (fallback == null) {
      return this;
    }
    final mergedNotes = notes.isNotEmpty ? notes : fallback.notes;
    final mergedNextSteps = nextSteps.isNotEmpty ? nextSteps : fallback.nextSteps;
    final mergedChecks = checks.isNotEmpty ? checks : fallback.checks;
    final mergedPending = pending.isNotEmpty ? pending : fallback.pending;

    return OperationsReadinessComponent(
      key: key,
      label: label ?? fallback.label,
      percentage: percentage,
      baseline: baseline != 0 ? baseline : fallback.baseline,
      computed: computed != 0 ? computed : fallback.computed,
      weight: weight != 0 ? weight : fallback.weight,
      notes: mergedNotes,
      nextSteps: mergedNextSteps,
      checks: mergedChecks,
      pending: mergedPending,
    );
  }
}

enum OperationsCheckStatus { done, todo }

class OperationsReadinessCheck {
  const OperationsReadinessCheck({
    required this.key,
    required this.label,
    required this.status,
    this.details,
  });

  factory OperationsReadinessCheck.fromJson(Map<String, dynamic> json) {
    final statusRaw = (json['status'] as String?)?.toLowerCase();
    final status = statusRaw == 'done'
        ? OperationsCheckStatus.done
        : OperationsCheckStatus.todo;
    return OperationsReadinessCheck(
      key: json['key']?.toString() ?? 'unknown',
      label: json['label']?.toString() ?? 'Sem descrição',
      status: status,
      details: json['details'] as String?,
    );
  }

  final String key;
  final String label;
  final OperationsCheckStatus status;
  final String? details;
}

class OperationsReadinessSource {
  const OperationsReadinessSource({
    required this.type,
    required this.value,
    this.description,
  });

  factory OperationsReadinessSource.fromJson(Map<String, dynamic> json) {
    return OperationsReadinessSource(
      type: json['type']?.toString() ?? 'document',
      value: json['value']?.toString() ?? '',
      description: json['description'] as String?,
    );
  }

  final String type;
  final String value;
  final String? description;

  String get identity => '$type::$value';
}

enum OperationsMilestoneStatus { pending, inProgress, done }

class OperationsReadinessMilestone {
  const OperationsReadinessMilestone({
    required this.id,
    required this.title,
    required this.status,
    this.description,
    this.owner,
    this.component,
    this.completion,
    this.targetDate,
    this.completedAt,
    this.blockers = const [],
    this.overdue = false,
  });

  factory OperationsReadinessMilestone.fromJson(Map<String, dynamic> json) {
    final statusRaw = (json['status'] as String?)?.toLowerCase();
    final completionValue = json['completion'];

    return OperationsReadinessMilestone(
      id: json['id']?.toString() ?? 'unknown',
      title: json['title']?.toString() ?? 'Milestone',
      status: _parseMilestoneStatus(statusRaw),
      description: json['description'] as String?,
      owner: json['owner'] as String?,
      component: json['component'] as String?,
      completion: completionValue is num
          ? completionValue.toInt().clamp(0, 100)
          : int.tryParse(completionValue?.toString() ?? '')?.clamp(0, 100),
      targetDate: OperationsReadinessSnapshot._parseDate(
        json['targetDate'] as String?,
      ),
      completedAt: OperationsReadinessSnapshot._parseDate(
        json['completedAt'] as String?,
      ),
      blockers:
          (json['blockers'] as List?)?.whereType<String>().toList() ?? const [],
      overdue: json['overdue'] == true,
    );
  }

  final String id;
  final String title;
  final OperationsMilestoneStatus status;
  final String? description;
  final String? owner;
  final String? component;
  final int? completion;
  final DateTime? targetDate;
  final DateTime? completedAt;
  final List<String> blockers;
  final bool overdue;

  bool get isDone => status == OperationsMilestoneStatus.done;
  bool get isInProgress => status == OperationsMilestoneStatus.inProgress;

  OperationsReadinessMilestone mergeWith(OperationsReadinessMilestone? fallback) {
    if (fallback == null) {
      return this;
    }
    return OperationsReadinessMilestone(
      id: id,
      title: title.isNotEmpty ? title : fallback.title,
      status: status == OperationsMilestoneStatus.pending &&
              fallback.status != OperationsMilestoneStatus.pending
          ? fallback.status
          : status,
      description: description ?? fallback.description,
      owner: owner ?? fallback.owner,
      component: component ?? fallback.component,
      completion: completion ?? fallback.completion,
      targetDate: targetDate ?? fallback.targetDate,
      completedAt: completedAt ?? fallback.completedAt,
      blockers: blockers.isNotEmpty ? blockers : fallback.blockers,
      overdue: overdue || fallback.overdue,
    );
  }
}

enum OperationsAlertLevel { info, warning, critical }

class OperationsReadinessAlert {
  const OperationsReadinessAlert({
    required this.id,
    required this.level,
    required this.message,
    this.details,
    this.component,
    this.actionLabel,
    this.actionUrl,
    this.active = true,
  });

  factory OperationsReadinessAlert.fromJson(Map<String, dynamic> json) {
    final levelRaw = (json['level'] as String?)?.toLowerCase();
    return OperationsReadinessAlert(
      id: json['id']?.toString() ?? 'alert',
      level: _parseAlertLevel(levelRaw),
      message: json['message']?.toString() ?? 'Alerta operacional',
      details: json['details'] as String?,
      component: json['component'] as String?,
      actionLabel: json['actionLabel'] as String?,
      actionUrl: json['actionUrl'] as String?,
      active: json['active'] != false,
    );
  }

  final String id;
  final OperationsAlertLevel level;
  final String message;
  final String? details;
  final String? component;
  final String? actionLabel;
  final String? actionUrl;
  final bool active;

  OperationsReadinessAlert mergeWith(OperationsReadinessAlert? fallback) {
    if (fallback == null) {
      return this;
    }
    return OperationsReadinessAlert(
      id: id,
      level: level,
      message: message.isNotEmpty ? message : fallback.message,
      details: details ?? fallback.details,
      component: component ?? fallback.component,
      actionLabel: actionLabel ?? fallback.actionLabel,
      actionUrl: actionUrl ?? fallback.actionUrl,
      active: active && fallback.active,
    );
  }
}

enum OperationsIncidentStatus { investigating, monitoring, resolved }

enum OperationsIncidentImpact { none, minor, major, critical }

class OperationsIncident {
  const OperationsIncident({
    required this.id,
    required this.title,
    required this.status,
    required this.impact,
    this.component,
    this.summary,
    this.startedAt,
    this.resolvedAt,
    this.updatedAt,
    this.durationMinutes,
    this.actions = const [],
    this.active = false,
  });

  factory OperationsIncident.fromJson(Map<String, dynamic> json) {
    final status = _parseIncidentStatus(json['status'] as String?);
    final impact = _parseIncidentImpact(json['impact'] as String?);
    final startedAt = OperationsReadinessSnapshot._parseDate(json['startedAt'] as String?);
    final resolvedAt = OperationsReadinessSnapshot._parseDate(json['resolvedAt'] as String?);
    final updatedAt = OperationsReadinessSnapshot._parseDate(json['updatedAt'] as String?) ?? resolvedAt ?? startedAt;
    final duration = json['durationMinutes'];

    return OperationsIncident(
      id: json['id']?.toString() ?? 'incident',
      title: (json['title'] as String?)?.isNotEmpty == true ? json['title'] as String : 'Incidente operacional',
      status: status,
      impact: impact,
      component: json['component'] as String?,
      summary: (json['summary'] as String?)?.isNotEmpty == true
          ? json['summary'] as String
          : (json['details'] as String?),
      startedAt: startedAt,
      resolvedAt: resolvedAt,
      updatedAt: updatedAt,
      durationMinutes: duration is num ? duration.toInt() : int.tryParse(duration?.toString() ?? ''),
      actions: (json['actions'] as List?)?.whereType<String>().toList() ?? const [],
      active: json['active'] == true || status != OperationsIncidentStatus.resolved,
    );
  }

  final String id;
  final String title;
  final OperationsIncidentStatus status;
  final OperationsIncidentImpact impact;
  final String? component;
  final String? summary;
  final DateTime? startedAt;
  final DateTime? resolvedAt;
  final DateTime? updatedAt;
  final int? durationMinutes;
  final List<String> actions;
  final bool active;

  bool get isResolved => status == OperationsIncidentStatus.resolved;
  bool get isActive => active && !isResolved;

  OperationsIncident mergeWith(OperationsIncident? fallback) {
    if (fallback == null) {
      return this;
    }

    return OperationsIncident(
      id: id,
      title: title.isNotEmpty ? title : fallback.title,
      status: status,
      impact: impact,
      component: component ?? fallback.component,
      summary: summary ?? fallback.summary,
      startedAt: startedAt ?? fallback.startedAt,
      resolvedAt: resolvedAt ?? fallback.resolvedAt,
      updatedAt: updatedAt ?? fallback.updatedAt,
      durationMinutes: durationMinutes ?? fallback.durationMinutes,
      actions: actions.isNotEmpty ? actions : fallback.actions,
      active: isActive || fallback.isActive,
    );
  }
}

enum OperationsMaintenanceStatus { scheduled, inProgress, completed }

enum OperationsMaintenanceImpact { none, minor, major, critical }

class OperationsMaintenanceWindow {
  const OperationsMaintenanceWindow({
    required this.id,
    required this.title,
    required this.status,
    required this.impact,
    this.windowStart,
    this.windowEnd,
    this.durationMinutes,
    this.description,
    this.notes,
    this.systems = const [],
    this.isActive = false,
    this.isUpcoming = false,
  });

  factory OperationsMaintenanceWindow.fromJson(Map<String, dynamic> json) {
    final status = _parseMaintenanceStatus(json['status'] as String?);
    final impact = _parseMaintenanceImpact(json['impact'] as String?);
    final windowStart = OperationsReadinessSnapshot._parseDate(json['windowStart'] as String?);
    final windowEnd = OperationsReadinessSnapshot._parseDate(json['windowEnd'] as String?);
    final duration = json['durationMinutes'];

    return OperationsMaintenanceWindow(
      id: json['id']?.toString() ?? 'maintenance',
      title: (json['title'] as String?)?.isNotEmpty == true ? json['title'] as String : 'Janela de manutenção',
      status: status,
      impact: impact,
      windowStart: windowStart,
      windowEnd: windowEnd,
      durationMinutes: duration is num ? duration.toInt() : int.tryParse(duration?.toString() ?? ''),
      description: json['description'] as String?,
      notes: json['notes'] as String?,
      systems: (json['systems'] as List?)?.whereType<String>().toList() ?? const [],
      isActive: json['isActive'] == true || status == OperationsMaintenanceStatus.inProgress,
      isUpcoming: json['isUpcoming'] == true || status == OperationsMaintenanceStatus.scheduled,
    );
  }

  final String id;
  final String title;
  final OperationsMaintenanceStatus status;
  final OperationsMaintenanceImpact impact;
  final DateTime? windowStart;
  final DateTime? windowEnd;
  final int? durationMinutes;
  final String? description;
  final String? notes;
  final List<String> systems;
  final bool isActive;
  final bool isUpcoming;

  OperationsMaintenanceWindow mergeWith(OperationsMaintenanceWindow? fallback) {
    if (fallback == null) {
      return this;
    }

    return OperationsMaintenanceWindow(
      id: id,
      title: title.isNotEmpty ? title : fallback.title,
      status: status,
      impact: impact,
      windowStart: windowStart ?? fallback.windowStart,
      windowEnd: windowEnd ?? fallback.windowEnd,
      durationMinutes: durationMinutes ?? fallback.durationMinutes,
      description: description ?? fallback.description,
      notes: notes ?? fallback.notes,
      systems: systems.isNotEmpty ? systems : fallback.systems,
      isActive: isActive || fallback.isActive,
      isUpcoming: isUpcoming || fallback.isUpcoming,
    );
  }
}

OperationsIncidentStatus _parseIncidentStatus(String? value) {
  switch (value) {
    case 'investigating':
      return OperationsIncidentStatus.investigating;
    case 'resolved':
      return OperationsIncidentStatus.resolved;
    case 'monitoring':
    default:
      return OperationsIncidentStatus.monitoring;
  }
}

OperationsIncidentImpact _parseIncidentImpact(String? value) {
  switch (value) {
    case 'critical':
      return OperationsIncidentImpact.critical;
    case 'major':
      return OperationsIncidentImpact.major;
    case 'none':
      return OperationsIncidentImpact.none;
    case 'minor':
    default:
      return OperationsIncidentImpact.minor;
  }
}

OperationsMaintenanceStatus _parseMaintenanceStatus(String? value) {
  switch (value) {
    case 'in_progress':
    case 'in-progress':
      return OperationsMaintenanceStatus.inProgress;
    case 'completed':
      return OperationsMaintenanceStatus.completed;
    case 'scheduled':
    default:
      return OperationsMaintenanceStatus.scheduled;
  }
}

OperationsMaintenanceImpact _parseMaintenanceImpact(String? value) {
  switch (value) {
    case 'critical':
      return OperationsMaintenanceImpact.critical;
    case 'major':
      return OperationsMaintenanceImpact.major;
    case 'none':
      return OperationsMaintenanceImpact.none;
    case 'minor':
    default:
      return OperationsMaintenanceImpact.minor;
  }
}

enum OperationsOnCallStatus { active, standby, offline }

class OperationsOnCall {
  OperationsOnCall({
    required this.id,
    required this.name,
    required this.role,
    required this.contact,
    required this.status,
    this.startedAt,
    this.endsAt,
    this.escalationPolicy,
    this.primary = false,
    this.shiftDurationMinutes,
  });

  factory OperationsOnCall.fromJson(Map<String, dynamic> json) {
    final startedAt = OperationsReadinessSnapshot._parseDate(json['startedAt'] as String?);
    final endsAt = OperationsReadinessSnapshot._parseDate(json['endsAt'] as String?);
    final durationRaw = json['shiftDurationMinutes'];
    final durationMinutes = durationRaw is num
        ? durationRaw.toInt()
        : int.tryParse(durationRaw?.toString() ?? '');
    var status = _parseOnCallStatus(json['status'] as String?);

    if (status == null) {
      final now = DateTime.now().toUtc();
      if (startedAt != null && endsAt != null) {
        if (now.isAfter(startedAt) && now.isBefore(endsAt)) {
          status = OperationsOnCallStatus.active;
        } else if (now.isBefore(startedAt)) {
          status = OperationsOnCallStatus.standby;
        } else {
          status = OperationsOnCallStatus.offline;
        }
      } else {
        status = OperationsOnCallStatus.standby;
      }
    }

    return OperationsOnCall(
      id: json['id']?.toString() ?? 'on_call',
      name: (json['name'] as String?)?.trim() ?? 'Plantonista',
      role: (json['role'] as String?)?.trim() ?? 'On-call',
      contact: (json['contact'] as String?)?.trim() ?? '',
      status: status,
      startedAt: startedAt,
      endsAt: endsAt,
      escalationPolicy: (json['escalationPolicy'] as String?)?.trim(),
      primary: json['primary'] == true,
      shiftDurationMinutes: durationMinutes,
    );
  }

  final String id;
  final String name;
  final String role;
  final String contact;
  final OperationsOnCallStatus status;
  final DateTime? startedAt;
  final DateTime? endsAt;
  final String? escalationPolicy;
  final bool primary;
  final int? shiftDurationMinutes;

  bool get isActive => status == OperationsOnCallStatus.active;
  bool get isStandby => status == OperationsOnCallStatus.standby;

  Duration? get shiftDuration {
    if (shiftDurationMinutes != null) {
      return Duration(minutes: shiftDurationMinutes!);
    }
    if (startedAt != null && endsAt != null) {
      return endsAt!.difference(startedAt!);
    }
    return null;
  }

  OperationsOnCall mergeWith(OperationsOnCall? fallback) {
    if (fallback == null) {
      return this;
    }

    return OperationsOnCall(
      id: id,
      name: name.isNotEmpty ? name : fallback.name,
      role: role.isNotEmpty ? role : fallback.role,
      contact: contact.isNotEmpty ? contact : fallback.contact,
      status: status,
      startedAt: startedAt ?? fallback.startedAt,
      endsAt: endsAt ?? fallback.endsAt,
      escalationPolicy: escalationPolicy ?? fallback.escalationPolicy,
      primary: primary || fallback.primary,
      shiftDurationMinutes: shiftDurationMinutes ?? fallback.shiftDurationMinutes,
    );
  }
}

OperationsOnCallStatus? _parseOnCallStatus(String? value) {
  switch (value) {
    case 'active':
      return OperationsOnCallStatus.active;
    case 'offline':
      return OperationsOnCallStatus.offline;
    case 'standby':
      return OperationsOnCallStatus.standby;
    default:
      return null;
  }
}

enum OperationsAutomationStatus { operational, inProgress, degraded, blocked }

class OperationsAutomation {
  OperationsAutomation({
    required this.id,
    required this.title,
    required this.status,
    required this.owners,
    this.description,
    this.lastRunAt,
    this.nextRunAt,
    this.successRate,
    this.coverage,
    this.signals = const [],
    this.playbooks = const [],
  });

  factory OperationsAutomation.fromJson(Map<String, dynamic> json) {
    final status = _parseAutomationStatus(json['status'] as String?) ??
        OperationsAutomationStatus.operational;
    final owners = _parseStringList(json['owners']);
    final signals = _parseStringList(json['signals']);
    final playbooks = _parseStringList(json['playbooks']);

    final successRateRaw = _parseDoubleNullable(json['successRate']);
    final coverageRaw = _parseDoubleNullable(json['coverage']);

    final successRate = successRateRaw == null
        ? null
        : (successRateRaw > 1 ? (successRateRaw / 100).clamp(0.0, 1.0) : successRateRaw.clamp(0.0, 1.0));
    final coverage = coverageRaw?.clamp(0.0, 100.0);

    return OperationsAutomation(
      id: json['id']?.toString() ?? 'automation',
      title: (json['title'] as String?)?.trim() ?? 'Automação',
      description: (json['description'] as String?)?.trim(),
      status: status,
      owners: owners,
      lastRunAt:
          OperationsReadinessSnapshot._parseDate(json['lastRunAt'] as String?),
      nextRunAt:
          OperationsReadinessSnapshot._parseDate(json['nextRunAt'] as String?),
      successRate: successRate,
      coverage: coverage,
      signals: signals,
      playbooks: playbooks,
    );
  }

  final String id;
  final String title;
  final String? description;
  final OperationsAutomationStatus status;
  final List<String> owners;
  final DateTime? lastRunAt;
  final DateTime? nextRunAt;
  final double? successRate;
  final double? coverage;
  final List<String> signals;
  final List<String> playbooks;

  bool get isOperational => status == OperationsAutomationStatus.operational;
  bool get isBlocked => status == OperationsAutomationStatus.blocked;

  OperationsAutomation mergeWith(OperationsAutomation? fallback) {
    if (fallback == null) {
      return this;
    }

    final mergedStatus =
        _resolveAutomationStatus(status, fallback.status);

    final mergedOwners = owners.isNotEmpty
        ? owners
        : fallback.owners;

    final mergedSignals = {
      ...fallback.signals,
      ...signals,
    }.toList();

    final mergedPlaybooks = {
      ...fallback.playbooks,
      ...playbooks,
    }.toList();

    return OperationsAutomation(
      id: id,
      title: title.isNotEmpty ? title : fallback.title,
      description: description ?? fallback.description,
      status: mergedStatus,
      owners: mergedOwners,
      lastRunAt: lastRunAt ?? fallback.lastRunAt,
      nextRunAt: nextRunAt ?? fallback.nextRunAt,
      successRate: successRate ?? fallback.successRate,
      coverage: coverage ?? fallback.coverage,
      signals: mergedSignals,
      playbooks: mergedPlaybooks,
    );
  }
}

enum OperationsSloStatus { healthy, atRisk, breaching }

enum OperationsSloDirection { above, below }

class OperationsSlo {
  const OperationsSlo({
    required this.id,
    required this.service,
    required this.indicator,
    required this.target,
    required this.current,
    required this.status,
    this.direction = OperationsSloDirection.above,
    this.windowDays,
    this.breaches,
    this.notes = const [],
  });

  factory OperationsSlo.fromJson(Map<String, dynamic> json) {
    final target = _parseDoubleNullable(json['target']) ?? 0;
    final current = _parseDoubleNullable(json['current']) ?? 0;
    final direction = _parseSloDirection(json['direction'] as String?);
    final statusValue = _parseSloStatus(json['status'] as String?);
    final resolvedDirection = direction ??
        (current >= target ? OperationsSloDirection.above : OperationsSloDirection.below);

    final computedStatus = statusValue ?? _computeSloStatus(
      target: target,
      current: current,
      direction: resolvedDirection,
      breaches: json['breaches'],
    );

    return OperationsSlo(
      id: json['id']?.toString() ?? 'slo',
      service: (json['service'] as String?)?.trim() ?? 'Serviço',
      indicator: (json['indicator'] as String?)?.trim() ?? 'Indicador',
      target: target,
      current: current,
      status: computedStatus,
      direction: resolvedDirection,
      windowDays: (json['windowDays'] as num?)?.toInt(),
      breaches: (json['breaches'] as num?)?.toInt(),
      notes: (json['notes'] as List?)?.whereType<String>().toList() ?? const [],
    );
  }

  final String id;
  final String service;
  final String indicator;
  final double target;
  final double current;
  final OperationsSloStatus status;
  final OperationsSloDirection direction;
  final int? windowDays;
  final int? breaches;
  final List<String> notes;

  bool get isBreaching => status == OperationsSloStatus.breaching;
  bool get isAtRisk => status == OperationsSloStatus.atRisk;

  double get attainment {
    if (target == 0) {
      return 0;
    }
    return (current / target) * 100;
  }

  OperationsSlo mergeWith(OperationsSlo? fallback) {
    if (fallback == null) {
      return this;
    }

    return OperationsSlo(
      id: id,
      service: service.isNotEmpty ? service : fallback.service,
      indicator: indicator.isNotEmpty ? indicator : fallback.indicator,
      target: target != 0 ? target : fallback.target,
      current: current != 0 ? current : fallback.current,
      status: status,
      direction: direction,
      windowDays: windowDays ?? fallback.windowDays,
      breaches: breaches ?? fallback.breaches,
      notes: notes.isNotEmpty ? notes : fallback.notes,
    );
  }
}

OperationsSloDirection _parseSloDirection(String? value) {
  switch (value) {
    case 'below':
      return OperationsSloDirection.below;
    case 'above':
    default:
      return OperationsSloDirection.above;
  }
}

OperationsSloStatus? _parseSloStatus(String? value) {
  switch (value) {
    case 'healthy':
      return OperationsSloStatus.healthy;
    case 'at_risk':
    case 'at-risk':
      return OperationsSloStatus.atRisk;
    case 'breaching':
      return OperationsSloStatus.breaching;
    default:
      return null;
  }
}

OperationsSloStatus _computeSloStatus({
  required double target,
  required double current,
  required OperationsSloDirection direction,
  Object? breaches,
}) {
  final breachCount = breaches is num ? breaches.toInt() : 0;

  final meetsTarget = direction == OperationsSloDirection.above
      ? current >= target
      : current <= target;

  if (meetsTarget && breachCount == 0) {
    return OperationsSloStatus.healthy;
  }

  final nearTarget = direction == OperationsSloDirection.above
      ? current >= target * 0.95
      : current <= target * 1.05;

  if (!meetsTarget && breachCount > 0) {
    return OperationsSloStatus.breaching;
  }

  return nearTarget ? OperationsSloStatus.atRisk : OperationsSloStatus.breaching;
}

enum OperationsSloBreachStatus { open, acknowledged, resolved }

enum OperationsSloBreachImpact { none, minor, major, critical }

class OperationsSloBreach {
  const OperationsSloBreach({
    required this.id,
    required this.sloId,
    required this.service,
    required this.indicator,
    required this.status,
    required this.impact,
    this.windowDays,
    this.breachPercentage,
    this.detectedAt,
    this.resolvedAt,
    this.owner,
    this.actions = const [],
    this.open = true,
  });

  factory OperationsSloBreach.fromJson(Map<String, dynamic> json) {
    final status = _parseSloBreachStatus(json['status'] as String?) ??
        (json['resolvedAt'] != null ? OperationsSloBreachStatus.resolved : OperationsSloBreachStatus.open);
    final impact = _parseSloBreachImpact(json['impact'] as String?);
    final detectedAt = OperationsReadinessSnapshot._parseDate(json['detectedAt'] as String?);
    final resolvedAt = OperationsReadinessSnapshot._parseDate(json['resolvedAt'] as String?);
    final windowDays = json['windowDays'];
    final breachPercentage = json['breachPercentage'];
    final owner = (json['owner'] as String?)?.trim();
    final actions = (json['actions'] as List?)
            ?.whereType<String>()
            .map((action) => action.trim())
            .where((action) => action.isNotEmpty)
            .toList() ??
        const <String>[];

    return OperationsSloBreach(
      id: json['id']?.toString() ?? 'breach',
      sloId: json['sloId']?.toString() ?? json['id']?.toString() ?? 'slo',
      service: (json['service'] as String?)?.trim() ?? 'Serviço',
      indicator: (json['indicator'] as String?)?.trim() ?? 'Indicador',
      status: status,
      impact: impact,
      windowDays: windowDays is num ? windowDays.toInt() : int.tryParse(windowDays?.toString() ?? ''),
      breachPercentage: breachPercentage is num
          ? breachPercentage.toDouble()
          : double.tryParse(breachPercentage?.toString() ?? ''),
      detectedAt: detectedAt,
      resolvedAt: resolvedAt,
      owner: owner?.isNotEmpty == true ? owner : null,
      actions: actions,
      open: json['open'] == true || status != OperationsSloBreachStatus.resolved,
    );
  }

  final String id;
  final String sloId;
  final String service;
  final String indicator;
  final OperationsSloBreachStatus status;
  final OperationsSloBreachImpact impact;
  final int? windowDays;
  final double? breachPercentage;
  final DateTime? detectedAt;
  final DateTime? resolvedAt;
  final String? owner;
  final List<String> actions;
  final bool open;

  bool get isResolved => status == OperationsSloBreachStatus.resolved;
  bool get isAcknowledged => status == OperationsSloBreachStatus.acknowledged;

  OperationsSloBreach mergeWith(OperationsSloBreach? fallback) {
    if (fallback == null) {
      return this;
    }

    final mergedActions = actions.isNotEmpty ? actions : fallback.actions;

    return OperationsSloBreach(
      id: id,
      sloId: sloId.isNotEmpty ? sloId : fallback.sloId,
      service: service.isNotEmpty ? service : fallback.service,
      indicator: indicator.isNotEmpty ? indicator : fallback.indicator,
      status: status,
      impact: impact,
      windowDays: windowDays ?? fallback.windowDays,
      breachPercentage: breachPercentage ?? fallback.breachPercentage,
      detectedAt: detectedAt ?? fallback.detectedAt,
      resolvedAt: resolvedAt ?? fallback.resolvedAt,
      owner: owner ?? fallback.owner,
      actions: mergedActions,
      open: open || fallback.open,
    );
  }
}

OperationsSloBreachStatus? _parseSloBreachStatus(String? value) {
  if (value == null) {
    return null;
  }
  switch (value.replaceAll('-', '_')) {
    case 'open':
      return OperationsSloBreachStatus.open;
    case 'acknowledged':
    case 'ack':
    case 'acked':
      return OperationsSloBreachStatus.acknowledged;
    case 'resolved':
    case 'closed':
    case 'fixed':
    case 'done':
      return OperationsSloBreachStatus.resolved;
    default:
      return null;
  }
}

OperationsSloBreachImpact _parseSloBreachImpact(String? value) {
  if (value == null) {
    return OperationsSloBreachImpact.minor;
  }
  switch (value.toLowerCase()) {
    case 'none':
      return OperationsSloBreachImpact.none;
    case 'minor':
    case 'low':
    case 'info':
      return OperationsSloBreachImpact.minor;
    case 'major':
    case 'high':
    case 'severe':
      return OperationsSloBreachImpact.major;
    case 'critical':
      return OperationsSloBreachImpact.critical;
    default:
      return OperationsSloBreachImpact.minor;
  }
}

OperationsAutomationStatus? _parseAutomationStatus(String? value) {
  if (value == null) {
    return null;
  }
  switch (value.toLowerCase().replaceAll('-', '_')) {
    case 'operational':
    case 'healthy':
    case 'ok':
    case 'green':
      return OperationsAutomationStatus.operational;
    case 'in_progress':
    case 'progress':
    case 'running':
      return OperationsAutomationStatus.inProgress;
    case 'degraded':
    case 'warning':
    case 'yellow':
    case 'at_risk':
      return OperationsAutomationStatus.degraded;
    case 'blocked':
    case 'critical':
    case 'down':
    case 'red':
      return OperationsAutomationStatus.blocked;
    default:
      return null;
  }
}

OperationsAutomationStatus _resolveAutomationStatus(
  OperationsAutomationStatus primary,
  OperationsAutomationStatus fallback,
) {
  int score(OperationsAutomationStatus status) {
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

  return score(primary) <= score(fallback) ? primary : fallback;
}

List<String> _parseStringList(Object? value) {
  if (value is String) {
    return value
        .split(',')
        .map((item) => item.trim())
        .where((item) => item.isNotEmpty)
        .toList();
  }
  if (value is List) {
    return value
        .map((item) => item?.toString().trim())
        .whereType<String>()
        .where((item) => item.isNotEmpty)
        .toList();
  }
  return const [];
}

double? _parseDoubleNullable(Object? value) {
  if (value is num) {
    return value.toDouble();
  }
  return double.tryParse(value?.toString() ?? '');
}

OperationsMilestoneStatus _parseMilestoneStatus(String? value) {
  switch (value) {
    case 'done':
      return OperationsMilestoneStatus.done;
    case 'in_progress':
    case 'in-progress':
      return OperationsMilestoneStatus.inProgress;
    default:
      return OperationsMilestoneStatus.pending;
  }
}

OperationsAlertLevel _parseAlertLevel(String? value) {
  switch (value) {
    case 'critical':
      return OperationsAlertLevel.critical;
    case 'warning':
      return OperationsAlertLevel.warning;
    default:
      return OperationsAlertLevel.info;
  }
}

int _parseInt(dynamic value) {
  if (value is int) {
    return value;
  }
  if (value is num) {
    return value.toInt();
  }
  if (value is String) {
    return int.tryParse(value) ?? 0;
  }
  return 0;
}

double _parseDouble(dynamic value) {
  if (value is double) {
    return value;
  }
  if (value is num) {
    return value.toDouble();
  }
  if (value is String) {
    return double.tryParse(value) ?? 0;
  }
  return 0;
}
