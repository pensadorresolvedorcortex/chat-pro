import { factories } from '@strapi/strapi';

const NOTIFICACAO_UID = 'api::notificacao.notificacao';

const toIsoString = (value: unknown): string | null => {
  if (!value) {
    return null;
  }
  const date = value instanceof Date ? value : new Date(value as string);
  return Number.isNaN(date.getTime()) ? null : date.toISOString();
};

const serialiseNotificacao = (entry: Record<string, any> | null) => {
  if (!entry) {
    return null;
  }

  return {
    id: entry.slug ?? String(entry.id ?? ''),
    slug: entry.slug ?? null,
    titulo: entry.titulo ?? null,
    mensagem: entry.mensagem ?? null,
    tipo: entry.tipo ?? null,
    segmento: entry.segmento ?? {},
    agendadaPara: toIsoString(entry.agendadaPara),
    createdAt: toIsoString(entry.createdAt),
    updatedAt: toIsoString(entry.updatedAt),
    publishedAt: toIsoString(entry.publishedAt),
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
    return strapi.entityService.findOne(NOTIFICACAO_UID, Number(identifier), options);
  }

  const matches = await strapi.entityService.findMany(NOTIFICACAO_UID, {
    filters: { slug: identifier },
    limit: 1,
    ...options,
  });

  if (Array.isArray(matches) && matches.length > 0) {
    return matches[0];
  }

  return null;
};

export default factories.createCoreController(NOTIFICACAO_UID, ({ strapi }) => ({
  async find(ctx) {
    const service = strapi.service(NOTIFICACAO_UID);
    const { results, pagination } = await service.find(ctx.query);

    const payload = results
      .map((entry: Record<string, any>) => serialiseNotificacao(entry))
      .filter((entry): entry is Record<string, unknown> => entry !== null);

    if (pagination?.total !== undefined) {
      ctx.set('X-Total-Count', String(pagination.total));
    }

    ctx.body = payload;
  },

  async findOne(ctx) {
    const { id } = ctx.params;
    const notificacao = await findByIdentifier(strapi, id);

    if (!notificacao) {
      return ctx.notFound('Notificação não encontrada.');
    }

    ctx.body = serialiseNotificacao(notificacao);
  },

  async create(ctx) {
    const data = resolveBody(ctx) ?? {};

    if (data.id && !data.slug) {
      data.slug = data.id;
    }

    const created = await strapi.entityService.create(NOTIFICACAO_UID, { data });

    ctx.status = 201;
    ctx.body = serialiseNotificacao(created);
  },

  async update(ctx) {
    const { id } = ctx.params;
    const existing = await findByIdentifier(strapi, id);

    if (!existing) {
      return ctx.notFound('Notificação não encontrada.');
    }

    const data = resolveBody(ctx) ?? {};

    if (data.id && !data.slug) {
      data.slug = data.id;
    }

    const updated = await strapi.entityService.update(NOTIFICACAO_UID, existing.id, {
      data,
    });

    ctx.body = serialiseNotificacao(updated);
  },

  async delete(ctx) {
    const { id } = ctx.params;
    const existing = await findByIdentifier(strapi, id);

    if (!existing) {
      return ctx.notFound('Notificação não encontrada.');
    }

    await strapi.entityService.delete(NOTIFICACAO_UID, existing.id);

    ctx.status = 204;
    ctx.body = null;
  },
}));
