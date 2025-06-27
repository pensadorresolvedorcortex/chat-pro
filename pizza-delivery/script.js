// Imagens base64 geradas localmente
const imagens = {
  pizza: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KICA8cGF0aCBkPSJNNTAgMTAgTDkwIDkwIEwxMCA5MCBaIiBmaWxsPSIjZjVkMjQ3IiBzdHJva2U9IiNkOTRlMTUiIHN0cm9rZS13aWR0aD0iNSIvPgogIDxjaXJjbGUgY3g9IjUwIiBjeT0iNTAiIHI9IjEwIiBmaWxsPSIjZDk0ZTE1Ii8+CiAgPGNpcmNsZSBjeD0iMzUiIGN5PSI2NSIgcj0iOCIgZmlsbD0iI2Q5NGUxNSIvPgogIDxjaXJjbGUgY3g9IjY1IiBjeT0iNzAiIHI9IjgiIGZpbGw9IiNkOTRlMTUiLz4KPC9zdmc+Cg==',
  complemento: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KICA8cmVjdCB4PSIyMCIgeT0iMjAiIHdpZHRoPSI2MCIgaGVpZ2h0PSI2MCIgcng9IjEwIiBmaWxsPSIjZjdmN2Y3IiBzdHJva2U9IiNkOTRlMTUiIHN0cm9rZS13aWR0aD0iNCIvPgogIDxjaXJjbGUgY3g9IjUwIiBjeT0iNTAiIHI9IjE1IiBmaWxsPSIjZDk0ZTE1Ii8+Cjwvc3ZnPgo=',
  bebida: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KICA8cmVjdCB4PSIzNSIgeT0iMjAiIHdpZHRoPSIzMCIgaGVpZ2h0PSI2MCIgZmlsbD0iIzAwN2JmZiIgc3Ryb2tlPSIjMDE1NzliIiBzdHJva2Utd2lkdGg9IjQiLz4KICA8cmVjdCB4PSI0MCIgeT0iMTUiIHdpZHRoPSIyMCIgaGVpZ2h0PSIxMCIgZmlsbD0iIzhlY2FlNiIgc3Ryb2tlPSIjMDE1NzliIiBzdHJva2Utd2lkdGg9IjQiLz4KPC9zdmc+Cg=='
};

const menuItems = [
  {id:1, categoria:'pizzas', nome:'Pizza Margherita', descricao:'Molho de tomate, mussarela e manjericão.', preco:35.90, img:imagens.pizza},
  {id:2, categoria:'pizzas', nome:'Pizza Calabresa', descricao:'Calabresa e cebola.', preco:38.90, img:imagens.pizza},
  {id:3, categoria:'pizzas', nome:'Pizza Quatro Queijos', descricao:'Blend de queijos especiais.', preco:42.50, img:imagens.pizza},
  {id:4, categoria:'complementos', nome:'BreadSticks', descricao:'Palitos temperados.', preco:15.00, img:imagens.complemento},
  {id:5, categoria:'complementos', nome:'Molho Extra', descricao:'Escolha seu sabor.', preco:5.00, img:imagens.complemento},
  {id:6, categoria:'bebidas', nome:'Refrigerante 2L', descricao:'Diversos sabores.', preco:10.00, img:imagens.bebida},
  {id:7, categoria:'bebidas', nome:'Água Mineral', descricao:'500ml', preco:4.00, img:imagens.bebida}
];

function confirmarCidade(){
  const estado = document.getElementById('estado').value;
  const cidade = document.getElementById('cidade').value;
  if(estado && cidade){
    localStorage.setItem('cidade', cidade);
    document.getElementById('cityModal').style.display='none';
    document.getElementById('mensagem').innerText = 'Estamos disponíveis para sua cidade. Tempo estimado de entrega: 40 a 50 minutos devido à alta demanda.';
    mostrarCategoria('pizzas');
    atualizarCarrinho();
  }
}

function mostrarCategoria(cat){
  const menu = document.getElementById('menu');
  menu.innerHTML = '';
  menuItems.filter(i=>i.categoria===cat).forEach(item=>{
    const div = document.createElement('div');
    div.className='card';
    div.innerHTML = `<img src="${item.img}" alt="${item.nome}">
                     <h3>${item.nome}</h3>
                     <p>${item.descricao}</p>
                     <strong>R$ ${item.preco.toFixed(2)}</strong>
                     <button onclick="adicionar(${item.id})">Adicionar ao Carrinho</button>`;
    menu.appendChild(div);
  });
}

function adicionar(id){
  let carrinho = JSON.parse(localStorage.getItem('carrinho')||'[]');
  const item = menuItems.find(i=>i.id===id);
  carrinho.push(item);
  localStorage.setItem('carrinho', JSON.stringify(carrinho));
  atualizarCarrinho();
}

function atualizarCarrinho(){
  const carrinho = JSON.parse(localStorage.getItem('carrinho')||'[]');
  const aside = document.getElementById('carrinho');
  aside.innerHTML = '<h2>Carrinho</h2>';
  const ul = document.createElement('ul');
  let total = 0;
  carrinho.forEach(item=>{
    const li = document.createElement('li');
    li.textContent = `${item.nome} - R$ ${item.preco.toFixed(2)}`;
    ul.appendChild(li);
    total += item.preco;
  });
  aside.appendChild(ul);
  aside.innerHTML += `<p>Total: R$ ${total.toFixed(2)}</p>`;
  if(carrinho.length){
    const btn = document.createElement('button');
    btn.textContent='Finalizar Pedido';
    btn.onclick=()=> location.href='checkout.html';
    aside.appendChild(btn);
  }
}

if(document.getElementById('checkoutForm')){
  const carrinho = JSON.parse(localStorage.getItem('carrinho')||'[]');
  if(!carrinho.length) location.href='index.html';
  document.getElementById('checkoutForm').addEventListener('submit',e=>{
    e.preventDefault();
    const nome=document.getElementById('nome').value;
    const endereco=document.getElementById('endereco').value;
    const pagamento=document.querySelector('input[name=pagamento]:checked').value;
    const total=carrinho.reduce((t,i)=>t+i.preco,0);
    localStorage.setItem('pedido', JSON.stringify({nome,endereco,pagamento,total,items:carrinho}));
    localStorage.removeItem('carrinho');
    location.href='confirmacao.html';
  });
}

if(document.getElementById('resumo')){
  const pedido = JSON.parse(localStorage.getItem('pedido'));
  if(!pedido) location.href='index.html';
  const div = document.getElementById('resumo');
  div.innerHTML = `<p>Obrigado, ${pedido.nome}!</p>
    <p>Endereço: ${pedido.endereco}</p>
    <p>Pagamento: ${pedido.pagamento}</p>
    <ul>` + pedido.items.map(i=>`<li>${i.nome} - R$ ${i.preco.toFixed(2)}</li>`).join('') + `</ul>
    <strong>Total: R$ ${pedido.total.toFixed(2)}</strong>
    <p>Previsão de entrega: 40 a 50 minutos.</p>`;
}

window.onload = function(){
  if(document.getElementById('cityModal')){
    if(localStorage.getItem('cidade')){
      document.getElementById('cityModal').style.display='none';
      document.getElementById('mensagem').innerText = 'Estamos disponíveis para sua cidade. Tempo estimado de entrega: 40 a 50 minutos devido à alta demanda.';
      mostrarCategoria('pizzas');
      atualizarCarrinho();
    }
  }
}
