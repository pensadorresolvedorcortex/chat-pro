import 'dart:async';

import 'dart:developer' as developer;

import 'package:flutter/foundation.dart';
import 'package:flutter/services.dart';

import 'notification_category.dart';
import 'notification_presentation_option.dart';
import 'notification_snapshots.dart';
import 'remote_notification_event.dart';

class RemoteNotificationChannel {
  RemoteNotificationChannel._();

  static final RemoteNotificationChannel instance = RemoteNotificationChannel._();

  static const _eventChannelName = 'academy.flutter/notifications/events';
  static const EventChannel _eventChannel = EventChannel(_eventChannelName);
  static const MethodChannel _methodChannel =
      MethodChannel('academy.flutter/notifications/methods');

  static const Set<NotificationPresentationOption>
      _defaultForegroundPresentationOptions = {
    NotificationPresentationOption.alert,
    NotificationPresentationOption.sound,
    NotificationPresentationOption.badge,
    NotificationPresentationOption.banner,
    NotificationPresentationOption.list,
  };

  Future<RemoteNotificationEvent?>? _initialNotificationFuture;
  RemoteNotificationEvent? _cachedInitialNotification;

  RemoteNotificationEvent? get cachedInitialNotification =>
      _cachedInitialNotification;

  bool get _supportsNativeChannel {
    if (kIsWeb) {
      return false;
    }

    return defaultTargetPlatform == TargetPlatform.iOS ||
        defaultTargetPlatform == TargetPlatform.macOS;
  }

  Stream<RemoteNotificationEvent> events({bool includeInitialEvent = true}) {
    if (!_supportsNativeChannel) {
      return const Stream<RemoteNotificationEvent>.empty();
    }

    StreamSubscription<dynamic>? subscription;
    late StreamController<RemoteNotificationEvent> controller;

    controller = StreamController<RemoteNotificationEvent>.broadcast(
      onListen: () {
        () async {
          if (includeInitialEvent) {
            final initial = await _loadInitialNotification();
            if (initial != null && !controller.isClosed) {
              controller.add(initial);
            }
          } else {
            await _loadInitialNotification();
          }

          subscription = _eventChannel.receiveBroadcastStream().listen(
            (dynamic event) {
              final parsed = _parseEvent(event);
              if (parsed != null) {
                controller.add(parsed);
              }
            },
            onError: controller.addError,
            onDone: () {
              if (!controller.isClosed) {
                controller.close();
              }
            },
          );
        }();
      },
      onCancel: () async {
        if (!controller.hasListener) {
          await subscription?.cancel();
          subscription = null;
        }
      },
    );

    return controller.stream;
  }

  Future<RemoteNotificationEvent?> initialNotification() async {
    if (!_supportsNativeChannel) {
      return null;
    }

    return _loadInitialNotification();
  }

  Future<RemoteNotificationEvent?> _loadInitialNotification() {
    if (_initialNotificationFuture != null) {
      return _initialNotificationFuture!;
    }

    final future = _fetchInitialNotification();
    _initialNotificationFuture = future;
    return future;
  }

  Future<RemoteNotificationEvent?> _fetchInitialNotification() async {
    try {
      final result =
          await _methodChannel.invokeMethod<dynamic>('consumeInitialNotification');
      if (result is Map<dynamic, dynamic>) {
        final event = RemoteNotificationEvent.fromMap(result);
        _cachedInitialNotification = event;
        return event;
      }
      _cachedInitialNotification = null;
      return null;
    } on PlatformException catch (error, stackTrace) {
      developer.log(
        'Failed to consume initial notification',
        name: 'RemoteNotificationChannel',
        error: error,
        stackTrace: stackTrace,
      );
      return null;
    }
  }

  Future<void> setCategories(List<NotificationCategory> categories) async {
    if (!_supportsNativeChannel) {
      return;
    }

    try {
      await _methodChannel.invokeMethod<void>('setCategories', {
        'categories': categories
            .map((category) => category.toMap())
            .toList(growable: false),
      });
    } on PlatformException catch (error, stackTrace) {
      developer.log(
        'Failed to configure notification categories',
        name: 'RemoteNotificationChannel',
        error: error,
        stackTrace: stackTrace,
      );
    }
  }

  Future<void> clearCategories() {
    return setCategories(const <NotificationCategory>[]);
  }

  Future<void> setForegroundPresentationOptions(
    Set<NotificationPresentationOption> options,
  ) async {
    if (!_supportsNativeChannel) {
      return;
    }

    try {
      await _methodChannel.invokeMethod<void>('setForegroundPresentationOptions', {
        'options': NotificationPresentationOption.toWireList(options),
      });
    } on PlatformException catch (error, stackTrace) {
      developer.log(
        'Failed to configure foreground presentation options',
        name: 'RemoteNotificationChannel',
        error: error,
        stackTrace: stackTrace,
      );
    }
  }

