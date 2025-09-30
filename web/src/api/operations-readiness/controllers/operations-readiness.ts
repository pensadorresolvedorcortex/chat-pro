import path from 'path';
import fs from 'fs';
import { factories } from '@strapi/strapi';
import type { Strapi } from '@strapi/strapi';

type RawComponent = {
  key: string;
  label?: string;
  percentage?: number;
  baseline?: number;
  weight?: number;
  notes?: string[];
  nextSteps?: string[];
};

type RawSnapshot = {
  timestamp?: string;
  overall?: {
    percentage?: number;
    baseline?: number;
    weights?: Record<string, number>;
    notes?: string[];
  };
  components?: RawComponent[];
  sources?: Array<{ type?: string; value?: string; description?: string }>;
  milestones?: RawMilestone[];
  alerts?: RawAlert[];
  incidents?: RawIncident[];
  maintenance?: RawMaintenanceWindow[];
  maintenanceWindows?: RawMaintenanceWindow[];
  onCall?: RawOnCall[];
  automations?: RawAutomation[];
  slos?: RawSlo[];
  sloBreaches?: RawSloBreach[];
};

type CheckStatus = 'done' | 'todo';

type ReadinessCheck = {
  key: string;
  label: string;
  status: CheckStatus;
  details?: string;
};

type EnrichedComponent = RawComponent & {
  baselinePercentage: number;
  computedPercentage: number;
  percentage: number;
  weight: number;
  checks: ReadinessCheck[];
  pending: string[];
};

type MilestoneStatus = 'pending' | 'in_progress' | 'done';

type RawMilestone = {
  id: string;
  title?: string;
  description?: string;
  owner?: string;
  component?: string;
  status?: MilestoneStatus;
  completion?: number;
  targetDate?: string;
  completedAt?: string;
  blockers?: string[];
};

type EnrichedMilestone = RawMilestone & {
  status: MilestoneStatus;
  completion: number;
  overdue: boolean;
  blockers: string[];
};

type AlertLevel = 'info' | 'warning' | 'critical';

type RawAlert = {
  id: string;
  level?: AlertLevel;
  message?: string;
  details?: string;
  component?: string;
  actionLabel?: string;
  actionUrl?: string;
  active?: boolean;
};

type EnrichedAlert = RawAlert & {
  level: AlertLevel;
  message: string;
  active: boolean;
  details?: string;
};

type IncidentStatus = 'investigating' | 'monitoring' | 'resolved';
type IncidentImpact = 'none' | 'minor' | 'major' | 'critical';

type RawIncident = {
  id: string;
  title?: string;
  status?: IncidentStatus;
  impact?: IncidentImpact;
  component?: string;
  summary?: string;
  details?: string;
  startedAt?: string;
  resolvedAt?: string;
  updatedAt?: string;
  durationMinutes?: number;
  actions?: string[];
};

type EnrichedIncident = RawIncident & {
  status: IncidentStatus;
  impact: IncidentImpact;
  summary?: string;
  startedAt?: string;
  resolvedAt?: string;
  updatedAt?: string;
  durationMinutes?: number;
  active: boolean;
  actions: string[];
};

type MaintenanceStatus = 'scheduled' | 'in_progress' | 'completed';
type MaintenanceImpact = 'none' | 'minor' | 'major' | 'critical';

type RawMaintenanceWindow = {
  id: string;
  title?: string;
  status?: MaintenanceStatus;
  impact?: MaintenanceImpact;
  description?: string;
  notes?: string;
  windowStart?: string;
  windowEnd?: string;
  systems?: string[];
  durationMinutes?: number;
};

type EnrichedMaintenanceWindow = RawMaintenanceWindow & {
  status: MaintenanceStatus;
  impact: MaintenanceImpact;
  windowStart?: string;
  windowEnd?: string;
  durationMinutes?: number;
  isActive: boolean;
  isUpcoming: boolean;
  systems: string[];
};

type RawOnCall = {
  id: string;
  name?: string;
  role?: string;
  contact?: string;
  primary?: boolean;
  status?: 'active' | 'standby' | 'offline';
  startedAt?: string;
  endsAt?: string;
  shiftDurationMinutes?: number;
  escalationPolicy?: string;
};

type EnrichedOnCall = RawOnCall & {
  name: string;
  role: string;
  contact: string;
  status: 'active' | 'standby' | 'offline';
  primary: boolean;
  startedAt?: string;
  endsAt?: string;
  shiftDurationMinutes?: number;
  escalationPolicy?: string;
};

type AutomationStatus = 'operational' | 'in_progress' | 'degraded' | 'blocked';

type RawAutomation = {
  id: string;
  title?: string;
  description?: string;
  status?: string;
  owners?: string[] | string;
  lastRunAt?: string;
  nextRunAt?: string;
  successRate?: number;
  coverage?: number;
  signals?: string[];
  playbooks?: string[];
};

