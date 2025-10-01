import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/network/dio_client.dart';
import '../../../core/operations/operations_channel.dart';
import '../../../core/operations/operations_status.dart';
import 'operations_readiness_repository.dart';

final operationsReadinessRepositoryProvider = Provider<OperationsReadinessRepository>((ref) {
  final dio = ref.watch(dioProvider);
  return OperationsReadinessRepository(dio, OperationsChannel.instance);
});

class OperationsReadinessController
    extends AutoDisposeAsyncNotifier<OperationsReadinessSnapshot> {
  @override
  Future<OperationsReadinessSnapshot> build() {
    final repository = ref.watch(operationsReadinessRepositoryProvider);
    return repository.fetch();
  }

  Future<void> refresh({bool preferNativeChannel = true}) async {
    final repository = ref.read(operationsReadinessRepositoryProvider);
    final previous = state.valueOrNull;

    try {
      final snapshot = await repository.refresh(
        preferNativeChannel: preferNativeChannel,
      );
      state = AsyncValue.data(snapshot);
    } catch (error, stackTrace) {
      if (previous != null) {
        state = AsyncValue.data(previous);
      } else {
        state = AsyncValue.error(error, stackTrace);
      }
      Error.throwWithStackTrace(error, stackTrace);
    }
  }
}

final operationsReadinessControllerProvider = AutoDisposeAsyncNotifierProvider<
    OperationsReadinessController, OperationsReadinessSnapshot>(
  OperationsReadinessController.new,
);

final operationsReadinessProvider = Provider<AsyncValue<OperationsReadinessSnapshot>>((ref) {
  return ref.watch(operationsReadinessControllerProvider);
});
