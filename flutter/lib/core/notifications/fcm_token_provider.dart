import 'package:flutter_riverpod/flutter_riverpod.dart';

import 'fcm_token_channel.dart';

final fcmTokenStreamProvider = StreamProvider<String>((ref) async* {
  final channel = FcmTokenChannel.instance;

  final current = await channel.currentToken();
  if (current != null && current.isNotEmpty) {
    yield current;
  }

  yield* channel.tokenStream();
});
