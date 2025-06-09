const readline = require('readline');
const Bot = require('./Bot');
const { getName } = require('./userDatabase');

const bot = new Bot(getName);
const userId = 'usuario-demo';

const rl = readline.createInterface({
  input: process.stdin,
  output: process.stdout
});

bot.handleMessage(userId, '').then(reply => console.log(reply));

rl.on('line', async (line) => {
  const reply = await bot.handleMessage(userId, line);
  console.log(reply);
  if (reply.includes('Atendimento finalizado.')) {
    rl.close();
  }
});
