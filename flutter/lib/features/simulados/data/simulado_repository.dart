import 'package:flutter/foundation.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import 'simulado_models.dart';

class SimuladosRepository {
  SimuladosRepository({@visibleForTesting this.assetPath = _defaultAssetPath});

  static const String _defaultAssetPath = 'assets/data/simulados.json';

  final String assetPath;
  List<Simulado>? _cached;

  Future<List<Simulado>> fetchAll() async {
    if (_cached != null) {
      return _cached!;
    }
    final simulados = await Simulado.loadFromAsset(assetPath);
    _cached = simulados;
    return simulados;
  }

  Future<Simulado?> fetchCurrentExpress() async {
    final simulados = await fetchAll();
    for (final simulado in simulados) {
      if (simulado.isInProgress) {
        return simulado;
      }
    }
    return simulados.isNotEmpty ? simulados.first : null;
  }
}

final simuladosRepositoryProvider = Provider<SimuladosRepository>((ref) {
  return SimuladosRepository();
});

final currentSimuladoProvider = FutureProvider<Simulado?>((ref) async {
  final repository = ref.watch(simuladosRepositoryProvider);
  return repository.fetchCurrentExpress();
});

final simuladosHistoryProvider = FutureProvider<List<Simulado>>((ref) async {
  final repository = ref.watch(simuladosRepositoryProvider);
  return repository.fetchAll();
});
