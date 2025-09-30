import fs from 'node:fs/promises';
import path from 'node:path';
import type { Core } from '@strapi/strapi';

const PLAN_UID = 'api::plano.plano';
const ASSINATURA_UID = 'api::assinatura.assinatura';
const COBRANCA_UID = 'api::cobranca-pix.cobranca-pix';
const QUESTAO_UID = 'api::questao.questao';
const SIMULADO_UID = 'api::simulado.simulado';
const DESAFIO_UID = 'api::desafio.desafio';
const META_UID = 'api::meta.meta';
const BIBLIOTECA_UID = 'api::biblioteca.biblioteca';
const CADERNO_UID = 'api::caderno.caderno';
const FILTRO_UID = 'api::filtro.filtro';
const CURSO_UID = 'api::curso.curso';
const LIVE_UID = 'api::live.live';
const MENTORIA_UID = 'api::mentoria.mentoria';
const NOTIFICACAO_UID = 'api::notificacao.notificacao';
const NPS_UID = 'api::nps.nps';
const SUPORTE_UID = 'api::suporte.suporte';

type PlanExample = {
  id: string;
  nome: string;
  descricao?: string;
  tipo: 'pago' | 'gratis_aluno';
  periodicidade?: 'mensal' | 'trimestral' | 'semestral' | 'anual';
  preco?: number;
  moeda?: string;
  pix?: {
    chave?: string;
    tipoChave?: 'email' | 'telefone' | 'cpf' | 'cnpj' | 'aleatoria';
    codigoCopiaCola?: string;
    qrCodeUrl?: string | null;
    qrCodeBase64?: string | null;
    expiraEm?: string | null;
    valor?: number | null;
    moeda?: string | null;
  } | null;
  beneficios?: unknown;
  statusAprovacao?: 'pendente' | 'aprovado' | 'rejeitado';
  aprovadoPor?: string | null;
  aprovadoEm?: string | null;
  ultimaSolicitacao?: string | null;
  destaque?: boolean;
  logsAprovacao?: Array<{
    status: 'pendente' | 'aprovado' | 'rejeitado';
    responsavel: string;
    observacao?: string | null;
    registradoEm: string;
  }>;
  ajustesPreco?: Array<{
    motivo?: string | null;
    atualizadoPor: string;
    atualizadoEm: string;
    precoAnterior?: number | string | null;
    precoAtual: number | string;
    moeda?: string | null;
  }>;
};

type AssinaturaExample = {
  id: string;
  usuarioId: string;
  planoId: string | null;
  metodoPagamento: 'pix' | 'gratis_aluno';
  pagador?: {
    nome?: string | null;
    documento?: string | null;
    email?: string | null;
  } | null;
  cobrancaPixId?: string | null;
  status: 'pendente' | 'ativa' | 'expirada' | 'cancelada';
  statusPagamento?: string;
  inicio?: string | null;
  fim?: string | null;
  canceladaEm?: string | null;
  pixInfo?: {
    ultimaCobrancaId?: string | null;
    codigoCopiaCola?: string | null;
    qrCodeUrl?: string | null;
    qrCodeBase64?: string | null;
    chavePix?: string | null;
    statusPagamento?: string | null;
    pagamentoConfirmadoEm?: string | null;
    atualizadoEm?: string | null;
    valor?: number | null;
    moeda?: string | null;
  } | null;
  createdAt?: string;
  updatedAt?: string;
};

type CobrancaExample = {
  id: string;
  txid: string;
  planoId: string | null;
  assinaturaId: string | null;
  usuarioId?: string | null;
  valor: number;
  moeda: string;
  status: 'pendente' | 'confirmado' | 'expirado' | 'cancelado' | 'reembolsado';
  codigoCopiaCola: string;
  chavePix?: string | null;
  qrCode?: {
    url?: string | null;
    base64?: string | null;
  } | null;
  expiraEm?: string | null;
  confirmadoEm?: string | null;
  expiradoEm?: string | null;
  canceladoEm?: string | null;
  pagador?: {
    nome?: string | null;
    documento?: string | null;
    email?: string | null;
    telefone?: string | null;
  } | null;
  metadata?: Record<string, unknown> | null;
  criadoEm?: string | null;
  atualizadoEm?: string | null;
};

