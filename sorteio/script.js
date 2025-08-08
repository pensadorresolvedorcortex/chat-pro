const tabela = document.getElementById('tabela-jogadores');
const sortearBtn = document.getElementById('sortear');
const resetarBtn = document.getElementById('resetar');
const salvarBtn = document.getElementById('salvar');
const limparBtn = document.getElementById('limpar');
const carregarBtn = document.getElementById('carregar');
const resultadoDiv = document.getElementById('resultado');

function resetarTabela() {
  const rows = tabela.querySelectorAll('tbody tr');
  rows.forEach(r => {
    r.querySelector('.nome').textContent = r.dataset.nome;
    r.querySelector('.posicao').value = '';
    r.querySelector('.pedra').value = '';
  });
  localStorage.removeItem('times');
  resultadoDiv.innerHTML = '';
}

function coletarDados() {
  const rows = tabela.querySelectorAll('tbody tr');
  let jogadores = [];
  rows.forEach((r) => {
    const nome = r.querySelector('.nome').textContent.trim();
    const pos = r.querySelector('.posicao').value;
    const pedra = r.querySelector('.pedra').value;
    jogadores.push({ nome, posicao: pos, pedra });
  });
  return jogadores;
}

function exibirTimes(data) {
  resultadoDiv.innerHTML = '';
  data.times.forEach((time, idx) => {
    const div = document.createElement('div');
    div.className = 'time fade-in';
    const titulo = `Time ${idx + 1}`;
    div.innerHTML = `<h3>${titulo}</h3><ul>` +
      time.map(j => `<li>${j.nome} - ${j.posicao} - Pedra ${j.pedra}</li>`).join('') +
      '</ul>';
    resultadoDiv.appendChild(div);
  });
}

sortearBtn.addEventListener('click', () => {
  fetch('sort.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(coletarDados())
  })
    .then(r => r.json())
    .then(data => {
      if (data.error) {
        alert(data.error);
        return;
      }
      exibirTimes(data);
      localStorage.setItem('times', resultadoDiv.innerHTML);
    })
    .catch(() => alert('Erro ao sortear times'));
});

resetarBtn.addEventListener('click', resetarTabela);
limparBtn.addEventListener('click', ()=>{ resultadoDiv.innerHTML=''; });

salvarBtn.addEventListener('click', () => {
  fetch('save.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(coletarDados())
  })
    .then(() => alert('Jogadores salvos!'))
    .catch(() => alert('Falha ao salvar jogadores'));
});

carregarBtn.addEventListener('click', () => {
  fetch('load.php')
    .then(r => r.json())
    .then(dados => {
      if (!Array.isArray(dados) || dados.length === 0) {
        alert('Nenhum time salvo');
        return;
      }
      const rows = tabela.querySelectorAll('tbody tr');
      dados.forEach((j, i) => {
        if (rows[i]) {
          rows[i].querySelector('.nome').textContent = j.nome;
          rows[i].querySelector('.posicao').value = j.posicao;
          rows[i].querySelector('.pedra').value = j.pedra;
          rows[i].dataset.nome = j.nome;
        }
      });
    })
    .catch(() => alert('Erro ao carregar jogadores'));
});

window.addEventListener('load', () => {
  const saved = localStorage.getItem('times');
  if (saved) {
    resultadoDiv.innerHTML = saved;
    resultadoDiv.querySelectorAll('.time').forEach(t => t.classList.add('fade-in'));
  }
  const rows = tabela.querySelectorAll('tbody tr');
  rows.forEach(r => { r.dataset.nome = r.querySelector('.nome').textContent; });
});
