import { factories } from '@strapi/strapi';

const PLAN_UID = 'api::plano.plano';

type PlanEntity = Record<string, any> | null;

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

const toIsoString = (value: unknown) => {
  if (!value) {
    return null;
  }

  const parsed = new Date(value as any);
  return Number.isNaN(parsed.valueOf()) ? null : parsed.toISOString();
};

const ensureArray = <T>(value: unknown): T[] => {
  if (Array.isArray(value)) {
    return value as T[];
  }

  return [];
};

const serialiseApprovalLogs = (logs: unknown) =>
  ensureArray<Record<string, any>>(logs).map((log) => ({
    status: log.status ?? null,
    responsavel: log.responsavel ?? null,
    observacao: log.observacao ?? null,
    registradoEm: toIsoString(log.registradoEm),
  }));

const serialisePriceAdjustments = (entries: unknown) =>
  ensureArray<Record<string, any>>(entries).map((entry) => ({
    atualizadoPor: entry.atualizadoPor ?? null,
    atualizadoEm: toIsoString(entry.atualizadoEm),
    motivo: entry.motivo ?? null,
    precoAnterior: toNumber(entry.precoAnterior),
    precoAtual: toNumber(entry.precoAtual),
    moeda: entry.moeda ?? null,
  }));

const serialiseBenefits = (benefits: unknown): string[] => {
  if (!benefits) {
    return [];
  }
  if (Array.isArray(benefits)) {
    return benefits
      .map((benefit) => {
        if (typeof benefit === 'string') {
          return benefit;
        }
        if (benefit && typeof benefit === 'object' && 'titulo' in benefit) {
          return String((benefit as any).titulo);
        }
        return String(benefit ?? '');
      })
      .filter((value) => value.trim().length > 0);
  }
  if (typeof benefits === 'string') {
    return [benefits];
  }
  return [String(benefits)];
};

class PlanValidationError extends Error {
  constructor(message: string, public status: number = 400) {
    super(message);
    this.name = 'PlanValidationError';
  }
}

const stripUndefined = (input: Record<string, unknown>) =>
  Object.fromEntries(
    Object.entries(input).filter(([, value]) => value !== undefined)
  );

const sanitisePlanInput = (
  ctx: any,
  input: Record<string, unknown>,
  options: { existing?: PlanEntity; isCreate?: boolean }
) => {
  const { existing = null, isCreate = false } = options;
  const actor = ctx.state?.user;
  const superAdmin = isSuperAdmin(actor);
  if (!input || typeof input !== 'object' || Array.isArray(input)) {
    throw new PlanValidationError('Payload inválido para o plano.');
  }

  const sanitized: Record<string, any> = stripUndefined({ ...input });

  delete sanitized.logsAprovacao;
  delete sanitized.ajustesPreco;

  if (!superAdmin) {
    delete sanitized.statusAprovacao;
    delete sanitized.aprovadoPor;
    delete sanitized.aprovadoEm;
  }

  delete sanitized.ultimaSolicitacao;

  const previousType = existing?.tipo ?? null;
  const finalType = sanitized.tipo ?? previousType ?? 'pago';

  if (finalType !== 'pago' && finalType !== 'gratis_aluno') {
    throw new PlanValidationError('Tipo de plano inválido.');
  }

  const ensurePriceString = (value: unknown) => {
    const decimal = toDecimalString(value);
    if (decimal === null) {
      return null;
    }
    return decimal;
  };

  if (finalType === 'gratis_aluno') {
    sanitized.tipo = 'gratis_aluno';
    sanitized.preco = '0.00';
    sanitized.moeda = sanitized.moeda ?? existing?.moeda ?? 'BRL';
    const existingStatus = typeof existing?.statusAprovacao === 'string'
      ? existing?.statusAprovacao
      : null;
    const wasApproved = existingStatus === 'aprovado';
    sanitized.statusAprovacao = wasApproved ? existingStatus : 'pendente';
    sanitized.aprovadoPor = wasApproved ? existing?.aprovadoPor ?? null : null;
    sanitized.aprovadoEm = wasApproved ? toIsoString(existing?.aprovadoEm) : null;
    sanitized.codigoCopiaCola = null;
    sanitized.qrCodeUrl = null;
    sanitized.qrCodeBase64 = null;
    sanitized.pixExpiraEm = null;

    const changedToFree = previousType !== 'gratis_aluno';
    if (isCreate || changedToFree || !wasApproved) {
      sanitized.ultimaSolicitacao = new Date().toISOString();
    }
  } else {
    sanitized.tipo = 'pago';
    const candidatePrice = sanitized.preco ?? existing?.preco ?? null;
    const decimal = ensurePriceString(candidatePrice);
    if (decimal === null) {
      throw new PlanValidationError('Planos pagos precisam informar um preço válido.');
    }

    const numeric = Number.parseFloat(decimal);
    if (Number.isNaN(numeric) || numeric <= 0) {
      throw new PlanValidationError('Planos pagos devem ter preço maior que zero.');
    }

    sanitized.preco = decimal;
    sanitized.moeda = sanitized.moeda ?? existing?.moeda ?? 'BRL';
  }

  return sanitized;
};

