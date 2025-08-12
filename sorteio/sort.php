<?php
header('Content-Type: application/json');
$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data) || count($data) === 0) {
    echo json_encode(['error' => 'Dados inválidos']);
    exit;
}


$goleiros = [];
$linha = [];
foreach ($data as $p) {
    if (trim($p['nome']) === '') continue;
    $p['posicao'] = isset($p['posicao']) && $p['posicao'] !== '' ? $p['posicao'] : 'Linha';
    $p['pedra'] = isset($p['pedra']) && $p['pedra'] !== '' ? (int)$p['pedra'] : 4;
    if ($p['posicao'] === 'Goleiro') {
        $goleiros[] = $p;
    } else {
        $linha[] = $p;
    }
}

$teams = [[], [], []];
// first two teams hold 6 players (including goalkeepers) and the third holds 5
$capacity = [6, 6, 5];

shuffle($goleiros);
if (isset($goleiros[0])) $teams[0][] = $goleiros[0];
if (isset($goleiros[1])) $teams[1][] = $goleiros[1];

// goleiros extra viram jogadores de linha
for ($i = 2; $i < count($goleiros); $i++) {
    $goleiros[$i]['posicao'] = 'Linha';
    $linha[] = $goleiros[$i];
}

$porPedra = [1=>[],2=>[],3=>[],4=>[]];
foreach ($linha as $j) {
    $porPedra[$j['pedra']][] = $j;
}

$t = 0;
for ($pedra = 1; $pedra <= 4; $pedra++) {
    if (empty($porPedra[$pedra])) continue;
    shuffle($porPedra[$pedra]);
    foreach ($porPedra[$pedra] as $j) {
        $attempts = 0;
        while ($attempts < 3 && count($teams[$t]) >= $capacity[$t]) {
            $t = ($t + 1) % 3;
            $attempts++;
        }
        if ($attempts === 3) break 2;
        $teams[$t][] = $j;
        $t = ($t + 1) % 3;
    }
}

echo json_encode(['times' => $teams]);
