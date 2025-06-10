<?php
// Exemplo simples de inicio via WhatsApp Web usando Chrome em modo automatizado.
// Requer o pacote facebook/webdriver e o chromedriver instalado no servidor.
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/Bot.php';
require __DIR__ . '/userDatabase.php';

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;

$bot = new Bot('getUserName');
$host = 'http://localhost:9515'; // endereco do chromedriver
$options = new ChromeOptions();
$options->addArguments(['--headless', '--no-sandbox']);
$capabilities = DesiredCapabilities::chrome();
$capabilities->setCapability(ChromeOptions::CAPABILITY_W3C, $options);
$driver = RemoteWebDriver::create($host, $capabilities);
$driver->get('https://web.whatsapp.com');

// aguarda o carregamento do QR Code e salva a captura de tela
$driver->wait(20, 1000)->until(
    WebDriverExpectedCondition::presenceOfElementLocated(
        WebDriverBy::cssSelector('canvas[aria-label="Scan me!"]')
    )
);
$screenshot = $driver->takeScreenshot();
$qrFile = __DIR__ . '/qr.png';
file_put_contents($qrFile, $screenshot);
echo $qrFile . PHP_EOL;

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
