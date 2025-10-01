import 'dart:convert';

import 'package:equatable/equatable.dart';
import 'package:flutter/services.dart';

class Simulado extends Equatable {
  const Simulado({
    required this.id,
    required this.title,
    required this.mode,
    required this.configuration,
    required this.statistics,
    required this.status,
    required this.updatedAt,
    required this.userId,
  });

  final String id;
  final String title;
  final String mode;
  final SimuladoConfiguration configuration;
  final SimuladoStatistics statistics;
  final String status;
  final DateTime? updatedAt;
  final String userId;

  bool get isInProgress => status == 'em_andamento';
  int get answered => statistics.answered;
  int get totalQuestions => configuration.totalQuestions;
  double get completionRatio =>
      totalQuestions == 0 ? 0 : answered / totalQuestions;

  factory Simulado.fromJson(Map<String, dynamic> json) {
    return Simulado(
      id: json['id'] as String,
      title: json['titulo']?.toString() ?? 'Simulado',
      mode: json['modalidade']?.toString() ?? 'customizado',
      configuration: SimuladoConfiguration.fromJson(
        json['configuracao'] as Map<String, dynamic>,
      ),
      statistics: SimuladoStatistics.fromJson(
        json['estatisticas'] as Map<String, dynamic>,
      ),
      status: json['status']?.toString() ?? 'em_andamento',
      updatedAt: json['atualizadoEm'] != null
          ? DateTime.tryParse(json['atualizadoEm'].toString())
          : null,
      userId: json['usuarioId']?.toString() ?? '',
    );
  }

  static Future<List<Simulado>> loadFromAsset(String assetPath) async {
    final content = await rootBundle.loadString(assetPath);
    final data = jsonDecode(content) as List<dynamic>;
    return data
        .cast<Map<String, dynamic>>()
        .map(Simulado.fromJson)
        .toList(growable: false);
  }

  @override
  List<Object?> get props => [
        id,
        title,
        mode,
        configuration,
        statistics,
        status,
        updatedAt,
        userId,
      ];
}

class SimuladoConfiguration extends Equatable {
  const SimuladoConfiguration({
    required this.disciplines,
    required this.board,
    required this.difficulty,
    required this.totalQuestions,
    required this.timeMinutes,
  });

  final List<String> disciplines;
  final String board;
  final String difficulty;
  final int totalQuestions;
  final int timeMinutes;

  factory SimuladoConfiguration.fromJson(Map<String, dynamic> json) {
    return SimuladoConfiguration(
      disciplines: (json['disciplinas'] as List<dynamic>? ?? const [])
          .map((item) => item.toString())
          .toList(growable: false),
      board: json['banca']?.toString() ?? '—',
      difficulty: json['dificuldade']?.toString() ?? 'intermediario',
      totalQuestions: (json['quantidadeQuestoes'] as num?)?.toInt() ?? 0,
      timeMinutes: (json['tempoMinutos'] as num?)?.toInt() ?? 0,
    );
  }

  Duration get duration => Duration(minutes: timeMinutes);

  @override
  List<Object?> get props => [
        disciplines,
        board,
        difficulty,
        totalQuestions,
        timeMinutes,
      ];
}

class SimuladoStatistics extends Equatable {
  const SimuladoStatistics({
    required this.answered,
    required this.correct,
    required this.averageTimeSeconds,
  });

  final int answered;
  final int correct;
  final int averageTimeSeconds;

  double? get accuracyPercent {
    if (answered == 0) {
      return null;
    }
    return (correct / answered) * 100;
  }

  factory SimuladoStatistics.fromJson(Map<String, dynamic> json) {
    return SimuladoStatistics(
      answered: (json['questoesRespondidas'] as num?)?.toInt() ?? 0,
      correct: (json['acertos'] as num?)?.toInt() ?? 0,
      averageTimeSeconds:
          (json['tempoMedioPorQuestaoSegundos'] as num?)?.toInt() ?? 0,
    );
  }

  @override
  List<Object?> get props => [answered, correct, averageTimeSeconds];
}
