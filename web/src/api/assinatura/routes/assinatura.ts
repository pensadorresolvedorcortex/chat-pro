export default {
  routes: [
    {
      method: 'GET',
      path: '/assinaturas',
      handler: 'assinatura.find',
      config: {
        policies: ['admin::isAuthenticatedAdmin'],
      },
    },
    {
      method: 'GET',
      path: '/assinaturas/:id',
      handler: 'assinatura.findOne',
      config: {
        policies: ['admin::isAuthenticatedAdmin'],
      },
    },
  ],
};
