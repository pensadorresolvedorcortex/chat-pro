import { factories } from '@strapi/strapi';

const BIBLIOTECA_UID = 'api::biblioteca.biblioteca';

const ensureArray = <T>(value: unknown): T[] => {
  if (!value) {
    return [];
  }
  if (Array.isArray(value)) {
    return value as T[];
  }
  return [value as T];
};

const ensureStringArray = (value: unknown): string[] =>
  ensureArray<unknown>(value)
    .map((entry) => {
      if (entry === null || entry === undefined) {
        return null;
      }
      return String(entry).trim();
    })
    .filter((entry): entry is string => Boolean(entry));

const toIsoString = (value: unknown): string | null => {
  if (!value) {
    return null;
  }
  const date = value instanceof Date ? value : new Date(String(value));
  return Number.isNaN(date.valueOf()) ? null : date.toISOString();
};

const toInteger = (value: unknown): number | null => {
  if (value === null || value === undefined) {
    return null;
  }
  const numeric = typeof value === 'string' ? Number.parseInt(value, 10) : Number(value);
  if (Number.isNaN(numeric)) {
    return null;
  }
  return Math.max(0, Math.trunc(numeric));
};

const parseBoolean = (value: unknown): boolean | null => {
  if (value === null || value === undefined) {
    return null;
  }
  if (typeof value === 'boolean') {
    return value;
  }
  const normalised = String(value).trim().toLowerCase();
  if (normalised === 'true' || normalised === '1') {
    return true;
  }
  if (normalised === 'false' || normalised === '0') {
    return false;
  }
  return null;
};

const resolveBody = (ctx: any) => {
  const body = ctx.request?.body ?? {};
  if (body.data && typeof body.data === 'object') {
    return body.data;
  }
  return body;
};

const allowedFormats = new Set(['pdf', 'video', 'audio', 'checklist', 'modelo']);
const allowedStatus = new Set(['rascunho', 'publicado', 'arquivado']);

const normaliseMaterialInput = (payload: Record<string, any>) => {
  const data: Record<string, any> = {};

  if (payload.id && !payload.slug) {
    data.slug = String(payload.id).trim();
  }
  if (payload.slug) {
    data.slug = String(payload.slug).trim();
  }
  if (payload.titulo) {
    data.titulo = String(payload.titulo);
  }
  if ('descricao' in payload) {
    data.descricao = payload.descricao ?? null;
  }

  if (payload.formato) {
    const formato = String(payload.formato).toLowerCase();
    if (allowedFormats.has(formato)) {
      data.formato = formato;
    }
  }

  if (payload.arquivoUrl) {
    data.arquivoUrl = String(payload.arquivoUrl);
  }
  if ('linkExterno' in payload) {
    data.linkExterno = payload.linkExterno ?? null;
  }
  if ('thumbnailUrl' in payload) {
    data.thumbnailUrl = payload.thumbnailUrl ?? null;
  }

  const duracao = toInteger(payload.duracaoMinutos);
  if (duracao !== null) {
    data.duracaoMinutos = duracao;
  } else if ('duracaoMinutos' in payload) {
    data.duracaoMinutos = null;
  }

  const disciplinaIds = ensureStringArray(payload.disciplinaIds ?? payload.disciplinaId);
  if (disciplinaIds.length > 0) {
    data.disciplinaIds = disciplinaIds;
  } else if ('disciplinaIds' in payload || 'disciplinaId' in payload) {
    data.disciplinaIds = [];
  }

  const assuntoIds = ensureStringArray(payload.assuntoIds ?? payload.assuntoId);
  if (assuntoIds.length > 0) {
    data.assuntoIds = assuntoIds;
  } else if ('assuntoIds' in payload || 'assuntoId' in payload) {
    data.assuntoIds = [];
  }

  const tags = ensureStringArray(payload.tags);
  if (tags.length > 0) {
    data.tags = tags;
  } else if ('tags' in payload) {
    data.tags = [];
  }

  const destaque = parseBoolean(payload.destaque);
  if (destaque !== null) {
    data.destaque = destaque;
  }

  const publicadoEm = toIsoString(payload.publicadoEm);
  if (publicadoEm) {
    data.publicadoEm = publicadoEm;
  } else if ('publicadoEm' in payload) {
    data.publicadoEm = null;
  }

  if (payload.status) {
    const status = String(payload.status).toLowerCase();
    if (allowedStatus.has(status)) {
      data.status = status;
    }
  }

  const autorId = payload.autorId ?? payload.autor?.id ?? null;
  if (autorId) {
    data.autorId = String(autorId);
  } else if ('autorId' in payload || (payload.autor && 'id' in payload.autor)) {
    data.autorId = null;
  }

  const autorNome = payload.autorNome ?? payload.autor?.nome ?? null;
  if (autorNome) {
    data.autorNome = String(autorNome);
  } else if ('autorNome' in payload || (payload.autor && 'nome' in payload.autor)) {
    data.autorNome = null;
  }

  const downloads = toInteger(payload.downloads);
  if (downloads !== null) {
    data.downloads = downloads;
  } else if ('downloads' in payload) {
    data.downloads = 0;
  }

  const visualizacoes = toInteger(payload.visualizacoes);
  if (visualizacoes !== null) {
    data.visualizacoes = visualizacoes;
  } else if ('visualizacoes' in payload) {
    data.visualizacoes = 0;
  }

  return data;
};

const isNumericId = (value: unknown) => {
  if (value === null || value === undefined) {
    return false;
  }
  return /^\d+$/.test(String(value));
};

