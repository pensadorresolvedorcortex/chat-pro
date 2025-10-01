export default ({ env }) => ({
  host: env('HOST', '0.0.0.0'),
  port: env.int('PORT', 1337),
  url: env('PUBLIC_URL', ''),
  app: {
    keys: env.array('APP_KEYS'),
  },
  proxy: env.bool('PROXY_ENABLED', false),
  custom: {
    pix: {
      chavePrincipal: env('PIX_PRIMARY_KEY'),
      tipoChave: env('PIX_PRIMARY_KEY_TYPE'),
      nomeRecebedor: env('PIX_RECEBEDOR_NOME', 'Academia da Comunicação'),
      cidadeRecebedor: env('PIX_RECEBEDOR_CIDADE', 'Sao Paulo'),
    },
  },
});
