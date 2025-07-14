const express = require('express');
const fs = require('fs');
const path = require('path');
const app = express();
const PORT = process.env.PORT || 3000;

const DATA_FILE = path.join(__dirname, 'savedList.json');

app.use(express.json());
app.use(express.static(__dirname));

app.get('/list', (req, res) => {
  fs.readFile(DATA_FILE, 'utf8', (err, data) => {
    if (err) {
      return res.json([]);
    }
    try {
      const list = JSON.parse(data);
      res.json(list);
    } catch (e) {
      res.json([]);
    }
  });
});

app.post('/list', (req, res) => {
  const list = req.body.list;
  if (!Array.isArray(list)) {
    return res.status(400).json({ error: 'Invalid list' });
  }
  fs.writeFile(DATA_FILE, JSON.stringify(list, null, 2), err => {
    if (err) {
      console.error(err);
      return res.status(500).json({ error: 'Could not save list' });
    }
    res.json({ ok: true });
  });
});

app.listen(PORT, () => {
  console.log('Server running on port', PORT);
});
