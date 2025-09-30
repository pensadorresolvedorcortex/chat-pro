import { factories } from '@strapi/strapi';
import { randomUUID } from 'node:crypto';

const ASSINATURA_UID = 'api::assinatura.assinatura';
const PLANO_UID = 'api::plano.plano';
const COBRANCA_UID = 'api::cobranca-pix.cobranca-pix';

const PIX_STATUSES = ['pendente', 'confirmado', 'expirado', 'cancelado', 'reembolsado'] as const;
type PixStatus = (typeof PIX_STATUSES)[number];

type PixChargeInput = {
  planoId?: string | number;
  assinaturaId?: string | number;
  usuarioId?: string;
  valor?: unknown;
  expiracaoMinutos?: unknown;
  nomePagador?: string | null;
  documentoPagador?: string | null;
  metadata?: Record<string, unknown> | null;
};

const formatAmount = (value: unknown, fallback: number): number => {
  if (value === null || value === undefined) {
    return Number(fallback.toFixed(2));
  }
  const numeric = Number.parseFloat(String(value));
  if (Number.isNaN(numeric) || numeric <= 0) {
    throw new Error('O valor informado é inválido.');
  }
  return Number(numeric.toFixed(2));
};

const calculateExpiration = (minutes: unknown): string => {
  const parsed = Number.parseInt(String(minutes ?? '30'), 10);
  const duration = Number.isNaN(parsed) || parsed <= 0 ? 30 : parsed;
  const expires = new Date(Date.now() + duration * 60 * 1000);
  return expires.toISOString();
};

const normaliseTxid = () => randomUUID().replace(/-/g, '').slice(0, 25).toUpperCase();

const buildCopyPasteCode = ({
  chave,
  txid,
  valor,
  nome,
  cidade,
}: {
  chave: string;
  txid: string;
  valor: number;
  nome: string;
  cidade: string;
}) => {
  const amount = valor.toFixed(2);
  const safeName = nome.trim().slice(0, 25).toUpperCase();
  const safeCity = cidade.trim().slice(0, 15).toUpperCase();
  return [
    '000201',
    `26360014BR.GOV.BCB.PIX01${String(chave.length).padStart(2, '0')}${chave}`,
    '52040000',
    '5303986',
    `540${String(amount.length).padStart(2, '0')}${amount}`,
    '5802BR',
    `591${String(safeName.length).padStart(2, '0')}${safeName}`,
    `601${String(safeCity.length).padStart(2, '0')}${safeCity}`,
    `62070503${txid}`,
    '6304FFFF',
  ].join('');
};

const sanitiseCharge = (charge: any) => {
  if (!charge) {
    return null;
  }
  const plano = charge.plano ?? {};
  const assinatura = charge.assinatura ?? {};
  return {
    id: charge.id,
    txid: charge.txid,
    status: charge.status,
    valor: Number.parseFloat(String(charge.valor ?? 0)) || 0,
    moeda: charge.moeda ?? 'BRL',
    codigoCopiaCola: charge.codigoCopiaCola,
    chavePix: charge.chavePix,
    qrCode: {
      base64: charge.qrCodeBase64,
      url: charge.qrCodeUrl,
    },
    expiraEm: charge.expiraEm,
    confirmadoEm: charge.confirmadoEm,
    expiradoEm: charge.expiradoEm,
    canceladoEm: charge.canceladoEm,
    planoId: plano.id ?? plano,
    assinaturaId: assinatura.id ?? assinatura,
    usuarioId: charge.usuarioId,
    pagador: {
      nome: charge.nomePagador,
      documento: charge.documentoPagador,
    },
    metadata: charge.metadata ?? null,
    criadoEm: charge.createdAt ?? null,
    atualizadoEm: charge.updatedAt ?? null,
  };
};

