import 'package:flutter/foundation.dart';
import 'package:flutter/services.dart';

import 'notification_authorization_status.dart';

class NotificationPermissionChannel {
  NotificationPermissionChannel._();

  static final NotificationPermissionChannel instance =
      NotificationPermissionChannel._();

  static const _channelName = 'academy.flutter/notifications/methods';
  static const MethodChannel _channel = MethodChannel(_channelName);

  bool get _supportsNativeChannel {
    if (kIsWeb) {
      return false;
    }
    return defaultTargetPlatform == TargetPlatform.iOS ||
        defaultTargetPlatform == TargetPlatform.macOS;
  }

  Future<NotificationAuthorizationStatus>
      getAuthorizationStatus() async {
    if (!_supportsNativeChannel) {
      return NotificationAuthorizationStatusParser.defaultForPlatform();
    }

    final raw = await _invoke<String>('getAuthorizationStatus');
    return NotificationAuthorizationStatusParser.parse(raw);
  }

  Future<NotificationAuthorizationStatus> requestAuthorization({
    bool alert = true,
    bool badge = true,
    bool sound = true,
  }) async {
    if (!_supportsNativeChannel) {
      return NotificationAuthorizationStatusParser.defaultForPlatform();
    }

    final raw = await _invoke<String>(
      'requestAuthorization',
      <String, bool>{
        'alert': alert,
        'badge': badge,
        'sound': sound,
      },
    );

    return NotificationAuthorizationStatusParser.parse(raw);
  }

  Future<bool> openSettings() async {
    if (!_supportsNativeChannel) {
      return false;
    }

    final opened = await _invoke<bool>('openSettings');
    return opened ?? false;
  }

  Future<T?> _invoke<T>(String method, [Object? arguments]) async {
    try {
      return await _channel.invokeMethod<T>(method, arguments);
    } on MissingPluginException {
      return null;
    } on PlatformException catch (error) {
      if (error.code == 'MissingPluginException') {
        return null;
      }
      rethrow;
    }
  }
}
