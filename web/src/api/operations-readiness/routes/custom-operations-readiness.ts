export default {
  routes: [
    {
      method: 'GET',
      path: '/operations/readiness',
      handler: 'operations-readiness.readiness',
      config: {
        policies: [],
      },
    },
  ],
};