type EnrichedAutomation = RawAutomation & {
  title: string;
  status: AutomationStatus;
  owners: string[];
  lastRunAt?: string;
  nextRunAt?: string;
  successRate?: number;
  coverage?: number;
  signals: string[];
  playbooks: string[];
};

type RawSlo = {
  id: string;
  service?: string;
  indicator?: string;
  target?: number;
  current?: number;
  direction?: 'above' | 'below';
  status?: 'healthy' | 'at_risk' | 'at-risk' | 'breaching';
  windowDays?: number;
  breaches?: number;
  notes?: string[];
};

type EnrichedSlo = RawSlo & {
  service: string;
  indicator: string;
  target: number;
  current: number;
  direction: 'above' | 'below';
  status: 'healthy' | 'at_risk' | 'breaching';
  windowDays?: number;
  breaches?: number;
  notes: string[];
};

type SloBreachStatus = 'open' | 'acknowledged' | 'resolved';
type SloBreachImpact = 'none' | 'minor' | 'major' | 'critical';

type RawSloBreach = {
  id?: string;
  sloId?: string;
  service?: string;
  indicator?: string;
  status?: SloBreachStatus | 'ack' | 'acked' | 'closed' | 'fixed' | 'done';
  impact?: SloBreachImpact | 'low' | 'info' | 'high' | 'severe';
  windowDays?: number;
  breachPercentage?: number;
  detectedAt?: string;
  resolvedAt?: string;
  owner?: string;
  actions?: string[];
};

type EnrichedSloBreach = RawSloBreach & {
  id: string;
  sloId: string;
  service: string;
  indicator: string;
  status: SloBreachStatus;
  impact: SloBreachImpact;
  windowDays?: number;
  breachPercentage?: number;
  detectedAt?: string;
  resolvedAt?: string;
  owner?: string;
  actions: string[];
  open: boolean;
};

type CountsSummary = {
  planosTotal: number;
  planosGratisAprovados: number;
  assinaturasAtivas: number;
  assinaturasPendentes: number;
  cobrancasConfirmadas: number;
  cobrancasPendentes: number;
};

const FALLBACK_JSON = path.join('..', 'docs', 'examples', 'operations_readiness.json');

const loadFallbackSnapshot = (strapi: Strapi.Strapi): RawSnapshot => {
  const appRoot = strapi.dirs?.app?.root ?? process.cwd();
  const fallbackPath = path.resolve(appRoot, FALLBACK_JSON);

  try {
    const content = fs.readFileSync(fallbackPath, 'utf8');
    return JSON.parse(content);
  } catch (error) {
    strapi.log.warn(
      `Não foi possível ler ${fallbackPath}: ${(error as Error).message}. Usando valores padrão.`
    );
    return {
      components: [],
      sources: [],
    };
  }
};

const exists = (root: string, relative: string): boolean => {
  return fs.existsSync(path.resolve(root, relative));
};

const makeCheck = (
  key: string,
  label: string,
  done: boolean,
  details?: string
): ReadinessCheck => ({
  key,
  label,
  status: done ? 'done' : 'todo',
  details,
});

const computeFlutterChecks = (repoRoot: string): ReadinessCheck[] => {
  return [
    makeCheck(
      'notificationsChannel',
      'AppDelegate com canais Pix e notificações ricas',
      exists(repoRoot, 'flutter/ios/Runner/AppDelegate.swift')
    ),
    makeCheck(
      'pixPaywallCache',
      'Paywall Pix com cache Hive',
      exists(repoRoot, 'flutter/lib/features/paywall/data/pix_charge_store.dart') &&
        exists(repoRoot, 'flutter/lib/features/paywall/data/plan_cache_store.dart')
    ),
    makeCheck(
      'studyModules',
      'Módulos de estudo implementados (questões/simulados/metas)',
      exists(repoRoot, 'flutter/lib/features/questions') &&
        exists(repoRoot, 'flutter/lib/features/simulados')
    ),
    makeCheck(
      'studyAssets',
      'Assets locais de questões e simulados empacotados',
      exists(repoRoot, 'flutter/assets/data/questoes.json') &&
        exists(repoRoot, 'flutter/assets/data/simulados.json')
    ),
    makeCheck(
      'instrumentedTests',
      'Testes widget/instrumentados para Pix',
      exists(repoRoot, 'flutter/test/features/paywall')
    ),
  ];
};

