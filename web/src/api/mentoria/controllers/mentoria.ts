import { factories } from '@strapi/strapi';

const MENTORIA_UID = 'api::mentoria.mentoria';

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
  return Number.isNaN(numeric) ? null : Number(numeric.toFixed(1));
};

const toInteger = (value: unknown): number | null => {
  if (value === null || value === undefined) {
    return null;
  }
  const numeric = Number.parseInt(String(value), 10);
  return Number.isNaN(numeric) ? null : numeric;
};

const normaliseSlots = (value: unknown) => {
  if (!Array.isArray(value)) {
    return [] as Array<Record<string, unknown>>;
  }
  return value
    .map((entry) => {
      if (!entry || typeof entry !== 'object') {
        return null;
      }
      const slot = entry as Record<string, unknown>;
      const inicio = toIsoString(slot.inicio);
      const fim = toIsoString(slot.fim);
      if (!inicio || !fim) {
        return null;
      }
      return {
        inicio,
        fim,
        status: typeof slot.status === 'string' ? slot.status : null,
        reservadoPor: typeof slot.reservadoPor === 'string' ? slot.reservadoPor : null,
      };
    })
    .filter((slot): slot is Record<string, unknown> => slot !== null);
};

const serialiseMentoria = (mentoria: Record<string, any> | null) => {
  if (!mentoria) {
    return null;
  }

  return {
    id: mentoria.slug ?? String(mentoria.id ?? ''),
    slug: mentoria.slug ?? null,
    titulo: mentoria.titulo ?? null,
    descricao: mentoria.descricao ?? null,
    mentorId: mentoria.mentorId ?? null,
    avaliacaoMedia: toDecimal(mentoria.avaliacaoMedia),
    alunosAtendidos: toInteger(mentoria.alunosAtendidos),
    slots: normaliseSlots(mentoria.slots ?? []),
    createdAt: toIsoString(mentoria.createdAt),
    updatedAt: toIsoString(mentoria.updatedAt),
    publishedAt: toIsoString(mentoria.publishedAt),
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
    return strapi.entityService.findOne(MENTORIA_UID, Number(identifier), options);
  }

  const matches = await strapi.entityService.findMany(MENTORIA_UID, {
    filters: { slug: identifier },
    limit: 1,
    ...options,
  });

  if (Array.isArray(matches) && matches.length > 0) {
    return matches[0];
  }

  return null;
};

export default factories.createCoreController(MENTORIA_UID, ({ strapi }) => ({
  async find(ctx) {
    const service = strapi.service(MENTORIA_UID);
    const { results, pagination } = await service.find(ctx.query);

    const payload = results
      .map((entry: Record<string, any>) => serialiseMentoria(entry))
      .filter((entry): entry is Record<string, unknown> => entry !== null);

    if (pagination?.total !== undefined) {
      ctx.set('X-Total-Count', String(pagination.total));
    }

    ctx.body = payload;
  },

  async findOne(ctx) {
    const { id } = ctx.params;
    const mentoria = await findByIdentifier(strapi, id);

    if (!mentoria) {
      return ctx.notFound('Mentoria não encontrada.');
    }

    ctx.body = serialiseMentoria(mentoria);
  },

  async create(ctx) {
    const data = resolveBody(ctx) ?? {};

    if (data.id && !data.slug) {
      data.slug = data.id;
    }

    const created = await strapi.entityService.create(MENTORIA_UID, { data });

    ctx.status = 201;
    ctx.body = serialiseMentoria(created);
  },

  async update(ctx) {
    const { id } = ctx.params;
    const existing = await findByIdentifier(strapi, id);

    if (!existing) {
      return ctx.notFound('Mentoria não encontrada.');
    }

    const data = resolveBody(ctx) ?? {};

    if (data.id && !data.slug) {
      data.slug = data.id;
    }

    const updated = await strapi.entityService.update(MENTORIA_UID, existing.id, {
      data,
    });

    ctx.body = serialiseMentoria(updated);
  },

  async delete(ctx) {
    const { id } = ctx.params;
    const existing = await findByIdentifier(strapi, id);

    if (!existing) {
      return ctx.notFound('Mentoria não encontrada.');
    }

    await strapi.entityService.delete(MENTORIA_UID, existing.id);

    ctx.status = 204;
    ctx.body = null;
  },
}));