const serialisePlan = (plan: PlanEntity) => {
  if (!plan) {
    return null;
  }

  const price = toNumber(plan.preco);

  const pixPayload = plan.tipo === 'pago'
    ? {
        chave: plan.chavePix ?? null,
        tipoChave: plan.tipoChavePix ?? null,
        codigoCopiaCola: plan.codigoCopiaCola ?? null,
        qrCodeUrl: plan.qrCodeUrl ?? null,
        qrCodeBase64: plan.qrCodeBase64 ?? null,
        expiraEm: toIsoString(plan.pixExpiraEm),
        valor: price,
        moeda: plan.moeda ?? 'BRL',
      }
    : null;

  return {
    id: plan.id,
    nome: plan.nome,
    slug: plan.slug,
    descricao: plan.descricao ?? null,
    tipo: plan.tipo ?? null,
    periodicidade: plan.periodicidade ?? null,
    preco: price,
    moeda: plan.moeda ?? 'BRL',
    chavePix: plan.chavePix ?? null,
    tipoChavePix: plan.tipoChavePix ?? null,
    codigoCopiaCola: plan.codigoCopiaCola ?? null,
    qrCode: {
      url: plan.qrCodeUrl ?? null,
      base64: plan.qrCodeBase64 ?? null,
    },
    pixExpiraEm: toIsoString(plan.pixExpiraEm),
    pix: pixPayload,
    beneficios: serialiseBenefits(plan.beneficios),
    statusAprovacao: plan.statusAprovacao ?? null,
    aprovadoPor: plan.aprovadoPor ?? null,
    aprovadoEm: toIsoString(plan.aprovadoEm),
    ultimaSolicitacao: toIsoString(plan.ultimaSolicitacao),
    destaque: Boolean(plan.destaque),
    logsAprovacao: serialiseApprovalLogs(plan.logsAprovacao),
    ajustesPreco: serialisePriceAdjustments(plan.ajustesPreco),
    createdAt: toIsoString(plan.createdAt),
    updatedAt: toIsoString(plan.updatedAt),
    publishedAt: toIsoString(plan.publishedAt),
  };
};

const resolveRequestData = (ctx: any) => {
  const body = ctx.request?.body ?? {};
  if (body.data && typeof body.data === 'object') {
    return body.data;
  }
  return body;
};

const toArray = <T>(value: unknown): T[] => {
  if (Array.isArray(value)) {
    return value as T[];
  }

  if (value) {
    return [value as T];
  }

  return [];
};

const isSuperAdmin = (user: any): boolean => {
  if (!user) {
    return false;
  }

  const roles = [
    ...toArray<Record<string, any>>(user.roles ?? []),
    ...toArray<Record<string, any>>(user.role ?? []),
  ];

  return roles.some((role) => {
    if (!role) {
      return false;
    }

    const code = String(role.code ?? '').toLowerCase();
    const name = String(role.name ?? '').toLowerCase();

    return code === 'strapi-super-admin' || name === 'super admin';
  });
};

const getAdminActor = (user: any): string | null => {
  if (!user) {
    return null;
  }

  const firstName = typeof user.firstname === 'string' ? user.firstname.trim() : '';
  const lastName = typeof user.lastname === 'string' ? user.lastname.trim() : '';
  const fullName = [firstName, lastName]
    .filter((part) => part.length > 0)
    .join(' ')
    .trim();

  if (fullName.length > 0) {
    return fullName;
  }

  const username = typeof user.username === 'string' ? user.username.trim() : '';
  if (username.length > 0) {
    return username;
  }

  const email = typeof user.email === 'string' ? user.email.trim() : '';
  if (email.length > 0) {
    return email;
  }

  return null;
};

