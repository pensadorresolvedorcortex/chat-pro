import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../data/question_models.dart';
import '../data/question_repository.dart';
import 'widgets/question_card.dart';

class QuestionsPage extends ConsumerWidget {
  const QuestionsPage({super.key});

  static const String routePath = '/questoes/recomendadas';
  static const String routeName = 'questoes-recomendadas';

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final questionsAsync = ref.watch(recommendedQuestionsProvider);

    return Scaffold(
      appBar: AppBar(
        title: const Text('Questões recomendadas'),
        actions: const [
          Padding(
            padding: EdgeInsets.symmetric(horizontal: 16),
            child: Icon(Icons.auto_awesome),
          ),
        ],
      ),
      body: questionsAsync.when(
        data: (questions) => _QuestionsList(questions: questions),
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (error, _) => _ErrorState(onRetry: () {
          ref.invalidate(recommendedQuestionsProvider);
        }),
      ),
    );
  }
}

class _QuestionsList extends StatefulWidget {
  const _QuestionsList({required this.questions});

  final List<Question> questions;

  @override
  State<_QuestionsList> createState() => _QuestionsListState();
}

class _QuestionsListState extends State<_QuestionsList> {
  Future<void> _onRefresh() async {
    // Provide a short delay to mimic network refresh behaviour.
    await Future<void>.delayed(const Duration(milliseconds: 350));
  }

  @override
  Widget build(BuildContext context) {
    final questions = widget.questions;
    return RefreshIndicator(
      onRefresh: _onRefresh,
      child: ListView.builder(
        physics: const AlwaysScrollableScrollPhysics(),
        itemCount: questions.length,
        itemBuilder: (context, index) {
          final question = questions[index];
          return QuestionCard(question: question);
        },
      ),
    );
  }
}

class _ErrorState extends StatelessWidget {
  const _ErrorState({required this.onRetry});

  final VoidCallback onRetry;

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          const Icon(Icons.wifi_off, size: 48),
          const SizedBox(height: 16),
          const Text('Não foi possível carregar as questões no momento.'),
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
