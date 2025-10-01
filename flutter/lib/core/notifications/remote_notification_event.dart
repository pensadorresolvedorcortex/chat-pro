import 'package:flutter/foundation.dart';

import 'notification_presentation_option.dart';

String? notificationParseString(dynamic value) {
  if (value is String) {
    final trimmed = value.trim();
    return trimmed.isEmpty ? null : trimmed;
  }
  return null;
}

int? notificationParseInt(dynamic value) {
  if (value is int) {
    return value;
  }
  if (value is num) {
    return value.toInt();
  }
  if (value is String) {
    return int.tryParse(value.trim());
  }
  return null;
}

double? notificationParseDouble(dynamic value) {
  if (value is double) {
    return value;
  }
  if (value is num) {
    return value.toDouble();
  }
  if (value is String) {
    return double.tryParse(value.trim());
  }
  return null;
}

Map<String, dynamic> notificationParsePayload(dynamic raw) {
  if (raw is Map) {
    return raw.map<String, dynamic>((dynamic key, dynamic value) {
      return MapEntry(key.toString(), value);
    });
  }

  return const <String, dynamic>{};
}

List<String> notificationParsePresentationOptions(dynamic raw) {
  if (raw is List) {
    return raw
        .whereType<String>()
        .map((option) => option.trim())
        .where((option) => option.isNotEmpty)
        .toList(growable: false);
  }

  return const <String>[];
}

DateTime? notificationParseDate(dynamic raw) {
  if (raw is String) {
    return DateTime.tryParse(raw);
  }

  if (raw is int) {
    return DateTime.fromMillisecondsSinceEpoch(
      raw >= 1000000000000 ? raw : raw * 1000,
      isUtc: true,
    ).toLocal();
  }

  if (raw is double) {
    final milliseconds = raw >= 1000000000000
        ? raw.round()
        : (raw * 1000).round();
    return DateTime.fromMillisecondsSinceEpoch(milliseconds, isUtc: true)
        .toLocal();
  }

  return null;
}

class RemoteNotificationAttachment {
  const RemoteNotificationAttachment({
    required this.identifier,
    this.url,
    this.type,
    this.name,
  });

  factory RemoteNotificationAttachment.fromMap(Map<dynamic, dynamic> map) {
    return RemoteNotificationAttachment(
      identifier: notificationParseString(map['identifier']) ?? '',
      url: notificationParseString(map['url']),
      type: notificationParseString(map['type']),
      name: notificationParseString(map['name']),
    );
  }

  static List<RemoteNotificationAttachment> fromDynamicList(dynamic value) {
    if (value is Iterable) {
      return value
          .whereType<Map<dynamic, dynamic>>()
          .map(RemoteNotificationAttachment.fromMap)
          .where((attachment) => attachment.identifier.isNotEmpty)
          .toList(growable: false);
    }

    return const <RemoteNotificationAttachment>[];
  }

  final String identifier;
  final String? url;
  final String? type;
  final String? name;

  @override
  bool operator ==(Object other) {
    if (identical(this, other)) {
      return true;
    }

    return other is RemoteNotificationAttachment &&
        other.identifier == identifier &&
        other.url == url &&
        other.type == type &&
        other.name == name;
  }

  @override
  int get hashCode => Object.hash(identifier, url, type, name);
}

class RemoteNotificationContent {
  const RemoteNotificationContent({
    this.title,
    this.subtitle,
    this.body,
    this.badge,
    this.launchImageName,
    this.threadIdentifier,
    this.summaryArgument,
    this.summaryArgumentCount,
    this.targetContentIdentifier,
    this.attachments = const <RemoteNotificationAttachment>[],
  });

  factory RemoteNotificationContent.fromMap(Map<dynamic, dynamic> map) {
    return RemoteNotificationContent(
      title: notificationParseString(map['title']),
      subtitle: notificationParseString(map['subtitle']),
      body: notificationParseString(map['body']),
      badge: notificationParseInt(map['badge']),
      launchImageName: notificationParseString(map['launchImageName']),
      threadIdentifier: notificationParseString(map['threadIdentifier']),
      summaryArgument: notificationParseString(map['summaryArgument']),
      summaryArgumentCount: notificationParseDouble(map['summaryArgumentCount']),
      targetContentIdentifier: notificationParseString(map['targetContentIdentifier']),
      attachments: RemoteNotificationAttachment.fromDynamicList(map['attachments']),
    );
  }

  static RemoteNotificationContent? maybeFrom(dynamic value) {
    if (value is Map) {
      return RemoteNotificationContent.fromMap(value);
    }
    return null;
  }

  final String? title;
  final String? subtitle;
  final String? body;
  final int? badge;
  final String? launchImageName;
  final String? threadIdentifier;
  final String? summaryArgument;
  final double? summaryArgumentCount;
  final String? targetContentIdentifier;
  final List<RemoteNotificationAttachment> attachments;

  bool get hasTextContent {
    return (title?.isNotEmpty ?? false) ||
        (subtitle?.isNotEmpty ?? false) ||
        (body?.isNotEmpty ?? false);
  }

  bool get hasAttachments => attachments.isNotEmpty;

