import 'dart:convert';

import 'package:dio/dio.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/network/dio_client.dart';
import '../domain/plan.dart';
import 'plan_cache_store.dart';

abstract class PlanRepository {
  Future<PlanFetchResult> fetchPlans();
}

enum PlanDataSource { remote, cached, local }

class PlanFetchResult {
  const PlanFetchResult._({
    required this.plans,
    required this.dataSource,
    required this.isFallback,
    this.fallbackReason,
    this.fetchedAt,
  });

  factory PlanFetchResult.remote(
    List<Plan> plans, {
    DateTime? fetchedAt,
  }) =>
      PlanFetchResult._(
        plans: plans,
        dataSource: PlanDataSource.remote,
        isFallback: false,
        fetchedAt: fetchedAt,
      );

  factory PlanFetchResult.cached(
    List<Plan> plans, {
    DateTime? fetchedAt,
  }) =>
      PlanFetchResult._(
        plans: plans,
        dataSource: PlanDataSource.cached,
        isFallback: false,
        fetchedAt: fetchedAt,
      );

  factory PlanFetchResult.local(
    List<Plan> plans, {
    DateTime? fetchedAt,
  }) =>
      PlanFetchResult._(
        plans: plans,
        dataSource: PlanDataSource.local,
        isFallback: false,
        fetchedAt: fetchedAt,
      );

  final List<Plan> plans;
  final PlanDataSource dataSource;
  final bool isFallback;
  final String? fallbackReason;
  final DateTime? fetchedAt;

  PlanFetchResult asFallback({String? reason}) => PlanFetchResult._(
        plans: plans,
        dataSource: dataSource,
        isFallback: true,
        fallbackReason: reason ?? fallbackReason,
        fetchedAt: fetchedAt,
      );
}

class AssetPlanRepository implements PlanRepository {
  const AssetPlanRepository({this.assetPath = 'assets/data/planos.json'});

  final String assetPath;

  @override
  Future<PlanFetchResult> fetchPlans() async {
    try {
      final raw = await rootBundle.loadString(assetPath);
      final decoded = jsonDecode(raw);
      final plansSource = _extractPlansCollection(decoded);
      final plans = _parsePlans(plansSource, source: 'asset:$assetPath');
      return PlanFetchResult.local(plans, fetchedAt: DateTime.now());
    } on FlutterError catch (error, stackTrace) {
      debugPrint('Falha ao carregar $assetPath: ${error.message}');
      debugPrintStack(stackTrace: stackTrace);
      rethrow;
    } on FormatException catch (error, stackTrace) {
      debugPrint('JSON inválido ao ler $assetPath: ${error.message}');
      debugPrintStack(stackTrace: stackTrace);
      rethrow;
    }
  }
}

class ApiPlanRepository implements PlanRepository {
  ApiPlanRepository(this._dio);

  final Dio _dio;

  @override
  Future<PlanFetchResult> fetchPlans() async {
    final response = await _dio.get<dynamic>('/planos');
    final data = response.data;

    final List<dynamic> rawPlans;
    if (data is List<dynamic>) {
      rawPlans = data;
    } else if (data is Map<String, dynamic>) {
      try {
        rawPlans = _extractPlansCollection(data);
      } on FormatException catch (error) {
        throw DioException(
          requestOptions: response.requestOptions,
          response: response,
          error: error.message,
        );
      }
    } else {
      throw DioException(
        requestOptions: response.requestOptions,
        response: response,
        error: 'Formato inesperado ao buscar planos.',
      );
    }

    final plans = _parsePlans(rawPlans, source: 'api:/planos');
    return PlanFetchResult.remote(plans, fetchedAt: DateTime.now());
  }
}

class HybridPlanRepository implements PlanRepository {
  const HybridPlanRepository({
    required PlanRepository remote,
    required PlanRepository local,
    required PlanCacheStore cache,
  })  : _remote = remote,
        _local = local,
        _cache = cache;

  final PlanRepository _remote;
  final PlanRepository _local;
  final PlanCacheStore _cache;

