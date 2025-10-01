import { factories } from '@strapi/strapi';

const SIMULADO_UID = 'api::simulado.simulado';

const isNumericId = (value: unknown) => {
  if (value === null || value === undefined) {
    return false;
  }
  return /^\d+$/.test(String(value));
};

const toIsoString = (value: unknown) => {
  if (!value) {
    return null;
  }
  const date = value instanceof Date ? value : new Date(value as string);
  return Number.isNaN(date.getTime()) ? null : date.toISOString();
};

const ensureArray = <T>(input: unknown): T[] => {
  if (!input) {
    return [];
  }
  if (Array.isArray(input)) {
    return input as T[];
  }
  return [input as T];
};

const resolveBody = (ctx: any) => {
  const body = ctx.request?.body ?? {};
  if (body.data && typeof body.data === 'object') {
    return body.data;
  }
  return body;
};

const serialiseSimulado = (simulado: Record<string, any> | null) => {
  if (!simulado) {
    return null;
  }

  const configuracao = simulado.configuracao ?? {};
  const estatisticas = simulado.estatisticas ?? null;
  const questaoIds = ensureArray<string>(simulado.questaoIds ?? simulado.questoes ?? []);
  const resultados = simulado.resultados ?? null;

  return {
    id: simulado.slug ?? String(simulado.id ?? ''),
    slug: simulado.slug ?? null,
    titulo: simulado.titulo ?? simulado.nome ?? null,
    modalidade: simulado.modalidade ?? simulado.tipo ?? null,
    usuarioId: simulado.usuarioId ?? null,
    configuracao,
    estatisticas,
    questaoIds,
    status: simulado.status ?? null,
    resultados,
    atualizadoEm: toIsoString(simulado.atualizadoEm),
    createdAt: toIsoString(simulado.createdAt),
    updatedAt: toIsoString(simulado.updatedAt),
    publishedAt: toIsoString(simulado.publishedAt),
  };
};

const findByIdentifier = async (strapi: any, identifier: string, options: Record<string, unknown> = {}) => {
  if (isNumericId(identifier)) {
    return strapi.entityService.findOne(SIMULADO_UID, Number(identifier), options);
  }

  const matches = await strapi.entityService.findMany(SIMULADO_UID, {
    filters: { slug: identifier },
    limit: 1,
    ...options,
  });

  if (Array.isArray(matches) && matches.length > 0) {
    return matches[0];
  }

  return null;
};

export default factories.createCoreController(SIMULADO_UID, ({ strapi }) => ({
  async find(ctx) {
    const { results, pagination } = await strapi.service(SIMULADO_UID).find(ctx.query);

    const simulados = results
      .map((simulado: Record<string, any>) => serialiseSimulado(simulado))
      .filter((entry): entry is Record<string, unknown> => entry !== null);

    if (pagination?.total !== undefined) {
      ctx.set('X-Total-Count', String(pagination.total));
    }

    ctx.body = simulados;
  },

  async findOne(ctx) {
    const { id } = ctx.params;
    const simulado = await findByIdentifier(strapi, id);

    if (!simulado) {
      return ctx.notFound('Simulado não encontrado.');
    }

    ctx.body = serialiseSimulado(simulado);
  },

  async create(ctx) {
    const data = resolveBody(ctx) ?? {};

    if (data.id && !data.slug) {
      data.slug = data.id;
    }

    if (!data.titulo && data.nome) {
      data.titulo = data.nome;
    }

    const created = await strapi.entityService.create(SIMULADO_UID, {
      data: {
        ...data,
        publicado: undefined,
      },
    });

    ctx.status = 201;
    ctx.body = serialiseSimulado(created);
  },

  async update(ctx) {
    const { id } = ctx.params;
    const existing = await findByIdentifier(strapi, id);

    if (!existing) {
      return ctx.notFound('Simulado não encontrado.');
    }

    const data = resolveBody(ctx) ?? {};

    if (data.id && !data.slug) {
      data.slug = data.id;
    }

    if (!data.titulo && data.nome) {
      data.titulo = data.nome;
    }

    const updated = await strapi.entityService.update(SIMULADO_UID, existing.id, {
      data,
    });

    ctx.body = serialiseSimulado(updated);
  },

  async delete(ctx) {
    const { id } = ctx.params;
    const existing = await findByIdentifier(strapi, id);

    if (!existing) {
      return ctx.notFound('Simulado não encontrado.');
    }

    await strapi.entityService.delete(SIMULADO_UID, existing.id);

    ctx.status = 204;
    ctx.body = null;
  },

  async generateExpress(ctx) {
    const payload = resolveBody(ctx) ?? {};
    const disciplinas = ensureArray<string>(payload.disciplinas ?? []);
    const now = new Date();

    const tituloBase = payload.nome ?? payload.titulo ?? 'Simulado Express';
    const disciplinaFocus = disciplinas[0] ? ` - ${disciplinas[0]}` : '';
    const slug = `sim-${now.getTime()}`;

    const data: Record<string, unknown> = {
      titulo: `${tituloBase}${disciplinaFocus}`.trim(),
      slug,
      usuarioId: payload.usuarioId ?? null,
      modalidade: 'express',
      configuracao: {
        ...payload.configuracao,
        disciplinas,
      },
      estatisticas: {
        questoesRespondidas: 0,
        acertos: 0,
        tempoMedioPorQuestaoSegundos: null,
      },
      questaoIds: ensureArray<string>(payload.questaoIds ?? []),
      status: 'em_andamento',
      atualizadoEm: now.toISOString(),
      publishedAt: now.toISOString(),
    };

    const created = await strapi.entityService.create(SIMULADO_UID, { data });

    ctx.status = 201;
    ctx.body = serialiseSimulado(created);
  },

  async submitResponses(ctx) {
    const { id } = ctx.params;
    const simulado = await findByIdentifier(strapi, id);

    if (!simulado) {
      return ctx.notFound('Simulado não encontrado.');
    }

    const payload = resolveBody(ctx) ?? {};
    const respostas = ensureArray<Record<string, any>>(payload.respostas ?? []);
    const respondidas = respostas.length;
    const acertosInformados = typeof payload.acertos === 'number' ? payload.acertos : null;
    const acertos = acertosInformados !== null ? acertosInformados : Math.max(0, Math.round(respondidas * 0.7));
    const erros = Math.max(0, respondidas - acertos);
    const aproveitamento = respondidas === 0 ? 0 : Number((acertos / respondidas).toFixed(2));

    const resultados = {
      questoesRespondidas: respondidas,
      acertos,
      erros,
      aproveitamento,
      rankingPosicao: payload.rankingPosicao ?? null,
    };

    await strapi.entityService.update(SIMULADO_UID, simulado.id, {
      data: {
        resultados,
        estatisticas: {
          ...(simulado.estatisticas ?? {}),
          questoesRespondidas: respondidas,
          acertos,
        },
        status: payload.status ?? simulado.status ?? 'em_andamento',
        atualizadoEm: new Date().toISOString(),
      },
    });

    ctx.body = resultados;
  },
}));
