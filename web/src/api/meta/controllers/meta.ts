import { factories } from '@strapi/strapi';

const META_UID = 'api::meta.meta';

const isNumericId = (value: unknown) => {
  if (value === null || value === undefined) {
    return false;
  }
  return /^\d+$/.test(String(value));
};

const toInteger = (value: unknown) => {
  if (value === null || value === undefined) {
    return null;
  }
  const parsed = Number.parseInt(String(value), 10);
  return Number.isNaN(parsed) ? null : parsed;
};

const toIsoString = (value: unknown) => {
  if (!value) {
    return null;
  }
  const date = value instanceof Date ? value : new Date(value as string);
  return Number.isNaN(date.getTime()) ? null : date.toISOString();
};

const resolveBody = (ctx: any) => {
  const body = ctx.request?.body ?? {};
  if (body.data && typeof body.data === 'object') {
    return body.data;
  }
  return body;
};

const serialiseMeta = (meta: Record<string, any> | null) => {
  if (!meta) {
    return null;
  }

  return {
    id: meta.slug ?? String(meta.id ?? ''),
    slug: meta.slug ?? null,
    titulo: meta.titulo ?? meta.descricao ?? null,
    descricao: meta.descricao ?? null,
    usuarioId: meta.usuarioId ?? null,
    tipo: meta.tipo ?? null,
    alvo: toInteger(meta.alvo),
    progressoAtual: toInteger(meta.progressoAtual),
    periodo: meta.periodo ?? null,
    ultimoReset: toIsoString(meta.ultimoReset),
    createdAt: toIsoString(meta.createdAt),
    updatedAt: toIsoString(meta.updatedAt),
    publishedAt: toIsoString(meta.publishedAt),
  };
};

const findByIdentifier = async (strapi: any, identifier: string, options: Record<string, unknown> = {}) => {
  if (isNumericId(identifier)) {
    return strapi.entityService.findOne(META_UID, Number(identifier), options);
  }

  const matches = await strapi.entityService.findMany(META_UID, {
    filters: { slug: identifier },
    limit: 1,
    ...options,
  });

  if (Array.isArray(matches) && matches.length > 0) {
    return matches[0];
  }

  return null;
};

export default factories.createCoreController(META_UID, ({ strapi }) => ({
  async find(ctx) {
    const { results, pagination } = await strapi.service(META_UID).find(ctx.query);

    const metas = results
      .map((meta: Record<string, any>) => serialiseMeta(meta))
      .filter((entry): entry is Record<string, unknown> => entry !== null);

    if (pagination?.total !== undefined) {
      ctx.set('X-Total-Count', String(pagination.total));
    }

    ctx.body = metas;
  },

  async findOne(ctx) {
    const { id } = ctx.params;
    const meta = await findByIdentifier(strapi, id);

    if (!meta) {
      return ctx.notFound('Meta não encontrada.');
    }

    ctx.body = serialiseMeta(meta);
  },

  async create(ctx) {
    const data = resolveBody(ctx) ?? {};

    if (data.id && !data.slug) {
      data.slug = data.id;
    }

    if (!data.titulo && data.descricao) {
      data.titulo = data.descricao;
    }

    const created = await strapi.entityService.create(META_UID, { data });

    ctx.status = 201;
    ctx.body = serialiseMeta(created);
  },

  async update(ctx) {
    const { id } = ctx.params;
    const existente = await findByIdentifier(strapi, id);

    if (!existente) {
      return ctx.notFound('Meta não encontrada.');
    }

    const data = resolveBody(ctx) ?? {};

    if (data.id && !data.slug) {
      data.slug = data.id;
    }

    if (!data.titulo && data.descricao) {
      data.titulo = data.descricao;
    }

    const atualizado = await strapi.entityService.update(META_UID, existente.id, {
      data,
    });

    ctx.body = serialiseMeta(atualizado);
  },

  async delete(ctx) {
    const { id } = ctx.params;
    const existente = await findByIdentifier(strapi, id);

    if (!existente) {
      return ctx.notFound('Meta não encontrada.');
    }

    await strapi.entityService.delete(META_UID, existente.id);

    ctx.status = 204;
    ctx.body = null;
  },
}));