const mapChargeStatusToSubscription = (status: PixStatus): string => {
  switch (status) {
    case 'confirmado':
      return 'ativa';
    case 'expirado':
      return 'expirada';
    case 'cancelado':
    case 'reembolsado':
      return 'cancelada';
    default:
      return 'pendente';
  }
};

export default factories.createCoreController(ASSINATURA_UID, ({ strapi }) => ({
  async getPrimaryPixKey(ctx) {
    const configured = getConfiguredPixKey(strapi);
    const nome = configPixName(strapi);
    const cidade = configPixCity(strapi);

    let effectiveKey = configured.chave;
    let effectiveType = configured.tipo ?? 'aleatoria';

    if (!effectiveKey) {
      const [planWithKey] = await strapi.entityService.findMany(PLANO_UID, {
        filters: { chavePix: { $notNull: true } },
        sort: { updatedAt: 'desc' },
        limit: 1,
      });
      if (planWithKey) {
        effectiveKey = planWithKey.chavePix;
        effectiveType = planWithKey.tipoChavePix ?? effectiveType;
      }
    }

    if (!effectiveKey) {
      return ctx.notFound('Nenhuma chave Pix configurada.');
    }

    const txid = ctx.query?.txid ? String(ctx.query.txid) : normaliseTxid();
    const valor = ctx.query?.valor ? Number.parseFloat(String(ctx.query.valor)) : 0;
    const amount = Number.isNaN(valor) || valor <= 0 ? 1 : valor;
    const copiaCola = buildCopyPasteCode({
      chave: effectiveKey,
      txid,
      valor: amount,
      nome,
      cidade,
    });

    ctx.body = {
      chavePix: effectiveKey,
      tipoChavePix: effectiveType,
      nomeRecebedor: nome,
      cidadeRecebedor: cidade,
      txid,
      codigoCopiaCola: copiaCola,
      qrCode: {
        base64: Buffer.from(copiaCola).toString('base64'),
        url: `https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=${encodeURIComponent(copiaCola)}`,
      },
    };
  },

  async createPixCharge(ctx) {
    const payload = (ctx.request.body || {}) as PixChargeInput;
    const { planoId, assinaturaId, usuarioId, valor, expiracaoMinutos, nomePagador, documentoPagador, metadata } = payload;

    if (!planoId && !assinaturaId) {
      return ctx.badRequest('Informe planoId ou assinaturaId para emitir a cobrança.');
    }

    let plan: any | null = null;
    if (planoId) {
      plan = await strapi.entityService.findOne(PLANO_UID, planoId);
      if (!plan) {
        return ctx.notFound('Plano não encontrado.');
      }
      if (plan.tipo === 'gratis_aluno') {
        return ctx.badRequest('Planos Grátis para Alunos não geram cobrança Pix.');
      }
    }

    let subscription: any | null = null;
    if (assinaturaId) {
      subscription = await strapi.entityService.findOne(ASSINATURA_UID, assinaturaId, {
        populate: { plano: true },
      });
      if (!subscription) {
        return ctx.notFound('Assinatura não encontrada.');
      }
      if (plan && subscription.plano?.id && subscription.plano.id !== plan.id) {
        return ctx.badRequest('A assinatura informada não pertence ao plano solicitado.');
      }
      if (!plan && subscription.plano?.id) {
        plan = subscription.plano;
      }
    }

    if (!plan) {
      return ctx.badRequest('Não foi possível resolver o plano da cobrança.');
    }

    const effectiveUserId = usuarioId || subscription?.usuarioId;
    if (!effectiveUserId) {
      return ctx.badRequest('O usuário da assinatura precisa ser informado.');
    }

    let amount: number;
    try {
      amount = formatAmount(valor, Number(plan.preco ?? 0) || 0.0);
    } catch (error: any) {
      return ctx.badRequest(error?.message ?? 'Valor da cobrança inválido.');
    }
    if (!amount || amount <= 0) {
      return ctx.badRequest('O valor da cobrança precisa ser maior que zero.');
    }

    const expiresAt = calculateExpiration(expiracaoMinutos);
    const txid = normaliseTxid();

    if (!subscription) {
      subscription = await strapi.entityService.create(ASSINATURA_UID, {
        data: {
          usuarioId: effectiveUserId,
          plano: plan.id,
          metodoPagamento: 'pix',
          status: 'pendente',
          iniciadaEm: new Date().toISOString(),
          expiraEm: expiresAt,
          pixChaveUsada: plan.chavePix ?? null,
          pixNomePagador: nomePagador ?? null,
          pixDocumentoPagador: documentoPagador ?? null,
          metadata: metadata ?? null,
        },
      });
    }

    const configured = getConfiguredPixKey(strapi);
    const pixKey = plan.chavePix || configured.chave || 'academia.pix@pagamentos.com';
    const copiaCola = buildCopyPasteCode({
      chave: pixKey,
      txid,
      valor: amount,
      nome: configPixName(strapi),
      cidade: configPixCity(strapi),
    });

    const charge = await strapi.entityService.create(COBRANCA_UID, {
      data: {
        txid,
        status: 'pendente',
        valor: amount,
        moeda: plan.moeda || 'BRL',
        codigoCopiaCola: copiaCola,
        chavePix: pixKey,
        qrCodeBase64: Buffer.from(copiaCola).toString('base64'),
        qrCodeUrl: `https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=${encodeURIComponent(copiaCola)}`,
        expiraEm: expiresAt,
        usuarioId: effectiveUserId,
        nomePagador: nomePagador ?? null,
        documentoPagador: documentoPagador ?? null,
        metadata: metadata ?? null,
        plano: plan.id,
        assinatura: subscription.id,
      },
      populate: { plano: true, assinatura: true },
    });

    await strapi.entityService.update(ASSINATURA_UID, subscription.id, {
      data: {
        expiraEm: expiresAt,
        status: 'pendente',
        pixChaveUsada: pixKey,
        pixNomePagador: nomePagador ?? subscription.pixNomePagador ?? null,
        pixDocumentoPagador: documentoPagador ?? subscription.pixDocumentoPagador ?? null,
      },
    });

    ctx.status = 201;
    ctx.body = sanitiseCharge(charge);
  },

  async findPixCharge(ctx) {
    const { id } = ctx.params;
    if (!id) {
      return ctx.badRequest('Informe o identificador da cobrança.');
    }

    const queryId = Number.isNaN(Number(id)) ? null : Number(id);
    let charge: any | null = null;
    if (queryId !== null) {
      charge = await strapi.entityService.findOne(COBRANCA_UID, queryId, {
        populate: { plano: true, assinatura: true },
      });
    }
    if (!charge) {
      const [byTxid] = await strapi.entityService.findMany(COBRANCA_UID, {
        filters: { txid: id },
        populate: { plano: true, assinatura: true },
        limit: 1,
      });
      charge = byTxid ?? null;
    }

    if (!charge) {
      return ctx.notFound('Cobrança não encontrada.');
    }

    ctx.body = sanitiseCharge(charge);
  },

  async updatePixChargeStatus(ctx) {
    const { id } = ctx.params;
    const body = (ctx.request.body ?? {}) as Record<string, unknown>;
    const rawStatus = body.status;
    const occurredAt =
      typeof body.occurredAt === 'string'
        ? body.occurredAt
        : typeof body.timestamp === 'string'
            ? body.timestamp
            : undefined;

    if (!id) {
      return ctx.badRequest('Informe o identificador da cobrança.');
    }

    if (typeof rawStatus !== 'string' || !PIX_STATUSES.includes(rawStatus as PixStatus)) {
      return ctx.badRequest('Status inválido.');
    }

    const fields = { ...body };
    delete fields.status;
    delete fields.occurredAt;
    delete fields.timestamp;

    try {
      const charge = await applyPixChargeStatusUpdate(strapi, id, rawStatus as PixStatus, {
        occurredAt,
        fields,
      });

      strapi.eventHub.emit('pix.charge.status', {
        event: 'pix.cobranca.status.manual',
        chargeId: charge.id,
        status: charge.status,
        txid: charge.txid,
        occurredAt: occurredAt ?? new Date().toISOString(),
        actorId: ctx.state?.user?.id ?? null,
      });

      ctx.body = sanitiseCharge(charge);
    } catch (error: any) {
      if (error?.message === 'Cobrança não encontrada.') {
        return ctx.notFound(error.message);
      }
      throw error;
    }
  },

  async handlePixWebhook(ctx) {
    if (!validateWebhookSecret(ctx, strapi)) {
      return;
    }

    const events = normalisePixWebhookEvents(ctx.request.body);
    if (!events.length) {
      return ctx.badRequest('Nenhum evento Pix recebido.');
    }

    const results: Array<Record<string, unknown>> = [];

    for (const event of events) {
      try {
        const charge = await processPixWebhookEvent(strapi, event);
        results.push({
          ok: true,
          id: charge?.id ?? null,
          status: charge?.status ?? event.status ?? null,
        });
      } catch (error: any) {
        const identifier =
          pickString(event, ['chargeId', 'cobrancaId', 'id']) ??
          pickString(event, ['txid', 'transactionId']);
        strapi.log.error('Erro ao processar webhook Pix', error);
        results.push({
          ok: false,
          id: identifier ?? null,
          message: error?.message ?? 'Falha ao aplicar atualização Pix.',
        });
      }
    }

    ctx.body = {
      processed: results.length,
      sucesso: results.filter((item) => item.ok).length,
      falhas: results.filter((item) => !item.ok).length,
      resultados: results,
    };
  },
}));