const computeStrapiChecks = (counts: CountsSummary): ReadinessCheck[] => {
  return [
    makeCheck(
      'plansSeeded',
      'Planos Pix cadastrados',
      counts.planosTotal > 0,
      `Total: ${counts.planosTotal}`
    ),
    makeCheck(
      'freePlanApproved',
      'Plano Grátis para Alunos aprovado',
      counts.planosGratisAprovados > 0,
      `Aprovados: ${counts.planosGratisAprovados}`
    ),
    makeCheck(
      'activeSubscriptions',
      'Assinaturas Pix ativas',
      counts.assinaturasAtivas > 0,
      `Ativas: ${counts.assinaturasAtivas}`
    ),
    makeCheck(
      'confirmedCharges',
      'Cobranças Pix confirmadas',
      counts.cobrancasConfirmadas > 0,
      `Confirmadas: ${counts.cobrancasConfirmadas}`
    ),
  ];
};

const computeOperationsChecks = (repoRoot: string): ReadinessCheck[] => {
  return [
    makeCheck(
      'pixAuditScript',
      'Script de auditoria Pix disponível',
      exists(repoRoot, 'scripts/ops/pix_ops_audit.py')
    ),
    makeCheck(
      'readinessScript',
      'Script de snapshot de prontidão',
      exists(repoRoot, 'scripts/ops/readiness_snapshot.py')
    ),
    makeCheck(
      'releaseRunbook',
      'Runbook de lançamento publicado',
      exists(repoRoot, 'docs/release-runbook.md')
    ),
    makeCheck(
      'finalizationPlaybook',
      'Playbook de finalização disponível',
      exists(repoRoot, 'docs/finalizacao-app.md')
    ),
  ];
};

const computeCounts = async (strapi: Strapi.Strapi): Promise<CountsSummary> => {
  const [
    planosTotal,
    planosGratisAprovados,
    assinaturasAtivas,
    assinaturasPendentes,
    cobrancasConfirmadas,
    cobrancasPendentes,
  ] = await Promise.all([
    strapi.db.query('api::plano.plano').count(),
    strapi.db
      .query('api::plano.plano')
      .count({ where: { tipo: 'gratis_aluno', statusAprovacao: 'aprovado' } }),
    strapi.db
      .query('api::assinatura.assinatura')
      .count({ where: { status: 'ativa' } }),
    strapi.db
      .query('api::assinatura.assinatura')
      .count({ where: { status: 'pendente' } }),
    strapi.db
      .query('api::cobranca-pix.cobranca-pix')
      .count({ where: { status: 'confirmado' } }),
    strapi.db
      .query('api::cobranca-pix.cobranca-pix')
      .count({ where: { status: 'pendente' } }),
  ]);

  return {
    planosTotal,
    planosGratisAprovados,
    assinaturasAtivas,
    assinaturasPendentes,
    cobrancasConfirmadas,
    cobrancasPendentes,
  };
};

const normalizeBaseline = (component: RawComponent): number => {
  if (typeof component.baseline === 'number') {
    return component.baseline;
  }
  if (typeof component.percentage === 'number') {
    return component.percentage;
  }
  return 0;
};

const enrichComponent = (
  raw: RawComponent,
  checks: ReadinessCheck[],
  weights: Record<string, number>
): EnrichedComponent => {
  const baselinePercentage = normalizeBaseline(raw);
  const weight = typeof raw.weight === 'number' ? raw.weight : weights[raw.key] ?? 0;
  const totalChecks = checks.length;
  const completed = checks.filter((check) => check.status === 'done').length;
  const computedPercentage =
    totalChecks > 0 ? Math.round((completed / totalChecks) * 100) : baselinePercentage;
  const percentage = Math.max(baselinePercentage, computedPercentage);
  const pending = checks.filter((check) => check.status !== 'done').map((check) => check.key);

  return {
    ...raw,
    baselinePercentage,
    computedPercentage,
    percentage,
    weight,
    checks,
    pending,
  };
};

const isCheckDone = (
  checksIndex: Map<string, ReadinessCheck>,
  key: string
): boolean => {
  return checksIndex.get(key)?.status === 'done';
};

const parseDate = (value?: string): Date | undefined => {
  if (!value) {
    return undefined;
  }
  const parsed = new Date(value);
  return Number.isNaN(parsed.getTime()) ? undefined : parsed;
};

const parseNumber = (value: unknown): number | undefined => {
  if (typeof value === 'number' && Number.isFinite(value)) {
    return value;
  }
  if (typeof value === 'string') {
    const parsed = Number(value);
    return Number.isFinite(parsed) ? parsed : undefined;
  }
  if (typeof value === 'boolean') {
    return value ? 1 : 0;
  }
  return undefined;
};

const formatIso = (value?: Date): string | undefined => {
  if (!value) {
    return undefined;
  }
  return value.toISOString();
};