const findByIdentifier = async (strapi: any, identifier: string) => {
  if (isNumericId(identifier)) {
    return strapi.entityService.findOne(BIBLIOTECA_UID, Number(identifier));
  }

  const matches = await strapi.entityService.findMany(BIBLIOTECA_UID, {
    filters: { slug: identifier },
    limit: 1,
  });

  if (Array.isArray(matches) && matches.length > 0) {
    return matches[0];
  }

  return null;
};

const serialiseMaterial = (material: Record<string, any> | null) => {
  if (!material) {
    return null;
  }

  const disciplinaIds = ensureStringArray(material.disciplinaIds);
  const assuntoIds = ensureStringArray(material.assuntoIds);
  const tags = ensureStringArray(material.tags);
  const autorId = material.autorId ? String(material.autorId) : null;
  const autorNome = material.autorNome ? String(material.autorNome) : null;

  return {
    id: material.slug ?? String(material.id ?? ''),
    titulo: material.titulo ?? null,
    descricao: material.descricao ?? null,
    formato: material.formato ?? null,
    arquivoUrl: material.arquivoUrl ?? null,
    linkExterno: material.linkExterno ?? null,
    thumbnailUrl: material.thumbnailUrl ?? null,
    duracaoMinutos: material.duracaoMinutos ?? null,
    disciplinaIds,
    assuntoIds,
    tags,
    destaque: Boolean(material.destaque),
    publicadoEm: toIsoString(material.publicadoEm ?? material.publishedAt),
    status: material.status ?? (material.publishedAt ? 'publicado' : 'rascunho'),
    autor: autorId || autorNome ? { id: autorId, nome: autorNome } : null,
    downloads: toInteger(material.downloads) ?? 0,
    visualizacoes: toInteger(material.visualizacoes) ?? 0,
    createdAt: toIsoString(material.createdAt),
    updatedAt: toIsoString(material.updatedAt),
  };
};

export default factories.createCoreController(BIBLIOTECA_UID, ({ strapi }) => ({
  async find(ctx) {
    const { formato, disciplinaId, destaque, tag, ...restQuery } = ctx.query ?? {};

    const page = Math.max(1, Number.parseInt(restQuery.page ?? '1', 10) || 1);
    const pageSize = Math.max(1, Math.min(100, Number.parseInt(restQuery.pageSize ?? '20', 10) || 20));

    const filters = { ...(restQuery.filters ?? {}) };

    if (formato) {
      filters.formato = String(formato).toLowerCase();
    }

    const destaqueFilter = parseBoolean(destaque);
    if (destaqueFilter !== null) {
      filters.destaque = destaqueFilter;
    }

    const queryOptions: Record<string, any> = {
      ...restQuery,
      filters,
      populate: {},
      sort: restQuery.sort ?? { publicadoEm: 'desc', updatedAt: 'desc' },
      pagination: {
        page: 1,
        pageSize: 200,
      },
    };

    delete queryOptions.page;
    delete queryOptions.pageSize;
    delete queryOptions.formato;
    delete queryOptions.disciplinaId;
    delete queryOptions.destaque;
    delete queryOptions.tag;

    const { results } = await strapi.service(BIBLIOTECA_UID).find(queryOptions);

    const serialised = results
      .map((entry: Record<string, any>) => serialiseMaterial(entry))
      .filter((entry): entry is Record<string, any> => entry !== null);

    const tagFilter = tag ? String(tag).toLowerCase() : null;
    const disciplinaFilter = disciplinaId ? String(disciplinaId) : null;

    const filtered = serialised.filter((item) => {
      if (disciplinaFilter && !item.disciplinaIds.includes(disciplinaFilter)) {
        return false;
      }
      if (tagFilter) {
        const tagsLower = (item.tags ?? []).map((entry: string) => entry.toLowerCase());
        if (!tagsLower.includes(tagFilter)) {
          return false;
        }
      }
      return true;
    });

    const total = filtered.length;
    const pageCount = total === 0 ? 0 : Math.ceil(total / pageSize);
    const start = (page - 1) * pageSize;
    const data = filtered.slice(start, start + pageSize);

    ctx.set('X-Total-Count', String(total));
    ctx.body = {
      data,
      pagination: {
        page,
        pageSize,
        pageCount,
        total,
      },
    };
  },

  async findOne(ctx) {
    const { id } = ctx.params;
    const material = await findByIdentifier(strapi, id);

    if (!material) {
      return ctx.notFound('Material não encontrado.');
    }

    ctx.body = serialiseMaterial(material);
  },

  async create(ctx) {
    const payload = normaliseMaterialInput(resolveBody(ctx) ?? {});

    const created = await strapi.entityService.create(BIBLIOTECA_UID, {
      data: payload,
    });

    ctx.status = 201;
    ctx.body = serialiseMaterial(created);
  },

  async update(ctx) {
    const { id } = ctx.params;
    const existing = await findByIdentifier(strapi, id);

    if (!existing) {
      return ctx.notFound('Material não encontrado.');
    }

    const payload = normaliseMaterialInput(resolveBody(ctx) ?? {});

    const updated = await strapi.entityService.update(BIBLIOTECA_UID, existing.id, {
      data: payload,
    });

    ctx.body = serialiseMaterial(updated);
  },

  async delete(ctx) {
    const { id } = ctx.params;
    const existing = await findByIdentifier(strapi, id);

    if (!existing) {
      return ctx.notFound('Material não encontrado.');
    }

    await strapi.entityService.delete(BIBLIOTECA_UID, existing.id);

    ctx.status = 204;
    ctx.body = null;
  },
}));
