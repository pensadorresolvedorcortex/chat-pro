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

  Future<void> applyNotificationPayload(Map<String, dynamic> payload) async {
    if (payload.isEmpty) {
      return;
    }

    final candidates = _extractCandidatePayloads(payload);

    for (final candidate in candidates) {
      try {
        final charge = PixCharge.fromJson(candidate);
        await saveCharge(charge);
        return;
      } catch (_) {
        continue;
      }
    }

    final merged = await _mergeWithExistingCandidate(candidates);
    if (merged != null) {
      await saveCharge(merged);
      return;
    }

    final identifier = _firstString(payload, const ['chargeId', 'cobrancaId', 'id']);
    if (identifier != null) {
      final existing = await _store.get(identifier);
      if (existing != null) {
        final updated = _mergeCharge(existing, payload);
        await saveCharge(updated);
        return;
      }
    }

    final txid = _firstString(payload, const ['txid', 'transactionId']);
    if (txid != null) {
      final existing = await _store.findByTxid(txid);
      if (existing != null) {
        final updated = _mergeCharge(existing, payload);
        await saveCharge(updated);
      }
    }
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

  List<Map<String, dynamic>> _extractCandidatePayloads(
    Map<String, dynamic> envelope,
  ) {
    final results = <Map<String, dynamic>>[];

    Map<String, dynamic>? addCandidate(dynamic value) {
      if (value is Map<String, dynamic>) {
        return value;
      }
      if (value is Map) {
        return value.map((key, dynamic val) => MapEntry(key.toString(), val));
      }
      return null;
    }

    for (final key in ['charge', 'cobranca', 'pixCharge', 'pix', 'data']) {
      final candidate = addCandidate(envelope[key]);
      if (candidate != null) {
        results.add(candidate);
        final inner = addCandidate(candidate['charge']) ?? addCandidate(candidate['cobranca']);
        if (inner != null) {
          results.add(inner);
        }
      }
    }

    final direct = addCandidate(envelope);
    if (direct != null) {
      results.add(direct);
    }

    final records = envelope['records'];
    if (records is Iterable) {
      for (final entry in records) {
        final candidate = addCandidate(entry);
        if (candidate != null) {
          results.add(candidate);
        }
      }
    }

    return results;
  }

  Future<PixCharge?> _mergeWithExistingCandidate(
    List<Map<String, dynamic>> candidates,
  ) async {
    for (final candidate in candidates) {
      final identifier = _firstString(
        candidate,
        const ['id', 'chargeId', 'cobrancaId'],
      );
      if (identifier == null) {
        continue;
      }
      final existing = await _store.get(identifier);
      if (existing == null) {
        continue;
      }
      return _mergeCharge(existing, candidate);
    }
    return null;
  }

  PixCharge _mergeCharge(
    PixCharge base,
    Map<String, dynamic> payload,
  ) {
    final statusRaw = _firstString(
      payload,
      const ['status', 'novoStatus', 'situacao', 'chargeStatus'],
    );
    final status = statusRaw != null
        ? pixChargeStatusFromString(statusRaw)
        : base.status;

    final amount = _readDouble(
          payload,
          const ['valor', 'amount'],
        ) ??
        base.amount;

    final currency =
        _firstString(payload, const ['moeda', 'currency']) ?? base.currency;

    final copyAndPasteCode = _firstString(
          payload,
          const [
            'codigoCopiaCola',
            'codigoCopiaECola',
            'copyAndPasteCode',
          ],
        ) ??
        base.copyAndPasteCode;

    final qrCodeUrl =
        _firstString(payload, const ['qrCodeUrl', 'qrCode']) ?? base.qrCodeUrl;
    final qrCodeBase64 = _firstString(
          payload,
          const ['qrCodeBase64', 'qrCodeBase64Payload'],
        ) ??
        base.qrCodeBase64;

    final pixKey =
        _firstString(payload, const ['chavePix', 'pixKey']) ?? base.pixKey;
    final txid = _firstString(payload, const ['txid', 'transactionId']) ??
        base.txid;

    final expiresAt = _readDate(
          payload,
          const ['expiraEm', 'expiresAt', 'expirationDate'],
        ) ??
        base.expiresAt;

    final updatedAt = _readDate(
          payload,
          const ['updatedAt', 'atualizadoEm', 'occurredAt', 'timestamp'],
        ) ??
        DateTime.now();

    return base.copyWith(
      status: status,
      amount: amount,
      currency: currency,
      copyAndPasteCode: copyAndPasteCode,
      qrCodeUrl: qrCodeUrl,
      qrCodeBase64: qrCodeBase64,
      pixKey: pixKey,
      txid: txid,
      expiresAt: expiresAt,
      updatedAt: updatedAt,
    );
  }

  String? _firstString(Map<String, dynamic> payload, List<String> keys) {
    for (final key in keys) {
      final value = payload[key];
      if (value == null) {
        continue;
      }
      if (value is String) {
        if (value.trim().isEmpty) {
          continue;
        }
        return value.trim();
      }
      return value.toString();
    }
    return null;
  }

  double? _readDouble(Map<String, dynamic> payload, List<String> keys) {
    final raw = _firstString(payload, keys);
    if (raw == null) {
      return null;
    }
    final normalised = raw.replaceAll(',', '.');
    return double.tryParse(normalised);
  }

  DateTime? _readDate(Map<String, dynamic> payload, List<String> keys) {
    final raw = _firstString(payload, keys);
    if (raw == null) {
      return null;
    }
    return DateTime.tryParse(raw);
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