const computeMilestoneProgress = (
  milestone: RawMilestone,
  counts: CountsSummary,
  checksIndex: Map<string, ReadinessCheck>
): { status: MilestoneStatus; completion: number; blockers: string[] } => {
  const blockers = new Set<string>((milestone.blockers ?? []).filter(Boolean));
  let progressScore = 0;
  let status: MilestoneStatus = milestone.status ?? 'pending';

  switch (milestone.id) {
    case 'ios_notifications_channel': {
      const notificationsReady = isCheckDone(checksIndex, 'notificationsChannel');
      const cachesReady = isCheckDone(checksIndex, 'pixPaywallCache');
      const modulesReady = isCheckDone(checksIndex, 'studyModules');

      if (notificationsReady) {
        status = 'done';
        progressScore = 1;
      } else {
        if (cachesReady) {
          progressScore += 0.4;
        }
        if (modulesReady) {
          progressScore += 0.3;
        }
        status = progressScore > 0 ? 'in_progress' : 'pending';
        blockers.add('Habilitar canal de notificações Pix no iOS');
      }
      break;
    }
    case 'pix_checkout_live': {
      const plansSeeded = counts.planosTotal > 0;
      const freePlanReady = isCheckDone(checksIndex, 'freePlanApproved');
      const activeSubscriptions = counts.assinaturasAtivas > 0;
      const confirmedCharges = counts.cobrancasConfirmadas > 0;

      if (plansSeeded) {
        progressScore += 0.25;
      } else {
        blockers.add('Publicar planos Pix no CMS');
      }

      if (freePlanReady) {
        progressScore += 0.15;
      } else {
        blockers.add('Aprovar Plano Grátis para Alunos');
      }

      if (activeSubscriptions) {
        progressScore += 0.25;
      } else {
        blockers.add('Ativar assinaturas Pix reais');
      }

      if (confirmedCharges) {
        progressScore += 0.35;
      } else {
        blockers.add('Confirmar ao menos uma cobrança Pix');
      }

      if (plansSeeded && freePlanReady && activeSubscriptions && confirmedCharges) {
        status = 'done';
        progressScore = 1;
      } else if (progressScore > 0) {
        status = 'in_progress';
      } else {
        status = 'pending';
      }
      break;
    }
    case 'ops_observability': {
      const auditReady = isCheckDone(checksIndex, 'pixAuditScript');
      const snapshotReady = isCheckDone(checksIndex, 'readinessScript');
      const runbookReady = isCheckDone(checksIndex, 'releaseRunbook');
      const checklistReady = isCheckDone(checksIndex, 'finalizationPlaybook');

      const completed = [auditReady, snapshotReady, runbookReady, checklistReady].filter(Boolean).length;
      progressScore = completed / 4;

      if (completed === 4) {
        status = 'done';
      } else if (completed > 0) {
        status = 'in_progress';
      } else {
        status = 'pending';
      }

      if (!runbookReady) {
        blockers.add('Publicar runbook operacional atualizado');
      }
      if (!checklistReady) {
        blockers.add('Finalizar checklist de finalização');
      }
      break;
    }
    default:
      status = milestone.status ?? 'pending';
      progressScore = typeof milestone.completion === 'number' ? milestone.completion / 100 : 0;
  }

  const completion = status === 'done'
    ? 100
    : Math.max(10, Math.round(Math.min(progressScore, 1) * 100));

  return {
    status,
    completion,
    blockers: Array.from(blockers),
  };
};

const enrichMilestones = (
  milestones: RawMilestone[] | undefined,
  counts: CountsSummary,
  checksIndex: Map<string, ReadinessCheck>
): EnrichedMilestone[] => {
  if (!milestones) {
    return [];
  }

  return milestones.map((milestone) => {
    const progress = computeMilestoneProgress(milestone, counts, checksIndex);
    const targetDate = parseDate(milestone.targetDate);
    const overdue = !!targetDate && progress.status !== 'done' && targetDate.getTime() < Date.now();

    return {
      ...milestone,
      status: progress.status,
      completion: progress.completion,
      overdue,
      blockers: progress.blockers,
    };
  });
};

const enrichAlerts = (
  alerts: RawAlert[] | undefined,
  counts: CountsSummary,
  checksIndex: Map<string, ReadinessCheck>
): EnrichedAlert[] => {
  if (!alerts) {
    return [];
  }

  return alerts
    .map((alert) => {
      const level = alert.level ?? 'info';
      let active = alert.active !== false;
      let details = alert.details;

      switch (alert.id) {
        case 'pending_pix_charges': {
          const pendingCharges = counts.cobrancasPendentes;
          active = pendingCharges > 0;
          if (active) {
            details = `Há ${pendingCharges} cobranças Pix pendentes aguardando conciliação.`;
          }
          break;
        }
        case 'missing_ios_tests': {
          const testsReady = isCheckDone(checksIndex, 'instrumentedTests');
          active = !testsReady;
          if (active && !details) {
            details = 'Implemente a suíte mínima de testes instrumentados para Pix no iOS.';
          }
          break;
        }
        default:
          active = alert.active !== false;
      }

      return {
        ...alert,
        level,
        active,
        details,
        message: alert.message ?? 'Alerta operacional',
      };
    })
    .filter((alert) => alert.active);
};

