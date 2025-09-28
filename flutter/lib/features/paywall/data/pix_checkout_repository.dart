import 'package:dio/dio.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/network/dio_client.dart';
import '../domain/pix_charge.dart';
import '../domain/plan.dart';
import 'pix_charge_repository.dart';

class PixCheckoutRepository {
  PixCheckoutRepository(this._dio, this._charges);

  final Dio _dio;
  final PixChargeRepository _charges;

  Future<PixCharge> createCharge({required String planId}) async {
    try {
      final response = await _dio.post<dynamic>(
        '/assinaturas/pix/cobrancas',
        data: <String, dynamic>{'planoId': planId},
      );

      final data = response.data;
      if (data is Map<String, dynamic>) {
        try {
          final charge = PixCharge.fromJson(data);
          await _charges.saveCharge(charge);
          return charge;
        } on FormatException catch (error, stackTrace) {
          debugPrint(
            'Resposta de cobrança Pix inválida: ${error.message}',
          );
          debugPrintStack(stackTrace: stackTrace);
          throw PixCheckoutException(
            'Não foi possível interpretar os dados retornados pelo Pix.',
          );
        }
      }

      throw DioException(
        requestOptions: response.requestOptions,
        response: response,
        error: 'Resposta inesperada ao gerar cobrança Pix.',
      );
    } on DioException catch (error, stackTrace) {
      debugPrint('Falha ao gerar cobrança Pix: ${error.message ?? error.error}');
      debugPrintStack(stackTrace: stackTrace);
      throw PixCheckoutException(_mapErrorMessage(error));
    }
  }
}

final pixCheckoutRepositoryProvider = Provider<PixCheckoutRepository>((ref) {
  final dio = ref.watch(dioProvider);
  final charges = ref.watch(pixChargeRepositoryProvider);
  return PixCheckoutRepository(dio, charges);
});

class PixCheckoutException implements Exception {
  PixCheckoutException(this.message);

  final String message;

  @override
  String toString() => message;
}

String _mapErrorMessage(DioException error) {
  if (error.response?.statusCode == 401) {
    return 'Faça login novamente para gerar a cobrança Pix.';
  }

  if (error.response?.data is Map<String, dynamic>) {
    final data = error.response!.data as Map<String, dynamic>;
    final message = data['message'] ?? data['error'];
    if (message is String && message.isNotEmpty) {
      return message;
    }
  }

  return 'Não foi possível gerar a cobrança Pix. Tente novamente em instantes.';
}

extension PlanPixFallback on Plan {
  PixCharge createFallbackCharge() {
    return toOfflinePixCharge();
  }
}
