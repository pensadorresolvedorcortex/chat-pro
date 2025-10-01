import 'package:flutter/foundation.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import 'question_models.dart';

class QuestionsRepository {
  QuestionsRepository({@visibleForTesting this.assetPath = _defaultAssetPath});

  static const String _defaultAssetPath = 'assets/data/questoes.json';

  final String assetPath;
  List<Question>? _cached;

  Future<List<Question>> fetchRecommended() async {
    if (_cached != null) {
      return _cached!;
    }
    final questions = await Question.loadFromAsset(assetPath);
    _cached = questions;
    return questions;
  }
}

final questionsRepositoryProvider = Provider<QuestionsRepository>((ref) {
  return QuestionsRepository();
});

final recommendedQuestionsProvider =
    FutureProvider<List<Question>>((ref) async {
  final repository = ref.watch(questionsRepositoryProvider);
  return repository.fetchRecommended();
});