const sanitizeActions = (actions?: string[]): string[] => {
  if (!actions) {
    return [];
  }
  return actions.filter((action): action is string => typeof action === 'string' && action.trim().length > 0);
};

const enrichIncidents = (incidents: RawIncident[] | undefined): EnrichedIncident[] => {
  if (!incidents) {
    return [];
  }

  const now = new Date();

  return incidents
    .map((incident) => {
      const startedAt = parseDate(incident.startedAt);
      const resolvedAt = parseDate(incident.resolvedAt);
      const updatedAt = parseDate(incident.updatedAt) ?? resolvedAt ?? now;
      const status: IncidentStatus = incident.status ?? (resolvedAt ? 'resolved' : 'monitoring');
      const impact: IncidentImpact = incident.impact ?? 'minor';
      const actions = sanitizeActions(incident.actions);
      const summary = incident.summary ?? incident.details;

      const durationMinutes = incident.durationMinutes ??
        (startedAt
          ? Math.max(1, Math.round(((resolvedAt ?? updatedAt).getTime() - startedAt.getTime()) / 60000))
          : undefined);

      return {
        ...incident,
        status,
        impact,
        summary,
        actions,
        startedAt: formatIso(startedAt),
        resolvedAt: formatIso(resolvedAt),
        updatedAt: formatIso(updatedAt),
        durationMinutes,
        active: status !== 'resolved',
      };
    })
    .sort((a, b) => {
      const aDate = a.startedAt ? Date.parse(a.startedAt) : 0;
      const bDate = b.startedAt ? Date.parse(b.startedAt) : 0;
      return bDate - aDate;
    });
};

const enrichMaintenanceWindows = (
  maintenance: RawMaintenanceWindow[] | undefined
): EnrichedMaintenanceWindow[] => {
  if (!maintenance) {
    return [];
  }

  const now = new Date();

  return maintenance
    .map((window) => {
      const startDate = parseDate(window.windowStart);
      const endDate = parseDate(window.windowEnd);
      let status: MaintenanceStatus = window.status ?? 'scheduled';

      if (!window.status) {
        if (endDate && endDate.getTime() < now.getTime()) {
          status = 'completed';
        } else if (startDate && startDate.getTime() <= now.getTime() && (!endDate || endDate.getTime() >= now.getTime())) {
          status = 'in_progress';
        } else {
          status = 'scheduled';
        }
      }

      const impact: MaintenanceImpact = window.impact ?? 'minor';
      const durationMinutes = window.durationMinutes ??
        (startDate && endDate ? Math.max(1, Math.round((endDate.getTime() - startDate.getTime()) / 60000)) : undefined);

      const systems = (window.systems ?? []).filter((system): system is string => {
        return typeof system === 'string' && system.trim().length > 0;
      });
      const isActive = status === 'in_progress';
      const isUpcoming = status === 'scheduled' && !!startDate && startDate.getTime() > now.getTime();

      return {
        ...window,
        status,
        impact,
        windowStart: formatIso(startDate),
        windowEnd: formatIso(endDate),
        durationMinutes,
        isActive,
        isUpcoming,
        systems,
      };
    })
    .sort((a, b) => {
      const aDate = a.windowStart ? Date.parse(a.windowStart) : 0;
      const bDate = b.windowStart ? Date.parse(b.windowStart) : 0;
      return aDate - bDate;
    });
};

const enrichOnCall = (entries: RawOnCall[] | undefined): EnrichedOnCall[] => {
  if (!entries) {
    return [];
  }

  const now = Date.now();

  return entries
    .map((entry) => {
      const startedAt = parseDate(entry.startedAt);
      const endsAt = parseDate(entry.endsAt);
      let status: 'active' | 'standby' | 'offline' = entry.status ?? 'standby';

      if (!entry.status) {
        if (startedAt && startedAt.getTime() <= now && (!endsAt || endsAt.getTime() >= now)) {
          status = 'active';
        } else if (startedAt && startedAt.getTime() > now) {
          status = 'standby';
        } else if (endsAt && endsAt.getTime() < now) {
          status = 'offline';
        } else if (entry.primary) {
          status = 'active';
        }
      }

      const durationMinutes =
        parseNumber(entry.shiftDurationMinutes) ??
        (startedAt && endsAt
          ? Math.max(1, Math.round((endsAt.getTime() - startedAt.getTime()) / 60000))
          : undefined);

      return {
        ...entry,
        name: entry.name?.trim() ?? 'Plantonista',
        role: entry.role?.trim() ?? 'On-call',
        contact: entry.contact?.trim() ?? '',
        status,
        primary: entry.primary === true,
        startedAt: formatIso(startedAt),
        endsAt: formatIso(endsAt),
        shiftDurationMinutes: durationMinutes,
        escalationPolicy: entry.escalationPolicy?.trim(),
      };
    })
    .sort((a, b) => {
      if (a.primary !== b.primary) {
        return a.primary ? -1 : 1;
      }
      const statusOrder: Record<'active' | 'standby' | 'offline', number> = {
        active: 0,
        standby: 1,
        offline: 2,
      };
      if (statusOrder[a.status] !== statusOrder[b.status]) {
        return statusOrder[a.status] - statusOrder[b.status];
      }
      const aStart = a.startedAt ? Date.parse(a.startedAt) : 0;
      const bStart = b.startedAt ? Date.parse(b.startedAt) : 0;
      return aStart - bStart;
    });
};

