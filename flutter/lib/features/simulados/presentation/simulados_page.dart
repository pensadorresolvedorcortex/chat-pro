import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../data/simulado_models.dart';
import '../data/simulado_repository.dart';
import 'widgets/simulado_card.dart';

class SimuladosExpressPage extends ConsumerWidget {
  const SimuladosExpressPage({super.key});

  static const String routePath = '/simulados/express';
  static const String routeName = 'simulados-express';

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final historyAsync = ref.watch(simuladosHistoryProvider);

    return Scaffold(
      appBar: AppBar(
        title: const Text('Simulados express'),
      ),
      body: historyAsync.when(
        data: (history) {
          Simulado? express;
          for (final item in history) {
            if (item.isInProgress) {
              express = item;
              break;
            }
          }
          express ??= history.isNotEmpty ? history.first : null;
          return _SimuladoContent(express: express, history: history);
        },
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (error, _) => _SimuladoError(onRetry: () {
          ref.invalidate(simuladosHistoryProvider);
        }),
      ),
    );
  }
}

class _SimuladoContent extends StatefulWidget {
  const _SimuladoContent({required this.express, required this.history});

  final Simulado? express;
  final List<Simulado> history;

  @override
  State<_SimuladoContent> createState() => _SimuladoContentState();
}

class _SimuladoContentState extends State<_SimuladoContent> {
  Future<void> _onRefresh() async {
    await Future<void>.delayed(const Duration(milliseconds: 350));
  }

  @override
  Widget build(BuildContext context) {
    final express = widget.express;
    final history = widget.history;
    return RefreshIndicator(
      onRefresh: _onRefresh,
      child: ListView(
        padding: const EdgeInsets.only(bottom: 24),
        children: [
          if (express != null)
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 24, 16, 8),
              child: SimuladoCard.express(simulado: express),
            ),
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 16, 16, 8),
            child: Text(
              'Histórico recente',
              style: Theme.of(context).textTheme.titleMedium?.copyWith(
                    fontWeight: FontWeight.w600,
                  ),
            ),
          ),
          ...history.map(
            (item) => Padding(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
              child: SimuladoCard.history(simulado: item),
            ),
          ),
        ],
      ),
    );
  }
}

class _SimuladoError extends StatelessWidget {
  const _SimuladoError({required this.onRetry});

  final VoidCallback onRetry;

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          const Icon(Icons.timer_off_outlined, size: 48),
          const SizedBox(height: 16),
          const Text('Não foi possível carregar os simulados agora.'),
          const SizedBox(height: 16),
          ElevatedButton(
            onPressed: onRetry,
            child: const Text('Tentar novamente'),
          ),
        ],
      ),
    );
  }
}
