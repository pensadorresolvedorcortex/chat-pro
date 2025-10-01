import 'package:flutter_riverpod/flutter_riverpod.dart';

import 'apns_token_channel.dart';

final apnsTokenChannelProvider = Provider<ApnsTokenChannel>(
  (ref) => ApnsTokenChannel.instance,
);

final apnsTokenStreamProvider = StreamProvider<String>((ref) async* {
  final channel = ref.watch(apnsTokenChannelProvider);

  final current = await channel.currentToken();
  if (current != null && current.isNotEmpty) {
    yield current;
  }

  yield* channel.tokenStream();
});
