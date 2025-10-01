import 'package:flutter/material.dart';

import '../../data/simulado_models.dart';

class SimuladoCard extends StatelessWidget {
  const SimuladoCard._({
    required this.simulado,
    required this.highlight,
    required this.showResumeButton,
  });

  factory SimuladoCard.express({required Simulado simulado}) {
    return SimuladoCard._(
      simulado: simulado,
      highlight: true,
      showResumeButton: true,
    );
  }

  factory SimuladoCard.history({required Simulado simulado}) {
    return SimuladoCard._(
      simulado: simulado,
      highlight: simulado.isInProgress,
      showResumeButton: simulado.isInProgress,
    );
  }

  final Simulado simulado;
  final bool highlight;
  final bool showResumeButton;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final completion = simulado.completionRatio.clamp(0.0, 1.0);
    final accuracy = simulado.statistics.accuracyPercent;
    final subtitle = _buildSubtitle();

    return Card(
      elevation: highlight ? 4 : 1,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
      child: Padding(
        padding: const EdgeInsets.fromLTRB(20, 20, 20, 16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Icon(
                  highlight ? Icons.rocket_launch_outlined : Icons.timer_outlined,
                  color: highlight
                      ? theme.colorScheme.primary
                      : theme.colorScheme.onSurfaceVariant,
                  size: 28,
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Text(
                    simulado.title,
                    style: theme.textTheme.titleMedium?.copyWith(
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 12),
            Text(subtitle, style: theme.textTheme.bodyMedium),
            const SizedBox(height: 16),
            ClipRRect(
              borderRadius: BorderRadius.circular(12),
              child: LinearProgressIndicator(
                minHeight: 10,
                value: completion,
                backgroundColor: theme.colorScheme.surfaceVariant,
                valueColor: AlwaysStoppedAnimation<Color>(
                  theme.colorScheme.primary,
                ),
              ),
            ),
            const SizedBox(height: 12),
            Row(
              children: [
                Expanded(
                  child: Wrap(
                    spacing: 8,
                    runSpacing: 4,
                    children: [
                      _InfoChip(
                        icon: Icons.question_answer_outlined,
                        label:
                            '${simulado.statistics.answered}/${simulado.configuration.totalQuestions} questões',
                      ),
                      _InfoChip(
                        icon: Icons.schedule_outlined,
                        label:
                            '${simulado.configuration.timeMinutes} min • ${simulado.configuration.board}',
                      ),
                      if (accuracy != null)
                        _InfoChip(
                          icon: Icons.insights_outlined,
                          label: 'Acertos ${accuracy.toStringAsFixed(0)}%',
                        ),
                    ],
                  ),
                ),
              ],
            ),
            if (showResumeButton) ...[
              const SizedBox(height: 16),
              FilledButton.icon(
                onPressed: () {},
                icon: const Icon(Icons.play_arrow_rounded),
                label: const Text('Continuar simulando'),
              ),
            ] else ...[
              const SizedBox(height: 12),
              Text(
                _buildUpdatedAtText(),
                style: theme.textTheme.bodySmall?.copyWith(
                  color: theme.colorScheme.onSurfaceVariant,
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }

  String _buildSubtitle() {
    final disciplines = simulado.configuration.disciplines.join(' • ');
    final difficulty = simulado.configuration.difficulty;
    return '$disciplines • Dificuldade $difficulty';
  }

  String _buildUpdatedAtText() {
    final updated = simulado.updatedAt;
    if (updated == null) {
      return 'Atualização recente';
    }
    return 'Atualizado em ${_formatDate(updated)}';
  }

  String _formatDate(DateTime date) {
    final day = date.day.toString().padLeft(2, '0');
    final month = date.month.toString().padLeft(2, '0');
    final hour = date.hour.toString().padLeft(2, '0');
    final minute = date.minute.toString().padLeft(2, '0');
    return '$day/$month às $hour:$minute';
  }
}

class _InfoChip extends StatelessWidget {
  const _InfoChip({required this.icon, required this.label});

  final IconData icon;
  final String label;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
      decoration: BoxDecoration(
        color: theme.colorScheme.surfaceVariant,
        borderRadius: BorderRadius.circular(999),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 16, color: theme.colorScheme.onSurfaceVariant),
          const SizedBox(width: 6),
          Text(
            label,
            style: theme.textTheme.bodySmall?.copyWith(
              color: theme.colorScheme.onSurfaceVariant,
            ),
          ),
        ],
      ),
    );
  }
}
