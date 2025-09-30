export default {
  routes: [
    {
      method: 'GET',
      path: '/desafios/:id/ranking',
      handler: 'desafio.ranking',
      config: {
        policies: [],
        middlewares: [],
      },
    },
  ],
};
