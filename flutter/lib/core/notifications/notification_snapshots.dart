import 'package:flutter/foundation.dart';

import 'remote_notification_event.dart';

class DeliveredNotificationSnapshot {
  const DeliveredNotificationSnapshot({
    required this.identifier,
    required this.trigger,
    required this.source,
    required this.payload,
    this.deliveredAt,
    this.categoryIdentifier,
    this.content,
  });

  factory DeliveredNotificationSnapshot.fromMap(Map<dynamic, dynamic> map) {
    final identifier = notificationParseString(map['identifier']) ?? '';
    return DeliveredNotificationSnapshot(
      identifier: identifier,
      trigger: notificationParseString(map['trigger']) ?? 'unknown',
      source: notificationParseString(map['source']) ?? 'apns',
      payload: notificationParsePayload(map['userInfo']),
      deliveredAt: notificationParseDate(map['deliveredAt']),
      categoryIdentifier: notificationParseString(map['categoryIdentifier']),
      content: RemoteNotificationContent.maybeFrom(map['content']),
    );
  }

  static List<DeliveredNotificationSnapshot> listFrom(dynamic raw) {
    if (raw is Iterable) {
      return raw
          .whereType<Map<dynamic, dynamic>>()
          .map(DeliveredNotificationSnapshot.fromMap)
          .where((notification) => notification.identifier.isNotEmpty)
          .toList(growable: false);
    }

    return const <DeliveredNotificationSnapshot>[];
  }

  final String identifier;
  final String trigger;
  final String source;
  final Map<String, dynamic> payload;
  final DateTime? deliveredAt;
  final String? categoryIdentifier;
  final RemoteNotificationContent? content;

  bool get hasPayload => payload.isNotEmpty;
  bool get hasContent => content != null;

  @override
  bool operator ==(Object other) {
    if (identical(this, other)) {
      return true;
    }

    return other is DeliveredNotificationSnapshot &&
        other.identifier == identifier &&
        other.trigger == trigger &&
        other.source == source &&
        other.categoryIdentifier == categoryIdentifier &&
        other.deliveredAt == deliveredAt &&
        other.content == content &&
        mapEquals(other.payload, payload);
  }

  @override
  int get hashCode => Object.hash(
        identifier,
        trigger,
        source,
        categoryIdentifier,
        deliveredAt,
        content,
        Object.hashAllUnordered(
          payload.entries.map((entry) => Object.hash(entry.key, entry.value)),
        ),
      );
}

class PendingNotificationRequestSnapshot {
  const PendingNotificationRequestSnapshot({
    required this.identifier,
    required this.trigger,
    required this.source,
    required this.payload,
    required this.repeats,
    this.nextTriggerDate,
    this.categoryIdentifier,
    this.content,
  });

  factory PendingNotificationRequestSnapshot.fromMap(
    Map<dynamic, dynamic> map,
  ) {
    final identifier = notificationParseString(map['identifier']) ?? '';
    return PendingNotificationRequestSnapshot(
      identifier: identifier,
      trigger: notificationParseString(map['trigger']) ?? 'unknown',
      source: notificationParseString(map['source']) ?? 'local',
      payload: notificationParsePayload(map['userInfo']),
      repeats: map['repeats'] == true,
      nextTriggerDate: notificationParseDate(map['nextTriggerDate']),
      categoryIdentifier: notificationParseString(map['categoryIdentifier']),
      content: RemoteNotificationContent.maybeFrom(map['content']),
    );
  }

  static List<PendingNotificationRequestSnapshot> listFrom(dynamic raw) {
    if (raw is Iterable) {
      return raw
          .whereType<Map<dynamic, dynamic>>()
          .map(PendingNotificationRequestSnapshot.fromMap)
          .where((notification) => notification.identifier.isNotEmpty)
          .toList(growable: false);
    }

    return const <PendingNotificationRequestSnapshot>[];
  }

  final String identifier;
  final String trigger;
  final String source;
  final Map<String, dynamic> payload;
  final bool repeats;
  final DateTime? nextTriggerDate;
  final String? categoryIdentifier;
  final RemoteNotificationContent? content;

  bool get hasPayload => payload.isNotEmpty;
  bool get hasContent => content != null;

  @override
  bool operator ==(Object other) {
    if (identical(this, other)) {
      return true;
    }

    return other is PendingNotificationRequestSnapshot &&
        other.identifier == identifier &&
        other.trigger == trigger &&
        other.source == source &&
        other.repeats == repeats &&
        other.nextTriggerDate == nextTriggerDate &&
        other.categoryIdentifier == categoryIdentifier &&
        other.content == content &&
        mapEquals(other.payload, payload);
  }

  @override
  int get hashCode => Object.hash(
        identifier,
        trigger,
        source,
        repeats,
        nextTriggerDate,
        categoryIdentifier,
        content,
        Object.hashAllUnordered(
          payload.entries.map((entry) => Object.hash(entry.key, entry.value)),
        ),
      );
}
