import { factories } from '@strapi/strapi';

import {
  DashboardHomePayload,
  cloneDashboardFallback,
} from '../utils/dashboard-fallback';

const PLANO_UID = 'api::plano.plano';
const ASSINATURA_UID = 'api::assinatura.assinatura';
const COBRANCA_UID = 'api::cobranca-pix.cobranca-pix';

type StrapiEntity<T = any> = T & { id?: number | string };

type PixChargeEntity = {
  status?: string | null;
  valor?: unknown;
  moeda?: string | null;
  plano?: StrapiEntity | string | null;
  assinatura?: StrapiEntity | string | null;
  usuarioId?: string | null;
  confirmadoEm?: string | null;
  expiraEm?: string | null;
  createdAt?: string | null;
};

type AssinaturaEntity = {
  id?: number | string;
  usuarioId?: string | null;
  status?: string | null;
  metodoPagamento?: string | null;
  iniciadaEm?: string | null;
  expiraEm?: string | null;
  plano?: StrapiEntity | string | null;
  cobrancas?: PixChargeEntity[] | null;
};

type PlanoEntity = {
  id?: number | string;
  slug?: string | null;
  nome?: string | null;
  tipo?: string | null;
  destaque?: boolean | null;
  preco?: unknown;
  moeda?: string | null;
  statusAprovacao?: string | null;
};

const toNumber = (value: unknown, fallback = 0): number => {
  if (value === null || value === undefined) {
    return fallback;
  }
  const parsed = Number.parseFloat(String(value));
  if (Number.isNaN(parsed)) {
    return fallback;
  }
  return Number(parsed.toFixed(2));
};

const toDateOnly = (value: unknown): string | undefined => {
  if (typeof value !== 'string') {
    return undefined;
  }
  const parsed = new Date(value);
  if (Number.isNaN(parsed.getTime())) {
    return undefined;
  }
  return parsed.toISOString().slice(0, 10);
};

const toPlanId = (plan: PlanoEntity | string | null | undefined): string => {
  if (!plan) {
    return 'plano-desconhecido';
  }
  if (typeof plan === 'string') {
    return plan;
  }
  if (plan.slug) {
    return plan.slug;
  }
  if (plan.id !== undefined && plan.id !== null) {
    return String(plan.id);
  }
  return 'plano-desconhecido';
};

const greetingForDate = (date: Date): string => {
  const hour = date.getHours();
  if (hour < 12) {
    return 'Bom dia';
  }
  if (hour < 18) {
    return 'Boa tarde';
  }
  return 'Boa noite';
};

const mapPlanHighlight = (plan: PlanoEntity): DashboardHomePayload['planosDestaque'][number] => {
  const preco = toNumber(plan.preco, 0);
  const tagFromType = plan.tipo === 'gratis_aluno' ? 'Plano Grátis para Alunos' : undefined;
  const destaqueTag = plan.destaque ? 'Destaque' : undefined;
  return {
    planoId: toPlanId(plan),
    titulo: plan.nome ?? 'Plano',
    tag: destaqueTag ?? tagFromType,
    preco,
    moeda: plan.moeda ?? 'BRL',
    statusAprovacao: plan.statusAprovacao ?? 'pendente',
  };
};

const mapPaymentStatus = (charge: PixChargeEntity | null | undefined): string => {
  const status = charge?.status;
  switch (status) {
    case 'confirmado':
      return 'pago';
    case 'expirado':
    case 'cancelado':
    case 'reembolsado':
      return 'expirado';
    default:
      return 'aguardando';
  }
};

const mapAssinaturaRecent = (
  assinatura: AssinaturaEntity,
): DashboardHomePayload['assinaturasRecentes'][number] => {
  const cobrancas = Array.isArray(assinatura.cobrancas) ? assinatura.cobrancas : [];
  const ultimaCobranca = cobrancas.length > 0 ? cobrancas[0] : null;
  return {
    assinaturaId: assinatura.id ? String(assinatura.id) : `assinatura-${assinatura.usuarioId ?? 'desconhecida'}`,
    usuarioId: assinatura.usuarioId ?? `user-${assinatura.id ?? 'desconhecido'}`,
    planoId: toPlanId(assinatura.plano),
    status: assinatura.status ?? 'pendente',
    statusPagamento: mapPaymentStatus(ultimaCobranca),
  };
};

const cloneFallback = (): DashboardHomePayload => cloneDashboardFallback();