const getConfiguredPixKey = (strapi: any): { chave: string | null; tipo: string | null } => {
  const config = strapi.config.get('custom.pix', {});
  return {
    chave: config.chavePrincipal || process.env.PIX_PRIMARY_KEY || null,
    tipo: config.tipoChave || process.env.PIX_PRIMARY_KEY_TYPE || null,
  };
};

const configPixName = (strapi: any): string => {
  const config = strapi.config.get('custom.pix', {});
  return config.nomeRecebedor || process.env.PIX_RECEBEDOR_NOME || 'Academia da Comunicação';
};

const configPixCity = (strapi: any): string => {
  const config = strapi.config.get('custom.pix', {});
  return config.cidadeRecebedor || process.env.PIX_RECEBEDOR_CIDADE || 'Sao Paulo';
};

const PIX_TIMESTAMP_FIELDS: Record<PixStatus, string | undefined> = {
  pendente: undefined,
  confirmado: 'confirmadoEm',
  expirado: 'expiradoEm',
  cancelado: 'canceladoEm',
  reembolsado: 'canceladoEm',
};

const PIX_EVENT_STATUS_MAP: Record<string, PixStatus> = {
  'pix.cobranca.confirmada': 'confirmado',
  'pix.cobranca.paga': 'confirmado',
  'pix.cobranca.expirada': 'expirado',
  'pix.cobranca.cancelada': 'cancelado',
  'pix.cobranca.reembolsada': 'reembolsado',
  'pix.cobranca.criada': 'pendente',
};

