const express = require('express');
const path = require('path');
const Bot = require('./Bot');
const { getName } = require('./userDatabase');

const app = express();
const bot = new Bot(getName);

app.use(express.json());
app.use(express.static(path.join(__dirname, 'public')));

app.post('/api/message', async (req, res) => {
  const { userId, text } = req.body || {};
  try {
    const reply = await bot.handleMessage(userId || 'web', text || '');
    res.json({ reply });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

const port = process.env.PORT || 3000;
app.listen(port, () => {
  console.log(`Servidor HTTP rodando na porta ${port}`);
});