  @override
  Future<PlanFetchResult> fetchPlans() async {
    String? failureReason;
    try {
      final remoteResult = await _remote.fetchPlans();
      if (remoteResult.plans.isNotEmpty) {
        final fetchedAt = remoteResult.fetchedAt ?? DateTime.now();
        try {
          await _cache.save(remoteResult.plans, fetchedAt: fetchedAt);
        } catch (error, stackTrace) {
          debugPrint('Falha ao salvar planos em cache: $error');
          debugPrintStack(stackTrace: stackTrace);
        }

        if (remoteResult.fetchedAt == null) {
          return PlanFetchResult.remote(
            remoteResult.plans,
            fetchedAt: fetchedAt,
          );
        }

        return remoteResult;
      }
      failureReason = 'Nenhum plano remoto disponível.';
    } on Exception catch (error, stackTrace) {
      debugPrint('Falha ao carregar planos remotos: $error');
      debugPrintStack(stackTrace: stackTrace);
      failureReason = error.toString();
    }

    final cached = await _readCachedPlans();
    if (cached != null) {
      return PlanFetchResult.cached(
        cached.plans,
        fetchedAt: cached.fetchedAt,
      ).asFallback(reason: failureReason);
    }

    final localResult = await _local.fetchPlans();
    return localResult.asFallback(reason: failureReason);
  }

  Future<PlanCacheSnapshot?> _readCachedPlans() async {
    try {
      return await _cache.read();
    } catch (error, stackTrace) {
      debugPrint('Erro inesperado ao acessar o cache de planos: $error');
      debugPrintStack(stackTrace: stackTrace);
      return null;
    }
  }
}

final planCacheStoreProvider = Provider<PlanCacheStore>((ref) {
  throw UnimplementedError('PlanCacheStore não foi inicializado.');
});

final planRepositoryProvider = Provider<PlanRepository>((ref) {
  final dio = ref.watch(dioProvider);
  final remote = ApiPlanRepository(dio);
  const local = AssetPlanRepository();
  final cache = ref.watch(planCacheStoreProvider);

  return HybridPlanRepository(remote: remote, local: local, cache: cache);
});

final plansProvider = FutureProvider<PlanFetchResult>((ref) async {
  final repository = ref.watch(planRepositoryProvider);
  return repository.fetchPlans();
});

List<dynamic> _extractPlansCollection(dynamic data) {
  if (data is List<dynamic>) {
    return data;
  }

  if (data is Map<String, dynamic>) {
    for (final key in ['planos', 'data', 'items']) {
      final value = data[key];
      if (value is List<dynamic>) {
        return value;
      }
    }
  }

  throw const FormatException('Coleção de planos não encontrada na resposta.');
}

List<Plan> _parsePlans(Iterable<dynamic> rawPlans, {required String source}) {
  final plans = <Plan>[];

  for (final entry in rawPlans) {
    if (entry is! Map<String, dynamic>) {
      debugPrint('Plano ignorado em $source: formato não suportado.');
      continue;
    }

    try {
      final normalised = _normalisePlanEntry(entry);
      plans.add(Plan.fromJson(normalised));
    } on FormatException catch (error, stackTrace) {
      debugPrint('Plano inválido ignorado em $source: ${error.message}');
      debugPrintStack(stackTrace: stackTrace);
    } catch (error, stackTrace) {
      debugPrint('Erro inesperado ao mapear plano em $source: $error');
      debugPrintStack(stackTrace: stackTrace);
    }
  }

  return plans;
}

Map<String, dynamic> _normalisePlanEntry(Map<String, dynamic> entry) {
  final attributes = entry['attributes'];
  if (attributes is! Map<String, dynamic>) {
    return entry;
  }

  final normalised = <String, dynamic>{...attributes};

  final id = entry['id'];
  if (id != null) {
    final idString = id.toString();
    normalised.putIfAbsent('id', () => idString);
    normalised.putIfAbsent('planoId', () => idString);
  }

  void mergeIfMap(String key) {
    final value = entry[key];
    if (value is Map<String, dynamic>) {
      normalised.putIfAbsent(key, () => value);
    }
  }

  mergeIfMap('pix');
  mergeIfMap('pixCheckout');

  final pixValue = normalised['pix'];
  if (pixValue is Map<String, dynamic>) {
    final pixData = pixValue['data'];
    final pixAttributes =
        pixData is Map<String, dynamic> ? pixData['attributes'] : null;
    if (pixAttributes is Map<String, dynamic>) {
      final flattened = <String, dynamic>{...pixAttributes};
      final pixId = pixData['id'];
      if (pixId != null) {
        flattened.putIfAbsent('id', () => pixId);
      }
      normalised['pix'] = flattened;
    }
  }

  return normalised;
}
