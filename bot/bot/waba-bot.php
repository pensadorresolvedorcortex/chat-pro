<?php
// Exemplo simples utilizando a API do WhatsApp Business Cloud.
// Não é necessário usar plataformas de terceiros como Blip ou Zendesk.
// Defina as variáveis de ambiente WABA_TOKEN e WABA_PHONE_ID com seus dados.

require __DIR__ . '/Bot.php';
require __DIR__ . '/userDatabase.php';

$token = getenv('WABA_TOKEN');
$phoneId = getenv('WABA_PHONE_ID');

if (!$token || !$phoneId) {
    fwrite(STDERR, "Defina WABA_TOKEN e WABA_PHONE_ID antes de executar.\n");
    exit(1);
}

function sendMessage($phone, $text, $token, $phoneId) {
    $url = "https://graph.facebook.com/v17.0/{$phoneId}/messages";
    $payload = [
        'messaging_product' => 'whatsapp',
        'to' => $phone,
        'type' => 'text',
        'text' => ['body' => $text]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer {$token}",
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    if ($result === false) {
        fwrite(STDERR, "Erro ao enviar mensagem: " . curl_error($ch) . "\n");
    }
    curl_close($ch);
}

$bot = new Bot('getUserName');
$userId = 'cli';
$phone = readline("Telefone do destinatário (com código do país): ");

$reply = $bot->handleMessage($userId, '');
sendMessage($phone, $reply, $token, $phoneId);
print "Bot: {$reply}\n";

while (true) {
    $line = readline("Você: ");
    if ($line === false) {
        break;
    }
    $reply = $bot->handleMessage($userId, $line);
    sendMessage($phone, $reply, $token, $phoneId);
    print "Bot: {$reply}\n";
    if ($reply === 'Atendimento finalizado.') {
        break;
    }
}
?>
