const readline = require('readline');
const Bot = require('./Bot');
const { getName } = require('./userDatabase');

const bot = new Bot(getName);
const rl = readline.createInterface({ input: process.stdin, output: process.stdout });

async function prompt(question) {
  return new Promise(resolve => rl.question(question, answer => resolve(answer)));
}

async function main() {
  let userId = 'user';
  console.log(await bot.handleMessage(userId, ''));
  while (true) {
    const text = await prompt('> ');
    const reply = await bot.handleMessage(userId, text);
    console.log(reply);
    if (reply === 'Atendimento finalizado.') {
      rl.close();
      break;
    }
  }
}

main();
