import { factories } from '@strapi/strapi';

const DESAFIO_UID = 'api::desafio.desafio';

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

const serialiseParticipantes = (participantes: unknown) =>
  ensureArray<Record<string, any>>(participantes)
    .map((participante) => ({
      usuarioId: participante.usuarioId ?? participante.id ?? null,
      questoesResolvidas: participante.questoesResolvidas ?? participante.questoes ?? 0,
      acertos: participante.acertos ?? null,
      posicao: participante.posicao ?? participante.ranking ?? null,
      progresso: participante.progresso ?? null,
    }))
    .filter((participante) => participante.usuarioId);

const serialiseDesafio = (desafio: Record<string, any> | null) => {
  if (!desafio) {
    return null;
  }

  return {
    id: desafio.slug ?? String(desafio.id ?? ''),
    slug: desafio.slug ?? null,
    titulo: desafio.titulo ?? desafio.nome ?? null,
    nome: desafio.titulo ?? desafio.nome ?? null,
    descricao: desafio.descricao ?? null,
    organizadorId: desafio.organizadorId ?? null,
    periodo: {
      inicio: toIsoString(desafio.inicio),
      fim: toIsoString(desafio.fim),
    },
    regras: desafio.regras ?? null,
    participantes: serialiseParticipantes(desafio.participantes),
    status: desafio.status ?? null,
    createdAt: toIsoString(desafio.createdAt),
    updatedAt: toIsoString(desafio.updatedAt),
    publishedAt: toIsoString(desafio.publishedAt),
  };
};

const findByIdentifier = async (strapi: any, identifier: string, options: Record<string, unknown> = {}) => {
  if (isNumericId(identifier)) {
    return strapi.entityService.findOne(DESAFIO_UID, Number(identifier), options);
  }

  const matches = await strapi.entityService.findMany(DESAFIO_UID, {
    filters: { slug: identifier },
    limit: 1,
    ...options,
  });

  if (Array.isArray(matches) && matches.length > 0) {
    return matches[0];
  }

  return null;
};

export default factories.createCoreController(DESAFIO_UID, ({ strapi }) => ({
  async find(ctx) {
    const { results, pagination } = await strapi.service(DESAFIO_UID).find(ctx.query);

    const desafios = results
      .map((desafio: Record<string, any>) => serialiseDesafio(desafio))
      .filter((entry): entry is Record<string, unknown> => entry !== null);

    if (pagination?.total !== undefined) {
      ctx.set('X-Total-Count', String(pagination.total));
    }

    ctx.body = desafios;
  },

  async findOne(ctx) {
    const { id } = ctx.params;
    const desafio = await findByIdentifier(strapi, id);

    if (!desafio) {
      return ctx.notFound('Desafio não encontrado.');
    }

    ctx.body = serialiseDesafio(desafio);
  },

  async create(ctx) {
    const data = resolveBody(ctx) ?? {};

    if (data.id && !data.slug) {
      data.slug = data.id;
    }

    if (!data.titulo && data.nome) {
      data.titulo = data.nome;
    }

    const created = await strapi.entityService.create(DESAFIO_UID, { data });

    ctx.status = 201;
    ctx.body = serialiseDesafio(created);
  },

  async update(ctx) {
    const { id } = ctx.params;
    const existente = await findByIdentifier(strapi, id);

    if (!existente) {
      return ctx.notFound('Desafio não encontrado.');
    }

    const data = resolveBody(ctx) ?? {};

    if (data.id && !data.slug) {
      data.slug = data.id;
    }

    if (!data.titulo && data.nome) {
      data.titulo = data.nome;
    }

    const atualizado = await strapi.entityService.update(DESAFIO_UID, existente.id, {
      data,
    });

    ctx.body = serialiseDesafio(atualizado);
  },

  async delete(ctx) {
    const { id } = ctx.params;
    const existente = await findByIdentifier(strapi, id);

    if (!existente) {
      return ctx.notFound('Desafio não encontrado.');
    }

    await strapi.entityService.delete(DESAFIO_UID, existente.id);

    ctx.status = 204;
    ctx.body = null;
  },

  async ranking(ctx) {
    const { id } = ctx.params;
    const desafio = await findByIdentifier(strapi, id);

    if (!desafio) {
      return ctx.notFound('Desafio não encontrado.');
    }

    const participantes = serialiseParticipantes(desafio.participantes).sort((a, b) => {
      if (a.posicao === null || a.posicao === undefined) {
        return 1;
      }
      if (b.posicao === null || b.posicao === undefined) {
        return -1;
      }
      return a.posicao - b.posicao;
    });

    ctx.body = {
      id: desafio.slug ?? String(desafio.id ?? ''),
      titulo: desafio.titulo ?? desafio.nome ?? null,
      participantes,
    };
  },
}));
