<?php
// Simple webhook endpoint for WhatsApp Business Cloud API in PHP
// Load configuration
$cfgFile = __DIR__ . '/config.json';
$cfg = [
    'WHATSAPP_TOKEN' => '',
    'WHATSAPP_PHONE_ID' => '',
    'VERIFY_TOKEN' => ''
];
if (file_exists($cfgFile)) {
    $data = json_decode(file_get_contents($cfgFile), true);
    if (is_array($data)) {
        $cfg = array_merge($cfg, $data);
    }
}
require_once __DIR__ . '/Bot.php';
require_once __DIR__ . '/userDatabase.php';
$bot = new Bot('getName');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $mode = $_GET['hub_mode'] ?? $_GET['hub.mode'] ?? '';
    $token = $_GET['hub_verify_token'] ?? $_GET['hub.verify_token'] ?? '';
    $challenge = $_GET['hub_challenge'] ?? $_GET['hub.challenge'] ?? '';
    if ($mode === 'subscribe' && $token === $cfg['VERIFY_TOKEN']) {
        http_response_code(200);
        echo $challenge;
    } else {
        http_response_code(403);
    }
    exit;
}

// Handle incoming message
$input = file_get_contents('php://input');
$data = json_decode($input, true);
$entry = $data['entry'][0] ?? null;
$changes = $entry['changes'][0] ?? null;
$value = $changes['value'] ?? null;
$message = $value['messages'][0] ?? null;
if ($message) {
    $from = $message['from'];
    $text = $message['text']['body'] ?? '';
    $reply = $bot->handleMessage($from, $text);
    sendMessage($from, $reply, $cfg);
}
http_response_code(200);

function sendMessage($to, $text, $cfg) {
    $url = "https://graph.facebook.com/v18.0/{$cfg['WHATSAPP_PHONE_ID']}/messages";
    $payload = json_encode([
        'messaging_product' => 'whatsapp',
        'to' => $to,
        'text' => ['body' => $text]
    ]);
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $cfg['WHATSAPP_TOKEN'],
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_exec($ch);
    curl_close($ch);
}
?>
