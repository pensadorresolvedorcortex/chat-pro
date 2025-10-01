import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:hive_flutter/hive_flutter.dart';

import 'app.dart';
import 'core/config/app_config.dart';
import 'core/config/app_config_loader.dart';
import 'core/notifications/notification_category.dart';
import 'core/notifications/notification_presentation_option.dart';
import 'core/notifications/notification_permission_channel.dart';
import 'core/notifications/remote_notification_channel.dart';
import 'features/paywall/data/pix_charge_repository.dart';
import 'features/paywall/data/pix_charge_store.dart';
import 'features/paywall/data/plan_cache_store.dart';
import 'features/paywall/data/plan_repository.dart';

Future<void> _firebaseMessagingBackgroundHandler(RemoteMessage message) async {
  await Firebase.initializeApp();
}

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await SystemChrome.setPreferredOrientations([DeviceOrientation.portraitUp]);

  await Hive.initFlutter();
  await Firebase.initializeApp();
  FirebaseMessaging.onBackgroundMessage(_firebaseMessagingBackgroundHandler);
  final messaging = FirebaseMessaging.instance;
  final isApplePlatform = !kIsWeb &&
      (defaultTargetPlatform == TargetPlatform.iOS ||
          defaultTargetPlatform == TargetPlatform.macOS);

  if (isApplePlatform) {
    await NotificationPermissionChannel.instance.requestAuthorization(
      alert: true,
      badge: true,
      sound: true,
    );
  }
  await messaging.requestPermission(
    alert: true,
    badge: true,
    sound: true,
    provisional: false,
  );
  await messaging.setForegroundNotificationPresentationOptions(
    alert: true,
    badge: true,
    sound: true,
  );
  await messaging.setAutoInitEnabled(true);

  if (isApplePlatform) {
    await RemoteNotificationChannel.instance.setForegroundPresentationOptions(
      const {
        NotificationPresentationOption.alert,
        NotificationPresentationOption.badge,
        NotificationPresentationOption.sound,
        NotificationPresentationOption.banner,
        NotificationPresentationOption.list,
      },
    );
    await RemoteNotificationChannel.instance.setCategories([
      NotificationCategory(
        identifier: 'pix.charge',
        actions: [
          NotificationAction(
            identifier: 'pix.copy',
            title: 'Copiar código',
            options: const {
              NotificationActionOption.foreground,
            },
          ),
          NotificationAction(
            identifier: 'pix.markPaid',
            title: 'Marcar como pago',
            options: const {
              NotificationActionOption.foreground,
              NotificationActionOption.authenticationRequired,
            },
          ),
          NotificationAction(
            identifier: 'pix.dismiss',
            title: 'Ignorar cobrança',
            options: const {
              NotificationActionOption.destructive,
            },
          ),
        ],
        options: const {
          NotificationCategoryOption.customDismissAction,
        },
        hiddenPreviewsBodyPlaceholder: 'Cobrança Pix disponível',
        categorySummaryFormat: '%u cobranças Pix',
      ),
      NotificationCategory(
        identifier: 'pix.support',
        actions: [
          NotificationAction(
            identifier: 'pix.replySupport',
            title: 'Responder suporte',
            options: const {
              NotificationActionOption.foreground,
            },
            textInput: const NotificationTextInputConfiguration(
              buttonTitle: 'Enviar',
              placeholder: 'Escreva sua mensagem...',
            ),
          ),
        ],
        options: const {
          NotificationCategoryOption.allowAnnouncement,
        },
      ),
    ]);

    await RemoteNotificationChannel.instance.clearBadgeCount();
  }

  final appConfig = await AppConfigLoader.load();
  final pixChargeStore = await PixChargeStore.create();
  await pixChargeStore.purge(
    olderThan: DateTime.now().subtract(const Duration(days: 15)),
  );
  final planCacheStore = await PlanCacheStore.create();

  runApp(
    ProviderScope(
      overrides: [
        appConfigProvider.overrideWithValue(appConfig),
        pixChargeStoreProvider.overrideWithValue(pixChargeStore),
        planCacheStoreProvider.overrideWithValue(planCacheStore),
      ],
      child: const AcademiaApp(),
    ),
  );
}
