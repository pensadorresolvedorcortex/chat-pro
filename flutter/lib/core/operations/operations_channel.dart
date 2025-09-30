import 'package:flutter/services.dart';

class OperationsChannel {
  OperationsChannel._();

  static const MethodChannel _channel = MethodChannel('academy.flutter/operations/methods');

  static final OperationsChannel instance = OperationsChannel._();

  Future<Map<String, dynamic>?> fetchBundledStatus() async {
    try {
      final dynamic result = await _channel.invokeMethod<dynamic>('fetchStatus');
      if (result is Map) {
        return result.map((key, value) => MapEntry(key.toString(), value));
      }
    } on PlatformException {
      return null;
    }
    return null;
  }

  Future<Map<String, dynamic>?> refreshStatus() async {
    try {
      final dynamic result = await _channel.invokeMethod<dynamic>('refreshStatus');
      if (result is Map) {
        return result.map((key, value) => MapEntry(key.toString(), value));
      }
    } on PlatformException {
      return null;
    }
    return null;
  }
}
