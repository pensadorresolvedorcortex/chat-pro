<?php
header('Content-Type: application/json');
$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data) || count($data) !== 17) {
    echo json_encode(['error' => 'Dados inválidos']);
    exit;
}

$posicoes = ['Goleiro', 'Fixo', 'Lateral Direito', 'Lateral Esquerdo', 'Meia', 'Pivô'];
$positions = array_fill_keys($posicoes, []);
foreach ($data as $p) {
    if ($p['nome'] === '' || $p['posicao'] === '' || $p['pedra'] === '') continue;
    $positions[$p['posicao']][] = $p;
}
foreach ($positions as &$list) {
    usort($list, fn($a, $b) => $a['pedra'] <=> $b['pedra']);
}
unset($list);

$teams = [[], [], []];
$capacity = [6, 6, 5];
$extras = [];

$add = function($idx, $player) use (&$teams, $capacity, &$extras) {
    if (count($teams[$idx]) < $capacity[$idx]) {
        $teams[$idx][] = $player;
    } else {
        $extras[] = $player;
    }
};

// goleiros apenas para os times 1 e 2
for ($i = 0; $i < 2; $i++) {
    if (!empty($positions['Goleiro'])) {
        $player = array_shift($positions['Goleiro']);
        $add($i, $player);
    }
}

// distribuir demais posições de forma rotativa respeitando capacidades
$order = ['Fixo', 'Lateral Direito', 'Lateral Esquerdo', 'Meia', 'Pivô'];
foreach ($order as $pos) {
    $t = 0;
    while (!empty($positions[$pos])) {
        $player = array_shift($positions[$pos]);
        $add($t, $player);
        $t = ($t + 1) % 3;
    }
}

// qualquer sobra
foreach ($positions as $pos => $list) {
    foreach ($list as $p) {
        $extras[] = $p;
    }
}

$teams[2] = array_merge($teams[2], $extras);

echo json_encode(['times' => $teams]);
