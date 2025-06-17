let dados = JSON.parse(localStorage.getItem('cryptoDashboard')) || {transacoes:[], ganhos:[]};
let graficoLinha, graficoPizza;

function salvar(){
    localStorage.setItem('cryptoDashboard', JSON.stringify(dados));
}

function showTab(id){
    document.querySelectorAll('.tab').forEach(t => t.style.display='none');
    document.getElementById(id).style.display='block';
    if(id==='dashboard') updateDashboard();
    if(id==='transacoes') renderTransacoes();
    if(id==='taxas') renderTaxas();
    if(id==='rentabilidade') renderGanhos();
}

function adicionarTransacao(e){
    e.preventDefault();
    const t = {
        tipo: document.getElementById('tipo').value,
        data: document.getElementById('data').value,
        moeda: document.getElementById('moeda').value,
        valor: parseFloat(document.getElementById('valor').value),
        origem: document.getElementById('origem').value,
        taxas: parseFloat(document.getElementById('taxas').value||0),
        obs: document.getElementById('obs').value
    };
    dados.transacoes.push(t);
    salvar();
    e.target.reset();
    renderTransacoes();
}

function adicionarGanho(e){
    e.preventDefault();
    const g = {
        moeda: document.getElementById('moedaGanho').value,
        valor: parseFloat(document.getElementById('valorGanho').value)
    };
    dados.ganhos.push(g);
    salvar();
    e.target.reset();
    renderGanhos();
}

function renderTransacoes(){
    const ul = document.getElementById('transacoesSalvas');
    ul.innerHTML='';
    dados.transacoes.forEach((t,i)=>{
        const li=document.createElement('li');
        li.textContent=`${t.data} - ${t.tipo} ${t.moeda} ${t.valor}`;
        const btn=document.createElement('button');
        btn.textContent='Excluir';
        btn.onclick=()=>{dados.transacoes.splice(i,1); salvar(); renderTransacoes();};
        li.appendChild(btn);
        ul.appendChild(li);
    });
}

function renderTaxas(){
    const nao = document.getElementById('taxasNao');
    const reemb = document.getElementById('taxasReemb');
    nao.innerHTML='';
    reemb.innerHTML='';
    dados.transacoes.filter(t=>t.tipo.includes('Taxa')).forEach((t,i)=>{
        const li=document.createElement('li');
        li.textContent=`${t.data} - ${t.tipo} ${t.valor}`;
        if(t.tipo==='Taxa Reembolsável'){
            const btn=document.createElement('button');
            btn.textContent='Marcar Reembolsada';
            btn.onclick=()=>{t.tipo='Taxa Não Reembolsável'; salvar(); renderTaxas();};
            li.appendChild(btn);
            reemb.appendChild(li);
        }else{
            nao.appendChild(li);
        }
    });
}

function renderGanhos(){
    const ul=document.getElementById('listaGanhos');
    ul.innerHTML='';
    dados.ganhos.forEach((g,i)=>{
        const li=document.createElement('li');
        li.textContent=`${g.moeda} +${g.valor}`;
        const btn=document.createElement('button');
        btn.textContent='Excluir';
        btn.onclick=()=>{dados.ganhos.splice(i,1); salvar(); renderGanhos();};
        li.appendChild(btn);
        ul.appendChild(li);
    });
    gerarGraficos();
}

function updateDashboard(){
    const cot=parseFloat(document.getElementById('cotacao').value||1);
    const totals={};
    dados.transacoes.forEach(t=>{
        totals[t.origem]=(totals[t.origem]||0)+(t.valor);
    });
    const div=document.getElementById('totais');
    div.innerHTML='';
    let totalGeral=0;
    for(const k in totals){
        totalGeral+=totals[k];
        const p=document.createElement('p');
        p.textContent=`${k}: ${totals[k]} (${(totals[k]*cot).toFixed(2)} BRL)`;
        div.appendChild(p);
    }
    const p=document.createElement('p');
    p.innerHTML=`<strong>Total Geral: ${totalGeral} USDT (${(totalGeral*cot).toFixed(2)} BRL)</strong>`;
    div.appendChild(p);
    const lista=document.getElementById('listaTransacoes');
    lista.innerHTML='';
    dados.transacoes.slice(-5).reverse().forEach(t=>{
        const li=document.createElement('li');
        li.textContent=`${t.data} - ${t.tipo} ${t.moeda} ${t.valor}`;
        lista.appendChild(li);
    });
    gerarGraficos();
}

function gerarGraficos(){
    const ctx=document.getElementById('graficoLinha');
    const ctxP=document.getElementById('graficoPizza');
    const datas=dados.transacoes.map(t=>t.data);
    const valores=dados.transacoes.map(t=>t.valor);
    if(graficoLinha){graficoLinha.destroy();}
    graficoLinha=new Chart(ctx,{type:'line',data:{labels:datas,datasets:[{label:'Valores',data:valores,borderColor:'blue'}]}});
    const ganhosPorMoeda={};
    dados.ganhos.forEach(g=>{ganhosPorMoeda[g.moeda]=(ganhosPorMoeda[g.moeda]||0)+g.valor;});
    if(graficoPizza){graficoPizza.destroy();}
    graficoPizza=new Chart(ctxP,{type:'pie',data:{labels:Object.keys(ganhosPorMoeda),datasets:[{data:Object.values(ganhosPorMoeda)}]}});
}

function exportData(){
    const dataStr="data:text/json;charset=utf-8,"+encodeURIComponent(JSON.stringify(dados));
    const link=document.createElement('a');
    link.setAttribute('href',dataStr);
    link.setAttribute('download','dados.json');
    link.click();
}

showTab('dashboard');
