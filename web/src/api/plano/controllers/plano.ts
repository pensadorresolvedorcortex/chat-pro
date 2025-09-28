import { factories } from '@strapi/strapi';

const PLAN_UID = 'api::plano.plano';

const toDecimalString = (value: unknown) => {
  if (value === null || value === undefined) {
    return null;
  }

  const numeric = typeof value === 'string' ? Number.parseFloat(value) : Number(value);
  if (Number.isNaN(numeric)) {
    return null;
  }

  return numeric.toFixed(2);
};

const toNumber = (value: unknown) => {
  const decimalString = toDecimalString(value);
  return decimalString === null ? null : Number.parseFloat(decimalString);
};

export default factories.createCoreController(PLAN_UID, ({ strapi }) => ({
  async approve(ctx) {
    const { id } = ctx.params;
    const { observacao, responsavel } = ctx.request.body ?? {};
    const adminUser = ctx.state?.user;
    const actor = responsavel || adminUser?.username || adminUser?.email;

    if (!actor) {
      return ctx.badRequest('responsavel é obrigatório para aprovar o plano.');
    }

    const plan = await strapi.entityService.findOne(PLAN_UID, id, {
      populate: { logsAprovacao: true, ajustesPreco: true, beneficios: true },
    });

    if (!plan) {
      return ctx.notFound('Plano não encontrado.');
    }

    if (plan.tipo !== 'gratis_aluno') {
      return ctx.badRequest('Somente Planos Grátis para Alunos exigem aprovação manual.');
    }

    if (plan.statusAprovacao === 'aprovado') {
      return ctx.badRequest('O plano já está aprovado.');
    }

    const now = new Date().toISOString();
    const approvalLogs = Array.isArray(plan.logsAprovacao) ? [...plan.logsAprovacao] : [];
    approvalLogs.push({
      status: 'aprovado',
      responsavel: actor,
      observacao: observacao ?? null,
      registradoEm: now,
    });

    const updatedPlan = await strapi.entityService.update(PLAN_UID, id, {
      data: {
        statusAprovacao: 'aprovado',
        aprovadoPor: actor,
        aprovadoEm: now,
        logsAprovacao: approvalLogs,
      },
      populate: { logsAprovacao: true, ajustesPreco: true, beneficios: true },
    });

    const sanitized = await this.sanitizeOutput(updatedPlan, ctx);
    return this.transformResponse(sanitized);
  },

  async updatePrice(ctx) {
    const { id } = ctx.params;
    const { preco, moeda, motivo, atualizadoPor } = ctx.request.body ?? {};

    if (preco === undefined || preco === null) {
      return ctx.badRequest('O campo preco é obrigatório.');
    }

    const numericPrice = Number(preco);
    if (Number.isNaN(numericPrice) || numericPrice < 0) {
      return ctx.badRequest('O campo preco deve ser um número maior ou igual a zero.');
    }

    const plan = await strapi.entityService.findOne(PLAN_UID, id, {
      populate: { ajustesPreco: true, logsAprovacao: true, beneficios: true },
    });

    if (!plan) {
      return ctx.notFound('Plano não encontrado.');
    }

    if (plan.tipo === 'gratis_aluno' && numericPrice > 0) {
      return ctx.badRequest('Planos Grátis para Alunos não podem receber preço maior que zero.');
    }

    const actor = atualizadoPor || ctx.state?.user?.username || ctx.state?.user?.email;
    if (!actor) {
      return ctx.badRequest('O campo atualizadoPor é obrigatório.');
    }

    const now = new Date().toISOString();
    const ajustes = Array.isArray(plan.ajustesPreco) ? [...plan.ajustesPreco] : [];

    ajustes.push({
      atualizadoPor: actor,
      atualizadoEm: now,
      motivo: motivo ?? null,
      precoAnterior: toDecimalString(plan.preco),
      precoAtual: numericPrice.toFixed(2),
      moeda: moeda || plan.moeda || 'BRL',
    });

    const updatedPlan = await strapi.entityService.update(PLAN_UID, id, {
      data: {
        preco: numericPrice.toFixed(2),
        moeda: moeda || plan.moeda || 'BRL',
        ajustesPreco: ajustes,
      },
      populate: { logsAprovacao: true, ajustesPreco: true, beneficios: true },
    });

    const sanitized = await this.sanitizeOutput(updatedPlan, ctx);
    return this.transformResponse(sanitized);
  },

  async dashboard(ctx) {
    const plans = await strapi.entityService.findMany(PLAN_UID, {
      populate: { ajustesPreco: true, logsAprovacao: true },
      sort: { updatedAt: 'desc' },
    });

    const sanitizedPlans = await Promise.all(
      plans.map((plan) => this.sanitizeOutput(plan, ctx))
    );

    const totalPlanosPagos = sanitizedPlans.filter((plan) => plan.tipo === 'pago').length;
    const totalPlanosGratuitos = sanitizedPlans.filter((plan) => plan.tipo === 'gratis_aluno').length;
    const pendentesAprovacao = sanitizedPlans.filter(
      (plan) => plan.tipo === 'gratis_aluno' && plan.statusAprovacao === 'pendente'
    ).length;

    const historicoPrecos = plans.flatMap((plan) => {
      if (!Array.isArray(plan.ajustesPreco)) {
        return [];
      }

      return plan.ajustesPreco.map((ajuste) => {
        const entry: Record<string, unknown> = {
          planoId: `${plan.slug ?? plan.id}`,
          nomePlano: plan.nome,
          atualizadoPor: ajuste.atualizadoPor,
          atualizadoEm: ajuste.atualizadoEm,
          moeda: ajuste.moeda ?? plan.moeda ?? 'BRL',
        };

        const precoAnterior = toNumber(ajuste.precoAnterior);
        const precoAtual = toNumber(ajuste.precoAtual);

        if (precoAnterior !== null) {
          entry.precoAnterior = precoAnterior;
        }

        if (precoAtual !== null) {
          entry.precoAtual = precoAtual;
        }

        if (ajuste.motivo) {
          entry.motivo = ajuste.motivo;
        }

        return entry;
      });
    });

    const historicoOrdenado = [...historicoPrecos].sort((a, b) => {
      const dataB = b.atualizadoEm ? new Date(String(b.atualizadoEm)).getTime() : 0;
      const dataA = a.atualizadoEm ? new Date(String(a.atualizadoEm)).getTime() : 0;
      return dataB - dataA;
    });

    const ultimoAjuste = historicoOrdenado.find((item) => Boolean(item.atualizadoEm)) ?? null;

    const response = {
      totalPlanosPagos,
      totalPlanosGratuitos,
      pendentesAprovacao,
      ultimoAjuste,
      historicoPrecos: historicoOrdenado,
      planos: sanitizedPlans,
    };

    const sanitized = await this.sanitizeOutput(response, ctx);
    return this.transformResponse(sanitized);
  },
}));
