import 'package:flutter/material.dart';

class DemoUserProfile {
  const DemoUserProfile({
    required this.name,
    required this.goal,
    required this.streakDays,
    required this.badge,
    required this.membershipLabel,
    required this.planStatus,
  });

  final String name;
  final String goal;
  final int streakDays;
  final String badge;
  final String membershipLabel;
  final String planStatus;
}

class DashboardLearnerSpotlight {
  const DashboardLearnerSpotlight({
    required this.name,
    required this.avatarUrl,
    required this.goal,
    required this.summary,
    required this.trend,
    required this.badge,
    required this.accentColor,
  });

  final String name;
  final String avatarUrl;
  final String goal;
  final String summary;
  final String trend;
  final String badge;
  final Color accentColor;
}

class DashboardQuickAction {
  const DashboardQuickAction({
    required this.icon,
    required this.title,
    required this.description,
    required this.route,
  });

  final IconData icon;
  final String title;
  final String description;
  final String route;
}

class DashboardMetricHighlight {
  const DashboardMetricHighlight({
    required this.label,
    required this.value,
    required this.caption,
    required this.color,
  });

  final String label;
  final String value;
  final String caption;
  final Color color;
}

class DashboardPlanHighlightData {
  const DashboardPlanHighlightData({
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
}

class DashboardModuleCardData {
  const DashboardModuleCardData({
    required this.title,
    required this.description,
    required this.category,
    required this.icon,
    this.progress,
    this.badge,
  });

  final String title;
  final String description;
  final String category;
  final IconData icon;
  final double? progress;
  final String? badge;
}

class DashboardLiveHighlight {
  const DashboardLiveHighlight({
    required this.title,
    required this.dateTime,
    required this.instructor,
    required this.durationMinutes,
  });

  final String title;
  final DateTime dateTime;
  final String instructor;
  final int durationMinutes;
}

class DashboardNewsItem {
  const DashboardNewsItem({
    required this.title,
    required this.summary,
    required this.link,
  });

  final String title;
  final String summary;
  final String link;
}

const demoUserProfile = DemoUserProfile(
  name: 'Camila Andrade',
  goal: 'Tribunal de Justiça do Ceará',
  streakDays: 32,
  badge: 'Top 5 TJ-CE',
  membershipLabel: 'Plano Pro Anual',
  planStatus: 'Renova em 14/06/2025',
);

const learnerSpotlights = [
  DashboardLearnerSpotlight(
    name: 'Juliana Matos',
    avatarUrl: 'https://cdn.academiadacomunicacao.com/avatars/juliana-matos.png',
    goal: 'SEFAZ Bahia',
    summary: '215 questões • 58% de acerto',
    trend: '+32 questões nesta semana',
    badge: 'Plano Grátis aprovado',
    accentColor: Color(0xFF6645F6),
  ),
  DashboardLearnerSpotlight(
    name: 'Rodrigo Lima',
    avatarUrl: 'https://cdn.academiadacomunicacao.com/avatars/rodrigo-lima.png',
    goal: 'Polícia Civil do DF',
    summary: '982 questões • 66% de acerto',
    trend: '+21 questões nesta semana',
    badge: 'Top 3 PCDF',
    accentColor: Color(0xFF1DD3C4),
  ),
  DashboardLearnerSpotlight(
    name: 'Larissa Gouveia',
    avatarUrl: 'https://cdn.academiadacomunicacao.com/avatars/larissa-gouveia.png',
    goal: 'INSS Analista',
    summary: '144 questões • 61% de acerto',
    trend: '+17 questões nesta semana',
    badge: 'Fila Planos Grátis',
    accentColor: Color(0xFFE5BE49),
  ),
];

const demoQuickActions = [
  DashboardQuickAction(
    icon: Icons.rocket_launch_outlined,
    title: 'Plano de estudos',
    description: 'Monte a semana em 2 minutos com base no seu objetivo.',
    route: '/metas/criar',
  ),
  DashboardQuickAction(
    icon: Icons.quiz_outlined,
    title: 'Questões do dia',
    description: '12 inéditas selecionadas para o TJ-CE.',
    route: '/questoes/recomendadas',
  ),
  DashboardQuickAction(
    icon: Icons.military_tech_outlined,
    title: 'Ranking TJ-CE',
    description: 'Veja sua posição e acelere nos desafios.',
    route: '/desafios/ranking',
  ),
  DashboardQuickAction(
    icon: Icons.local_library_outlined,
    title: 'Biblioteca',
    description: 'Mapas mentais e checklists liberados hoje.',
    route: '/biblioteca',
  ),
];

const weeklyMetrics = [
  DashboardMetricHighlight(
    label: 'Questões',
    value: '94/150',
    caption: '+18 vs. semana passada',
    color: Color(0xFF6645F6),
  ),
  DashboardMetricHighlight(
    label: 'Horas',
    value: '14h',
    caption: '3h restantes na meta',
    color: Color(0xFF1DD3C4),
  ),
  DashboardMetricHighlight(
    label: 'Acertos',
    value: '76%',
    caption: '+4 pts. na última semana',
    color: Color(0xFFE5BE49),
  ),
];

const planHighlights = [
  DashboardPlanHighlightData(
    planId: 'plano-mensal-plus',
    title: 'Plano Plus Mensal',
    tag: 'Recomendado',
    price: 64.9,
    currency: 'BRL',
    approvalStatus: 'aprovado',
  ),
  DashboardPlanHighlightData(
    planId: 'plano-pro-anual',
    title: 'Plano Pro Anual',
    tag: 'Melhor custo-benefício',
    price: 529.0,
    currency: 'BRL',
    approvalStatus: 'aprovado',
  ),
  DashboardPlanHighlightData(
    planId: 'plano-gratis-alunos',
    title: 'Plano Grátis para Alunos',
    tag: 'Aguardando aprovação',
    price: 0,
    currency: 'BRL',
    approvalStatus: 'pendente',
  ),
];

const continueStudying = [
  DashboardModuleCardData(
    title: 'Caderno Interpretação TJ-CE',
    description: '18 questões restantes para concluir o bloco.',
    category: 'Caderno',
    icon: Icons.menu_book_outlined,
    progress: 0.62,
  ),
  DashboardModuleCardData(
    title: 'Simulado CEBRASPE 2023',
    description: '30 questões • 2h • 9 acertos até agora.',
    category: 'Simulado',
    icon: Icons.timer_outlined,
    progress: 0.2,
  ),
  DashboardModuleCardData(
    title: 'Meta da semana',
    description: 'Resolver 150 questões e registrar 20h de estudo.',
    category: 'Metas',
    icon: Icons.flag_outlined,
    progress: 0.63,
  ),
];

const featuredCourses = [
  DashboardModuleCardData(
    title: 'Discursivas TJ-CE',
    description: 'Henrique Porto • 18h de aulas ao vivo + PDFs.',
    category: 'Curso',
    icon: Icons.live_tv_outlined,
    badge: 'Novo',
  ),
  DashboardModuleCardData(
    title: 'Gramática Intensiva',
    description: '120 aulas com revisões rápidas e flashcards.',
    category: 'Curso',
    icon: Icons.school_outlined,
    badge: 'Em alta',
  ),
];

const communityHighlights = [
  DashboardModuleCardData(
    title: 'Desafio Maratona TJ-CE',
    description: 'Camila está em 4º lugar com 118 questões resolvidas.',
    category: 'Desafio',
    icon: Icons.emoji_events_outlined,
  ),
  DashboardModuleCardData(
    title: 'Discussão de discursivas',
    description: '24 novas respostas no fórum de discursivas TJ-CE.',
    category: 'Comunidade',
    icon: Icons.forum_outlined,
  ),
];

const upcomingLives = [
  DashboardLiveHighlight(
    title: 'Aulão de revisão TJ-CE',
    dateTime: DateTime(2024, 7, 2, 20, 0),
    instructor: 'Henrique Porto',
    durationMinutes: 90,
  ),
  DashboardLiveHighlight(
    title: 'Estratégias SEFAZ 2024',
    dateTime: DateTime(2024, 7, 9, 20, 0),
    instructor: 'Camila Moura',
    durationMinutes: 75,
  ),
];

const newsroomHighlights = [
  DashboardNewsItem(
    title: 'Novo edital TJ-CE publicado',
    summary: 'Inscrições a partir de 15/07; confira provas e cronograma.',
    link: 'https://blog.academiadacomunicacao.com/noticias/edital-tjce',
  ),
  DashboardNewsItem(
    title: 'Relatório TJ-CE: tópicos mais cobrados',
    summary: 'Atualizamos estatísticas com base em 3.200 questões recentes.',
    link: 'https://blog.academiadacomunicacao.com/relatorios/tjce-topicos',
  ),
];