type QuestaoExample = {
  id: string;
  enunciado: string;
  alternativas: Array<{
    letra: string;
    descricao: string;
    correta: boolean;
  }>;
  explicacao?: string | null;
  dificuldade?: string | null;
  ano?: number | string | null;
  disciplina: string;
  assuntos?: Array<string> | null;
  banca?: string | null;
  orgaosRelacionados?: Array<string> | null;
  estatisticas?: Record<string, unknown> | null;
  fonte?: string | null;
  tags?: Array<string> | null;
};

type SimuladoExample = {
  id: string;
  usuarioId?: string;
  titulo?: string;
  nome?: string;
  modalidade?: string;
  tipo?: string;
  configuracao?: Record<string, unknown> | null;
  estatisticas?: Record<string, unknown> | null;
  questaoIds?: Array<string> | null;
  questoes?: Array<string> | null;
  resultados?: Record<string, unknown> | null;
  status?: string | null;
  atualizadoEm?: string | null;
};

type DesafioExample = {
  id: string;
  titulo?: string;
  nome?: string;
  descricao?: string | null;
  organizadorId?: string | null;
  periodo?: { inicio?: string | null; fim?: string | null } | null;
  regras?: Record<string, unknown> | null;
  participantes?: Array<Record<string, unknown>> | null;
  status?: string | null;
};

type MetaExample = {
  id: string;
  usuarioId: string;
  titulo?: string | null;
  descricao?: string | null;
  tipo: string;
  alvo?: number | string | null;
  progressoAtual?: number | string | null;
  periodo?: string | null;
  ultimoReset?: string | null;
};

type BibliotecaExample = {
  id: string;
  titulo: string;
  descricao?: string | null;
  formato: 'pdf' | 'video' | 'audio' | 'checklist' | 'modelo';
  arquivoUrl: string;
  linkExterno?: string | null;
  thumbnailUrl?: string | null;
  duracaoMinutos?: number | string | null;
  disciplinaIds?: string[];
  assuntoIds?: string[];
  tags?: string[];
  destaque?: boolean;
  publicadoEm?: string | null;
  status?: 'rascunho' | 'publicado' | 'arquivado';
  autor?: { id?: string | null; nome?: string | null } | null;
  autorId?: string | null;
  autorNome?: string | null;
  downloads?: number | string | null;
  visualizacoes?: number | string | null;
};

type CadernoExample = {
  id: string;
  titulo: string;
  descricao?: string | null;
  usuarioId: string;
  progresso?: number | string | null;
  questoes?: Array<{
    id: string;
    status?: string | null;
    correta?: boolean | string | null;
  }> | null;
  atualizadoEm?: string | null;
};

type FiltroExample = {
  id: string;
  nome: string;
  usuarioId: string;
  criterios?: Record<string, unknown> | null;
  atualizadoEm?: string | null;
};

type CursoExample = {
  id: string;
  titulo: string;
  descricao?: string | null;
  nivel?: string | null;
  tags?: Array<string> | null;
  instrutores?: Array<string> | null;
  rating?: number | string | null;
  cargaHorariaHoras?: number | string | null;
  alunosMatriculados?: number | string | null;
  aulas?: Array<Record<string, unknown>> | null;
};

type LiveExample = {
  id: string;
  titulo: string;
  descricao?: string | null;
  instrutor?: string | null;
  link?: string | null;
  capacidade?: number | string | null;
  inscritos?: number | string | null;
  inicio?: string | null;
  fim?: string | null;
  materialApoio?: Array<string> | null;
};

type MentoriaExample = {
  id: string;
  titulo: string;
  descricao?: string | null;
  mentorId: string;
  avaliacaoMedia?: number | string | null;
  alunosAtendidos?: number | string | null;
  slots?: Array<Record<string, unknown>> | null;
};

type NotificacaoExample = {
  id: string;
  titulo: string;
  mensagem: string;
  tipo?: string | null;
  segmento?: Record<string, unknown> | null;
  agendadaPara?: string | null;
};

type NpsExample = {
  id: string;
  titulo: string;
  status?: string | null;
  kpis?: Record<string, unknown> | null;
  perguntas?: Array<Record<string, unknown>> | null;
  respostas?: Array<Record<string, unknown>> | null;
};

type SuporteExample = {
  id: string;
  assunto: string;
  categoria?: string | null;
  usuarioId: string;
  status?: string | null;
  mensagens?: Array<Record<string, unknown>> | null;
};

const readJson = async <T>(baseDir: string, file: string): Promise<T | null> => {
  try {
    const fullPath = path.resolve(baseDir, file);
    const content = await fs.readFile(fullPath, 'utf-8');
    return JSON.parse(content) as T;
  } catch (error) {
    return null;
  }
};

