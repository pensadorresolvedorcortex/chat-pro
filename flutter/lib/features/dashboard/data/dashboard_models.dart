import 'package:equatable/equatable.dart';

class DashboardHomeData extends Equatable {
  const DashboardHomeData({
    required this.user,
    required this.quickActions,
    required this.weeklyMetrics,
    required this.learnerSpotlights,
    required this.planHighlights,
    required this.modules,
    required this.upcomingLives,
    required this.news,
    required this.recentSubscriptions,
    this.lastSync,
    this.source,
  });

  final DashboardUserProfile user;
  final List<DashboardQuickAction> quickActions;
  final List<DashboardMetric> weeklyMetrics;
  final List<DashboardLearnerSpotlight> learnerSpotlights;
  final List<DashboardPlanHighlight> planHighlights;
  final List<DashboardModule> modules;
  final List<DashboardLiveHighlight> upcomingLives;
  final List<DashboardNewsItem> news;
  final List<DashboardRecentSubscription> recentSubscriptions;
  final DateTime? lastSync;
  final String? source;

  factory DashboardHomeData.fromJson(Map<String, dynamic> json) {
    DateTime? parseDate(String? value) {
      if (value == null || value.isEmpty) {
        return null;
      }
      try {
        return DateTime.parse(value).toLocal();
      } catch (_) {
        return null;
      }
    }

    List<T> parseList<T>(dynamic value, T Function(Map<String, dynamic>) map) {
      if (value is List) {
        return value
            .whereType<Map<String, dynamic>>()
            .map(map)
            .toList(growable: false);
      }
      return const [];
    }

    return DashboardHomeData(
      user: DashboardUserProfile.fromJson(
        (json['usuario'] as Map<String, dynamic>?) ?? const {},
      ),
      quickActions: parseList(
        json['atalhosRapidos'],
        DashboardQuickAction.fromJson,
      ),
      weeklyMetrics: parseList(
        json['metricasSemana'],
        DashboardMetric.fromJson,
      ),
      learnerSpotlights: parseList(
        json['destaquesAlunos'],
        DashboardLearnerSpotlight.fromJson,
      ),
      planHighlights: parseList(
        json['planosDestaque'],
        DashboardPlanHighlight.fromJson,
      ),
      modules: parseList(json['modulos'], DashboardModule.fromJson),
      upcomingLives: parseList(
        json['proximasLives'],
        DashboardLiveHighlight.fromJson,
      ),
      news: parseList(json['noticias'], DashboardNewsItem.fromJson),
      recentSubscriptions: parseList(
        json['assinaturasRecentes'],
        DashboardRecentSubscription.fromJson,
      ),
      lastSync: parseDate(json['ultimaSincronizacao'] as String?),
      source: json['fonte'] as String?,
    );
  }

  DashboardHomeData copyWith({
    DashboardUserProfile? user,
    List<DashboardQuickAction>? quickActions,
    List<DashboardMetric>? weeklyMetrics,
    List<DashboardLearnerSpotlight>? learnerSpotlights,
    List<DashboardPlanHighlight>? planHighlights,
    List<DashboardModule>? modules,
    List<DashboardLiveHighlight>? upcomingLives,
    List<DashboardNewsItem>? news,
    List<DashboardRecentSubscription>? recentSubscriptions,
    DateTime? Function()? lastSync,
    String? Function()? source,
  }) {
    return DashboardHomeData(
      user: user ?? this.user,
      quickActions: quickActions ?? this.quickActions,
      weeklyMetrics: weeklyMetrics ?? this.weeklyMetrics,
      learnerSpotlights: learnerSpotlights ?? this.learnerSpotlights,
      planHighlights: planHighlights ?? this.planHighlights,
      modules: modules ?? this.modules,
      upcomingLives: upcomingLives ?? this.upcomingLives,
      news: news ?? this.news,
      recentSubscriptions: recentSubscriptions ?? this.recentSubscriptions,
      lastSync: lastSync != null ? lastSync() : this.lastSync,
      source: source != null ? source() : this.source,
    );
  }

  @override
  List<Object?> get props => [
        user,
        quickActions,
        weeklyMetrics,
        learnerSpotlights,
        planHighlights,
        modules,
        upcomingLives,
        news,
        recentSubscriptions,
        lastSync,
        source,
      ];
}

class DashboardUserProfile extends Equatable {
  const DashboardUserProfile({
    required this.id,
    required this.name,
    required this.greeting,
    required this.goal,
    required this.streakDays,
    required this.level,
    required this.badge,
    required this.currentSubscription,
  });

