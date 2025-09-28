export default {
  routes: [
    {
      method: 'GET',
      path: '/health',
      handler: 'health.index',
      config: {
        policies: [],
        middlewares: [],
      },
    },
  ],
};