const normaliseDecimal = (value: unknown): number | null => {
  if (value === null || value === undefined) {
    return null;
  }
  if (typeof value === 'number' && Number.isFinite(value)) {
    return value;
  }
  if (typeof value === 'string' && value.trim().length > 0) {
    const parsed = Number.parseFloat(value);
    return Number.isNaN(parsed) ? null : parsed;
  }
  return null;
};

const normaliseInteger = (value: unknown): number | null => {
  const decimal = normaliseDecimal(value);
  if (decimal === null) {
    return null;
  }
  if (!Number.isFinite(decimal)) {
    return null;
  }
  return Math.trunc(decimal);
};

const ensurePlans = async (strapi: Core.Strapi, examplesDir: string) => {
  const examples = await readJson<PlanExample[] | null>(examplesDir, 'planos.json');
  if (!examples || !Array.isArray(examples) || examples.length === 0) {
    return new Map<string, number>();
  }

  const result = new Map<string, number>();

  for (const plan of examples) {
    if (!plan?.id) {
      continue;
    }

    const existing = await strapi.entityService.findMany(PLAN_UID, {
      filters: { slug: plan.id },
      publicationState: 'preview',
    });

    if (Array.isArray(existing) && existing.length > 0) {
      result.set(plan.id, existing[0].id as number);
      continue;
    }

    const pix = plan.pix ?? {};
    const data: Record<string, unknown> = {
      nome: plan.nome,
      slug: plan.id,
      descricao: plan.descricao ?? null,
      tipo: plan.tipo,
      periodicidade: plan.periodicidade ?? 'mensal',
      preco: normaliseDecimal(plan.preco) ?? 0,
      moeda: plan.moeda ?? pix.moeda ?? 'BRL',
      chavePix: pix.chave ?? null,
      tipoChavePix: pix.tipoChave ?? null,
      codigoCopiaCola: pix.codigoCopiaCola ?? null,
      qrCodeUrl: pix.qrCodeUrl ?? null,
      qrCodeBase64: pix.qrCodeBase64 ?? null,
      pixExpiraEm: pix.expiraEm ?? null,
      beneficios: plan.beneficios ?? [],
      statusAprovacao: plan.statusAprovacao ?? 'pendente',
      aprovadoPor: plan.aprovadoPor ?? null,
      aprovadoEm: plan.aprovadoEm ?? null,
      ultimaSolicitacao: plan.ultimaSolicitacao ?? null,
      destaque: Boolean(plan.destaque),
    };

    if (Array.isArray(plan.logsAprovacao) && plan.logsAprovacao.length > 0) {
      data.logsAprovacao = plan.logsAprovacao.map((log) => ({
        status: log.status,
        responsavel: log.responsavel,
        observacao: log.observacao ?? null,
        registradoEm: log.registradoEm,
      }));
    }

    if (Array.isArray(plan.ajustesPreco) && plan.ajustesPreco.length > 0) {
      data.ajustesPreco = plan.ajustesPreco.map((entry) => ({
        motivo: entry.motivo ?? null,
        atualizadoPor: entry.atualizadoPor,
        atualizadoEm: entry.atualizadoEm,
        precoAnterior: normaliseDecimal(entry.precoAnterior),
        precoAtual: normaliseDecimal(entry.precoAtual) ?? 0,
        moeda: entry.moeda ?? plan.moeda ?? pix.moeda ?? 'BRL',
      }));
    }

    const created = await strapi.entityService.create(PLAN_UID, {
      data,
    });

    result.set(plan.id, created.id as number);
  }

  return result;
};

const ensureQuestoes = async (strapi: Core.Strapi, examplesDir: string) => {
  const examples = await readJson<QuestaoExample[] | null>(examplesDir, 'questoes.json');
  if (!examples || !Array.isArray(examples) || examples.length === 0) {
    return;
  }

  for (const questao of examples) {
    if (!questao?.id) {
      continue;
    }

    const existing = await strapi.entityService.findMany(QUESTAO_UID, {
      filters: { slug: questao.id },
      publicationState: 'preview',
    });

    if (Array.isArray(existing) && existing.length > 0) {
      continue;
    }

    const data: Record<string, unknown> = {
      slug: questao.id,
      enunciado: questao.enunciado,
      alternativas: questao.alternativas ?? [],
      explicacao: questao.explicacao ?? null,
      dificuldade: questao.dificuldade ?? 'intermediaria',
      ano: questao.ano ?? null,
      disciplina: questao.disciplina,
      assuntos: questao.assuntos ?? [],
      banca: questao.banca ?? null,
      orgaosRelacionados: questao.orgaosRelacionados ?? [],
      estatisticas: questao.estatisticas ?? null,
      fonte: questao.fonte ?? null,
      tags: questao.tags ?? [],
      publishedAt: new Date().toISOString(),
    };

    await strapi.entityService.create(QUESTAO_UID, { data });
  }
};

