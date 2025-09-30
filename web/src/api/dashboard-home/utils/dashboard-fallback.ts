export type DashboardHomePayload = {
  usuario: {
    id: string;
    nome: string;
    saudacao?: string;
    objetivo?: string;
    streakDias?: number;
    nivel?: string;
    badge?: string;
    assinaturaAtual?: {
      planoId: string;
      status: string;
      renovaEm?: string;
    } | null;
  };
  atalhosRapidos: Array<{
    icone: string;
    titulo: string;
    descricao: string;
    rota: string;
  }>;
  metricasSemana: Array<{
    rotulo: string;
    valor: string;
    comentario?: string;
  }>;
  destaquesAlunos: Array<{
    usuarioId: string;
    nome: string;
    avatarUrl: string;
    objetivo?: string;
    resumo: string;
    tendencia?: string;
    badge?: string;
  }>;
  planosDestaque: Array<{
    planoId: string;
    titulo: string;
    tag?: string;
    preco: number;
    moeda: string;
    statusAprovacao?: string;
  }>;
  assinaturasRecentes: Array<{
    assinaturaId: string;
    usuarioId: string;
    planoId: string;
    status: string;
    statusPagamento?: string;
  }>;
  modulos: Array<{
    titulo: string;
    cards: Array<{
      tipo: string;
      titulo: string;
      descricao: string;
      progresso?: number;
      tag?: string;
    }>;
  }>;
  proximasLives: Array<{
    titulo: string;
    data: string;
    instrutor: string;
    duracaoMinutos?: number;
    link?: string;
  }>;
  noticias: Array<{
    titulo: string;
    resumo: string;
    link: string;
  }>;
  ultimaSincronizacao: string;
  fonte?: string;
};

