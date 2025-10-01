import 'package:flutter/foundation.dart';

enum NotificationAuthorizationStatus {
  notDetermined,
  denied,
  authorized,
  provisional,
  ephemeral,
}

extension NotificationAuthorizationStatusX on NotificationAuthorizationStatus {
  String get value {
    switch (this) {
      case NotificationAuthorizationStatus.notDetermined:
        return 'notDetermined';
      case NotificationAuthorizationStatus.denied:
        return 'denied';
      case NotificationAuthorizationStatus.authorized:
        return 'authorized';
      case NotificationAuthorizationStatus.provisional:
        return 'provisional';
      case NotificationAuthorizationStatus.ephemeral:
        return 'ephemeral';
    }
  }

  bool get isGranted {
    switch (this) {
      case NotificationAuthorizationStatus.authorized:
      case NotificationAuthorizationStatus.provisional:
      case NotificationAuthorizationStatus.ephemeral:
        return true;
      case NotificationAuthorizationStatus.notDetermined:
      case NotificationAuthorizationStatus.denied:
        return false;
    }
  }
}

class NotificationAuthorizationStatusParser {
  const NotificationAuthorizationStatusParser._();

  static NotificationAuthorizationStatus parse(String? value) {
    switch (value) {
      case 'authorized':
        return NotificationAuthorizationStatus.authorized;
      case 'denied':
        return NotificationAuthorizationStatus.denied;
      case 'provisional':
        return NotificationAuthorizationStatus.provisional;
      case 'ephemeral':
        return NotificationAuthorizationStatus.ephemeral;
      case 'notDetermined':
      default:
        return NotificationAuthorizationStatus.notDetermined;
    }
  }

  static NotificationAuthorizationStatus defaultForPlatform() {
    if (kIsWeb) {
      return NotificationAuthorizationStatus.authorized;
    }
    switch (defaultTargetPlatform) {
      case TargetPlatform.iOS:
      case TargetPlatform.macOS:
        return NotificationAuthorizationStatus.notDetermined;
      default:
        return NotificationAuthorizationStatus.authorized;
    }
  }
}
