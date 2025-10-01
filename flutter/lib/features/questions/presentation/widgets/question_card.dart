import 'package:flutter/material.dart';

import '../../data/question_models.dart';

class QuestionCard extends StatefulWidget {
  const QuestionCard({super.key, required this.question});

  final Question question;

  @override
  State<QuestionCard> createState() => _QuestionCardState();
}

class _QuestionCardState extends State<QuestionCard> {
  String? _selectedLetter;
  bool _revealed = false;

  void _select(String letter) {
    setState(() {
      _selectedLetter = letter;
      _revealed = true;
    });
  }

  void _toggleExplanation() {
    setState(() {
      _revealed = true;
    });
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final question = widget.question;

    return Card(
      margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      child: Padding(
        padding: const EdgeInsets.fromLTRB(20, 16, 20, 24),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              question.discipline,
              style: theme.textTheme.labelLarge?.copyWith(
                color: theme.colorScheme.primary,
                fontWeight: FontWeight.w600,
              ),
            ),
            const SizedBox(height: 8),
            Text(
              question.statement,
              style: theme.textTheme.titleMedium?.copyWith(
                fontWeight: FontWeight.w600,
              ),
            ),
            const SizedBox(height: 16),
            ...question.alternatives.map(
              (alternative) => _AlternativeTile(
                alternative: alternative,
                isSelected: alternative.letter == _selectedLetter,
                isCorrect: alternative.isCorrect,
                revealed: _revealed,
                onTap: () => _select(alternative.letter),
              ),
            ),
            const SizedBox(height: 12),
            Wrap(
              spacing: 8,
              runSpacing: 4,
              children: [
                Chip(
                  label: Text('${question.year} • ${question.board}'),
                  backgroundColor: theme.colorScheme.surfaceVariant,
                ),
                Chip(
                  label: Text('Nível: ${question.difficulty}'),
                  backgroundColor: theme.colorScheme.surfaceVariant,
                ),
                if (question.accuracyPercent != null)
                  Chip(
                    label: Text(
                      'Acertos: ${question.accuracyPercent!.toStringAsFixed(1)}%',
                    ),
                    backgroundColor: theme.colorScheme.surfaceVariant,
                  ),
                ...question.topics.map(
                  (topic) => Chip(
                    label: Text(topic.replaceAll('-', ' ')),
                    backgroundColor: theme.colorScheme.surfaceVariant,
                  ),
                ),
              ],
            ),
            if (question.hasExplanation)
              Padding(
                padding: const EdgeInsets.only(top: 16),
                child: OutlinedButton.icon(
                  onPressed: _toggleExplanation,
                  icon: const Icon(Icons.visibility_outlined),
                  label: Text(
                    _revealed ? 'Comentário do professor' : 'Ver explicação',
                  ),
                ),
              ),
            AnimatedCrossFade(
              firstChild: const SizedBox.shrink(),
              secondChild: _ExplanationBlock(
                explanation: question.explanation ?? '',
                isCorrect: _selectedLetter == question.correctLetter,
              ),
              crossFadeState: _revealed
                  ? CrossFadeState.showSecond
                  : CrossFadeState.showFirst,
              duration: const Duration(milliseconds: 250),
            ),
          ],
        ),
      ),
    );
  }
}

class _AlternativeTile extends StatelessWidget {
  const _AlternativeTile({
    required this.alternative,
    required this.isSelected,
    required this.isCorrect,
    required this.revealed,
    required this.onTap,
  });

  final QuestionAlternative alternative;
  final bool isSelected;
  final bool isCorrect;
  final bool revealed;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final bool highlight = revealed && (isCorrect || isSelected);
    final Color baseColor;
    if (highlight) {
      baseColor = isCorrect
          ? theme.colorScheme.secondaryContainer
          : theme.colorScheme.errorContainer;
    } else {
      baseColor = theme.colorScheme.surfaceVariant;
    }

    final Color borderColor = highlight
        ? (isCorrect
            ? theme.colorScheme.secondary
            : theme.colorScheme.error)
        : theme.dividerColor;

    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(12),
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 200),
        margin: const EdgeInsets.symmetric(vertical: 6),
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          color: baseColor,
          borderRadius: BorderRadius.circular(12),
          border: Border.all(color: borderColor),
        ),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            CircleAvatar(
              radius: 16,
              backgroundColor: highlight
                  ? borderColor
                  : theme.colorScheme.onSurface.withOpacity(0.08),
              child: Text(
                alternative.letter,
                style: theme.textTheme.titleMedium?.copyWith(
                  color: highlight
                      ? theme.colorScheme.onSecondaryContainer
                      : theme.colorScheme.onSurface,
                  fontWeight: FontWeight.bold,
                ),
              ),
            ),
            const SizedBox(width: 16),
            Expanded(
              child: Text(
                alternative.description,
                style: theme.textTheme.bodyLarge,
              ),
            ),
            if (revealed && isCorrect)
              Icon(
                Icons.check_circle,
                color: theme.colorScheme.secondary,
              )
            else if (revealed && isSelected && !isCorrect)
              Icon(
                Icons.highlight_off,
                color: theme.colorScheme.error,
              ),
          ],
        ),
      ),
    );
  }
}

class _ExplanationBlock extends StatelessWidget {
  const _ExplanationBlock({required this.explanation, required this.isCorrect});

  final String explanation;
  final bool isCorrect;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Container(
      width: double.infinity,
      margin: const EdgeInsets.only(top: 12),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(12),
        color: isCorrect
            ? theme.colorScheme.secondaryContainer
            : theme.colorScheme.surfaceVariant,
        border: Border.all(
          color: isCorrect
              ? theme.colorScheme.secondary
              : theme.colorScheme.outlineVariant,
        ),
      ),
      child: Text(
        explanation,
        style: theme.textTheme.bodyMedium,
      ),
    );
  }
}
