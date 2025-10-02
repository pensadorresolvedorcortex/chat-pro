import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/data/asset_loader.dart';
import '../../shared/widgets/app_preloader.dart';
import '../../shared/widgets/app_scaffold.dart';

class Question {
  const Question({
    required this.id,
    required this.statement,
    required this.alternatives,
    required this.discipline,
    required this.difficulty,
  });

  final String id;
  final String statement;
  final List<QuestionAlternative> alternatives;
  final String discipline;
  final String difficulty;

  factory Question.fromMap(Map<String, dynamic> map) {
    return Question(
      id: map['id'] as String,
      statement: map['enunciado'] as String,
      discipline: map['disciplina'] as String,
      difficulty: map['dificuldade'] as String,
      alternatives: (map['alternativas'] as List<dynamic>)
          .map((item) => QuestionAlternative.fromMap(item as Map<String, dynamic>))
          .toList(),
    );
  }
}

class QuestionAlternative {
  const QuestionAlternative({
    required this.letter,
    required this.description,
    required this.isCorrect,
  });

  final String letter;
  final String description;
  final bool isCorrect;

  factory QuestionAlternative.fromMap(Map<String, dynamic> map) {
    return QuestionAlternative(
      letter: map['letra'] as String,
      description: map['descricao'] as String,
      isCorrect: map['correta'] as bool,
    );
  }
}

final questionsProvider = FutureProvider<List<Question>>((ref) async {
  final loader = AssetLoader(rootBundle);
  final list = await loader.loadJsonList('assets/data/questoes.json');
  return list
      .map((item) => Question.fromMap(item as Map<String, dynamic>))
      .toList();
});

class QuestionsPage extends ConsumerWidget {
  const QuestionsPage({super.key});

  static const routePath = '/questoes/recomendadas';
  static const routeName = 'questions';

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final asyncQuestions = ref.watch(questionsProvider);
    return asyncQuestions.when(
      loading: () => AppScaffold(
        body: const Center(
          child: AppPreloader(message: 'Carregando questões...'),
        ),
      ),
      error: (error, stackTrace) => AppScaffold(
        body: Center(
          child: Padding(
            padding: const EdgeInsets.all(24),
            child: Text('Não foi possível carregar as questões.\n$error'),
          ),
        ),
      ),
      data: (questions) => AppScaffold(
        body: ListView.builder(
          padding: const EdgeInsets.fromLTRB(16, 24, 16, 32),
          itemCount: questions.length + 1,
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
                      'Questões recomendadas',
                      style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                            color: Colors.white,
                            fontWeight: FontWeight.w700,
                          ),
                    ),
                    const SizedBox(height: 12),
                    Text(
                      'Refine filtros no CMS para personalizar a lista exibida aqui.',
                      style: Theme.of(context).textTheme.bodyLarge?.copyWith(
                            color: Colors.white.withOpacity(0.85),
                          ),
                    ),
                  ],
                ),
              );
            }
            final question = questions[index - 1];
            return _QuestionCard(question: question);
          },
        ),
      ),
    );
  }
}

class _QuestionCard extends StatelessWidget {
  const _QuestionCard({required this.question});

  final Question question;

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
          Wrap(
            spacing: 8,
            runSpacing: 8,
            children: [
              Chip(
                label: Text(question.discipline),
                backgroundColor: colorScheme.primary.withOpacity(0.1),
              ),
              Chip(
                label: Text('Dificuldade: ${question.difficulty}'),
                backgroundColor: colorScheme.secondary.withOpacity(0.1),
              ),
            ],
          ),
          const SizedBox(height: 16),
          Text(
            question.statement,
            style: theme.textTheme.titleMedium?.copyWith(
              fontWeight: FontWeight.w600,
              color: colorScheme.primary,
            ),
          ),
          const SizedBox(height: 20),
          ...question.alternatives.map(
            (alternative) => Padding(
              padding: const EdgeInsets.only(bottom: 12),
              child: DecoratedBox(
                decoration: BoxDecoration(
                  color: alternative.isCorrect
                      ? colorScheme.secondaryContainer
                      : colorScheme.surface,
                  borderRadius: BorderRadius.circular(14),
                  border: Border.all(
                    color: alternative.isCorrect
                        ? colorScheme.secondary
                        : colorScheme.outlineVariant,
                  ),
                ),
                child: ListTile(
                  leading: CircleAvatar(
                    backgroundColor: alternative.isCorrect
                        ? colorScheme.secondary
                        : colorScheme.primaryContainer,
                    child: Text(
                      alternative.letter,
                      style: theme.textTheme.bodyMedium?.copyWith(
                        color: alternative.isCorrect
                            ? colorScheme.onSecondary
                            : colorScheme.onPrimaryContainer,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                  ),
                  title: Text(alternative.description),
                  trailing: alternative.isCorrect
                      ? Icon(Icons.check_circle, color: colorScheme.secondary)
                      : null,
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}
