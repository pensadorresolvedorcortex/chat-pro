import 'dart:async';

import 'package:flutter/foundation.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/notifications/remote_notification_event.dart';
import '../../../core/notifications/remote_notification_provider.dart';
import '../data/pix_charge_repository.dart';

final pixChargeNotificationListenerProvider = Provider<void>((ref) {
  final repository = ref.watch(pixChargeRepositoryProvider);

  Future<void> handle(RemoteNotificationEvent? event) async {
    if (event == null) {
      return;
    }
    if (event.payload.isEmpty) {
      return;
    }

    final topic = _normaliseTopic(event.payload);
    final category = event.categoryIdentifier ?? '';
    final looksLikePixEvent =
        (topic?.startsWith('pix.') ?? false) || category.startsWith('pix.');

    if (!looksLikePixEvent) {
      final type = event.payload['type'] ?? event.payload['evento'];
      if (type is! String || !type.toLowerCase().startsWith('pix')) {
        return;
      }
    }

    try {
      final envelope = event.payload.map(
        (key, value) => MapEntry(key.toString(), value),
      );
      envelope['trigger'] = event.trigger;
      envelope['wasTapped'] = event.wasTapped;
      envelope['applicationState'] = event.applicationState;
      if (event.actionIdentifier != null) {
        envelope['actionIdentifier'] = event.actionIdentifier;
      }
      if (event.categoryIdentifier != null) {
        envelope['categoryIdentifier'] = event.categoryIdentifier;
      }
      if (event.userText != null) {
        envelope['userText'] = event.userText;
      }
      if (event.receivedAt != null) {
        envelope.putIfAbsent(
          'occurredAt',
          () => event.receivedAt!.toIso8601String(),
        );
      }

      await repository.applyNotificationPayload(envelope);
    } catch (error, stackTrace) {
      debugPrint('Falha ao aplicar atualização Pix da notificação: $error');
      debugPrintStack(stackTrace: stackTrace);
    }
  }

  ref.listen(remoteNotificationStreamProvider, (_, value) {
    value.whenData((event) {
      unawaited(handle(event));
    });
  });

  ref.listen(remoteInitialNotificationProvider, (_, value) {
    value.whenData((event) {
      unawaited(handle(event));
    });
  });
});

String? _normaliseTopic(Map<String, dynamic> payload) {
  const keys = ['topic', 'event', 'evento', 'tipo'];
  for (final key in keys) {
    final value = payload[key];
    if (value is String && value.trim().isNotEmpty) {
      return value.trim().toLowerCase();
    }
  }

  final data = payload['data'];
  if (data is Map) {
    return _normaliseTopic(
      data.map((key, dynamic value) => MapEntry(key.toString(), value)),
    );
  }

  return null;
}
