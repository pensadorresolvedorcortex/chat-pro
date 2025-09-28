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
      data: {
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
    ctx.body = { data: sanitiseCharge(charge) };
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

    ctx.body = { data: sanitiseCharge(charge) };
  },

  async updatePixChargeStatus(ctx) {
    const { id } = ctx.params;
    const { status } = ctx.request.body ?? {};

    if (!id) {
      return ctx.badRequest('Informe o identificador da cobrança.');
    }
    if (!status || !PIX_STATUSES.includes(status)) {
      return ctx.badRequest('Status inválido.');
    }

    const timestamps: Record<PixStatus, string | undefined> = {
      pendente: undefined,
      confirmado: 'confirmadoEm',
      expirado: 'expiradoEm',
      cancelado: 'canceladoEm',
      reembolsado: 'canceladoEm',
    } as const;

    const now = new Date().toISOString();
    const data: Record<string, unknown> = { status };
    const timestampField = timestamps[status as PixStatus];
    if (timestampField) {
      data[timestampField] = now;
    }

    const charge = await strapi.entityService.update(COBRANCA_UID, id, {
      data,
      populate: { assinatura: true, plano: true },
    });

    if (charge?.assinatura?.id) {
      await strapi.entityService.update(ASSINATURA_UID, charge.assinatura.id, {
        data: {
          status: mapChargeStatusToSubscription(status as PixStatus),
          expiraEm: status === 'pendente' ? charge.expiraEm : charge.assinatura.expiraEm,
          canceladaEm: status === 'cancelado' || status === 'reembolsado' ? now : charge.assinatura.canceladaEm,
        },
      });
    }

    ctx.body = { data: sanitiseCharge(charge) };
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
