import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';

import '../../core/data/asset_loader.dart';
import '../../shared/widgets/app_preloader.dart';
import '../../shared/widgets/app_scaffold.dart';

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
      loading: () => AppScaffold(
        body: const Center(
          child: AppPreloader(message: 'Carregando simulados...'),
        ),
      ),
      error: (error, stackTrace) => AppScaffold(
        body: Center(
          child: Padding(
            padding: const EdgeInsets.all(24),
            child: Text('Não foi possível carregar os simulados.\n$error'),
          ),
        ),
      ),
      data: (simulados) => AppScaffold(
        body: ListView.builder(
          padding: const EdgeInsets.fromLTRB(16, 24, 16, 32),
          itemCount: simulados.length + 1,
          itemBuilder: (context, index) {
            if (index == 0) {
              return Container(
                margin: const EdgeInsets.only(bottom: 24),
                padding: const EdgeInsets.all(24),
                decoration: BoxDecoration(
                  borderRadius: BorderRadius.circular(28),
                  gradient: const LinearGradient(
                    colors: [Color(0xFF6645f6), Color(0xFF1dd3c4)],
                    begin: Alignment.topLeft,
                    end: Alignment.bottomRight,
                  ),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Simulados express',
                      style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                            color: Colors.white,
                            fontWeight: FontWeight.w700,
                          ),
                    ),
                    const SizedBox(height: 12),
                    Text(
                      'Configure bancas, dificuldades e modos direto pelo CMS para atualizar esta visão.',
                      style: Theme.of(context).textTheme.bodyLarge?.copyWith(
                            color: Colors.white.withOpacity(0.85),
                          ),
                    ),
                  ],
                ),
              );
            }
            final simulado = simulados[index - 1];
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
    final colorScheme = theme.colorScheme;
    return Container(
      margin: const EdgeInsets.only(bottom: 20),
      padding: const EdgeInsets.all(22),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(24),
        color: colorScheme.surface,
        border: Border.all(color: colorScheme.outlineVariant),
        boxShadow: [
          BoxShadow(
            color: colorScheme.primary.withOpacity(0.05),
            blurRadius: 16,
            offset: const Offset(0, 10),
          ),
        ],
      ),
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
              Chip(
                label: Text(_statusLabel),
                backgroundColor: colorScheme.secondary.withOpacity(0.16),
              ),
            ],
          ),
          const SizedBox(height: 10),
          Text(
            'Banca ${simulado.bank.toUpperCase()} • ${simulado.difficulty}',
            style: theme.textTheme.bodyMedium,
          ),
          const SizedBox(height: 14),
          Wrap(
            spacing: 8,
            runSpacing: 8,
            children: [
              for (final discipline in simulado.disciplines)
                Chip(
                  label: Text(discipline),
                  backgroundColor: colorScheme.primary.withOpacity(0.1),
                ),
            ],
          ),
          const SizedBox(height: 18),
          Row(
            children: [
              Expanded(
                child: Text(
                  '${simulado.questionCount} questões',
                  style: theme.textTheme.titleMedium?.copyWith(
                    fontWeight: FontWeight.w600,
                    color: colorScheme.primary,
                  ),
                ),
              ),
              Text(
                '${simulado.averageTimeSeconds}s por questão',
                style: theme.textTheme.bodyMedium,
              ),
            ],
          ),
          const SizedBox(height: 18),
          Text(
            "Atualizado em ${DateFormat('dd/MM/yyyy HH:mm').format(simulado.updatedAt.toLocal())}",
            style: theme.textTheme.bodySmall?.copyWith(
              color: colorScheme.outline,
            ),
          ),
          const SizedBox(height: 20),
          FilledButton(
            onPressed: () {},
            style: FilledButton.styleFrom(
              minimumSize: const Size.fromHeight(48),
              backgroundColor: colorScheme.primary,
              foregroundColor: Colors.white,
            ),
            child: Text(simulado.status == 'em_andamento'
                ? 'Continuar simulando'
                : 'Rever simulado'),
          ),
        ],
      ),
    );
  }
}
