const tabela = document.getElementById('tabela-jogadores');
const sortearBtn = document.getElementById('sortear');
const resetarBtn = document.getElementById('resetar');
const salvarBtn = document.getElementById('salvar');
const limparBtn = document.getElementById('limpar');
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
  rows.forEach((r,i)=>{
    const nome = r.querySelector('.nome').textContent.trim();
    const pos = r.querySelector('.posicao').value;
    const pedra = r.querySelector('.pedra').value;
    jogadores.push({nome, posicao:pos, pedra: parseInt(pedra)});
  });
  return jogadores;
}

function exibirTimes(data) {
  resultadoDiv.innerHTML = '';
  data.times.forEach((time, idx)=>{
    const div = document.createElement('div');
    div.className='time';
    div.innerHTML = `<h3>Time ${idx+1}</h3><ul>` +
      time.map(j=>`<li>${j.nome} - ${j.posicao} - Pedra ${j.pedra}</li>`).join('')+
      '</ul>';
    resultadoDiv.appendChild(div);
  });
  if(data.extras.length){
    const div=document.createElement('div');
    div.className='time';
    div.innerHTML='<h3>Reservas</h3><ul>'+
      data.extras.map(j=>`<li>${j.nome} - ${j.posicao} - Pedra ${j.pedra}</li>`).join('')+
      '</ul>';
    resultadoDiv.appendChild(div);
  }
}

sortearBtn.addEventListener('click', ()=>{
  fetch('sort.php', {
    method:'POST',
    headers:{'Content-Type':'application/json'},
    body: JSON.stringify(coletarDados())
  }).then(r=>r.json()).then(exibirTimes);
});

resetarBtn.addEventListener('click', resetarTabela);
limparBtn.addEventListener('click', ()=>{ resultadoDiv.innerHTML=''; });

salvarBtn.addEventListener('click', ()=>{
  const times = resultadoDiv.innerHTML;
  localStorage.setItem('times', times);
});

window.addEventListener('load',()=>{
  const saved = localStorage.getItem('times');
  if(saved) resultadoDiv.innerHTML = saved;
  const rows = tabela.querySelectorAll('tbody tr');
  rows.forEach(r => { r.dataset.nome = r.querySelector('.nome').textContent; });
});
