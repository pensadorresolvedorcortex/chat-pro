import 'dart:convert';

import 'package:hive_flutter/hive_flutter.dart';

import '../domain/pix_charge.dart';

class PixChargeStore {
  PixChargeStore(this._box);

  static const String _boxName = 'pix_charges_v1';

  final Box<String> _box;

  static Future<PixChargeStore> create() async {
    final box = await Hive.openBox<String>(_boxName);
    return PixChargeStore(box);
  }

  Future<void> save(PixCharge charge) async {
    await _box.put(charge.id, jsonEncode(charge.toJson()));
  }

  Future<PixCharge?> get(String id) async {
    final raw = _box.get(id);
    if (raw == null) {
      return null;
    }

    try {
      final decoded = jsonDecode(raw);
      if (decoded is Map<String, dynamic>) {
        return PixCharge.fromJson(decoded);
      }
      if (decoded is Map) {
        return PixCharge.fromJson(
          decoded.map((key, value) => MapEntry(key.toString(), value)),
        );
      }
    } catch (_) {
      await _box.delete(id);
    }

    return null;
  }

  List<PixCharge> _readAll() {
    final charges = <PixCharge>[];
    for (final raw in _box.values) {
      if (raw is! String) {
        continue;
      }
      try {
        final decoded = jsonDecode(raw);
        if (decoded is Map<String, dynamic>) {
          charges.add(PixCharge.fromJson(decoded));
        } else if (decoded is Map) {
          charges.add(
            PixCharge.fromJson(
              decoded.map((key, value) => MapEntry(key.toString(), value)),
            ),
          );
        }
      } catch (_) {
        continue;
      }
    }

    charges.sort((a, b) => b.updatedAt.compareTo(a.updatedAt));
    return charges;
  }

  Stream<List<PixCharge>> watchAll() async* {
    yield _readAll();
    await for (final _ in _box.watch()) {
      yield _readAll();
    }
  }

  Future<void> purge({required DateTime olderThan}) async {
    final keysToDelete = <dynamic>[];
    for (final entry in _box.toMap().entries) {
      final raw = entry.value;
      if (raw is! String) {
        keysToDelete.add(entry.key);
        continue;
      }

      try {
        final decoded = jsonDecode(raw);
        if (decoded is Map<String, dynamic>) {
          final updatedAtString =
              decoded['updatedAt'] ?? decoded['atualizadoEm'] ?? decoded['createdAt'];
          if (updatedAtString is String) {
            final updatedAt = DateTime.tryParse(updatedAtString);
            if (updatedAt != null && updatedAt.isBefore(olderThan)) {
              keysToDelete.add(entry.key);
            }
          }
        }
      } catch (_) {
        keysToDelete.add(entry.key);
      }
    }

    if (keysToDelete.isNotEmpty) {
      await _box.deleteAll(keysToDelete);
    }
  }
}
