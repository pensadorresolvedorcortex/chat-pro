export default {
  routes: [
    {
      method: 'GET',
      path: '/dashboard/home',
      handler: 'dashboard-home.home',
      config: {
        policies: ['plugin::users-permissions.isAuthenticated'],
        middlewares: [],
      },
    },
  ],
};
