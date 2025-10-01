export default {
  routes: [
    {
      method: 'GET',
      path: '/cobrancas-pix',
      handler: 'cobranca-pix.find',
      config: {
        policies: ['admin::isAuthenticatedAdmin'],
      },
    },
    {
      method: 'GET',
      path: '/cobrancas-pix/:id',
      handler: 'cobranca-pix.findOne',
      config: {
        policies: ['admin::isAuthenticatedAdmin'],
      },
    },
  ],
};
