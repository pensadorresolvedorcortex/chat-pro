<?php
header('Content-Type: application/json');
if (!file_exists('jogadores.json')) {
    echo json_encode([]);
    exit;
}
$data = json_decode(file_get_contents('jogadores.json'), true);
if (!is_array($data)) $data = [];
echo json_encode($data);