const validateWebhookSecret = (ctx: any, strapi: any): boolean => {
  const configured =
    strapi.config.get('custom.pix', {}).webhookSecret || process.env.PIX_WEBHOOK_SECRET;
  if (!configured) {
    return true;
  }

  const headers = ctx.request?.headers ?? {};
  const provided =
    headers['x-pix-webhook-secret'] ||
    headers['x-webhook-secret'] ||
    headers['x-hub-signature'] ||
    ctx.query?.secret;

  if (!provided || String(provided) !== configured) {
    ctx.unauthorized('Assinatura do webhook Pix inválida.');
    return false;
  }

  return true;
};

const normalisePixWebhookEvents = (payload: any): Array<Record<string, any>> => {
  if (!payload) {
    return [];
  }

  if (Array.isArray(payload)) {
    return payload.filter((item) => !!item).map(toPlainObject);
  }

  if (Array.isArray(payload?.eventos)) {
    return payload.eventos.filter((item: unknown) => !!item).map(toPlainObject);
  }

  return [toPlainObject(payload)];
};

const toPlainObject = (value: any): Record<string, any> => {
  if (value && typeof value === 'object') {
    return Object.fromEntries(
      Object.entries(value).map(([key, entry]) => [key.toString(), entry]),
    );
  }
  return {};
};

