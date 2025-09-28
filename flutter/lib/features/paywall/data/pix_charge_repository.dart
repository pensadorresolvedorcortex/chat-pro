import 'dart:async';

import 'package:dio/dio.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/network/dio_client.dart';
import '../domain/pix_charge.dart';
import 'pix_charge_store.dart';

class PixChargeRepository {
  PixChargeRepository(this._dio, this._store);

  final Dio _dio;
  final PixChargeStore _store;

  Future<PixCharge> fetchCharge(String id) async {
    final response = await _dio.get<dynamic>('/assinaturas/pix/cobrancas/$id');
    final payload = _extractPayload(response);

    if (payload == null) {
      throw DioException(
        requestOptions: response.requestOptions,
        response: response,
        error: 'Formato inesperado da cobrança Pix.',
      );
    }

    try {
      final charge = PixCharge.fromJson(payload);
      await _store.save(charge);
      return charge;
    } on FormatException catch (error, stackTrace) {
      debugPrint('Cobrança Pix inválida recebida: ${error.message}');
      debugPrintStack(stackTrace: stackTrace);
      throw DioException(
        requestOptions: response.requestOptions,
        response: response,
        error: error.message,
      );
    }
  }

  Future<void> saveCharge(PixCharge charge) => _store.save(charge);

  Stream<PixCharge> watchCharge(
    String id, {
    PixCharge? initial,
    Duration interval = const Duration(seconds: 5),
  }) async* {
    PixCharge? latest = initial ?? await _store.get(id);
    if (latest != null) {
      yield latest;
    }

    while (true) {
      try {
        final updated = await fetchCharge(id);
        latest = updated;
        yield updated;
      } on DioException catch (error, stackTrace) {
        debugPrint(
          'Falha ao atualizar cobrança Pix $id: ${error.message ?? error.error}',
        );
        debugPrintStack(stackTrace: stackTrace);
        if (latest != null) {
          yield latest;
        }
      } catch (error, stackTrace) {
        debugPrint('Erro inesperado ao atualizar cobrança Pix $id: $error');
        debugPrintStack(stackTrace: stackTrace);
        if (latest != null) {
          yield latest;
        }
      }

      if (latest != null && latest.isFinal) {
        break;
      }

      await Future.delayed(interval);
    }
  }

  Stream<List<PixCharge>> watchHistory() => _store.watchAll();

  Future<void> purgeExpired({
    Duration maxAge = const Duration(days: 7),
  }) async {
    final threshold = DateTime.now().subtract(maxAge);
    await _store.purge(olderThan: threshold);
  }

  Map<String, dynamic>? _extractPayload(Response<dynamic> response) {
    final data = response.data;
    if (data is Map<String, dynamic>) {
      final possibleKeys = ['data', 'cobranca', 'charge'];
      for (final key in possibleKeys) {
        final value = data[key];
        if (value is Map<String, dynamic>) {
          return value;
        }
      }
      return data;
    }
    return null;
  }
}

final pixChargeStoreProvider = Provider<PixChargeStore>((ref) {
  throw UnimplementedError('PixChargeStore não foi inicializado.');
});

final pixChargeRepositoryProvider = Provider<PixChargeRepository>((ref) {
  final dio = ref.watch(dioProvider);
  final store = ref.watch(pixChargeStoreProvider);
  return PixChargeRepository(dio, store);
});

final pixChargeHistoryProvider = StreamProvider<List<PixCharge>>((ref) {
  final repository = ref.watch(pixChargeRepositoryProvider);
  return repository.watchHistory();
});
