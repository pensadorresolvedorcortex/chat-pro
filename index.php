<?php
// Carrega configuracoes existentes ou valores padrao
$cfgFile = __DIR__ . '/config.json';
$cfg = [
    'WHATSAPP_TOKEN' => '',
    'WHATSAPP_PHONE_ID' => '',
    'VERIFY_TOKEN' => '',
    'PORT' => 3000
];
if (file_exists($cfgFile)) {
    $data = json_decode(file_get_contents($cfgFile), true);
    if (is_array($data)) {
        $cfg = array_merge($cfg, $data);
    }
}
$status = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cfg['WHATSAPP_TOKEN'] = $_POST['token'] ?? '';
    $cfg['WHATSAPP_PHONE_ID'] = $_POST['phone'] ?? '';
    $cfg['VERIFY_TOKEN'] = $_POST['verify'] ?? '';
    file_put_contents($cfgFile, json_encode($cfg, JSON_PRETTY_PRINT));
    $status = 'Configurações salvas.';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Configuração do Bot - Privilége</title>
<style>
body { font-family: Arial, sans-serif; margin: 2em; }
label { display: block; margin-top: 1em; }
button { margin: 0.5em 0; }
.submenu { margin-left: 1em; display: none; }
</style>
</head>
<body>
<h1>Configuração do WhatsApp Business</h1>
<form method="post">
    <label>Token:<br><input type="text" name="token" value="<?php echo htmlspecialchars($cfg['WHATSAPP_TOKEN']); ?>" required></label>
    <label>ID do Telefone:<br><input type="text" name="phone" value="<?php echo htmlspecialchars($cfg['WHATSAPP_PHONE_ID']); ?>" required></label>
    <label>Verify Token:<br><input type="text" name="verify" value="<?php echo htmlspecialchars($cfg['VERIFY_TOKEN']); ?>" required></label>
    <button type="submit">Salvar</button>
</form>
<p><?php echo $status; ?></p>

<h2>Menu de Soluções</h2>
<ul id="menu">
    <li><button data-target="ecommerce">E-commerce</button></li>
    <li><button data-target="institucional">Site Institucional</button></li>
    <li><button data-target="landing">Landing Page</button></li>
    <li><button data-target="redes">Gestão de Redes Sociais</button></li>
    <li><button data-target="hospedagem">Hospedagem de Sites</button></li>
    <li><button data-target="outras">Outras Soluções em Marketing</button></li>
</ul>
<div id="ecommerce" class="submenu">
    <p>Podemos criar ou otimizar o seu e-commerce:</p>
    <ul>
        <li>Criar novo e-commerce</li>
        <li>Melhorar e-commerce existente</li>
        <li>Integrar com plataformas de pagamento</li>
    </ul>
</div>
<div id="institucional" class="submenu">
    <p>Sites institucionais sob medida para sua empresa.</p>
</div>
<div id="landing" class="submenu">
    <p>Landing pages otimizadas para conversão.</p>
</div>
<div id="redes" class="submenu">
    <p>Gestão estratégica das suas redes sociais.</p>
</div>
<div id="hospedagem" class="submenu">
    <p>Hospedagem segura e rápida para seu site.</p>
</div>
<div id="outras" class="submenu">
    <p>Conte-nos suas necessidades que ajudamos com outras soluções de marketing.</p>
</div>

<script>
document.querySelectorAll('#menu button').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.submenu').forEach(div => div.style.display = 'none');
        const target = document.getElementById(btn.getAttribute('data-target'));
        if (target) target.style.display = 'block';
    });
});
</script>
</body>
</html>