  @override
  bool operator ==(Object other) {
    if (identical(this, other)) {
      return true;
    }

    return other is RemoteNotificationContent &&
        other.title == title &&
        other.subtitle == subtitle &&
        other.body == body &&
        other.badge == badge &&
        other.launchImageName == launchImageName &&
        other.threadIdentifier == threadIdentifier &&
        other.summaryArgument == summaryArgument &&
        other.summaryArgumentCount == summaryArgumentCount &&
        other.targetContentIdentifier == targetContentIdentifier &&
        listEquals(other.attachments, attachments);
  }

  @override
  int get hashCode => Object.hashAll([
        title,
        subtitle,
        body,
        badge,
        launchImageName,
        threadIdentifier,
        summaryArgument,
        summaryArgumentCount,
        targetContentIdentifier,
        Object.hashAll(attachments),
      ]);
}

class RemoteNotificationEvent {
  const RemoteNotificationEvent({
    required this.payload,
    required this.trigger,
    required this.wasTapped,
    required this.source,
    required this.applicationState,
    this.actionIdentifier,
    this.categoryIdentifier,
    this.presentationOptions = const <String>[],
    this.receivedAt,
    this.content,
    this.userText,
  });

  factory RemoteNotificationEvent.fromMap(Map<dynamic, dynamic> map) {
    final payload = notificationParsePayload(map['userInfo']);
    final trigger = map['trigger'] as String?;
    final wasTapped = map['wasTapped'];
    final source = map['source'] as String?;
    final applicationState = map['applicationState'] as String?;
    final rawReceivedAt = map['receivedAt'];

    final content = RemoteNotificationContent.maybeFrom(map['content']);
    final userText = notificationParseString(map['userText']);

    return RemoteNotificationEvent(
      payload: payload,
      trigger: trigger ?? 'unknown',
      wasTapped: wasTapped is bool ? wasTapped : false,
      source: source ?? 'apns',
      applicationState: applicationState ?? 'unknown',
      actionIdentifier: map['actionIdentifier'] as String?,
      categoryIdentifier: map['categoryIdentifier'] as String?,
      presentationOptions:
          notificationParsePresentationOptions(map['presentationOptions']),
      receivedAt: notificationParseDate(rawReceivedAt),
      content: content,
      userText: userText,
    );
  }

  final Map<String, dynamic> payload;
  final String trigger;
  final bool wasTapped;
  final String source;
  final String applicationState;
  final String? actionIdentifier;
  final String? categoryIdentifier;
  final List<String> presentationOptions;
  final DateTime? receivedAt;
  final RemoteNotificationContent? content;
  final String? userText;

  Set<NotificationPresentationOption> get presentationOptionFlags {
    return NotificationPresentationOption.fromDynamicList(presentationOptions);
  }

  bool get isForegroundEvent => trigger == 'willPresent';
  bool get isBackgroundTap => wasTapped && trigger == 'didReceive';
  bool get launchedApplication => trigger == 'launch';
  bool get deliveredWhileActive => applicationState == 'active';

  RemoteNotificationEvent copyWith({
    Map<String, dynamic>? payload,
    String? trigger,
    bool? wasTapped,
    String? source,
    String? applicationState,
    String? actionIdentifier,
    String? categoryIdentifier,
    List<String>? presentationOptions,
    DateTime? receivedAt,
    RemoteNotificationContent? content,
    String? userText,
  }) {
    return RemoteNotificationEvent(
      payload: payload ?? this.payload,
      trigger: trigger ?? this.trigger,
      wasTapped: wasTapped ?? this.wasTapped,
      source: source ?? this.source,
      applicationState: applicationState ?? this.applicationState,
      actionIdentifier: actionIdentifier ?? this.actionIdentifier,
      categoryIdentifier: categoryIdentifier ?? this.categoryIdentifier,
      presentationOptions: presentationOptions ?? this.presentationOptions,
      receivedAt: receivedAt ?? this.receivedAt,
      content: content ?? this.content,
      userText: userText ?? this.userText,
    );
  }

  @override
  String toString() {
    return 'RemoteNotificationEvent(trigger: $trigger, wasTapped: $wasTapped, '
        'source: $source, applicationState: $applicationState, '
        'receivedAt: $receivedAt, actionIdentifier: $actionIdentifier, '
        'categoryIdentifier: $categoryIdentifier, userText: $userText, '
        'presentationOptions: $presentationOptions, content: $content, '
        'payload: $payload)';
  }

  @override
  bool operator ==(Object other) {
    if (identical(this, other)) {
      return true;
    }

    return other is RemoteNotificationEvent &&
        mapEquals(other.payload, payload) &&
        other.trigger == trigger &&
        other.wasTapped == wasTapped &&
        other.source == source &&
        other.applicationState == applicationState &&
        other.receivedAt == receivedAt &&
        other.actionIdentifier == actionIdentifier &&
        other.categoryIdentifier == categoryIdentifier &&
        other.content == content &&
        other.userText == userText &&
        listEquals(other.presentationOptions, presentationOptions);
  }

  @override
  int get hashCode {
    return Object.hashAll([
      Object.hashAllUnordered(
        payload.entries.map((entry) => Object.hash(entry.key, entry.value)),
      ),
      trigger,
      wasTapped,
      source,
      applicationState,
      receivedAt,
      actionIdentifier,
      categoryIdentifier,
      content,
      userText,
      Object.hashAll(presentationOptions),
    ]);
  }
}
