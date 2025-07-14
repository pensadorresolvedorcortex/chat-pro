const positions = [
  'Goleiro',
  'Fixo',
  'Lateral Direito',
  'Lateral Esquerdo',
  'Meia',
  'Pivô'
];

const initialPlayers = [
  { name: 'Dheniell', pos: 'Fixo', stone: 1 },
  { name: 'Bolo', pos: 'Fixo', stone: 1 },
  { name: 'MP', pos: 'Fixo', stone: 1 },
  { name: 'Wallace', pos: 'Lateral Direito', stone: 1 },
  { name: 'Papel', pos: 'Lateral Direito', stone: 1 },
  { name: 'Fred', pos: 'Lateral Direito', stone: 1 },
  { name: 'Dario', pos: 'Lateral Esquerdo', stone: 1 },
  { name: 'Gabriel R', pos: 'Lateral Esquerdo', stone: 1 },
  { name: 'Kloh', pos: 'Lateral Esquerdo', stone: 1 },
  { name: 'Bebeto', pos: 'Meia', stone: 1 },
  { name: 'Diego', pos: 'Meia', stone: 1 },
  { name: 'Kauê', pos: 'Meia', stone: 1 },
  { name: 'Maicon', pos: 'Pivô', stone: 1 },
  { name: 'Matheus', pos: 'Pivô', stone: 1 },
  { name: 'Fernando', pos: 'Pivô', stone: 1 },
  { name: 'Baiano', pos: 'Goleiro', stone: 1 },
  { name: 'Darlan', pos: 'Goleiro', stone: 1 },
];

function createPlayerRows(list = initialPlayers) {
  const container = document.getElementById('players-container');
  container.innerHTML = '';
  list.forEach((player, idx) => {
    const row = document.createElement('div');
    row.classList.add('player-row');
      row.innerHTML = `
        <div class="row-number">${idx + 1} -</div>
      <input type="text" value="${player.name}" required>
      <select required>
        ${positions
          .map(p => `<option value="${p}" ${p === player.pos ? 'selected' : ''}>${p}</option>`)
          .join('')}
      </select>
      <select required>
        <option value="1" ${player.stone === 1 ? 'selected' : ''}>Pedra 1</option>
        <option value="2" ${player.stone === 2 ? 'selected' : ''}>Pedra 2</option>
        <option value="3" ${player.stone === 3 ? 'selected' : ''}>Pedra 3</option>
      </select>
    `;
    container.appendChild(row);
  });
}

function shuffle(array) {
  for (let i = array.length - 1; i > 0; i--) {
    const j = Math.floor(Math.random() * (i + 1));
    [array[i], array[j]] = [array[j], array[i]];
  }
}

function sortTeams() {
  const rows = Array.from(document.querySelectorAll('.player-row'));
  const players = [];
  for (const row of rows) {
    const [, nameInput, posSelect, stoneSelect] = row.children;
    const name = nameInput.value.trim();
    const pos = posSelect.value;
    const stone = parseInt(stoneSelect.value, 10);
    if (!name) {
      alert('Preencha todos os nomes.');
      return;
    }
    players.push({ name, pos, stone });
  }

  const groups = {
    'Goleiro': [],
    'Fixo': [],
    'Lateral Direito': [],
    'Lateral Esquerdo': [],
    'Meia': [],
    'Pivô': []
  };

  for (const p of players) {
    groups[p.pos].push(p);
  }

  if (groups['Goleiro'].length !== 2) {
    alert('É necessário informar exatamente 2 goleiros.');
    return;
  }
  for (const pos of positions) {
    if (pos === 'Goleiro') continue;
    if (groups[pos].length !== 3) {
      alert(`A posição ${pos} deve ter exatamente 3 jogadores.`);
      return;
    }
  }

  const teams = [
    { players: [], total: 0 },
    { players: [], total: 0 },
    { players: [], total: 0 }
  ];

  // distribui posições exceto goleiro tentando balancear as pedras
  for (const pos of positions) {
    if (pos === 'Goleiro') continue;
    const arr = groups[pos].slice().sort((a, b) => a.stone - b.stone);
    for (const player of arr) {
      teams.sort((a, b) => a.total - b.total); // time com menor soma recebe o melhor
      teams[0].players.push(player);
      teams[0].total += player.stone;
    }
  }

  // goleiros: apenas dois times recebem um
  const gks = groups['Goleiro'].slice().sort((a, b) => a.stone - b.stone);
  for (const gk of gks) {
    teams.sort((a, b) => a.total - b.total);
    teams[0].players.push(gk);
    teams[0].total += gk.stone;
  }

  displayTeams(teams);
}

function displayTeams(teams) {
  const container = document.getElementById('team-list');
  container.innerHTML = '';
  teams.forEach((team, idx) => {
    const div = document.createElement('div');
    div.classList.add('team');
    const playersHtml = team.players
      .map(p => `<div class="player"><span>${p.name} - ${p.pos}</span> <span class="pedra-${p.stone}">Pedra ${p.stone}</span></div>`)
      .join('');
    div.innerHTML = `<h3>Time ${idx + 1}</h3>${playersHtml}<div class="total">Total de Pedras: ${team.total}</div>`;
    container.appendChild(div);
  });
  document.getElementById('teams').classList.remove('hidden');
}

document.getElementById('draw').addEventListener('click', sortTeams);
document.getElementById('redraw').addEventListener('click', sortTeams);

function savePlayerList() {
  const rows = Array.from(document.querySelectorAll('.player-row'));
  const list = rows.map(row => {
    const [, nameInput, posSelect, stoneSelect] = row.children;
    return {
      name: nameInput.value.trim(),
      pos: posSelect.value,
      stone: parseInt(stoneSelect.value, 10)
    };
  });
  fetch('/list', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ list })
  })
    .then(res => {
      if (!res.ok) throw new Error();
      alert('Lista salva no servidor!');
    })
    .catch(() => alert('Erro ao salvar lista'));
}

function loadSavedList() {
  fetch('/list')
    .then(res => res.json())
    .then(data => {
      if (Array.isArray(data) && data.length) {
        createPlayerRows(data);
      } else {
        createPlayerRows(initialPlayers);
      }
    })
    .catch(() => createPlayerRows(initialPlayers));
}

document.getElementById('save-list').addEventListener('click', savePlayerList);
document.getElementById('load-list').addEventListener('click', loadSavedList);

// Preenche os jogadores assim que o DOM estiver pronto
document.addEventListener('DOMContentLoaded', loadSavedList);
