import 'package:shared_preferences/shared_preferences.dart';

class AuthTokenStorage {
  const AuthTokenStorage();

  static const _tokenKey = 'auth.token';

  Future<String?> readToken() async {
    final prefs = await SharedPreferences.getInstance();
    final value = prefs.getString(_tokenKey);
    if (value == null || value.trim().isEmpty) {
      return null;
    }
    return value;
  }

  Future<void> writeToken(String? token) async {
    final prefs = await SharedPreferences.getInstance();
    final normalized = token?.trim();
    if (normalized == null || normalized.isEmpty) {
      await prefs.remove(_tokenKey);
      return;
    }
    await prefs.setString(_tokenKey, normalized);
  }

  Future<void> clear() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_tokenKey);
  }
}
