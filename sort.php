<?php
header('Content-Type: application/json');
$data = json_decode(file_get_contents('php://input'), true);
if(!is_array($data) || count($data) != 17){
    echo json_encode(['error'=>'Dados inválidos']);
    exit;
}

$positions = [
  'Goleiro'=>[], 'Fixo'=>[], 'LD'=>[], 'LE'=>[], 'Meia'=>[], 'Pivô'=>[]
];
foreach($data as $p){
    if($p['nome']==='' || $p['posicao']==='' || $p['pedra']==='') continue;
    $positions[$p['posicao']][] = $p;
}
foreach($positions as &$list){
    usort($list,function($a,$b){return $a['pedra'] <=> $b['pedra'];});
}
$teams=[[],[],[]];
// goleiros
for($i=0;$i<2;$i++){
    if(!empty($positions['Goleiro']))
        $teams[$i][] = array_shift($positions['Goleiro']);
}
// outras posicoes
foreach(['Fixo','LD','LE'] as $pos){
    for($t=0;$t<3;$t++){
        if(!empty($positions[$pos]))
            $teams[$t][] = array_shift($positions[$pos]);
    }
}
$mp = array_merge($positions['Meia'],$positions['Pivô']);
usort($mp,function($a,$b){return $a['pedra'] <=> $b['pedra'];});
for($t=0;$t<3;$t++){
    if(!empty($mp))
        $teams[$t][] = array_shift($mp);
}
$extras = array_merge($positions['Goleiro'],$positions['Fixo'],$positions['LD'],$positions['LE'],$positions['Meia'],$positions['Pivô'],$mp);

echo json_encode(['times'=>$teams,'extras'=>$extras]);