const pickString = (payload: Record<string, any>, keys: string[]): string | null => {
  for (const key of keys) {
    const value = payload[key];
    if (typeof value === 'string' && value.trim().length > 0) {
      return value.trim();
    }
    if (value !== undefined && value !== null) {
      return String(value);
    }
  }
  return null;
};

const resolvePixStatus = (raw?: string | null): PixStatus | null => {
  if (!raw) {
    return null;
  }
  const normalised = raw.toLowerCase();
  if (PIX_STATUSES.includes(normalised as PixStatus)) {
    return normalised as PixStatus;
  }
  return PIX_EVENT_STATUS_MAP[normalised] ?? null;
};

const extractChargePayload = (event: Record<string, any>): Record<string, any> | null => {
  const direct = toMap(event.charge) ?? toMap(event.cobranca);
  if (direct) {
    return direct;
  }
  const data = toMap(event.data);
  if (data) {
    return toMap(data.charge) ?? toMap(data.cobranca) ?? data;
  }
  return null;
};

const toMap = (value: any): Record<string, any> | null => {
  if (value && typeof value === 'object') {
    return Object.fromEntries(
      Object.entries(value).map(([key, entry]) => [key.toString(), entry]),
    );
  }
  return null;
};

const prepareChargeFields = (payload: Record<string, any>): Record<string, unknown> => {
  const result: Record<string, unknown> = {};

  const copyFields = [
    'codigoCopiaCola',
    'codigoCopiaECola',
    'qrCodeBase64',
    'qrCodeUrl',
    'chavePix',
    'nomePagador',
    'documentoPagador',
    'metadata',
    'usuarioId',
  ];

  for (const key of copyFields) {
    if (payload[key] === undefined) {
      continue;
    }
    if (key === 'codigoCopiaECola') {
      if (result.codigoCopiaCola === undefined) {
        result.codigoCopiaCola = payload[key];
      }
      continue;
    }
    result[key] = payload[key];
  }

  if (payload.codigo && result.codigoCopiaCola === undefined) {
    result.codigoCopiaCola = payload.codigo;
  }

  if (payload.valor !== undefined) {
    const amount = Number.parseFloat(String(payload.valor));
    if (!Number.isNaN(amount)) {
      result.valor = amount;
    }
  }

  if (payload.moeda) {
    result.moeda = payload.moeda;
  }

  if (payload.txid) {
    result.txid = payload.txid;
  }

  if (payload.qrCode?.base64 && result.qrCodeBase64 === undefined) {
    result.qrCodeBase64 = payload.qrCode.base64;
  }

  if (payload.qrCode?.url && result.qrCodeUrl === undefined) {
    result.qrCodeUrl = payload.qrCode.url;
  }

  if (payload.expiraEm || payload.expiresAt) {
    result.expiraEm = payload.expiraEm ?? payload.expiresAt;
  }

  if (payload.plano) {
    result.plano = typeof payload.plano === 'object' ? payload.plano.id ?? payload.plano : payload.plano;
  }

  if (payload.assinatura) {
    result.assinatura =
      typeof payload.assinatura === 'object'
        ? payload.assinatura.id ?? payload.assinatura
        : payload.assinatura;
  }

  return result;
};

