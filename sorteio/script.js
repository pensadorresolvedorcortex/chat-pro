function init() {
  const tabela = document.getElementById('tabela-jogadores');
  const sortearBtn = document.getElementById('sortear');
  const salvarBtn = document.getElementById('salvar');
  const limparBtn = document.getElementById('limpar');
  const carregarBtn = document.getElementById('carregar');
  const resultadoDiv = document.getElementById('resultado');
  const contadorGoleiros = document.getElementById('contador-goleiros');
  const portelaBtn = document.getElementById('portela');
  const resenhaBtn = document.getElementById('resenha');
  const logoImg = document.getElementById('logo');

  const TOTAL_JOGADORES = 18;

  function carregarLista(nomes, manterResultado = false) {
    const tbody = tabela.querySelector('tbody');
    tbody.innerHTML = '';
    const totalLinhas = Math.max(nomes.length, TOTAL_JOGADORES);
    for (let i = 0; i < totalLinhas; i++) {
      const nome = nomes[i] || '';
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td>${i + 1}</td>
        <td contenteditable="true" class="editable nome">${nome}</td>
        <td>
          <select class="posicao">
            <option value="">--Selecione--</option>
            <option>Goleiro</option>
            <option>Fixo</option>
            <option>Lateral Direito</option>
            <option>Lateral Esquerdo</option>
            <option>Meia</option>
            <option>Pivô</option>
          </select>
        </td>
        <td>
          <select class="pedra">
            <option value="">--Selecione--</option>
            <option value="1" class="p1">Pedra 1</option>
            <option value="2" class="p2">Pedra 2</option>
            <option value="3" class="p3">Pedra 3</option>
            <option value="4" class="p4">Pedra 4</option>
            <option value="5" class="p5">Pedra 5</option>
            <option value="6" class="p6">Pedra 6</option>
          </select>
        </td>`;
      tbody.appendChild(tr);
    }
    atualizarContadorGoleiros();
    if (!manterResultado) {
      localStorage.removeItem('times');
      resultadoDiv.innerHTML = '';
    }
  }

  function atualizarContadorGoleiros() {
    if (!contadorGoleiros) return;
    const rows = tabela.querySelectorAll('tbody tr');
    let total = 0;
    rows.forEach((r) => {
      const posicao = r.querySelector('.posicao');
      if (posicao && posicao.value === 'Goleiro') total++;
    });
    contadorGoleiros.textContent = `Goleiros selecionados: ${total}/3`;
    contadorGoleiros.classList.toggle('alerta', total < 3);
  }

  function coletarDados() {
    const rows = tabela.querySelectorAll('tbody tr');
    let jogadores = [];
    rows.forEach((r) => {
      const nome = r.querySelector('.nome').textContent.trim();
      const pos = r.querySelector('.posicao').value;
      const pedra = r.querySelector('.pedra').value;
      if (!nome) return; // ignora linhas vazias
      jogadores.push({
        nome,
        posicao: pos || 'Linha',
        pedra: pedra || '6'
      });
    });
    atualizarContadorGoleiros();
    return jogadores;
  }

  function exibirTimes(data) {
    resultadoDiv.innerHTML = '';
    let textoCompartilhar = '';
    data.times.forEach((time, idx) => {
      const div = document.createElement('div');
      div.className = 'time fade-in';
      const titulo = `Time ${idx + 1}`;
      div.innerHTML = `<h3>${titulo}</h3><ul>` +
        time.map(j => `<li>${j.nome} - ${j.posicao} - Pedra ${j.pedra}</li>`).join('') +
        '</ul>';
      resultadoDiv.appendChild(div);
      textoCompartilhar += `${titulo}:\n` +
        time.map(j => `${j.nome} - ${j.posicao} - Pedra ${j.pedra}`).join('\n') +
        '\n\n';
    });
    const share = document.createElement('a');
    share.id = 'share';
    share.target = '_blank';
    share.className = 'share-btn';
    share.href = `https://wa.me/?text=${encodeURIComponent(textoCompartilhar.trim())}`;
    share.innerHTML = '<i class="fa-brands fa-whatsapp"></i> Compartilhar no WhatsApp';
    resultadoDiv.appendChild(share);
  }

  if (sortearBtn) {
    sortearBtn.addEventListener('click', () => {
      fetch('sort.php', {
        method: 'POST',
        cache: 'no-store',
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
  }

  if (limparBtn) {
    limparBtn.addEventListener('click', () => {
      resultadoDiv.innerHTML = '';
      localStorage.removeItem('times');
    });
  }

  if (salvarBtn) {
    salvarBtn.addEventListener('click', () => {
      fetch('save.php', {
        method: 'POST',
        cache: 'no-store',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(coletarDados())
      })
        .then(() => alert('Jogadores salvos!'))
        .catch(() => alert('Falha ao salvar jogadores'));
    });
  }

  if (carregarBtn) {
    carregarBtn.addEventListener('click', () => {
      fetch('load.php', { cache: 'no-store' })
        .then(r => r.json())
        .then(dados => {
          if (!Array.isArray(dados) || dados.length === 0) {
            alert('Nenhum time salvo');
            return;
          }
          carregarLista(dados.map(j => j.nome));
          const rows = tabela.querySelectorAll('tbody tr');
          dados.forEach((j, i) => {
            if (rows[i]) {
              rows[i].querySelector('.posicao').value = j.posicao;
              rows[i].querySelector('.pedra').value = j.pedra;
            }
          });
          atualizarContadorGoleiros();
        })
        .catch(() => alert('Erro ao carregar jogadores'));
    });
  }

  const jogadoresPortela = [
    'Dheniell','Dario','Papel','Wallace','Matheus','Kloh','Bebeto',
    'Costela','Diego','Matheus MP','Gabriel','Bolo','Flavinho','Caputo',
    'Fred','Darlan','Baiano'
  ];

  const jogadoresResenha = [
    'Dheniell','Felipe','Gabriel','Lucas','Bahia','Bilico','Quelotti',
    'Ricardo','Pepe','Pedron','Rezende','Schittini','Tai','Dani',
    'Nicolato','Rodolfo','Juninho'
  ];

  const cacheBust = `?v=${Date.now()}`;
  const logos = {
    portela: `https://www.studioprivilege.com.br/sorteio/portela.png${cacheBust}`,
    resenha: `https://www.studioprivilege.com.br/sorteio/resenha.png${cacheBust}`
  };

  function atualizarLogo(src, alt) {
    if (logoImg) {
      logoImg.src = src;
      logoImg.alt = alt;
    }
  }

  if (portelaBtn) {
    portelaBtn.addEventListener('click', () => {
      carregarLista(jogadoresPortela);
      atualizarLogo(logos.portela, 'Portela');
    });
  }

  if (resenhaBtn) {
    resenhaBtn.addEventListener('click', () => {
      carregarLista(jogadoresResenha);
      atualizarLogo(logos.resenha, 'Resenha Sagrada');
    });
  }

  tabela.addEventListener('change', (event) => {
    if (event.target && event.target.classList.contains('posicao')) {
      atualizarContadorGoleiros();
    }
  });

  carregarLista(jogadoresResenha, true);
  atualizarLogo(logos.resenha, 'Resenha Sagrada');
  const saved = localStorage.getItem('times');
  if (saved) {
    resultadoDiv.innerHTML = saved;
    resultadoDiv.querySelectorAll('.time').forEach(t => t.classList.add('fade-in'));
  }
  atualizarContadorGoleiros();
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', init);
} else {
  init();
}

