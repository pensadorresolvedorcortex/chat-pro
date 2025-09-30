import { factories } from '@strapi/strapi';

const LIVE_UID = 'api::live.live';

const toIsoString = (value: unknown): string | null => {
  if (!value) {
    return null;
  }
  const date = value instanceof Date ? value : new Date(value as string);
  return Number.isNaN(date.getTime()) ? null : date.toISOString();
};

const toInteger = (value: unknown): number | null => {
  if (value === null || value === undefined) {
    return null;
  }
  const parsed = Number.parseInt(String(value), 10);
  return Number.isNaN(parsed) ? null : parsed;
};

const normaliseArray = (value: unknown) => {
  if (!Array.isArray(value)) {
    return [] as unknown[];
  }
  return value.filter((item) => item !== null && item !== undefined);
};

const serialiseLive = (live: Record<string, any> | null) => {
  if (!live) {
    return null;
  }

  return {
    id: live.slug ?? String(live.id ?? ''),
    slug: live.slug ?? null,
    titulo: live.titulo ?? null,
    descricao: live.descricao ?? null,
    instrutor: live.instrutor ?? null,
    link: live.link ?? null,
    capacidade: toInteger(live.capacidade),
    inscritos: toInteger(live.inscritos),
    inicio: toIsoString(live.inicio),
    fim: toIsoString(live.fim),
    materialApoio: normaliseArray(live.materialApoio ?? []),
    createdAt: toIsoString(live.createdAt),
    updatedAt: toIsoString(live.updatedAt),
    publishedAt: toIsoString(live.publishedAt),
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
    return strapi.entityService.findOne(LIVE_UID, Number(identifier), options);
  }

  const matches = await strapi.entityService.findMany(LIVE_UID, {
    filters: { slug: identifier },
    limit: 1,
    ...options,
  });

  if (Array.isArray(matches) && matches.length > 0) {
    return matches[0];
  }

  return null;
};

export default factories.createCoreController(LIVE_UID, ({ strapi }) => ({
  async find(ctx) {
    const service = strapi.service(LIVE_UID);
    const { results, pagination } = await service.find(ctx.query);

    const payload = results
      .map((entry: Record<string, any>) => serialiseLive(entry))
      .filter((entry): entry is Record<string, unknown> => entry !== null);

    if (pagination?.total !== undefined) {
      ctx.set('X-Total-Count', String(pagination.total));
    }

    ctx.body = payload;
  },

  async findOne(ctx) {
    const { id } = ctx.params;
    const live = await findByIdentifier(strapi, id);

    if (!live) {
      return ctx.notFound('Live não encontrada.');
    }

    ctx.body = serialiseLive(live);
  },

  async create(ctx) {
    const data = resolveBody(ctx) ?? {};

    if (data.id && !data.slug) {
      data.slug = data.id;
    }

    const created = await strapi.entityService.create(LIVE_UID, { data });

    ctx.status = 201;
    ctx.body = serialiseLive(created);
  },

  async update(ctx) {
    const { id } = ctx.params;
    const existing = await findByIdentifier(strapi, id);

    if (!existing) {
      return ctx.notFound('Live não encontrada.');
    }

    const data = resolveBody(ctx) ?? {};

    if (data.id && !data.slug) {
      data.slug = data.id;
    }

    const updated = await strapi.entityService.update(LIVE_UID, existing.id, {
      data,
    });

    ctx.body = serialiseLive(updated);
  },

  async delete(ctx) {
    const { id } = ctx.params;
    const existing = await findByIdentifier(strapi, id);

    if (!existing) {
      return ctx.notFound('Live não encontrada.');
    }

    await strapi.entityService.delete(LIVE_UID, existing.id);

    ctx.status = 204;
    ctx.body = null;
  },
}));
