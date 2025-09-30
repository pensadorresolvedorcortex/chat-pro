import 'package:flutter_riverpod/flutter_riverpod.dart';

import 'auth_token_storage.dart';

final authTokenStorageProvider = Provider<AuthTokenStorage>((ref) {
  return const AuthTokenStorage();
});

final authTokenStateProvider =
    StateNotifierProvider<AuthTokenController, AsyncValue<String?>>((ref) {
  final storage = ref.watch(authTokenStorageProvider);
  return AuthTokenController(storage);
});

final authTokenProvider = Provider<String?>((ref) {
  final tokenState = ref.watch(authTokenStateProvider);
  return tokenState.valueOrNull;
});

class AuthTokenController extends StateNotifier<AsyncValue<String?>> {
  AuthTokenController(this._storage) : super(const AsyncValue.loading()) {
    _load();
  }

  final AuthTokenStorage _storage;

  Future<void> _load() async {
    try {
      final token = await _storage.readToken();
      if (!mounted) {
        return;
      }
      state = AsyncValue.data(token);
    } catch (error, stackTrace) {
      state = AsyncValue.error(error, stackTrace);
    }
  }

  Future<void> refresh() => _load();

  Future<void> setToken(String? token) async {
    final normalized = token?.trim();
    try {
      await _storage.writeToken(normalized);
      if (!mounted) {
        return;
      }
      state = AsyncValue.data(
        normalized != null && normalized.isNotEmpty ? normalized : null,
      );
    } catch (error, stackTrace) {
      state = AsyncValue.error(error, stackTrace);
    }
  }

  Future<void> clear() => setToken(null);
}
