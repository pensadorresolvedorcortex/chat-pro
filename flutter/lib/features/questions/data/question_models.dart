import 'dart:convert';

import 'package:equatable/equatable.dart';
import 'package:flutter/services.dart';

class Question extends Equatable {
  const Question({
    required this.id,
    required this.statement,
    required this.alternatives,
    required this.correctLetter,
    required this.discipline,
    required this.topics,
    required this.board,
    required this.year,
    required this.difficulty,
    this.explanation,
    this.statistics,
  });

  final String id;
  final String statement;
  final List<QuestionAlternative> alternatives;
  final String correctLetter;
  final String discipline;
  final List<String> topics;
  final String board;
  final int year;
  final String difficulty;
  final String? explanation;
  final QuestionStatistics? statistics;

  bool get hasExplanation => (explanation ?? '').trim().isNotEmpty;

  QuestionAlternative? alternativeByLetter(String letter) {
    return alternatives.firstWhere(
      (alternative) => alternative.letter == letter,
      orElse: () => QuestionAlternative(
        letter: letter,
        description: '',
        isCorrect: false,
      ),
    );
  }

  double? get accuracyPercent => statistics?.accuracyPercent;

  factory Question.fromJson(Map<String, dynamic> json) {
    final alternatives = (json['alternativas'] as List<dynamic>)
        .cast<Map<String, dynamic>>()
        .map(QuestionAlternative.fromJson)
        .toList();
    final correct = alternatives.firstWhere(
      (alt) => alt.isCorrect,
      orElse: () => alternatives.first,
    );
    return Question(
      id: json['id'] as String,
      statement: json['enunciado'] as String,
      alternatives: alternatives,
      correctLetter: correct.letter,
      discipline: json['disciplina'] as String,
      topics: (json['assuntos'] as List<dynamic>?)
              ?.map((item) => item.toString())
              .toList(growable: false) ??
          const <String>[],
      board: json['banca']?.toString() ?? 'Indefinida',
      year: (json['ano'] as num?)?.toInt() ?? DateTime.now().year,
      difficulty: json['dificuldade']?.toString() ?? 'indefinida',
      explanation: json['explicacao']?.toString(),
      statistics: json['estatisticas'] is Map<String, dynamic>
          ? QuestionStatistics.fromJson(
              json['estatisticas'] as Map<String, dynamic>,
            )
          : null,
    );
  }

  static Future<List<Question>> loadFromAsset(String assetPath) async {
    final content = await rootBundle.loadString(assetPath);
    final data = jsonDecode(content) as List<dynamic>;
    return data
        .cast<Map<String, dynamic>>()
        .map(Question.fromJson)
        .toList(growable: false);
  }

  @override
  List<Object?> get props => [
        id,
        statement,
        alternatives,
        correctLetter,
        discipline,
        topics,
        board,
        year,
        difficulty,
        explanation,
        statistics,
      ];
}

class QuestionAlternative extends Equatable {
  const QuestionAlternative({
    required this.letter,
    required this.description,
    required this.isCorrect,
  });

  final String letter;
  final String description;
  final bool isCorrect;

  factory QuestionAlternative.fromJson(Map<String, dynamic> json) {
    return QuestionAlternative(
      letter: json['letra']?.toString() ?? '',
      description: json['descricao']?.toString() ?? '',
      isCorrect: json['correta'] == true,
    );
  }

  @override
  List<Object?> get props => [letter, description, isCorrect];
}

class QuestionStatistics extends Equatable {
  const QuestionStatistics({
    required this.answered,
    required this.correct,
    required this.wrong,
    required this.medianTimeSeconds,
  });

  final int answered;
  final int correct;
  final int wrong;
  final int medianTimeSeconds;

  double? get accuracyPercent {
    if (answered <= 0) {
      return null;
    }
    return (correct / answered) * 100;
  }

  factory QuestionStatistics.fromJson(Map<String, dynamic> json) {
    return QuestionStatistics(
      answered: (json['respondidas'] as num?)?.toInt() ?? 0,
      correct: (json['acertos'] as num?)?.toInt() ?? 0,
      wrong: (json['erros'] as num?)?.toInt() ?? 0,
      medianTimeSeconds: (json['tempoMedianoSegundos'] as num?)?.toInt() ?? 0,
    );
  }

  @override
  List<Object?> get props => [answered, correct, wrong, medianTimeSeconds];
}
