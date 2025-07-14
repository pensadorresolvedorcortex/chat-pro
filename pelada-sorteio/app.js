// Aplicação desenvolvida para o repositório Chat-Pro
const defaultJogadores = [
  { nome: 'Dheniell', posicao: 'Goleiro', pedra: 1 },
  { nome: 'Dario', posicao: 'Fixo', pedra: 1 },
  { nome: 'Papel', posicao: 'LD', pedra: 2 },
  { nome: 'Wallace', posicao: 'LE', pedra: 2 },
  { nome: 'Matheus', posicao: 'Meia', pedra: 2 },
  { nome: 'Kloh', posicao: 'Piv\u00f4', pedra: 2 },
  { nome: 'Bebeto', posicao: 'LD', pedra: 3 },
  { nome: 'Custela', posicao: 'LE', pedra: 3 },
  { nome: 'Diego', posicao: 'Fixo', pedra: 2 },
  { nome: 'Matheus MP', posicao: 'Meia', pedra: 1 },
  { nome: 'Gabriel', posicao: 'Piv\u00f4', pedra: 2 },
  { nome: 'Bolo', posicao: 'LD', pedra: 2 },
  { nome: 'Geisel', posicao: 'LE', pedra: 2 },
  { nome: 'Caputo', posicao: 'Fixo', pedra: 3 },
  { nome: 'Fred', posicao: 'Meia', pedra: 2 },
  { nome: 'Darlan', posicao: 'Piv\u00f4', pedra: 3 },
  { nome: 'Baiano', posicao: 'Goleiro', pedra: 2 }
];

let jogadores = JSON.parse(localStorage.getItem('jogadores')) || defaultJogadores.slice();
let timesAtuais = JSON.parse(localStorage.getItem('times')) || [];
let reservasAtuais = JSON.parse(localStorage.getItem('reservas')) || [];

function salvarJogadores() {
  localStorage.setItem('jogadores', JSON.stringify(jogadores));
  localStorage.setItem('times', JSON.stringify(timesAtuais));
  localStorage.setItem('reservas', JSON.stringify(reservasAtuais));
}

function resetarJogadores() {
  jogadores = defaultJogadores.slice();
  timesAtuais = [];
  reservasAtuais = [];
  salvarJogadores();
  renderTabela();
  document.getElementById('resultado').innerHTML = '';
}

function renderTabela() {
  const tbody = document.querySelector('#tabela-jogadores tbody');
  tbody.innerHTML = '';
  jogadores.forEach((jog, idx) => {
    const tr = document.createElement('tr');

    const tdNum = document.createElement('td');
    tdNum.textContent = idx + 1;

    const tdNome = document.createElement('td');
    const inputNome = document.createElement('input');
    inputNome.value = jog.nome;
    inputNome.addEventListener('change', e => {
      jog.nome = e.target.value;
    });
    tdNome.appendChild(inputNome);

    const tdPos = document.createElement('td');
    const selPos = document.createElement('select');
    ['Goleiro', 'Fixo', 'LD', 'LE', 'Meia', 'Piv\u00f4'].forEach(opt => {
      const o = document.createElement('option');
      o.value = opt;
      o.textContent = opt;
      if (opt === jog.posicao) o.selected = true;
      selPos.appendChild(o);
    });
    selPos.addEventListener('change', e => {
      jog.posicao = e.target.value;
    });
    tdPos.appendChild(selPos);

    const tdPedra = document.createElement('td');
    const selPedra = document.createElement('select');
    [1, 2, 3].forEach(n => {
      const o = document.createElement('option');
      o.value = n;
      o.textContent = 'Pedra ' + n;
      if (n === jog.pedra) o.selected = true;
      selPedra.appendChild(o);
    });
    selPedra.addEventListener('change', e => {
      jog.pedra = parseInt(e.target.value);
      tr.className = 'pedra-' + jog.pedra;
    });
    tdPedra.appendChild(selPedra);

    tr.className = 'pedra-' + jog.pedra;

    tr.appendChild(tdNum);
    tr.appendChild(tdNome);
    tr.appendChild(tdPos);
    tr.appendChild(tdPedra);

    tbody.appendChild(tr);
  });
}

function agruparPorPosicao(jogs) {
  const grupos = {
    Goleiro: [],
    Fixo: [],
    LD: [],
    LE: [],
    MeiaPivo: []
  };
  jogs.forEach(j => {
    if (j.posicao === 'Goleiro') grupos.Goleiro.push(j);
    else if (j.posicao === 'Fixo') grupos.Fixo.push(j);
    else if (j.posicao === 'LD') grupos.LD.push(j);
    else if (j.posicao === 'LE') grupos.LE.push(j);
    else if (j.posicao === 'Meia' || j.posicao === 'Piv\u00f4') grupos.MeiaPivo.push(j);
  });
  return grupos;
}

