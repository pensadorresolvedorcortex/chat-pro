const positions = [
  'Goleiro',
  'Fixo',
  'Lateral Direito',
  'Lateral Esquerdo',
  'Meia',
  'Pivô'
];

function createPlayerRows() {
  const container = document.getElementById('players-container');
  for (let i = 0; i < 17; i++) {
    const row = document.createElement('div');
    row.classList.add('player-row');
    row.innerHTML = `
      <input type="text" placeholder="Nome" required>
      <select required>
        ${positions.map(p => `<option value="${p}">${p}</option>`).join('')}
      </select>
      <select required>
        <option value="1">Pedra 1</option>
        <option value="2">Pedra 2</option>
        <option value="3">Pedra 3</option>
      </select>
    `;
    container.appendChild(row);
  }
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
    const [nameInput, posSelect, stoneSelect] = row.children;
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
createPlayerRows();

window.addEventListener('load', () => {
  const pre = document.getElementById('preloader');
  if (pre) pre.style.display = 'none';
});
