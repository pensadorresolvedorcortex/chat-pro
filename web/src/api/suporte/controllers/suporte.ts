import { factories } from '@strapi/strapi';

const SUPORTE_UID = 'api::suporte.suporte';

const toIsoString = (value: unknown): string | null => {
  if (!value) {
    return null;
  }
  const date = value instanceof Date ? value : new Date(value as string);
  return Number.isNaN(date.getTime()) ? null : date.toISOString();
};

const normaliseMensagens = (value: unknown) => {
  if (!Array.isArray(value)) {
    return [] as Array<Record<string, unknown>>;
  }
  return value
    .map((entry) => {
      if (!entry || typeof entry !== 'object') {
        return null;
      }
      const mensagem = entry as Record<string, unknown>;
      const autor = typeof mensagem.autor === 'string' ? mensagem.autor : null;
      const conteudo = typeof mensagem.conteudo === 'string' ? mensagem.conteudo : null;
      const enviadoEm = toIsoString(mensagem.enviadoEm);
      if (!autor || !conteudo) {
        return null;
      }
      return {
        autor,
        conteudo,
        enviadoEm,
      };
    })
    .filter((mensagem): mensagem is Record<string, unknown> => mensagem !== null);
};

const serialiseTicket = (ticket: Record<string, any> | null) => {
  if (!ticket) {
    return null;
  }

  return {
    id: ticket.slug ?? String(ticket.id ?? ''),
    slug: ticket.slug ?? null,
    assunto: ticket.assunto ?? null,
    categoria: ticket.categoria ?? null,
    usuarioId: ticket.usuarioId ?? null,
    status: ticket.status ?? null,
    mensagens: normaliseMensagens(ticket.mensagens ?? []),
    createdAt: toIsoString(ticket.createdAt),
    updatedAt: toIsoString(ticket.updatedAt),
    publishedAt: toIsoString(ticket.publishedAt),
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
    return strapi.entityService.findOne(SUPORTE_UID, Number(identifier), options);
  }

  const matches = await strapi.entityService.findMany(SUPORTE_UID, {
    filters: { slug: identifier },
    limit: 1,
    ...options,
  });

  if (Array.isArray(matches) && matches.length > 0) {
    return matches[0];
  }

  return null;
};

export default factories.createCoreController(SUPORTE_UID, ({ strapi }) => ({
  async find(ctx) {
    const service = strapi.service(SUPORTE_UID);
    const { results, pagination } = await service.find(ctx.query);

    const payload = results
      .map((entry: Record<string, any>) => serialiseTicket(entry))
      .filter((entry): entry is Record<string, unknown> => entry !== null);

    if (pagination?.total !== undefined) {
      ctx.set('X-Total-Count', String(pagination.total));
    }

    ctx.body = payload;
  },

  async findOne(ctx) {
    const { id } = ctx.params;
    const ticket = await findByIdentifier(strapi, id);

    if (!ticket) {
      return ctx.notFound('Ticket de suporte não encontrado.');
    }

    ctx.body = serialiseTicket(ticket);
  },

  async create(ctx) {
    const data = resolveBody(ctx) ?? {};

    if (data.id && !data.slug) {
      data.slug = data.id;
    }

    const created = await strapi.entityService.create(SUPORTE_UID, { data });

    ctx.status = 201;
    ctx.body = serialiseTicket(created);
  },

  async update(ctx) {
    const { id } = ctx.params;
    const existing = await findByIdentifier(strapi, id);

    if (!existing) {
      return ctx.notFound('Ticket de suporte não encontrado.');
    }

    const data = resolveBody(ctx) ?? {};

    if (data.id && !data.slug) {
      data.slug = data.id;
    }

    const updated = await strapi.entityService.update(SUPORTE_UID, existing.id, {
      data,
    });

    ctx.body = serialiseTicket(updated);
  },

  async delete(ctx) {
    const { id } = ctx.params;
    const existing = await findByIdentifier(strapi, id);

    if (!existing) {
      return ctx.notFound('Ticket de suporte não encontrado.');
    }

    await strapi.entityService.delete(SUPORTE_UID, existing.id);

    ctx.status = 204;
    ctx.body = null;
  },
}));
