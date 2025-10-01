import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';

import '../../core/data/asset_loader.dart';

class Simulado {
  const Simulado({
    required this.id,
    required this.title,
    required this.mode,
    required this.disciplines,
    required this.bank,
    required this.difficulty,
    required this.questionCount,
    required this.averageTimeSeconds,
    required this.status,
    required this.updatedAt,
  });

  final String id;
  final String title;
  final String mode;
  final List<String> disciplines;
  final String bank;
  final String difficulty;
  final int questionCount;
  final int averageTimeSeconds;
  final String status;
  final DateTime updatedAt;

  factory Simulado.fromMap(Map<String, dynamic> map) {
    final config = map['configuracao'] as Map<String, dynamic>;
    final stats = map['estatisticas'] as Map<String, dynamic>;
    return Simulado(
      id: map['id'] as String,
      title: map['titulo'] as String,
      mode: map['modalidade'] as String,
      disciplines:
          (config['disciplinas'] as List<dynamic>).map((e) => e as String).toList(),
      bank: config['banca'] as String,
      difficulty: config['dificuldade'] as String,
      questionCount: config['quantidadeQuestoes'] as int,
      averageTimeSeconds: stats['tempoMedioPorQuestaoSegundos'] as int,
      status: map['status'] as String,
      updatedAt: DateTime.parse(map['atualizadoEm'] as String),
    );
  }
}

final simuladosProvider = FutureProvider<List<Simulado>>((ref) async {
  final loader = AssetLoader(rootBundle);
  final list = await loader.loadJsonList('assets/data/simulados.json');
  return list
      .map((item) => Simulado.fromMap(item as Map<String, dynamic>))
      .toList();
});

class SimuladosPage extends ConsumerWidget {
  const SimuladosPage({super.key});

  static const routePath = '/simulados';
  static const routeName = 'simulados';

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final asyncSimulados = ref.watch(simuladosProvider);
    return asyncSimulados.when(
      loading: () => const Scaffold(
        body: Center(child: CircularProgressIndicator()),
      ),
      error: (error, stackTrace) => Scaffold(
        body: Center(
          child: Padding(
            padding: const EdgeInsets.all(24),
            child: Text('Não foi possível carregar os simulados.\n$error'),
          ),
        ),
      ),
      data: (simulados) => Scaffold(
        appBar: AppBar(
          title: const Text('Simulados'),
        ),
        body: ListView.builder(
          padding: const EdgeInsets.all(16),
          itemCount: simulados.length,
          itemBuilder: (context, index) {
            final simulado = simulados[index];
            return _SimuladoCard(simulado: simulado);
          },
        ),
      ),
    );
  }
}

class _SimuladoCard extends StatelessWidget {
  const _SimuladoCard({required this.simulado});

  final Simulado simulado;

  String get _statusLabel {
    switch (simulado.status) {
      case 'finalizado':
        return 'Finalizado';
      case 'em_andamento':
        return 'Em andamento';
      default:
        return 'Planejado';
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Card(
      margin: const EdgeInsets.only(bottom: 16),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
      child: Padding(
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Expanded(
                  child: Text(
                    simulado.title,
                    style: theme.textTheme.titleLarge?.copyWith(
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                ),
                Chip(label: Text(_statusLabel)),
              ],
            ),
            const SizedBox(height: 8),
            Text('Banca ${simulado.bank.toUpperCase()} • ${simulado.difficulty}'),
            const SizedBox(height: 12),
            Wrap(
              spacing: 8,
              runSpacing: 8,
              children: [
                for (final discipline in simulado.disciplines)
                  Chip(label: Text(discipline)),
              ],
            ),
            const SizedBox(height: 12),
            Row(
              children: [
                Expanded(
                  child: Text(
                    '${simulado.questionCount} questões',
                    style: theme.textTheme.titleMedium,
                  ),
                ),
                Text('${simulado.averageTimeSeconds}s por questão'),
              ],
            ),
            const SizedBox(height: 12),
            Text(
              "Atualizado em ${DateFormat('dd/MM/yyyy HH:mm').format(simulado.updatedAt.toLocal())}",
              style: theme.textTheme.bodySmall,
            ),
            const SizedBox(height: 12),
            FilledButton(
              onPressed: () {},
              child: Text(simulado.status == 'em_andamento'
                  ? 'Continuar simulando'
                  : 'Rever simulado'),
            ),
          ],
        ),
      ),
    );
  }
}
