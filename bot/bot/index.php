<?php
$started = false;
$pid = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
        $error = 'Dependências ausentes. Execute `composer install` no servidor.';
    } else {
        $cmd = 'php wweb-bot.php > wweb.log 2>&1 & echo $!';
        $pid = shell_exec($cmd);
        if ($pid) {
            $started = true;
        } else {
            $error = 'Falha ao iniciar o bot. Verifique o arquivo wweb.log.';
        }
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
        <p>Bot iniciado. Verifique o arquivo <code>wweb.log</code> ou o terminal para escanear o QR Code.</p>
        <p>PID: <?php echo htmlspecialchars(trim($pid)); ?></p>
    <?php elseif ($error): ?>
        <p style="color:red;">Erro: <?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>
</body>
</html>