  final String id;
  final String name;
  final String greeting;
  final String goal;
  final int streakDays;
  final String level;
  final String badge;
  final DashboardSubscription? currentSubscription;

  factory DashboardUserProfile.fromJson(Map<String, dynamic> json) {
    DashboardSubscription? subscription;
    final rawSubscription = json['assinaturaAtual'];
    if (rawSubscription is Map<String, dynamic>) {
      subscription = DashboardSubscription.fromJson(rawSubscription);
    }

    return DashboardUserProfile(
      id: json['id']?.toString() ?? '',
      name: json['nome']?.toString() ?? '',
      greeting: json['saudacao']?.toString() ?? '',
      goal: json['objetivo']?.toString() ?? '',
      streakDays: _parseInt(json['streakDias']),
      level: json['nivel']?.toString() ?? '',
      badge: json['badge']?.toString() ?? '',
      currentSubscription: subscription,
    );
  }

  @override
  List<Object?> get props => [
        id,
        name,
        greeting,
        goal,
        streakDays,
        level,
        badge,
        currentSubscription,
      ];
}

class DashboardSubscription extends Equatable {
  const DashboardSubscription({
    required this.planId,
    required this.status,
    required this.renewsAt,
  });

  final String planId;
  final String status;
  final DateTime? renewsAt;

  factory DashboardSubscription.fromJson(Map<String, dynamic> json) {
    DateTime? renewsAt;
    final rawDate = json['renovaEm'];
    if (rawDate is String && rawDate.isNotEmpty) {
      try {
        renewsAt = DateTime.parse(rawDate).toLocal();
      } catch (_) {
        renewsAt = null;
      }
    }

    return DashboardSubscription(
      planId: json['planoId']?.toString() ?? '',
      status: json['status']?.toString() ?? '',
      renewsAt: renewsAt,
    );
  }

  @override
  List<Object?> get props => [planId, status, renewsAt];
}

class DashboardQuickAction extends Equatable {
  const DashboardQuickAction({
    required this.icon,
    required this.title,
    required this.description,
    required this.route,
  });

  final String icon;
  final String title;
  final String description;
  final String route;

  factory DashboardQuickAction.fromJson(Map<String, dynamic> json) {
    return DashboardQuickAction(
      icon: json['icone']?.toString() ?? 'rocket_launch',
      title: json['titulo']?.toString() ?? '',
      description: json['descricao']?.toString() ?? '',
      route: json['rota']?.toString() ?? '',
    );
  }

  @override
  List<Object?> get props => [icon, title, description, route];
}

class DashboardMetric extends Equatable {
  const DashboardMetric({
    required this.label,
    required this.value,
    required this.caption,
  });

  final String label;
  final String value;
  final String caption;

  factory DashboardMetric.fromJson(Map<String, dynamic> json) {
    return DashboardMetric(
      label: json['rotulo']?.toString() ?? '',
      value: json['valor']?.toString() ?? '',
      caption: json['comentario']?.toString() ?? '',
    );
  }

  @override
  List<Object?> get props => [label, value, caption];
}

class DashboardLearnerSpotlight extends Equatable {
  const DashboardLearnerSpotlight({
    required this.userId,
    required this.name,
    required this.avatarUrl,
    required this.goal,
    required this.summary,
    required this.trend,
    required this.badge,
  });

  final String userId;
  final String name;
  final String avatarUrl;
  final String goal;
  final String summary;
  final String trend;
  final String badge;

  factory DashboardLearnerSpotlight.fromJson(Map<String, dynamic> json) {
    return DashboardLearnerSpotlight(
      userId: json['usuarioId']?.toString() ?? '',
      name: json['nome']?.toString() ?? '',
      avatarUrl: json['avatarUrl']?.toString() ?? '',
      goal: json['objetivo']?.toString() ?? '',
      summary: json['resumo']?.toString() ?? '',
      trend: json['tendencia']?.toString() ?? '',
      badge: json['badge']?.toString() ?? '',
    );
  }

  @override
  List<Object?> get props => [userId, name, avatarUrl, goal, summary, trend, badge];
}

class DashboardPlanHighlight extends Equatable {
  const DashboardPlanHighlight({
    required this.planId,
    required this.title,
    required this.tag,
    required this.price,
    required this.currency,
    required this.approvalStatus,
  });

  final String planId;
  final String title;
  final String tag;
  final double price;
  final String currency;
  final String approvalStatus;

