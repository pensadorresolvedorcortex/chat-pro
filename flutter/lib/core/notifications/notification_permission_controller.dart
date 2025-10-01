import 'package:flutter_riverpod/flutter_riverpod.dart';

import 'notification_authorization_status.dart';
import 'notification_permission_channel.dart';

final notificationPermissionChannelProvider = Provider<
    NotificationPermissionChannel>((ref) => NotificationPermissionChannel.instance);

final notificationPermissionControllerProvider = StateNotifierProvider<
    NotificationPermissionController,
    AsyncValue<NotificationAuthorizationStatus>>((ref) {
  final channel = ref.watch(notificationPermissionChannelProvider);
  return NotificationPermissionController(channel)..refresh();
});

class NotificationPermissionController
    extends StateNotifier<AsyncValue<NotificationAuthorizationStatus>> {
  NotificationPermissionController(this._channel)
      : super(const AsyncValue.loading());

  final NotificationPermissionChannel _channel;

  Future<void> refresh() async {
    state = const AsyncValue.loading();
    try {
      final status = await _channel.getAuthorizationStatus();
      state = AsyncValue.data(status);
    } catch (error, stackTrace) {
      state = AsyncValue.error(error, stackTrace);
    }
  }

  Future<void> requestAuthorization({
    bool alert = true,
    bool badge = true,
    bool sound = true,
  }) async {
    state = const AsyncValue.loading();
    try {
      final status = await _channel.requestAuthorization(
        alert: alert,
        badge: badge,
        sound: sound,
      );
      state = AsyncValue.data(status);
    } catch (error, stackTrace) {
      state = AsyncValue.error(error, stackTrace);
    }
  }

  Future<bool> openSettings() {
    return _channel.openSettings();
  }
}
