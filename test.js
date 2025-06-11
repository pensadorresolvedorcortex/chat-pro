const Bot = require('./Bot');
const { getName } = require('./userDatabase');

(async () => {
  const bot = new Bot(getName);
  let reply = await bot.handleMessage('u', '');
  if (!reply.includes('Seja muito bem-vindo')) {
    throw new Error('welcome message missing');
  }
  reply = await bot.handleMessage('u', '1');
  if (!reply.includes('Olá')) {
    throw new Error('existing client flow failed');
  }
  reply = await bot.handleMessage('u', 'sair');
  if (reply !== 'Atendimento finalizado.') {
    throw new Error('exit failed');
  }
  console.log('ok');
})();
