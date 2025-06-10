import pkg from 'whatsapp-web.js';
const { Client, LocalAuth } = pkg;
import qrcode from 'qrcode-terminal';
import Bot from './Bot.js';
import { getName } from './userDatabase.js';

const bot = new Bot(getName);

const client = new Client({ authStrategy: new LocalAuth() });

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

