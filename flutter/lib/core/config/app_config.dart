import 'package:flutter_riverpod/flutter_riverpod.dart';

class AppConfig {
  const AppConfig({
    required this.apiBaseUrl,
  });

  factory AppConfig.fromEnvironment() {
    final candidates = <String?>[
      const String.fromEnvironment('PIX_API_BASE_URL'),
      const String.fromEnvironment('API_BASE_URL'),
    ];

    for (final candidate in candidates) {
      final sanitised = _normaliseBaseUrl(candidate);
      if (sanitised != null) {
        return AppConfig(apiBaseUrl: sanitised);
      }
    }

    return const AppConfig(apiBaseUrl: _defaultApiBaseUrl);
  }

  factory AppConfig.fromMap(Map<String, dynamic> map) {
    return AppConfig(
      apiBaseUrl: _normaliseBaseUrl(map['apiBaseUrl'] as String?) ??
          _defaultApiBaseUrl,
    );
  }

  static const String _defaultApiBaseUrl =
      'https://api.academiadacomunicacao.com/qc/v1';

  final String apiBaseUrl;

  AppConfig copyWith({String? apiBaseUrl}) {
    return AppConfig(
      apiBaseUrl: apiBaseUrl != null
          ? _normaliseBaseUrl(apiBaseUrl) ?? this.apiBaseUrl
          : this.apiBaseUrl,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'apiBaseUrl': apiBaseUrl,
    };
  }

  static String? _normaliseBaseUrl(String? raw) {
    if (raw == null) {
      return null;
    }

    final trimmed = raw.trim();
    if (trimmed.isEmpty) {
      return null;
    }

    final cleaned = trimmed.endsWith('/')
        ? trimmed.substring(0, trimmed.length - 1)
        : trimmed;

    if (cleaned.startsWith('http://') || cleaned.startsWith('https://')) {
      return cleaned;
    }

    return 'https://$cleaned';
  }
}

final appConfigProvider = Provider<AppConfig>((ref) {
  return AppConfig.fromEnvironment();
});
