export default {
  routes: [
    {
      method: 'GET',
      path: '/planos/dashboard',
      handler: 'plano.dashboard',
      config: {
        policies: ['admin::isAuthenticatedAdmin'],
      },
    },
    {
      method: 'POST',
      path: '/planos/:id/aprovar',
      handler: 'plano.approve',
      config: {
        policies: ['admin::isAuthenticatedAdmin'],
      },
    },
    {
      method: 'PATCH',
      path: '/planos/:id/preco',
      handler: 'plano.updatePrice',
      config: {
        policies: ['admin::isAuthenticatedAdmin'],
      },
    },
  ],
};