const normalizeAutomationStatus = (status?: string): AutomationStatus => {
  if (!status) {
    return 'operational';
  }
  const normalized = status.toLowerCase().replace(/-/g, '_');
  if (
    normalized === 'operational' ||
    normalized === 'in_progress' ||
    normalized === 'degraded' ||
    normalized === 'blocked'
  ) {
    return normalized;
  }
  if (normalized === 'ok' || normalized === 'healthy' || normalized === 'green') {
    return 'operational';
  }
  if (normalized === 'warning' || normalized === 'yellow' || normalized === 'at_risk') {
    return 'degraded';
  }
  if (normalized === 'critical' || normalized === 'red' || normalized === 'down') {
    return 'blocked';
  }
  return 'operational';
};

const parseOwners = (owners?: RawAutomation['owners']): string[] => {
  if (!owners) {
    return [];
  }
  if (typeof owners === 'string') {
    return owners
      .split(',')
      .map((owner) => owner.trim())
      .filter((owner) => owner.length > 0);
  }
  return owners
    .map((owner) => owner?.toString().trim())
    .filter((owner): owner is string => !!owner && owner.length > 0);
};

const parseSuccessRate = (value: unknown): number | undefined => {
  const parsed = parseNumber(value);
  if (typeof parsed !== 'number') {
    return undefined;
  }
  if (parsed > 1) {
    return Math.min(1, parsed / 100);
  }
  if (parsed < 0) {
    return 0;
  }
  return parsed;
};

const parseCoverage = (value: unknown): number | undefined => {
  const parsed = parseNumber(value);
  if (typeof parsed !== 'number') {
    return undefined;
  }
  if (parsed < 0) {
    return 0;
  }
  if (parsed > 100) {
    return 100;
  }
  return parsed;
};

const enrichAutomations = (
  automations: RawAutomation[] | undefined
): EnrichedAutomation[] => {
  if (!automations) {
    return [];
  }

  return automations
    .map((automation, index) => {
      const lastRun = parseDate(automation.lastRunAt);
      const nextRun = parseDate(automation.nextRunAt);
      const status = normalizeAutomationStatus(automation.status);

      return {
        ...automation,
        id: automation.id ?? `automation-${index}`,
        title: automation.title?.trim() ?? 'Automação',
        description: automation.description?.trim(),
        status,
        owners: parseOwners(automation.owners),
        lastRunAt: formatIso(lastRun),
        nextRunAt: formatIso(nextRun),
        successRate: parseSuccessRate(automation.successRate),
        coverage: parseCoverage(automation.coverage),
        signals:
          automation.signals?.filter(
            (signal): signal is string => typeof signal === 'string' && signal.trim().length > 0
          ) ?? [],
        playbooks:
          automation.playbooks?.filter(
            (playbook): playbook is string =>
              typeof playbook === 'string' && playbook.trim().length > 0
          ) ?? [],
      };
    })
    .sort((a, b) => {
      const statusOrder: Record<AutomationStatus, number> = {
        blocked: 0,
        degraded: 1,
        in_progress: 2,
        operational: 3,
      };
      if (statusOrder[a.status] !== statusOrder[b.status]) {
        return statusOrder[a.status] - statusOrder[b.status];
      }
      const aLast = a.lastRunAt ? Date.parse(a.lastRunAt) : 0;
      const bLast = b.lastRunAt ? Date.parse(b.lastRunAt) : 0;
      if (aLast !== bLast) {
        return bLast - aLast;
      }
      return a.title.localeCompare(b.title);
    });
};

const normalizeSloStatus = (
  status: RawSlo['status']
): 'healthy' | 'at_risk' | 'breaching' | undefined => {
  if (!status) {
    return undefined;
  }
  if (status === 'at-risk') {
    return 'at_risk';
  }
  if (status === 'healthy' || status === 'at_risk' || status === 'breaching') {
    return status;
  }
  return undefined;
};