export const DASHBOARD_HOME_FALLBACK: DashboardHomePayload = Object.freeze({
  usuario: {
    id: 'user-camila-andrade',
    nome: 'Camila Andrade',
    saudacao: 'Bom dia',
    objetivo: 'Tribunal de Justiça do Ceará',
    streakDias: 32,
    nivel: 'Avançado',
    badge: 'Top 5 TJ-CE',
    assinaturaAtual: {
      planoId: 'plano-pro-anual',
      status: 'ativa',
      renovaEm: '2025-06-14',
    },
  },
  atalhosRapidos: [
    {
      icone: 'rocket_launch',
      titulo: 'Plano de estudos',
      descricao: 'Monte sua semana em menos de 2 minutos.',
      rota: '/metas/criar',
    },
    {
      icone: 'quiz',
      titulo: 'Questões recomendadas',
      descricao: 'Novas 12 questões de interpretação para hoje.',
      rota: '/questoes/recomendadas',
    },
    {
      icone: 'military_tech',
      titulo: 'Ranking TJ-CE',
      descricao: 'Você está em 4º lugar esta semana.',
      rota: '/desafios/ranking',
    },
    {
      icone: 'local_library',
      titulo: 'Biblioteca',
      descricao: 'Confira os novos mapas mentais liberados.',
      rota: '/biblioteca',
    },
    {
      icone: 'track_changes',
      titulo: 'Status operacional',
      descricao: 'Monitore Flutter iOS, Strapi e operações.',
      rota: '/operacoes/readiness',
    },
  ],
  metricasSemana: [
    {
      rotulo: 'Questões',
      valor: '94/150',
      comentario: '+18 vs. semana passada',
    },
    {
      rotulo: 'Horas',
      valor: '14h',
      comentario: '3h restantes na meta',
    },
    {
      rotulo: 'Acertos',
      valor: '76%',
      comentario: '+4 pts. na última semana',
    },
  ],
  destaquesAlunos: [
    {
      usuarioId: 'user-juliana-matos',
      nome: 'Juliana Matos',
      avatarUrl: 'https://cdn.academiadacomunicacao.com/avatars/juliana-matos.png',
      objetivo: 'SEFAZ Bahia',
      resumo: '215 questões • 58% de acerto',
      tendencia: '+32 questões nesta semana',
      badge: 'Plano Grátis aprovado',
    },
    {
      usuarioId: 'user-rodrigo-lima',
      nome: 'Rodrigo Lima',
      avatarUrl: 'https://cdn.academiadacomunicacao.com/avatars/rodrigo-lima.png',
      objetivo: 'Polícia Civil do DF',
      resumo: '982 questões • 66% de acerto',
      tendencia: '+21 questões nesta semana',
      badge: 'Top 3 PCDF',
    },
    {
      usuarioId: 'user-larissa-gouveia',
      nome: 'Larissa Gouveia',
      avatarUrl: 'https://cdn.academiadacomunicacao.com/avatars/larissa-gouveia.png',
      objetivo: 'INSS Analista',
      resumo: '144 questões • 61% de acerto',
      tendencia: '+17 questões nesta semana',
      badge: 'Fila Planos Grátis',
    },
  ],
  planosDestaque: [
    {
      planoId: 'plano-mensal-plus',
      titulo: 'Plano Plus Mensal',
      tag: 'Recomendado',
      preco: 64.9,
      moeda: 'BRL',
      statusAprovacao: 'aprovado',
    },
    {
      planoId: 'plano-pro-anual',
      titulo: 'Plano Pro Anual',
      tag: 'Melhor custo-benefício',
      preco: 529.0,
      moeda: 'BRL',
      statusAprovacao: 'aprovado',
    },
    {
      planoId: 'plano-gratis-alunos',
      titulo: 'Plano Grátis para Alunos',
      tag: 'Liberado',
      preco: 0,
      moeda: 'BRL',
      statusAprovacao: 'aprovado',
    },
  ],
  assinaturasRecentes: [
    {
      assinaturaId: 'assinatura-ana-pix',
      usuarioId: 'user-ana-ribeiro',
      planoId: 'plano-mensal-plus',
      status: 'ativa',
      statusPagamento: 'pago',
    },
    {
      assinaturaId: 'assinatura-marcos-pix',
      usuarioId: 'user-marcos-vieira',
      planoId: 'plano-pro-anual',
      status: 'ativa',
      statusPagamento: 'pago',
    },
    {
      assinaturaId: 'assinatura-juliana-gratis',
      usuarioId: 'user-juliana-matos',
      planoId: 'plano-gratis-alunos',
      status: 'ativa',
      statusPagamento: 'pago',
    },
  ],
  modulos: [
    {
      titulo: 'Retome seus estudos',
      cards: [
        {
          tipo: 'caderno',
          titulo: 'Caderno - Interpretação de Texto',
          descricao: '18 questões restantes',
          progresso: 0.62,
        },
        {
          tipo: 'simulado',
          titulo: 'Simulado CEBRASPE 2023',
          descricao: '30 questões • 2h00',
          progresso: 0.2,
        },
      ],
    },
    {
      titulo: 'Cursos em destaque',
      cards: [
        {
          tipo: 'curso',
          titulo: 'Discursivas TJ-CE',
          descricao: 'Prof. Henrique Porto',
          tag: 'Novo',
        },
        {
          tipo: 'curso',
          titulo: 'Pacote Comunicação',
          descricao: '+120 aulas • 34h',
          tag: 'Top 1',
        },
      ],
    },
  ],
  proximasLives: [
    {
      titulo: 'Aulão de revisão TJ-CE',
      data: '2024-07-02T23:00:00Z',
      instrutor: 'Henrique Porto',
      duracaoMinutos: 90,
      link: 'https://lives.academiadacomunicacao.com/tjce-aulao',
    },
  ],
  noticias: [
    {
      titulo: 'Novo edital TJ-CE publicado',
      resumo: 'Inscrições abrem em 15 de julho. Veja os detalhes do cronograma.',
      link: 'https://blog.academiadacomunicacao.com/noticias/edital-tjce',
    },
    {
      titulo: '3 planos Pix atualizados',
      resumo: 'Planos Plus, Pro e Grátis para Alunos revisados hoje pelo super admin.',
      link: 'https://blog.academiadacomunicacao.com/noticias/planos-pix',
    },
  ],
  ultimaSincronizacao: '2024-06-21T16:00:00Z',
  fonte: 'Sincronizado automaticamente com o CMS Strapi 5',
});

export const cloneDashboardFallback = (): DashboardHomePayload =>
  JSON.parse(JSON.stringify(DASHBOARD_HOME_FALLBACK));