function embaralhar(arr) {
  for (let i = arr.length - 1; i > 0; i--) {
    const j = Math.floor(Math.random() * (i + 1));
    [arr[i], arr[j]] = [arr[j], arr[i]];
  }
  return arr;
}

function sortearTimes() {
  if (jogadores.length !== 17) {
    alert('O sorteio requer exatamente 17 jogadores.');
    return;
  }
  const goleiros = jogadores.filter(j => j.posicao === 'Goleiro');
  if (goleiros.length !== 2) {
    alert('Devem existir exatamente 2 goleiros definidos.');
    return;
  }
  for (const j of jogadores) {
    if (!j.nome || !j.posicao || !j.pedra) {
      alert('Preencha todos os campos antes do sorteio.');
      return;
    }
  }
  const grupos = agruparPorPosicao(jogadores);
  const times = [[], [], []];
  const reservas = [];

  // distribuir fixo, LD, LE e meia/pivô
  ['Fixo', 'LD', 'LE', 'MeiaPivo'].forEach(pos => {
    embaralhar(grupos[pos]);
    for (let i = 0; i < 3; i++) {
      const jogador = grupos[pos].shift();
      if (jogador) times[i].push(jogador);
    }
    if (pos !== 'MeiaPivo') {
      if (grupos[pos].length) reservas.push(...grupos[pos]);
      grupos[pos] = [];
    }
  });

  // distribuir goleiros (somente 2 em campo)
  embaralhar(grupos.Goleiro);
  for (let i = 0; i < 2; i++) {
    const gk = grupos.Goleiro[i];
    if (gk) times[i].push(gk);
  }
  if (grupos.Goleiro.length > 2) reservas.push(...grupos.Goleiro.slice(2));

  // completar time 3 com mais um jogador de meia/pivô
  const extra = grupos.MeiaPivo.shift();
  if (extra) times[2].push(extra);
  if (grupos.MeiaPivo.length) reservas.push(...grupos.MeiaPivo);

  // completar times com reservas até atingir 5 jogadores cada
  times.forEach(time => {
    while (time.length < 5 && reservas.length) {
      time.push(reservas.shift());
    }
  });

  timesAtuais = times;
  reservasAtuais = reservas;
  exibirResultado();
}

function exibirResultado() {
  const div = document.getElementById('resultado');
  div.innerHTML = '';
  timesAtuais.forEach((time, idx) => {
    const ul = document.createElement('ul');
    ul.innerHTML = `<strong>\u26BD Time ${idx + 1}</strong>`;
    time.forEach(j => {
      const li = document.createElement('li');
      li.textContent = `${j.nome} - ${j.posicao} (Pedra ${j.pedra})`;
      ul.appendChild(li);
    });
    div.appendChild(ul);
  });

  if (reservasAtuais.length) {
    const ul = document.createElement('ul');
    ul.innerHTML = '<strong>\u26BD Reservas</strong>';
    reservasAtuais.forEach(j => {
      const li = document.createElement('li');
      li.textContent = `${j.nome} - ${j.posicao} (Pedra ${j.pedra})`;
      const sel = document.createElement('select');
      sel.innerHTML = '<option value="">Mover para...</option>' +
        '<option value="0">Time 1</option>' +
        '<option value="1">Time 2</option>' +
        '<option value="2">Time 3</option>';
      sel.addEventListener('change', e => moverReserva(j, parseInt(e.target.value)));
      li.appendChild(sel);
      ul.appendChild(li);
    });
    div.appendChild(ul);
  }
}

function moverReserva(jogador, idxTime) {
  if (isNaN(idxTime)) return;
  reservasAtuais = reservasAtuais.filter(r => r !== jogador);
  timesAtuais[idxTime].push(jogador);
  exibirResultado();
}

document.getElementById('sortear').addEventListener('click', sortearTimes);
document.getElementById('salvar').addEventListener('click', () => {
  salvarJogadores();
  alert('Jogadores salvos.');
});
document.getElementById('resetar').addEventListener('click', () => {
  if (confirm('Deseja resetar para os nomes padr\u00e3o?')) {
    localStorage.removeItem('jogadores');
    resetarJogadores();
  }
});

renderTabela();
if (timesAtuais.length || reservasAtuais.length) {
  exibirResultado();
}
