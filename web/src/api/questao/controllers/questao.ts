import { factories } from '@strapi/strapi';

const QUESTAO_UID = 'api::questao.questao';

type QuestaoEntity = Record<string, any> | null;

const ensureArray = <T>(value: unknown): T[] => {
  if (Array.isArray(value)) {
    return value as T[];
  }
  if (value === null || value === undefined) {
    return [];
  }
  return [value as T];
};

const toInteger = (value: unknown) => {
  if (value === null || value === undefined) {
    return null;
  }
  const parsed = Number.parseInt(String(value), 10);
  return Number.isNaN(parsed) ? null : parsed;
};

const serialiseAlternativas = (alternativas: unknown) =>
  ensureArray<Record<string, any>>(alternativas)
    .map((alt) => ({
      letra: alt.letra ?? null,
      descricao: alt.descricao ?? alt.texto ?? null,
      correta: Boolean(alt.correta),
    }))
    .filter((alt) => alt.letra || alt.descricao);

const serialiseQuestao = (questao: QuestaoEntity) => {
  if (!questao) {
    return null;
  }

  return {
    id: questao.id ?? null,
    slug: questao.slug ?? null,
    enunciado: questao.enunciado ?? null,
    alternativas: serialiseAlternativas(questao.alternativas),
    explicacao: questao.explicacao ?? null,
    dificuldade: questao.dificuldade ?? null,
    ano: toInteger(questao.ano),
    disciplina: questao.disciplina ?? null,
    assuntos: ensureArray<string>(questao.assuntos ?? questao.topicos ?? []),
    banca: questao.banca ?? null,
    orgaosRelacionados: ensureArray<string>(questao.orgaosRelacionados ?? questao.orgaos ?? []),
    estatisticas: questao.estatisticas ?? null,
    fonte: questao.fonte ?? null,
    tags: ensureArray<string>(questao.tags ?? []),
    createdAt: questao.createdAt ?? null,
    updatedAt: questao.updatedAt ?? null,
    publishedAt: questao.publishedAt ?? null,
  };
};

const resolveBody = (ctx: any) => {
  const body = ctx.request?.body ?? {};
  if (body.data && typeof body.data === 'object') {
    return body.data;
  }
  return body;
};

export default factories.createCoreController(QUESTAO_UID, ({ strapi }) => ({
  async find(ctx) {
    const service = strapi.service(QUESTAO_UID);
    const { results, pagination } = await service.find(ctx.query);

    const questoes = results
      .map((questao: QuestaoEntity) => serialiseQuestao(questao))
      .filter((questao): questao is Record<string, unknown> => questao !== null);

    if (pagination?.total !== undefined) {
      ctx.set('X-Total-Count', String(pagination.total));
    }

    ctx.body = questoes;
  },

  async findOne(ctx) {
    const { id } = ctx.params;
    const questao = await strapi.entityService.findOne(QUESTAO_UID, id, {});

    if (!questao) {
      return ctx.notFound('Questão não encontrada.');
    }

    ctx.body = serialiseQuestao(questao);
  },

  async create(ctx) {
    const data = resolveBody(ctx);
    const created = await strapi.entityService.create(QUESTAO_UID, { data });
    ctx.status = 201;
    ctx.body = serialiseQuestao(created);
  },

  async update(ctx) {
    const { id } = ctx.params;
    const data = resolveBody(ctx);
    const updated = await strapi.entityService.update(QUESTAO_UID, id, { data });
    ctx.body = serialiseQuestao(updated);
  },

  async delete(ctx) {
    const { id } = ctx.params;
    await strapi.entityService.delete(QUESTAO_UID, id);
    ctx.status = 204;
    ctx.body = null;
  },
}));
