<?php
// Exemplo simples de inicio via WhatsApp Web usando Chrome em modo automatizado.
// Requer o pacote facebook/webdriver e o chromedriver instalado no servidor.
// Pode ser executado via CLI ou acessado diretamente pelo navegador.
$cli = php_sapi_name() === 'cli';
if (!$cli) {
    // quando acessado pelo navegador, enviamos texto puro para acompanhar o log
    header('Content-Type: text/plain; charset=UTF-8');
    @ob_flush();
    @flush();
}

function report_error(string $msg, bool $cli)
{
    if ($cli) {
        fwrite(STDERR, $msg . "\n");
    } else {
        echo $msg . "\n";
        @ob_flush();
        @flush();
    }
}

require_once __DIR__ . '/composer_helper.php';

if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
    composer_install($cli);
}

if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
    report_error("Dependências ausentes após tentativa de instalar.", $cli);
    exit(1);
}

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/Bot.php';
require __DIR__ . '/userDatabase.php';

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;

function chromedriver_running(string $host): bool {
    $parts = parse_url($host);
    if (empty($parts['host']) || empty($parts['port'])) {
        return false;
    }
    $fp = @fsockopen($parts['host'], $parts['port'], $errno, $errstr, 2);
    if ($fp) {
        fclose($fp);
        return true;
    }
    return false;
}

$bot = new Bot('getUserName');
$host = 'http://localhost:9515'; // endereco do chromedriver
$options = new ChromeOptions();
$options->addArguments(['--headless', '--no-sandbox']);
$capabilities = DesiredCapabilities::chrome();
$capabilities->setCapability(ChromeOptions::CAPABILITY_W3C, $options);

$driverPid = null;
if (!chromedriver_running($host)) {
    $localDriver = __DIR__ . '/bin/chromedriver';
    if (is_file($localDriver) && is_executable($localDriver)) {
        $cmd = escapeshellarg($localDriver) . ' --port=9515 > /dev/null 2>&1 & echo $!';
        run_command($cmd, $out, $status);
        $driverPid = trim($out[0] ?? '');
        sleep(2); // aguarda inicializar
        if (!chromedriver_running($host)) {
            report_error("Não foi possível iniciar o chromedriver em $localDriver", $cli);
            exit(1);
        }
    } else {
        report_error("Chromedriver não encontrado em $localDriver. Execute scripts/install_chromedriver.sh", $cli);
        exit(1);
    }
}

$driver = null;
try {
    $driver = RemoteWebDriver::create($host, $capabilities);
    $driver->get('https://web.whatsapp.com');
} catch (Exception $e) {
    report_error("Falha ao iniciar o navegador: {$e->getMessage()}", $cli);
    if ($driverPid && function_exists('posix_kill')) posix_kill((int)$driverPid, SIGTERM);
    exit(1);
}

// aguarda o carregamento do QR Code e salva a captura de tela
try {
    $driver->wait(20, 1000)->until(
        WebDriverExpectedCondition::presenceOfElementLocated(
            WebDriverBy::cssSelector('canvas[aria-label="Scan me!"]')
        )
    );
    $screenshot = $driver->takeScreenshot();
    $qrFile = __DIR__ . '/qr.png';
    file_put_contents($qrFile, $screenshot);
    echo $qrFile . PHP_EOL;
} catch (Exception $e) {
    report_error("Não foi possível capturar o QR Code: {$e->getMessage()}", $cli);
    $driver->quit();
    exit(1);
}

echo "Escaneie o QR Code e aguarde a conexão...\n";
// Aguardamos manualmente o usuário confirmar
sleep(15);

while (true) {
    // Exemplo simplificado que verifica novas mensagens em um contato fixo.
    // A implementação real exigiria manipular o DOM para ler e responder.
    // ...
    sleep(5);
}

$driver->quit();
if ($driverPid && function_exists('posix_kill')) {
    posix_kill((int)$driverPid, SIGTERM);
}
?>
