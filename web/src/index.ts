import type { Core } from '@strapi/strapi';
import { seedExamples } from './bootstrap/seed';

export default {
  register() {},
  async bootstrap({ strapi }: { strapi: Core.Strapi }) {
    strapi.log.info('Academia da Comunicação CMS inicializado.');

    try {
      await seedExamples(strapi);
      strapi.log.info('Seeds de exemplos Pix sincronizados com sucesso.');
    } catch (error) {
      strapi.log.error('Falha ao executar seeds de exemplos Pix:', error as Error);
    }
  },
};
