<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sorteio de Times</title>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<link rel="stylesheet" href="style.css">
</head>
<body>
<header>
    <h1><i class="fa-solid fa-futbol logo-icon"></i> Sorteio de Times</h1>
</header>
<div class="table-container">
<table id="tabela-jogadores">
<thead>
<tr><th>Nº</th><th>Nome</th><th>Posição</th><th>Pedra</th></tr>
</thead>
<tbody></tbody>
</table>
</div>
<div class="botoes">
<button id="portela"><i class="fa-solid fa-users"></i> Portela</button>
<button id="resenha"><i class="fa-solid fa-users"></i> Resenha Sagrada</button>
<button id="sortear"><i class="fa-solid fa-shuffle"></i> Sortear Times</button>
<button id="salvar"><i class="fa-solid fa-floppy-disk"></i> Salvar Times</button>
<button id="carregar"><i class="fa-solid fa-clock-rotate-left"></i> Jogadores Semana Passada</button>
<button id="limpar"><i class="fa-solid fa-trash"></i> Resetar Times Sorteados</button>
</div>
<div id="resultado"></div>
<script src="script.js"></script>
</body>
</html>
