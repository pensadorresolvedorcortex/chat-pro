import 'dart:async';

import 'package:flutter/services.dart';

class FcmTokenChannel {
  FcmTokenChannel._();

  static final FcmTokenChannel instance = FcmTokenChannel._();

  static const _eventChannelName = 'academy.flutter/fcm_token/events';
  static const _methodChannelName = 'academy.flutter/fcm_token/methods';

  static const EventChannel _eventChannel = EventChannel(_eventChannelName);
  static const MethodChannel _methodChannel = MethodChannel(_methodChannelName);

  Stream<String> tokenStream() {
    return _eventChannel
        .receiveBroadcastStream()
        .where((event) => event is String && event.isNotEmpty)
        .cast<String>();
  }

  Future<String?> currentToken() {
    return _methodChannel.invokeMethod<String>('getToken');
  }
}
