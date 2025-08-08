<?php
header('Content-Type: application/json');
$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data)) {
    echo json_encode(['status' => 'error', 'message' => 'Dados inválidos']);
    exit;
}
file_put_contents('jogadores.json', json_encode($data, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
echo json_encode(['status' => 'ok']);
