async function getName(userId) {
  // Simula busca assíncrona; em produção poderia consultar um banco
  return new Promise(resolve => {
    setTimeout(() => resolve('Patrícia'), 50);
  });
}
module.exports = { getName };
