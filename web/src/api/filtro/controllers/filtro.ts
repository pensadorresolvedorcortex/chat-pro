import { factories } from '@strapi/strapi';

const FILTRO_UID = 'api::filtro.filtro';

const toIsoString = (value: unknown): string | null => {
  if (!value) {
    return null;
  }
  const date = value instanceof Date ? value : new Date(value as string);
  return Number.isNaN(date.getTime()) ? null : date.toISOString();
};

const serialiseFiltro = (filtro: Record<string, any> | null) => {
  if (!filtro) {
    return null;
  }

  return {
    id: filtro.slug ?? String(filtro.id ?? ''),
    slug: filtro.slug ?? null,
    nome: filtro.nome ?? null,
    usuarioId: filtro.usuarioId ?? null,
    criterios: filtro.criterios ?? {},
    atualizadoEm: toIsoString(filtro.atualizadoEm ?? filtro.updatedAt),
    createdAt: toIsoString(filtro.createdAt),
    updatedAt: toIsoString(filtro.updatedAt),
    publishedAt: toIsoString(filtro.publishedAt),
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
    return strapi.entityService.findOne(FILTRO_UID, Number(identifier), options);
  }

  const matches = await strapi.entityService.findMany(FILTRO_UID, {
    filters: { slug: identifier },
    limit: 1,
    ...options,
  });

  if (Array.isArray(matches) && matches.length > 0) {
    return matches[0];
  }

  return null;
};

export default factories.createCoreController(FILTRO_UID, ({ strapi }) => ({
  async find(ctx) {
    const service = strapi.service(FILTRO_UID);
    const { results, pagination } = await service.find(ctx.query);

    const payload = results
      .map((entry: Record<string, any>) => serialiseFiltro(entry))
      .filter((entry): entry is Record<string, unknown> => entry !== null);

    if (pagination?.total !== undefined) {
      ctx.set('X-Total-Count', String(pagination.total));
    }

    ctx.body = payload;
  },

  async findOne(ctx) {
    const { id } = ctx.params;
    const filtro = await findByIdentifier(strapi, id);

    if (!filtro) {
      return ctx.notFound('Filtro salvo não encontrado.');
    }

    ctx.body = serialiseFiltro(filtro);
  },

  async create(ctx) {
    const data = resolveBody(ctx) ?? {};

    if (data.id && !data.slug) {
      data.slug = data.id;
    }

    const created = await strapi.entityService.create(FILTRO_UID, { data });

    ctx.status = 201;
    ctx.body = serialiseFiltro(created);
  },

  async update(ctx) {
    const { id } = ctx.params;
    const existing = await findByIdentifier(strapi, id);

    if (!existing) {
      return ctx.notFound('Filtro salvo não encontrado.');
    }

    const data = resolveBody(ctx) ?? {};

    if (data.id && !data.slug) {
      data.slug = data.id;
    }

    const updated = await strapi.entityService.update(FILTRO_UID, existing.id, {
      data,
    });

    ctx.body = serialiseFiltro(updated);
  },

  async delete(ctx) {
    const { id } = ctx.params;
    const existing = await findByIdentifier(strapi, id);

    if (!existing) {
      return ctx.notFound('Filtro salvo não encontrado.');
    }

    await strapi.entityService.delete(FILTRO_UID, existing.id);

    ctx.status = 204;
    ctx.body = null;
  },
}));