const ensureSimulados = async (strapi: Core.Strapi, examplesDir: string) => {
  const examples = await readJson<SimuladoExample[] | null>(examplesDir, 'simulados.json');
  if (!examples || !Array.isArray(examples) || examples.length === 0) {
    return;
  }

  for (const simulado of examples) {
    if (!simulado?.id) {
      continue;
    }

    const existing = await strapi.entityService.findMany(SIMULADO_UID, {
      filters: { slug: simulado.id },
      publicationState: 'preview',
      limit: 1,
    });

    if (Array.isArray(existing) && existing.length > 0) {
      continue;
    }

    const data: Record<string, unknown> = {
      slug: simulado.id,
      titulo: simulado.titulo ?? simulado.nome ?? simulado.id,
      usuarioId: simulado.usuarioId ?? null,
      modalidade: simulado.modalidade ?? simulado.tipo ?? null,
      configuracao: simulado.configuracao ?? null,
      estatisticas: simulado.estatisticas ?? null,
      questaoIds: simulado.questaoIds ?? simulado.questoes ?? [],
      resultados: simulado.resultados ?? null,
      status: simulado.status ?? null,
      atualizadoEm: simulado.atualizadoEm ?? null,
      publishedAt: new Date().toISOString(),
    };

    await strapi.entityService.create(SIMULADO_UID, { data });
  }
};

const ensureDesafios = async (strapi: Core.Strapi, examplesDir: string) => {
  const examples = await readJson<DesafioExample[] | null>(examplesDir, 'desafios.json');
  if (!examples || !Array.isArray(examples) || examples.length === 0) {
    return;
  }

  for (const desafio of examples) {
    if (!desafio?.id) {
      continue;
    }

    const existing = await strapi.entityService.findMany(DESAFIO_UID, {
      filters: { slug: desafio.id },
      publicationState: 'preview',
      limit: 1,
    });

    if (Array.isArray(existing) && existing.length > 0) {
      continue;
    }

    const data: Record<string, unknown> = {
      slug: desafio.id,
      titulo: desafio.titulo ?? desafio.nome ?? desafio.id,
      descricao: desafio.descricao ?? null,
      organizadorId: desafio.organizadorId ?? null,
      inicio: desafio.periodo?.inicio ?? null,
      fim: desafio.periodo?.fim ?? null,
      regras: desafio.regras ?? null,
      participantes: desafio.participantes ?? [],
      status: desafio.status ?? null,
      publishedAt: new Date().toISOString(),
    };

    await strapi.entityService.create(DESAFIO_UID, { data });
  }
};

const ensureMetas = async (strapi: Core.Strapi, examplesDir: string) => {
  const examples = await readJson<MetaExample[] | null>(examplesDir, 'metas.json');
  if (!examples || !Array.isArray(examples) || examples.length === 0) {
    return;
  }

  for (const meta of examples) {
    if (!meta?.id) {
      continue;
    }

    const existing = await strapi.entityService.findMany(META_UID, {
      filters: { slug: meta.id },
      publicationState: 'preview',
      limit: 1,
    });

    if (Array.isArray(existing) && existing.length > 0) {
      continue;
    }

    const data: Record<string, unknown> = {
      slug: meta.id,
      usuarioId: meta.usuarioId,
      titulo: meta.titulo ?? meta.descricao ?? meta.id,
      descricao: meta.descricao ?? null,
      tipo: meta.tipo,
      alvo: normaliseDecimal(meta.alvo) ?? null,
      progressoAtual: normaliseDecimal(meta.progressoAtual) ?? null,
      periodo: meta.periodo ?? null,
      ultimoReset: meta.ultimoReset ?? null,
      publishedAt: new Date().toISOString(),
    };

    await strapi.entityService.create(META_UID, { data });
  }
};

