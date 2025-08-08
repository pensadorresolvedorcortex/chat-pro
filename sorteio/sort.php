<?php
header('Content-Type: application/json');
$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data) || count($data) !== 17) {
    echo json_encode(['error' => 'Dados inválidos']);
    exit;
}


$goleiros = [];
$jogadores = [];
foreach ($data as $p) {
    if ($p['nome'] === '' || $p['posicao'] === '' || $p['pedra'] === '') continue;
    if ($p['posicao'] === 'Goleiro') {
        $goleiros[] = $p;
    } else {
        $jogadores[] = $p;
    }
}

$teams = [[], [], []];
$capacity = [6, 6, 5];

for ($i = 0; $i < 2; $i++) {
    if (!empty($goleiros)) {
        $teams[$i][] = array_shift($goleiros);
    }
}

$jogadores = array_merge($jogadores, $goleiros); // caso sobre goleiro

$porPedra = [1=>[],2=>[],3=>[]];
foreach ($jogadores as $j) {
    $porPedra[$j['pedra']][] = $j;
}

$t = 0;
for ($pedra=1; $pedra<=3; $pedra++) {
    if (empty($porPedra[$pedra])) continue;
    shuffle($porPedra[$pedra]);
    foreach ($porPedra[$pedra] as $j) {
        while ($t < 2 && count($teams[$t]) >= $capacity[$t]) {
            $t = ($t + 1) % 2;
        }
        if (count($teams[0]) < $capacity[0] || count($teams[1]) < $capacity[1]) {
            if (count($teams[$t]) < $capacity[$t]) {
                $teams[$t][] = $j;
            } else {
                $teams[1-$t][] = $j;
            }
        } else {
            $teams[2][] = $j;
        }
        $t = ($t + 1) % 2;
    }
}

while (count($teams[2]) < $capacity[2]) {
    foreach ([0,1] as $idx) {
        if (count($teams[$idx]) > $capacity[$idx]-1 && count($teams[2]) < $capacity[2]) {
            $teams[2][] = array_pop($teams[$idx]);
        }
    }
}

echo json_encode(['times' => $teams]);
