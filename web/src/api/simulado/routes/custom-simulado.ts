export default {
  routes: [
    {
      method: 'POST',
      path: '/simulados/gerar',
      handler: 'simulado.generateExpress',
      config: {
        policies: [],
        middlewares: [],
      },
    },
    {
      method: 'POST',
      path: '/simulados/:id/responder',
      handler: 'simulado.submitResponses',
      config: {
        policies: [],
        middlewares: [],
      },
    },
  ],
};