  Future<void> resetForegroundPresentationOptions() async {
    if (!_supportsNativeChannel) {
      return;
    }

    try {
      await _methodChannel.invokeMethod<void>('resetForegroundPresentationOptions');
    } on PlatformException catch (error, stackTrace) {
      developer.log(
        'Failed to reset foreground presentation options',
        name: 'RemoteNotificationChannel',
        error: error,
        stackTrace: stackTrace,
      );
    }
  }

  Future<List<DeliveredNotificationSnapshot>> deliveredNotifications() async {
    if (!_supportsNativeChannel) {
      return const <DeliveredNotificationSnapshot>[];
    }

    final raw = await _methodChannel.invokeMethod<dynamic>(
      'listDeliveredNotifications',
    );
    return DeliveredNotificationSnapshot.listFrom(raw);
  }

  Future<List<PendingNotificationRequestSnapshot>>
      pendingNotificationRequests() async {
    if (!_supportsNativeChannel) {
      return const <PendingNotificationRequestSnapshot>[];
    }

    final raw = await _methodChannel.invokeMethod<dynamic>(
      'listPendingNotificationRequests',
    );
    return PendingNotificationRequestSnapshot.listFrom(raw);
  }

  Future<void> removeDeliveredNotifications(
    Iterable<String> identifiers,
  ) async {
    if (!_supportsNativeChannel) {
      return;
    }

    final sanitized = _sanitizeIdentifierList(identifiers);
    if (sanitized.isEmpty) {
      return;
    }

    await _methodChannel.invokeMethod<void>(
      'removeDeliveredNotifications',
      <String, dynamic>{'identifiers': sanitized},
    );
  }

  Future<void> removeAllDeliveredNotifications() async {
    if (!_supportsNativeChannel) {
      return;
    }

    await _methodChannel.invokeMethod<void>('removeAllDeliveredNotifications');
  }

  Future<void> removePendingNotificationRequests(
    Iterable<String> identifiers,
  ) async {
    if (!_supportsNativeChannel) {
      return;
    }

    final sanitized = _sanitizeIdentifierList(identifiers);
    if (sanitized.isEmpty) {
      return;
    }

    await _methodChannel.invokeMethod<void>(
      'removePendingNotificationRequests',
      <String, dynamic>{'identifiers': sanitized},
    );
  }

  Future<int> badgeCount() async {
    if (!_supportsNativeChannel) {
      return 0;
    }

    final result = await _methodChannel.invokeMethod<dynamic>('getBadgeCount');
    return _parseBadgeResult(result);
  }

  Future<int> setBadgeCount(int count) async {
    if (!_supportsNativeChannel) {
      return 0;
    }

    final result = await _methodChannel.invokeMethod<dynamic>(
      'setBadgeCount',
      <String, dynamic>{'badge': count},
    );
    return _parseBadgeResult(result);
  }

  Future<int> incrementBadgeCount([int delta = 1]) async {
    if (!_supportsNativeChannel) {
      return delta < 0 ? 0 : delta;
    }

    final result = await _methodChannel.invokeMethod<dynamic>(
      'incrementBadgeCount',
      <String, dynamic>{'badge': delta},
    );
    return _parseBadgeResult(result);
  }

  Future<void> clearBadgeCount() async {
    if (!_supportsNativeChannel) {
      return;
    }

    await _methodChannel.invokeMethod<void>('clearBadgeCount');
  }

  Future<Set<NotificationPresentationOption>> foregroundPresentationOptions() async {
    if (!_supportsNativeChannel) {
      return _defaultForegroundPresentationOptions;
    }

    try {
      final dynamic result =
          await _methodChannel.invokeMethod<dynamic>('getForegroundPresentationOptions');
      return NotificationPresentationOption.fromDynamicList(result);
    } on PlatformException catch (error, stackTrace) {
      developer.log(
        'Failed to obtain foreground presentation options',
        name: 'RemoteNotificationChannel',
        error: error,
        stackTrace: stackTrace,
      );
      return _defaultForegroundPresentationOptions;
    }
  }

  RemoteNotificationEvent? _parseEvent(dynamic event) {
    if (event is Map<dynamic, dynamic>) {
      final parsed = RemoteNotificationEvent.fromMap(event);
      if (parsed.trigger == 'launch' && _cachedInitialNotification != null) {
        _cachedInitialNotification = parsed;
      }
      return parsed;
    }
    return null;
  }
}

List<String> _sanitizeIdentifierList(Iterable<String> identifiers) {
  return identifiers
      .map((identifier) => identifier.trim())
      .where((identifier) => identifier.isNotEmpty)
      .toSet()
      .toList(growable: false);
}

int _parseBadgeResult(dynamic raw) {
  if (raw is int) {
    return raw;
  }
  if (raw is num) {
    return raw.toInt();
  }
  if (raw is String) {
    return int.tryParse(raw) ?? 0;
  }
  return 0;
}
