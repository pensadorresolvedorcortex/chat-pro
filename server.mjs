import express from 'express';
import axios from 'axios';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';
import dotenv from 'dotenv';
import Bot from './bot/Bot.js';
import { getName } from './bot/userDatabase.js';

dotenv.config();
const __dirname = path.dirname(fileURLToPath(import.meta.url));

let config = {
  WHATSAPP_TOKEN: process.env.WHATSAPP_TOKEN || '',
  WHATSAPP_PHONE_ID: process.env.WHATSAPP_PHONE_ID || '',
  VERIFY_TOKEN: process.env.VERIFY_TOKEN || '',
  PORT: process.env.PORT || 3000
};

try {
  const stored = JSON.parse(fs.readFileSync(path.join(__dirname, 'config.json')));
  config = { ...config, ...stored };
} catch (e) {
  // arquivo inexistente ou inválido
}

const bot = new Bot(getName);
const app = express();
app.use(express.json());
app.use(express.urlencoded({ extended: true }));
app.use(express.static(path.join(__dirname, 'public')));

app.get('/', (req, res) => {
  res.sendFile(path.join(__dirname, 'public/index.html'));
});

app.get('/api/config', (req, res) => {
  res.json(config);
});

app.post('/api/config', (req, res) => {
  config = { ...config, ...req.body };
  fs.writeFileSync(path.join(__dirname, 'config.json'), JSON.stringify(config, null, 2));
  res.json({ status: 'saved' });
});

app.get('/webhook', (req, res) => {
  const mode = req.query['hub.mode'];
  const token = req.query['hub.verify_token'];
  const challenge = req.query['hub.challenge'];
  if (mode === 'subscribe' && token === config.VERIFY_TOKEN) {
    res.status(200).send(challenge);
  } else {
    res.sendStatus(403);
  }
});

app.post('/webhook', async (req, res) => {
  const entry = req.body.entry?.[0];
  const changes = entry?.changes?.[0];
  const value = changes?.value;
  const messageObject = value?.messages?.[0];
  if (messageObject) {
    const from = messageObject.from;
    const text = messageObject.text?.body || '';
    const reply = await bot.handleMessage(from, text);
    await sendMessage(from, reply);
  }
  res.sendStatus(200);
});

async function sendMessage(to, text) {
  const url = `https://graph.facebook.com/v18.0/${config.WHATSAPP_PHONE_ID}/messages`;
  try {
    await axios.post(
      url,
      {
        messaging_product: 'whatsapp',
        to,
        text: { body: text }
      },
      {
        headers: {
          'Authorization': `Bearer ${config.WHATSAPP_TOKEN}`,
          'Content-Type': 'application/json'
        }
      }
    );
  } catch (err) {
    console.error('Erro ao enviar mensagem:', err.response?.data || err.message);
  }
}

app.listen(config.PORT, () => {
  console.log(`Servidor rodando na porta ${config.PORT}`);
});
