import 'package:flutter_riverpod/flutter_riverpod.dart';

import 'remote_notification_channel.dart';
import 'remote_notification_event.dart';

final remoteNotificationStreamProvider = StreamProvider<RemoteNotificationEvent>((ref) {
  return RemoteNotificationChannel.instance.events();
});

final remoteInitialNotificationProvider =
    FutureProvider<RemoteNotificationEvent?>((ref) {
  final channel = RemoteNotificationChannel.instance;
  final cached = channel.cachedInitialNotification;
  if (cached != null) {
    return Future.value(cached);
  }

  return channel.initialNotification();
});
