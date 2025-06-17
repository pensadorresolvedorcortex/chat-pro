const incomeForm=document.getElementById('incomeForm');
const expenseForm=document.getElementById('expenseForm');
const transTable=document.querySelector('#transTable tbody');
const balanceEl=document.getElementById('balance');
const filterMonth=document.getElementById('filterMonth');
const filterYear=document.getElementById('filterYear');
const filterType=document.getElementById('filterType');
const filterCategory=document.getElementById('filterCategory');
const applyFilters=document.getElementById('applyFilters');
let transactions=JSON.parse(localStorage.getItem('transactions')||'[]');

function save(){localStorage.setItem('transactions',JSON.stringify(transactions));}

function addTransaction(t){transactions.push(t);save();render();}

function calcBalance(){return transactions.reduce((sum,t)=>t.type==='income'?sum+Number(t.value):sum-Number(t.value),0);}

function renderFilters(){const months=['','01','02','03','04','05','06','07','08','09','10','11','12'];filterMonth.innerHTML=months.map((m,i)=>`<option value="${m}">${i?m:''}</option>`).join('');const years=[...new Set(transactions.map(t=>t.date.slice(0,4)))];filterYear.innerHTML=['',...years].map(y=>`<option value="${y}">${y}</option>`).join('');const cats=[...new Set(transactions.map(t=>t.category))];filterCategory.innerHTML=['',...cats].map(c=>`<option>${c}</option>`).join('');}

function renderTable(list){transTable.innerHTML=list.map(t=>`<tr><td>${t.date}</td><td>${t.description}</td><td>${t.category}</td><td>${t.type==='income'?'Receita':'Despesa'}</td><td>${Number(t.value).toFixed(2)}</td></tr>`).join('');}

function filterTransactions(){let list=[...transactions];if(filterMonth.value)list=list.filter(t=>t.date.slice(5,7)===filterMonth.value);if(filterYear.value)list=list.filter(t=>t.date.slice(0,4)===filterYear.value);if(filterType.value)list=list.filter(t=>t.type===filterType.value);if(filterCategory.value)list=list.filter(t=>t.category===filterCategory.value);renderTable(list);}

function renderCharts(){const ctx1=document.getElementById('summaryChart');const ctx2=document.getElementById('expenseChart');if(window.sumChart)window.sumChart.destroy();if(window.expChart)window.expChart.destroy();const month=new Date().toISOString().slice(0,7);const incomes=transactions.filter(t=>t.type==='income'&&t.date.startsWith(month)).reduce((s,t)=>s+Number(t.value),0);const expenses=transactions.filter(t=>t.type==='expense'&&t.date.startsWith(month)).reduce((s,t)=>s+Number(t.value),0);window.sumChart=new Chart(ctx1,{type:'bar',data:{labels:['Receitas','Despesas'],datasets:[{data:[incomes,expenses],backgroundColor:['#4caf50','#f44336']}]},options:{plugins:{legend:{display:false}},scales:{y:{beginAtZero:true}}}});
const byCat={};transactions.filter(t=>t.type==='expense'&&t.date.startsWith(month)).forEach(t=>{byCat[t.category]=(byCat[t.category]||0)+Number(t.value);});window.expChart=new Chart(ctx2,{type:'pie',data:{labels:Object.keys(byCat),datasets:[{data:Object.values(byCat),backgroundColor:['#2196f3','#9c27b0','#ff9800','#009688','#e91e63','#3f51b5','#795548','#607d8b']}]}});
}

function render(){balanceEl.textContent=calcBalance().toFixed(2);renderFilters();filterTransactions();renderCharts();}

incomeForm.addEventListener('submit',e=>{e.preventDefault();addTransaction({type:'income',value:incomeForm.incomeValue.value,description:incomeForm.incomeDesc.value,date:incomeForm.incomeDate.value,category:incomeForm.incomeCategory.value});incomeForm.reset();});
expenseForm.addEventListener('submit',e=>{e.preventDefault();addTransaction({type:'expense',value:expenseForm.expenseValue.value,description:expenseForm.expenseDesc.value,date:expenseForm.expenseDate.value,category:expenseForm.expenseCategory.value});expenseForm.reset();});
applyFilters.addEventListener('click',filterTransactions);
render();
