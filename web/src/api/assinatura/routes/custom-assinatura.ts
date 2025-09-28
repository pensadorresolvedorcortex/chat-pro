export default {
  routes: [
    {
      method: 'POST',
      path: '/assinaturas/pix/cobrancas',
      handler: 'assinatura.createPixCharge',
      config: {
        policies: [],
      },
    },
    {
      method: 'GET',
      path: '/assinaturas/pix/cobrancas/:id',
      handler: 'assinatura.findPixCharge',
      config: {
        policies: [],
      },
    },
    {
      method: 'PATCH',
      path: '/assinaturas/pix/cobrancas/:id/status',
      handler: 'assinatura.updatePixChargeStatus',
      config: {
        policies: ['admin::isAuthenticatedAdmin'],
      },
    },
    {
      method: 'GET',
      path: '/assinaturas/pix/chave-principal',
      handler: 'assinatura.getPrimaryPixKey',
      config: {
        auth: false,
      },
    },
  ],
};
