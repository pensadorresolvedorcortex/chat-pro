import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/data/asset_loader.dart';

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
      loading: () => const Scaffold(
        body: Center(child: CircularProgressIndicator()),
      ),
      error: (error, stackTrace) => Scaffold(
        body: Center(
          child: Padding(
            padding: const EdgeInsets.all(24),
            child: Text('Não foi possível carregar as questões.\n$error'),
          ),
        ),
      ),
      data: (questions) => Scaffold(
        appBar: AppBar(
          title: const Text('Questões recomendadas'),
        ),
        body: ListView.builder(
          padding: const EdgeInsets.all(16),
          itemCount: questions.length,
          itemBuilder: (context, index) {
            final question = questions[index];
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
                Chip(label: Text(question.discipline)),
                const SizedBox(width: 8),
                Chip(label: Text('Dificuldade: ${question.difficulty}')),
              ],
            ),
            const SizedBox(height: 16),
            Text(
              question.statement,
              style: theme.textTheme.titleMedium,
            ),
            const SizedBox(height: 16),
            ...question.alternatives.map(
              (alternative) => Padding(
                padding: const EdgeInsets.only(bottom: 12),
                child: DecoratedBox(
                  decoration: BoxDecoration(
                    color: alternative.isCorrect
                        ? theme.colorScheme.secondaryContainer
                        : theme.colorScheme.surface,
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(
                      color: alternative.isCorrect
                          ? theme.colorScheme.secondary
                          : theme.colorScheme.outlineVariant,
                    ),
                  ),
                  child: ListTile(
                    leading: CircleAvatar(
                      backgroundColor: alternative.isCorrect
                          ? theme.colorScheme.secondary
                          : theme.colorScheme.primaryContainer,
                      child: Text(
                        alternative.letter,
                        style: theme.textTheme.bodyMedium?.copyWith(
                          color: alternative.isCorrect
                              ? theme.colorScheme.onSecondary
                              : theme.colorScheme.onPrimaryContainer,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                    ),
                    title: Text(alternative.description),
                    trailing: alternative.isCorrect
                        ? Icon(Icons.check_circle,
                            color: theme.colorScheme.secondary)
                        : null,
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