const ensureBiblioteca = async (strapi: Core.Strapi, examplesDir: string) => {
  const examples = await readJson<BibliotecaExample[] | null>(examplesDir, 'biblioteca.json');
  if (!examples || !Array.isArray(examples) || examples.length === 0) {
    return;
  }

  for (const material of examples) {
    if (!material?.id) {
      continue;
    }

    const existing = await strapi.entityService.findMany(BIBLIOTECA_UID, {
      filters: { slug: material.id },
      publicationState: 'preview',
      limit: 1,
    });

    if (Array.isArray(existing) && existing.length > 0) {
      continue;
    }

    const data: Record<string, unknown> = {
      slug: material.id,
      titulo: material.titulo,
      descricao: material.descricao ?? null,
      formato: material.formato,
      arquivoUrl: material.arquivoUrl,
      linkExterno: material.linkExterno ?? null,
      thumbnailUrl: material.thumbnailUrl ?? null,
      duracaoMinutos: material.duracaoMinutos ?? null,
      disciplinaIds: Array.isArray(material.disciplinaIds) ? material.disciplinaIds : [],
      assuntoIds: Array.isArray(material.assuntoIds) ? material.assuntoIds : [],
      tags: Array.isArray(material.tags) ? material.tags : [],
      destaque: Boolean(material.destaque),
      publicadoEm: material.publicadoEm ?? null,
      status: material.status ?? 'publicado',
      autorId: material.autorId ?? material.autor?.id ?? null,
      autorNome: material.autorNome ?? material.autor?.nome ?? null,
      downloads: normaliseInteger(material.downloads ?? 0) ?? 0,
      visualizacoes: normaliseInteger(material.visualizacoes ?? 0) ?? 0,
      publishedAt: material.publicadoEm ?? new Date().toISOString(),
    };

    await strapi.entityService.create(BIBLIOTECA_UID, { data });
  }
};

const ensureCadernos = async (strapi: Core.Strapi, examplesDir: string) => {
  const examples = await readJson<CadernoExample[] | null>(examplesDir, 'cadernos.json');
  if (!examples || !Array.isArray(examples) || examples.length === 0) {
    return;
  }

  for (const caderno of examples) {
    if (!caderno?.id) {
      continue;
    }

    const existing = await strapi.entityService.findMany(CADERNO_UID, {
      filters: { slug: caderno.id },
      publicationState: 'preview',
      limit: 1,
    });

    if (Array.isArray(existing) && existing.length > 0) {
      continue;
    }

    const questoes = Array.isArray(caderno.questoes)
      ? caderno.questoes
          .map((questao) => {
            if (!questao?.id) {
              return null;
            }

            let correta: boolean | null = null;
            if (typeof questao.correta === 'boolean') {
              correta = questao.correta;
            } else if (typeof questao.correta === 'string') {
              const value = questao.correta.toLowerCase();
              if (value === 'true') {
                correta = true;
              } else if (value === 'false') {
                correta = false;
              }
            }

            return {
              id: questao.id,
              status: questao.status ?? null,
              correta,
            };
          })
          .filter((questao): questao is Record<string, unknown> => questao !== null)
      : [];

    const data: Record<string, unknown> = {
      slug: caderno.id,
      titulo: caderno.titulo,
      descricao: caderno.descricao ?? null,
      usuarioId: caderno.usuarioId,
      progresso: normaliseDecimal(caderno.progresso) ?? 0,
      questoes,
      atualizadoEm: caderno.atualizadoEm ?? null,
      publishedAt: new Date().toISOString(),
    };

    await strapi.entityService.create(CADERNO_UID, { data });
  }
};

const ensureFiltros = async (strapi: Core.Strapi, examplesDir: string) => {
  const examples = await readJson<FiltroExample[] | null>(examplesDir, 'filtros.json');
  if (!examples || !Array.isArray(examples) || examples.length === 0) {
    return;
  }

  for (const filtro of examples) {
    if (!filtro?.id) {
      continue;
    }

    const existing = await strapi.entityService.findMany(FILTRO_UID, {
      filters: { slug: filtro.id },
      publicationState: 'preview',
      limit: 1,
    });

    if (Array.isArray(existing) && existing.length > 0) {
      continue;
    }

    const data: Record<string, unknown> = {
      slug: filtro.id,
      nome: filtro.nome,
      usuarioId: filtro.usuarioId,
      criterios: filtro.criterios ?? {},
      atualizadoEm: filtro.atualizadoEm ?? null,
      publishedAt: new Date().toISOString(),
    };

    await strapi.entityService.create(FILTRO_UID, { data });
  }
};

