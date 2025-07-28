<?php
$default_players = [
    "Dheniell", "Dario", "Papel", "Wallace", "Matheus", "Kloh",
    "Bebeto", "Custela", "Diego", "Matheus MP", "Gabriel", "Bolo",
    "Geisel", "Caputo", "Fred", "Darlan", "Baiano"
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sorteio de Times</title>
<link rel="stylesheet" href="style.css">
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;700&display=swap" rel="stylesheet">
</head>
<body>
<div class="container">
    <h1>Sistema de Sorteio de Jogadores</h1>
    <table id="tabela-jogadores">
        <thead>
            <tr>
                <th>Nº</th>
                <th>Nome do Jogador</th>
                <th>Posição</th>
                <th>Pedra</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($default_players as $i => $nome): ?>
            <tr>
                <td><?php echo $i+1; ?></td>
                <td><input type="text" value="<?php echo htmlspecialchars($nome); ?>" class="nome-input" aria-label="Nome do Jogador"></td>
                <td>
                    <select class="posicao-select" aria-label="Posição">
                        <option value="">--</option>
                        <option>Goleiro</option>
                        <option>Fixo</option>
                        <option>Lateral Direito</option>
                        <option>Lateral Esquerdo</option>
                        <option>Meia</option>
                        <option>Pivô</option>
                    </select>
                </td>
                <td>
                    <select class="pedra-select" aria-label="Pedra">
                        <option value="">--</option>
                        <option value="1">Pedra 1</option>
                        <option value="2">Pedra 2</option>
                        <option value="3">Pedra 3</option>
                    </select>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <div class="botoes">
        <button id="sortear">Sortear Times</button>
        <button id="resetar">Resetar Tabela</button>
        <button id="carregar">Carregar Semana Anterior</button>
        <button id="salvar">Salvar Times</button>
        <button id="resetar-times">Resetar Times Sorteados</button>
        <button id="baixar-pdf">Baixar PDF</button>
    </div>
    <div id="resultado" class="times"></div>
</div>
<script src="script.js"></script>
</body>
</html>
