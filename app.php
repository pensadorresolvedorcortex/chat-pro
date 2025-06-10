<?php
require __DIR__ . '/Bot.php';
require __DIR__ . '/userDatabase.php';

$bot = new Bot('getUserName');
$userId = 'cli';

echo "Digite 'sair' para encerrar ou 'menu' para voltar ao início.\n";
$stdin = fopen('php://stdin', 'r');
$reply = $bot->handleMessage($userId, '');
echo "$reply\n";
while (($line = fgets($stdin)) !== false) {
    $response = trim($line);
    $reply = $bot->handleMessage($userId, $response);
    echo "$reply\n";
    if ($reply === 'Atendimento finalizado.') {
        break;
    }
}
?>
