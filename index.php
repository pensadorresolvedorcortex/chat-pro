<?php
$players = [
    ["name" => "Dheniell"],
    ["name" => "Dario"],
    ["name" => "Papel"],
    ["name" => "Wallace"],
    ["name" => "Matheus"],
    ["name" => "Kloh"],
    ["name" => "Bebeto"],
    ["name" => "Custela"],
    ["name" => "Diego"],
    ["name" => "Matheus MP"],
    ["name" => "Gabriel"],
    ["name" => "Bolo"],
    ["name" => "Geisel"],
    ["name" => "Caputo"],
    ["name" => "Fred"],
    ["name" => "Darlan"],
    ["name" => "Baiano"],
];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Sorteio de Times</title>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
</head>
<body>
<header>
    <img src="https://raw.githubusercontent.com/pensadorresolvedorcortex/chat-pro/main/icone.png" alt="Logo" id="logo">
</header>
<table id="tabela-jogadores">
<thead>
<tr><th>Nº</th><th>Nome</th><th>Posição</th><th>Pedra</th></tr>
</thead>
<tbody>
<?php foreach ($players as $i => $p): ?>
<tr>
<td><?= $i+1 ?></td>
<td contenteditable="true" class="editable nome"><?= $p['name'] ?></td>
<td>
<select class="posicao">
<option value="">--Selecione--</option>
<option>Goleiro</option>
<option>Fixo</option>
<option>LD</option>
<option>LE</option>
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
</select>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<div class="botoes">
<button id="sortear">Sortear Times</button>
<button id="resetar">Resetar Tabela</button>
<button id="salvar">Salvar Times</button>
<button id="limpar">Resetar Times Sorteados</button>
</div>
<div id="resultado"></div>
<script src="script.js"></script>
</body>
</html>
