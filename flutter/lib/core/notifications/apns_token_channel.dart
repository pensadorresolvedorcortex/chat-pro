import 'dart:async';

import 'package:flutter/foundation.dart';
import 'package:flutter/services.dart';

class ApnsTokenChannel {
  ApnsTokenChannel._();

  static final ApnsTokenChannel instance = ApnsTokenChannel._();

  static const _eventChannelName = 'academy.flutter/apns_token/events';
  static const _methodChannelName = 'academy.flutter/apns_token/methods';

  static const EventChannel _eventChannel = EventChannel(_eventChannelName);
  static const MethodChannel _methodChannel = MethodChannel(_methodChannelName);

  bool get _supportsNativeChannel {
    if (kIsWeb) {
      return false;
    }

    return defaultTargetPlatform == TargetPlatform.iOS ||
        defaultTargetPlatform == TargetPlatform.macOS;
  }

  Stream<String> tokenStream() {
    if (!_supportsNativeChannel) {
      return const Stream<String>.empty();
    }

    return _eventChannel
        .receiveBroadcastStream()
        .where((event) => event is String && event.isNotEmpty)
        .cast<String>();
  }

  Future<String?> currentToken() async {
    if (!_supportsNativeChannel) {
      return null;
    }

    return _methodChannel.invokeMethod<String>('getToken');
  }
}