export default factories.createCoreController(PLAN_UID, ({ strapi }) => ({
  async find(ctx) {
    const service = strapi.service(PLAN_UID);
    const { results, pagination } = await service.find({
      ...ctx.query,
      populate: { logsAprovacao: true, ajustesPreco: true, beneficios: true },
    });

    const planos = results
      .map((plan: PlanEntity) => serialisePlan(plan))
      .filter((plan): plan is Record<string, unknown> => plan !== null);

    if (pagination?.total !== undefined) {
      ctx.set('X-Total-Count', String(pagination.total));
    }

    ctx.body = planos;
  },

  async findOne(ctx) {
    const { id } = ctx.params;
    const plan = await strapi.entityService.findOne(PLAN_UID, id, {
      populate: { logsAprovacao: true, ajustesPreco: true, beneficios: true },
    });

    if (!plan) {
      return ctx.notFound('Plano não encontrado.');
    }

    ctx.body = serialisePlan(plan);
  },

  async create(ctx) {
    try {
      const data = sanitisePlanInput(
        ctx,
        resolveRequestData(ctx),
        { isCreate: true }
      );

      const created = await strapi.entityService.create(PLAN_UID, {
        data,
        populate: { logsAprovacao: true, ajustesPreco: true, beneficios: true },
      });

      ctx.status = 201;
      ctx.body = serialisePlan(created);
    } catch (error) {
      if (error instanceof PlanValidationError) {
        if (error.status === 400) {
          return ctx.badRequest(error.message);
        }
        return ctx.throw(error.status, error.message);
      }
      throw error;
    }
  },

  async update(ctx) {
    const { id } = ctx.params;
    const existing = await strapi.entityService.findOne(PLAN_UID, id);
    if (!existing) {
      return ctx.notFound('Plano não encontrado.');
    }

    try {
      const data = sanitisePlanInput(
        ctx,
        resolveRequestData(ctx),
        { existing }
      );

      const updated = await strapi.entityService.update(PLAN_UID, id, {
        data,
        populate: { logsAprovacao: true, ajustesPreco: true, beneficios: true },
      });

      ctx.body = serialisePlan(updated);
    } catch (error) {
      if (error instanceof PlanValidationError) {
        if (error.status === 400) {
          return ctx.badRequest(error.message);
        }
        return ctx.throw(error.status, error.message);
      }
      throw error;
    }
  },

  async delete(ctx) {
    const { id } = ctx.params;
    await strapi.entityService.delete(PLAN_UID, id);
    ctx.status = 204;
    ctx.body = null;
  },

  async approve(ctx) {
    const { id } = ctx.params;
    const { observacao } = ctx.request.body ?? {};
    const adminUser = ctx.state?.user;

    if (!isSuperAdmin(adminUser)) {
      return ctx.forbidden(
        'Apenas super admins podem aprovar Planos Grátis para Alunos.'
      );
    }

    const actor = getAdminActor(adminUser);
    if (!actor) {
      return ctx.badRequest(
        'Não foi possível identificar o responsável pela aprovação.'
      );
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
    const approvalLogs = Array.isArray(plan.logsAprovacao)
      ? [...plan.logsAprovacao]
      : [];
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

    ctx.body = serialisePlan(updatedPlan);
  },

  async updatePrice(ctx) {
    const { id } = ctx.params;
    const { preco, moeda, motivo } = ctx.request.body ?? {};

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

    const adminUser = ctx.state?.user;
    const actor = getAdminActor(adminUser);
    if (!actor) {
      return ctx.forbidden(
        'Não foi possível identificar o administrador responsável pela alteração de preço.'
      );
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

    ctx.body = serialisePlan(updatedPlan);
  },

  async dashboard(ctx) {
    const plans = await strapi.entityService.findMany(PLAN_UID, {
      populate: { ajustesPreco: true, logsAprovacao: true },
      sort: { updatedAt: 'desc' },
    });

    const serialisedPlans = plans.map((plan) => serialisePlan(plan)).filter(Boolean) as any[];

    const totalPlanosPagos = serialisedPlans.filter((plan) => plan.tipo === 'pago').length;
    const totalPlanosGratuitos = serialisedPlans.filter((plan) => plan.tipo === 'gratis_aluno').length;
    const pendentesAprovacao = serialisedPlans.filter(
      (plan) => plan.tipo === 'gratis_aluno' && plan.statusAprovacao === 'pendente'
    ).length;

    const historicoPrecos = serialisedPlans.flatMap((plan) => {
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

        const precoAnterior = ajuste.precoAnterior;
        const precoAtual = ajuste.precoAtual;

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
      planos: serialisedPlans,
    };

    ctx.body = response;
  },
}));