const ensureCursos = async (strapi: Core.Strapi, examplesDir: string) => {
  const examples = await readJson<CursoExample[] | null>(examplesDir, 'cursos.json');
  if (!examples || !Array.isArray(examples) || examples.length === 0) {
    return;
  }

  for (const curso of examples) {
    if (!curso?.id) {
      continue;
    }

    const existing = await strapi.entityService.findMany(CURSO_UID, {
      filters: { slug: curso.id },
      publicationState: 'preview',
      limit: 1,
    });

    if (Array.isArray(existing) && existing.length > 0) {
      continue;
    }

    const data: Record<string, unknown> = {
      slug: curso.id,
      titulo: curso.titulo,
      descricao: curso.descricao ?? null,
      nivel: curso.nivel ?? null,
      tags: Array.isArray(curso.tags) ? curso.tags : [],
      instrutores: Array.isArray(curso.instrutores) ? curso.instrutores : [],
      rating: normaliseDecimal(curso.rating) ?? null,
      cargaHorariaHoras: normaliseInteger(curso.cargaHorariaHoras) ?? null,
      alunosMatriculados: normaliseInteger(curso.alunosMatriculados) ?? null,
      aulas: Array.isArray(curso.aulas) ? curso.aulas : [],
      publishedAt: new Date().toISOString(),
    };

    await strapi.entityService.create(CURSO_UID, { data });
  }
};

const ensureLives = async (strapi: Core.Strapi, examplesDir: string) => {
  const examples = await readJson<LiveExample[] | null>(examplesDir, 'lives.json');
  if (!examples || !Array.isArray(examples) || examples.length === 0) {
    return;
  }

  for (const live of examples) {
    if (!live?.id) {
      continue;
    }

    const existing = await strapi.entityService.findMany(LIVE_UID, {
      filters: { slug: live.id },
      publicationState: 'preview',
      limit: 1,
    });

    if (Array.isArray(existing) && existing.length > 0) {
      continue;
    }

    const data: Record<string, unknown> = {
      slug: live.id,
      titulo: live.titulo,
      descricao: live.descricao ?? null,
      instrutor: live.instrutor ?? null,
      link: live.link ?? null,
      capacidade: normaliseInteger(live.capacidade) ?? null,
      inscritos: normaliseInteger(live.inscritos) ?? null,
      inicio: live.inicio ?? null,
      fim: live.fim ?? null,
      materialApoio: Array.isArray(live.materialApoio) ? live.materialApoio : [],
      publishedAt: new Date().toISOString(),
    };

    await strapi.entityService.create(LIVE_UID, { data });
  }
};

const ensureMentorias = async (strapi: Core.Strapi, examplesDir: string) => {
  const examples = await readJson<MentoriaExample[] | null>(examplesDir, 'mentorias.json');
  if (!examples || !Array.isArray(examples) || examples.length === 0) {
    return;
  }

  for (const mentoria of examples) {
    if (!mentoria?.id) {
      continue;
    }

    const existing = await strapi.entityService.findMany(MENTORIA_UID, {
      filters: { slug: mentoria.id },
      publicationState: 'preview',
      limit: 1,
    });

    if (Array.isArray(existing) && existing.length > 0) {
      continue;
    }

    const slots = Array.isArray(mentoria.slots)
      ? mentoria.slots
          .map((slot) => {
            if (!slot) {
              return null;
            }
            const inicio = typeof slot.inicio === 'string' ? slot.inicio : null;
            const fim = typeof slot.fim === 'string' ? slot.fim : null;
            if (!inicio || !fim) {
              return null;
            }
            return {
              inicio,
              fim,
              status: typeof slot.status === 'string' ? slot.status : null,
              reservadoPor: typeof slot.reservadoPor === 'string' ? slot.reservadoPor : null,
            };
          })
          .filter((slot): slot is Record<string, unknown> => slot !== null)
      : [];

    const data: Record<string, unknown> = {
      slug: mentoria.id,
      titulo: mentoria.titulo,
      descricao: mentoria.descricao ?? null,
      mentorId: mentoria.mentorId,
      avaliacaoMedia: normaliseDecimal(mentoria.avaliacaoMedia) ?? null,
      alunosAtendidos: normaliseInteger(mentoria.alunosAtendidos) ?? null,
      slots,
      publishedAt: new Date().toISOString(),
    };

    await strapi.entityService.create(MENTORIA_UID, { data });
  }
};

