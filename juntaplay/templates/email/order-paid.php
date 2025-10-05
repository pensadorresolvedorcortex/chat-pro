<?php
declare(strict_types=1);
?>
<html>
<body style="font-family: Arial, sans-serif; color: #111;">
    <h1><?php esc_html_e('Pagamento confirmado!', 'juntaplay'); ?></h1>
    <p><?php esc_html_e('Obrigado pela sua compra. As cotas abaixo foram confirmadas:', 'juntaplay'); ?></p>
    <ul>
        <?php foreach ($quotas as $quota) : ?>
            <li><?php echo esc_html($quota); ?></li>
        <?php endforeach; ?>
    </ul>
</body>
</html>
