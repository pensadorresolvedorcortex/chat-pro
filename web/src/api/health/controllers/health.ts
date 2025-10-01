import type { Context } from 'koa';
import type { Core } from '@strapi/strapi';

type ServiceStatus = 'up' | 'down' | 'disabled' | 'unknown';

type StatusPayload = {
  status: ServiceStatus;
  latencyMs?: number;
  error?: string;
};

declare const strapi: Core.Strapi;

const pingDatabase = async (app: Core.Strapi): Promise<StatusPayload> => {
  const connection = app.db?.connection as { raw?: (sql: string) => Promise<unknown> } | undefined;

  if (!connection?.raw) {
    return { status: 'down', error: 'Conexão com o banco indisponível' };
  }

  const startedAt = Date.now();

  try {
    await connection.raw('select 1');
    return { status: 'up', latencyMs: Date.now() - startedAt };
  } catch (error) {
    return {
      status: 'down',
      latencyMs: Date.now() - startedAt,
      error: error instanceof Error ? error.message : 'Falha ao consultar o banco de dados',
    };
  }
};

const resolveOptionalService = async (
  app: Core.Strapi,
  options: { plugin: string; enabled: boolean; ping?: () => Promise<StatusPayload> },
): Promise<StatusPayload> => {
  if (!options.enabled) {
    return { status: 'disabled' };
  }

  const pluginApi = (app as unknown as { plugin?: (name: string) => any }).plugin?.(options.plugin);

  if (!pluginApi) {
    return { status: 'unknown', error: `Plugin ${options.plugin} não está carregado` };
  }

  try {
    if (options.ping) {
      return await options.ping();
    }

    const service = pluginApi.service?.(options.plugin);
    const pingFn = service?.ping ?? service?.getClient?.()?.ping;

    if (typeof pingFn === 'function') {
      const startedAt = Date.now();
      const result = await pingFn.call(service);
      const ok = result === true || result === 'PONG' || result === 1 || result === 'OK';
      return {
        status: ok ? 'up' : 'down',
        latencyMs: Date.now() - startedAt,
        error: ok ? undefined : 'Ping retornou um estado inesperado',
      };
    }

    return { status: 'unknown', error: 'Plugin não expõe método de verificação' };
  } catch (error) {
    return {
      status: 'down',
      error: error instanceof Error ? error.message : 'Falha na verificação do serviço',
    };
  }
};

const normaliseOverall = (statuses: StatusPayload[]): 'ok' | 'degraded' =>
  statuses.some((status) => status.status === 'down') ? 'degraded' : 'ok';

export default {
  async index(ctx: Context) {
    const app = strapi;

    const database = await pingDatabase(app);

    const redisEnabled = app.config.get('plugin.redis.config.enabled', false);
    const redis = await resolveOptionalService(app, { plugin: 'redis', enabled: redisEnabled });

    const meilisearchEnabled = app.config.get('plugin.meilisearch.enabled', false);
    const meilisearch = await resolveOptionalService(app, {
      plugin: 'meilisearch',
      enabled: meilisearchEnabled,
    });

    const services = { database, redis, meilisearch };

    ctx.body = {
      status: normaliseOverall(Object.values(services)),
      timestamp: new Date().toISOString(),
      services,
    };
  },
};
