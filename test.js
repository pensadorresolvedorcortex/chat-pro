const assert = require('assert');
const Bot = require('./Bot.js');
const { getName } = require('./userDatabase.js');

(async () => {
  const bot = new Bot(getName);
  const user = 'test';
  let reply = await bot.handleMessage(user, '');
  assert(reply.includes('Agência Privilége'), 'start message');

  reply = await bot.handleMessage(user, '1');
  assert(reply.includes('Olá'), 'existing client greeting');

  reply = await bot.handleMessage(user, 'sair');
  assert(reply.includes('Atendimento finalizado'), 'exit message');

  console.log('All tests passed');
})();
