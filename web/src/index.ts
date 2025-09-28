import type { Core } from '@strapi/strapi';

export default {
  register() {},
  bootstrap({ strapi }: { strapi: Core.Strapi }) {
    strapi.log.info('Academia da Comunicação CMS inicializado.');
  },
};
