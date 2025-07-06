<?php
// Exemplo simples de inicio via WhatsApp Web usando Chrome em modo automatizado.
// Requer o pacote facebook/webdriver e o chromedriver instalado no servidor.
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/Bot.php';
require __DIR__ . '/userDatabase.php';

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\WebDriverBy;

$bot = new Bot('getUserName');
$host = 'http://localhost:9515'; // endereco do chromedriver
$driver = RemoteWebDriver::create($host, DesiredCapabilities::chrome());
$driver->get('https://web.whatsapp.com');

echo "Escaneie o QR Code e aguarde a conexão...\n";
// Aguardamos manualmente o usuário confirmar
sleep(15);

while (true) {
    // Exemplo simplificado que verifica novas mensagens em um contato fixo.
    // A implementação real exigiria manipular o DOM para ler e responder.
    // ...
    sleep(5);
}
?>