const ensureNotificacoes = async (strapi: Core.Strapi, examplesDir: string) => {
  const examples = await readJson<NotificacaoExample[] | null>(examplesDir, 'notificacoes.json');
  if (!examples || !Array.isArray(examples) || examples.length === 0) {
    return;
  }

  for (const notificacao of examples) {
    if (!notificacao?.id) {
      continue;
    }

    const existing = await strapi.entityService.findMany(NOTIFICACAO_UID, {
      filters: { slug: notificacao.id },
      publicationState: 'preview',
      limit: 1,
    });

    if (Array.isArray(existing) && existing.length > 0) {
      continue;
    }

    const data: Record<string, unknown> = {
      slug: notificacao.id,
      titulo: notificacao.titulo,
      mensagem: notificacao.mensagem,
      tipo: notificacao.tipo ?? null,
      segmento: notificacao.segmento ?? {},
      agendadaPara: notificacao.agendadaPara ?? null,
      publishedAt: new Date().toISOString(),
    };

    await strapi.entityService.create(NOTIFICACAO_UID, { data });
  }
};

const ensureNpsPesquisas = async (strapi: Core.Strapi, examplesDir: string) => {
  const payload = await readJson<{ pesquisas?: NpsExample[] } | null>(examplesDir, 'nps.json');
  const pesquisas = payload?.pesquisas ?? [];

  for (const pesquisa of pesquisas) {
    if (!pesquisa?.id) {
      continue;
    }

    const existing = await strapi.entityService.findMany(NPS_UID, {
      filters: { slug: pesquisa.id },
      publicationState: 'preview',
      limit: 1,
    });

    if (Array.isArray(existing) && existing.length > 0) {
      continue;
    }

    const data: Record<string, unknown> = {
      slug: pesquisa.id,
      titulo: pesquisa.titulo,
      status: pesquisa.status ?? null,
      kpis: pesquisa.kpis ?? {},
      perguntas: Array.isArray(pesquisa.perguntas) ? pesquisa.perguntas : [],
      respostas: Array.isArray(pesquisa.respostas) ? pesquisa.respostas : [],
      publishedAt: new Date().toISOString(),
    };

    await strapi.entityService.create(NPS_UID, { data });
  }
};

const ensureTicketsSuporte = async (strapi: Core.Strapi, examplesDir: string) => {
  const examples = await readJson<SuporteExample[] | null>(examplesDir, 'suporte.json');
  if (!examples || !Array.isArray(examples) || examples.length === 0) {
    return;
  }

  for (const ticket of examples) {
    if (!ticket?.id) {
      continue;
    }

    const existing = await strapi.entityService.findMany(SUPORTE_UID, {
      filters: { slug: ticket.id },
      publicationState: 'preview',
      limit: 1,
    });

    if (Array.isArray(existing) && existing.length > 0) {
      continue;
    }

    const mensagens = Array.isArray(ticket.mensagens)
      ? ticket.mensagens
          .map((mensagem) => {
            if (!mensagem) {
              return null;
            }
            const autor = typeof mensagem.autor === 'string' ? mensagem.autor : null;
            const conteudo = typeof mensagem.conteudo === 'string' ? mensagem.conteudo : null;
            const enviadoEm = typeof mensagem.enviadoEm === 'string' ? mensagem.enviadoEm : null;
            if (!autor || !conteudo) {
              return null;
            }
            return {
              autor,
              conteudo,
              enviadoEm,
            };
          })
          .filter((mensagem): mensagem is Record<string, unknown> => mensagem !== null)
      : [];

    const data: Record<string, unknown> = {
      slug: ticket.id,
      assunto: ticket.assunto,
      categoria: ticket.categoria ?? null,
      usuarioId: ticket.usuarioId,
      status: ticket.status ?? null,
      mensagens,
      publishedAt: new Date().toISOString(),
    };

    await strapi.entityService.create(SUPORTE_UID, { data });
  }
};

