let pkg;
try {
  pkg = await import('whatsapp-web.js');
} catch (err) {
  console.error('Dependência whatsapp-web.js não encontrada. Execute "npm install" antes de iniciar o bot.');
  process.exit(1);
}
const { Client, LocalAuth } = pkg.default || pkg;
let qrcode;
try {
  qrcode = (await import('qrcode-terminal')).default;
} catch (err) {
  console.error('Dependência qrcode-terminal não encontrada. Execute "npm install" antes de iniciar o bot.');
  process.exit(1);
}
import Bot from './Bot.js';
import { getName } from './userDatabase.js';

const bot = new Bot(getName);

const client = new Client({ authStrategy: new LocalAuth() });

console.log('Aguardando leitura do QR Code para conectar ao WhatsApp...');

client.on('qr', qr => {
  qrcode.generate(qr, { small: true });
});

client.on('ready', () => {
  console.log('Cliente conectado ao WhatsApp.');
});

client.on('message', async msg => {
  if (msg.fromMe) return;
  const reply = await bot.handleMessage(msg.from, msg.body);
  await client.sendMessage(msg.from, reply);
});

client.initialize();