const processPixWebhookEvent = async (strapi: any, event: Record<string, any>) => {
  const chargePayload = extractChargePayload(event) ?? {};

  const identifier =
    pickString(event, ['chargeId', 'cobrancaId', 'id']) ??
    pickString(chargePayload, ['id', 'chargeId', 'cobrancaId']) ??
    pickString(event, ['txid', 'transactionId']) ??
    pickString(chargePayload, ['txid', 'transactionId']);

  if (!identifier) {
    throw new Error('Evento Pix sem identificador da cobrança.');
  }

  const resolvedStatus =
    resolvePixStatus(pickString(event, ['status', 'novoStatus', 'situacao', 'chargeStatus'])) ??
    resolvePixStatus(pickString(chargePayload, ['status', 'situacao'])) ??
    resolvePixStatus(event.event);

  const occurredAt =
    pickString(event, ['occurredAt', 'timestamp', 'eventAt']) ??
    pickString(chargePayload, ['updatedAt', 'atualizadoEm']) ??
    undefined;

  const fields = {
    ...prepareChargeFields(chargePayload),
    ...prepareChargeFields(event),
  };

  const charge = await applyPixChargeStatusUpdate(
    strapi,
    identifier,
    resolvedStatus ?? 'pendente',
    {
      occurredAt,
      fields,
    },
  );

  strapi.eventHub.emit('pix.charge.status', {
    event: event.event ?? 'pix.cobranca.atualizada',
    chargeId: charge.id,
    status: charge.status,
    txid: charge.txid,
    occurredAt: occurredAt ?? new Date().toISOString(),
  });

  return charge;
};

const applyPixChargeStatusUpdate = async (
  strapi: any,
  identifier: string | number,
  status: PixStatus,
  options: { occurredAt?: string; fields?: Record<string, unknown> } = {},
) => {
  const { occurredAt, fields = {} } = options;

  const numericId = Number.parseInt(String(identifier), 10);
  const isNumeric = !Number.isNaN(numericId) && String(numericId) === String(identifier);

  let charge: any | null = null;

  if (isNumeric) {
    charge = await strapi.entityService.findOne(COBRANCA_UID, numericId, {
      populate: { assinatura: true, plano: true },
    });
  }

  if (!charge) {
    const [byTxid] = await strapi.entityService.findMany(COBRANCA_UID, {
      filters: { txid: identifier },
      populate: { assinatura: true, plano: true },
      limit: 1,
    });
    charge = byTxid ?? null;
  }

  if (!charge) {
    throw new Error('Cobrança não encontrada.');
  }

  const appliedAt = occurredAt ?? new Date().toISOString();
  const data: Record<string, unknown> = { status };
  const timestampField = PIX_TIMESTAMP_FIELDS[status];
  if (timestampField) {
    data[timestampField] = appliedAt;
  }

  Object.assign(data, fields);

  const updated = await strapi.entityService.update(COBRANCA_UID, charge.id, {
    data,
    populate: { assinatura: true, plano: true },
  });

  if (updated?.assinatura?.id) {
    const subscriptionUpdate: Record<string, unknown> = {
      status: mapChargeStatusToSubscription(status),
    };

    if (status === 'pendente') {
      subscriptionUpdate.expiraEm = updated.expiraEm ?? charge.expiraEm;
    }

    if (status === 'confirmado') {
      subscriptionUpdate.expiraEm = updated.assinatura?.expiraEm ?? updated.expiraEm ?? charge.expiraEm;
      subscriptionUpdate.canceladaEm = null;
    }

    if (status === 'expirado') {
      subscriptionUpdate.expiraEm = updated.expiraEm ?? appliedAt;
    }

    if (status === 'cancelado' || status === 'reembolsado') {
      subscriptionUpdate.canceladaEm = appliedAt;
    }

    await strapi.entityService.update(ASSINATURA_UID, updated.assinatura.id, {
      data: subscriptionUpdate,
    });
  }

  return updated;
};