const ensureAssinaturas = async (
  strapi: Core.Strapi,
  examplesDir: string,
  planMap: Map<string, number>,
) => {
  const payload = await readJson<{ data?: AssinaturaExample[] } | null>(examplesDir, 'assinaturas_pix.json');
  const exemplos = payload?.data ?? [];

  const assinaturasMap = new Map<string, number>();

  for (const assinatura of exemplos) {
    if (!assinatura?.id) {
      continue;
    }

    const filters: Record<string, unknown> = {
      usuarioId: assinatura.usuarioId,
      metodoPagamento: assinatura.metodoPagamento,
    };

    if (assinatura.planoId && planMap.has(assinatura.planoId)) {
      filters.plano = planMap.get(assinatura.planoId);
    }

    const existing = await strapi.entityService.findMany(ASSINATURA_UID, {
      filters,
    });

    if (Array.isArray(existing) && existing.length > 0) {
      assinaturasMap.set(assinatura.id, existing[0].id as number);
      continue;
    }

    const data: Record<string, unknown> = {
      usuarioId: assinatura.usuarioId,
      metodoPagamento: assinatura.metodoPagamento,
      status: assinatura.status,
      iniciadaEm: assinatura.inicio ?? null,
      expiraEm: assinatura.fim ?? null,
      canceladaEm: assinatura.canceladaEm ?? null,
      pixChaveUsada: assinatura.pixInfo?.chavePix ?? null,
      pixNomePagador: assinatura.pagador?.nome ?? null,
      pixDocumentoPagador: assinatura.pagador?.documento ?? null,
      metadata: {
        exampleId: assinatura.id,
        pagador: assinatura.pagador ?? null,
        pixInfo: assinatura.pixInfo ?? null,
      },
    };

    if (assinatura.planoId && planMap.has(assinatura.planoId)) {
      data.plano = planMap.get(assinatura.planoId);
    }

    const created = await strapi.entityService.create(ASSINATURA_UID, { data });
    assinaturasMap.set(assinatura.id, created.id as number);
  }

  return assinaturasMap;
};

const ensureCobrancas = async (
  strapi: Core.Strapi,
  examplesDir: string,
  planMap: Map<string, number>,
  assinaturaMap: Map<string, number>,
) => {
  const payload = await readJson<{ cobrancas?: CobrancaExample[] } | null>(examplesDir, 'cobrancas_pix.json');
  const cobrancas = payload?.cobrancas ?? [];

  for (const cobranca of cobrancas) {
    if (!cobranca?.txid) {
      continue;
    }

    const existing = await strapi.entityService.findMany(COBRANCA_UID, {
      filters: { txid: cobranca.txid },
    });

    if (Array.isArray(existing) && existing.length > 0) {
      continue;
    }

    const data: Record<string, unknown> = {
      txid: cobranca.txid,
      status: cobranca.status,
      valor: normaliseDecimal(cobranca.valor) ?? 0,
      moeda: cobranca.moeda ?? 'BRL',
      codigoCopiaCola: cobranca.codigoCopiaCola,
      chavePix: cobranca.chavePix ?? null,
      qrCodeUrl: cobranca.qrCode?.url ?? null,
      qrCodeBase64: cobranca.qrCode?.base64 ?? null,
      expiraEm: cobranca.expiraEm ?? null,
      confirmadoEm: cobranca.confirmadoEm ?? null,
      expiradoEm: cobranca.expiradoEm ?? null,
      canceladoEm: cobranca.canceladoEm ?? null,
      usuarioId: cobranca.usuarioId ?? null,
      nomePagador: cobranca.pagador?.nome ?? null,
      documentoPagador: cobranca.pagador?.documento ?? null,
      metadata: {
        exampleId: cobranca.id,
        origem: cobranca.metadata?.origem ?? null,
        pagador: cobranca.pagador ?? null,
        payload: cobranca,
      },
    };

    if (cobranca.planoId && planMap.has(cobranca.planoId)) {
      data.plano = planMap.get(cobranca.planoId);
    }

    if (cobranca.assinaturaId && assinaturaMap.has(cobranca.assinaturaId)) {
      data.assinatura = assinaturaMap.get(cobranca.assinaturaId);
    }

    await strapi.entityService.create(COBRANCA_UID, { data });
  }
};

export const seedExamples = async (strapi: Core.Strapi) => {
  const examplesDir = path.resolve(strapi.dirs.app.root, '..', 'docs', 'examples');

  await ensureQuestoes(strapi, examplesDir);
  await ensureSimulados(strapi, examplesDir);
  await ensureDesafios(strapi, examplesDir);
  await ensureMetas(strapi, examplesDir);
  await ensureBiblioteca(strapi, examplesDir);
  await ensureCadernos(strapi, examplesDir);
  await ensureFiltros(strapi, examplesDir);
  await ensureCursos(strapi, examplesDir);
  await ensureLives(strapi, examplesDir);
  await ensureMentorias(strapi, examplesDir);
  await ensureNotificacoes(strapi, examplesDir);
  await ensureNpsPesquisas(strapi, examplesDir);
  await ensureTicketsSuporte(strapi, examplesDir);
  const planMap = await ensurePlans(strapi, examplesDir);
  const assinaturaMap = await ensureAssinaturas(strapi, examplesDir, planMap);
  await ensureCobrancas(strapi, examplesDir, planMap, assinaturaMap);
};
