import 'dart:convert';

import 'package:dio/dio.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/network/dio_client.dart';
import 'dashboard_models.dart';

abstract class DashboardRepository {
  Future<DashboardFetchResult> fetchHome();
}

enum DashboardDataSource { remote, local }

class DashboardFetchResult {
  const DashboardFetchResult._({
    required this.data,
    required this.dataSource,
    required this.isFallback,
    this.fallbackReason,
  });

  factory DashboardFetchResult.remote(DashboardHomeData data) =>
      DashboardFetchResult._(
        data: data,
        dataSource: DashboardDataSource.remote,
        isFallback: false,
      );

  factory DashboardFetchResult.local(DashboardHomeData data) =>
      DashboardFetchResult._(
        data: data,
        dataSource: DashboardDataSource.local,
        isFallback: false,
      );

  final DashboardHomeData data;
  final DashboardDataSource dataSource;
  final bool isFallback;
  final String? fallbackReason;

  DashboardFetchResult asFallback({String? reason}) => DashboardFetchResult._(
        data: data,
        dataSource: dataSource,
        isFallback: true,
        fallbackReason: reason ?? fallbackReason,
      );
}

class ApiDashboardRepository implements DashboardRepository {
  ApiDashboardRepository(this._dio);

  final Dio _dio;

  @override
  Future<DashboardFetchResult> fetchHome() async {
    final response = await _dio.get<dynamic>('/dashboard/home');
    final payload = _unwrap(response.data, response.requestOptions);
    final data = DashboardHomeData.fromJson(payload);
    return DashboardFetchResult.remote(data);
  }

  Map<String, dynamic> _unwrap(dynamic value, RequestOptions options) {
    if (value is Map<String, dynamic>) {
      if (value.containsKey('data') && value['data'] is Map<String, dynamic>) {
        return value['data'] as Map<String, dynamic>;
      }
      return value;
    }

    throw DioException(
      requestOptions: options,
      response: Response(requestOptions: options, data: value),
      error: 'Formato inesperado ao carregar /dashboard/home',
    );
  }
}

class AssetDashboardRepository implements DashboardRepository {
  const AssetDashboardRepository({
    this.assetPath = 'assets/data/dashboard_home.json',
  });

  final String assetPath;

  @override
  Future<DashboardFetchResult> fetchHome() async {
    final raw = await rootBundle.loadString(assetPath);
    final decoded = jsonDecode(raw);
    if (decoded is! Map<String, dynamic>) {
      throw const FormatException('Dashboard asset inválido');
    }
    final data = DashboardHomeData.fromJson(decoded);
    return DashboardFetchResult.local(data);
  }
}

class HybridDashboardRepository implements DashboardRepository {
  const HybridDashboardRepository({
    required DashboardRepository remote,
    required DashboardRepository local,
  })  : _remote = remote,
        _local = local;

  final DashboardRepository _remote;
  final DashboardRepository _local;

  @override
  Future<DashboardFetchResult> fetchHome() async {
    String? fallbackReason;
    try {
      final remote = await _remote.fetchHome();
      if (remote.data.learnerSpotlights.isNotEmpty ||
          remote.data.quickActions.isNotEmpty) {
        return remote;
      }
      fallbackReason = 'Dashboard remoto sem dados.';
    } on Exception catch (error) {
      fallbackReason = error.toString();
    }

    final local = await _local.fetchHome();
    return local.asFallback(reason: fallbackReason);
  }
}

final dashboardRepositoryProvider = Provider<DashboardRepository>((ref) {
  final dio = ref.watch(dioProvider);
  final remote = ApiDashboardRepository(dio);
  const local = AssetDashboardRepository();
  return HybridDashboardRepository(remote: remote, local: local);
});

final dashboardProvider = FutureProvider<DashboardFetchResult>((ref) async {
  final repository = ref.watch(dashboardRepositoryProvider);
  return repository.fetchHome();
});
