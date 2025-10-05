<?php
declare(strict_types=1);
?>
<html>
<body style="font-family: Arial, sans-serif; color: #111;">
    <h1><?php esc_html_e('Reserva expirada', 'juntaplay'); ?></h1>
    <p><?php esc_html_e('As seguintes cotas voltaram para o estoque por falta de pagamento:', 'juntaplay'); ?></p>
    <ul>
        <?php foreach ($quotas as $quota) : ?>
            <li><?php echo esc_html($quota); ?></li>
        <?php endforeach; ?>
    </ul>
</body>
</html>
