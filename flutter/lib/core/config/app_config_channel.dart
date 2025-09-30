import 'dart:developer' as developer;

import 'package:flutter/services.dart';

class AppConfigChannel {
  const AppConfigChannel._();

  static const MethodChannel _channel =
      MethodChannel('academy.flutter/config/methods');

  static Future<Map<String, dynamic>?> getConfig() async {
    try {
      final result =
          await _channel.invokeMapMethod<String, dynamic>('getConfig');
      return result;
    } on PlatformException catch (error, stackTrace) {
      developer.log(
        'Failed to load app config from iOS channel',
        name: 'AppConfigChannel',
        error: error,
        stackTrace: stackTrace,
      );
      return null;
    }
  }
}
