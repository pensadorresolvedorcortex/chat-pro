export type Service = {
  name: string;
  description: string;
  icon: string;
  gradient: string;
  accent: string;
};

export const services: Service[] = [
  {
    name: 'Spotify Premium',
    description: 'Playlists compartilhadas em alta fidelidade com gestão inteligente de vagas.',
    icon: '/services/spotify.svg',
    gradient: 'linear-gradient(135deg, rgba(74, 222, 128, 0.18), rgba(34, 197, 94, 0.12))',
    accent: '#22c55e',
  },
  {
    name: 'Netflix Ultra',
    description: 'Controle de perfis, avisos de lotação e convites ultra-rápidos.',
    icon: '/services/netflix.svg',
    gradient: 'linear-gradient(135deg, rgba(239, 68, 68, 0.2), rgba(248, 113, 113, 0.14))',
    accent: '#ef4444',
  },
  {
    name: 'Disney+ Family',
    description: 'Automatize renovações e mantenha a família sempre sincronizada.',
    icon: '/services/disney-plus.svg',
    gradient: 'linear-gradient(135deg, rgba(59, 130, 246, 0.18), rgba(59, 130, 246, 0.12))',
    accent: '#60a5fa',
  },
  {
    name: 'Notion HQ',
    description: 'Blueprints inteligentes para organizar conteúdos e centralizar decisões.',
    icon: '/services/notion.svg',
    gradient: 'linear-gradient(135deg, rgba(148, 163, 184, 0.16), rgba(148, 163, 184, 0.1))',
    accent: '#e2e8f0',
  },
  {
    name: 'Figma Teams',
    description: 'Prototipagem colaborativa com roteamento automático de feedback.',
    icon: '/services/figma.svg',
    gradient: 'linear-gradient(135deg, rgba(94, 234, 212, 0.16), rgba(94, 234, 212, 0.08))',
    accent: '#22d3ee',
  },
];

export const aiSuggestions = [
  'Gerar convite com mensagem acolhedora para novo membro da família',
  'Criar checklist de onboarding para um squad de streaming',
  'Sugerir combinação de serviços para reduzir custos este mês',
  'Automatizar cobrança semanal com lembrete gentil',
  'Priorizar vagas para contatos VIP com aprovação rápida',
  'Reescrever aviso de renovação com tom entusiasmado e curto',
];
