import { factories } from '@strapi/strapi';

const CURSO_UID = 'api::curso.curso';

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
  return Number.isNaN(numeric) ? null : Number(numeric.toFixed(2));
};

const toInteger = (value: unknown): number | null => {
  if (value === null || value === undefined) {
    return null;
  }
  const numeric = Number.parseInt(String(value), 10);
  return Number.isNaN(numeric) ? null : numeric;
};

const normaliseArray = (value: unknown) => {
  if (!Array.isArray(value)) {
    return [] as unknown[];
  }
  return value.filter((item) => item !== null && item !== undefined);
};

const serialiseCurso = (curso: Record<string, any> | null) => {
  if (!curso) {
    return null;
  }

  return {
    id: curso.slug ?? String(curso.id ?? ''),
    slug: curso.slug ?? null,
    titulo: curso.titulo ?? null,
    descricao: curso.descricao ?? null,
    nivel: curso.nivel ?? null,
    tags: normaliseArray(curso.tags ?? []),
    instrutores: normaliseArray(curso.instrutores ?? []),
    rating: toDecimal(curso.rating),
    cargaHorariaHoras: toInteger(curso.cargaHorariaHoras),
    alunosMatriculados: toInteger(curso.alunosMatriculados),
    aulas: normaliseArray(curso.aulas ?? []),
    createdAt: toIsoString(curso.createdAt),
    updatedAt: toIsoString(curso.updatedAt),
    publishedAt: toIsoString(curso.publishedAt),
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
    return strapi.entityService.findOne(CURSO_UID, Number(identifier), options);
  }

  const matches = await strapi.entityService.findMany(CURSO_UID, {
    filters: { slug: identifier },
    limit: 1,
    ...options,
  });

  if (Array.isArray(matches) && matches.length > 0) {
    return matches[0];
  }

  return null;
};

export default factories.createCoreController(CURSO_UID, ({ strapi }) => ({
  async find(ctx) {
    const service = strapi.service(CURSO_UID);
    const { results, pagination } = await service.find(ctx.query);

    const payload = results
      .map((entry: Record<string, any>) => serialiseCurso(entry))
      .filter((entry): entry is Record<string, unknown> => entry !== null);

    if (pagination?.total !== undefined) {
      ctx.set('X-Total-Count', String(pagination.total));
    }

    ctx.body = payload;
  },

  async findOne(ctx) {
    const { id } = ctx.params;
    const curso = await findByIdentifier(strapi, id);

    if (!curso) {
      return ctx.notFound('Curso não encontrado.');
    }

    ctx.body = serialiseCurso(curso);
  },

  async create(ctx) {
    const data = resolveBody(ctx) ?? {};

    if (data.id && !data.slug) {
      data.slug = data.id;
    }

    const created = await strapi.entityService.create(CURSO_UID, { data });

    ctx.status = 201;
    ctx.body = serialiseCurso(created);
  },

  async update(ctx) {
    const { id } = ctx.params;
    const existing = await findByIdentifier(strapi, id);

    if (!existing) {
      return ctx.notFound('Curso não encontrado.');
    }

    const data = resolveBody(ctx) ?? {};

    if (data.id && !data.slug) {
      data.slug = data.id;
    }

    const updated = await strapi.entityService.update(CURSO_UID, existing.id, {
      data,
    });

    ctx.body = serialiseCurso(updated);
  },

  async delete(ctx) {
    const { id } = ctx.params;
    const existing = await findByIdentifier(strapi, id);

    if (!existing) {
      return ctx.notFound('Curso não encontrado.');
    }

    await strapi.entityService.delete(CURSO_UID, existing.id);

    ctx.status = 204;
    ctx.body = null;
  },
}));
