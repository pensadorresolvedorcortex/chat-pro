function getPlayers() {
    const rows = document.querySelectorAll('#tabela-jogadores tbody tr');
    const players = [];
    rows.forEach(row => {
        const name = row.querySelector('.nome-input').value.trim();
        const position = row.querySelector('.posicao-select').value;
        const stone = row.querySelector('.pedra-select').value;
        players.push({name, position, stone});
    });
    return players;
}
function validate(players) {
    if (players.length !== 17) {
        alert('É necessário ter 17 jogadores.');
        return false;
    }
    const goleiros = players.filter(p=>p.position==='Goleiro');
    if (goleiros.length !== 2) {
        alert('Devem haver exatamente 2 goleiros.');
        return false;
    }
    for (const p of players) {
        if(!p.name || !p.position || !p.stone) {
            alert('Todos os campos devem estar preenchidos.');
            return false;
        }
    }
    return true;
}
function sortearTimes(players) {
    const teams = [[],[],[]];
    const reserves = [];
    const positions = {
        'Goleiro': [],
        'Fixo': [],
        'Lateral Direito': [],
        'Lateral Esquerdo': [],
        'Meia': [],
        'Pivô': []
    };
    players.forEach(p=>positions[p.position].push(p));
    for (const key in positions) {
        positions[key].sort((a,b)=>a.stone-b.stone);
    }
    // Goleiros para time 0 e 1
    for(let i=0;i<2;i++){
        if(positions['Goleiro'][i]) teams[i].push(positions['Goleiro'][i]);
    }
    // Distribuir outras posições
    const cycleAssign = (posArr)=>{
        let t = 0;
        posArr.forEach(p=>{
            if(teams[t].length<5){
                teams[t].push(p);
                t=(t+1)%3;
            }else{
                reserves.push(p);
            }
        });
    };
    cycleAssign(positions['Fixo']);
    cycleAssign(positions['Lateral Direito']);
    cycleAssign(positions['Lateral Esquerdo']);
    cycleAssign([...positions['Meia'], ...positions['Pivô']]);
    // Preencher faltantes
    const rest = [];
    for(const key in positions){
        positions[key].forEach(p=>{
            if(!teams.some(t=>t.includes(p)) && !reserves.includes(p)) rest.push(p);
        });
    }
    rest.forEach(p=>{
        const t = teams.sort((a,b)=>a.length-b.length)[0];
        if(t.length<5) t.push(p); else reserves.push(p);
    });
    return {teams,reserves};
}
function renderTeams(data){
    const container=document.getElementById('resultado');
    container.innerHTML='';
    data.teams.forEach((team,i)=>{
        const div=document.createElement('div');
        div.className='time';
        div.innerHTML=`<h3>Time ${i+1}</h3>`+team.map(p=>`<div>${p.name} - ${p.position} - Pedra ${p.stone}</div>`).join('');
        container.appendChild(div);
    });
    if(data.reserves.length){
        const r=document.createElement('div');
        r.className='reservas';
        r.innerHTML='<h3>Reservas</h3>'+data.reserves.map(p=>`<div>${p.name}</div>`).join('');
        container.appendChild(r);
    }
}

document.getElementById('sortear').addEventListener('click',()=>{
    const players = getPlayers();
    if(!validate(players)) return;
    const result = sortearTimes(players);
    renderTeams(result);
});

document.getElementById('resetar').addEventListener('click',()=>{
    location.reload();
});

document.getElementById('resetar-times').addEventListener('click',()=>{
    document.getElementById('resultado').innerHTML='';
});

document.getElementById('salvar').addEventListener('click',()=>{
    const players = getPlayers();
    localStorage.setItem('jogadores', JSON.stringify(players));
    alert('Times salvos localmente.');
});

window.addEventListener('load',()=>{
    const saved = localStorage.getItem('jogadores');
    if(saved){
        const players = JSON.parse(saved);
        const rows = document.querySelectorAll('#tabela-jogadores tbody tr');
        players.forEach((p,i)=>{
            if(rows[i]){
                rows[i].querySelector('.nome-input').value=p.name;
                rows[i].querySelector('.posicao-select').value=p.position;
                rows[i].querySelector('.pedra-select').value=p.stone;
            }
        });
    }
});

document.getElementById('baixar-pdf').addEventListener('click',()=>{
    const printContents = document.getElementById('resultado').innerHTML;
    const newWin = window.open('', '', 'width=900,height=700');
    newWin.document.write('<html><head><title>Times</title>');
    newWin.document.write('<link rel="stylesheet" href="style.css">');
    newWin.document.write('</head><body>');
    newWin.document.write(printContents);
    newWin.document.write('</body></html>');
    newWin.document.close();
    newWin.print();
    newWin.close();
});

