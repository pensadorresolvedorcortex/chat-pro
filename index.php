<?php
$started = false;
$pid = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // start the WhatsApp Web bot in the background
    // output redirected to wweb.log
    $cmd = 'node wweb-bot.mjs > wweb.log 2>&1 & echo $!';
    $pid = shell_exec($cmd);
    $started = true;
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
        <p>Bot iniciado. Verifique o arquivo <code>wweb.log</code> ou o terminal para escanear o QR Code.</p>
        <p>PID: <?php echo htmlspecialchars(trim($pid)); ?></p>
    <?php endif; ?>
</body>
</html>