const enrichSlos = (slos: RawSlo[] | undefined): EnrichedSlo[] => {
  if (!slos) {
    return [];
  }

  return slos
    .map((slo) => {
      const target = parseNumber(slo.target) ?? 0;
      const current = parseNumber(slo.current) ?? 0;
      const direction: 'above' | 'below' = slo.direction === 'below' ? 'below' : 'above';
      const breaches = Math.max(0, Math.round(parseNumber(slo.breaches) ?? 0));
      const windowDays = parseNumber(slo.windowDays);
      let status = normalizeSloStatus(slo.status);

      if (!status) {
        const meetsTarget = direction === 'above' ? current >= target : current <= target;
        const nearTarget = direction === 'above'
          ? current >= target * 0.95
          : current <= target * 1.05;

        if (meetsTarget && breaches === 0) {
          status = 'healthy';
        } else if (!meetsTarget && breaches > 0) {
          status = 'breaching';
        } else {
          status = nearTarget ? 'at_risk' : 'breaching';
        }
      }

      const notes = (slo.notes ?? []).filter((note): note is string => {
        return typeof note === 'string' && note.trim().length > 0;
      });

      return {
        ...slo,
        service: slo.service?.trim() ?? 'Serviço',
        indicator: slo.indicator?.trim() ?? 'Indicador',
        target,
        current,
        direction,
        status: status ?? 'healthy',
        windowDays: typeof windowDays === 'number' ? Math.max(0, Math.round(windowDays)) : undefined,
        breaches,
        notes,
      };
    })
    .sort((a, b) => {
      const statusOrder: Record<'healthy' | 'at_risk' | 'breaching', number> = {
        healthy: 0,
        at_risk: 1,
        breaching: 2,
      };
      if (statusOrder[a.status] !== statusOrder[b.status]) {
        return statusOrder[a.status] - statusOrder[b.status];
      }
      return a.service.localeCompare(b.service);
    });
};

const normalizeSloBreachStatus = (
  status: RawSloBreach['status']
): SloBreachStatus => {
  if (!status) {
    return 'open';
  }
  const normalized = status.replace(/-/g, '_').toLowerCase();
  if (normalized === 'ack' || normalized === 'acked') {
    return 'acknowledged';
  }
  if (normalized === 'closed' || normalized === 'fixed' || normalized === 'done') {
    return 'resolved';
  }
  if (normalized === 'acknowledged' || normalized === 'resolved' || normalized === 'open') {
    return normalized as SloBreachStatus;
  }
  return 'open';
};

const normalizeSloBreachImpact = (
  impact: RawSloBreach['impact']
): SloBreachImpact => {
  if (!impact) {
    return 'minor';
  }
  const normalized = impact.toLowerCase();
  if (normalized === 'low' || normalized === 'info') {
    return 'minor';
  }
  if (normalized === 'high' || normalized === 'severe') {
    return 'major';
  }
  if (normalized === 'none' || normalized === 'minor' || normalized === 'major' || normalized === 'critical') {
    return normalized as SloBreachImpact;
  }
  return 'minor';
};

const enrichSloBreaches = (
  breaches: RawSloBreach[] | undefined
): EnrichedSloBreach[] => {
  if (!breaches) {
    return [];
  }

  return breaches
    .map((breach, index) => {
      const detectedAt = parseDate(breach.detectedAt);
      const resolvedAt = parseDate(breach.resolvedAt);
      const status = normalizeSloBreachStatus(breach.status);
      const impact = normalizeSloBreachImpact(breach.impact);
      const windowDaysNumber = parseNumber(breach.windowDays);
      const breachPercentage = parseNumber(breach.breachPercentage);
      const id = breach.id?.toString() ?? breach.sloId?.toString() ?? `breach-${index}`;
      const sloId = breach.sloId?.toString() ?? id;
      const owner = breach.owner?.trim();
      const actions = sanitizeActions(breach.actions);

      return {
        ...breach,
        id,
        sloId,
        service: breach.service?.trim() ?? 'Serviço',
        indicator: breach.indicator?.trim() ?? 'Indicador',
        status,
        impact,
        windowDays:
          typeof windowDaysNumber === 'number' && !Number.isNaN(windowDaysNumber)
            ? Math.round(windowDaysNumber)
            : undefined,
        breachPercentage:
          typeof breachPercentage === 'number' && !Number.isNaN(breachPercentage)
            ? breachPercentage
            : undefined,
        detectedAt: formatIso(detectedAt),
        resolvedAt: formatIso(resolvedAt),
        owner: owner && owner.length > 0 ? owner : undefined,
        actions,
        open: status !== 'resolved',
      };
    })
    .sort((a, b) => {
      const statusOrder: Record<SloBreachStatus, number> = {
        open: 0,
        acknowledged: 1,
        resolved: 2,
      };
      if (statusOrder[a.status] !== statusOrder[b.status]) {
        return statusOrder[a.status] - statusOrder[b.status];
      }
      const aDetected = a.detectedAt ? Date.parse(a.detectedAt) : 0;
      const bDetected = b.detectedAt ? Date.parse(b.detectedAt) : 0;
      if (aDetected !== bDetected) {
        return bDetected - aDetected;
      }
      return a.service.localeCompare(b.service);
    });
};

