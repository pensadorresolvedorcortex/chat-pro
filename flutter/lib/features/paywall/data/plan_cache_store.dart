import 'dart:convert';

import 'package:flutter/foundation.dart';
import 'package:hive_flutter/hive_flutter.dart';

import '../domain/plan.dart';

class PlanCacheSnapshot {
  const PlanCacheSnapshot({
    required this.plans,
    this.fetchedAt,
  });

  final List<Plan> plans;
  final DateTime? fetchedAt;
}

class PlanCacheStore {
  PlanCacheStore(this._box);

  static const String _boxName = 'plan_cache_v1';
  static const String _cacheKey = 'latest';

  final Box<String> _box;

  static Future<PlanCacheStore> create() async {
    final box = await Hive.openBox<String>(_boxName);
    return PlanCacheStore(box);
  }

  Future<void> save(List<Plan> plans, {DateTime? fetchedAt}) async {
    if (plans.isEmpty) {
      await _box.delete(_cacheKey);
      return;
    }

    final snapshot = <String, dynamic>{
      'fetchedAt': (fetchedAt ?? DateTime.now()).toIso8601String(),
      'plans': plans.map((plan) => plan.toJson()).toList(),
    };

    await _box.put(_cacheKey, jsonEncode(snapshot));
  }

  Future<PlanCacheSnapshot?> read() async {
    final raw = _box.get(_cacheKey);
    if (raw == null) {
      return null;
    }

    try {
      final decoded = jsonDecode(raw);
      if (decoded is! Map<String, dynamic>) {
        throw const FormatException('Payload inválido para cache de planos.');
      }

      final fetchedAtString = decoded['fetchedAt'];
      DateTime? fetchedAt;
      if (fetchedAtString is String) {
        fetchedAt = DateTime.tryParse(fetchedAtString);
      }

      final plansRaw = decoded['plans'];
      if (plansRaw is! List) {
        throw const FormatException('Lista de planos ausente no cache.');
      }

      final plans = <Plan>[];
      for (final entry in plansRaw) {
        if (entry is Map<String, dynamic>) {
          plans.add(Plan.fromJson(entry));
        } else if (entry is Map) {
          plans.add(
            Plan.fromJson(
              entry.map((key, value) => MapEntry(key.toString(), value)),
            ),
          );
        }
      }

      if (plans.isEmpty) {
        return null;
      }

      return PlanCacheSnapshot(
        plans: plans,
        fetchedAt: fetchedAt,
      );
    } catch (error, stackTrace) {
      debugPrint('Falha ao ler cache de planos: $error');
      debugPrintStack(stackTrace: stackTrace);
      await _box.delete(_cacheKey);
      return null;
    }
  }

  Future<void> clear() async {
    await _box.delete(_cacheKey);
  }
}
