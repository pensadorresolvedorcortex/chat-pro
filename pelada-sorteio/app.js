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

function salvarJogadores() {
  localStorage.setItem('jogadores', JSON.stringify(jogadores));
}

function resetarJogadores() {
  jogadores = defaultJogadores.slice();
  salvarJogadores();
  renderTabela();
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

function sortearTimes() {
  const grupos = agruparPorPosicao(jogadores);
  const times = [[], [], []];
  const posicoes = ['Goleiro', 'Fixo', 'LD', 'LE', 'MeiaPivo'];

  posicoes.forEach(pos => {
    const lista = grupos[pos];
    if (!lista || lista.length === 0) return;
    lista.sort((a, b) => a.pedra - b.pedra);
    const start = Math.floor(Math.random() * 3);
    for (let i = 0; i < 3; i++) {
      const jogador = lista[i];
      if (jogador) {
        const idxTime = (start + i) % 3;
        times[idxTime].push(jogador);
      }
    }
  });

  exibirResultado(times);
}

function exibirResultado(times) {
  const div = document.getElementById('resultado');
  div.innerHTML = '';
  times.forEach((time, idx) => {
    const ul = document.createElement('ul');
    ul.innerHTML = `<strong>Time ${idx + 1}</strong>`;
    time.forEach(j => {
      const li = document.createElement('li');
      li.textContent = `${j.nome} - ${j.posicao} (Pedra ${j.pedra})`;
      ul.appendChild(li);
    });
    div.appendChild(ul);
  });
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