const weightedAverage = (
  components: EnrichedComponent[],
  weights: Record<string, number>,
  selector: 'baselinePercentage' | 'computedPercentage'
): number => {
  let totalWeight = 0;
  let total = 0;

  for (const component of components) {
    const weight = component.weight ?? weights[component.key] ?? 0;
    if (weight <= 0) {
      continue;
    }
    const value = component[selector];
    total += value * weight;
    totalWeight += weight;
  }

  if (totalWeight === 0) {
    return 0;
  }

  return total / totalWeight;
};

export default factories.createCoreController(
  'api::operations-readiness.operations-readiness',
  ({ strapi }) => ({
    async readiness(ctx) {
      const fallback = loadFallbackSnapshot(strapi);
      const repoRoot = path.resolve(strapi.dirs.app.root, '..');
      const counts = await computeCounts(strapi);

      const weights = fallback.overall?.weights ?? {
        flutter_ios: 0.4,
        strapi_backend: 0.4,
        operations: 0.2,
      };

      const rawComponents = fallback.components ?? [];
      const incidentItems = (fallback.incidents ?? []).filter((item): item is RawIncident => {
        return !!item && typeof item === 'object';
      });
      const maintenanceItems = (
        (fallback.maintenance ?? fallback.maintenanceWindows) ?? []
      ).filter((item): item is RawMaintenanceWindow => {
        return !!item && typeof item === 'object';
      });
      const onCallItems = (fallback.onCall ?? []).filter((item): item is RawOnCall => {
        return !!item && typeof item === 'object';
      });
      const automationItems = (fallback.automations ?? []).filter(
        (item): item is RawAutomation => !!item && typeof item === 'object'
      );
      const sloItems = (fallback.slos ?? []).filter((item): item is RawSlo => {
        return !!item && typeof item === 'object';
      });
      const sloBreachItems = (fallback.sloBreaches ?? []).filter(
        (item): item is RawSloBreach => !!item && typeof item === 'object'
      );

      const enriched = rawComponents.map((component) => {
        switch (component.key) {
          case 'flutter_ios':
            return enrichComponent(
              component,
              computeFlutterChecks(repoRoot),
              weights
            );
          case 'strapi_backend':
            return enrichComponent(
              component,
              computeStrapiChecks(counts),
              weights
            );
          case 'operations':
            return enrichComponent(
              component,
              computeOperationsChecks(repoRoot),
              weights
            );
          default:
            return enrichComponent(component, [], weights);
        }
      });

      const checksIndex = new Map<string, ReadinessCheck>();
      for (const component of enriched) {
        for (const check of component.checks) {
          checksIndex.set(check.key, check);
        }
      }

      const milestones = enrichMilestones(fallback.milestones, counts, checksIndex);
      const alerts = enrichAlerts(fallback.alerts, counts, checksIndex);

      const baselineOverall =
        typeof fallback.overall?.baseline === 'number'
          ? fallback.overall?.baseline ?? 0
          : weightedAverage(enriched, weights, 'baselinePercentage');
      const computedOverall = Math.round(
        weightedAverage(enriched, weights, 'computedPercentage')
      );
      const percentage = Math.max(baselineOverall, computedOverall);

      ctx.body = {
        timestamp: new Date().toISOString(),
        baselineTimestamp: fallback.timestamp ?? null,
        overall: {
          percentage,
          baseline: baselineOverall,
          computed: computedOverall,
          weights,
          notes: fallback.overall?.notes ?? [],
        },
        components: enriched.map((component) => ({
          key: component.key,
          label: component.label,
          percentage: component.percentage,
          baseline: component.baselinePercentage,
          computed: component.computedPercentage,
          weight: component.weight,
          notes: component.notes ?? [],
          nextSteps: component.nextSteps ?? [],
          checks: component.checks,
          pending: component.pending,
        })),
        counts,
        sources: fallback.sources ?? [],
        milestones,
        alerts,
        incidents: enrichIncidents(incidentItems),
        maintenanceWindows: enrichMaintenanceWindows(maintenanceItems),
        onCall: enrichOnCall(onCallItems),
        automations: enrichAutomations(automationItems),
        slos: enrichSlos(sloItems),
        sloBreaches: enrichSloBreaches(sloBreachItems),
      };
    },
  })
);
