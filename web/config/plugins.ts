export default ({ env }) => {
  const redisEnabled = env.bool('REDIS_ENABLED', false);
  const meilisearchEnabled = env.bool('MEILISEARCH_ENABLED', false);

  return {
    email: {
      config: {
        provider: 'nodemailer',
        providerOptions: {
          host: env('SMTP_HOST', 'smtp.ethereal.email'),
          port: env.int('SMTP_PORT', 587),
          auth: {
            user: env('SMTP_USERNAME'),
            pass: env('SMTP_PASSWORD'),
          },
        },
        settings: {
          defaultFrom: env('EMAIL_DEFAULT_FROM', 'nao-responda@academia.com.br'),
          defaultReplyTo: env('EMAIL_DEFAULT_REPLY_TO', 'suporte@academia.com.br'),
        },
      },
    },
    'users-permissions': {
      config: {
        jwt: {
          expiresIn: env('JWT_EXPIRES_IN', '30d'),
        },
      },
    },
    seo: {
      enabled: true,
    },
    sentry: {
      enabled: env.bool('SENTRY_ENABLED', false),
      config: {
        dsn: env('SENTRY_DSN'),
      },
    },
    redis: {
      config: redisEnabled
          ? {
              enabled: true,
              sentinels: null,
              host: env('REDIS_HOST', '127.0.0.1'),
              port: env.int('REDIS_PORT', 6379),
              password: env('REDIS_PASSWORD', undefined),
              db: env.int('REDIS_DB', 0),
              keyPrefix: env('REDIS_PREFIX', 'academia'),
            }
          : {
              enabled: false,
            },
    },
    meilisearch: {
      enabled: meilisearchEnabled,
      config: meilisearchEnabled
          ? {
              host: env('MEILISEARCH_HOST', 'http://127.0.0.1:7700'),
              apiKey: env('MEILISEARCH_API_KEY'),
            }
          : {
              host: null,
              apiKey: null,
            },
    },
  };
};
