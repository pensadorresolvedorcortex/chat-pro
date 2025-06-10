<?php
require_once __DIR__.'/Bot.php';
require_once __DIR__.'/userDatabase.php';

$bot = new Bot('getName');
$userId = 'usuario-demo';

echo $bot->handleMessage($userId, '') . PHP_EOL;

while (($line = fgets(STDIN)) !== false) {
    $reply = $bot->handleMessage($userId, $line);
    echo $reply . PHP_EOL;
    if (strpos($reply, 'Atendimento finalizado.') !== false) {
        break;
    }
}
