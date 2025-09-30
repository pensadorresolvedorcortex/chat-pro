import { factories } from '@strapi/strapi';

const CADERNO_UID = 'api::caderno.caderno';

const toIsoString = (value: unknown): string | null => {
  if (!value) {
    return null;
  }
  const date = value instanceof Date ? value : new Date(value as string);
  return Number.isNaN(date.getTime()) ? null : date.toISOString();
};

const toDecimal = (value: unknown): number | null => {
  if (value === null || value === undefined) {
    return null;
  }
  const numeric = Number.parseFloat(String(value));
  return Number.isNaN(numeric) ? null : Number(numeric.toFixed(4));
};

const normaliseQuestions = (value: unknown) => {
  if (!Array.isArray(value)) {
    return [] as Array<Record<string, unknown>>;
  }
  return value
    .map((entry) => {
      if (!entry || typeof entry !== 'object') {
        return null;
      }
      const question = entry as Record<string, unknown>;
      const id = typeof question.id === 'string' ? question.id : null;
      if (!id) {
        return null;
      }
      return {
        id,
        status: typeof question.status === 'string' ? question.status : null,
        correta:
          typeof question.correta === 'boolean'
            ? question.correta
            : question.correta === 'true'
            ? true
            : question.correta === 'false'
            ? false
            : null,
      };
    })
    .filter((question): question is Record<string, unknown> => question !== null);
};

const serialiseCaderno = (caderno: Record<string, any> | null) => {
  if (!caderno) {
    return null;
  }

  const progresso = toDecimal(caderno.progresso);

  return {
    id: caderno.slug ?? String(caderno.id ?? ''),
    slug: caderno.slug ?? null,
    titulo: caderno.titulo ?? null,
    descricao: caderno.descricao ?? null,
    usuarioId: caderno.usuarioId ?? null,
    progresso: progresso ?? null,
    questoes: normaliseQuestions(caderno.questoes ?? []),
    atualizadoEm: toIsoString(caderno.atualizadoEm ?? caderno.updatedAt),
    createdAt: toIsoString(caderno.createdAt),
    updatedAt: toIsoString(caderno.updatedAt),
    publishedAt: toIsoString(caderno.publishedAt),
  };
};

const resolveBody = (ctx: any) => {
  const body = ctx.request?.body ?? {};
  if (body.data && typeof body.data === 'object') {
    return body.data;
  }
  return body;
};

const findByIdentifier = async (strapi: any, identifier: string, options: Record<string, unknown> = {}) => {
  if (/^\d+$/.test(identifier)) {
    return strapi.entityService.findOne(CADERNO_UID, Number(identifier), options);
  }

  const matches = await strapi.entityService.findMany(CADERNO_UID, {
    filters: { slug: identifier },
    limit: 1,
    ...options,
  });

  if (Array.isArray(matches) && matches.length > 0) {
    return matches[0];
  }

  return null;
};

export default factories.createCoreController(CADERNO_UID, ({ strapi }) => ({
  async find(ctx) {
    const service = strapi.service(CADERNO_UID);
    const { results, pagination } = await service.find(ctx.query);

    const payload = results
      .map((entry: Record<string, any>) => serialiseCaderno(entry))
      .filter((entry): entry is Record<string, unknown> => entry !== null);

    if (pagination?.total !== undefined) {
      ctx.set('X-Total-Count', String(pagination.total));
    }

    ctx.body = payload;
  },

  async findOne(ctx) {
    const { id } = ctx.params;
    const caderno = await findByIdentifier(strapi, id);

    if (!caderno) {
      return ctx.notFound('Caderno não encontrado.');
    }

    ctx.body = serialiseCaderno(caderno);
  },

  async create(ctx) {
    const data = resolveBody(ctx) ?? {};

    if (data.id && !data.slug) {
      data.slug = data.id;
    }

    const created = await strapi.entityService.create(CADERNO_UID, { data });

    ctx.status = 201;
    ctx.body = serialiseCaderno(created);
  },

  async update(ctx) {
    const { id } = ctx.params;
    const existing = await findByIdentifier(strapi, id);

    if (!existing) {
      return ctx.notFound('Caderno não encontrado.');
    }

    const data = resolveBody(ctx) ?? {};

    if (data.id && !data.slug) {
      data.slug = data.id;
    }

    const updated = await strapi.entityService.update(CADERNO_UID, existing.id, {
      data,
    });

    ctx.body = serialiseCaderno(updated);
  },

  async delete(ctx) {
    const { id } = ctx.params;
    const existing = await findByIdentifier(strapi, id);

    if (!existing) {
      return ctx.notFound('Caderno não encontrado.');
    }

    await strapi.entityService.delete(CADERNO_UID, existing.id);

    ctx.status = 204;
    ctx.body = null;
  },
}));