  factory DashboardPlanHighlight.fromJson(Map<String, dynamic> json) {
    return DashboardPlanHighlight(
      planId: json['planoId']?.toString() ?? '',
      title: json['titulo']?.toString() ?? '',
      tag: json['tag']?.toString() ?? '',
      price: _parseDouble(json['preco']),
      currency: json['moeda']?.toString() ?? 'BRL',
      approvalStatus: json['statusAprovacao']?.toString() ?? '',
    );
  }

  @override
  List<Object?> get props => [planId, title, tag, price, currency, approvalStatus];
}

class DashboardModule extends Equatable {
  const DashboardModule({
    required this.title,
    required this.cards,
  });

  final String title;
  final List<DashboardModuleCard> cards;

  factory DashboardModule.fromJson(Map<String, dynamic> json) {
    final cards = (json['cards'] as List?)
            ?.whereType<Map<String, dynamic>>()
            .map(DashboardModuleCard.fromJson)
            .toList(growable: false) ??
        const [];

    return DashboardModule(
      title: json['titulo']?.toString() ?? '',
      cards: cards,
    );
  }

  @override
  List<Object?> get props => [title, cards];
}

class DashboardModuleCard extends Equatable {
  const DashboardModuleCard({
    required this.type,
    required this.title,
    required this.description,
    required this.progress,
    required this.tag,
  });

  final String type;
  final String title;
  final String description;
  final double? progress;
  final String? tag;

  factory DashboardModuleCard.fromJson(Map<String, dynamic> json) {
    return DashboardModuleCard(
      type: json['tipo']?.toString() ?? '',
      title: json['titulo']?.toString() ?? '',
      description: json['descricao']?.toString() ?? '',
      progress: _parseDoubleNullable(json['progresso']),
      tag: json['tag']?.toString(),
    );
  }

  @override
  List<Object?> get props => [type, title, description, progress, tag];
}

class DashboardLiveHighlight extends Equatable {
  const DashboardLiveHighlight({
    required this.title,
    required this.dateTime,
    required this.instructor,
    required this.durationMinutes,
    required this.link,
  });

  final String title;
  final DateTime? dateTime;
  final String instructor;
  final int durationMinutes;
  final String link;

  factory DashboardLiveHighlight.fromJson(Map<String, dynamic> json) {
    DateTime? dateTime;
    final rawDate = json['data'];
    if (rawDate is String && rawDate.isNotEmpty) {
      try {
        dateTime = DateTime.parse(rawDate).toLocal();
      } catch (_) {
        dateTime = null;
      }
    }

    return DashboardLiveHighlight(
      title: json['titulo']?.toString() ?? '',
      dateTime: dateTime,
      instructor: json['instrutor']?.toString() ?? '',
      durationMinutes: _parseInt(json['duracaoMinutos']),
      link: json['link']?.toString() ?? '',
    );
  }

  @override
  List<Object?> get props => [title, dateTime, instructor, durationMinutes, link];
}

class DashboardNewsItem extends Equatable {
  const DashboardNewsItem({
    required this.title,
    required this.summary,
    required this.link,
  });

  final String title;
  final String summary;
  final String link;

  factory DashboardNewsItem.fromJson(Map<String, dynamic> json) {
    return DashboardNewsItem(
      title: json['titulo']?.toString() ?? '',
      summary: json['resumo']?.toString() ?? '',
      link: json['link']?.toString() ?? '',
    );
  }

  @override
  List<Object?> get props => [title, summary, link];
}

class DashboardRecentSubscription extends Equatable {
  const DashboardRecentSubscription({
    required this.subscriptionId,
    required this.userId,
    required this.planId,
    required this.status,
    required this.paymentStatus,
  });

  final String subscriptionId;
  final String userId;
  final String planId;
  final String status;
  final String paymentStatus;

  factory DashboardRecentSubscription.fromJson(Map<String, dynamic> json) {
    return DashboardRecentSubscription(
      subscriptionId: json['assinaturaId']?.toString() ?? '',
      userId: json['usuarioId']?.toString() ?? '',
      planId: json['planoId']?.toString() ?? '',
      status: json['status']?.toString() ?? '',
      paymentStatus: json['statusPagamento']?.toString() ?? '',
    );
  }

  @override
  List<Object?> get props => [subscriptionId, userId, planId, status, paymentStatus];
}

int _parseInt(dynamic value) {
  if (value == null) {
    return 0;
  }
  final parsed = int.tryParse(value.toString());
  return parsed ?? 0;
}

double _parseDouble(dynamic value) {
  if (value == null) {
    return 0;
  }
  final parsed = double.tryParse(value.toString());
  return parsed ?? 0;
}

double? _parseDoubleNullable(dynamic value) {
  if (value == null) {
    return null;
  }
  final parsed = double.tryParse(value.toString());
  return parsed;
}
