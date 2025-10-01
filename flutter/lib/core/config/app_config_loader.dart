import 'package:flutter/foundation.dart';

import 'app_config.dart';
import 'app_config_channel.dart';

class AppConfigLoader {
  const AppConfigLoader._();

  static Future<AppConfig> load() async {
    final envConfig = AppConfig.fromEnvironment();

    final isApplePlatform = !kIsWeb &&
        (defaultTargetPlatform == TargetPlatform.iOS ||
            defaultTargetPlatform == TargetPlatform.macOS);

    if (!isApplePlatform) {
      return envConfig;
    }

    final nativeConfigMap = await AppConfigChannel.getConfig();
    if (nativeConfigMap == null || nativeConfigMap.isEmpty) {
      return envConfig;
    }

    final nativeConfig = AppConfig.fromMap(nativeConfigMap);

    final hasEnvOverride =
        envConfig.apiBaseUrl != AppConfig.defaultApiBaseUrl;

    if (hasEnvOverride) {
      return nativeConfig.copyWith(apiBaseUrl: envConfig.apiBaseUrl);
    }

    return nativeConfig;
  }
}
