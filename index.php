<?php
$path = __DIR__ . '/config.json';
$config = [
    'whatsapp_token' => '',
    'whatsapp_phone_id' => '',
    'verify_token' => ''
];
if (file_exists($path)) {
    $data = json_decode(file_get_contents($path), true);
    if (is_array($data)) {
        $config = array_merge($config, $data);
    }
}
$success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $config['whatsapp_token'] = $_POST['token'] ?? '';
    $config['whatsapp_phone_id'] = $_POST['phone_id'] ?? '';
    $config['verify_token'] = $_POST['verify_token'] ?? '';
    file_put_contents($path, json_encode($config, JSON_PRETTY_PRINT));
    $success = true;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Configurar Bot</title>
</head>
<body>
<h1>Configuração do WhatsApp Business</h1>
<?php if ($success): ?>
<p><strong>Configurações salvas com sucesso.</strong></p>
<?php endif; ?>
<form method="post">
    <label>Token:<br>
        <input type="text" name="token" value="<?php echo htmlspecialchars($config['whatsapp_token']); ?>" required>
    </label><br><br>
    <label>ID do Telefone:<br>
        <input type="text" name="phone_id" value="<?php echo htmlspecialchars($config['whatsapp_phone_id']); ?>" required>
    </label><br><br>
    <label>Verify Token:<br>
        <input type="text" name="verify_token" value="<?php echo htmlspecialchars($config['verify_token']); ?>" required>
    </label><br><br>
    <button type="submit">Salvar</button>
</form>
</body>
</html>
