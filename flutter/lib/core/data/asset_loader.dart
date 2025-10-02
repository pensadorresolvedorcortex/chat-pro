import 'dart:convert';

import 'package:flutter/services.dart';

class AssetLoader {
  const AssetLoader(this.bundle);

  final AssetBundle bundle;

  Future<Map<String, dynamic>> loadJson(String path) async {
    final raw = await bundle.loadString(path);
    final decoded = json.decode(raw);
    if (decoded is Map<String, dynamic>) {
      return decoded;
    }
    throw const FormatException('O asset informado não contém um objeto JSON.');
  }

  Future<List<dynamic>> loadJsonList(String path) async {
    final raw = await bundle.loadString(path);
    final decoded = json.decode(raw);
    if (decoded is List) {
      return decoded;
    }
    throw const FormatException('O asset informado não contém uma lista JSON.');
  }
}