const normaliseUserId = (raw: unknown): string | null => {
  if (!raw) {
    return null;
  }
  if (typeof raw === 'string' && raw.trim().length > 0) {
    return raw;
  }
  if (typeof raw === 'number') {
    return `user-${raw}`;
  }
  if (typeof raw === 'object' && raw !== null && 'id' in (raw as Record<string, unknown>)) {
    const id = (raw as Record<string, unknown>).id;
    if (typeof id === 'string' && id.trim().length > 0) {
      return id;
    }
    if (typeof id === 'number') {
      return `user-${id}`;
    }
  }
  return null;
};

export default factories.createCoreController('api::dashboard-home.dashboard-home', ({ strapi }) => ({
  async home(ctx) {
    const payload = cloneFallback();

    const authUser = ctx.state?.user as Record<string, any> | undefined;
    const resolvedUserId =
      normaliseUserId(authUser?.profileId) ??
      normaliseUserId(authUser?.uid) ??
      normaliseUserId(authUser?.username) ??
      (authUser?.id !== undefined ? `user-${authUser.id}` : null);

    if (authUser) {
      payload.usuario = {
        ...payload.usuario,
        id: resolvedUserId ?? payload.usuario.id,
        nome:
          authUser.fullName ??
          authUser.nome ??
          authUser.name ??
          authUser.username ??
          authUser.email ??
          payload.usuario.nome,
        saudacao: greetingForDate(new Date()),
        objetivo: authUser.objetivo ?? authUser.goal ?? payload.usuario.objetivo,
        streakDias:
          typeof authUser.streakDias === 'number'
            ? authUser.streakDias
            : payload.usuario.streakDias,
        nivel: authUser.nivel ?? payload.usuario.nivel,
        badge: authUser.badge ?? payload.usuario.badge,
      };
    }

    const [assinaturaAtual] = resolvedUserId
      ? await strapi.entityService.findMany<AssinaturaEntity>(ASSINATURA_UID, {
          filters: {
            usuarioId: resolvedUserId,
            status: { $in: ['ativa', 'pendente'] },
          },
          sort: { updatedAt: 'desc' },
          limit: 1,
          populate: { plano: true },
        })
      : [];

    payload.usuario.assinaturaAtual = assinaturaAtual
      ? {
          planoId: toPlanId(assinaturaAtual.plano),
          status: assinaturaAtual.status ?? 'pendente',
          renovaEm: toDateOnly(assinaturaAtual.expiraEm),
        }
      : null;

    const planos = await strapi.entityService.findMany<PlanoEntity>(PLANO_UID, {
      filters: {
        $or: [
          { publishedAt: { $notNull: true } },
          { publishedAt: { $null: true } },
        ],
      },
      sort: { destaque: 'desc', updatedAt: 'desc' },
      limit: 6,
    });

    if (Array.isArray(planos) && planos.length > 0) {
      payload.planosDestaque = planos.map(mapPlanHighlight);
    }

    const assinaturas = await strapi.entityService.findMany<AssinaturaEntity>(ASSINATURA_UID, {
      sort: { createdAt: 'desc' },
      limit: 6,
      populate: {
        plano: true,
        cobrancas: {
          sort: { createdAt: 'desc' },
          limit: 1,
        },
      },
    });

    if (Array.isArray(assinaturas) && assinaturas.length > 0) {
      payload.assinaturasRecentes = assinaturas.map(mapAssinaturaRecent);
      const ativas = assinaturas.filter((item) => item.status === 'ativa').length;
      const pendentes = assinaturas.filter((item) => item.status === 'pendente').length;
      payload.metricasSemana = [
        {
          rotulo: 'Assinaturas ativas',
          valor: String(ativas),
          comentario: pendentes > 0 ? `${pendentes} pendentes` : undefined,
        },
        ...payload.metricasSemana,
      ];
    }

    if (resolvedUserId) {
      const cobrancas = await strapi.entityService.findMany<PixChargeEntity>(COBRANCA_UID, {
        filters: { usuarioId: resolvedUserId },
        sort: { createdAt: 'desc' },
        limit: 3,
        populate: { plano: true, assinatura: true },
      });

      if (Array.isArray(cobrancas) && cobrancas.length > 0) {
        const ultima = cobrancas[0];
        const comentario =
          ultima?.status === 'pendente' && ultima.expiraEm
            ? `expira em ${new Date(ultima.expiraEm).toLocaleDateString('pt-BR')}`
            : undefined;
        payload.metricasSemana = [
          {
            rotulo: 'Última cobrança Pix',
            valor: mapPaymentStatus(ultima).toUpperCase(),
            comentario,
          },
          ...payload.metricasSemana,
        ];
      }
    }

    payload.ultimaSincronizacao = new Date().toISOString();
    payload.fonte = 'Strapi CMS';

    ctx.body = payload;
  },
}));
