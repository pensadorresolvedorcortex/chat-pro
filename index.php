<?php
$started = false;
$pid = '';
$error = '';
$qrImage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/composer_helper.php';

    if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
        ob_start();
        composer_install(false);
        ob_end_flush();
    }

    if (file_exists(__DIR__ . '/vendor/autoload.php')) {
        $cmd = 'php wweb-bot.php > wweb.log 2>&1 & echo $!';
        if (!run_command($cmd, $out, $code)) {
            $error = 'Funções de execução de comandos desabilitadas. Inicie o bot via linha de comando: php wweb-bot.php';
        } else {
            $pid = $out[0] ?? '';
        }
        if ($pid) {
            $started = true;
            $qrPath = __DIR__ . '/qr.png';
            $elapsed = 0;
            while (!file_exists($qrPath) && $elapsed < 20) {
                sleep(1);
                $elapsed++;
            }
            if (file_exists($qrPath)) {
                $qrImage = 'qr.png?' . time();
            } else {
                $error = 'QR Code não gerado. Verifique o log.';
            }
        } else {
            $error = 'Falha ao iniciar o bot. Verifique o arquivo wweb.log.';
        }
    } else {
        $error = 'Dependências ausentes e falha ao executar composer.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Configurar Bot via WhatsApp Web</title>
</head>
<body>
    <h1>Configuração do Bot</h1>
    <p>Use o botão abaixo para iniciar o bot que se conecta ao WhatsApp Web.</p>
    <form method="post">
        <button type="submit">Iniciar Bot</button>
    </form>
    <?php if ($started): ?>
        <?php if ($qrImage): ?>
            <p>Escaneie o QR Code abaixo para conectar:</p>
            <img src="<?php echo htmlspecialchars($qrImage); ?>" alt="QR Code">
            <p>PID: <?php echo htmlspecialchars(trim($pid)); ?></p>
        <?php else: ?>
            <p>Bot iniciado, mas o QR Code não pôde ser gerado.</p>
            <p>PID: <?php echo htmlspecialchars(trim($pid)); ?></p>
        <?php endif; ?>
    <?php elseif ($error): ?>
        <p style="color:red;">Erro: <?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>
</body>
</html>
